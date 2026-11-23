<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
siteOpen('Contact Soweto Appliance Repairs | ' . biz('biz_phone'), [
    'path' => 'contact', 'crumbs' => [['Contact', 'contact']], 'description' => 'WhatsApp or call ' . biz('biz_phone') . ' for same-day appliance repairs in Soweto and Johannesburg. Open seven days.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Contact', 'contact']]) ?><h1><?= e(biz('biz_phone')) ?></h1><p class="sub">WhatsApp is quickest, because you can send a photo. <?= e(biz('biz_hours')) ?>.</p><div class="row"><?= waButton('Hi, I need an appliance repaired.', 'WhatsApp us', 'btn btn-wa btn-lg') ?><?= callButton('btn btn-glass btn-lg') ?></div></div></div></section>
<section class="sec"><div class="wrap"><div class="cards">
  <div class="card"><h2 style="font-size:1.5rem">Book online</h2><p>Fill in the form and we will call or WhatsApp you back.</p><p><a class="btn btn-flame" href="<?= APP_URL ?>/book">Book a repair</a></p></div>
  <div class="card"><h2 style="font-size:1.5rem">Email</h2><p><a href="mailto:<?= e(biz('biz_email')) ?>" style="overflow-wrap:anywhere"><?= e(biz('biz_email')) ?></a></p><p>For quotes, landlords and businesses.</p></div>
  <div class="card"><h2 style="font-size:1.5rem">Where we are</h2><p>We come to you in Soweto and 19 other areas. There is no shop to visit.</p><p><a class="link" href="<?= APP_URL ?>/areas">See all areas</a></p></div>
</div></div></section>
<section class="sec sec--tint"><div class="wrap two"><div><h2>What to send us</h2><p class="sub">Three things save you a second visit.</p></div><ol class="steps" style="grid-template-columns:1fr"><li><?= lcd('01') ?><h3>Your area</h3><p>A suburb or a location pin.</p></li><li><?= lcd('02') ?><h3>The model sticker</h3><p>Inside the fridge door, behind the washer door or on the oven frame.</p></li><li><?= lcd('03') ?><h3>The fault</h3><p>A photo of the code, or a short video of the noise.</p></li></ol></div></section>
<?php siteClose();
