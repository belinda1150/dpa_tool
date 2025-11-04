<?php
/**
 * Add Incident Page
 * Report new security incident or data breach
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $incident_title = sanitize_input($_POST['incident_title'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $severity = sanitize_input($_POST['severity'] ?? 'minor');
    $detected_at = !empty($_POST['detected_at']) ? $_POST['detected_at'] : date('Y-m-d H:i:s');

    $notifiable = isset($_POST['notifiable']) ? 1 : 0;
    $data_subjects_affected = $notifiable && isset($_POST['data_subjects_affected']) ? intval($_POST['data_subjects_affected']) : 0;
    $data_categories_affected = $notifiable ? sanitize_input($_POST['data_categories_affected'] ?? '') : null;

    $detected_by = isset($_POST['detected_by']) && $_POST['detected_by'] !== '' ? intval($_POST['detected_by']) : null;
    $ropa_id = isset($_POST['ropa_id']) && $_POST['ropa_id'] !== '' ? intval($_POST['ropa_id']) : null;

    $status = sanitize_input($_POST['status'] ?? 'open');
    $how_detected = sanitize_input($_POST['how_detected'] ?? '');

    // Validation
    $errors = [];

    if (empty($incident_title)) {
        $errors[] = 'Incident title is required.';
    }
    if (empty($description)) {
        $errors[] = 'Incident description is required.';
    }
    if ($notifiable && empty($data_categories_affected)) {
        $errors[] = 'Data categories affected is required for notifiable breaches.';
    }

    if (empty($errors)) {
        // Generate unique incident reference
        $incident_ref = 'INC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Insert incident
        $query = "INSERT INTO incidents (
                    org_id, incident_ref, incident_title, description,
                    severity, detected_at, notifiable, data_subjects_affected,
                    data_categories_affected, detected_by, ropa_id,
                    status, how_detected, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = db_query($query, [
            $org_id,
            $incident_ref,
            $incident_title,
            $description,
            $severity,
            $detected_at,
            $notifiable,
            $data_subjects_affected,
            $data_categories_affected,
            $detected_by,
            $ropa_id,
            $status,
            $how_detected,
            $user_id
        ]);

        if ($stmt) {
            $incident_id = db_insert_id();

            // Log audit
            log_audit($org_id, $user_id, 'incident', $incident_id, 'create', "Created incident: $incident_title");

            // Create notification for handler if assigned
            if ($detected_by && $detected_by != $user_id) {
                $priority = ($notifiable || $severity === 'critical') ? 'high' : 'normal';

                create_notification(
                    $org_id,
                    $detected_by,
                    'incident_assigned',
                    'Incident Detected',
                    "New incident detected: $incident_title",
                    'incident',
                    $incident_id,
                    "incident_view.php?id=$incident_id",
                    $priority
                );
            }

            // Notify DPO if it's a notifiable breach
            if ($notifiable) {
                $dpo_query = "SELECT user_id FROM users WHERE org_id = ? AND role_id = 2 LIMIT 1";
                $dpo = db_fetch_one(db_query($dpo_query, [$org_id]));

                if ($dpo) {
                    create_notification(
                        $org_id,
                        $dpo['user_id'],
                        'data_breach',
                        'DATA BREACH REPORTED',
                        "A data breach has been reported: $incident_title. POTRAZ notification required within 72 hours.",
                        'incident',
                        $incident_id,
                        "incident_view.php?id=$incident_id",
                        'high'
                    );
                }
            }

            set_flash_message('Incident reported successfully!', 'success');
            redirect('incident_list.php');
        } else {
            $errors[] = 'Database error: Unable to report incident.';
        }
    }

    // Store errors in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Fetch ROPA entries for dropdown
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? ORDER BY activity_name";
$stmt = db_query($ropa_query, [$org_id]);
$ropa_entries = db_fetch_all($stmt);

// Fetch users for detected_by dropdown
$users_query = "SELECT user_id, first_name, last_name, email FROM users WHERE org_id = ? AND status = 'active' ORDER BY first_name, last_name";
$stmt = db_query($users_query, [$org_id]);
$users = db_fetch_all($stmt);

// Get form errors and data if available
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Report Incident</title>
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

                <div class="row">
                    <div class="col-md-12">
                        <h2>Report New Incident</h2>
                        <h5>Document security incident or data breach</h5>
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

    <!-- Important Notice -->
    <div class="alert alert-warning">
        <i class="fa fa-info-circle"></i>
        <strong>Important:</strong> If this is a data breach, POTRAZ must be notified within 72 hours of discovery.
        Complete all fields accurately for compliance reporting.
    </div>

    <!-- Add Incident Form -->
    <form method="POST" action="incident_add.php" id="incidentForm">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">

                <!-- Basic Information -->
                <div class="panel panel-danger">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Incident Details</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="incident_title">Incident Title <span class="text-danger">*</span></label>
                            <input type="text" name="incident_title" id="incident_title" class="form-control" required
                                   value="<?php echo htmlspecialchars($form_data['incident_title'] ?? ''); ?>"
                                   placeholder="e.g., Unauthorized Access to Customer Database">
                        </div>

                        <div class="form-group">
                            <label for="description">Incident Description <span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control" rows="5" required
                                      placeholder="Detailed description of what happened, how it was discovered, and initial findings..."><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="severity">Severity <span class="text-danger">*</span></label>
                            <select name="severity" id="severity" class="form-control" required>
                                <?php
                                $severities = [
                                    'minor' => 'Minor - Minimal impact',
                                    'serious' => 'Serious - Significant impact',
                                    'critical' => 'Critical - Severe impact'
                                ];
                                foreach ($severities as $val => $label):
                                    $selected = (isset($form_data['severity']) && $form_data['severity'] === $val) ? 'selected' : ($val === 'minor' ? 'selected' : '');
                                ?>
                                    <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="detected_at">Detection Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="detected_at" id="detected_at" class="form-control" required
                                   value="<?php echo isset($form_data['detected_at']) ? date('Y-m-d\TH:i', strtotime($form_data['detected_at'])) : date('Y-m-d\TH:i'); ?>"
                                   max="<?php echo date('Y-m-d\TH:i'); ?>">
                            <p class="help-block">When was the incident detected or discovered?</p>
                        </div>

                        <div class="form-group">
                            <label for="how_detected">How Was It Detected?</label>
                            <textarea name="how_detected" id="how_detected" class="form-control" rows="3"
                                      placeholder="e.g., Automated monitoring alert, user report, audit finding..."><?php echo htmlspecialchars($form_data['how_detected'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="ropa_id">Related Processing Activity</label>
                                    <select name="ropa_id" id="ropa_id" class="form-control">
                                        <option value="">-- Select ROPA --</option>
                                        <?php foreach ($ropa_entries as $ropa): ?>
                                            <?php $selected = (isset($form_data['ropa_id']) && $form_data['ropa_id'] == $ropa['ropa_id']) ? 'selected' : ''; ?>
                                            <option value="<?php echo $ropa['ropa_id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="detected_by">Detected By</label>
                                    <select name="detected_by" id="detected_by" class="form-control">
                                        <option value="">-- Select User --</option>
                                        <?php foreach ($users as $user): ?>
                                            <?php $selected = (isset($form_data['detected_by']) && $form_data['detected_by'] == $user['user_id']) ? 'selected' : ''; ?>
                                            <option value="<?php echo $user['user_id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="help-block">Person who detected this incident</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-md-6">

                <!-- Data Breach Information -->
                <div class="panel panel-warning" id="dataBreachPanel">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-shield"></i> Data Breach Information
                            <span class="label label-danger pull-right" id="breachLabel" style="display: none;">72-HOUR DEADLINE</span>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="notifiable" id="notifiable" value="1"
                                           <?php echo (isset($form_data['notifiable']) && $form_data['notifiable']) ? 'checked' : ''; ?>>
                                    <strong>This is a Notifiable Breach</strong> (Personal data compromised/exposed)
                                </label>
                            </div>
                            <p class="help-block text-danger" id="breachWarning" style="display: <?php echo (isset($form_data['notifiable']) && $form_data['notifiable']) ? 'block' : 'none'; ?>;">
                                <i class="fa fa-warning"></i>
                                <strong>IMPORTANT:</strong> POTRAZ must be notified within 72 hours of breach discovery (SI 156 of 2022)
                            </p>
                        </div>

                        <div id="breachFields" style="display: <?php echo (isset($form_data['notifiable']) && $form_data['notifiable']) ? 'block' : 'none'; ?>;">
                            <div class="form-group">
                                <label for="data_subjects_affected">Number of Affected Data Subjects</label>
                                <input type="number" name="data_subjects_affected" id="data_subjects_affected" class="form-control"
                                       value="<?php echo htmlspecialchars($form_data['data_subjects_affected'] ?? ''); ?>"
                                       min="0" placeholder="e.g., 150">
                                <p class="help-block">How many individuals are affected? (Approximate if exact number unknown)</p>
                            </div>

                            <div class="form-group">
                                <label for="data_categories_affected">Types of Data Affected <span class="text-danger">*</span></label>
                                <textarea name="data_categories_affected" id="data_categories_affected" class="form-control" rows="3"
                                          placeholder="e.g., Names, email addresses, phone numbers, ID numbers, financial data, medical records..."><?php echo htmlspecialchars($form_data['data_categories_affected'] ?? ''); ?></textarea>
                                <p class="help-block">List all types of personal data that were compromised</p>
                            </div>

                            <div class="alert alert-danger">
                                <strong>Breach Response Checklist:</strong>
                                <ul style="margin-bottom: 0;">
                                    <li>Contain the breach immediately</li>
                                    <li>Preserve evidence</li>
                                    <li>Notify DPO and management</li>
                                    <li>Assess risk to data subjects</li>
                                    <li>Prepare POTRAZ notification (within 72 hours)</li>
                                    <li>Consider notifying affected individuals</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Immediate Actions -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-flash"></i> Immediate Actions Taken</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="root_cause">Root Cause (if known)</label>
                            <textarea name="root_cause" id="root_cause" class="form-control" rows="3"
                                      placeholder="Describe the root cause if already identified..."><?php echo htmlspecialchars($form_data['root_cause'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="corrective_actions">Corrective Actions Taken</label>
                            <textarea name="corrective_actions" id="corrective_actions" class="form-control" rows="4"
                                      placeholder="Describe immediate containment actions taken (e.g., disabled accounts, isolated systems, changed passwords, notified management...)..."><?php echo htmlspecialchars($form_data['corrective_actions'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="status">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="open" selected>Open - Incident reported, awaiting investigation</option>
                                <option value="investigating">Investigating - Currently being investigated</option>
                                <option value="contained">Contained - Threat contained, investigation ongoing</option>
                            </select>
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
                        <button type="submit" class="btn btn-danger btn-lg">
                            <i class="fa fa-save"></i> Report Incident
                        </button>
                        <a href="incident_list.php" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<script>
// Toggle data breach fields
function toggleBreachFields() {
    var isChecked = $('#notifiable').is(':checked');

    if (isChecked) {
        $('#breachFields').slideDown();
        $('#breachWarning').show();
        $('#breachLabel').show();
        $('#dataBreachPanel').removeClass('panel-warning').addClass('panel-danger');
        $('#data_categories_affected').attr('required', true);
    } else {
        $('#breachFields').slideUp();
        $('#breachWarning').hide();
        $('#breachLabel').hide();
        $('#dataBreachPanel').removeClass('panel-danger').addClass('panel-warning');
        $('#data_categories_affected').attr('required', false);
    }
}

$(document).ready(function() {
    // Initialize breach fields
    toggleBreachFields();

    // Toggle on checkbox change
    $('#notifiable').on('change', toggleBreachFields);

    // Auto-check data breach if type is "Data Breach"
    $('#incident_type').on('change', function() {
        if ($(this).val() === 'Data Breach') {
            $('#notifiable').prop('checked', true);
            toggleBreachFields();
        }
    });

    // Form validation
    $('#incidentForm').on('submit', function(e) {
        var title = $('#incident_title').val().trim();
        var description = $('#description').val().trim();
        var isBreach = $('#notifiable').is(':checked');
        var dataCategories = $('#data_categories_affected').val().trim();

        if (!title || !description) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }

        if (isBreach && !dataCategories) {
            e.preventDefault();
            alert('Data types affected is required for data breaches.');
            return false;
        }

        // Confirm submission for data breach
        if (isBreach) {
            if (!confirm('This incident is marked as a DATA BREACH.\n\nPOTRAZ must be notified within 72 hours.\n\nDo you want to proceed with reporting this incident?')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>

            </div>
        </div>
    </div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
