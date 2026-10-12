<?php
declare(strict_types=1);
require __DIR__ . '/_head.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $old = (string)($_POST['old'] ?? '');
    $new = (string)($_POST['new'] ?? '');
    $uid = (int)admin_user()['id'];
    $st = db()->prepare('SELECT password FROM users WHERE id = ?');
    $st->execute([$uid]);
    if (!password_verify($old, (string)$st->fetchColumn())) {
        $error = 'رمز فعلی اشتباه است.';
    } elseif (mb_strlen($new) < 8) {
        $error = 'رمز جدید باید حداقل ۸ نویسه باشد.';
    } else {
        db()->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        flash('رمز عبور تغییر کرد.');
        header('Location: ' . url('admin/index.php'));
        exit;
    }
}
?>
<div class="panel">
    <h1>تغییر رمز عبور</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form class="f" method="post">
        <?= csrf_field() ?>
        <label>رمز فعلی</label><input type="password" name="old" required>
        <label>رمز جدید</label><input type="password" name="new" required>
        <p><button class="btn" type="submit">ذخیره</button></p>
    </form>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
