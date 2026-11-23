<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$db = getDB();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = postText('full_name', 120);
    $body = postText('body', 1200);
    $stars = (int) ($_POST['stars'] ?? 0);
    if (!csrfValid()) $errors[] = 'Your session expired. Please send it again.';
    if (!empty($_POST['website'])) redirect('reviews');
    if ($name === '' || mb_strlen($body) < 10 || $stars < 1 || $stars > 5) $errors[] = 'Add your name, a star rating and a few words.';
    if (!$errors && rateLimited('review', 3, 1440)) $errors[] = 'You have already sent a review today. Thank you!';
    if (!$errors) {
        $db->prepare('INSERT INTO reviews (full_name, area, service, stars, body) VALUES (?,?,?,?,?)')->execute([$name, areas()[$_POST['area'] ?? ''][0] ?? null, isset(services()[$_POST['service'] ?? '']) ? $_POST['service'] : null, $stars, $body]);
        sendMail(get_setting('notify_email', ADMIN_EMAIL), "New review from {$name} ({$stars} stars)", emailLayout('New review to approve', '<p>' . e($body) . '</p>', 'Open in admin', APP_URL . '/admin/reviews.php'), [], null, 'review');
        flash('success', 'Thank you. Your review will show once we have checked that it is from a customer.');
        redirect('reviews');
    }
}
$rows = $db->query('SELECT * FROM reviews WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 60')->fetchAll();
siteOpen('Customer Reviews | Soweto Appliance Repairs', ['path' => 'reviews', 'crumbs' => [['Reviews', 'reviews']], 'description' => 'What customers say about Soweto Appliance Repairs, and a place to leave your own review.']);
?>
<section class="top top--plain"><div class="wrap top__in"><div><?= crumbs([['Reviews', 'reviews']]) ?><h1>Reviews</h1><p class="sub">Only real customers, published as they were written.</p><?php if ($u = biz('biz_review_url')): ?><div class="row"><a class="btn btn-flame" href="<?= e($u) ?>" target="_blank" rel="noopener">Review us on Google</a></div><?php endif; ?></div></div></section>
<section class="sec"><div class="wrap"><?= flashHtml() ?>
  <?php if ($rows): ?><div class="quotes" style="margin-bottom:4rem"><?php foreach ($rows as $r): ?><blockquote><div class="stars" aria-label="<?= (int) $r['stars'] ?> out of 5"><?= str_repeat('★', (int) $r['stars']) ?></div><p><?= e($r['body']) ?></p><footer><?= e($r['full_name']) ?><?= $r['area'] ? ', ' . e($r['area']) : '' ?></footer></blockquote><?php endforeach; ?></div><?php endif; ?>
  <div class="two"><div><h2>Did we fix yours?</h2><p class="sub">Tell the next person what happened.</p></div>
  <form class="form" method="post"><?= csrfField() ?><div class="hp" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <?php if ($errors): ?><div class="alert alert-error" role="alert"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
    <div class="form__2"><div class="field"><label for="full_name">Your name</label><input id="full_name" name="full_name" required value="<?= e($_POST['full_name'] ?? '') ?>"></div>
    <div class="field"><label for="stars">Rating</label><select id="stars" name="stars" required><option value="">Choose…</option><?php foreach ([5 => 'Excellent', 4 => 'Good', 3 => 'Okay', 2 => 'Poor', 1 => 'Bad'] as $n => $l): ?><option value="<?= $n ?>"><?= $n ?> stars, <?= lcfirst($l) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="service">Appliance</label><select id="service" name="service"><option value="">Choose…</option><?php foreach (services() as $k => $s): ?><option value="<?= $k ?>"><?= e($s['short']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label for="area">Area</label><select id="area" name="area"><option value="">Choose…</option><?php foreach (areas() as $k => $a): ?><option value="<?= $k ?>"><?= e($a[0]) ?></option><?php endforeach; ?></select></div></div>
    <div class="field"><label for="body">Your review</label><textarea id="body" name="body" required><?= e($_POST['body'] ?? '') ?></textarea></div>
    <button class="btn btn-flame" type="submit">Send review</button></form></div>
</div></section>
<?php siteClose();
