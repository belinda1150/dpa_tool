<?php
/**
 * View Cross-Border Transfer Details
 * Display complete information about a specific cross-border transfer
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get transfer ID from URL
$cb_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$cb_id) {
    set_flash_message('Invalid transfer ID.', 'error');
    redirect('crossborder_list.php');
}

// Fetch transfer details
$query = "SELECT cb.*,
          pa.activity_name, pa.ropa_id,
          s.safeguard_name, s.safeguard_description,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
          u.email as created_by_email
          FROM cross_border_transfers cb
          LEFT JOIN processing_activities pa ON cb.ropa_id = pa.ropa_id
          LEFT JOIN safeguards s ON cb.safeguard_id = s.safeguard_id
          LEFT JOIN users u ON cb.created_by = u.user_id
          WHERE cb.cb_id = ? AND cb.org_id = ?";

$stmt = db_query($query, [$cb_id, $org_id]);
$transfer = db_fetch_one($stmt);

if (!$transfer) {
    set_flash_message('Cross-border transfer not found.', 'error');
    redirect('crossborder_list.php');
}

// Status badge color
$status_colors = [
    'pending' => 'warning',
    'submitted' => 'info',
    'approved' => 'success',
    'blocked' => 'danger'
];
$status_color = $status_colors[$transfer['status']] ?? 'default';

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Cross-Border Transfer</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

<div class="container-fluid">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Cross-Border Transfer Details</h2>
                        <h5>Transfer to <?php echo htmlspecialchars($transfer['destination_country']); ?></h5>
                    </div>
                </div>
                <hr />

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <a href="crossborder_edit.php?id=<?php echo $cb_id; ?>" class="btn btn-primary">
                        <i class="fa fa-edit"></i> Edit Transfer
                    </a>
                    <a href="crossborder_list.php" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to List
                    </a>
                    <a href="crossborder_export.php?id=<?php echo $cb_id; ?>" class="btn btn-success pull-right">
                        <i class="fa fa-download"></i> Generate POTRAZ Notification Template
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-md-6">

            <!-- Transfer Information -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-info-circle"></i> Transfer Information
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Destination Country:</th>
                            <td>
                                <strong><?php echo htmlspecialchars($transfer['destination_country']); ?></strong>
                                <?php if ($transfer['is_high_risk']): ?>
                                    <span class="label label-danger">
                                        <i class="fa fa-exclamation-triangle"></i> HIGH RISK
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Recipient Organisation:</th>
                            <td><?php echo htmlspecialchars($transfer['recipient_org']); ?></td>
                        </tr>
                        <?php if ($transfer['recipient_contact']): ?>
                        <tr>
                            <th>Recipient Contact:</th>
                            <td><?php echo htmlspecialchars($transfer['recipient_contact']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Data Type Transferred:</th>
                            <td><?php echo nl2br(htmlspecialchars($transfer['data_type_transferred'])); ?></td>
                        </tr>
                        <tr>
                            <th>Transfer Frequency:</th>
                            <td>
                                <span class="label label-default">
                                    <?php echo ucfirst($transfer['transfer_frequency']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($transfer['activity_name']): ?>
                        <tr>
                            <th>Linked ROPA Activity:</th>
                            <td>
                                <a href="ropa_view.php?id=<?php echo $transfer['ropa_id']; ?>">
                                    <?php echo htmlspecialchars($transfer['activity_name']); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Metadata -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-clock-o"></i> Record Metadata
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Created By:</th>
                            <td>
                                <?php echo htmlspecialchars($transfer['created_by_name']); ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($transfer['created_by_email']); ?></small>
                            </td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($transfer['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($transfer['updated_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Column -->
        <div class="col-md-6">

            <!-- Safeguards & Compliance -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fa fa-shield"></i> Safeguards & Compliance Status
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Legal Safeguard:</th>
                            <td>
                                <strong><?php echo htmlspecialchars($transfer['safeguard_name']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($transfer['safeguard_description']); ?></small>
                            </td>
                        </tr>
                        <?php if ($transfer['safeguard_details']): ?>
                        <tr>
                            <th>Safeguard Details:</th>
                            <td><?php echo nl2br(htmlspecialchars($transfer['safeguard_details'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="label label-<?php echo $status_color; ?>" style="font-size: 14px; padding: 8px 12px;">
                                    <?php echo strtoupper($transfer['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>POTRAZ Notification Ref:</th>
                            <td>
                                <?php if ($transfer['potraz_notification_ref']): ?>
                                    <strong><?php echo htmlspecialchars($transfer['potraz_notification_ref']); ?></strong>
                                <?php else: ?>
                                    <span class="text-danger"><i class="fa fa-warning"></i> Not yet notified</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($transfer['submitted_at']): ?>
                        <tr>
                            <th>Submitted to POTRAZ:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($transfer['submitted_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($transfer['approved_at']): ?>
                        <tr>
                            <th>Approved Date:</th>
                            <td><?php echo date('d F Y, H:i', strtotime($transfer['approved_at'])); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($transfer['approval_notes']): ?>
                        <tr>
                            <th>Approval Notes:</th>
                            <td><?php echo nl2br(htmlspecialchars($transfer['approval_notes'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Compliance Checklist -->
            <div class="panel panel-<?php echo $transfer['is_high_risk'] ? 'danger' : 'success'; ?>">
                <div class="panel-heading">
                    <i class="fa fa-check-square-o"></i> Compliance Checklist
                </div>
                <div class="panel-body">
                    <ul class="list-unstyled">
                        <li>
                            <i class="fa fa-<?php echo $transfer['safeguard_id'] ? 'check text-success' : 'times text-danger'; ?>"></i>
                            Appropriate safeguard documented
                        </li>
                        <li>
                            <i class="fa fa-<?php echo $transfer['ropa_id'] ? 'check text-success' : 'times text-warning'; ?>"></i>
                            Linked to processing activity <?php echo !$transfer['ropa_id'] ? '(Optional)' : ''; ?>
                        </li>
                        <li>
                            <i class="fa fa-<?php echo $transfer['potraz_notification_ref'] ? 'check text-success' : 'times text-danger'; ?>"></i>
                            POTRAZ notified
                        </li>
                        <li>
                            <i class="fa fa-<?php echo ($transfer['status'] === 'approved') ? 'check text-success' : 'times text-warning'; ?>"></i>
                            Transfer approved
                        </li>
                    </ul>

                    <?php if ($transfer['is_high_risk']): ?>
                        <div class="alert alert-danger" style="margin-top: 15px; margin-bottom: 0;">
                            <strong><i class="fa fa-exclamation-triangle"></i> HIGH-RISK TRANSFER:</strong>
                            This country does not have adequate data protection. Enhanced safeguards and DPO approval required.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>

            </div>
        </div>
    </div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
