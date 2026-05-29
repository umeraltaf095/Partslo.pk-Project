<?php 
session_start();
require_once "../includes/db_connect.php";  
$message = "";  

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $phone   = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password_raw = $_POST["password"] ?? "";
    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    if (!empty($email)) {

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = "Email already registered!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address) 
                                   VALUES (?, ?, ?, ?, ?)");

            if ($stmt->execute([$name, $email, $password, $phone, $address])) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Registration failed!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration - PartsLo.pk</title>
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
        overflow-y: auto;
    }

    .info-side {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 60px;
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        color: white;
        text-align: center;
    }

    .info-side h2 {
        font-size: 42px;
        margin-bottom: 20px;
        font-weight: 700;
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .info-side p {
        font-size: 20px;
        line-height: 1.6;
        max-width: 80%;
        opacity: 0.95;
    }

    .auth-box {
        width: 100%;
        max-width: 450px;
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border: 1px solid #eaeaea;
        margin: auto;
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
        padding: 12px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 15px;
        transition: border-color 0.3s;
    }

    .auth-box input:focus {
        border-color: #11998e;
        outline: none;
        box-shadow: 0 0 0 3px rgba(17, 153, 142, 0.1);
    }

    .auth-box button {
        width: 100%;
        padding: 14px;
        background: #11998e;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.3s, transform 0.1s;
        margin-top: 10px;
    }

    .auth-box button:hover {
        background: #0e8074;
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
        color: #11998e;
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
            <h2>Create an Account</h2>
            <p class="subtitle">Fill in the details below to get started.</p>

            <?php if (!empty($message)) : ?>
                <div class="msg"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="John Doe" required>

                <label>Email Address</label>
                <input type="email" name="email" placeholder="john@example.com" required>

                <label>Password</label>
                <input type="password" name="password" placeholder="Create a password" required>

                <label>Phone Number</label>
                <input type="text" name="phone" placeholder="Your phone number" required>

                <label>Shipping Address</label>
                <input type="text" name="address" placeholder="Your full address" required>

                <button type="submit">Register Account</button>

                <div class="link">Already have an account? <a href="login.php">Sign in instead</a></div>
            </form>
        </div>
    </div>
    <div class="info-side">
        <h2>Join PartsLo.pk Today!</h2>
        <p>Register yourself to get the best moto parts at unbeatable prices. Create an account to unlock exclusive deals, manage your orders, and track your purchases easily.</p>
    </div>
</div>

</body>
</html>
