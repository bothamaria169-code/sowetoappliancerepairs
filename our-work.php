<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
require_once __DIR__ . '/includes/media.php';

$uploads = getDB()->query('SELECT * FROM media WHERE show_in_gallery = 1 ORDER BY id DESC LIMIT 60')->fetchAll();
siteOpen('Our Work: Appliance Repair Photos from Soweto & Joburg', [
    'path' => 'our-work', 'crumbs' => [['Our work', 'our-work']], 'description' => 'Photos from real appliance repairs: fridges, washing machines, stoves, ovens and geysers in homes around Soweto and Johannesburg.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Our work', 'our-work']]) ?><h1>Real jobs, real kitchens</h1><p class="sub">No stock photos here. These come off the technician's phone.</p></div></div></section>
<section class="sec"><div class="wrap">
  <div class="filter" data-filter=".strip figure"><button type="button" class="is-on" data-k="">All</button><?php foreach (services() as $slug => $s): ?><button type="button" data-k="<?= $slug ?>"><?= e($s['short']) ?></button><?php endforeach; ?></div>
  <div class="strip">
    <?php foreach ($uploads as $m): $u = $m['ik_url'] ?: APP_URL . '/' . $m['path']; ?><figure data-k="<?= e($m['service']) ?>"><img src="<?= e($m['ik_url'] ? ImageKit::sized($m['ik_url'], 620) : $u) ?>" alt="<?= e($m['title'] ?: 'Appliance repair') ?>" width="<?= (int) ($m['width'] ?: 600) ?>" height="<?= (int) ($m['height'] ?: 800) ?>" loading="lazy"></figure><?php endforeach; ?>
    <?php foreach (workPhotos() as $i => [$f, $alt, $svc]): ?><figure data-k="<?= $svc ?>"><?= photo($f, $alt, '', '(max-width: 620px) 50vw, 300px', $i < 2) ?></figure><?php endforeach; ?>
  </div>
</div></section>
<?= ctaBand('Yours could be next') ?>
<?php siteClose();
