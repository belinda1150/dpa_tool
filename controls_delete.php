<?php
/**
 * DPA Tool - Delete Control
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('DPO');

$org_id = get_current_org_id();

// Get control ID
$control_id = intval($_GET['id'] ?? 0);

if (!$control_id) {
    set_flash_message('Invalid control ID', 'error');
    redirect('controls.php');
}

// Get control details
$control_query = "SELECT * FROM controls WHERE control_id = ? AND org_id = ?";
$control_stmt = db_query($control_query, [$control_id, $org_id]);
$control = db_fetch_one($control_stmt);

if (!$control) {
    set_flash_message('Control not found', 'error');
    redirect('controls.php');
}

// Check if control is in use
$usage_query = "SELECT COUNT(*) as count FROM risk_controls WHERE control_id = ?";
$usage_stmt = db_query($usage_query, [$control_id]);
$usage = db_fetch_one($usage_stmt);

if ($usage['count'] > 0) {
    set_flash_message('Cannot delete control - it is linked to ' . $usage['count'] . ' risk(s)', 'error');
    redirect('controls.php');
}

// Delete control
$delete_query = "DELETE FROM controls WHERE control_id = ? AND org_id = ?";
$stmt = db_query($delete_query, [$control_id, $org_id]);

if ($stmt) {
    log_audit($org_id, get_current_user_id(), 'control', $control_id, 'delete');
    set_flash_message('Control deleted successfully', 'success');
} else {
    set_flash_message('Failed to delete control', 'error');
}

redirect('controls.php');
?>
