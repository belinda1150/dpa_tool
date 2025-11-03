<?php
/**
 * Export Consent Register
 * Export consent details in CSV or PDF format
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$consent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'pdf';

// Fetch organization
$org_query = "SELECT * FROM organizations WHERE org_id = ?";
$org = db_fetch_one(db_query($org_query, [$org_id]));

// Single consent or full register
if ($consent_id > 0) {
    // Single consent
    $query = "SELECT c.*, pa.activity_name FROM consents c
              LEFT JOIN processing_activities pa ON c.ropa_id = pa.ropa_id
              WHERE c.consent_id = ? AND c.org_id = ?";
    $consents = [db_fetch_one(db_query($query, [$consent_id, $org_id]))];
    $title = "Consent Record - CNS-" . str_pad($consent_id, 5, '0', STR_PAD_LEFT);
} else {
    // Full register
    $query = "SELECT c.*, pa.activity_name FROM consents c
              LEFT JOIN processing_activities pa ON c.ropa_id = pa.ropa_id
              WHERE c.org_id = ? ORDER BY c.consent_date DESC";
    $consents = db_fetch_all(db_query($query, [$org_id]));
    $title = "Consent Register";
}

// CSV Export
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Consent_Register_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, ['CONSENT REGISTER']);
    fputcsv($output, ['Organization', $org['org_name']]);
    fputcsv($output, ['Generated', date('d M Y, H:i')]);
    fputcsv($output, []);

    fputcsv($output, ['Consent ID', 'Data Subject', 'Email', 'Purpose', 'Consent Date', 'Status', 'Method', 'Expiry Date', 'Withdrawal Date']);

    foreach ($consents as $c) {
        fputcsv($output, [
            'CNS-' . str_pad($c['consent_id'], 5, '0', STR_PAD_LEFT),
            $c['subject_name'],
            $c['subject_email'],
            $c['purpose'],
            date('d M Y', strtotime($c['consent_date'])),
            ucfirst($c['status']),
            $c['consent_method'],
            $c['expiry_date'] ? date('d M Y', strtotime($c['expiry_date'])) : 'N/A',
            $c['withdrawal_date'] ? date('d M Y', strtotime($c['withdrawal_date'])) : 'N/A'
        ]);
    }

    fclose($output);
    exit;
}

// PDF/HTML Output
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo $title; ?> - <?php echo htmlspecialchars($org['org_name']); ?></title>
    <link rel="stylesheet" href="assets/css/export-styles.css">
    <link rel="stylesheet" href="assets/css/module-styles.css">
</head>
<body>

<div class="no-print">
    <button onclick="window.print();" class="btn-print">Print / Save as PDF</button>
    <button onclick="history.back();" class="btn-back">Back</button>
</div>

<div class="header">
    <h1><?php echo strtoupper($title); ?></h1>
    <p><strong><?php echo htmlspecialchars($org['org_name']); ?></strong></p>
    <p>Generated: <?php echo date('d F Y, H:i'); ?></p>
</div>

<?php if ($consent_id > 0 && !empty($consents[0])): ?>
    <!-- Single Consent Details -->
    <?php $c = $consents[0]; ?>

    <div class="section">
        <div class="section-title">CONSENT DETAILS</div>
        <table class="info-table">
            <tr><th width="30%">Consent ID:</th><td><strong>CNS-<?php echo str_pad($c['consent_id'], 5, '0', STR_PAD_LEFT); ?></strong></td></tr>
            <tr><th>Data Subject:</th><td><?php echo htmlspecialchars($c['subject_name']); ?> (<?php echo htmlspecialchars($c['subject_email']); ?>)</td></tr>
            <tr><th>Purpose:</th><td><strong><?php echo htmlspecialchars($c['purpose']); ?></strong></td></tr>
            <tr><th>Status:</th><td><span class="badge badge-<?php echo $c['status'] == 'active' ? 'success' : 'warning'; ?>"><?php echo strtoupper($c['status']); ?></span></td></tr>
            <tr><th>Consent Date:</th><td><?php echo date('d F Y', strtotime($c['consent_date'])); ?></td></tr>
            <tr><th>Consent Method:</th><td><?php echo htmlspecialchars($c['consent_method']); ?></td></tr>
            <?php if ($c['expiry_date']): ?>
            <tr><th>Expiry Date:</th><td><?php echo date('d F Y', strtotime($c['expiry_date'])); ?></td></tr>
            <?php endif; ?>
        </table>

        <?php if ($c['purpose_description']): ?>
        <p><strong>Purpose Description:</strong></p>
        <p class="bg-box-light-simple"><?php echo nl2br(htmlspecialchars($c['purpose_description'])); ?></p>
        <?php endif; ?>

        <?php if ($c['consent_evidence']): ?>
        <p><strong>Consent Evidence:</strong></p>
        <p class="bg-box-light-simple"><?php echo nl2br(htmlspecialchars($c['consent_evidence'])); ?></p>
        <?php endif; ?>

        <?php if ($c['status'] == 'withdrawn'): ?>
        <p><strong>Withdrawal Date:</strong> <?php echo date('d F Y, H:i', strtotime($c['withdrawal_date'])); ?></p>
        <?php if ($c['withdrawal_reason']): ?>
        <p><strong>Withdrawal Reason:</strong></p>
        <p class="bg-box-warning-simple"><?php echo nl2br(htmlspecialchars($c['withdrawal_reason'])); ?></p>
        <?php endif; ?>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Consent Register Table -->
    <div class="section">
        <div class="section-title">CONSENT REGISTER</div>
        <table class="info-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data Subject</th>
                    <th>Purpose</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Method</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($consents as $c): ?>
                <tr>
                    <td>CNS-<?php echo str_pad($c['consent_id'], 5, '0', STR_PAD_LEFT); ?></td>
                    <td><?php echo htmlspecialchars($c['subject_name']); ?><br><small><?php echo htmlspecialchars($c['subject_email']); ?></small></td>
                    <td><?php echo htmlspecialchars($c['purpose']); ?></td>
                    <td><?php echo date('d M Y', strtotime($c['consent_date'])); ?></td>
                    <td><span class="badge badge-<?php echo $c['status'] == 'active' ? 'success' : 'warning'; ?>"><?php echo strtoupper($c['status']); ?></span></td>
                    <td><small><?php echo htmlspecialchars($c['consent_method']); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="section">
    <div class="section-title">LEGAL COMPLIANCE</div>
    <p><strong>CDPA Section 8:</strong> Processing of personal data requires a lawful basis, including consent. Consent must be freely given, specific, informed, and unambiguous.</p>
    <p><strong>CDPA Section 22:</strong> Data subjects have the right to withdraw consent at any time. Withdrawal does not affect lawfulness of processing before withdrawal.</p>
</div>

<div class="footer">
    <p><strong><?php echo htmlspecialchars($org['org_name']); ?></strong><br>Consent Management System<br>Generated: <?php echo date('d F Y, H:i'); ?></p>
</div>

</body>
</html>
