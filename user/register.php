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
        /* SAME NAVBAR STYLE AS index.php */
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

        /* Store title styling */
        .page-title {
            text-align: center;
            margin-top: 25px;
            font-size: 32px;
            font-weight: bold;
            color: #222;
        }
    </style>
</head>
<body>

<!-- SAME NAVBAR AS LOGIN + INDEX -->
<div class="navbar">

    <div class="navbar-left">
        <a href="/partslo/index.php">🏠 Home</a>
    </div>

    <div class="navbar-right">
       
        
        <a href="/partslo/admin/admin_login.php" style="color:#80bfff;">Admin Login</a>
    </div>

</div>

<h1 class="page-title">PartsLo.pk</h1>

<div class="auth-container">
    <h2>Create Account</h2>

    <?php if (!empty($message)) : ?>
        <p class="msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Phone</label>
        <input type="text" name="phone" required>

        <label>Address</label>
        <input type="text" name="address" required>

        <button type="submit">Register</button>

        <p class="link">Already have an account? <a href="login.php">Login</a></p>
    </form>
</div>

</body>
</html>
