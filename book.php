<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';
require_once __DIR__ . '/includes/media.php';

$errors = [];
$in = ['service' => (string) ($_GET['service'] ?? ''), 'area' => (string) ($_GET['area'] ?? ''), 'full_name' => '', 'phone' => '', 'email' => '', 'address' => '', 'brand' => '', 'problem' => '', 'pref_date' => '', 'pref_slot' => ''];
$days = bookingDays();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($in) as $k) $in[$k] = postText($k, $k === 'problem' ? 2000 : 190);
    $phone = preg_replace('/[^\d+]/', '', $in['phone']);
    if (!csrfValid()) $errors[] = 'Your session expired. Please send it again.';
    if (!empty($_POST['website'])) redirect('book');   // bots fill the hidden field
    if (!isset(services()[$in['service']])) $errors[] = 'Choose the appliance.';
    if (!isset(areas()[$in['area']]) && $in['area'] !== 'other') $errors[] = 'Choose your area.';
    if (mb_strlen($in['full_name']) < 2) $errors[] = 'Enter your name.';
    if (!preg_match('/^(\+27|0)\d{9}$/', $phone)) $errors[] = 'Enter a South African phone number, like 060 123 4567.';
    if ($in['email'] !== '' && !filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Check the email address, or leave it empty.';
    if (!isset($days[$in['pref_date']])) $errors[] = 'Choose a day for the visit.';
    elseif (empty($days[$in['pref_date']][$in['pref_slot']])) $errors[] = 'That time is no longer open. Choose another time.';
    if (empty($_POST['consent'])) $errors[] = 'Tick the box so that we may contact you about this repair.';
    if (!$errors && rateLimited('book', 6, 60)) $errors[] = 'Too many requests from this connection. Please WhatsApp us instead.';

    if (!$errors) {
        $photo = null;
        if (!empty($_FILES['photo']['tmp_name'])) {
            $up = saveUploadedPhoto($_FILES['photo'], 'booking');
            $photo = $up['ok'] ? $up['path'] : null;
        }
        $b = createBooking([
            'full_name' => $in['full_name'], 'phone' => $phone, 'email' => $in['email'] ?: null, 'service' => $in['service'], 'area' => areas()[$in['area']][0] ?? 'Other area', 'address' => $in['address'] ?: null,
            'brand' => $in['brand'] ?: null, 'problem' => $in['problem'] ?: null, 'pref_date' => $in['pref_date'], 'pref_slot' => $in['pref_slot'], 'photo' => $photo, 'source' => 'website',
            'source_page' => mb_substr(postText('from_page'), 0, 255) ?: null, 'referrer' => mb_substr((string) ($_SESSION['first_ref'] ?? ''), 0, 255) ?: null, 'consent_at' => date('Y-m-d H:i:s'),
        ]);
        $_SESSION['last_booking'] = ['ref' => $b['ref'], 'appliance' => services()[$b['service']]['short'], 'area' => $b['area'], 'when' => requestedLabel($b), 'problem' => $b['problem'] ?: '-', 'email' => $b['email']];
        $_SESSION['booking_uncounted'] = true;
        redirectThen('thank-you', function () use ($b) {
            notifyOwnerOfBooking($b);
            if ($b['email']) notifyBooking('booking_received', $b);
        });
    }
}

// /book?service=fridge-repairs is the Google Ads landing page for that appliance: same form, matching headline.
$svc = services()[$in['service']] ?? null;
$wa = 'Hi, I need ' . ($svc ? 'my ' . lcfirst($svc['short']) : 'an appliance') . ' repaired. I am in ';
siteOpen($svc ? $svc['name'] . ' in Soweto & Joburg | Book Today' : 'Book an Appliance Repair in Soweto & Joburg', [
    'path' => 'book', 'crumbs' => [['Book a repair', 'book']], 'description' => 'Book a same-day appliance repair in Soweto or Johannesburg. R' . callout() . ' call-out, free if we repair.',
]);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Book a repair', 'book']]) ?><h1><?= $svc ? e($svc['name']) . ' in Soweto &amp; Joburg' : 'Book a repair' ?></h1><p class="sub">WhatsApp us, or fill in the form. It takes about a minute. R<?= e(callout()) ?> call-out, free if we repair.</p>
  <div class="row"><?= waButton($wa, 'WhatsApp us', 'btn btn-wa btn-lg') ?><a class="btn btn-glass btn-lg" href="#book-form">Fill in the form</a></div></div></div></section>
