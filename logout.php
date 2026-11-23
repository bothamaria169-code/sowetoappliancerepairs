<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValid()) {
    logoutUser();
}
header('Location: ' . APP_URL . '/');
exit;
