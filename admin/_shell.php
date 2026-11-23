<?php
declare(strict_types=1);

/**
 * Admin shell: sidebar, top bar and page header. Pages handle their POSTs
 * (which redirect), then call adminOpen() … adminClose().
 */
require_once dirname(__DIR__) . '/includes/boot.php';

requireAdmin();
$ADMIN = getCurrentUser();
if ($_SERVER['REQUEST_METHOD'] === 'POST') requireCsrf();

function adminOpen(string $active, string $title, string $sub = '', string $actions = ''): void
{
    global $ADMIN;
    $db = getDB();
    $newB   = (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'new'")->fetchColumn();
    $newR   = (int) $db->query('SELECT COUNT(*) FROM reviews WHERE is_approved = 0')->fetchColumn();
    $drafts = (int) $db->query('SELECT COUNT(*) FROM posts WHERE is_published = 0')->fetchColumn();
    $failed = (int) $db->query("SELECT COUNT(*) FROM sent_emails WHERE status = 'failed' AND created_at > (NOW() - INTERVAL 1 DAY)")->fetchColumn();

    $nav = [
        'Customers' => [
            ['dashboard', 'index.php',    'fa-gauge-high',   'Dashboard', 0],
            ['bookings',  'bookings.php', 'fa-calendar-check', 'Bookings', $newB],
            ['reviews',   'reviews.php',  'fa-star',         'Reviews', $newR],
            ['chats',     'chats.php',    'fa-comments',     'Chats', 0],
        ],
        'Traffic' => [
            ['articles',  'articles.php', 'fa-newspaper',    'Articles', $drafts],
            ['topics',    'topics.php',   'fa-lightbulb',    'Topic queue', 0],
            ['seo',       'seo.php',      'fa-chart-line',   'SEO & visits', 0],
            ['social',    'social.php',   'fa-share-nodes',  'Social', 0],
            ['media',     'media.php',    'fa-images',       'Photos', 0],
        ],
        'Setup' => [
            ['settings',  'settings.php', 'fa-sliders',      'Settings', 0],
            ['emails',    'emails.php',   'fa-envelope-open-text', 'Sent log', $failed],
            ['account',   'account.php',  'fa-user-gear',    'My account', 0],
            ['audit',     'audit.php',    'fa-clock-rotate-left', 'Audit log', 0],
        ],
    ];
    $name = $ADMIN['full_name'] ?: $ADMIN['email'];
    pageHead($title . ' – Admin – ' . APP_NAME, ['css' => ['style.css', 'admin.css'], 'noindex' => true]);
    ?>
<body class="adm">
<div class="adm-scrim" id="admScrim"></div>
<aside class="adm-side" id="admSide">
  <?= logo('logo--light') ?>
  <?php foreach ($nav as $group => $links): ?>
    <div class="adm-side__label"><?= e($group) ?></div>
    <?php foreach ($links as [$slug, $href, $icon, $label, $count]): ?>
      <a href="<?= APP_URL ?>/admin/<?= $href ?>" class="<?= $active === $slug ? 'is-active' : '' ?>">
        <i class="fa-solid <?= $icon ?>"></i><span><?= e($label) ?></span>
        <?php if ($count): ?><b class="n"><?= (int) $count ?></b><?php endif; ?>
      </a>
    <?php endforeach; ?>
  <?php endforeach; ?>
  <div class="adm-side__foot">
    <a href="<?= APP_URL ?>/" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i><span>View website</span></a>
  </div>
</aside>

<div class="adm-main">
  <header class="adm-top">
    <button class="icon-btn adm-burger" id="admBurger" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <form class="adm-search" action="<?= APP_URL ?>/admin/bookings.php" method="get" role="search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" placeholder="Search bookings by ref, name, phone or area…" aria-label="Search bookings" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
    <div class="adm-top__right">
      <button class="icon-btn" id="themeBtn" title="Switch light / dark" aria-label="Switch theme"><i class="fa-solid fa-circle-half-stroke"></i></button>
      <div class="adm-me">
        <div class="avatar"><?= e(strtoupper(mb_substr($name, 0, 1))) ?></div>
        <div class="adm-me__txt"><strong><?= e($name) ?></strong><small>Administrator</small></div>
      </div>
      <form method="POST" action="<?= APP_URL ?>/logout.php"><?= csrfField() ?><button class="icon-btn" title="Sign out" aria-label="Sign out"><i class="fa-solid fa-arrow-right-from-bracket"></i></button></form>
    </div>
  </header>

  <main class="adm-body">
    <div class="adm-head">
      <div><h1><?= e($title) ?></h1><?php if ($sub): ?><p><?= $sub ?></p><?php endif; ?></div>
      <?php if ($actions): ?><div class="adm-head__actions"><?= $actions ?></div><?php endif; ?>
    </div>
    <?= flashHtml() ?>
<?php
}

/** Is cron alive? Returns [ok, text]. */
function cronBeat(): array
{
    $at = (int) get_setting('scheduler_heartbeat', '0');
    if (!$at) return [false, 'The cron job has never run. Articles will not be written until you add it (see the setup guide).'];
    $min = (int) floor((time() - $at) / 60);
    return [$min <= 20, $min <= 20 ? 'Everything is running. Last check ' . ($min < 1 ? 'just now' : "{$min} min ago") . '.' : "The cron job last ran {$min} minutes ago. Check cPanel → Cron Jobs."];
}

function adminClose(string $scripts = ''): void
{
    appClose($scripts);
}
