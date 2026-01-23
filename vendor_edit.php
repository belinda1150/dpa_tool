<?php
/**
 * DPA Tool - Edit Vendor
 * Update existing vendor information
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get vendor ID
$vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$vendor_id) {
    set_flash_message('Invalid vendor ID.', 'error');
    redirect('vendor_list.php');
}

// Fetch existing vendor
$query = "SELECT * FROM vendors WHERE vendor_id = ? AND org_id = ?";
$stmt = db_query($query, [$vendor_id, $org_id]);
$vendor = db_fetch_one($stmt);

if (!$vendor) {
    set_flash_message('Vendor not found.', 'error');
    redirect('vendor_list.php');
}

// Fetch existing ROPA links
$ropa_link_query = "SELECT ropa_id FROM vendor_ropa WHERE vendor_id = ?";
$stmt = db_query($ropa_link_query, [$vendor_id]);
$existing_ropa = array_column(db_fetch_all($stmt), 'ropa_id');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $vendor_name = sanitize_input($_POST['vendor_name'] ?? '');
    $vendor_type = sanitize_input($_POST['vendor_type'] ?? 'processor');
    $description = sanitize_input($_POST['description'] ?? '');
    $website = sanitize_input($_POST['website'] ?? '');
    $country = sanitize_input($_POST['country'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');

    // Contact information
    $primary_contact_name = sanitize_input($_POST['primary_contact_name'] ?? '');
    $primary_contact_email = sanitize_input($_POST['primary_contact_email'] ?? '');
    $primary_contact_phone = sanitize_input($_POST['primary_contact_phone'] ?? '');
    $dpo_name = sanitize_input($_POST['dpo_name'] ?? '');
    $dpo_email = sanitize_input($_POST['dpo_email'] ?? '');

    // DPA/Contract information
    $dpa_status = sanitize_input($_POST['dpa_status'] ?? 'none');
    $dpa_signed_date = !empty($_POST['dpa_signed_date']) ? $_POST['dpa_signed_date'] : null;
    $dpa_expiry_date = !empty($_POST['dpa_expiry_date']) ? $_POST['dpa_expiry_date'] : null;
    $contract_ref = sanitize_input($_POST['contract_ref'] ?? '');
    $contract_start_date = !empty($_POST['contract_start_date']) ? $_POST['contract_start_date'] : null;
    $contract_end_date = !empty($_POST['contract_end_date']) ? $_POST['contract_end_date'] : null;

    // Data processing details
    $data_categories_processed = sanitize_input($_POST['data_categories_processed'] ?? '');
    $processing_purpose = sanitize_input($_POST['processing_purpose'] ?? '');
    $data_volume = sanitize_input($_POST['data_volume'] ?? 'medium');
    $access_type = sanitize_input($_POST['access_type'] ?? 'process');
    $has_subprocessors = isset($_POST['has_subprocessors']) ? 1 : 0;
    $subprocessor_details = sanitize_input($_POST['subprocessor_details'] ?? '');
    $transfers_data_internationally = isset($_POST['transfers_data_internationally']) ? 1 : 0;
    $data_transfer_countries = sanitize_input($_POST['data_transfer_countries'] ?? '');

    // Status and review
    $criticality = sanitize_input($_POST['criticality'] ?? 'medium');
    $review_frequency_months = intval($_POST['review_frequency_months'] ?? 12);
    $next_review_date = !empty($_POST['next_review_date']) ? $_POST['next_review_date'] : null;
    $notes = sanitize_input($_POST['notes'] ?? '');

    // Validation
    $errors = [];

    if (empty($vendor_name)) {
        $errors[] = 'Vendor name is required.';
    }

    // Check for duplicate vendor name (exclude current vendor)
    $check_query = "SELECT vendor_id FROM vendors WHERE org_id = ? AND vendor_name = ? AND vendor_id != ?";
    $check_stmt = db_query($check_query, [$org_id, $vendor_name, $vendor_id]);
    if (db_fetch_one($check_stmt)) {
        $errors[] = 'A vendor with this name already exists.';
    }

    // Validate email addresses
    if (!empty($primary_contact_email) && !filter_var($primary_contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid primary contact email address.';
    }
    if (!empty($dpo_email) && !filter_var($dpo_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid DPO email address.';
    }

    // Validate website URL
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid website URL format.';
    }

    // DPA date validation
    if ($dpa_status === 'signed' && empty($dpa_signed_date)) {
        $errors[] = 'DPA signed date is required when status is "Signed".';
    }

    if (empty($errors)) {
        // Update vendor
        $query = "UPDATE vendors SET
                    vendor_name = ?, vendor_type = ?, description = ?, website = ?, country = ?, address = ?,
                    primary_contact_name = ?, primary_contact_email = ?, primary_contact_phone = ?,
                    dpo_name = ?, dpo_email = ?,
                    dpa_status = ?, dpa_signed_date = ?, dpa_expiry_date = ?,
                    contract_ref = ?, contract_start_date = ?, contract_end_date = ?,
                    data_categories_processed = ?, processing_purpose = ?, data_volume = ?, access_type = ?,
                    has_subprocessors = ?, subprocessor_details = ?,
                    transfers_data_internationally = ?, data_transfer_countries = ?,
                    criticality = ?, next_review_date = ?, review_frequency_months = ?, notes = ?
                  WHERE vendor_id = ? AND org_id = ?";

        $stmt = db_query($query, [
            $vendor_name, $vendor_type, $description, $website, $country, $address,
            $primary_contact_name, $primary_contact_email, $primary_contact_phone,
            $dpo_name, $dpo_email,
            $dpa_status, $dpa_signed_date, $dpa_expiry_date,
            $contract_ref, $contract_start_date, $contract_end_date,
            $data_categories_processed, $processing_purpose, $data_volume, $access_type,
            $has_subprocessors, $subprocessor_details,
            $transfers_data_internationally, $data_transfer_countries,
            $criticality, $next_review_date, $review_frequency_months, $notes,
            $vendor_id, $org_id
        ]);

        if ($stmt) {
            // Update ROPA links - delete old and insert new
            db_query("DELETE FROM vendor_ropa WHERE vendor_id = ?", [$vendor_id]);

            if (!empty($_POST['linked_ropa'])) {
                foreach ($_POST['linked_ropa'] as $ropa_id) {
                    $ropa_query = "INSERT INTO vendor_ropa (vendor_id, ropa_id, relationship_type) VALUES (?, ?, 'processor')";
                    db_query($ropa_query, [$vendor_id, intval($ropa_id)]);
                }
            }

            // Log audit
            log_audit($org_id, $user_id, 'vendor', $vendor_id, 'update');

            set_flash_message('Vendor updated successfully!', 'success');
            redirect('vendor_view.php?id=' . $vendor_id);
        } else {
            $errors[] = 'Database error: Unable to update vendor.';
        }
    }

    // Store errors in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
    }
}

// Fetch ROPA entries for linking
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? AND status != 'archived' ORDER BY activity_name";
$stmt = db_query($ropa_query, [$org_id]);
$ropa_entries = db_fetch_all($stmt);

// Get form errors
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Use POST data if available, otherwise use vendor data
$form_data = $_POST ?: $vendor;

// Vendor types
$vendor_types = [
    'processor' => 'Data Processor',
    'supplier' => 'Supplier',
    'contractor' => 'Contractor',
    'cloud_provider' => 'Cloud Provider',
    'consultant' => 'Consultant',
    'other' => 'Other'
];

// DPA statuses
$dpa_statuses = [
    'none' => 'No DPA Required',
    'pending' => 'DPA Pending',
    'under_review' => 'Under Review',
    'signed' => 'Signed',
    'expired' => 'Expired'
];

// Data volume options
$data_volumes = [
    'low' => 'Low (< 1,000 records)',
    'medium' => 'Medium (1,000 - 10,000 records)',
    'high' => 'High (10,000 - 100,000 records)',
    'very_high' => 'Very High (> 100,000 records)'
];

// Access type options
$access_types = [
    'no_access' => 'No Access to Personal Data',
    'view_only' => 'View Only',
    'process' => 'Process Data',
    'store' => 'Store Data',
    'full_access' => 'Full Access (Process & Store)'
];

// Criticality options
$criticality_options = [
    'low' => 'Low - Non-critical vendor',
    'medium' => 'Medium - Important vendor',
    'high' => 'High - Critical for operations',
    'critical' => 'Critical - Essential/irreplaceable'
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Edit Vendor</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
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
                        <h2>Edit Vendor</h2>
                        <h5><?php echo htmlspecialchars($vendor['vendor_name']); ?> (<?php echo htmlspecialchars($vendor['vendor_ref']); ?>)</h5>
                    </div>
                </div>
                <hr />

                <!-- Form Errors -->
                <?php if (!empty($form_errors)): ?>
                    <div class="alert alert-danger alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                        <strong>Please correct the following errors:</strong>
                        <ul>
                            <?php foreach ($form_errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Edit Vendor Form -->
                <form method="POST" action="vendor_edit.php?id=<?php echo $vendor_id; ?>" id="vendorForm">
                    <div class="row">
                        <!-- Left Column -->
                        <div class="col-md-6">

                            <!-- Basic Information -->
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-building"></i> Vendor Information</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label for="vendor_name">Vendor Name <span class="text-danger">*</span></label>
                                        <input type="text" name="vendor_name" id="vendor_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($form_data['vendor_name'] ?? ''); ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="vendor_type">Vendor Type</label>
                                                <select name="vendor_type" id="vendor_type" class="form-control">
                                                    <?php foreach ($vendor_types as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['vendor_type'] ?? '') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="criticality">Criticality</label>
                                                <select name="criticality" id="criticality" class="form-control">
                                                    <?php foreach ($criticality_options as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['criticality'] ?? '') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="country">Country</label>
                                                <input type="text" name="country" id="country" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['country'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="website">Website</label>
                                                <input type="url" name="website" id="website" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['website'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-user"></i> Contact Information</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label for="primary_contact_name">Primary Contact Name</label>
                                        <input type="text" name="primary_contact_name" id="primary_contact_name" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['primary_contact_name'] ?? ''); ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="primary_contact_email">Email</label>
                                                <input type="email" name="primary_contact_email" id="primary_contact_email" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['primary_contact_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="primary_contact_phone">Phone</label>
                                                <input type="text" name="primary_contact_phone" id="primary_contact_phone" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['primary_contact_phone'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5><strong>Vendor DPO Contact</strong></h5>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="dpo_name">DPO Name</label>
                                                <input type="text" name="dpo_name" id="dpo_name" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['dpo_name'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="dpo_email">DPO Email</label>
                                                <input type="email" name="dpo_email" id="dpo_email" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['dpo_email'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">

                            <!-- DPA/Contract Information -->
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-file-text"></i> DPA & Contract</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="dpa_status">DPA Status</label>
                                                <select name="dpa_status" id="dpa_status" class="form-control">
                                                    <?php foreach ($dpa_statuses as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['dpa_status'] ?? '') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="contract_ref">Contract Reference</label>
                                                <input type="text" name="contract_ref" id="contract_ref" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['contract_ref'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row" id="dpa_dates">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="dpa_signed_date">DPA Signed Date</label>
                                                <input type="date" name="dpa_signed_date" id="dpa_signed_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['dpa_signed_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="dpa_expiry_date">DPA Expiry Date</label>
                                                <input type="date" name="dpa_expiry_date" id="dpa_expiry_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['dpa_expiry_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="contract_start_date">Contract Start</label>
                                                <input type="date" name="contract_start_date" id="contract_start_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['contract_start_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="contract_end_date">Contract End</label>
                                                <input type="date" name="contract_end_date" id="contract_end_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['contract_end_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="review_frequency_months">Review Frequency</label>
                                                <select name="review_frequency_months" id="review_frequency_months" class="form-control">
                                                    <option value="3" <?php echo ($form_data['review_frequency_months'] ?? '') == '3' ? 'selected' : ''; ?>>Quarterly</option>
                                                    <option value="6" <?php echo ($form_data['review_frequency_months'] ?? '') == '6' ? 'selected' : ''; ?>>Semi-Annual</option>
                                                    <option value="12" <?php echo ($form_data['review_frequency_months'] ?? '') == '12' ? 'selected' : ''; ?>>Annual</option>
                                                    <option value="24" <?php echo ($form_data['review_frequency_months'] ?? '') == '24' ? 'selected' : ''; ?>>Biennial</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="next_review_date">Next Review Date</label>
                                                <input type="date" name="next_review_date" id="next_review_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['next_review_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Processing Details -->
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-database"></i> Data Processing</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="data_volume">Data Volume</label>
                                                <select name="data_volume" id="data_volume" class="form-control">
                                                    <?php foreach ($data_volumes as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['data_volume'] ?? '') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="access_type">Access Type</label>
                                                <select name="access_type" id="access_type" class="form-control">
                                                    <?php foreach ($access_types as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['access_type'] ?? '') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="data_categories_processed">Data Categories</label>
                                        <textarea name="data_categories_processed" id="data_categories_processed" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['data_categories_processed'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="processing_purpose">Processing Purpose</label>
                                        <textarea name="processing_purpose" id="processing_purpose" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['processing_purpose'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="has_subprocessors" value="1"
                                                <?php echo !empty($form_data['has_subprocessors']) ? 'checked' : ''; ?>>
                                            <strong>Uses sub-processors</strong>
                                        </label>
                                    </div>

                                    <div class="form-group" id="subprocessor_group" style="display: none;">
                                        <label for="subprocessor_details">Sub-processor Details</label>
                                        <textarea name="subprocessor_details" id="subprocessor_details" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['subprocessor_details'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="transfers_data_internationally" value="1"
                                                <?php echo !empty($form_data['transfers_data_internationally']) ? 'checked' : ''; ?>>
                                            <strong>International data transfers</strong>
                                        </label>
                                    </div>

                                    <div class="form-group" id="transfer_countries_group" style="display: none;">
                                        <label for="data_transfer_countries">Transfer Countries</label>
                                        <input type="text" name="data_transfer_countries" id="data_transfer_countries" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['data_transfer_countries'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Link to ROPA -->
                            <?php if (!empty($ropa_entries)): ?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-link"></i> Linked Processing Activities</h3>
                                </div>
                                <div class="panel-body" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($ropa_entries as $ropa): ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="linked_ropa[]" value="<?php echo $ropa['ropa_id']; ?>"
                                                <?php echo in_array($ropa['ropa_id'], $existing_ropa) ? 'checked' : ''; ?>>
                                            <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['notes'] ?? ''); ?></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-save"></i> Update Vendor
                                    </button>
                                    <a href="vendor_view.php?id=<?php echo $vendor_id; ?>" class="btn btn-default btn-lg">
                                        <i class="fa fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        $(document).ready(function() {
            function toggleDpaFields() {
                var status = $('#dpa_status').val();
                if (status === 'signed' || status === 'expired') {
                    $('#dpa_dates').show();
                } else {
                    $('#dpa_dates').hide();
                }
            }

            function toggleSubprocessor() {
                if ($('input[name="has_subprocessors"]').is(':checked')) {
                    $('#subprocessor_group').show();
                } else {
                    $('#subprocessor_group').hide();
                }
            }

            function toggleTransferCountries() {
                if ($('input[name="transfers_data_internationally"]').is(':checked')) {
                    $('#transfer_countries_group').show();
                } else {
                    $('#transfer_countries_group').hide();
                }
            }

            toggleDpaFields();
            toggleSubprocessor();
            toggleTransferCountries();

            $('#dpa_status').on('change', toggleDpaFields);
            $('input[name="has_subprocessors"]').on('change', toggleSubprocessor);
            $('input[name="transfers_data_internationally"]').on('change', toggleTransferCountries);
        });
    </script>
</body>
</html>
