<?php
require_once "../includes/auth.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['qty'] as $id => $qty) {
        $id = intval($id);
        $qty = max(0, intval($qty));
        if ($qty === 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty'] = $qty;
            }
        }
    }
}
header('Location: /partslo/cart/cart.php');
exit;
