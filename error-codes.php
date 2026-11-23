<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$q = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 20);
siteOpen('Washing Machine & Fridge Error Codes: Samsung, LG, Hisense', [
    'path' => 'error-codes', 'crumbs' => [['Error codes', 'error-codes']],
    'description' => 'Look up Samsung, LG and Hisense error codes such as 5E, 4E, UE, OE and dE. What each code means and what you can safely try first.',
    'schema' => [faqSchema(array_map(fn($x) => ["What does {$x[2]} mean on a {$x[0]} " . lcfirst($x[1]) . '?', $x[3] . '. ' . $x[4]], array_slice(errorCodes(), 0, 20)))],
]);
?>
<section class="top"><div class="wrap top__in"><div><?= crumbs([['Error codes', 'error-codes']]) ?><h1>Error codes, explained</h1><p class="sub">A code names the system that complained, not the part that failed. Look yours up, try the safe step, and call us if it comes back.</p></div><div class="meter" style="padding:1.8rem 2rem"><?= lcd('5E', 'lcd--hero') ?></div></div></section>
<section class="sec"><div class="wrap">
  <form class="searchbar"><label class="sr" for="codeSearch">Code or brand</label><input id="codeSearch" name="q" value="<?= e($q) ?>" placeholder="Type a code or brand: 5E, OE, Samsung, LG fridge" autocomplete="off"><button class="btn btn-flame">Look up</button></form>
  <p id="codeNone" hidden>We do not have that code listed. <?= waButton('Hi, my appliance shows this code: ' . $q, 'Send us the code', 'btn btn-wa') ?></p>
  <table class="ctable"><thead><tr><th scope="col">Code</th><th scope="col">Appliance</th><th scope="col">What it means</th></tr></thead><tbody>
  <?php foreach (errorCodes() as [$brand, $app, $code, $mean, $try, $svc]): ?>
    <tr data-code="<?= e(mb_strtolower("$code $brand $app $brand $code")) ?>"><td><span class="lcdtag"><?= lcd($code, '', $code) ?></span></td><td><strong><?= e($brand) ?></strong><br><a href="<?= APP_URL . '/' . $svc ?>"><?= e($app) ?></a></td><td><strong><?= e($mean) ?></strong><p><?= e($try) ?></p></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <p class="sub">Codes differ between models and years, so check your manual if it does not match. We are an independent repair service and not linked to Samsung, LG or Hisense.</p>
</div></section>
<?= ctaBand('Code keeps coming back?', 'Hi, my appliance shows an error code: ') ?>
<?php siteClose();
