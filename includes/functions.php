<?php
declare(strict_types=1);

function jsonResponse(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function e(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function postText(string $key, int $len = 255): string
{
    return mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $len);
}

const BOOKING_STATUSES = ['new' => 'New', 'contacted' => 'Contacted', 'booked' => 'Visit booked', 'visited' => 'Went to site', 'done' => 'Repaired', 'lost' => 'Not going ahead'];

/** Business details shown on every page; editable in Admin → Settings → Business. */
function biz(string $key): string
{
    static $defaults = [
        'biz_name'       => 'Soweto Appliance Repairs',
        'biz_phone'      => '060 432 2996',
        'biz_whatsapp'   => '27604322996',
        'biz_email'      => 'admin@sowetoappliancerepairs.co.za',
        'biz_callout'    => '250',
        'biz_guarantee'  => '9',
        'biz_hours'      => 'Open 7 days a week',
        'biz_facebook'   => '',
        'biz_instagram'  => '',
        'biz_gbp'        => '',
        'biz_review_url' => '',
    ];
    return get_setting($key, $defaults[$key] ?? '');
}

/** The call-out fee as shown to customers: 250.00 (the R is added in the text). */
function callout(): string
{
    return number_format((float) biz('biz_callout'), 2, '.', ' ');
}

/** 060 432 2996 → +27604322996 for tel: links and schema. */
function telIntl(): string
{
    $d = preg_replace('/\D/', '', biz('biz_phone'));
    return '+' . (str_starts_with($d, '0') ? '27' . substr($d, 1) : $d);
}

function rand_(float|int|string|null $n, bool $cents = false): string
{
    if ($n === null || $n === '') return '—';
    $n = (float) $n;
    return 'R' . number_format($n, $cents || floor($n) != $n ? 2 : 0, '.', ' ');
}

function timeAgo(?string $dt): string
{
    if (!$dt) return '—';
    $s = time() - strtotime($dt);
    return match (true) {
        $s < 60      => 'just now',
        $s < 3600    => floor($s / 60) . ' min ago',
        $s < 86400   => floor($s / 3600) . ' h ago',
        $s < 172800  => 'yesterday',
        $s < 2592000 => floor($s / 86400) . ' days ago',
        default      => date('j M Y', strtotime($dt)),
    };
}

function excerpt(?string $text, int $len = 180): string
{
    $t = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $text))));
    return mb_strlen($t) > $len ? rtrim(mb_substr($t, 0, $len)) . '…' : $t;
}

function slugify(string $s): string
{
    $s = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $s), '-'));
    return $s !== '' ? mb_substr($s, 0, 180) : 'item-' . bin2hex(random_bytes(3));
}

function audit(string $action, string $target = '', string $detail = ''): void
{
    getDB()->prepare('INSERT INTO audit_log (admin_id, action, target, detail, ip) VALUES (?, ?, ?, ?, ?)')
        ->execute([$_SESSION['user_id'] ?? null, $action, mb_substr($target, 0, 200), $detail, clientIp()]);
}

function pager(int $total, int $page, int $per, array $query): string
{
    $pages = (int) ceil($total / $per);
    if ($pages <= 1) return '';
    $link = function (int $p, string $label, bool $on = false) use ($query) {
        $q = http_build_query(array_merge($query, ['page' => $p]));
        return '<a class="' . ($on ? 'is-on' : '') . '" href="?' . e($q) . '">' . $label . '</a>';
    };
    $out = '<nav class="pager">';
    if ($page > 1) $out .= $link($page - 1, '<i class="fa-solid fa-chevron-left"></i>');
    for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++) $out .= $link($p, (string) $p, $p === $page);
    if ($page < $pages) $out .= $link($page + 1, '<i class="fa-solid fa-chevron-right"></i>');
    return $out . '<span>' . number_format($total) . ' results</span></nav>';
}

function waLink(string $text = ''): string
{
    return 'https://wa.me/' . preg_replace('/\D/', '', biz('biz_whatsapp')) . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}
