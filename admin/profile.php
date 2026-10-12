<?php
declare(strict_types=1);
require __DIR__ . '/_head.php';
$file = ROOT . '/data/profile.json';
$profile = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
if (!is_array($profile)) $profile = [];
$profile = array_merge(['first_name'=>'','last_name'=>'','bio'=>'','avatar'=>'','facebook'=>'','instagram'=>'','telegram'=>'','linkedin'=>'','website'=>''], $profile);
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $new = [];
    foreach (['first_name','last_name','bio','facebook','instagram','telegram','linkedin','website'] as $key) {
        $new[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $new['avatar'] = $profile['avatar'];
    if (!empty($_POST['remove_avatar'])) $new['avatar'] = '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up = $_FILES['avatar'];
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
                $name = 'author-' . bin2hex(random_bytes(10)) . '.' . $exts[$mime];
                if (!move_uploaded_file($up['tmp_name'], $dir . '/' . $name)) $error = 'ذخیره تصویر انجام نشد.';
                else $new['avatar'] = 'uploads/' . $name;
            }
        }
    }
    if ($error === '') {
        file_put_contents($file, json_encode($new, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
        flash('پروفایل مدیر ذخیره شد.');
        header('Location: ' . url('admin/profile.php'));
        exit;
    }
    $profile = array_merge($profile, $new);
}
$count = (int)db()->query("SELECT COUNT(*) FROM posts WHERE status='published'")->fetchColumn();
?>
<div class="panel"><h1>پروفایل نویسنده / مدیر</h1>
<p class="hint">اطلاعات این بخش را هر زمان خواستید می‌توانید ویرایش کنید. تعداد نوشته‌های منتشرشده به‌صورت خودکار محاسبه می‌شود.</p>
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<form class="f" method="post" enctype="multipart/form-data">
<?= csrf_field() ?>
<div class="profile-grid">
<div><label>نام</label><input type="text" name="first_name" value="<?= e($profile['first_name']) ?>"></div>
<div><label>نام خانوادگی</label><input type="text" name="last_name" value="<?= e($profile['last_name']) ?>"></div>
<div class="wide"><label>تصویر پروفایل</label><?php if ($profile['avatar']): ?><img class="profile-avatar" src="<?= e(url($profile['avatar'])) ?>" alt="تصویر مدیر"><?php endif; ?><input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp"><p class="hint">حداکثر ۵ مگابایت؛ JPG، PNG، GIF یا WebP.</p><?php if ($profile['avatar']): ?><label><input type="checkbox" name="remove_avatar" value="1"> حذف تصویر فعلی</label><?php endif; ?></div>
<div class="wide"><label>دربارهٔ خود</label><textarea class="short" name="bio"><?= e($profile['bio']) ?></textarea></div>
<div><label>فیسبوک</label><input type="text" name="facebook" value="<?= e($profile['facebook']) ?>" placeholder="https://..."></div>
<div><label>اینستاگرام</label><input type="text" name="instagram" value="<?= e($profile['instagram']) ?>" placeholder="https://..."></div>
<div><label>تلگرام</label><input type="text" name="telegram" value="<?= e($profile['telegram']) ?>" placeholder="https://..."></div>
<div><label>لینکدین</label><input type="text" name="linkedin" value="<?= e($profile['linkedin']) ?>" placeholder="https://..."></div>
<div class="wide"><label>وب‌سایت شخصی</label><input type="text" name="website" value="<?= e($profile['website']) ?>" placeholder="https://..."></div>
<div><label>تعداد نوشته‌های منتشرشده</label><input type="text" value="<?= e(fa($count)) ?>" readonly></div>
</div><p><button class="btn" type="submit">ذخیره تغییرات</button></p></form></div>
<?php require __DIR__ . '/_foot.php'; ?>
