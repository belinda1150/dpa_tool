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

// Date filter handling
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Calculate date range based on filter
switch ($date_filter) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'last7days':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'last30days':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        break;
    case 'this_month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('first day of last month'));
        $end_date = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
    case 'custom':
        // Use the provided start_date and end_date
        break;
    case 'all':
    default:
        $start_date = '';
        $end_date = '';
        break;
}

// Build date filter SQL clause
$date_where_created = '';
$date_params = [];
if (!empty($start_date) && !empty($end_date)) {
    $date_where_created = " AND created_at BETWEEN ? AND ?";
    $date_params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
}

// Get dashboard statistics
$stats = [];

// ROPA Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) as validated,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
    SUM(CASE WHEN has_special_categories = 1 THEN 1 ELSE 0 END) as with_special_categories
    FROM processing_activities WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$stats['ropa'] = db_fetch_one($stmt);

// DPIA Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as pending_approval,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM dpia WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$stats['dpia'] = db_fetch_one($stmt);

// Risk Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
    SUM(CASE WHEN residual_score >= " . RISK_ACCEPTABLE_THRESHOLD . " THEN 1 ELSE 0 END) as high_risk,
    SUM(CASE WHEN due_date < CURDATE() AND status != 'closed' THEN 1 ELSE 0 END) as overdue
    FROM risks WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$stats['risks'] = db_fetch_one($stmt);

// Incident Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('open', 'investigating') THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN notifiable = 1 AND notified_at IS NULL THEN 1 ELSE 0 END) as pending_notification,
    SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical
    FROM incidents WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$stats['incidents'] = db_fetch_one($stmt);

// DSR Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status IN ('received', 'in_progress') THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN due_at < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM dsr_requests WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$stats['dsr'] = db_fetch_one($stmt);

// Consent Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'granted' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
    SUM(CASE WHEN expires_at <= DATE_ADD(NOW(), INTERVAL " . CONSENT_EXPIRY_ALERT_DAYS . " DAY) AND status = 'granted' THEN 1 ELSE 0 END) as expiring_soon
    FROM consents WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$stats['consents'] = db_fetch_one($stmt);

// Cross-Border Statistics
$query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN is_high_risk = 1 THEN 1 ELSE 0 END) as high_risk
    FROM cross_border_transfers WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
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

// ============================================
// KPI: Breach Notification Compliance (72-hour rule)
// ============================================
$query = "SELECT
    COUNT(*) as total_notifiable,
    SUM(CASE WHEN notified_at IS NOT NULL AND
        TIMESTAMPDIFF(HOUR, detected_at, notified_at) <= 72 THEN 1 ELSE 0 END) as notified_on_time,
    SUM(CASE WHEN notified_at IS NOT NULL AND
        TIMESTAMPDIFF(HOUR, detected_at, notified_at) > 72 THEN 1 ELSE 0 END) as notified_late,
    SUM(CASE WHEN notified_at IS NULL AND notifiable = 1 THEN 1 ELSE 0 END) as pending_notification,
    AVG(CASE WHEN notified_at IS NOT NULL THEN
        TIMESTAMPDIFF(HOUR, detected_at, notified_at) ELSE NULL END) as avg_notification_hours
    FROM incidents
    WHERE org_id = ? AND notifiable = 1" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$breach_compliance = db_fetch_one($stmt);

$breach_compliance_rate = 0;
if ($breach_compliance['total_notifiable'] > 0) {
    $breach_compliance_rate = round(($breach_compliance['notified_on_time'] / $breach_compliance['total_notifiable']) * 100, 1);
}

// ============================================
// KPI: DSR SLA Performance
// ============================================
$query = "SELECT
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'completed' AND completed_at <= due_at THEN 1 ELSE 0 END) as completed_on_time,
    SUM(CASE WHEN status = 'completed' AND completed_at > due_at THEN 1 ELSE 0 END) as completed_late,
    SUM(CASE WHEN status != 'completed' AND NOW() > due_at THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN status IN ('received', 'in_progress') AND NOW() <= due_at THEN 1 ELSE 0 END) as in_progress_on_track,
    AVG(CASE WHEN status = 'completed' THEN
        TIMESTAMPDIFF(DAY, received_at, completed_at) ELSE NULL END) as avg_completion_days
    FROM dsr_requests
    WHERE org_id = ?" . $date_where_created;
