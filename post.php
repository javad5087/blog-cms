<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare('SELECT p.*, c.name AS cat_name FROM posts p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.id = ? AND p.status = "published"');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    http_response_code(404);
    $pageTitle = 'یافت نشد';
    $crumbs = [[site_name(), url('index.php')], ['یافت نشد', null]];
    require __DIR__ . '/includes/header.php';
    echo '<p class="empty">این نوشته وجود ندارد.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$GLOBALS['pageBannerImage'] = !empty($p['featured_image']) ? $p['featured_image'] : 'uploads/site-banner.jpg';
$tags = post_tags((int)$p['id']);
$related = related_posts($p);
$pageTitle = $p['title'] . ' | ' . site_name();
$crumbs = [[site_name(), url('index.php')]];
if ($p['category_id']) {
    $crumbs[] = [$p['cat_name'], url('index.php?cat=' . (int)$p['category_id'])];
}
$crumbs[] = [$p['title'], null];
require __DIR__ . '/includes/header.php';
?>
<article class="post single">
    <h1><?= e($p['title']) ?></h1>
    <?= post_meta($p) ?>
    <div class="body"><?= render_content($p['content']) ?></div>

    <?php if ($related): ?><ol class="related-list related-after-body"><?php foreach ($related as $rp): ?><li><a href="<?= e(url('post.php?id='.(int)$rp['id'])) ?>"><?= e($rp['title']) ?></a></li><?php endforeach; ?></ol><?php endif; ?>
    <?php
    $pf = ROOT . '/data/profile.json'; $author = is_file($pf) ? json_decode((string)file_get_contents($pf), true) : [];
    if (!is_array($author)) $author = [];
    $postCount = (int)db()->query('SELECT COUNT(*) FROM posts WHERE status="published"')->fetchColumn();
    $authorName = trim(($author['first_name'] ?? '') . ' ' . ($author['last_name'] ?? ''));
    $socials = ['instagram'=>'اینستاگرام','telegram'=>'تلگرام','facebook'=>'فیسبوک','linkedin'=>'لینکدین','website'=>'وب‌سایت'];
    $hasSocial = false; foreach ($socials as $key=>$label) if (!empty($author[$key])) $hasSocial = true;
    ?>
    <?php if ($authorName || !empty($author['bio']) || $hasSocial): ?><aside class="author-card">
      <?php if (!empty($author['avatar'])): ?><img class="author-avatar" src="<?= e(url($author['avatar'])) ?>" alt="<?= e($authorName ?: 'نویسنده') ?>"><?php endif; ?>
      <div class="author-details"><strong><?= e($authorName ?: 'مدیر سایت') ?></strong>
      <?php if (!empty($author['bio'])): ?><p><?= nl2br(e($author['bio'])) ?></p><?php endif; ?>
      <span class="author-count">تعداد نوشته‌ها: <?= e(fa($postCount)) ?></span>
      <?php if ($hasSocial): ?><div class="author-socials"><?php foreach ($socials as $key=>$label): if (!empty($author[$key])): ?><a href="<?= e($author[$key]) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($label) ?>" title="<?= e($label) ?>"><?= e($label) ?></a><?php endif; endforeach; ?></div><?php endif; ?>
      </div></aside><?php endif; ?>

    <?php if ($tags): ?>
        <div class="tags">
            <?php foreach ($tags as $t): ?>
                <a href="<?= e(url('index.php?tag=' . (int)$t['id'])) ?>">#<?= e($t['name']) ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</article>
<?php require __DIR__ . '/includes/footer.php'; ?>
