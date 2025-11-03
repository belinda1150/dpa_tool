<?php
/**
 * Lawful Basis Compliance Report
 * Shows processing activities and their lawful basis status
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all ROPA entries with lawful basis
$query = "SELECT pa.*,
          lb.basis_name, lb.basis_description, lb.cdpa_reference,
          d.dept_name,
          CONCAT(u.first_name, ' ', u.last_name) as created_by_name
          FROM processing_activities pa
          LEFT JOIN lawful_basis lb ON pa.lawful_basis_id = lb.lawful_basis_id
          LEFT JOIN departments d ON pa.dept_id = d.dept_id
          LEFT JOIN users u ON pa.created_by = u.user_id
          WHERE pa.org_id = ? AND pa.status != 'archived'
          ORDER BY
            CASE WHEN pa.lawful_basis_id IS NULL THEN 0 ELSE 1 END,
            pa.activity_name ASC";

$stmt = db_query($query, [$org_id]);
$activities = db_fetch_all($stmt);

// Calculate statistics
$total_activities = count($activities);
$with_basis = 0;
$without_basis = 0;
$by_basis = [];

foreach ($activities as $activity) {
    if ($activity['lawful_basis_id']) {
        $with_basis++;
        $basis_name = $activity['basis_name'];
        if (!isset($by_basis[$basis_name])) {
            $by_basis[$basis_name] = 0;
        }
        $by_basis[$basis_name]++;
    } else {
        $without_basis++;
    }
}

$compliance_rate = $total_activities > 0 ? round(($with_basis / $total_activities) * 100) : 0;

// Fetch consent register statistics
$consent_query = "SELECT
                  COUNT(*) as total_consents,
                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_consents,
                  SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_consents,
                  SUM(CASE WHEN status = 'withdrawn' THEN 1 ELSE 0 END) as withdrawn_consents
                  FROM consents WHERE org_id = ?";
$consent_stats = db_fetch_one(db_query($consent_query, [$org_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Lawful Basis Compliance Report</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .compliance-score {
            font-size: 72px;
            font-weight: bold;
            margin: 20px 0;
        }
        .score-excellent { color: #27ae60; }
        .score-good { color: #2ecc71; }
        .score-warning { color: #f39c12; }
        .score-poor { color: #e74c3c; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

<div class="container-fluid">

    <!-- Page Header -->
    <div class="row no-print">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-gavel"></i> Lawful Basis Compliance Report
                    <small>CDPA s.22-23</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="reports.php">Reports</a></li>
                    <li class="active">Lawful Basis Compliance</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row no-print">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <button onclick="window.print();" class="btn btn-primary">
                        <i class="fa fa-print"></i> Print Report
                    </button>
                    <a href="reports.php" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> Back to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Compliance Score -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-<?php echo $compliance_rate >= 90 ? 'success' : ($compliance_rate >= 70 ? 'warning' : 'danger'); ?>">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bar-chart"></i> Overall Compliance Score</h3>
                </div>
                <div class="panel-body text-center">
                    <div class="compliance-score <?php
                        if ($compliance_rate >= 95) echo 'score-excellent';
                        elseif ($compliance_rate >= 80) echo 'score-good';
                        elseif ($compliance_rate >= 60) echo 'score-warning';
                        else echo 'score-poor';
                    ?>">
                        <?php echo $compliance_rate; ?>%
                    </div>
                    <h4><?php echo $with_basis; ?> of <?php echo $total_activities; ?> processing activities have a documented lawful basis</h4>

                    <?php if ($without_basis > 0): ?>
                        <div class="alert alert-danger" style="margin-top: 20px;">
                            <strong><i class="fa fa-exclamation-triangle"></i> Action Required:</strong>
                            <?php echo $without_basis; ?> processing activit<?php echo $without_basis > 1 ? 'ies' : 'y'; ?> lack<?php echo $without_basis > 1 ? '' : 's'; ?> a documented lawful basis.
                            This is a CDPA violation.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success" style="margin-top: 20px;">
                            <strong><i class="fa fa-check-circle"></i> Excellent!</strong>
                            All processing activities have a documented lawful basis.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-list fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $total_activities; ?></div>
                            <div>Total Activities</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-check fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $with_basis; ?></div>
                            <div>With Lawful Basis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-exclamation-triangle fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $without_basis; ?></div>
                            <div>Missing Basis</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <div class="row">
                        <div class="col-xs-3">
                            <i class="fa fa-check-square fa-3x"></i>
                        </div>
                        <div class="col-xs-9 text-right">
                            <div style="font-size: 36px; font-weight: bold;"><?php echo $consent_stats['active_consents'] ?? 0; ?></div>
                            <div>Active Consents</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lawful Basis Breakdown -->
    <div class="row">
        <div class="col-md-6">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-pie-chart"></i> Lawful Basis Distribution</h3>
                </div>
                <div class="panel-body">
                    <?php if (!empty($by_basis)): ?>
                        <?php foreach ($by_basis as $basis_name => $count): ?>
                            <?php $percentage = round(($count / $total_activities) * 100); ?>
                            <div style="margin-bottom: 15px;">
                                <strong><?php echo htmlspecialchars($basis_name); ?></strong>
                                <span class="pull-right"><?php echo $count; ?> activit<?php echo $count > 1 ? 'ies' : 'y'; ?> (<?php echo $percentage; ?>%)</span>
                                <div class="progress" style="margin-top: 5px; margin-bottom: 0;">
                                    <div class="progress-bar progress-bar-info" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No lawful bases documented yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-check-square-o"></i> Consent Register Summary</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Total Consents Recorded:</th>
                            <td><strong><?php echo $consent_stats['total_consents'] ?? 0; ?></strong></td>
                        </tr>
                        <tr>
                            <th>Active Consents:</th>
                            <td><span class="label label-success"><?php echo $consent_stats['active_consents'] ?? 0; ?></span></td>
                        </tr>
                        <tr>
                            <th>Expired Consents:</th>
                            <td><span class="label label-danger"><?php echo $consent_stats['expired_consents'] ?? 0; ?></span></td>
                        </tr>
                        <tr>
                            <th>Withdrawn Consents:</th>
                            <td><span class="label label-warning"><?php echo $consent_stats['withdrawn_consents'] ?? 0; ?></span></td>
                        </tr>
                    </table>
                    <a href="consent_list.php" class="btn btn-primary btn-block no-print">
                        <i class="fa fa-arrow-right"></i> View Consent Register
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Missing Lawful Basis (Critical) -->
    <?php if ($without_basis > 0): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-exclamation-triangle"></i> Processing Activities WITHOUT Lawful Basis (URGENT)</h3>
                </div>
                <div class="panel-body">
                    <div class="alert alert-danger">
                        <strong><i class="fa fa-warning"></i> CDPA s.22 Violation:</strong>
                        Processing personal data without a lawful basis is illegal under Zimbabwe's Cyber and Data Protection Act.
                        Immediate action required to document the lawful basis or cease processing.
                    </div>

                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Activity Name</th>
                                <th>Department</th>
                                <th>Description</th>
                                <th>Created By</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <?php if (!$activity['lawful_basis_id']): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($activity['activity_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($activity['dept_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($activity['description'] ?? '', 0, 80)) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars($activity['created_by_name']); ?></td>
                                    <td class="no-print">
                                        <a href="ropa_edit.php?id=<?php echo $activity['ropa_id']; ?>" class="btn btn-warning btn-xs">
                                            <i class="fa fa-edit"></i> Add Lawful Basis
                                        </a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Activities by Lawful Basis -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-table"></i> All Processing Activities by Lawful Basis</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-striped table-bordered table-hover" id="activitiesTable">
                        <thead>
                            <tr>
                                <th>Activity Name</th>
                                <th>Department</th>
                                <th>Lawful Basis</th>
                                <th>CDPA Reference</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                            <tr class="<?php echo !$activity['lawful_basis_id'] ? 'danger' : ''; ?>">
                                <td><strong><?php echo htmlspecialchars($activity['activity_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($activity['dept_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($activity['lawful_basis_id']): ?>
                                        <span class="label label-success"><?php echo htmlspecialchars($activity['basis_name']); ?></span>
                                    <?php else: ?>
                                        <span class="label label-danger">NOT SET</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($activity['cdpa_reference']): ?>
                                        <small><?php echo htmlspecialchars($activity['cdpa_reference']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="label label-<?php
                                        echo $activity['status'] === 'validated' ? 'success' : 'warning';
                                    ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <a href="ropa_view.php?id=<?php echo $activity['ropa_id']; ?>" class="btn btn-info btn-xs">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a href="ropa_edit.php?id=<?php echo $activity['ropa_id']; ?>" class="btn btn-primary btn-xs">
                                        <i class="fa fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- CDPA Reference Guide -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-book"></i> CDPA s.22: Lawful Basis for Processing</h3>
                </div>
                <div class="panel-body">
                    <p><strong>Zimbabwe Cyber and Data Protection Act - Section 22</strong></p>
                    <p>Processing of personal data is lawful only if based on one of the following:</p>

                    <div class="row">
                        <div class="col-md-6">
                            <h5><strong>1. Consent</strong></h5>
                            <p style="font-size: 12px;">The data subject has given explicit consent to the processing for specific purposes.</p>

                            <h5><strong>2. Contract</strong></h5>
                            <p style="font-size: 12px;">Processing is necessary for the performance of a contract with the data subject.</p>

                            <h5><strong>3. Legal Obligation</strong></h5>
                            <p style="font-size: 12px;">Processing is required by Zimbabwean law.</p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>4. Vital Interests</strong></h5>
                            <p style="font-size: 12px;">Processing is necessary to protect the life or health of the data subject or another person.</p>

                            <h5><strong>5. Public Task</strong></h5>
                            <p style="font-size: 12px;">Processing is necessary for the performance of a task carried out in the public interest or in the exercise of official authority.</p>

                            <h5><strong>6. Legitimate Interests</strong></h5>
                            <p style="font-size: 12px;">Processing is necessary for legitimate interests pursued by the data controller or a third party, except where overridden by the interests or fundamental rights of the data subject.</p>
                        </div>
                    </div>

                    <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                        <strong><i class="fa fa-warning"></i> Important:</strong>
                        You must identify and document the appropriate lawful basis BEFORE commencing any processing activity.
                        The lawful basis cannot be changed retroactively.
                    </div>
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
<script src="assets/js/custom.js"></script>
<script src="assets/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $('#activitiesTable').DataTable({
        "order": [[2, "asc"]],
        "pageLength": 25
    });
});
</script>
</body>
</html>
