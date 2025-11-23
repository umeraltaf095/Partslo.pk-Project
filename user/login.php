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

    /* Page title like index.php header */
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

<!-- SAME NAVBAR AS OTHER PAGES -->
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

  <h2>Login</h2>

  <?php if ($message): ?>
      <p class="msg"><?= htmlspecialchars($message) ?></p>
  <?php endif; ?>

  <?php if (isset($_GET['registered'])): ?>
      <p class="msg" style="background:#e6ffef;border-left:4px solid green;">
         Account created successfully. Please login.
      </p>
  <?php endif; ?>

  <form method="post">

    <label>Email</label>
    <input type="email" name="email" required>

    <label>Password</label>
    <input type="password" name="password" required>

    <button type="submit">Login</button>

    <p class="link">Don't have an account? <a href="register.php">Register</a></p>
  </form>
</div>

</body>
</html>
