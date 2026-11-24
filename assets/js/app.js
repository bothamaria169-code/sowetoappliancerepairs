/* Soweto Appliance Repairs — public site and admin behaviour. */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var calm = matchMedia('(prefers-reduced-motion: reduce)').matches;

  function toast(msg) {
    var t = $('#toast'); if (!t) return;
    t.textContent = msg; t.classList.add('show');
    clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('show'); }, 2800);
  }
  function post(path, data) {
    var body = new FormData();
    Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
    body.append('_csrf', SAR.csrf);
    return fetch(SAR.url + '/' + path, { method: 'POST', body: body, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  window.sarPost = post; window.sarToast = toast;

  // Theme
  if ($('#themeBtn')) $('#themeBtn').onclick = function () {
    var r = document.documentElement, dark = r.dataset.theme ? r.dataset.theme === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
    r.dataset.theme = dark ? 'light' : 'dark';
    try { localStorage.setItem('sar-theme', r.dataset.theme); } catch (e) {}
  };

  // Menus
  var burger = $('#burger'), nav = $('#nav');
  if (burger) burger.onclick = function () { var o = nav.classList.toggle('open'); burger.setAttribute('aria-expanded', o); burger.innerHTML = '<i class="fa-solid fa-' + (o ? 'xmark' : 'bars') + '"></i>'; };
  var side = $('#admSide'), scrim = $('#admScrim');
  if ($('#admBurger')) $('#admBurger').onclick = function () { side.classList.add('open'); scrim.classList.add('show'); };
  if (scrim) scrim.onclick = function () { side.classList.remove('open'); scrim.classList.remove('show'); };

  $$('form[data-confirm]').forEach(function (f) { f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); }); });
  $$('form[data-busy]').forEach(function (f) { f.addEventListener('submit', function () { var b = f.querySelector('[type=submit], button:not([type])'); if (b) { b.disabled = true; b.classList.add('is-busy'); } }); });
  $$('[data-toggle-pw]').forEach(function (b) { b.onclick = function () { var i = document.getElementById(b.dataset.togglePw); i.type = i.type === 'password' ? 'text' : 'password'; }; });

  // Count taps on Call and WhatsApp (for the admin dashboard, Analytics and the pixel)
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-tap]'); if (!a) return;
    var d = new FormData(); d.append('kind', a.dataset.tap); d.append('path', location.pathname);
    if (navigator.sendBeacon) navigator.sendBeacon(SAR.url + '/ajax/tap.php', d);
    if (window.gtag) {
      gtag('event', a.dataset.tap === 'whatsapp' ? 'whatsapp_click' : 'call_click', { method: a.dataset.tap });
      // A WhatsApp from the thank-you page is the same customer as the booking: not a second lead for Google Ads
      if (a.dataset.tap === 'whatsapp' && SAR.adsWa && !/\/thank-you$/.test(location.pathname)) gtag('event', 'conversion', { send_to: SAR.adsWa });
    }
    if (window.fbq) fbq('track', 'Contact');
  });

  // LCD: change what a display shows
  function setLcd(svg, text) {
    if (!svg) return;
    var cells = $$('g', svg), s = String(text);
    while (s.length < cells.length) s = ' ' + s;
    s = s.slice(-cells.length);
    cells.forEach(function (g, i) {
      var on = SAR.lcd[s[i]] || '';
      $$('polygon', g).forEach(function (p) { p.setAttribute('class', on.indexOf(p.dataset.s) > -1 ? 'on' : 'off'); });
    });
  }

  // Hero display: a fault appears, then it is fixed
  var hero = $('#heroMeter');
  if (hero) {
    var svg = $('.lcd', hero), lab = $('#heroLabel'), unit = $('#heroUnit'), note = $('#heroNote');
    var scenes = [
      { l: 'Fridge', u: '°C', from: 28, to: 4, a: 'Too warm. Food at risk.', b: 'Cold again.' },
      { l: 'Washing machine', u: '', code: '5E', b1: 'donE', a: 'Will not drain.', b: 'Drained and spinning.' },
      { l: 'Oven', u: '°C', from: 20, to: 180, a: 'Not heating.', b: 'Ready to bake.' },
      { l: 'Geyser', u: '°C', from: 15, to: 60, a: 'No hot water.', b: 'Hot water is back.' }
    ], n = 0;
    var play = function () {
      var s = scenes[n++ % scenes.length];
      lab.textContent = s.l; unit.textContent = s.u; note.textContent = s.a;
      setLcd(svg, s.code || s.from);
      setTimeout(function () {
        if (s.code) { setLcd(svg, s.b1); note.textContent = s.b; return; }
        var v = s.from, step = (s.to - s.from) / 22, i = 0;
        var h = setInterval(function () { v += step; setLcd(svg, Math.round(++i >= 22 ? s.to : v)); if (i >= 22) { clearInterval(h); note.textContent = s.b; } }, 60);
      }, 1500);
    };
    if (!calm) { setTimeout(play, 600); setInterval(function () { if (!document.hidden) play(); }, 5200); }
  }

  // Fault finder
  var fd = $('#finderData');
  if (fd) {
    var data = JSON.parse(fd.textContent), list = $('#finderList'), screen = $('#tester .lcd');
    var pick = function (app, f, btn) {
      $$('button', list).forEach(function (b) { b.classList.toggle('is-on', b === btn); });
      setLcd(screen, f.l);
      $('#tTitle').textContent = app.name + ': ' + f.s.toLowerCase();
      $('#tCauses').innerHTML = ''; f.c.forEach(function (c) { var li = document.createElement('li'); li.textContent = c; $('#tCauses').appendChild(li); });
      $('#tCheck').textContent = f.k; $('#tMore').href = f.u;
      $('#tWa').href = $('#tWa').dataset.base + '?text=' + encodeURIComponent('Hi, my ' + app.name.toLowerCase() + ' has a problem: ' + f.s.toLowerCase() + '. Can you come and look? I am in ');
    };
    var show = function (slug) {
      var app = data[slug]; list.innerHTML = '';
      app.faults.forEach(function (f, i) {
        var b = document.createElement('button'); b.type = 'button'; b.textContent = f.s; b.onclick = function () { pick(app, f, b); if (innerWidth < 960) $('#tester').scrollIntoView({ behavior: calm ? 'auto' : 'smooth', block: 'center' }); };
        list.appendChild(b); if (i === 0) pick(app, f, b);
      });
    };
    $$('.finder__apps button').forEach(function (b) { b.onclick = function () { $$('.finder__apps button').forEach(function (x) { x.setAttribute('aria-selected', x === b); }); show(b.dataset.app); }; });
    show('fridge-repairs');
  }

  // Suburb checker
  var aq = $('#areaQ');
  if (aq) {
    var out = $('#areaOut'), links = $$('.areas a');
    $('#areaForm').onsubmit = function (e) { e.preventDefault(); };
    aq.addEventListener('input', function () {
      var q = aq.value.trim().toLowerCase(), hits = [];
      links.forEach(function (a) { var h = q.length > 1 && a.dataset.sub.split('|').some(function (s) { return s.indexOf(q) === 0 || s.indexOf(' ' + q) > -1; }); a.classList.toggle('hit', h); a.classList.toggle('dim', q.length > 1 && !h); if (h) hits.push(a); });
      out.textContent = q.length < 2 ? '' : hits.length ? 'Yes, we come to you. See ' : 'Not on our list yet. WhatsApp us and we will tell you straight.';
      if (q.length > 1 && hits.length) { var l = document.createElement('a'); l.href = hits[0].href; l.textContent = hits[0].dataset.name; out.appendChild(l); }
    });
  }

  // Filters: gallery + error codes
  $$('[data-filter]').forEach(function (bar) {
    var items = $$(bar.dataset.filter);
    $$('button', bar).forEach(function (b) { b.onclick = function () { $$('button', bar).forEach(function (x) { x.classList.toggle('is-on', x === b); }); items.forEach(function (i) { i.hidden = !!b.dataset.k && i.dataset.k !== b.dataset.k; }); }; });
  });
  var cs = $('#codeSearch');
  if (cs) {
    var rows = $$('[data-code]'), none = $('#codeNone');
    var run = function () { var q = cs.value.trim().toLowerCase(), n = 0; rows.forEach(function (r) { var m = !q || r.dataset.code.indexOf(q) > -1; r.hidden = !m; if (m) n++; }); none.hidden = n > 0; };
    cs.addEventListener('input', run); cs.form.onsubmit = function (e) { e.preventDefault(); run(); }; run();
  }

  // Chat
  var chat = $('#chat');
  if (chat) {
    var log = $('#chatLog'), input = $('#chatText'), hist = [], fab = $('.fab');
    var open = function (o) { chat.hidden = !o; fab.hidden = o; fab.style.display = o ? 'none' : ''; if (o) input.focus(); };
    $('#chatOpen').onclick = function () { open(true); };
    $('#chatClose').onclick = function () { open(false); };
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !chat.hidden) open(false); });
    var add = function (cls, text) { var d = document.createElement('div'); d.className = 'msg msg--' + cls; d.textContent = text; log.appendChild(d); log.scrollTop = log.scrollHeight; return d; };
    $('#chatForm').onsubmit = function (e) {
      e.preventDefault();
      var t = input.value.trim(); if (!t) return;
      input.value = ''; add('me', t); hist.push({ r: 'user', t: t });
      var wait = add('bot', '…');
      post('ajax/chat.php', { history: JSON.stringify(hist.slice(-8)) }).then(function (r) {
        wait.textContent = r.reply || r.error; if (r.reply) hist.push({ r: 'bot', t: r.reply });
      }).catch(function () { wait.textContent = 'I could not connect. Please use WhatsApp below.'; }).then(function () { log.scrollTop = log.scrollHeight; });
    };
  }

  // Copy buttons (admin)
  $$('[data-copy]').forEach(function (b) { b.addEventListener('click', function () { navigator.clipboard.writeText(b.dataset.copy); toast('Copied'); }); });
})();
