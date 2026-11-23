<?php
declare(strict_types=1);

/** Page shells, the LCD display, icons, photos and schema.org data. */

// ── Small pieces ──────────────────────────────────────────────────
/** Which of the 7 segments (a–g) each character lights. */
const LCD_MAP = [
    '0' => 'abcdef', '1' => 'bc', '2' => 'abdeg', '3' => 'abcdg', '4' => 'bcfg', '5' => 'acdfg', '6' => 'acdefg', '7' => 'abc', '8' => 'abcdefg', '9' => 'abcdfg',
    'A' => 'abcefg', 'b' => 'cdefg', 'B' => 'cdefg', 'C' => 'adef', 'c' => 'deg', 'd' => 'bcdeg', 'D' => 'bcdeg', 'E' => 'adefg', 'F' => 'aefg', 'f' => 'aefg', 'H' => 'bcefg', 'I' => 'bc',
    'L' => 'def', 'n' => 'ceg', 'N' => 'ceg', 'o' => 'cdeg', 'O' => 'abcdef', 'P' => 'abefg', 'r' => 'eg', 'R' => 'eg', 'S' => 'acdfg', 't' => 'defg', 'T' => 'defg', 'U' => 'bcdef', 'u' => 'cde', 'h' => 'cefg', '-' => 'g', ':' => '', ' ' => '', '°' => 'abfg',
];

/** A seven-segment display drawn in SVG. */
function lcd(string $text, string $class = '', string $label = ''): string
{
    $seg = [
        'a' => '4,1 20,1 17,5 7,5', 'b' => '21,2 21,20 17,17 17,6', 'c' => '21,22 21,40 17,36 17,25',
        'd' => '4,41 20,41 17,37 7,37', 'e' => '3,22 3,40 7,36 7,25', 'f' => '3,2 3,20 7,17 7,6', 'g' => '4,21 7,19 17,19 20,21 17,23 7,23',
    ];
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);
    $out = '';
    $shift = 0;
    foreach ($chars as $i => $c) {
        if ($c === ':') { $out .= '<rect class="on" x="' . ($i * 27 - $shift + 1) . '" y="11" width="5" height="5"/><rect class="on" x="' . ($i * 27 - $shift - 1) . '" y="26" width="5" height="5"/>'; $shift += 17; continue; }
        $on = LCD_MAP[$c] ?? '';
        $out .= '<g transform="translate(' . ($i * 27 - $shift) . ' 0) skewX(-4)">';
        foreach ($seg as $k => $pts) $out .= '<polygon class="' . (str_contains($on, $k) ? 'on' : 'off') . '" data-s="' . $k . '" points="' . $pts . '"/>';
        $out .= '</g>';
    }
    $w = count($chars) * 27 - $shift;
    return '<svg class="lcd ' . e($class) . '" viewBox="-4 -1 ' . ($w + 3) . ' 44" ' . ($label !== '' ? 'role="img" aria-label="' . e($label) . '"' : 'aria-hidden="true"') . '>' . $out . '</svg>';
}

