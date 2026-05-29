<?php
require_once "admin_auth.php";
require_once "../includes/db_connect.php";

// Fetch key statistics
// 1. Total Products
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $stmt->fetchColumn();

// 2. Total Categories
$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$total_categories = $stmt->fetchColumn();

// 3. Total Completed Orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Delivered'");
$completed_orders = $stmt->fetchColumn();

// 4. Total Pending Orders
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$pending_orders = $stmt->fetchColumn();

// 5. Recent Products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT 5");
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PartsLo.pk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4b134f;
            --secondary: #c94b4b;
            --bg-color: #f8fafc;
            --text-dark: #334155;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
        }

        .navbar {
            background: #1e293b;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .navbar .brand {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .navbar .nav-links span {
            color: #cbd5e1;
            font-weight: 500;
            font-size: 15px;
        }

        .navbar .nav-links a {
            padding: 8px 16px;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: background 0.3s;
        }

        .navbar .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            margin: 0;
            color: #0f172a;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 30px;
        }

        /* Sidebar Navigation */
        .sidebar {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            align-self: start;
        }

        .sidebar h3 {
            padding: 0 20px;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 13px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 1px;
            font-weight: 600;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li a {
            display: block;
            padding: 14px 20px;
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .sidebar ul li a.active, .sidebar ul li a:hover {
            background: #f1f5f9;
            color: var(--primary);
            border-left-color: var(--primary);
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 36px;
            color: #0f172a;
        }

        .stat-card p {
            margin: 0;
            color: var(--text-muted);
            font-size: 15px;
            font-weight: 500;
        }

        /* Specific Colors for Stat Cards */
        .stat-card.blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .stat-card.green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .stat-card.yellow::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .stat-card.purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

        .content-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .content-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
        }

        .content-card-header h2 {
            margin: 0;
            font-size: 20px;
            color: #0f172a;
        }

        .content-card-header a {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .content-card-header a:hover {
            text-decoration: underline;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            font-size: 15px;
            color: var(--text-dark);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background-color: #f8fafc;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-img {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        .price-tag {
            font-weight: 600;
            color: #10b981;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="/partslo/admin/dashboard.php" class="brand">PartsLo Admin</a>
    <div class="nav-links">
        <span>Hello, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
        <a href="/partslo/index.php" target="_blank">View Store</a>
        <a href="/partslo/admin/logout.php" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5;">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <div class="page-header">
        <h1>Dashboard Overview</h1>
    </div>

    <div class="dashboard-grid">
        
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <h3>Manage Website</h3>
            <ul>
                <li><a href="/partslo/admin/dashboard.php" class="active">Dashboard Home</a></li>
                <li><a href="/partslo/admin/products.php">Manage Products</a></li>
                <li><a href="/partslo/admin/categories.php">Manage Categories</a></li>
                <li><a href="/partslo/admin/orders.php">Manage Orders</a></li>
                <li><a href="/partslo/admin/reports.php">Reports & Analytics</a></li>
            </ul>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <h3><?= $total_products ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card purple">
                    <h3><?= $total_categories ?></h3>
                    <p>Total Categories</p>
                </div>
                <div class="stat-card green">
                    <h3><?= $completed_orders ?></h3>
                    <p>Completed Orders</p>
                </div>
                <div class="stat-card yellow">
                    <h3><?= $pending_orders ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>

            <!-- Recent Products Table -->
            <div class="content-card">
                <div class="content-card-header">
                    <h2>Recently Added Products</h2>
                    <a href="/partslo/admin/products.php">View All Products →</a>
                </div>
                
                <?php if (empty($recent_products)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">No products found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category ID</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_products as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <img src="/partslo/assets/images/<?= htmlspecialchars($p['image']) ?>" alt="" class="product-img">
                                                <span><?= htmlspecialchars($p['name']) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($p['category_id']) ?></td>
                                        <td class="price-tag">Rs <?= number_format($p['price'], 2) ?></td>
                                        <td><?= intval($p['stock']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

</body>
</html>
