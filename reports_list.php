<?php
/**
 * Reports Dashboard
 * Generate compliance reports and POTRAZ submissions
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Fetch organization details
$org_query = "SELECT * FROM organizations WHERE org_id = ?";
$org = db_fetch_one(db_query($org_query, [$org_id]));

// Calculate statistics for report cards
$stats = [];

// ROPA Statistics
$ropa_query = "SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) as active,
               SUM(CASE WHEN has_special_categories = 1 OR has_minors = 1 OR estimated_data_subjects > 1000 THEN 1 ELSE 0 END) as high_risk
               FROM processing_activities WHERE org_id = ?";
$stats['ropa'] = db_fetch_one(db_query($ropa_query, [$org_id]));

// DPIA Statistics
$dpia_query = "SELECT COUNT(*) as total,
               SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
               SUM(CASE WHEN residual_risk_score >= 15 THEN 1 ELSE 0 END) as high_risk
               FROM dpia WHERE org_id = ?";
$stats['dpia'] = db_fetch_one(db_query($dpia_query, [$org_id]));

// Risk Statistics
$risk_query = "SELECT COUNT(*) as total,
               AVG(residual_likelihood * residual_impact) as avg_risk,
               SUM(CASE WHEN (residual_likelihood * residual_impact) >= 15 THEN 1 ELSE 0 END) as critical_high
               FROM risks WHERE org_id = ? AND status != 'archived'";
$stats['risks'] = db_fetch_one(db_query($risk_query, [$org_id]));

// Incident Statistics (Year to Date)
$incident_query = "SELECT COUNT(*) as total,
                   SUM(CASE WHEN notifiable = 1 THEN 1 ELSE 0 END) as breaches,
                   SUM(CASE WHEN notifiable = 1 AND notified_at IS NOT NULL THEN 1 ELSE 0 END) as notified,
                   SUM(CASE WHEN notifiable = 1 AND notified_at IS NULL AND TIMESTAMPDIFF(HOUR, detected_at, NOW()) > 72 THEN 1 ELSE 0 END) as overdue
                   FROM incidents
                   WHERE org_id = ? AND YEAR(detected_at) = YEAR(CURDATE())";
$stats['incidents'] = db_fetch_one(db_query($incident_query, [$org_id]));

// DSR Statistics (Year to Date)
$dsr_query = "SELECT COUNT(*) as total,
              SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
              SUM(CASE WHEN status != 'completed' AND DATEDIFF(NOW(), received_at) > 30 THEN 1 ELSE 0 END) as overdue
              FROM dsr_requests
              WHERE org_id = ? AND YEAR(received_at) = YEAR(CURDATE())";
$stats['dsr'] = db_fetch_one(db_query($dsr_query, [$org_id]));

// Consent Statistics
$consent_query = "SELECT COUNT(*) as total,
                  SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                  SUM(CASE WHEN status = 'withdrawn' THEN 1 ELSE 0 END) as withdrawn
                  FROM consents WHERE org_id = ?";
$stats['consent'] = db_fetch_one(db_query($consent_query, [$org_id]));

// Cross-Border Transfers
$transfer_query = "SELECT COUNT(*) as total,
                   COUNT(DISTINCT destination_country) as countries
                   FROM cross_border_transfers WHERE org_id = ? AND status = 'active'";
$stats['transfers'] = db_fetch_one(db_query($transfer_query, [$org_id]));

// Calculate Compliance Score
$compliance_score = 0;
$max_score = 0;

// ROPA Completeness (30 points)
$max_score += 30;
if ($stats['ropa']['total'] > 0) {
    $compliance_score += 30;
}

// DPIA Coverage (20 points)
$max_score += 20;
if ($stats['ropa']['high_risk'] > 0) {
    $dpia_coverage = ($stats['dpia']['total'] / $stats['ropa']['high_risk']) * 100;
    $compliance_score += min(20, ($dpia_coverage / 100) * 20);
} else {
    $compliance_score += 20; // No high-risk activities = full points
}

// Risk Management (15 points)
$max_score += 15;
if ($stats['risks']['total'] > 0) {
    $risk_ratio = ($stats['risks']['critical_high'] / $stats['risks']['total']);
    $compliance_score += 15 * (1 - $risk_ratio);
}

// Breach Notification Compliance (20 points)
$max_score += 20;
if ($stats['incidents']['breaches'] > 0) {
    $notification_rate = ($stats['incidents']['notified'] / $stats['incidents']['breaches']) * 100;
    $compliance_score += ($notification_rate / 100) * 20;
    if ($stats['incidents']['overdue'] > 0) {
        $compliance_score -= 10; // Penalty for overdue notifications
    }
} else {
    $compliance_score += 20; // No breaches = full points
}

// DSR Response Rate (15 points)
$max_score += 15;
if ($stats['dsr']['total'] > 0) {
    $dsr_completion = ($stats['dsr']['completed'] / $stats['dsr']['total']) * 100;
    $compliance_score += ($dsr_completion / 100) * 15;
    if ($stats['dsr']['overdue'] > 0) {
        $compliance_score -= 5; // Penalty for overdue requests
    }
} else {
    $compliance_score += 15; // No requests = full points
}

$compliance_score = max(0, min(100, $compliance_score));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Reports & Compliance</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .stat-card {
            border-left: 5px solid;
            margin-bottom: 20px;
        }
        .compliance-gauge {
            text-align: center;
            padding: 30px;
        }
        .gauge-score {
            font-size: 72px;
            font-weight: bold;
        }
        .report-card {
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-file-text"></i> Reports &amp; Compliance
                    <small>POTRAZ Reporting</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li class="active">Reports</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Compliance Score Card -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <i class="fa fa-shield"></i> Overall Compliance Score
                    </h3>
                </div>
                <div class="panel-body text-center">
                    <h1 style="font-size: 72px; margin: 20px 0;">
                        <?php
                        $score_color = $compliance_score >= 80 ? '#27ae60' : ($compliance_score >= 60 ? '#f39c12' : '#c0392b');
                        echo "<span style='color: $score_color;'>" . round($compliance_score) . "%</span>";
                        ?>
                    </h1>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar progress-bar-<?php echo $compliance_score >= 80 ? 'success' : ($compliance_score >= 60 ? 'warning' : 'danger'); ?>"
                             style="width: <?php echo $compliance_score; ?>%">
                            <?php echo round($compliance_score); ?>% Compliant
                        </div>
                    </div>
                    <p class="text-muted" style="margin-top: 15px;">
                        <?php
                        if ($compliance_score >= 80) {
                            echo "<i class='fa fa-check-circle text-success'></i> <strong>Excellent:</strong> Your organization demonstrates strong CDPA compliance.";
                        } elseif ($compliance_score >= 60) {
                            echo "<i class='fa fa-warning text-warning'></i> <strong>Fair:</strong> Some areas need improvement to meet CDPA requirements.";
                        } else {
                            echo "<i class='fa fa-times-circle text-danger'></i> <strong>Needs Attention:</strong> Immediate action required to achieve compliance.";
                        }
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Grid -->
    <div class="row">
        <div class="col-md-3">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-list-alt"></i> ROPA Entries</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['ropa']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['ropa']['active']; ?> Active |
                        <?php echo $stats['ropa']['high_risk']; ?> High-Risk
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-shield"></i> DPIAs</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['dpia']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['dpia']['approved']; ?> Approved |
                        <?php echo $stats['dpia']['high_risk']; ?> High-Risk
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-exclamation-triangle"></i> Active Risks</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['risks']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['risks']['critical_high']; ?> Critical/High |
                        Avg: <?php echo number_format($stats['risks']['avg_risk'] ?? 0, 1); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="panel panel-danger">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-bolt"></i> Incidents (YTD)</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['incidents']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['incidents']['breaches']; ?> Breaches |
                        <?php echo $stats['incidents']['overdue']; ?> Overdue
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-users"></i> DSR Requests (YTD)</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['dsr']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['dsr']['completed']; ?> Completed |
                        <?php echo $stats['dsr']['overdue']; ?> Overdue
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-check-square"></i> Consents</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['consent']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['consent']['active']; ?> Active |
                        <?php echo $stats['consent']['withdrawn']; ?> Withdrawn
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-globe"></i> Cross-Border</h3>
                </div>
                <div class="panel-body text-center">
                    <h2><?php echo $stats['transfers']['total']; ?></h2>
                    <p class="text-muted">
                        <?php echo $stats['transfers']['countries']; ?> Countries
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Reports -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-file-pdf-o"></i> Available Reports</h3>
                </div>
                <div class="panel-body">
                    <div class="list-group">
                        <!-- Annual Compliance Report -->
                        <a href="report_annual_compliance.php" class="list-group-item">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="list-group-item-heading">
                                        <i class="fa fa-calendar text-primary"></i> Annual Compliance Report
                                    </h4>
                                    <p class="list-group-item-text">
                                        Comprehensive annual report for POTRAZ submission. Includes ROPA summary, DPIA status,
                                        risk assessment, incident reports, DSR statistics, and compliance declarations.
                                        <br><strong>Recommended Frequency:</strong> Annually (or as requested by POTRAZ)
                                    </p>
                                </div>
                                <div class="col-md-4 text-right" style="padding-top: 20px;">
                                    <a href="report_annual_compliance.php?format=pdf" class="btn btn-primary">
                                        <i class="fa fa-file-pdf-o"></i> Generate PDF
                                    </a>
                                </div>
                            </div>
                        </a>

                        <!-- Data Breach Notifications Report -->
                        <a href="report_breach_notifications.php" class="list-group-item">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="list-group-item-heading">
                                        <i class="fa fa-exclamation-triangle text-danger"></i> Data Breach Notifications Report
                                    </h4>
                                    <p class="list-group-item-text">
                                        Summary of all data breaches reported to POTRAZ under CDPA s.26.
                                        Includes breach details, notification dates, POTRAZ reference numbers, and compliance status.
                                        <br><strong>Recommended Frequency:</strong> Quarterly or as needed
                                    </p>
                                </div>
                                <div class="col-md-4 text-right" style="padding-top: 20px;">
                                    <a href="report_breach_notifications.php?format=pdf" class="btn btn-danger">
                                        <i class="fa fa-file-pdf-o"></i> Generate PDF
                                    </a>
                                    <a href="report_breach_notifications.php?format=csv" class="btn btn-default">
                                        <i class="fa fa-file-excel-o"></i> Export CSV
                                    </a>
                                </div>
                            </div>
                        </a>

                        <!-- Lawful Basis Compliance Report -->
                        <a href="report_lawful_basis.php" class="list-group-item">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="list-group-item-heading">
                                        <i class="fa fa-gavel text-warning"></i> Lawful Basis Compliance Report
                                    </h4>
                                    <p class="list-group-item-text">
                                        Assessment of lawful basis documentation for all processing activities under CDPA s.22-23.
                                        Identifies activities without documented lawful basis and consent register summary.
                                        <br><strong>Recommended Frequency:</strong> Quarterly or before POTRAZ audits
                                    </p>
                                </div>
                                <div class="col-md-4 text-right" style="padding-top: 20px;">
                                    <a href="report_lawful_basis.php" class="btn btn-warning">
                                        <i class="fa fa-file-text-o"></i> Generate Report
                                    </a>
                                </div>
                            </div>
                        </a>

                        <!-- ROPA Register Export -->
                        <a href="ropa_export.php?format=pdf" class="list-group-item">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="list-group-item-heading">
                                        <i class="fa fa-book text-info"></i> ROPA Register (Full Export)
                                    </h4>
                                    <p class="list-group-item-text">
                                        Complete Record of Processing Activities as required by CDPA s.12.
                                        Detailed listing of all data processing operations conducted by your organization.
                                        <br><strong>Recommended Frequency:</strong> Keep current and export when updated
                                    </p>
                                </div>
                                <div class="col-md-4 text-right" style="padding-top: 20px;">
                                    <a href="ropa_export.php?format=pdf" class="btn btn-info">
                                        <i class="fa fa-file-pdf-o"></i> Generate PDF
                                    </a>
                                    <a href="ropa_export.php?format=csv" class="btn btn-default">
                                        <i class="fa fa-file-excel-o"></i> Export CSV
                                    </a>
                                </div>
                            </div>
                        </a>

                        <!-- Risk Register Export -->
                        <a href="risk_export.php?format=pdf" class="list-group-item">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 class="list-group-item-heading">
                                        <i class="fa fa-fire text-warning"></i> Risk Register (Full Export)
                                    </h4>
                                    <p class="list-group-item-text">
                                        Complete organizational risk assessment including inherent risk, treatment measures,
                                        and residual risk levels. Demonstrates risk management program effectiveness.
                                        <br><strong>Recommended Frequency:</strong> Quarterly review and export
                                    </p>
                                </div>
                                <div class="col-md-4 text-right" style="padding-top: 20px;">
                                    <a href="risk_export.php?format=pdf" class="btn btn-warning">
                                        <i class="fa fa-file-pdf-o"></i> Generate PDF
                                    </a>
                                    <a href="risk_export.php?format=csv" class="btn btn-default">
                                        <i class="fa fa-file-excel-o"></i> Export CSV
                                    </a>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- POTRAZ Compliance Checklist -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-check-square-o"></i> POTRAZ Compliance Checklist</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th width="5%">Status</th>
                                <th width="45%">Requirement</th>
                                <th width="25%">CDPA Reference</th>
                                <th width="25%">Current Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-center">
                                    <?php echo $stats['ropa']['total'] > 0 ? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>'; ?>
                                </td>
                                <td>Maintain Record of Processing Activities (ROPA)</td>
                                <td>CDPA s.12</td>
                                <td>
                                    <?php
                                    if ($stats['ropa']['total'] > 0) {
                                        echo "<span class='label label-success'>{$stats['ropa']['total']} Entries</span>";
                                    } else {
                                        echo "<span class='label label-danger'>Not Started</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <?php
                                    $dpia_compliant = ($stats['ropa']['high_risk'] == 0 || $stats['dpia']['total'] >= $stats['ropa']['high_risk']);
                                    echo $dpia_compliant ? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>';
                                    ?>
                                </td>
                                <td>Conduct DPIAs for High-Risk Processing</td>
                                <td>CDPA s.13</td>
                                <td>
                                    <?php
                                    if ($stats['ropa']['high_risk'] == 0) {
                                        echo "<span class='label label-success'>No High-Risk Activities</span>";
                                    } elseif ($dpia_compliant) {
                                        echo "<span class='label label-success'>{$stats['dpia']['total']} / {$stats['ropa']['high_risk']} Complete</span>";
                                    } else {
                                        echo "<span class='label label-danger'>{$stats['dpia']['total']} / {$stats['ropa']['high_risk']} - Missing DPIAs</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <?php echo $stats['incidents']['overdue'] == 0 ? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>'; ?>
                                </td>
                                <td>Report Data Breaches within 72 Hours</td>
                                <td>CDPA s.26</td>
                                <td>
                                    <?php
                                    if ($stats['incidents']['breaches'] == 0) {
                                        echo "<span class='label label-success'>No Breaches</span>";
                                    } elseif ($stats['incidents']['overdue'] == 0) {
                                        echo "<span class='label label-success'>{$stats['incidents']['notified']} / {$stats['incidents']['breaches']} Notified</span>";
                                    } else {
                                        echo "<span class='label label-danger'>{$stats['incidents']['overdue']} Overdue Notifications</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <?php echo $stats['dsr']['overdue'] == 0 ? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-warning text-warning"></i>'; ?>
                                </td>
                                <td>Respond to DSR Requests within 30 Days</td>
                                <td>CDPA s.15-22</td>
                                <td>
                                    <?php
                                    if ($stats['dsr']['total'] == 0) {
                                        echo "<span class='label label-info'>No Requests</span>";
                                    } elseif ($stats['dsr']['overdue'] == 0) {
                                        echo "<span class='label label-success'>{$stats['dsr']['completed']} / {$stats['dsr']['total']} On Time</span>";
                                    } else {
                                        echo "<span class='label label-warning'>{$stats['dsr']['overdue']} Overdue</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-center">
                                    <?php echo $stats['risks']['total'] > 0 ? '<i class="fa fa-check-circle text-success"></i>' : '<i class="fa fa-times-circle text-danger"></i>'; ?>
                                </td>
                                <td>Implement Appropriate Security Measures</td>
                                <td>CDPA s.24</td>
                                <td>
                                    <?php
                                    if ($stats['risks']['total'] > 0) {
                                        echo "<span class='label label-success'>{$stats['risks']['total']} Risks Identified</span>";
                                    } else {
                                        echo "<span class='label label-danger'>No Risk Assessment</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
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
<script src="assets/js/custom.js"></script>
</body>
</html>
