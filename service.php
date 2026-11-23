<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$slug = (string) ($_GET['s'] ?? '');
$s = services()[$slug] ?? null;
if (!$s) notFound();
$c = callout();
$g = biz('biz_guarantee');
$faults = faultsFor($slug);
$codes = array_filter(errorCodes(), fn($x) => $x[5] === $slug);
$low = lcfirst($s['short']);
$faqs = [
    ["How much does a {$low} repair cost?", "It depends on the part that failed, so we test first and then tell you the price. The call-out is R{$c}, and it is free if we do the repair."],
    ["Do you repair the {$low} at my home?", 'Yes. The technician comes to you with the tools and works on it where it stands.'],
    ['Is the repair guaranteed?', "Yes. Repairs carry a {$g}-month guarantee."],
    ["How soon can you come?", 'We offer same-day service, seven days a week. Message early in the day for the best chance of an early slot.'],
];
$wa = "Hi, I need a {$low} repair. I am in ";
siteOpen($s['name'] . ' in Soweto & Joburg | Same Day, R' . $c, [
    'path' => $slug, 'description' => excerpt("{$s['name']} at your home in Soweto and Johannesburg. Same-day service, {$g}-month guarantee, R{$c} call-out that is free if we repair. WhatsApp " . biz('biz_phone') . '.', 160),
    'crumbs' => [['Repairs', 'services'], [$s['name'], $slug]], 'preload' => APP_URL . '/assets/img/work/' . $s['photo'] . '.webp',
    'schema' => [faqSchema($faqs), ['@context' => 'https://schema.org', '@type' => 'Service', 'name' => $s['name'], 'serviceType' => $s['name'], 'url' => APP_URL . '/' . $slug, 'description' => $s['lead'],
        'provider' => ['@id' => APP_URL . '/#business'], 'areaServed' => array_map(fn($a) => ['@type' => 'City', 'name' => $a[0]], array_values(areas())),
        'offers' => ['@type' => 'Offer', 'priceCurrency' => 'ZAR', 'price' => $c, 'description' => 'Call-out fee, free when we do the repair']]],
]);
?>
<section class="top"><div class="wrap top__in">
  <div><?= crumbs([['Repairs', 'services'], [$s['name'], $slug]]) ?><h1><?= e($s['name']) ?> in Soweto &amp; Joburg</h1><p class="sub"><?= e($s['lead']) ?></p>
    <div class="row"><?= waButton($wa, 'WhatsApp us', 'btn btn-wa btn-lg') ?><a class="btn btn-glass btn-lg" href="<?= APP_URL ?>/book?service=<?= $slug ?>">Book online</a></div></div>
  <div class="top__img"><?= photo($s['photo'], $s['name'], '', '(max-width: 820px) 100vw, 420px', true) ?><div class="meter"><?= lcd($s['lcd']) ?></div></div>
</div></section>

<section class="sec"><div class="wrap split">
  <div class="prose">
    <h2 style="margin-top:0">What we fix</h2>
    <div class="chips"><?php foreach ($s['common'] as $x): ?><span><?= e($x) ?></span><?php endforeach; ?></div>
    <?php foreach ($s['body'] as [$h, $t]): ?><h2><?= e($h) ?></h2><p><?= e($t) ?></p><?php endforeach; ?>
    <h2>Fault guides</h2>
    <div class="cards" style="grid-template-columns:repeat(auto-fit,minmax(250px,1fr))"><?php foreach ($faults as $fs => $f): ?><a class="card card--f" href="<?= APP_URL ?>/faults/<?= $fs ?>"><span class="lcdtag"><?= lcd($f['lcd']) ?></span><h3 style="margin:0"><?= e($f['s']) ?></h3></a><?php endforeach; ?></div>
    <?php if ($codes): ?><p><a class="link" href="<?= APP_URL ?>/error-codes">Look up <?= count($codes) ?> <?= e($low) ?> error codes</a></p><?php endif; ?>
    <h2>Photos from our jobs</h2>
    <div class="strip" style="columns:3"><?php foreach (array_slice(array_values(array_filter(workPhotos(), fn($w) => $w[2] === $slug)) ?: workPhotos(), 0, 6) as [$f, $alt]): ?><figure><?= photo($f, $alt, '', '(max-width: 620px) 50vw, 260px') ?></figure><?php endforeach; ?></div>
    <h2>Questions</h2><?= faqHtml($faqs) ?>
  </div>
  <aside>
    <div class="box box--ink"><h3>R<?= e($c) ?> call-out</h3><p>Free when we do the repair. <?= e($g) ?>-month guarantee.</p><?= waButton($wa, 'WhatsApp us') ?><?= callButton('btn btn-glass') ?></div>
    <div class="box"><h3><?= e($s['short']) ?> repairs near you</h3><div class="chips"><?php foreach (areas() as $as => $a): ?><a class="chip" href="<?= APP_URL ?>/areas/<?= $as ?>"><?= e($a[0]) ?></a><?php endforeach; ?></div></div>
  </aside>
</div></section>
<?php if ($posts = latestPosts(3, $slug)): ?><section class="sec sec--tint"><div class="wrap"><div class="sec__head"><h2>Read before you spend</h2></div><div class="posts"><?php foreach ($posts as $p) echo postCard($p); ?></div></div></section><?php endif; ?>
<?= ctaBand('Get your ' . $low . ' working again', $wa) ?>
<?php siteClose();
