<?php
/**
 * DPA Tool - Login Page
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard.php');
}

// Handle login submission
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login_user($email, $password)) {
        redirect('dashboard.php');
    } else {
        $error_message = 'Invalid email or password';
    }
}

// Get status message from URL
if (isset($_GET['status'])) {
    $error_message = $_GET['status'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - Login</title>
  <link href='http://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel="stylesheet" href="assets/css/login.css">
  <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
</head>

<body>
  <div class="wrapper">
    <form class="login" method="post" action="index.php">
      <div class="login-header">
        <img src="assets/img/logo.png" class="login-logo">
        <p class="title login-title">
          <?php echo APP_NAME; ?>
        </p>
        <?php if (!empty($error_message)): ?>
          <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
      </div>

      <input type="text" placeholder="Email" name="email" autofocus required />
      <i class="fa fa-user"></i>
      <input type="password" placeholder="Password" name="password" required />
      <i class="fa fa-key"></i>

      <button type="submit">
        <span class="state">Login</span>
      </button>
    </form>
  </div>
</body>
</html>
