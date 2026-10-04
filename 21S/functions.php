<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

const CATEGORIES = ['ยางรถยนต์', 'ผ้าเบรก', 'โช้คอัพ', 'น้ำมันเครื่อง'];
const STATUS_LABELS = [
    'pending'   => 'รอชำระเงิน',
    'paid'      => 'ชำระเงินแล้ว',
    'shipping'  => 'กำลังจัดส่ง',
    'completed' => 'ส่งสำเร็จ',
    'cancelled' => 'ยกเลิก',
];

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v): string { return number_format((float)$v, 2); }

// ---------- CSRF ----------
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('คำขอไม่ถูกต้อง (CSRF) กรุณากลับไปโหลดหน้าใหม่');
    }
}

// ---------- Admin ----------
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}

// ---------- Cart ----------
function cart_count(): int { return array_sum($_SESSION['cart'] ?? []); }

// ---------- Upload รูปภาพ ----------
// คืนค่า: ชื่อไฟล์ใหม่ | null (ไม่ได้เลือกไฟล์) | โยน Exception ถ้าไฟล์ไม่ผ่าน
function upload_image(array $file, string $dir): ?string {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return null; }
    if ($file['error'] !== UPLOAD_ERR_OK) { throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ'); }
    if ($file['size'] > 2 * 1024 * 1024) { throw new RuntimeException('ไฟล์ต้องมีขนาดไม่เกิน 2 MB'); }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) { throw new RuntimeException('รองรับเฉพาะไฟล์ JPG, PNG, WEBP'); }

    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], rtrim($dir, '/') . '/' . $name)) {
        throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ ตรวจสอบสิทธิ์โฟลเดอร์ uploads');
    }
    return $name;
}

// ---------- Layout ----------
function page_head(string $title, string $base = '', bool $admin = false): void { ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> | AutoPart21</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/style.css">
</head>
<body class="<?= $admin ? 'is-admin' : '' ?>">
<header class="topbar">
  <a class="brand" href="<?= $base ?><?= $admin ? 'admin/products.php' : 'shop.php' ?>">AutoPart21<small><?= $admin ? 'หลังบ้าน' : 'ยางและอะไหล่ยนต์' ?></small></a>
  <nav>
  <?php if ($admin): ?>
    <a href="<?= $base ?>admin/products.php">สินค้า</a>
    <a href="<?= $base ?>admin/orders.php">คำสั่งซื้อ</a>
    <a href="<?= $base ?>shop.php">ดูหน้าร้าน</a>
    <a href="<?= $base ?>admin/logout.php">ออกจากระบบ</a>
  <?php else: ?>
    <a href="<?= $base ?>shop.php">สินค้า</a>
    <a class="cart-pill" href="<?= $base ?>cart.php">ตะกร้า <b><?= cart_count() ?></b></a>
    <a href="<?= $base ?>admin/login.php">เข้าระบบหลังบ้าน</a>
  <?php endif; ?>
  </nav>
</header>
<main class="wrap">
<?php }

function page_foot(): void { ?>
</main>
<footer class="foot">AutoPart21 · โปรเจกต์ร้านยางและอะไหล่ยนต์</footer>
</body>
</html>
<?php }
