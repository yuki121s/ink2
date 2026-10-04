<?php
require __DIR__ . '/../db_connect.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $st = $_POST['status'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if (isset(STATUS_LABELS[$st])) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$st, $id]);
        $_SESSION['flash'] = ['ok', 'อัปเดตสถานะคำสั่งซื้อ #' . $id . ' แล้ว'];
    }
    header('Location: orders.php?id=' . $id); exit;
}

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
page_head('คำสั่งซื้อ', '../', true);

if (isset($_GET['id'])):
    $s = $pdo->prepare("SELECT * FROM orders WHERE id = ?"); $s->execute([(int)$_GET['id']]);
    $o = $s->fetch();
    if (!$o) { echo '<div class="alert err">ไม่พบคำสั่งซื้อ</div>'; page_foot(); exit; }
    $s = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?"); $s->execute([$o['id']]);
    $items = $s->fetchAll(); ?>
  <h1>คำสั่งซื้อ #<?= (int)$o['id'] ?></h1>
  <?php if ($flash): ?><div class="alert <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>
  <div class="cols">
    <div class="scroll"><table>
      <tr><th>สินค้า</th><th>ราคา</th><th>จำนวน</th><th class="r">รวม</th></tr>
      <?php foreach ($items as $i): ?>
      <tr><td><?= e($i['name']) ?></td><td>฿<?= money($i['price']) ?></td><td><?= (int)$i['qty'] ?></td><td class="r">฿<?= money($i['price'] * $i['qty']) ?></td></tr>
      <?php endforeach; ?>
      <tr><td colspan="3"><b>ยอดรวม</b></td><td class="r"><b>฿<?= money($o['total_price']) ?></b></td></tr>
    </table></div>
    <div class="panel">
      <p><b>ผู้รับ:</b> <?= e($o['fullname']) ?></p>
      <p><b>โทร:</b> <?= e($o['phone']) ?></p>
      <p><b>ที่อยู่:</b> <?= nl2br(e($o['address'])) ?></p>
      <p><b>ชำระเงิน:</b> <?= $o['payment_method'] === 'cod' ? 'เก็บเงินปลายทาง' : 'โอนเงิน' ?></p>
      <p><b>วันที่สั่ง:</b> <?= e($o['created_at']) ?></p>
      <?php if ($o['slip_image']): ?><p><b>สลิป:</b><br><a href="../uploads/slips/<?= e($o['slip_image']) ?>" target="_blank"><img class="preview" src="../uploads/slips/<?= e($o['slip_image']) ?>" alt="สลิป"></a></p><?php endif; ?>
      <form method="post" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
        <div class="field"><label>สถานะ</label>
          <select name="status"><?php foreach (STATUS_LABELS as $k => $v): ?><option value="<?= $k ?>" <?= $o['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
        <button class="btn primary">บันทึกสถานะ</button> <a class="btn" href="orders.php">กลับ</a>
      </form>
    </div>
  </div>
<?php else:
    $rows = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(); ?>
  <h1>คำสั่งซื้อทั้งหมด</h1>
  <div class="scroll"><table>
    <tr><th>เลขที่</th><th>วันที่</th><th>ผู้สั่ง</th><th class="r">ยอดรวม</th><th>สถานะ</th><th></th></tr>
    <?php if (!$rows): ?><tr><td colspan="6">ยังไม่มีคำสั่งซื้อ</td></tr><?php endif; ?>
    <?php foreach ($rows as $o): ?>
    <tr><td>#<?= (int)$o['id'] ?></td><td><?= e($o['created_at']) ?></td><td><?= e($o['fullname']) ?></td>
      <td class="r">฿<?= money($o['total_price']) ?></td>
      <td><span class="badge <?= e($o['status']) ?>"><?= e(STATUS_LABELS[$o['status']]) ?></span></td>
      <td><a class="btn small" href="orders.php?id=<?= (int)$o['id'] ?>">ดูรายละเอียด</a></td></tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; page_foot();
