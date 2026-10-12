<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Tehran');

define('ROOT', dirname(__DIR__));
function site_settings(): array { $defaults = ['banner'=>'uploads/site-banner.jpg','site_name'=>'وبلاگ متریال','site_description'=>'']; $f = ROOT . '/data/settings.json'; if (!is_file($f)) return $defaults; $raw = @file_get_contents($f); $v = is_string($raw) ? json_decode($raw, true) : null; return is_array($v) ? array_merge($defaults, $v) : $defaults; }
function site_name(): string { return trim((string)(site_settings()['site_name'] ?? '')) ?: 'وبلاگ متریال'; }
const PER_PAGE = 5;

/* ---------- مسیرها ---------- */
function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $dir = preg_replace('#/admin$#', '', $dir);
    return rtrim($dir, '/');
}
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}
function abs_url(string $path = ''): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return ($https ? 'https' : 'http') . '://' . $host . url($path);
}

/* ---------- خروجی امن و ارقام فارسی ---------- */
function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function fa($s): string
{
    return strtr((string)$s, [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ]);
}
function en_digits(string $s): string
{
    return strtr($s, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ]);
}

/* ---------- تاریخ جلالی ---------- */
function gregorian_to_jalali(int $gy, int $gm, int $gd): array
{
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
        + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}
function jalali_date(int $ts, bool $withTime = false): string
{
    static $months = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
    [$jy, $jm, $jd] = gregorian_to_jalali((int)date('Y', $ts), (int)date('n', $ts), (int)date('j', $ts));
    $out = $jd . ' ' . $months[$jm] . ' ' . $jy;
    if ($withTime) {
        $out .= ' - ' . date('H:i', $ts);
    }
    return fa($out);
}

/* ---------- ابزارهای نوشته ---------- */
function reading_time(string $text): int
{
    $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    return max(1, (int)ceil(count($words) / 180));
}
function make_excerpt(string $content, int $len = 260): string
{
    $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($content)));
    if (mb_strlen($plain) <= $len) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $len), " ،.,") . '…';
}
function render_content(string $content): string
{
    // Lightweight Markdown renderer; escape source before applying formatting.
    $content = trim(str_replace(["\r\n", "\r"], "\n", $content));
    if ($content === '') return '';
    $blocks = preg_split('/\n\s*\n/u', $content);
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') continue;
        if (preg_match('/^```(?:[a-z0-9_-]+)?\n([\s\S]*?)\n```$/iu', $block, $m)) {
            $html .= '<pre><code>' . e($m[1]) . '</code></pre>';
            continue;
        }
        $lines = explode("\n", $block);
        if (preg_match('/^\s*>\s?/u', $lines[0])) {
            $html .= '<blockquote>' . nl2br(e(preg_replace('/^\s*>\s?/mu', '', $block))) . '</blockquote>';
            continue;
        }
        $isUl = preg_match('/^\s*[-*+]\s+/u', $lines[0]);
        $isOl = preg_match('/^\s*\d+[.)]\s+/u', $lines[0]);
        if ($isUl || $isOl) {
            $tag = $isUl ? 'ul' : 'ol'; $html .= '<' . $tag . '>';
            foreach ($lines as $line) {
                $line = preg_replace('/^\s*(?:[-*+]\s+|\d+[.)]\s+)/u', '', $line);
                $html .= '<li>' . e($line) . '</li>';
            }
            $html .= '</' . $tag . '>';
            continue;
        }
        $safe = e($block);
        // Markdown images: ![alt|width](url), width is optional and capped.
        $safe = preg_replace_callback('/!\[([^\]|]*)(?:\|(\d{1,4}))?\]\((https?:\/\/[^\s)]+|\/[a-zA-Z0-9_\.\/-]+)\)/u', function($m) {
            $w = isset($m[2]) && $m[2] !== '' ? min(1200, max(40, (int)$m[2])) : 640;
            return '<img loading="lazy" style="width:' . $w . 'px;max-width:100%;height:auto" alt="' . $m[1] . '" src="' . $m[3] . '">';
        }, $safe);
        $safe = preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/u', '<em>$1</em>', $safe);
        $safe = preg_replace('/~~(.+?)~~/us', '<del>$1</del>', $safe);
        $safe = preg_replace('/__(.+?)__/us', '<u>$1</u>', $safe);
        $safe = preg_replace('/`([^`\n]+)`/u', '<code>$1</code>', $safe);
        $safe = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u', fn($m) => '<a href="' . $m[2] . '" rel="nofollow noopener" target="_blank">' . $m[1] . '</a>', $safe);
        $html .= '<p>' . nl2br($safe) . '</p>';
    }
    return $html;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_check(): void
{
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('درخواست نامعتبر است (CSRF).');
    }
}

