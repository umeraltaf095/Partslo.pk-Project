<?php
session_start();
require_once "../includes/db_connect.php";

// Get product ID from URL
if (!isset($_GET['id'])) {
    echo "Product ID not provided!";
    exit;
}

$id = intval($_GET['id']);

// Fetch product from database with category name
$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found!";
    exit;
}

// Check login status
$logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> | PartsLo</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<header>
    <h1>PartsLo.pk - Product Details</h1>
</header>

<section class="product-preview">
    <div class="product-card" style="max-width: 600px; margin: 20px auto;">
        
        <img src="/partslo/assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>" style="height:300px;">
             
        <h2><?php echo htmlspecialchars($product['name']); ?></h2>

        <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
        <p><strong>Price:</strong> Rs <?php echo htmlspecialchars($product['price']); ?></p>
        <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>

        <?php if ($logged_in): ?>
            
            <!-- Add to Cart Form -->
            <form action="../cart/add.php" method="POST">

                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label><strong>Quantity:</strong></label>
                <input type="number" name="qty" value="1" min="1" required>
                <button type="submit" style="padding:10px 20px; background:#28a745; color:white;">
                    Add to Cart
                </button>
            </form>

        <?php else: ?>

            <!-- Disabled button for guests -->
            <button disabled style="padding:10px 20px; background:gray; color:white; cursor:not-allowed;">
                Add to Cart (Login Required)
            </button>

            <p><a href="../user/login.php" style="color:blue;">Click here to Login</a></p>

        <?php endif; ?>
    </div>
</section>

</body>
</html>
