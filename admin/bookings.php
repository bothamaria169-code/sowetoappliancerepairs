<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') {
        $s = $db->prepare('SELECT ref, photo FROM bookings WHERE id = ?');
        $s->execute([$id]);
        if ($b = $s->fetch()) {
            if ($b['photo'] && str_starts_with($b['photo'], 'uploads/media/')) @unlink(MEDIA_DIR . basename($b['photo']));
            $db->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
            audit('booking_delete', $b['ref']);
            flash('success', 'Booking deleted.');
        }
        redirect('admin/bookings.php');
    }
    $st = (string) ($_POST['status'] ?? '');
    if (isset(BOOKING_STATUSES[$st])) {
        $q = str_replace(',', '.', postText('quoted', 12));
        $db->prepare('UPDATE bookings SET status = ?, notes = ?, quoted = ?, updated_at = NOW() WHERE id = ?')->execute([$st, postText('notes', 3000) ?: null, is_numeric($q) ? $q : null, $id]);
        audit('booking_update', '#' . $id, $st);
        flash('success', 'Saved.');
    }
    redirect('admin/bookings.php?id=' . $id);
}

if (isset($_GET['id'])) {
    $s = $db->prepare('SELECT * FROM bookings WHERE id = ?');
    $s->execute([(int) $_GET['id']]);
    $b = $s->fetch();
    if (!$b) redirect('admin/bookings.php');
    $d = preg_replace('/\D/', '', $b['phone']);
    $intl = str_starts_with($d, '0') ? '27' . substr($d, 1) : $d;
    $svc = services()[$b['service']]['short'] ?? $b['service'];
    $hi = 'Hi ' . explode(' ', $b['full_name'])[0] . ', this is ' . APP_NAME . ' about your ' . lcfirst($svc) . " (ref {$b['ref']}). ";
    adminOpen('bookings', $b['full_name'], 'Booking ' . e($b['ref']) . ', ' . e(timeAgo($b['created_at'])), '<a class="btn btn-ghost" href="bookings.php">← All bookings</a><a class="btn btn-ghost" href="tel:+' . e($intl) . '"><i class="fa-solid fa-phone"></i>Call</a><a class="btn btn-primary" target="_blank" rel="noopener" href="https://wa.me/' . e($intl) . '?text=' . rawurlencode($hi) . '"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>');
    ?>
    <div class="grid-2"><div class="card"><div class="card__head"><h2>Details</h2></div><div class="card__body"><dl class="kv">
      <?php foreach (['Appliance' => $svc, 'Brand / model' => $b['brand'], 'Problem' => $b['problem'], 'Area' => $b['area'], 'Address' => $b['address'], 'Wanted' => $b['when_wanted'], 'Phone' => $b['phone'], 'Email' => $b['email'], 'Booked from page' => $b['source_page'], 'First came from' => $b['referrer']] as $k => $v): ?><dt><?= e($k) ?></dt><dd><?= nl2br(e($v ?: '—')) ?></dd><?php endforeach; ?></dl>
      <?php if ($b['photo']): ?><p style="margin-top:1rem"><a href="<?= APP_URL . '/' . e($b['photo']) ?>" target="_blank"><img src="<?= APP_URL . '/' . e($b['photo']) ?>" alt="Customer photo" style="max-height:340px;border-radius:14px"></a></p><?php endif; ?></div></div>
    <div class="card"><div class="card__head"><h2>Follow up</h2></div><form class="card__body" method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
      <div class="field"><label for="status">Status</label><select id="status" name="status"><?php foreach (BOOKING_STATUSES as $k => $l): ?><option value="<?= $k ?>" <?= $b['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="quoted">Price quoted (R)</label><input id="quoted" name="quoted" inputmode="decimal" value="<?= e($b['quoted'] !== null ? (float) $b['quoted'] : '') ?>"></div>
      <div class="field"><label for="notes">Notes</label><textarea id="notes" name="notes"><?= e($b['notes']) ?></textarea></div>
      <div class="form-foot"><button class="btn btn-primary">Save</button></div></form>
      <form class="card__body" method="post" data-confirm="Delete this booking for good?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-xs danger"><i class="fa-solid fa-trash-can"></i>Delete booking</button></form></div></div>
    <?php
    adminClose();
    exit;
}

$status = isset(BOOKING_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$q = trim((string) ($_GET['q'] ?? ''));
$where = ['1=1'];
$args = [];
if ($status) { $where[] = 'status = ?'; $args[] = $status; }
if ($q !== '') { $where[] = '(ref LIKE ? OR full_name LIKE ? OR phone LIKE ? OR area LIKE ?)'; array_push($args, ...array_fill(0, 4, "%{$q}%")); }
$page = max(1, (int) ($_GET['page'] ?? 1));
$w = implode(' AND ', $where);
$c = $db->prepare("SELECT COUNT(*) FROM bookings WHERE {$w}");
$c->execute($args);
$total = (int) $c->fetchColumn();
$s = $db->prepare("SELECT * FROM bookings WHERE {$w} ORDER BY (status = 'new') DESC, created_at DESC LIMIT 30 OFFSET " . (($page - 1) * 30));
$s->execute($args);
$rows = $s->fetchAll();
adminOpen('bookings', 'Bookings', 'Every request from the website.');
?>
<div class="tabs"><a href="?" class="<?= $status ? '' : 'is-on' ?>">All</a><?php foreach (BOOKING_STATUSES as $k => $l): ?><a href="?status=<?= $k ?>" class="<?= $status === $k ? 'is-on' : '' ?>"><?= $l ?></a><?php endforeach; ?></div>
<div class="card"><?php if ($rows): ?><table class="tbl"><thead><tr><th>Customer</th><th>Appliance</th><th>Area</th><th>Problem</th><th>Status</th><th>When</th></tr></thead><tbody><?php foreach ($rows as $b): ?><tr><td class="cell-main"><strong><a href="?id=<?= (int) $b['id'] ?>"><?= e($b['full_name']) ?></a></strong><br><small class="muted"><?= e($b['phone']) ?> · <?= e($b['ref']) ?></small></td><td data-label="Appliance"><?= e(services()[$b['service']]['short'] ?? $b['service']) ?></td><td data-label="Area"><?= e($b['area']) ?></td><td data-label="Problem"><span class="muted"><?= e(excerpt($b['problem'], 70)) ?></span></td><td data-label="Status"><span class="tag <?= $b['status'] === 'new' ? 'tag-on' : ($b['status'] === 'lost' ? 'tag-off' : 'tag-user') ?>"><?= e(BOOKING_STATUSES[$b['status']] ?? '') ?></span></td><td data-label="When"><span class="muted"><?= e(timeAgo($b['created_at'])) ?></span></td></tr><?php endforeach; ?></tbody></table><?= pager($total, $page, 30, array_filter(['status' => $status, 'q' => $q])) ?><?php else: ?><div class="empty"><i class="fa-solid fa-calendar"></i>Nothing here yet.</div><?php endif; ?></div>
<?php adminClose();
