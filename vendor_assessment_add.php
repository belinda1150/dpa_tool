<?php
/**
 * DPA Tool - Add Vendor Risk Assessment
 * Create risk assessment with 5-category scoring
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_type = sanitize_input($_POST['assessment_type'] ?? 'initial');
    $assessment_date = $_POST['assessment_date'] ?? date('Y-m-d');

    // Risk scores
    $data_security_likelihood = intval($_POST['data_security_likelihood'] ?? 0);
    $data_security_impact = intval($_POST['data_security_impact'] ?? 0);
    $compliance_likelihood = intval($_POST['compliance_likelihood'] ?? 0);
    $compliance_impact = intval($_POST['compliance_impact'] ?? 0);
    $operational_likelihood = intval($_POST['operational_likelihood'] ?? 0);
    $operational_impact = intval($_POST['operational_impact'] ?? 0);
    $financial_likelihood = intval($_POST['financial_likelihood'] ?? 0);
    $financial_impact = intval($_POST['financial_impact'] ?? 0);
    $reputational_likelihood = intval($_POST['reputational_likelihood'] ?? 0);
    $reputational_impact = intval($_POST['reputational_impact'] ?? 0);

    // Treatment
    $treatment_strategy = sanitize_input($_POST['treatment_strategy'] ?? 'mitigate');
    $treatment_plan = sanitize_input($_POST['treatment_plan'] ?? '');
    $treatment_owner_id = !empty($_POST['treatment_owner_id']) ? intval($_POST['treatment_owner_id']) : null;
    $treatment_due_date = !empty($_POST['treatment_due_date']) ? $_POST['treatment_due_date'] : null;

    // Findings
    $key_findings = sanitize_input($_POST['key_findings'] ?? '');
    $recommendations = sanitize_input($_POST['recommendations'] ?? '');

    // Validation
    $errors = [];
    $categories = ['data_security', 'compliance', 'operational', 'financial', 'reputational'];

    foreach ($categories as $cat) {
        $l = intval($_POST[$cat . '_likelihood'] ?? 0);
        $i = intval($_POST[$cat . '_impact'] ?? 0);
        if ($l < 1 || $l > 5) {
            $errors[] = ucfirst(str_replace('_', ' ', $cat)) . ' likelihood must be between 1 and 5.';
        }
        if ($i < 1 || $i > 5) {
            $errors[] = ucfirst(str_replace('_', ' ', $cat)) . ' impact must be between 1 and 5.';
        }
    }

    if (empty($errors)) {
        // Calculate overall risk score (average of category scores)
        $scores = [];
        foreach ($categories as $cat) {
            $l = intval($_POST[$cat . '_likelihood']);
            $i = intval($_POST[$cat . '_impact']);
            $scores[] = $l * $i;
        }
        $inherent_risk_score = array_sum($scores) / count($scores);

        // Determine risk level
        if ($inherent_risk_score >= 20) {
            $inherent_risk_level = 'critical';
        } elseif ($inherent_risk_score >= 15) {
            $inherent_risk_level = 'high';
        } elseif ($inherent_risk_score >= 8) {
            $inherent_risk_level = 'medium';
        } else {
            $inherent_risk_level = 'low';
        }

        // Generate reference
        $assessment_ref = generate_reference('VRA');

        // Insert assessment
        $query = "INSERT INTO vendor_risk_assessments (
                    org_id, vendor_id, assessment_ref, assessment_type, assessment_date,
                    data_security_likelihood, data_security_impact,
                    compliance_likelihood, compliance_impact,
                    operational_likelihood, operational_impact,
                    financial_likelihood, financial_impact,
                    reputational_likelihood, reputational_impact,
                    inherent_risk_score, inherent_risk_level,
                    treatment_strategy, treatment_plan, treatment_owner_id, treatment_due_date,
                    key_findings, recommendations, status, assessed_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)";

        $stmt = db_query($query, [
            $org_id, $vendor_id, $assessment_ref, $assessment_type, $assessment_date,
            $data_security_likelihood, $data_security_impact,
            $compliance_likelihood, $compliance_impact,
            $operational_likelihood, $operational_impact,
            $financial_likelihood, $financial_impact,
            $reputational_likelihood, $reputational_impact,
            $inherent_risk_score, $inherent_risk_level,
            $treatment_strategy, $treatment_plan, $treatment_owner_id, $treatment_due_date,
            $key_findings, $recommendations, $user_id
        ]);

        if ($stmt) {
            $assessment_id = db_insert_id();

            // Log audit
            log_audit($org_id, $user_id, 'vendor_assessment', $assessment_id, 'create');

            // Update vendor last review date and next review date
            $next_review = date('Y-m-d', strtotime('+' . $vendor['review_frequency_months'] . ' months'));
            db_query("UPDATE vendors SET last_review_date = ?, next_review_date = ? WHERE vendor_id = ?",
                    [$assessment_date, $next_review, $vendor_id]);

            // Notify if high risk
            if ($inherent_risk_level === 'critical' || $inherent_risk_level === 'high') {
                $dpo_query = "SELECT u.user_id FROM users u
                              LEFT JOIN roles r ON u.role_id = r.role_id
                              WHERE u.org_id = ? AND r.role_name IN ('Admin', 'DPO') AND u.status = 'active'";
                $dpo_stmt = db_query($dpo_query, [$org_id]);
                while ($dpo = db_fetch_one($dpo_stmt)) {
                    create_notification(
                        $org_id,
                        $dpo['user_id'],
                        'vendor_high_risk',
                        'High Risk Vendor Assessment',
                        "Vendor '{$vendor['vendor_name']}' has been assessed as {$inherent_risk_level} risk.",
                        'vendor',
                        $vendor_id,
                        "vendor_view.php?id=$vendor_id",
                        'high'
                    );
                }
            }

            set_flash_message('Risk assessment completed successfully!', 'success');
            redirect('vendor_view.php?id=' . $vendor_id);
        } else {
            $errors[] = 'Database error: Unable to save assessment.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
    }
}

// Fetch users for treatment owner dropdown
$users_query = "SELECT user_id, first_name, last_name FROM users WHERE org_id = ? AND status = 'active' ORDER BY first_name";
$stmt = db_query($users_query, [$org_id]);
$users = db_fetch_all($stmt);

// Get form data
$form_errors = $_SESSION['form_errors'] ?? [];
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_data']);

// Likelihood and impact labels
$likelihood_labels = [
    1 => '1 - Very Low (Rare)',
    2 => '2 - Low (Unlikely)',
    3 => '3 - Medium (Possible)',
    4 => '4 - High (Likely)',
    5 => '5 - Very High (Almost Certain)'
];

$impact_labels = [
    1 => '1 - Very Low (Negligible)',
    2 => '2 - Low (Minor)',
    3 => '3 - Medium (Moderate)',
    4 => '4 - High (Major)',
    5 => '5 - Very High (Catastrophic)'
];

$assessment_types = [
    'initial' => 'Initial Assessment',
    'periodic' => 'Periodic Review',
    'incident_triggered' => 'Incident Triggered',
    'contract_renewal' => 'Contract Renewal'
];

$risk_categories = [
    'data_security' => ['label' => 'Data Security', 'description' => 'Risk of data breaches, unauthorized access, or data loss'],
    'compliance' => ['label' => 'Compliance', 'description' => 'Risk of regulatory non-compliance or legal violations'],
    'operational' => ['label' => 'Operational', 'description' => 'Risk of service disruption or operational failures'],
    'financial' => ['label' => 'Financial', 'description' => 'Risk of financial loss or contractual penalties'],
    'reputational' => ['label' => 'Reputational', 'description' => 'Risk of damage to reputation or stakeholder trust']
];
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Vendor Risk Assessment</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .risk-category-panel { margin-bottom: 15px; }
        .risk-score-display {
            text-align: center;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
            margin-top: 10px;
        }
        .risk-score-display .score-value {
            font-size: 24px;
            font-weight: bold;
        }
        .overall-score {
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .overall-score .big-score {
            font-size: 48px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="row">
                    <div class="col-md-12">
                        <h2>Vendor Risk Assessment</h2>
                        <h5><?php echo htmlspecialchars($vendor['vendor_name']); ?> (<?php echo htmlspecialchars($vendor['vendor_ref']); ?>)</h5>
                    </div>
                </div>
                <hr />

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

                <form method="POST" action="vendor_assessment_add.php?vendor_id=<?php echo $vendor_id; ?>" id="assessmentForm">
                    <div class="row">
                        <!-- Left Column - Risk Assessment -->
                        <div class="col-md-8">

                            <!-- Assessment Info -->
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Assessment Information</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="assessment_type">Assessment Type <span class="text-danger">*</span></label>
                                                <select name="assessment_type" id="assessment_type" class="form-control" required>
                                                    <?php foreach ($assessment_types as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data['assessment_type'] ?? 'initial') === $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="assessment_date">Assessment Date <span class="text-danger">*</span></label>
                                                <input type="date" name="assessment_date" id="assessment_date" class="form-control" required
                                                       value="<?php echo htmlspecialchars($form_data['assessment_date'] ?? date('Y-m-d')); ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Risk Categories -->
                            <?php foreach ($risk_categories as $key => $category): ?>
                            <div class="panel panel-warning risk-category-panel">
                                <div class="panel-heading">
                                    <h3 class="panel-title">
                                        <i class="fa fa-exclamation-triangle"></i> <?php echo $category['label']; ?>
                                    </h3>
                                </div>
                                <div class="panel-body">
                                    <p class="text-muted"><?php echo $category['description']; ?></p>
                                    <div class="row">
                                        <div class="col-sm-5">
                                            <div class="form-group">
                                                <label>Likelihood <span class="text-danger">*</span></label>
                                                <select name="<?php echo $key; ?>_likelihood" id="<?php echo $key; ?>_likelihood"
                                                        class="form-control likelihood-select" data-category="<?php echo $key; ?>" required>
                                                    <?php foreach ($likelihood_labels as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data[$key . '_likelihood'] ?? '3') == $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-5">
                                            <div class="form-group">
                                                <label>Impact <span class="text-danger">*</span></label>
                                                <select name="<?php echo $key; ?>_impact" id="<?php echo $key; ?>_impact"
                                                        class="form-control impact-select" data-category="<?php echo $key; ?>" required>
                                                    <?php foreach ($impact_labels as $val => $label): ?>
                                                        <option value="<?php echo $val; ?>"
                                                            <?php echo ($form_data[$key . '_impact'] ?? '3') == $val ? 'selected' : ''; ?>>
                                                            <?php echo $label; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <div class="risk-score-display">
                                                <div class="score-value">
                                                    <span class="label label-default" id="<?php echo $key; ?>_score">9</span>
                                                </div>
                                                <small>Score</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <!-- Treatment Plan -->
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-shield"></i> Risk Treatment Plan</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="treatment_strategy">Treatment Strategy <span class="text-danger">*</span></label>
                                                <select name="treatment_strategy" id="treatment_strategy" class="form-control" required>
                                                    <option value="mitigate">Mitigate - Reduce risk through controls</option>
                                                    <option value="accept">Accept - Accept the risk</option>
                                                    <option value="transfer">Transfer - Transfer risk (insurance, contracts)</option>
                                                    <option value="avoid">Avoid - Terminate vendor relationship</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="treatment_owner_id">Treatment Owner</label>
                                                <select name="treatment_owner_id" id="treatment_owner_id" class="form-control">
                                                    <option value="">-- Select Owner --</option>
                                                    <?php foreach ($users as $user): ?>
                                                        <option value="<?php echo $user['user_id']; ?>">
                                                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="treatment_plan">Treatment Plan</label>
                                        <textarea name="treatment_plan" id="treatment_plan" class="form-control" rows="3"
                                                  placeholder="Describe specific measures to address identified risks..."><?php echo htmlspecialchars($form_data['treatment_plan'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="treatment_due_date">Treatment Due Date</label>
                                        <input type="date" name="treatment_due_date" id="treatment_due_date" class="form-control"
                                               value="<?php echo htmlspecialchars($form_data['treatment_due_date'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Findings -->
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-file-text"></i> Findings & Recommendations</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="form-group">
                                        <label for="key_findings">Key Findings</label>
                                        <textarea name="key_findings" id="key_findings" class="form-control" rows="4"
                                                  placeholder="Document key findings from the assessment..."><?php echo htmlspecialchars($form_data['key_findings'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="recommendations">Recommendations</label>
                                        <textarea name="recommendations" id="recommendations" class="form-control" rows="4"
                                                  placeholder="Recommendations for risk mitigation..."><?php echo htmlspecialchars($form_data['recommendations'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column - Summary -->
                        <div class="col-md-4">

                            <!-- Overall Risk Score -->
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-warning"></i> Overall Risk Score</h3>
                                </div>
                                <div class="panel-body">
                                    <div class="overall-score" id="overallScoreDisplay">
                                        <div class="big-score">
                                            <span class="label label-warning" id="overallScoreValue">9.0</span>
                                        </div>
                                        <p id="overallScoreLabel">Medium Risk</p>
                                    </div>

                                    <table class="table table-condensed">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th class="text-center">Score</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($risk_categories as $key => $category): ?>
                                            <tr>
                                                <td><?php echo $category['label']; ?></td>
                                                <td class="text-center">
                                                    <span class="label label-default category-score-display" id="<?php echo $key; ?>_score_summary">9</span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Vendor Info -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-building"></i> Vendor Information</h3>
                                </div>
                                <div class="panel-body">
                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($vendor['vendor_name']); ?></p>
                                    <p><strong>Type:</strong> <?php echo ucwords(str_replace('_', ' ', $vendor['vendor_type'])); ?></p>
                                    <p><strong>Country:</strong> <?php echo htmlspecialchars($vendor['country'] ?? 'N/A'); ?></p>
                                    <p><strong>Criticality:</strong> <?php echo ucfirst($vendor['criticality']); ?></p>
                                    <p><strong>DPA Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $vendor['dpa_status'])); ?></p>
                                </div>
                            </div>

                            <!-- Risk Level Guide -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Risk Level Guide</h3>
                                </div>
                                <div class="panel-body">
                                    <p><span class="label label-danger">Critical</span> Score >= 20</p>
                                    <p><span class="label label-warning">High</span> Score 15-19</p>
                                    <p><span class="label label-info">Medium</span> Score 8-14</p>
                                    <p><span class="label label-success">Low</span> Score < 8</p>
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
                                        <i class="fa fa-save"></i> Complete Assessment
                                    </button>
                                    <a href="vendor_view.php?id=<?php echo $vendor_id; ?>" class="btn btn-default btn-lg">
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

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/custom.js"></script>
    <script>
        var categories = ['data_security', 'compliance', 'operational', 'financial', 'reputational'];

        function getScoreClass(score) {
            if (score >= 20) return 'danger';
            if (score >= 15) return 'warning';
            if (score >= 8) return 'info';
            return 'success';
        }

        function getScoreLabel(score) {
            if (score >= 20) return 'Critical Risk';
            if (score >= 15) return 'High Risk';
            if (score >= 8) return 'Medium Risk';
            return 'Low Risk';
        }

        function updateCategoryScore(category) {
            var likelihood = parseInt($('#' + category + '_likelihood').val()) || 1;
            var impact = parseInt($('#' + category + '_impact').val()) || 1;
            var score = likelihood * impact;

            var scoreClass = getScoreClass(score);
            $('#' + category + '_score').removeClass().addClass('label label-' + scoreClass).text(score);
            $('#' + category + '_score_summary').removeClass().addClass('label label-' + scoreClass).text(score);
        }

        function updateOverallScore() {
            var totalScore = 0;
            categories.forEach(function(cat) {
                var likelihood = parseInt($('#' + cat + '_likelihood').val()) || 1;
                var impact = parseInt($('#' + cat + '_impact').val()) || 1;
                totalScore += (likelihood * impact);
            });

            var avgScore = totalScore / categories.length;
            var scoreClass = getScoreClass(avgScore);
            var scoreLabel = getScoreLabel(avgScore);

            $('#overallScoreValue').removeClass().addClass('label label-' + scoreClass).text(avgScore.toFixed(1));
            $('#overallScoreLabel').text(scoreLabel);
        }

        $(document).ready(function() {
            // Initialize scores
            categories.forEach(function(cat) {
                updateCategoryScore(cat);
            });
            updateOverallScore();

            // Update on change
            $('.likelihood-select, .impact-select').on('change', function() {
                var category = $(this).data('category');
                updateCategoryScore(category);
                updateOverallScore();
            });
        });
    </script>
</body>
</html>
