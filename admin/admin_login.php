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
            background: linear-gradient(135deg, #4b134f 0%, #c94b4b 100%);
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
            opacity: 0.95;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            border: 1px solid #eaeaea;
        }

        .login-box h2 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            font-size: 28px;
            text-align: left;
        }
        
        .login-box p.subtitle {
            color: #777;
            margin-bottom: 30px;
            font-size: 15px;
        }

        .login-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }

        .login-box input {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .login-box input:focus {
            border-color: #c94b4b;
            outline: none;
            box-shadow: 0 0 0 3px rgba(201, 75, 75, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: #222;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s, transform 0.1s;
        }

        .btn-login:hover {
            background: #444;
        }
        
        .btn-login:active {
            transform: scale(0.98);
        }

        .error-msg {
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
        <a href="/partslo/user/login.php">User Login</a>
        <a href="/partslo/user/register.php">Register</a>
    </div>
</div>

<div class="split-container">
    <div class="form-side">
        <div class="login-box">
            <h2>Admin Portal</h2>
            <p class="subtitle">Enter your credentials to access the dashboard.</p>

            <?php if($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <label>Username</label>
                <input type="text" name="username" placeholder="Admin Username" required>
                
                <label>Password</label>
                <input type="password" name="password" placeholder="Password" required>

                <button class="btn-login" type="submit">Sign In</button>
            </form>
        </div>
    </div>
    <div class="info-side">
        <h2>PartsLo Admin Control Panel</h2>
        <p>Secure login for PartsLo.pk administrators. Manage inventory, process orders, and maintain the platform's seamless operation from here. Authorized personnel only.</p>
    </div>
</div>

</body>
</html>
