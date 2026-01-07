<?php
/**
 * DPA Tool - Dashboard
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'includes/stats_card.php';
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

// ============================================
// ECHARTS DATA - Department Compliance
// ============================================
$query = "SELECT d.dept_name,
          COUNT(DISTINCT pa.ropa_id) as total_ropa,
          COALESCE(SUM(CASE WHEN pa.status = 'validated' THEN 1 ELSE 0 END), 0) as validated_ropa,
          COUNT(DISTINCT dp.dpia_id) as total_dpia,
          COALESCE(SUM(CASE WHEN dp.status = 'approved' THEN 1 ELSE 0 END), 0) as approved_dpia,
          COUNT(DISTINCT r.risk_id) as total_risks,
          COALESCE(SUM(CASE WHEN r.status = 'closed' THEN 1 ELSE 0 END), 0) as closed_risks
          FROM departments d
          LEFT JOIN processing_activities pa ON d.dept_id = pa.dept_id AND pa.org_id = ?
          LEFT JOIN dpia dp ON pa.ropa_id = dp.ropa_id AND dp.org_id = ?
          LEFT JOIN risks r ON d.dept_id = r.dept_id AND r.org_id = ?
          WHERE d.org_id = ?
          GROUP BY d.dept_id, d.dept_name
          ORDER BY d.dept_name";
$stmt = db_query($query, [$org_id, $org_id, $org_id, $org_id]);
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
          WHERE org_id = ? AND status != 'closed'
          GROUP BY risk_category
          ORDER BY count DESC";
$stmt = db_query($query, [$org_id]);
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
    $query = "SELECT DATE(snapshot_date) as date, compliance_score
              FROM dashboard_snapshots
              WHERE org_id = ?
              ORDER BY snapshot_date DESC
              LIMIT 30";
    $stmt = db_query($query, [$org_id]);
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
                <hr />

                <!-- Statistics Cards -->
                <?php
                render_stats_row([
                    [
                        'value' => $stats['ropa']['total'],
                        'label' => 'ROPA',
                        'icon' => 'fa-list-alt',
                        'color' => 'blue'
                    ],
                    [
                        'value' => $stats['dpia']['total'],
                        'label' => 'DPIAs',
                        'icon' => 'fa-shield',
                        'color' => 'green'
                    ],
                    [
                        'value' => $stats['risks']['open'],
                        'label' => 'Open Risks',
                        'icon' => 'fa-exclamation-triangle',
                        'color' => 'brown'
                    ],
                    [
                        'value' => $stats['incidents']['active'],
                        'label' => 'Incidents',
                        'icon' => 'fa-exclamation-circle',
                        'color' => 'red'
                    ]
                ]);

                render_stats_row([
                    [
                        'value' => $stats['dsr']['pending'],
                        'label' => 'Pending DSR',
                        'icon' => 'fa-user-circle',
                        'color' => 'purple'
                    ],
                    [
                        'value' => $stats['consents']['active'],
                        'label' => 'Consents',
                        'icon' => 'fa-check-square',
                        'color' => 'green'
                    ],
                    [
                        'value' => $stats['crossborder']['total'],
                        'label' => 'Cross-Border',
                        'icon' => 'fa-globe',
                        'color' => 'blue'
                    ],
                    [
                        'value' => count($notifications),
                        'label' => 'Notifications',
                        'icon' => 'fa-bell',
                        'color' => 'red'
                    ]
                ]);
                ?>

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

                <!-- ECharts Visualizations -->
                <!-- Compliance Score Trend Over Time -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-line-chart"></i> Compliance Score Trend Over Time
                            </div>
                            <div class="panel-body">
                                <div id="complianceTrendChart" style="width: 100%; height: 400px;"></div>
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
                            <div class="panel-body">
                                <div id="deptComplianceChart" style="width: 100%; height: 400px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-pie-chart"></i> Risk Distribution by Category
                            </div>
                            <div class="panel-body">
                                <div id="riskDistributionChart" style="width: 100%; height: 400px;"></div>
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
    // RESPONSIVE CHARTS
    // ============================================
    window.addEventListener('resize', function() {
        complianceTrendChart.resize();
        deptComplianceChart.resize();
        riskDistributionChart.resize();
    });
    </script>
</body>
</html>
