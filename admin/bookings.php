<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();

function deleteLead(int $id): void
{
    if (!($b = findBooking($id))) return;
    if ($b['photo'] && str_starts_with($b['photo'], 'uploads/media/')) @unlink(MEDIA_DIR . basename($b['photo']));
    getDB()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
    audit('lead_delete', $b['ref']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $act = (string) ($_POST['action'] ?? '');
    $back = 'admin/bookings.php';

    if ($act === 'bulk') {
        $ids = array_slice(array_unique(array_map('intval', (array) ($_POST['ids'] ?? []))), 0, 200);
        $do = (string) ($_POST['do'] ?? '');
        foreach ($ids as $x) {
            if ($do === 'delete') deleteLead($x);
            elseif (isset(LEAD_TICKS[$do])) setTick($x, $do, true);
        }
        flash($ids && $do ? 'success' : 'error', $ids && $do ? count($ids) . ($do === 'delete' ? ' leads deleted.' : ' leads updated.') : 'Tick the leads on the left, and choose what to do.');
        redirect($back);
    }
    if ($act === 'add') {
        $phone = preg_replace('/[^\d+]/', '', postText('phone', 40));
        $email = postText('email', 190);
        if (mb_strlen(postText('full_name')) < 2 || !preg_match('/^(\+27|0)\d{9}$/', $phone) || !isset(services()[$_POST['service'] ?? ''])) { flash('error', 'Fill in the name, a South African phone number and the appliance.'); redirect('admin/bookings.php?add=1'); }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Check the email address.'); redirect('admin/bookings.php?add=1'); }
        $b = createBooking(['full_name' => postText('full_name', 120), 'phone' => $phone, 'email' => $email ?: null, 'service' => (string) $_POST['service'], 'area' => areas()[$_POST['area'] ?? ''][0] ?? 'Other area',
            'address' => postText('address') ?: null, 'brand' => postText('brand', 80) ?: null, 'problem' => postText('problem', 2000) ?: null, 'source' => 'manual', 'admin_notified_at' => date('Y-m-d H:i:s')]);
        flash('success', 'Lead added. Choose a date and time below.');
        redirect('admin/bookings.php?id=' . $b['id']);
    }
    $b = findBooking($id);
    if (!$b) redirect($back);
    $back .= '?id=' . $id;

    if ($act === 'delete') { deleteLead($id); flash('success', 'Lead deleted.'); redirect('admin/bookings.php'); }
    if ($act === 'seen') { $db->prepare('UPDATE bookings SET needs_reply = 0 WHERE id = ?')->execute([$id]); redirect($back); }
    if ($act === 'resend') {
        $err = notifyOwnerOfBooking($b);
        flash($err === '' ? 'success' : 'error', $err === '' ? 'Sent to ' . implode(', ', notifyEmails()) . '.' : 'Not sent: ' . $err);
        redirect($back);
    }
    if ($act === 'details') {
        $q = str_replace([',', ' '], ['.', ''], postText('quoted', 12));
        $email = postText('email', 190);
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Check the email address.'); redirect($back); }
        $db->prepare('UPDATE bookings SET full_name = ?, email = ?, address = ?, brand = ?, problem = ?, notes = ?, quoted = ?, updated_at = NOW() WHERE id = ?')
           ->execute([postText('full_name', 120) ?: $b['full_name'], $email ?: null, postText('address') ?: null, postText('brand', 80) ?: null, postText('problem', 2000) ?: null, postText('notes', 3000) ?: null, is_numeric($q) ? $q : null, $id]);
        flash('success', 'Saved.');
        redirect($back);
    }
    if (in_array($act, ['plan', 'send'], true)) {
        $key = (string) ($_POST['key'] ?? '');
        $d = postText('visit_date', 10);
        $f = postText('visit_from', 5);
        $t = postText('visit_to', 5);
        $needs = in_array($key, ['visit_confirmed', 'visit_changed', 'visit_reminder'], true);
        if ($d !== '' && (!validDate($d) || !validTime($f) || ($t !== '' && (!validTime($t) || $t <= $f)))) { flash('error', 'Check the date and times: the end must be after the start.'); redirect($back); }
        if ($act === 'send' && $needs && $d === '') { flash('error', 'Choose a date and time first.'); redirect($back); }
        if ($act === 'send' && (!isset(templateDefaults()[$key]) || templateDefaults()[$key]['to'] !== 'customer')) redirect($back);
        $changed = false;
        if ($act === 'send' && $key === 'visit_cancelled') planVisit($id, '', '', '');
        else [, $changed] = planVisit($id, $d, $f, $t);
        if ($act === 'send') {
            // Text is sent as it stands in the boxes, so check that it matches the date that was just saved.
            $err = notifyBooking($key, findBooking($id), mb_substr(postText('subject', 250), 0, 250), mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 6000));
            if ($err === '') {
                if (!findBooking($id)['contacted_at']) setTick($id, 'contacted', true);
                if ($key === 'not_going_ahead') setTick($id, 'lost', true);
                if ($key === 'visit_reminder') $db->prepare('UPDATE bookings SET reminder_sent_at = NOW() WHERE id = ?')->execute([$id]);
            }
            flash($err === '' ? 'success' : 'error', $err === '' ? 'Emailed to ' . $b['email'] . '.' : 'The email was not sent: ' . $err . ' The date is saved. Try WhatsApp, or check Settings → Email.');
        } else {
            flash('success', $changed ? 'Visit saved. The customer has not been told yet.' : 'Saved.');
        }
        redirect($back);
    }
    redirect($back);
}

