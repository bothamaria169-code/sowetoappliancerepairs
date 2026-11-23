<?php
declare(strict_types=1);

// Counts taps on Call and WhatsApp. No personal data is stored.
require_once dirname(__DIR__) . '/includes/boot.php';

$kind = (string) ($_POST['kind'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($kind, ['call', 'whatsapp'], true) && !isAdmin() && !rateLimited('tap', 20, 60)) {
    getDB()->prepare('INSERT INTO lead_taps (kind, path) VALUES (?, ?)')->execute([$kind, mb_substr((string) ($_POST['path'] ?? ''), 0, 190)]);
}
http_response_code(204);
