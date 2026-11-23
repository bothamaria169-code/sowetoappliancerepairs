<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem = password_verify((string) ($_POST['current'] ?? ''), (string) $ADMIN['password_hash']) ? passwordProblem((string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? '')) : 'The current password is wrong.';
    if ($problem) flash('error', $problem);
    else {
        getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash((string) $_POST['password'], PASSWORD_DEFAULT), $ADMIN['id']]);
        audit('password_change');
        flash('success', 'Password changed.');
    }
    redirect('admin/account.php');
}
adminOpen('account', 'My account', e($ADMIN['email']));
?>
<form class="card" method="post" style="max-width:520px"><div class="card__head"><h2>Change password</h2></div><div class="card__body"><?= csrfField() ?>
  <div class="field"><label for="current">Current password</label><input id="current" name="current" type="password" autocomplete="current-password" required></div>
  <div class="field"><label for="password">New password <span class="hint">at least 8 characters, with a number</span></label><input id="password" name="password" type="password" autocomplete="new-password" required></div>
  <div class="field"><label for="confirm">New password again</label><input id="confirm" name="confirm" type="password" autocomplete="new-password" required></div>
  <button class="btn btn-primary">Change password</button></div></form>
<?php adminClose();
