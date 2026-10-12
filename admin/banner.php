<?php
declare(strict_types=1);
require __DIR__ . '/_head.php';
$file = ROOT . '/data/settings.json';
$settings = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
if (!is_array($settings)) $settings = [];
$settings = array_merge(['banner'=>'uploads/site-banner.jpg'], $settings);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up = $_FILES['banner'];
        if ($up['error'] !== UPLOAD_ERR_OK || $up['size'] > 5 * 1024 * 1024) {
            $error = 'آپلود تصویر ناموفق است یا حجم آن بیشتر از ۵ مگابایت است.';
        } else {
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($up['tmp_name']);
            $exts = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
            if (!isset($exts[$mime]) || @getimagesize($up['tmp_name']) === false) {
                $error = 'فرمت مجاز تصویر JPG، PNG، GIF یا WebP است.';
            } else {
                $dir = ROOT . '/uploads';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $name = 'site-banner-' . bin2hex(random_bytes(8)) . '.' . $exts[$mime];
                if (!move_uploaded_file($up['tmp_name'], $dir . '/' . $name)) $error = 'ذخیرهٔ تصویر انجام نشد.';
                else $settings['banner'] = 'uploads/' . $name;
            }
        }
    }
    if ($error === '') {
        file_put_contents($file, json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        flash('بنر سایت ذخیره شد.');
        header('Location: ' . url('admin/banner.php'));
        exit;
    }
}
?>
<div class="panel"><h1>بنر سایت</h1><p class="hint">تصویر زیر در بالای سایت نمایش داده می‌شود. اگر برای نوشته‌ای تصویر شاخص تعیین شود، در صفحهٔ همان نوشته از تصویر شاخص استفاده خواهد شد.</p>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form class="f" method="post" enctype="multipart/form-data"><?= csrf_field() ?>
<label>تصویر فعلی بنر</label><img class="banner-preview" src="<?= e(url($settings['banner'])) ?>" alt="پیش‌نمایش بنر">
<label for="banner">انتخاب تصویر جدید</label><input id="banner" type="file" name="banner" accept="image/jpeg,image/png,image/gif,image/webp" required><p class="hint">حداکثر ۵ مگابایت؛ JPG، PNG، GIF یا WebP. تصویر بنر پیش‌فرض هم‌اکنون تنظیم شده است.</p>
<button class="btn" type="submit"><span aria-hidden="true">▣</span> ذخیرهٔ بنر</button></form></div>
<?php require __DIR__ . '/_foot.php'; ?>
