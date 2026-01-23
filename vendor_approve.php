<?php
/**
 * DPA Tool - Vendor Status Management
 * Handle approve, activate, suspend actions
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

$vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action = isset($_GET['action']) ? sanitize_input($_GET['action']) : '';

if (!$vendor_id || !in_array($action, ['approve', 'activate', 'suspend'])) {
    set_flash_message('Invalid request.', 'error');
    redirect('vendor_list.php');
}

// Fetch vendor
$query = "SELECT * FROM vendors WHERE vendor_id = ? AND org_id = ?";
$stmt = db_query($query, [$vendor_id, $org_id]);
$vendor = db_fetch_one($stmt);

if (!$vendor) {
    set_flash_message('Vendor not found.', 'error');
    redirect('vendor_list.php');
}

// Check permissions for approve action
if ($action === 'approve' && !has_permission('DPO') && !is_admin()) {
    set_flash_message('You do not have permission to approve vendors.', 'error');
    redirect('vendor_view.php?id=' . $vendor_id);
}

// Validate action based on current status
$valid_transitions = [
    'approve' => ['pending_review'],
    'activate' => ['approved', 'suspended'],
    'suspend' => ['active']
];

if (!in_array($vendor['status'], $valid_transitions[$action])) {
    set_flash_message('Invalid status transition.', 'error');
    redirect('vendor_view.php?id=' . $vendor_id);
}

// Determine new status
$new_status = [
    'approve' => 'approved',
    'activate' => 'active',
    'suspend' => 'suspended'
][$action];

// Update status
$update_fields = "status = ?";
$params = [$new_status];

if ($action === 'approve') {
    $update_fields .= ", approved_by = ?, approved_at = NOW()";
    $params[] = $user_id;
}

$params[] = $vendor_id;
$params[] = $org_id;

$query = "UPDATE vendors SET $update_fields WHERE vendor_id = ? AND org_id = ?";
$stmt = db_query($query, $params);

if ($stmt) {
    // Log audit
    log_audit($org_id, $user_id, 'vendor', $vendor_id, $action);

    // Notify vendor creator
    if ($vendor['created_by'] != $user_id) {
        $action_labels = [
            'approve' => 'approved',
            'activate' => 'activated',
            'suspend' => 'suspended'
        ];

        create_notification(
            $org_id,
            $vendor['created_by'],
            'vendor_status_change',
            'Vendor Status Updated',
            "Vendor '{$vendor['vendor_name']}' has been {$action_labels[$action]}.",
            'vendor',
            $vendor_id,
            "vendor_view.php?id=$vendor_id",
            'normal'
        );
    }

    $messages = [
        'approve' => 'Vendor has been approved.',
        'activate' => 'Vendor has been activated.',
        'suspend' => 'Vendor has been suspended.'
    ];

    set_flash_message($messages[$action], 'success');
} else {
    set_flash_message('Error updating vendor status.', 'error');
}

redirect('vendor_view.php?id=' . $vendor_id);
