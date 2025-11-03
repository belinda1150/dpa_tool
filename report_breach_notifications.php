<?php
/**
 * Data Breach Notifications Report
 * Summary of all breaches reported to POTRAZ
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

// Fetch all data breaches for the year
$breach_query = "SELECT i.*,
                 u.first_name as handler_first, u.last_name as handler_last,
                 u_dept.dept_name,
                 reporter.first_name as reporter_first, reporter.last_name as reporter_last,
                 TIMESTAMPDIFF(HOUR, i.detected_at, i.notified_at) as hours_to_notification
                 FROM incidents i
                 LEFT JOIN users u ON i.detected_by = u.user_id
                 LEFT JOIN departments u_dept ON u.dept_id = u_dept.dept_id
                 LEFT JOIN users reporter ON i.created_by = reporter.user_id
                 WHERE i.org_id = ? AND i.notifiable = 1 AND YEAR(i.detected_at) = ?
                 ORDER BY i.detected_at DESC";

$breaches = db_fetch_all(db_query($breach_query, [$org_id, $year]));

// Calculate statistics
$total_breaches = count($breaches);
$notified = 0;
$on_time = 0;
$late = 0;
$not_notified = 0;

foreach ($breaches as $breach) {
    if ($breach['notified_at']) {
        $notified++;
        if ($breach['hours_to_notification'] <= 72) {
            $on_time++;
        } else {
            $late++;
        }
    } else {
        $not_notified++;
    }
}

$compliance_rate = $total_breaches > 0 ? ($on_time / $total_breaches) * 100 : 0;

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Breach_Notifications_' . $year . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    // Header
    fputcsv($output, ['DATA BREACH NOTIFICATIONS REPORT - ' . $year]);
    fputcsv($output, ['Organization', $org['org_name']]);
    fputcsv($output, ['Generated', date('d M Y, H:i')]);
    fputcsv($output, []);

    // Summary
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Breaches', $total_breaches]);
    fputcsv($output, ['Notified to POTRAZ', $notified]);
    fputcsv($output, ['On Time (<=72 hours)', $on_time]);
    fputcsv($output, ['Late (>72 hours)', $late]);
    fputcsv($output, ['Not Yet Notified', $not_notified]);
    fputcsv($output, ['Compliance Rate', round($compliance_rate, 1) . '%']);
    fputcsv($output, []);

    // Details
    fputcsv($output, ['BREACH DETAILS']);
    fputcsv($output, [
        'Incident ID', 'Date', 'Title', 'Severity',
        'Affected Subjects', 'Data Types', 'POTRAZ Notified',
        'Notification Date', 'Hours to Notify', 'POTRAZ Ref', 'Status'
    ]);

    foreach ($breaches as $breach) {
        fputcsv($output, [
            'INC-' . str_pad($breach['incident_id'], 5, '0', STR_PAD_LEFT),
            date('d M Y, H:i', strtotime($breach['detected_at'])),
            $breach['incident_title'],
            strtoupper($breach['severity']),
            $breach['data_subjects_affected'] ?? 'Unknown',
            $breach['data_categories_affected'] ?? 'Not specified',
            $breach['notified_at'] ? 'YES' : 'NO',
            $breach['notified_at'] ? date('d M Y, H:i', strtotime($breach['notified_at'])) : 'N/A',
            $breach['hours_to_notification'] ?? 'N/A',
            $breach['potraz_ref'] ?? 'N/A',
            $breach['hours_to_notification'] && $breach['hours_to_notification'] <= 72 ? 'ON TIME' : 'LATE'
        ]);
    }

    fclose($output);
    exit;
}

// HTML/PDF Output
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Data Breach Notifications Report <?php echo $year; ?> - <?php echo htmlspecialchars($org['org_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            margin: 30px;
            color: #333;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #c0392b;
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
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #c0392b;
            color: white;
            padding: 12px;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .info-table th {
            background-color: #ecf0f1;
            text-align: left;
            padding: 8px;
            font-weight: bold;
            border: 1px solid #bdc3c7;
        }
        .info-table td {
            padding: 8px;
            border: 1px solid #bdc3c7;
        }
        .breach-card {
            border: 2px solid #c0392b;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #fff5f5;
            page-break-inside: avoid;
        }
        .breach-header {
            background-color: #c0392b;
            color: white;
            padding: 10px;
            margin: -15px -15px 15px -15px;
            font-size: 13pt;
            font-weight: bold;
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
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
            font-size: 9pt;
        }
        .badge-success { background-color: #27ae60; }
        .badge-danger { background-color: #c0392b; }
        .badge-warning { background-color: #f39c12; }
        .badge-info { background-color: #3498db; }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #c0392b;
            font-size: 9pt;
            color: #666;
            text-align: center;
        }
        @media print {
            body { margin: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<!-- Print/Download Buttons -->
<div class="no-print" style="text-align: right; margin-bottom: 20px;">
    <button onclick="window.print();" style="padding: 10px 20px; font-size: 12pt; cursor: pointer;">
        <i class="fa fa-file-pdf-o"></i> Print / Save as PDF
    </button>
    <button onclick="window.location.href='report_breach_notifications.php?format=csv&year=<?php echo $year; ?>';"
            style="padding: 10px 20px; font-size: 12pt; cursor: pointer; margin-left: 10px;">
        Export CSV
    </button>
    <button onclick="window.location.href='reports_list.php';"
            style="padding: 10px 20px; font-size: 12pt; cursor: pointer; margin-left: 10px;">
        Back to Reports
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1>DATA BREACH NOTIFICATIONS REPORT</h1>
    <p style="font-size: 16pt; margin: 15px 0;"><strong><?php echo $year; ?></strong></p>
    <p><strong><?php echo htmlspecialchars($org['org_name']); ?></strong></p>
    <p>Submitted to: Postal and Telecommunications Regulatory Authority of Zimbabwe (POTRAZ)</p>
    <p>Report Date: <?php echo date('d F Y'); ?></p>
</div>

<!-- Legal Requirement Notice -->
<div class="section">
    <div class="alert alert-danger">
        <strong>CDPA Section 26 - Data Breach Notification:</strong><br>
        A data controller shall notify the Authority of a data breach within 72 hours of becoming aware of the breach.
        Failure to comply may result in penalties under the Cyber and Data Protection Act [Chapter 12:07].
    </div>
</div>

<!-- Summary Statistics -->
<div class="section">
    <div class="section-title">SUMMARY</div>
    <table class="info-table">
        <tr>
            <th width="50%">Reporting Period:</th>
            <td><strong>1 January <?php echo $year; ?> - 31 December <?php echo $year; ?></strong></td>
        </tr>
        <tr>
            <th>Total Data Breaches:</th>
            <td><strong style="font-size: 14pt; color: #c0392b;"><?php echo $total_breaches; ?></strong></td>
        </tr>
        <tr>
            <th>Breaches Notified to POTRAZ:</th>
            <td><strong><?php echo $notified; ?></strong></td>
        </tr>
        <tr>
            <th>Notifications within 72 Hours:</th>
            <td>
                <strong><?php echo $on_time; ?></strong>
                <?php if ($on_time > 0): ?>
                    <span class="badge badge-success">ON TIME</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Late Notifications (&gt;72 hours):</th>
            <td>
                <strong><?php echo $late; ?></strong>
                <?php if ($late > 0): ?>
                    <span class="badge badge-danger">LATE</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Pending Notification:</th>
            <td>
                <strong><?php echo $not_notified; ?></strong>
                <?php if ($not_notified > 0): ?>
                    <span class="badge badge-warning">ACTION REQUIRED</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Compliance Rate:</th>
            <td>
                <strong style="font-size: 14pt; color: <?php echo $compliance_rate >= 80 ? '#27ae60' : '#c0392b'; ?>;">
                    <?php echo round($compliance_rate, 1); ?>%
                </strong>
            </td>
        </tr>
    </table>

    <?php if ($total_breaches == 0): ?>
        <div class="alert alert-success">
            <strong>Compliance Status:</strong> EXCELLENT - No data breaches occurred during <?php echo $year; ?>.
        </div>
    <?php elseif ($compliance_rate == 100): ?>
        <div class="alert alert-success">
            <strong>Compliance Status:</strong> COMPLIANT - All data breaches were notified to POTRAZ within 72 hours as required by CDPA s.26.
        </div>
    <?php elseif ($not_notified > 0): ?>
        <div class="alert alert-danger">
            <strong>Compliance Status:</strong> NON-COMPLIANT - <?php echo $not_notified; ?> breach(es) have not been notified to POTRAZ. Immediate action required.
        </div>
    <?php elseif ($late > 0): ?>
        <div class="alert alert-danger">
            <strong>Compliance Status:</strong> NON-COMPLIANT - <?php echo $late; ?> breach(es) were notified after the 72-hour deadline.
        </div>
    <?php endif; ?>
</div>

<!-- Detailed Breach Records -->
<?php if ($total_breaches > 0): ?>
<div class="section">
    <div class="section-title">DETAILED BREACH RECORDS</div>

    <?php foreach ($breaches as $index => $breach): ?>
    <div class="breach-card">
        <div class="breach-header">
            BREACH #<?php echo ($index + 1); ?>: INC-<?php echo str_pad($breach['incident_id'], 5, '0', STR_PAD_LEFT); ?> -
            <?php echo htmlspecialchars($breach['incident_title']); ?>
        </div>

        <table class="info-table">
            <tr>
                <th width="30%">Incident Date:</th>
                <td><?php echo date('d F Y, H:i', strtotime($breach['detected_at'])); ?></td>
            </tr>
            <tr>
                <th>Severity:</th>
                <td>
                    <?php
                    $sev_class = ['minor'=>'success','serious'=>'warning','critical'=>'danger'][$breach['severity']] ?? 'info';
                    echo "<span class='badge badge-$sev_class'>" . strtoupper($breach['severity']) . "</span>";
                    ?>
                </td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?php echo htmlspecialchars($breach['dept_name'] ?? 'Not Specified'); ?></td>
            </tr>
            <tr>
                <th>Reported By:</th>
                <td><?php echo htmlspecialchars($breach['reporter_first'] . ' ' . $breach['reporter_last']); ?></td>
            </tr>
            <tr>
                <th>Incident Handler:</th>
                <td><?php echo $breach['handler_first'] ? htmlspecialchars($breach['handler_first'] . ' ' . $breach['handler_last']) : 'Unassigned'; ?></td>
            </tr>
        </table>

        <p><strong>Description:</strong></p>
        <p style="background-color: white; padding: 10px; border: 1px solid #ddd;">
            <?php echo nl2br(htmlspecialchars($breach['incident_description'])); ?>
        </p>

        <p><strong>Affected Data Subjects:</strong></p>
        <p style="background-color: white; padding: 10px; border: 1px solid #ddd;">
            <?php echo htmlspecialchars($breach['data_subjects_affected'] ?? 'Unknown'); ?>
        </p>

        <p><strong>Data Types Affected:</strong></p>
        <p style="background-color: white; padding: 10px; border: 1px solid #ddd;">
            <?php echo nl2br(htmlspecialchars($breach['data_categories_affected'] ?? 'Not specified')); ?>
        </p>

        <table class="info-table" style="margin-top: 15px;">
            <tr style="background-color: <?php echo $breach['notified_at'] ? '#d4edda' : '#f8d7da'; ?>;">
                <th width="30%">POTRAZ Notification Status:</th>
                <td>
                    <?php if ($breach['notified_at']): ?>
                        <strong style="color: #27ae60;">NOTIFIED</strong>
                        <span class="badge badge-<?php echo ($breach['hours_to_notification'] <= 72) ? 'success' : 'danger'; ?>">
                            <?php echo ($breach['hours_to_notification'] <= 72) ? 'ON TIME' : 'LATE'; ?>
                        </span>
                    <?php else: ?>
                        <strong style="color: #c0392b;">NOT NOTIFIED</strong>
                        <span class="badge badge-danger">ACTION REQUIRED</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($breach['notified_at']): ?>
            <tr>
                <th>Notification Date:</th>
                <td><?php echo date('d F Y, H:i', strtotime($breach['notified_at'])); ?></td>
            </tr>
            <tr>
                <th>Hours to Notification:</th>
                <td>
                    <strong><?php echo $breach['hours_to_notification']; ?> hours</strong>
                    <?php if ($breach['hours_to_notification'] <= 72): ?>
                        (Within 72-hour requirement)
                    <?php else: ?>
                        <strong style="color: #c0392b;">(EXCEEDED 72-hour requirement)</strong>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>POTRAZ Reference Number:</th>
                <td><strong><?php echo htmlspecialchars($breach['potraz_ref']); ?></strong></td>
            </tr>
            <?php if ($breach['potraz_notes']): ?>
            <tr>
                <th>Notification Notes:</th>
                <td><?php echo nl2br(htmlspecialchars($breach['potraz_notes'])); ?></td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
        </table>

        <?php if ($breach['immediate_actions']): ?>
        <p><strong>Immediate Actions Taken:</strong></p>
        <p style="background-color: white; padding: 10px; border: 1px solid #ddd;">
            <?php echo nl2br(htmlspecialchars($breach['immediate_actions'])); ?>
        </p>
        <?php endif; ?>

        <table class="info-table" style="margin-top: 15px;">
            <tr>
                <th width="30%">Current Status:</th>
                <td>
                    <?php
                    $st_class = ['open'=>'danger','investigating'=>'warning','contained'=>'info','resolved'=>'info','closed'=>'success'][$breach['status']] ?? 'info';
                    echo "<span class='badge badge-$st_class'>" . strtoupper($breach['status']) . "</span>";
                    ?>
                </td>
            </tr>
        </table>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Compliance Statement -->
<div class="section">
    <div class="section-title">COMPLIANCE STATEMENT</div>
    <p>
        This report has been prepared in accordance with the Zimbabwe Cyber and Data Protection Act [Chapter 12:07]
        Section 26, which requires data controllers to notify POTRAZ of any data breach within 72 hours of becoming aware.
    </p>

    <?php if ($total_breaches > 0): ?>
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong> acknowledges its obligations under CDPA s.26
        and commits to:
    </p>
    <ul>
        <li>Promptly investigating all security incidents and data breaches;</li>
        <li>Notifying POTRAZ within 72 hours of becoming aware of any personal data breach;</li>
        <li>Implementing corrective measures to prevent future breaches;</li>
        <li>Maintaining accurate records of all breach notifications.</li>
    </ul>
    <?php endif; ?>

    <?php if ($late > 0 || $not_notified > 0): ?>
    <div class="alert alert-danger">
        <strong>Remedial Actions Required:</strong><br>
        <?php if ($not_notified > 0): ?>
        - Immediately notify POTRAZ of <?php echo $not_notified; ?> outstanding breach(es)<br>
        <?php endif; ?>
        <?php if ($late > 0): ?>
        - Review breach notification procedures to ensure future compliance with 72-hour deadline<br>
        <?php endif; ?>
        - Implement enhanced incident detection and response mechanisms
    </div>
    <?php endif; ?>
</div>

<!-- Signature Section -->
<div class="section" style="margin-top: 50px;">
    <table width="100%">
        <tr>
            <td width="50%">
                <strong>Prepared By:</strong><br>
                <div style="border-top: 1px solid #333; width: 250px; margin: 40px 0 10px 0;"></div>
                Name: _________________________<br>
                Title: _________________________<br>
                Date: _________________________
            </td>
            <td width="50%">
                <strong>Reviewed By (DPO):</strong><br>
                <div style="border-top: 1px solid #333; width: 250px; margin: 40px 0 10px 0;"></div>
                Name: _________________________<br>
                Title: Data Protection Officer<br>
                Date: _________________________
            </td>
        </tr>
    </table>
</div>

<!-- Footer -->
<div class="footer">
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong><br>
        Data Breach Notifications Report - <?php echo $year; ?><br>
        Generated: <?php echo date('d F Y, H:i'); ?><br>
        <em>This document is confidential and contains sensitive security information.</em>
    </p>
</div>

</body>
</html>