$stmt = db_query($query, array_merge([$org_id], $date_params));
$dsr_performance = db_fetch_one($stmt);

$dsr_sla_rate = 0;
if ($dsr_performance['total_requests'] > 0) {
    $dsr_sla_rate = round(($dsr_performance['completed_on_time'] / $dsr_performance['total_requests']) * 100, 1);
}

// ============================================
// KPI: Training Completion Rate
// ============================================
$query = "SELECT
    COUNT(*) as total_assignments,
    SUM(CASE WHEN ta.status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN ta.status = 'overdue' THEN 1 ELSE 0 END) as overdue,
    SUM(CASE WHEN ta.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN ta.status = 'assigned' THEN 1 ELSE 0 END) as not_started
    FROM training_assignments ta
    INNER JOIN users u ON ta.user_id = u.user_id
    WHERE u.org_id = ?";
$stmt = db_query($query, [$org_id]);
$training_stats = db_fetch_one($stmt);

$training_completion_rate = 0;
if ($training_stats['total_assignments'] > 0) {
    $training_completion_rate = round(($training_stats['completed'] / $training_stats['total_assignments']) * 100, 1);
}

// For admins: Get user-by-user training progress
$user_training_progress = [];
if (is_admin()) {
    $query = "SELECT
        u.user_id,
        CONCAT(u.first_name, ' ', u.last_name) as user_name,
        COUNT(ta.assign_id) as total_assignments,
        SUM(CASE WHEN ta.status = 'completed' THEN 1 ELSE 0 END) as completed,
        ROUND((SUM(CASE WHEN ta.status = 'completed' THEN 1 ELSE 0 END) / COUNT(ta.assign_id)) * 100, 1) as completion_rate
        FROM users u
        LEFT JOIN training_assignments ta ON u.user_id = ta.user_id
        WHERE u.org_id = ? AND u.status = 'active'
        GROUP BY u.user_id, u.first_name, u.last_name
        HAVING total_assignments > 0
        ORDER BY completion_rate DESC, user_name
        LIMIT 10";
    $stmt = db_query($query, [$org_id]);
    $user_training_progress = db_fetch_all($stmt);
}

// Get recent activities for timeline
$date_where_audit = !empty($start_date) && !empty($end_date) ? " AND al.created_at BETWEEN ? AND ?" : "";

// For staff, only show their own activities; for admin/DPO, show all
$user_filter = "";
$query_params = [$org_id];

if (!is_admin() && !is_dpo()) {
    $user_filter = " AND al.user_id = ?";
    $query_params[] = $user_id;
}

if (!empty($date_params)) {
    $query_params = array_merge($query_params, $date_params);
}

$query = "SELECT al.entity_type, al.entity_id, al.action, al.created_at, u.first_name, u.last_name
          FROM audit_log al
          LEFT JOIN users u ON al.user_id = u.user_id
          WHERE al.org_id = ?" . $user_filter . $date_where_audit . "
          ORDER BY al.created_at DESC
          LIMIT 10";
$stmt = db_query($query, $query_params);
$recent_activities = db_fetch_all($stmt);

// Get notifications
$query = "SELECT * FROM notifications
          WHERE org_id = ? AND (user_id = ? OR user_id IS NULL) AND is_read = 0
          ORDER BY priority DESC, created_at DESC
          LIMIT 5";
$stmt = db_query($query, [$org_id, $user_id]);
$notifications = db_fetch_all($stmt);

// ============================================
// ECHARTS DATA - Department Compliance
// ============================================
$pa_date_filter = !empty($start_date) && !empty($end_date) ? " AND pa.created_at BETWEEN ? AND ?" : "";
$dp_date_filter = !empty($start_date) && !empty($end_date) ? " AND dp.created_at BETWEEN ? AND ?" : "";
$r_date_filter = !empty($start_date) && !empty($end_date) ? " AND r.created_at BETWEEN ? AND ?" : "";

