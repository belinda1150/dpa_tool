<?php
/**
 * DSR Requests List
 * Data Subject Rights requests with 30-day SLA tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all DSR requests with calculated SLA
$query = "SELECT d.*,
          u.first_name as handler_first, u.last_name as handler_last,
          creator.first_name as creator_first, creator.last_name as creator_last,
          DATEDIFF(NOW(), d.received_at) as days_elapsed,
          DATEDIFF(d.completed_at, d.received_at) as days_to_complete,
          CASE
            WHEN d.status = 'completed' THEN
              CASE WHEN DATEDIFF(d.completed_at, d.received_at) <= 30 THEN 'on_time' ELSE 'late' END
            WHEN DATEDIFF(NOW(), d.received_at) > 30 THEN 'overdue'
            WHEN DATEDIFF(NOW(), d.received_at) >= 25 THEN 'due_soon'
            ELSE 'on_track'
          END as sla_status
          FROM dsr_requests d
          LEFT JOIN users u ON d.assigned_to = u.user_id
          LEFT JOIN users creator ON d.created_by = creator.user_id
          WHERE d.org_id = ?
          ORDER BY d.received_at DESC";

$requests = db_fetch_all(db_query($query, [$org_id]));

// Calculate statistics
$total = count($requests);
$pending = 0;
$completed = 0;
$overdue = 0;
$due_soon = 0;

foreach ($requests as $req) {
    if ($req['status'] == 'completed') {
        $completed++;
    } else {
        $pending++;
        if ($req['sla_status'] == 'overdue') {
            $overdue++;
        } elseif ($req['sla_status'] == 'due_soon') {
            $due_soon++;
        }
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - DSR Requests</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
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
                    <i class="fa fa-user-circle"></i> Data Subject Rights (DSR) Requests
                    <small>CDPA s.15-22</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li class="active">DSR Requests</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-primary">
                <div class="panel-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $total; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-list"></i> Total Requests
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $pending; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-clock-o"></i> Pending
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-danger">
                <div class="panel-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $overdue; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-exclamation-triangle"></i> Overdue (&gt;30 Days)
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $completed; ?></h2>
                    <p class="text-muted" style="margin: 0;">
                        <i class="fa fa-check-circle"></i> Completed
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue/Due Soon Alerts -->
    <?php if ($overdue > 0 || $due_soon > 0): ?>
    <div class="row">
        <div class="col-md-12">
            <?php if ($overdue > 0): ?>
            <div class="alert alert-danger">
                <strong><i class="fa fa-exclamation-triangle"></i> URGENT:</strong>
                <?php echo $overdue; ?> request(s) have exceeded the 30-day response deadline required by CDPA.
                <a href="#" onclick="$('#requestsTable').DataTable().column(6).search('overdue').draw(); return false;" class="btn btn-danger btn-xs pull-right">
                    <i class="fa fa-filter"></i> Show Overdue
                </a>
            </div>
            <?php endif; ?>

            <?php if ($due_soon > 0): ?>
            <div class="alert alert-warning">
                <strong><i class="fa fa-clock-o"></i> Due Soon:</strong>
                <?php echo $due_soon; ?> request(s) are approaching the 30-day deadline (25+ days elapsed).
                <a href="#" onclick="$('#requestsTable').DataTable().column(6).search('due_soon').draw(); return false;" class="btn btn-warning btn-xs pull-right">
                    <i class="fa fa-filter"></i> Show Due Soon
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add New Request Button -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-list"></i> All DSR Requests
                    </h3>
                    <div class="pull-right" style="margin-top: -22px;">
                        <a href="dsr_add.php" class="btn btn-primary btn-sm">
                            <i class="fa fa-plus"></i> New DSR Request
                        </a>
                    </div>
                </div>
                <div class="panel-body">
                    <?php if (empty($requests)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No DSR requests recorded yet. Click "New DSR Request" to register a data subject rights request.
                        </div>
                    <?php else: ?>
                        <table id="requestsTable" class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Request Date</th>
                                    <th>Request Type</th>
                                    <th>Data Subject</th>
                                    <th>Status</th>
                                    <th>Days Elapsed</th>
                                    <th>SLA Status</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td>
                                        <strong>DSR-<?php echo str_pad($req['dsr_id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($req['received_at'])); ?></td>
                                    <td>
                                        <span class="label label-info">
                                            <?php echo htmlspecialchars($req['request_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($req['subject_name']); ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($req['subject_email']); ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = [
                                            'received' => 'warning',
                                            'in_progress' => 'info',
                                            'completed' => 'success',
                                            'rejected' => 'danger'
                                        ][$req['status']] ?? 'default';
                                        echo "<span class='label label-$status_class'>" . ucfirst(str_replace('_', ' ', $req['status'])) . "</span>";
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        if ($req['status'] == 'completed') {
                                            echo $req['days_to_complete'] . ' days';
                                        } else {
                                            echo '<strong>' . $req['days_elapsed'] . '</strong> / 30 days';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($req['status'] == 'completed') {
                                            if ($req['sla_status'] == 'on_time') {
                                                echo "<span class='label label-success'><i class='fa fa-check'></i> On Time</span>";
                                            } else {
                                                echo "<span class='label label-danger'><i class='fa fa-times'></i> Late</span>";
                                            }
                                        } else {
                                            if ($req['sla_status'] == 'overdue') {
                                                echo "<span class='label label-danger'><i class='fa fa-exclamation-triangle'></i> OVERDUE</span>";
                                            } elseif ($req['sla_status'] == 'due_soon') {
                                                echo "<span class='label label-warning'><i class='fa fa-clock-o'></i> Due Soon</span>";
                                            } else {
                                                echo "<span class='label label-success'><i class='fa fa-check-circle'></i> On Track</span>";
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($req['handler_first']) {
                                            echo htmlspecialchars($req['handler_first'] . ' ' . $req['handler_last']);
                                        } else {
                                            echo '<span class="text-muted">Unassigned</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-xs">
                                            <a href="dsr_view.php?id=<?php echo $req['dsr_id']; ?>" class="btn btn-info" title="View Details">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <?php if ($req['status'] != 'completed'): ?>
                                            <a href="dsr_process.php?id=<?php echo $req['dsr_id']; ?>" class="btn btn-success" title="Process Request">
                                                <i class="fa fa-check"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Panel -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> About Data Subject Rights (DSR)</h3>
                </div>
                <div class="panel-body">
                    <p>
                        Under the Zimbabwe Cyber and Data Protection Act (CDPA), data subjects have the following rights:
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li><strong>Right to Access</strong> (s.15) - Obtain confirmation and access to their personal data</li>
                                <li><strong>Right to Rectification</strong> (s.16) - Correct inaccurate or incomplete data</li>
                                <li><strong>Right to Erasure</strong> (s.17) - Request deletion of personal data</li>
                                <li><strong>Right to Data Portability</strong> (s.18) - Receive data in structured, machine-readable format</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li><strong>Right to Restriction</strong> (s.19) - Restrict processing under certain conditions</li>
                                <li><strong>Right to Object</strong> (s.20) - Object to processing for specific purposes</li>
                                <li><strong>Rights Related to Automated Decision-Making</strong> (s.21) - Challenge automated decisions</li>
                                <li><strong>Right to Withdraw Consent</strong> (s.22) - Withdraw previously given consent</li>
                            </ul>
                        </div>
                    </div>
                    <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                        <strong><i class="fa fa-clock-o"></i> Important:</strong>
                        Data controllers must respond to DSR requests within <strong>30 days</strong> of receipt.
                        Extensions may be granted in complex cases, but data subjects must be notified within the initial 30-day period.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#requestsTable').DataTable({
        "order": [[1, "desc"]],
        "pageLength": 25,
        "columnDefs": [
            { "orderable": false, "targets": 8 }
        ]
    });
});
</script>


            </div>
        </div>
    </div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