<section class="sec"><div class="wrap split">
  <form class="form" id="book-form" method="post" enctype="multipart/form-data" data-busy>
    <?= csrfField() ?><input type="hidden" name="from_page" value="<?= e(parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '') ?>">
    <div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><div><?= implode('<br>', array_map('e', $errors)) ?></div></div><?php endif; ?>
    <fieldset class="field"><legend>What is broken?</legend><div class="pick"><?php foreach (services() as $slug => $s): ?><label><input type="radio" name="service" value="<?= $slug ?>" required <?= $in['service'] === $slug ? 'checked' : '' ?>><span><?= icon($s['icon']) ?><?= e($s['short']) ?></span></label><?php endforeach; ?></div></fieldset>
    <div class="field"><label for="brand">Brand or model <small>(if you know it)</small></label><input id="brand" name="brand" value="<?= e($in['brand']) ?>" placeholder="Samsung, Defy, LG…"></div>
    <div class="field"><label for="problem">What is it doing?</label><textarea id="problem" name="problem" placeholder="Fridge is warm but the freezer is cold. It started yesterday."><?= e($in['problem']) ?></textarea></div>
    <div class="field"><label for="photo">Photo <small>(optional, up to 8 MB)</small></label><input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp"></div>
    <?= whenPicker($days, $in['pref_date'], $in['pref_slot']) ?>
    <div class="form__2">
      <div class="field"><label for="area">Area</label><select id="area" name="area" required><option value="">Choose…</option><?php foreach (areas() as $slug => $a): ?><option value="<?= $slug ?>" <?= $in['area'] === $slug ? 'selected' : '' ?>><?= e($a[0]) ?></option><?php endforeach; ?><option value="other" <?= $in['area'] === 'other' ? 'selected' : '' ?>>Somewhere else</option></select></div>
      <div class="field"><label for="address">Suburb or street</label><input id="address" name="address" value="<?= e($in['address']) ?>" autocomplete="street-address"></div>
      <div class="field"><label for="full_name">Your name</label><input id="full_name" name="full_name" required value="<?= e($in['full_name']) ?>" autocomplete="name"></div>
      <div class="field"><label for="phone">Phone or WhatsApp</label><input id="phone" name="phone" type="tel" required value="<?= e($in['phone']) ?>" autocomplete="tel" placeholder="060 123 4567"></div>
    </div>
    <div class="field"><label for="email">Email <small>(so that we can send you the confirmed time)</small></label><input id="email" name="email" type="email" value="<?= e($in['email']) ?>" autocomplete="email"></div>
    <label class="check"><input type="checkbox" name="consent" value="1" required><span>You may contact me about this repair. See the <a href="<?= APP_URL ?>/privacy">privacy notice</a>.</span></label>
    <button class="btn btn-flame btn-lg" type="submit">Send booking</button>
  </form>
  <aside><div class="box box--ink"><h3>Quicker on WhatsApp</h3><p>Send a photo or a video and your area.</p><?= waButton($wa, 'WhatsApp us') ?><?= callButton('btn btn-glass') ?></div><div class="box"><h3>You can count on</h3><ul class="ticks" style="margin:0"><li>Same-day service, 7 days</li><li>Free call-out if we repair</li><li><?= e(biz('biz_guarantee')) ?>-month guarantee</li></ul></div></aside>
</div></section>
<?php siteClose();
