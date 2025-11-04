<?php
/**
 * DPIA Approval Page
 * DPO reviews and approves/rejects submitted DPIAs
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

// Require login and DPO or Admin role
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$is_dpo = is_dpo();
$is_admin = is_admin();

// Only DPO or Admin can approve
if (!$is_dpo && !$is_admin) {
    set_flash_message('Access denied. Only DPO or Admin can approve DPIAs.', 'danger');
    redirect('dpia_list.php');
}

// Get DPIA ID from URL
$dpia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($dpia_id <= 0) {
    set_flash_message('Invalid DPIA ID.', 'danger');
    redirect('dpia_list.php');
}

// Handle approval/rejection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action'] ?? '');
    $approval_notes = sanitize_input($_POST['approval_notes'] ?? '');

    if (!in_array($action, ['approve', 'reject', 'rework'])) {
        set_flash_message('Invalid action.', 'danger');
        redirect("dpia_approve.php?id=$dpia_id");
    }

    // Determine new status
    $new_status = '';
    $message = '';

    switch ($action) {
        case 'approve':
            $new_status = 'approved';
            $message = 'DPIA has been approved successfully.';
            break;
        case 'reject':
            $new_status = 'rejected';
            $message = 'DPIA has been rejected.';
            break;
        case 'rework':
            $new_status = 'rework';
            $message = 'DPIA has been sent back for rework.';
            break;
    }

    // Update DPIA status
    $query = "UPDATE dpia SET
              status = ?,
              approver_id = ?,
              approval_notes = ?,
              approved_at = NOW()
              WHERE dpia_id = ? AND org_id = ?";

    $stmt = db_query($query, [$new_status, $user_id, $approval_notes, $dpia_id, $org_id]);

    if ($stmt) {
        // Log audit
        log_audit($org_id, $user_id, 'dpia', $dpia_id, 'update', "Status changed to: $new_status");

        // Get DPIA creator to send notification
        $dpia_query = "SELECT created_by, dpia_title FROM dpia WHERE dpia_id = ?";
        $dpia_result = db_fetch_one(db_query($dpia_query, [$dpia_id]));

        if ($dpia_result) {
            $creator_id = $dpia_result['created_by'];
            $dpia_title = $dpia_result['dpia_title'];

            // Create notification for creator
            $notif_title = "DPIA $new_status: $dpia_title";
            $notif_message = "Your DPIA has been $new_status by the DPO.";

            if (!empty($approval_notes)) {
                $notif_message .= " Notes: $approval_notes";
            }

            $priority = ($new_status === 'rejected' || $new_status === 'rework') ? 'high' : 'normal';

            // Map status to correct notification type
            $notification_type = 'dpia_' . $new_status;
            if ($new_status === 'rework') {
                $notification_type = 'dpia_revision_required';
            }

            create_notification(
                $org_id,
                $creator_id,
                $notification_type,
                $notif_title,
                $notif_message,
                'dpia',
                $dpia_id,
                "dpia_view.php?id=$dpia_id",
                $priority
            );
        }

        set_flash_message($message, 'success');
        redirect('dpia_list.php');
    } else {
        set_flash_message('Error updating DPIA status.', 'danger');
    }
}

// Fetch DPIA details
$query = "SELECT d.*,
          pa.activity_name,
          u.first_name as creator_first,
          u.last_name as creator_last,
          u.email as creator_email,
          dept.dept_name
          FROM dpia d
          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
          LEFT JOIN users u ON d.created_by = u.user_id
          LEFT JOIN departments dept ON pa.dept_id = dept.dept_id
          WHERE d.dpia_id = ? AND d.org_id = ?";

$stmt = db_query($query, [$dpia_id, $org_id]);
$dpia = db_fetch_one($stmt);

if (!$dpia) {
    set_flash_message('DPIA not found.', 'danger');
    redirect('dpia_list.php');
}

// Check if DPIA is in submitted status
if ($dpia['status'] !== 'submitted') {
    set_flash_message('This DPIA is not pending approval (Status: ' . $dpia['status'] . ').', 'warning');
    redirect("dpia_view.php?id=$dpia_id");
}

// Fetch all DPIA risks
$risks_query = "SELECT dr.*,
                u.first_name as responsible_first,
                u.last_name as responsible_last,
                (dr.likelihood * dr.impact) as inherent_score,
                (dr.residual_likelihood * dr.residual_impact) as residual_score
                FROM dpia_risks dr
                LEFT JOIN users u ON dr.responsible_user_id = u.user_id
                WHERE dr.dpia_id = ?
                ORDER BY (dr.likelihood * dr.impact) DESC";

$stmt = db_query($risks_query, [$dpia_id]);
$risks = db_fetch_all($stmt);

// Fetch step data
$steps_query = "SELECT * FROM dpia_steps WHERE dpia_id = ? ORDER BY step_number";
$stmt = db_query($steps_query, [$dpia_id]);
$steps = db_fetch_all($stmt);

$step_data = [];
foreach ($steps as $step) {
    $step_data[$step['step_number']] = json_decode($step['step_data'], true);
}

// Calculate risk statistics
$total_risks = count($risks);
$high_risks = 0;
$medium_risks = 0;
$low_risks = 0;

foreach ($risks as $risk) {
    $residual = $risk['residual_score'] ?? $risk['inherent_score'];
    if ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
        $high_risks++;
    } elseif ($residual >= 3) {
        $medium_risks++;
    } else {
        $low_risks++;
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Approve DPIA</title>
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
                        <h2>Approve DPIA</h2>
                        <h5>Review and approve Data Protection Impact Assessment</h5>
                    </div>
                </div>
                <hr />

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

    <!-- DPIA Overview Panel -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-file-text"></i> DPIA Overview</h3>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">DPIA Title:</th>
                                    <td><strong><?php echo htmlspecialchars($dpia['dpia_title']); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Processing Activity:</th>
                                    <td><?php echo htmlspecialchars($dpia['activity_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Department:</th>
                                    <td><?php echo htmlspecialchars($dpia['dept_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Created By:</th>
                                    <td>
                                        <?php echo htmlspecialchars($dpia['creator_first'] . ' ' . $dpia['creator_last']); ?>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($dpia['creator_email']); ?></small>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Submitted On:</th>
                                    <td><?php echo date('d M Y, H:i', strtotime($dpia['updated_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="40%">Screening Result:</th>
                                    <td>
                                        <?php if ($dpia['screening_result'] === 'required'): ?>
                                            <span class="label label-danger">DPIA Required</span>
                                        <?php else: ?>
                                            <span class="label label-info">DPIA Recommended</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Residual Risk Score:</th>
                                    <td>
                                        <?php
                                        $residual = $dpia['residual_risk_score'] ?? 0;
                                        $risk_class = 'success';
                                        $risk_label = 'Low Risk';

                                        if ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
                                            $risk_class = 'danger';
                                            $risk_label = 'High Risk';
                                        } elseif ($residual >= 3) {
                                            $risk_class = 'warning';
                                            $risk_label = 'Medium Risk';
                                        }
                                        ?>
                                        <span class="label label-<?php echo $risk_class; ?>" style="font-size: 14px;">
                                            <?php echo $residual; ?>/25 - <?php echo $risk_label; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Risks Identified:</th>
                                    <td>
                                        <strong><?php echo $total_risks; ?></strong> risks
                                        <div class="progress" style="margin-top: 5px; margin-bottom: 0;">
                                            <?php if ($high_risks > 0): ?>
                                                <div class="progress-bar progress-bar-danger" style="width: <?php echo ($high_risks / $total_risks * 100); ?>%">
                                                    <?php echo $high_risks; ?> High
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($medium_risks > 0): ?>
                                                <div class="progress-bar progress-bar-warning" style="width: <?php echo ($medium_risks / $total_risks * 100); ?>%">
                                                    <?php echo $medium_risks; ?> Med
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($low_risks > 0): ?>
                                                <div class="progress-bar progress-bar-success" style="width: <?php echo ($low_risks / $total_risks * 100); ?>%">
                                                    <?php echo $low_risks; ?> Low
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span class="label label-info">Pending Approval</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DPIA Description -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><i class="fa fa-info-circle"></i> Description & Screening Reason</h4>
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5><strong>Description:</strong></h5>
                            <p><?php echo nl2br(htmlspecialchars($dpia['description'] ?? 'N/A')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><strong>Screening Reason:</strong></h5>
                            <p><?php echo nl2br(htmlspecialchars($dpia['screening_reason'] ?? 'N/A')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risks Summary -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <i class="fa fa-exclamation-triangle"></i> Identified Risks & Mitigations
                        <span class="badge"><?php echo $total_risks; ?></span>
                    </h4>
                </div>
                <div class="panel-body">
                    <?php if (empty($risks)): ?>
                        <p class="text-muted">No risks identified.</p>
                    <?php else: ?>
                        <?php foreach ($risks as $index => $risk): ?>
                            <?php
                            $inherent = $risk['inherent_score'];
                            $residual = $risk['residual_score'];

                            $inherent_class = $inherent >= 6 ? 'danger' : ($inherent >= 3 ? 'warning' : 'success');
                            $residual_class = $residual >= RISK_ACCEPTABLE_THRESHOLD ? 'danger' : ($residual >= 3 ? 'warning' : 'success');
                            ?>
                            <div class="panel panel-default" style="border-left: 4px solid #<?php echo $residual_class === 'danger' ? 'f44336' : ($residual_class === 'warning' ? 'ff9800' : '4caf50'); ?>;">
                                <div class="panel-heading" style="background-color: #f9f9f9;">
                                    <h5 style="margin: 0;">
                                        <strong>Risk #<?php echo $index + 1; ?>:</strong>
                                        <?php echo htmlspecialchars($risk['risk_title']); ?>
                                        <span class="pull-right">
                                            <span class="label label-<?php echo $residual_class; ?>">
                                                Residual: <?php echo $residual; ?>/25
                                            </span>
                                        </span>
                                    </h5>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($risk['risk_description'])); ?></p>
                                            <p><strong>Category:</strong>
                                                <span class="label label-default"><?php echo htmlspecialchars($risk['risk_category']); ?></span>
                                            </p>

                                            <?php if (!empty($risk['mitigation_measures'])): ?>
                                                <p><strong>Mitigation Measures:</strong></p>
                                                <div class="well well-sm">
                                                    <?php echo nl2br(htmlspecialchars($risk['mitigation_measures'])); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($risk['responsible_first'])): ?>
                                                <p><strong>Responsible Person:</strong>
                                                    <?php echo htmlspecialchars($risk['responsible_first'] . ' ' . $risk['responsible_last']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-4">
                                            <table class="table table-bordered table-condensed">
                                                <tr class="active">
                                                    <th colspan="2" class="text-center">Inherent Risk (Before Mitigation)</th>
                                                </tr>
                                                <tr>
                                                    <th>Likelihood:</th>
                                                    <td><?php echo $risk['likelihood']; ?>/5</td>
                                                </tr>
                                                <tr>
                                                    <th>Impact:</th>
                                                    <td><?php echo $risk['impact']; ?>/5</td>
                                                </tr>
                                                <tr>
                                                    <th>Score:</th>
                                                    <td>
                                                        <span class="label label-<?php echo $inherent_class; ?>">
                                                            <?php echo $inherent; ?>/25
                                                        </span>
                                                    </td>
                                                </tr>
                                                <tr class="active">
                                                    <th colspan="2" class="text-center">Residual Risk (After Mitigation)</th>
                                                </tr>
                                                <tr>
                                                    <th>Likelihood:</th>
                                                    <td><?php echo $risk['residual_likelihood'] ?? '-'; ?>/5</td>
                                                </tr>
                                                <tr>
                                                    <th>Impact:</th>
                                                    <td><?php echo $risk['residual_impact'] ?? '-'; ?>/5</td>
                                                </tr>
                                                <tr>
                                                    <th>Score:</th>
                                                    <td>
                                                        <span class="label label-<?php echo $residual_class; ?>">
                                                            <?php echo $residual; ?>/25
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultation Information -->
    <?php if (isset($step_data[6])): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title"><i class="fa fa-users"></i> Consultation & Sign-off</h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><strong>DPO Consulted:</strong></h5>
                                <p><?php echo htmlspecialchars($step_data[6]['dpo_consulted'] ?? 'N/A'); ?></p>

                                <h5><strong>DPO Feedback:</strong></h5>
                                <p><?php echo nl2br(htmlspecialchars($step_data[6]['dpo_feedback'] ?? 'N/A')); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h5><strong>Stakeholders Consulted:</strong></h5>
                                <p><?php echo nl2br(htmlspecialchars($step_data[6]['stakeholders_consulted'] ?? 'N/A')); ?></p>

                                <h5><strong>Consultation Notes:</strong></h5>
                                <p><?php echo nl2br(htmlspecialchars($step_data[6]['consultation_notes'] ?? 'N/A')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Approval Form -->
    <div class="row">
        <div class="col-md-12">
            <form method="POST" action="dpia_approve.php?id=<?php echo $dpia_id; ?>" id="approvalForm">
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h4 class="panel-title"><i class="fa fa-pencil"></i> DPO Decision & Notes</h4>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="approval_notes">Approval Notes / Comments <span class="text-danger">*</span></label>
                            <textarea name="approval_notes" id="approval_notes" class="form-control" rows="5" required
                                placeholder="Provide detailed notes about your decision, any concerns, recommendations, or additional measures required..."></textarea>
                            <p class="help-block">
                                These notes will be visible to the DPIA creator and will be included in the DPIA record.
                            </p>
                        </div>

                        <hr>

                        <h5><strong>DPO Decision:</strong></h5>
                        <p class="text-muted">
                            Review all risks and mitigations carefully before making a decision.
                            High residual risks (score &ge; <?php echo RISK_ACCEPTABLE_THRESHOLD; ?>) require additional justification if approving.
                        </p>

                        <div class="btn-group btn-group-lg" role="group">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-lg"
                                    onclick="return confirm('Are you sure you want to APPROVE this DPIA?');">
                                <i class="fa fa-check-circle"></i> Approve DPIA
                            </button>
                            <button type="submit" name="action" value="rework" class="btn btn-warning btn-lg"
                                    onclick="return confirm('Send this DPIA back for rework?');">
                                <i class="fa fa-repeat"></i> Request Rework
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-lg"
                                    onclick="return confirm('Are you sure you want to REJECT this DPIA? This action indicates the processing should not proceed.');">
                                <i class="fa fa-times-circle"></i> Reject DPIA
                            </button>
                        </div>
                    </div>
                    <div class="panel-footer">
                        <a href="dpia_view.php?id=<?php echo $dpia_id; ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to View
                        </a>
                        <a href="dpia_list.php" class="btn btn-default">
                            <i class="fa fa-list"></i> Back to List
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
// Form validation
document.getElementById('approvalForm').addEventListener('submit', function(e) {
    var notes = document.getElementById('approval_notes').value.trim();

    if (notes.length < 10) {
        e.preventDefault();
        alert('Please provide detailed approval notes (at least 10 characters).');
        return false;
    }
});
</script>


            </div>
        </div>
    </div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
