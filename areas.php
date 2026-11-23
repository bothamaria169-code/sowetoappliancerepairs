<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
siteOpen('Areas We Cover: Soweto, Joburg, West & East Rand Repairs', [
    'path' => 'areas', 'crumbs' => [['Areas', 'areas']],
    'description' => 'Appliance repairs in 20 areas around Johannesburg: Soweto, Roodepoort, Randburg, Sandton, Midrand, Kempton Park, Boksburg, Lenasia and more.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Areas', 'areas']]) ?><h1>We work in 20 areas</h1><p class="sub">From Randfontein in the west to Brakpan in the east, and Midrand down to Ennerdale. Type your suburb to check.</p></div></div></section>
<section class="sec"><div class="wrap">
  <form class="areaf" id="areaForm"><label class="sr" for="areaQ">Your suburb</label><i class="fa-solid fa-location-dot"></i><input id="areaQ" autocomplete="off" placeholder="Diepkloof, Wilgeheuwel, Tembisa…"><output id="areaOut" aria-live="polite"></output></form>
  <div class="cards areas" style="border:0;gap:1rem"><?php foreach (areas() as $slug => $a): ?><a class="card" style="font:inherit;letter-spacing:0" href="<?= APP_URL ?>/areas/<?= $slug ?>" data-sub="<?= e(mb_strtolower($a[0] . '|' . implode('|', $a[3]))) ?>" data-name="<?= e($a[0]) ?>"><h2 style="font-size:1.5rem"><?= e($a[0]) ?></h2><p><?= e(implode(', ', array_slice($a[3], 0, 5))) ?> and more</p></a><?php endforeach; ?></div>
</div></section>
<?= ctaBand() ?>
<?php siteClose();
