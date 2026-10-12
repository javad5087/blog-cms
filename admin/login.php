<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = db()->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([trim((string)($_POST['username'] ?? ''))]);
    $u = $st->fetch();
    if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => (int)$u['id'], 'username' => $u['username']];
        header('Location: ' . url('admin/index.php'));
        exit;
    }
    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود | <?= e(site_name()) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<div class="login-box panel">
    <h1>ورود به مدیریت</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form class="f" method="post">
        <?= csrf_field() ?>
        <label>نام کاربری</label>
        <input type="text" name="username" autofocus required>
        <label>رمز عبور</label>
        <input type="password" name="password" required>
        <p><button class="btn" type="submit">ورود</button></p>
    </form>
</div>
</body>
</html>
