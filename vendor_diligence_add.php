<?php
/**
 * DPA Tool - Add Vendor Due Diligence Record
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

$vendor_id = isset($_GET['vendor_id']) ? intval($_GET['vendor_id']) : 0;

if (!$vendor_id) {
    set_flash_message('Invalid vendor ID.', 'error');
    redirect('vendor_list.php');
}

// Fetch vendor
$query = "SELECT * FROM vendors WHERE vendor_id = ? AND org_id = ?";
$stmt = db_query($query, [$vendor_id, $org_id]);
$vendor = db_fetch_one($stmt);

if (!$vendor) {
    set_flash_message('Vendor not found.', 'error');
    redirect('vendor_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $questionnaire_type = sanitize_input($_POST['questionnaire_type'] ?? '');
    $questionnaire_name = sanitize_input($_POST['questionnaire_name'] ?? '');
    $sent_date = !empty($_POST['sent_date']) ? $_POST['sent_date'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $completed_date = !empty($_POST['completed_date']) ? $_POST['completed_date'] : null;
    $score = !empty($_POST['score']) ? floatval($_POST['score']) : null;
    $pass_threshold = floatval($_POST['pass_threshold'] ?? 70);
    $result = sanitize_input($_POST['result'] ?? 'pending');
    $findings = sanitize_input($_POST['findings'] ?? '');
    $remediation_required = isset($_POST['remediation_required']) ? 1 : 0;
    $remediation_notes = sanitize_input($_POST['remediation_notes'] ?? '');
    $remediation_due_date = !empty($_POST['remediation_due_date']) ? $_POST['remediation_due_date'] : null;

    $errors = [];
    if (empty($questionnaire_type)) $errors[] = 'Questionnaire type is required.';
    if (empty($questionnaire_name)) $errors[] = 'Questionnaire name is required.';

    if (empty($errors)) {
        $query = "INSERT INTO vendor_due_diligence (org_id, vendor_id, questionnaire_type, questionnaire_name,
                  sent_date, due_date, completed_date, score, pass_threshold, result, findings,
                  remediation_required, remediation_notes, remediation_due_date, created_by)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = db_query($query, [
            $org_id, $vendor_id, $questionnaire_type, $questionnaire_name,
            $sent_date, $due_date, $completed_date, $score, $pass_threshold, $result, $findings,
            $remediation_required, $remediation_notes, $remediation_due_date, $user_id
        ]);

        if ($stmt) {
            log_audit($org_id, $user_id, 'vendor_diligence', db_insert_id(), 'create');
            set_flash_message('Due diligence record added successfully!', 'success');
            redirect('vendor_view.php?id=' . $vendor_id);
        } else {
            $errors[] = 'Database error.';
        }
    }

    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
}

$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

$questionnaire_types = [
    'security' => 'Security Assessment',
    'privacy' => 'Privacy Assessment',
    'business_continuity' => 'Business Continuity',
    'financial' => 'Financial Assessment',
    'custom' => 'Custom Questionnaire'
];

$result_options = [
    'pending' => 'Pending',
    'passed' => 'Passed',
    'failed' => 'Failed',
    'conditional' => 'Conditional Pass'
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add Due Diligence</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
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
                        <h2>Add Due Diligence Record</h2>
                        <h5><?php echo htmlspecialchars($vendor['vendor_name']); ?></h5>
                    </div>
                </div>
                <hr />

                <?php if (!empty($form_errors)): ?>
                <div class="alert alert-danger">
                    <ul><?php foreach ($form_errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fa fa-clipboard"></i> Questionnaire Details</h3>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label>Questionnaire Type <span class="text-danger">*</span></label>
                                        <select name="questionnaire_type" class="form-control" required>
                                            <option value="">-- Select Type --</option>
                                            <?php foreach ($questionnaire_types as $val => $label): ?>
                                            <option value="<?php echo $val; ?>" <?php echo ($form_data['questionnaire_type'] ?? '') === $val ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Questionnaire Name <span class="text-danger">*</span></label>
                                        <input type="text" name="questionnaire_name" class="form-control" required
                                               value="<?php echo htmlspecialchars($form_data['questionnaire_name'] ?? ''); ?>"
                                               placeholder="e.g., Annual Security Assessment 2024">
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Sent Date</label>
                                                <input type="date" name="sent_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['sent_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>Due Date</label>
                                                <input type="date" name="due_date" class="form-control"
                                                       value="<?php echo htmlspecialchars($form_data['due_date'] ?? ''); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Completed Date</label>
                                        <input type="date" name="completed_date" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['completed_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header">
                                    <h3 class="card-title"><i class="fa fa-check-circle"></i> Results</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Score (%)</label>
                                                <input type="number" name="score" class="form-control" min="0" max="100" step="0.1"
                                                       value="<?php echo htmlspecialchars($form_data['score'] ?? ''); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Pass Threshold (%)</label>
                                                <input type="number" name="pass_threshold" class="form-control" min="0" max="100"
                                                       value="<?php echo htmlspecialchars($form_data['pass_threshold'] ?? '70'); ?>">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>Result</label>
                                                <select name="result" class="form-control">
                                                    <?php foreach ($result_options as $val => $label): ?>
                                                    <option value="<?php echo $val; ?>" <?php echo ($form_data['result'] ?? 'pending') === $val ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Findings</label>
                                        <textarea name="findings" class="form-control" rows="3"
                                                  placeholder="Key findings from the assessment..."><?php echo htmlspecialchars($form_data['findings'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="remediation_required" value="1"
                                                <?php echo !empty($form_data['remediation_required']) ? 'checked' : ''; ?>>
                                            <strong>Remediation Required</strong>
                                        </label>
                                    </div>

                                    <div id="remediation_fields" style="display: none;">
                                        <div class="form-group">
                                            <label>Remediation Notes</label>
                                            <textarea name="remediation_notes" class="form-control" rows="2"><?php echo htmlspecialchars($form_data['remediation_notes'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="form-group">
                                            <label>Remediation Due Date</label>
                                            <input type="date" name="remediation_due_date" class="form-control"
                                                   value="<?php echo htmlspecialchars($form_data['remediation_due_date'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-success btn-lg"><i class="fa fa-save"></i> Save Record</button>
                            <a href="vendor_view.php?id=<?php echo $vendor_id; ?>" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </div>
                </form>
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
            function toggleRemediation() {
                if ($('input[name="remediation_required"]').is(':checked')) {
                    $('#remediation_fields').show();
                } else {
                    $('#remediation_fields').hide();
                }
            }
            toggleRemediation();
            $('input[name="remediation_required"]').on('change', toggleRemediation);
        });
    </script>
</body>
</html>
