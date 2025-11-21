<?php
session_start();
require_once "../includes/db_connect.php";
header('Content-Type: application/json');

// protect
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

// read json
$body = json_decode(file_get_contents('php://input'), true);
if (!$body || !isset($body['order_id']) || !isset($body['status'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$orderId = intval($body['order_id']);
$status = trim($body['status']);

// allowed statuses - must match DB allowed values (adjust if different)
$allowedStatuses = ['Pending','Accepted','Rejected','Packed','Shipped','Out for Delivery','Delivered'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
$stmt->execute([$status, $orderId]);


    // prepare response label & class for immediate UI update
    $status_label = $status;
    // create a class-friendly value
    $status_class = str_replace(' ', '', $status);

    echo json_encode(['success' => true, 'status_label' => $status_label, 'status_class' => $status_class]);
    exit;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}