/** Line icons drawn for this site (same stroke, same corner radius). */
function icon(string $name): string
{
    $p = [
        'fridge' => '<rect x="12" y="4" width="24" height="40" rx="4"/><path d="M12 19h24M17 10v4M17 25v8"/>',
        'washer' => '<rect x="8" y="5" width="32" height="38" rx="4"/><path d="M8 14h32M14 9.5h6"/><circle cx="24" cy="28" r="9"/><path d="M16 29c3-3 5 2 8 0s5-3 8-1"/><circle cx="34" cy="9.5" r="1"/>',
        'stove' => '<rect x="6" y="8" width="36" height="32" rx="4"/><circle cx="16" cy="18" r="5"/><circle cx="32" cy="18" r="5"/><circle cx="16" cy="31" r="4"/><circle cx="32" cy="31" r="5"/>',
        'oven' => '<rect x="7" y="5" width="34" height="38" rx="4"/><path d="M7 15h34M14 10h.01M20 10h.01M28 10h6"/><rect x="13" y="22" width="22" height="14" rx="2"/><path d="M14 19h20"/>',
        'microwave' => '<rect x="4" y="10" width="40" height="28" rx="4"/><rect x="9" y="16" width="22" height="16" rx="2"/><path d="M36 10v28M39.9 17h.2M39.9 23h.2M39.9 29h.2M10 38v3M38 38v3"/>',
        'geyser' => '<rect x="5" y="13" width="38" height="22" rx="11"/><path d="M14 35v7M34 35v7M14 13V7M34 13V7M20 24h8"/><circle cx="24" cy="24" r="5"/>',
    ];
    return '<svg class="ico" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/** <img> for one of our own photos, with a small version for phones. */
function photo(string $file, string $alt, string $class = '', string $sizes = '(max-width: 700px) 100vw, 520px', bool $eager = false): string
{
    static $dim = [];
    $path = BASE_DIR . '/assets/img/work/' . $file;
    $d = $dim[$file] ??= [@getimagesize($path . '.webp') ?: [1000, 1333], @getimagesize($path . '-sm.webp') ?: [520, 693]];
    $u = APP_URL . '/assets/img/work/' . $file;
    return '<img class="' . e($class) . '" src="' . $u . '.webp" srcset="' . $u . '-sm.webp ' . $d[1][0] . 'w, ' . $u . '.webp ' . $d[0][0] . 'w" sizes="' . e($sizes) . '" width="' . $d[0][0] . '" height="' . $d[0][1]
         . '" alt="' . e($alt) . '" ' . ($eager ? 'fetchpriority="high"' : 'loading="lazy"') . ' decoding="async">';
}

function logo(string $class = ''): string
{
    return '<a href="' . APP_URL . '/" class="logo ' . e($class) . '" aria-label="' . e(APP_NAME) . ' home"><img src="' . APP_URL . '/assets/img/mark.svg" width="44" height="44" alt=""><span>Soweto<b>Appliance Repairs</b></span></a>';
}

function asset(string $path): string
{
    return APP_URL . '/assets/' . $path . '?v=' . (string) @filemtime(BASE_DIR . '/assets/' . $path);
}

function waButton(string $text, string $label, string $class = 'btn btn-wa'): string
{
    return '<a class="' . $class . '" href="' . e(waLink($text)) . '" target="_blank" rel="noopener" data-tap="whatsapp"><i class="fa-brands fa-whatsapp"></i>' . e($label) . '</a>';
}

function callButton(string $class = 'btn btn-line'): string
{
    return '<a class="' . $class . '" href="tel:' . e(telIntl()) . '" data-tap="call"><i class="fa-solid fa-phone"></i>' . e(biz('biz_phone')) . '</a>';
}

// ── Schema.org ────────────────────────────────────────────────────
function businessSchema(): array
{
    $same = array_values(array_filter([biz('biz_facebook'), biz('biz_instagram'), biz('biz_gbp')]));
    $offers = [];
    foreach (services() as $slug => $s) $offers[] = ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $s['name'], 'url' => APP_URL . '/' . $slug]];
    return array_filter([
        '@context' => 'https://schema.org', '@type' => ['LocalBusiness', 'HomeAndConstructionBusiness'], '@id' => APP_URL . '/#business',
        'name' => APP_NAME, 'url' => APP_URL . '/', 'telephone' => telIntl(), 'email' => biz('biz_email'),
        'image' => APP_URL . '/assets/img/og.jpg', 'logo' => APP_URL . '/assets/img/mark-512.png',
        'description' => APP_TAGLINE, 'priceRange' => 'Call-out R' . callout() . ', free with repair', 'currenciesAccepted' => 'ZAR',
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Soweto', 'addressRegion' => 'Gauteng', 'addressCountry' => 'ZA'],
        'openingHoursSpecification' => ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']],
        'areaServed' => array_map(fn($a) => ['@type' => 'City', 'name' => $a[0]], array_values(areas())),
        'hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => 'Appliance repairs', 'itemListElement' => $offers],
        'sameAs' => $same,
    ]);
}

