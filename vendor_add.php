<?php
/**
 * DPA Tool - Add Vendor
 * Create new vendor entry with details and DPA information
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

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
    $notes = sanitize_input($_POST['notes'] ?? '');

    // Validation
    $errors = [];

    if (empty($vendor_name)) {
        $errors[] = 'Vendor name is required.';
    }

    // Check for duplicate vendor name
    $check_query = "SELECT vendor_id FROM vendors WHERE org_id = ? AND vendor_name = ?";
    $check_stmt = db_query($check_query, [$org_id, $vendor_name]);
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

    // Validate phone number (allows +, digits, spaces, dashes, parentheses, 7-20 chars)
    if (!empty($primary_contact_phone) && !preg_match('/^[\+]?[\d\s\-\(\)]{7,20}$/', $primary_contact_phone)) {
        $errors[] = 'Invalid phone number format. Use digits, spaces, dashes, or parentheses (7-20 characters).';
    }

    // Validate website URL
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Invalid website URL format. Include http:// or https://';
    }

    // DPA date validation
    if ($dpa_status === 'signed' && empty($dpa_signed_date)) {
        $errors[] = 'DPA signed date is required when status is "Signed".';
    }

    // Contract date validation
    if (!empty($contract_start_date) && !empty($contract_end_date)) {
        if (strtotime($contract_end_date) <= strtotime($contract_start_date)) {
            $errors[] = 'Contract end date must be after start date.';
        }
    }

    if (empty($errors)) {
        // Generate vendor reference
        $vendor_ref = generate_reference('VND');

        // Calculate next review date
        $next_review_date = date('Y-m-d', strtotime('+' . $review_frequency_months . ' months'));

        // Insert vendor
        $query = "INSERT INTO vendors (
                    org_id, vendor_ref, vendor_name, vendor_type, description, website, country, address,
                    primary_contact_name, primary_contact_email, primary_contact_phone, dpo_name, dpo_email,
                    dpa_status, dpa_signed_date, dpa_expiry_date, contract_ref, contract_start_date, contract_end_date,
                    data_categories_processed, processing_purpose, data_volume, access_type,
                    has_subprocessors, subprocessor_details, transfers_data_internationally, data_transfer_countries,
                    status, criticality, next_review_date, review_frequency_months, notes, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = db_query($query, [
            $org_id, $vendor_ref, $vendor_name, $vendor_type, $description, $website, $country, $address,
            $primary_contact_name, $primary_contact_email, $primary_contact_phone, $dpo_name, $dpo_email,
            $dpa_status, $dpa_signed_date, $dpa_expiry_date, $contract_ref, $contract_start_date, $contract_end_date,
            $data_categories_processed, $processing_purpose, $data_volume, $access_type,
            $has_subprocessors, $subprocessor_details, $transfers_data_internationally, $data_transfer_countries,
            'pending_review', $criticality, $next_review_date, $review_frequency_months, $notes, $user_id
        ]);

        if ($stmt) {
            $vendor_id = db_insert_id();

            // Handle ROPA links if provided
            if (!empty($_POST['linked_ropa'])) {
                foreach ($_POST['linked_ropa'] as $ropa_id) {
                    $ropa_query = "INSERT INTO vendor_ropa (vendor_id, ropa_id, relationship_type) VALUES (?, ?, 'processor')";
                    db_query($ropa_query, [$vendor_id, intval($ropa_id)]);
                }
            }

            // Log audit
            log_audit($org_id, $user_id, 'vendor', $vendor_id, 'create');

            // Create notification for DPO to review new vendor
            $dpo_query = "SELECT u.user_id FROM users u
                          LEFT JOIN roles r ON u.role_id = r.role_id
                          WHERE u.org_id = ? AND r.role_name IN ('Admin', 'DPO') AND u.status = 'active'";
            $dpo_stmt = db_query($dpo_query, [$org_id]);
            while ($dpo = db_fetch_one($dpo_stmt)) {
                if ($dpo['user_id'] != $user_id) {
                    create_notification(
                        $org_id,
                        $dpo['user_id'],
                        'vendor_pending_review',
                        'New Vendor Pending Review',
                        "New vendor '$vendor_name' has been added and requires review.",
                        'vendor',
                        $vendor_id,
                        "vendor_view.php?id=$vendor_id",
                        'normal'
                    );
                }
            }

            set_flash_message('Vendor added successfully! Status set to Pending Review.', 'success');
            redirect('vendor_view.php?id=' . $vendor_id);
        } else {
            $errors[] = 'Database error: Unable to add vendor.';
        }
    }

    // Store errors in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Fetch ROPA entries for linking
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? AND status != 'archived' ORDER BY activity_name";
$stmt = db_query($ropa_query, [$org_id]);
$ropa_entries = db_fetch_all($stmt);

// Get form errors and data if available
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

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
    <title><?php echo APP_NAME; ?> - Add Vendor</title>
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
                        <h2>Add New Vendor</h2>
                        <h5>Register a new vendor or third-party service provider</h5>
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

                <!-- Add Vendor Form -->
                <form method="POST" action="vendor_add.php" id="vendorForm">
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
                                               value="<?php echo htmlspecialchars($form_data['vendor_name'] ?? ''); ?>"
                                               placeholder="e.g., Acme Cloud Services Ltd">
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="vendor_type">Vendor Type <span class="text-danger">*</span></label>
                                                <select name="vendor_type" id="vendor_type" class="form-control" required>
                                                    <?php foreach ($vendor_types as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['vendor_type'] ?? 'processor') === $val ? 'selected' : ''; ?>>
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
                                                            <?php echo ($form_data['criticality'] ?? 'medium') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="description">Description</label>
                                        <textarea name="description" id="description" class="form-control" rows="3"
                                                  placeholder="Brief description of services provided..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="country">Country</label>
                                                <input type="text" name="country" id="country" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['country'] ?? ''); ?>"
                                                       placeholder="e.g., Zimbabwe">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="website">Website</label>
                                                <input type="url" name="website" id="website" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['website'] ?? ''); ?>"
                                                       placeholder="https://example.com">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" class="form-control" rows="2"
                                                  placeholder="Physical address..."><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
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
                                    <h5><strong>Vendor DPO Contact (if applicable)</strong></h5>

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
                                                            <?php echo ($form_data['dpa_status'] ?? 'none') === $val ? 'selected' : ''; ?>>
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
                                                       value="<?php echo htmlspecialchars($form_data['contract_ref'] ?? ''); ?>"
                                                       placeholder="e.g., CON-2024-001">
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

                                    <div class="form-group">
                                        <label for="review_frequency_months">Review Frequency (months)</label>
                                        <select name="review_frequency_months" id="review_frequency_months" class="form-control">
                                            <option value="3" <?php echo ($form_data['review_frequency_months'] ?? '12') == '3' ? 'selected' : ''; ?>>Quarterly (3 months)</option>
                                            <option value="6" <?php echo ($form_data['review_frequency_months'] ?? '12') == '6' ? 'selected' : ''; ?>>Semi-Annual (6 months)</option>
                                            <option value="12" <?php echo ($form_data['review_frequency_months'] ?? '12') == '12' ? 'selected' : ''; ?>>Annual (12 months)</option>
                                            <option value="24" <?php echo ($form_data['review_frequency_months'] ?? '12') == '24' ? 'selected' : ''; ?>>Biennial (24 months)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Data Processing Details -->
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-database"></i> Data Processing Details</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="data_volume">Data Volume</label>
                                                <select name="data_volume" id="data_volume" class="form-control">
                                                    <?php foreach ($data_volumes as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['data_volume'] ?? 'medium') === $val ? 'selected' : ''; ?>>
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
                                                            <?php echo ($form_data['access_type'] ?? 'process') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="data_categories_processed">Data Categories Processed</label>
                                        <textarea name="data_categories_processed" id="data_categories_processed" class="form-control" rows="2"
                                                  placeholder="e.g., Names, email addresses, financial data..."><?php echo htmlspecialchars($form_data['data_categories_processed'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="processing_purpose">Processing Purpose</label>
                                        <textarea name="processing_purpose" id="processing_purpose" class="form-control" rows="2"
                                                  placeholder="Describe why this vendor processes personal data..."><?php echo htmlspecialchars($form_data['processing_purpose'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="has_subprocessors" value="1"
                                                <?php echo !empty($form_data['has_subprocessors']) ? 'checked' : ''; ?>>
                                            <strong>Vendor uses sub-processors</strong>
                                        </label>
                                    </div>

                                    <div class="form-group" id="subprocessor_group" style="display: none;">
                                        <label for="subprocessor_details">Sub-processor Details</label>
                                        <textarea name="subprocessor_details" id="subprocessor_details" class="form-control" rows="2"
                                                  placeholder="List known sub-processors..."><?php echo htmlspecialchars($form_data['subprocessor_details'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="transfers_data_internationally" value="1"
                                                <?php echo !empty($form_data['transfers_data_internationally']) ? 'checked' : ''; ?>>
                                            <strong>Transfers data internationally</strong>
                                        </label>
                                    </div>

                                    <div class="form-group" id="transfer_countries_group" style="display: none;">
                                        <label for="data_transfer_countries">Transfer Countries</label>
                                        <input type="text" name="data_transfer_countries" id="data_transfer_countries" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['data_transfer_countries'] ?? ''); ?>"
                                               placeholder="e.g., USA, UK, EU">
                                    </div>
                                </div>
                            </div>

                            <!-- Link to ROPA -->
                            <?php if (!empty($ropa_entries)): ?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-link"></i> Link to Processing Activities</h3>
                                </div>
                                <div class="panel-body" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($ropa_entries as $ropa): ?>
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="linked_ropa[]" value="<?php echo $ropa['ropa_id']; ?>"
                                                <?php echo in_array($ropa['ropa_id'], $form_data['linked_ropa'] ?? []) ? 'checked' : ''; ?>>
                                            <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Notes -->
                            <div class="form-group">
                                <label for="notes">Additional Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2"
                                          placeholder="Any additional notes..."><?php echo htmlspecialchars($form_data['notes'] ?? ''); ?></textarea>
                            </div>

                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-body">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-save"></i> Save Vendor
                                    </button>
                                    <a href="vendor_list.php" class="btn btn-default btn-lg">
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
            // Toggle DPA date fields based on status
            function toggleDpaFields() {
                var status = $('#dpa_status').val();
                if (status === 'signed' || status === 'expired') {
                    $('#dpa_dates').show();
                } else {
                    $('#dpa_dates').hide();
                }
            }

            // Toggle sub-processor details
            function toggleSubprocessor() {
                if ($('input[name="has_subprocessors"]').is(':checked')) {
                    $('#subprocessor_group').show();
                } else {
                    $('#subprocessor_group').hide();
                }
            }

            // Toggle transfer countries
            function toggleTransferCountries() {
                if ($('input[name="transfers_data_internationally"]').is(':checked')) {
                    $('#transfer_countries_group').show();
                } else {
                    $('#transfer_countries_group').hide();
                }
            }

            // Initialize
            toggleDpaFields();
            toggleSubprocessor();
            toggleTransferCountries();

            // Event handlers
            $('#dpa_status').on('change', toggleDpaFields);
            $('input[name="has_subprocessors"]').on('change', toggleSubprocessor);
            $('input[name="transfers_data_internationally"]').on('change', toggleTransferCountries);

            // Email validation function
            function isValidEmail(email) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            // Phone validation function (allows +, digits, spaces, dashes, parentheses)
            function isValidPhone(phone) {
                var phoneRegex = /^[\+]?[\d\s\-\(\)]{7,20}$/;
                return phoneRegex.test(phone);
            }

            // Real-time email validation
            $('input[type="email"]').on('blur', function() {
                var email = $(this).val().trim();
                if (email && !isValidEmail(email)) {
                    $(this).css('border-color', '#d9534f');
                    if (!$(this).next('.validation-error').length) {
                        $(this).after('<small class="validation-error text-danger">Please enter a valid email address</small>');
                    }
                } else {
                    $(this).css('border-color', '');
                    $(this).next('.validation-error').remove();
                }
            });

            // Real-time phone validation
            $('#primary_contact_phone').on('blur', function() {
                var phone = $(this).val().trim();
                if (phone && !isValidPhone(phone)) {
                    $(this).css('border-color', '#d9534f');
                    if (!$(this).next('.validation-error').length) {
                        $(this).after('<small class="validation-error text-danger">Please enter a valid phone number</small>');
                    }
                } else {
                    $(this).css('border-color', '');
                    $(this).next('.validation-error').remove();
                }
            });

            // Form validation
            $('#vendorForm').on('submit', function(e) {
                var errors = [];
                var name = $('#vendor_name').val().trim();
                var primaryEmail = $('#primary_contact_email').val().trim();
                var dpoEmail = $('#dpo_email').val().trim();
                var phone = $('#primary_contact_phone').val().trim();

                if (!name) {
                    errors.push('Vendor name is required.');
                }

                if (primaryEmail && !isValidEmail(primaryEmail)) {
                    errors.push('Primary contact email is invalid.');
                }

                if (dpoEmail && !isValidEmail(dpoEmail)) {
                    errors.push('DPO email is invalid.');
                }

                if (phone && !isValidPhone(phone)) {
                    errors.push('Phone number format is invalid. Use digits, spaces, dashes, or parentheses.');
                }

                if (errors.length > 0) {
                    e.preventDefault();
                    alert('Please correct the following errors:\n\n' + errors.join('\n'));
                    return false;
                }
            });
        });
    </script>
</body>
</html>
