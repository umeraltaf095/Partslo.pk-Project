<?php
session_start();
require_once "../includes/db_connect.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Let MySQL compare hashed password using PASSWORD()
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? AND password = PASSWORD(?)");
    $stmt->execute([$username, $password]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['username'];

        header("Location: /partslo/admin/dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Login - PartsLo.pk</title>
<link rel="stylesheet" href="../assets/css/style.css">

<style>
.login-box {
    max-width: 400px;
    margin: 80px auto;
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0px 0px 12px rgba(0,0,0,0.15);
}
.login-box input {
    width: 100%;
    padding: 12px;
    margin-top: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 6px;
}
.btn-login {
    width: 100%;
    padding: 12px;
    background: #111;
    color: #fff;
    border: none;
    border-radius: 6px;
    margin-top: 15px;
    cursor: pointer;
}
.btn-login:hover {
    background: #000;
}
.error-msg {
    color: red;
    margin-bottom: 10px;
}
</style>
</head>
<body>

<h1 style="text-align:center;">Admin Login</h1>

<div class="login-box">

    <?php if($error): ?>
        <p class="error-msg"><?= $error ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="username" placeholder="Admin Username" required>
        <input type="password" name="password" placeholder="Password" required>

        <button class="btn-login" type="submit">Login</button>
    </form>

</div>

</body>
</html>
