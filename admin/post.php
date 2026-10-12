<?php
declare(strict_types=1);
require __DIR__ . '/_head.php';

$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$post = ['id'=>0,'title'=>'','excerpt'=>'','content'=>'','category_id'=>0,'status'=>'published','featured_image'=>''];
if ($id) {
    $st = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $st->execute([$id]);
    $post = $st->fetch() ?: $post;
}
$tagsCsv = $post['id'] ? implode('، ', array_column(post_tags((int)$post['id']), 'name')) : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim((string)($_POST['title'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $excerpt = trim((string)($_POST['excerpt'] ?? ''));
    $cat = (int)($_POST['category_id'] ?? 0) ?: null;
    $status = ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'published';
    $tagsCsv = (string)($_POST['tags'] ?? '');
    $featuredImage = (string)($post['featured_image'] ?? '');
    if (!empty($_POST['remove_featured_image'])) $featuredImage = '';

    if (!empty($_FILES['featured_image']['name'])) {
        $file = $_FILES['featured_image'];
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
            $error = 'بارگذاری تصویر ناموفق بود؛ حداکثر حجم مجاز ۵ مگابایت است.';
        } else {
            $info = @getimagesize($file['tmp_name']);
            $mime = $info['mime'] ?? '';
            $exts = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
            if (!$info || !isset($exts[$mime])) {
                $error = 'فرمت تصویر باید JPG، PNG، GIF یا WebP باشد.';
            } else {
                $uploadDir = dirname(__DIR__) . '/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = bin2hex(random_bytes(16)) . '.' . $exts[$mime];
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
                    $featuredImage = 'uploads/' . $filename;
                } else {
                    $error = 'ذخیره تصویر روی سرور ممکن نشد.';
                }
            }
        }
    }
    if ($excerpt === '') $excerpt = make_excerpt($content, 240);
    $post = array_merge($post, ['title'=>$title,'content'=>$content,'excerpt'=>$excerpt,'category_id'=>$cat,'status'=>$status,'featured_image'=>$featuredImage]);

    if ($error === '' && ($title === '' || $content === '')) $error = 'عنوان و متن نوشته الزامی است.';
    if ($error === '') {
        $now = time();
        if ($post['id']) {
            $pdo->prepare('UPDATE posts SET title=?, excerpt=?, content=?, category_id=?, status=?, featured_image=?, updated_at=? WHERE id=?')
                ->execute([$title,$excerpt,$content,$cat,$status,$featuredImage,$now,$post['id']]);
            $pid = (int)$post['id'];
        } else {
            $pdo->prepare('INSERT INTO posts (title, excerpt, content, category_id, status, featured_image, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$title,$excerpt,$content,$cat,$status,$featuredImage,$now,$now]);
            $pid = (int)$pdo->lastInsertId();
        }
        set_post_tags($pdo, $pid, $tagsCsv);
        flash('نوشته ذخیره شد.');
        header('Location: ' . url('admin/index.php'));
        exit;
    }
}
$cats = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
?>
<div class="panel">
    <h1><?= $post['id'] ? 'ویرایش نوشته' : 'نوشتهٔ جدید' ?></h1>
    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form class="f post-editor-form" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="editor-top-row">
            <div><label>وضعیت</label><select name="status"><option value="published" <?= $post['status']==='published'?'selected':'' ?>>منتشر شده</option><option value="draft" <?= $post['status']==='draft'?'selected':'' ?>>پیش‌نویس</option></select></div>
            <div><label>برچسب‌ها</label><input type="text" name="tags" value="<?= e($tagsCsv) ?>" placeholder="با ویرگول جدا کنید"></div>
            <div><label>دسته</label><select name="category_id"><option value="0">— بدون دسته —</option><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)$post['category_id']===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        </div>

        <label>عنوان</label>
        <input type="text" name="title" value="<?= e($post['title']) ?>" required>

        <label>تصویر پست در صفحهٔ اول <span class="hint">(JPG، PNG، GIF یا WebP؛ حداکثر ۵ مگابایت)</span></label>
        <div class="featured-upload">
            <input type="file" name="featured_image" id="featuredImageInput" accept="image/jpeg,image/png,image/gif,image/webp">
            <?php if (!empty($post['featured_image'])): ?>
                <img class="featured-preview" id="featuredPreview" src="<?= e(url($post['featured_image'])) ?>" alt="پیش‌نمایش تصویر پست">
                <label class="remove-image"><input type="checkbox" name="remove_featured_image" value="1"> حذف تصویر فعلی</label>
            <?php else: ?><img class="featured-preview" id="featuredPreview" alt="پیش‌نمایش تصویر" hidden><?php endif; ?>
        </div>

        <label>خلاصه <span class="hint">(اگر خالی بماند از ابتدای متن ساخته می‌شود)</span></label>
        <textarea class="short" name="excerpt"><?= e($post['excerpt']) ?></textarea>

        <label>متن نوشته <span class="hint">(پاراگراف‌ها را با یک خط خالی جدا کنید؛ ابزارهای زیر Markdown درج می‌کنند)</span></label>
        <div class="markdown-editor">
            <div class="markdown-toolbar" role="toolbar" aria-label="ابزارهای قالب‌بندی متن">
                <button type="button" data-md="bold" title="بولد"><strong>B</strong></button>
                <button type="button" data-md="italic" title="ایتالیک"><em>I</em></button>
                <button type="button" data-md="underline" title="زیرخط"><u>U</u></button>
                <button type="button" data-md="strike" title="روخط"><s>S</s></button>
                <span class="toolbar-sep"></span>
                <button type="button" data-md="ul" title="فهرست غیرعددی">• ≡</button>
                <button type="button" data-md="ol" title="فهرست عددی">۱. ≡</button>
                <button type="button" data-md="quote" title="نقل قول">❞</button>
                <button type="button" data-md="code" title="کد">&lt;/&gt;</button>
                <button type="button" data-md="image" title="درج تصویر با اندازه دلخواه">▧ تصویر</button>
            </div>
            <textarea name="content" id="markdownContent" required><?= e($post['content']) ?></textarea>
            <div class="editor-hint">برای تصویر داخل متن، نشانی تصویر و عرض دلخواه وارد کنید. نمونه: <code>![توضیح تصویر|320](/uploads/image.jpg)</code></div>
        </div>
        <p><button class="btn icon-action" type="submit" aria-label="ذخیره" title="ذخیره"><span aria-hidden="true">▣</span> ذخیره</button> <a class="btn gray" href="<?= e(url('admin/index.php')) ?>">انصراف</a></p>
    </form>
