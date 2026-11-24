<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/media.php';

$tabs = ['business' => 'Business', 'articles' => 'Article writer', 'ai' => 'AI (Groq)', 'email' => 'Email', 'tracking' => 'Google & Meta', 'photos' => 'ImageKit'];
$tab = isset($tabs[$_GET['tab'] ?? '']) ? $_GET['tab'] : 'business';
$fields = [
    'business' => ['biz_phone', 'biz_whatsapp', 'biz_email', 'biz_callout', 'biz_guarantee', 'biz_hours', 'biz_facebook', 'biz_instagram', 'biz_gbp', 'biz_review_url'],
    'articles' => ['articles_on', 'articles_review', 'article_hour'],
    'ai'       => ['ai_enabled', 'ai_monthly_limit', 'groq_model'],
    'email'    => ['notify_email', 'smtp_host', 'smtp_port', 'smtp_user', 'mail_from_name'],
    'tracking' => ['gsc_verification', 'bing_verification', 'ga4_id', 'ads_id', 'ads_wa_label', 'meta_pixel_id'],
    'photos'   => [],
];
$secrets = ['email' => ['smtp_pass'], 'ai' => ['groq_api_key']];
$checks = ['articles_on', 'ai_enabled'];
$links = ['biz_facebook', 'biz_instagram', 'biz_gbp', 'biz_review_url'];
$week = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'test_email') {
        $to = filter_var(postText('test_to', 190), FILTER_VALIDATE_EMAIL) ?: $ADMIN['email'];
        $err = sendMail($to, 'Test email from ' . APP_NAME, emailLayout('It works!', '<p>Your email settings are correct.</p>'), [], null, 'test');
        flash($err === '' ? 'success' : 'error', $err === '' ? "Sent to {$to} (route: {$GLOBALS['SAR_LAST_ROUTE']}). Check the inbox and spam." : 'Sending failed: ' . $err);
    } elseif ($action === 'test_ai') {
        $models = groqModels(true);
        if (!aiKey()) flash('error', 'Save a Groq API key first.');
        elseif (!$models) flash('error', 'Groq refused the key or could not be reached.');
        else {
            $r = groqJson('Answer only with JSON {"msg":""}.', 'Say hello to an appliance repair company in 5 words.', 60);
            flash($r['ok'] ? 'success' : 'error', $r['ok'] ? 'Groq works with ' . groqModel() . ': "' . ($r['data']['msg'] ?? 'ok') . '"' : 'Groq error: ' . $r['error']);
        }
    } elseif ($action === 'test_ik') {
        $err = ImageKit::test();
        flash($err === '' ? 'success' : 'error', $err === '' ? 'ImageKit works.' : 'ImageKit: ' . $err);
    } else {
        foreach ($fields[$tab] as $k) {
            $v = trim((string) ($_POST[$k] ?? ''));
            if (in_array($k, $checks, true)) $v = !empty($_POST[$k]) ? '1' : '0';
            if (in_array($k, $links, true) && $v !== '' && !preg_match('#^https://#', $v)) { flash('error', 'Links must start with https://'); continue; }
            if (in_array($k, ['biz_callout', 'biz_guarantee', 'biz_whatsapp', 'article_hour', 'ai_monthly_limit'], true)) $v = preg_replace('/\D/', '', $v);
            if (in_array($k, ['gsc_verification', 'bing_verification'], true) && preg_match('/content="([^"]+)"/', $v, $m)) $v = $m[1];
            if (in_array($k, ['ads_id', 'ads_wa_label'], true) && $v !== '') {
                if (!preg_match($k === 'ads_id' ? '#AW-\d+#' : '#AW-\d+/[\w-]+#', $v, $m)) { flash('error', $k === 'ads_id' ? 'The Google Ads ID looks like AW-123456789.' : 'The WhatsApp conversion looks like AW-123456789/AbCdEf. You can paste the whole event snippet.'); continue; }
                $v = $m[0];
            }
            set_setting($k, $v);
        }
        if ($tab === 'articles') set_setting('article_days', implode(',', array_intersect($week, (array) ($_POST['article_days'] ?? []))));
        foreach ($secrets[$tab] ?? [] as $k) save_secret_setting($k, (string) ($_POST[$k] ?? ''));
        audit('settings_save', $tab);
        flash('success', $tabs[$tab] . ' settings saved.');
    }
    redirect('admin/settings.php?tab=' . $tab);
}

