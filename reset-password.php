<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$user  = findResetUser($token);
$error = '';
if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = (string) ($_POST['password'] ?? '');
    if (!csrfValid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($problem = passwordProblem($pw, (string) ($_POST['confirm'] ?? ''))) {
        $error = $problem;
    } elseif ($u = completePasswordReset($token, $pw)) {
        beginUserSession($u);
        flash('success', 'Password saved. You\'re signed in.');
        redirect('admin/');
    }
}

authOpen('Set a new password', $user ? 'Set a new password' : 'Link expired', $user
    ? 'For <strong>' . e($user['email']) . '</strong>.'
    : 'This reset link has already been used or has expired. Ask for a new one below.');
?>
      <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div><?php endif; ?>
      <?php if ($user): ?>
      <form method="POST" novalidate data-busy>
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <div class="field">
          <label for="password">New password</label>
          <div class="input-wrap"><i class="fa-solid fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="At least 8 characters, with a number" autocomplete="new-password" required autofocus>
            <button type="button" class="pw-toggle" data-toggle-pw="password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
          </div>
        </div>
        <div class="field">
          <label for="confirm">Confirm password</label>
          <div class="input-wrap"><i class="fa-solid fa-lock"></i><input type="password" id="confirm" name="confirm" autocomplete="new-password" required></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Save password</button>
      </form>
      <?php else: ?>
        <a class="btn btn-primary btn-block btn-lg" href="<?= APP_URL ?>/forgot-password.php">Send a new link</a>
      <?php endif; ?>
<?php authClose();
