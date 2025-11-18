<?php
require_once "admin_auth.php";
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard - PartsLo.pk</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
.top-bar {
    background: #111;
    color: #fff;
    padding: 12px 20px;
    display: flex;
    justify-content: space-between;
}
.top-bar a {
    color: #00c3ff;
    text-decoration: none;
    margin-left: 15px;
}
.dashboard-box {
    max-width: 900px;
    margin: 40px auto;
}
</style>
</head>
<body>

<div class="top-bar">
    <div><strong>PartsLo Admin Panel</strong></div>
    <div>
        Welcome, <?= htmlspecialchars($_SESSION['admin_name']) ?> |
        <a href="/partslo/admin/logout.php">Logout</a>
    </div>
</div>

<div class="dashboard-box">
    <h2>Dashboard</h2>

    <ul>
        <li><a href="/partslo/admin/products.php">Manage Products</a></li>
        <li><a href="/partslo/admin/categories.php">Manage Categories</a></li>
        <li><a href="/partslo/admin/orders.php">Manage Orders</a></li>
        <li><a href="/partslo/admin/reports.php">Reports</a></li>
    </ul>
</div>

</body>
</html>