</div>
<script>
(() => {
 const area = document.getElementById('markdownContent');
 document.querySelectorAll('[data-md]').forEach(btn => btn.addEventListener('click', () => {
   const type = btn.dataset.md, start = area.selectionStart, end = area.selectionEnd;
   const selected = area.value.slice(start, end), before = area.value.slice(0, start), after = area.value.slice(end);
   let left = '', right = '', replacement = selected || 'متن';
   if (type === 'bold') { left='**'; right='**'; }
   if (type === 'italic') { left='*'; right='*'; }
   if (type === 'underline') { left='__'; right='__'; }
   if (type === 'strike') { left='~~'; right='~~'; }
   if (type === 'code') { left='`'; right='`'; }
   if (type === 'quote') { replacement = selected.split('\n').map(x=>'> '+x).join('\n'); }
   if (type === 'ul' || type === 'ol') {
     replacement = (selected || 'مورد اول\nمورد دوم').split('\n').map((x,i)=> type==='ul' ? '- '+x : (i+1)+'. '+x).join('\n');
   }
   if (type === 'image') {
     const url = prompt('نشانی تصویر (URL یا مسیر داخل سایت):', '/uploads/');
     if (!url) return;
     const width = prompt('عرض تصویر به پیکسل (مثلاً ۳۲۰):', '320');
     replacement = '![' + (prompt('توضیح تصویر:', 'تصویر نوشته') || 'تصویر') + '|' + (parseInt(width,10)||320) + '](' + url.trim() + ')';
   } else if (left) replacement = left + replacement + right;
   area.value = before + replacement + after;
   area.focus();
   const pos = before.length + replacement.length;
   area.setSelectionRange(pos, pos);
   area.dispatchEvent(new Event('input', {bubbles:true}));
 }));
 const input = document.getElementById('featuredImageInput'), preview = document.getElementById('featuredPreview');
 input?.addEventListener('change', () => {
   if (input.files && input.files[0]) { preview.src = URL.createObjectURL(input.files[0]); preview.hidden = false; }
 });
})();
</script>
<?php require __DIR__ . '/_foot.php'; ?>
