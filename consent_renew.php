<?php
/**
 * Renew Consent
 * Create a new consent record based on an expiring/expired consent
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get original consent ID
$original_consent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($original_consent_id <= 0) {
    set_flash_message('Invalid consent ID.', 'error');
    redirect('consent_list.php');
}

// Fetch original consent
$query = "SELECT * FROM consents WHERE consent_id = ? AND org_id = ?";
$stmt = db_query($query, [$original_consent_id, $org_id]);
$original_consent = db_fetch_one($stmt);

if (!$original_consent) {
    set_flash_message('Original consent not found.', 'error');
    redirect('consent_list.php');
}

// Handle renewal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consent_date = sanitize_input($_POST['consent_date']);
    $expiry_date = !empty($_POST['expiry_date']) ? sanitize_input($_POST['expiry_date']) : null;
    $consent_method = sanitize_input($_POST['consent_method']);
    $consent_evidence = sanitize_input($_POST['consent_evidence']);
    $renewal_notes = sanitize_input($_POST['renewal_notes'] ?? '');

    // Create new consent record
    $insert_query = "INSERT INTO consents (
                     org_id, subject_name, subject_email, subject_phone, subject_id_number,
                     purpose, purpose_description, consent_date, consent_method, consent_evidence,
                     expiry_date, data_categories, retention_period, withdrawal_method,
                     ropa_id, status, created_by, renewed_from_consent_id
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)";

    $params = [
        $org_id,
        $original_consent['subject_name'],
        $original_consent['subject_email'],
        $original_consent['subject_phone'],
        $original_consent['subject_id_number'],
        $original_consent['purpose'],
        $original_consent['purpose_description'],
        $consent_date,
        $consent_method,
        $consent_evidence,
        $expiry_date,
        $original_consent['data_categories'],
        $original_consent['retention_period'],
        $original_consent['withdrawal_method'],
        $original_consent['ropa_id'],
        $user_id,
        $original_consent_id
    ];

    db_query($insert_query, $params);
    $new_consent_id = db_insert_id();

    // Mark original as renewed (if it was expired)
    if ($original_consent['status'] === 'expired' || $original_consent['status'] === 'active') {
        $update_query = "UPDATE consents
                        SET status = 'renewed',
                            renewed_to_consent_id = ?,
                            renewal_notes = ?
                        WHERE consent_id = ?";
        db_query($update_query, [$new_consent_id, $renewal_notes, $original_consent_id]);
    }

    log_audit($org_id, $user_id, 'CREATE', 'consent', $new_consent_id,
        "Renewed consent from CNS-" . str_pad($original_consent_id, 5, '0', STR_PAD_LEFT));

    log_audit($org_id, $user_id, 'UPDATE', 'consent', $original_consent_id,
        "Original consent renewed to CNS-" . str_pad($new_consent_id, 5, '0', STR_PAD_LEFT));

    set_flash_message('Consent renewed successfully!', 'success');
    redirect("consent_view.php?id=$new_consent_id");
}

// Fetch ROPA entries
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? AND status = 'active' ORDER BY activity_name";
$ropa_entries = db_fetch_all(db_query($ropa_query, [$org_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Renew Consent</title>
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
                    <i class="fa fa-refresh"></i> Renew Consent
                    <small>Create fresh consent record</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="consent_list.php">Consent Management</a></li>
                    <li><a href="consent_view.php?id=<?php echo $original_consent_id; ?>">View Consent</a></li>
                    <li class="active">Renew</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Original Consent Info -->
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info">
                <strong><i class="fa fa-info-circle"></i> Renewing Consent:</strong>
                CNS-<?php echo str_pad($original_consent_id, 5, '0', STR_PAD_LEFT); ?> -
                <?php echo htmlspecialchars($original_consent['purpose']); ?> for
                <?php echo htmlspecialchars($original_consent['subject_name']); ?>
                (<?php echo htmlspecialchars($original_consent['subject_email']); ?>)
            </div>
        </div>
    </div>

    <form method="POST" action="consent_renew.php?id=<?php echo $original_consent_id; ?>">
        <div class="row">
            <div class="col-md-8">
                <!-- Renewal Details -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-refresh"></i> New Consent Details</h3>
                    </div>
                    <div class="panel-body">
                        <div class="alert alert-warning">
                            <strong><i class="fa fa-exclamation-triangle"></i> Important:</strong>
                            Renewal requires obtaining fresh consent from the data subject. You must have evidence
                            that they have actively renewed their consent.
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>New Consent Date <span class="text-danger">*</span></label>
                                    <input type="date" name="consent_date" class="form-control" required
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">Date when renewed consent was obtained</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>New Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="form-control"
                                           value="<?php echo $original_consent['expiry_date'] ? date('Y-m-d', strtotime($original_consent['expiry_date'] . ' +1 year')) : ''; ?>"
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">Typically 1-2 years from now</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Consent Method <span class="text-danger">*</span></label>
                            <select name="consent_method" class="form-control" required>
                                <option value="">-- Select Method --</option>
                                <option value="Online Form" <?php echo $original_consent['consent_method'] == 'Online Form' ? 'selected' : ''; ?>>Online Form / Checkbox</option>
                                <option value="Email Confirmation" selected>Email Confirmation / Opt-in</option>
                                <option value="Written Agreement">Written Agreement / Signature</option>
                                <option value="Verbal Consent">Verbal Consent (Recorded)</option>
                                <option value="SMS Opt-in">SMS Opt-in</option>
                                <option value="Mobile App">Mobile App Permission</option>
                                <option value="Cookie Banner">Cookie Banner / Pop-up</option>
                                <option value="Renewal Form">Consent Renewal Form</option>
                                <option value="Other">Other</option>
                            </select>
                            <small class="text-muted">How was the renewed consent obtained?</small>
                        </div>

                        <div class="form-group">
                            <label>Consent Evidence <span class="text-danger">*</span></label>
                            <textarea name="consent_evidence" class="form-control" rows="4" required
                                      placeholder="Describe the evidence of renewed consent (e.g., confirmation email timestamp, form submission ID, signature date)"></textarea>
                            <small class="text-muted">You must be able to demonstrate that fresh consent was obtained</small>
                        </div>

                        <div class="form-group">
                            <label>Renewal Notes</label>
                            <textarea name="renewal_notes" class="form-control" rows="3"
                                      placeholder="Any additional notes about the renewal process or changes from the original consent"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Original Consent Summary (Read-Only) -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-file-text"></i> Original Consent Summary (Unchanged)</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Data Subject:</strong><br>
                                   <?php echo htmlspecialchars($original_consent['subject_name']); ?><br>
                                   <?php echo htmlspecialchars($original_consent['subject_email']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Purpose:</strong><br>
                                   <?php echo htmlspecialchars($original_consent['purpose']); ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($original_consent['purpose_description']): ?>
                        <p><strong>Purpose Description:</strong></p>
                        <div style="background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                            <?php echo nl2br(htmlspecialchars($original_consent['purpose_description'])); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($original_consent['data_categories']): ?>
                        <p style="margin-top: 10px;"><strong>Data Categories:</strong></p>
                        <div style="background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 12px;">
                            <?php echo nl2br(htmlspecialchars($original_consent['data_categories'])); ?>
                        </div>
                        <?php endif; ?>

                        <div class="alert alert-info" style="margin-top: 15px; margin-bottom: 0; font-size: 12px;">
                            <strong><i class="fa fa-info-circle"></i> Note:</strong>
                            The data subject must be informed of the same purposes, data categories, and retention periods
                            as the original consent. If processing has changed, obtain entirely new consent.
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-md-4">
                <!-- Action Buttons -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fa fa-refresh"></i> Create Renewed Consent
                        </button>
                        <a href="consent_view.php?id=<?php echo $original_consent_id; ?>" class="btn btn-default btn-block">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>

                <!-- Renewal Requirements -->
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-check-square"></i> Renewal Requirements</h3>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 12px;"><strong>Before renewing:</strong></p>
                        <ul style="font-size: 11px;">
                            <li>Contact data subject with renewal request</li>
                            <li>Re-explain purpose and data use</li>
                            <li>Obtain clear affirmative action</li>
                            <li>Document evidence of renewal</li>
                            <li>Ensure withdrawal option is clear</li>
                        </ul>

                        <div class="alert alert-danger" style="font-size: 11px; margin-bottom: 0;">
                            <strong>Never:</strong>
                            <ul style="margin: 5px 0 0 15px;">
                                <li>Assume silence = renewal</li>
                                <li>Use pre-ticked boxes</li>
                                <li>Bundle renewal with other actions</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Original Consent Details -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-clock-o"></i> Original Consent</h3>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 12px; margin: 0;">
                            <strong>Original Date:</strong><br>
                            <?php echo date('d F Y', strtotime($original_consent['consent_date'])); ?><br><br>
                            <?php if ($original_consent['expiry_date']): ?>
                            <strong>Expiry Date:</strong><br>
                            <?php echo date('d F Y', strtotime($original_consent['expiry_date'])); ?><br>
                            <span class="label label-<?php echo strtotime($original_consent['expiry_date']) < time() ? 'danger' : 'warning'; ?>">
                                <?php echo strtotime($original_consent['expiry_date']) < time() ? 'Expired' : 'Expiring Soon'; ?>
                            </span><br><br>
                            <?php endif; ?>
                            <strong>Original Method:</strong><br>
                            <?php echo htmlspecialchars($original_consent['consent_method']); ?>
                        </p>
                    </div>
                </div>

                <!-- Best Practices -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-lightbulb-o"></i> Best Practices</h3>
                    </div>
                    <div class="panel-body">
                        <p style="font-size: 11px; margin: 0;">
                            <strong>Renewal Timing:</strong><br>
                            Contact data subjects 30 days before expiry. If no response within 14 days,
                            send a reminder. If still no response by expiry, stop processing.
                        </p>
                        <hr style="margin: 10px 0;">
                        <p style="font-size: 11px; margin: 0;">
                            <strong>Communication:</strong><br>
                            Use the same communication channel as the original consent. Make renewal as easy as the original opt-in.
                        </p>
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
</body>
</html>
