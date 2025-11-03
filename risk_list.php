<?php
/**
 * Risk Register List Page
 * Display all organizational risks with heat map visualization
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch all risks for the organization
$query = "SELECT r.*,
          u.first_name as owner_first,
          u.last_name as owner_last,
          dept.dept_name,
          (r.likelihood * r.impact) as inherent_score,
          (r.residual_likelihood * r.residual_impact) as residual_score
          FROM risks r
          LEFT JOIN users u ON r.owner_id = u.user_id
          LEFT JOIN departments dept ON r.dept_id = dept.dept_id
          WHERE r.org_id = ?
          ORDER BY (r.residual_likelihood * r.residual_impact) DESC, r.created_at DESC";

$stmt = db_query($query, [$org_id]);
$risks = db_fetch_all($stmt);

// Calculate statistics
$total_risks = count($risks);
$high_risks = 0;
$medium_risks = 0;
$low_risks = 0;
$critical_risks = 0;
$open_risks = 0;
$mitigated_risks = 0;
$accepted_risks = 0;

foreach ($risks as $risk) {
    $residual = $risk['residual_score'] ?? $risk['inherent_score'];

    if ($residual >= 15) {
        $critical_risks++;
    } elseif ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
        $high_risks++;
    } elseif ($residual >= 3) {
        $medium_risks++;
    } else {
        $low_risks++;
    }

    // Count by status
    if ($risk['status'] === 'open') {
        $open_risks++;
    } elseif ($risk['status'] === 'mitigated') {
        $mitigated_risks++;
    } elseif ($risk['status'] === 'accepted') {
        $accepted_risks++;
    }
}

// Prepare data for heat map (5x5 grid)
$heat_map = [];
for ($l = 5; $l >= 1; $l--) {
    for ($i = 1; $i <= 5; $i++) {
        $heat_map[$l][$i] = 0; // Count of risks in each cell
    }
}

// Populate heat map with current risks
foreach ($risks as $risk) {
    $likelihood = intval($risk['residual_likelihood'] ?? $risk['likelihood']);
    $impact = intval($risk['residual_impact'] ?? $risk['impact']);

    if ($likelihood >= 1 && $likelihood <= 5 && $impact >= 1 && $impact <= 5) {
        $heat_map[$likelihood][$impact]++;
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Risk Register</title>
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

    <!-- Page Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-warning"></i> Risk Register
                    <small>Organizational risk assessment and management</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li class="active">Risk Register</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type']); ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-default">
                <div class="panel-body">
                    <div class="text-center">
                        <h2 class="text-primary" style="margin: 0; font-size: 36px;"><?php echo $total_risks; ?></h2>
                        <p class="text-muted">Total Risks</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-danger">
                <div class="panel-body">
                    <div class="text-center">
                        <h2 style="margin: 0; font-size: 36px;"><?php echo $critical_risks + $high_risks; ?></h2>
                        <p>Critical/High Risks</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-warning">
                <div class="panel-body">
                    <div class="text-center">
                        <h2 style="margin: 0; font-size: 36px;"><?php echo $medium_risks; ?></h2>
                        <p>Medium Risks</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="panel panel-success">
                <div class="panel-body">
                    <div class="text-center">
                        <h2 style="margin: 0; font-size: 36px;"><?php echo $low_risks; ?></h2>
                        <p>Low Risks</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Heat Map -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-fire"></i> Risk Heat Map (Residual Risk)
                        <span class="pull-right">
                            <small>Higher = More Critical</small>
                        </span>
                    </h3>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th width="15%" class="text-center" style="vertical-align: middle;">
                                        <strong>Likelihood →<br>Impact ↓</strong>
                                    </th>
                                    <th width="17%" class="text-center">1 - Very Low</th>
                                    <th width="17%" class="text-center">2 - Low</th>
                                    <th width="17%" class="text-center">3 - Medium</th>
                                    <th width="17%" class="text-center">4 - High</th>
                                    <th width="17%" class="text-center">5 - Very High</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($impact = 5; $impact >= 1; $impact--): ?>
                                    <tr>
                                        <th class="text-center" style="vertical-align: middle;">
                                            <?php
                                            $impact_labels = [1 => 'Very Low', 2 => 'Low', 3 => 'Medium', 4 => 'High', 5 => 'Very High'];
                                            echo $impact . ' - ' . $impact_labels[$impact];
                                            ?>
                                        </th>
                                        <?php for ($likelihood = 1; $likelihood <= 5; $likelihood++): ?>
                                            <?php
                                            $score = $likelihood * $impact;
                                            $count = $heat_map[$likelihood][$impact];

                                            // Determine cell color based on risk score
                                            if ($score >= 15) {
                                                $cell_class = 'danger';
                                                $bg_color = '#d9534f';
                                                $text_color = 'white';
                                            } elseif ($score >= RISK_ACCEPTABLE_THRESHOLD) {
                                                $cell_class = 'danger';
                                                $bg_color = '#f0ad4e';
                                                $text_color = 'white';
                                            } elseif ($score >= 3) {
                                                $cell_class = 'warning';
                                                $bg_color = '#fcf8e3';
                                                $text_color = '#333';
                                            } else {
                                                $cell_class = 'success';
                                                $bg_color = '#d4edda';
                                                $text_color = '#333';
                                            }
                                            ?>
                                            <td class="text-center <?php echo $cell_class; ?>"
                                                style="background-color: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; height: 80px; vertical-align: middle; font-weight: bold;">
                                                <div style="font-size: 24px;"><?php echo $score; ?></div>
                                                <?php if ($count > 0): ?>
                                                    <div style="font-size: 14px;">
                                                        <span class="badge" style="background-color: rgba(0,0,0,0.3);">
                                                            <?php echo $count; ?> risk<?php echo $count > 1 ? 's' : ''; ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 15px;">
                        <p class="text-muted">
                            <strong>Legend:</strong>
                            <span class="label label-danger">Critical (15-25)</span>
                            <span class="label" style="background-color: #f0ad4e;">High (6-14)</span>
                            <span class="label label-warning">Medium (3-5)</span>
                            <span class="label label-success">Low (1-2)</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk List -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-list"></i> All Risks
                        <a href="risk_add.php" class="btn btn-success btn-sm pull-right">
                            <i class="fa fa-plus"></i> Add New Risk
                        </a>
                    </h3>
                </div>
                <div class="panel-body">
                    <?php if (empty($risks)): ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            No risks have been identified yet. Click "Add New Risk" to create your first risk entry.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table id="risksTable" class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Risk ID</th>
                                        <th>Risk Title</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Risk Owner</th>
                                        <th>Inherent Score</th>
                                        <th>Residual Score</th>
                                        <th>Risk Level</th>
                                        <th>Status</th>
                                        <th>Review Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($risks as $risk): ?>
                                        <?php
                                        $inherent = $risk['inherent_score'];
                                        $residual = $risk['residual_score'] ?? $inherent;

                                        // Determine risk level based on residual score
                                        if ($residual >= 15) {
                                            $risk_level = 'Critical';
                                            $risk_class = 'danger';
                                        } elseif ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
                                            $risk_level = 'High';
                                            $risk_class = 'warning';
                                        } elseif ($residual >= 3) {
                                            $risk_level = 'Medium';
                                            $risk_class = 'info';
                                        } else {
                                            $risk_level = 'Low';
                                            $risk_class = 'success';
                                        }

                                        // Status badges
                                        $status_labels = [
                                            'open' => 'danger',
                                            'mitigated' => 'warning',
                                            'accepted' => 'info',
                                            'closed' => 'success'
                                        ];
                                        $status_class = $status_labels[$risk['status']] ?? 'default';
                                        ?>
                                        <tr>
                                            <td>RISK-<?php echo str_pad($risk['risk_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($risk['risk_title']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="label label-default">
                                                    <?php echo htmlspecialchars($risk['risk_category']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($risk['dept_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <?php
                                                if ($risk['owner_first']) {
                                                    echo htmlspecialchars($risk['owner_first'] . ' ' . $risk['owner_last']);
                                                } else {
                                                    echo '<span class="text-muted">Not Assigned</span>';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="label label-default"><?php echo $inherent; ?>/25</span>
                                            </td>
                                            <td class="text-center">
                                                <span class="label label-<?php echo $risk_class; ?>">
                                                    <?php echo $residual; ?>/25
                                                </span>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $risk_class; ?>">
                                                    <?php echo $risk_level; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="label label-<?php echo $status_class; ?>">
                                                    <?php echo ucfirst($risk['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if ($risk['review_date']) {
                                                    $review_date = strtotime($risk['review_date']);
                                                    $today = strtotime('today');

                                                    if ($review_date < $today) {
                                                        echo '<span class="text-danger">' . date('d M Y', $review_date) . ' (Overdue)</span>';
                                                    } elseif ($review_date <= strtotime('+7 days')) {
                                                        echo '<span class="text-warning">' . date('d M Y', $review_date) . ' (Soon)</span>';
                                                    } else {
                                                        echo date('d M Y', $review_date);
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">Not Set</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-xs">
                                                    <a href="risk_view.php?id=<?php echo $risk['risk_id']; ?>"
                                                       class="btn btn-info" title="View Risk">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="risk_edit.php?id=<?php echo $risk['risk_id']; ?>"
                                                       class="btn btn-warning" title="Edit Risk">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($risks)): ?>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-sm-6">
                            <a href="risk_add.php" class="btn btn-success">
                                <i class="fa fa-plus"></i> Add New Risk
                            </a>
                            <a href="risk_export.php" class="btn btn-primary">
                                <i class="fa fa-download"></i> Export Register
                            </a>
                        </div>
                        <div class="col-sm-6 text-right">
                            <p class="text-muted" style="margin-top: 8px;">
                                Showing <?php echo $total_risks; ?> risk<?php echo $total_risks != 1 ? 's' : ''; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
$(document).ready(function() {
    // Initialize DataTables
    $('#risksTable').DataTable({
        "order": [[6, "desc"]], // Sort by residual score (descending)
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "All"]],
        "columnDefs": [
            { "orderable": false, "targets": [10] } // Actions column not sortable
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