// ── CSV ───────────────────────────────────────────────────────────
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $safe = fn($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'" . $v : (string) $v;   // no formulas in Excel
    fputcsv($out, ['Reference', 'Date', 'Name', 'Phone', 'Email', 'Appliance', 'Brand', 'Area', 'Address', 'Problem', 'Asked for', 'Visit', 'Contacted', 'Went to site', 'Repaired', 'Not going ahead', 'Reason', 'Stage', 'Quoted', 'Notes']);
    foreach ($db->query('SELECT * FROM bookings ORDER BY created_at DESC') as $b) {
        fputcsv($out, array_map($safe, [$b['ref'], $b['created_at'], $b['full_name'], $b['phone'], $b['email'], services()[$b['service']]['short'] ?? '', $b['brand'], $b['area'], $b['address'], $b['problem'], requestedLabel($b), visitLabel($b),
            $b['contacted_at'] ? 'Yes' : '', $b['went_at'] ? 'Yes' : '', $b['repaired_at'] ? 'Yes' : '', $b['lost_at'] ? 'Yes' : '', $b['lost_reason'], BOOKING_STATUSES[$b['status']] ?? '', $b['quoted'], $b['notes']]));
    }
    exit;
}

/** The four tick boxes of one lead. */
function tickBoxes(array $b): string
{
    $out = '<div class="ticks" data-lead="' . (int) $b['id'] . '">';
    foreach (LEAD_TICKS as $k => [$col, $label]) $out .= '<label class="tk tk--' . $k . '" title="' . ($b[$col] ? e($label . ' ' . date('j M H:i', strtotime($b[$col]))) : e($label)) . '"><input type="checkbox" data-tick="' . $k . '" ' . ($b[$col] ? 'checked' : '') . '><span>' . e($label) . '</span></label>';
    return $out . '</div>';
}

function stageTag(array $b): string
{
    $c = ['new' => 'tag-on', 'contacted' => 'tag-user', 'booked' => 'tag-google', 'visited' => 'tag-admin', 'done' => 'tag-on', 'lost' => 'tag-off'][$b['status']] ?? 'tag-user';
    return '<span class="tag ' . $c . '" data-stage="' . (int) $b['id'] . '">' . e(BOOKING_STATUSES[$b['status']] ?? $b['status']) . '</span>';
}

