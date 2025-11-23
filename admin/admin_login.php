<?php
session_start();
require_once "../includes/db_connect.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - PartsLo.pk</title>
    <link rel="stylesheet" href="../assets/css/style.css">

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

        .page-title {
            text-align: center;
            margin-top: 30px;
            font-size: 32px;
            font-weight: bold;
            color: #222;
        }

        .login-box {
            max-width: 400px;
            margin: 40px auto;
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
            background: #222;
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
            text-align: center;
        }
    </style>
</head>
<body>

<!-- NAVBAR (Same as User Pages) -->
<div class="navbar">

    <div class="navbar-left">
        <a href="/partslo/index.php">🏠 Home</a>
    </div>

    <div class="navbar-right">
        <a href="/partslo/user/login.php">User Login</a>
        <a href="/partslo/user/register.php">Register</a>
       
    </div>

</div>

<h1 class="page-title">PartsLo.pk</h1>

<div class="login-box">

    <?php if($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="username" placeholder="Admin Username" required>
        <input type="password" name="password" placeholder="Password" required>

        <button class="btn-login" type="submit">Login</button>
    </form>

</div>

</body>
</html>
