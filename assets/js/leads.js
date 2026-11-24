/* Lead list and lead page (admin). */
(function () {
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var toast = window.sarToast, post = function (d) { return sarPost('ajax/lead.php', d); };

  // Show what the server saved
  function apply(id, r) {
    $$('[data-lead="' + id + '"] [data-tick]').forEach(function (i) { i.checked = !!r.ticks[i.dataset.tick]; });
    var colours = { 'new': 'tag-on', contacted: 'tag-user', booked: 'tag-google', visited: 'tag-admin', done: 'tag-on', lost: 'tag-off' };
    $$('[data-stage="' + id + '"]').forEach(function (t) { t.textContent = r.label; t.className = 'tag ' + (colours[r.status] || 'tag-user'); });
    if (r.counts) {
      var all = 0;
      Object.keys(r.counts).forEach(function (k) { all += r.counts[k]; var c = $('[data-count="' + k + '"]'); if (c) c.textContent = r.counts[k]; });
      var a = $('[data-count="all"]'); if (a) a.textContent = all;
    }
  }

  // Tick boxes
  document.addEventListener('change', function (e) {
    var i = e.target.closest('[data-tick]'); if (!i) return;
    var id = i.closest('[data-lead]').dataset.lead, on = i.checked, reason = '';
    if (on && i.dataset.tick === 'lost') {
      reason = prompt('Why is it not going ahead? (you can leave this empty)', '');
      if (reason === null) { i.checked = false; return; }
    }
    i.disabled = true;
    post({ op: 'tick', id: id, tick: i.dataset.tick, on: on ? '1' : '0', reason: reason }).then(function (r) {
      i.disabled = false;
      if (!r.ok) { i.checked = !on; toast(r.error || 'Not saved'); return; }
      apply(id, r); toast(on ? 'Ticked' : 'Unticked');
    }).catch(function () { i.disabled = false; i.checked = !on; toast('Not saved. Check your connection.'); });
  });

  // WhatsApp and Call buttons tick Contacted
  document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-touch]'); if (!a) return;
    post({ op: 'touch', id: a.dataset.id, how: a.dataset.touch }).then(function (r) { if (r.ok) { apply(a.dataset.id, r); toast('Ticked as contacted. Untick it if they did not answer.'); } }).catch(function () {});
  });

  // Select all
  var all = $('#pickAll');
  if (all) all.onchange = function () { $$('input[name="ids[]"]').forEach(function (c) { c.checked = all.checked; }); };

  // Plan a visit
  var f = $('#planForm'); if (!f) return;
  var dirty = false, boxes = ['subject', 'message', 'wa'].map(function (k) { return f.elements[k]; });
  boxes.forEach(function (b) { b.addEventListener('input', function () { dirty = true; }); });
  function fields() { return { id: f.dataset.id, key: f.elements.key.value, visit_date: f.elements.visit_date.value, visit_from: f.elements.visit_from.value, visit_to: f.elements.visit_to.value }; }
  function refill(force) {
    if (dirty && !force) { $('#stale').hidden = false; return; }
    var d = fields(); d.op = 'preview';
    post(d).then(function (r) { if (!r.ok) return; f.elements.subject.value = r.subject; f.elements.message.value = r.body; f.elements.wa.value = r.wa; dirty = false; $('#stale').hidden = true; });
  }
  ['visit_date', 'visit_from', 'visit_to'].forEach(function (k) { f.elements[k].addEventListener('change', function () { refill(false); }); });
  f.elements.key.addEventListener('change', function () { refill(true); });
  $('#refill').onclick = function (e) { e.preventDefault(); refill(true); };
  $$('[data-slot]', f).forEach(function (b) { b.onclick = function () { var s = b.dataset.slot.split('-'); f.elements.visit_from.value = s[0]; f.elements.visit_to.value = s[1]; refill(false); }; });
  $$('[data-pane]', f).forEach(function (a) { a.onclick = function (e) { e.preventDefault(); $$('[data-pane]', f).forEach(function (x) { x.classList.toggle('is-on', x === a); }); $$('[data-panes]', f).forEach(function (p) { p.hidden = p.dataset.panes !== a.dataset.pane; }); }; });

  f.addEventListener('submit', function (e) {
    var b = e.submitter;
    if (b && b.value === 'send' && !$('#stale').hidden && !confirm('You changed the date after editing the message. Is the date in the message correct?')) { e.preventDefault(); return; }
    // The browser must still see which button was pressed, so grey it out a moment later.
    if (b) setTimeout(function () { b.disabled = true; b.classList.add('is-busy'); }, 0);
  });
  $('#waSend').onclick = function () {
    var d = fields(); d.op = 'whatsapp'; d.wa = f.elements.wa.value;
    var w = window.open('', '_blank');
    post(d).then(function (r) {
      if (!r.ok) { if (w) w.close(); toast(r.error || 'Not saved'); return; }
      apply(f.dataset.id, r);
      if (w) { w.opener = null; w.location = r.url; } else { location.href = r.url; }
      toast('Saved. Press send in WhatsApp.');
    }).catch(function () { if (w) w.close(); toast('Not saved. Check your connection.'); });
  };
})();
