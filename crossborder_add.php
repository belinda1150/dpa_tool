<?php
/**
 * Add Cross-Border Transfer
 * Record data transfer outside Zimbabwe with POTRAZ notification tracking
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ropa_id = !empty($_POST['ropa_id']) ? intval($_POST['ropa_id']) : null;
    $destination_country = sanitize_input($_POST['destination_country'] ?? '');
    $recipient_org = sanitize_input($_POST['recipient_org'] ?? '');
    $recipient_contact = sanitize_input($_POST['recipient_contact'] ?? '');
    $data_type_transferred = sanitize_input($_POST['data_type_transferred'] ?? '');
    $transfer_frequency = sanitize_input($_POST['transfer_frequency'] ?? 'periodic');
    $safeguard_id = !empty($_POST['safeguard_id']) ? intval($_POST['safeguard_id']) : null;
    $safeguard_details = sanitize_input($_POST['safeguard_details'] ?? '');
    $potraz_notification_ref = sanitize_input($_POST['potraz_notification_ref'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'pending');

    // High-risk countries (those without adequate protection)
    $high_risk_countries = ['China', 'Russia', 'North Korea', 'Iran', 'Syria', 'Cuba', 'Venezuela'];
    $is_high_risk = in_array($destination_country, $high_risk_countries) ? 1 : 0;

    // Validation
    $errors = [];

    if (empty($destination_country)) {
        $errors[] = 'Destination country is required.';
    }
    if (empty($recipient_org)) {
        $errors[] = 'Recipient organisation is required.';
    }
    if (empty($data_type_transferred)) {
        $errors[] = 'Data type transferred is required.';
    }
    if (empty($safeguard_id)) {
        $errors[] = 'Safeguard measure is required.';
    }

    if (empty($errors)) {
        // Set submitted_at if status is submitted
        $submitted_at = ($status === 'submitted' || $status === 'approved') ? date('Y-m-d H:i:s') : null;
        $approved_at = ($status === 'approved') ? date('Y-m-d H:i:s') : null;

        $query = "INSERT INTO cross_border_transfers (
                    org_id, ropa_id, destination_country, recipient_org, recipient_contact,
                    data_type_transferred, transfer_frequency, safeguard_id, safeguard_details,
                    potraz_notification_ref, status, submitted_at, approved_at, is_high_risk, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = db_query($query, [
            $org_id,
            $ropa_id,
            $destination_country,
            $recipient_org,
            $recipient_contact,
            $data_type_transferred,
            $transfer_frequency,
            $safeguard_id,
            $safeguard_details,
            $potraz_notification_ref,
            $status,
            $submitted_at,
            $approved_at,
            $is_high_risk,
            $user_id
        ]);

        if ($stmt) {
            $cb_id = db_insert_id();

            // Log audit
            log_audit($org_id, $user_id, 'cross_border_transfer', $cb_id, 'create', "Created cross-border transfer to $destination_country");

            // Create notification for DPO if high-risk
            if ($is_high_risk) {
                $dpo_query = "SELECT user_id FROM users WHERE org_id = ? AND role_id = 2 LIMIT 1";
                $dpo = db_fetch_one(db_query($dpo_query, [$org_id]));

                if ($dpo) {
                    create_notification(
                        $org_id,
                        $dpo['user_id'],
                        'cross_border_high_risk',
                        'HIGH-RISK Cross-Border Transfer Recorded',
                        "A cross-border transfer to $destination_country (high-risk country) has been recorded. POTRAZ notification required.",
                        'cross_border_transfer',
                        $cb_id,
                        "crossborder_view.php?id=$cb_id",
                        'high'
                    );
                }
            }

            set_flash_message('Cross-border transfer recorded successfully!', 'success');
            redirect('crossborder_list.php');
        } else {
            $errors[] = 'Database error: Unable to record transfer.';
        }
    }

    // Store errors in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Fetch ROPA entries for dropdown
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? AND status != 'archived' ORDER BY activity_name";
$stmt = db_query($ropa_query, [$org_id]);
$ropa_entries = db_fetch_all($stmt);

// Fetch safeguards for dropdown
$safeguards_query = "SELECT safeguard_id, safeguard_name, safeguard_description FROM safeguards ORDER BY safeguard_name";
$stmt = db_query($safeguards_query);
$safeguards = db_fetch_all($stmt);

// Get form errors and data if available
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

// Countries list (common destinations)
$countries = [
    'South Africa', 'United Kingdom', 'United States', 'Germany', 'France', 'Netherlands',
    'Switzerland', 'Ireland', 'Australia', 'Canada', 'Singapore', 'India', 'Kenya',
    'Mauritius', 'Botswana', 'Zambia', 'Namibia', 'Tanzania', 'Malawi', 'Mozambique',
    'China', 'Russia', 'Brazil', 'Japan', 'UAE', 'Other'
];

// High-risk countries
$high_risk_countries = ['China', 'Russia', 'North Korea', 'Iran', 'Syria', 'Cuba', 'Venezuela'];

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Record Cross-Border Transfer</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

<div class="container-fluid">

    <!-- Page Header -->
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-globe"></i> Record Cross-Border Transfer
                    <small>Document data transfer outside Zimbabwe</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="crossborder_list.php">Cross-Border Transfers</a></li>
                    <li class="active">Record Transfer</li>
                </ol>
            </div>
        </div>
    </div>

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

    <!-- Important Notice -->
    <div class="alert alert-warning">
        <i class="fa fa-info-circle"></i>
        <strong>POTRAZ Compliance:</strong> All cross-border transfers must be notified to POTRAZ.
        High-risk countries require additional safeguards and DPO approval.
    </div>

    <!-- Add Transfer Form -->
    <form method="POST" action="crossborder_add.php" id="transferForm">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">

                <!-- Transfer Details -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <i class="fa fa-info-circle"></i> Transfer Details
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label>Link to Processing Activity (ROPA) <small class="text-muted">(Optional)</small></label>
                            <select name="ropa_id" class="form-control">
                                <option value="">-- Select ROPA Entry --</option>
                                <?php foreach ($ropa_entries as $ropa): ?>
                                    <option value="<?php echo $ropa['ropa_id']; ?>"
                                            <?php echo (($form_data['ropa_id'] ?? '') == $ropa['ropa_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Link this transfer to an existing processing activity</small>
                        </div>

                        <div class="form-group">
                            <label>Destination Country <span class="text-danger">*</span></label>
                            <select name="destination_country" id="destination_country" class="form-control" required>
                                <option value="">-- Select Country --</option>
                                <?php foreach ($countries as $country): ?>
                                    <option value="<?php echo $country; ?>"
                                            <?php echo (($form_data['destination_country'] ?? '') == $country) ? 'selected' : ''; ?>
                                            <?php echo in_array($country, $high_risk_countries) ? 'data-high-risk="true"' : ''; ?>>
                                        <?php echo $country; ?>
                                        <?php echo in_array($country, $high_risk_countries) ? ' ⚠️ HIGH RISK' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="alert alert-danger" id="highRiskWarning" style="display:none;">
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong>HIGH-RISK COUNTRY:</strong> This country does not have adequate data protection.
                            Strong safeguards and DPO approval required.
                        </div>

                        <div class="form-group">
                            <label>Recipient Organisation <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_org" class="form-control" required
                                   value="<?php echo htmlspecialchars($form_data['recipient_org'] ?? ''); ?>"
                                   placeholder="e.g., AWS EMEA SARL, Microsoft Ireland">
                        </div>

                        <div class="form-group">
                            <label>Recipient Contact</label>
                            <input type="text" name="recipient_contact" class="form-control"
                                   value="<?php echo htmlspecialchars($form_data['recipient_contact'] ?? ''); ?>"
                                   placeholder="Email or phone of recipient DPO/contact">
                        </div>

                        <div class="form-group">
                            <label>Data Type Transferred <span class="text-danger">*</span></label>
                            <textarea name="data_type_transferred" class="form-control" rows="3" required
                                      placeholder="Describe what personal data is being transferred (e.g., customer names, emails, payment info)"><?php echo htmlspecialchars($form_data['data_type_transferred'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Transfer Frequency</label>
                            <select name="transfer_frequency" class="form-control">
                                <option value="one-time" <?php echo (($form_data['transfer_frequency'] ?? 'periodic') == 'one-time') ? 'selected' : ''; ?>>One-time</option>
                                <option value="periodic" <?php echo (($form_data['transfer_frequency'] ?? 'periodic') == 'periodic') ? 'selected' : ''; ?>>Periodic</option>
                                <option value="continuous" <?php echo (($form_data['transfer_frequency'] ?? 'periodic') == 'continuous') ? 'selected' : ''; ?>>Continuous</option>
                            </select>
                        </div>

                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-md-6">

                <!-- Safeguards & Compliance -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <i class="fa fa-shield"></i> Safeguards & POTRAZ Notification
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label>Legal Safeguard <span class="text-danger">*</span></label>
                            <select name="safeguard_id" id="safeguard_id" class="form-control" required>
                                <option value="">-- Select Safeguard --</option>
                                <?php foreach ($safeguards as $safeguard): ?>
                                    <option value="<?php echo $safeguard['safeguard_id']; ?>"
                                            <?php echo (($form_data['safeguard_id'] ?? '') == $safeguard['safeguard_id']) ? 'selected' : ''; ?>
                                            data-description="<?php echo htmlspecialchars($safeguard['safeguard_description']); ?>">
                                        <?php echo htmlspecialchars($safeguard['safeguard_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" id="safeguardDescription">Select a safeguard to see description</small>
                        </div>

                        <div class="form-group">
                            <label>Safeguard Details</label>
                            <textarea name="safeguard_details" class="form-control" rows="3"
                                      placeholder="Provide details about the safeguard (e.g., SCC version, encryption method, consent reference)"><?php echo htmlspecialchars($form_data['safeguard_details'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>POTRAZ Notification Reference</label>
                            <input type="text" name="potraz_notification_ref" class="form-control"
                                   value="<?php echo htmlspecialchars($form_data['potraz_notification_ref'] ?? ''); ?>"
                                   placeholder="e.g., POTRAZ/CBT/2025/001">
                            <small class="text-muted">Enter reference number once POTRAZ notification is submitted</small>
                        </div>

                        <div class="form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control">
                                <option value="pending" <?php echo (($form_data['status'] ?? 'pending') == 'pending') ? 'selected' : ''; ?>>Pending - Not yet notified</option>
                                <option value="submitted" <?php echo (($form_data['status'] ?? 'pending') == 'submitted') ? 'selected' : ''; ?>>Submitted - Notified to POTRAZ</option>
                                <option value="approved" <?php echo (($form_data['status'] ?? 'pending') == 'approved') ? 'selected' : ''; ?>>Approved - POTRAZ approved</option>
                                <option value="blocked" <?php echo (($form_data['status'] ?? 'pending') == 'blocked') ? 'selected' : ''; ?>>Blocked - Transfer not permitted</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <strong>Compliance Checklist:</strong>
                            <ul style="margin-bottom: 0;">
                                <li>Appropriate safeguards in place</li>
                                <li>Data subjects informed of transfer</li>
                                <li>Recipient provides adequate protection or contractual guarantees</li>
                                <li>POTRAZ notified of transfer</li>
                            </ul>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Form Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Record Cross-Border Transfer
                        </button>
                        <a href="crossborder_list.php" class="btn btn-default btn-lg">
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
    </div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
<script>
$(document).ready(function() {
    // High-risk country warning
    $('#destination_country').on('change', function() {
        var isHighRisk = $(this).find('option:selected').data('high-risk');
        if (isHighRisk) {
            $('#highRiskWarning').slideDown();
        } else {
            $('#highRiskWarning').slideUp();
        }
    });

    // Safeguard description display
    $('#safeguard_id').on('change', function() {
        var description = $(this).find('option:selected').data('description');
        if (description) {
            $('#safeguardDescription').html('<strong>' + description + '</strong>');
        } else {
            $('#safeguardDescription').text('Select a safeguard to see description');
        }
    });

    // Trigger on page load if values exist
    $('#destination_country').trigger('change');
    $('#safeguard_id').trigger('change');
});
</script>
</body>
</html>
