<?php
declare(strict_types=1);

require_once __DIR__ . '/_shell.php';
require_once BASE_DIR . '/includes/media.php';
$db = getDB();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    switch ($_POST['action'] ?? '') {
        case 'upload':
            $n = 0;
            $errs = [];
            foreach (array_keys((array) ($_FILES['photos']['tmp_name'] ?? [])) as $i) {
                if (!$_FILES['photos']['tmp_name'][$i]) continue;
                $up = saveUploadedPhoto(['tmp_name' => $_FILES['photos']['tmp_name'][$i], 'size' => $_FILES['photos']['size'][$i], 'error' => $_FILES['photos']['error'][$i]], 'work');
                if (!$up['ok']) { $errs[] = $up['error']; continue; }
                $ik = ImageKit::ready() ? ImageKit::upload($up['file'], basename($up['file'])) : ['ok' => false, 'url' => '', 'id' => '', 'error' => ''];
                if (!$ik['ok'] && $ik['error']) $errs[] = 'ImageKit: ' . $ik['error'] . ' (the photo is served from this site instead)';
                $db->prepare('INSERT INTO media (title, service, path, ik_url, ik_file_id, width, height) VALUES (?,?,?,?,?,?,?)')->execute([postText('title', 200) ?: null, isset(services()[$_POST['service'] ?? '']) ? $_POST['service'] : null, $up['path'], $ik['url'] ?: null, $ik['id'] ?: null, $up['w'], $up['h']]);
                $n++;
            }
            if ($n) flash('success', "{$n} photo(s) added to Our work.");
            foreach (array_unique($errs) as $x) flash('error', $x);
            break;
        case 'toggle': $db->prepare('UPDATE media SET show_in_gallery = 1 - show_in_gallery WHERE id = ?')->execute([$id]); break;
        case 'delete':
            $s = $db->prepare('SELECT * FROM media WHERE id = ?');
            $s->execute([$id]);
            if ($m = $s->fetch()) {
                @unlink(MEDIA_DIR . basename($m['path']));
                if ($m['ik_file_id']) ImageKit::delete($m['ik_file_id']);
                $db->prepare('DELETE FROM media WHERE id = ?')->execute([$id]);
                audit('photo_delete', $m['path']);
                flash('success', 'Photo deleted.');
            }
            break;
    }
    redirect('admin/media.php');
}
$rows = $db->query('SELECT * FROM media ORDER BY id DESC LIMIT 200')->fetchAll();
adminOpen('media', 'Photos', 'Photos of new jobs. They show first on <a href="' . APP_URL . '/our-work" target="_blank">Our work</a>.');
?>
<form class="card" method="post" enctype="multipart/form-data" data-busy style="margin-bottom:1.2rem"><div class="card__head"><h2>Add photos</h2><span class="tag <?= ImageKit::ready() ? 'tag-on' : 'tag-off' ?>">ImageKit <?= ImageKit::ready() ? 'on' : 'off' ?></span></div><div class="card__body"><?= csrfField() ?><input type="hidden" name="action" value="upload"><div class="form-grid">
  <div class="field full"><label for="photos">Photos <span class="hint">JPG, PNG or WebP, up to 8 MB each. Location data is removed.</span></label><input id="photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
  <div class="field"><label for="title">What it shows</label><input id="title" name="title" placeholder="Fridge compressor replaced in Dobsonville"></div><div class="field"><label for="service">Appliance</label><select id="service" name="service"><?php foreach (services() as $k => $x): ?><option value="<?= $k ?>"><?= e($x['short']) ?></option><?php endforeach; ?></select></div></div><button class="btn btn-primary" type="submit">Upload</button></div></form>
<div class="card"><?php if ($rows): ?><div class="mediagrid"><?php foreach ($rows as $m): ?><figure><img src="<?= e($m['ik_url'] ? ImageKit::sized($m['ik_url'], 340) : APP_URL . '/' . $m['path']) ?>" alt="" loading="lazy"><figcaption><?= e($m['title'] ?: 'No title') ?><div class="row-actions"><form method="post"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="toggle"><button class="btn-xs"><?= $m['show_in_gallery'] ? 'Hide' : 'Show' ?></button></form><form method="post" data-confirm="Delete this photo?"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn-xs danger" aria-label="Delete"><i class="fa-solid fa-trash-can"></i></button></form></div></figcaption></figure><?php endforeach; ?></div><?php else: ?><div class="empty"><i class="fa-solid fa-images"></i>The site already shows <?= count(workPhotos()) ?> photos. New ones you upload appear here.</div><?php endif; ?></div>
<?php adminClose();