function faqSchema(array $faqs): array
{
    return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(
        fn($f) => ['@type' => 'Question', 'name' => $f[0] ?? $f['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1] ?? $f['a']]], $faqs)];
}

/** @param array<int, array{0: string, 1: string}> $items [label, path] */
function crumbSchema(array $items): array
{
    $list = [];
    foreach (array_merge([['Home', '']], $items) as $i => [$label, $path]) $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $label, 'item' => APP_URL . '/' . $path];
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}

function crumbs(array $items): string
{
    $out = '<nav class="crumbs" aria-label="Breadcrumb"><a href="' . APP_URL . '/">Home</a>';
    $last = count($items) - 1;
    foreach ($items as $i => [$label, $path]) $out .= '<i aria-hidden="true">/</i>' . ($i === $last ? '<span aria-current="page">' . e($label) . '</span>' : '<a href="' . APP_URL . '/' . e($path) . '">' . e($label) . '</a>');
    return $out . '</nav>';
}

// ── Visits (no cookies, no personal data: one counter per page, day and source) ──
function countHit(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || isAdmin()) return;
    $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse/', $ua)) return;
    $ref = strtolower((string) parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST));
    $own = strtolower((string) parse_url(APP_URL, PHP_URL_HOST));
    $origin = match (true) {
        $ref === ''                       => 'Direct',
        str_contains($ref, $own)          => 'Internal',
        str_contains($ref, 'google.')     => 'Google',
        str_contains($ref, 'bing.')       => 'Bing',
        (bool) preg_match('/duckduckgo|yahoo|ecosia|yandex/', $ref) => 'Other search',
        (bool) preg_match('/facebook|fb\.|instagram|tiktok|whatsapp|t\.co|twitter|x\.com/', $ref) => 'Social',
        (bool) preg_match('/chatgpt|openai|perplexity|claude|gemini|copilot/', $ref) => 'AI assistants',
        default                           => 'Other sites',
    };
    $base = (string) parse_url(APP_URL, PHP_URL_PATH);
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base));
    try {
        getDB()->prepare('INSERT INTO page_hits (day, path, origin, n) VALUES (CURDATE(), ?, ?, 1) ON DUPLICATE KEY UPDATE n = n + 1')->execute([mb_substr($path ?: '/', 0, 190), $origin]);
    } catch (Throwable) {
    }
}

// ── <head> ────────────────────────────────────────────────────────
function pageHead(string $title, array $opt = []): void
{
    $css   = $opt['css'] ?? ['site.css'];
    $desc  = $opt['description'] ?? APP_TAGLINE;
    $canon = $opt['canonical'] ?? null;
    $image = $opt['image'] ?? APP_URL . '/assets/img/og.jpg';
    $ga    = get_setting('ga4_id');
    $ads   = get_setting('ads_id');
    $pixel = get_setting('meta_pixel_id');
    ?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= e($title) ?></title>
  <meta name="description" content="<?= e($desc) ?>">
  <meta name="robots" content="<?= !empty($opt['noindex']) ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1' ?>">
  <?php if ($canon): ?><link rel="canonical" href="<?= e($canon) ?>"><?php endif; ?>
  <?php if ($gsc = get_setting('gsc_verification')): ?><meta name="google-site-verification" content="<?= e($gsc) ?>"><?php endif; ?>
  <?php if ($bing = get_setting('bing_verification')): ?><meta name="msvalidate.01" content="<?= e($bing) ?>"><?php endif; ?>
  <meta name="theme-color" content="#0a1c3d">
  <meta name="geo.region" content="ZA-GP"><meta name="geo.placename" content="Soweto, Johannesburg">
  <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
  <meta property="og:locale" content="en_ZA">
  <meta property="og:title" content="<?= e($title) ?>">
  <meta property="og:description" content="<?= e($desc) ?>">
  <meta property="og:type" content="<?= e($opt['og_type'] ?? 'website') ?>">
  <meta property="og:url" content="<?= e($canon ?: APP_URL . '/') ?>">
  <meta property="og:image" content="<?= e($image) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <link rel="icon" href="<?= APP_URL ?>/favicon.ico" sizes="32x32">
  <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/mark.svg">
  <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/img/apple-touch-icon.png">
  <link rel="alternate" type="application/rss+xml" title="<?= e(APP_NAME) ?> advice" href="<?= APP_URL ?>/feed.xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wdth,wght@12..96,75..100,400..800&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <?php foreach ($css as $file): ?>
  <link rel="stylesheet" href="<?= asset('css/' . $file) ?>">
  <?php endforeach; ?>
  <?php if (!empty($opt['preload'])): ?><link rel="preload" as="image" href="<?= e($opt['preload']) ?>" fetchpriority="high"><?php endif; ?>
  <script>try{var t=localStorage.getItem('sar-theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}document.documentElement.classList.add('js');</script>
  <?php foreach ($opt['schema'] ?? [] as $ld): ?>
  <script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
  <?php endforeach; ?>
  <?php if (($ga || $ads) && !IS_LOCAL): ?>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga ?: $ads) ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());<?php foreach (array_filter([$ga, $ads]) as $id): ?>gtag('config',<?= json_encode($id) ?>);<?php endforeach; ?></script>
  <?php endif; ?>
  <?php if ($pixel && !IS_LOCAL): ?>
  <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init',<?= json_encode($pixel) ?>);fbq('track','PageView');</script>
  <?php endif; ?>
</head>
<?php
}

