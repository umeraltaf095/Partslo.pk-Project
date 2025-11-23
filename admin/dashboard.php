<?php
require_once "admin_auth.php";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard - PartsLo.pk</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        /* ⭐ TOP BAR (Same as user dashboard) ⭐ */
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

        /* ⭐ CENTER BOX LIKE USER DASHBOARD ⭐ */
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

        .details-box ul {
            list-style: none;
            padding: 0;
        }

        .details-box ul li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .details-box ul li:last-child {
            border-bottom: none;
        }

        .details-box a {
            font-size: 18px;
            color: #00aaff;
            text-decoration: none;
            font-weight: bold;
        }

        .details-box a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<!-- ⭐ HEADER / TOP BAR ⭐ -->
<div class="top-bar">
    <div class="store-name">PartsLo.pk</div>

    <div class="links">
        Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?>
        <a href="/partslo/admin/logout.php">Logout</a>
    </div>
</div>

<header>
    <h1 style="text-align:center; margin-top:20px;">Admin Dashboard</h1>
</header>

<!-- ⭐ ADMIN OPTIONS BOX ⭐ -->
<div class="details-box">
    <h3>Manage Website</h3>

    <ul>
        <li><a href="/partslo/admin/products.php">Manage Products</a></li>
        <li><a href="/partslo/admin/categories.php">Manage Categories</a></li>
        <li><a href="/partslo/admin/orders.php">Manage Orders</a></li>
        <li><a href="/partslo/admin/reports.php">Reports</a></li>
    </ul>
</div>

</body>
</html>
