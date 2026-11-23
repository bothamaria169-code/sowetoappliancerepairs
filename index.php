<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$c = callout();
$g = biz('biz_guarantee');
$faqs = siteFaqs();
$posts = latestPosts(3);
$reviews = getDB()->query('SELECT * FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 6')->fetchAll();

// Fault finder data for the home page tool.
$finder = [];
foreach (services() as $slug => $s) {
    $list = [];
    foreach (faultsFor($slug) as $fs => $f) $list[] = ['s' => $f['s'], 'u' => APP_URL . '/faults/' . $fs, 'l' => $f['lcd'], 'c' => array_slice(array_column($f['causes'], 0), 0, 3), 'k' => $f['checks'][0]];
    $finder[$slug] = ['name' => $s['short'], 'faults' => $list];
}

siteOpen('Appliance Repairs in Soweto & Joburg | Same Day, R' . $c . ' Call-Out', [
    'path' => '', 'class' => 'home',
    'description' => "Fridge, washing machine, stove, oven, microwave and geyser repairs at your home in Soweto and Johannesburg. Same-day service, {$g}-month guarantee. R{$c} call-out, free if we repair.",
    'preload' => APP_URL . '/assets/img/work/board-multimeter.webp',
    'schema' => [faqSchema($faqs), ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => APP_NAME, 'url' => APP_URL . '/']],
]);
?>
<section class="hero">
  <div class="hero__photo"><?= photo('board-multimeter', 'Technician testing a washing machine control board with an orange multimeter', '', '(max-width: 900px) 100vw, 55vw', true) ?></div>
  <div class="wrap hero__in">
    <div class="hero__txt">
      <h1>Appliance repairs in Soweto <em>&amp; Joburg</em></h1>
      <p class="hero__lead">We come to your home and fix the fridge, washing machine, stove, oven, microwave or geyser right there. Same day, seven days a week.</p>
      <div class="hero__btns">
        <?= waButton('Hi, I need an appliance repaired.', 'WhatsApp us', 'btn btn-wa btn-lg') ?>
        <a class="btn btn-flame btn-lg" href="<?= APP_URL ?>/book">Book a repair</a>
      </div>
      <p class="hero__tel">or call <a href="tel:<?= e(telIntl()) ?>" data-tap="call"><?= e(biz('biz_phone')) ?></a></p>
    </div>
    <div class="meter hero__meter" id="heroMeter">
      <div class="meter__top"><span id="heroLabel">Fridge</span><span class="meter__unit" id="heroUnit">°C</span></div>
      <?= lcd('  28', 'lcd--hero') ?>
      <div class="meter__bot" id="heroNote">Too warm. Food at risk.</div>
    </div>
    <div class="sticker" aria-label="Call-out R<?= e($c) ?>, free if we repair">
      <svg viewBox="0 0 200 200" aria-hidden="true"><circle cx="100" cy="100" r="92" fill="none" stroke="currentColor" stroke-width="2.5" stroke-dasharray="3 8" stroke-linecap="round"/></svg>
      <span>Call-out</span><strong>R<?= e($c) ?></strong><b>FREE if<br>we repair</b>
    </div>
  </div>
</section>

<div class="marquee" aria-hidden="true"><div class="marquee__in"><?php for ($i = 0; $i < 2; $i++): ?><span>Fridges</span><i></i><span>Washing machines</span><i></i><span>Stoves</span><i></i><span>Ovens</span><i></i><span>Microwaves</span><i></i><span>Geysers</span><i></i><span><?= e($g) ?>-month guarantee</span><i></i><span>Same-day service</span><i></i><?php endfor; ?></div></div>

