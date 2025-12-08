<?php
/**
 * DPA Tool - Delete/Archive ROPA Entry
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();
require_permission('DPO'); // Only DPO can archive

$org_id = get_current_org_id();
$user_id = get_current_user_id();
$ropa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$ropa_id) {
    set_flash_message('Invalid ROPA entry.', 'error');
    redirect('ropa_list.php');
}

// Verify ownership
$query = "SELECT * FROM processing_activities WHERE ropa_id = ? AND org_id = ?";
$stmt = db_query($query, [$ropa_id, $org_id]);
$ropa = db_fetch_one($stmt);

if (!$ropa) {
    set_flash_message('ROPA entry not found.', 'error');
    redirect('ropa_list.php');
}

// Archive the entry (soft delete)
$query = "UPDATE processing_activities SET status = 'archived' WHERE ropa_id = ? AND org_id = ?";
$stmt = db_query($query, [$ropa_id, $org_id]);

if ($stmt) {
    log_audit($org_id, $user_id, 'ropa', $ropa_id, 'delete');
    set_flash_message('Processing activity archived successfully.', 'success');
} else {
    set_flash_message('Failed to archive processing activity.', 'error');
}

redirect('ropa_list.php');
?>
