<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$failed = isset($_GET['failed']);
$rows = getDB()->query('SELECT * FROM sent_emails' . ($failed ? " WHERE status = 'failed'" : '') . ' ORDER BY created_at DESC LIMIT 300')->fetchAll();
adminOpen('emails', 'Sent log', 'Every email the site sent from ' . e(mailboxConfig()['from']) . '. "Via" shows the route it took.',
    '<a class="btn btn-ghost" href="settings.php?tab=email"><i class="fa-solid fa-gear"></i>Email settings</a>');
?>
<div class="tabs"><a href="?" class="<?= $failed ? '' : 'is-on' ?>">All</a><a href="?failed=1" class="<?= $failed ? 'is-on' : '' ?>">Failed</a></div>
<div class="card">
  <?php if ($rows): ?>
  <table class="tbl"><thead><tr><th>To</th><th>Subject</th><th>Type</th><th>Via</th><th>Status</th><th>When</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="cell-main"><strong><?= e($r['to_email']) ?></strong></td><td data-label="Subject"><?= e($r['subject']) ?><?= $r['error'] ? '<br><small style="color:var(--coral)">' . e($r['error']) . '</small>' : '' ?></td>
      <td data-label="Type"><span class="muted"><?= e($r['kind'] ?: '—') ?></span></td>
      <td data-label="Via"><span class="muted" title="smtp = the mail server · localhost = local route on the same server · mail() = built-in mail, may land in spam"><?= e(in_array($r['mailbox'] ?? '', ['smtp', 'localhost', 'mail()'], true) ? $r['mailbox'] : '—') ?></span></td>
      <td data-label="Status"><span class="tag <?= $r['status'] === 'sent' ? 'tag-on' : 'tag-off' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
      <td data-label="When"><span class="muted"><?= e(timeAgo($r['created_at'])) ?></span></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php else: ?><div class="empty"><i class="fa-solid fa-envelope"></i>No emails yet.</div><?php endif; ?>
</div>
<?php adminClose();
