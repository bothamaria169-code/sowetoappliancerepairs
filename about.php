<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
siteOpen('About Soweto Appliance Repairs', [
    'path' => 'about', 'crumbs' => [['About', 'about']], 'description' => 'A mobile appliance repair service for Soweto and Johannesburg. We come to your home, test first and repair on the spot.',
    'schema' => [['@context' => 'https://schema.org', '@type' => 'AboutPage', 'name' => 'About ' . APP_NAME, 'mainEntity' => ['@id' => APP_URL . '/#business']]],
]);
?>
<section class="top"><div class="wrap top__in"><div><?= crumbs([['About', 'about']]) ?><h1>We fix it at your home</h1><p class="sub">A broken fridge is hard enough. You should not also have to find a bakkie, load it and wait weeks for a workshop to call.</p></div><div class="top__img"><?= photo('stove-multimeter', 'Technician testing a stove with a multimeter', '', '(max-width: 820px) 100vw, 420px', true) ?></div></div></section>
<section class="sec"><div class="wrap split"><div class="prose">
  <p class="lede">Soweto Appliance Repairs is a mobile service. The technician, the tools and the meter come to you, in Soweto and 19 other areas around Johannesburg.</p>
  <h2>How we work</h2><ul><li><strong>Test before talk.</strong> We measure the part before we call it broken.</li><li><strong>One fair rule on the call-out.</strong> R<?= e(callout()) ?>, and free if we do the repair.</li><li><strong>A guarantee you can use.</strong> <?= e(biz('biz_guarantee')) ?> months on our repairs.</li><li><strong>Seven days.</strong> Appliances do not wait for Monday.</li></ul>
  <h2>What we repair</h2><div class="chips"><?php foreach (services() as $slug => $s): ?><a class="chip" href="<?= APP_URL . '/' . $slug ?>"><?= e($s['name']) ?></a><?php endforeach; ?></div>
  <h2>Why we give so much away</h2><p>The <a href="<?= APP_URL ?>/faults">fault finder</a>, the <a href="<?= APP_URL ?>/error-codes">error codes</a> and our <a href="<?= APP_URL ?>/blog">advice</a> are free. If you can fix it with a clean filter, do that and keep your money. If you cannot, you know who to call.</p>
</div><aside><div class="box box--ink"><h3>Talk to us</h3><p><?= e(biz('biz_hours')) ?></p><?= waButton('Hi, I need an appliance repaired.', 'WhatsApp us') ?><?= callButton('btn btn-glass') ?></div></aside></div></section>
<?= ctaBand() ?>
<?php siteClose();
