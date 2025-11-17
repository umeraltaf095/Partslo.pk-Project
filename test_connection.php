<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/db_connect.php';

try {
    $stmt = $pdo->query("SELECT DATABASE()"); // check connected DB
    $db = $stmt->fetchColumn();
    echo "Connected to database: " . $db;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo '<pre>'; print_r($tables); echo '</pre>';
