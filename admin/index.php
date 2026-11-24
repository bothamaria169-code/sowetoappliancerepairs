<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();
$one = fn(string $sql) => (int) $db->query($sql)->fetchColumn();

$new   = $one("SELECT COUNT(*) FROM bookings WHERE status = 'new'");
$b30   = $one('SELECT COUNT(*) FROM bookings WHERE created_at > (NOW() - INTERVAL 30 DAY)');
$taps  = $one('SELECT COUNT(*) FROM lead_taps WHERE created_at > (NOW() - INTERVAL 30 DAY)');
$wa    = $one("SELECT COUNT(*) FROM lead_taps WHERE kind = 'whatsapp' AND created_at > (NOW() - INTERVAL 30 DAY)");
$v30   = $one('SELECT COALESCE(SUM(n),0) FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY');
$s30   = $one("SELECT COALESCE(SUM(n),0) FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY AND origin IN ('Google','Bing','Other search','AI assistants')");
$posts = $one('SELECT COUNT(*) FROM posts WHERE is_published = 1');
$queue = $one("SELECT COUNT(*) FROM article_topics WHERE status = 'queued' AND tries < 3");

$days = [];
for ($i = 29; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-{$i} days"))] = 0;
foreach ($db->query('SELECT day, SUM(n) n FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY GROUP BY day') as $r) if (isset($days[$r['day']])) $days[$r['day']] = (int) $r['n'];
$max = max(1, max($days));
$vs = $db->prepare('SELECT * FROM bookings WHERE visit_date BETWEEN ? AND ? AND lost_at IS NULL AND went_at IS NULL AND repaired_at IS NULL ORDER BY visit_date, visit_from LIMIT 8');
$vs->execute([date('Y-m-d'), date('Y-m-d', strtotime('+2 days'))]);
$visits = $vs->fetchAll();
$latest = $db->query('SELECT * FROM bookings ORDER BY created_at DESC LIMIT 7')->fetchAll();
$top = $db->query('SELECT path, SUM(n) n FROM page_hits WHERE day >= CURDATE() - INTERVAL 29 DAY GROUP BY path ORDER BY n DESC LIMIT 7')->fetchAll();
$topMax = max(1, (int) ($top[0]['n'] ?? 1));
[$ok, $beat] = cronBeat();

adminOpen('dashboard', 'Dashboard', 'Bookings, visitors and articles at a glance.', '<a class="btn btn-ghost" href="articles.php"><i class="fa-solid fa-newspaper"></i>Articles</a><a class="btn btn-primary" href="bookings.php"><i class="fa-solid fa-list-check"></i>Leads</a>');
?>
<div class="beat <?= $ok ? 'ok' : 'bad' ?>"><i class="fa-solid <?= $ok ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i><span><strong>Is everything still running?</strong> <?= e($beat) ?></span></div>
<div class="kpis">
  <div class="kpi"><div class="kpi__ico"><i class="fa-solid fa-inbox"></i></div><div><div class="kpi__label">New leads</div><div class="kpi__value"><?= $new ?></div><div class="kpi__delta"><?= $b30 ?> in 30 days</div></div></div>
  <div class="kpi kpi--mint"><div class="kpi__ico"><i class="fa-brands fa-whatsapp"></i></div><div><div class="kpi__label">Call &amp; WhatsApp taps</div><div class="kpi__value"><?= $taps ?></div><div class="kpi__delta"><?= $wa ?> WhatsApp · 30 days</div></div></div>
  <div class="kpi kpi--violet"><div class="kpi__ico"><i class="fa-solid fa-eye"></i></div><div><div class="kpi__label">Page views</div><div class="kpi__value"><?= number_format($v30, 0, '.', ' ') ?></div><div class="kpi__delta"><b><?= number_format($s30, 0, '.', ' ') ?></b> from search &amp; AI</div></div></div>
  <div class="kpi kpi--amber"><div class="kpi__ico"><i class="fa-solid fa-newspaper"></i></div><div><div class="kpi__label">Articles live</div><div class="kpi__value"><?= $posts ?></div><div class="kpi__delta"><?= $queue ?> topics waiting</div></div></div>
</div>
<?php if ($visits): ?><div class="card" style="margin-bottom:1.2rem"><div class="card__head"><h2>Next visits</h2><a href="visits.php">All visits</a></div><?php foreach ($visits as $b): ?><a class="list-row" style="color:inherit;text-decoration:none" href="bookings.php?id=<?= (int) $b['id'] ?>"><div style="min-width:150px;font-weight:800"><?= e(date('D j M', strtotime($b['visit_date']))) ?><br><small><?= e(visitTime($b)) ?></small></div><div class="list-row__main"><strong><?= e($b['full_name']) ?>, <?= e(lcfirst(services()[$b['service']]['short'] ?? '')) ?></strong><small><?= e(trim(($b['address'] ?: '') . ', ' . $b['area'], ', ')) ?></small></div><div class="list-row__side"><?= $b['confirmed_at'] ? '<span class="tag tag-on">Confirmed</span>' : '' ?></div></a><?php endforeach; ?></div><?php endif; ?>
<div class="grid-2">
  <div class="card"><div class="card__head"><div><h2>Page views, last 30 days</h2></div><a href="seo.php">More</a></div><div class="card__body">
    <div class="spark" role="img" aria-label="Page views per day for 30 days"><?php foreach ($days as $d => $n): ?><span style="height:<?= max(3, (int) round($n / $max * 100)) ?>%" title="<?= date('j M', strtotime($d)) ?>: <?= $n ?>" class="<?= $n ? '' : 'zero' ?>"></span><?php endforeach; ?></div>
    <div class="spark__axis"><span><?= date('j M', strtotime(array_key_first($days))) ?></span><span>Today</span></div></div></div>
  <div class="card"><div class="card__head"><h2>Most visited pages</h2></div><div class="card__body"><?php if ($top): foreach ($top as $t): ?><div class="bar-row"><span title="<?= e($t['path']) ?>" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($t['path'] === '/' ? 'Home' : $t['path']) ?></span><div class="track"><span style="width:<?= (int) round($t['n'] / $topMax * 100) ?>%"></span></div><b><?= (int) $t['n'] ?></b></div><?php endforeach; else: ?><div class="empty">Visits will show here.</div><?php endif; ?></div></div>
</div>
<div class="card"><div class="card__head"><h2>Latest leads</h2><a href="bookings.php">All leads</a></div>
<?php if ($latest): ?><table class="tbl"><thead><tr><th>Customer</th><th>Appliance</th><th>Area</th><th>Status</th><th>When</th></tr></thead><tbody><?php foreach ($latest as $b): ?><tr><td class="cell-main"><strong><a href="bookings.php?id=<?= (int) $b['id'] ?>"><?= e($b['full_name']) ?></a></strong><br><small class="muted"><?= e($b['phone']) ?></small></td><td data-label="Appliance"><?= e(services()[$b['service']]['short'] ?? $b['service']) ?></td><td data-label="Area"><?= e($b['area']) ?></td><td data-label="Status"><span class="tag <?= $b['status'] === 'new' ? 'tag-on' : 'tag-user' ?>"><?= e(BOOKING_STATUSES[$b['status']] ?? $b['status']) ?></span></td><td data-label="When"><span class="muted"><?= e(timeAgo($b['created_at'])) ?></span></td></tr><?php endforeach; ?></tbody></table>
<?php else: ?><div class="empty"><i class="fa-solid fa-calendar"></i>Bookings from the website will show here.</div><?php endif; ?></div>
<?php adminClose();
