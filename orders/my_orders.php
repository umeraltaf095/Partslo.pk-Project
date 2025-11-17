<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>My Orders</title></head>
<body>
<header><h1>My Orders</h1></header>
<div style="max-width:900px;margin:20px auto;">
  <?php if (empty($orders)): ?>
    <p>No orders yet.</p>
  <?php else: ?>
    <table style="width:100%;border-collapse:collapse">
      <thead><tr><th>Order #</th><th>Total</th><th>Status</th><th>Placed</th><th></th></tr></thead>
      <tbody>
      <?php foreach($orders as $o): ?>
        <tr>
          <td><?=htmlspecialchars($o['order_number'])?></td>
          <td>Rs <?=number_format($o['total_amount'],2)?></td>
          <td><?=htmlspecialchars($o['status'])?></td>
          <td><?=htmlspecialchars($o['created_at'])?></td>
          <td><a href="/partslo/orders/view_order.php?id=<?=$o['id']?>">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
</body></html>
