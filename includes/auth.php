<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/mailer.php';

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('sar_sess');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => str_starts_with(APP_URL, 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function isLoggedIn(): bool
{
    startAppSession();
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        $back = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . APP_URL . '/login.php' . ($back ? '?next=' . rawurlencode($back) : ''));
        exit;
    }
}

function getCurrentUser(): ?array
{
    static $cache = null;
    if (!isLoggedIn()) return null;
    if ($cache && (int) $cache['id'] === (int) $_SESSION['user_id']) return $cache;
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    if (!$user || !(bool) $user['is_active']) {
        logoutUser();
        return null;
    }
    return $cache = $user;
}

function findUserByEmail(string $email): ?array
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    return $stmt->fetch() ?: null;
}

/** @return true|'invalid'|'disabled'|'locked'|'nopassword' */
function loginUser(string $email, string $password): bool|string
{
    $email = strtolower(trim($email));
    if (tooManyAttempts($email)) return 'locked';
    $row = findUserByEmail($email);
    if ($row && empty($row['password_hash'])) {
        recordAttempt($email, false);
        return 'nopassword';
    }
    if (!$row || !password_verify($password, (string) $row['password_hash'])) {
        recordAttempt($email, false);
        return 'invalid';
    }
    if (!(bool) $row['is_active']) {
        recordAttempt($email, false);
        return 'disabled';
    }
    if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
        getDB()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    recordAttempt($email, true);
    beginUserSession($row);
    return true;
}

function beginUserSession(array $user): void
{
    startAppSession();
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'] ?? 'user';
    getDB()->prepare('UPDATE users SET last_login_at = NOW(), login_count = login_count + 1 WHERE id = ?')->execute([$user['id']]);
}


function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireAdmin(): void
{
    requireLogin();
    $user = getCurrentUser();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ' . APP_URL . '/');
        exit;
    }
    $_SESSION['user_role'] = 'admin';
}

const RESET_MINUTES = 60;

function requestPasswordReset(string $email): void
{
    $user = findUserByEmail($email);
    if (!$user || !(bool) $user['is_active']) return;
    $s = getDB()->prepare('SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
    $s->execute([$user['id']]);
    if ((int) $s->fetchColumn() >= 3) return;
    sendResetLink($user);
}

function sendResetLink(array $user): string
{
    $token = bin2hex(random_bytes(32));
    getDB()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at, ip) VALUES (?, ?, NOW() + INTERVAL ' . RESET_MINUTES . ' MINUTE, ?)')
        ->execute([$user['id'], hash('sha256', $token), clientIp()]);
    $link = APP_URL . '/reset-password.php?token=' . $token;
    $hasPw = !empty($user['password_hash']);
    return sendMail($user['email'], ($hasPw ? 'Reset your ' : 'Set your ') . APP_NAME . ' password', emailLayout(
        $hasPw ? 'Reset your password' : 'Set a password',
        '<p>We received a request to ' . ($hasPw ? 'reset' : 'set') . ' the password for <strong>' . e($user['email']) . '</strong>.</p>'
        . '<p>The link works once and expires in ' . RESET_MINUTES . ' minutes. If you didn\'t ask for this, ignore this email.</p>',
        $hasPw ? 'Reset password' : 'Set password', $link
    ), [], null, 'reset');
}

function findResetUser(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
    $s = getDB()->prepare(
        'SELECT u.* FROM password_resets r JOIN users u ON u.id = r.user_id
         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW() AND u.is_active = 1'
    );
    $s->execute([hash('sha256', $token)]);
    return $s->fetch() ?: null;
}

function completePasswordReset(string $token, string $password): ?array
{
    $user = findResetUser($token);
    if (!$user) return null;
    $db = getDB();
    $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    $db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$user['id']]);
    return $user;
}

function passwordProblem(string $password, string $confirm): ?string
{
    if (strlen($password) < 8) return 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) return 'Use at least one letter and one number.';
    if ($password !== $confirm) return 'Passwords do not match.';
    return null;
}

function logoutUser(): void
{
    startAppSession();
    session_unset();
    session_destroy();
}
