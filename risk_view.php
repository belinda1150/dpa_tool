<?php
/**
 * View Risk Page
 * Display complete risk details with treatment plan and history
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get risk ID from URL
$risk_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($risk_id <= 0) {
    set_flash_message('Invalid risk ID.', 'danger');
    redirect('risk_list.php');
}

// Fetch risk details
$query = "SELECT r.*,
          u.first_name as owner_first,
          u.last_name as owner_last,
          u.email as owner_email,
          creator.first_name as creator_first,
          creator.last_name as creator_last,
          dept.dept_name,
          (r.likelihood * r.impact) as inherent_score,
          (r.residual_likelihood * r.residual_impact) as residual_score
          FROM risks r
          LEFT JOIN users u ON r.owner_id = u.user_id
          LEFT JOIN users creator ON r.created_by = creator.user_id
          LEFT JOIN departments dept ON r.dept_id = dept.dept_id
          WHERE r.risk_id = ? AND r.org_id = ?";

$stmt = db_query($query, [$risk_id, $org_id]);
$risk = db_fetch_one($stmt);

if (!$risk) {
    set_flash_message('Risk not found.', 'danger');
    redirect('risk_list.php');
}

// Calculate risk scores
$inherent_score = $risk['inherent_score'];
$residual_score = $risk['residual_score'] ?? $inherent_score;

// Determine risk levels
function get_risk_level($score) {
    if ($score >= 15) {
        return ['level' => 'Critical', 'class' => 'danger'];
    } elseif ($score >= RISK_ACCEPTABLE_THRESHOLD) {
        return ['level' => 'High', 'class' => 'warning'];
    } elseif ($score >= 3) {
        return ['level' => 'Medium', 'class' => 'info'];
    } else {
        return ['level' => 'Low', 'class' => 'success'];
    }
}

$inherent_level = get_risk_level($inherent_score);
$residual_level = get_risk_level($residual_score);

// Fetch audit log for this risk
$audit_query = "SELECT al.*, u.first_name, u.last_name
                FROM audit_log al
                LEFT JOIN users u ON al.user_id = u.user_id
                WHERE al.entity_type = 'risk' AND al.entity_id = ?
                ORDER BY al.created_at DESC
                LIMIT 10";
$stmt = db_query($audit_query, [$risk_id]);
$audit_logs = db_fetch_all($stmt);

// Fetch linked controls
$controls_query = "SELECT rc.*, c.control_name, c.control_type, c.framework_ref
                   FROM risk_controls rc
                   JOIN controls c ON rc.control_id = c.control_id
                   WHERE rc.risk_id = ?
                   ORDER BY c.control_name";
$stmt = db_query($controls_query, [$risk_id]);
$linked_controls = db_fetch_all($stmt);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Risk</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
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
                        <h2>View Risk</h2>
                        <h5>RISK-<?php echo str_pad($risk_id, 4, '0', STR_PAD_LEFT); ?></h5>
                    </div>
                </div>
                <hr />

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type']); ?> alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php
                echo htmlspecialchars($_SESSION['flash_message']);
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Risk Summary Panel -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-warning"></i> <?php echo htmlspecialchars($risk['risk_title']); ?>
                        <span class="float-end">
                            <?php
                            $status_labels = [
                                'open' => 'danger',
                                'mitigated' => 'warning',
                                'accepted' => 'info',
                                'closed' => 'success'
                            ];
                            $status_class = $status_labels[$risk['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $status_class; ?>">
                                <?php echo strtoupper($risk['status']); ?>
                            </span>
                        </span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-bordered">
                                <tr>
                                    <th width="25%">Risk ID:</th>
                                    <td><strong>RISK-<?php echo str_pad($risk_id, 4, '0', STR_PAD_LEFT); ?></strong></td>
                                </tr>
                                <tr>
                                    <th>Category:</th>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($risk['risk_category']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department:</th>
                                    <td><?php echo htmlspecialchars($risk['dept_name'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <th>Risk Owner:</th>
                                    <td>
                                        <?php if ($risk['owner_first']): ?>
                                            <?php echo htmlspecialchars($risk['owner_first'] . ' ' . $risk['owner_last']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($risk['owner_email']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Not Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Risk Source:</th>
                                    <td><?php echo htmlspecialchars($risk['risk_source'] ?: 'N/A'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-<?php echo $residual_level['class']; ?>">
                                <div class="card-header text-center">
                                    <h4 style="margin: 0;">Current Risk Level</h4>
                                </div>
                                <div class="card-body text-center">
                                    <h1 style="margin: 10px 0; font-size: 48px;">
                                        <?php echo $residual_score; ?><small>/25</small>
                                    </h1>
                                    <h4><?php echo $residual_level['level']; ?> Risk</h4>
                                </div>
                            </div>

                            <?php if ($risk['review_date']): ?>
                                <?php
                                $review_date = strtotime($risk['review_date']);
                                $today = strtotime('today');
                                $is_overdue = $review_date < $today;
                                $is_soon = $review_date <= strtotime('+7 days');
                                ?>
                                <div class="alert alert-<?php echo $is_overdue ? 'danger' : ($is_soon ? 'warning' : 'info'); ?>">
                                    <strong>Next Review:</strong><br>
                                    <?php echo date('d M Y', $review_date); ?>
                                    <?php if ($is_overdue): ?>
                                        <br><span class="badge bg-danger">OVERDUE</span>
                                    <?php elseif ($is_soon): ?>
                                        <br><span class="badge bg-warning text-dark">DUE SOON</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Description -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="fa fa-file-text"></i> Risk Description</h4>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($risk['risk_description'])); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Assessment -->
    <div class="row">
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header">
                    <h4 class="card-title"><i class="fa fa-line-chart"></i> Inherent Risk (Before Treatment)</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Likelihood:</th>
                            <td>
                                <strong><?php echo $risk['likelihood']; ?>/5</strong>
                                <?php
                                $likelihood_labels = [
                                    1 => 'Very Low (Rare)',
                                    2 => 'Low (Unlikely)',
                                    3 => 'Medium (Possible)',
                                    4 => 'High (Likely)',
                                    5 => 'Very High (Almost Certain)'
                                ];
                                echo ' - ' . $likelihood_labels[$risk['likelihood']];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Impact:</th>
                            <td>
                                <strong><?php echo $risk['impact']; ?>/5</strong>
                                <?php
                                $impact_labels = [
                                    1 => 'Very Low (Negligible)',
                                    2 => 'Low (Minor)',
                                    3 => 'Medium (Moderate)',
                                    4 => 'High (Major)',
                                    5 => 'Very High (Catastrophic)'
                                ];
                                echo ' - ' . $impact_labels[$risk['impact']];
                                ?>
                            </td>
                        </tr>
                        <tr class="<?php echo $inherent_level['class']; ?>">
                            <th>Inherent Risk Score:</th>
                            <td>
                                <span class="badge bg-<?php echo $inherent_level['class']; ?>" style="font-size: 14px;">
                                    <?php echo $inherent_score; ?>/25 - <?php echo $inherent_level['level']; ?> Risk
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header">
                    <h4 class="card-title"><i class="fa fa-shield"></i> Residual Risk (After Treatment)</h4>
                </div>
                <div class="card-body">
                    <?php if ($risk['residual_likelihood'] && $risk['residual_impact']): ?>
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">Likelihood:</th>
                                <td>
                                    <strong><?php echo $risk['residual_likelihood']; ?>/5</strong>
                                    <?php echo ' - ' . $likelihood_labels[$risk['residual_likelihood']]; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Impact:</th>
                                <td>
                                    <strong><?php echo $risk['residual_impact']; ?>/5</strong>
                                    <?php echo ' - ' . $impact_labels[$risk['residual_impact']]; ?>
                                </td>
                            </tr>
                            <tr class="<?php echo $residual_level['class']; ?>">
                                <th>Residual Risk Score:</th>
                                <td>
                                    <span class="badge bg-<?php echo $residual_level['class']; ?>" style="font-size: 14px;">
                                        <?php echo $residual_score; ?>/25 - <?php echo $residual_level['level']; ?> Risk
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <?php
                        // Calculate risk reduction
                        $reduction = $inherent_score - $residual_score;
                        $reduction_pct = round(($reduction / $inherent_score) * 100);
                        ?>
                        <?php if ($reduction > 0): ?>
                            <div class="alert alert-success" style="margin-bottom: 0;">
                                <i class="fa fa-check-circle"></i>
                                <strong>Risk Reduction:</strong> <?php echo $reduction; ?> points (<?php echo $reduction_pct; ?>% reduction)
                            </div>
                        <?php elseif ($reduction < 0): ?>
                            <div class="alert alert-warning" style="margin-bottom: 0;">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>Note:</strong> Residual risk is higher than inherent risk. Review treatment plan.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" style="margin-bottom: 0;">
                                <i class="fa fa-info-circle"></i>
                                <strong>Note:</strong> No risk reduction. Consider additional mitigations.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">Residual risk has not been assessed yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Treatment Plan -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-info">
                <div class="card-header">
                    <h4 class="card-title"><i class="fa fa-shield"></i> Risk Treatment Plan</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="25%">Treatment Strategy:</th>
                            <td>
                                <?php
                                $strategy_labels = [
                                    'mitigate' => ['label' => 'Mitigate', 'class' => 'primary', 'icon' => 'shield'],
                                    'accept' => ['label' => 'Accept', 'class' => 'info', 'icon' => 'check-circle'],
                                    'transfer' => ['label' => 'Transfer', 'class' => 'warning', 'icon' => 'exchange'],
                                    'avoid' => ['label' => 'Avoid', 'class' => 'success', 'icon' => 'ban']
                                ];
                                $strategy = $strategy_labels[$risk['treatment_strategy']] ?? ['label' => 'Unknown', 'class' => 'default', 'icon' => 'question'];
                                ?>
                                <span class="badge bg-<?php echo $strategy['class']; ?>">
                                    <i class="fa fa-<?php echo $strategy['icon']; ?>"></i>
                                    <?php echo strtoupper($strategy['label']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Treatment Description:</th>
                            <td><?php echo nl2br(htmlspecialchars($risk['treatment_description'] ?: 'N/A')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Linked Controls -->
    <div class="row">
        <div class="col-md-12">
            <div class="card border-success">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="card-title"><i class="fa fa-shield"></i> Linked Controls (<?php echo count($linked_controls); ?>)</h4>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="risk_controls_manage.php?id=<?php echo $risk_id; ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Manage Controls
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($linked_controls)): ?>
                        <div class="alert alert-info" style="margin-bottom: 0;">
                            <i class="fa fa-info-circle"></i> No controls linked to this risk yet.
                            <a href="risk_controls_manage.php?id=<?php echo $risk_id; ?>">Add controls</a> to mitigate this risk.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Control Name</th>
                                        <th>Type</th>
                                        <th>Framework</th>
                                        <th>Status</th>
                                        <th>Effectiveness</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($linked_controls as $lc): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($lc['control_name']); ?></strong></td>
                                        <td>
                                            <?php
                                            $type_class = '';
                                            switch ($lc['control_type']) {
                                                case 'preventive': $type_class = 'primary'; break;
                                                case 'detective': $type_class = 'info'; break;
                                                case 'corrective': $type_class = 'warning'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $type_class; ?>">
                                                <?php echo ucfirst($lc['control_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($lc['framework_ref'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($lc['implementation_status']) {
                                                case 'planned': $status_class = 'secondary'; break;
                                                case 'in_progress': $status_class = 'info'; break;
                                                case 'implemented': $status_class = 'success'; break;
                                                case 'verified': $status_class = 'primary'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $lc['implementation_status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($lc['effectiveness']): ?>
                                            <span class="badge bg-<?php echo $lc['effectiveness'] == 'high' ? 'success' : ($lc['effectiveness'] == 'medium' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($lc['effectiveness']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Not assessed</span>
                                            <?php endif; ?>
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

    <!-- Audit Log -->
    <?php if (!empty($audit_logs)): ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title"><i class="fa fa-history"></i> Activity History</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-condensed">
                                <thead>
                                    <tr>
                                        <th>Date/Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audit_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?>
                                            </td>
                                            <td>
                                                <?php
                                                $action_labels = [
                                                    'create' => ['label' => 'Created', 'class' => 'success'],
                                                    'update' => ['label' => 'Updated', 'class' => 'info'],
                                                    'delete' => ['label' => 'Deleted', 'class' => 'danger']
                                                ];
                                                $action = $action_labels[$log['action']] ?? ['label' => 'Action', 'class' => 'default'];
                                                ?>
                                                <span class="badge bg-<?php echo $action['class']; ?>">
                                                    <?php echo $action['label']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['details'] ?: '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Metadata -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="fa fa-info-circle"></i> Metadata</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Created By:</strong> <?php echo htmlspecialchars($risk['creator_first'] . ' ' . $risk['creator_last']); ?></p>
                            <p><strong>Created On:</strong> <?php echo date('d M Y, H:i', strtotime($risk['created_at'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Last Updated:</strong> <?php echo date('d M Y, H:i', strtotime($risk['updated_at'])); ?></p>
                            <?php if ($risk['review_date']): ?>
                                <p><strong>Next Review:</strong> <?php echo date('d M Y', strtotime($risk['review_date'])); ?></p>
                            <?php endif; ?>
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
                    <a href="risk_edit.php?id=<?php echo $risk_id; ?>" class="btn btn-warning">
                        <i class="fa fa-edit"></i> Edit Risk
                    </a>
                    <a href="risk_controls_manage.php?id=<?php echo $risk_id; ?>" class="btn btn-primary">
                        <i class="fa fa-shield"></i> Manage Controls
                    </a>
                    <a href="risk_list.php" class="btn btn-secondary">
                        <i class="fa fa-list"></i> Back to List
                    </a>
                    <a href="risk_export.php?id=<?php echo $risk_id; ?>" class="btn btn-primary float-end">
                        <i class="fa fa-download"></i> Export Risk
                    </a>
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
</body>
</html>
