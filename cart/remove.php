<?php
require_once "../includes/auth.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$id = intval($_GET['product_id'] ?? 0);
if ($id && isset($_SESSION['cart'][$id])) {
    unset($_SESSION['cart'][$id]);
}
header('Location: /partslo/cart/cart.php');
exit;
