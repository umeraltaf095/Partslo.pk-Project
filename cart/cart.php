<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

if (session_status() === PHP_SESSION_NONE)
    session_start();

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item)
    $total += $item['price'] * $item['qty'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Cart - PartsLo.pk</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        :root {
            --primary: #1e3c72;
            --secondary: #2a5298;
            --bg-color: #f4f7f6;
            --text-dark: #333;
            --text-muted: #666;
            --card-bg: #fff;
            --border-color: #eaeaea;
            --danger: #dc2626;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
        }

        /* ⭐ TOP BAR ⭐ */
        .navbar {
            background: #222;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar .brand {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            letter-spacing: 1px;
        }

        .navbar .nav-links a {
            color: #ddd;
            margin-left: 20px;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s;
        }

        .navbar .nav-links a:hover {
            color: #fff;
        }

        /* ⭐ CART CONTAINER ⭐ */
        .page-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            margin: 0;
            color: var(--primary);
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 2.5fr 1fr;
            gap: 30px;
        }

        .cart-box,
        .summary-box {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        /* ⭐ TABLE ⭐ */
        .cart-table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
        }

        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .product-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--primary);
        }

        .qty-badge {
            background: #f1f5f9;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-dark);
        }

        .item-price {
            font-weight: 600;
        }

        /* REMOVE LINK */
        .remove-link {
            color: var(--danger);
            font-size: 14px;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 4px;
            background: #fee2e2;
            transition: all 0.2s;
            display: inline-block;
        }

        .remove-link:hover {
            background: var(--danger);
            color: white;
        }

        /* ⭐ SUMMARY BOX ⭐ */
        .summary-box h3 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            color: var(--primary);
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            border-top: 2px solid var(--border-color);
            padding-top: 15px;
            margin-top: 10px;
            color: var(--primary);
        }

        /* ⭐ BUTTON ⭐ */
        .btn-checkout {
            display: block;
            width: 100%;
            text-align: center;
            padding: 14px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            margin-top: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state h2 {
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .btn-shop {
            display: inline-block;
            padding: 12px 24px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-shop:hover {
            background: var(--secondary);
        }

        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <!-- ⭐ TOP NAV BAR ⭐ -->
    <div class="navbar">
        <a href="/partslo/index.php" class="brand">PartsLo.pk</a>
        <div class="nav-links">
            <a href="/partslo/index.php">Shop</a>
            <a href="/partslo/user/dashboard.php">Dashboard</a>
            <a href="/partslo/orders/my_orders.php">My Orders</a>
            <a href="/partslo/user/logout.php" style="color: #ff6b6b;">Logout</a>
        </div>
    </div>

    <div class="page-container">
        <div class="page-header">
            <h1>Your Shopping Cart</h1>
        </div>

        <?php if (empty($cart)): ?>
            <div class="cart-box empty-state">
                <h2>Your cart is currently empty</h2>
                <p style="color: var(--text-muted); margin-bottom: 30px;">Looks like you haven't added any moto parts to
                    your cart yet.</p>
                <a href="/partslo/" class="btn-shop">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">

                <div class="cart-box">
                    <div class="cart-table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product Details</th>
                                    <th>Unit Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $id => $it): ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <img src="/partslo/assets/images/<?= htmlspecialchars($it['image']) ?>"
                                                    alt="<?= htmlspecialchars($it['name']) ?>" class="product-image">
                                                <span class="product-name"><?= htmlspecialchars($it['name']) ?></span>
                                            </div>
                                        </td>
                                        <td>Rs <?= number_format($it['price'], 2) ?></td>
                                        <td><span class="qty-badge"><?= intval($it['qty']) ?></span></td>
                                        <td class="item-price">Rs <?= number_format($it['price'] * $it['qty'], 2) ?></td>
                                        <td>
                                            <a class="remove-link" href="remove.php?product_id=<?= intval($id) ?>">Remove</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="summary-box">
                    <h3>Order Summary</h3>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs <?= number_format($total, 2) ?></span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Calculated at checkout</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total Amount</span>
                        <span>Rs <?= number_format($total, 2) ?></span>
                    </div>

                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout</a>
                </div>

            </div>
        <?php endif; ?>

    </div>

</body>

</html>