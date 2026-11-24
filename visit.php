<?php
declare(strict_types=1);

// The customer's private page: confirm the visit, ask for another time, or cancel.
require_once __DIR__ . '/includes/boot.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: private, no-store');

$token = (string) ($_GET['t'] ?? $_POST['t'] ?? '');
$b = null;
if (preg_match('/^[a-f0-9]{32}$/', $token) && !rateLimited('visit', 60, 60)) {
    $s = getDB()->prepare('SELECT * FROM bookings WHERE token = ?');
    $s->execute([$token]);
    $b = $s->fetch() ?: null;
}
if (!$b) notFound();

$open = !$b['lost_at'] && !$b['repaired_at'];
$days = $open ? bookingDays((int) $b['id']) : [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $open) {
    $act = (string) ($_POST['action'] ?? '');
    $id = (int) $b['id'];
    $db = getDB();
    $note = '';
    if (!csrfValid()) $errors[] = 'Your session expired. Please try again.';
    elseif (rateLimited('visitpost', 12, 60)) $errors[] = 'Too many changes. Please WhatsApp us.';
    elseif ($act === 'confirm' && $b['visit_date']) {
        $db->prepare('UPDATE bookings SET confirmed_at = NOW(), needs_reply = 0, reply_note = ? WHERE id = ?')->execute([$note = 'Confirmed ' . visitLabel($b), $id]);
        flash('success', 'Thank you. Your visit is confirmed.');
    } elseif ($act === 'change') {
        $d = postText('pref_date', 10);
        $sl = postText('pref_slot', 11);
        if (empty($days[$d][$sl])) $errors[] = 'Choose a day and a time that is still open.';
        else {
            $note = 'Asked for ' . requestedLabel(['pref_date' => $d, 'pref_slot' => $sl]);
            $db->prepare('UPDATE bookings SET pref_date = ?, pref_slot = ?, confirmed_at = NULL, needs_reply = 1, reply_note = ? WHERE id = ?')->execute([$d, $sl, $note, $id]);
            flash('success', 'Thank you. We have your new time and will confirm it.');
        }
    } elseif ($act === 'cancel') {
        $note = 'Cancelled the request';
        $db->prepare('UPDATE bookings SET needs_reply = 1, reply_note = ? WHERE id = ?')->execute([$note, $id]);
        setTick($id, 'lost', true, 'Cancelled by the customer');
        flash('success', 'Your request is cancelled.');
    }
    if (!$errors && $note !== '') {
        logLead($id, 'customer', 'Customer: ' . $note);
        $fresh = findBooking($id);
        redirectThen('visit?t=' . $token, fn() => notifyBooking('customer_reply', $fresh));
    }
}

$svc = services()[$b['service']] ?? null;
siteOpen('Your visit | ' . APP_NAME, ['path' => 'visit', 'noindex' => true, 'description' => 'Confirm or change your visit.']);
?>
<section class="top top--plain"><div class="wrap top__in"><div><h1>Your visit</h1><p class="sub">Reference <?= e($b['ref']) ?>, <?= e(lcfirst($svc['short'] ?? 'appliance')) ?> in <?= e($b['area']) ?></p></div></div></section>
<section class="sec"><div class="wrap split"><div>
  <?= flashHtml() ?>
  <?php if ($errors): ?><div class="alert alert-error" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= e(implode(' ', $errors)) ?></span></div><?php endif; ?>
  <?php if ($b['lost_at']): ?>
    <div class="vcard"><span>This request is closed.</span><p>Want us to come after all? WhatsApp us and mention <?= e($b['ref']) ?>.</p></div>
  <?php elseif ($b['repaired_at']): ?>
    <div class="vcard"><span>This repair is done.</span><p>The guarantee is <?= e(biz('biz_guarantee')) ?> months. Something not right? WhatsApp us and mention <?= e($b['ref']) ?>.</p></div>
  <?php else: ?>
    <div class="vcard"><?php if ($b['visit_date']): ?><span><?= $b['confirmed_at'] ? 'Confirmed' : 'We plan to come on' ?></span><b><?= e(visitLabel($b)) ?></b>
      <?php if (!$b['confirmed_at']): ?><form method="post" data-busy><?= csrfField() ?><input type="hidden" name="t" value="<?= e($token) ?>"><input type="hidden" name="action" value="confirm"><button class="btn btn-flame btn-lg" type="submit">Yes, that suits me</button></form><?php endif; ?>
    <?php else: ?><span>You asked for</span><b><?= e(requestedLabel($b) ?: 'No time chosen yet') ?></b><p>We will confirm the time with you.</p><?php endif; ?>
    <?php if ($b['needs_reply'] && $b['visit_date']): ?><p>You asked for <?= e(requestedLabel($b)) ?> instead. We will confirm it.</p><?php endif; ?></div>
    <form class="form" method="post" data-busy><?= csrfField() ?><input type="hidden" name="t" value="<?= e($token) ?>"><input type="hidden" name="action" value="change">
      <h2 style="font-size:1.7rem"><?= $b['visit_date'] ? 'Choose another time' : 'Choose a time' ?></h2><?= whenPicker($days) ?>
      <?php if ($days): ?><button class="btn btn-line btn-lg" type="submit">Ask for this time</button><?php endif; ?></form>
    <form method="post" data-confirm="Cancel this request?" style="margin-top:2rem"><?= csrfField() ?><input type="hidden" name="t" value="<?= e($token) ?>"><input type="hidden" name="action" value="cancel"><button class="btn btn-line" type="submit">I no longer need the visit</button></form>
  <?php endif; ?>
</div>
<aside><div class="box box--ink"><h3>Questions?</h3><p>Mention <?= e($b['ref']) ?>.</p><?= waButton("Hi, about my visit, ref {$b['ref']}. ", 'WhatsApp us') ?><?= callButton('btn btn-glass') ?></div></aside></div></section>
<?php siteClose();
