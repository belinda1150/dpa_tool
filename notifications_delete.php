<?php
/**
 * DPA Tool - Delete Notification
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

// Delete notification (verify ownership first)
$query = "DELETE FROM notifications
          WHERE notification_id = ? AND user_id = ? AND org_id = ?";
$stmt = db_query($query, [$notification_id, $user_id, $org_id]);

if ($stmt) {
    set_flash_message('Notification deleted', 'success');
} else {
    set_flash_message('Failed to delete notification', 'error');
}

redirect('notifications.php');
?>
