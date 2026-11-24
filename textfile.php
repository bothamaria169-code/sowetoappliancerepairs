<?php
declare(strict_types=1);

// Plain-text files that need live values: robots.txt, llms.txt and the IndexNow key.
require_once __DIR__ . '/includes/boot.php';

header('Content-Type: text/plain; charset=utf-8');
$f = (string) ($_GET['f'] ?? '');
if ($f === 'robots') {
    echo "User-agent: *\nDisallow: /admin/\nDisallow: /ajax/\nDisallow: /login\nDisallow: /visit\nDisallow: /cron.php\n\nSitemap: " . APP_URL . "/sitemap.xml\n";
} elseif ($f === 'llms') {
    $c = callout();
    echo '# ' . APP_NAME . "\n\n> " . APP_TAGLINE . "\n\n- Phone and WhatsApp: " . biz('biz_phone') . "\n- Call-out: R{$c}, free when we do the repair\n- Guarantee: " . biz('biz_guarantee') . " months on repairs\n- Hours: 7 days a week, same-day service\n- Areas: " . implode(', ', array_column(areas(), 0)) . "\n\n## Services\n";
    foreach (services() as $s => $x) echo "- [{$x['name']}](" . APP_URL . "/{$s}): {$x['lead']}\n";
    echo "\n## Fault guides\n";
    foreach (faults() as $s => $x) echo "- [{$x['q']}](" . APP_URL . "/faults/{$s})\n";
    echo "\n## Reference\n- [Error codes](" . APP_URL . "/error-codes)\n- [Prices](" . APP_URL . "/prices)\n\n## Articles\n";
    foreach (latestPosts(40) as $p) echo "- [{$p['title']}](" . postUrl($p) . "): {$p['excerpt']}\n";
} elseif (preg_match('/^[a-f0-9]{32}$/', $f) && hash_equals((string) secret('indexnow_key', ''), $f)) {
    echo $f;
} else {
    http_response_code(404);
}
