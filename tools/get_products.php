<?php
require_once "../includes/db_connect.php";


$category = $_GET['category'] ?? ''; // this will now be category slug
$keyword = $_GET['keyword'] ?? '';
$min = $_GET['min'] ?? 0;
$max = $_GET['max'] ?? 999999;

// Base SQL: join products with categories
$sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.price BETWEEN ? AND ?";
$params = [$min, $max];

// Filter by category slug if provided
if($category !== ''){
    $sql .= " AND c.slug = ?";
    $params[] = $category;
}

// Filter by keyword
if($keyword !== ''){
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$keyword%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($products);
