<?php
session_start();
require_once "../includes/db_connect.php";

// protect
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

/* ----------------------------------------------------------
   AJAX REQUEST: LOAD ONLY THE MODAL CONTENT FOR ONE ORDER
   (This block returns only the modal inner HTML and exits)
-----------------------------------------------------------*/
if (isset($_GET['view'])) {

    $orderId = intval($_GET['view']);

    $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email
                           FROM orders o
                           LEFT JOIN users u ON o.user_id = u.id
                           WHERE o.id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo "<div style='padding:20px'>Order not found.</div>";
        exit;
    }

    // get order items (adjust column names if your order_items uses different names)
    $itStmt = $pdo->prepare("SELECT oi.*, p.name as product_name 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
    $itStmt->execute([$orderId]);
    $items = $itStmt->fetchAll(PDO::FETCH_ASSOC);

    // load status list (adjust if DB enum differs)
    $allowedStatuses = ['Pending','Accepted','Rejected','Packed','Shipped','Out for Delivery','Delivered'];

    ob_start();
    ?>
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h3 style="margin:0">Order #<?= htmlspecialchars($order['order_number']) ?></h3>
        <button class="button" onclick="closeModal()">Close</button>
    </div>

    <div class="order-info-box" style="margin-top:12px;padding:12px;background:#f7f7f7;border-radius:6px;">
        <h4 style="margin:6px 0;">Customer Details</h4>
        <p style="margin:4px 0;"><strong>Name:</strong> <?= htmlspecialchars($order['user_name'] ?? 'Guest') ?></p>
        <p style="margin:4px 0;"><strong>Email:</strong> <?= htmlspecialchars($order['user_email']) ?></p>
        <p style="margin:4px 0;"><strong>Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
        <p style="margin:4px 0;"><strong>Address:</strong><br><?= nl2br(htmlspecialchars($order['address'])) ?></p>
    </div>

    <h4 style="margin-top:12px;">Order Items</h4>
    <table class="order-items" style="width:100%;border-collapse:collapse;margin-top:8px;">
        <thead>
            <tr>
                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Product</th>
                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Qty</th>
                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Unit</th>
                <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;"><?= htmlspecialchars($it['product_name'] ?? ('Product #' . $it['product_id'])) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;"><?= intval($it['quantity'] ?? $it['qty'] ?? 0) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">Rs <?= number_format($it['unit_price'] ?? $it['price'] ?? 0,2) ?></td>
                <td style="padding:8px;border-bottom:1px solid #f0f0f0;">Rs <?= number_format($it['total_price'] ?? ($it['quantity'] * ($it['unit_price'] ?? $it['price'] ?? 0)),2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
            <strong>Total Amount:</strong> Rs <?= number_format($order['total_amount'],2) ?><br>
            <small class="small">Payment: <?= htmlspecialchars($order['payment_method'] ?? '') ?></small>
        </div>

        <div style="min-width:320px;">
            <div class="form-row" style="display:flex;gap:8px;align-items:center;">
                <label for="status_select_<?= intval($order['id']) ?>" style="margin-right:6px;">Status</label>

                <select id="status_select_<?= intval($order['id']) ?>" style="padding:8px;border-radius:6px;border:1px solid #ccc;">
                    <?php foreach ($allowedStatuses as $st): ?>
                        <option value="<?= htmlspecialchars($st) ?>" <?= ($order['status'] === $st) ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                    <?php endforeach; ?>
                </select>

                <button class="button" onclick="updateStatus(<?= intval($order['id']) ?>)">Save</button>
            </div>
        </div>
    </div>

    <?php
    echo ob_get_clean();
    exit; // stop further rendering for AJAX
}

/* ----------------------------------------------------------
   NORMAL PAGE (NO VIEW PARAM)
-----------------------------------------------------------*/

// Fetch filter if provided
$filter = $_GET['status'] ?? '';
$allowedStatuses = ['Pending','Accepted','Rejected','Packed','Shipped','Out for Delivery','Delivered'];

// Build query to fetch orders for the list
if ($filter && in_array($filter, $allowedStatuses)) {
    $stmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email
                           FROM orders o
                           LEFT JOIN users u ON o.user_id = u.id
                           WHERE o.status = ?
                           ORDER BY o.created_at DESC");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("SELECT o.*, u.name as user_name, u.email as user_email
                         FROM orders o
                         LEFT JOIN users u ON o.user_id = u.id
                         ORDER BY o.created_at DESC");
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Admin - Manage Orders</title>
<link rel="stylesheet" href="../assets/css/style.css">

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

    /* Navbar */
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
        margin-left: 10px;
    }

    .navbar .nav-links a:hover {
        background: rgba(255,255,255,0.2);
    }

    /* container */
    .container { max-width:1200px; margin:40px auto; padding: 0 20px; }

    .page-header { margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
    .page-header h1 { font-size: 30px; margin: 0; color: #0f172a; }

    /* card */
    .card {
        width:100%;
        background: var(--card-bg);
        padding:25px;
        border-radius:12px;
        box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
        box-sizing: border-box;
    }

    .filter-form { display:flex; gap:10px; align-items:center; }
    .select { padding:10px 15px; border-radius:6px; border:1px solid #cbd5e1; font-family: inherit; font-size: 14px; outline: none; }
    .select:focus { border-color: var(--primary); }

    /* table */
    .table-responsive { overflow-x: auto; width: 100%; margin-top: 15px; }
    table { width:100%; border-collapse:collapse; min-width: 900px; }
    th, td { padding:15px 12px; border-bottom:1px solid var(--border-color); text-align:left; }
    th { background: #f8fafc; color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    tr:hover td { background-color: #f8fafc; }
    
    .button { padding:10px 16px; background: var(--primary); color:#fff; border:none; border-radius:6px; text-decoration:none; cursor:pointer; font-size:14px; font-weight:500; transition:all 0.2s; display:inline-block; }
    .button:hover { background: #3a0f3d; }

    /* status badges */
    .badge { padding:6px 12px; border-radius:20px; font-size: 12px; font-weight:600; display:inline-block; text-transform: uppercase; }
    .s-Pending { background:#fef3c7; color:#d97706; }
    .s-Accepted { background:#dbeafe; color:#2563eb; }
    .s-Rejected { background:#fee2e2; color:#dc2626; }
    .s-Packed { background:#f3e8ff; color:#9333ea; }
    .s-Shipped { background:#e0f2fe; color:#0284c7; }
    .s-OutForDelivery { background:#ffedd5; color:#ea580c; }
    .s-Delivered { background:#dcfce7; color:#16a34a; }

    /* modal */
    .modal-bg { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); display:none; justify-content:center; align-items:center; z-index:9999; backdrop-filter: blur(2px); }
    .modal { width:800px; max-width:95%; max-height: 90vh; overflow-y:auto; background:#fff; padding:30px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); border-top: 5px solid var(--primary); animation: fadeIn 0.2s ease-out; }
    
    .order-info-box { background:#f8fafc; padding:20px; border-radius:8px; border: 1px solid var(--border-color); margin: 20px 0; }
    .order-info-box h4 { margin: 0 0 10px 0; color: var(--primary); font-size: 18px; }
    
    .order-items { width:100%; border-collapse:collapse; margin-top:15px; min-width: unset; }
    .order-items th { background: transparent; border-bottom: 2px solid var(--border-color); }
    
    .form-row { display:flex; gap:12px; align-items:center; }
    .form-row select { padding:10px; border-radius:6px; border:1px solid #cbd5e1; font-family: inherit; font-size: 14px; outline: none; }
    
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width:880px){
        .navbar { flex-direction: column; gap: 15px; }
        .page-header { flex-direction: column; align-items: flex-start; }
    }
</style>

<script>
function openOrderModal(orderId){
    // fetch order details via AJAX GET to orders.php?view=<id>
    fetch('orders.php?view=' + orderId)
    .then(res => res.text())
    .then(html => {
        // insert modal content and show modal
        document.getElementById('modalContent').innerHTML = html;
        document.getElementById('orderModal').style.display = 'flex';
    }).catch(err => alert('Failed to load order details.'));
}

function closeModal(){
    document.getElementById('orderModal').style.display = 'none';
}

// update status via AJAX
function updateStatus(orderId){
    const sel = document.getElementById('status_select_'+orderId);
    if(!sel) { alert('Status selector not found'); return; }
    const newStatus = sel.value;
    if (!confirm('Change status to "' + newStatus + '"?')) return;

    fetch('update_order_status.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({order_id: orderId, status: newStatus})
    }).then(res => res.json())
    .then(json => {
        if (json.success) {
            // update status in table
            const statusCell = document.getElementById('status_cell_' + orderId);
            if (statusCell) statusCell.innerHTML = '<span class="badge s-' + json.status_class + '">' + json.status_label + '</span>';
            alert('Status updated.');
        } else {
            alert('Update failed: ' + (json.error || 'unknown'));
        }
    }).catch(err => alert('Request failed'));
}
</script>

</head>
<body>

<div class="navbar">
    <a href="/partslo/admin/dashboard.php" class="brand">PartsLo Admin</a>
    <div class="nav-links">
        <a href="/partslo/index.php" target="_blank">View Store</a>
        <a href="/partslo/admin/dashboard.php">Dashboard</a>
        <a href="/partslo/user/logout.php" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5;">Logout</a>
    </div>
</div>

<div class="container">
    <div class="page-header">
        <h1>Manage Orders</h1>
        <form method="GET" class="filter-form">
            <select name="status" class="select">
                <option value="">All Statuses</option>
                <?php foreach ($allowedStatuses as $st): ?>
                    <option value="<?= htmlspecialchars($st) ?>" <?= ($st === $filter) ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button" type="submit">Filter Results</button>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total Amount</th>
                        <th>Date Placed</th>
                        <th>Current Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--text-muted);">No orders found matching your criteria.</td></tr>
                    <?php else: ?>
                        <?php foreach($orders as $o):
                            $status_class = str_replace(' ', '', $o['status']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($o['id']) ?></td>
                            <td>
                                <a href="javascript:void(0)" style="font-weight:600; color:var(--primary); text-decoration:none;" onclick="openOrderModal(<?= intval($o['id']) ?>)"><?= htmlspecialchars($o['order_number']) ?></a>
                            </td>
                            <td><strong><?= htmlspecialchars($o['user_name'] ?? 'Guest') ?></strong></td>
                            <td><?= htmlspecialchars($o['phone']) ?></td>
                            <td style="font-weight:600;">Rs <?= number_format($o['total_amount'],2) ?></td>
                            <td><?= date('M d, Y g:i A', strtotime($o['created_at'])) ?></td>
                            <td id="status_cell_<?= intval($o['id']) ?>">
                                <span class="badge s-<?= htmlspecialchars($status_class) ?>"><?= htmlspecialchars($o['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- modal wrapper - content will be injected -->
<div class="modal-bg" id="orderModal" style="display:none;">
    <div class="modal" id="modalContent">
        <!-- content loaded dynamically -->
    </div>
</div>

</body>
</html>
