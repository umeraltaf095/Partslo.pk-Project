<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

if (session_status() === PHP_SESSION_NONE) session_start();

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    // fixed header syntax
    header('Location: /partslo/cart/cart.php');
    exit;
}

$message = "";
$user = current_user($pdo);

// compute total for display (available on GET and POST)
$total = 0;
foreach ($cart as $it) {
    // ensure numeric safety
    $price = isset($it['price']) ? (float)$it['price'] : 0;
    $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
    $total += $price * $qty;
}

// If form submitted, create order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['address'] ?? $user['address']);
    $phone = trim($_POST['phone'] ?? $user['phone']);
    $payment_method = 'Cash on Delivery';
    $user_id = $_SESSION['user_id'];

    // compute total again (defensive)
    $total = 0;
    foreach ($cart as $it) $total += (float)$it['price'] * (int)$it['qty'];

    try {
        $pdo->beginTransaction();

        $order_number = 'ORD'.time().rand(100,999);

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, address, phone, status, payment_method) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([$user_id, $order_number, $total, $address, $phone, 'Pending', $payment_method]);
        $order_id = $pdo->lastInsertId();

        $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
        VALUES (?, ?, ?, ?, ?)");

        foreach ($cart as $it) {
            $item_total = (float)$it['price'] * (int)$it['qty'];
            $itemStmt->execute([$order_id, $it['id'], $it['qty'], $it['price'], $item_total]);

            // reduce stock if applicable (silently ignore if stock column not present)
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                ->execute([(int)$it['qty'], $it['id']]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);

        header("Location: /partslo/orders/view_order.php?id={$order_id}");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Failed to place order: " . $e->getMessage();
    }
}

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Checkout - PartsLo.pk</title>
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
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg-color);
        color: var(--text-dark);
    }

    /* ⭐ TOP NAV BAR ⭐ */
    .navbar {
        background: #222;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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

    /* ⭐ CONTAINER ⭐ */
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

    .checkout-layout {
        display: grid;
        grid-template-columns: 1.5fr 1fr;
        gap: 30px;
    }

    .checkout-box {
        background: var(--card-bg);
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    .checkout-box h2 {
        margin-top: 0;
        margin-bottom: 25px;
        font-size: 22px;
        color: var(--primary);
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }

    /* FORM STYLES */
    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-dark);
        font-size: 14px;
    }

    textarea, input {
        width: 100%;
        padding: 14px;
        font-size: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
        transition: border-color 0.3s;
        font-family: inherit;
    }

    textarea:focus, input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.1);
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    .payment-method {
        background: #f8fafc;
        padding: 15px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 500;
    }

    /* ⭐ ORDER SUMMARY ⭐ */
    .order-summary {
        background: #f8fafc;
        padding: 30px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
    }

    .order-summary h3 {
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 20px;
        color: var(--primary);
        border-bottom: 2px solid var(--border-color);
        padding-bottom: 10px;
    }

    .item-list {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
    }

    .item-list li {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        font-size: 15px;
        color: var(--text-dark);
        border-bottom: 1px dashed #cbd5e1;
        padding-bottom: 12px;
    }
    
    .item-list li:last-child {
        border-bottom: none;
    }

    .item-name {
        flex: 1;
        padding-right: 15px;
    }

    .item-price {
        font-weight: 600;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 16px;
        color: var(--text-muted);
    }

    .summary-row.total {
        font-size: 22px;
        font-weight: 700;
        border-top: 2px solid var(--border-color);
        padding-top: 15px;
        margin-top: 15px;
        color: var(--primary);
    }

    /* ⭐ BUTTON ⭐ */
    .btn-submit {
        width: 100%;
        padding: 16px;
        border: none;
        background: linear-gradient(135deg, #11998e, #38ef7d);
        color: white;
        font-size: 18px;
        font-weight: bold;
        border-radius: 8px;
        cursor: pointer;
        margin-top: 25px;
        transition: transform 0.2s, box-shadow 0.2s;
        box-shadow: 0 4px 10px rgba(17, 153, 142, 0.3);
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(17, 153, 142, 0.4);
    }

    /* ERROR MESSAGE */
    .msg { 
        background: #fee2e2;
        color: #b91c1c;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 25px;
        border-left: 4px solid #ef4444;
    }

    @media (max-width: 900px) {
        .checkout-layout {
            grid-template-columns: 1fr;
        }
        
        .order-summary {
            order: -1; /* Move summary to top on mobile */
        }
    }
</style>
</head>
<body>

<!-- ⭐ TOP NAV ⭐ -->
<div class="navbar">
    <a href="/partslo/index.php" class="brand">PartsLo.pk</a>
    <div class="nav-links">
        <a href="/partslo/index.php">Shop</a>
        <a href="/partslo/cart/cart.php">Cart</a>
        <a href="/partslo/orders/my_orders.php">My Orders</a>
        <a href="/partslo/user/logout.php" style="color: #ff6b6b;">Logout</a>
    </div>
</div>

<div class="page-container">
    <div class="page-header">
        <h1>Secure Checkout</h1>
    </div>

    <?php if ($message): ?>
        <div class="msg"><?=htmlspecialchars($message)?></div>
    <?php endif; ?>

    <form method="post">
        <div class="checkout-layout">
            
            <!-- Left Side: Form -->
            <div class="checkout-box">
                <h2>Shipping Information</h2>
                
                <div class="form-group">
                    <label>Full Delivery Address</label>
                    <textarea name="address" required placeholder="Enter your full street address, apartment, city, etc."><?=htmlspecialchars($user['address'] ?? '')?></textarea>
                </div>

                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" required placeholder="e.g. 0300 1234567" value="<?=htmlspecialchars($user['phone'] ?? '')?>">
                </div>

                <h2>Payment Method</h2>
                <div class="form-group">
                    <div class="payment-method">
                        <input type="radio" checked disabled style="width:auto; margin:0;"> 
                        <span>Cash on Delivery (Pay when you receive the package)</span>
                    </div>
                </div>
            </div>

            <!-- Right Side: Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                
                <ul class="item-list">
                <?php foreach($cart as $it): ?>
                    <li>
                        <span class="item-name">
                            <?=htmlspecialchars($it['name'])?> 
                            <span style="color:var(--text-muted); font-size:13px;">x <?=intval($it['qty'])?></span>
                        </span>
                        <span class="item-price">Rs <?=number_format((float)$it['price'] * (int)$it['qty'],2)?></span>
                    </li>
                <?php endforeach; ?>
                </ul>

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rs <?=number_format($total, 2)?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="color:#10b981; font-weight:600;">Free</span>
                </div>

                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span>Rs <?=number_format($total, 2)?></span>
                </div>

                <button type="submit" class="btn-submit">Place Order Now</button>
                <p style="text-align:center; font-size:12px; color:var(--text-muted); margin-top:15px;">By placing your order, you agree to our Terms of Service and Privacy Policy.</p>
            </div>

        </div>
    </form>
</div>

</body>
</html>
