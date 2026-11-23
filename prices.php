<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$c = callout();
$g = biz('biz_guarantee');
$faqs = [
    ['When do I pay the call-out?', "You pay the R{$c} call-out if we come and test the appliance but you decide not to repair it. If we do the repair, the call-out is free."],
    ['Why can you not give a price over the phone?', 'The same symptom can have a small cause or a large one. A fridge that is not cooling might need a fan, a sensor or a compressor. We test it first so that you pay for the right part.'],
    ['Can I get a rough idea on WhatsApp?', 'Yes. Send a photo of the model sticker and a video of the fault. We will tell you the likely causes before we come.'],
    ['What does the guarantee cover?', "Our repairs carry a {$g}-month guarantee."],
];
siteOpen("Appliance Repair Prices Soweto | R{$c} Call-Out, Free If We Repair", [
    'path' => 'prices', 'crumbs' => [['Prices', 'prices']], 'description' => "What an appliance repair costs in Soweto and Johannesburg. R{$c} call-out, free when we do the repair, and a {$g}-month guarantee.", 'schema' => [faqSchema($faqs)],
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Prices', 'prices']]) ?><h1>R<?= e($c) ?> call-out. Free if we repair.</h1><p class="sub">You know what the visit costs before you book, and what the repair costs before we start.</p></div></div></section>
<section class="sec"><div class="wrap">
  <div class="pricebox"><div><strong>R<?= e($c) ?></strong><p>Call-out and fault test, if you decide not to repair</p></div><div><strong>Free</strong><p>Call-out when we do the repair</p></div><div><strong><?= e($g) ?> months</strong><p>Guarantee on the repair</p></div></div>
  <div class="two"><div class="prose"><h2 style="margin-top:0">What decides the price of a repair</h2><ul><li><strong>The part.</strong> A pump, fan or plate costs less than a control board or compressor.</li><li><strong>The model.</strong> Parts for common models are easier to find.</li><li><strong>The access.</strong> Built-in and stacked appliances take longer to reach.</li><li><strong>The damage.</strong> A leak left too long can reach the wiring.</li></ul><h2>When we would tell you not to repair</h2><p>If the repair would cost close to a replacement, or the body is rusted through, we say so. You pay the call-out and keep your money for the next one.</p></div><?= faqHtml($faqs) ?></div>
</div></section>
<?= ctaBand('Ask for a likely cost', 'Hi, can you give me an idea of what this repair might cost?') ?>
<?php siteClose();
