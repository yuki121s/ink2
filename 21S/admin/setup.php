<?php
// สร้างบัญชีผู้ดูแลระบบครั้งแรก — ใช้เสร็จแล้ว "ลบไฟล์นี้ทิ้ง" ทันที
require __DIR__ . '/../db_connect.php';
$msg = '';
$has = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn() > 0;
if ($has) {
    $msg = 'มีผู้ดูแลระบบอยู่แล้ว กรุณาลบไฟล์ setup.php ออกจากเซิร์ฟเวอร์';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? ''); $p = $_POST['password'] ?? '';
    if ($u === '' || strlen($p) < 8) { $msg = 'ต้องกรอกชื่อผู้ใช้ และรหัสผ่านอย่างน้อย 8 ตัวอักษร'; }
    else {
        $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)")
            ->execute([$u, password_hash($p, PASSWORD_DEFAULT)]);
        $msg = 'สร้างบัญชีสำเร็จ — ลบไฟล์ setup.php แล้วไปที่หน้า login';
        $has = true;
    }
}
page_head('ตั้งค่าผู้ดูแลระบบ', '../');
?>
<div class="panel login">
  <h1>สร้างผู้ดูแลระบบ</h1>
  <?php if ($msg): ?><div class="alert <?= $has ? 'ok' : 'err' ?>"><?= e($msg) ?></div><?php endif; ?>
  <?php if (!$has): ?>
  <form method="post"><?= csrf_field() ?>
    <div class="field"><label>ชื่อผู้ใช้</label><input name="username" required></div>
    <div class="field"><label>รหัสผ่าน (อย่างน้อย 8 ตัว)</label><input type="password" name="password" required></div>
    <button class="btn primary block">สร้างบัญชี</button>
  </form>
  <?php else: ?><a class="btn primary" href="login.php">ไปหน้าเข้าสู่ระบบ</a><?php endif; ?>
</div>
<?php page_foot();
