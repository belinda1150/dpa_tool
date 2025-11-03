<?php
/**
 * DPA Tool - Export ROPA Register
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$org_id = get_current_org_id();
$export_type = isset($_GET['type']) ? $_GET['type'] : (isset($_GET['format']) ? $_GET['format'] : 'csv');
$ropa_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Get organization details
$query = "SELECT * FROM organizations WHERE org_id = ?";
$stmt = db_query($query, [$org_id]);
$org = db_fetch_one($stmt);

// Build query based on whether exporting single or all entries
if ($ropa_id) {
    $query = "SELECT pa.*, d.dept_name, p.purpose_name, lb.basis_name, rp.policy_name,
              u.first_name, u.last_name
              FROM processing_activities pa
              LEFT JOIN departments d ON pa.dept_id = d.dept_id
              LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
              LEFT JOIN lawful_basis lb ON pa.lawful_basis_id = lb.lawful_basis_id
              LEFT JOIN retention_policies rp ON pa.retention_id = rp.retention_id
              LEFT JOIN users u ON pa.created_by = u.user_id
              WHERE pa.ropa_id = ? AND pa.org_id = ?";
    $stmt = db_query($query, [$ropa_id, $org_id]);
} else {
    $query = "SELECT pa.*, d.dept_name, p.purpose_name, lb.basis_name, rp.policy_name,
              u.first_name, u.last_name
              FROM processing_activities pa
              LEFT JOIN departments d ON pa.dept_id = d.dept_id
              LEFT JOIN purposes p ON pa.purpose_id = p.purpose_id
              LEFT JOIN lawful_basis lb ON pa.lawful_basis_id = lb.lawful_basis_id
              LEFT JOIN retention_policies rp ON pa.retention_id = rp.retention_id
              LEFT JOIN users u ON pa.created_by = u.user_id
              WHERE pa.org_id = ? AND pa.status != 'archived'
              ORDER BY pa.created_at DESC";
    $stmt = db_query($query, [$org_id]);
}

$ropa_entries = db_fetch_all($stmt);

if (empty($ropa_entries)) {
    set_flash_message('No ROPA entries to export.', 'error');
    redirect('ropa_list.php');
}

// Export as CSV
if ($export_type == 'csv') {
    $filename = 'ROPA_Register_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Header row
    fputcsv($output, [
        'Activity Name',
        'Department',
        'Purpose',
        'Lawful Basis',
        'Data Source',
        'Estimated Subjects',
        'Special Categories',
        'Minors',
        'Cross-Border',
        'Retention Policy',
        'Security Measures',
        'Status',
        'Created By',
        'Created Date'
    ]);

    // Data rows
    foreach ($ropa_entries as $entry) {
        fputcsv($output, [
            $entry['activity_name'],
            $entry['dept_name'] ?? 'N/A',
            $entry['purpose_name'] ?? 'N/A',
            $entry['basis_name'] ?? 'N/A',
            ucfirst($entry['data_source']),
            $entry['estimated_data_subjects'],
            $entry['has_special_categories'] ? 'Yes' : 'No',
            $entry['has_minors'] ? 'Yes' : 'No',
            $entry['has_cross_border'] ? 'Yes' : 'No',
            $entry['policy_name'] ?? 'N/A',
            $entry['security_measures'] ?? 'N/A',
            ucfirst($entry['status']),
            $entry['first_name'] . ' ' . $entry['last_name'],
            date('d/m/Y', strtotime($entry['created_at']))
        ]);
    }

    fclose($output);
    exit;
}

// Export as HTML (can be printed as PDF)
else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>ROPA Register - <?php echo htmlspecialchars($org['org_name']); ?></title>
        <link rel="stylesheet" href="../assets/css/export-styles.css">
        <link rel="stylesheet" href="../assets/css/module-styles.css">
    </head>
    <body>
        <div class="no-print mb-20">
            <button onclick="window.print();" class="btn">Print / Save as PDF</button>
            <a href="ropa_list.php">Back to List</a>
        </div>

        <h1>Record of Processing Activities (ROPA) Register</h1>

        <div class="header-info">
            <strong>Organization:</strong> <?php echo htmlspecialchars($org['org_name']); ?><br>
            <strong>Report Generated:</strong> <?php echo date('d F Y H:i'); ?><br>
            <strong>Total Entries:</strong> <?php echo count($ropa_entries); ?><br>
            <strong>Legal Basis:</strong> Cyber and Data Protection Act [Chapter 12:07] s.24-25
        </div>

        <?php foreach ($ropa_entries as $entry): ?>
            <?php
            // Get additional details for this entry
            $query = "SELECT sc.category_name FROM ropa_subject_categories rsc
                      JOIN subject_categories sc ON rsc.subject_category_id = sc.subject_category_id
                      WHERE rsc.ropa_id = ?";
            $stmt = db_query($query, [$entry['ropa_id']]);
            $subjects = db_fetch_all($stmt);

            $query = "SELECT dc.category_name, dc.is_special_category FROM ropa_data_categories rdc
                      JOIN data_categories dc ON rdc.data_category_id = dc.data_category_id
                      WHERE rdc.ropa_id = ?";
            $stmt = db_query($query, [$entry['ropa_id']]);
            $data_cats = db_fetch_all($stmt);

            $query = "SELECT r.recipient_name, r.recipient_type FROM ropa_recipients rr
                      JOIN recipients r ON rr.recipient_id = r.recipient_id
                      WHERE rr.ropa_id = ?";
            $stmt = db_query($query, [$entry['ropa_id']]);
            $recipients = db_fetch_all($stmt);

            $query = "SELECT sl.location_name, sl.location_type FROM ropa_storage_locations rsl
                      JOIN storage_locations sl ON rsl.location_id = sl.location_id
                      WHERE rsl.ropa_id = ?";
            $stmt = db_query($query, [$entry['ropa_id']]);
            $locations = db_fetch_all($stmt);
            ?>

            <h2><?php echo htmlspecialchars($entry['activity_name']); ?></h2>

            <table>
                <tr>
                    <th width="25%">Controller/Department</th>
                    <td><?php echo htmlspecialchars($entry['dept_name'] ?? $org['org_name']); ?></td>
                </tr>
                <tr>
                    <th>Processing Purpose</th>
                    <td><?php echo htmlspecialchars($entry['purpose_name'] ?? 'Not specified'); ?></td>
                </tr>
                <tr>
                    <th>Lawful Basis</th>
                    <td><strong><?php echo htmlspecialchars($entry['basis_name'] ?? 'Not specified'); ?></strong></td>
                </tr>
                <tr>
                    <th>Categories of Data Subjects</th>
                    <td>
                        <?php if (!empty($subjects)): ?>
                            <?php foreach ($subjects as $subject): ?>
                                <span class="badge badge-info"><?php echo htmlspecialchars($subject['category_name']); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Categories of Personal Data</th>
                    <td>
                        <?php if (!empty($data_cats)): ?>
                            <?php foreach ($data_cats as $cat): ?>
                                <span class="badge <?php echo $cat['is_special_category'] ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Recipients/Third Parties</th>
                    <td>
                        <?php if (!empty($recipients)): ?>
                            <ul class="list-compact">
                                <?php foreach ($recipients as $recipient): ?>
                                    <li><?php echo htmlspecialchars($recipient['recipient_name']); ?>
                                        (<?php echo htmlspecialchars($recipient['recipient_type']); ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            None
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Storage Location</th>
                    <td>
                        <?php if (!empty($locations)): ?>
                            <?php foreach ($locations as $location): ?>
                                <?php echo htmlspecialchars($location['location_name']); ?>
                                (<?php echo htmlspecialchars($location['location_type']); ?>)<br>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Not specified
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Retention Period</th>
                    <td><?php echo htmlspecialchars($entry['policy_name'] ?? 'Not specified'); ?></td>
                </tr>
                <tr>
                    <th>Security Measures</th>
                    <td><?php echo htmlspecialchars($entry['security_measures'] ?? 'Not specified'); ?></td>
                </tr>
                <tr>
                    <th>Risk Indicators</th>
                    <td>
                        <?php if ($entry['has_special_categories']): ?>
                            <span class="badge badge-danger">Special Categories</span>
                        <?php endif; ?>
                        <?php if ($entry['has_minors']): ?>
                            <span class="badge badge-warning">Minors</span>
                        <?php endif; ?>
                        <?php if ($entry['has_cross_border']): ?>
                            <span class="badge badge-info">Cross-Border</span>
                        <?php endif; ?>
                        <?php if (!$entry['has_special_categories'] && !$entry['has_minors'] && !$entry['has_cross_border']): ?>
                            None
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div class="page-break"></div>

        <?php endforeach; ?>

        <div class="footer">
            <strong>Document Reference:</strong> ROPA-<?php echo date('Ymd-His'); ?><br>
            <strong>Generated by:</strong> <?php echo htmlspecialchars($_SESSION['dpa_user_name']); ?><br>
            <strong>Compliance Framework:</strong> Cyber and Data Protection Act [Chapter 12:07] and SI 156 of 2022<br>
            <strong>Regulatory Authority:</strong> POTRAZ (Postal and Telecommunications Regulatory Authority of Zimbabwe)
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
