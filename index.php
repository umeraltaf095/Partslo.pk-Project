<?php
session_start();
require_once "includes/db_connect.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PartsLo.pk - Auto Parts Store</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .navbar {
            background: #222;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .navbar a {
            color: white;
            margin-right: 15px;
            text-decoration: none;
            font-weight: bold;
        }

        .navbar-right a:last-child {
            margin-right: 0;
        }

        /* Search bar styling */
        .search-bar {
            background: #fff;
            padding: 20px;
            margin-top: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.2);
        }

        .search-bar input,
        .search-bar select {
            padding: 10px;
            font-size: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 200px;
        }

        .search-bar button {
            padding: 10px 20px;
            background: #222;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .search-bar button:hover {
            background: #444;
        }
    </style>
</head>

<body>

    <div class="navbar">

        <div class="navbar-left">
            <a href="/partslo/index.php">🏠 Home</a>
        </div>

        <div class="navbar-right">
            <?php if (isset($_SESSION['user_id'])): ?>

                <span>👋 Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="/partslo/user/dashboard.php">Dashboard</a>
                <a href="/partslo/orders/my_orders.php">My Orders</a>
                <a href="/partslo/user/logout.php" style="color:#ff8080;">Logout</a>

            <?php else: ?>

                <a href="/partslo/user/login.php">Login</a>
                <a href="/partslo/user/register.php">Register</a>

            <?php endif; ?>
        </div>
    </div>

    <header>
        <h1>PartsLo.pk</h1>

        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search products...">

            <select id="categoryFilter">
                <option value="">All Categories</option>
                <?php
                $cats = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cats as $cat) {
                    echo '<option value="' . $cat['slug'] . '">' . $cat['name'] . '</option>';
                }
                ?>
            </select>

            <input type="number" id="minPrice" placeholder="Min Price">
            <input type="number" id="maxPrice" placeholder="Max Price">

            <button id="searchBtn">Search</button>
        </div>
    </header>

    <section class="categories">
        <?php
        foreach ($cats as $cat) {
            echo '<div class="cat-box" data-cat="' . $cat['slug'] . '">' . $cat['name'] . '</div>';
        }
        ?>
    </section>

    <section id="productList" class="product-grid">
    </section>

    <script src="assets/js/main.js"></script>
</body>

</html>