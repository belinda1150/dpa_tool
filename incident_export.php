<?php
/**
 * Export Incident Report
 * Export incident details in CSV or print-to-PDF format
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

if ($incident_id <= 0) {
    set_flash_message('Invalid incident ID.', 'danger');
    redirect('incident_list.php');
}

// Fetch incident details
$query = "SELECT i.*,
          u.first_name as handler_first, u.last_name as handler_last,
          reporter.first_name as reporter_first, reporter.last_name as reporter_last,
          dept.dept_name,
          pa.activity_name,
          TIMESTAMPDIFF(HOUR, i.incident_date, NOW()) as hours_since,
          TIMESTAMPDIFF(HOUR, i.incident_date, i.potraz_notified_date) as hours_to_notification
          FROM incidents i
          LEFT JOIN users u ON i.incident_handler_id = u.user_id
          LEFT JOIN users reporter ON i.reported_by = reporter.user_id
          LEFT JOIN departments dept ON i.dept_id = dept.dept_id
          LEFT JOIN processing_activities pa ON i.ropa_id = pa.ropa_id
          WHERE i.incident_id = ? AND i.org_id = ?";

$stmt = db_query($query, [$incident_id, $org_id]);
$incident = db_fetch_one($stmt);

if (!$incident) {
    set_flash_message('Incident not found.', 'danger');
    redirect('incident_list.php');
}

// Fetch organization details
$org_query = "SELECT * FROM organizations WHERE org_id = ?";
$org = db_fetch_one(db_query($org_query, [$org_id]));

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Incident_Report_' . $incident_id . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header
    fputcsv($output, ['INCIDENT REPORT']);
    fputcsv($output, ['Generated', date('d M Y, H:i')]);
    fputcsv($output, ['Organization', $org['org_name']]);
    fputcsv($output, []);

    // Basic Information
    fputcsv($output, ['BASIC INFORMATION']);
    fputcsv($output, ['Incident ID', 'INC-' . str_pad($incident_id, 5, '0', STR_PAD_LEFT)]);
    fputcsv($output, ['Incident Title', $incident['incident_title']]);
    fputcsv($output, ['Incident Type', $incident['incident_type']]);
    fputcsv($output, ['Severity', strtoupper($incident['severity'])]);
    fputcsv($output, ['Status', strtoupper($incident['status'])]);
    fputcsv($output, ['Incident Date', date('d M Y, H:i', strtotime($incident['incident_date']))]);
    fputcsv($output, ['Department', $incident['dept_name'] ?? 'N/A']);
    fputcsv($output, ['Linked ROPA', $incident['activity_name'] ?? 'N/A']);
    fputcsv($output, ['Reported By', $incident['reporter_first'] . ' ' . $incident['reporter_last']]);
    fputcsv($output, ['Incident Handler', $incident['handler_first'] ? $incident['handler_first'] . ' ' . $incident['handler_last'] : 'Unassigned']);
    fputcsv($output, []);

    // Description
    fputcsv($output, ['INCIDENT DESCRIPTION']);
    fputcsv($output, [$incident['incident_description']]);
    fputcsv($output, []);

    // Data Breach Details
    if ($incident['is_data_breach']) {
        fputcsv($output, ['DATA BREACH DETAILS']);
        fputcsv($output, ['Data Breach', 'YES']);
        fputcsv($output, ['Affected Data Subjects', $incident['affected_data_subjects'] ?? 'Unknown']);
        fputcsv($output, ['Data Types Affected', $incident['data_types_affected'] ?? 'Not specified']);
        fputcsv($output, []);

        fputcsv($output, ['POTRAZ NOTIFICATION']);
        if ($incident['potraz_notified_date']) {
            fputcsv($output, ['Notified', 'YES']);
            fputcsv($output, ['Notification Date', date('d M Y, H:i', strtotime($incident['potraz_notified_date']))]);
            fputcsv($output, ['POTRAZ Reference', $incident['potraz_reference']]);
            fputcsv($output, ['Hours to Notification', $incident['hours_to_notification']]);
            fputcsv($output, ['Compliance', $incident['hours_to_notification'] <= 72 ? 'Within 72 hours' : 'LATE']);
            if ($incident['potraz_notes']) {
                fputcsv($output, ['Notification Notes', $incident['potraz_notes']]);
            }
        } else {
            fputcsv($output, ['Notified', 'NO']);
            fputcsv($output, ['Hours Elapsed', floor($incident['hours_since'])]);
            fputcsv($output, ['Status', $incident['hours_since'] > 72 ? 'OVERDUE' : 'PENDING']);
        }
        fputcsv($output, []);
    }

    // Immediate Actions
    fputcsv($output, ['IMMEDIATE ACTIONS TAKEN']);
    fputcsv($output, [$incident['immediate_actions'] ?: 'None recorded']);
    fputcsv($output, []);

    // Metadata
    fputcsv($output, ['METADATA']);
    fputcsv($output, ['Created', date('d M Y, H:i', strtotime($incident['created_at']))]);
    fputcsv($output, ['Last Updated', date('d M Y, H:i', strtotime($incident['updated_at']))]);

    fclose($output);
    exit;
}

// PDF Export (HTML format for print-to-PDF)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Incident Report - INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.6;
            margin: 40px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24pt;
            color: #c0392b;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #34495e;
            color: white;
            padding: 10px;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table th {
            background-color: #ecf0f1;
            text-align: left;
            padding: 10px;
            font-weight: bold;
            width: 30%;
            border: 1px solid #bdc3c7;
        }
        .info-table td {
            padding: 10px;
            border: 1px solid #bdc3c7;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
        }
        .badge-danger { background-color: #c0392b; }
        .badge-warning { background-color: #e67e22; }
        .badge-success { background-color: #27ae60; }
        .badge-info { background-color: #3498db; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #c0392b;
            color: #721c24;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #e67e22;
            color: #856404;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #27ae60;
            color: #155724;
        }
        .description-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            white-space: pre-wrap;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #333;
            font-size: 10pt;
            color: #666;
            text-align: center;
        }
        .signature-box {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 300px;
            margin: 50px auto 10px auto;
        }
        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<!-- Print Button -->
<div class="no-print" style="text-align: right; margin-bottom: 20px;">
    <button onclick="window.print();" style="padding: 10px 20px; font-size: 14pt; cursor: pointer;">
        Print / Save as PDF
    </button>
    <button onclick="window.location.href='incident_view.php?id=<?php echo $incident_id; ?>';"
            style="padding: 10px 20px; font-size: 14pt; cursor: pointer; margin-left: 10px;">
        Back to Incident
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1>SECURITY INCIDENT REPORT</h1>
    <p><strong><?php echo htmlspecialchars($org['org_name']); ?></strong></p>
    <p>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></p>
    <p>Generated: <?php echo date('d F Y, H:i'); ?></p>
</div>

<!-- Classification Alert -->
<?php if ($incident['is_data_breach']): ?>
<div class="alert alert-danger">
    <strong>DATA BREACH INCIDENT</strong><br>
    This incident involves a breach of personal data and requires POTRAZ notification under CDPA s.26
</div>
<?php endif; ?>

<!-- Basic Information -->
<div class="section">
    <div class="section-title">BASIC INFORMATION</div>
    <table class="info-table">
        <tr>
            <th>Incident ID:</th>
            <td>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></td>
        </tr>
        <tr>
            <th>Incident Title:</th>
            <td><strong><?php echo htmlspecialchars($incident['incident_title']); ?></strong></td>
        </tr>
        <tr>
            <th>Incident Type:</th>
            <td>
                <span class="badge badge-info"><?php echo htmlspecialchars($incident['incident_type']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Severity:</th>
            <td>
                <?php
                $sev_class = ['low'=>'success','medium'=>'info','high'=>'warning','critical'=>'danger'][$incident['severity']] ?? 'info';
                echo "<span class='badge badge-$sev_class'>" . strtoupper($incident['severity']) . "</span>";
                ?>
            </td>
        </tr>
        <tr>
            <th>Status:</th>
            <td>
                <?php
                $st_class = ['open'=>'danger','investigating'=>'warning','contained'=>'info','resolved'=>'info','closed'=>'success'][$incident['status']] ?? 'info';
                echo "<span class='badge badge-$st_class'>" . strtoupper($incident['status']) . "</span>";
                ?>
            </td>
        </tr>
        <tr>
            <th>Incident Date:</th>
            <td><?php echo date('d F Y, H:i', strtotime($incident['incident_date'])); ?></td>
        </tr>
        <tr>
            <th>Department:</th>
            <td><?php echo htmlspecialchars($incident['dept_name'] ?? 'Not Linked'); ?></td>
        </tr>
        <tr>
            <th>Linked ROPA:</th>
            <td><?php echo htmlspecialchars($incident['activity_name'] ?? 'Not Linked'); ?></td>
        </tr>
        <tr>
            <th>Reported By:</th>
            <td><?php echo htmlspecialchars($incident['reporter_first'] . ' ' . $incident['reporter_last']); ?></td>
        </tr>
        <tr>
            <th>Incident Handler:</th>
            <td><?php echo $incident['handler_first'] ? htmlspecialchars($incident['handler_first'] . ' ' . $incident['handler_last']) : 'Unassigned'; ?></td>
        </tr>
    </table>
</div>

<!-- Incident Description -->
<div class="section">
    <div class="section-title">INCIDENT DESCRIPTION</div>
    <div class="description-box">
<?php echo htmlspecialchars($incident['incident_description']); ?>
    </div>
</div>

<!-- Data Breach Details -->
<?php if ($incident['is_data_breach']): ?>
<div class="section">
    <div class="section-title">DATA BREACH DETAILS</div>
    <table class="info-table">
        <tr>
            <th>Affected Data Subjects:</th>
            <td><?php echo htmlspecialchars($incident['affected_data_subjects'] ?? 'Unknown'); ?></td>
        </tr>
        <tr>
            <th>Data Types Affected:</th>
            <td><?php echo nl2br(htmlspecialchars($incident['data_types_affected'] ?? 'Not specified')); ?></td>
        </tr>
    </table>
</div>

<!-- POTRAZ Notification Status -->
<div class="section">
    <div class="section-title">POTRAZ NOTIFICATION STATUS</div>
    <?php if ($incident['potraz_notified_date']): ?>
        <div class="alert alert-success">
            <strong>POTRAZ Notified:</strong> <?php echo date('d F Y, H:i', strtotime($incident['potraz_notified_date'])); ?><br>
            <strong>Reference Number:</strong> <?php echo htmlspecialchars($incident['potraz_reference']); ?><br>
            <strong>Time to Notification:</strong> <?php echo $incident['hours_to_notification']; ?> hours
            <?php if ($incident['hours_to_notification'] <= 72): ?>
                (Within 72-hour requirement)
            <?php else: ?>
                <span style="color: #c0392b;">(LATE - exceeded 72-hour requirement)</span>
            <?php endif; ?>
        </div>
        <?php if ($incident['potraz_notes']): ?>
            <p><strong>Notification Notes:</strong></p>
            <div class="description-box">
<?php echo nl2br(htmlspecialchars($incident['potraz_notes'])); ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-danger">
            <strong>POTRAZ NOT YET NOTIFIED</strong><br>
            Hours Elapsed: <?php echo floor($incident['hours_since']); ?><br>
            <?php if ($incident['hours_since'] > 72): ?>
                <strong style="color: #c0392b;">STATUS: OVERDUE (Exceeded 72-hour requirement)</strong>
            <?php else: ?>
                Hours Remaining: <?php echo floor(72 - $incident['hours_since']); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Immediate Actions -->
<div class="section">
    <div class="section-title">IMMEDIATE ACTIONS TAKEN</div>
    <div class="description-box">
<?php echo nl2br(htmlspecialchars($incident['immediate_actions'] ?: 'None recorded')); ?>
    </div>
</div>

<!-- Metadata -->
<div class="section">
    <div class="section-title">METADATA</div>
    <table class="info-table">
        <tr>
            <th>Created:</th>
            <td><?php echo date('d F Y, H:i', strtotime($incident['created_at'])); ?></td>
        </tr>
        <tr>
            <th>Last Updated:</th>
            <td><?php echo date('d F Y, H:i', strtotime($incident['updated_at'])); ?></td>
        </tr>
    </table>
</div>

<!-- Compliance Statement -->
<div class="section">
    <div class="section-title">COMPLIANCE STATEMENT</div>
    <p>
        This incident report has been prepared in accordance with the Zimbabwe Cyber and Data Protection Act
        [Chapter 12:07] and Data Protection (General) Regulations (SI 156 of 2022).
    </p>
    <?php if ($incident['is_data_breach']): ?>
        <p>
            <strong>Legal Requirement:</strong> Section 26 of the CDPA requires data controllers to notify
            POTRAZ of any data breach within 72 hours of becoming aware of the breach.
        </p>
    <?php endif; ?>
</div>

<!-- Signature Box -->
<div class="signature-box">
    <p><strong>Prepared By:</strong></p>
    <div class="signature-line"></div>
    <p style="text-align: center; margin: 0;">
        Name: _________________________________ Date: _________________
    </p>

    <p style="margin-top: 40px;"><strong>Reviewed By (DPO):</strong></p>
    <div class="signature-line"></div>
    <p style="text-align: center; margin: 0;">
        Name: _________________________________ Date: _________________
    </p>
</div>

<!-- Footer -->
<div class="footer">
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong><br>
        DPA Compliance Management System<br>
        Report Generated: <?php echo date('d F Y, H:i'); ?>
    </p>
</div>

</body>
</html>
