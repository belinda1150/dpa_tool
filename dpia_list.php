<?php
/**
 * DPA Tool - DPIA List
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();

// Get all DPIA entries
$query = "SELECT d.*, pa.activity_name, u.first_name, u.last_name,
          CASE
            WHEN d.residual_risk_score >= " . RISK_ACCEPTABLE_THRESHOLD . " THEN 'High'
            WHEN d.residual_risk_score >= 3 THEN 'Medium'
            ELSE 'Low'
          END as risk_level
          FROM dpia d
          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
          LEFT JOIN users u ON d.created_by = u.user_id
          WHERE d.org_id = ?
          ORDER BY d.created_at DESC";
$stmt = db_query($query, [$org_id]);
$dpia_entries = db_fetch_all($stmt);

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - DPIA Register</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/js/dataTables/dataTables.bootstrap.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Data Protection Impact Assessments (DPIA)</h2>
                        <h5>Manage privacy risk assessments - CDPA s.28-29, SI 156 s.8</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <!-- Info Panel -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> <strong>When is a DPIA Required?</strong>
                            A DPIA is mandatory when processing:
                            <ul>
                                <li>Special categories of personal data (health, biometrics, etc.)</li>
                                <li>Personal data of minors (under 18 years)</li>
                                <li>Large-scale processing (<?php echo number_format(DPIA_THRESHOLD_SUBJECTS); ?>+ data subjects)</li>
                                <li>Cross-border transfers of personal data</li>
                                <li>Systematic monitoring or profiling</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <div class="row">
                                    <div class="col-md-6">
                                        <i class="fa fa-shield"></i> DPIA Register
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="dpia_wizard.php" class="btn btn-primary btn-sm">
                                            <i class="fa fa-plus"></i> Start New DPIA
                                        </a>
                                        <a href="dpia_export.php" class="btn btn-success btn-sm">
                                            <i class="fa fa-download"></i> Export Register
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered table-hover" id="dpiaTable">
                                        <thead>
                                            <tr>
                                                <th>DPIA Title</th>
                                                <th>Linked ROPA</th>
                                                <th>Status</th>
                                                <th>Screening</th>
                                                <th>Residual Risk</th>
                                                <th>Risk Level</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($dpia_entries as $entry): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($entry['dpia_title']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($entry['activity_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($entry['status']) {
                                                        case 'approved':
                                                            $status_class = 'success';
                                                            break;
                                                        case 'submitted':
                                                            $status_class = 'info';
                                                            break;
                                                        case 'rework':
                                                            $status_class = 'warning';
                                                            break;
                                                        case 'rejected':
                                                            $status_class = 'danger';
                                                            break;
                                                        default:
                                                            $status_class = 'default';
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($entry['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($entry['screening_result'] == 'needed'): ?>
                                                        <span class="label label-warning">DPIA Needed</span>
                                                    <?php else: ?>
                                                        <span class="label label-default">Not Needed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($entry['residual_risk_score']): ?>
                                                        <strong><?php echo $entry['residual_risk_score']; ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $risk_class = 'default';
                                                    if ($entry['risk_level'] == 'High') {
                                                        $risk_class = 'danger';
                                                    } elseif ($entry['risk_level'] == 'Medium') {
                                                        $risk_class = 'warning';
                                                    } elseif ($entry['risk_level'] == 'Low') {
                                                        $risk_class = 'success';
                                                    }
                                                    ?>
                                                    <span class="label label-<?php echo $risk_class; ?>">
                                                        <?php echo $entry['risk_level']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($entry['first_name'] . ' ' . $entry['last_name']); ?></td>
                                                <td><?php echo format_date($entry['created_at'], 'd M Y'); ?></td>
                                                <td>
                                                    <a href="dpia_view.php?id=<?php echo $entry['dpia_id']; ?>" class="btn btn-info btn-xs" title="View">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <?php if ($entry['status'] == 'draft' || $entry['status'] == 'rework'): ?>
                                                    <a href="dpia_wizard.php?id=<?php echo $entry['dpia_id']; ?>" class="btn btn-warning btn-xs" title="Continue">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php if (is_dpo() && $entry['status'] == 'submitted'): ?>
                                                    <a href="dpia_approve.php?id=<?php echo $entry['dpia_id']; ?>" class="btn btn-success btn-xs" title="Approve">
                                                        <i class="fa fa-check"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="dpia_export.php?id=<?php echo $entry['dpia_id']; ?>" class="btn btn-default btn-xs" title="Export PDF">
                                                        <i class="fa fa-download"></i>
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
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/dataTables/jquery.dataTables.js"></script>
    <script src="assets/js/dataTables/dataTables.bootstrap.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        $(document).ready(function() {
            $('#dpiaTable').dataTable({
                "order": [[7, "desc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
