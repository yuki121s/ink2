<?php
// ฟอร์มเดียวใช้ได้ทั้ง "เพิ่ม" (ไม่มี ?id) และ "แก้ไข" (?id=..)
require __DIR__ . '/../db_connect.php';
require_admin();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$p  = ['name' => '', 'description' => '', 'price' => '', 'stock' => '', 'category' => '', 'image' => null];

if ($id) {
    $s = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $s->execute([$id]);
    $p = $s->fetch();
    if (!$p) { $_SESSION['flash'] = ['err', 'ไม่พบสินค้าที่ต้องการแก้ไข']; header('Location: products.php'); exit; }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name  = trim($_POST['name'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    $cat   = $_POST['category'] ?? '';

    if ($name === '')                                  { $errors[] = 'กรุณากรอกชื่อสินค้า'; }
    if (!is_numeric($price) || $price < 0)             { $errors[] = 'ราคาต้องเป็นตัวเลขตั้งแต่ 0 ขึ้นไป'; }
    if (!ctype_digit((string)$stock))                  { $errors[] = 'สต็อกต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป'; }
    if (!in_array($cat, CATEGORIES, true))             { $errors[] = 'กรุณาเลือกหมวดหมู่'; }

    $newImg = null;
    if (!$errors) {
        try { $newImg = upload_image($_FILES['image'] ?? ['error' => UPLOAD_ERR_NO_FILE], __DIR__ . '/../uploads'); }
        catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
    }

    if (!$errors) {
        if ($id) {
            $pdo->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category=?, image=? WHERE id=?")
                ->execute([$name, $desc, $price, $stock, $cat, $newImg ?? $p['image'], $id]);
            if ($newImg && $p['image'] && is_file(__DIR__ . '/../uploads/' . $p['image'])) {
                unlink(__DIR__ . '/../uploads/' . $p['image']);   // ลบรูปเก่า
            }
            $_SESSION['flash'] = ['ok', 'บันทึกการแก้ไขแล้ว'];
        } else {
            $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image) VALUES (?,?,?,?,?,?)")
                ->execute([$name, $desc, $price, $stock, $cat, $newImg]);
            $_SESSION['flash'] = ['ok', 'เพิ่มสินค้าใหม่แล้ว'];
        }
        header('Location: products.php'); exit;
    }
    $p = array_merge($p, ['name' => $name, 'description' => $desc, 'price' => $price, 'stock' => $stock, 'category' => $cat]);
}

page_head($id ? 'แก้ไขสินค้า' : 'เพิ่มสินค้า', '../', true);
?>
<h1><?= $id ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่' ?></h1>
<form class="panel form" method="post" enctype="multipart/form-data">
  <?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int)$id ?>">

  <div class="field"><label>ชื่อสินค้า</label><input name="name" required value="<?= e($p['name']) ?>" placeholder="เช่น ยาง Michelin 215/55R17"></div>
  <div class="field"><label>รายละเอียด</label><textarea name="description" rows="3"><?= e($p['description']) ?></textarea></div>
  <div class="field"><label>ราคา (บาท)</label><input type="number" step="0.01" min="0" name="price" required value="<?= e($p['price']) ?>"></div>
  <div class="field"><label>จำนวนสต็อก</label><input type="number" min="0" name="stock" required value="<?= e($p['stock']) ?>"></div>
  <div class="field"><label>หมวดหมู่</label>
    <select name="category" required>
      <option value="">-- เลือกหมวดหมู่ --</option>
      <?php foreach (CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $p['category'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?>
    </select></div>
  <div class="field"><label>รูปสินค้า (JPG/PNG/WEBP ไม่เกิน 2 MB)</label>
    <?php if ($p['image']): ?><img class="preview" src="../uploads/<?= e($p['image']) ?>" alt=""><br><small>เลือกไฟล์ใหม่เพื่อเปลี่ยนรูป ถ้าไม่เลือกจะใช้รูปเดิม</small><?php endif; ?>
    <input type="file" name="image" accept="image/*"></div>

  <button class="btn primary">บันทึกข้อมูล</button>
  <a class="btn" href="products.php">ยกเลิก</a>
</form>
<?php page_foot();
