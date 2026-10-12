<?php
declare(strict_types=1);
require __DIR__ . '/_head.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    db()->prepare('DELETE FROM posts WHERE id = ?')->execute([(int)$_POST['id']]);
    flash('نوشته حذف شد.');
    header('Location: ' . url('admin/index.php'));
    exit;
}

$rows = db()->query('SELECT p.id, p.title, p.status, p.created_at, c.name AS cat_name
    FROM posts p LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.created_at DESC, p.id DESC')->fetchAll();
?>
<div class="panel">
    <h1>نوشته‌ها (<?= e(fa(count($rows))) ?>)</h1>
    <p><a class="btn" href="<?= e(url('admin/post.php')) ?>">+ نوشتهٔ جدید</a></p>
    <table class="list">
        <tr><th>عنوان</th><th>دسته</th><th>وضعیت</th><th>تاریخ</th><th></th></tr>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['title']) ?></td>
                <td><?= e($r['cat_name'] ?? '—') ?></td>
                <td><?= $r['status'] === 'published' ? 'منتشرشده' : 'پیش‌نویس' ?></td>
                <td><?= e(jalali_date((int)$r['created_at'])) ?></td>
                <td>
                    <a class="btn sm gray" href="<?= e(url('admin/post.php?id=' . (int)$r['id'])) ?>">ویرایش</a>
                    <form class="inline" method="post" onsubmit="return confirm('این نوشته حذف شود؟')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn sm red" type="submit">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
<?php require __DIR__ . '/_foot.php'; ?>
