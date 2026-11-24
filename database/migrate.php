<?php
declare(strict_types=1);

/**
 * Brings the database up to the current schema on the first request after a
 * deploy; no SQL import needed. Works on MySQL 8 and MariaDB. No table or
 * column is named after a MySQL 8 reserved word.
 */
const SCHEMA_VERSION = 3;

function runMigrations(PDO $db): void
{
    $db->exec("CREATE TABLE IF NOT EXISTS app_settings (k VARCHAR(64) PRIMARY KEY, v MEDIUMTEXT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $v = (int) $db->query("SELECT v FROM app_settings WHERE k = 'schema_version'")->fetchColumn();
    if ($v < SCHEMA_VERSION) {
        if ((int) $db->query("SELECT GET_LOCK('sar_migrate', 10)")->fetchColumn() !== 1) return;
        try {
            if ($v < 1) { migrationV1($db); seedTopics($db); seedPosts($db); }
            elseif ($v < 2) seedTopics($db);   // microwave topics
            if ($v < 3) migrationV3($db);
            $db->prepare("REPLACE INTO app_settings (k, v) VALUES ('schema_version', ?)")->execute([(string) SCHEMA_VERSION]);
        } finally {
            $db->query("SELECT RELEASE_LOCK('sar_migrate')");
        }
    }
    seedAdmin($db);
}

function migrationV1(PDO $db): void
{
    $t = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $now = 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP';

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE, full_name VARCHAR(120) NULL, password_hash VARCHAR(255) NULL,
        role ENUM('user','admin') NOT NULL DEFAULT 'admin', is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login_at DATETIME NULL, login_count INT NOT NULL DEFAULT 0, created_at {$now}){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL, ip VARCHAR(45) NOT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0, method VARCHAR(20) NOT NULL DEFAULT 'password', created_at {$now},
        INDEX (email(120), created_at), INDEX (ip, created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL, used_at DATETIME NULL, ip VARCHAR(45) NULL, created_at {$now},
        INDEX (token_hash), FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS audit_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id INT UNSIGNED NULL, action VARCHAR(60) NOT NULL,
        target VARCHAR(200) NULL, detail TEXT NULL, ip VARCHAR(45) NULL, created_at {$now}, INDEX (created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS sent_emails (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, to_email VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL,
        kind VARCHAR(40) NULL, status VARCHAR(10) NOT NULL, error VARCHAR(500) NULL, mailbox VARCHAR(10) NULL, created_at {$now}, INDEX (created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS ai_requests (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, kind VARCHAR(40) NOT NULL,
        ok TINYINT(1) NOT NULL DEFAULT 0, created_at {$now}, INDEX (created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS rate_hits (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bucket VARCHAR(60) NOT NULL, created_at {$now}, INDEX (bucket, created_at)){$t}");

    $db->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, ref VARCHAR(12) NOT NULL UNIQUE,
        full_name VARCHAR(120) NOT NULL, phone VARCHAR(40) NOT NULL, email VARCHAR(190) NULL,
        service VARCHAR(60) NOT NULL, area VARCHAR(80) NOT NULL, address VARCHAR(255) NULL,
        brand VARCHAR(80) NULL, problem TEXT NULL, when_wanted VARCHAR(60) NULL, photo VARCHAR(255) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'new', notes TEXT NULL, quoted DECIMAL(10,2) NULL,
        source_page VARCHAR(255) NULL, referrer VARCHAR(255) NULL, consent_at DATETIME NULL,
        created_at {$now}, updated_at DATETIME NULL, INDEX (status, created_at), INDEX (created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120) NOT NULL, area VARCHAR(80) NULL,
        service VARCHAR(60) NULL, stars TINYINT UNSIGNED NOT NULL, body TEXT NOT NULL,
        is_approved TINYINT(1) NOT NULL DEFAULT 0, created_at {$now}, INDEX (is_approved, created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(200) NOT NULL UNIQUE, title VARCHAR(220) NOT NULL,
        meta_title VARCHAR(160) NULL, meta_description VARCHAR(260) NULL, excerpt TEXT NULL, body MEDIUMTEXT NOT NULL,
        faqs MEDIUMTEXT NULL, keyword VARCHAR(160) NULL, service VARCHAR(60) NULL, area VARCHAR(60) NULL, cover VARCHAR(255) NULL,
        source VARCHAR(10) NOT NULL DEFAULT 'manual', is_published TINYINT(1) NOT NULL DEFAULT 0,
        views INT UNSIGNED NOT NULL DEFAULT 0, published_at DATETIME NULL, created_at {$now}, updated_at DATETIME NULL,
        INDEX (is_published, published_at), INDEX (service)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS article_topics (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(220) NOT NULL, keyword VARCHAR(160) NOT NULL UNIQUE,
        service VARCHAR(60) NULL, area VARCHAR(60) NULL, status VARCHAR(12) NOT NULL DEFAULT 'queued',
        tries INT NOT NULL DEFAULT 0, last_error VARCHAR(400) NULL, post_id INT UNSIGNED NULL,
        sort_order INT NOT NULL DEFAULT 100, created_at {$now}, INDEX (status, sort_order)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS media (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200) NULL, service VARCHAR(60) NULL,
        path VARCHAR(255) NOT NULL, ik_url VARCHAR(500) NULL, ik_file_id VARCHAR(80) NULL,
        width INT NULL, height INT NULL, show_in_gallery TINYINT(1) NOT NULL DEFAULT 1, created_at {$now}, INDEX (show_in_gallery)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS page_hits (
        day DATE NOT NULL, path VARCHAR(190) NOT NULL, origin VARCHAR(40) NOT NULL, n INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (day, path, origin)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS lead_taps (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, kind VARCHAR(12) NOT NULL, path VARCHAR(190) NULL, created_at {$now}, INDEX (created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS chat_log (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, chat_key CHAR(24) NOT NULL, role VARCHAR(10) NOT NULL, body TEXT NOT NULL,
        created_at {$now}, INDEX (chat_key), INDEX (created_at)){$t}");
    $db->exec("CREATE TABLE IF NOT EXISTS social_posts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, post_id INT UNSIGNED NULL, platform VARCHAR(12) NOT NULL, caption TEXT NULL,
        ok TINYINT(1) NOT NULL DEFAULT 0, remote_id VARCHAR(120) NULL, error VARCHAR(500) NULL, created_at {$now}, INDEX (created_at)){$t}");
}

function columnExists(PDO $db, string $table, string $column): bool
{
    $s = $db->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $s->execute([$table, $column]);
    return (bool) $s->fetchColumn();
}

/** Booking times, tick boxes, planned visits, email templates and lead history. */
function migrationV3(PDO $db): void
{
    $t = ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $cols = [
        'token' => 'CHAR(32) NULL', 'source' => "VARCHAR(20) NOT NULL DEFAULT 'website'",
        'pref_date' => 'DATE NULL', 'pref_slot' => 'VARCHAR(20) NULL',
        'visit_date' => 'DATE NULL', 'visit_from' => 'TIME NULL', 'visit_to' => 'TIME NULL',
        'contacted_at' => 'DATETIME NULL', 'went_at' => 'DATETIME NULL', 'repaired_at' => 'DATETIME NULL', 'lost_at' => 'DATETIME NULL', 'lost_reason' => 'VARCHAR(190) NULL',
        'confirmed_at' => 'DATETIME NULL', 'needs_reply' => 'TINYINT(1) NOT NULL DEFAULT 0', 'reply_note' => 'VARCHAR(400) NULL',
        'reminder_sent_at' => 'DATETIME NULL', 'admin_notified_at' => 'DATETIME NULL', 'notify_tries' => 'INT NOT NULL DEFAULT 0',
    ];
    foreach ($cols as $c => $def) if (!columnExists($db, 'bookings', $c)) $db->exec("ALTER TABLE bookings ADD COLUMN {$c} {$def}");
    $db->exec('CREATE TABLE IF NOT EXISTS booking_events (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, booking_id INT UNSIGNED NOT NULL, kind VARCHAR(20) NOT NULL, detail VARCHAR(500) NULL,
        admin_id INT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX (booking_id, created_at),
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE)' . $t);
    $db->exec('CREATE TABLE IF NOT EXISTS email_templates (
        tkey VARCHAR(40) PRIMARY KEY, subject VARCHAR(255) NULL, body TEXT NULL, wa TEXT NULL, updated_at DATETIME NULL)' . $t);

    // Leads that were there before: keep their stage, and give each a private link.
    $db->exec("UPDATE bookings SET contacted_at = COALESCE(updated_at, created_at) WHERE contacted_at IS NULL AND status IN ('contacted','booked','done')");
    $db->exec("UPDATE bookings SET repaired_at = COALESCE(updated_at, created_at) WHERE repaired_at IS NULL AND status = 'done'");
    $db->exec("UPDATE bookings SET lost_at = COALESCE(updated_at, created_at) WHERE lost_at IS NULL AND status = 'lost'");
    $db->exec("UPDATE bookings SET status = 'contacted' WHERE status = 'booked' AND visit_date IS NULL");
    $db->exec('UPDATE bookings SET admin_notified_at = created_at WHERE admin_notified_at IS NULL');
    $u = $db->prepare('UPDATE bookings SET token = ? WHERE id = ?');
    foreach ($db->query('SELECT id FROM bookings WHERE token IS NULL')->fetchAll(PDO::FETCH_COLUMN) as $id) $u->execute([bin2hex(random_bytes(16)), $id]);
    try { $db->exec('CREATE UNIQUE INDEX bookings_token ON bookings (token)'); } catch (PDOException) { /* already there */ }
    try { $db->exec('CREATE INDEX bookings_visit ON bookings (visit_date, visit_from)'); } catch (PDOException) { /* already there */ }
}

/** Real searches, in the order we want to win them. */
function seedTopics(PDO $db): void
{
    $topics = require __DIR__ . '/seed/topics.php';
    $s = $db->prepare('INSERT IGNORE INTO article_topics (title, keyword, service, area, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($topics as $i => [$title, $kw, $service, $area]) $s->execute([$title, $kw, $service, $area, ($i + 1) * 10]);
}

function seedPosts(PDO $db): void
{
    $file = __DIR__ . '/seed/posts.json';
    if (!is_file($file)) return;
    $s = $db->prepare("INSERT IGNORE INTO posts (slug, title, meta_title, meta_description, excerpt, body, faqs, keyword, service, area, cover, source, is_published, published_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ai', 1, ?)");
    $done = $db->prepare("UPDATE article_topics SET status = 'done' WHERE keyword = ?");
    $posts = json_decode((string) file_get_contents($file), true) ?: [];
    foreach ($posts as $i => $p) {
        $s->execute([$p['slug'], $p['title'], $p['meta_title'], $p['meta_description'], $p['excerpt'], $p['body'],
            json_encode($p['faqs'], JSON_UNESCAPED_UNICODE), $p['keyword'], $p['service'], $p['area'], $p['cover'],
            date('Y-m-d 09:00:00', strtotime('-' . ($i * 2) . ' days'))]);
        $done->execute([$p['keyword']]);
    }
}

function seedAdmin(PDO $db): void
{
    $pw = (string) secret('admin_seed_password', '');
    if ($pw === '' || $pw === 'CHANGE_ME') return;
    $s = $db->prepare('SELECT id FROM users WHERE email = ?');
    $s->execute([ADMIN_EMAIL]);
    if ($s->fetchColumn()) return;
    $db->prepare("INSERT INTO users (email, full_name, password_hash, role, is_active) VALUES (?, 'Administrator', ?, 'admin', 1)")
       ->execute([ADMIN_EMAIL, password_hash($pw, PASSWORD_DEFAULT)]);
}
