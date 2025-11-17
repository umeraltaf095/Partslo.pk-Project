<?php
// auth.php - start session + helper functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: /partslo/user/login.php');
        exit;
    }
}

// Get logged in user data from DB (returns associative array)
function current_user(PDO $pdo) {
    if (!is_logged_in()) return null;
    $stmt = $pdo->prepare("SELECT id, name, email, phone, address FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
