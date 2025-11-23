<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>My Orders - PartsLo.pk</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>

    /* ⭐ TOP BAR (Same as Cart Page) ⭐ */
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

    /* ⭐ MAIN CONTAINER ⭐ */
    .order-box {
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

    /* View Button */
    .btn-dark {
        padding: 8px 12px;
        border-radius: 5px;
        background: #222;
        color: #fff;
        text-decoration: none;
        font-size: 14px;
    }
    .btn-dark:hover { background: #000; }

</style>

</head>

<body>

<!-- ⭐ NAVIGATION BAR ⭐ -->
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
    <h1 style="text-align:center; margin-top:20px;">My Orders</h1>
</header>

<div class="order-box">

<?php if (empty($orders)): ?>
    <p style="font-size:17px;">No orders yet. <a href="/partslo/">Go to shop</a></p>

<?php else: ?>

    <table>
        <thead>
            <tr>
                <th>Order #</th>
                <th>Total</th>
                <th>Status</th>
                <th>Placed On</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= htmlspecialchars($o['order_number']) ?></td>
                <td>Rs <?= number_format($o['total_amount'], 2) ?></td>
                <td><?= htmlspecialchars($o['status']) ?></td>
                <td><?= htmlspecialchars($o['created_at']) ?></td>
                <td>
                    <a class="btn-dark" href="/partslo/orders/view_order.php?id=<?= $o['id'] ?>">
                        View
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>

</div>

</body>
</html>