function flashHtml(): string
{
    $out = '';
    foreach (takeFlashes() as [$type, $msg]) {
        $icon = $type === 'error' ? 'fa-circle-exclamation' : ($type === 'success' ? 'fa-circle-check' : 'fa-circle-info');
        $out .= '<div class="alert alert-' . e($type) . '"><i class="fa-solid ' . $icon . '"></i><span>' . e($msg) . '</span></div>';
    }
    return $out;
}

// ── Public site ───────────────────────────────────────────────────
/** $opt: description, path (for the canonical), crumbs, schema, image, og_type, preload, noindex, class. */
function siteOpen(string $title, array $opt = []): void
{
    countHit();
    $opt['canonical'] = APP_URL . '/' . ltrim($opt['path'] ?? '', '/');
    $schema = [businessSchema()];
    if (!empty($opt['crumbs'])) $schema[] = crumbSchema($opt['crumbs']);
    $opt['schema'] = array_merge($schema, $opt['schema'] ?? []);
    pageHead($title, $opt);
    $active = explode('/', (string) ($opt['path'] ?? ''))[0];
    $nav = ['services' => 'Repairs', 'faults' => 'Fault finder', 'prices' => 'Prices', 'areas' => 'Areas', 'our-work' => 'Our work', 'blog' => 'Advice', 'contact' => 'Contact'];
    if (isset(services()[$active])) $active = 'services';
    if ($active === 'error-codes') $active = 'faults';
    ?>
<body class="site <?= e($opt['class'] ?? '') ?>">
<a class="skip" href="#main">Skip to content</a>
<header class="head" id="head">
  <div class="wrap head__in">
    <?= logo() ?>
    <nav class="nav" id="nav" aria-label="Main">
      <?php foreach ($nav as $slug => $label): ?><a href="<?= APP_URL . '/' . $slug ?>" <?= $active === $slug ? 'aria-current="page"' : '' ?>><?= $label ?></a><?php endforeach; ?>
      <div class="nav__foot"><?= callButton('btn btn-line') ?><a class="btn btn-flame" href="<?= APP_URL ?>/book">Book a repair</a></div>
    </nav>
    <div class="head__act">
      <a class="head__tel" href="tel:<?= e(telIntl()) ?>" data-tap="call"><i class="fa-solid fa-phone"></i><span><?= e(biz('biz_phone')) ?></span></a>
      <button class="icon-btn" id="themeBtn" aria-label="Switch between light and dark"><i class="fa-solid fa-circle-half-stroke"></i></button>
      <a class="btn btn-flame head__book" href="<?= APP_URL ?>/book">Book a repair</a>
      <button class="icon-btn burger" id="burger" aria-label="Menu" aria-expanded="false" aria-controls="nav"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
</header>
<main id="main">
<?php
}

