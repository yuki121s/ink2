<?php
require 'db_connect.php';

$errors  = [];
$orderId = null;
$_SESSION['cart'] = $_SESSION['cart'] ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    // ---- เพิ่มสินค้า ----
    if ($action === 'add' && $id > 0) {
        $s = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $s->execute([$id]);
        $stock = $s->fetchColumn();
        $have  = $_SESSION['cart'][$id] ?? 0;
        if ($stock !== false && $stock > $have) {
            $_SESSION['cart'][$id] = $have + 1;
            $_SESSION['flash'] = ['ok', 'เพิ่มสินค้าลงตะกร้าแล้ว'];
        } else {
            $_SESSION['flash'] = ['err', 'จำนวนสินค้าในคลังไม่พอ'];
        }
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'shop.php')); exit;
    }
    // ---- ลบ ----
    if ($action === 'remove') { unset($_SESSION['cart'][$id]); header('Location: cart.php'); exit; }

    // ---- เปลี่ยนจำนวน ----
    if ($action === 'update') {
        $qty = max(0, (int)($_POST['qty'] ?? 0));
        if ($qty === 0) { unset($_SESSION['cart'][$id]); } else { $_SESSION['cart'][$id] = $qty; }
        header('Location: cart.php'); exit;
    }

    // ---- สั่งซื้อ ----
    if ($action === 'checkout' && $_SESSION['cart']) {
        $fullname = trim($_POST['fullname'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $payment  = $_POST['payment_method'] ?? '';

        if ($fullname === '' || $address === '') { $errors[] = 'กรุณากรอกชื่อและที่อยู่จัดส่ง'; }
        if (!preg_match('/^[0-9+\-\s]{9,15}$/', $phone)) { $errors[] = 'เบอร์โทรศัพท์ไม่ถูกต้อง'; }
        if (!in_array($payment, ['cod', 'transfer'], true)) { $errors[] = 'กรุณาเลือกวิธีชำระเงิน'; }

        $slip = null;
        if (!$errors) {
            try { $slip = upload_image($_FILES['slip'] ?? ['error' => UPLOAD_ERR_NO_FILE], __DIR__ . '/uploads/slips'); }
            catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); }
        }

        if (!$errors) {
            try {
                $pdo->beginTransaction();
                $total = 0; $lines = [];
                foreach ($_SESSION['cart'] as $pid => $qty) {
                    // ล็อกแถวสินค้าเพื่อกันสต็อกติดลบเมื่อมีคนสั่งพร้อมกัน
                    $s = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id = ? FOR UPDATE");
                    $s->execute([$pid]);
                    $p = $s->fetch();
                    if (!$p) { continue; }
                    if ($p['stock'] < $qty) { throw new RuntimeException('สินค้า "' . $p['name'] . '" เหลือไม่พอ'); }
                    $total += $p['price'] * $qty;
                    $lines[] = [$p, $qty];
                }
                if (!$lines) { throw new RuntimeException('ตะกร้าว่างเปล่า'); }

                $pdo->prepare("INSERT INTO orders (fullname, phone, address, payment_method, total_price, slip_image) VALUES (?,?,?,?,?,?)")
                    ->execute([$fullname, $phone, $address, $payment, $total, $slip]);
                $orderId = (int)$pdo->lastInsertId();

                $ins = $pdo->prepare("INSERT INTO order_items (order_id, product_id, name, price, qty) VALUES (?,?,?,?,?)");
                $upd = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                foreach ($lines as [$p, $qty]) {
                    $ins->execute([$orderId, $p['id'], $p['name'], $p['price'], $qty]);
                    $upd->execute([$qty, $p['id']]);
                }
                $pdo->commit();
                $_SESSION['cart'] = [];
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $orderId = null;
                $errors[] = $ex instanceof RuntimeException ? $ex->getMessage() : 'ระบบขัดข้อง กรุณาลองใหม่';
            }
        }
    }
}

