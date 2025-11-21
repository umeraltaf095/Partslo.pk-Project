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

<style>
.top-bar {
    background: #111;
    color: #fff;
    padding: 12px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.top-bar a { color: #fff; text-decoration:none; margin-left:12px; }
.top-bar .store-name { color:#00c3ff; font-weight:bold; font-size:18px; }

.container { max-width:1100px; margin:28px auto; }
.card { background:#fff; padding:16px; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.08); }

.filter-row { display:flex; gap:10px; align-items:center; margin-bottom:12px; }
.select { padding:8px 10px; border-radius:6px; border:1px solid #ccc; }

/* table */
table { width:100%; border-collapse:collapse; table-layout:fixed; }
th, td { padding:10px 12px; border-bottom:1px solid #eee; text-align:left; overflow:hidden; white-space:nowrap; text-overflow:ellipsis; }
th:nth-child(1) { width:6%; }
th:nth-child(2) { width:18%; }
th:nth-child(3) { width:16%; }
th:nth-child(4) { width:12%; }
th:nth-child(5) { width:12%; }
th:nth-child(6) { width:12%; }
th:nth-child(7) { width:18%; }

.button { padding:7px 12px; background:#111; color:#fff; border-radius:6px; text-decoration:none; cursor:pointer; display:inline-block; }
.badge { padding:6px 10px; border-radius:6px; color:#fff; font-weight:600; display:inline-block; }

/* status colors (class names must match badge class generation) */
.s-Pending { background:#ffb84d; color:#111; }
.s-Accepted { background:#2db14a; }
.s-Rejected { background:#d00000; }
.s-Packed { background:#6c63ff; }
.s-Shipped { background:#00aaff; }
.s-OutForDelivery { background:#ff8000; } /* class uses no spaces */
.s-Delivered { background:#33cc66; }

/* modal */
.modal-bg { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); display:none; justify-content:center; align-items:center; z-index:999; }
.modal { width:880px; max-width:96%; background:#fff; padding:18px; border-radius:8px; max-height:90vh; overflow:auto; }
.modal h3 { margin-top:0; }
.order-items { width:100%; border-collapse:collapse; margin-top:10px; }
.order-items th, .order-items td { padding:8px; border-bottom:1px solid #eee; text-align:left; }
.form-row { display:flex; gap:12px; align-items:center; margin-top:10px; }
.form-row select, .form-row input { padding:8px 10px; border-radius:6px; border:1px solid #ccc; }
.small { font-size:13px; color:#666; }

.order-info-box { background:#fafafa; padding:12px; border-radius:6px; }
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

<div class="top-bar">
    <div class="store-name">PartsLo Admin</div>
    <div>
        <a href="/partslo/index.php">Home</a>
        <a href="/partslo/admin/dashboard.php">Dashboard</a>
        <a href="/partslo/user/logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 style="margin:0">Orders</h2>
            <div>
                <form method="GET" style="display:inline;">
                    <select name="status" class="select">
                        <option value="">All statuses</option>
                        <?php foreach ($allowedStatuses as $st): ?>
                            <option value="<?= htmlspecialchars($st) ?>" <?= ($st === $filter) ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button" type="submit">Filter</button>
                </form>
            </div>
        </div>

        <div style="margin-top:12px; overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Order Number</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Created</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="padding:20px;text-align:center;">No orders found.</td></tr>
                    <?php else: ?>
                        <?php foreach($orders as $o):
                            // build class-friendly status (no spaces)
                            $status_class = str_replace(' ', '', $o['status']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($o['id']) ?></td>
                            <td>
                                <a href="javascript:void(0)" onclick="openOrderModal(<?= intval($o['id']) ?>)"><?= htmlspecialchars($o['order_number']) ?></a>
                            </td>
                            <td><?= htmlspecialchars($o['user_name'] ?? 'Guest') ?></td>
                            <td><?= htmlspecialchars($o['phone']) ?></td>
                            <td>Rs <?= number_format($o['total_amount'],2) ?></td>
                            <td><?= htmlspecialchars($o['created_at']) ?></td>
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
