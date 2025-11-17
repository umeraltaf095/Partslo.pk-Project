<?php
require_once "../includes/db_connect.php";
require_once "../includes/auth.php";
require_login();
$user = current_user($pdo);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dashboard - PartsLo.pk</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
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
        .top-bar a:hover {
            text-decoration: underline;
        }

        /* ⭐ USER DETAILS CARD ⭐ */
        .details-box {
            max-width: 450px;
            margin: 40px auto;
            padding: 25px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.15);
        }
        .details-box h3 {
            text-align: center;
            margin-bottom: 20px;
            color: #111;
        }
        .details-box p {
            padding: 10px 0;
            font-size: 16px;
            border-bottom: 1px solid #eee;
        }
        .details-box p:last-child {
            border-bottom: none;
        }
        .details-label {
            font-weight: bold;
            color: #00aaff;
        }
    </style>
</head>

<body>

<div class="top-bar">
    <div class="store-name"><?= htmlspecialchars($user['name']) ?></div>

    <div class="links">
        <a href="/partslo/index.php">Home</a>
        <a href="/partslo/cart/cart.php">Cart</a>
        <a href="/partslo/orders/my_orders.php">My Orders</a>
        <a href="/partslo/user/dashboard.php">Dashboard</a>
        <a href="/partslo/user/logout.php">Logout</a>
    </div>
</div>

<header>
    <h1 style="text-align:center; margin-top:20px;">
        PartsLo.pk
    </h1>
</header>

<!-- ⭐ CENTER USER DETAILS BOX ⭐ -->
<div class="details-box">
    <h3>Your Details</h3>

    <p><span class="details-label">Email:</span> <?= htmlspecialchars($user['email']) ?></p>
    <p><span class="details-label">Phone:</span> <?= htmlspecialchars($user['phone']) ?></p>
    <p><span class="details-label">Address:</span> <?= htmlspecialchars($user['address']) ?></p>
</div>

</body>
</html>