/* ---------- پایگاه داده ---------- */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $dir = ROOT . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $pdo = new PDO('sqlite:' . $dir . '/blog.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    init_schema($pdo);
    return $pdo;
}

function init_schema(PDO $pdo): void
{
    $pdo->exec('
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    );
    CREATE TABLE IF NOT EXISTS tags (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE
    );
    CREATE TABLE IF NOT EXISTS posts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        excerpt TEXT NOT NULL DEFAULT "",
        content TEXT NOT NULL,
        category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
        status TEXT NOT NULL DEFAULT "published",
        created_at INTEGER NOT NULL,
        updated_at INTEGER NOT NULL
    );
    CREATE TABLE IF NOT EXISTS post_tags (
        post_id INTEGER NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
        tag_id INTEGER NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
        PRIMARY KEY (post_id, tag_id)
    );
    CREATE INDEX IF NOT EXISTS idx_posts_cat ON posts(category_id, created_at);
    ');

    // Upgrade existing installations without losing their data.
    $cols = $pdo->query('PRAGMA table_info(posts)')->fetchAll();
    if (!in_array('featured_image', array_column($cols, 'name'), true)) {
        $pdo->exec("ALTER TABLE posts ADD COLUMN featured_image TEXT NOT NULL DEFAULT ''");
    }

    if ((int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0) {
        $st = $pdo->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
        $st->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
    }
    if ((int)$pdo->query('SELECT COUNT(*) FROM posts')->fetchColumn() === 0) {
        seed_data($pdo);
    }
}

function set_post_tags(PDO $pdo, int $postId, string $csv): void
{
    $pdo->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);
    $names = preg_split('/[,،]+/u', $csv, -1, PREG_SPLIT_NO_EMPTY);
    $seen = [];
    foreach ($names as $n) {
        $n = trim($n);
        if ($n === '' || isset($seen[mb_strtolower($n)])) {
            continue;
        }
        $seen[mb_strtolower($n)] = true;
        $pdo->prepare('INSERT OR IGNORE INTO tags (name) VALUES (?)')->execute([$n]);
        $st = $pdo->prepare('SELECT id FROM tags WHERE name = ?');
        $st->execute([$n]);
        $tid = (int)$st->fetchColumn();
        $pdo->prepare('INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)')->execute([$postId, $tid]);
    }
}

