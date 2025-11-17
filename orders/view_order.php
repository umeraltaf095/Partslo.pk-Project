<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT o.*, u.name AS user_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    echo "Order not found or you don't have permission.";
    exit;
}
$items = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Order <?=$order['order_number']?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<header><h1>Order <?=$order['order_number']?></h1></header>
<div style="max-width:800px;margin:20px auto;">
  <p>Status: <strong><?=htmlspecialchars($order['status'])?></strong></p>
  <p>Total: Rs <?=number_format($order['total_amount'],2)?></p>
  <p>Phone: <?=htmlspecialchars($order['phone'])?></p>
  <p>Address: <?=nl2br(htmlspecialchars($order['address']))?></p>
  <h3>Items</h3>
  <ul>
    <?php foreach($items as $it): ?>
      <li><?=htmlspecialchars($it['name'])?> x <?=intval($it['quantity'])?> — Rs <?=number_format($it['total_price'],2)?></li>
    <?php endforeach; ?>
  </ul>

  <?php if ($order['status'] === 'Pending'): ?>
    <p><a href="/partslo/orders/cancel.php?id=<?=$order['id']?>" style="color:red;">Cancel Order</a></p>
  <?php endif; ?>

  <?php if ($order['status'] === 'Delivered'): ?>
    <p><a href="/partslo/orders/feedback.php?id=<?=$order['id']?>">Write Feedback</a></p>
  <?php endif; ?>
</div>
</body>
</html>
