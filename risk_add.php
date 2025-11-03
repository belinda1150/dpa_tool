<?php
/**
 * Add Risk Page
 * Create new risk entry with assessment and treatment plan
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
    $risk_title = sanitize_input($_POST['risk_title'] ?? '');
    $risk_description = sanitize_input($_POST['risk_description'] ?? '');
    $risk_category = sanitize_input($_POST['risk_category'] ?? '');
    $dept_id = isset($_POST['dept_id']) && $_POST['dept_id'] !== '' ? intval($_POST['dept_id']) : null;
    $owner_id = isset($_POST['owner_id']) && $_POST['owner_id'] !== '' ? intval($_POST['owner_id']) : null;

    // Risk assessment
    $likelihood = intval($_POST['likelihood'] ?? 1);
    $impact = intval($_POST['impact'] ?? 1);
    $risk_source = sanitize_input($_POST['risk_source'] ?? '');

    // Treatment plan
    $treatment_strategy = sanitize_input($_POST['treatment_strategy'] ?? 'mitigate');
    $treatment_description = sanitize_input($_POST['treatment_description'] ?? '');
    $residual_likelihood = isset($_POST['residual_likelihood']) && $_POST['residual_likelihood'] !== '' ? intval($_POST['residual_likelihood']) : null;
    $residual_impact = isset($_POST['residual_impact']) && $_POST['residual_impact'] !== '' ? intval($_POST['residual_impact']) : null;

    // Timeline and status
    $review_date = !empty($_POST['review_date']) ? $_POST['review_date'] : null;
    $status = sanitize_input($_POST['status'] ?? 'open');

    // Validation
    $errors = [];

    if (empty($risk_title)) {
        $errors[] = 'Risk title is required.';
    }
    if (empty($risk_description)) {
        $errors[] = 'Risk description is required.';
    }
    if (empty($risk_category)) {
        $errors[] = 'Risk category is required.';
    }
    if ($likelihood < 1 || $likelihood > 5) {
        $errors[] = 'Likelihood must be between 1 and 5.';
    }
    if ($impact < 1 || $impact > 5) {
        $errors[] = 'Impact must be between 1 and 5.';
    }

    if (empty($errors)) {
        // Insert risk
        $query = "INSERT INTO risks (
                    org_id, dept_id, risk_title, risk_description, risk_category,
                    likelihood, impact,
                    residual_likelihood, residual_impact,
                    owner_id, due_date, status, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = db_query($query, [
            $org_id,
            $dept_id,
            $risk_title,
            $risk_description,
            $risk_category,
            $likelihood,
            $impact,
            $residual_likelihood,
            $residual_impact,
            $owner_id,
            $review_date,
            $status,
            $user_id
        ]);

        if ($stmt) {
            $risk_id = db_insert_id();

            // Log audit
            log_audit($org_id, $user_id, 'risk', $risk_id, 'create', "Created risk: $risk_title");

            // Create notification for risk owner if assigned
            if ($owner_id) {
                $inherent_score = $likelihood * $impact;
                $residual_score = ($residual_likelihood && $residual_impact) ? ($residual_likelihood * $residual_impact) : $inherent_score;

                $priority = 'normal';
                if ($residual_score >= 15) {
                    $priority = 'high';
                } elseif ($residual_score >= RISK_ACCEPTABLE_THRESHOLD) {
                    $priority = 'high';
                }

                create_notification(
                    $org_id,
                    $owner_id,
                    'risk_assigned',
                    'Risk Assigned to You',
                    "You have been assigned as the owner of risk: $risk_title",
                    'risk',
                    $risk_id,
                    "risk_view.php?id=$risk_id",
                    $priority
                );
            }

            set_flash_message('Risk added successfully!', 'success');
            redirect('risk_list.php');
        } else {
            $errors[] = 'Database error: Unable to add risk.';
        }
    }

    // Store errors in session
    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Fetch departments for dropdown
$dept_query = "SELECT dept_id, dept_name FROM departments WHERE org_id = ? ORDER BY dept_name";
$stmt = db_query($dept_query, [$org_id]);
$departments = db_fetch_all($stmt);

// Fetch users for risk owner dropdown
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
    <title><?php echo APP_NAME; ?> - Add Risk</title>
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
                    <i class="fa fa-plus-circle"></i> Add New Risk
                    <small>Register and assess organizational risk</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="risk_list.php">Risk Register</a></li>
                    <li class="active">Add Risk</li>
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

    <!-- Add Risk Form -->
    <form method="POST" action="risk_add.php" id="riskForm">
        <div class="row">
            <!-- Left Column -->
            <div class="col-md-6">

                <!-- Basic Information -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Basic Information</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="risk_title">Risk Title <span class="text-danger">*</span></label>
                            <input type="text" name="risk_title" id="risk_title" class="form-control" required
                                   value="<?php echo htmlspecialchars($form_data['risk_title'] ?? ''); ?>"
                                   placeholder="e.g., Unauthorized Access to Personal Data">
                        </div>

                        <div class="form-group">
                            <label for="risk_description">Risk Description <span class="text-danger">*</span></label>
                            <textarea name="risk_description" id="risk_description" class="form-control" rows="4" required
                                      placeholder="Detailed description of the risk, what could happen, and potential consequences..."><?php echo htmlspecialchars($form_data['risk_description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="risk_category">Risk Category <span class="text-danger">*</span></label>
                            <select name="risk_category" id="risk_category" class="form-control" required>
                                <option value="">-- Select Category --</option>
                                <?php
                                $categories = [
                                    'Data Security', 'Data Privacy', 'Compliance', 'Operational',
                                    'Reputational', 'Financial', 'Technical', 'Third-Party',
                                    'Cyber Security', 'Business Continuity', 'Legal', 'Strategic'
                                ];
                                foreach ($categories as $cat):
                                    $selected = (isset($form_data['risk_category']) && $form_data['risk_category'] === $cat) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="risk_source">Risk Source</label>
                            <input type="text" name="risk_source" id="risk_source" class="form-control"
                                   value="<?php echo htmlspecialchars($form_data['risk_source'] ?? ''); ?>"
                                   placeholder="e.g., DPIA Assessment, Audit Finding, Incident Report">
                            <p class="help-block">Where was this risk identified?</p>
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="dept_id">Department</label>
                                    <select name="dept_id" id="dept_id" class="form-control">
                                        <option value="">-- Select Department --</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <?php $selected = (isset($form_data['dept_id']) && $form_data['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>
                                            <option value="<?php echo $dept['dept_id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="owner_id">Risk Owner</label>
                                    <select name="owner_id" id="owner_id" class="form-control">
                                        <option value="">-- Assign Risk Owner --</option>
                                        <?php foreach ($users as $user): ?>
                                            <?php $selected = (isset($form_data['owner_id']) && $form_data['owner_id'] == $user['user_id']) ? 'selected' : ''; ?>
                                            <option value="<?php echo $user['user_id']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="help-block">Person responsible for managing this risk</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Risk Assessment -->
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-line-chart"></i> Inherent Risk Assessment (Before Treatment)</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="likelihood">Likelihood <span class="text-danger">*</span></label>
                                    <select name="likelihood" id="likelihood" class="form-control" required>
                                        <?php
                                        $likelihood_labels = [
                                            1 => '1 - Very Low (Rare)',
                                            2 => '2 - Low (Unlikely)',
                                            3 => '3 - Medium (Possible)',
                                            4 => '4 - High (Likely)',
                                            5 => '5 - Very High (Almost Certain)'
                                        ];
                                        foreach ($likelihood_labels as $val => $label):
                                            $selected = (isset($form_data['likelihood']) && $form_data['likelihood'] == $val) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="impact">Impact <span class="text-danger">*</span></label>
                                    <select name="impact" id="impact" class="form-control" required>
                                        <?php
                                        $impact_labels = [
                                            1 => '1 - Very Low (Negligible)',
                                            2 => '2 - Low (Minor)',
                                            3 => '3 - Medium (Moderate)',
                                            4 => '4 - High (Major)',
                                            5 => '5 - Very High (Catastrophic)'
                                        ];
                                        foreach ($impact_labels as $val => $label):
                                            $selected = (isset($form_data['impact']) && $form_data['impact'] == $val) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $val; ?>" <?php echo $selected; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info" id="inherentScore" style="margin-bottom: 0;">
                            <strong>Inherent Risk Score:</strong>
                            <span class="pull-right">
                                <span class="label label-default" id="inherentScoreValue">1</span>
                                <span id="inherentScoreLabel">Low Risk</span>
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column -->
            <div class="col-md-6">

                <!-- Treatment Plan -->
                <div class="panel panel-success">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-shield"></i> Risk Treatment Plan</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="treatment_strategy">Treatment Strategy <span class="text-danger">*</span></label>
                            <select name="treatment_strategy" id="treatment_strategy" class="form-control" required>
                                <option value="mitigate" selected>Mitigate - Reduce likelihood or impact</option>
                                <option value="accept">Accept - Accept the risk as-is</option>
                                <option value="transfer">Transfer - Transfer to third party (insurance, outsource)</option>
                                <option value="avoid">Avoid - Eliminate the risk by changing processes</option>
                            </select>
                        </div>

                        <div class="form-group" id="treatmentDescriptionGroup">
                            <label for="treatment_description">Treatment Description <span class="text-danger">*</span></label>
                            <textarea name="treatment_description" id="treatment_description" class="form-control" rows="4" required
                                      placeholder="Describe the specific actions, controls, or measures to address this risk..."><?php echo htmlspecialchars($form_data['treatment_description'] ?? ''); ?></textarea>
                        </div>

                        <div id="residualRiskGroup">
                            <h5><strong>Residual Risk (After Treatment):</strong></h5>
                            <p class="text-muted">Expected risk level after implementing treatment measures</p>

                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="residual_likelihood">Residual Likelihood</label>
                                        <select name="residual_likelihood" id="residual_likelihood" class="form-control">
                                            <option value="">-- Not Assessed --</option>
                                            <?php foreach ($likelihood_labels as $val => $label): ?>
                                                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="residual_impact">Residual Impact</label>
                                        <select name="residual_impact" id="residual_impact" class="form-control">
                                            <option value="">-- Not Assessed --</option>
                                            <?php foreach ($impact_labels as $val => $label): ?>
                                                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-success" id="residualScore" style="display: none; margin-bottom: 0;">
                                <strong>Residual Risk Score:</strong>
                                <span class="pull-right">
                                    <span class="label label-default" id="residualScoreValue">0</span>
                                    <span id="residualScoreLabel"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status & Timeline -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-calendar"></i> Status & Timeline</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="open" selected>Open - Risk identified, treatment in progress</option>
                                <option value="mitigated">Mitigated - Treatment measures implemented</option>
                                <option value="accepted">Accepted - Risk accepted by management</option>
                                <option value="closed">Closed - Risk no longer applicable</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="review_date">Next Review Date</label>
                            <input type="date" name="review_date" id="review_date" class="form-control"
                                   value="<?php echo htmlspecialchars($form_data['review_date'] ?? ''); ?>"
                                   min="<?php echo date('Y-m-d'); ?>">
                            <p class="help-block">Schedule when this risk should be reviewed again</p>
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
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fa fa-save"></i> Save Risk
                        </button>
                        <a href="risk_list.php" class="btn btn-default btn-lg">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

</div>

<script>
// Calculate and display inherent risk score
function updateInherentScore() {
    var likelihood = parseInt($('#likelihood').val()) || 1;
    var impact = parseInt($('#impact').val()) || 1;
    var score = likelihood * impact;

    var scoreClass = 'default';
    var scoreLabel = 'Low Risk';

    if (score >= 15) {
        scoreClass = 'danger';
        scoreLabel = 'Critical Risk';
    } else if (score >= 6) {
        scoreClass = 'warning';
        scoreLabel = 'High Risk';
    } else if (score >= 3) {
        scoreClass = 'info';
        scoreLabel = 'Medium Risk';
    } else {
        scoreClass = 'success';
        scoreLabel = 'Low Risk';
    }

    $('#inherentScoreValue').removeClass().addClass('label label-' + scoreClass).text(score + '/25');
    $('#inherentScoreLabel').text(scoreLabel);
}

// Calculate and display residual risk score
function updateResidualScore() {
    var likelihood = parseInt($('#residual_likelihood').val());
    var impact = parseInt($('#residual_impact').val());

    if (likelihood && impact) {
        var score = likelihood * impact;

        var scoreClass = 'default';
        var scoreLabel = 'Low Risk';

        if (score >= 15) {
            scoreClass = 'danger';
            scoreLabel = 'Critical Risk';
        } else if (score >= 6) {
            scoreClass = 'warning';
            scoreLabel = 'High Risk';
        } else if (score >= 3) {
            scoreClass = 'info';
            scoreLabel = 'Medium Risk';
        } else {
            scoreClass = 'success';
            scoreLabel = 'Low Risk';
        }

        $('#residualScoreValue').removeClass().addClass('label label-' + scoreClass).text(score + '/25');
        $('#residualScoreLabel').text(scoreLabel);
        $('#residualScore').show();
    } else {
        $('#residualScore').hide();
    }
}

// Toggle treatment description based on strategy
function updateTreatmentFields() {
    var strategy = $('#treatment_strategy').val();

    if (strategy === 'accept') {
        $('#treatment_description').attr('placeholder', 'Explain why this risk is being accepted and who approved the acceptance...');
        $('#residualRiskGroup').hide();
    } else if (strategy === 'avoid') {
        $('#treatment_description').attr('placeholder', 'Describe how the risk will be eliminated by changing or stopping the activity...');
        $('#residualRiskGroup').show();
    } else if (strategy === 'transfer') {
        $('#treatment_description').attr('placeholder', 'Describe how the risk will be transferred (insurance, outsourcing, contracts, etc.)...');
        $('#residualRiskGroup').show();
    } else {
        $('#treatment_description').attr('placeholder', 'Describe the specific actions, controls, or measures to address this risk...');
        $('#residualRiskGroup').show();
    }
}

$(document).ready(function() {
    // Initialize score displays
    updateInherentScore();
    updateResidualScore();
    updateTreatmentFields();

    // Update scores when values change
    $('#likelihood, #impact').on('change', updateInherentScore);
    $('#residual_likelihood, #residual_impact').on('change', updateResidualScore);
    $('#treatment_strategy').on('change', updateTreatmentFields);

    // Form validation
    $('#riskForm').on('submit', function(e) {
        var title = $('#risk_title').val().trim();
        var description = $('#risk_description').val().trim();
        var category = $('#risk_category').val();
        var treatment = $('#treatment_description').val().trim();

        if (!title || !description || !category || !treatment) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });
});
</script>


            </div>
        </div>
    </div>

<script src="assets/js/jquery-1.10.2.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
