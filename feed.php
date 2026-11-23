<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

header('Content-Type: application/rss+xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"><channel><title>' . e(APP_NAME) . ': advice</title><link>' . e(APP_URL) . '/blog</link><description>' . e(APP_TAGLINE) . '</description><language>en-za</language><atom:link href="' . e(APP_URL) . '/feed.xml" rel="self" type="application/rss+xml"/>' . "\n";
foreach (latestPosts(20) as $p) echo '<item><title>' . e($p['title']) . '</title><link>' . e(postUrl($p)) . '</link><guid>' . e(postUrl($p)) . '</guid><pubDate>' . date('r', strtotime($p['published_at'])) . '</pubDate><description>' . e($p['excerpt']) . "</description></item>\n";
echo '</channel></rss>';
