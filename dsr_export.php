<?php
/**
 * Export DSR Request
 * Export DSR details in CSV or PDF format
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$dsr_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

if ($dsr_id <= 0) {
    set_flash_message('Invalid DSR request ID.', 'danger');
    redirect('dsr_list.php');
}

// Fetch DSR details
$query = "SELECT d.*,
          u.first_name as handler_first, u.last_name as handler_last,
          creator.first_name as creator_first, creator.last_name as creator_last,
          pa.activity_name,
          DATEDIFF(NOW(), d.received_at) as days_elapsed,
          DATEDIFF(d.completed_at, d.received_at) as days_to_complete
          FROM dsr_requests d
          LEFT JOIN users u ON d.assigned_to = u.user_id
          LEFT JOIN users creator ON d.created_by = creator.user_id
          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
          WHERE d.dsr_id = ? AND d.org_id = ?";

$dsr = db_fetch_one(db_query($query, [$dsr_id, $org_id]));

if (!$dsr) {
    set_flash_message('DSR request not found.', 'danger');
    redirect('dsr_list.php');
}

// Fetch organization details
$org_query = "SELECT * FROM organizations WHERE org_id = ?";
$org = db_fetch_one(db_query($org_query, [$org_id]));

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="DSR_Request_' . $dsr_id . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

    // Header
    fputcsv($output, ['DSR REQUEST DETAILS']);
    fputcsv($output, ['Organization', $org['org_name']]);
    fputcsv($output, ['Generated', date('d M Y, H:i')]);
    fputcsv($output, []);

    // Request Information
    fputcsv($output, ['REQUEST INFORMATION']);
    fputcsv($output, ['Request ID', 'DSR-' . str_pad($dsr_id, 5, '0', STR_PAD_LEFT)]);
    fputcsv($output, ['Request Type', $dsr['request_type']]);
    fputcsv($output, ['Request Date', date('d M Y', strtotime($dsr['received_at']))]);
    fputcsv($output, ['Request Method', $dsr['request_method']]);
    fputcsv($output, ['Priority', ucfirst($dsr['priority'])]);
    fputcsv($output, ['Status', ucfirst(str_replace('_', ' ', $dsr['status']))]);
    fputcsv($output, []);

    // Data Subject
    fputcsv($output, ['DATA SUBJECT INFORMATION']);
    fputcsv($output, ['Name', $dsr['subject_name']]);
    fputcsv($output, ['Email', $dsr['subject_email']]);
    fputcsv($output, ['Phone', $dsr['subject_phone'] ?? 'N/A']);
    fputcsv($output, ['ID Number', $dsr['subject_id_number'] ?? 'N/A']);
    fputcsv($output, ['Identity Verified', $dsr['identity_verified'] ? 'Yes' : 'No']);
    if ($dsr['identity_verified']) {
        fputcsv($output, ['Verification Date', date('d M Y, H:i', strtotime($dsr['verification_date']))]);
        fputcsv($output, ['Verification Method', $dsr['verification_method']]);
    }
    fputcsv($output, []);

    // Request Details
    fputcsv($output, ['REQUEST DESCRIPTION']);
    fputcsv($output, [$dsr['request_description']]);
    fputcsv($output, []);

    // Assignment
    fputcsv($output, ['ASSIGNMENT']);
    fputcsv($output, ['Assigned To', $dsr['handler_first'] ? $dsr['handler_first'] . ' ' . $dsr['handler_last'] : 'Unassigned']);
    fputcsv($output, ['Linked ROPA', $dsr['activity_name'] ?? 'N/A']);
    fputcsv($output, []);

    // SLA Tracking
    fputcsv($output, ['SLA TRACKING']);
    if ($dsr['status'] == 'completed' || $dsr['status'] == 'rejected') {
        fputcsv($output, ['Days to Complete', $dsr['days_to_complete']]);
        fputcsv($output, ['SLA Compliance', $dsr['days_to_complete'] <= 30 ? 'On Time' : 'Late']);
    } else {
        fputcsv($output, ['Days Elapsed', $dsr['days_elapsed']]);
        fputcsv($output, ['Days Remaining', max(0, 30 - $dsr['days_elapsed'])]);
        fputcsv($output, ['SLA Status', $dsr['days_elapsed'] > 30 ? 'Overdue' : 'On Track']);
    }
    fputcsv($output, []);

    // Response (if completed)
    if ($dsr['status'] == 'completed' && $dsr['response_notes']) {
        fputcsv($output, ['RESPONSE TO DATA SUBJECT']);
        fputcsv($output, [$dsr['response_notes']]);
        fputcsv($output, []);
    }

    // Rejection (if rejected)
    if ($dsr['status'] == 'rejected' && $dsr['rejection_reason']) {
        fputcsv($output, ['REJECTION REASON']);
        fputcsv($output, [$dsr['rejection_reason']]);
        fputcsv($output, []);
    }

    // Metadata
    fputcsv($output, ['METADATA']);
    fputcsv($output, ['Created By', $dsr['creator_first'] . ' ' . $dsr['creator_last']]);
    fputcsv($output, ['Created', date('d M Y, H:i', strtotime($dsr['created_at']))]);
    fputcsv($output, ['Last Updated', date('d M Y, H:i', strtotime($dsr['updated_at']))]);

    fclose($output);
    exit;
}

// HTML/PDF Output
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DSR Request - DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?></title>
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
            border-bottom: 3px solid #3498db;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24pt;
            color: #3498db;
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
            background-color: #3498db;
            color: white;
            padding: 10px;
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
        .badge-success { background-color: #27ae60; }
        .badge-info { background-color: #3498db; }
        .badge-warning { background-color: #f39c12; }
        .badge-danger { background-color: #c0392b; }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
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
            border-top: 2px solid #3498db;
            font-size: 10pt;
            color: #666;
            text-align: center;
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
    <button onclick="window.location.href='dsr_view.php?id=<?php echo $dsr_id; ?>';"
            style="padding: 10px 20px; font-size: 14pt; cursor: pointer; margin-left: 10px;">
        Back to Request
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1>DATA SUBJECT RIGHTS REQUEST</h1>
    <p><strong><?php echo htmlspecialchars($org['org_name']); ?></strong></p>
    <p>DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?></p>
    <p>Generated: <?php echo date('d F Y, H:i'); ?></p>
</div>

<!-- Request Summary -->
<div class="section">
    <div class="section-title">REQUEST INFORMATION</div>
    <table class="info-table">
        <tr>
            <th>Request ID:</th>
            <td><strong>DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?></strong></td>
        </tr>
        <tr>
            <th>Request Type:</th>
            <td>
                <span class="badge badge-info"><?php echo htmlspecialchars($dsr['request_type']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Status:</th>
            <td>
                <?php
                $status_class = [
                    'pending' => 'warning',
                    'in_progress' => 'info',
                    'completed' => 'success',
                    'rejected' => 'danger'
                ][$dsr['status']] ?? 'info';
                echo "<span class='badge badge-$status_class'>" . strtoupper(str_replace('_', ' ', $dsr['status'])) . "</span>";
                ?>
            </td>
        </tr>
        <tr>
            <th>Priority:</th>
            <td><?php echo ucfirst($dsr['priority']); ?></td>
        </tr>
        <tr>
            <th>Request Date:</th>
            <td><strong><?php echo date('d F Y', strtotime($dsr['received_at'])); ?></strong></td>
        </tr>
        <tr>
            <th>Request Method:</th>
            <td><?php echo htmlspecialchars($dsr['request_method']); ?></td>
        </tr>
        <tr>
            <th>Assigned To:</th>
            <td>
                <?php
                if ($dsr['handler_first']) {
                    echo htmlspecialchars($dsr['handler_first'] . ' ' . $dsr['handler_last']);
                } else {
                    echo 'Unassigned';
                }
                ?>
            </td>
        </tr>
        <tr>
            <th>Linked ROPA Entry:</th>
            <td><?php echo htmlspecialchars($dsr['activity_name'] ?? 'Not Linked'); ?></td>
        </tr>
    </table>
</div>

<!-- Data Subject Information -->
<div class="section">
    <div class="section-title">DATA SUBJECT INFORMATION</div>
    <table class="info-table">
        <tr>
            <th>Full Name:</th>
            <td><strong><?php echo htmlspecialchars($dsr['subject_name']); ?></strong></td>
        </tr>
        <tr>
            <th>Email Address:</th>
            <td><?php echo htmlspecialchars($dsr['subject_email']); ?></td>
        </tr>
        <tr>
            <th>Phone Number:</th>
            <td><?php echo htmlspecialchars($dsr['subject_phone'] ?? 'Not Provided'); ?></td>
        </tr>
        <tr>
            <th>ID Number / Passport:</th>
            <td><?php echo htmlspecialchars($dsr['subject_id_number'] ?? 'Not Provided'); ?></td>
        </tr>
        <tr>
            <th>Identity Verification:</th>
            <td>
                <?php if ($dsr['identity_verified']): ?>
                    <span class="badge badge-success">VERIFIED</span><br>
                    Date: <?php echo date('d F Y, H:i', strtotime($dsr['verification_date'])); ?><br>
                    Method: <?php echo htmlspecialchars($dsr['verification_method']); ?>
                    <?php if ($dsr['verification_notes']): ?>
                        <br>Notes: <?php echo htmlspecialchars($dsr['verification_notes']); ?>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge badge-warning">NOT VERIFIED</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>
</div>

<!-- Request Description -->
<div class="section">
    <div class="section-title">REQUEST DESCRIPTION</div>
    <div class="description-box">
<?php echo htmlspecialchars($dsr['request_description']); ?>
    </div>

    <?php if ($dsr['internal_notes']): ?>
    <p style="margin-top: 20px;"><strong>Internal Processing Notes:</strong></p>
    <div class="description-box" style="background-color: #fffbf0; border-color: #f0e68c;">
<?php echo htmlspecialchars($dsr['internal_notes']); ?>
    </div>
    <?php endif; ?>
</div>

<!-- SLA Tracking -->
<div class="section">
    <div class="section-title">SLA TRACKING (30-DAY REQUIREMENT)</div>
    <table class="info-table">
        <?php if ($dsr['status'] == 'completed' || $dsr['status'] == 'rejected'): ?>
        <tr>
            <th>Completion Date:</th>
            <td><?php echo date('d F Y, H:i', strtotime($dsr['completion_date'])); ?></td>
        </tr>
        <tr>
            <th>Days to Complete:</th>
            <td>
                <strong><?php echo $dsr['days_to_complete']; ?> days</strong>
            </td>
        </tr>
        <tr>
            <th>SLA Compliance:</th>
            <td>
                <?php if ($dsr['days_to_complete'] <= 30): ?>
                    <span class="badge badge-success">ON TIME</span> - Completed within 30-day requirement
                <?php else: ?>
                    <span class="badge badge-danger">LATE</span> - Exceeded 30-day requirement by <?php echo ($dsr['days_to_complete'] - 30); ?> days
                <?php endif; ?>
            </td>
        </tr>
        <?php else: ?>
        <tr>
            <th>Days Elapsed:</th>
            <td><strong><?php echo $dsr['days_elapsed']; ?> days</strong></td>
        </tr>
        <tr>
            <th>Days Remaining:</th>
            <td><strong><?php echo max(0, 30 - $dsr['days_elapsed']); ?> days</strong></td>
        </tr>
        <tr>
            <th>SLA Status:</th>
            <td>
                <?php if ($dsr['days_elapsed'] > 30): ?>
                    <span class="badge badge-danger">OVERDUE</span> - Exceeded 30-day deadline
                <?php elseif ($dsr['days_elapsed'] >= 25): ?>
                    <span class="badge badge-warning">DUE SOON</span> - Approaching deadline
                <?php else: ?>
                    <span class="badge badge-success">ON TRACK</span> - Within SLA
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="alert alert-<?php echo ($dsr['status'] == 'completed' && $dsr['days_to_complete'] <= 30) ? 'success' : 'warning'; ?>">
        <strong>CDPA Requirement:</strong> Data controllers must respond to data subject rights requests within 30 days of receipt.
        Extensions may be granted in complex cases, but the data subject must be informed within the initial 30-day period.
    </div>
</div>

<!-- Response Details (if completed/rejected) -->
<?php if ($dsr['status'] == 'completed' || $dsr['status'] == 'rejected'): ?>
<div class="section">
    <div class="section-title">
        <?php echo $dsr['status'] == 'completed' ? 'RESPONSE TO DATA SUBJECT' : 'REJECTION DETAILS'; ?>
    </div>

    <?php if ($dsr['status'] == 'completed' && $dsr['response_notes']): ?>
    <div class="description-box">
<?php echo nl2br(htmlspecialchars($dsr['response_notes'])); ?>
    </div>
    <?php endif; ?>

    <?php if ($dsr['status'] == 'rejected' && $dsr['rejection_reason']): ?>
    <div class="alert alert-danger">
        <strong>Request Rejected - Reason:</strong><br>
        <?php echo nl2br(htmlspecialchars($dsr['rejection_reason'])); ?>
    </div>
    <p>
        <strong>Data Subject Rights:</strong> The data subject has been informed of their right to complain to
        the Postal and Telecommunications Regulatory Authority of Zimbabwe (POTRAZ) regarding this decision.
    </p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Legal Compliance Statement -->
<div class="section">
    <div class="section-title">LEGAL COMPLIANCE STATEMENT</div>
    <p>
        This DSR request has been processed in accordance with the Zimbabwe Cyber and Data Protection Act
        [Chapter 12:07], specifically:
    </p>
    <ul>
        <li><strong>Section 15:</strong> Right to Access</li>
        <li><strong>Section 16:</strong> Right to Rectification</li>
        <li><strong>Section 17:</strong> Right to Erasure</li>
        <li><strong>Section 18:</strong> Right to Data Portability</li>
        <li><strong>Section 19:</strong> Right to Restriction of Processing</li>
        <li><strong>Section 20:</strong> Right to Object</li>
        <li><strong>Section 21:</strong> Rights Related to Automated Decision-Making</li>
        <li><strong>Section 22:</strong> Right to Withdraw Consent</li>
    </ul>
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong> is committed to respecting and
        protecting the data subject rights of all individuals whose personal data we process.
    </p>
</div>

<!-- Metadata -->
<div class="section">
    <div class="section-title">DOCUMENT METADATA</div>
    <table class="info-table">
        <tr>
            <th>Created By:</th>
            <td><?php echo htmlspecialchars($dsr['creator_first'] . ' ' . $dsr['creator_last']); ?></td>
        </tr>
        <tr>
            <th>Created Date:</th>
            <td><?php echo date('d F Y, H:i', strtotime($dsr['created_at'])); ?></td>
        </tr>
        <tr>
            <th>Last Updated:</th>
            <td><?php echo date('d F Y, H:i', strtotime($dsr['updated_at'])); ?></td>
        </tr>
        <tr>
            <th>Report Generated:</th>
            <td><?php echo date('d F Y, H:i'); ?></td>
        </tr>
    </table>
</div>

<!-- Footer -->
<div class="footer">
    <p>
        <strong><?php echo htmlspecialchars($org['org_name']); ?></strong><br>
        DSR Request Report - DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?><br>
        Generated: <?php echo date('d F Y, H:i'); ?><br>
        <em>This document contains personal data and should be handled confidentially.</em>
    </p>
</div>

</body>
</html>
