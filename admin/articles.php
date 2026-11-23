<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/media.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? 'save');
    if ($action === 'write') {
        set_time_limit(600);
        ignore_user_abort(true);
        $r = writeNextArticle((int) $ADMIN['id']);
        audit('article_write', $r['title'] ?? ($r['topic'] ?? ''), $r['error'] ?? '');
        flash($r['ok'] ? 'success' : 'error', $r['ok'] ? "Written: {$r['title']}" : 'Not written: ' . $r['error']);
        redirect($r['ok'] ? 'admin/articles.php?id=' . $r['id'] : 'admin/articles.php');
    }
    if ($action === 'delete') {
        $db->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
        audit('article_delete', '#' . $id);
        flash('success', 'Article deleted.');
        redirect('admin/articles.php');
    }
    if ($action === 'toggle') {
        $s = $db->prepare('SELECT is_published, published_at FROM posts WHERE id = ?');
        $s->execute([$id]);
        if ($p = $s->fetch()) {
            $on = $p['is_published'] ? 0 : 1;
            $db->prepare('UPDATE posts SET is_published = ?, published_at = COALESCE(published_at, NOW()) WHERE id = ?')->execute([$on, $id]);
            if ($on) afterPublish($id);
            flash('success', $on ? 'Published.' : 'Moved to drafts.');
        }
        redirect('admin/articles.php');
    }
    $title = postText('title', 220);
    if ($title === '') { flash('error', 'The title is empty.'); redirect('admin/articles.php?id=' . $id); }
    $slug = slugify(postText('slug', 180) ?: $title);
    $c = $db->prepare('SELECT id FROM posts WHERE slug = ? AND id <> ?');
    $c->execute([$slug, $id]);
    if ($c->fetchColumn()) $slug .= '-' . bin2hex(random_bytes(2));
    $cover = postText('cover', 255);
    if (!empty($_FILES['cover_file']['tmp_name'])) {
        $up = saveUploadedPhoto($_FILES['cover_file'], 'article');
        if ($up['ok']) $cover = $up['path']; else flash('error', $up['error']);
    }
    $faqs = [];
    foreach (preg_split('/\n\s*\n/', trim((string) ($_POST['faqs'] ?? ''))) ?: [] as $b) {
        $l = explode("\n", trim($b), 2);
        if (count($l) === 2 && trim($l[0]) !== '') $faqs[] = ['q' => trim($l[0]), 'a' => trim($l[1])];
    }
    $on = !empty($_POST['is_published']) ? 1 : 0;
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['published_at'] ?? '')) ? $_POST['published_at'] . date(' H:i:s') : date('Y-m-d H:i:s');
    $vals = [$slug, $title, postText('meta_title', 160) ?: null, postText('meta_description', 260) ?: null, postText('excerpt', 600) ?: null, trim((string) ($_POST['body'] ?? '')), json_encode($faqs, JSON_UNESCAPED_UNICODE),
        postText('keyword', 160) ?: null, isset(services()[$_POST['service'] ?? '']) ? $_POST['service'] : null, isset(areas()[$_POST['area'] ?? '']) ? $_POST['area'] : null, $cover ?: null, $on, $date];
    if ($id) $db->prepare('UPDATE posts SET slug=?, title=?, meta_title=?, meta_description=?, excerpt=?, body=?, faqs=?, keyword=?, service=?, area=?, cover=?, is_published=?, published_at=?, updated_at=NOW() WHERE id=?')->execute([...$vals, $id]);
    else { $db->prepare('INSERT INTO posts (slug, title, meta_title, meta_description, excerpt, body, faqs, keyword, service, area, cover, is_published, published_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($vals); $id = (int) $db->lastInsertId(); }
    if ($on) afterPublish($id);
    audit('article_save', $title);
    flash('success', 'Article saved.');
    redirect('admin/articles.php?id=' . $id);
}

if (isset($_GET['id']) || isset($_GET['new'])) {
    $s = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $s->execute([(int) ($_GET['id'] ?? 0)]);
    $p = $s->fetch() ?: [];
    $v = fn(string $k) => e($p[$k] ?? '');
    $faqText = implode("\n\n", array_map(fn($f) => $f['q'] . "\n" . $f['a'], json_decode((string) ($p['faqs'] ?? ''), true) ?: []));
    $covers = array_unique(array_merge(array_column(workPhotos(), 0), ['lineup', 'geyser-pair']));
    adminOpen('articles', $p ? 'Edit article' : 'New article', $p ? e(str_word_count($p['body'])) . ' words · ' . (int) $p['views'] . ' views' : 'Write it yourself.', '<a class="btn btn-ghost" href="articles.php">← All articles</a>' . ($p ? '<a class="btn btn-ghost" target="_blank" href="' . e(postUrl($p)) . '">View</a>' : ''));
    ?>
    <form class="card" method="post" enctype="multipart/form-data"><div class="card__body"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) ($p['id'] ?? 0) ?>">
      <div class="field"><label for="title">Title</label><input id="title" name="title" required value="<?= $v('title') ?>"></div>
      <div class="form-grid">
        <div class="field"><label for="slug">Web address <span class="hint">/blog/…</span></label><input id="slug" name="slug" value="<?= $v('slug') ?>" placeholder="made from the title"></div>
        <div class="field"><label for="keyword">Search phrase</label><input id="keyword" name="keyword" value="<?= $v('keyword') ?>"></div>
        <div class="field"><label for="meta_title">Google title <span class="hint">about 58 characters</span></label><input id="meta_title" name="meta_title" value="<?= $v('meta_title') ?>"></div>
        <div class="field"><label for="published_at">Date</label><input id="published_at" type="date" name="published_at" value="<?= e(substr((string) ($p['published_at'] ?? date('Y-m-d')), 0, 10) ?: date('Y-m-d')) ?>"></div>
        <div class="field full"><label for="meta_description">Google description <span class="hint">130 to 155 characters</span></label><textarea id="meta_description" name="meta_description" style="min-height:64px"><?= $v('meta_description') ?></textarea></div>
        <div class="field full"><label for="excerpt">Summary</label><textarea id="excerpt" name="excerpt" style="min-height:64px"><?= $v('excerpt') ?></textarea></div>
        <div class="field"><label for="service">Appliance</label><select id="service" name="service"><option value="">General</option><?php foreach (services() as $k => $x): ?><option value="<?= $k ?>" <?= ($p['service'] ?? '') === $k ? 'selected' : '' ?>><?= e($x['short']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="area">Area</label><select id="area" name="area"><option value="">None</option><?php foreach (areas() as $k => $x): ?><option value="<?= $k ?>" <?= ($p['area'] ?? '') === $k ? 'selected' : '' ?>><?= e($x[0]) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="cover">Cover photo</label><select id="cover" name="cover"><?php $cv = (string) ($p['cover'] ?? ''); if ($cv !== '' && !in_array($cv, $covers, true)): ?><option value="<?= e($cv) ?>" selected>Current upload</option><?php endif; foreach ($covers as $x): ?><option <?= $cv === $x ? 'selected' : '' ?>><?= e($x) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="cover_file">or upload one</label><input id="cover_file" type="file" name="cover_file" accept="image/*"></div>
      </div>
      <div class="field"><label for="body">Article <span class="hint">## heading · ### small heading · - bullet · 1. step · **bold**</span></label><textarea id="body" name="body" style="min-height:460px"><?= $v('body') ?></textarea></div>
      <div class="field"><label for="faqs">Questions <span class="hint">question on one line, answer below it, blank line between</span></label><textarea id="faqs" name="faqs" style="min-height:200px"><?= e($faqText) ?></textarea></div>
      <label class="check"><input type="checkbox" name="is_published" value="1" <?= !empty($p['is_published']) ? 'checked' : '' ?>> <span>Published</span></label>
      <div class="form-foot"><button class="btn btn-primary">Save article</button></div></div></form>
    <?php
    adminClose();
    exit;
}

$rows = $db->query('SELECT id, slug, title, service, source, is_published, views, published_at, created_at FROM posts ORDER BY COALESCE(published_at, created_at) DESC LIMIT 300')->fetchAll();
$next = $db->query("SELECT title FROM article_topics WHERE status = 'queued' AND tries < 3 ORDER BY sort_order, id LIMIT 1")->fetchColumn();
adminOpen('articles', 'Articles', 'The writer works on ' . e(str_replace(',', ', ', get_setting('article_days', 'Tue,Fri'))) . ' from ' . (int) get_setting('article_hour', '7') . ':00. Change it in <a href="settings.php?tab=articles">Settings</a>.',
    '<a class="btn btn-ghost" href="?new=1"><i class="fa-solid fa-plus"></i>Write my own</a><form method="post" data-busy>' . csrfField() . '<input type="hidden" name="action" value="write"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-wand-magic-sparkles"></i>Write the next article</button></form>');
?>
<?php if ($next): ?><p class="hint" style="margin-bottom:1rem">Next in the queue: <strong><?= e($next) ?></strong>. Writing takes up to a minute.</p><?php endif; ?>
<div class="card"><?php if ($rows): ?><table class="tbl"><thead><tr><th>Title</th><th>Appliance</th><th>Written by</th><th class="num">Views</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($rows as $r): ?><tr><td class="cell-main"><strong><a href="?id=<?= (int) $r['id'] ?>"><?= e($r['title']) ?></a></strong><br><small class="muted"><?= date('j M Y', strtotime($r['published_at'] ?: $r['created_at'])) ?></small></td><td data-label="Appliance"><?= e(services()[$r['service'] ?? '']['short'] ?? 'General') ?></td><td data-label="Written by"><?= $r['source'] === 'ai' ? 'AI' : 'You' ?></td><td class="num" data-label="Views"><?= (int) $r['views'] ?></td><td data-label="Status"><span class="tag <?= $r['is_published'] ? 'tag-on' : 'tag-off' ?>"><?= $r['is_published'] ? 'Live' : 'Draft' ?></span></td><td><div class="row-actions"><a class="btn-xs" target="_blank" href="<?= APP_URL ?>/blog/<?= e($r['slug']) ?>">View</a><form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn-xs"><?= $r['is_published'] ? 'Unpublish' : 'Publish' ?></button></form><form method="post" data-confirm="Delete this article for good?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-xs danger" aria-label="Delete"><i class="fa-solid fa-trash-can"></i></button></form></div></td></tr><?php endforeach; ?></tbody></table><?php else: ?><div class="empty"><i class="fa-solid fa-newspaper"></i>No articles yet. Press Write the next article.</div><?php endif; ?></div>
<?php adminClose();
