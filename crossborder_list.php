<?php
/**
 * Cross-Border Transfers List Page
 * Display all cross-border transfers with POTRAZ notification tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all cross-border transfers
$query = "SELECT cb.*,
          pa.activity_name,
          s.safeguard_name,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name
          FROM cross_border_transfers cb
          LEFT JOIN processing_activities pa ON cb.ropa_id = pa.ropa_id
          LEFT JOIN safeguards s ON cb.safeguard_id = s.safeguard_id
          LEFT JOIN users u ON cb.created_by = u.user_id
          WHERE cb.org_id = ?
          ORDER BY cb.created_at DESC";

$stmt = db_query($query, [$org_id]);
$transfers = db_fetch_all($stmt);

// Calculate statistics
$total_transfers = count($transfers);
$pending_count = 0;
$submitted_count = 0;
$approved_count = 0;
$blocked_count = 0;
$high_risk_count = 0;

foreach ($transfers as $transfer) {
    if ($transfer['status'] === 'pending') $pending_count++;
    if ($transfer['status'] === 'submitted') $submitted_count++;
    if ($transfer['status'] === 'approved') $approved_count++;
    if ($transfer['status'] === 'blocked') $blocked_count++;
    if ($transfer['is_high_risk']) $high_risk_count++;
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Cross-Border Transfers</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-pending { background-color: #f0ad4e; color: white; }
        .status-submitted { background-color: #5bc0de; color: white; }
        .status-approved { background-color: #5cb85c; color: white; }
        .status-blocked { background-color: #d9534f; color: white; }
        .high-risk-badge {
            background-color: #d9534f;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

                <div class="row">
                    <div class="col-md-12">
                        <h2>Cross-Border Transfers Register</h2>
                        <h5>Data transfers outside Zimbabwe</h5>
                    </div>
                </div>
                <hr />

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-globe fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_transfers; ?></div>
                            <div>Total Transfers</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-clock-o fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $pending_count; ?></div>
                            <div>Pending Notification</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-check fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $approved_count; ?></div>
                            <div>Approved</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-danger">
                <div class="card-header">
                    <div class="row">
                        <div class="col-3">
                            <i class="fa fa-exclamation-triangle fa-3x"></i>
                        </div>
                        <div class="col-9 text-end">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $high_risk_count; ?></div>
                            <div>High-Risk Countries</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <a href="crossborder_add.php" class="btn btn-primary">
                        <i class="fa fa-plus"></i> Record New Cross-Border Transfer
                    </a>
                    <a href="crossborder_export.php" class="btn btn-success">
                        <i class="fa fa-download"></i> Export Register (PDF)
                    </a>
                    <a href="crossborder_export.php?format=csv" class="btn btn-info">
                        <i class="fa fa-file-excel-o"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fa fa-table"></i> Cross-Border Transfers
                    <div class="float-end">
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search..." style="width: 200px; display: inline-block;">
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($transfers)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No cross-border transfers recorded yet. Click "Record New Cross-Border Transfer" to add one.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover" id="transfersTable">
                                <thead>
                                    <tr>
                                        <th>Destination</th>
                                        <th>Recipient</th>
                                        <th>Data Type</th>
                                        <th>Safeguard</th>
                                        <th>POTRAZ Ref</th>
                                        <th>Status</th>
                                        <th>Date Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transfers as $transfer): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($transfer['destination_country']); ?></strong>
                                                <?php if ($transfer['is_high_risk']): ?>
                                                    <span class="high-risk-badge">
                                                        <i class="fa fa-exclamation-triangle"></i> HIGH RISK
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($transfer['activity_name']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fa fa-link"></i> <?php echo htmlspecialchars($transfer['activity_name']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($transfer['recipient_org']); ?>
                                                <?php if ($transfer['recipient_contact']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($transfer['recipient_contact']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $data_preview = strlen($transfer['data_type_transferred']) > 50
                                                        ? substr($transfer['data_type_transferred'], 0, 50) . '...'
                                                        : $transfer['data_type_transferred'];
                                                    echo htmlspecialchars($data_preview);
                                                ?>
                                                <br><small class="text-muted">
                                                    <i class="fa fa-clock-o"></i> <?php echo ucfirst($transfer['transfer_frequency']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($transfer['safeguard_name']); ?></td>
                                            <td>
                                                <?php if ($transfer['potraz_notification_ref']): ?>
                                                    <span class="badge bg-success">
                                                        <?php echo htmlspecialchars($transfer['potraz_notification_ref']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Not notified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $transfer['status']; ?>">
                                                    <?php echo strtoupper($transfer['status']); ?>
                                                </span>
                                                <?php if ($transfer['submitted_at']): ?>
                                                    <br><small class="text-muted">
                                                        <?php echo date('d M Y', strtotime($transfer['submitted_at'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d M Y', strtotime($transfer['created_at'])); ?>
                                                <br><small class="text-muted">by <?php echo htmlspecialchars($transfer['created_by_name']); ?></small>
                                            </td>
                                            <td>
                                                <a href="crossborder_view.php?id=<?php echo $transfer['cb_id']; ?>" class="btn btn-info btn-sm" title="View Details">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="crossborder_edit.php?id=<?php echo $transfer['cb_id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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

<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
<script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#transfersTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>
</body>
</html>
