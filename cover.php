<?php
declare(strict_types=1);

// A published article's cover as a 1080×1080 JPEG with the title on it (for Facebook and Instagram).
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/articles.php';

$s = getDB()->prepare('SELECT * FROM posts WHERE id = ? AND is_published = 1');
$s->execute([(int) ($_GET['id'] ?? 0)]);
$p = $s->fetch();
if (!$p) { http_response_code(404); exit; }

$cover = (string) $p['cover'];
$file = str_starts_with($cover, 'uploads/media/') ? BASE_DIR . '/uploads/media/' . basename($cover) : BASE_DIR . '/assets/img/work/' . basename($cover ?: 'board-multimeter') . '.webp';
$src = is_file($file) ? @imagecreatefromwebp($file) : false;
if (!$src) $src = imagecreatefromwebp(BASE_DIR . '/assets/img/work/board-multimeter.webp');
$side = min(imagesx($src), imagesy($src));
$im = imagecreatetruecolor(1080, 1080);
imagecopyresampled($im, $src, 0, 0, intdiv(imagesx($src) - $side, 2), intdiv(imagesy($src) - $side, 2), 1080, 1080, $side, $side);
for ($y = 620; $y < 1080; $y++) imagefilledrectangle($im, 0, $y, 1080, $y, imagecolorallocatealpha($im, 10, 28, 61, (int) max(8, 127 - ($y - 620) * .5)));
imagefilledrectangle($im, 0, 1060, 1080, 1080, imagecolorallocate($im, 255, 122, 26));
header('Content-Type: image/jpeg');
header('Cache-Control: public, max-age=86400');
imagejpeg($im, null, 86);
