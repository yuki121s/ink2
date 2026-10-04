<?php
require __DIR__ . '/../db_connect.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $s = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $s->execute([trim($_POST['username'] ?? '')]);
    $a = $s->fetch();
    if ($a && password_verify($_POST['password'] ?? '', $a['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $a['id'];
        header('Location: products.php'); exit;
    }
    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
}
page_head('เข้าสู่ระบบหลังบ้าน', '../');
?>
<form class="panel login" method="post">
  <h1>เข้าสู่ระบบหลังบ้าน</h1>
  <?php if ($error): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <div class="field"><label>ชื่อผู้ใช้</label><input name="username" required autofocus></div>
  <div class="field"><label>รหัสผ่าน</label><input type="password" name="password" required></div>
  <button class="btn primary block">เข้าสู่ระบบ</button>
</form>
<?php page_foot();
