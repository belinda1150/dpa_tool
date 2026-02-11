<?php
/**
 * DPA Tool - Mandatory Password Reset Page
 * Version: 1.0
 * Date: October 2025
 *
 * Forces users with password_reset_required flag to reset their password
 * before accessing the system.
 */

require_once 'config/config.php';
require_once 'includes/auth.php';

// Must be logged in to reset password
if (!is_logged_in()) {
    redirect('index.php');
}

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Check if password reset is required
$query = "SELECT password_reset_required FROM users WHERE user_id = ?";
$stmt = db_query($query, [$user_id]);
$user = db_fetch_one($stmt);

// If password reset not required, redirect to dashboard
if (!$user || $user['password_reset_required'] == 0) {
    redirect('dashboard.php');
}

// Handle password reset submission
$error_message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required';
    } elseif (strlen($new_password) < PASSWORD_MIN_LENGTH) {
        $error_message = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Passwords do not match';
    } else {
        // Hash the new password with bcrypt
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Update user's password and clear the reset flag
        $update_query = "UPDATE users SET password = ?, password_reset_required = 0 WHERE user_id = ?";
        $update_stmt = db_query($update_query, [$hashed_password, $user_id]);

        if ($update_stmt) {
            // Log the password change
            log_audit($org_id, $user_id, 'user', $user_id, 'update', null, ['action' => 'mandatory_password_reset']);

            // Clear the session flag to prevent redirect loop
            unset($_SESSION['dpa_password_reset_required']);

            // Set success message
            set_flash_message('Password successfully reset. You can now access the system.', 'success');

            // Redirect to dashboard
            redirect('dashboard.php');
        } else {
            $error_message = 'Failed to reset password. Please try again.';
        }
    }
}

// Get user name for display
$user_info = get_current_dpa_user();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo APP_NAME; ?> - Password Reset Required</title>
  <script src="assets/js/theme.js"></script>
  <link href="assets/css/theme-variables.css" rel="stylesheet" />
  <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,700' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="assets/css/css/all.min.css">
  <link rel="stylesheet" href="assets/css/css/v4-shims.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/5.0.0/normalize.min.css">
  <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
  <div class="wrapper">
    <form class="login" method="post" action="password_reset.php">
      <div class="login-header">
        <p class="title login-title">Password Reset Required</p>

        <?php if (!empty($error_message)): ?>
          <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <p style="color: #666; font-size: 14px; margin: 15px 0;">
          Hello <?php echo htmlspecialchars($user_info['first_name']); ?>,<br>
          For security reasons, you must reset your password before continuing.
        </p>
      </div>

      <input type="password"
             placeholder="New Password (min <?php echo PASSWORD_MIN_LENGTH; ?> characters)"
             name="new_password"
             autofocus
             required />
      <i class="fa fa-lock"></i>

      <input type="password"
             placeholder="Confirm New Password"
             name="confirm_password"
             required />
      <i class="fa fa-lock"></i>

      <button type="submit">
        <span class="state">Reset Password</span>
      </button>

      <div style="margin-top: 15px; text-align: center;">
        <a href="logout.php" style="color: #666; font-size: 13px; text-decoration: none;">
          <i class="fa fa-sign-out"></i> Logout
        </a>
      </div>
    </form>
  </div>
</body>
</html>