function seed_data(PDO $pdo): void
{
    $cats = ['فناوری', 'طراحی', 'برنامه‌نویسی'];
    foreach ($cats as $c) {
        $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$c]);
    }
    $body = "این یک متن نمونه برای نمایش چیدمان نوشته‌هاست. می‌توانید آن را از پنل مدیریت ویرایش یا حذف کنید.\n\n"
        . "طراحی خوب یعنی خوانایی، فاصله‌گذاری مناسب و توجه به جزئیات کوچکی که تجربهٔ کاربر را می‌سازند. "
        . "در این بخش چند جملهٔ دیگر آمده تا زمان خواندن و خلاصهٔ نوشته به‌درستی محاسبه شود. "
        . str_repeat('متن آزمایشی برای پر کردن محتوا و محاسبهٔ زمان خواندن نوشته. ', 12)
        . "\n\nپاراگراف پایانی نوشته اینجاست و جمع‌بندی موضوع را ارائه می‌دهد.";

    $posts = [
        ['نصب و راه‌اندازی قالب متریال', 1, 'قالب,متریال,راه‌اندازی'],
        ['آشنایی با اصول طراحی راست‌به‌چپ', 2, 'طراحی,راست‌به‌چپ'],
        ['شروع کار با PHP و SQLite', 3, 'php,sqlite,پایگاه‌داده'],
        ['تایپوگرافی فارسی در وب', 2, 'فونت,طراحی'],
        ['مقدمه‌ای بر معماری سایت‌های سبک', 1, 'معماری,وب'],
        ['امنیت پایه در برنامه‌های PHP', 3, 'php,امنیت'],
        ['انتخاب پالت رنگ برای وبلاگ', 2, 'رنگ,طراحی'],
        ['چرا SQLite برای پروژه‌های کوچک کافی است', 3, 'sqlite,پایگاه‌داده'],
        ['آینده‌ی وب و ابزارهای نوین', 1, 'وب,فناوری'],
        ['صفحه‌بندی بهینه در وب‌سایت‌ها', 3, 'php,صفحه‌بندی'],
        ['اصول دسترس‌پذیری در طراحی', 2, 'دسترس‌پذیری,طراحی'],
        ['هوش مصنوعی در زندگی روزمره', 1, 'هوش‌مصنوعی,فناوری'],
    ];
    $now = time();
    $i = 0;
    foreach ($posts as [$title, $cat, $tags]) {
        $ts = $now - ($i * 86400 * 2) - $i * 3600;
        $pdo->prepare('INSERT INTO posts (title, excerpt, content, category_id, status, created_at, updated_at)
                       VALUES (?, ?, ?, ?, "published", ?, ?)')
            ->execute([$title, make_excerpt($body, 240), $body, $cat, $ts, $ts]);
        set_post_tags($pdo, (int)$pdo->lastInsertId(), $tags);
        $i++;
    }
}

/* ---------- کوئری‌های نمایش ---------- */
function post_tags(int $postId): array
{
    $st = db()->prepare('SELECT t.id, t.name FROM tags t JOIN post_tags pt ON pt.tag_id = t.id
                         WHERE pt.post_id = ? ORDER BY t.name');
    $st->execute([$postId]);
    return $st->fetchAll();
}

function fetch_posts(array $f, int $page): array
{
    $where = ['p.status = "published"'];
    $args = [];
    if (!empty($f['cat'])) {
        $where[] = 'p.category_id = ?';
        $args[] = (int)$f['cat'];
    }
    if (!empty($f['tag'])) {
        $where[] = 'p.id IN (SELECT post_id FROM post_tags WHERE tag_id = ?)';
        $args[] = (int)$f['tag'];
    }
    if (!empty($f['q'])) {
        $where[] = '(p.title LIKE ? OR p.content LIKE ?)';
        $like = '%' . $f['q'] . '%';
        $args[] = $like;
        $args[] = $like;
    }
    $w = implode(' AND ', $where);
    $st = db()->prepare("SELECT COUNT(*) FROM posts p WHERE $w");
    $st->execute($args);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / PER_PAGE));
    $page = min(max(1, $page), $pages);

    $st = db()->prepare("SELECT p.*, c.name AS cat_name FROM posts p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $w ORDER BY p.created_at DESC, p.id DESC LIMIT " . PER_PAGE . ' OFFSET ' . (($page - 1) * PER_PAGE));
    $st->execute($args);
    return ['rows' => $st->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
}

