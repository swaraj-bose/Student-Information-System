<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// public/index.php

session_start();
require_once __DIR__ . '/../config/db.php';

$errors = [];

// 1) Registration Handler
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $name     = trim($_POST['name'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $role     = $_POST['role'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $subject  = trim($_POST['subject'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (!$name || !$mobile || !$role || !$username || !$password || !$confirm) {
        $errors[] = 'All fields are required for registration.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors[] = 'Mobile number must be 10 digits.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                $errors[] = 'Username already taken.';
            } else {
                // Insert new user
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins = $pdo->prepare("
                    INSERT INTO users (name, mobile, role, username, subject, password_hash)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$name, $mobile, $role, $username, $subject, $hash]);
                header('Location: index.php?registered=1');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// 2) Login Handler
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $errors[] = 'Both username and password are required to login.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role']     = $user['role'];
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid credentials.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>StudentIS – Login / Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>

<?php if (isset($_GET['registered'])): ?>
  <p class="success">Registration successful! You can now log in.</p>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="error">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="container">
  <!-- Login Form -->
  <form method="post" action="">
    <h2>Login</h2>
    <input type="hidden" name="action" value="login">
    <label>
      Username
      <input type="text" name="username" required>
    </label>
    <label>
      Password
      <input type="password" name="password" required>
    </label>
    <button type="submit">Log In</button>
  </form>

  <!-- Registration Form -->
  <form method="post" action="">
    <h2>Register</h2>
    <input type="hidden" name="action" value="register">
    <label>
      Name
      <input type="text" name="name" required>
    </label>
    <label>
      Mobile Number
      <input type="text" name="mobile" required>
    </label>
    <label>
      Role
      <select name="role" required>
        <option value="">-- Select --</option>
        <option value="student">Student</option>
        <option value="teacher">Teacher</option>
      </select>
    </label>
    <label>
      Username
      <input type="text" name="username" required>
    </label>
    <label>
      Subject
      <input type="text" name="subject">
    </label>
    <label>
      Password
      <input type="password" name="password" required>
    </label>
    <label>
      Confirm Password
      <input type="password" name="confirm_password" required>
    </label>
    <button type="submit">Register</button>
  </form>
</div>

<script src="js/scripts.js" defer></script>
</body>
</html>

