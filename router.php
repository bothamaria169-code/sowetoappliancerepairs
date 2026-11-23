<?php
declare(strict_types=1);

// Local testing without Apache: php -S localhost:8790 router.php  (does what .htaccess does)
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$path = rtrim((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$file = __DIR__ . $path;
if (preg_match('#^/(config|includes|classes|database|docs|images|scripts|_private|\.git|\.claude)(/|$)#', $path)) { http_response_code(403); exit('Forbidden'); }
if ($path !== '' && is_file($file)) return false;
if ($path === '/admin') { require __DIR__ . '/admin/index.php'; return; }
$go = function (string $script, array $get = []) { $_GET = $get + $_GET; require __DIR__ . '/' . $script; };
if ($path === '') return $go('index.php');
if (preg_match('#^/(services|areas|faults|error-codes|our-work|prices|about|contact|book|blog|reviews|privacy|terms|login)$#', $path, $m)) return $go($m[1] . '.php');
if (preg_match('#^/(fridge|washing-machine|stove|oven|microwave|geyser)-repairs$#', $path)) return $go('service.php', ['s' => substr($path, 1)]);
if (preg_match('#^/areas/([a-z0-9-]+)$#', $path, $m)) return $go('area.php', ['s' => $m[1]]);
if (preg_match('#^/faults/([a-z0-9-]+)$#', $path, $m)) return $go('fault.php', ['s' => $m[1]]);
if (preg_match('#^/blog/([a-z0-9-]+)$#', $path, $m)) return $go('post.php', ['s' => $m[1]]);
if ($path === '/sitemap.xml') return $go('sitemap.php');
if ($path === '/feed.xml') return $go('feed.php');
if (preg_match('#^/(robots|llms|[a-f0-9]{32})\.txt$#', $path, $m)) return $go('textfile.php', ['f' => $m[1]]);
$go('404.php');
