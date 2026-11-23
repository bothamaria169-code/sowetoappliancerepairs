<?php
declare(strict_types=1);

require_once BASE_DIR . '/classes/MetaPoster.php';

/** A short caption for an article: Groq writes it, the link and hashtags are fixed. */
function socialCaption(array $p): string
{
    $hook = '';
    if (aiEnabled() && aiUnderLimit()) {
        $r = groqJson('Write a Facebook caption for an appliance repair service in Soweto. Answer ONLY with JSON {"caption":""}. 2 or 3 short sentences, South African English, useful, no prices, no hashtags, no links, no claims about certificates or experience.', "Article: {$p['title']}\n{$p['excerpt']}", 250);
        $hook = trim(strip_tags((string) ($r['data']['caption'] ?? '')));
        logAi(null, 'caption', $hook !== '');
    }
    if ($hook === '' || preg_match('/\bR\s?\d/', $hook)) $hook = $p['title'] . "\n\n" . $p['excerpt'];
    return $hook . "\n\nRead it: " . postUrl($p) . "\nWhatsApp " . biz('biz_phone') . "\n\n#Soweto #ApplianceRepairs #Joburg";
}

/** The cover as a JPEG Meta can fetch (Instagram refuses WebP). */
function socialImage(array $p): string
{
    return APP_URL . '/cover.php?id=' . (int) $p['id'];
}

/** Shares the oldest published article not yet shared on a connected platform. Returns a log line or ''. */
function shareNextPost(): string
{
    if (get_setting('social_on', '1') !== '1' || IS_LOCAL) return '';
    foreach (['facebook', 'instagram'] as $platform) {
        if (!MetaPoster::connected($platform)) continue;
        $s = getDB()->prepare("SELECT p.* FROM posts p WHERE p.is_published = 1 AND p.published_at > (NOW() - INTERVAL 5 DAY) AND p.published_at <= NOW()
            AND (SELECT COUNT(*) FROM social_posts s WHERE s.post_id = p.id AND s.platform = ? AND s.ok = 1) = 0
            AND (SELECT COUNT(*) FROM social_posts s WHERE s.post_id = p.id AND s.platform = ?) < 3
            AND (SELECT COUNT(*) FROM social_posts s WHERE s.platform = ? AND s.created_at > (NOW() - INTERVAL 20 HOUR)) = 0
            ORDER BY p.published_at LIMIT 1");
        $s->execute([$platform, $platform, $platform]);
        $p = $s->fetch();
        if (!$p) continue;
        $cap = socialCaption($p);
        $r = MetaPoster::publish($platform, $cap, socialImage($p), 'image');
        getDB()->prepare('INSERT INTO social_posts (post_id, platform, caption, ok, remote_id, error) VALUES (?,?,?,?,?,?)')
            ->execute([$p['id'], $platform, $cap, $r['ok'] ? 1 : 0, $r['id'] ?: null, $r['error'] ? mb_substr($r['error'], 0, 500) : null]);
        return ucfirst($platform) . ($r['ok'] ? ' posted: ' : ' failed: ') . ($r['ok'] ? $p['title'] : $r['error']);
    }
    return '';
}
