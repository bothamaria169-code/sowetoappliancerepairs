<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$slug = (string) ($_GET['s'] ?? '');
$a = areas()[$slug] ?? null;
if (!$a) notFound();
[$name, $where, $intro, $subs, $near, $tip] = $a;
$c = callout();
$g = biz('biz_guarantee');
$photos = array_keys(services());
$idx = array_search($slug, array_keys(areas()), true);
$pool = ['board-multimeter', 'fridge-double-door-open', 'stove-multimeter', 'washer-front-loader-opened', 'fridge-compressor', 'washer-hisense-top-loader', 'hob-oven-white', 'washer-board-out', 'fridge-top-freezer-open', 'washer-samsung-front-loader'];
$cover = $pool[$idx % count($pool)];
$faqs = [
    ["Do you charge extra to come to {$name}?", "The call-out is R{$c}, and it is free if we do the repair."],
    ["Which parts of {$name} do you cover?", "All of it, including " . implode(', ', array_slice($subs, 0, 6)) . '. If your suburb is not listed, send us a WhatsApp.'],
    ["Can you come to {$name} today?", 'We offer same-day service, seven days a week. Message us early in the day for the best chance of an early slot.'],
    ['Is the repair guaranteed?', "Yes, repairs carry a {$g}-month guarantee."],
];
$wa = "Hi, I need an appliance repaired in {$name}.";
$trail = [['Areas', 'areas'], [$name, 'areas/' . $slug]];
siteOpen("Appliance Repairs {$name} | Fridge, Washing Machine, Stove", [
    'path' => 'areas/' . $slug, 'crumbs' => $trail, 'preload' => APP_URL . '/assets/img/work/' . $cover . '.webp',
    'description' => excerpt("Fridge, washing machine, stove, oven and geyser repairs in {$name} at your home. Same day, {$g}-month guarantee. R{$c} call-out, free if we repair.", 160),
    'schema' => [faqSchema($faqs), ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => "Appliance repairs in {$name}", 'serviceType' => 'Appliance repair', 'provider' => ['@id' => APP_URL . '/#business'],
        'areaServed' => ['@type' => 'City', 'name' => $name, 'containedInPlace' => ['@type' => 'AdministrativeArea', 'name' => 'Gauteng']]]],
]);
?>
<section class="top"><div class="wrap top__in">
  <div><?= crumbs($trail) ?><h1>Appliance repairs in <?= e($name) ?></h1><p class="sub"><?= e($intro) ?></p>
    <div class="row"><?= waButton($wa, 'WhatsApp us', 'btn btn-wa btn-lg') ?><a class="btn btn-glass btn-lg" href="<?= APP_URL ?>/book?area=<?= $slug ?>">Book online</a></div></div>
  <div class="top__img"><?= photo($cover, "Appliance repair in {$name}", '', '(max-width: 820px) 100vw, 420px', true) ?></div>
</div></section>
<section class="sec"><div class="wrap split">
  <div class="prose">
    <h2 style="margin-top:0">Repairs we do in <?= e($name) ?></h2>
    <div class="cards" style="grid-template-columns:repeat(auto-fit,minmax(250px,1fr))"><?php foreach (services() as $ss => $s): ?><a class="card" href="<?= APP_URL . '/' . $ss ?>"><?= icon($s['icon']) ?><h3 style="margin:0"><?= e($s['short']) ?> repairs <?= e($name) ?></h3><p><?= e(ucfirst(implode(', ', array_map('lcfirst', array_slice($s['common'], $idx % 3, 4))))) ?>.</p></a><?php endforeach; ?></div>
    <h2>Suburbs we come to</h2><div class="chips"><?php foreach ($subs as $x): ?><span><?= e($x) ?></span><?php endforeach; ?></div>
    <h2>Good to know in <?= e($name) ?></h2><p><?= e($tip) ?></p>
    <h2>Price and guarantee</h2><p>The call-out to <?= e($name) ?> is <strong>R<?= e($c) ?>, and free if we do the repair</strong>. We test the appliance, tell you what failed and what it will cost, and only then start. Repairs are guaranteed for <?= e($g) ?> months.</p>
    <h2>Questions from <?= e($name) ?></h2><?= faqHtml($faqs) ?>
  </div>
  <aside>
    <div class="box box--ink"><h3>We come to <?= e($name) ?></h3><p>Same-day service, seven days a week.</p><?= waButton($wa, 'WhatsApp us') ?><?= callButton('btn btn-glass') ?></div>
    <div class="box"><h3>Nearby</h3><?php foreach ($near as $n): if (isset(areas()[$n])): ?><a href="<?= APP_URL ?>/areas/<?= $n ?>"><?= e(areas()[$n][0]) ?></a><?php endif; endforeach; ?></div>
    <div class="box"><h3>Check it first</h3><?php foreach (array_slice(faults(), ($idx * 3) % 18, 4) as $fs => $f): ?><a href="<?= APP_URL ?>/faults/<?= $fs ?>"><?= e(services()[$f['service']]['short'] . ': ' . lcfirst($f['s'])) ?></a><?php endforeach; ?></div>
  </aside>
</div></section>
<?= ctaBand("Broken appliance in {$name}?", $wa) ?>
<?php siteClose();
