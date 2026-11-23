<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$rows = getDB()->query('SELECT * FROM chat_log WHERE created_at > (NOW() - INTERVAL 30 DAY) ORDER BY id DESC LIMIT 600')->fetchAll();
$chats = [];
foreach ($rows as $r) $chats[$r['chat_key']][] = $r;
adminOpen('chats', 'Chats', 'What visitors ask the website assistant. Kept for 90 days. Good ideas for new articles.');
if (!$chats): ?><div class="card"><div class="empty"><i class="fa-solid fa-comments"></i>No chats yet.</div></div><?php endif;
foreach (array_slice($chats, 0, 60) as $msgs): ?>
<div class="card" style="margin-bottom:1rem"><div class="card__head"><h2><?= e(timeAgo($msgs[0]['created_at'])) ?></h2></div><div class="card__body"><?php foreach (array_reverse($msgs) as $m): ?><p style="margin-bottom:.5rem"><strong><?= $m['role'] === 'bot' ? 'Assistant' : 'Visitor' ?>:</strong> <?= nl2br(e($m['body'])) ?></p><?php endforeach; ?></div></div>
<?php endforeach;
adminClose();
