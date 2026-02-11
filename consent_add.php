<?php
/**
 * Add New Consent
 * Record data subject consent
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = sanitize_input($_POST['subject_name'] ?? '');
    $subject_email = sanitize_input($_POST['subject_email'] ?? '');
    $subject_phone = sanitize_input($_POST['subject_phone'] ?? '');
    $subject_id_number = sanitize_input($_POST['subject_id_number'] ?? '');
    $purpose = sanitize_input($_POST['purpose'] ?? '');
    $purpose_description = sanitize_input($_POST['purpose_description'] ?? '');
    $consent_date = sanitize_input($_POST['consent_date'] ?? date('Y-m-d'));
    $consent_method = sanitize_input($_POST['consent_method'] ?? '');
    $consent_evidence = sanitize_input($_POST['consent_evidence'] ?? '');
    $expiry_date = !empty($_POST['expiry_date']) ? sanitize_input($_POST['expiry_date']) : null;
    $data_categories = sanitize_input($_POST['data_categories'] ?? '');
    $retention_period = sanitize_input($_POST['retention_period'] ?? '');
    $withdrawal_method = sanitize_input($_POST['withdrawal_method'] ?? '');
    $ropa_id = !empty($_POST['ropa_id']) ? intval($_POST['ropa_id']) : null;

    // Validation
    $errors = [];
    if (empty($subject_name)) $errors[] = "Data subject name is required.";
    if (empty($subject_email)) $errors[] = "Data subject email is required.";
    if (empty($purpose)) $errors[] = "Purpose is required.";
    if (empty($consent_date)) $errors[] = "Consent date is required.";
    if (empty($consent_method)) $errors[] = "Consent method is required.";

    // Validate email
    if (!empty($subject_email) && !filter_var($subject_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address format.";
    }

    if (empty($errors)) {
        // Generate a unique subject reference
        $subject_ref = 'SUBJ-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Get a default purpose_id (use first available purpose or create a default one)
        $purpose_check = "SELECT purpose_id FROM purposes LIMIT 1";
        $purpose_result = db_fetch_one(db_query($purpose_check, []));
        $purpose_id = $purpose_result ? $purpose_result['purpose_id'] : 1;

        $query = "INSERT INTO consents (
                  org_id, subject_ref, subject_name, subject_email, subject_phone, subject_id_number,
                  purpose_id, purpose, purpose_description, consent_date, consent_method, consent_evidence,
                  expiry_date, data_categories, retention_period, withdrawal_method,
                  ropa_id, status, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'granted', ?)";

        $stmt = db_query($query, [
            $org_id, $subject_ref, $subject_name, $subject_email, $subject_phone, $subject_id_number,
            $purpose_id, $purpose, $purpose_description, $consent_date, $consent_method, $consent_evidence,
            $expiry_date, $data_categories, $retention_period, $withdrawal_method,
            $ropa_id, $user_id
        ]);

        $consent_id = db_insert_id();

        // Log audit
        log_audit($org_id, $user_id, 'consent', $consent_id, 'create', "Recorded consent: $purpose for $subject_name");

        set_flash_message('Consent recorded successfully!', 'success');
        redirect("consent_view.php?id=$consent_id");
    }
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
    <title><?php echo APP_NAME; ?> - Record New Consent</title>
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

                <div class="row">
                    <div class="col-md-12">
                        <h2>Record New Consent</h2>
                        <h5>Data Subject Consent</h5>
                    </div>
                </div>
                <hr />

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <strong>Validation Errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="consent_add.php" id="consentForm">
        <div class="row">
            <div class="col-md-8">
                <!-- Data Subject Information -->
                <div class="card border-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-user"></i> Data Subject Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" class="form-control" required
                                   placeholder="Full name of data subject">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="subject_email" class="form-control" required
                                           placeholder="email@example.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="subject_phone" class="form-control"
                                           placeholder="+263...">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ID Number / Passport</label>
                            <input type="text" name="subject_id_number" class="form-control"
                                   placeholder="For identity verification">
                            <small class="text-muted">Used to uniquely identify the data subject</small>
                        </div>
                    </div>
                </div>

                <!-- Consent Purpose -->
                <div class="card border-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-gavel"></i> Consent Purpose</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Purpose <span class="text-danger">*</span></label>
                            <select name="purpose" id="purposeSelect" class="form-control" required>
                                <option value="">-- Select Purpose --</option>
                                <option value="Marketing Communications">Marketing Communications</option>
                                <option value="Newsletter Subscription">Newsletter Subscription</option>
                                <option value="Product/Service Delivery">Product/Service Delivery</option>
                                <option value="Customer Support">Customer Support</option>
                                <option value="Account Management">Account Management</option>
                                <option value="Research & Analytics">Research & Analytics</option>
                                <option value="Third-Party Sharing">Third-Party Sharing</option>
                                <option value="Profiling & Automated Decisions">Profiling & Automated Decisions</option>
                                <option value="Event Registration">Event Registration</option>
                                <option value="Employment Application">Employment Application</option>
                                <option value="Other">Other (Please Specify)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Purpose Description</label>
                            <textarea name="purpose_description" class="form-control" rows="4"
                                      placeholder="Detailed description of what the data will be used for. Be specific and clear so data subjects understand what they're consenting to."></textarea>
                            <small class="text-muted">Consent must be informed - clearly explain how data will be processed</small>
                        </div>

                        <div class="form-group">
                            <label>Data Categories to be Processed</label>
                            <textarea name="data_categories" class="form-control" rows="3"
                                      placeholder="List the types of personal data covered by this consent (e.g., name, email, phone, address, payment details)"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Linked Processing Activity (ROPA)</label>
                            <select name="ropa_id" class="form-control">
                                <option value="">-- Not Linked --</option>
                                <?php foreach ($ropa_entries as $ropa): ?>
                                    <option value="<?php echo $ropa['ropa_id']; ?>">
                                        <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Link to the processing activity this consent authorizes</small>
                        </div>
                    </div>
                </div>

                <!-- Consent Details -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-file-text"></i> Consent Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Consent Date <span class="text-danger">*</span></label>
                                    <input type="date" name="consent_date" class="form-control" required
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">Date when consent was obtained</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="form-control"
                                           min="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">Date when consent should be reviewed/renewed</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Consent Method <span class="text-danger">*</span></label>
                            <select name="consent_method" class="form-control" required>
                                <option value="">-- Select Method --</option>
                                <option value="Online Form">Online Form / Checkbox</option>
                                <option value="Written Agreement">Written Agreement / Signature</option>
                                <option value="Email Confirmation">Email Confirmation / Opt-in</option>
                                <option value="Verbal Consent">Verbal Consent (Recorded)</option>
                                <option value="SMS Opt-in">SMS Opt-in</option>
                                <option value="Mobile App">Mobile App Permission</option>
                                <option value="Cookie Banner">Cookie Banner / Pop-up</option>
                                <option value="Terms & Conditions">Terms & Conditions Acceptance</option>
                                <option value="Other">Other</option>
                            </select>
                            <small class="text-muted">How the consent was obtained</small>
                        </div>

                        <div class="form-group">
                            <label>Consent Evidence</label>
                            <textarea name="consent_evidence" class="form-control" rows="4"
                                      placeholder="Describe the evidence of consent (e.g., checkbox text, form fields, timestamp, IP address, signature details, recording reference)"></textarea>
                            <small class="text-muted">You must be able to demonstrate that consent was obtained</small>
                        </div>

                        <div class="form-group">
                            <label>Retention Period</label>
                            <input type="text" name="retention_period" class="form-control"
                                   placeholder="e.g., 2 years, Until account closure, 6 months after last contact">
                            <small class="text-muted">How long will data be retained</small>
                        </div>

                        <div class="form-group">
                            <label>Withdrawal Method</label>
                            <textarea name="withdrawal_method" class="form-control" rows="3"
                                      placeholder="Describe how data subjects can withdraw consent (e.g., unsubscribe link, account settings, email request, contact form)"></textarea>
                            <small class="text-muted">Must be as easy to withdraw as to give consent (CDPA s.22)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Consent Requirements -->
                <div class="card border-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-check-square"></i> Valid Consent Requirements</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>CDPA s.8 Requirements:</strong></p>
                        <ul style="font-size: 12px; margin-bottom: 15px;">
                            <li><strong>Freely Given</strong> - No coercion, bundling, or negative consequences</li>
                            <li><strong>Specific</strong> - Clear, granular purpose</li>
                            <li><strong>Informed</strong> - Clear information provided</li>
                            <li><strong>Unambiguous</strong> - Clear affirmative action</li>
                        </ul>

                        <div class="alert alert-warning" style="font-size: 12px; margin-bottom: 0;">
                            <strong>Invalid Consent:</strong>
                            <ul style="margin: 5px 0 0 15px;">
                                <li>Pre-ticked boxes</li>
                                <li>Silence or inactivity</li>
                                <li>Bundled conditions</li>
                                <li>Vague language</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fa fa-save"></i> Record Consent
                        </button>
                        <a href="consent_list.php" class="btn btn-secondary btn-block">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>

                <!-- Quick Reference -->
                <div class="card border-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-book"></i> Documentation Tips</h3>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 12px; margin-bottom: 10px;">
                            <strong>What to Document:</strong>
                        </p>
                        <ul style="font-size: 11px; margin: 0;">
                            <li>Who gave consent</li>
                            <li>When consent was given</li>
                            <li>What they were told</li>
                            <li>How they gave consent</li>
                            <li>Whether they opted out of anything</li>
                            <li>When consent will be refreshed</li>
                        </ul>
                    </div>
                </div>

                <!-- Legal Basis Info -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-info-circle"></i> Legal Basis</h3>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 11px; margin: 0;">
                            <strong>Remember:</strong> Consent is just one of several lawful bases under CDPA s.8.
                            Other bases include contract, legal obligation, vital interests, public task, and legitimate interests.
                            Only use consent when it's appropriate.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Allow custom purpose entry
    $('#purposeSelect').change(function() {
        if ($(this).val() === 'Other') {
            var customPurpose = prompt('Please specify the purpose:');
            if (customPurpose) {
                $(this).append('<option value="' + customPurpose + '" selected>' + customPurpose + '</option>');
            } else {
                $(this).val('');
            }
        }
    });
});
</script>


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
