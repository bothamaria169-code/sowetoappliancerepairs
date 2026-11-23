<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'add':
            $title = postText('title', 200);
            $kw = mb_strtolower(postText('keyword', 150)) ?: mb_strtolower($title);
            if ($title === '') { flash('error', 'Type a title.'); break; }
            $s = $db->prepare('INSERT IGNORE INTO article_topics (title, keyword, service, area, sort_order) VALUES (?,?,?,?,?)');
            $s->execute([$title, mb_substr($kw, 0, 160), isset(services()[$_POST['service'] ?? '']) ? $_POST['service'] : null, isset(areas()[$_POST['area'] ?? '']) ? $_POST['area'] : null, 0]);
            flash($s->rowCount() ? 'success' : 'error', $s->rowCount() ? 'Added to the front of the queue.' : 'That search phrase is already in the list.');
            break;
        case 'suggest':
            $n = refillTopics(PHP_INT_MAX, 12);
            flash($n ? 'success' : 'error', $n ? "{$n} new topics added." : 'The AI did not return new topics. Check Settings → AI.');
            break;
        case 'write':
            set_time_limit(600);
            ignore_user_abort(true);
            $r = writeNextArticle((int) $ADMIN['id'], $id);
            flash($r['ok'] ? 'success' : 'error', $r['ok'] ? "Written: {$r['title']}" : 'Not written: ' . $r['error']);
            if ($r['ok']) redirect('admin/articles.php?id=' . $r['id']);
            break;
        case 'retry':
            $db->prepare('UPDATE article_topics SET tries = 0, last_error = NULL WHERE id = ?')->execute([$id]);
            break;
        case 'delete':
            $db->prepare('DELETE FROM article_topics WHERE id = ?')->execute([$id]);
            flash('success', 'Topic removed.');
            break;
    }
    redirect('admin/topics.php');
}
$rows = $db->query("SELECT * FROM article_topics WHERE status = 'queued' ORDER BY (tries >= 3), sort_order, id")->fetchAll();
$done = (int) $db->query("SELECT COUNT(*) FROM article_topics WHERE status = 'done'")->fetchColumn();
adminOpen('topics', 'Topic queue', count($rows) . ' waiting, ' . $done . ' written. When fewer than 6 are left, the AI suggests more.', '<form method="post" data-busy>' . csrfField() . '<input type="hidden" name="action" value="suggest"><button class="btn btn-ghost" type="submit"><i class="fa-solid fa-lightbulb"></i>Suggest 12 topics</button></form>');
?>
<form class="card" method="post" style="margin-bottom:1.2rem"><div class="card__head"><h2>Add a topic</h2></div><div class="card__body"><?= csrfField() ?><input type="hidden" name="action" value="add"><div class="form-grid">
  <div class="field"><label for="title">Title or question</label><input id="title" name="title" required placeholder="Why does my freezer smell?"></div><div class="field"><label for="keyword">What people type in Google</label><input id="keyword" name="keyword" placeholder="freezer smells bad"></div>
  <div class="field"><label for="service">Appliance</label><select id="service" name="service"><option value="">General</option><?php foreach (services() as $k => $x): ?><option value="<?= $k ?>"><?= e($x['short']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="area">Area</label><select id="area" name="area"><option value="">None</option><?php foreach (areas() as $k => $x): ?><option value="<?= $k ?>"><?= e($x[0]) ?></option><?php endforeach; ?></select></div></div><button class="btn btn-primary">Add topic</button></div></form>
<div class="card"><table class="tbl"><thead><tr><th>Topic</th><th>Search phrase</th><th>Appliance</th><th>Area</th><th></th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td class="cell-main"><strong><?= e($r['title']) ?></strong><?php if ($r['last_error']): ?><br><small style="color:var(--coral)"><?= e($r['last_error']) ?> (<?= (int) $r['tries'] ?> tries)</small><?php endif; ?></td><td data-label="Search phrase"><span class="muted"><?= e($r['keyword']) ?></span></td><td data-label="Appliance"><?= e(services()[$r['service'] ?? '']['short'] ?? 'General') ?></td><td data-label="Area"><?= e(areas()[$r['area'] ?? ''][0] ?? '—') ?></td><td><div class="row-actions">
  <?php foreach (array_filter(['write' => 'Write now', 'retry' => $r['tries'] ? 'Retry later' : null]) as $act => $label): ?><form method="post" data-busy><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="<?= $act ?>"><button class="btn-xs" type="submit"><?= $label ?></button></form><?php endforeach; ?>
  <form method="post" data-confirm="Remove this topic?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-xs danger" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button></form></div></td></tr><?php endforeach; ?></tbody></table></div>
<?php adminClose();
