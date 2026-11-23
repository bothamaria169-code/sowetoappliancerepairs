<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
siteOpen('Appliance Fault Finder: What Is Wrong and What to Check', [
    'path' => 'faults', 'crumbs' => [['Fault finder', 'faults']],
    'description' => 'Find out why your fridge, washing machine, stove, oven or geyser stopped working. Likely causes and safe checks before you pay for a repair.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Fault finder', 'faults']]) ?><h1>Fault finder</h1><p class="sub">Choose what your appliance is doing. Each guide gives the likely causes, safe things you can check and when to switch it off.</p><div class="row"><a class="btn btn-flame" href="<?= APP_URL ?>/error-codes">Look up an error code</a></div></div></div></section>
<section class="sec"><div class="wrap">
<?php foreach (services() as $slug => $s): ?>
  <div class="group" id="<?= $slug ?>"><h2><?= icon($s['icon']) ?><?= e($s['short']) ?></h2><div class="cards"><?php foreach (faultsFor($slug) as $fs => $f): ?><a class="card card--f" href="<?= APP_URL ?>/faults/<?= $fs ?>"><span class="lcdtag"><?= lcd($f['lcd']) ?></span><div><h3><?= e($f['s']) ?></h3></div></a><?php endforeach; ?></div></div>
<?php endforeach; ?>
</div></section>
<?= ctaBand('Cannot find it?', 'Hi, my appliance has a problem I cannot find on your website.') ?>
<?php siteClose();
