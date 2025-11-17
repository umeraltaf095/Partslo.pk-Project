<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

if (session_status() === PHP_SESSION_NONE) session_start();

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) $total += $item['price'] * $item['qty'];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Cart - PartsLo.pk</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
    /* ⭐ TOP BAR ⭐ */
    .top-bar {
        background: #111;
        padding: 12px 20px;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .top-bar .store-name {
        font-size: 22px;
        font-weight: bold;
        color: #00c3ff;
    }
    .top-bar a {
        color: #fff;
        margin-left: 20px;
        text-decoration: none;
        font-size: 16px;
    }
    .top-bar a:hover { text-decoration: underline; }

    /* ⭐ CART CONTAINER ⭐ */
    .cart-box {
        max-width: 1000px;
        margin: 30px auto;
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 0 15px rgba(0,0,0,0.15);
    }

    /* ⭐ TABLE ⭐ */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #eee;
        font-size: 15px;
    }
    th { background: #f5f5f5; }

    /* ⭐ BUTTON ⭐ */
    .btn-dark {
        padding: 10px 14px;
        border-radius: 5px;
        background: #222;
        color: #fff;
        text-decoration: none;
        font-size: 15px;
    }
    .btn-dark:hover { background: #000; }

    /* REMOVE LINK */
    .remove-link {
        color: #d00000;
        font-weight: bold;
        text-decoration: none;
    }
    .remove-link:hover { text-decoration: underline; }

</style>

</head>

<body>

<!-- ⭐ TOP NAV BAR ⭐ -->
<div class="top-bar">
    <div class="store-name">PartsLo.pk</div>
    <div class="links">
        <a href="/partslo/index.php">Home</a>
        <a href="/partslo/user/dashboard.php">Dashboard</a>
        <a href="/partslo/orders/my_orders.php">My Orders</a>
        <a href="/partslo/user/logout.php">Logout</a>
    </div>
</div>

<header>
    <h1 style="text-align:center; margin-top:20px;">Your Cart</h1>
</header>

<div class="cart-box">

<?php if (empty($cart)): ?>
    <p>Your cart is empty. <a href="/partslo/">Go to shop</a></p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Subtotal</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
        <?php foreach($cart as $id => $it): ?>
            <tr>
                <td>
                    <img src="/partslo/assets/images/<?=htmlspecialchars($it['image'])?>" 
                         style="height:50px;margin-right:8px;">
                    <?=htmlspecialchars($it['name'])?>
                </td>

                <td>Rs <?=number_format($it['price'], 2)?></td>

                <!-- ⭐ FIXED (NON-EDITABLE) QTY ⭐ -->
                <td><?=intval($it['qty'])?></td>

                <!-- Subtotal -->
                <td>Rs <?=number_format($it['price'] * $it['qty'], 2)?></td>

                <td>
                    <a class="remove-link" href="remove.php?product_id=<?=intval($id)?>">
                        Remove
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <p style="text-align:right;margin-top:10px;font-size:18px;">
        <strong>Total: Rs <?=number_format($total, 2)?></strong>
    </p>

    <div style="text-align:right;">
        <a href="checkout.php" class="btn-dark">Proceed to Checkout</a>
    </div>

<?php endif; ?>

</div>

</body>
</html>
