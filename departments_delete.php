<?php
/**
 * DPA Tool - Delete Department
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin');

$org_id = get_current_org_id();

// Get department ID
$dept_id = intval($_GET['id'] ?? 0);

if (!$dept_id) {
    set_flash_message('Invalid department ID', 'error');
    redirect('departments.php');
}

// Get department details
$dept_query = "SELECT * FROM departments WHERE dept_id = ? AND org_id = ?";
$dept_stmt = db_query($dept_query, [$dept_id, $org_id]);
$department = db_fetch_one($dept_stmt);

if (!$department) {
    set_flash_message('Department not found', 'error');
    redirect('departments.php');
}

// Delete department (CASCADE will handle foreign key relationships)
// Note: Users in this department will have their dept_id set to NULL
$delete_query = "DELETE FROM departments WHERE dept_id = ? AND org_id = ?";
$stmt = db_query($delete_query, [$dept_id, $org_id]);

if ($stmt) {
    log_audit($org_id, get_current_user_id(), 'department', $dept_id, 'delete');
    set_flash_message('Department deleted successfully', 'success');
} else {
    set_flash_message('Failed to delete department', 'error');
}

redirect('departments.php');
?>
