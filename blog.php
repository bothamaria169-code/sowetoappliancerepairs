<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$db = getDB();
$topic = (string) ($_GET['topic'] ?? '');
$topic = isset(services()[$topic]) ? $topic : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 12;
$where = 'is_published = 1 AND published_at <= NOW()' . ($topic ? ' AND service = ' . $db->quote($topic) : '');
$total = (int) $db->query("SELECT COUNT(*) FROM posts WHERE {$where}")->fetchColumn();
$posts = $db->query("SELECT * FROM posts WHERE {$where} ORDER BY published_at DESC LIMIT {$per} OFFSET " . (($page - 1) * $per))->fetchAll();
if ($page > 1 && !$posts) notFound();

siteOpen(($topic ? services()[$topic]['short'] . ' Advice' : 'Appliance Advice') . ' for Joburg Homes' . ($page > 1 ? ", page {$page}" : ''), [
    'path' => 'blog' . ($topic || $page > 1 ? '?' . http_build_query(array_filter(['topic' => $topic, 'page' => $page > 1 ? $page : null])) : ''), 'crumbs' => [['Advice', 'blog']],
    'description' => 'Plain-language advice on fridges, washing machines, stoves, ovens and geysers: what went wrong, what to check and when to call.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Advice', 'blog']]) ?><h1>Advice before you spend</h1><p class="sub">What went wrong, what to check and when to call.</p></div></div></section>
<section class="sec"><div class="wrap">
  <nav class="filter" aria-label="Topics"><a href="<?= APP_URL ?>/blog" class="<?= $topic ? '' : 'is-on' ?>">All</a><?php foreach (services() as $slug => $s): ?><a href="<?= APP_URL ?>/blog?topic=<?= $slug ?>" class="<?= $topic === $slug ? 'is-on' : '' ?>"><?= e($s['short']) ?></a><?php endforeach; ?></nav>
  <?php if ($posts): ?><div class="posts"><?php foreach ($posts as $p) echo postCard($p); ?></div><?= pager($total, $page, $per, $topic ? ['topic' => $topic] : []) ?><?php else: ?><p class="sub">No articles here yet. Try the <a href="<?= APP_URL ?>/faults">fault finder</a>.</p><?php endif; ?>
</div></section>
<?= ctaBand() ?>
<?php siteClose();
