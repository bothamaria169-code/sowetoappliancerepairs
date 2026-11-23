<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$sent = false;
$email = strtolower(trim((string) ($_GET['email'] ?? '')));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if (!csrfValid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        requestPasswordReset($email);
        $sent = true;
    }
}

authOpen('Forgot password', $sent ? 'Check your email' : 'Forgot your password?', $sent
    ? 'If <strong>' . e($email) . '</strong> has an account, a reset link is on its way. It works once and expires in 60 minutes.'
    : 'Enter your email and we\'ll send you a link to set a new password.');
?>
      <?php if ($error): ?><div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div><?php endif; ?>
      <?php if (!$sent): ?>
      <form method="POST" novalidate data-busy>
        <?= csrfField() ?>
        <div class="field">
          <label for="email">Email</label>
          <div class="input-wrap"><i class="fa-regular fa-envelope"></i><input type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg">Send reset link</button>
      </form>
      <?php else: ?>
        <div class="auth__icon"><i class="fa-solid fa-envelope-circle-check"></i></div>
        <p class="hint">No email after a few minutes? Check your spam folder, or call us on <?= e(biz('biz_phone')) ?>.</p>
      <?php endif; ?>
      <p class="auth__alt"><a href="<?= APP_URL ?>/login">Back to sign in</a></p>
<?php authClose();
