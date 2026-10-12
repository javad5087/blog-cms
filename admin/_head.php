<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$cur = basename($_SERVER['SCRIPT_NAME']);
$nav = [
    'index.php' => ['نوشته‌ها','▤'],
    'post.php' => ['نوشتهٔ جدید','＋'],
    'categories.php' => ['دسته‌ها','▦'],
    'tags.php' => ['برچسب‌ها','⌑'],
    'profile.php' => ['پروفایل مدیر','◉'],
    'banner.php' => ['بنر سایت','▧'],
    'site.php' => ['مشخصات سایت','⚙'],
];
$msg = flash();
$profileFile = ROOT . '/data/profile.json';
$profile = is_file($profileFile) ? json_decode((string)file_get_contents($profileFile), true) : [];
if (!is_array($profile)) $profile = [];
$avatar = !empty($profile['avatar']) ? $profile['avatar'] : '';
$fullName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')) ?: 'مدیر سایت';
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مدیریت | <?= e(site_name()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head><body>
<div class="admin-bar"><span>پنل مدیریت</span><span class="sp"></span><a href="<?= e(url('index.php')) ?>" target="_blank">مشاهدهٔ سایت</a><a class="admin-user" href="<?= e(url('admin/profile.php')) ?>"><?php if ($avatar): ?><img class="admin-avatar" src="<?= e(url($avatar)) ?>" alt="تصویر مدیر"><?php else: ?><span class="admin-avatar" aria-hidden="true"></span><?php endif; ?><?= e($fullName) ?></a><a href="<?= e(url('admin/logout.php')) ?>">خروج</a></div>
<div class="admin-shell"><aside class="admin-drawer"><div class="drawer-title">ناوبری</div><?php foreach ($nav as $file => [$label,$icon]): ?><a class="<?= $cur === $file ? 'on' : '' ?>" href="<?= e(url('admin/' . $file)) ?>"><span aria-hidden="true"><?= e($icon) ?></span><?= e($label) ?></a><?php endforeach; ?><div class="drawer-bottom"><a class="<?= $cur === 'password.php' ? 'on' : '' ?>" href="<?= e(url('admin/password.php')) ?>"><span aria-hidden="true">⚙</span> تغییر رمز عبور</a><a href="<?= e(url('admin/logout.php')) ?>">↪ خروج از حساب</a></div></aside><main class="admin-main"><div class="admin-wrap">
<?php if ($msg): ?><div class="notice"><?= e($msg) ?></div><?php endif; ?>
