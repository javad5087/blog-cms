<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$file = ROOT . '/data/settings.json';
$settings = site_settings();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $settings['site_name'] = trim((string)($_POST['site_name'] ?? '')) ?: 'وبلاگ متریال';
    $settings['site_description'] = trim((string)($_POST['site_description'] ?? ''));
    $dir = dirname($file);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
        $error = 'پوشهٔ data ایجاد نشد. لطفاً دسترسی نوشتن هاست را بررسی کنید.';
    } else {
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $saved = is_string($json) ? @file_put_contents($file, $json . PHP_EOL, LOCK_EX) : false;
        if ($saved === false) {
            $error = 'ذخیره مشخصات سایت انجام نشد. دسترسی نوشتن پوشهٔ data را در هاست بررسی کنید.';
        } else {
            flash('مشخصات سایت با موفقیت ذخیره شد.');
            header('Location: ' . url('admin/site.php'));
            exit;
        }
    }
}
require __DIR__ . '/_head.php';
?>
<h1>مشخصات سایت</h1>
<?php if ($error): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-card">
    <?= csrf_field() ?>
    <label for="site_name">نام سایت</label>
    <input id="site_name" name="site_name" required maxlength="160" value="<?= e($settings['site_name'] ?? 'وبلاگ متریال') ?>">
    <label for="site_description">توضیحات کوتاه سایت</label>
    <textarea id="site_description" name="site_description" rows="4" maxlength="500"><?= e($settings['site_description'] ?? '') ?></textarea>
    <button class="btn" type="submit">ذخیره مشخصات</button>
</form>
<?php require __DIR__ . '/_foot.php'; ?>
