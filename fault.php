<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$slug = (string) ($_GET['s'] ?? '');
$f = faults()[$slug] ?? null;
if (!$f) notFound();
$s = services()[$f['service']];
$c = callout();
$wa = 'Hi, my ' . lcfirst($s['short']) . ' has a problem: ' . lcfirst($f['s']) . '. I am in ';
$trail = [['Fault finder', 'faults'], [$s['short'] . ': ' . lcfirst($f['s']), 'faults/' . $slug]];
$faqs = [
    [$f['q'] . (str_ends_with($f['q'], '?') ? '' : ': what is the cause?'), $f['lead'] . ' Common causes: ' . implode(', ', array_map('lcfirst', array_column($f['causes'], 0))) . '.'],
    ['What can I safely check myself?', implode(' ', $f['checks'])],
    ['When should I stop and call a technician?', $f['stop']],
];
siteOpen(excerpt($f['q'], 58) . ' | Soweto Repairs', [
    'path' => 'faults/' . $slug, 'crumbs' => $trail, 'description' => excerpt($f['lead'] . ' Causes, safe checks and when to call.', 158), 'og_type' => 'article', 'schema' => [faqSchema($faqs)],
]);
?>
<section class="top"><div class="wrap top__in">
  <div><?= crumbs($trail) ?><h1><?= e($f['q']) ?></h1><p class="sub"><?= e($f['lead']) ?></p></div>
  <div><div class="meter" style="padding:2rem 2.2rem"><?= lcd($f['lcd'], 'lcd--hero') ?></div></div>
</div></section>
<section class="sec"><div class="wrap split">
  <div class="prose">
    <h2 style="margin-top:0">Likely causes</h2>
    <div><?php foreach ($f['causes'] as $i => [$h, $t]): ?><div class="cause"><?= lcd((string) ($i + 1)) ?><div><h3><?= e($h) ?></h3><p><?= e($t) ?></p></div></div><?php endforeach; ?></div>
    <h2>Safe checks you can do</h2><ol><?php foreach ($f['checks'] as $x): ?><li><?= e($x) ?></li><?php endforeach; ?></ol>
    <div class="stop"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>When to stop</strong><p><?= e($f['stop']) ?></p></div></div>
    <h2>What we do when we come</h2><p><?= e($f['fix']) ?> The call-out is R<?= e($c) ?>, and it is free if we do the repair.</p>
  </div>
  <aside>
    <div class="box box--ink"><h3>Still broken?</h3><p>We come to you, same day. R<?= e($c) ?> call-out, free if we repair.</p><?= waButton($wa, 'Send this fault to us') ?><?= callButton('btn btn-glass') ?></div>
    <div class="box"><h3>Other <?= e(lcfirst($s['short'])) ?> faults</h3><?php foreach (faultsFor($f['service']) as $fs => $o): if ($fs !== $slug): ?><a href="<?= APP_URL ?>/faults/<?= $fs ?>"><?= e($o['s']) ?></a><?php endif; endforeach; ?><a href="<?= APP_URL . '/' . $f['service'] ?>"><?= e($s['name']) ?></a></div>
  </aside>
</div></section>
<?php if ($posts = latestPosts(3, $f['service'])): ?><section class="sec sec--tint"><div class="wrap"><div class="sec__head"><h2>More advice</h2></div><div class="posts"><?php foreach ($posts as $p) echo postCard($p); ?></div></div></section><?php endif; ?>
<?= ctaBand('Let us take a look', $wa) ?>
<?php siteClose();
