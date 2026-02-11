<?php
/**
 * DPA Tool - CDPA Compliance Report Export
 * Comprehensive compliance report for POTRAZ submission
 * Standalone printable HTML (Print / Save as PDF)
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();

// Organization info
$stmt = db_query("SELECT * FROM organizations WHERE org_id = ?", [$org_id]);
$org = db_fetch_one($stmt);
$org_name = $org['org_name'] ?? 'Organization';

// DPO info
$stmt = db_query("SELECT u.first_name, u.last_name, u.email, u.phone
                  FROM users u
                  INNER JOIN roles r ON u.role_id = r.role_id
                  WHERE u.org_id = ? AND r.role_name = 'DPO' AND u.status = 'active'
                  LIMIT 1", [$org_id]);
$dpo = db_fetch_one($stmt);

// === CDPA Checklist Scores ===
$checklist_data = [];
$overall_pct = 0;
$table_check = $GLOBALS['dpa_db']->query("SHOW TABLES LIKE 'compliance_responses'");
if ($table_check->num_rows > 0) {
    require_once 'config/checklist_items.php';
    $stmt = db_query("SELECT item_key, response FROM compliance_responses WHERE org_id = ?", [$org_id]);
    $cr_rows = db_fetch_all($stmt);
    $cr_saved = [];
    foreach ($cr_rows as $r) $cr_saved[$r['item_key']] = $r['response'];

    $total_earned = 0;
    $total_applicable = 0;

    foreach ($CHECKLIST_ITEMS as $cat_key => $cat) {
        $cat_earned = 0;
        $cat_applicable = 0;
        $cat_yes = 0; $cat_no = 0; $cat_partial = 0; $cat_na = 0;

        foreach ($cat['items'] as $ik => $it) {
            $resp = $cr_saved[$ik] ?? null;
            if ($resp === 'yes') { $cat_earned += 1; $cat_applicable++; $cat_yes++; }
            elseif ($resp === 'partial') { $cat_earned += 0.5; $cat_applicable++; $cat_partial++; }
            elseif ($resp === 'no') { $cat_applicable++; $cat_no++; }
            elseif ($resp === 'na') { $cat_na++; }
        }

        $cat_pct = $cat_applicable > 0 ? round(($cat_earned / $cat_applicable) * 100, 1) : 0;
        $total_earned += $cat_earned;
        $total_applicable += $cat_applicable;

        $checklist_data[] = [
            'label' => $cat['label'],
            'section' => $cat['cdpa_section'],
            'pct' => $cat_pct,
            'yes' => $cat_yes,
            'no' => $cat_no,
            'partial' => $cat_partial,
            'na' => $cat_na,
            'total' => count($cat['items']),
        ];
    }
    $overall_pct = $total_applicable > 0 ? round(($total_earned / $total_applicable) * 100, 1) : 0;
}

// === Module Stats ===
$stmt = db_query("SELECT COUNT(*) as total, SUM(CASE WHEN status='validated' THEN 1 ELSE 0 END) as validated,
                  SUM(CASE WHEN has_special_categories=1 THEN 1 ELSE 0 END) as special
                  FROM processing_activities WHERE org_id = ?", [$org_id]);
$ropa = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total, SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved
                  FROM dpia WHERE org_id = ?", [$org_id]);
$dpia = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total, SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) as open_risks,
                  SUM(CASE WHEN (residual_likelihood*residual_impact) >= 15 THEN 1 ELSE 0 END) as critical
                  FROM risks WHERE org_id = ?", [$org_id]);
$risks = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total,
                  SUM(CASE WHEN notifiable=1 THEN 1 ELSE 0 END) as breaches,
                  SUM(CASE WHEN notifiable=1 AND notified_at IS NOT NULL AND TIMESTAMPDIFF(HOUR, detected_at, notified_at) <= 72 THEN 1 ELSE 0 END) as notified_on_time
                  FROM incidents WHERE org_id = ? AND YEAR(detected_at) = YEAR(CURDATE())", [$org_id]);
$incidents = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total,
                  SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                  SUM(CASE WHEN status != 'completed' AND NOW() > due_at THEN 1 ELSE 0 END) as overdue
                  FROM dsr_requests WHERE org_id = ?", [$org_id]);
$dsr = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total,
                  SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active
                  FROM vendors WHERE org_id = ?", [$org_id]);
$vendors = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total FROM cross_border_transfers WHERE org_id = ? AND status = 'active'", [$org_id]);
$transfers = db_fetch_one($stmt);

$stmt = db_query("SELECT COUNT(*) as total,
                  SUM(CASE WHEN status='granted' THEN 1 ELSE 0 END) as active
                  FROM consents WHERE org_id = ?", [$org_id]);
$consents = db_fetch_one($stmt);

// === CSV Export ===
$format = $_GET['format'] ?? 'html';
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="CDPA_Compliance_Report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($out, ['CDPA COMPLIANCE REPORT - ' . $org_name]);
    fputcsv($out, ['Generated', date('d M Y H:i:s')]);
    fputcsv($out, ['Overall Compliance Score', $overall_pct . '%']);
    fputcsv($out, []);

    fputcsv($out, ['CDPA CHECKLIST RESULTS']);
    fputcsv($out, ['Category', 'CDPA Section', 'Score %', 'Yes', 'Partial', 'No', 'N/A', 'Total Items']);
    foreach ($checklist_data as $cd) {
        fputcsv($out, [$cd['label'], $cd['section'], $cd['pct'] . '%', $cd['yes'], $cd['partial'], $cd['no'], $cd['na'], $cd['total']]);
    }
    fputcsv($out, []);

    fputcsv($out, ['MODULE STATISTICS']);
    fputcsv($out, ['Module', 'Total', 'Completed/Active', 'Notes']);
    fputcsv($out, ['ROPA', $ropa['total'], $ropa['validated'], $ropa['special'] . ' with special categories']);
    fputcsv($out, ['DPIA', $dpia['total'], $dpia['approved'], '']);
    fputcsv($out, ['Risks', $risks['total'], $risks['total'] - $risks['open_risks'] . ' closed', $risks['critical'] . ' critical']);
    fputcsv($out, ['Incidents (YTD)', $incidents['total'], $incidents['breaches'] . ' breaches', $incidents['notified_on_time'] . ' notified on time']);
    fputcsv($out, ['DSR Requests', $dsr['total'], $dsr['completed'] . ' completed', $dsr['overdue'] . ' overdue']);
    fputcsv($out, ['Vendors', $vendors['total'], $vendors['active'] . ' active', '']);
    fputcsv($out, ['Cross-Border', $transfers['total'], '', '']);
    fputcsv($out, ['Consents', $consents['total'], $consents['active'] . ' active', '']);
    fputcsv($out, []);
    fputcsv($out, ['Report generated from ' . APP_NAME . ' - CDPA Compliance System']);

    fclose($out);
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CDPA Compliance Report - <?php echo htmlspecialchars($org_name); ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { font-size: 11pt; }
            .page-break { page-break-before: always; }
        }
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; color: #333; }
        .header { text-align: center; border-bottom: 3px solid #1B1464; padding-bottom: 15px; margin-bottom: 25px; }
        .header h1 { margin: 0; color: #1B1464; font-size: 24px; }
        .header p { margin: 3px 0; color: #666; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 8px 10px; text-align: left; font-size: 13px; }
        table th { background-color: #f0f4f8; font-weight: bold; }
        .score-box { text-align: center; padding: 20px; margin-bottom: 25px; border: 2px solid #ddd; border-radius: 8px; }
        .score-box .score { font-size: 64px; font-weight: bold; }
        .score-box .label { font-size: 14px; color: #666; }
        .section-title { color: #1B1464; border-bottom: 2px solid #1B1464; padding-bottom: 5px; margin-top: 30px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; color: #fff; }
        .badge-green { background: #28a745; }
        .badge-yellow { background: #ffc107; color: #333; }
        .badge-red { background: #dc3545; }
        .badge-gray { background: #6c757d; }
        .footer { margin-top: 40px; padding-top: 15px; border-top: 2px solid #1B1464; text-align: center; font-size: 11px; color: #666; }
        .no-print { position: fixed; top: 10px; right: 10px; z-index: 1000; }
        .btn { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 4px; font-size: 14px; color: #fff; }
        .btn-primary { background: #1B1464; }
        .btn-success { background: #28a745; }
        .btn-secondary { background: #6c757d; }
        .progress-bar-inline { display: inline-block; width: 100px; height: 14px; background: #eee; border-radius: 3px; overflow: hidden; vertical-align: middle; }
        .progress-bar-inline .fill { height: 100%; border-radius: 3px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .info-grid div { padding: 8px 0; }
        .info-grid .label { color: #666; font-size: 12px; }
        .info-grid .value { font-weight: 600; }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn btn-primary" onclick="window.print();"><i class="fa fa-print"></i> Print / Save as PDF</button>
    <button class="btn btn-success" onclick="window.location.href='report_compliance_export.php?format=csv';"><i class="fa fa-download"></i> Download CSV</button>
    <button class="btn btn-secondary" onclick="window.location.href='reports_list.php';">Back to Reports</button>
</div>

<!-- Header -->
<div class="header">
    <h1>CDPA Compliance Report</h1>
    <p><strong><?php echo htmlspecialchars($org_name); ?></strong></p>
    <?php if (!empty($org['potraz_registration_ref'])): ?>
    <p>POTRAZ Registration: <?php echo htmlspecialchars($org['potraz_registration_ref']); ?></p>
    <?php endif; ?>
    <p>Report Date: <?php echo date('d M Y'); ?></p>
    <p><em>Cyber and Data Protection Act [Chapter 12:07] - Zimbabwe</em></p>
</div>

<!-- Organization Details -->
<h2 class="section-title">1. Organization Details</h2>
<div class="info-grid">
    <div><span class="label">Organization:</span><br><span class="value"><?php echo htmlspecialchars($org_name); ?></span></div>
    <div><span class="label">Type:</span><br><span class="value"><?php echo htmlspecialchars(ucfirst($org['org_type'] ?? 'Controller')); ?></span></div>
    <div><span class="label">Email:</span><br><span class="value"><?php echo htmlspecialchars($org['org_email'] ?? ''); ?></span></div>
    <div><span class="label">Phone:</span><br><span class="value"><?php echo htmlspecialchars($org['org_phone'] ?? ''); ?></span></div>
    <?php if ($dpo): ?>
    <div><span class="label">Data Protection Officer:</span><br><span class="value"><?php echo htmlspecialchars($dpo['first_name'] . ' ' . $dpo['last_name']); ?></span></div>
    <div><span class="label">DPO Contact:</span><br><span class="value"><?php echo htmlspecialchars($dpo['email']); ?></span></div>
    <?php endif; ?>
</div>

<!-- Overall Score -->
<h2 class="section-title">2. Overall Compliance Score</h2>
<div class="score-box">
    <?php
    if ($overall_pct >= 80) { $sc = '#28a745'; $sl = 'Strong Compliance'; }
    elseif ($overall_pct >= 50) { $sc = '#ffc107'; $sl = 'Needs Improvement'; }
    elseif ($overall_pct > 0) { $sc = '#dc3545'; $sl = 'Significant Gaps'; }
    else { $sc = '#6c757d'; $sl = 'Not Assessed'; }
    ?>
    <div class="score" style="color: <?php echo $sc; ?>;"><?php echo $overall_pct > 0 ? $overall_pct . '%' : 'N/A'; ?></div>
    <div class="label"><?php echo $sl; ?></div>
</div>

<!-- CDPA Checklist Results -->
<?php if (!empty($checklist_data)): ?>
<h2 class="section-title">3. CDPA Checklist Assessment</h2>
<table>
    <thead>
        <tr>
            <th>Category</th>
            <th>CDPA Section</th>
            <th>Score</th>
            <th>Compliant</th>
            <th>Partial</th>
            <th>Gaps</th>
            <th>N/A</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($checklist_data as $cd): ?>
        <tr>
            <td><?php echo htmlspecialchars($cd['label']); ?></td>
            <td><?php echo htmlspecialchars($cd['section']); ?></td>
            <td>
                <div class="progress-bar-inline">
                    <div class="fill" style="width:<?php echo $cd['pct']; ?>%; background:<?php echo $cd['pct'] >= 80 ? '#28a745' : ($cd['pct'] >= 50 ? '#ffc107' : '#dc3545'); ?>;"></div>
                </div>
                <strong><?php echo $cd['pct']; ?>%</strong>
            </td>
            <td><?php echo $cd['yes']; ?></td>
            <td><?php echo $cd['partial']; ?></td>
            <td><?php echo $cd['no']; ?></td>
            <td><?php echo $cd['na']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Module Summary -->
<h2 class="section-title"><?php echo !empty($checklist_data) ? '4' : '3'; ?>. Data Protection Programme Summary</h2>
<table>
    <thead>
        <tr><th>Module</th><th>Total</th><th>Status</th><th>Notes</th></tr>
    </thead>
    <tbody>
        <tr>
            <td>Record of Processing Activities (ROPA)</td>
            <td><?php echo $ropa['total']; ?></td>
            <td><span class="badge badge-<?php echo $ropa['validated'] > 0 ? 'green' : 'red'; ?>"><?php echo $ropa['validated']; ?> Validated</span></td>
            <td><?php echo $ropa['special']; ?> with special categories</td>
        </tr>
        <tr>
            <td>Data Protection Impact Assessments</td>
            <td><?php echo $dpia['total']; ?></td>
            <td><span class="badge badge-<?php echo $dpia['approved'] > 0 ? 'green' : 'gray'; ?>"><?php echo $dpia['approved']; ?> Approved</span></td>
            <td></td>
        </tr>
        <tr>
            <td>Risk Register</td>
            <td><?php echo $risks['total']; ?></td>
            <td><span class="badge badge-<?php echo $risks['open_risks'] == 0 ? 'green' : 'yellow'; ?>"><?php echo $risks['open_risks']; ?> Open</span></td>
            <td><?php echo $risks['critical']; ?> critical/high</td>
        </tr>
        <tr>
            <td>Incidents &amp; Breaches (YTD)</td>
            <td><?php echo $incidents['total']; ?></td>
            <td><span class="badge badge-<?php echo $incidents['breaches'] == 0 ? 'green' : 'yellow'; ?>"><?php echo $incidents['breaches']; ?> Breaches</span></td>
            <td><?php echo $incidents['notified_on_time']; ?> notified within 72hrs</td>
        </tr>
        <tr>
            <td>Data Subject Requests</td>
            <td><?php echo $dsr['total']; ?></td>
            <td><span class="badge badge-<?php echo $dsr['overdue'] == 0 ? 'green' : 'red'; ?>"><?php echo $dsr['completed']; ?> Completed</span></td>
            <td><?php echo $dsr['overdue']; ?> overdue</td>
        </tr>
        <tr>
            <td>Vendor / Processor Management</td>
            <td><?php echo $vendors['total']; ?></td>
            <td><span class="badge badge-green"><?php echo $vendors['active']; ?> Active</span></td>
            <td></td>
        </tr>
        <tr>
            <td>Cross-Border Transfers</td>
            <td><?php echo $transfers['total']; ?></td>
            <td><span class="badge badge-<?php echo $transfers['total'] > 0 ? 'green' : 'gray'; ?>">Active</span></td>
            <td></td>
        </tr>
        <tr>
            <td>Consent Records</td>
            <td><?php echo $consents['total']; ?></td>
            <td><span class="badge badge-green"><?php echo $consents['active']; ?> Active</span></td>
            <td></td>
        </tr>
    </tbody>
</table>

<!-- Declaration -->
<div class="page-break"></div>
<h2 class="section-title"><?php echo !empty($checklist_data) ? '5' : '4'; ?>. Declaration</h2>
<p>
    This report presents the data protection compliance status of <strong><?php echo htmlspecialchars($org_name); ?></strong>
    as of <strong><?php echo date('d M Y'); ?></strong>. The information herein has been compiled from the organization's
    data protection management system and represents the current state of compliance with the Cyber and Data Protection Act
    [Chapter 12:07] of Zimbabwe.
</p>
<br><br>
<table style="border: none; width: 60%;">
    <tr style="border: none;">
        <td style="border: none; padding-top: 40px; border-top: 1px solid #333; width: 50%;">
            <strong>Data Protection Officer</strong><br>
            <?php if ($dpo): ?>
            <?php echo htmlspecialchars($dpo['first_name'] . ' ' . $dpo['last_name']); ?>
            <?php endif; ?>
        </td>
        <td style="border: none; padding-top: 40px; border-top: 1px solid #333; width: 50%;">
            <strong>Date</strong><br>
            <?php echo date('d M Y'); ?>
        </td>
    </tr>
</table>

<!-- Footer -->
<div class="footer">
    <p>
        <strong><?php echo APP_NAME; ?></strong> - CDPA Compliance Report<br>
        Generated: <?php echo date('d M Y, H:i:s'); ?><br>
        <em>This document is confidential and intended for regulatory submission to POTRAZ.</em>
    </p>
</div>

</body>
</html>
