<?php
/**
 * DPA Tool - Mark All Notifications as Read
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Mark all unread notifications as read
$query = "UPDATE notifications SET is_read = 1, read_at = NOW()
          WHERE user_id = ? AND org_id = ? AND is_read = 0";
$stmt = db_query($query, [$user_id, $org_id]);

if ($stmt) {
    set_flash_message('All notifications marked as read', 'success');
} else {
    set_flash_message('Failed to mark notifications as read', 'error');
}

redirect('notifications.php');
?>
