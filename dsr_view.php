<?php
/**
 * View DSR Request Details
 * Complete request information with status workflow
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$dsr_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($dsr_id <= 0) {
    set_flash_message('Invalid DSR request ID.', 'danger');
    redirect('dsr_list.php');
}

// Fetch DSR details
$query = "SELECT d.*,
          u.first_name as handler_first, u.last_name as handler_last, u.email as handler_email,
          creator.first_name as creator_first, creator.last_name as creator_last,
          pa.activity_name,
          DATEDIFF(NOW(), d.received_at) as days_elapsed,
          DATEDIFF(d.completed_at, d.received_at) as days_to_complete,
          (30 - DATEDIFF(NOW(), d.received_at)) as days_remaining
          FROM dsr_requests d
          LEFT JOIN users u ON d.assigned_to = u.user_id
          LEFT JOIN users creator ON d.created_by = creator.user_id
          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
          WHERE d.dsr_id = ? AND d.org_id = ?";

$stmt = db_query($query, [$dsr_id, $org_id]);
$dsr = db_fetch_one($stmt);

if (!$dsr) {
    set_flash_message('DSR request not found.', 'danger');
    redirect('dsr_list.php');
}

// Calculate SLA status
if ($dsr['status'] == 'completed') {
    $sla_status = $dsr['days_to_complete'] <= 30 ? 'on_time' : 'late';
} else {
    if ($dsr['days_elapsed'] > 30) {
        $sla_status = 'overdue';
    } elseif ($dsr['days_elapsed'] >= 25) {
        $sla_status = 'due_soon';
    } else {
        $sla_status = 'on_track';
    }
}

// Fetch activity history
$history_query = "SELECT * FROM audit_log
                  WHERE org_id = ? AND entity_type = 'dsr_request' AND entity_id = ?
                  ORDER BY created_at DESC";
$history = db_fetch_all(db_query($history_query, [$org_id, $dsr_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - DSR Request Details</title>
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
            <div class="page-header">
                <h1>
                    <i class="fa fa-user-circle"></i> DSR Request Details
                    <small>DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?></small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="dsr_list.php">DSR Requests</a></li>
                    <li class="active">View Request</li>
                </ol>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Request Summary -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Request Summary</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Request ID:</th>
                            <td><strong>DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Request Type:</th>
                            <td>
                                <span class="label label-info" style="font-size: 13px;">
                                    <?php echo htmlspecialchars($dsr['request_type']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php
                                $status_class = [
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'rejected' => 'danger'
                                ][$dsr['status']] ?? 'default';
                                echo "<span class='label label-$status_class' style='font-size: 13px;'>" . ucfirst(str_replace('_', ' ', $dsr['status'])) . "</span>";
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Priority:</th>
                            <td>
                                <?php
                                $priority_class = [
                                    'low' => 'default',
                                    'medium' => 'info',
                                    'high' => 'warning',
                                    'urgent' => 'danger'
                                ][$dsr['priority']] ?? 'default';
                                echo "<span class='label label-$priority_class' style='font-size: 13px;'>" . ucfirst($dsr['priority']) . "</span>";
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Request Date:</th>
                            <td><strong><?php echo date('d F Y', strtotime($dsr['request_date'])); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Request Method:</th>
                            <td><?php echo htmlspecialchars($dsr['request_method']); ?></td>
                        </tr>
                        <tr>
                            <th>Assigned To:</th>
                            <td>
                                <?php
                                if ($dsr['handler_first']) {
                                    echo htmlspecialchars($dsr['handler_first'] . ' ' . $dsr['handler_last']);
                                    echo '<br><small class="text-muted">' . htmlspecialchars($dsr['handler_email']) . '</small>';
                                } else {
                                    echo '<span class="text-muted">Unassigned</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Linked ROPA:</th>
                            <td>
                                <?php
                                if ($dsr['activity_name']) {
                                    echo '<a href="ropa_view.php?id=' . $dsr['ropa_id'] . '">' . htmlspecialchars($dsr['activity_name']) . '</a>';
                                } else {
                                    echo '<span class="text-muted">Not Linked</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Data Subject Information -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-user"></i> Data Subject Information</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Name:</th>
                            <td><strong><?php echo htmlspecialchars($dsr['subject_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($dsr['subject_email']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo htmlspecialchars($dsr['subject_phone'] ?? 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <th>ID Number:</th>
                            <td><?php echo htmlspecialchars($dsr['subject_id_number'] ?? 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <th>Identity Verified:</th>
                            <td>
                                <?php if ($dsr['identity_verified']): ?>
                                    <span class="label label-success"><i class="fa fa-check"></i> Verified</span>
                                    <br><small class="text-muted">
                                        <?php echo date('d M Y, H:i', strtotime($dsr['verification_date'])); ?>
                                        <?php if ($dsr['verification_method']): ?>
                                            via <?php echo htmlspecialchars($dsr['verification_method']); ?>
                                        <?php endif; ?>
                                    </small>
                                <?php else: ?>
                                    <span class="label label-warning"><i class="fa fa-warning"></i> Not Verified</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Request Details -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-file-text"></i> Request Details</h3>
                </div>
                <div class="panel-body">
                    <p><strong>Description:</strong></p>
                    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap;">
<?php echo htmlspecialchars($dsr['request_description']); ?>
                    </div>

                    <?php if ($dsr['internal_notes']): ?>
                    <p style="margin-top: 20px;"><strong>Internal Notes:</strong></p>
                    <div style="background-color: #fffbf0; padding: 15px; border: 1px solid #f0e68c; border-radius: 4px; white-space: pre-wrap;">
<?php echo htmlspecialchars($dsr['internal_notes']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Response Details (if completed) -->
            <?php if ($dsr['status'] == 'completed' || $dsr['status'] == 'rejected'): ?>
            <div class="panel panel-<?php echo $dsr['status'] == 'completed' ? 'success' : 'danger'; ?>">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-<?php echo $dsr['status'] == 'completed' ? 'check-circle' : 'times-circle'; ?>"></i>
                        Response Details
                    </h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Completion Date:</th>
                            <td><strong><?php echo date('d F Y, H:i', strtotime($dsr['completion_date'])); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Days to Complete:</th>
                            <td>
                                <strong><?php echo $dsr['days_to_complete']; ?> days</strong>
                                <?php if ($dsr['days_to_complete'] <= 30): ?>
                                    <span class="label label-success"><i class="fa fa-check"></i> Within SLA</span>
                                <?php else: ?>
                                    <span class="label label-danger"><i class="fa fa-times"></i> Exceeded SLA</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <?php if ($dsr['response_notes']): ?>
                    <p><strong>Response to Data Subject:</strong></p>
                    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap;">
<?php echo htmlspecialchars($dsr['response_notes']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($dsr['rejection_reason']): ?>
                    <p style="margin-top: 15px;"><strong>Rejection Reason:</strong></p>
                    <div style="background-color: #fff5f5; padding: 15px; border: 1px solid #f8d7da; border-radius: 4px; white-space: pre-wrap;">
<?php echo htmlspecialchars($dsr['rejection_reason']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activity History -->
            <?php if (!empty($history)): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-history"></i> Activity History</h3>
                </div>
                <div class="panel-body">
                    <div class="timeline">
                        <?php foreach ($history as $log): ?>
                        <div style="margin-bottom: 15px; padding-left: 30px; border-left: 2px solid #3498db; position: relative;">
                            <div style="position: absolute; left: -8px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: #3498db;"></div>
                            <div>
                                <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                                <small class="text-muted pull-right">
                                    <?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?>
                                </small>
                            </div>
                            <p class="text-muted" style="margin: 5px 0 0 0; font-size: 12px;">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- SLA Tracking -->
            <div class="panel panel-<?php echo $sla_status == 'overdue' ? 'danger' : ($sla_status == 'due_soon' ? 'warning' : 'success'); ?>">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-clock-o"></i> SLA Tracking</h3>
                </div>
                <div class="panel-body text-center">
                    <?php if ($dsr['status'] == 'completed' || $dsr['status'] == 'rejected'): ?>
                        <h2 style="margin: 10px 0;"><?php echo $dsr['days_to_complete']; ?> Days</h2>
                        <p class="text-muted">Time to Complete</p>
                        <?php if ($dsr['days_to_complete'] <= 30): ?>
                            <div class="alert alert-success" style="margin-bottom: 0;">
                                <i class="fa fa-check-circle"></i> <strong>Completed On Time</strong><br>
                                Within 30-day SLA requirement
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger" style="margin-bottom: 0;">
                                <i class="fa fa-times-circle"></i> <strong>Completed Late</strong><br>
                                Exceeded 30-day SLA by <?php echo ($dsr['days_to_complete'] - 30); ?> days
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <h2 style="margin: 10px 0;"><?php echo $dsr['days_elapsed']; ?> / 30 Days</h2>
                        <p class="text-muted">Days Elapsed</p>

                        <div class="progress" style="height: 25px; margin-bottom: 15px;">
                            <?php
                            $progress = min(100, ($dsr['days_elapsed'] / 30) * 100);
                            $progress_class = $sla_status == 'overdue' ? 'danger' : ($sla_status == 'due_soon' ? 'warning' : 'info');
                            ?>
                            <div class="progress-bar progress-bar-<?php echo $progress_class; ?>" style="width: <?php echo $progress; ?>%">
                                <?php echo round($progress); ?>%
                            </div>
                        </div>

                        <?php if ($sla_status == 'overdue'): ?>
                            <div class="alert alert-danger" style="margin-bottom: 0;">
                                <i class="fa fa-exclamation-triangle"></i> <strong>OVERDUE</strong><br>
                                Exceeded 30-day deadline by <?php echo abs($dsr['days_remaining']); ?> days
                            </div>
                        <?php elseif ($sla_status == 'due_soon'): ?>
                            <div class="alert alert-warning" style="margin-bottom: 0;">
                                <i class="fa fa-clock-o"></i> <strong>Due Soon</strong><br>
                                <?php echo $dsr['days_remaining']; ?> days remaining
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success" style="margin-bottom: 0;">
                                <i class="fa fa-check-circle"></i> <strong>On Track</strong><br>
                                <?php echo $dsr['days_remaining']; ?> days remaining
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <?php if ($dsr['status'] != 'completed' && $dsr['status'] != 'rejected'): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-cogs"></i> Actions</h3>
                </div>
                <div class="panel-body">
                    <a href="dsr_process.php?id=<?php echo $dsr_id; ?>" class="btn btn-success btn-block">
                        <i class="fa fa-check"></i> Process Request
                    </a>
                    <a href="dsr_export.php?id=<?php echo $dsr_id; ?>&format=pdf" class="btn btn-primary btn-block">
                        <i class="fa fa-file-pdf-o"></i> Export PDF
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-download"></i> Export</h3>
                </div>
                <div class="panel-body">
                    <a href="dsr_export.php?id=<?php echo $dsr_id; ?>&format=pdf" class="btn btn-primary btn-block">
                        <i class="fa fa-file-pdf-o"></i> Export PDF
                    </a>
                    <a href="dsr_export.php?id=<?php echo $dsr_id; ?>&format=csv" class="btn btn-default btn-block">
                        <i class="fa fa-file-excel-o"></i> Export CSV
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Metadata -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info"></i> Metadata</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted" style="margin: 0; font-size: 12px;">
                        <strong>Created By:</strong><br>
                        <?php echo htmlspecialchars($dsr['creator_first'] . ' ' . $dsr['creator_last']); ?><br>
                        <?php echo date('d M Y, H:i', strtotime($dsr['created_at'])); ?>
                    </p>
                    <hr style="margin: 10px 0;">
                    <p class="text-muted" style="margin: 0; font-size: 12px;">
                        <strong>Last Updated:</strong><br>
                        <?php echo date('d M Y, H:i', strtotime($dsr['updated_at'])); ?>
                    </p>
                </div>
            </div>

            <a href="dsr_list.php" class="btn btn-default btn-block">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