function ctaBand(string $heading = 'Let us fix it today', string $wa = 'Hi, I need an appliance repaired.', string $text = ''): string
{
    $c = e(callout());
    $text = $text ?: "Send a photo of the appliance and tell us where you are. Call-out R{$c}, free if we do the repair.";
    return '<section class="cta"><div class="wrap cta__in"><div class="cta__lcd">' . lcd('donE') . '</div><div><h2>' . e($heading) . '</h2><p>' . $text . '</p></div><div class="cta__btns">'
        . waButton($wa, 'WhatsApp us', 'btn btn-wa btn-lg') . '<a class="btn btn-glass btn-lg" href="' . APP_URL . '/book">Book online</a></div></div></section>';
}

function siteClose(string $scripts = ''): void
{
    $g = e(biz('biz_guarantee'));
    ?>
</main>
<footer class="foot">
  <div class="wrap foot__grid">
    <div>
      <?= logo('logo--light') ?>
      <p>We repair fridges, washing machines, stoves, ovens, microwaves and geysers at your home in Soweto and around Johannesburg.</p>
      <ul class="foot__facts"><li>Same-day service</li><li><?= $g ?>-month guarantee</li><li>Open 7 days</li></ul>
    </div>
    <div><h3>Repairs</h3><?php foreach (services() as $slug => $s): ?><a href="<?= APP_URL . '/' . $slug ?>"><?= e($s['name']) ?></a><?php endforeach; ?><a href="<?= APP_URL ?>/error-codes">Error codes</a><a href="<?= APP_URL ?>/faults">Fault finder</a></div>
    <div class="foot__areas"><h3>Areas</h3><?php foreach (areas() as $slug => $a): ?><a href="<?= APP_URL ?>/areas/<?= $slug ?>"><?= e($a[0]) ?></a><?php endforeach; ?></div>
    <div><h3>Contact</h3>
      <a href="tel:<?= e(telIntl()) ?>" data-tap="call"><?= e(biz('biz_phone')) ?></a>
      <a href="<?= e(waLink('Hi, I need an appliance repaired.')) ?>" target="_blank" rel="noopener" data-tap="whatsapp">WhatsApp</a>
      <a href="mailto:<?= e(biz('biz_email')) ?>"><?= e(biz('biz_email')) ?></a>
      <a href="<?= APP_URL ?>/book">Book a repair</a><a href="<?= APP_URL ?>/about">About us</a><a href="<?= APP_URL ?>/reviews">Reviews</a><a href="<?= APP_URL ?>/blog">Advice</a>
      <?php if (biz('biz_facebook') || biz('biz_instagram')): ?><p class="foot__soc"><?php if ($f = biz('biz_facebook')): ?><a href="<?= e($f) ?>" aria-label="Facebook" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a><?php endif; if ($i = biz('biz_instagram')): ?><a href="<?= e($i) ?>" aria-label="Instagram" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a><?php endif; ?></p><?php endif; ?>
    </div>
  </div>
  <div class="wrap foot__end"><span>© <?= date('Y') ?> <?= e(APP_NAME) ?></span><span><a href="<?= APP_URL ?>/privacy">Privacy</a><a href="<?= APP_URL ?>/terms">Terms</a><a href="<?= APP_URL ?>/login">Staff</a></span></div>
</footer>

<div class="chat" id="chat" hidden>
  <div class="chat__head"><div><strong>Ask us</strong><small>Instant answers. For a visit, WhatsApp us.</small></div><button class="icon-btn" id="chatClose" aria-label="Close chat"><i class="fa-solid fa-xmark"></i></button></div>
  <div class="chat__log" id="chatLog" aria-live="polite"><div class="msg msg--bot">Hi! What is wrong with your appliance? Tell me the appliance, what it is doing and your area.</div></div>
  <form class="chat__form" id="chatForm"><label class="sr" for="chatText">Your message</label><input id="chatText" maxlength="400" autocomplete="off" placeholder="My fridge is not cooling…"><button class="btn btn-flame" aria-label="Send"><i class="fa-solid fa-arrow-up"></i></button></form>
  <a class="chat__wa" href="<?= e(waLink('Hi, I need an appliance repaired.')) ?>" target="_blank" rel="noopener" data-tap="whatsapp"><i class="fa-brands fa-whatsapp"></i>Continue on WhatsApp</a>
</div>
<div class="fab">
  <button class="fab__chat" id="chatOpen" aria-label="Ask a question"><i class="fa-solid fa-comment-dots"></i></button>
  <a class="fab__wa" href="<?= e(waLink('Hi, I need an appliance repaired.')) ?>" target="_blank" rel="noopener" data-tap="whatsapp" aria-label="WhatsApp us"><i class="fa-brands fa-whatsapp"></i></a>
</div>
<nav class="dock" aria-label="Quick contact">
  <a href="tel:<?= e(telIntl()) ?>" data-tap="call"><i class="fa-solid fa-phone"></i>Call</a>
  <a class="dock__wa" href="<?= e(waLink('Hi, I need an appliance repaired.')) ?>" target="_blank" rel="noopener" data-tap="whatsapp"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>
  <a href="<?= APP_URL ?>/book"><i class="fa-solid fa-calendar-check"></i>Book</a>
</nav>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.SAR = { url: <?= json_encode(APP_URL) ?>, csrf: <?= json_encode(csrfToken()) ?>, lcd: <?= json_encode(LCD_MAP, JSON_UNESCAPED_UNICODE) ?> };</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?= $scripts ?>
</body>
</html>
<?php
}

