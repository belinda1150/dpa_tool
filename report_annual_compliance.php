<?php
/**
 * Annual Compliance Report
 * Comprehensive report for POTRAZ submission
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$format = isset($_GET['format']) ? $_GET['format'] : 'html';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Fetch organization details
$org_query = "SELECT * FROM organizations WHERE org_id = ?";
$org = db_fetch_one(db_query($org_query, [$org_id]));

// Fetch DPO details
$dpo_query = "SELECT u.*, r.role_name FROM users u
              JOIN roles r ON u.role_id = r.role_id
              WHERE u.org_id = ? AND r.role_name = 'DPO' LIMIT 1";
$dpo = db_fetch_one(db_query($dpo_query, [$org_id]));

// ROPA Statistics
$ropa_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) as active,
     SUM(CASE WHEN has_special_categories = 1 OR has_minors = 1 OR estimated_data_subjects > 1000 THEN 1 ELSE 0 END) as requires_dpia
     FROM processing_activities WHERE org_id = ?",
    [$org_id]
));

// ROPA by Purpose
$ropa_by_purpose = db_fetch_all(db_query(
    "SELECT p.purpose_name as processing_purpose, COUNT(*) as count
     FROM processing_activities pa
     LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
     WHERE pa.org_id = ? AND pa.status = 'validated'
     GROUP BY p.purpose_name
     ORDER BY count DESC",
    [$org_id]
));

// DPIA Statistics
$dpia_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
     SUM(CASE WHEN residual_risk_score >= 15 THEN 1 ELSE 0 END) as high_risk,
     SUM(CASE WHEN residual_risk_score BETWEEN 10 AND 14 THEN 1 ELSE 0 END) as medium_risk,
     SUM(CASE WHEN residual_risk_score < 10 THEN 1 ELSE 0 END) as low_risk
     FROM dpia WHERE org_id = ?",
    [$org_id]
));

// Risk Statistics
$risk_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN (residual_likelihood * residual_impact) >= 15 THEN 1 ELSE 0 END) as critical,
     SUM(CASE WHEN (residual_likelihood * residual_impact) BETWEEN 10 AND 14 THEN 1 ELSE 0 END) as high,
     SUM(CASE WHEN (residual_likelihood * residual_impact) BETWEEN 5 AND 9 THEN 1 ELSE 0 END) as medium,
     SUM(CASE WHEN (residual_likelihood * residual_impact) < 5 THEN 1 ELSE 0 END) as low,
     AVG(likelihood * impact) as avg_inherent,
     AVG(residual_likelihood * residual_impact) as avg_residual
     FROM risks WHERE org_id = ? AND status != 'archived'",
    [$org_id]
));

// Incidents - Year to Date
$incident_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN notifiable = 1 THEN 1 ELSE 0 END) as breaches,
     SUM(CASE WHEN notifiable = 1 AND notified_at IS NOT NULL THEN 1 ELSE 0 END) as notified,
     SUM(CASE WHEN notifiable = 1 AND notified_at IS NOT NULL AND TIMESTAMPDIFF(HOUR, detected_at, notified_at) <= 72 THEN 1 ELSE 0 END) as on_time,
     SUM(CASE WHEN severity = 'critical' THEN 1 ELSE 0 END) as critical,
     SUM(CASE WHEN severity = 'serious' THEN 1 ELSE 0 END) as high
     FROM incidents WHERE org_id = ? AND YEAR(detected_at) = ?",
    [$org_id, $year]
));

// Incidents by Severity
$incidents_by_type = db_fetch_all(db_query(
    "SELECT severity as incident_type, COUNT(*) as count
     FROM incidents
     WHERE org_id = ? AND YEAR(detected_at) = ?
     GROUP BY severity
     ORDER BY FIELD(severity, 'critical', 'serious', 'minor')",
    [$org_id, $year]
));

// DSR Statistics - Year to Date
$dsr_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
     SUM(CASE WHEN status = 'completed' AND DATEDIFF(completed_at, received_at) <= 30 THEN 1 ELSE 0 END) as on_time,
     AVG(DATEDIFF(completed_at, received_at)) as avg_days
     FROM dsr_requests WHERE org_id = ? AND YEAR(received_at) = ?",
    [$org_id, $year]
));

// DSR by Request Type
$dsr_by_type = db_fetch_all(db_query(
    "SELECT request_type, COUNT(*) as count
     FROM dsr_requests
     WHERE org_id = ? AND YEAR(received_at) = ?
     GROUP BY request_type
     ORDER BY count DESC",
    [$org_id, $year]
));

// Consent Statistics
$consent_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
     SUM(CASE WHEN status = 'withdrawn' THEN 1 ELSE 0 END) as withdrawn
     FROM consents WHERE org_id = ?",
    [$org_id]
));

// Cross-Border Transfers
$transfer_stats = db_fetch_one(db_query(
    "SELECT COUNT(*) as total,
     COUNT(DISTINCT destination_country) as countries
     FROM cross_border_transfers WHERE org_id = ? AND status = 'active'",
    [$org_id]
));

// Calculate overall compliance score (same logic as dashboard)
$compliance_score = 0;

// ROPA (30 points)
if ($ropa_stats['total'] > 0) $compliance_score += 30;

// DPIA (20 points)
if ($ropa_stats['requires_dpia'] > 0) {
    $dpia_coverage = ($dpia_stats['total'] / $ropa_stats['requires_dpia']);
    $compliance_score += min(20, $dpia_coverage * 20);
} else {
    $compliance_score += 20;
}

// Risk Management (15 points)
if ($risk_stats['total'] > 0) {
    $high_risk_ratio = ($risk_stats['critical'] / $risk_stats['total']);
    $compliance_score += 15 * (1 - $high_risk_ratio);
}

// Breach Notification (20 points)
if ($incident_stats['breaches'] > 0) {
    $notification_rate = ($incident_stats['on_time'] / $incident_stats['breaches']);
    $compliance_score += $notification_rate * 20;
} else {
    $compliance_score += 20;
}

// DSR Response (15 points)
if ($dsr_stats['total'] > 0) {
    $dsr_rate = ($dsr_stats['on_time'] / $dsr_stats['total']);
    $compliance_score += $dsr_rate * 15;
} else {
    $compliance_score += 15;
}

$compliance_score = max(0, min(100, $compliance_score));

// HTML/PDF Output
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Annual Compliance Report <?php echo $year; ?> - <?php echo htmlspecialchars($org['org_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            margin: 30px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 28pt;
            color: #2c3e50;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .subsection-title {
            background-color: #ecf0f1;
            padding: 8px;
            font-size: 12pt;
            font-weight: bold;
            margin: 15px 0 10px 0;
            border-left: 4px solid #3498db;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table th {
            background-color: #ecf0f1;
            text-align: left;
            padding: 10px;
            font-weight: bold;
            border: 1px solid #bdc3c7;
        }
        .info-table td {
            padding: 8px;
            border: 1px solid #bdc3c7;
        }
        .score-box {
            text-align: center;
            padding: 30px;
            background-color: #f8f9fa;
            border: 2px solid #2c3e50;
            margin: 20px 0;
        }
        .score-box h2 {
            font-size: 48pt;
            margin: 10px 0;
            color: <?php echo $compliance_score >= 80 ? '#27ae60' : ($compliance_score >= 60 ? '#f39c12' : '#c0392b'); ?>;
        }
        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-left: 5px solid;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #27ae60;
            color: #155724;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #f39c12;
            color: #856404;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #c0392b;
            color: #721c24;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #2c3e50;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 350px;
            margin: 40px 0 5px 0;
        }
        @media print {
            body { margin: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<!-- Print Button -->
<div class="no-print" style="text-align: right; margin-bottom: 20px;">
    <button onclick="window.print();" style="padding: 10px 20px; font-size: 12pt; cursor: pointer;">
        Print / Save as PDF
    </button>
    <button onclick="window.location.href='reports_list.php';"
            style="padding: 10px 20px; font-size: 12pt; cursor: pointer; margin-left: 10px;">
        Back to Reports
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1>ANNUAL COMPLIANCE REPORT</h1>
    <p style="font-size: 18pt; margin: 15px 0;"><strong><?php echo $year; ?></strong></p>
    <p><strong><?php echo htmlspecialchars($org['org_name']); ?></strong></p>
    <p>Submitted to: Postal and Telecommunications Regulatory Authority of Zimbabwe (POTRAZ)</p>
    <p>Report Date: <?php echo date('d F Y'); ?></p>
</div>

<!-- Executive Summary -->
<div class="section">
    <div class="section-title">EXECUTIVE SUMMARY</div>
    <div class="score-box">
        <p style="font-size: 14pt; margin: 0;"><strong>Overall Compliance Score</strong></p>
        <h2><?php echo round($compliance_score); ?>%</h2>
        <p style="font-size: 12pt; color: #666;">
            <?php
            if ($compliance_score >= 80) {
                echo "EXCELLENT - Strong compliance with CDPA requirements";
            } elseif ($compliance_score >= 60) {
                echo "FAIR - Some areas require improvement";
            } else {
                echo "NEEDS ATTENTION - Immediate action required";
            }
            ?>
        </p>
    </div>

    <p>
        This report provides a comprehensive overview of <strong><?php echo htmlspecialchars($org['org_name']); ?></strong>'s
        compliance with the Zimbabwe Cyber and Data Protection Act [Chapter 12:07] and the Data Protection (General)
        Regulations (SI 156 of 2022) for the year <?php echo $year; ?>.
    </p>

    <table class="info-table">
        <tr>
            <th width="40%">Organization Name:</th>
            <td><?php echo htmlspecialchars($org['org_name']); ?></td>
        </tr>
        <tr>
            <th>Registration Number:</th>
            <td><?php echo htmlspecialchars($org['registration_number'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Address:</th>
            <td><?php echo htmlspecialchars($org['address'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Contact:</th>
            <td><?php echo htmlspecialchars($org['phone'] ?? 'N/A'); ?> | <?php echo htmlspecialchars($org['email'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Data Protection Officer:</th>
            <td>
                <?php
                if ($dpo) {
                    echo htmlspecialchars($dpo['first_name'] . ' ' . $dpo['last_name']) . '<br>';
                    echo htmlspecialchars($dpo['email']);
                } else {
                    echo 'Not Assigned';
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>Reporting Period:</th>
            <td>1 January <?php echo $year; ?> - 31 December <?php echo $year; ?></td>
        </tr>
    </table>
</div>

<!-- ROPA Summary -->
<div class="section">
    <div class="section-title">1. RECORD OF PROCESSING ACTIVITIES (ROPA)</div>
    <p><em>CDPA Section 12: Every data controller and processor shall maintain a record of processing activities.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Total Processing Activities Registered:</th>
            <td><strong><?php echo $ropa_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>Active Processing Activities:</th>
            <td><strong><?php echo $ropa_stats['active']; ?></strong></td>
        </tr>
        <tr>
            <th>High-Risk Activities (Requiring DPIA):</th>
            <td><strong><?php echo $ropa_stats['requires_dpia']; ?></strong></td>
        </tr>
    </table>

    <?php if (!empty($ropa_by_purpose)): ?>
    <div class="subsection-title">Processing Activities by Purpose</div>
    <table class="info-table">
        <thead>
            <tr>
                <th>Purpose</th>
                <th width="20%">Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ropa_by_purpose as $purpose): ?>
            <tr>
                <td><?php echo htmlspecialchars($purpose['processing_purpose']); ?></td>
                <td><?php echo $purpose['count']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($ropa_stats['total'] > 0): ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> COMPLIANT - Organization maintains a comprehensive ROPA as required by CDPA s.12.
    </div>
    <?php else: ?>
    <div class="alert alert-danger">
        <strong>Compliance Status:</strong> NON-COMPLIANT - No processing activities recorded. ROPA is mandatory under CDPA s.12.
    </div>
    <?php endif; ?>
</div>

<!-- DPIA Summary -->
<div class="section">
    <div class="section-title">2. DATA PROTECTION IMPACT ASSESSMENTS (DPIA)</div>
    <p><em>CDPA Section 13: A data controller shall carry out an impact assessment where processing is likely to result in a high risk.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Total DPIAs Conducted:</th>
            <td><strong><?php echo $dpia_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>DPIAs Approved by DPO:</th>
            <td><strong><?php echo $dpia_stats['approved']; ?></strong></td>
        </tr>
        <tr>
            <th>High-Risk DPIAs:</th>
            <td><strong><?php echo $dpia_stats['high_risk']; ?></strong></td>
        </tr>
        <tr>
            <th>Medium-Risk DPIAs:</th>
            <td><strong><?php echo $dpia_stats['medium_risk']; ?></strong></td>
        </tr>
        <tr>
            <th>Low-Risk DPIAs:</th>
            <td><strong><?php echo $dpia_stats['low_risk']; ?></strong></td>
        </tr>
    </table>

    <?php
    $dpia_compliant = ($ropa_stats['requires_dpia'] == 0 || $dpia_stats['total'] >= $ropa_stats['requires_dpia']);
    if ($dpia_compliant):
    ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> COMPLIANT - All high-risk processing activities have undergone DPIA assessment.
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <strong>Compliance Status:</strong> PARTIALLY COMPLIANT - <?php echo ($ropa_stats['requires_dpia'] - $dpia_stats['total']); ?> high-risk activities require DPIA assessment.
    </div>
    <?php endif; ?>
</div>

<!-- Risk Management -->
<div class="section">
    <div class="section-title">3. RISK MANAGEMENT</div>
    <p><em>CDPA Section 24: A data controller or processor shall implement appropriate security measures.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Total Risks Identified:</th>
            <td><strong><?php echo $risk_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>Critical Risks (Score 15-25):</th>
            <td><strong><?php echo $risk_stats['critical']; ?></strong></td>
        </tr>
        <tr>
            <th>High Risks (Score 10-14):</th>
            <td><strong><?php echo $risk_stats['high']; ?></strong></td>
        </tr>
        <tr>
            <th>Medium Risks (Score 5-9):</th>
            <td><strong><?php echo $risk_stats['medium']; ?></strong></td>
        </tr>
        <tr>
            <th>Low Risks (Score 1-4):</th>
            <td><strong><?php echo $risk_stats['low']; ?></strong></td>
        </tr>
        <tr>
            <th>Average Inherent Risk Score:</th>
            <td><strong><?php echo number_format($risk_stats['avg_inherent'] ?? 0, 2); ?></strong></td>
        </tr>
        <tr>
            <th>Average Residual Risk Score:</th>
            <td><strong><?php echo number_format($risk_stats['avg_residual'] ?? 0, 2); ?></strong></td>
        </tr>
        <tr>
            <th>Risk Reduction:</th>
            <td>
                <strong>
                <?php
                if ($risk_stats['avg_inherent'] > 0) {
                    $reduction = (($risk_stats['avg_inherent'] - $risk_stats['avg_residual']) / $risk_stats['avg_inherent']) * 100;
                    echo number_format($reduction, 1) . '%';
                } else {
                    echo 'N/A';
                }
                ?>
                </strong>
            </td>
        </tr>
    </table>

    <?php if ($risk_stats['total'] > 0): ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> COMPLIANT - Organization has identified and is managing data protection risks.
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <strong>Compliance Status:</strong> NEEDS IMPROVEMENT - No formal risk assessment documented.
    </div>
    <?php endif; ?>
</div>

<!-- Security Incidents & Breach Notifications -->
<div class="section">
    <div class="section-title">4. SECURITY INCIDENTS & BREACH NOTIFICATIONS</div>
    <p><em>CDPA Section 26: A data controller shall notify the Authority of a data breach within 72 hours of becoming aware.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Total Security Incidents (<?php echo $year; ?>):</th>
            <td><strong><?php echo $incident_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>Data Breaches:</th>
            <td><strong><?php echo $incident_stats['breaches']; ?></strong></td>
        </tr>
        <tr>
            <th>Breaches Notified to POTRAZ:</th>
            <td><strong><?php echo $incident_stats['notified']; ?></strong></td>
        </tr>
        <tr>
            <th>Notifications within 72 Hours:</th>
            <td><strong><?php echo $incident_stats['on_time']; ?></strong></td>
        </tr>
        <tr>
            <th>Critical Incidents:</th>
            <td><strong><?php echo $incident_stats['critical']; ?></strong></td>
        </tr>
        <tr>
            <th>High Severity Incidents:</th>
            <td><strong><?php echo $incident_stats['high']; ?></strong></td>
        </tr>
    </table>

    <?php if (!empty($incidents_by_type)): ?>
    <div class="subsection-title">Incidents by Type</div>
    <table class="info-table">
        <thead>
            <tr>
                <th>Incident Type</th>
                <th width="20%">Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($incidents_by_type as $type): ?>
            <tr>
                <td><?php echo htmlspecialchars($type['incident_type']); ?></td>
                <td><?php echo $type['count']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php
    if ($incident_stats['breaches'] == 0):
    ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> EXCELLENT - No data breaches reported during <?php echo $year; ?>.
    </div>
    <?php elseif ($incident_stats['breaches'] == $incident_stats['on_time']): ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> COMPLIANT - All data breaches notified to POTRAZ within 72 hours as required by CDPA s.26.
    </div>
    <?php else: ?>
    <div class="alert alert-danger">
        <strong>Compliance Status:</strong> NON-COMPLIANT - <?php echo ($incident_stats['breaches'] - $incident_stats['on_time']); ?> breach notification(s) exceeded 72-hour requirement.
    </div>
    <?php endif; ?>
</div>

<!-- Data Subject Rights Requests -->
<div class="section">
    <div class="section-title">5. DATA SUBJECT RIGHTS (DSR) REQUESTS</div>
    <p><em>CDPA Sections 15-22: Data subjects have rights including access, rectification, erasure, and portability.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Total DSR Requests (<?php echo $year; ?>):</th>
            <td><strong><?php echo $dsr_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>Requests Completed:</th>
            <td><strong><?php echo $dsr_stats['completed']; ?></strong></td>
        </tr>
        <tr>
            <th>Completed within 30 Days:</th>
            <td><strong><?php echo $dsr_stats['on_time']; ?></strong></td>
        </tr>
        <tr>
            <th>Average Response Time:</th>
            <td><strong><?php echo $dsr_stats['avg_days'] ? round($dsr_stats['avg_days']) : 'N/A'; ?> days</strong></td>
        </tr>
    </table>

    <?php if (!empty($dsr_by_type)): ?>
    <div class="subsection-title">Requests by Type</div>
    <table class="info-table">
        <thead>
            <tr>
                <th>Request Type</th>
                <th width="20%">Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dsr_by_type as $type): ?>
            <tr>
                <td><?php echo htmlspecialchars($type['request_type']); ?></td>
                <td><?php echo $type['count']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php
    if ($dsr_stats['total'] == 0):
    ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> No DSR requests received during <?php echo $year; ?>.
    </div>
    <?php elseif ($dsr_stats['completed'] == $dsr_stats['on_time']): ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> COMPLIANT - All DSR requests responded to within 30-day requirement.
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <strong>Compliance Status:</strong> PARTIALLY COMPLIANT - Some requests exceeded 30-day response time.
    </div>
    <?php endif; ?>
</div>

<!-- Consent Management -->
<div class="section">
    <div class="section-title">6. CONSENT MANAGEMENT</div>
    <p><em>CDPA Section 8: Processing of personal data requires consent or another legal basis.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Total Consents Recorded:</th>
            <td><strong><?php echo $consent_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>Active Consents:</th>
            <td><strong><?php echo $consent_stats['active']; ?></strong></td>
        </tr>
        <tr>
            <th>Withdrawn Consents:</th>
            <td><strong><?php echo $consent_stats['withdrawn']; ?></strong></td>
        </tr>
    </table>

    <div class="alert alert-success">
        <strong>Compliance Status:</strong> Organization maintains records of consent and honors withdrawal requests.
    </div>
</div>

<!-- Cross-Border Transfers -->
<div class="section">
    <div class="section-title">7. CROSS-BORDER DATA TRANSFERS</div>
    <p><em>CDPA Section 28: Personal data shall not be transferred outside Zimbabwe unless adequate protections exist.</em></p>

    <table class="info-table">
        <tr>
            <th width="50%">Active Cross-Border Transfers:</th>
            <td><strong><?php echo $transfer_stats['total']; ?></strong></td>
        </tr>
        <tr>
            <th>Destination Countries:</th>
            <td><strong><?php echo $transfer_stats['countries']; ?></strong></td>
        </tr>
    </table>

    <?php if ($transfer_stats['total'] > 0): ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> Cross-border transfers documented with appropriate safeguards.
    </div>
    <?php else: ?>
    <div class="alert alert-success">
        <strong>Compliance Status:</strong> No cross-border data transfers. Not applicable.
    </div>
    <?php endif; ?>
</div>

<!-- Compliance Declaration -->
<div class="section">
    <div class="section-title">8. COMPLIANCE DECLARATION</div>
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong> hereby declares that:
    </p>
    <ul style="line-height: 2.0;">
        <li>We have implemented appropriate technical and organizational measures to ensure data protection compliance;</li>
        <li>We maintain comprehensive records of processing activities as required by CDPA s.12;</li>
        <li>We conduct Data Protection Impact Assessments for high-risk processing activities;</li>
        <li>We have established procedures to respond to data subject rights requests within statutory timeframes;</li>
        <li>We have incident response procedures and report data breaches to POTRAZ as required;</li>
        <li>We continue to review and improve our data protection practices.</li>
    </ul>

    <p>
        This report has been prepared in accordance with the Zimbabwe Cyber and Data Protection Act [Chapter 12:07]
        and the Data Protection (General) Regulations (SI 156 of 2022).
    </p>
</div>

<!-- Signature Section -->
<div class="signature-section">
    <table width="100%">
        <tr>
            <td width="50%">
                <strong>Prepared By:</strong>
                <div class="signature-line"></div>
                Name: _____________________________<br>
                Title: _____________________________<br>
                Date: _____________________________
            </td>
            <td width="50%">
                <strong>Approved By (DPO):</strong>
                <div class="signature-line"></div>
                Name: <?php echo $dpo ? htmlspecialchars($dpo['first_name'] . ' ' . $dpo['last_name']) : '_____________________________'; ?><br>
                Title: Data Protection Officer<br>
                Date: _____________________________
            </td>
        </tr>
    </table>
</div>

<!-- Footer -->
<div class="footer">
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong><br>
        Annual Compliance Report - <?php echo $year; ?><br>
        Generated: <?php echo date('d F Y, H:i'); ?><br>
        <em>This document is confidential and intended for POTRAZ submission only.</em>
    </p>
</div>

</body>
</html>