function related_posts(array $post, int $limit = 3): array
{
    if (empty($post['category_id'])) {
        return [];
    }
    $st = db()->prepare('SELECT id, title FROM posts WHERE status = "published" AND category_id = ? AND id != ?
                         ORDER BY created_at DESC LIMIT ' . $limit);
    $st->execute([$post['category_id'], $post['id']]);
    return $st->fetchAll();
}

/* ---------- اجزای نمایشی ---------- */
function post_meta(array $p): string
{
    $cat = $p['cat_name']
        ? '<a href="' . e(url('index.php?cat=' . (int)$p['category_id'])) . '">' . e($p['cat_name']) . '</a>'
        : 'بدون دسته';
    $shareId = 'shareDialog' . (int)$p['id'];
    return '<div class="meta">'
        . '<span>دسته: ' . $cat . '</span>'
        . '<span>زمان خواندن: ' . fa(reading_time($p['content'])) . ' دقیقه</span>'
        . '<span>' . e(jalali_date((int)$p['created_at'])) . '</span>'
        . share_buttons((string)$p['title'], abs_url('post.php?id=' . (int)$p['id']), $shareId)
        . '</div>';
}

function share_buttons(string $title, string $link, string $dialogId = 'shareDialog'): string
{
    $u = rawurlencode($link); $t = rawurlencode($title);
    $items = [
        ['fb', 'f', 'فیسبوک', "https://www.facebook.com/sharer/sharer.php?u=$u"],
        ['tg', '➤', 'تلگرام', "https://t.me/share/url?url=$u&text=$t"],
        ['wa', 'W', 'واتساپ', "https://wa.me/?text=$t%20$u"],
        ['in', 'in', 'لینکدین', "https://www.linkedin.com/sharing/share-offsite/?url=$u"],
    ];
    $h = '<button type="button" class="share-trigger" aria-haspopup="dialog" onclick="document.getElementById(&quot;' . e($dialogId) . '&quot;).showModal()" aria-label="اشتراک‌گذاری"><svg viewBox="0 0 24 24" width="19" height="19" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.7 10.7 6.6-4.4M8.7 13.3l6.6 4.4"/></svg><span>اشتراک‌گذاری</span></button>';
    $h .= '<dialog id="' . e($dialogId) . '" class="share-dialog"><form method="dialog" class="dialog-close"><button aria-label="بستن">×</button></form><h3>اشتراک‌گذاری نوشته</h3><div class="share">';
    foreach ($items as [$cls, $label, $tip, $href]) {
        $h .= '<a class="sh ' . $cls . '" data-tip="' . e($tip) . '" aria-label="' . e($tip) . '" href="' . e($href) . '" target="_blank" rel="noopener noreferrer">' . e($label) . '</a>';
    }
    $h .= '</div><button type="button" class="copy-link" data-link="' . e($link) . '" onclick="navigator.clipboard.writeText(this.dataset.link).then(()=>this.textContent=&quot;پیوند کپی شد&quot;)">کپی پیوند</button></dialog>';
    return $h;
}

function paginate(int $page, int $pages, string $baseQuery): string
{
    if ($pages <= 1) {
        return '';
    }
    $link = fn(int $n) => e(url('index.php?' . ($baseQuery !== '' ? $baseQuery . '&' : '') . 'page=' . $n));
    $h = '<nav class="pager" aria-label="صفحه‌بندی">';
    $h .= $page > 1 ? '<a class="pg nav" href="' . $link($page - 1) . '">‹ قبلی</a>' : '<span class="pg nav off">‹ قبلی</span>';
    $shown = [];
    for ($i = 1; $i <= $pages; $i++) {
        if ($i === 1 || $i === $pages || abs($i - $page) <= 1) {
            $shown[] = $i;
        }
    }
    $prev = 0;
    foreach ($shown as $i) {
        if ($i - $prev > 1) {
            $h .= '<span class="pg dots">…</span>';
        }
        $h .= $i === $page
            ? '<span class="pg cur">' . fa($i) . '</span>'
            : '<a class="pg" href="' . $link($i) . '">' . fa($i) . '</a>';
        $prev = $i;
    }
    $h .= $page < $pages ? '<a class="pg nav" href="' . $link($page + 1) . '">بعدی ›</a>' : '<span class="pg nav off">بعدی ›</span>';
    return $h . '</nav>';
}

/* ---------- احراز هویت مدیر ---------- */
function admin_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}
function require_admin(): void
{
    if (!admin_user()) {
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}
function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}
