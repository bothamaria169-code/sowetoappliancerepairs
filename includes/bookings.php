<?php
declare(strict_types=1);

/**
 * Bookings (leads): the times customers can choose, the four tick boxes
 * (contacted, went to site, repaired, not going ahead), planned visits and the
 * history of each lead.
 */
require_once __DIR__ . '/templates.php';

/** tick => [column, label] */
const LEAD_TICKS = [
    'contacted' => ['contacted_at', 'Contacted'],
    'went'      => ['went_at',      'Went to site'],
    'repaired'  => ['repaired_at',  'Repaired'],
    'lost'      => ['lost_at',      'Not going ahead'],
];

const DEFAULT_SLOTS = "08:00-10:00\n10:00-12:00\n12:00-14:00\n14:00-16:00\n16:00-18:00";

// ── Times customers can choose ────────────────────────────────────
/** @return string[] like 08:00-10:00, from Settings → Bookings */
function bookingSlots(): array
{
    $out = [];
    foreach (preg_split('/\R/', get_setting('booking_slots', DEFAULT_SLOTS)) ?: [] as $l) {
        if (preg_match('/^\s*(\d{1,2}):(\d{2})\s*(?:-|–|to)\s*(\d{1,2}):(\d{2})\s*$/u', $l, $m)) {
            $s = sprintf('%02d:%02d-%02d:%02d', $m[1], $m[2], $m[3], $m[4]);
            if (validTime(substr($s, 0, 5)) && validTime(substr($s, 6)) && substr($s, 0, 5) < substr($s, 6)) $out[] = $s;
        }
    }
    return array_values(array_unique($out)) ?: explode("\n", DEFAULT_SLOTS);
}

function validTime(string $t): bool
{
    return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);
}

function validDate(string $d): bool
{
    $x = DateTime::createFromFormat('!Y-m-d', $d);
    return $x !== false && $x->format('Y-m-d') === $d;
}

/**
 * Days and times open for booking: date => [slot => free?]. Full slots, days
 * off, blocked dates and times too close to now are closed.
 *
 * @return array<string, array<string, bool>>
 */
