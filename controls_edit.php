<?php
/**
 * DPA Tool - Edit Control
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

// Get control ID
$control_id = intval($_GET['id'] ?? 0);

if (!$control_id) {
    set_flash_message('Invalid control ID', 'error');
    redirect('controls.php');
}

// Get control details
$control_query = "SELECT * FROM controls WHERE control_id = ? AND org_id = ?";
$control_stmt = db_query($control_query, [$control_id, $org_id]);
$control = db_fetch_one($control_stmt);

if (!$control) {
    set_flash_message('Control not found', 'error');
    redirect('controls.php');
}

// Get usage count
$usage_query = "SELECT COUNT(*) as count FROM risk_controls WHERE control_id = ?";
$usage_stmt = db_query($usage_query, [$control_id]);
$usage = db_fetch_one($usage_stmt);

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
        $query = "UPDATE controls SET
                  control_name = ?,
                  control_type = ?,
                  framework_ref = ?,
                  control_description = ?
                  WHERE control_id = ? AND org_id = ?";

        $stmt = db_query($query, [$control_name, $control_type, $framework_ref, $control_description, $control_id, $org_id]);

        if ($stmt) {
            log_audit($org_id, get_current_user_id(), 'control', $control_id, 'update');
            set_flash_message('Control updated successfully', 'success');
            redirect('controls.php');
        } else {
            $errors[] = 'Failed to update control';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Edit Control</title>
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
                        <h2>Edit Control</h2>
                        <h5>Update control information</h5>
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
                                <i class="fa fa-edit"></i> Control Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="controls_edit.php?id=<?php echo $control_id; ?>">
                                    <div class="form-group">
                                        <label>Control Name <span class="text-danger">*</span></label>
                                        <input type="text" name="control_name" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['control_name'] ?? $control['control_name']); ?>"
                                               required>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Control Type <span class="text-danger">*</span></label>
                                                <select name="control_type" class="form-control" required>
                                                    <option value="">-- Select Type --</option>
                                                    <?php
                                                    $selected_type = isset($_POST['control_type']) ? $_POST['control_type'] : $control['control_type'];
                                                    ?>
                                                    <option value="preventive" <?php echo $selected_type == 'preventive' ? 'selected' : ''; ?>>Preventive</option>
                                                    <option value="detective" <?php echo $selected_type == 'detective' ? 'selected' : ''; ?>>Detective</option>
                                                    <option value="corrective" <?php echo $selected_type == 'corrective' ? 'selected' : ''; ?>>Corrective</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Framework Reference</label>
                                                <input type="text" name="framework_ref" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['framework_ref'] ?? $control['framework_ref']); ?>"
                                                       placeholder="e.g., ISO 27001 A.9.4, CDPA s.26">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="control_description" class="form-control" rows="5"><?php echo htmlspecialchars($_POST['control_description'] ?? $control['control_description']); ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Control
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
                                <i class="fa fa-info-circle"></i> Control Details
                            </div>
                            <div class="panel-body">
                                <dl>
                                    <dt>Control ID</dt>
                                    <dd><?php echo $control['control_id']; ?></dd>

                                    <dt>Used in Risks</dt>
                                    <dd>
                                        <span class="badge badge-info"><?php echo $usage['count']; ?></span>
                                        <?php echo $usage['count'] == 1 ? 'risk' : 'risks'; ?>
                                    </dd>

                                    <dt>Created</dt>
                                    <dd><?php echo format_datetime($control['created_at'], 'd M Y H:i'); ?></dd>
                                </dl>
                            </div>
                        </div>

                        <?php if ($usage['count'] > 0): ?>
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <i class="fa fa-exclamation-triangle"></i> In Use
                            </div>
                            <div class="panel-body">
                                <p>This control is linked to <?php echo $usage['count']; ?> risk(s).</p>
                                <p>Changes to this control will affect all linked risks.</p>
                            </div>
                        </div>
                        <?php endif; ?>
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
