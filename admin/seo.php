<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $urls = [APP_URL . '/'];
    foreach (array_merge(array_keys(services()), array_map(fn($x) => 'areas/' . $x, array_keys(areas())), array_map(fn($x) => 'faults/' . $x, array_keys(faults())), ['services', 'faults', 'error-codes', 'prices', 'areas', 'our-work', 'blog']) as $p) $urls[] = APP_URL . '/' . $p;
    foreach ($db->query('SELECT slug FROM posts WHERE is_published = 1')->fetchAll(PDO::FETCH_COLUMN) as $s) $urls[] = APP_URL . '/blog/' . $s;
    $ok = indexNow($urls);
    flash($ok ? 'success' : 'error', $ok ? count($urls) . ' pages sent to IndexNow (Bing and others).' : (IS_LOCAL ? 'This only works on the live site.' : 'IndexNow did not accept it: ' . get_setting('indexnow_last')));
    redirect('admin/seo.php');
}
$src = $db->query("SELECT origin, SUM(n) n FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY AND origin <> 'Internal' GROUP BY origin ORDER BY n DESC")->fetchAll();
$pages = $db->query('SELECT path, SUM(n) n FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY GROUP BY path ORDER BY n DESC LIMIT 25')->fetchAll();
$land = $db->query("SELECT path, SUM(n) n FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY AND origin IN ('Google','Bing','Other search','AI assistants') GROUP BY path ORDER BY n DESC LIMIT 25")->fetchAll();
$taps = $db->query('SELECT path, kind, COUNT(*) n FROM lead_taps WHERE created_at > (NOW() - INTERVAL 30 DAY) GROUP BY path, kind ORDER BY n DESC LIMIT 25')->fetchAll();
$max = max(1, (int) ($src[0]['n'] ?? 1));
$table = function (array $rows, string $empty) {
    if (!$rows) { echo '<div class="empty">' . $empty . '</div>'; return; }
    echo '<table class="tbl"><tbody>';
    foreach ($rows as $r) echo '<tr><td class="cell-main"><a href="' . e(APP_URL . $r['path']) . '" target="_blank">' . e($r['path'] === '/' ? 'Home page' : $r['path']) . '</a>' . (isset($r['kind']) ? ' <span class="tag tag-user">' . e($r['kind']) . '</span>' : '') . '</td><td class="num">' . (int) $r['n'] . '</td></tr>';
    echo '</tbody></table>';
};
adminOpen('seo', 'SEO & visits', 'Last 30 days. No cookies, and your own visits are not counted while you are signed in.', '<a class="btn btn-ghost" target="_blank" href="' . APP_URL . '/sitemap.xml">Sitemap</a><form method="post" data-busy>' . csrfField() . '<button class="btn btn-primary" type="submit">Send all pages to Bing</button></form>');
?>
<div class="grid-2 even"><div class="card"><div class="card__head"><h2>Where visitors come from</h2></div><div class="card__body"><?php if ($src): foreach ($src as $r): ?><div class="bar-row"><span><?= e($r['origin']) ?></span><div class="track"><span style="width:<?= (int) round($r['n'] / $max * 100) ?>%"></span></div><b><?= (int) $r['n'] ?></b></div><?php endforeach; else: ?><div class="empty">No visits yet.</div><?php endif; ?></div></div>
<div class="card"><div class="card__head"><h2>Pages that win search visitors</h2></div><?php $table($land, 'Once Google starts sending people, the pages show here.'); ?></div></div>
<div class="grid-2 even"><div class="card"><div class="card__head"><h2>Most visited pages</h2></div><?php $table($pages, 'No visits yet.'); ?></div><div class="card"><div class="card__head"><h2>Pages that make people call</h2></div><?php $table($taps, 'Taps on Call and WhatsApp show here.'); ?></div></div>
<div class="card"><div class="card__head"><h2>Checklist</h2></div><div class="card__body"><ol class="steps-list">
  <li><?= get_setting('gsc_verification') ? '✔' : '○' ?> Google Search Console verified (<a href="settings.php?tab=tracking">Settings → Google & Meta</a>), sitemap <code>sitemap.xml</code> submitted</li>
  <li><?= biz('biz_gbp') ? '✔' : '○' ?> Google Business Profile link added (<a href="settings.php?tab=business">Settings → Business</a>)</li>
  <li><?= get_setting('ga4_id') ? '✔' : '○' ?> Google Analytics 4 ID added</li>
  <li><?= cronBeat()[0] ? '✔' : '○' ?> Cron job running, so articles are written by themselves</li>
  <li><?= (int) $db->query('SELECT COUNT(*) FROM reviews WHERE is_approved = 1')->fetchColumn() >= 5 ? '✔' : '○' ?> At least 5 real reviews approved</li>
</ol><p class="hint">IndexNow: <?= e(get_setting('indexnow_last') ?: 'not sent yet') ?></p></div></div>
<?php adminClose();
