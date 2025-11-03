<?php
/**
 * DPA Tool - Add New Control
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('DPO');

$org_id = get_current_org_id();
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $control_name = sanitize_input($_POST['control_name'] ?? '');
    $control_type = sanitize_input($_POST['control_type'] ?? '');
    $framework_ref = sanitize_input($_POST['framework_ref'] ?? '');
    $control_description = sanitize_input($_POST['control_description'] ?? '');

    // Validation
    if (empty($control_name)) {
        $errors[] = 'Control name is required';
    }
    if (empty($control_type)) {
        $errors[] = 'Control type is required';
    }

    if (empty($errors)) {
        $query = "INSERT INTO controls (org_id, control_name, control_type, framework_ref, control_description)
                  VALUES (?, ?, ?, ?, ?)";

        $stmt = db_query($query, [$org_id, $control_name, $control_type, $framework_ref, $control_description]);

        if ($stmt) {
            $control_id = db_insert_id();
            log_audit($org_id, get_current_user_id(), 'control', $control_id, 'create');
            set_flash_message('Control created successfully', 'success');
            redirect('controls.php');
        } else {
            $errors[] = 'Failed to create control';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add New Control</title>
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
                        <h2>Add New Control</h2>
                        <h5>Create a new security or compliance control</h5>
                    </div>
                </div>
                <hr />

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>Please fix the following errors:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-shield"></i> Control Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="controls_add.php">
                                    <div class="form-group">
                                        <label>Control Name <span class="text-danger">*</span></label>
                                        <input type="text" name="control_name" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['control_name'] ?? ''); ?>"
                                               placeholder="e.g., Encryption at Rest, Access Control Policy, Firewall Rules"
                                               required>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Control Type <span class="text-danger">*</span></label>
                                                <select name="control_type" class="form-control" required>
                                                    <option value="">-- Select Type --</option>
                                                    <option value="preventive" <?php echo (isset($_POST['control_type']) && $_POST['control_type'] == 'preventive') ? 'selected' : ''; ?>>Preventive</option>
                                                    <option value="detective" <?php echo (isset($_POST['control_type']) && $_POST['control_type'] == 'detective') ? 'selected' : ''; ?>>Detective</option>
                                                    <option value="corrective" <?php echo (isset($_POST['control_type']) && $_POST['control_type'] == 'corrective') ? 'selected' : ''; ?>>Corrective</option>
                                                </select>
                                                <small class="help-block">
                                                    <strong>Preventive:</strong> Prevents incidents<br>
                                                    <strong>Detective:</strong> Detects incidents<br>
                                                    <strong>Corrective:</strong> Corrects after incident
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Framework Reference</label>
                                                <input type="text" name="framework_ref" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['framework_ref'] ?? ''); ?>"
                                                       placeholder="e.g., ISO 27001 A.9.4, CDPA s.26">
                                                <small class="help-block">ISO 27001, NIST, CDPA reference</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="control_description" class="form-control" rows="5"
                                                  placeholder="Describe how this control works and what it protects against"><?php echo htmlspecialchars($_POST['control_description'] ?? ''); ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Create Control
                                        </button>
                                        <a href="controls.php" class="btn btn-default">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> About Controls
                            </div>
                            <div class="panel-body">
                                <p><strong>Controls</strong> are security measures that reduce risk.</p>

                                <h5>Control Types:</h5>
                                <dl>
                                    <dt>Preventive</dt>
                                    <dd>Stop incidents before they happen (e.g., firewalls, encryption, access controls)</dd>

                                    <dt>Detective</dt>
                                    <dd>Detect incidents when they occur (e.g., logging, monitoring, audits)</dd>

                                    <dt>Corrective</dt>
                                    <dd>Fix issues after detection (e.g., incident response, backups, patches)</dd>
                                </dl>

                                <h5>Examples:</h5>
                                <ul>
                                    <li>Multi-factor authentication (Preventive)</li>
                                    <li>SIEM monitoring (Detective)</li>
                                    <li>Disaster recovery plan (Corrective)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
