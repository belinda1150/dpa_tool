<?php
/**
 * Risk Register Export Page
 * Export risk register to CSV or printable PDF format
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get export format and risk ID (if exporting single risk)
$format = isset($_GET['format']) ? $_GET['format'] : 'html'; // 'html' or 'csv'
$risk_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch organization name
$org_query = "SELECT org_name FROM organizations WHERE org_id = ?";
$stmt = db_query($org_query, [$org_id]);
$org = db_fetch_one($stmt);
$org_name = $org['org_name'] ?? 'Organization';

// Build query based on whether exporting single risk or all risks
if ($risk_id > 0) {
    // Export single risk
    $query = "SELECT r.*,
              u.first_name as owner_first,
              u.last_name as owner_last,
              creator.first_name as creator_first,
              creator.last_name as creator_last,
              dept.dept_name,
              (r.likelihood * r.impact) as inherent_score,
              (r.residual_likelihood * r.residual_impact) as residual_score
              FROM risks r
              LEFT JOIN users u ON r.owner_id = u.user_id
              LEFT JOIN users creator ON r.created_by = creator.user_id
              LEFT JOIN departments dept ON r.dept_id = dept.dept_id
              WHERE r.risk_id = ? AND r.org_id = ?";

    $stmt = db_query($query, [$risk_id, $org_id]);
    $risks = db_fetch_all($stmt);

    if (empty($risks)) {
        set_flash_message('Risk not found.', 'danger');
        redirect('risk_list.php');
    }

    $export_title = 'Risk Report - RISK-' . str_pad($risk_id, 4, '0', STR_PAD_LEFT);
} else {
    // Export all risks
    $query = "SELECT r.*,
              u.first_name as owner_first,
              u.last_name as owner_last,
              creator.first_name as creator_first,
              creator.last_name as creator_last,
              dept.dept_name,
              (r.likelihood * r.impact) as inherent_score,
              (r.residual_likelihood * r.residual_impact) as residual_score
              FROM risks r
              LEFT JOIN users u ON r.owner_id = u.user_id
              LEFT JOIN users creator ON r.created_by = creator.user_id
              LEFT JOIN departments dept ON r.dept_id = dept.dept_id
              WHERE r.org_id = ?
              ORDER BY (r.residual_likelihood * r.residual_impact) DESC, r.created_at DESC";

    $stmt = db_query($query, [$org_id]);
    $risks = db_fetch_all($stmt);

    $export_title = 'Risk Register';
}

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Risk_Register_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header section
    fputcsv($output, ['RISK REGISTER - ' . $org_name]);
    fputcsv($output, ['Generated', date('d M Y H:i:s')]);
    fputcsv($output, []);

    // Column headers
    fputcsv($output, [
        'Risk ID',
        'Risk Title',
        'Risk Description',
        'Category',
        'Department',
        'Risk Owner',
        'Risk Source',
        'Likelihood',
        'Impact',
        'Inherent Score',
        'Treatment Strategy',
        'Treatment Description',
        'Residual Likelihood',
        'Residual Impact',
        'Residual Score',
        'Risk Level',
        'Status',
        'Review Date',
        'Created By',
        'Created On'
    ]);

    // Data rows
    foreach ($risks as $risk) {
        $inherent = $risk['inherent_score'];
        $residual = $risk['residual_score'] ?? $inherent;

        // Determine risk level
        if ($residual >= 15) {
            $risk_level = 'Critical';
        } elseif ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
            $risk_level = 'High';
        } elseif ($residual >= 3) {
            $risk_level = 'Medium';
        } else {
            $risk_level = 'Low';
        }

        fputcsv($output, [
            'RISK-' . str_pad($risk['risk_id'], 4, '0', STR_PAD_LEFT),
            $risk['risk_title'],
            $risk['risk_description'],
            $risk['risk_category'],
            $risk['dept_name'] ?? 'N/A',
            $risk['owner_first'] ? $risk['owner_first'] . ' ' . $risk['owner_last'] : 'Not Assigned',
            $risk['risk_source'] ?? 'N/A',
            $risk['likelihood'],
            $risk['impact'],
            $inherent,
            ucfirst($risk['treatment_strategy']),
            $risk['treatment_description'] ?? '',
            $risk['residual_likelihood'] ?? 'N/A',
            $risk['residual_impact'] ?? 'N/A',
            $residual,
            $risk_level,
            ucfirst($risk['status']),
            $risk['review_date'] ? date('d M Y', strtotime($risk['review_date'])) : 'Not Set',
            $risk['creator_first'] . ' ' . $risk['creator_last'],
            date('d M Y', strtotime($risk['created_at']))
        ]);
    }

    fputcsv($output, []);
    fputcsv($output, ['Report generated from Zimbabwe DPA Tool - CDPA Compliance System']);
    fputcsv($output, ['Generated on ' . date('d M Y H:i:s')]);

    fclose($output);
    exit;
}

// HTML/PDF Export (for printing)
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $export_title; ?> - <?php echo htmlspecialchars($org_name); ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 11pt;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            color: #2c3e50;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .risk-card {
            border: 2px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .risk-critical {
            border-left: 5px solid #d32f2f;
        }

        .risk-high {
            border-left: 5px solid #f57c00;
        }

        .risk-medium {
            border-left: 5px solid #fbc02d;
        }

        .risk-low {
            border-left: 5px solid #388e3c;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #ecf0f1;
            font-weight: bold;
            width: 30%;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }

        .badge-critical { background-color: #d32f2f; }
        .badge-high { background-color: #f57c00; }
        .badge-medium { background-color: #fbc02d; color: #333; }
        .badge-low { background-color: #388e3c; }
        .bg-secondary { background-color: #95a5a6; }
        .badge-open { background-color: #e74c3c; }
        .badge-mitigated { background-color: #f39c12; }
        .badge-accepted { background-color: #3498db; }
        .badge-closed { background-color: #27ae60; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            border: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-success {
            background-color: #27ae60;
            color: white;
        }

        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }

        .summary-table {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>

<!-- Print Buttons (hidden when printing) -->
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print();">
        Print / Save as PDF
    </button>
    <button class="btn btn-success" onclick="window.location.href='risk_export.php?<?php echo $risk_id > 0 ? 'id=' . $risk_id . '&' : ''; ?>format=csv';">
        Download CSV
    </button>
    <button class="btn btn-secondary" onclick="window.location.href='risk_list.php';">
        Back to Risk Register
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1><?php echo $export_title; ?></h1>
    <p><strong><?php echo htmlspecialchars($org_name); ?></strong></p>
    <p>Generated: <?php echo date('d M Y, H:i'); ?></p>
    <p><em>Compliance with Cyber and Data Protection Act [Chapter 12:07] - Zimbabwe</em></p>
</div>

<!-- Summary Statistics (only for full register export) -->
<?php if ($risk_id === 0): ?>
    <?php
    // Calculate statistics
    $total = count($risks);
    $critical = $high = $medium = $low = 0;
    $open = $mitigated = $accepted = $closed = 0;

    foreach ($risks as $risk) {
        $residual = $risk['residual_score'] ?? $risk['inherent_score'];

        if ($residual >= 15) $critical++;
        elseif ($residual >= RISK_ACCEPTABLE_THRESHOLD) $high++;
        elseif ($residual >= 3) $medium++;
        else $low++;

        if ($risk['status'] === 'open') $open++;
        elseif ($risk['status'] === 'mitigated') $mitigated++;
        elseif ($risk['status'] === 'accepted') $accepted++;
        elseif ($risk['status'] === 'closed') $closed++;
    }
    ?>
    <div style="margin-bottom: 30px;">
        <h2>Risk Summary</h2>
        <table class="summary-table">
            <tr>
                <th>Total Risks:</th>
                <td><?php echo $total; ?></td>
                <th>Critical Risks:</th>
                <td><span class="badge badge-critical"><?php echo $critical; ?></span></td>
            </tr>
            <tr>
                <th>High Risks:</th>
                <td><span class="badge badge-high"><?php echo $high; ?></span></td>
                <th>Medium Risks:</th>
                <td><span class="badge badge-medium"><?php echo $medium; ?></span></td>
            </tr>
            <tr>
                <th>Low Risks:</th>
                <td><span class="badge badge-low"><?php echo $low; ?></span></td>
                <th>Open Risks:</th>
                <td><span class="badge badge-open"><?php echo $open; ?></span></td>
            </tr>
        </table>
    </div>
<?php endif; ?>

<!-- Risk Details -->
<?php foreach ($risks as $risk): ?>
    <?php
    $inherent = $risk['inherent_score'];
    $residual = $risk['residual_score'] ?? $inherent;

    // Determine risk level and class
    if ($residual >= 15) {
        $risk_level = 'Critical';
        $risk_class = 'risk-critical';
        $badge_class = 'badge-critical';
    } elseif ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
        $risk_level = 'High';
        $risk_class = 'risk-high';
        $badge_class = 'badge-high';
    } elseif ($residual >= 3) {
        $risk_level = 'Medium';
        $risk_class = 'risk-medium';
        $badge_class = 'badge-medium';
    } else {
        $risk_level = 'Low';
        $risk_class = 'risk-low';
        $badge_class = 'badge-low';
    }

    $status_badges = [
        'open' => 'badge-open',
        'mitigated' => 'badge-mitigated',
        'accepted' => 'badge-accepted',
        'closed' => 'badge-closed'
    ];
    $status_class = $status_badges[$risk['status']] ?? 'bg-secondary';
    ?>

    <div class="risk-card <?php echo $risk_class; ?>">
        <h3 style="margin-top: 0;">
            RISK-<?php echo str_pad($risk['risk_id'], 4, '0', STR_PAD_LEFT); ?>:
            <?php echo htmlspecialchars($risk['risk_title']); ?>
            <span class="badge <?php echo $badge_class; ?>" style="float: right;">
                <?php echo $risk_level; ?> - <?php echo $residual; ?>/25
            </span>
        </h3>

        <table>
            <tr>
                <th>Risk Description:</th>
                <td><?php echo nl2br(htmlspecialchars($risk['risk_description'])); ?></td>
            </tr>
            <tr>
                <th>Category:</th>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($risk['risk_category']); ?></span></td>
            </tr>
            <tr>
                <th>Department:</th>
                <td><?php echo htmlspecialchars($risk['dept_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <th>Risk Owner:</th>
                <td>
                    <?php
                    if ($risk['owner_first']) {
                        echo htmlspecialchars($risk['owner_first'] . ' ' . $risk['owner_last']);
                    } else {
                        echo 'Not Assigned';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Risk Source:</th>
                <td><?php echo htmlspecialchars($risk['risk_source'] ?: 'N/A'); ?></td>
            </tr>
        </table>

        <h4>Risk Assessment</h4>
        <table>
            <tr>
                <th>Inherent Risk:</th>
                <td>
                    Likelihood: <?php echo $risk['likelihood']; ?>/5,
                    Impact: <?php echo $risk['impact']; ?>/5,
                    <strong>Score: <?php echo $inherent; ?>/25</strong>
                </td>
            </tr>
            <?php if ($risk['residual_likelihood'] && $risk['residual_impact']): ?>
                <tr>
                    <th>Residual Risk:</th>
                    <td>
                        Likelihood: <?php echo $risk['residual_likelihood']; ?>/5,
                        Impact: <?php echo $risk['residual_impact']; ?>/5,
                        <strong>Score: <?php echo $residual; ?>/25</strong>
                    </td>
                </tr>
                <tr>
                    <th>Risk Reduction:</th>
                    <td>
                        <?php
                        $reduction = $inherent - $residual;
                        if ($reduction > 0) {
                            $pct = round(($reduction / $inherent) * 100);
                            echo "<strong>$reduction points ($pct% reduction)</strong>";
                        } elseif ($reduction < 0) {
                            echo '<em>Residual risk higher than inherent (requires review)</em>';
                        } else {
                            echo '<em>No risk reduction</em>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <h4>Treatment Plan</h4>
        <table>
            <tr>
                <th>Strategy:</th>
                <td><strong><?php echo strtoupper($risk['treatment_strategy']); ?></strong></td>
            </tr>
            <tr>
                <th>Treatment Description:</th>
                <td><?php echo nl2br(htmlspecialchars($risk['treatment_description'] ?: 'N/A')); ?></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>Status:</th>
                <td><span class="badge <?php echo $status_class; ?>"><?php echo strtoupper($risk['status']); ?></span></td>
            </tr>
            <tr>
                <th>Review Date:</th>
                <td>
                    <?php
                    if ($risk['review_date']) {
                        echo date('d M Y', strtotime($risk['review_date']));
                        $review_date = strtotime($risk['review_date']);
                        if ($review_date < time()) {
                            echo ' <strong>(OVERDUE)</strong>';
                        }
                    } else {
                        echo 'Not Set';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Created By:</th>
                <td>
                    <?php echo htmlspecialchars($risk['creator_first'] . ' ' . $risk['creator_last']); ?>
                    on <?php echo date('d M Y', strtotime($risk['created_at'])); ?>
                </td>
            </tr>
        </table>
    </div>
<?php endforeach; ?>

<!-- Footer -->
<div class="footer">
    <p>
        <strong>Zimbabwe DPA Tool</strong> - Risk Register<br>
        Report Generated: <?php echo date('d M Y, H:i:s'); ?><br>
        <em>This document is confidential and intended for internal use only.</em>
    </p>
</div>

</body>
</html>
