<?php
require_once "../includes/db_connect.php";
require_once "../includes/auth.php";
require_login();
$user = current_user($pdo);

// Fetch recent orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total orders count
$stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM orders WHERE user_id = ?");
$stmt2->execute([$user['id']]);
$total_orders = $stmt2->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - PartsLo.pk</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-dark);
        }

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

        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 28px;
            margin: 0;
            color: var(--primary);
        }

        .welcome-msg {
            font-size: 16px;
            color: var(--text-muted);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--primary);
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .profile-info {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .profile-info li {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .profile-info li:last-child {
            margin-bottom: 0;
        }

        .profile-info label {
            font-size: 13px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .profile-info span {
            font-size: 16px;
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(30, 60, 114, 0.2);
        }

        .stat-card h3 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
        }

        .stat-card p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th, .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table th {
            background-color: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
        }

        .orders-table td {
            font-size: 15px;
        }

        .orders-table tr:hover td {
            background-color: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-Pending { background: #fef3c7; color: #d97706; }
        .status-Processing { background: #e0f2fe; color: #0284c7; }
        .status-Shipped { background: #f3e8ff; color: #9333ea; }
        .status-Completed { background: #dcfce7; color: #16a34a; }
        .status-Cancelled { background: #fee2e2; color: #dc2626; }
        .status- { background: #f1f5f9; color: #64748b; } /* Fallback */

        .btn-action {
            padding: 6px 12px;
            background: var(--bg-color);
            color: var(--primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-action:hover {
            background: var(--primary);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .empty-state a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .empty-state a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="/partslo/index.php" class="brand">PartsLo.pk</a>
    <div class="nav-links">
        <a href="/partslo/index.php">Shop</a>
        <a href="/partslo/cart/cart.php">Cart</a>
        <a href="/partslo/orders/my_orders.php">My Orders</a>
        <a href="/partslo/user/logout.php" style="color: #ff6b6b;">Logout</a>
    </div>
</div>

<div class="dashboard-container">
    <div class="page-header">
        <div>
            <h1>Dashboard</h1>
            <p class="welcome-msg">Welcome back, <?= htmlspecialchars($user['name']) ?>!</p>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <!-- Left Column: Profile -->
        <div class="card" style="align-self: start;">
            <h2 class="card-title">Profile Details</h2>
            <ul class="profile-info">
                <li>
                    <label>Full Name</label>
                    <span><?= htmlspecialchars($user['name']) ?></span>
                </li>
                <li>
                    <label>Email Address</label>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </li>
                <li>
                    <label>Phone Number</label>
                    <span><?= htmlspecialchars($user['phone']) ?></span>
                </li>
                <li>
                    <label>Shipping Address</label>
                    <span><?= htmlspecialchars($user['address']) ?></span>
                </li>
            </ul>
        </div>

        <!-- Right Column: Stats & Orders -->
        <div class="main-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $total_orders ?></h3>
                    <p>Total Orders</p>
                </div>
                <div class="stat-card" style="background: linear-gradient(135deg, #11998e, #38ef7d);">
                    <h3>Active</h3>
                    <p>Account Status</p>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title" style="display:flex; justify-content:space-between; align-items:center;">
                    Recent Orders
                    <a href="/partslo/orders/my_orders.php" style="font-size:14px; color:var(--secondary); text-decoration:none; font-weight:500;">View All</a>
                </h2>
                
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <p>You haven't placed any orders yet.</p>
                        <a href="/partslo/index.php">Start Shopping Now</a>
                    </div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $o): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($o['order_number']) ?></strong></td>
                                        <td><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                                        <td>Rs <?= number_format($o['total_amount'], 2) ?></td>
                                        <td>
                                            <?php 
                                            $status_class = "status-" . str_replace(' ', '', $o['status']); 
                                            ?>
                                            <span class="status-badge <?= $status_class ?>"><?= htmlspecialchars($o['status']) ?></span>
                                        </td>
                                        <td>
                                            <a href="/partslo/orders/view_order.php?id=<?= $o['id'] ?>" class="btn-action">View</a>
                                        </td>
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
