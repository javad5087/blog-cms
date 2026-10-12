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
            $pdo->prepare('INSERT INTO tags (name) VALUES (?)')->execute([$name]);
            flash('برچسب افزوده شد.');
        } elseif ($action === 'rename' && $name !== '') {
            $pdo->prepare('UPDATE tags SET name = ? WHERE id = ?')->execute([$name, (int)$_POST['id']]);
            flash('نام برچسب تغییر کرد.');
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([(int)$_POST['id']]);
            flash('برچسب حذف شد.');
        }
        header('Location: ' . url('admin/tags.php'));
        exit;
    } catch (PDOException $ex) {
        $error = 'این نام قبلاً استفاده شده است.';
    }
}
$rows = $pdo->query('SELECT t.id, t.name, COUNT(pt.post_id) AS n FROM tags t
    LEFT JOIN post_tags pt ON pt.tag_id = t.id GROUP BY t.id ORDER BY t.name')->fetchAll();
?>
<div class="panel">
    <h1>برچسب‌ها</h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form class="f row" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div><label>برچسب جدید</label><input type="text" name="name" required></div>
        <button class="btn" type="submit">افزودن</button>
    </form>
    <table class="list" style="margin-top:20px">
        <tr><th>نام</th><th>عملیات</th><th>تعداد نوشته</th></tr>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>
                    <form class="f row" method="post">
                        <?= csrf_field() ?><input type="hidden" name="action" value="rename">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <div><input type="text" name="name" value="<?= e($r['name']) ?>" required></div>
                        <button class="btn sm gray icon-action" type="submit" aria-label="ذخیره" title="ذخیره">▣</button>
                    </form>
                </td>
                <td>
                    <form class="inline" method="post" onsubmit="return confirm('این برچسب حذف شود؟')">
                        <?= csrf_field() ?><input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn sm red icon-action" type="submit" aria-label="حذف" title="حذف">⌫</button>
                    </form>
                </td>
                <td><span class="tag-count"><?= e(fa($r['n'])) ?></span></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
