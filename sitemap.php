<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

header('Content-Type: application/xml; charset=utf-8');
$built = date('Y-m-d', max(array_map(fn($f) => (int) @filemtime(BASE_DIR . $f), ['/includes/content.php', '/includes/faults.php', '/index.php'])));
$row = function (string $path, string $mod, array $imgs = []) {
    echo '<url><loc>' . e(APP_URL . '/' . $path) . "</loc><lastmod>{$mod}</lastmod>";
    foreach ($imgs as $i) echo '<image:image><image:loc>' . e($i) . '</image:loc></image:image>';
    echo "</url>\n";
};
$img = fn(string $f) => APP_URL . '/assets/img/work/' . $f . '.webp';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
$row('', $built, [$img('board-multimeter'), $img('lineup')]);
foreach (['services', 'faults', 'error-codes', 'prices', 'areas', 'about', 'contact', 'book', 'reviews', 'privacy', 'terms'] as $p) $row($p, $built);
$row('our-work', $built, array_map(fn($w) => $img($w[0]), workPhotos()));
foreach (services() as $slug => $s) $row($slug, $built, array_map($img, array_unique(array_merge([$s['photo']], $s['photos']))));
foreach (areas() as $slug => $a) $row('areas/' . $slug, $built);
foreach (faults() as $slug => $f) $row('faults/' . $slug, $built);
$posts = getDB()->query('SELECT slug, cover, published_at, updated_at FROM posts WHERE is_published = 1 AND published_at <= NOW() ORDER BY published_at DESC LIMIT 5000')->fetchAll();
if ($posts) $row('blog', date('Y-m-d', strtotime($posts[0]['published_at'])));
foreach ($posts as $p) $row('blog/' . $p['slug'], date('Y-m-d', strtotime($p['updated_at'] ?: $p['published_at'])), [coverUrl($p['cover'])]);
echo '</urlset>';
