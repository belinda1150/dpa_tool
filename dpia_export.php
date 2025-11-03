<?php
/**
 * DPIA Export Page
 * Export DPIA report to CSV or printable PDF format
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get DPIA ID and format from URL
$dpia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'html'; // 'html' or 'csv'

if ($dpia_id <= 0) {
    set_flash_message('Invalid DPIA ID.', 'danger');
    redirect('dpia_list.php');
}

// Fetch DPIA with all related data
$query = "SELECT d.*,
          pa.activity_name,
          o.org_name,
          u.first_name as creator_first,
          u.last_name as creator_last,
          dept.dept_name,
          approver.first_name as approver_first,
          approver.last_name as approver_last
          FROM dpia d
          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
          LEFT JOIN organizations o ON d.org_id = o.org_id
          LEFT JOIN users u ON d.created_by = u.user_id
          LEFT JOIN departments dept ON pa.dept_id = dept.dept_id
          LEFT JOIN users approver ON d.approver_id = approver.user_id
          WHERE d.dpia_id = ? AND d.org_id = ?";

$stmt = db_query($query, [$dpia_id, $org_id]);
$dpia = db_fetch_one($stmt);

if (!$dpia) {
    set_flash_message('DPIA not found.', 'danger');
    redirect('dpia_list.php');
}

// Fetch all risks
$risks_query = "SELECT dr.*,
                u.first_name as responsible_first,
                u.last_name as responsible_last,
                (dr.likelihood * dr.impact) as inherent_score,
                (dr.residual_likelihood * dr.residual_impact) as residual_score
                FROM dpia_risks dr
                LEFT JOIN users u ON dr.responsible_user_id = u.user_id
                WHERE dr.dpia_id = ?
                ORDER BY (dr.likelihood * dr.impact) DESC";

$stmt = db_query($risks_query, [$dpia_id]);
$risks = db_fetch_all($stmt);

// Fetch step data
$steps_query = "SELECT * FROM dpia_steps WHERE dpia_id = ? ORDER BY step_number";
$stmt = db_query($steps_query, [$dpia_id]);
$steps = db_fetch_all($stmt);

$step_data = [];
foreach ($steps as $step) {
    $step_data[$step['step_number']] = json_decode($step['step_data'], true);
}

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="DPIA_Report_' . $dpia_id . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');

    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header section
    fputcsv($output, ['DATA PROTECTION IMPACT ASSESSMENT (DPIA) REPORT']);
    fputcsv($output, ['Organization', $dpia['org_name']]);
    fputcsv($output, ['Generated', date('d M Y H:i:s')]);
    fputcsv($output, []);

    // Basic Information
    fputcsv($output, ['BASIC INFORMATION']);
    fputcsv($output, ['DPIA ID', $dpia_id]);
    fputcsv($output, ['DPIA Title', $dpia['dpia_title']]);
    fputcsv($output, ['Processing Activity', $dpia['activity_name'] ?? 'N/A']);
    fputcsv($output, ['Department', $dpia['dept_name'] ?? 'N/A']);
    fputcsv($output, ['Description', $dpia['description']]);
    fputcsv($output, ['Screening Result', $dpia['screening_result']]);
    fputcsv($output, ['Screening Reason', $dpia['screening_reason']]);
    fputcsv($output, ['Status', $dpia['status']]);
    fputcsv($output, ['Residual Risk Score', $dpia['residual_risk_score'] . '/25']);
    fputcsv($output, ['Created By', $dpia['creator_first'] . ' ' . $dpia['creator_last']]);
    fputcsv($output, ['Created On', date('d M Y', strtotime($dpia['created_at']))]);

    if ($dpia['approver_first']) {
        fputcsv($output, ['Approved By', $dpia['approver_first'] . ' ' . $dpia['approver_last']]);
        fputcsv($output, ['Approval Date', date('d M Y', strtotime($dpia['approval_date']))]);
        fputcsv($output, ['Approval Notes', $dpia['approval_notes']]);
    }

    fputcsv($output, []);

    // Processing Description (Step 2)
    if (isset($step_data[2])) {
        fputcsv($output, ['PROCESSING DESCRIPTION']);
        fputcsv($output, ['Processing Description', $step_data[2]['processing_description'] ?? 'N/A']);
        fputcsv($output, ['Data Types', $step_data[2]['data_types'] ?? 'N/A']);
        fputcsv($output, ['Data Subjects', $step_data[2]['data_subjects'] ?? 'N/A']);
        fputcsv($output, ['Processing Purpose', $step_data[2]['processing_purpose'] ?? 'N/A']);
        fputcsv($output, []);
    }

    // Risks
    fputcsv($output, ['IDENTIFIED RISKS']);
    fputcsv($output, ['Risk #', 'Risk Title', 'Description', 'Category', 'Likelihood', 'Impact', 'Inherent Score', 'Mitigation Measures', 'Residual Likelihood', 'Residual Impact', 'Residual Score', 'Responsible Person']);

    foreach ($risks as $index => $risk) {
        fputcsv($output, [
            $index + 1,
            $risk['risk_title'],
            $risk['risk_description'],
            $risk['risk_category'],
            $risk['likelihood'] . '/5',
            $risk['impact'] . '/5',
            $risk['inherent_score'] . '/25',
            $risk['mitigation_measures'] ?? '',
            ($risk['residual_likelihood'] ?? 'N/A') . '/5',
            ($risk['residual_impact'] ?? 'N/A') . '/5',
            $risk['residual_score'] . '/25',
            $risk['responsible_first'] ? $risk['responsible_first'] . ' ' . $risk['responsible_last'] : 'N/A'
        ]);
    }

    fputcsv($output, []);

    // Consultation (Step 6)
    if (isset($step_data[6])) {
        fputcsv($output, ['CONSULTATION & SIGN-OFF']);
        fputcsv($output, ['DPO Consulted', $step_data[6]['dpo_consulted'] ?? 'N/A']);
        fputcsv($output, ['DPO Feedback', $step_data[6]['dpo_feedback'] ?? 'N/A']);
        fputcsv($output, ['Stakeholders Consulted', $step_data[6]['stakeholders_consulted'] ?? 'N/A']);
        fputcsv($output, ['Consultation Notes', $step_data[6]['consultation_notes'] ?? 'N/A']);
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
    <title>DPIA Report - <?php echo htmlspecialchars($dpia['dpia_title']); ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                font-size: 12pt;
            }
        }

        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
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

        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #34495e;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 16px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
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

        .risk-card {
            border: 2px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .risk-high {
            border-left: 5px solid #e74c3c;
        }

        .risk-medium {
            border-left: 5px solid #f39c12;
        }

        .risk-low {
            border-left: 5px solid #27ae60;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }

        .badge-danger { background-color: #e74c3c; }
        .badge-warning { background-color: #f39c12; }
        .badge-success { background-color: #27ae60; }
        .badge-info { background-color: #3498db; }
        .badge-default { background-color: #95a5a6; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #333;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .signature-box {
            margin-top: 40px;
            border: 1px solid #333;
            padding: 20px;
            page-break-inside: avoid;
        }

        .signature-line {
            margin-top: 40px;
            border-bottom: 1px solid #333;
            width: 300px;
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

        .btn-default {
            background-color: #95a5a6;
            color: white;
        }
    </style>
</head>
<body>

<!-- Print Buttons (hidden when printing) -->
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print();">
        Print / Save as PDF
    </button>
    <button class="btn btn-success" onclick="window.location.href='dpia_export.php?id=<?php echo $dpia_id; ?>&format=csv';">
        Download CSV
    </button>
    <button class="btn btn-default" onclick="window.location.href='dpia_view.php?id=<?php echo $dpia_id; ?>';">
        Back to DPIA
    </button>
</div>

<!-- Header -->
<div class="header">
    <h1>DATA PROTECTION IMPACT ASSESSMENT (DPIA)</h1>
    <p><strong><?php echo htmlspecialchars($dpia['org_name']); ?></strong></p>
    <p>Generated: <?php echo date('d M Y, H:i'); ?></p>
    <p><em>Compliance with Cyber and Data Protection Act [Chapter 12:07] - Zimbabwe</em></p>
</div>

<!-- Basic Information -->
<div class="section">
    <div class="section-title">1. BASIC INFORMATION</div>
    <table>
        <tr>
            <th>DPIA Reference:</th>
            <td>DPIA-<?php echo str_pad($dpia_id, 6, '0', STR_PAD_LEFT); ?></td>
        </tr>
        <tr>
            <th>DPIA Title:</th>
            <td><strong><?php echo htmlspecialchars($dpia['dpia_title']); ?></strong></td>
        </tr>
        <tr>
            <th>Processing Activity:</th>
            <td><?php echo htmlspecialchars($dpia['activity_name'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Department:</th>
            <td><?php echo htmlspecialchars($dpia['dept_name'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Screening Result:</th>
            <td>
                <?php if ($dpia['screening_result'] === 'required'): ?>
                    <span class="badge badge-danger">DPIA REQUIRED</span>
                <?php else: ?>
                    <span class="badge badge-info">DPIA RECOMMENDED</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Status:</th>
            <td>
                <?php
                $status_labels = [
                    'draft' => 'default',
                    'submitted' => 'info',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'rework' => 'warning'
                ];
                $status_class = $status_labels[$dpia['status']] ?? 'default';
                ?>
                <span class="badge badge-<?php echo $status_class; ?>"><?php echo strtoupper($dpia['status']); ?></span>
            </td>
        </tr>
        <tr>
            <th>Created By:</th>
            <td><?php echo htmlspecialchars($dpia['creator_first'] . ' ' . $dpia['creator_last']); ?></td>
        </tr>
        <tr>
            <th>Created On:</th>
            <td><?php echo date('d M Y', strtotime($dpia['created_at'])); ?></td>
        </tr>
        <tr>
            <th>Residual Risk Score:</th>
            <td>
                <?php
                $residual = $dpia['residual_risk_score'] ?? 0;
                if ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
                    echo '<span class="badge badge-danger">HIGH RISK: ' . $residual . '/25</span>';
                } elseif ($residual >= 3) {
                    echo '<span class="badge badge-warning">MEDIUM RISK: ' . $residual . '/25</span>';
                } else {
                    echo '<span class="badge badge-success">LOW RISK: ' . $residual . '/25</span>';
                }
                ?>
            </td>
        </tr>
    </table>
</div>

<!-- Description -->
<div class="section">
    <div class="section-title">2. DESCRIPTION & SCREENING JUSTIFICATION</div>
    <table>
        <tr>
            <th>Description:</th>
            <td><?php echo nl2br(htmlspecialchars($dpia['description'])); ?></td>
        </tr>
        <tr>
            <th>Screening Reason:</th>
            <td><?php echo nl2br(htmlspecialchars($dpia['screening_reason'])); ?></td>
        </tr>
    </table>
</div>

<!-- Processing Details (Step 2) -->
<?php if (isset($step_data[2])): ?>
<div class="section">
    <div class="section-title">3. PROCESSING DESCRIPTION</div>
    <table>
        <tr>
            <th>Processing Description:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[2]['processing_description'] ?? 'N/A')); ?></td>
        </tr>
        <tr>
            <th>Data Types Processed:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[2]['data_types'] ?? 'N/A')); ?></td>
        </tr>
        <tr>
            <th>Data Subjects:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[2]['data_subjects'] ?? 'N/A')); ?></td>
        </tr>
        <tr>
            <th>Processing Purpose:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[2]['processing_purpose'] ?? 'N/A')); ?></td>
        </tr>
    </table>
</div>
<?php endif; ?>

<!-- Risks & Mitigations -->
<div class="section">
    <div class="section-title">4. IDENTIFIED RISKS & MITIGATION MEASURES</div>

    <?php if (empty($risks)): ?>
        <p><em>No risks identified.</em></p>
    <?php else: ?>
        <?php foreach ($risks as $index => $risk): ?>
            <?php
            $inherent = $risk['inherent_score'];
            $residual = $risk['residual_score'];

            if ($residual >= RISK_ACCEPTABLE_THRESHOLD) {
                $risk_class = 'risk-high';
                $risk_label = 'HIGH RISK';
                $risk_badge = 'badge-danger';
            } elseif ($residual >= 3) {
                $risk_class = 'risk-medium';
                $risk_label = 'MEDIUM RISK';
                $risk_badge = 'badge-warning';
            } else {
                $risk_class = 'risk-low';
                $risk_label = 'LOW RISK';
                $risk_badge = 'badge-success';
            }
            ?>

            <div class="risk-card <?php echo $risk_class; ?>">
                <h4 style="margin-top: 0;">
                    Risk #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($risk['risk_title']); ?>
                    <span class="badge <?php echo $risk_badge; ?>" style="float: right;">
                        <?php echo $risk_label; ?> - <?php echo $residual; ?>/25
                    </span>
                </h4>

                <table>
                    <tr>
                        <th>Description:</th>
                        <td><?php echo nl2br(htmlspecialchars($risk['risk_description'])); ?></td>
                    </tr>
                    <tr>
                        <th>Category:</th>
                        <td><span class="badge badge-default"><?php echo htmlspecialchars($risk['risk_category']); ?></span></td>
                    </tr>
                    <tr>
                        <th>Inherent Risk (Before Mitigation):</th>
                        <td>
                            Likelihood: <?php echo $risk['likelihood']; ?>/5,
                            Impact: <?php echo $risk['impact']; ?>/5,
                            <strong>Score: <?php echo $inherent; ?>/25</strong>
                        </td>
                    </tr>
                    <tr>
                        <th>Mitigation Measures:</th>
                        <td><?php echo nl2br(htmlspecialchars($risk['mitigation_measures'] ?? 'N/A')); ?></td>
                    </tr>
                    <tr>
                        <th>Residual Risk (After Mitigation):</th>
                        <td>
                            Likelihood: <?php echo $risk['residual_likelihood'] ?? 'N/A'; ?>/5,
                            Impact: <?php echo $risk['residual_impact'] ?? 'N/A'; ?>/5,
                            <strong>Score: <?php echo $residual; ?>/25</strong>
                        </td>
                    </tr>
                    <?php if ($risk['responsible_first']): ?>
                    <tr>
                        <th>Responsible Person:</th>
                        <td><?php echo htmlspecialchars($risk['responsible_first'] . ' ' . $risk['responsible_last']); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Risk Matrix Summary -->
<div class="section">
    <div class="section-title">5. RISK MATRIX SUMMARY</div>
    <table>
        <thead>
            <tr>
                <th>Risk Category</th>
                <th>Count</th>
                <th>Avg Inherent Score</th>
                <th>Avg Residual Score</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Calculate risk statistics by category
            $risk_stats = [];
            foreach ($risks as $risk) {
                $category = $risk['risk_category'];
                if (!isset($risk_stats[$category])) {
                    $risk_stats[$category] = [
                        'count' => 0,
                        'total_inherent' => 0,
                        'total_residual' => 0
                    ];
                }
                $risk_stats[$category]['count']++;
                $risk_stats[$category]['total_inherent'] += $risk['inherent_score'];
                $risk_stats[$category]['total_residual'] += $risk['residual_score'];
            }

            foreach ($risk_stats as $category => $stats):
                $avg_inherent = round($stats['total_inherent'] / $stats['count'], 1);
                $avg_residual = round($stats['total_residual'] / $stats['count'], 1);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($category); ?></td>
                    <td><?php echo $stats['count']; ?></td>
                    <td><?php echo $avg_inherent; ?>/25</td>
                    <td><?php echo $avg_residual; ?>/25</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Consultation -->
<?php if (isset($step_data[6])): ?>
<div class="section">
    <div class="section-title">6. CONSULTATION & SIGN-OFF</div>
    <table>
        <tr>
            <th>DPO Consulted:</th>
            <td><?php echo htmlspecialchars($step_data[6]['dpo_consulted'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>DPO Feedback:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[6]['dpo_feedback'] ?? 'N/A')); ?></td>
        </tr>
        <tr>
            <th>Stakeholders Consulted:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[6]['stakeholders_consulted'] ?? 'N/A')); ?></td>
        </tr>
        <tr>
            <th>Consultation Notes:</th>
            <td><?php echo nl2br(htmlspecialchars($step_data[6]['consultation_notes'] ?? 'N/A')); ?></td>
        </tr>
    </table>
</div>
<?php endif; ?>

<!-- DPO Approval -->
<?php if ($dpia['approver_first']): ?>
<div class="section">
    <div class="section-title">7. DPO APPROVAL</div>
    <table>
        <tr>
            <th>Approved By:</th>
            <td><?php echo htmlspecialchars($dpia['approver_first'] . ' ' . $dpia['approver_last']); ?></td>
        </tr>
        <tr>
            <th>Approval Date:</th>
            <td><?php echo date('d M Y, H:i', strtotime($dpia['approval_date'])); ?></td>
        </tr>
        <tr>
            <th>Approval Notes:</th>
            <td><?php echo nl2br(htmlspecialchars($dpia['approval_notes'])); ?></td>
        </tr>
    </table>
</div>
<?php endif; ?>

<!-- Compliance Statement -->
<div class="section">
    <div class="section-title">8. CDPA COMPLIANCE STATEMENT</div>
    <p>
        This Data Protection Impact Assessment has been conducted in accordance with:
    </p>
    <ul>
        <li><strong>Cyber and Data Protection Act [Chapter 12:07]</strong> - Zimbabwe</li>
        <li><strong>Statutory Instrument 156 of 2022</strong> - Data Protection Regulations</li>
        <li><strong>POTRAZ Guidelines</strong> on Data Protection Impact Assessments</li>
    </ul>
    <p>
        <strong>Conclusion:</strong>
        <?php if ($residual >= RISK_ACCEPTABLE_THRESHOLD): ?>
            The residual risk score of <strong><?php echo $residual; ?>/25</strong> indicates <strong>HIGH RISK</strong>.
            Additional safeguards and DPO oversight are required before proceeding with this processing.
        <?php elseif ($residual >= 3): ?>
            The residual risk score of <strong><?php echo $residual; ?>/25</strong> indicates <strong>MEDIUM RISK</strong>.
            The proposed mitigation measures are adequate, but regular monitoring is required.
        <?php else: ?>
            The residual risk score of <strong><?php echo $residual; ?>/25</strong> indicates <strong>LOW RISK</strong>.
            The proposed mitigation measures adequately address identified risks.
        <?php endif; ?>
    </p>
</div>

<!-- Signature Boxes -->
<div class="signature-box">
    <h4>SIGN-OFF</h4>
    <p><strong>Prepared By:</strong></p>
    <p>Name: <?php echo htmlspecialchars($dpia['creator_first'] . ' ' . $dpia['creator_last']); ?></p>
    <p>Date: <?php echo date('d M Y', strtotime($dpia['created_at'])); ?></p>
    <div class="signature-line"></div>
    <p style="margin-top: 5px; font-size: 11px;"><em>Signature</em></p>

    <?php if ($dpia['approver_first']): ?>
        <p style="margin-top: 30px;"><strong>Approved By (DPO):</strong></p>
        <p>Name: <?php echo htmlspecialchars($dpia['approver_first'] . ' ' . $dpia['approver_last']); ?></p>
        <p>Date: <?php echo date('d M Y', strtotime($dpia['approval_date'])); ?></p>
        <div class="signature-line"></div>
        <p style="margin-top: 5px; font-size: 11px;"><em>Signature</em></p>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="footer">
    <p>
        <strong>Zimbabwe DPA Tool</strong> - Data Protection Compliance System<br>
        Report Generated: <?php echo date('d M Y, H:i:s'); ?><br>
        Document Reference: DPIA-<?php echo str_pad($dpia_id, 6, '0', STR_PAD_LEFT); ?><br>
        <em>This document is confidential and intended for internal use only.</em>
    </p>
</div>

</body>
</html>
