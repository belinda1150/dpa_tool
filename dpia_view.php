<?php
/**
 * DPA Tool - View DPIA
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();
$dpia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$dpia_id) {
    set_flash_message('Invalid DPIA entry.', 'error');
    redirect('dpia_list.php');
}

// Get DPIA entry
$query = "SELECT d.*, pa.activity_name, u.first_name, u.last_name, u.email,
          approver.first_name as approver_first, approver.last_name as approver_last
          FROM dpia d
          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
          LEFT JOIN users u ON d.created_by = u.user_id
          LEFT JOIN users approver ON d.approver_id = approver.user_id
          WHERE d.dpia_id = ? AND d.org_id = ?";
$stmt = db_query($query, [$dpia_id, $org_id]);
$dpia = db_fetch_one($stmt);

if (!$dpia) {
    set_flash_message('DPIA not found.', 'error');
    redirect('dpia_list.php');
}

// Get all DPIA steps
$query = "SELECT * FROM dpia_steps WHERE dpia_id = ? ORDER BY step_number";
$stmt = db_query($query, [$dpia_id]);
$steps = db_fetch_all($stmt);

// Get all risks
$query = "SELECT dr.*, u.first_name, u.last_name
          FROM dpia_risks dr
          LEFT JOIN users u ON dr.responsible_user_id = u.user_id
          WHERE dr.dpia_id = ?
          ORDER BY dr.dpia_risk_id";
$stmt = db_query($query, [$dpia_id]);
$risks = db_fetch_all($stmt);

// Parse step data
$step_data = [];
foreach ($steps as $step) {
    $step_data[$step['step_number']] = json_decode($step['step_data'], true);
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View DPIA</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .section-title {
            background-color: #f5f5f5;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #00a950;
            font-weight: bold;
        }
        .info-row {
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fafafa;
            border-left: 3px solid #ddd;
        }
        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        .risk-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            background-color: #fff;
        }
        .risk-score {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 18px;
        }
        .risk-high { background-color: #d9534f; color: white; }
        .risk-medium { background-color: #f0ad4e; color: white; }
        .risk-low { background-color: #5cb85c; color: white; }
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
                        <h2>Data Protection Impact Assessment (DPIA)</h2>
                        <h5><?php echo htmlspecialchars($dpia['dpia_title']); ?></h5>
                    </div>
                </div>
                <hr />

                <div class="row">
                    <div class="col-md-12">
                        <!-- Action Buttons -->
                        <div class="btn-group pull-right" style="margin-bottom: 15px;">
                            <?php if ($dpia['status'] == 'draft' || $dpia['status'] == 'rework'): ?>
                                <a href="dpia_wizard.php?id=<?php echo $dpia_id; ?>" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Continue Editing
                                </a>
                            <?php endif; ?>
                            <?php if (is_dpo() && $dpia['status'] == 'submitted'): ?>
                                <a href="dpia_approve.php?id=<?php echo $dpia_id; ?>" class="btn btn-success">
                                    <i class="fa fa-check"></i> Review & Approve
                                </a>
                            <?php endif; ?>
                            <a href="dpia_export.php?id=<?php echo $dpia_id; ?>" class="btn btn-primary">
                                <i class="fa fa-download"></i> Export PDF
                            </a>
                            <a href="dpia_list.php" class="btn btn-default">
                                <i class="fa fa-arrow-left"></i> Back to List
                            </a>
                        </div>
                        <div class="clearfix"></div>

                        <!-- Status Panel -->
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Status:</strong>
                                        <?php
                                        $status_class = '';
                                        switch ($dpia['status']) {
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
                                        <span class="label label-<?php echo $status_class; ?> label-lg">
                                            <?php echo strtoupper($dpia['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Residual Risk Score:</strong>
                                        <?php
                                        $risk_class = 'low';
                                        if ($dpia['residual_risk_score'] >= RISK_ACCEPTABLE_THRESHOLD) {
                                            $risk_class = 'high';
                                        } elseif ($dpia['residual_risk_score'] >= 3) {
                                            $risk_class = 'medium';
                                        }
                                        ?>
                                        <span class="risk-score risk-<?php echo $risk_class; ?>">
                                            <?php echo $dpia['residual_risk_score'] ?? 'N/A'; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Linked ROPA:</strong>
                                        <?php if ($dpia['activity_name']): ?>
                                            <a href="ropa_view.php?id=<?php echo $dpia['ropa_id']; ?>">
                                                <?php echo htmlspecialchars($dpia['activity_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Screening Result:</strong>
                                        <?php if ($dpia['screening_result'] == 'needed'): ?>
                                            <span class="label label-warning">DPIA Needed</span>
                                        <?php else: ?>
                                            <span class="label label-default">Not Needed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Basic Information -->
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> Basic Information
                            </div>
                            <div class="panel-body">
                                <div class="info-row">
                                    <div class="info-label">DPIA Title:</div>
                                    <div><?php echo htmlspecialchars($dpia['dpia_title']); ?></div>
                                </div>

                                <?php if ($dpia['description']): ?>
                                <div class="info-row">
                                    <div class="info-label">Description:</div>
                                    <div><?php echo nl2br(htmlspecialchars($dpia['description'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if ($dpia['screening_reason']): ?>
                                <div class="info-row">
                                    <div class="info-label">Screening Justification:</div>
                                    <div><?php echo nl2br(htmlspecialchars($dpia['screening_reason'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Processing Description (Step 2) -->
                        <?php if (!empty($step_data[2])): ?>
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-file-text"></i> Processing Description
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($step_data[2]['processing_description'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Processing Description:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[2]['processing_description'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($step_data[2]['necessity_justification'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Necessity & Proportionality:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[2]['necessity_justification'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($step_data[2]['data_minimization'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Data Minimization Measures:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[2]['data_minimization'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($step_data[2]['proportionality'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Proportionality Assessment:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[2]['proportionality'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Risks & Mitigations -->
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <i class="fa fa-exclamation-triangle"></i> Identified Risks & Mitigation Measures
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($risks)): ?>
                                    <?php foreach ($risks as $index => $risk): ?>
                                        <div class="risk-card">
                                            <h4 style="margin-top: 0;">
                                                Risk #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($risk['risk_title']); ?>
                                                <span class="label label-info pull-right">
                                                    <?php echo ucfirst($risk['risk_category']); ?>
                                                </span>
                                            </h4>

                                            <?php if ($risk['risk_description']): ?>
                                            <p><strong>Description:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($risk['risk_description'])); ?>
                                            </p>
                                            <?php endif; ?>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5>Inherent Risk (Before Mitigation)</h5>
                                                    <table class="table table-bordered table-condensed">
                                                        <tr>
                                                            <th>Likelihood</th>
                                                            <td><?php echo $risk['likelihood']; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Impact</th>
                                                            <td><?php echo $risk['impact']; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Inherent Score</th>
                                                            <td><strong><?php echo $risk['inherent_score']; ?></strong></td>
                                                        </tr>
                                                    </table>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>Residual Risk (After Mitigation)</h5>
                                                    <table class="table table-bordered table-condensed">
                                                        <tr>
                                                            <th>Likelihood</th>
                                                            <td><?php echo $risk['residual_likelihood'] ?? 'N/A'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Impact</th>
                                                            <td><?php echo $risk['residual_impact'] ?? 'N/A'; ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Residual Score</th>
                                                            <td>
                                                                <?php
                                                                $res_score = $risk['residual_score'] ?? 0;
                                                                $res_class = 'low';
                                                                if ($res_score >= RISK_ACCEPTABLE_THRESHOLD) {
                                                                    $res_class = 'high';
                                                                } elseif ($res_score >= 3) {
                                                                    $res_class = 'medium';
                                                                }
                                                                ?>
                                                                <span class="risk-score risk-<?php echo $res_class; ?>">
                                                                    <?php echo $res_score; ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>

                                            <?php if ($risk['mitigation_measures']): ?>
                                            <div class="alert alert-success">
                                                <strong><i class="fa fa-shield"></i> Mitigation Measures:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($risk['mitigation_measures'])); ?>
                                            </div>
                                            <?php endif; ?>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <strong>Responsible Person:</strong>
                                                    <?php if ($risk['first_name']): ?>
                                                        <?php echo htmlspecialchars($risk['first_name'] . ' ' . $risk['last_name']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not assigned</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Target Date:</strong>
                                                    <?php if ($risk['target_date']): ?>
                                                        <?php echo format_date($risk['target_date'], 'd M Y'); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not set</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No risks identified.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Consultation (Step 6) -->
                        <?php if (!empty($step_data[6])): ?>
                        <div class="panel panel-success">
                            <div class="panel-heading">
                                <i class="fa fa-users"></i> Consultation & Evidence
                            </div>
                            <div class="panel-body">
                                <div class="info-row">
                                    <div class="info-label">DPO Consulted:</div>
                                    <div>
                                        <?php if (!empty($step_data[6]['dpo_consulted'])): ?>
                                            <i class="fa fa-check-circle text-success"></i> Yes
                                        <?php else: ?>
                                            <i class="fa fa-times-circle text-danger"></i> No
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($step_data[6]['stakeholders_consulted'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Other Stakeholders Consulted:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[6]['stakeholders_consulted'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($step_data[6]['consultation_outcome'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Consultation Outcome:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[6]['consultation_outcome'])); ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($step_data[6]['evidence_notes'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Evidence & Documentation:</div>
                                    <div><?php echo nl2br(htmlspecialchars($step_data[6]['evidence_notes'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Approval Information -->
                        <?php if ($dpia['status'] == 'approved' || $dpia['status'] == 'rejected' || $dpia['status'] == 'rework'): ?>
                        <div class="panel panel-<?php echo $dpia['status'] == 'approved' ? 'success' : 'warning'; ?>">
                            <div class="panel-heading">
                                <i class="fa fa-check-square-o"></i> DPO Review & Approval
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Reviewed By:</div>
                                        <div><?php echo htmlspecialchars($dpia['approver_first'] . ' ' . $dpia['approver_last']); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Approval Date:</div>
                                        <div><?php echo format_datetime($dpia['approved_at'], 'd M Y H:i'); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Decision:</div>
                                        <div>
                                            <span class="label label-<?php echo $status_class; ?> label-lg">
                                                <?php echo strtoupper($dpia['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($dpia['approval_notes']): ?>
                                <div class="info-row" style="margin-top: 15px;">
                                    <div class="info-label">DPO Notes:</div>
                                    <div><?php echo nl2br(htmlspecialchars($dpia['approval_notes'])); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Metadata -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-clock-o"></i> Record Information
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="info-label">Created By:</div>
                                        <div><?php echo htmlspecialchars($dpia['first_name'] . ' ' . $dpia['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($dpia['email']); ?></small>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Created Date:</div>
                                        <div><?php echo format_datetime($dpia['created_at'], 'd M Y H:i'); ?></div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="info-label">Last Updated:</div>
                                        <div><?php echo format_datetime($dpia['updated_at'], 'd M Y H:i'); ?></div>
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
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
