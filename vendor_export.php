<?php
/**
 * DPA Tool - Export Vendor Register
 * Export to CSV or printable HTML
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();
$format = isset($_GET['format']) ? sanitize_input($_GET['format']) : 'html';

// Fetch vendors with latest assessment
$query = "SELECT v.*,
          (SELECT vra.inherent_risk_score
           FROM vendor_risk_assessments vra
           WHERE vra.vendor_id = v.vendor_id AND vra.status IN ('completed', 'approved')
           ORDER BY vra.assessment_date DESC LIMIT 1) as latest_risk_score,
          (SELECT vra.inherent_risk_level
           FROM vendor_risk_assessments vra
           WHERE vra.vendor_id = v.vendor_id AND vra.status IN ('completed', 'approved')
           ORDER BY vra.assessment_date DESC LIMIT 1) as latest_risk_level,
          (SELECT COUNT(*) FROM vendor_certifications vc
           WHERE vc.vendor_id = v.vendor_id AND vc.status = 'valid') as valid_certs
          FROM vendors v
          WHERE v.org_id = ?
          ORDER BY v.vendor_name";

$stmt = db_query($query, [$org_id]);
$vendors = db_fetch_all($stmt);

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="vendor_register_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // Headers
    fputcsv($output, [
        'Vendor Ref', 'Vendor Name', 'Type', 'Country', 'Status', 'Criticality',
        'DPA Status', 'DPA Expiry', 'Contract End', 'Risk Score', 'Risk Level',
        'Valid Certifications', 'Next Review', 'Created At'
    ]);

    // Data rows
    foreach ($vendors as $vendor) {
        fputcsv($output, [
            $vendor['vendor_ref'],
            $vendor['vendor_name'],
            ucwords(str_replace('_', ' ', $vendor['vendor_type'])),
            $vendor['country'] ?? '',
            ucfirst(str_replace('_', ' ', $vendor['status'])),
            ucfirst($vendor['criticality']),
            ucfirst(str_replace('_', ' ', $vendor['dpa_status'])),
            $vendor['dpa_expiry_date'] ?? '',
            $vendor['contract_end_date'] ?? '',
            $vendor['latest_risk_score'] ? number_format($vendor['latest_risk_score'], 1) : 'N/A',
            $vendor['latest_risk_level'] ? ucfirst($vendor['latest_risk_level']) : 'Not Assessed',
            $vendor['valid_certs'],
            $vendor['next_review_date'] ?? '',
            $vendor['created_at']
        ]);
    }

    fclose($output);
    exit;
}

// HTML/Print Export
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Register - <?php echo date('Y-m-d'); ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        h2 { font-size: 14px; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #337ab7; color: white; font-size: 10px; }
        tr:nth-child(even) { background: #f9f9f9; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; color: white; }
        .badge-success { background: #5cb85c; }
        .badge-warning { background: #f0ad4e; }
        .badge-danger { background: #d9534f; }
        .badge-info { background: #5bc0de; }
        .badge-default { background: #999; }
        .summary { margin-bottom: 20px; }
        .summary-item { display: inline-block; margin-right: 30px; }
        .summary-value { font-size: 24px; font-weight: bold; color: #337ab7; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print();">Print</button>
        <a href="vendor_export.php?format=csv">Download CSV</a>
        <a href="vendor_list.php">Back to List</a>
    </div>

    <h1>Vendor Register</h1>
    <h2>Generated: <?php echo date('d M Y H:i'); ?></h2>

    <?php
    // Calculate summary stats
    $total = count($vendors);
    $active = 0;
    $high_risk = 0;
    $pending = 0;

    foreach ($vendors as $v) {
        if ($v['status'] === 'active') $active++;
        if ($v['status'] === 'pending_review') $pending++;
        if ($v['latest_risk_score'] >= VENDOR_HIGH_RISK_THRESHOLD) $high_risk++;
    }
    ?>

    <div class="summary">
        <div class="summary-item">
            <div class="summary-value"><?php echo $total; ?></div>
            <div>Total Vendors</div>
        </div>
        <div class="summary-item">
            <div class="summary-value"><?php echo $active; ?></div>
            <div>Active</div>
        </div>
        <div class="summary-item">
            <div class="summary-value"><?php echo $pending; ?></div>
            <div>Pending Review</div>
        </div>
        <div class="summary-item">
            <div class="summary-value" style="color: #d9534f;"><?php echo $high_risk; ?></div>
            <div>High Risk</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Ref</th>
                <th>Vendor Name</th>
                <th>Type</th>
                <th>Country</th>
                <th>Status</th>
                <th>DPA</th>
                <th>Risk</th>
                <th>Certs</th>
                <th>Next Review</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vendors as $vendor): ?>
            <tr>
                <td><?php echo htmlspecialchars($vendor['vendor_ref']); ?></td>
                <td><strong><?php echo htmlspecialchars($vendor['vendor_name']); ?></strong></td>
                <td><?php echo ucwords(str_replace('_', ' ', $vendor['vendor_type'])); ?></td>
                <td><?php echo htmlspecialchars($vendor['country'] ?? '-'); ?></td>
                <td>
                    <?php
                    $status_classes = [
                        'active' => 'success',
                        'pending_review' => 'warning',
                        'approved' => 'info',
                        'suspended' => 'danger',
                        'terminated' => 'default'
                    ];
                    $class = $status_classes[$vendor['status']] ?? 'default';
                    ?>
                    <span class="badge badge-<?php echo $class; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $vendor['status'])); ?>
                    </span>
                </td>
                <td>
                    <?php
                    $dpa_classes = [
                        'signed' => 'success',
                        'pending' => 'warning',
                        'expired' => 'danger',
                        'none' => 'default'
                    ];
                    $class = $dpa_classes[$vendor['dpa_status']] ?? 'default';
                    ?>
                    <span class="badge badge-<?php echo $class; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $vendor['dpa_status'])); ?>
                    </span>
                </td>
                <td>
                    <?php if ($vendor['latest_risk_level']): ?>
                        <?php
                        $risk_classes = ['critical' => 'danger', 'high' => 'warning', 'medium' => 'info', 'low' => 'success'];
                        $class = $risk_classes[$vendor['latest_risk_level']] ?? 'default';
                        ?>
                        <span class="badge badge-<?php echo $class; ?>">
                            <?php echo ucfirst($vendor['latest_risk_level']); ?>
                        </span>
                        <small>(<?php echo number_format($vendor['latest_risk_score'], 1); ?>)</small>
                    <?php else: ?>
                        <span class="badge badge-default">N/A</span>
                    <?php endif; ?>
                </td>
                <td><?php echo $vendor['valid_certs']; ?></td>
                <td>
                    <?php
                    if ($vendor['next_review_date']) {
                        $days = days_until($vendor['next_review_date']);
                        $style = $days < 0 ? 'color: #d9534f;' : ($days < 30 ? 'color: #f0ad4e;' : '');
                        echo '<span style="' . $style . '">' . format_date($vendor['next_review_date'], 'd M Y') . '</span>';
                        if ($days < 0) echo ' <small>(Overdue)</small>';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p style="margin-top: 20px; font-size: 10px; color: #999;">
        Report generated by <?php echo APP_NAME; ?> on <?php echo date('d M Y H:i:s'); ?>
    </p>
</body>
</html>