<section class="sec finder" id="finder">
  <div class="wrap finder__grid">
    <div>
      <h2>What is it doing?</h2>
      <p class="sub">Pick the appliance and the symptom. You get the likely causes and one safe thing to check, before you pay anyone.</p>
      <div class="finder__apps" role="tablist" aria-label="Appliance">
        <?php $first = true; foreach (services() as $slug => $s): ?><button role="tab" type="button" data-app="<?= $slug ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"><?= icon($s['icon']) ?><span><?= e($s['short']) ?></span></button><?php $first = false; endforeach; ?>
      </div>
      <div class="finder__list" id="finderList"><?php foreach (faultsFor('fridge-repairs') as $fs => $f): ?><a href="<?= APP_URL ?>/faults/<?= $fs ?>"><?= e($f['s']) ?></a><?php endforeach; ?></div>
      <p class="finder__more"><a href="<?= APP_URL ?>/faults">All fault guides</a><a href="<?= APP_URL ?>/error-codes">Look up an error code</a></p>
    </div>
    <div class="tester" id="tester">
      <div class="tester__screen"><?= lcd('  28', 'lcd--tester') ?></div>
      <div class="tester__body">
        <h3 id="tTitle">Fridge: not cooling</h3>
        <p class="tester__k">Likely causes</p><ul id="tCauses"><?php foreach (array_slice(faults()['fridge-not-cooling']['causes'], 0, 3) as $x): ?><li><?= e($x[0]) ?></li><?php endforeach; ?></ul>
        <p class="tester__k">Check this first</p><p id="tCheck"><?= e(faults()['fridge-not-cooling']['checks'][0]) ?></p>
        <div class="row"><a class="btn btn-flame" id="tMore" href="<?= APP_URL ?>/faults/fridge-not-cooling">Full guide</a><a class="btn btn-wa" id="tWa" target="_blank" rel="noopener" data-tap="whatsapp" data-base="<?= e(waLink()) ?>" href="<?= e(waLink('Hi, my fridge is not cooling. Can you come and look?')) ?>"><i class="fa-brands fa-whatsapp"></i>Send to a technician</a></div>
      </div>
    </div>
  </div>
</section>

<section class="sec sec--ink price">
  <div class="wrap price__grid">
    <div class="price__big"><span class="price__lbl">Call-out fee</span><span class="price__now">R<?= e($c) ?></span><span class="price__tag">Free if we repair</span></div>
    <div>
      <h2>The call-out is free when we do the repair</h2>
      <p class="sub">We come to you and test the appliance for R<?= e($c) ?>. Give us the go-ahead to repair it and the call-out falls away. You pay for the repair, nothing for the trip.</p>
      <ul class="ticks"><li><b><?= e($g) ?>-month guarantee</b> on the repair</li><li><b>Same-day service</b>, seven days a week</li><li><b>No bakkie needed.</b> The appliance stays in your kitchen</li></ul>
      <a class="link" href="<?= APP_URL ?>/prices">How our prices work</a>
    </div>
  </div>
</section>

<section class="sec">
  <div class="wrap">
    <div class="sec__head"><h2>Six appliances. One call.</h2></div>
    <div class="lineup"><?= photo('lineup', 'Fridge, stove, oven and washing machines repaired by Soweto Appliance Repairs', '', '(max-width: 1240px) 100vw, 1200px') ?></div>
    <div class="five"><?php foreach (services() as $slug => $s): ?><a href="<?= APP_URL . '/' . $slug ?>"><?= icon($s['icon']) ?><h3><?= e($s['short']) ?></h3><p><?= e(ucfirst(implode(', ', array_map('lcfirst', array_slice($s['common'], 0, 2))))) ?></p></a><?php endforeach; ?></div>
  </div>
</section>

<section class="sec sec--tint">
  <div class="wrap">
    <div class="sec__head"><h2>From broken to working in four steps</h2></div>
    <ol class="steps">
      <li><?= lcd('01') ?><h3>Send a photo</h3><p>WhatsApp the appliance, the model sticker and your area. A video of the noise or the code helps.</p></li>
      <li><?= lcd('02') ?><h3>We come to you</h3><p>Same day where we can, to your house, flat or complex.</p></li>
      <li><?= lcd('03') ?><h3>We test it</h3><p>Meter in hand, we find the part that failed and tell you the price.</p></li>
      <li><?= lcd('04') ?><h3>It works again</h3><p>We repair it, run it in front of you, and guarantee the repair for <?= e($g) ?> months.</p></li>
    </ol>
  </div>
