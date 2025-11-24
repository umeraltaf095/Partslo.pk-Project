<?php
require_once "admin_auth.php";
require_once "../includes/db_connect.php";
?>

<!DOCTYPE html>
<html>
<head>
<title>Reports - PartsLo.pk</title>
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
.report-box {
    max-width: 900px;
    background: #fff;
    padding: 25px;
    margin: 25px auto;
    border-radius: 8px;
    box-shadow: 0 0 12px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}

.report-box h2 {
    color: #0056b3;
    border-left: 5px solid #0056b3;
    padding-left: 10px;
    margin-bottom: 15px;
    font-size: 22px;
    font-weight: bold;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 15px;
}

.report-table th {
    background: #0056b3;
    color: #fff;
    padding: 12px;
    text-align: left;
    font-weight: bold;
    border-bottom: 3px solid #003f82;
}

.report-table td {
    padding: 10px;
    border-bottom: 1px solid #d9d9d9;
}

.report-table tr:nth-child(even) {
    background: #f3f8ff;
}

.low-stock {
    color: red;
    font-weight: bold;
}
</style>

</head>

<body>
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

<!-- Daily Report -->
<div class="report-box">
    <h2>Total Sold Parts - Daily</h2>

    <table class="report-table">
        <tr>
            <th>Date</th>
            <th>Parts Sold</th>
        </tr>

        <?php
        $daily_sold = $pdo->query("
            SELECT DATE(o.created_at) AS day, SUM(oi.quantity) AS sold_qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            GROUP BY DATE(o.created_at)
            ORDER BY day DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($daily_sold as $d):
        ?>
        <tr>
            <td><?= $d['day'] ?></td>
            <td><?= $d['sold_qty'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>


<!-- Monthly Report -->
<div class="report-box">
    <h2>Total Sold Parts - Monthly</h2>

    <table class="report-table">
        <tr>
            <th>Month</th>
            <th>Parts Sold</th>
        </tr>

        <?php
        $monthly_sold = $pdo->query("
            SELECT DATE_FORMAT(o.created_at, '%Y-%m') AS month, SUM(oi.quantity) AS sold_qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            GROUP BY DATE_FORMAT(o.created_at, '%Y-%m')
            ORDER BY month DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($monthly_sold as $m):
        ?>
        <tr>
            <td><?= $m['month'] ?></td>
            <td><?= $m['sold_qty'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>


<!-- Yearly Report -->
<div class="report-box">
    <h2>Total Sold Parts - Yearly</h2>

    <table class="report-table">
        <tr>
            <th>Year</th>
            <th>Parts Sold</th>
        </tr>

        <?php
        $yearly_sold = $pdo->query("
            SELECT YEAR(o.created_at) AS year, SUM(oi.quantity) AS sold_qty
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            GROUP BY YEAR(o.created_at)
            ORDER BY year DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($yearly_sold as $y):
        ?>
        <tr>
            <td><?= $y['year'] ?></td>
            <td><?= $y['sold_qty'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>


<!-- Remaining Stock Report -->
<div class="report-box">
    <h2>Remaining Stock Report (All Products)</h2>

    <table class="report-table">
        <tr>
            <th>Product</th>
            <th>Total Stock</th>
            <th>Total Sold</th>
            <th>Remaining</th>
        </tr>

        <?php
        $products = $pdo->query("
            SELECT p.id, p.name, p.stock
            FROM products p
            ORDER BY p.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $p):

            // Total sold for each product
            $sold_stmt = $pdo->prepare("
                SELECT SUM(quantity) AS sold_qty
                FROM order_items
                WHERE product_id = ?
            ");
            $sold_stmt->execute([$p['id']]);
            $sold = $sold_stmt->fetchColumn() ?: 0;

            $remaining = $p['stock'] - $sold;
            $low = $remaining < 5 ? "low-stock" : "";
        ?>
        <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= $p['stock'] ?></td>
            <td><?= $sold ?></td>
            <td class="<?= $low ?>"><?= $remaining ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
