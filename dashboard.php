<?php
/**
 * DPA Tool - Dashboard
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

// Check if password reset is required
if (isset($_SESSION['dpa_password_reset_required']) && $_SESSION['dpa_password_reset_required'] === true) {
    redirect('password_reset.php');
}

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Get dashboard statistics
$stats = [];

// ROPA Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) as validated,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN has_special_categories = 1 THEN 1 ELSE 0 END) as with_special_categories
    FROM processing_activities WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['ropa'] = db_fetch_one($stmt);

// DPIA Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending_approval,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM dpia WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['dpia'] = db_fetch_one($stmt);

// Risk Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN residual_score >= " . RISK_ACCEPTABLE_THRESHOLD . " THEN 1 ELSE 0 END) as high_risk,
    SUM(CASE WHEN due_date < CURDATE() AND status != 'closed' THEN 1 ELSE 0 END) as overdue
    FROM risks WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['risks'] = db_fetch_one($stmt);

// Incident Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('open', 'investigating') THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN notifiable = 1 AND notified_at IS NULL THEN 1 ELSE 0 END) as pending_notification,
    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical
    FROM incidents WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['incidents'] = db_fetch_one($stmt);

// DSR Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('received', 'in_progress') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN due_at < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM dsr_requests WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['dsr'] = db_fetch_one($stmt);

// Consent Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'granted' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN expires_at <= DATE_ADD(NOW(), INTERVAL " . CONSENT_EXPIRY_ALERT_DAYS . " DAY) AND status = 'granted' THEN 1 ELSE 0 END) as expiring_soon
    FROM consents WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['consents'] = db_fetch_one($stmt);

// Cross-Border Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_high_risk = 1 THEN 1 ELSE 0 END) as high_risk
    FROM cross_border_transfers WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$stats['crossborder'] = db_fetch_one($stmt);

// Calculate Compliance Score
$compliance_score = 0;
$max_score = 100;

// ROPA completion (20 points)
if ($stats['ropa']['total'] > 0) {
    $compliance_score += ($stats['ropa']['validated'] / $stats['ropa']['total']) * 20;
}

// DPIA completion (15 points)
if ($stats['dpia']['total'] > 0) {
    $compliance_score += ($stats['dpia']['approved'] / $stats['dpia']['total']) * 15;
}

// Risk management (15 points)
if ($stats['risks']['total'] > 0) {
    $closed_risks = $stats['risks']['total'] - $stats['risks']['open'];
    $compliance_score += ($closed_risks / $stats['risks']['total']) * 15;
}

// Incident response (15 points)
if ($stats['incidents']['total'] > 0) {
    $resolved_incidents = $stats['incidents']['total'] - $stats['incidents']['active'];
    $compliance_score += ($resolved_incidents / $stats['incidents']['total']) * 15;
} else {
    $compliance_score += 15; // No incidents is good
}

// DSR completion (15 points)
if ($stats['dsr']['total'] > 0) {
    $compliance_score += ($stats['dsr']['completed'] / $stats['dsr']['total']) * 15;
} else {
    $compliance_score += 15; // No pending DSRs is good
}

// Consent management (10 points)
if ($stats['consents']['total'] > 0) {
    $compliance_score += ($stats['consents']['active'] / $stats['consents']['total']) * 10;
}

// Cross-border compliance (10 points)
if ($stats['crossborder']['total'] > 0) {
    $compliance_score += ($stats['crossborder']['approved'] / $stats['crossborder']['total']) * 10;
} else {
    $compliance_score += 10; // No cross-border transfers is fine
}

$compliance_score = round($compliance_score, 2);

// Get recent activities for timeline
$query = "SELECT al.entity_type, al.entity_id, al.action, al.created_at, u.first_name, u.last_name
          FROM audit_log al
          LEFT JOIN users u ON al.user_id = u.user_id
          WHERE al.org_id = ?
          ORDER BY al.created_at DESC
          LIMIT 10";
$stmt = db_query($query, [$org_id]);
$recent_activities = db_fetch_all($stmt);

// Get notifications
$query = "SELECT * FROM notifications
          WHERE org_id = ? AND (user_id = ? OR user_id IS NULL) AND is_read = 0
          ORDER BY priority DESC, created_at DESC
          LIMIT 5";
$stmt = db_query($query, [$org_id, $user_id]);
$notifications = db_fetch_all($stmt);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/dashboard-custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Dashboard</h2>
                        <h5>Compliance Overview - <?php echo date('F Y'); ?></h5>
                    </div>
                </div>
                <hr />

                <!-- Compliance Score -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading panel-compliance-header">
                                <h4><i class="fa fa-check-circle"></i> Overall Compliance Score</h4>
                            </div>
                            <div class="panel-body text-center">
                                <h1 class="compliance-score <?php echo $compliance_score >= 80 ? 'compliance-score-excellent' : ($compliance_score >= 60 ? 'compliance-score-good' : 'compliance-score-poor'); ?>">
                                    <?php echo $compliance_score; ?>%
                                </h1>
                                <div class="progress compliance-progress">
                                    <div class="progress-bar <?php echo $compliance_score >= 80 ? 'progress-bar-success' : ($compliance_score >= 60 ? 'progress-bar-warning' : 'progress-bar-danger'); ?>"
                                         role="progressbar"
                                         style="width: <?php echo $compliance_score; ?>%;">
                                        <?php echo $compliance_score; ?>%
                                    </div>
                                </div>
                                <p class="compliance-status">
                                    <?php
                                    if ($compliance_score >= 80) {
                                        echo '<i class="fa fa-check-circle status-icon-excellent"></i> Excellent compliance status';
                                    } elseif ($compliance_score >= 60) {
                                        echo '<i class="fa fa-exclamation-triangle status-icon-good"></i> Good, but needs improvement';
                                    } else {
                                        echo '<i class="fa fa-times-circle status-icon-poor"></i> Requires immediate attention';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row">
                    <!-- ROPA -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box bg-color-blue set-icon">
                                <i class="fa fa-list-alt"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['ropa']['total']; ?></p>
                                <p class="text-muted">Processing Activities</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['ropa']['validated']; ?> Validated |
                                    <?php echo $stats['ropa']['draft']; ?> Draft
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- DPIA -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box bg-color-green set-icon">
                                <i class="fa fa-shield"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['dpia']['total']; ?></p>
                                <p class="text-muted">DPIAs</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['dpia']['pending_approval']; ?> Pending Approval
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Risks -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box bg-color-brown set-icon">
                                <i class="fa fa-exclamation-triangle"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['risks']['open']; ?></p>
                                <p class="text-muted">Open Risks</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['risks']['high_risk']; ?> High Risk |
                                    <?php echo $stats['risks']['overdue']; ?> Overdue
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Incidents -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box bg-color-red set-icon">
                                <i class="fa fa-warning"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['incidents']['active']; ?></p>
                                <p class="text-muted">Active Incidents</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['incidents']['pending_notification']; ?> Pending Notification
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- DSR Requests -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box set-icon icon-box-purple">
                                <i class="fa fa-user-circle"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['dsr']['pending']; ?></p>
                                <p class="text-muted">Pending DSR</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['dsr']['overdue']; ?> Overdue
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Consents -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box set-icon icon-box-green">
                                <i class="fa fa-check-square-o"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['consents']['active']; ?></p>
                                <p class="text-muted">Active Consents</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['consents']['expiring_soon']; ?> Expiring Soon
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Cross-Border -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box set-icon icon-box-blue">
                                <i class="fa fa-globe"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo $stats['crossborder']['total']; ?></p>
                                <p class="text-muted">Cross-Border Transfers</p>
                                <p class="text-muted stat-details">
                                    <?php echo $stats['crossborder']['pending']; ?> Pending
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="col-md-3 col-sm-6 col-xs-12">
                        <div class="panel panel-back noti-box">
                            <span class="icon-box set-icon icon-box-red">
                                <i class="fa fa-bell"></i>
                            </span>
                            <div class="text-box">
                                <p class="main-text"><?php echo count($notifications); ?></p>
                                <p class="text-muted">Unread Notifications</p>
                                <p class="text-muted stat-details">
                                    <a href="#notificationsPanel" class="link-primary">View All</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities & Notifications -->
                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-md-6">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-clock-o"></i> Recent Activities
                            </div>
                            <div class="panel-body">
                                <div class="list-group">
                                    <?php if (empty($recent_activities)): ?>
                                        <p class="text-muted">No recent activities</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                            <div class="list-group-item">
                                                <small class="text-muted"><?php echo format_datetime($activity['created_at'], 'd M Y H:i'); ?></small><br>
                                                <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                                <?php echo $activity['action']; ?>
                                                <span class="label label-info"><?php echo strtoupper($activity['entity_type']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="col-md-6" id="notificationsPanel">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-bell"></i> Notifications
                            </div>
                            <div class="panel-body">
                                <div class="list-group">
                                    <?php if (empty($notifications)): ?>
                                        <p class="text-muted">No new notifications</p>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notif): ?>
                                            <div class="list-group-item">
                                                <span class="badge badge-<?php echo $notif['priority'] == 'critical' ? 'danger' : ($notif['priority'] == 'high' ? 'warning' : 'info'); ?>">
                                                    <?php echo strtoupper($notif['priority']); ?>
                                                </span>
                                                <h5 class="list-group-item-heading"><?php echo htmlspecialchars($notif['title']); ?></h5>
                                                <p class="list-group-item-text"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <small class="text-muted"><?php echo format_datetime($notif['created_at'], 'd M Y H:i'); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-rocket"></i> Quick Actions
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="ropa_add.php" class="btn btn-primary btn-lg btn-block">
                                            <i class="fa fa-plus"></i> Add ROPA Entry
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="dpia_wizard.php" class="btn btn-success btn-lg btn-block">
                                            <i class="fa fa-shield"></i> Start DPIA
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="incident_add.php" class="btn btn-danger btn-lg btn-block">
                                            <i class="fa fa-warning"></i> Report Incident
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="reports.php" class="btn btn-info btn-lg btn-block">
                                            <i class="fa fa-bar-chart"></i> View Reports
                                        </a>
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
