<?php 
require_once "../includes/db_connect.php";  
$message = "";  

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name    = trim($_POST["name"] ?? "");
    $email   = trim($_POST["email"] ?? "");
    $phone   = trim($_POST["phone"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $password_raw = $_POST["password"] ?? "";
    $password = password_hash($password_raw, PASSWORD_DEFAULT);

    // Run validation only if email is not empty
    if (!empty($email)) {

        // Check email already exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $message = "Email already registered!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");

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
<html>
<head>
    <title>User Registration</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-container">
    <h2>Create Account</h2>

    <?php if (!empty($message)) : ?>
        <p class="msg"><?= $message ?></p>
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
