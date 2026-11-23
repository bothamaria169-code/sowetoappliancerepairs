<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'delete') { $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]); flash('success', 'Review deleted.'); }
    else { $db->prepare('UPDATE reviews SET is_approved = 1 - is_approved WHERE id = ?')->execute([$id]); flash('success', 'Updated.'); }
    audit('review', '#' . $id, (string) ($_POST['action'] ?? ''));
    redirect('admin/reviews.php');
}
$rows = $db->query('SELECT * FROM reviews ORDER BY is_approved, created_at DESC LIMIT 200')->fetchAll();
adminOpen('reviews', 'Reviews', 'Approve only reviews from real customers.', '<a class="btn btn-ghost" target="_blank" href="' . APP_URL . '/reviews">Review page</a>');
?>
<div class="card"><?php if ($rows): ?><table class="tbl"><thead><tr><th>Customer</th><th>Review</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td class="cell-main"><strong><?= e($r['full_name']) ?></strong><br><small class="muted"><?= e($r['area'] ?: '') ?> · <?= e(timeAgo($r['created_at'])) ?></small></td><td data-label="Review"><?= str_repeat('★', (int) $r['stars']) ?> <?= e($r['body']) ?></td><td data-label="Status"><span class="tag <?= $r['is_approved'] ? 'tag-on' : 'tag-off' ?>"><?= $r['is_approved'] ? 'Live' : 'Waiting' ?></span></td><td><div class="row-actions"><form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn-xs"><?= $r['is_approved'] ? 'Hide' : 'Approve' ?></button></form><form method="post" data-confirm="Delete this review?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-xs danger" aria-label="Delete"><i class="fa-solid fa-trash-can"></i></button></form></div></td></tr><?php endforeach; ?></tbody></table><?php else: ?><div class="empty"><i class="fa-solid fa-star"></i>No reviews yet. Send customers to <?= e(APP_URL) ?>/reviews after a job.</div><?php endif; ?></div>
<?php adminClose();
