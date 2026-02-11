<?php
/**
 * Edit Cross-Border Transfer
 * Update existing cross-border transfer record
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get transfer ID
$cb_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$cb_id) {
    set_flash_message('Invalid transfer ID.', 'error');
    redirect('crossborder_list.php');
}

// Fetch existing transfer
$query = "SELECT * FROM cross_border_transfers WHERE cb_id = ? AND org_id = ?";
$stmt = db_query($query, [$cb_id, $org_id]);
$transfer = db_fetch_one($stmt);

if (!$transfer) {
    set_flash_message('Transfer not found.', 'error');
    redirect('crossborder_list.php');
}

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
        // Set submitted_at if status changed to submitted and wasn't before
        $submitted_at = (($status === 'submitted' || $status === 'approved') && !$transfer['submitted_at']) ? date('Y-m-d H:i:s') : $transfer['submitted_at'];
        $approved_at = ($status === 'approved' && !$transfer['approved_at']) ? date('Y-m-d H:i:s') : $transfer['approved_at'];

        $query = "UPDATE cross_border_transfers SET
                    ropa_id = ?, destination_country = ?, recipient_org = ?, recipient_contact = ?,
                    data_type_transferred = ?, transfer_frequency = ?, safeguard_id = ?, safeguard_details = ?,
                    potraz_notification_ref = ?, status = ?, submitted_at = ?, approved_at = ?, is_high_risk = ?
                  WHERE cb_id = ? AND org_id = ?";

        $stmt = db_query($query, [
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
            $cb_id,
            $org_id
        ]);

        if ($stmt) {
            // Log audit
            log_audit($org_id, $user_id, 'cross_border_transfer', $cb_id, 'update', "Updated cross-border transfer to $destination_country");

            set_flash_message('Cross-border transfer updated successfully!', 'success');
            redirect("crossborder_view.php?id=$cb_id");
        } else {
            $errors[] = 'Database error: Unable to update transfer.';
        }
    }

    // Store errors
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
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

// Get form errors
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Use transfer data for form (not form_data from POST)
$form_data = $transfer;

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
    <title><?php echo APP_NAME; ?> - Edit Cross-Border Transfer</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
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

                <div class="row">
                    <div class="col-md-12">
                        <h2>Edit Cross-Border Transfer</h2>
                        <h5>Update transfer to <?php echo htmlspecialchars($transfer['destination_country']); ?></h5>
                    </div>
                </div>
                <hr />

    <!-- Form Errors -->
    <?php if (!empty($form_errors)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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

    <!-- Edit Transfer Form -->
    <form method="POST" action="crossborder_edit.php?id=<?php echo $cb_id; ?>" id="transferForm">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">

                <!-- Transfer Details -->
                <div class="card border-primary">
                    <div class="card-header">
                        <i class="fa fa-info-circle"></i> Transfer Details
                    </div>
                    <div class="card-body">

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
                <div class="card border-info">
                    <div class="card-header">
                        <i class="fa fa-shield"></i> Safeguards & POTRAZ Notification
                    </div>
                    <div class="card-body">

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
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Update Cross-Border Transfer
                        </button>
                        <a href="crossborder_view.php?id=<?php echo $cb_id; ?>" class="btn btn-secondary btn-lg">
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

<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap5.bundle.min.js"></script>
    <script src="assets/js/sidebar-menu.js"></script>
<script src="assets/js/custom.js"></script>
<script src="assets/js/global-search.js"></script>
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
