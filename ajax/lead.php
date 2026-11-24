<?php
declare(strict_types=1);

// Lead list actions for signed-in admins: tick boxes, message previews, planning a visit for WhatsApp.
require_once dirname(__DIR__) . '/includes/boot.php';

$me = isLoggedIn() ? getCurrentUser() : null;
if (!$me || $me['role'] !== 'admin') jsonResponse(['ok' => false, 'error' => 'Please sign in again.'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfValid()) jsonResponse(['ok' => false, 'error' => 'Refresh the page and try again.'], 419);

$id = (int) ($_POST['id'] ?? 0);
$b = findBooking($id);
if (!$b) jsonResponse(['ok' => false, 'error' => 'This lead was deleted.'], 404);

function leadState(array $b): array
{
    $ticks = [];
    foreach (LEAD_TICKS as $k => [$col]) $ticks[$k] = !empty($b[$col]);
    return ['ok' => true, 'ticks' => $ticks, 'status' => $b['status'], 'label' => BOOKING_STATUSES[$b['status']] ?? $b['status'], 'counts' => leadCounts()];
}

/** The date and times from the form, or an error. @return array{0: string, 1: string, 2: string, 3: string} */
function postedVisit(): array
{
    $d = postText('visit_date', 10);
    $f = postText('visit_from', 5);
    $t = postText('visit_to', 5);
    if ($d === '') return ['', '', '', ''];
    if (!validDate($d)) return ['', '', '', 'Choose a date.'];
    if (!validTime($f)) return ['', '', '', 'Choose a time.'];
    if ($t !== '' && (!validTime($t) || $t <= $f)) return ['', '', '', 'The end time must be after the start time.'];
    return [$d, $f, $t, ''];
}

switch ($_POST['op'] ?? '') {
    case 'tick':
        $r = setTick($id, (string) ($_POST['tick'] ?? ''), ($_POST['on'] ?? '') === '1', postText('reason', 190));
        if (!$r) jsonResponse(['ok' => false, 'error' => 'Unknown tick box.'], 422);
        jsonResponse(leadState($r));

    case 'touch':   // WhatsApp or Call was pressed in the list
        $how = ($_POST['how'] ?? '') === 'call' ? 'Called' : 'WhatsApp opened';
        logLead($id, 'contact', $how);
        jsonResponse(leadState($b['contacted_at'] ? $b : setTick($id, 'contacted', true)));

    case 'preview':
        $key = (string) ($_POST['key'] ?? '');
        if (!isset(templateDefaults()[$key])) jsonResponse(['ok' => false, 'error' => 'Unknown template.'], 422);
        [$d, $f, $t] = postedVisit();
        $x = $d !== '' ? ['visit_date' => $d, 'visit_from' => $f, 'visit_to' => $t ?: null] + $b : $b;
        $tpl = template($key);
        jsonResponse(['ok' => true, 'subject' => fillText($tpl['subject'], $x), 'body' => fillText($tpl['body'], $x), 'wa' => fillText($tpl['wa'], $x, true)]);

    case 'whatsapp':   // saves the visit, then the browser opens WhatsApp
        $key = (string) ($_POST['key'] ?? '');
        [$d, $f, $t, $err] = postedVisit();
        if ($err !== '') jsonResponse(['ok' => false, 'error' => $err], 422);
        if (in_array($key, ['visit_confirmed', 'visit_changed'], true) && $d === '') jsonResponse(['ok' => false, 'error' => 'Choose a date and time first.'], 422);
        if ($key === 'visit_cancelled') [$b] = planVisit($id, '', '', '');
        elseif ($d !== '') [$b] = planVisit($id, $d, $f, $t);
        $text = mb_substr(trim((string) ($_POST['wa'] ?? '')), 0, 2000);
        logLead($id, 'whatsapp', 'WhatsApp: ' . (templateDefaults()[$key]['name'] ?? 'own message'));
        if (!$b['contacted_at']) $b = setTick($id, 'contacted', true);
        if ($key === 'not_going_ahead' && !$b['lost_at']) $b = setTick($id, 'lost', true);
        jsonResponse(leadState($b) + ['url' => customerWa($b, $text)]);
}
jsonResponse(['ok' => false, 'error' => 'Unknown action.'], 422);