</section>

<section class="sec work">
  <div class="wrap">
    <div class="sec__head sec__head--row"><h2>Real jobs, real kitchens</h2><a class="link" href="<?= APP_URL ?>/our-work">See all our work</a></div>
    <div class="strip"><?php foreach (array_slice(workPhotos(), 0, 8) as [$f, $alt]): ?><figure><?= photo($f, $alt, '', '(max-width: 700px) 60vw, 300px') ?></figure><?php endforeach; ?></div>
  </div>
</section>

<section class="sec sec--ink codes">
  <div class="wrap codes__grid">
    <div><h2>Is it showing a code?</h2><p class="sub">Samsung, LG and Hisense displays tell you which system complained. Look yours up.</p>
      <form class="codes__form" action="<?= APP_URL ?>/error-codes" method="get"><label class="sr" for="hcode">Error code</label><input id="hcode" name="q" maxlength="12" placeholder="Type a code, e.g. 5E"><button class="btn btn-flame">Look up</button></form></div>
    <div class="codes__chips"><?php foreach (['5E', '4E', 'UE', 'dE', 'OE', 'LE', 'F03', '3E'] as $code): ?><a href="<?= APP_URL ?>/error-codes?q=<?= $code ?>" aria-label="Error code <?= $code ?>"><?= lcd($code) ?></a><?php endforeach; ?></div>
  </div>
</section>

<section class="sec" id="areas">
  <div class="wrap">
    <div class="sec__head"><h2>We work in 20 areas</h2><p class="sub">Type your suburb to check.</p></div>
    <form class="areaf" id="areaForm"><label class="sr" for="areaQ">Your suburb</label><i class="fa-solid fa-location-dot"></i><input id="areaQ" autocomplete="off" placeholder="Diepkloof, Wilgeheuwel, Tembisa…"><output id="areaOut" aria-live="polite"></output></form>
    <div class="areas"><?php foreach (areas() as $slug => $a): ?><a href="<?= APP_URL ?>/areas/<?= $slug ?>" data-sub="<?= e(mb_strtolower($a[0] . '|' . implode('|', $a[3]))) ?>" data-name="<?= e($a[0]) ?>"><?= e($a[0]) ?></a><?php endforeach; ?></div>
  </div>
</section>

<?php if ($reviews): ?>
<section class="sec sec--tint"><div class="wrap"><div class="sec__head sec__head--row"><h2>What customers say</h2><a class="link" href="<?= APP_URL ?>/reviews">All reviews</a></div>
  <div class="quotes"><?php foreach ($reviews as $r): ?><blockquote><div class="stars" aria-label="<?= (int) $r['stars'] ?> out of 5"><?= str_repeat('★', (int) $r['stars']) ?></div><p><?= e($r['body']) ?></p><footer><?= e($r['full_name']) ?><?= $r['area'] ? ', ' . e($r['area']) : '' ?></footer></blockquote><?php endforeach; ?></div></div></section>
<?php endif; ?>

<?php if ($posts): ?>
<section class="sec sec--tint"><div class="wrap"><div class="sec__head sec__head--row"><h2>Advice before you spend</h2><a class="link" href="<?= APP_URL ?>/blog">All articles</a></div>
  <div class="posts"><?php foreach ($posts as $p) echo postCard($p); ?></div></div></section>
<?php endif; ?>

<section class="sec"><div class="wrap two"><div><h2>Good to know</h2><p class="sub">Still unsure? Ask in the chat, or send a WhatsApp.</p><?= waButton('Hi, I have a question about a repair.', 'Ask on WhatsApp') ?></div><?= faqHtml($faqs) ?></div></section>
<?= ctaBand() ?>
<?php siteClose('<script type="application/json" id="finderData">' . json_encode($finder, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>');
