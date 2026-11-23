<?php
declare(strict_types=1);

/**
 * Every scheduled job. cPanel → Cron Jobs, every 10 minutes:
 *   /usr/local/bin/php $HOME/public_html/cron.php >> $HOME/sar-cron.log 2>&1
 * Also reachable as /cron.php?key=<cron_key> for hosts without PHP cron.
 */
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/config/config.php';
    $key = (string) secret('cron_key', '');
    if ($key === '' || $key === 'CHANGE_ME' || !hash_equals($key, (string) ($_GET['key'] ?? ''))) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/plain; charset=utf-8');
}
set_time_limit(600);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/articles.php';
require_once __DIR__ . '/includes/social.php';

$db = getDB();
$say = fn(string $m) => print(date('Y-m-d H:i') . ' ' . $m . "\n");
if ((int) $db->query("SELECT GET_LOCK('sar_cron', 1)")->fetchColumn() !== 1) exit;
set_setting('scheduler_heartbeat', (string) time());

// 1. Write an article on the chosen days, after the chosen hour, once a day.
$days  = array_filter(explode(',', get_setting('article_days', 'Tue,Fri')));
$hour  = (int) get_setting('article_hour', '7');
$today = date('Y-m-d');
if (get_setting('articles_on', '1') === '1' && in_array(date('D'), $days, true) && (int) date('G') >= $hour && get_setting('article_last_day') !== $today) {
    $tries = get_setting('article_try_day') === $today ? (int) get_setting('article_try_n', '0') : 0;
    if ($tries < 3) {
        set_setting('article_try_day', $today);
        set_setting('article_try_n', (string) ($tries + 1));
        $added = refillTopics();
        if ($added) $say("Topic queue: {$added} new topics.");
        $r = writeNextArticle();
        if ($r['ok']) {
            set_setting('article_last_day', $today);
            $say('Article written: ' . $r['title']);
        } else {
            $say('Article failed: ' . $r['error']);
        }
    }
}

// 2. Share new published articles on Facebook and Instagram, one per run.
if ($line = shareNextPost()) $say($line);

// 3. Housekeeping.
if (get_setting('cleanup_day') !== $today) {
    set_setting('cleanup_day', $today);
    $db->exec('DELETE FROM rate_hits WHERE created_at < (NOW() - INTERVAL 2 DAY)');
    $db->exec('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 60 DAY)');
    $db->exec('DELETE FROM chat_log WHERE created_at < (NOW() - INTERVAL 90 DAY)');
    $db->exec('DELETE FROM password_resets WHERE created_at < (NOW() - INTERVAL 7 DAY)');
    $db->exec('DELETE FROM page_hits WHERE day < (CURDATE() - INTERVAL 400 DAY)');
}
$db->query("SELECT RELEASE_LOCK('sar_cron')");
