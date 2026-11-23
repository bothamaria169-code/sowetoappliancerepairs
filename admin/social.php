<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/social.php';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action'] ?? '') {
        case 'save':
            set_setting('meta_app_id', preg_replace('/\D/', '', postText('meta_app_id', 40)));
            save_secret_setting('meta_app_secret', postText('meta_app_secret', 120));
            set_setting('social_on', !empty($_POST['social_on']) ? '1' : '0');
            flash('success', 'Saved.');
            break;
        case 'disconnect': MetaPoster::disconnect(); flash('success', 'Disconnected.'); break;
        case 'share':
            set_time_limit(200);
            $line = shareNextPost();
            flash(str_contains($line, 'posted') ? 'success' : 'error', $line ?: (IS_LOCAL ? 'Posting only works on the live site.' : 'Nothing to share right now.'));
            break;
    }
    audit('social', (string) ($_POST['action'] ?? ''));
    redirect('admin/social.php');
}
$c = MetaPoster::config();
$log = $db->query('SELECT s.*, p.title FROM social_posts s LEFT JOIN posts p ON p.id = s.post_id ORDER BY s.id DESC LIMIT 60')->fetchAll();
adminOpen('social', 'Social', 'Shares every new article on your Facebook Page and Instagram, at most one a day on each.');
?>
<div class="grid-2 even"><div class="card"><div class="card__head"><h2>Connection</h2></div><div class="card__body">
  <div class="status-line <?= MetaPoster::connected('facebook') ? 'ok' : 'off' ?>"><span class="dot"></span><?= MetaPoster::connected('facebook') ? 'Facebook: ' . e($c['page_name']) : 'Facebook is not connected' ?></div>
  <div class="status-line <?= MetaPoster::connected('instagram') ? 'ok' : 'off' ?>"><span class="dot"></span><?= MetaPoster::connected('instagram') ? 'Instagram: @' . e($c['instagram_name']) : 'Instagram is not connected' ?></div>
  <?php if (MetaPoster::ready()): ?><a class="btn btn-primary" href="meta-connect.php?start=1"><i class="fa-brands fa-facebook-f"></i>Connect with Facebook</a><?php endif; ?>
  <?php if (MetaPoster::connected('facebook')): ?><form method="post" style="display:inline"><?= csrfField() ?><input type="hidden" name="action" value="share"><button class="btn btn-ghost">Share now</button></form> <form method="post" style="display:inline" data-confirm="Disconnect Facebook and Instagram?"><?= csrfField() ?><input type="hidden" name="action" value="disconnect"><button class="btn btn-ghost">Disconnect</button></form><?php endif; ?></div></div>
<form class="card" method="post"><div class="card__head"><h2>Meta app</h2></div><div class="card__body"><?= csrfField() ?><input type="hidden" name="action" value="save">
  <ol class="steps-list"><li>🟦 developers.facebook.com → <strong>My Apps</strong> → <strong>Create App</strong>.</li><li>Add <strong>Facebook Login</strong> → <strong>Settings</strong> → paste this in <strong>Valid OAuth Redirect URIs</strong>:</li></ol><div class="copy-box"><code><?= e(MetaPoster::redirectUri()) ?></code><button type="button" class="btn-xs" data-copy="<?= e(MetaPoster::redirectUri()) ?>">Copy</button></div>
  <div class="field"><label for="meta_app_id">App ID</label><input id="meta_app_id" name="meta_app_id" value="<?= e(get_setting('meta_app_id')) ?>"></div><div class="field"><label for="meta_app_secret">App secret</label><input id="meta_app_secret" name="meta_app_secret" type="password" autocomplete="off" placeholder="<?= e(masked(get_setting('meta_app_secret')) ?: 'not set') ?>"></div>
  <label class="check"><input type="checkbox" name="social_on" value="1" <?= get_setting('social_on', '1') === '1' ? 'checked' : '' ?>> <span>Share new articles automatically</span></label><button class="btn btn-primary">Save</button></div></form></div>
<div class="card"><div class="card__head"><h2>Post log</h2></div><?php if ($log): ?><table class="tbl"><thead><tr><th>Article</th><th>Platform</th><th>Result</th><th>When</th></tr></thead><tbody><?php foreach ($log as $r): ?><tr><td class="cell-main"><?= e($r['title'] ?: '—') ?><?= $r['error'] ? '<br><small style="color:var(--coral)">' . e($r['error']) . '</small>' : '' ?></td><td data-label="Platform"><?= e(ucfirst($r['platform'])) ?></td><td data-label="Result"><span class="tag <?= $r['ok'] ? 'tag-on' : 'tag-off' ?>"><?= $r['ok'] ? 'Posted' : 'Failed' ?></span></td><td data-label="When"><span class="muted"><?= e(timeAgo($r['created_at'])) ?></span></td></tr><?php endforeach; ?></tbody></table><?php else: ?><div class="empty">Nothing shared yet.</div><?php endif; ?></div>
<?php adminClose();