function notFound(): never
{
    http_response_code(404);
    siteOpen('Page not found – ' . APP_NAME, ['noindex' => true, 'path' => '404']);
    echo '<section class="nf"><div class="wrap"><div class="meter">' . lcd('E404', '', 'Error 404') . '</div><h1>This page is not working</h1><p>The address may be old or mistyped. The rest of the site is fine.</p><p class="row"><a class="btn btn-flame" href="' . APP_URL . '/">Go to the home page</a><a class="btn btn-line" href="' . APP_URL . '/faults">Find a fault</a></p></div></section>';
    siteClose();
    exit;
}

/** FAQ accordion. */
function faqHtml(array $faqs): string
{
    $out = '<div class="faq">';
    foreach ($faqs as $i => $f) $out .= '<details' . ($i === 0 ? ' open' : '') . '><summary>' . e($f[0] ?? $f['q']) . '</summary><p>' . e($f[1] ?? $f['a']) . '</p></details>';
    return $out . '</div>';
}

function postCard(array $p): string
{
    $svc = services()[$p['service'] ?? '']['short'] ?? 'Advice';
    return '<a class="post" href="' . e(postUrl($p)) . '"><div class="post__img"><img src="' . e(coverUrl($p['cover'], true)) . '" alt="" loading="lazy" width="520" height="390"></div><div class="post__txt"><small>' . e($svc) . ' · ' . readMinutes($p['body']) . ' min read</small><h3>' . e($p['title']) . '</h3><p>' . e(excerpt($p['excerpt'], 130)) . '</p></div></a>';
}

// ── Sign-in pages ─────────────────────────────────────────────────
function authOpen(string $title, string $heading, string $sub): void
{
    pageHead($title . ' – ' . APP_NAME, ['noindex' => true, 'css' => ['style.css']]);
    ?>
<body class="auth">
<aside class="auth__brand"><?= logo('logo--light') ?>
  <div class="auth__pitch"><h2>Bookings, articles and <span>search traffic</span> in one place.</h2><p>The staff side of <?= e(APP_NAME) ?>.</p></div>
</aside>
<main class="auth__main"><div class="auth__card">
  <div class="auth__mobile-brand"><?= logo() ?></div>
  <h1><?= e($heading) ?></h1><p class="auth__sub"><?= $sub ?></p>
<?php
    echo flashHtml();
}

function authClose(): void
{
    echo '</div></main><script>window.SAR={url:' . json_encode(APP_URL) . ',csrf:' . json_encode(csrfToken()) . ',lcd:{}};</script><script src="' . asset('js/app.js') . '" defer></script></body></html>';
}

function appClose(string $scripts = ''): void
{
    ?>
  </main>
</div>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script>window.SAR = { url: <?= json_encode(APP_URL) ?>, csrf: <?= json_encode(csrfToken()) ?>, lcd: {} };</script>
<script src="<?= asset('js/app.js') ?>" defer></script>
<?= $scripts ?>
</body>
</html>
<?php
}
