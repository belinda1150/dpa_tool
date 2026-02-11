<?php
/**
 * DPA Tool - Privacy Notice Generator
 * Auto-generates a privacy notice from ROPA, org, and system data
 * Standalone printable HTML
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

// Processing activities with purposes and lawful basis
$stmt = db_query("SELECT pa.activity_name, pa.description, pa.data_source,
                         pa.has_special_categories, pa.has_minors, pa.estimated_data_subjects,
                         p.purpose_name, p.purpose_description,
                         lb.basis_name, lb.cdpa_reference,
                         rp.policy_name as retention_name, rp.retention_period,
                         d.dept_name
                  FROM processing_activities pa
                  LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
                  LEFT JOIN lawful_basis lb ON pa.lawful_basis_id = lb.lawful_basis_id
                  LEFT JOIN retention_policies rp ON pa.retention_id = rp.retention_id
                  LEFT JOIN departments d ON pa.dept_id = d.dept_id
                  WHERE pa.org_id = ? AND pa.status != 'archived'
                  ORDER BY pa.activity_name", [$org_id]);
$activities = db_fetch_all($stmt);

// Cross-border transfers
$stmt = db_query("SELECT destination_country, transfer_mechanism, purpose
                  FROM cross_border_transfers
                  WHERE org_id = ? AND status = 'active'
                  ORDER BY destination_country", [$org_id]);
$transfers = db_fetch_all($stmt);

// Recipients
$stmt = db_query("SELECT recipient_name, recipient_type, country
                  FROM recipients
                  WHERE org_id = ?
                  ORDER BY recipient_name", [$org_id]);
$recipients = db_fetch_all($stmt);

// Unique lawful bases used
$lawful_bases_used = [];
foreach ($activities as $a) {
    if (!empty($a['basis_name']) && !in_array($a['basis_name'], $lawful_bases_used)) {
        $lawful_bases_used[] = $a['basis_name'];
    }
}

// Unique purposes
$purposes_used = [];
foreach ($activities as $a) {
    if (!empty($a['purpose_name']) && !in_array($a['purpose_name'], $purposes_used)) {
        $purposes_used[] = $a['purpose_name'];
    }
}

// Check for special categories
$has_special = false;
$has_minors = false;
foreach ($activities as $a) {
    if ($a['has_special_categories']) $has_special = true;
    if ($a['has_minors']) $has_minors = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Privacy Notice - <?php echo htmlspecialchars($org_name); ?></title>
    <style>
        @media print {
            .no-print { display: none; }
            body { font-size: 11pt; }
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 30px 40px; line-height: 1.8; color: #333; max-width: 900px; margin-left: auto; margin-right: auto; }
        h1 { color: #1B1464; font-size: 26px; border-bottom: 3px solid #1B1464; padding-bottom: 10px; }
        h2 { color: #1B1464; font-size: 18px; margin-top: 30px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        h3 { color: #333; font-size: 15px; margin-top: 20px; }
        p { margin: 8px 0; font-size: 14px; }
        ul { padding-left: 25px; }
        li { margin-bottom: 5px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        table th, table td { border: 1px solid #ddd; padding: 8px 10px; }
        table th { background: #f0f4f8; font-weight: 600; text-align: left; }
        .highlight { background: #fffde7; padding: 12px 15px; border-left: 4px solid #ffc107; margin: 15px 0; font-size: 13px; }
        .contact-box { background: #f8f9fa; padding: 15px 20px; border-radius: 6px; margin: 15px 0; }
        .footer { margin-top: 40px; padding-top: 15px; border-top: 2px solid #1B1464; font-size: 11px; color: #666; text-align: center; }
        .no-print { position: fixed; top: 10px; right: 10px; z-index: 1000; }
        .btn { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; border-radius: 4px; font-size: 14px; color: #fff; }
        .btn-primary { background: #1B1464; }
        .btn-secondary { background: #6c757d; }
        .version { font-size: 11px; color: #999; }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn btn-primary" onclick="window.print();">Print / Save as PDF</button>
    <button class="btn btn-secondary" onclick="window.location.href='reports_list.php';">Back to Reports</button>
</div>

<h1>Privacy Notice</h1>
<p class="version">Last updated: <?php echo date('d M Y'); ?> | <?php echo htmlspecialchars($org_name); ?></p>

<h2>1. Who We Are</h2>
<p>
    <strong><?php echo htmlspecialchars($org_name); ?></strong> ("we", "us", "our") is the data controller responsible
    for your personal data. We are registered as a <?php echo htmlspecialchars($org['org_type'] ?? 'data controller'); ?>
    under the Cyber and Data Protection Act [Chapter 12:07] of Zimbabwe.
</p>
<?php if (!empty($org['potraz_registration_ref'])): ?>
<p>Our POTRAZ registration reference is: <strong><?php echo htmlspecialchars($org['potraz_registration_ref']); ?></strong></p>
<?php endif; ?>

<?php if ($dpo): ?>
<h2>2. Data Protection Officer</h2>
<p>We have appointed a Data Protection Officer (DPO) who can be contacted regarding any data protection matters:</p>
<div class="contact-box">
    <strong><?php echo htmlspecialchars($dpo['first_name'] . ' ' . $dpo['last_name']); ?></strong><br>
    Email: <?php echo htmlspecialchars($dpo['email']); ?><br>
    <?php if (!empty($dpo['phone'])): ?>Phone: <?php echo htmlspecialchars($dpo['phone']); ?><br><?php endif; ?>
    <?php if (!empty($org['org_address'])): ?>Address: <?php echo htmlspecialchars($org['org_address']); ?><?php endif; ?>
</div>
<?php endif; ?>

<h2><?php echo $dpo ? '3' : '2'; ?>. What Personal Data We Collect and Why</h2>
<p>We process personal data for the following purposes:</p>

<?php if (!empty($activities)): ?>
<table>
    <thead>
        <tr>
            <th>Processing Activity</th>
            <th>Purpose</th>
            <th>Lawful Basis</th>
            <th>Data Source</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($activities as $a): ?>
        <tr>
            <td><?php echo htmlspecialchars($a['activity_name']); ?></td>
            <td><?php echo htmlspecialchars($a['purpose_name'] ?? 'See description'); ?></td>
            <td><?php echo htmlspecialchars($a['basis_name'] ?? 'Not specified'); ?>
                <?php if (!empty($a['cdpa_reference'])): ?>
                <br><small>(<?php echo htmlspecialchars($a['cdpa_reference']); ?>)</small>
                <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars(ucfirst($a['data_source'] ?? 'Internal')); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p><em>Processing activities are currently being documented.</em></p>
<?php endif; ?>

<?php if ($has_special): ?>
<div class="highlight">
    <strong>Special Category Data:</strong> We process special categories of personal data (such as health data, biometric data, or data revealing racial or ethnic origin) where we have obtained your explicit consent or where processing is required by law.
</div>
<?php endif; ?>

<?php if ($has_minors): ?>
<div class="highlight">
    <strong>Children's Data:</strong> Some of our processing activities involve data relating to minors. We implement additional safeguards in accordance with the CDPA to protect children's personal data.
</div>
<?php endif; ?>

<?php $section = $dpo ? 4 : 3; ?>
<h2><?php echo $section; ?>. Who We Share Your Data With</h2>
<?php if (!empty($recipients)): ?>
<p>We may share your personal data with the following categories of recipients:</p>
<table>
    <thead>
        <tr><th>Recipient</th><th>Type</th><th>Country</th></tr>
    </thead>
    <tbody>
        <?php foreach ($recipients as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['recipient_name']); ?></td>
            <td><?php echo htmlspecialchars(ucfirst($r['recipient_type'])); ?></td>
            <td><?php echo htmlspecialchars($r['country'] ?? 'Zimbabwe'); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>We do not routinely share your personal data with third parties. Where sharing is necessary, appropriate data processing agreements are in place.</p>
<?php endif; ?>

<?php $section++; ?>
<?php if (!empty($transfers)): ?>
<h2><?php echo $section; ?>. International Transfers</h2>
<p>We transfer personal data to the following countries, with appropriate safeguards in place as required by the CDPA (s.28):</p>
<table>
    <thead>
        <tr><th>Destination Country</th><th>Safeguard Mechanism</th><th>Purpose</th></tr>
    </thead>
    <tbody>
        <?php foreach ($transfers as $t): ?>
        <tr>
            <td><?php echo htmlspecialchars($t['destination_country']); ?></td>
            <td><?php echo htmlspecialchars($t['transfer_mechanism'] ?? 'Contractual clauses'); ?></td>
            <td><?php echo htmlspecialchars($t['purpose'] ?? ''); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php $section++; ?>
<?php endif; ?>

<h2><?php echo $section; ?>. How Long We Keep Your Data</h2>
<?php
$retention_data = [];
foreach ($activities as $a) {
    if (!empty($a['retention_name'])) {
        $key = $a['retention_name'];
        if (!isset($retention_data[$key])) {
            $retention_data[$key] = ['period' => $a['retention_period'], 'activities' => []];
        }
        $retention_data[$key]['activities'][] = $a['activity_name'];
    }
}
?>
<?php if (!empty($retention_data)): ?>
<p>We retain personal data only for as long as necessary for the purposes for which it was collected:</p>
<table>
    <thead>
        <tr><th>Retention Policy</th><th>Period</th><th>Applies To</th></tr>
    </thead>
    <tbody>
        <?php foreach ($retention_data as $name => $rd): ?>
        <tr>
            <td><?php echo htmlspecialchars($name); ?></td>
            <td><?php echo htmlspecialchars($rd['period']); ?></td>
            <td><?php echo htmlspecialchars(implode(', ', $rd['activities'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p>We retain your personal data only for as long as is necessary to fulfil the purposes for which it was collected, after which it is securely deleted or anonymized in accordance with our retention schedule.</p>
<?php endif; ?>

<?php $section++; ?>
<h2><?php echo $section; ?>. Your Rights</h2>
<p>Under the Cyber and Data Protection Act (CDPA), you have the following rights:</p>
<ul>
    <li><strong>Right of Access</strong> (s.15) — You can request a copy of the personal data we hold about you.</li>
    <li><strong>Right to Rectification</strong> (s.16) — You can ask us to correct inaccurate or incomplete data.</li>
    <li><strong>Right to Erasure</strong> (s.17) — You can request deletion of your personal data in certain circumstances.</li>
    <li><strong>Right to Restrict Processing</strong> (s.18) — You can ask us to limit how we use your data.</li>
    <li><strong>Right to Data Portability</strong> (s.19) — You can request your data in a structured, machine-readable format.</li>
    <li><strong>Right to Object</strong> (s.20) — You can object to processing based on legitimate interests or direct marketing.</li>
    <li><strong>Rights related to Automated Decision-Making</strong> (s.21) — You have the right not to be subject to decisions based solely on automated processing.</li>
</ul>
<p>
    To exercise any of these rights, please contact our Data Protection Officer using the details provided above.
    We will respond to your request within 30 days as required by the CDPA.
</p>

<?php $section++; ?>
<h2><?php echo $section; ?>. How to Complain</h2>
<p>
    If you are not satisfied with how we handle your personal data, you have the right to lodge a complaint with the
    Postal and Telecommunications Regulatory Authority of Zimbabwe (POTRAZ):
</p>
<div class="contact-box">
    <strong>POTRAZ</strong><br>
    Website: www.potraz.gov.zw<br>
    Email: info@potraz.gov.zw
</div>

<?php $section++; ?>
<h2><?php echo $section; ?>. Changes to This Notice</h2>
<p>
    We may update this privacy notice from time to time. Any changes will be communicated through appropriate channels.
    This notice was last updated on <strong><?php echo date('d M Y'); ?></strong>.
</p>

<div class="footer">
    <p>
        Privacy Notice generated by <strong><?php echo APP_NAME; ?></strong><br>
        <?php echo htmlspecialchars($org_name); ?> | <?php echo date('d M Y'); ?><br>
        <em>This notice should be reviewed and approved by the Data Protection Officer before publication.</em>
    </p>
</div>

</body>
</html>
