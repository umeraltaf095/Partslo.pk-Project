<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

$order_id = intval($_GET['id'] ?? 0);
if (!$order_id) exit('Invalid');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO feedback (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $product_id, $rating, $comment]);
    header("Location: /partslo/orders/view_order.php?id={$order_id}");
    exit;
}

// Show simple form: list products in order
$stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id = ?");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head><meta charset="utf-8"><title>Feedback</title></head>
<body>
<h1>Write Feedback</h1>
<form method="post">
  <label>Product</label>
  <select name="product_id" required>
    <?php foreach($items as $it): ?>
      <option value="<?=intval($it['product_id'])?>"><?=htmlspecialchars($it['name'])?></option>
    <?php endforeach; ?>
  </select>
  <label>Rating (1-5)</label>
  <input type="number" name="rating" min="1" max="5" value="5" required>
  <label>Comment</label>
  <textarea name="comment" required></textarea>
  <button type="submit">Submit Feedback</button>
</form>
</body></html>
