<?php
declare(strict_types=1);
require __DIR__ . '/_head.php';

$pdo = db();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $name = trim((string)($_POST['name'] ?? ''));
    try {
        if ($action === 'add' && $name !== '') {
            $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]);
            flash('دسته افزوده شد.');
        } elseif ($action === 'rename' && $name !== '') {
            $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute([$name, (int)$_POST['id']]);
            flash('نام دسته تغییر کرد.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([(int)$_POST['id']]);
            flash('دسته حذف شد؛ نوشته‌های آن بدون دسته ماندند.');
        }
        header('Location: ' . url('admin/categories.php'));
        exit;
    } catch (PDOException $ex) {
        $error = 'این نام قبلاً استفاده شده است.';
    }
}
$rows = $pdo->query('SELECT c.id, c.name, COUNT(p.id) AS n FROM categories c
    LEFT JOIN posts p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name')->fetchAll();
?>
<div class="panel">
    <h1>دسته‌ها</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form class="f row" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div><label>دستهٔ جدید</label><input type="text" name="name" required></div>
        <button class="btn" type="submit">افزودن</button>
    </form>
    <table class="list" style="margin-top:20px">
        <tr><th>نام</th><th>تعداد نوشته</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <form class="f row" method="post">
                        <?= csrf_field() ?><input type="hidden" name="action" value="rename">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <div><input type="text" name="name" value="<?= e($r['name']) ?>" required></div>
                        <button class="btn sm gray" type="submit">ذخیره</button>
                    </form>
                </td>
                <td><?= e(fa($r['n'])) ?></td>
                <td>
                    <form class="inline" method="post" onsubmit="return confirm('این دسته حذف شود؟')">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn sm red" type="submit">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
