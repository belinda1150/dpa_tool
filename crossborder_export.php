<?php
/**
 * Export Cross-Border Transfers Register
 * Generate PDF or CSV export of cross-border transfers
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get format (default to PDF)
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';
$single_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Fetch organization details
$org_query = "SELECT * FROM organizations WHERE org_id = ?";
$org_stmt = db_query($org_query, [$org_id]);
$org = db_fetch_one($org_stmt);

// Fetch transfers
if ($single_id) {
    $query = "SELECT cb.*,
              pa.activity_name,
              s.safeguard_name,
              CONCAT(u.first_name, ' ', u.last_name) as created_by_name
              FROM cross_border_transfers cb
              LEFT JOIN processing_activities pa ON cb.ropa_id = pa.ropa_id
              LEFT JOIN safeguards s ON cb.safeguard_id = s.safeguard_id
              LEFT JOIN users u ON cb.created_by = u.user_id
              WHERE cb.cb_id = ? AND cb.org_id = ?
              ORDER BY cb.created_at DESC";
    $stmt = db_query($query, [$single_id, $org_id]);
} else {
    $query = "SELECT cb.*,
              pa.activity_name,
              s.safeguard_name,
              CONCAT(u.first_name, ' ', u.last_name) as created_by_name
              FROM cross_border_transfers cb
              LEFT JOIN processing_activities pa ON cb.ropa_id = pa.ropa_id
              LEFT JOIN safeguards s ON cb.safeguard_id = s.safeguard_id
              LEFT JOIN users u ON cb.created_by = u.user_id
              WHERE cb.org_id = ?
              ORDER BY cb.created_at DESC";
    $stmt = db_query($query, [$org_id]);
}

$transfers = db_fetch_all($stmt);

if (empty($transfers)) {
    set_flash_message('No cross-border transfers to export.', 'warning');
    redirect('crossborder_list.php');
}

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cross_border_transfers_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // CSV Headers
    fputcsv($output, [
        'Transfer ID',
        'Destination Country',
        'High Risk',
        'Recipient Organisation',
        'Recipient Contact',
        'Data Type',
        'Transfer Frequency',
        'Safeguard',
        'POTRAZ Ref',
        'Status',
        'ROPA Activity',
        'Created By',
        'Created Date'
    ]);

    // Data rows
    foreach ($transfers as $transfer) {
        fputcsv($output, [
            $transfer['cb_id'],
            $transfer['destination_country'],
            $transfer['is_high_risk'] ? 'Yes' : 'No',
            $transfer['recipient_org'],
            $transfer['recipient_contact'] ?? '',
            $transfer['data_type_transferred'],
            ucfirst($transfer['transfer_frequency']),
            $transfer['safeguard_name'],
            $transfer['potraz_notification_ref'] ?? 'Not notified',
            strtoupper($transfer['status']),
            $transfer['activity_name'] ?? '',
            $transfer['created_by_name'],
            date('Y-m-d', strtotime($transfer['created_at']))
        ]);
    }

    fclose($output);
    exit;
}

// PDF Export (HTML to PDF)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cross-Border Transfers Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .org-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f5f5f5;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        td {
            border: 1px solid #ddd;
            padding: 6px;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .high-risk {
            background-color: #f44336;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
        }
        .status-badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            color: white;
        }
        .status-pending { background-color: #ff9800; }
        .status-submitted { background-color: #2196F3; }
        .status-approved { background-color: #4CAF50; }
        .status-blocked { background-color: #f44336; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CROSS-BORDER TRANSFERS REGISTER</h1>
        <p>Data Transfers Outside Zimbabwe - POTRAZ Compliance</p>
    </div>

    <div class="org-info">
        <strong>Organisation:</strong> <?php echo htmlspecialchars($org['org_name']); ?><br>
        <strong>POTRAZ Registration:</strong> <?php echo htmlspecialchars($org['potraz_registration_ref'] ?? 'Not registered'); ?><br>
        <strong>Report Generated:</strong> <?php echo date('d F Y H:i'); ?><br>
        <strong>Total Transfers:</strong> <?php echo count($transfers); ?>
    </div>

    <?php if ($single_id): ?>
        <!-- Single Transfer Detail -->
        <?php $transfer = $transfers[0]; ?>
        <h2>Transfer #<?php echo $transfer['cb_id']; ?> - POTRAZ Notification Template</h2>

        <table>
            <tr>
                <th width="30%">Field</th>
                <th width="70%">Details</th>
            </tr>
            <tr>
                <td><strong>Destination Country</strong></td>
                <td>
                    <?php echo htmlspecialchars($transfer['destination_country']); ?>
                    <?php if ($transfer['is_high_risk']): ?>
                        <span class="high-risk">HIGH RISK</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><strong>Recipient Organisation</strong></td>
                <td><?php echo htmlspecialchars($transfer['recipient_org']); ?></td>
            </tr>
            <tr>
                <td><strong>Recipient Contact</strong></td>
                <td><?php echo htmlspecialchars($transfer['recipient_contact'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td><strong>Data Type Transferred</strong></td>
                <td><?php echo nl2br(htmlspecialchars($transfer['data_type_transferred'])); ?></td>
            </tr>
            <tr>
                <td><strong>Transfer Frequency</strong></td>
                <td><?php echo ucfirst($transfer['transfer_frequency']); ?></td>
            </tr>
            <tr>
                <td><strong>Legal Safeguard</strong></td>
                <td><?php echo htmlspecialchars($transfer['safeguard_name']); ?></td>
            </tr>
            <tr>
                <td><strong>Safeguard Details</strong></td>
                <td><?php echo nl2br(htmlspecialchars($transfer['safeguard_details'] ?? 'N/A')); ?></td>
            </tr>
            <tr>
                <td><strong>Linked Processing Activity</strong></td>
                <td><?php echo htmlspecialchars($transfer['activity_name'] ?? 'Not linked'); ?></td>
            </tr>
            <tr>
                <td><strong>Status</strong></td>
                <td><span class="status-badge status-<?php echo $transfer['status']; ?>"><?php echo strtoupper($transfer['status']); ?></span></td>
            </tr>
            <tr>
                <td><strong>POTRAZ Notification Ref</strong></td>
                <td><?php echo htmlspecialchars($transfer['potraz_notification_ref'] ?? 'Pending notification'); ?></td>
            </tr>
            <tr>
                <td><strong>Date Created</strong></td>
                <td><?php echo date('d F Y', strtotime($transfer['created_at'])); ?></td>
            </tr>
            <tr>
                <td><strong>Created By</strong></td>
                <td><?php echo htmlspecialchars($transfer['created_by_name']); ?></td>
            </tr>
        </table>

        <div style="margin-top: 20px; padding: 15px; background-color: #ffffcc; border: 1px solid #ffcc00;">
            <strong>POTRAZ Notification Requirements:</strong>
            <ul>
                <li>Submit this notification within 30 days of initiating the transfer</li>
                <li>Ensure appropriate safeguards are documented and implemented</li>
                <li>Maintain records of data subject consent where applicable</li>
                <li>Update POTRAZ if transfer terms or recipients change</li>
            </ul>
        </div>

    <?php else: ?>
        <!-- All Transfers Register -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Destination</th>
                    <th>Recipient</th>
                    <th>Data Type</th>
                    <th>Safeguard</th>
                    <th>POTRAZ Ref</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transfers as $transfer): ?>
                    <tr>
                        <td><?php echo $transfer['cb_id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($transfer['destination_country']); ?>
                            <?php if ($transfer['is_high_risk']): ?>
                                <br><span class="high-risk">HIGH RISK</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($transfer['recipient_org']); ?></td>
                        <td><?php echo htmlspecialchars(substr($transfer['data_type_transferred'], 0, 50)) . (strlen($transfer['data_type_transferred']) > 50 ? '...' : ''); ?></td>
                        <td><?php echo htmlspecialchars($transfer['safeguard_name']); ?></td>
                        <td><?php echo htmlspecialchars($transfer['potraz_notification_ref'] ?? 'Pending'); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $transfer['status']; ?>">
                                <?php echo strtoupper($transfer['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d M Y', strtotime($transfer['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="footer">
        <p>This document is generated from <?php echo APP_NAME; ?> - Zimbabwe Data Protection Compliance Application</p>
        <p>Generated on <?php echo date('d F Y \a\t H:i'); ?> | © <?php echo date('Y'); ?> <?php echo htmlspecialchars($org['org_name']); ?></p>
        <p><strong>CONFIDENTIAL:</strong> This document contains sensitive information and should be handled according to your organisation's data protection policies.</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
