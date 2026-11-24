<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();
$all = templateDefaults();
$key = isset($all[$_GET['t'] ?? '']) ? $_GET['t'] : '';

/** A made-up lead, for previews and test emails. */
function sampleLead(): array
{
    return ['id' => 0, 'ref' => 'SAR123ABC', 'token' => str_repeat('0', 32), 'full_name' => 'Thandi Example', 'phone' => '0601234567', 'email' => 'customer@example.com', 'service' => 'fridge-repairs', 'area' => 'Soweto', 'address' => '12 Example Street, Diepkloof', 'brand' => 'Samsung',
        'problem' => 'Fridge is warm but the freezer is cold.', 'when_wanted' => null, 'pref_date' => date('Y-m-d', strtotime('+1 day')), 'pref_slot' => '10:00-12:00', 'visit_date' => date('Y-m-d', strtotime('+1 day')), 'visit_from' => '10:00:00', 'visit_to' => '12:00:00', 'reply_note' => 'Confirmed the visit'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k = (string) ($_POST['key'] ?? '');
    if (!isset($all[$k])) redirect('admin/templates.php');
    $act = (string) ($_POST['action'] ?? '');
    if ($act === 'reset') {
        $db->prepare('DELETE FROM email_templates WHERE tkey = ?')->execute([$k]);
        audit('template_reset', $k);
        flash('success', 'The original text is back.');
    } else {
        $subject = postText('subject', 250);
        $body = mb_substr(trim((string) ($_POST['body'] ?? '')), 0, 8000);
        $wa = mb_substr(trim((string) ($_POST['wa'] ?? '')), 0, 2000);
        preg_match_all('/\{([a-z_]+)\}/', $subject . $body . $wa, $m);
        $bad = array_diff(array_unique($m[1]), array_keys(templateWords()));
        if ($subject === '' || $body === '') { flash('error', 'The subject and the message cannot be empty.'); redirect('admin/templates.php?t=' . $k); }
        $db->prepare('REPLACE INTO email_templates (tkey, subject, body, wa, updated_at) VALUES (?, ?, ?, ?, NOW())')->execute([$k, $subject, $body, $wa]);
        audit('template_save', $k);
        flash('success', 'Template saved.');
        if ($bad) flash('error', 'These words are unknown and will be sent as they are: {' . implode('}, {', $bad) . '}');
        if ($act === 'test') {
            $b = sampleLead();
            $err = sendMail($ADMIN['email'], '[Test] ' . fillText($subject, $b), emailLayout('', textToEmail(fillText($body, $b), $b)), [], null, 'test');
            flash($err === '' ? 'success' : 'error', $err === '' ? "Test sent to {$ADMIN['email']}." : 'Test not sent: ' . $err);
        }
    }
    redirect('admin/templates.php?t=' . $k);
}

if ($key) {
    $t = template($key);
    $mine = (bool) $db->query('SELECT COUNT(*) FROM email_templates WHERE tkey = ' . $db->quote($key))->fetchColumn();
    $b = sampleLead();
    adminOpen('templates', $t['name'], ($t['to'] === 'you' ? 'Goes to you. ' : 'Goes to the customer. ') . e($t['when']) . '.', '<a class="btn btn-ghost" href="templates.php">← All templates</a>');
    ?>
    <div class="grid-2"><form class="card" method="post"><div class="card__body"><?= csrfField() ?><input type="hidden" name="key" value="<?= $key ?>">
      <div class="field"><label for="subject">Email subject</label><input id="subject" name="subject" maxlength="250" value="<?= e($t['subject']) ?>"></div>
      <div class="field"><label for="body">Email message <span class="hint">blank line = new paragraph · **bold**</span></label><textarea id="body" name="body" style="min-height:330px"><?= e($t['body']) ?></textarea></div>
      <?php if ($t['to'] === 'customer'): ?><div class="field"><label for="wa">WhatsApp message <span class="hint">*bold*</span></label><textarea id="wa" name="wa" style="min-height:120px"><?= e($t['wa']) ?></textarea></div><?php endif; ?>
      <div class="form-foot"><button class="btn btn-primary" name="action" value="save">Save</button><button class="btn btn-ghost" name="action" value="test">Save and send me a test</button></div></div></form>
    <div><div class="card"><div class="card__head"><h2>Words that fill themselves in</h2></div><div class="card__body"><p class="hint" style="margin-bottom:.8rem">Click a word to put it where your cursor is.</p><div class="chips"><?php foreach (templateWords() as $w => $what): ?><button type="button" class="btn-xs" data-word="{<?= $w ?>}" title="<?= e($what) ?>">{<?= $w ?>} <span class="muted"><?= e($what) ?></span></button><?php endforeach; ?></div></div></div>
    <div class="card" style="margin-top:1.2rem"><div class="card__head"><h2>How it looks</h2><p>Saved version, with an example customer</p></div><div class="card__body"><p><strong><?= e(fillText($t['subject'], $b)) ?></strong></p><div style="border:1px solid var(--line);border-radius:12px;padding:1rem;margin:.6rem 0"><?= textToEmail(fillText($t['body'], $b), $b) ?></div><?php if ($t['wa'] !== ''): ?><p class="hint">WhatsApp</p><p style="white-space:pre-line"><?= e(fillText($t['wa'], $b, true)) ?></p><?php endif; ?></div></div>
    <?php if ($mine): ?><form method="post" data-confirm="Throw away your text and use the original?" style="margin-top:1rem"><?= csrfField() ?><input type="hidden" name="key" value="<?= $key ?>"><input type="hidden" name="action" value="reset"><button class="btn-xs danger">Use the original text</button></form><?php endif; ?></div></div>
    <script>
    (function(){var last=document.getElementById('body');document.querySelectorAll('#subject,#body,#wa').forEach(function(x){x.addEventListener('focus',function(){last=x;});});
    document.querySelectorAll('[data-word]').forEach(function(b){b.onclick=function(){var s=last.selectionStart,e=last.selectionEnd;last.value=last.value.slice(0,s)+b.dataset.word+last.value.slice(e);last.focus();last.selectionStart=last.selectionEnd=s+b.dataset.word.length;};});})();
    </script>
    <?php
    adminClose();
    exit;
}

$mine = $db->query('SELECT tkey FROM email_templates')->fetchAll(PDO::FETCH_COLUMN);
adminOpen('templates', 'Email templates', 'What the website sends, by email and WhatsApp. Click one to change it.');
foreach (['customer' => 'To the customer', 'you' => 'To you'] as $to => $head): ?>
<div class="card" style="margin-bottom:1.2rem"><div class="card__head"><h2><?= $head ?></h2></div><table class="tbl"><tbody><?php foreach ($all as $k => $t): if ($t['to'] === $to): ?><tr><td class="cell-main"><strong><a href="?t=<?= $k ?>"><?= e($t['name']) ?></a></strong><br><small class="muted"><?= e(template($k)['subject']) ?></small></td><td data-label="Sent"><span class="muted"><?= e($t['when']) ?></span></td><td data-label="Text"><span class="tag <?= in_array($k, $mine, true) ? 'tag-on' : 'tag-user' ?>"><?= in_array($k, $mine, true) ? 'Your text' : 'Original' ?></span></td><td><div class="row-actions"><a class="btn-xs" href="?t=<?= $k ?>">Edit</a></div></td></tr><?php endif; endforeach; ?></tbody></table></div>
<?php endforeach;
adminClose();
