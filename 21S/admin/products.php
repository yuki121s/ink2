<?php
require __DIR__ . '/../db_connect.php';
require_admin();

// ---- DELETE (POST + CSRF) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $s = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $s->execute([$id]);
    $img = $s->fetchColumn();
    $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
    if ($img && is_file(__DIR__ . '/../uploads/' . $img)) { unlink(__DIR__ . '/../uploads/' . $img); }
    $_SESSION['flash'] = ['ok', 'ลบสินค้าเรียบร้อยแล้ว'];
    header('Location: products.php'); exit;
}

// ---- READ ----
$q = trim($_GET['q'] ?? '');
$s = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR category LIKE ? ORDER BY id DESC");
$s->execute(["%$q%", "%$q%"]);
$rows = $s->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
page_head('จัดการสินค้า', '../', true);
?>
<div class="toolbar">
  <h1 style="margin:0">จัดการสินค้า</h1>
  <form class="search" method="get" style="margin:0">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อหรือหมวดหมู่">
    <button class="btn small">ค้นหา</button>
  </form>
  <a class="btn primary" href="product_form.php">เพิ่มสินค้าใหม่</a>
</div>
<?php if ($flash): ?><div class="alert <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="scroll">
<table>
  <tr><th>รหัส</th><th>รูป</th><th>ชื่อสินค้า</th><th>หมวดหมู่</th><th class="r">ราคา (บาท)</th><th class="r">สต็อก</th><th>จัดการ</th></tr>
  <?php if (!$rows): ?><tr><td colspan="7">ยังไม่มีสินค้า กด "เพิ่มสินค้าใหม่" เพื่อเริ่มต้น</td></tr><?php endif; ?>
  <?php foreach ($rows as $p): ?>
  <tr>
    <td><?= (int)$p['id'] ?></td>
    <td><?php if ($p['image']): ?><img class="thumb" src="../uploads/<?= e($p['image']) ?>" alt=""><?php else: ?><span class="thumb"></span><?php endif; ?></td>
    <td><?= e($p['name']) ?></td>
    <td><?= e($p['category']) ?></td>
    <td class="r"><?= money($p['price']) ?></td>
    <td class="r"><?= (int)$p['stock'] ?></td>
    <td class="row-actions">
      <a class="btn small" href="product_form.php?id=<?= (int)$p['id'] ?>">แก้ไข</a>
      <form method="post" onsubmit="return confirm('ต้องการลบสินค้านี้ใช่หรือไม่?')">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <button class="btn danger small">ลบ</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php page_foot();
