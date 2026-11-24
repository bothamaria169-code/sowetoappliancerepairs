<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();
$s = $db->prepare('SELECT * FROM bookings WHERE visit_date >= ? AND lost_at IS NULL ORDER BY visit_date, visit_from LIMIT 200');
$s->execute([date('Y-m-d', strtotime('-1 day'))]);
$days = [];
foreach ($s->fetchAll() as $b) $days[$b['visit_date']][] = $b;
$late = $db->prepare('SELECT * FROM bookings WHERE visit_date < ? AND lost_at IS NULL AND repaired_at IS NULL AND went_at IS NULL ORDER BY visit_date DESC LIMIT 30');
$late->execute([date('Y-m-d')]);
$late = $late->fetchAll();
$ask = $db->query("SELECT * FROM bookings WHERE visit_date IS NULL AND lost_at IS NULL AND repaired_at IS NULL AND went_at IS NULL ORDER BY pref_date IS NULL, pref_date, pref_slot LIMIT 50")->fetchAll();

$row = function (array $b, string $left, string $extra = '') {
    $where = trim(($b['address'] ?: '') . ', ' . $b['area'], ', ');
    echo '<div class="list-row"><div style="min-width:104px;font-weight:800">' . $left . '</div><div class="list-row__main"><strong><a href="bookings.php?id=' . (int) $b['id'] . '">' . e($b['full_name']) . '</a>, ' . e(lcfirst(services()[$b['service']]['short'] ?? $b['service'])) . '</strong><small>' . e($where) . ($b['problem'] ? ' · ' . e(excerpt($b['problem'], 60)) : '') . '</small></div><div class="row-actions">' . $extra
       . '<a class="btn-xs" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=' . rawurlencode($where . ', Gauteng') . '"><i class="fa-solid fa-location-dot"></i>Map</a><a class="btn-xs" href="tel:+' . e(intlPhone($b['phone'])) . '"><i class="fa-solid fa-phone"></i>Call</a>'
       . '<a class="btn-xs" target="_blank" rel="noopener" href="' . e(customerWa($b, fillText(template('on_the_way')['wa'], $b, true))) . '"><i class="fa-brands fa-whatsapp"></i>On my way</a></div></div>';
};
$tags = fn(array $b) => ($b['confirmed_at'] ? '<span class="tag tag-on">Confirmed</span>' : '') . ($b['needs_reply'] ? '<span class="tag tag-off">' . e(excerpt($b['reply_note'], 40)) . '</span>' : '') . ($b['repaired_at'] ? '<span class="tag tag-on">Repaired</span>' : ($b['went_at'] ? '<span class="tag tag-admin">Went</span>' : ''));
adminOpen('visits', 'Visits', 'Where you have to be, and when.', '<a class="btn btn-ghost" href="settings.php?tab=bookings"><i class="fa-solid fa-clock"></i>Booking times</a><a class="btn btn-primary" href="bookings.php">Leads</a>');
if ($late): ?><div class="card" style="margin-bottom:1.2rem"><div class="card__head"><h2>Did you go? Tick it in the lead</h2></div><?php foreach ($late as $b) $row($b, e(date('D j M', strtotime($b['visit_date']))), $tags($b)); ?></div><?php endif;
foreach ($days as $d => $list): if ($d < date('Y-m-d')) continue; ?>
<div class="card" style="margin-bottom:1.2rem"><div class="card__head"><h2><?= $d === date('Y-m-d') ? 'Today, ' : ($d === date('Y-m-d', strtotime('+1 day')) ? 'Tomorrow, ' : '') ?><?= e(dayLabel($d)) ?></h2><p><?= count($list) ?> <?= count($list) === 1 ? 'visit' : 'visits' ?></p></div><?php foreach ($list as $b) $row($b, e(visitTime($b)), $tags($b)); ?></div>
<?php endforeach;
if (!array_filter(array_keys($days), fn($d) => $d >= date('Y-m-d'))): ?><div class="card" style="margin-bottom:1.2rem"><div class="empty"><i class="fa-solid fa-calendar-days"></i>No visits planned. Open a lead and choose a date and time.</div></div><?php endif; ?>
<div class="card"><div class="card__head"><h2>Waiting for you to confirm a time</h2><p><?= count($ask) ?></p></div><?php foreach ($ask as $b) $row($b, $b['pref_date'] ? e(date('D j M', strtotime($b['pref_date']))) . '<br><small>' . e(slotLabel($b['pref_slot'])) . '</small>' : '<small>No time</small>', '<a class="btn-xs" href="bookings.php?id=' . (int) $b['id'] . '">Choose</a>'); if (!$ask): ?><div class="empty">Nobody is waiting.</div><?php endif; ?></div>
<?php adminClose();