$query = "SELECT d.dept_name,
          COUNT(DISTINCT pa.ropa_id) as total_ropa,
          COALESCE(SUM(CASE WHEN pa.status = 'validated' THEN 1 ELSE 0 END), 0) as validated_ropa,
          COUNT(DISTINCT dp.dpia_id) as total_dpia,
          COALESCE(SUM(CASE WHEN dp.status = 'approved' THEN 1 ELSE 0 END), 0) as approved_dpia,
          COUNT(DISTINCT r.risk_id) as total_risks,
          COALESCE(SUM(CASE WHEN r.status = 'closed' THEN 1 ELSE 0 END), 0) as closed_risks
          FROM departments d
          LEFT JOIN processing_activities pa ON d.dept_id = pa.dept_id AND pa.org_id = ?" . $pa_date_filter . "
          LEFT JOIN dpia dp ON pa.ropa_id = dp.ropa_id AND dp.org_id = ?" . $dp_date_filter . "
          LEFT JOIN risks r ON d.dept_id = r.dept_id AND r.org_id = ?" . $r_date_filter . "
          WHERE d.org_id = ?
          GROUP BY d.dept_id, d.dept_name
          ORDER BY d.dept_name";

$query_params = [$org_id];
if (!empty($start_date) && !empty($end_date)) {
    $query_params = array_merge($query_params, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
}
$query_params[] = $org_id;
if (!empty($start_date) && !empty($end_date)) {
    $query_params = array_merge($query_params, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
}
$query_params[] = $org_id;
if (!empty($start_date) && !empty($end_date)) {
    $query_params = array_merge($query_params, [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
}
$query_params[] = $org_id;

$stmt = db_query($query, $query_params);
$dept_compliance_raw = db_fetch_all($stmt) ?: [];

// Calculate compliance score per department
$dept_compliance = [];
foreach ($dept_compliance_raw as $dept) {
    $score = 0;
    $weights = 0;

    // ROPA compliance (weight: 40)
    if ($dept['total_ropa'] > 0) {
        $score += ($dept['validated_ropa'] / $dept['total_ropa']) * 40;
        $weights += 40;
    }

    // DPIA compliance (weight: 30)
    if ($dept['total_dpia'] > 0) {
        $score += ($dept['approved_dpia'] / $dept['total_dpia']) * 30;
        $weights += 30;
    }

    // Risk management (weight: 30)
    if ($dept['total_risks'] > 0) {
        $score += ($dept['closed_risks'] / $dept['total_risks']) * 30;
        $weights += 30;
    }

    // If department has any data, calculate weighted average
    if ($weights > 0) {
        $final_score = ($score / $weights) * 100;
    } else {
        $final_score = 0; // No data for this department
    }

    $dept_compliance[] = [
        'name' => $dept['dept_name'],
        'score' => round($final_score, 1)
    ];
}

// ============================================
// ECHARTS DATA - Risk Distribution by Category
// ============================================
$query = "SELECT risk_category, COUNT(*) as count
          FROM risks
          WHERE org_id = ? AND status != 'closed'" . $date_where_created . "
          GROUP BY risk_category
          ORDER BY count DESC";
$stmt = db_query($query, array_merge([$org_id], $date_params));
$risk_distribution = db_fetch_all($stmt) ?: [];

// If no risks, add a placeholder
if (empty($risk_distribution)) {
    $risk_distribution = [
        ['risk_category' => 'No Open Risks', 'count' => 0]
    ];
}

// ============================================
// ECHARTS DATA - Compliance Score Trend Over Time
// ============================================
$compliance_trend = [];

// Check if dashboard_snapshots table exists
$table_check = $dpa_db->query("SHOW TABLES LIKE 'dashboard_snapshots'");
$table_exists = $table_check->num_rows > 0;

if ($table_exists) {
    // Get historical data from database
    $snapshot_date_filter = !empty($start_date) && !empty($end_date) ? " AND snapshot_date BETWEEN ? AND ?" : "";
    $query = "SELECT DATE(snapshot_date) as date, compliance_score
              FROM dashboard_snapshots
              WHERE org_id = ?" . $snapshot_date_filter . "
              ORDER BY snapshot_date DESC
              LIMIT 30";
    $query_params = [$org_id];
    if (!empty($start_date) && !empty($end_date)) {
        $query_params = array_merge($query_params, [$start_date, $end_date]);
    }
    $stmt = db_query($query, $query_params);
    $compliance_trend_raw = db_fetch_all($stmt) ?: [];

    // Reverse to get chronological order
    $compliance_trend = array_reverse($compliance_trend_raw);

    // If no historical data exists, create today's snapshot
    if (empty($compliance_trend)) {
        // Save today's snapshot
        $snapshot_query = "INSERT INTO dashboard_snapshots
                          (org_id, snapshot_date, compliance_score, kpis_json,
                           total_ropa, validated_ropa, total_dpia, approved_dpia,
                           total_risks, open_risks, total_incidents, active_incidents,
                           total_dsr, pending_dsr)
                          VALUES (?, CURDATE(), ?, '{}', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE compliance_score = VALUES(compliance_score)";

        db_query($snapshot_query, [
            $org_id,
            $compliance_score,
            $stats['ropa']['total'] ?? 0,
            $stats['ropa']['validated'] ?? 0,
            $stats['dpia']['total'] ?? 0,
            $stats['dpia']['approved'] ?? 0,
            $stats['risks']['total'] ?? 0,
            $stats['risks']['open'] ?? 0,
            $stats['incidents']['total'] ?? 0,
            $stats['incidents']['active'] ?? 0,
            $stats['dsr']['total'] ?? 0,
            $stats['dsr']['pending'] ?? 0
        ]);

        // Show today's data
        $compliance_trend[] = [
            'date' => date('Y-m-d'),
            'compliance_score' => $compliance_score
        ];
    }
} else {
    // Table doesn't exist yet - show current score only
    $compliance_trend[] = [
        'date' => date('Y-m-d'),
        'compliance_score' => $compliance_score
    ];
}

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

                <!-- Date Filter -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default" style="background-color: #f9f9f9;">
                            <div class="panel-body">
                                <form method="GET" action="dashboard.php" id="dateFilterForm" style="margin-bottom: 0;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label style="margin-bottom: 2px;">Quick Filter:</label>
                                            <select name="date_filter" id="dateFilter" class="form-control input-sm" onchange="toggleCustomDates()">
                                                <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                                <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                                <option value="last7days" <?php echo $date_filter == 'last7days' ? 'selected' : ''; ?>>Last 7 Days</option>
                                                <option value="last30days" <?php echo $date_filter == 'last30days' ? 'selected' : ''; ?>>Last 30 Days</option>
                                                <option value="this_month" <?php echo $date_filter == 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                                <option value="last_month" <?php echo $date_filter == 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                                <option value="this_year" <?php echo $date_filter == 'this_year' ? 'selected' : ''; ?>>This Year</option>
                                                <option value="custom" <?php echo $date_filter == 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3" id="customStartDate" style="display: <?php echo $date_filter == 'custom' ? 'block' : 'none'; ?>;">
                                            <label style="margin-bottom: 2px;">Start Date:</label>
                                            <input type="date" name="start_date" class="form-control input-sm" value="<?php echo htmlspecialchars($start_date); ?>">
                                        </div>
                                        <div class="col-md-3" id="customEndDate" style="display: <?php echo $date_filter == 'custom' ? 'block' : 'none'; ?>;">
                                            <label style="margin-bottom: 2px;">End Date:</label>
                                            <input type="date" name="end_date" class="form-control input-sm" value="<?php echo htmlspecialchars($end_date); ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label style="margin-bottom: 2px;">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-sm btn-block">Apply Filter</button>
                                        </div>
                                    </div>
                                    <?php if ($date_filter != 'all'): ?>
                                    <div class="row" style="margin-top: 5px;">
                                        <div class="col-md-12">
                                            <small>
                                                <i class="fa fa-filter"></i> Showing data from <strong><?php echo date('M j, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('M j, Y', strtotime($end_date)); ?></strong>
                                                <a href="dashboard.php" style="margin-left: 10px;"><i class="fa fa-times"></i> Clear Filter</a>
                                            </small>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                function toggleCustomDates() {
                    var filter = document.getElementById('dateFilter').value;
                    var startDateDiv = document.getElementById('customStartDate');
                    var endDateDiv = document.getElementById('customEndDate');

                    if (filter === 'custom') {
                        startDateDiv.style.display = 'block';
                        endDateDiv.style.display = 'block';
                    } else {
                        startDateDiv.style.display = 'none';
                        endDateDiv.style.display = 'none';
                        // Auto-submit for predefined filters
                        document.getElementById('dateFilterForm').submit();
                    }
                }
                </script>

                <!-- Compliance Score & Trend -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-default" style="height: 100%;">
                            <div class="panel-heading panel-compliance-header">
                                <h4><i class="fa fa-check-circle"></i> Overall Compliance Score</h4>
                            </div>
                            <div class="panel-body text-center" style="display: flex; flex-direction: column; justify-content: center; height: 280px;">
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
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-line-chart"></i> Compliance Score Trend Over Time
                            </div>
                            <div class="panel-body" style="height: 280px;">
                                <div id="complianceTrendChart" style="width: 100%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Performance Indicators -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-clock-o"></i> Breach Notification (72hr)
                            </div>
                            <div class="panel-body" style="height: 280px;">
                                <div id="breachComplianceGauge" style="width: 100%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-user-circle"></i> DSR SLA Performance
                            </div>
                            <div class="panel-body" style="height: 280px;">
                                <div id="dsrSlaChart" style="width: 100%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-graduation-cap"></i> Training Completion
                            </div>
                            <div class="panel-body" style="height: 280px;">
                                <div id="trainingCompletionChart" style="width: 100%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Department Compliance & Risk Distribution -->
                <div class="row">
                    <div class="col-md-7">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-bar-chart"></i> Department Compliance Comparison
                            </div>
                            <div class="panel-body" style="height: 280px;">
                                <div id="deptComplianceChart" style="width: 100%; height: 100%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-pie-chart"></i> Risk Distribution by Category
                            </div>
                            <div class="panel-body" style="height: 280px;">
                                <div id="riskDistributionChart" style="width: 100%; height: 100%;"></div>
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
                                    <a href="notifications.php" class="link-primary">View All</a>
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
                                <div class="list-group" style="max-height: 150px; overflow-y: auto; overflow-x: hidden;">
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
    <script src="assets/js/echarts.min.js"></script>
    <script src="assets/js/custom.js"></script>

    <script>
    // ============================================
    // ECHARTS INITIALIZATION
    // ============================================

    // Prepare data from PHP
    var deptComplianceData = <?php echo json_encode($dept_compliance); ?>;
    var riskDistributionData = <?php echo json_encode($risk_distribution); ?>;
    var complianceTrendData = <?php echo json_encode($compliance_trend); ?>;

    // ============================================
    // 1. COMPLIANCE SCORE TREND OVER TIME
    // ============================================
    var complianceTrendChart = echarts.init(document.getElementById('complianceTrendChart'));

    var trendDates = complianceTrendData.map(function(item) { return item.date; });
    var trendScores = complianceTrendData.map(function(item) { return parseFloat(item.compliance_score); });

    var complianceTrendOption = {
        tooltip: {
            trigger: 'axis',
            formatter: function(params) {
                return params[0].axisValue + '<br/>' +
                       'Compliance Score: <strong>' + params[0].value + '%</strong>';
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            data: trendDates,
            axisLabel: {
                rotate: 45
            }
        },
        yAxis: {
            type: 'value',
            min: 0,
            max: 100,
            axisLabel: {
                formatter: '{value}%'
            }
        },
        series: [{
            name: 'Compliance Score',
            type: 'line',
            smooth: true,
            symbol: 'circle',
            symbolSize: 8,
            lineStyle: {
                width: 3
            },
            areaStyle: {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [{
                    offset: 0,
                    color: 'rgba(52, 152, 219, 0.5)'
                }, {
                    offset: 1,
                    color: 'rgba(52, 152, 219, 0.1)'
                }])
            },
            itemStyle: {
                color: '#3498db'
            },
            data: trendScores,
            markLine: {
                silent: true,
                lineStyle: {
                    color: '#27ae60',
                    type: 'dashed'
                },
                data: [{
                    yAxis: 80,
                    label: {
                        formatter: 'Target: 80%',
                        position: 'end'
                    }
                }]
            }
        }]
    };

    complianceTrendChart.setOption(complianceTrendOption);

    // ============================================
    // 2. DEPARTMENT COMPLIANCE COMPARISON
    // ============================================
    var deptComplianceChart = echarts.init(document.getElementById('deptComplianceChart'));

    var deptNames = deptComplianceData.map(function(item) { return item.name; });
    var deptScores = deptComplianceData.map(function(item) { return item.score; });

    var deptComplianceOption = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            },
            formatter: function(params) {
                var score = params[0].value;
                var status = score >= 80 ? 'Excellent' : (score >= 60 ? 'Good' : 'Needs Improvement');
                return params[0].axisValue + '<br/>' +
                       'Score: <strong>' + score + '%</strong><br/>' +
                       'Status: ' + status;
            }
        },
        grid: {
            left: '3%',
            right: '4%',
            bottom: '3%',
            containLabel: true
        },
        xAxis: {
            type: 'value',
            min: 0,
            max: 100,
            axisLabel: {
                formatter: '{value}%'
            }
        },
        yAxis: {
            type: 'category',
            data: deptNames,
            axisLabel: {
                fontSize: 11
            }
        },
        series: [{
            name: 'Compliance Score',
            type: 'bar',
            barWidth: '60%',
            data: deptScores.map(function(score) {
                var color;
                if (score >= 80) {
                    color = '#27ae60'; // Green
                } else if (score >= 60) {
                    color = '#f39c12'; // Orange
                } else {
                    color = '#e74c3c'; // Red
                }
                return {
                    value: score,
                    itemStyle: { color: color }
                };
            }),
            label: {
                show: true,
                position: 'right',
                formatter: '{c}%',
                fontSize: 11,
                fontWeight: 'bold'
            }
        }]
    };

    deptComplianceChart.setOption(deptComplianceOption);

    // ============================================
    // 3. RISK DISTRIBUTION BY CATEGORY
    // ============================================
    var riskDistributionChart = echarts.init(document.getElementById('riskDistributionChart'));

    var riskData = riskDistributionData.map(function(item) {
        return {
            name: item.risk_category,
            value: parseInt(item.count)
        };
    });

    // Color palette for risk categories
    var riskColors = ['#e74c3c', '#e67e22', '#f39c12', '#3498db', '#9b59b6', '#1abc9c', '#34495e'];

    var riskDistributionOption = {
        tooltip: {
            trigger: 'item',
            formatter: function(params) {
                return params.name + '<br/>' +
                       'Count: <strong>' + params.value + '</strong><br/>' +
                       'Percentage: <strong>' + params.percent + '%</strong>';
            }
        },
        legend: {
            orient: 'vertical',
            right: '10%',
            top: 'center',
            formatter: function(name) {
                var item = riskData.find(function(d) { return d.name === name; });
                return name + ': ' + (item ? item.value : 0);
            }
        },
        color: riskColors,
        series: [{
            name: 'Risk Category',
            type: 'pie',
            radius: ['40%', '70%'],
            center: ['35%', '50%'],
            avoidLabelOverlap: false,
            itemStyle: {
                borderRadius: 10,
                borderColor: '#fff',
                borderWidth: 2
            },
            label: {
                show: false,
                position: 'center'
            },
            emphasis: {
                label: {
                    show: true,
                    fontSize: '18',
                    fontWeight: 'bold',
                    formatter: function(params) {
                        return params.name + '\n' + params.value;
                    }
                }
            },
            labelLine: {
                show: false
            },
            data: riskData
        }]
    };

    riskDistributionChart.setOption(riskDistributionOption);

    // ============================================
    // 4. BREACH NOTIFICATION COMPLIANCE - GAUGE
    // ============================================
    var breachComplianceGauge = echarts.init(document.getElementById('breachComplianceGauge'));

    var breachComplianceOption = {
        series: [{
            type: 'gauge',
            startAngle: 180,
            endAngle: 0,
            min: 0,
            max: 100,
            radius: '90%',
            center: ['50%', '70%'],
            splitNumber: 10,
            axisLine: {
                lineStyle: {
                    width: 20,
                    color: [
                        [0.6, '#e74c3c'],
                        [0.8, '#f39c12'],
                        [1, '#27ae60']
                    ]
                }
            },
            pointer: {
                icon: 'path://M2.9,0.7L2.9,0.7c1.4,0,2.6,1.2,2.6,2.6v115c0,1.4-1.2,2.6-2.6,2.6l0,0c-1.4,0-2.6-1.2-2.6-2.6V3.3C0.3,1.9,1.4,0.7,2.9,0.7z',
                width: 8,
                length: '75%',
                itemStyle: {
                    color: '#004889'
                }
            },
            axisTick: {
                length: 8,
                lineStyle: {
                    color: 'auto',
                    width: 1
                }
            },
            splitLine: {
                length: 12,
                lineStyle: {
                    color: 'auto',
                    width: 2
                }
            },
            axisLabel: {
                color: '#464646',
                fontSize: 10,
                distance: -45,
                formatter: function (value) {
                    if (value === 50) return '50%';
                    if (value === 100) return '100%';
                    return '';
                },
                rich: {
                    zero: {
                        padding: [0, 0, 0, -10]
                    }
                }
            },
            title: {
                offsetCenter: [0, '20%'],
                fontSize: 12,
                color: '#666'
            },
            detail: {
                fontSize: 28,
                fontWeight: 'bold',
                offsetCenter: [0, '0%'],
                valueAnimation: true,
                formatter: function (value) {
                    return value.toFixed(0) + '%';
                },
                color: 'auto'
            },
            data: [{
                value: <?php echo $breach_compliance_rate; ?>,
                name: 'Within 72hrs'
            }]
        }]
    };

    breachComplianceGauge.setOption(breachComplianceOption);

    // ============================================
    // 5. DSR SLA PERFORMANCE - DONUT CHART
    // ============================================
    var dsrSlaChart = echarts.init(document.getElementById('dsrSlaChart'));

    var dsrSlaOption = {
        tooltip: {
            trigger: 'item',
            formatter: function(params) {
                return params.seriesName + '<br/>' + params.name + ': ' + params.value;
            }
        },
        legend: {
            bottom: 0,
            left: 'center',
            data: ['On Time', 'Late', 'Overdue', 'In Progress'],
            textStyle: {
                fontSize: 10
            }
        },
        series: [
            {
                name: 'DSR Status',
                type: 'pie',
                radius: ['40%', '70%'],
                center: ['50%', '45%'],
                avoidLabelOverlap: false,
                itemStyle: {
                    borderRadius: 8,
                    borderColor: '#fff',
                    borderWidth: 2
                },
                label: {
                    show: true,
                    position: 'outside',
                    formatter: '{b}\n{d}%',
                    fontSize: 10
                },
                emphasis: {
                    label: {
                        show: true,
                        fontSize: 12,
                        fontWeight: 'bold'
                    }
                },
                labelLine: {
                    show: true,
                    length: 10,
                    length2: 10
                },
                data: [
                    {value: <?php echo $dsr_performance['completed_on_time'] ?? 0; ?>, name: 'On Time', itemStyle: {color: '#27ae60'}},
                    {value: <?php echo $dsr_performance['completed_late'] ?? 0; ?>, name: 'Late', itemStyle: {color: '#f39c12'}},
                    {value: <?php echo $dsr_performance['overdue'] ?? 0; ?>, name: 'Overdue', itemStyle: {color: '#e74c3c'}},
                    {value: <?php echo $dsr_performance['in_progress_on_track'] ?? 0; ?>, name: 'In Progress', itemStyle: {color: '#3498db'}}
                ]
            }
        ]
    };

    dsrSlaChart.setOption(dsrSlaOption);

    // ============================================
    // 6. TRAINING COMPLETION
    // ============================================
    var trainingCompletionChart = echarts.init(document.getElementById('trainingCompletionChart'));

    <?php if (is_admin()): ?>
    // Admin view: Horizontal bar chart showing user-by-user progress
    var trainingCompletionOption = {
        tooltip: {
            trigger: 'axis',
            axisPointer: {
                type: 'shadow'
            },
            formatter: function(params) {
                return params[0].name + '<br/>' +
                       'Completion: ' + params[0].value + '%<br/>' +
                       'Total: ' + params[0].data.total + ' assignments<br/>' +
                       'Completed: ' + params[0].data.completed;
            }
        },
        grid: {
            left: '25%',
            right: '10%',
            top: '5%',
            bottom: '10%',
            containLabel: true
        },
        xAxis: {
            type: 'value',
            max: 100,
            axisLabel: {
                formatter: '{value}%',
                fontSize: 10
            }
        },
        yAxis: {
            type: 'category',
            data: [
                <?php foreach ($user_training_progress as $user): ?>
                '<?php echo addslashes($user['user_name']); ?>',
                <?php endforeach; ?>
            ],
            axisLabel: {
                fontSize: 10
            }
        },
        series: [{
            name: 'Completion Rate',
            type: 'bar',
            data: [
                <?php foreach ($user_training_progress as $user): ?>
                {
                    value: <?php echo $user['completion_rate'] ?? 0; ?>,
                    total: <?php echo $user['total_assignments']; ?>,
                    completed: <?php echo $user['completed']; ?>,
                    itemStyle: {
                        color: <?php
                            $rate = $user['completion_rate'] ?? 0;
                            if ($rate >= 80) {
                                echo "'#27ae60'";
                            } elseif ($rate >= 60) {
                                echo "'#f39c12'";
                            } else {
                                echo "'#e74c3c'";
                            }
                        ?>
                    }
                },
                <?php endforeach; ?>
            ],
            label: {
                show: true,
                position: 'right',
                formatter: '{c}%',
                fontSize: 10
            }
        }]
    };
    <?php else: ?>
    // Staff view: Ring chart with overall completion percentage
    var trainingCompletionOption = {
        tooltip: {
            trigger: 'item',
            formatter: function(params) {
                return params.seriesName + '<br/>' + params.name + ': ' + params.value + ' (' + params.percent + '%)';
            }
        },
        graphic: [{
            type: 'text',
            left: 'center',
            top: 'center',
            style: {
                text: '<?php echo $training_completion_rate; ?>%',
                fontSize: 36,
                fontWeight: 'bold',
                fill: '<?php echo $training_completion_rate >= 80 ? "#27ae60" : ($training_completion_rate >= 60 ? "#f39c12" : "#e74c3c"); ?>'
            }
        }, {
            type: 'text',
            left: 'center',
            top: 'center',
            style: {
                text: 'Completed',
                fontSize: 11,
                fill: '#999',
                y: 30
            }
        }],
        series: [{
            name: 'Training Status',
            type: 'pie',
            radius: ['60%', '80%'],
            center: ['50%', '50%'],
            avoidLabelOverlap: false,
            itemStyle: {
                borderRadius: 0,
                borderColor: '#fff',
                borderWidth: 0
            },
            label: {
                show: true,
                position: 'outside',
                formatter: '{b}\n{c}',
                fontSize: 9
            },
            emphasis: {
                scale: false
            },
            labelLine: {
                show: true,
                length: 8,
                length2: 8
            },
            data: [
                {value: <?php echo $training_stats['completed'] ?? 0; ?>, name: 'Completed', itemStyle: {color: '#27ae60'}},
                {value: <?php echo $training_stats['in_progress'] ?? 0; ?>, name: 'In Progress', itemStyle: {color: '#3498db'}},
                {value: <?php echo $training_stats['not_started'] ?? 0; ?>, name: 'Not Started', itemStyle: {color: '#95a5a6'}},
                {value: <?php echo $training_stats['overdue'] ?? 0; ?>, name: 'Overdue', itemStyle: {color: '#e74c3c'}}
            ]
        }]
    };
    <?php endif; ?>

    trainingCompletionChart.setOption(trainingCompletionOption);

    // ============================================
    // RESPONSIVE CHARTS
    // ============================================
    window.addEventListener('resize', function() {
        complianceTrendChart.resize();
        deptComplianceChart.resize();
        riskDistributionChart.resize();
        breachComplianceGauge.resize();
        dsrSlaChart.resize();
        trainingCompletionChart.resize();
    });
    </script>
</body>
</html>
