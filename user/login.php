<?php
session_start();
require_once "../includes/db_connect.php";
require_once "../includes/auth.php";

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password, name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];

        header('Location: /partslo/index.php');   
        exit;

    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - PartsLo.pk</title>
<link rel="stylesheet" href="../assets/css/auth.css">

<style>
    body {
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        flex-direction: column;
        height: 100vh;
        background-color: #f4f7f6;
    }
    .navbar {
        background: #222;
        padding: 15px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .navbar a {
        color: white;
        margin-right: 20px;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s;
    }
    .navbar a:hover {
        color: #ddd;
    }
    .navbar-right a:last-child {
        margin-right: 0;
    }

    .split-container {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    .form-side {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 40px;
        background: #ffffff;
    }

    .info-side {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 60px;
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        color: white;
        text-align: center;
    }

    .info-side h2 {
        font-size: 42px;
        margin-bottom: 20px;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .info-side p {
        font-size: 20px;
        line-height: 1.6;
        max-width: 80%;
        opacity: 0.9;
    }

    .auth-box {
        width: 100%;
        max-width: 450px;
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border: 1px solid #eaeaea;
    }

    .auth-box h2 {
        margin-top: 0;
        margin-bottom: 10px;
        color: #333;
        font-size: 28px;
        text-align: left;
    }
    
    .auth-box p.subtitle {
        color: #777;
        margin-bottom: 30px;
        font-size: 15px;
    }

    .auth-box label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #444;
        font-size: 14px;
    }

    .auth-box input {
        width: 100%;
        padding: 14px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .auth-box input:focus {
        border-color: #2a5298;
        outline: none;
        box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1);
    }

    .auth-box button {
        width: 100%;
        padding: 14px;
        background: #1e3c72;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.3s, transform 0.1s;
    }

    .auth-box button:hover {
        background: #2a5298;
    }
    
    .auth-box button:active {
        transform: scale(0.98);
    }

    .auth-box .link {
        text-align: center;
        margin-top: 25px;
        font-size: 15px;
        color: #666;
    }

    .auth-box .link a {
        color: #1e3c72;
        text-decoration: none;
        font-weight: 600;
    }

    .auth-box .link a:hover {
        text-decoration: underline;
    }

    .msg {
        background: #fee2e2;
        color: #b91c1c;
        padding: 12px;
        border-radius: 6px;
        margin-bottom: 20px;
        font-size: 14px;
        border-left: 4px solid #ef4444;
    }
    .msg.success {
        background: #e6ffef;
        color: #047857;
        border-left: 4px solid #10b981;
    }
</style>
</head>

<body>

<div class="navbar">
    <div class="navbar-left">
        <a href="/partslo/index.php">🏠 Home</a>
    </div>
    <div class="navbar-right">
        <a href="/partslo/admin/admin_login.php" style="color:#80bfff;">Admin Login</a>
    </div>
</div>

<div class="split-container">
    <div class="form-side">
        <div class="auth-box">
            <h2>Welcome Back</h2>
            <p class="subtitle">Please enter your details to sign in.</p>

            <?php if ($message): ?>
                <div class="msg"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div class="msg success">
                   Account created successfully. Please login.
                </div>
            <?php endif; ?>

            <form method="post">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>

                <button type="submit">Sign In</button>

                <div class="link">Don't have an account? <a href="register.php">Register now</a></div>
            </form>
        </div>
    </div>
    <div class="info-side">
        <h2>PartsLo.pk</h2>
        <p>Login to your account to track your orders, save your favorite moto parts, and enjoy a seamless checkout experience. We provide the best quality parts for your ride.</p>
    </div>
</div>

</body>
</html>
