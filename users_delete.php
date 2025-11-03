<?php
/**
 * DPA Tool - Delete/Deactivate User
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin');

$org_id = get_current_org_id();
$current_user_id = get_current_user_id();

// Get user ID
$user_id = intval($_GET['id'] ?? 0);

if (!$user_id) {
    set_flash_message('Invalid user ID', 'error');
    redirect('users.php');
}

// Prevent deleting yourself
if ($user_id == $current_user_id) {
    set_flash_message('You cannot deactivate your own account', 'error');
    redirect('users.php');
}

// Get user details
$user = get_user_by_id($user_id);

if (!$user || $user['org_id'] != $org_id) {
    set_flash_message('User not found', 'error');
    redirect('users.php');
}

// Deactivate user
if (delete_user($user_id)) {
    set_flash_message('User deactivated successfully', 'success');
} else {
    set_flash_message('Failed to deactivate user', 'error');
}

redirect('users.php');
?>
