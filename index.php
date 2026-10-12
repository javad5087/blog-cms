<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$f = [
    'cat' => (int)($_GET['cat'] ?? 0),
    'tag' => (int)($_GET['tag'] ?? 0),
    'q' => trim((string)($_GET['q'] ?? '')),
];
$res = fetch_posts($f, $page);

$crumbs = [[site_name(), url('index.php')]];
$qs = [];
if ($f['cat']) {
    $st = db()->prepare('SELECT name FROM categories WHERE id = ?');
    $st->execute([$f['cat']]);
    $crumbs[] = ['دسته: ' . ($st->fetchColumn() ?: 'نامشخص'), null];
    $qs[] = 'cat=' . $f['cat'];
} elseif ($f['tag']) {
    $st = db()->prepare('SELECT name FROM tags WHERE id = ?');
    $st->execute([$f['tag']]);
    $crumbs[] = ['برچسب: ' . ($st->fetchColumn() ?: 'نامشخص'), null];
    $qs[] = 'tag=' . $f['tag'];
} elseif ($f['q'] !== '') {
    $crumbs[] = ['جستجو: ' . $f['q'], null];
    $qs[] = 'q=' . rawurlencode($f['q']);
} else {
    $crumbs[] = ['آخرین نوشته‌ها', null];
}

$pageTitle = site_name();
require __DIR__ . '/includes/header.php';
?>
<?php if (!$res['rows']): ?>
    <p class="empty">نوشته‌ای پیدا نشد.</p>
<?php endif; ?>

<?php foreach ($res['rows'] as $p):
    $link = url('post.php?id=' . (int)$p['id']);
    $related = related_posts($p);
    ?>
    <article class="post post-preview">
        <div class="post-preview-text">
            <h2><a href="<?= e($link) ?>"><?= e($p['title']) ?></a></h2>
            <?= post_meta($p) ?>
            <p class="excerpt"><?= e($p['excerpt'] !== '' ? $p['excerpt'] : make_excerpt($p['content'])) ?></p>
            <?php if ($related): ?><ol class="related-list"><?php foreach ($related as $rp): ?><li><a href="<?= e(url('post.php?id='.(int)$rp['id'])) ?>"><?= e($rp['title']) ?></a></li><?php endforeach; ?></ol><?php endif; ?>
        </div>
    </article>
<?php endforeach; ?>

<?= paginate($res['page'], $res['pages'], implode('&', $qs)) ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
