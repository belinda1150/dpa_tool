<?php
/**
 * DPA Tool - View Vendor Risk Assessment
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();
$assessment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$assessment_id) {
    set_flash_message('Invalid assessment ID.', 'error');
    redirect('vendor_list.php');
}

// Fetch assessment with vendor and user details
$query = "SELECT vra.*, v.vendor_name, v.vendor_ref, v.vendor_id,
          assessor.first_name as assessor_first, assessor.last_name as assessor_last,
          approver.first_name as approver_first, approver.last_name as approver_last,
          owner.first_name as owner_first, owner.last_name as owner_last
          FROM vendor_risk_assessments vra
          JOIN vendors v ON vra.vendor_id = v.vendor_id
          LEFT JOIN users assessor ON vra.assessed_by = assessor.user_id
          LEFT JOIN users approver ON vra.approved_by = approver.user_id
          LEFT JOIN users owner ON vra.treatment_owner_id = owner.user_id
          WHERE vra.assessment_id = ? AND vra.org_id = ?";
$stmt = db_query($query, [$assessment_id, $org_id]);
$assessment = db_fetch_one($stmt);

if (!$assessment) {
    set_flash_message('Assessment not found.', 'error');
    redirect('vendor_list.php');
}

function get_risk_badge($level) {
    $badges = [
        'critical' => '<span class="label label-danger">Critical</span>',
        'high' => '<span class="label label-warning">High</span>',
        'medium' => '<span class="label label-info">Medium</span>',
        'low' => '<span class="label label-success">Low</span>'
    ];
    return $badges[$level] ?? '<span class="label label-default">' . ucfirst($level) . '</span>';
}

function get_score_class($score) {
    if ($score >= 20) return 'danger';
    if ($score >= 15) return 'warning';
    if ($score >= 8) return 'info';
    return 'success';
}

$risk_categories = [
    'data_security' => 'Data Security',
    'compliance' => 'Compliance',
    'operational' => 'Operational',
    'financial' => 'Financial',
    'reputational' => 'Reputational'
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Assessment</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-8">
                        <h2>Vendor Risk Assessment</h2>
                        <h5>
                            <a href="vendor_view.php?id=<?php echo $assessment['vendor_id']; ?>">
                                <?php echo htmlspecialchars($assessment['vendor_name']); ?>
                            </a>
                            - <?php echo htmlspecialchars($assessment['assessment_ref']); ?>
                        </h5>
                    </div>
                    <div class="col-md-4 text-right">
                        <a href="vendor_view.php?id=<?php echo $assessment['vendor_id']; ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Vendor
                        </a>
                    </div>
                </div>
                <hr />

                <div class="row">
                    <div class="col-md-8">
                        <!-- Assessment Details -->
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-info-circle"></i> Assessment Details</h3>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Assessment Type:</strong> <?php echo ucwords(str_replace('_', ' ', $assessment['assessment_type'])); ?></p>
                                        <p><strong>Assessment Date:</strong> <?php echo format_date($assessment['assessment_date'], 'd M Y'); ?></p>
                                        <p><strong>Assessed By:</strong> <?php echo htmlspecialchars($assessment['assessor_first'] . ' ' . $assessment['assessor_last']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong>
                                            <?php
                                            $status_badges = [
                                                'draft' => '<span class="label label-default">Draft</span>',
                                                'in_progress' => '<span class="label label-info">In Progress</span>',
                                                'completed' => '<span class="label label-primary">Completed</span>',
                                                'approved' => '<span class="label label-success">Approved</span>'
                                            ];
                                            echo $status_badges[$assessment['status']] ?? $assessment['status'];
                                            ?>
                                        </p>
                                        <?php if ($assessment['approved_by']): ?>
                                        <p><strong>Approved By:</strong> <?php echo htmlspecialchars($assessment['approver_first'] . ' ' . $assessment['approver_last']); ?></p>
                                        <p><strong>Approved At:</strong> <?php echo format_datetime($assessment['approved_at'], 'd M Y H:i'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Risk Scores -->
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-line-chart"></i> Risk Scores by Category</h3>
                            </div>
                            <div class="panel-body">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Category</th>
                                            <th class="text-center">Likelihood</th>
                                            <th class="text-center">Impact</th>
                                            <th class="text-center">Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($risk_categories as $key => $label):
                                            $likelihood = $assessment[$key . '_likelihood'];
                                            $impact = $assessment[$key . '_impact'];
                                            $score = $likelihood * $impact;
                                            $scoreClass = get_score_class($score);
                                        ?>
                                        <tr>
                                            <td><strong><?php echo $label; ?></strong></td>
                                            <td class="text-center"><?php echo $likelihood; ?></td>
                                            <td class="text-center"><?php echo $impact; ?></td>
                                            <td class="text-center">
                                                <span class="label label-<?php echo $scoreClass; ?>"><?php echo $score; ?></span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="active">
                                            <td colspan="3"><strong>Overall Inherent Risk</strong></td>
                                            <td class="text-center">
                                                <span class="label label-<?php echo get_score_class($assessment['inherent_risk_score']); ?>" style="font-size: 14px;">
                                                    <?php echo number_format($assessment['inherent_risk_score'], 1); ?>
                                                </span>
                                                <?php echo get_risk_badge($assessment['inherent_risk_level']); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- Treatment Plan -->
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-shield"></i> Treatment Plan</h3>
                            </div>
                            <div class="panel-body">
                                <p><strong>Strategy:</strong> <?php echo ucfirst($assessment['treatment_strategy']); ?></p>
                                <?php if ($assessment['treatment_owner_id']): ?>
                                <p><strong>Owner:</strong> <?php echo htmlspecialchars($assessment['owner_first'] . ' ' . $assessment['owner_last']); ?></p>
                                <?php endif; ?>
                                <?php if ($assessment['treatment_due_date']): ?>
                                <p><strong>Due Date:</strong> <?php echo format_date($assessment['treatment_due_date'], 'd M Y'); ?></p>
                                <?php endif; ?>
                                <?php if ($assessment['treatment_plan']): ?>
                                <hr>
                                <p><strong>Treatment Plan:</strong></p>
                                <p><?php echo nl2br(htmlspecialchars($assessment['treatment_plan'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Findings -->
                        <?php if ($assessment['key_findings'] || $assessment['recommendations']): ?>
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-file-text"></i> Findings & Recommendations</h3>
                            </div>
                            <div class="panel-body">
                                <?php if ($assessment['key_findings']): ?>
                                <h5><strong>Key Findings:</strong></h5>
                                <p><?php echo nl2br(htmlspecialchars($assessment['key_findings'])); ?></p>
                                <?php endif; ?>

                                <?php if ($assessment['recommendations']): ?>
                                <h5><strong>Recommendations:</strong></h5>
                                <p><?php echo nl2br(htmlspecialchars($assessment['recommendations'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-4">
                        <!-- Overall Score Card -->
                        <div class="panel panel-danger">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-warning"></i> Overall Risk</h3>
                            </div>
                            <div class="panel-body text-center">
                                <h1>
                                    <span class="label label-<?php echo get_score_class($assessment['inherent_risk_score']); ?>" style="font-size: 48px;">
                                        <?php echo number_format($assessment['inherent_risk_score'], 1); ?>
                                    </span>
                                </h1>
                                <h3><?php echo get_risk_badge($assessment['inherent_risk_level']); ?></h3>
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa fa-clock-o"></i> Metadata</h3>
                            </div>
                            <div class="panel-body">
                                <p><strong>Created:</strong><br><?php echo format_datetime($assessment['created_at'], 'd M Y H:i'); ?></p>
                                <p><strong>Last Updated:</strong><br><?php echo format_datetime($assessment['updated_at'], 'd M Y H:i'); ?></p>
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
