<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$rows = getDB()->query('SELECT a.*, u.email FROM audit_log a LEFT JOIN users u ON u.id = a.admin_id ORDER BY a.created_at DESC LIMIT 400')->fetchAll();
adminOpen('audit', 'Audit log', 'Who changed what in the admin.');
?>
<div class="card">
  <?php if ($rows): ?>
  <table class="tbl"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Item</th><th>Detail</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="cell-main"><?= date('j M Y H:i', strtotime($r['created_at'])) ?></td><td data-label="Who"><?= e($r['email'] ?: '—') ?></td>
      <td data-label="Action"><span class="tag tag-user"><?= e($r['action']) ?></span></td><td data-label="Item"><?= e($r['target']) ?></td><td data-label="Detail"><span class="muted"><?= e($r['detail']) ?></span></td></tr>
  <?php endforeach; ?>
  </tbody></table>
  <?php else: ?><div class="empty"><i class="fa-solid fa-clock-rotate-left"></i>Nothing logged yet.</div><?php endif; ?>
</div>
<?php adminClose();
