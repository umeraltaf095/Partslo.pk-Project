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

    /* ⭐ CONTAINER ⭐ */
    .checkout-box {
        max-width: 900px;
        margin: 30px auto;
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0px 0px 15px rgba(0,0,0,0.15);
    }

    h1 {
        text-align:center;
        margin-top: 20px;
    }

    label {
        font-weight: bold;
        margin-top: 10px;
        display: block;
    }

    textarea, input {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-top: 5px;
    }

    /* ⭐ ORDER SUMMARY ⭐ */
    .summary-box {
        background: #f7f7f7;
        padding: 15px;
        border-radius: 6px;
        margin-top: 15px;
    }
    .summary-box ul {
        margin: 0;
        padding-left: 20px;
    }
    .summary-box ul li {
        margin-bottom: 8px;
        font-size: 16px;
    }

    /* ⭐ BUTTON ⭐ */
    .btn-dark {
        padding: 12px 20px;
        border: none;
        background: #111;
        color: white;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        margin-top: 15px;
        display: inline-block;
    }
    .btn-dark:hover {
        background: #000;
    }

    /* ERROR MESSAGE */
    .msg { color: red; font-weight: bold; }
</style>

</head>
<body>

<!-- ⭐ TOP NAV ⭐ -->
<div class="top-bar">
    <div class="store-name">PartsLo.pk</div>

    <div>
        <a href="/partslo/index.php">Home</a>
        <a href="/partslo/user/dashboard.php">Dashboard</a>
        <a href="/partslo/orders/my_orders.php">My Orders</a>
        <a href="/partslo/user/logout.php">Logout</a>
    </div>
</div>

<h1>Checkout</h1>

<div class="checkout-box">

  <?php if ($message): ?>
      <p class="msg"><?=htmlspecialchars($message)?></p>
  <?php endif; ?>

  <form method="post">

    <label>Delivery Address</label>
    <textarea name="address" required><?=htmlspecialchars($user['address'] ?? '')?></textarea>

    <label>Phone</label>
    <input type="text" name="phone" required value="<?=htmlspecialchars($user['phone'] ?? '')?>">

    <p><strong>Payment Method:</strong> Cash on Delivery</p>

    <h3>Order Summary</h3>

    <div class="summary-box">
        <ul>
        <?php foreach($cart as $it): ?>
            <li>
                <?=htmlspecialchars($it['name'])?>  
                (<?=intval($it['qty'])?> pcs) —  
                Rs <?=number_format((float)$it['price'] * (int)$it['qty'],2)?>
            </li>
        <?php endforeach; ?>
        </ul>

        <p><strong>Total: Rs <?=number_format($total, 2)?></strong></p>
    </div>

    <button type="submit" class="btn-dark">Place Order</button>

  </form>

</div>

</body>
</html>
