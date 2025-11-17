<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /partslo/orders/my_orders.php');
    exit;
}

// verify user and status pending
$stmt = $pdo->prepare("SELECT user_id, status FROM orders WHERE id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order || $order['user_id'] != $_SESSION['user_id']) {
    echo "Invalid request.";
    exit;
}
if ($order['status'] !== 'Pending') {
    echo "You can cancel only pending orders.";
    exit;
}
$pdo->prepare("UPDATE orders SET status = 'Cancelled' WHERE id = ?")->execute([$id]);
header('Location: /partslo/orders/view_order.php?id=' . $id);
exit;