// ── Add a lead ────────────────────────────────────────────────────
if (isset($_GET['add'])) {
    adminOpen('bookings', 'Add a lead', 'For people who called or sent a WhatsApp.', '<a class="btn btn-ghost" href="bookings.php">← All leads</a>');
    ?>
    <form class="card" method="post" style="max-width:820px"><div class="card__body"><?= csrfField() ?><input type="hidden" name="action" value="add"><div class="form-grid">
      <div class="field"><label for="full_name">Name</label><input id="full_name" name="full_name" required></div><div class="field"><label for="phone">Phone</label><input id="phone" name="phone" type="tel" required placeholder="082 123 4567"></div>
      <div class="field"><label for="email">Email <span class="hint">optional</span></label><input id="email" name="email" type="email"></div><div class="field"><label for="service">Appliance</label><select id="service" name="service" required><option value="">Choose…</option><?php foreach (services() as $k => $s): ?><option value="<?= $k ?>"><?= e($s['short']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="area">Area</label><select id="area" name="area"><option value="">Other area</option><?php foreach (areas() as $k => $a): ?><option value="<?= $k ?>"><?= e($a[0]) ?></option><?php endforeach; ?></select></div><div class="field"><label for="address">Address</label><input id="address" name="address"></div>
      <div class="field"><label for="brand">Brand or model</label><input id="brand" name="brand"></div><div class="field full"><label for="problem">Problem</label><textarea id="problem" name="problem"></textarea></div></div><button class="btn btn-primary">Add lead</button></div></form>
    <?php
    adminClose();
    exit;
}

// ── One lead ──────────────────────────────────────────────────────
if (isset($_GET['id'])) {
    $b = findBooking((int) $_GET['id']);
    if (!$b) redirect('admin/bookings.php');
    $svc = services()[$b['service']]['short'] ?? $b['service'];
    $ev = $db->prepare('SELECT * FROM booking_events WHERE booking_id = ? ORDER BY id DESC LIMIT 80');
    $ev->execute([$b['id']]);
    $events = $ev->fetchAll();
    $slots = bookingSlots();
    [$pf, $pt] = $b['pref_slot'] ? explode('-', $b['pref_slot']) : ['', ''];
    // A fresh lead starts with the day and time the customer asked for.
    $ask = !$b['visit_date'] || $b['needs_reply'];
    $vd = $ask ? (string) ($b['pref_date'] ?: $b['visit_date']) : (string) $b['visit_date'];
    $vf = $ask && $pf ? $pf : substr((string) $b['visit_from'], 0, 5);
    $vt = $ask && $pt ? $pt : substr((string) $b['visit_to'], 0, 5);
    $key = match (true) { (bool) $b['repaired_at'] => 'repair_done', (bool) $b['lost_at'] => 'not_going_ahead', !$b['visit_date'] => 'visit_confirmed', (bool) $b['needs_reply'] => 'visit_changed', default => 'visit_confirmed' };
    $tpl = template($key);
    $x = $vd && $vf ? ['visit_date' => $vd, 'visit_from' => $vf, 'visit_to' => $vt ?: null] + $b : $b;
    adminOpen('bookings', $b['full_name'], e($b['ref']) . ', ' . e(timeAgo($b['created_at'])) . ' ' . stageTag($b), '<a class="btn btn-ghost" href="bookings.php">← All leads</a><a class="btn btn-ghost" data-touch="call" data-id="' . (int) $b['id'] . '" href="tel:+' . e(intlPhone($b['phone'])) . '"><i class="fa-solid fa-phone"></i>' . e($b['phone']) . '</a>');
    ?>
    <?php if ($b['needs_reply']): ?><div class="beat bad"><i class="fa-solid fa-comment-dots"></i><span><strong>The customer answered:</strong> <?= e($b['reply_note']) ?></span><form method="post" style="margin-left:auto"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="seen"><button class="btn-xs">Got it</button></form></div><?php endif; ?>
    <?php if (!$b['admin_notified_at'] && $b['notify_tries']): ?><div class="beat bad"><i class="fa-solid fa-triangle-exclamation"></i><span>The email about this lead did not reach you yet (<?= (int) $b['notify_tries'] ?> tries). Check <a href="settings.php?tab=email">Settings → Email</a> and <a href="emails.php?failed=1">Sent log</a>.</span></div><?php endif; ?>
    <div class="card" style="margin-bottom:1.2rem"><div class="card__body"><?= tickBoxes($b) ?><?php if ($b['lost_reason']): ?><p class="hint" style="margin-top:.6rem">Reason: <?= e($b['lost_reason']) ?></p><?php endif; ?></div></div>
    <div class="grid-2" style="align-items:start">
      <form class="card" method="post" id="planForm" data-id="<?= (int) $b['id'] ?>"><div class="card__head"><h2>Choose the visit and tell the customer</h2></div><div class="card__body"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
        <?php if (requestedLabel($b)): ?><p style="margin-bottom:.8rem">They asked for <strong><?= e(requestedLabel($b)) ?></strong>.<?= $b['confirmed_at'] ? ' <span class="tag tag-on">Customer confirmed the visit</span>' : '' ?></p><?php endif; ?>
        <div class="form-grid" style="grid-template-columns:1.3fr 1fr 1fr">
          <div class="field"><label for="visit_date">Date</label><input id="visit_date" type="date" name="visit_date" value="<?= e($vd) ?>"></div>
          <div class="field"><label for="visit_from">From</label><input id="visit_from" type="time" name="visit_from" value="<?= e($vf) ?>"></div>
          <div class="field"><label for="visit_to">To</label><input id="visit_to" type="time" name="visit_to" value="<?= e($vt) ?>"></div></div>
        <div class="chips" style="margin:-.3rem 0 1rem"><?php foreach ($slots as $s): ?><button type="button" class="btn-xs" data-slot="<?= e($s) ?>"><?= e(slotLabel($s)) ?></button><?php endforeach; ?></div>
        <div class="field"><label for="key">Message <span class="hint"><a href="templates.php">Edit the templates</a></span></label><select id="key" name="key"><?php foreach (templateDefaults() as $k => $t): if ($t['to'] === 'customer'): ?><option value="<?= $k ?>" <?= $k === $key ? 'selected' : '' ?>><?= e($t['name']) ?></option><?php endif; endforeach; ?></select></div>
        <div class="tabs" style="margin-bottom:.8rem"><a href="#" data-pane="mail" class="<?= $b['email'] ? 'is-on' : '' ?>">Email</a><a href="#" data-pane="wa" class="<?= $b['email'] ? '' : 'is-on' ?>">WhatsApp</a></div>
        <div data-panes="mail" <?= $b['email'] ? '' : 'hidden' ?>>
          <?php if (!$b['email']): ?><p class="alert alert-info">This customer left no email address. Use WhatsApp, or add an address under Details.</p><?php endif; ?>
          <div class="field"><label for="subject">Subject</label><input id="subject" name="subject" maxlength="250" value="<?= e(fillText($tpl['subject'], $x)) ?>"></div>
          <div class="field"><label for="message">Message <span class="hint">change anything before you send</span></label><textarea id="message" name="message" style="min-height:250px"><?= e(fillText($tpl['body'], $x)) ?></textarea></div></div>
        <div data-panes="wa" <?= $b['email'] ? 'hidden' : '' ?>><div class="field"><label for="wa">WhatsApp message <span class="hint">to <?= e($b['phone']) ?></span></label><textarea id="wa" name="wa" style="min-height:170px"><?= e(fillText($tpl['wa'], $x, true)) ?></textarea></div></div>
        <p class="hint" id="stale" hidden>You changed the text, so it was kept. <a href="#" id="refill">Fill in the template again</a></p>
        <div class="form-foot"><button class="btn btn-primary" name="action" value="send" <?= $b['email'] ? '' : 'disabled' ?>><i class="fa-solid fa-envelope"></i>Save and email</button><button class="btn btn-dark" type="button" id="waSend"><i class="fa-brands fa-whatsapp"></i>Save and WhatsApp</button><button class="btn btn-ghost" name="action" value="plan">Save only</button></div>
      </div></form>
      <div>
        <form class="card" method="post"><div class="card__head"><h2>Details</h2></div><div class="card__body"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="details">
          <dl class="kv" style="margin-bottom:1rem"><?php foreach (['Appliance' => $svc, 'Area' => $b['area'], 'Phone' => $b['phone'], 'Asked for' => requestedLabel($b), 'Visit' => visitLabel($b), 'Came from' => $b['source'] === 'manual' ? 'Added by hand' : $b['referrer'], 'Page' => $b['source_page']] as $k => $v): if ($v): ?><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd><?php endif; endforeach; ?></dl>
          <div class="form-grid"><div class="field"><label for="full_name">Name</label><input id="full_name" name="full_name" value="<?= e($b['full_name']) ?>"></div><div class="field"><label for="email">Email</label><input id="email" name="email" type="email" value="<?= e($b['email']) ?>"></div>
          <div class="field"><label for="address">Address</label><input id="address" name="address" value="<?= e($b['address']) ?>"></div><div class="field"><label for="brand">Brand or model</label><input id="brand" name="brand" value="<?= e($b['brand']) ?>"></div></div>
          <div class="field"><label for="problem">Problem</label><textarea id="problem" name="problem" style="min-height:70px"><?= e($b['problem']) ?></textarea></div>
          <div class="field"><label for="quoted">Price quoted (R)</label><input id="quoted" name="quoted" inputmode="decimal" value="<?= e($b['quoted'] !== null ? (float) $b['quoted'] : '') ?>"></div>
          <div class="field"><label for="notes">My notes <span class="hint">only you see these</span></label><textarea id="notes" name="notes" style="min-height:80px"><?= e($b['notes']) ?></textarea></div><button class="btn btn-primary">Save details</button>
          <?php if ($b['photo']): ?><p style="margin-top:1rem"><a href="<?= APP_URL . '/' . e($b['photo']) ?>" target="_blank"><img src="<?= APP_URL . '/' . e($b['photo']) ?>" alt="Customer photo" style="max-height:260px;border-radius:14px"></a></p><?php endif; ?></div></form>
        <div class="card" style="margin-top:1.2rem"><div class="card__head"><h2>History</h2></div><div style="max-height:360px;overflow:auto"><?php foreach ($events as $v): ?><div class="list-row"><div class="list-row__main"><strong style="white-space:normal"><?= e($v['detail']) ?></strong><small><?= date('j M Y H:i', strtotime($v['created_at'])) ?></small></div></div><?php endforeach; if (!$events): ?><div class="empty">Nothing yet.</div><?php endif; ?></div>
          <div class="card__body" style="display:flex;gap:.5rem;flex-wrap:wrap"><a class="btn-xs" target="_blank" href="<?= e(visitUrl($b)) ?>">Customer's page</a><form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="resend"><button class="btn-xs">Email this lead to me</button></form><form method="post" data-confirm="Delete this lead for good?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-xs danger"><i class="fa-solid fa-trash-can"></i>Delete</button></form></div></div>
      </div>
    </div>
    <?php
    adminClose('<script src="' . asset('js/leads.js') . '" defer></script>');
    exit;
}

// ── The lead list ─────────────────────────────────────────────────
$status = isset(BOOKING_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$q = trim((string) ($_GET['q'] ?? ''));
$svcF = isset(services()[$_GET['service'] ?? '']) ? $_GET['service'] : '';
$where = ['1=1'];
$args = [];
if ($status) { $where[] = 'status = ?'; $args[] = $status; }
if ($svcF) { $where[] = 'service = ?'; $args[] = $svcF; }
if ($q !== '') { $where[] = '(ref LIKE ? OR full_name LIKE ? OR phone LIKE ? OR area LIKE ? OR email LIKE ?)'; array_push($args, ...array_fill(0, 5, "%{$q}%")); }
$page = max(1, (int) ($_GET['page'] ?? 1));
$w = implode(' AND ', $where);
$c = $db->prepare("SELECT COUNT(*) FROM bookings WHERE {$w}");
$c->execute($args);
$total = (int) $c->fetchColumn();
$s = $db->prepare("SELECT * FROM bookings WHERE {$w} ORDER BY needs_reply DESC, (status = 'new') DESC, (status IN ('done','lost')), created_at DESC LIMIT 40 OFFSET " . (($page - 1) * 40));
$s->execute($args);
$rows = $s->fetchAll();
$counts = leadCounts();
$lastMail = $db->query("SELECT status, error FROM sent_emails WHERE kind = 'booking_admin' ORDER BY id DESC LIMIT 1")->fetch();

adminOpen('bookings', 'Leads', 'Tick what you have done. Untick it if it was a mistake.', '<a class="btn btn-ghost" href="?export=1"><i class="fa-solid fa-file-csv"></i>Excel</a><a class="btn btn-ghost" href="visits.php"><i class="fa-solid fa-calendar-days"></i>Visits</a><a class="btn btn-primary" href="?add=1"><i class="fa-solid fa-plus"></i>Add a lead</a>');
if ($lastMail && $lastMail['status'] === 'failed'): ?><div class="beat bad"><i class="fa-solid fa-triangle-exclamation"></i><span><strong>The last booking email did not reach you.</strong> <?= e(excerpt($lastMail['error'], 120)) ?> <a href="settings.php?tab=email">Fix it in Settings → Email</a></span></div><?php endif; ?>
<div class="tabs"><a href="?" class="<?= $status ? '' : 'is-on' ?>">All <b class="cnt" data-count="all"><?= array_sum($counts) ?></b></a><?php foreach (BOOKING_STATUSES as $k => $l): ?><a href="?status=<?= $k ?>" class="<?= $status === $k ? 'is-on' : '' ?>"><?= $l ?> <b class="cnt" data-count="<?= $k ?>"><?= $counts[$k] ?></b></a><?php endforeach; ?></div>
<div class="card">
  <div class="toolbar"><form method="get" style="display:contents"><?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?><input class="input" name="q" value="<?= e($q) ?>" placeholder="Name, phone, area or reference"><select name="service" onchange="this.form.submit()"><option value="">All appliances</option><?php foreach (services() as $k => $x): ?><option value="<?= $k ?>" <?= $svcF === $k ? 'selected' : '' ?>><?= e($x['short']) ?></option><?php endforeach; ?></select><button class="btn-xs">Search</button></form>
    <form method="post" id="bulk" data-confirm="Do this for all the leads you selected?" style="display:flex;gap:.4rem"><?= csrfField() ?><input type="hidden" name="action" value="bulk"><select name="do"><option value="">With selected…</option><?php foreach (LEAD_TICKS as $k => [, $l]): ?><option value="<?= $k ?>">Tick: <?= e($l) ?></option><?php endforeach; ?><option value="delete">Delete</option></select><button class="btn-xs">Go</button></form></div>
<?php if ($rows): ?><table class="tbl leads"><thead><tr><th><input type="checkbox" id="pickAll" aria-label="Select all"></th><th>Customer</th><th>Job</th><th>Visit</th><th>Done so far</th><th>Stage</th></tr></thead><tbody>
<?php foreach ($rows as $b): $hi = fillText(template($b['visit_date'] ? 'visit_confirmed' : 'ask_time')['wa'], $b, true); $first = explode(' ', $b['full_name'])[0]; ?>
  <tr class="<?= $b['needs_reply'] ? 'hot' : '' ?>">
    <td data-label="Select"><input type="checkbox" name="ids[]" value="<?= (int) $b['id'] ?>" form="bulk" aria-label="Select <?= e($b['full_name']) ?>"></td>
    <td class="cell-main"><strong><a href="?id=<?= (int) $b['id'] ?>"><?= e($b['full_name']) ?></a></strong><br><small class="muted"><?= e($b['phone']) ?> · <?= e($b['ref']) ?> · <?= e(timeAgo($b['created_at'])) ?></small>
      <div class="row-actions" style="justify-content:flex-start;margin-top:.4rem"><a class="btn-xs" data-touch="whatsapp" data-id="<?= (int) $b['id'] ?>" target="_blank" rel="noopener" href="<?= e(customerWa($b, $hi)) ?>"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a><a class="btn-xs" data-touch="call" data-id="<?= (int) $b['id'] ?>" href="tel:+<?= e(intlPhone($b['phone'])) ?>"><i class="fa-solid fa-phone"></i>Call</a></div></td>
    <td data-label="Job"><span><strong><?= e(services()[$b['service']]['short'] ?? $b['service']) ?></strong>, <?= e($b['area']) ?><br><small class="muted"><?= e(excerpt($b['problem'], 70)) ?></small></span></td>
    <td data-label="Visit"><span><?php if ($b['visit_date']): ?><strong><?= e(date('D j M', strtotime($b['visit_date']))) ?></strong><br><?= e(visitTime($b)) ?><?= $b['confirmed_at'] ? '<br><span class="tag tag-on">Confirmed</span>' : '' ?><?php else: ?><small class="muted"><?= requestedLabel($b) ? 'Asked: ' . e(requestedLabel($b)) : 'No time yet' ?></small><br><a class="btn-xs" style="margin-top:.3rem" href="?id=<?= (int) $b['id'] ?>">Choose a time</a><?php endif; ?><?php if ($b['needs_reply']): ?><br><small style="color:var(--coral)"><?= e($b['reply_note']) ?></small><?php endif; ?></span></td>
    <td data-label="Done so far"><?= tickBoxes($b) ?></td>
    <td data-label="Stage"><?= stageTag($b) ?></td>
  </tr>
<?php endforeach; ?></tbody></table><?= pager($total, $page, 40, array_filter(['status' => $status, 'q' => $q, 'service' => $svcF])) ?>
<?php else: ?><div class="empty"><i class="fa-solid fa-calendar"></i>Nothing here.</div><?php endif; ?></div>
<?php adminClose('<script src="' . asset('js/leads.js') . '" defer></script>');
