<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
siteOpen('Appliance Repair Services in Soweto & Johannesburg', [
    'path' => 'services', 'crumbs' => [['Repairs', 'services']],
    'description' => 'Fridge, freezer, washing machine, stove, oven, microwave and geyser repairs at your home in Soweto and Johannesburg. Same day, seven days a week.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Repairs', 'services']]) ?><h1>What we repair</h1><p class="sub">Six appliances, repaired at your home. Choose yours to see the faults we fix and what to check first.</p></div></div></section>
<section class="sec"><div class="wrap"><div class="bento"><?php foreach (services() as $slug => $s): ?>
  <a class="bento__i" href="<?= APP_URL . '/' . $slug ?>"><?= photo($s['photo'], $s['name'], '', '(max-width: 700px) 100vw, 600px') ?><div class="bento__t"><?= icon($s['icon']) ?><h2 style="font-size:clamp(1.4rem,2.4vw,2.1rem)"><?= e($s['name']) ?></h2><p><?= e(ucfirst(implode(', ', array_map('lcfirst', array_slice($s['common'], 0, 3))))) ?></p></div></a>
<?php endforeach; ?></div></div></section>
<?= ctaBand('Not sure what is wrong?', 'Hi, I am not sure what is wrong with my appliance.', 'Send a short video on WhatsApp. You do not need to know the name of the part.') ?>
<?php siteClose();