$in = fn(string $k, string $label, string $hint = '', string $def = '', string $type = 'text') => '<div class="field"><label for="' . $k . '">' . e($label) . ($hint ? ' <span class="hint">' . $hint . '</span>' : '') . '</label><input id="' . $k . '" type="' . $type . '" name="' . $k . '" value="' . e(get_setting($k, $def)) . '"></div>';
$sec = fn(string $k, string $label, string $hint = '') => '<div class="field"><label for="' . $k . '">' . e($label) . ($hint ? ' <span class="hint">' . $hint . '</span>' : '') . '</label><input id="' . $k . '" type="password" name="' . $k . '" autocomplete="off" placeholder="' . e(masked(get_setting($k)) ?: 'using config/secrets.php') . '"></div>';
$chk = fn(string $k, string $label, string $def) => '<label class="check"><input type="checkbox" name="' . $k . '" value="1" ' . (get_setting($k, $def) === '1' ? 'checked' : '') . '> <span>' . $label . '</span></label>';
$test = ['email' => ['test_email', 'Send test email'], 'ai' => ['test_ai', 'Test AI'], 'photos' => ['test_ik', 'Test ImageKit']][$tab] ?? null;
adminOpen('settings', 'Settings', 'Changes apply straight away.');
?>
<div class="tabs"><?php foreach ($tabs as $k => $l): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'is-on' : '' ?>"><?= e($l) ?></a><?php endforeach; ?></div>
<div class="card" style="max-width:860px"><form class="card__body" method="post"><?= csrfField() ?>
<?php if ($tab === 'business'): ?>
  <p class="hint" style="margin-bottom:1rem">Used on every page and in Google's business data. Keep the name and phone the same as your Google Business Profile.</p><div class="form-grid">
  <?= $in('biz_phone', 'Phone', 'as shown', biz('biz_phone')) . $in('biz_whatsapp', 'WhatsApp', 'digits only, 27…', biz('biz_whatsapp')) . $in('biz_email', 'Email', '', biz('biz_email')) . $in('biz_hours', 'Hours', '', biz('biz_hours')) . $in('biz_callout', 'Call-out fee (R)', '', biz('biz_callout')) . $in('biz_guarantee', 'Guarantee (months)', '', biz('biz_guarantee')) . $in('biz_facebook', 'Facebook link') . $in('biz_instagram', 'Instagram link') . $in('biz_gbp', 'Google Business Profile link') . $in('biz_review_url', 'Google review link') ?></div>
<?php elseif ($tab === 'articles'): ?>
  <?= $chk('articles_on', 'Write articles by themselves', '1') ?>
  <div class="field"><label>Days</label><div class="chips"><?php $on = explode(',', get_setting('article_days', 'Tue,Fri')); foreach ($week as $d): ?><label class="chip-check"><input type="checkbox" name="article_days[]" value="<?= $d ?>" <?= in_array($d, $on, true) ? 'checked' : '' ?>><?= $d ?></label><?php endforeach; ?></div></div>
  <div class="field"><label for="articles_review">Check before publishing</label><select id="articles_review" name="articles_review"><?php foreach (['risky' => 'Only stove, oven, microwave and geyser articles (recommended)', 'all' => 'Every article', 'none' => 'Publish everything straight away'] as $k => $l): ?><option value="<?= $k ?>" <?= get_setting('articles_review', 'risky') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <?= $in('article_hour', 'From which hour', '0 to 23', '7', 'number') ?><p class="hint">Two good articles a week beat one every day. Google rewards helpful pages, not many pages.</p>
<?php elseif ($tab === 'ai'): ?>
  <div class="status-line <?= aiEnabled() ? 'ok' : 'off' ?>"><span class="dot"></span><?= aiEnabled() ? 'AI is on · model ' . e(groqModel()) . ' · ' . aiUsedThisMonth() . ' calls this month' : 'AI is off' ?></div>
  <?= $chk('ai_enabled', 'AI on (articles, chat, captions)', '1') . $sec('groq_api_key', 'Groq API key', 'console.groq.com → API Keys. Blank keeps the current one.') ?>
  <div class="field"><label for="groq_model">Model</label><select id="groq_model" name="groq_model"><option value="">Choose the best one for me</option><?php foreach (groqModels() as $m): ?><option <?= get_setting('groq_model') === $m ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?></select></div><?= $in('ai_monthly_limit', 'Calls per month', 'stops runaway use', '600', 'number') ?>
<?php elseif ($tab === 'email'): ?>
  <div class="form-grid"><?= $in('notify_email', 'Send bookings to', '', ADMIN_EMAIL, 'email') . $in('mail_from_name', 'From name', '', APP_NAME) . $in('smtp_user', 'Sending mailbox', '', SMTP_USER) . $sec('smtp_pass', 'Mailbox password', 'blank keeps the saved one') . $in('smtp_host', 'Mail server', '', SMTP_HOST) . $in('smtp_port', 'Port', '465 or 587', (string) SMTP_PORT) ?><div class="field"><label for="test_to">Send the test to</label><input id="test_to" type="email" name="test_to" placeholder="<?= e($ADMIN['email']) ?>"></div></div>
<?php elseif ($tab === 'tracking'): ?>
  <?= $in('gsc_verification', 'Google Search Console code', 'paste the whole meta tag or just the code') . $in('bing_verification', 'Bing Webmaster code') . $in('ga4_id', 'Google Analytics 4', 'G-…') . $in('ads_id', 'Google Ads', 'AW-…') . $in('ads_wa_label', 'Google Ads: WhatsApp click', 'AW-…/label, or paste the whole event snippet') . $in('meta_pixel_id', 'Meta Pixel', 'numbers only') ?>
<?php else: ?>
  <div class="status-line <?= ImageKit::ready() ? 'ok' : 'off' ?>"><span class="dot"></span><?= ImageKit::ready() ? 'Keys found in config/secrets.php' : 'No ImageKit keys in config/secrets.php' ?></div><p class="hint" style="margin-bottom:1rem">Photos you add under Photos are also sent to ImageKit, which serves them fast and in the right size.</p>
<?php endif; ?>
  <div class="form-foot"><?php if ($tab !== 'photos'): ?><button class="btn btn-primary" name="action" value="save">Save</button><?php endif; if ($test): ?><button class="btn btn-ghost" name="action" value="<?= $test[0] ?>"><?= $test[1] ?></button><?php endif; ?></div>
</form></div>
<?php adminClose();
