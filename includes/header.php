<?php
/** @var string $pageTitle */
/** @var array $crumbs  [[label, url|null], ...] */
$pageTitle = $pageTitle ?? site_name();
$crumbs = $crumbs ?? [];
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<header class="topbar">
    <button class="category-toggle" type="button" aria-expanded="false" aria-controls="category-menu" onclick="const m=document.getElementById('category-menu');m.hidden=!m.hidden;this.setAttribute('aria-expanded',String(!m.hidden))" aria-label="نمایش دسته‌ها"><span></span><span></span><span></span></button>
    <div class="brand-wrap"><a class="brand" href="<?= e(url('index.php')) ?>"><?= e(site_name()) ?></a><?php if (!empty(site_settings()['site_description'])): ?><div class="site-description"><?= e(site_settings()['site_description']) ?></div><?php endif; ?></div>
    <form class="search" action="<?= e(url('index.php')) ?>" method="get" role="search">
        <button type="submit" aria-label="جستجو">
            <svg width="24" height="24" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M15.5 14h-.8l-.3-.3A6.5 6.5 0 1 0 14 15.5l.3.3v.8l5 5 1.5-1.5-5-5zm-6 0a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9z"/></svg>
        </button>
        <input type="search" name="q" placeholder="جستجو…" value="<?= e($_GET['q'] ?? '') ?>">
    </form>
</header>
<?php
$siteSettings = site_settings();
$bannerImage = $GLOBALS['pageBannerImage'] ?? ($siteSettings['banner'] ?? 'uploads/site-banner.jpg');
?>
<?php $cats = db()->query('SELECT c.id,c.name,COUNT(p.id) AS post_count FROM categories c LEFT JOIN posts p ON p.category_id=c.id AND p.status="published" GROUP BY c.id,c.name ORDER BY c.name')->fetchAll(); ?>
<nav class="category-menu" id="category-menu" hidden aria-label="دسته‌بندی نوشته‌ها"><?php foreach ($cats as $cat): ?><a href="<?= e(url('index.php?cat='.(int)$cat['id'])) ?>"><span><?= e($cat['name']) ?></span><b><?= e(fa($cat['post_count'])) ?></b></a><?php endforeach; ?></nav>
<div class="banner" aria-hidden="true" style="background-image:linear-gradient(0deg,rgba(0,0,0,.08),rgba(0,0,0,.08)),url('<?= e(url($bannerImage)) ?>')"></div>
<main class="card">
    <?php if ($crumbs): ?>
        <nav class="crumbs" aria-label="مسیر">
            <?php foreach ($crumbs as $i => [$label, $href]): ?>
                <?= $i ? '<span class="sep">&lt;</span>' : '' ?>
                <?= $href ? '<a href="' . e($href) . '">' . e($label) . '</a>' : '<span>' . e($label) . '</span>' ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
