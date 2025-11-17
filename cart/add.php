<?php
// add to cart: expects GET or POST with product_id and qty (optional)
require_once "../includes/db_connect.php";
require_once "../includes/auth.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$id = intval($_REQUEST['product_id'] ?? 0);
$qty = max(1, intval($_REQUEST['qty'] ?? 1));

if ($id <= 0) {
    header('Location: /partslo/');
    exit;
}

// retrieve product to confirm existence and price
$stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$p) {
    header('Location: /partslo/');
    exit;
}

// initialize cart
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// if exists add qty, else set
if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['qty'] += $qty;
} else {
    $_SESSION['cart'][$id] = [
        'id' => $p['id'],
        'name' => $p['name'],
        'price' => $p['price'],
        'image' => $p['image'],
        'qty' => $qty
    ];
}

header('Location: /partslo/cart/cart.php');
exit;