// ---- โหลดรายการในตะกร้า ----
$items = []; $total = 0;
if ($_SESSION['cart']) {
    $in = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
    $s = $pdo->prepare("SELECT * FROM products WHERE id IN ($in)");
    $s->execute(array_keys($_SESSION['cart']));
    foreach ($s->fetchAll() as $p) {
        $p['qty'] = min($_SESSION['cart'][$p['id']], max(1, (int)$p['stock']));
        $p['line'] = $p['price'] * $p['qty'];
        $total += $p['line'];
        $items[] = $p;
    }
}

page_head('ตะกร้าสินค้า');

if ($orderId): ?>
  <div class="panel" style="max-width:560px">
    <h1>สั่งซื้อสำเร็จ</h1>
    <p>เลขที่คำสั่งซื้อของคุณคือ <b>#<?= $orderId ?></b> ร้านจะติดต่อกลับทางเบอร์โทรที่ให้ไว้</p>
    <p style="margin-top:16px"><a class="btn primary" href="shop.php">เลือกซื้อสินค้าต่อ</a></p>
  </div>
<?php page_foot(); exit; endif; ?>

<h1>ตะกร้าสินค้า</h1>
<?php foreach ($errors as $er): ?><div class="alert err"><?= e($er) ?></div><?php endforeach; ?>

<?php if (!$items): ?>
  <div class="panel">ตะกร้ายังว่างอยู่ <a class="btn primary small" href="shop.php">ไปเลือกสินค้า</a></div>
<?php else: ?>
<div class="cols">
  <div class="scroll">
    <table>
      <tr><th></th><th>สินค้า</th><th>ราคา/ชิ้น</th><th>จำนวน</th><th class="r">รวม</th><th></th></tr>
      <?php foreach ($items as $i): ?>
      <tr>
        <td><?php if ($i['image']): ?><img class="thumb" src="uploads/<?= e($i['image']) ?>" alt=""><?php endif; ?></td>
        <td><?= e($i['name']) ?></td>
        <td>฿<?= money($i['price']) ?></td>
        <td>
          <form method="post" style="display:flex;gap:4px">
            <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
            <input type="number" name="qty" min="0" max="<?= (int)$i['stock'] ?>" value="<?= (int)$i['qty'] ?>" style="width:64px;padding:5px;border:1px solid #c5cbd1;border-radius:6px">
            <button class="btn small">อัปเดต</button>
          </form>
        </td>
        <td class="r">฿<?= money($i['line']) ?></td>
        <td>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
            <button class="btn danger small">ลบ</button></form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <form class="panel" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="checkout">
    <h2 style="margin-top:0">ข้อมูลจัดส่ง</h2>
    <div class="field"><label>ชื่อ-นามสกุล</label><input name="fullname" required value="<?= e($_POST['fullname'] ?? '') ?>"></div>
    <div class="field"><label>เบอร์โทรศัพท์</label><input name="phone" required value="<?= e($_POST['phone'] ?? '') ?>"></div>
    <div class="field"><label>ที่อยู่จัดส่ง</label><textarea name="address" rows="3" required><?= e($_POST['address'] ?? '') ?></textarea></div>
    <div class="field"><label>วิธีชำระเงิน</label>
      <select name="payment_method" required>
        <option value="cod">เก็บเงินปลายทาง</option>
        <option value="transfer">โอนเงิน (แนบสลิป)</option>
      </select></div>
    <div class="field"><label>สลิปโอนเงิน (เฉพาะกรณีโอน, JPG/PNG/WEBP ไม่เกิน 2 MB)</label><input type="file" name="slip" accept="image/*"></div>
    <div class="sum"><span>ยอดรวม</span><span>฿<?= money($total) ?></span></div>
    <button class="btn primary block">ยืนยันสั่งซื้อ</button>
  </form>
</div>
<?php endif; ?>
<?php page_foot();