function bookingDays(int $except = 0): array
{
    $ahead  = max(1, min(60, (int) get_setting('booking_days_ahead', '14')));
    $cap    = max(1, (int) get_setting('booking_capacity', '2'));
    $notice = max(0, (int) get_setting('booking_notice_hours', '2'));
    $off    = array_filter(explode(',', get_setting('booking_days_off', '')));
    preg_match_all('/\d{4}-\d{2}-\d{2}/', get_setting('booking_blocked', ''), $b);
    $blocked = $b[0];

    $used = [];
    $s = getDB()->prepare('SELECT pref_date, pref_slot, visit_date, visit_from, visit_to FROM bookings
        WHERE lost_at IS NULL AND repaired_at IS NULL AND id <> ? AND (visit_date >= ? OR (visit_date IS NULL AND pref_date >= ?))');
    $s->execute([$except, date('Y-m-d'), date('Y-m-d')]);
    foreach ($s as $r) {
        if ($r['visit_date']) $used[$r['visit_date']][] = [substr((string) $r['visit_from'], 0, 5), substr((string) ($r['visit_to'] ?: $r['visit_from']), 0, 5)];
        elseif ($r['pref_slot']) $used[$r['pref_date']][] = explode('-', $r['pref_slot']);
    }

    $days = [];
    $earliest = date('Y-m-d H:i', time() + $notice * 3600);
    for ($i = 0; $i < $ahead; $i++) {
        $d = date('Y-m-d', strtotime("+{$i} days"));
        if (in_array(date('D', strtotime($d)), $off, true) || in_array($d, $blocked, true)) continue;
        $row = [];
        foreach (bookingSlots() as $slot) {
            [$from, $to] = explode('-', $slot);
            $n = count(array_filter($used[$d] ?? [], fn($u) => $u[0] < $to && ($u[1] > $from || $u[0] === $from)));
            $row[$slot] = "{$d} {$from}" >= $earliest && $n < $cap;
        }
        if (in_array(true, $row, true)) $days[$d] = $row;
    }
    return $days;
}

function slotLabel(?string $slot): string
{
    return $slot ? str_replace('-', ' to ', $slot) : '';
}

/** "Thursday 26 November" */
function dayLabel(?string $d): string
{
    return $d ? date('l j F', strtotime($d)) : '';
}

/** What the customer asked for, e.g. "Thursday 26 November, 10:00 to 12:00". */
function requestedLabel(array $b): string
{
    if (!empty($b['pref_date'])) return dayLabel($b['pref_date']) . ($b['pref_slot'] ? ', ' . slotLabel($b['pref_slot']) : '');
    return (string) ($b['when_wanted'] ?? '');
}

/** The visit you planned, or ''. */
function visitLabel(array $b): string
{
    if (empty($b['visit_date'])) return '';
    return dayLabel($b['visit_date']) . ', ' . visitTime($b);
}

function visitTime(array $b): string
{
    $f = substr((string) ($b['visit_from'] ?? ''), 0, 5);
    $t = substr((string) ($b['visit_to'] ?? ''), 0, 5);
    return $f . ($t !== '' && $t !== $f ? ' to ' . $t : '');
}

// ── Leads ─────────────────────────────────────────────────────────
function findBooking(int $id): ?array
{
    $s = getDB()->prepare('SELECT * FROM bookings WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

/** @return array the saved booking */
function createBooking(array $d): array
{
    $db = getDB();
    $ref = 'SAR' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $cols = ['ref' => $ref, 'token' => bin2hex(random_bytes(16))] + $d;
    $db->prepare('INSERT INTO bookings (' . implode(', ', array_keys($cols)) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute(array_values($cols));
    $b = findBooking((int) $db->lastInsertId());
    logLead((int) $b['id'], 'created', $b['source'] === 'website' ? 'Booked on the website' . (requestedLabel($b) ? ' for ' . requestedLabel($b) : '') : 'Added by hand');
    return $b;
}

/** The stage of a lead, worked out from its ticks and its planned visit. */
function leadStatus(array $b): string
{
    return match (true) {
        !empty($b['lost_at'])      => 'lost',
        !empty($b['repaired_at'])  => 'done',
        !empty($b['went_at'])      => 'visited',
        !empty($b['visit_date'])   => 'booked',
        !empty($b['contacted_at']) => 'contacted',
        default                    => 'new',
    };
}

function syncLead(int $id): array
{
    $b = findBooking($id);
    getDB()->prepare('UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?')->execute([leadStatus($b), $id]);
    return findBooking($id);
}

/** Ticks or unticks one box. Repaired and Not going ahead cannot both be on. */
function setTick(int $id, string $tick, bool $on, string $reason = ''): ?array
{
    if (!isset(LEAD_TICKS[$tick]) || !($b = findBooking($id))) return null;
    [$col, $label] = LEAD_TICKS[$tick];
    $db = getDB();
    if ((bool) $b[$col] !== $on) {
        $db->prepare("UPDATE bookings SET {$col} = " . ($on ? 'NOW()' : 'NULL') . ' WHERE id = ?')->execute([$id]);
        if ($on && $tick === 'lost')     $db->prepare('UPDATE bookings SET repaired_at = NULL WHERE id = ?')->execute([$id]);
        if ($on && $tick === 'repaired') $db->prepare('UPDATE bookings SET lost_at = NULL, lost_reason = NULL WHERE id = ?')->execute([$id]);
        logLead($id, $on ? 'tick' : 'untick', ($on ? 'Ticked: ' : 'Unticked: ') . $label . ($reason !== '' ? ": {$reason}" : ''));
    }
    if ($tick === 'lost') $db->prepare('UPDATE bookings SET lost_reason = ? WHERE id = ?')->execute([$on ? ($reason !== '' ? mb_substr($reason, 0, 190) : $b['lost_reason']) : null, $id]);
    return syncLead($id);
}

/** Saves the visit. An empty date removes it. Returns the booking and whether it changed. */
function planVisit(int $id, string $date, string $from, string $to): array
{
    $b = findBooking($id);
    $new = $date === '' ? [null, null, null] : [$date, $from, $to !== '' ? $to : null];
    $old = [$b['visit_date'], $b['visit_from'] ? substr($b['visit_from'], 0, 5) : null, $b['visit_to'] ? substr($b['visit_to'], 0, 5) : null];
    if ($new !== $old) {
        getDB()->prepare('UPDATE bookings SET visit_date = ?, visit_from = ?, visit_to = ?, confirmed_at = NULL, reminder_sent_at = NULL, needs_reply = 0, reply_note = NULL WHERE id = ?')->execute([...$new, $id]);
        $b = syncLead($id);
        logLead($id, 'visit', $date === '' ? 'Visit removed' : 'Visit planned for ' . visitLabel($b));
    }
    return [$b, $new !== $old];
}

function logLead(int $id, string $kind, string $detail): void
{
    getDB()->prepare('INSERT INTO booking_events (booking_id, kind, detail, admin_id) VALUES (?, ?, ?, ?)')->execute([$id, $kind, mb_substr($detail, 0, 500), PHP_SAPI !== 'cli' && isAdmin() ? ($_SESSION['user_id'] ?? null) : null]);
}

/** Counts per stage for the tabs. */
function leadCounts(): array
{
    $out = array_fill_keys(array_keys(BOOKING_STATUSES), 0);
    foreach (getDB()->query('SELECT status, COUNT(*) n FROM bookings GROUP BY status') as $r) $out[$r['status']] = (int) $r['n'];
    return $out;
}

/** 060… → 2760… for wa.me and tel: links. */
function intlPhone(string $phone): string
{
    $d = preg_replace('/\D/', '', $phone);
    return str_starts_with($d, '0') ? '27' . substr($d, 1) : $d;
}

function customerWa(array $b, string $text): string
{
    return 'https://wa.me/' . intlPhone($b['phone']) . '?text=' . rawurlencode($text);
}

/** The customer's private page to confirm or change the visit. */
function visitUrl(array $b): string
{
    return APP_URL . '/visit?t=' . $b['token'];
}

/** A calendar file for the visit. */
function visitIcs(array $b): ?array
{
    if (empty($b['visit_date']) || empty($b['visit_from'])) return null;
    $utc = fn(string $t) => gmdate('Ymd\THis\Z', strtotime($b['visit_date'] . ' ' . $t));
    $end = $b['visit_to'] ?: date('H:i', strtotime($b['visit_from']) + 7200);
    $esc = fn(string $t) => str_replace(["\\", ',', ';', "\r", "\n"], ["\\\\", '\,', '\;', '', '\n'], $t);
    $svc = services()[$b['service']]['short'] ?? 'Appliance';
    $l = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . LIVE_DOMAIN . '//EN', 'METHOD:PUBLISH', 'BEGIN:VEVENT', 'UID:' . $b['ref'] . '@' . LIVE_DOMAIN, 'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'DTSTART:' . $utc($b['visit_from']), 'DTEND:' . $utc($end), 'SUMMARY:' . $esc(APP_NAME . ': ' . lcfirst($svc) . ' repair'), 'LOCATION:' . $esc(trim(($b['address'] ?: '') . ', ' . $b['area'], ', ')),
        'DESCRIPTION:' . $esc("Reference {$b['ref']}. " . biz('biz_phone')), 'END:VEVENT', 'END:VCALENDAR'];
    return [implode("\r\n", $l) . "\r\n", 'visit-' . $b['ref'] . '.ics'];
}

// ── Notifications ─────────────────────────────────────────────────
/** Everybody who gets the owner's emails (Settings → Email, separated by commas). */
function notifyEmails(): array
{
    $list = array_filter(array_map('trim', preg_split('/[,;\s]+/', get_setting('notify_email', ADMIN_EMAIL)) ?: []), fn($x) => filter_var($x, FILTER_VALIDATE_EMAIL));
    return array_values(array_unique($list)) ?: [ADMIN_EMAIL];
}

/**
 * Sends one template about a booking. $subject and $body replace the template's
 * own text when the owner edited the message.
 */
function notifyBooking(string $key, array $b, ?string $subject = null, ?string $body = null): string
{
    $t = template($key);
    $toOwner = $t['to'] === 'you';
    if (!$toOwner && empty($b['email'])) return 'This customer has no email address.';
    $subject ??= fillText($t['subject'], $b);
    $body    ??= fillText($t['body'], $b);
    $html = textToEmail($body, $b);
    [$label, $url] = match (true) {
        $toOwner                 => ['Open the lead', APP_URL . '/admin/bookings.php?id=' . $b['id']],
        $key === 'repair_done'   => ['Leave a review', biz('biz_review_url') ?: APP_URL . '/reviews'],
        in_array($key, ['visit_confirmed', 'visit_changed', 'visit_reminder'], true) => ['Confirm or change the time', visitUrl($b)],
        $key === 'ask_time'      => ['Choose a day and time', visitUrl($b)],
        default                  => [null, null],
    };
    $files = [];
    if (in_array($key, ['visit_confirmed', 'visit_changed'], true) && ($ics = visitIcs($b))) $files[] = $ics;
    $replyTo = $toOwner ? ($b['email'] ?: null) : (get_setting('smtp_user', SMTP_USER) ?: ADMIN_EMAIL);
    $err = sendMail($toOwner ? notifyEmails() : $b['email'], $subject, emailLayout('', $html, $label, $url), [], $replyTo, $key, ['files' => $files]);
    if (!$toOwner) logLead((int) $b['id'], $err === '' ? 'email' : 'email_failed', ($err === '' ? 'Emailed: ' : 'Email failed: ') . $t['name'] . ($err === '' ? '' : " ({$err})"));
    return $err;
}

/** Tells the owner about a new booking; cron tries again while it fails. */
function notifyOwnerOfBooking(array $b): string
{
    $err = notifyBooking('booking_admin', $b);
    getDB()->prepare('UPDATE bookings SET notify_tries = notify_tries + 1, admin_notified_at = ' . ($err === '' ? 'NOW()' : 'admin_notified_at') . ' WHERE id = ?')->execute([$b['id']]);
    return $err;
}

/** Sends the visitor to $to and keeps working afterwards, so a slow mail server never holds them up. */
function redirectThen(string $to, callable $work): never
{
    ignore_user_abort(true);
    set_time_limit(120);
    header('Location: ' . APP_URL . '/' . ltrim($to, '/'));
    header('Content-Length: 0');
    header('Connection: close');
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    while (ob_get_level() > 0) ob_end_flush();
    flush();
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    elseif (function_exists('litespeed_finish_request')) litespeed_finish_request();
    try { $work(); } catch (Throwable $e) { error_log('SAR after-response: ' . $e->getMessage()); }
    exit;
}

/** Cron: retry owner emails, remind customers, send the owner today's list. Returns log lines. */
function bookingCron(): array
{
    $db = getDB();
    $log = [];
    foreach ($db->query('SELECT * FROM bookings WHERE admin_notified_at IS NULL AND notify_tries BETWEEN 1 AND 8 AND created_at > (NOW() - INTERVAL 2 DAY) LIMIT 5')->fetchAll() as $b) {
        $err = notifyOwnerOfBooking($b);
        $log[] = "Booking email {$b['ref']}: " . ($err === '' ? 'sent' : $err);
    }
    // Once, in the day before the visit, between 07:00 and 20:00.
    if (get_setting('reminders_on', '1') === '1' && (int) date('G') >= 7 && (int) date('G') < 20) {
        $s = $db->prepare('SELECT * FROM bookings WHERE visit_date IS NOT NULL AND lost_at IS NULL AND repaired_at IS NULL AND went_at IS NULL AND reminder_sent_at IS NULL AND email IS NOT NULL
            AND TIMESTAMP(visit_date, visit_from) BETWEEN ? AND ? LIMIT 10');
        $s->execute([date('Y-m-d H:i:s', time() + 5400), date('Y-m-d H:i:s', time() + 86400)]);
        foreach ($s->fetchAll() as $b) {
            $err = notifyBooking('visit_reminder', $b);
            $db->prepare('UPDATE bookings SET reminder_sent_at = NOW() WHERE id = ?')->execute([$b['id']]);
            $log[] = "Reminder {$b['ref']}: " . ($err === '' ? 'sent' : $err);
        }
    }
    $today = date('Y-m-d');
    if (get_setting('agenda_on', '1') === '1' && (int) date('G') >= 6 && (int) date('G') < 12 && get_setting('agenda_day') !== $today) {
        set_setting('agenda_day', $today);
        $s = $db->prepare('SELECT * FROM bookings WHERE visit_date = ? AND lost_at IS NULL ORDER BY visit_from');
        $s->execute([$today]);
        if ($rows = $s->fetchAll()) {
            $html = '<p>Your visits for ' . e(dayLabel($today)) . ':</p>';
            foreach ($rows as $b) $html .= emailRows(['Time' => visitTime($b), 'Customer' => $b['full_name'] . ', ' . $b['phone'], 'Job' => (services()[$b['service']]['short'] ?? $b['service']) . ($b['problem'] ? ': ' . excerpt($b['problem'], 120) : ''), 'Where' => trim(($b['address'] ?: '') . ', ' . $b['area'], ', '), 'Confirmed' => $b['confirmed_at'] ? 'Yes' : 'Not yet']) . '<br>';
            $err = sendMail(notifyEmails(), count($rows) . (count($rows) === 1 ? ' visit' : ' visits') . ' today', emailLayout('', $html, 'Open visits', APP_URL . '/admin/visits.php'), [], null, 'agenda');
            $log[] = "Today's visits: " . ($err === '' ? 'sent' : $err);
        }
    }
    return $log;
}
