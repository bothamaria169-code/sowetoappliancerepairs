<?php
declare(strict_types=1);

/** Every page starts here: config, database, auth, content and layout. */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/articles.php';
require_once __DIR__ . '/bookings.php';
require_once __DIR__ . '/layout.php';

startAppSession();

// Where this visitor first came from; saved with a booking so the owner sees what brings work.
if (!isset($_SESSION['first_ref'])) {
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $_SESSION['first_ref'] = $ref !== '' && !str_starts_with($ref, APP_URL) ? mb_substr(strtok($ref, '?'), 0, 250) : 'Direct';
}
if (!empty($_GET['gclid']) || !empty($_GET['gbraid']) || !empty($_GET['wbraid'])) $_SESSION['first_ref'] = 'Google Ads';
