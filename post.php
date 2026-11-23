<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/boot.php';

$db = getDB();
$st = $db->prepare('SELECT * FROM posts WHERE slug = ?' . (isAdmin() ? '' : ' AND is_published = 1 AND published_at <= NOW()'));
$st->execute([(string) ($_GET['s'] ?? '')]);
$p = $st->fetch();
if (!$p) notFound();
if (!isAdmin()) $db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$p['id']]);

$svc = services()[$p['service'] ?? ''] ?? null;
$area = areas()[$p['area'] ?? ''] ?? null;
$faqs = json_decode((string) $p['faqs'], true) ?: [];
$wa = 'Hi, I read "' . $p['title'] . '" on your website. I need help with my ' . ($svc ? lcfirst($svc['short']) : 'appliance') . '. I am in ' . ($area[0] ?? '');
$trail = [['Advice', 'blog'], [excerpt($p['title'], 50), 'blog/' . $p['slug']]];
$pub = date('c', strtotime($p['published_at'] ?: $p['created_at']));
$mod = date('c', strtotime($p['updated_at'] ?: ($p['published_at'] ?: $p['created_at'])));
$schema = [['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $p['title'], 'description' => $p['meta_description'], 'image' => [coverUrl($p['cover'])], 'datePublished' => $pub, 'dateModified' => $mod,
    'author' => ['@type' => 'Organization', 'name' => APP_NAME, 'url' => APP_URL . '/about'], 'publisher' => ['@id' => APP_URL . '/#business'], 'mainEntityOfPage' => postUrl($p), 'inLanguage' => 'en-ZA']];
if ($faqs) $schema[] = faqSchema($faqs);
preg_match_all('/^## (.+)$/m', $p['body'], $m);

siteOpen($p['meta_title'] ?: $p['title'], ['path' => 'blog/' . $p['slug'], 'crumbs' => $trail, 'description' => $p['meta_description'] ?: excerpt($p['excerpt'], 155), 'image' => coverUrl($p['cover']), 'og_type' => 'article', 'schema' => $schema, 'noindex' => !$p['is_published']]);
?>
<article>
<header class="top"><div class="wrap top__in">
  <div><?= crumbs($trail) ?><h1 style="font-size:clamp(2.1rem,4.6vw,3.6rem)"><?= e($p['title']) ?></h1><p class="sub"><?= e($p['excerpt']) ?></p><div class="byline"><span><?= e(APP_NAME) ?></span><time datetime="<?= $pub ?>"><?= date('j F Y', strtotime($pub)) ?></time><span><?= readMinutes($p['body']) ?> min read</span></div></div>
  <div class="top__img"><img src="<?= e(coverUrl($p['cover'])) ?>" alt="<?= e($svc ? $svc['name'] : 'Appliance repair') ?>" width="800" height="1000" fetchpriority="high"></div>
</div></header>
<section class="sec"><div class="wrap split">
  <div class="prose">
    <?php if (count($m[1]) > 3): ?><nav class="toc" aria-label="In this article"><strong>In this article</strong><?php foreach ($m[1] as $h): ?><a href="#<?= e(slugify(trim($h))) ?>"><?= e(trim($h)) ?></a><?php endforeach; ?></nav><?php endif; ?>
    <?= articleHtml($p['body'], $area ? [] : []) ?>
    <?php if ($faqs): ?><h2>Questions people ask</h2><?= faqHtml($faqs) ?><?php endif; ?>
    <p style="color:var(--text-3);font-size:.92rem">General guidance only. Electricity and gas can kill: if you are unsure, switch the appliance off and call a technician.</p>
  </div>
  <aside>
    <div class="box box--ink"><h3>Need it fixed?</h3><p>We come to you. R<?= e(callout()) ?> call-out, free if we repair.</p><?= waButton($wa, 'WhatsApp us') ?><?= callButton('btn btn-glass') ?></div>
    <?php if ($svc): ?><div class="box"><h3><?= e($svc['short']) ?> help</h3><a href="<?= APP_URL . '/' . $p['service'] ?>"><?= e($svc['name']) ?></a><?php foreach (array_slice(faultsFor($p['service']), 0, 5, true) as $fs => $f): ?><a href="<?= APP_URL ?>/faults/<?= $fs ?>"><?= e($f['s']) ?></a><?php endforeach; ?></div><?php endif; ?>
    <?php if ($area): ?><div class="box"><h3>In your area</h3><a href="<?= APP_URL ?>/areas/<?= e($p['area']) ?>">Appliance repairs in <?= e($area[0]) ?></a></div><?php endif; ?>
  </aside>
</div></section>
</article>
<?php if ($more = latestPosts(3, $p['service'], (int) $p['id'])): ?><section class="sec sec--tint"><div class="wrap"><div class="sec__head"><h2>Keep reading</h2></div><div class="posts"><?php foreach ($more as $x) echo postCard($x); ?></div></div></section><?php endif; ?>
<?= ctaBand('Let us fix it', $wa) ?>
<?php siteClose();
