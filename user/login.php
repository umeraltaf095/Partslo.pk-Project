<?php
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
        // set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: /partslo/index.php');   // <--- REDIRECT UPDATED
        exit;
    } else {
        $message = "Invalid email or password.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login - PartsLo</title>
  <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
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

    <p class="link">Don't have account? <a href="register.php">Register</a></p>
  </form>
</div>
</body>
</html>
