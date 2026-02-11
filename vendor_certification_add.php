<?php
/**
 * DPA Tool - Add Vendor Certification
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

if (!$vendor_id) {
    set_flash_message('Invalid vendor ID.', 'error');
    redirect('vendor_list.php');
}

// Fetch vendor
$query = "SELECT * FROM vendors WHERE vendor_id = ? AND org_id = ?";
$stmt = db_query($query, [$vendor_id, $org_id]);
$vendor = db_fetch_one($stmt);

if (!$vendor) {
    set_flash_message('Vendor not found.', 'error');
    redirect('vendor_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $certification_type = sanitize_input($_POST['certification_type'] ?? '');
    $certification_name = sanitize_input($_POST['certification_name'] ?? '');
    $issuing_body = sanitize_input($_POST['issuing_body'] ?? '');
    $certificate_number = sanitize_input($_POST['certificate_number'] ?? '');
    $issue_date = !empty($_POST['issue_date']) ? $_POST['issue_date'] : null;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $notes = sanitize_input($_POST['notes'] ?? '');

    $errors = [];
    if (empty($certification_type)) $errors[] = 'Certification type is required.';
    if (empty($certification_name)) $errors[] = 'Certification name is required.';

    if (empty($errors)) {
        // Determine status
        $status = 'valid';
        if ($expiry_date && strtotime($expiry_date) < time()) {
            $status = 'expired';
        }

        $query = "INSERT INTO vendor_certifications (vendor_id, certification_type, certification_name,
                  issuing_body, certificate_number, issue_date, expiry_date, status, notes, verified_by, verified_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = db_query($query, [
            $vendor_id, $certification_type, $certification_name,
            $issuing_body, $certificate_number, $issue_date, $expiry_date, $status, $notes, $user_id
        ]);

        if ($stmt) {
            log_audit($org_id, $user_id, 'vendor_certification', db_insert_id(), 'create');
            set_flash_message('Certification added successfully!', 'success');
            redirect('vendor_view.php?id=' . $vendor_id);
        } else {
            $errors[] = 'Database error.';
        }
    }

    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
}

$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$cert_types = [
    'iso_27001' => 'ISO 27001 (Information Security)',
    'iso_27701' => 'ISO 27701 (Privacy)',
    'soc2_type1' => 'SOC 2 Type I',
    'soc2_type2' => 'SOC 2 Type II',
    'gdpr_compliant' => 'GDPR Compliance',
    'hipaa' => 'HIPAA Compliance',
    'pci_dss' => 'PCI DSS',
    'other' => 'Other'
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add Certification</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Add Vendor Certification</h2>
                        <h5><?php echo htmlspecialchars($vendor['vendor_name']); ?></h5>
                    </div>
                </div>
                <hr />

                <?php if (!empty($form_errors)): ?>
                <div class="alert alert-danger">
                    <ul><?php foreach ($form_errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card border-primary">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fa fa-certificate"></i> Certification Details</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Certification Type <span class="text-danger">*</span></label>
                                        <select name="certification_type" class="form-control" required>
                                            <option value="">-- Select Type --</option>
                                            <?php foreach ($cert_types as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo ($form_data['certification_type'] ?? '') === $val ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Certification Name <span class="text-danger">*</span></label>
                                        <input type="text" name="certification_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($form_data['certification_name'] ?? ''); ?>"
                                               placeholder="e.g., ISO/IEC 27001:2022">
                                    </div>

                                    <div class="form-group">
                                        <label>Issuing Body</label>
                                        <input type="text" name="issuing_body" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['issuing_body'] ?? ''); ?>"
                                               placeholder="e.g., BSI, TUV, etc.">
                                    </div>

                                    <div class="form-group">
                                        <label>Certificate Number</label>
                                        <input type="text" name="certificate_number" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['certificate_number'] ?? ''); ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Issue Date</label>
                                                <input type="date" name="issue_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['issue_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Expiry Date</label>
                                                <input type="date" name="expiry_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['expiry_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($form_data['notes'] ?? ''); ?></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Certification</button>
                                    <a href="vendor_view.php?id=<?php echo $vendor_id; ?>" class="btn btn-secondary">Cancel</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
    <script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
</body>
</html>
