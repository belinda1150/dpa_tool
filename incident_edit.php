<?php
/**
 * Edit Incident Page
 * Update incident details and investigation status
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$incident_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($incident_id <= 0) {
    set_flash_message('Invalid incident ID.', 'danger');
    redirect('incident_list.php');
}

// Fetch incident details
$query = "SELECT * FROM incidents WHERE incident_id = ? AND org_id = ?";
$stmt = db_query($query, [$incident_id, $org_id]);
$incident = db_fetch_one($stmt);

if (!$incident) {
    set_flash_message('Incident not found.', 'danger');
    redirect('incident_list.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incident_title = sanitize_input($_POST['incident_title'] ?? '');
    $incident_type = sanitize_input($_POST['incident_type'] ?? '');
    $incident_description = sanitize_input($_POST['incident_description'] ?? '');
    $severity = sanitize_input($_POST['severity'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'open');
    $incident_handler_id = !empty($_POST['incident_handler_id']) ? intval($_POST['incident_handler_id']) : null;
    $dept_id = !empty($_POST['dept_id']) ? intval($_POST['dept_id']) : null;
    $ropa_id = !empty($_POST['ropa_id']) ? intval($_POST['ropa_id']) : null;
    $immediate_actions = sanitize_input($_POST['immediate_actions'] ?? '');
    $is_data_breach = isset($_POST['is_data_breach']) ? 1 : 0;
    $affected_data_subjects = $is_data_breach ? sanitize_input($_POST['affected_data_subjects'] ?? '') : null;
    $data_types_affected = $is_data_breach ? sanitize_input($_POST['data_types_affected'] ?? '') : null;

    // Validation
    $errors = [];
    if (empty($incident_title)) $errors[] = "Incident title is required.";
    if (empty($incident_type)) $errors[] = "Incident type is required.";
    if (empty($incident_description)) $errors[] = "Incident description is required.";
    if (empty($severity)) $errors[] = "Severity level is required.";

    if (empty($errors)) {
        $query = "UPDATE incidents SET
                  incident_title = ?,
                  incident_type = ?,
                  incident_description = ?,
                  severity = ?,
                  status = ?,
                  incident_handler_id = ?,
                  dept_id = ?,
                  ropa_id = ?,
                  immediate_actions = ?,
                  is_data_breach = ?,
                  affected_data_subjects = ?,
                  data_types_affected = ?
                  WHERE incident_id = ? AND org_id = ?";

        db_query($query, [
            $incident_title, $incident_type, $incident_description, $severity,
            $status, $incident_handler_id, $dept_id, $ropa_id, $immediate_actions,
            $is_data_breach, $affected_data_subjects, $data_types_affected,
            $incident_id, $org_id
        ]);

        log_audit($org_id, $user_id, 'incident', $incident_id, 'update', "Updated incident: $incident_title");
        set_flash_message('Incident updated successfully!', 'success');
        redirect("incident_view.php?id=$incident_id");
    }
}

// Fetch departments
$dept_query = "SELECT dept_id, dept_name FROM departments WHERE org_id = ? ORDER BY dept_name";
$departments = db_fetch_all(db_query($dept_query, [$org_id]));

// Fetch users for handler assignment
$users_query = "SELECT user_id, first_name, last_name, email FROM users WHERE org_id = ? ORDER BY first_name, last_name";
$users = db_fetch_all(db_query($users_query, [$org_id]));

// Fetch ROPA entries
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? ORDER BY activity_name";
$ropa_entries = db_fetch_all(db_query($ropa_query, [$org_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Update Incident</title>
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
                        <h2>Update Incident</h2>
                        <h5>INC-<?php echo str_pad($incident_id, 5, '0', STR_PAD_LEFT); ?></h5>
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

    <form method="POST" action="incident_edit.php?id=<?php echo $incident_id; ?>" id="incidentForm">
        <div class="row">
            <div class="col-md-8">
                <!-- Basic Information -->
                <div class="card border-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-info-circle"></i> Basic Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Incident Title <span class="text-danger">*</span></label>
                            <input type="text" name="incident_title" class="form-control" required
                                   value="<?php echo htmlspecialchars($incident['incident_title']); ?>"
                                   placeholder="Brief descriptive title of the incident">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Incident Type <span class="text-danger">*</span></label>
                                    <select name="incident_type" class="form-control" required>
                                        <option value="">-- Select Type --</option>
                                        <?php
                                        $types = [
                                            'Unauthorized Access',
                                            'Data Breach',
                                            'Ransomware',
                                            'Phishing Attack',
                                            'Malware Infection',
                                            'Lost/Stolen Device',
                                            'Insider Threat',
                                            'System Failure',
                                            'DDoS Attack',
                                            'Physical Security',
                                            'Third-Party Breach',
                                            'Other'
                                        ];
                                        foreach ($types as $type) {
                                            $selected = ($incident['incident_type'] === $type) ? 'selected' : '';
                                            echo "<option value=\"$type\" $selected>$type</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Severity <span class="text-danger">*</span></label>
                                    <select name="severity" class="form-control" required>
                                        <option value="">-- Select Severity --</option>
                                        <?php
                                        $severities = ['low', 'medium', 'high', 'critical'];
                                        foreach ($severities as $sev) {
                                            $selected = ($incident['severity'] === $sev) ? 'selected' : '';
                                            echo "<option value=\"$sev\" $selected>" . ucfirst($sev) . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Incident Description <span class="text-danger">*</span></label>
                            <textarea name="incident_description" class="form-control" rows="5" required
                                      placeholder="Detailed description of what happened, when, and how it was discovered"><?php echo htmlspecialchars($incident['incident_description']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Department</label>
                                    <select name="dept_id" class="form-control">
                                        <option value="">-- Not Linked --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['dept_id']; ?>"
                                                    <?php echo ($incident['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Linked ROPA Entry</label>
                                    <select name="ropa_id" class="form-control">
                                        <option value="">-- Not Linked --</option>
                                        <?php foreach ($ropa_entries as $ropa): ?>
                                            <option value="<?php echo $ropa['ropa_id']; ?>"
                                                    <?php echo ($incident['ropa_id'] == $ropa['ropa_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">Link to affected processing activity if applicable</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Breach Details -->
                <div class="card border-danger" id="breachPanel" style="<?php echo $incident['is_data_breach'] ? '' : 'display:none;'; ?>">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-database"></i> Data Breach Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <i class="fa fa-warning"></i>
                            <strong>POTRAZ Notification Required:</strong> All data breaches must be reported to POTRAZ within 72 hours of discovery (CDPA s.26).
                        </div>

                        <div class="form-group">
                            <label>Affected Data Subjects (Approximate Count)</label>
                            <input type="text" name="affected_data_subjects" class="form-control"
                                   value="<?php echo htmlspecialchars($incident['affected_data_subjects'] ?? ''); ?>"
                                   placeholder="e.g., 500 customers, 25 employees">
                        </div>

                        <div class="form-group">
                            <label>Data Types Affected</label>
                            <textarea name="data_types_affected" class="form-control" rows="4"
                                      placeholder="List all types of personal data that were compromised (e.g., names, ID numbers, bank details, health records)"><?php echo htmlspecialchars($incident['data_types_affected'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Immediate Actions -->
                <div class="card border-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-flash"></i> Immediate Actions Taken</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Actions Taken to Contain the Incident</label>
                            <textarea name="immediate_actions" class="form-control" rows="5"
                                      placeholder="Document all immediate containment actions (e.g., systems isolated, passwords reset, access revoked, backups restored)"><?php echo htmlspecialchars($incident['immediate_actions'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Status & Assignment -->
                <div class="card border-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fa fa-tasks"></i> Status & Assignment</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_data_breach" id="breachCheckbox"
                                       value="1" <?php echo $incident['is_data_breach'] ? 'checked' : ''; ?>>
                                This is a Data Breach
                            </label>
                            <p class="form-text text-danger">
                                <i class="fa fa-info-circle"></i> Check if personal data was compromised
                            </p>
                        </div>

                        <div class="form-group">
                            <label>Incident Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-control" required>
                                <?php
                                $statuses = [
                                    'open' => 'Open',
                                    'investigating' => 'Investigating',
                                    'contained' => 'Contained',
                                    'resolved' => 'Resolved',
                                    'closed' => 'Closed'
                                ];
                                foreach ($statuses as $value => $label) {
                                    $selected = ($incident['status'] === $value) ? 'selected' : '';
                                    echo "<option value=\"$value\" $selected>$label</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Incident Handler</label>
                            <select name="incident_handler_id" class="form-control">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>"
                                            <?php echo ($incident['incident_handler_id'] == $user['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Assign to security/IT personnel</small>
                        </div>

                        <div class="well well-sm">
                            <p class="text-muted" style="margin: 0;">
                                <strong>Incident Date:</strong><br>
                                <?php echo date('d M Y, H:i', strtotime($incident['incident_date'])); ?>
                            </p>
                            <hr style="margin: 10px 0;">
                            <p class="text-muted" style="margin: 0;">
                                <strong>Reported By:</strong><br>
                                <?php
                                $reporter_query = "SELECT first_name, last_name FROM users WHERE user_id = ?";
                                $reporter = db_fetch_one(db_query($reporter_query, [$incident['reported_by']]));
                                echo htmlspecialchars($reporter['first_name'] . ' ' . $reporter['last_name']);
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fa fa-save"></i> Save Changes
                        </button>
                        <a href="incident_view.php?id=<?php echo $incident_id; ?>" class="btn btn-secondary btn-block">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Toggle breach panel
    $('#breachCheckbox').change(function() {
        if ($(this).is(':checked')) {
            $('#breachPanel').slideDown();
        } else {
            $('#breachPanel').slideUp();
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
