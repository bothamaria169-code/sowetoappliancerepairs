<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
$c = e(callout());
$g = e(biz('biz_guarantee'));
siteOpen('Terms of Service | ' . APP_NAME, ['path' => 'terms', 'crumbs' => [['Terms', 'terms']], 'description' => 'Call-out, repair and guarantee terms of Soweto Appliance Repairs.']);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Terms', 'terms']]) ?><h1>Terms of service</h1><p class="sub">The short version of how we work together.</p></div></div></section>
<section class="sec"><div class="wrap prose">
<h2>Call-out</h2><p>The call-out fee is R<?= $c ?>. It covers the visit and the fault test. If you accept the quote and we do the repair, the call-out fee falls away.</p>
<h2>Quotes</h2><p>We tell you the price before we start a repair. If we find something else on the way, we ask you first.</p>
<h2>Booking times</h2><p>We aim for same-day service. Traffic, spares and weather can move a time slot, and we will let you know.</p>
<h2>Guarantee</h2><p>Repairs carry a <?= $g ?>-month guarantee from the date of the repair on the work done and the part replaced. It does not cover a different fault, misuse, power surges, or work done by someone else afterwards. Your rights under the Consumer Protection Act are not affected.</p>
<h2>Advice on this website</h2><p>Guides, error codes and chat answers are general information. They cannot replace a test of your own appliance. Do not work on live electrical parts, gas or a geyser yourself.</p>
<h2>Brands</h2><p>Brand names belong to their owners and are used only to describe the appliances we repair. We are an independent service.</p>
</div></section>
<?php siteClose();
