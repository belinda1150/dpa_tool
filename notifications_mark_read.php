<?php
/**
 * DPA Tool - Mark Notification as Read
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get notification ID
$notification_id = intval($_GET['id'] ?? 0);

if (!$notification_id) {
    set_flash_message('Invalid notification ID', 'error');
    redirect('notifications.php');
}

// Mark as read (verify ownership first)
$query = "UPDATE notifications SET is_read = 1, read_at = NOW()
          WHERE notification_id = ? AND user_id = ? AND org_id = ?";
$stmt = db_query($query, [$notification_id, $user_id, $org_id]);

if ($stmt) {
    set_flash_message('Notification marked as read', 'success');
} else {
    set_flash_message('Failed to mark notification as read', 'error');
}

// Redirect back
$redirect = $_GET['redirect'] ?? 'notifications.php';
redirect($redirect);
?>
