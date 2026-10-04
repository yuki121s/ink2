<?php
require 'db_connect.php';

$cat = $_GET['category'] ?? '';
$q   = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];
if ($cat !== '' && in_array($cat, CATEGORIES, true)) { $sql .= " AND category = ?"; $params[] = $cat; }
if ($q !== '') { $sql .= " AND name LIKE ?"; $params[] = "%$q%"; }
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);

page_head('สินค้าทั้งหมด');
?>
<h1>ยางและอะไหล่ยนต์</h1>
<?php if ($flash): ?><div class="alert <?= e($flash[0]) ?>"><?= e($flash[1]) ?></div><?php endif; ?>

<div class="filters">
  <a class="chip <?= $cat === '' ? 'on' : '' ?>" href="shop.php">ทั้งหมด</a>
  <?php foreach (CATEGORIES as $c): ?>
    <a class="chip <?= $cat === $c ? 'on' : '' ?>" href="shop.php?category=<?= urlencode($c) ?>"><?= e($c) ?></a>
  <?php endforeach; ?>
  <form class="search" method="get">
    <?php if ($cat): ?><input type="hidden" name="category" value="<?= e($cat) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="ค้นหาชื่อสินค้า">
    <button class="btn small">ค้นหา</button>
  </form>
</div>

<?php if (!$products): ?>
  <div class="panel">ไม่พบสินค้าที่ตรงกับเงื่อนไข ลองเลือกหมวดอื่นหรือล้างคำค้นหา</div>
<?php else: ?>
<div class="grid">
<?php foreach ($products as $p): ?>
  <div class="card">
    <div class="pic">
      <span class="tag"><?= e($p['category']) ?></span>
      <?php if ($p['image'] && is_file(__DIR__ . '/uploads/' . $p['image'])): ?>
        <img src="uploads/<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
      <?php else: ?>ยังไม่มีรูปสินค้า<?php endif; ?>
    </div>
    <div class="body">
      <div class="name"><?= e($p['name']) ?></div>
      <div class="price">฿<?= money($p['price']) ?></div>
      <?php if ($p['stock'] > 0): ?>
        <div class="stock">คงเหลือ <?= (int)$p['stock'] ?> ชิ้น</div>
        <form method="post" action="cart.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="btn primary block">ใส่ตะกร้า</button>
        </form>
      <?php else: ?>
        <div class="stock">สินค้าหมดชั่วคราว</div>
        <button class="btn block" disabled>สินค้าหมด</button>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php page_foot();
