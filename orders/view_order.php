<?php
require_once "../includes/auth.php";
require_once "../includes/db_connect.php";
require_login();

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT o.*, u.name AS user_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo "Order not found or you don't have permission.";
    exit;
}

$items_query = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items_query->execute([$id]);
$items = $items_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Order <?= $order['order_number'] ?> - PartsLo.pk</title>

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

/* ⭐ MAIN CONTAINER ⭐ */
.order-box {
    max-width: 900px;
    margin: 30px auto;
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.15);
}

/* ⭐ HEADING ⭐ */
.section-title {
    margin-top: 20px;
    font-size: 20px;
    border-bottom: 2px solid #eee;
    padding-bottom: 5px;
}

/* ⭐ ORDER ITEMS TABLE ⭐ */
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

/* ⭐ BUTTONS ⭐ */
.btn-dark {
    padding: 8px 12px;
    border-radius: 5px;
    background: #222;
    color: #fff;
    text-decoration: none;
}
.btn-dark:hover { background: #000; }

.btn-danger {
    padding: 8px 12px;
    background: #d00000;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
}
.btn-danger:hover { background: #a00000; }

</style>

</head>

<body>

<!-- ⭐ TOP NAV BAR ⭐ -->
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
    <h1 style="text-align:center; margin-top:20px;">Order <?= $order['order_number'] ?></h1>
</header>

<div class="order-box">

    <h2 class="section-title">Order Details</h2>

    <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
    <p><strong>Total:</strong> Rs <?= number_format($order['total_amount'], 2) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
    <p><strong>Address:</strong><br><?= nl2br(htmlspecialchars($order['address'])) ?></p>

    <h2 class="section-title">Items</h2>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td>
                    <img src="/partslo/assets/images/<?= htmlspecialchars($it['image']) ?>" 
                         style="height:50px;margin-right:8px;">
                    <?= htmlspecialchars($it['name']) ?>
                </td>

                <td><?= intval($it['quantity']) ?></td>
                <td>Rs <?= number_format($it['total_price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ⭐ CONDITIONAL BUTTONS ⭐ -->
    <div style="margin-top:20px;">
        <?php if ($order['status'] === 'Pending'): ?>
            <a href="/partslo/orders/cancel.php?id=<?= $order['id'] ?>" 
               class="btn-danger">Cancel Order</a>
        <?php endif; ?>

        <?php if ($order['status'] === 'Delivered'): ?>
           <a href="#" class="btn-dark" onclick="openFeedbackModal()">Write Feedback</a>

        <?php endif; ?>
    </div>

</div>
<!-- ⭐ FEEDBACK MODAL ⭐ -->
<div id="feedbackModal" class="modal-overlay">
    <div class="modal-box">
        <h2>Write Feedback</h2>

        <form method="post" action="/partslo/orders/feedback.php?id=<?= $order['id'] ?>">
            
            <!-- Product Select -->
            <label>Select Product</label>
            <select name="product_id" required>
                <?php foreach ($items as $it): ?>
                    <option value="<?= intval($it['product_id']) ?>">
                        <?= htmlspecialchars($it['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Rating -->
            <label>Rating (1-5)</label>
            <input type="number" name="rating" min="1" max="5" value="5" required>

            <!-- Comment -->
            <label>Comment</label>
            <textarea name="comment" placeholder="Write your feedback..." required></textarea>

            <button type="submit" class="btn-dark" style="width:100%;">Submit Feedback</button>
        </form>

        <button class="close-btn" onclick="closeFeedbackModal()">Close</button>
    </div>
</div>

<style>
/* ⭐ MODAL OVERLAY ⭐ */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(2px);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

/* ⭐ MODAL BOX ⭐ */
.modal-box {
    background: white;
    width: 400px;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(0,0,0,0.25);
}

.modal-box h2 {
    margin-top: 0;
    margin-bottom: 15px;
}

.modal-box label {
    margin-top: 10px;
    font-weight: bold;
    display: block;
}

.modal-box input,
.modal-box select,
.modal-box textarea {
    width: 100%;
    padding: 10px;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.close-btn {
    margin-top: 15px;
    width: 100%;
    background: #d00000;
    color: white;
    padding: 10px;
    border-radius: 5px;
    border: none;
}
.close-btn:hover { background: #a00000; }
</style>

<script>
function openFeedbackModal(){
    document.getElementById('feedbackModal').style.display = 'flex';
}
function closeFeedbackModal(){
    document.getElementById('feedbackModal').style.display = 'none';
}
</script>


</body>
</html>
