<?php
/**
 * DPA Tool - DPIA Wizard (7 Steps)
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Check if editing existing DPIA
$dpia_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Initialize or load DPIA
if ($dpia_id) {
    $query = "SELECT * FROM dpia WHERE dpia_id = ? AND org_id = ?";
    $stmt = db_query($query, [$dpia_id, $org_id]);
    $dpia = db_fetch_one($stmt);

    if (!$dpia) {
        set_flash_message('DPIA not found.', 'error');
        redirect('dpia_list.php');
    }
} else {
    $dpia = null;
}

// Get ROPA ID from URL if starting from ROPA
$ropa_id = isset($_GET['ropa_id']) ? intval($_GET['ropa_id']) : ($dpia['ropa_id'] ?? null);

// Handle form submission for each step
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = intval($_POST['step']);

    // Step 1: Screening
    if ($step == 1) {
        $dpia_title = sanitize_input($_POST['dpia_title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $ropa_id_post = !empty($_POST['ropa_id']) ? intval($_POST['ropa_id']) : null;
        $screening_result = sanitize_input($_POST['screening_result'] ?? 'needed');
        $screening_reason = sanitize_input($_POST['screening_reason'] ?? '');

        if ($dpia_id) {
            // Update existing
            $query = "UPDATE dpia SET dpia_title = ?, description = ?, ropa_id = ?,
                      screening_result = ?, screening_reason = ?
                      WHERE dpia_id = ? AND org_id = ?";
            db_query($query, [$dpia_title, $description, $ropa_id_post, $screening_result,
                            $screening_reason, $dpia_id, $org_id]);
        } else {
            // Create new
            $query = "INSERT INTO dpia (org_id, ropa_id, dpia_title, description, screening_result,
                      screening_reason, status, created_by)
                      VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)";
            $stmt = db_query($query, [$org_id, $ropa_id_post, $dpia_title, $description,
                                     $screening_result, $screening_reason, $user_id]);
            $dpia_id = db_insert_id();
            log_audit($org_id, $user_id, 'dpia', $dpia_id, 'create');
        }

        // Save step data
        $step_data = json_encode([
            'screening_result' => $screening_result,
            'screening_reason' => $screening_reason
        ]);
        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 1, 'screening', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect("dpia_wizard.php?id=$dpia_id&step=2");
    }

    // Step 2: Processing Description
    elseif ($step == 2) {
        $step_data = json_encode([
            'processing_description' => sanitize_input($_POST['processing_description'] ?? ''),
            'necessity_justification' => sanitize_input($_POST['necessity_justification'] ?? ''),
            'data_minimization' => sanitize_input($_POST['data_minimization'] ?? ''),
            'proportionality' => sanitize_input($_POST['proportionality'] ?? '')
        ]);

        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 2, 'processing_description', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect("dpia_wizard.php?id=$dpia_id&step=3");
    }

    // Step 3: Risk Identification
    elseif ($step == 3) {
        // Handle multiple risks
        if (!empty($_POST['risk_titles'])) {
            // Delete existing risks for this DPIA (to replace)
            db_query("DELETE FROM dpia_risks WHERE dpia_id = ?", [$dpia_id]);

            foreach ($_POST['risk_titles'] as $index => $risk_title) {
                if (!empty($risk_title)) {
                    $risk_description = sanitize_input($_POST['risk_descriptions'][$index] ?? '');
                    $risk_category = sanitize_input($_POST['risk_categories'][$index] ?? 'confidentiality');
                    $likelihood = intval($_POST['likelihoods'][$index] ?? 1);
                    $impact = intval($_POST['impacts'][$index] ?? 1);

                    $query = "INSERT INTO dpia_risks (dpia_id, risk_title, risk_description, risk_category,
                              likelihood, impact)
                              VALUES (?, ?, ?, ?, ?, ?)";
                    db_query($query, [$dpia_id, sanitize_input($risk_title), $risk_description,
                                     $risk_category, $likelihood, $impact]);
                }
            }
        }

        $step_data = json_encode(['risks_identified' => count($_POST['risk_titles'] ?? [])]);
        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 3, 'risk_identification', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect("dpia_wizard.php?id=$dpia_id&step=4");
    }

    // Step 4: Mitigation Measures
    elseif ($step == 4) {
        if (!empty($_POST['risk_ids'])) {
            foreach ($_POST['risk_ids'] as $index => $risk_id) {
                $mitigation = sanitize_input($_POST['mitigations'][$index] ?? '');
                $residual_likelihood = intval($_POST['residual_likelihoods'][$index] ?? 1);
                $residual_impact = intval($_POST['residual_impacts'][$index] ?? 1);
                $responsible_user = !empty($_POST['responsible_users'][$index]) ? intval($_POST['responsible_users'][$index]) : null;
                $target_date = !empty($_POST['target_dates'][$index]) ? $_POST['target_dates'][$index] : null;

                $query = "UPDATE dpia_risks SET
                          mitigation_measures = ?,
                          residual_likelihood = ?,
                          residual_impact = ?,
                          responsible_user_id = ?,
                          target_date = ?,
                          status = 'mitigating'
                          WHERE dpia_risk_id = ?";
                db_query($query, [$mitigation, $residual_likelihood, $residual_impact,
                                 $responsible_user, $target_date, intval($risk_id)]);
            }
        }

        $step_data = json_encode(['mitigations_added' => count($_POST['risk_ids'] ?? [])]);
        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 4, 'mitigation', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect("dpia_wizard.php?id=$dpia_id&step=5");
    }

    // Step 5: Residual Risk Assessment
    elseif ($step == 5) {
        // Calculate overall residual risk
        $query = "SELECT AVG(residual_likelihood * residual_impact) as avg_residual
                  FROM dpia_risks WHERE dpia_id = ?
                  AND residual_likelihood IS NOT NULL AND residual_impact IS NOT NULL";
        $stmt = db_query($query, [$dpia_id]);
        $result = db_fetch_one($stmt);
        $residual_risk_score = round($result['avg_residual'] ?? 0);

        $query = "UPDATE dpia SET residual_risk_score = ? WHERE dpia_id = ?";
        db_query($query, [$residual_risk_score, $dpia_id]);

        $step_data = json_encode(['residual_risk_score' => $residual_risk_score]);
        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 5, 'residual_assessment', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect("dpia_wizard.php?id=$dpia_id&step=6");
    }

    // Step 6: Consultation & Evidence
    elseif ($step == 6) {
        $step_data = json_encode([
            'dpo_consulted' => isset($_POST['dpo_consulted']) ? 1 : 0,
            'stakeholders_consulted' => sanitize_input($_POST['stakeholders_consulted'] ?? ''),
            'consultation_outcome' => sanitize_input($_POST['consultation_outcome'] ?? ''),
            'evidence_notes' => sanitize_input($_POST['evidence_notes'] ?? '')
        ]);

        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 6, 'consultation', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect("dpia_wizard.php?id=$dpia_id&step=7");
    }

    // Step 7: Review & Submit
    elseif ($step == 7) {
        $action = sanitize_input($_POST['action'] ?? 'save');

        if ($action == 'submit') {
            // Submit for DPO approval
            $query = "UPDATE dpia SET status = 'submitted' WHERE dpia_id = ?";
            db_query($query, [$dpia_id]);

            // Create notification for DPO
            $dpo_query = "SELECT user_id FROM users WHERE org_id = ? AND role_id = 2 LIMIT 1";
            $stmt = db_query($dpo_query, [$org_id]);
            $dpo = db_fetch_one($stmt);

            if ($dpo) {
                create_notification($org_id, $dpo['user_id'], 'dpia_pending', 'DPIA Pending Approval',
                    "A new DPIA has been submitted for your review and approval.",
                    'dpia', $dpia_id, "dpia_approve.php?id=$dpia_id", 'high');
            }

            log_audit($org_id, $user_id, 'dpia', $dpia_id, 'update', null, ['action' => 'submitted']);

            set_flash_message('DPIA submitted successfully for DPO approval!', 'success');
        } else {
            set_flash_message('DPIA saved as draft.', 'success');
        }

        $step_data = json_encode(['completed' => true, 'action' => $action]);
        db_query("INSERT INTO dpia_steps (dpia_id, step_number, step_type, step_data, completed_at)
                  VALUES (?, 7, 'review_submit', ?, NOW())
                  ON DUPLICATE KEY UPDATE step_data = ?, completed_at = NOW()",
                 [$dpia_id, $step_data, $step_data]);

        redirect('dpia_list.php');
    }
}

// Load existing step data if editing
$step_data = [];
if ($dpia_id) {
    $query = "SELECT * FROM dpia_steps WHERE dpia_id = ? AND step_number = ?";
    $stmt = db_query($query, [$dpia_id, $current_step]);
    $step_record = db_fetch_one($stmt);
    if ($step_record && $step_record['step_data']) {
        $step_data = json_decode($step_record['step_data'], true);
    }
}

// Get lookup data
$query = "SELECT * FROM processing_activities WHERE org_id = ? AND status != 'archived' ORDER BY activity_name";
$stmt = db_query($query, [$org_id]);
$ropa_activities = db_fetch_all($stmt);

$query = "SELECT * FROM users WHERE org_id = ? AND status = 'active' ORDER BY last_name, first_name";
$stmt = db_query($query, [$org_id]);
$users = db_fetch_all($stmt);

// Get existing risks if on step 3+
$existing_risks = [];
if ($dpia_id && $current_step >= 3) {
    $query = "SELECT * FROM dpia_risks WHERE dpia_id = ? ORDER BY dpia_risk_id";
    $stmt = db_query($query, [$dpia_id]);
    $existing_risks = db_fetch_all($stmt);
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - DPIA Wizard</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .wizard-header {
            margin-bottom: 30px;
        }
        .wizard-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .wizard-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .wizard-step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #ddd;
            color: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .wizard-step.active .wizard-step-number {
            background-color: #00a950;
            color: white;
        }
        .wizard-step.completed .wizard-step-number {
            background-color: #5cb85c;
            color: white;
        }
        .wizard-step-label {
            font-size: 12px;
            color: #666;
        }
        .wizard-step.active .wizard-step-label {
            color: #00a950;
            font-weight: bold;
        }
        .risk-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fafafa;
            border-radius: 4px;
        }
        .risk-score-matrix {
            display: inline-block;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">
                <div class="wizard-header">
                    <h2>Data Protection Impact Assessment (DPIA) Wizard</h2>
                    <h5>Step-by-step privacy risk assessment</h5>
                </div>

                <!-- Wizard Progress Steps -->
                <div class="wizard-steps">
                    <?php
                    $steps = [
                        1 => 'Screening',
                        2 => 'Description',
                        3 => 'Risks',
                        4 => 'Mitigation',
                        5 => 'Residual Risk',
                        6 => 'Consultation',
                        7 => 'Review'
                    ];
                    foreach ($steps as $step_num => $step_label):
                        $step_class = '';
                        if ($step_num == $current_step) {
                            $step_class = 'active';
                        } elseif ($step_num < $current_step) {
                            $step_class = 'completed';
                        }
                    ?>
                    <div class="wizard-step <?php echo $step_class; ?>">
                        <div class="wizard-step-number"><?php echo $step_num; ?></div>
                        <div class="wizard-step-label"><?php echo $step_label; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" action="dpia_wizard.php?id=<?php echo $dpia_id; ?>&step=<?php echo $current_step; ?>">
                    <input type="hidden" name="step" value="<?php echo $current_step; ?>">

                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <i class="fa fa-list-ol"></i> Step <?php echo $current_step; ?>: <?php echo $steps[$current_step]; ?>
                        </div>
                        <div class="panel-body">

                            <?php if ($current_step == 1): ?>
                                <!-- Step 1: Screening -->
                                <div class="alert alert-info">
                                    <strong>Purpose:</strong> Determine if a full DPIA is required for this processing activity.
                                </div>

                                <div class="form-group">
                                    <label>DPIA Title <span class="text-danger">*</span></label>
                                    <input type="text" name="dpia_title" class="form-control" required
                                           value="<?php echo htmlspecialchars($dpia['dpia_title'] ?? ''); ?>"
                                           placeholder="e.g., DPIA for Employee Biometric Access System">
                                </div>

                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($dpia['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Link to Processing Activity (ROPA)</label>
                                    <select name="ropa_id" class="form-control">
                                        <option value="">Select ROPA Entry (Optional)</option>
                                        <?php foreach ($ropa_activities as $activity): ?>
                                            <option value="<?php echo $activity['ropa_id']; ?>"
                                                    <?php echo ($ropa_id == $activity['ropa_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($activity['activity_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Screening Result <span class="text-danger">*</span></label>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="screening_result" value="needed" required
                                                   <?php echo (($dpia['screening_result'] ?? 'needed') == 'needed') ? 'checked' : ''; ?>>
                                            <strong>DPIA is needed</strong> - This processing requires a full DPIA
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="screening_result" value="not_needed"
                                                   <?php echo (($dpia['screening_result'] ?? '') == 'not_needed') ? 'checked' : ''; ?>>
                                            <strong>DPIA not needed</strong> - Processing is low risk
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Screening Reason/Justification</label>
                                    <textarea name="screening_reason" class="form-control" rows="4"
                                              placeholder="Explain why a DPIA is or is not required for this processing activity..."><?php echo htmlspecialchars($dpia['screening_reason'] ?? ''); ?></textarea>
                                </div>

                            <?php elseif ($current_step == 2): ?>
                                <!-- Step 2: Processing Description -->
                                <div class="alert alert-info">
                                    <strong>Purpose:</strong> Describe the nature, scope, context and purposes of processing.
                                </div>

                                <div class="form-group">
                                    <label>Processing Description <span class="text-danger">*</span></label>
                                    <textarea name="processing_description" class="form-control" rows="4" required
                                              placeholder="Describe what personal data will be processed, how it will be collected, used, stored, and who will have access..."><?php echo htmlspecialchars($step_data['processing_description'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Necessity & Proportionality</label>
                                    <textarea name="necessity_justification" class="form-control" rows="3"
                                              placeholder="Explain why this processing is necessary and proportionate to achieve the stated purpose..."><?php echo htmlspecialchars($step_data['necessity_justification'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Data Minimization Measures</label>
                                    <textarea name="data_minimization" class="form-control" rows="3"
                                              placeholder="How will you ensure only necessary data is collected and retained for only as long as needed?"><?php echo htmlspecialchars($step_data['data_minimization'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Proportionality Assessment</label>
                                    <textarea name="proportionality" class="form-control" rows="3"
                                              placeholder="Is the processing proportionate to the risks posed to individuals?"><?php echo htmlspecialchars($step_data['proportionality'] ?? ''); ?></textarea>
                                </div>

                            <?php elseif ($current_step == 3): ?>
                                <!-- Step 3: Risk Identification -->
                                <div class="alert alert-info">
                                    <strong>Purpose:</strong> Identify privacy risks (confidentiality, integrity, availability).
                                </div>

                                <div class="risk-score-matrix">
                                    <strong>Risk Scoring:</strong><br>
                                    Likelihood × Impact = Risk Score<br>
                                    <small>1=Very Low, 2=Low, 3=Medium, 4=High, 5=Very High</small>
                                </div>

                                <div id="risks-container">
                                    <?php if (!empty($existing_risks)): ?>
                                        <?php foreach ($existing_risks as $index => $risk): ?>
                                            <div class="risk-item">
                                                <h5>Risk #<?php echo $index + 1; ?></h5>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Risk Title</label>
                                                            <input type="text" name="risk_titles[]" class="form-control"
                                                                   value="<?php echo htmlspecialchars($risk['risk_title']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Category</label>
                                                            <select name="risk_categories[]" class="form-control">
                                                                <option value="confidentiality" <?php echo ($risk['risk_category'] == 'confidentiality') ? 'selected' : ''; ?>>Confidentiality</option>
                                                                <option value="integrity" <?php echo ($risk['risk_category'] == 'integrity') ? 'selected' : ''; ?>>Integrity</option>
                                                                <option value="availability" <?php echo ($risk['risk_category'] == 'availability') ? 'selected' : ''; ?>>Availability</option>
                                                                <option value="compliance" <?php echo ($risk['risk_category'] == 'compliance') ? 'selected' : ''; ?>>Compliance</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Risk Description</label>
                                                    <textarea name="risk_descriptions[]" class="form-control" rows="2"><?php echo htmlspecialchars($risk['risk_description']); ?></textarea>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Likelihood (1-5)</label>
                                                            <select name="likelihoods[]" class="form-control">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo ($risk['likelihood'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Impact (1-5)</label>
                                                            <select name="impacts[]" class="form-control">
                                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                    <option value="<?php echo $i; ?>" <?php echo ($risk['impact'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                                <?php endfor; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="risk-item">
                                            <h5>Risk #1</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Risk Title</label>
                                                        <input type="text" name="risk_titles[]" class="form-control" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Category</label>
                                                        <select name="risk_categories[]" class="form-control">
                                                            <option value="confidentiality">Confidentiality</option>
                                                            <option value="integrity">Integrity</option>
                                                            <option value="availability">Availability</option>
                                                            <option value="compliance">Compliance</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Risk Description</label>
                                                <textarea name="risk_descriptions[]" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Likelihood (1-5)</label>
                                                        <select name="likelihoods[]" class="form-control">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Impact (1-5)</label>
                                                        <select name="impacts[]" class="form-control">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <button type="button" class="btn btn-default" onclick="addRisk()">
                                    <i class="fa fa-plus"></i> Add Another Risk
                                </button>

<?php elseif ($current_step == 4): ?>
                                <!-- Step 4: Mitigation Measures -->
                                <div class="alert alert-info">
                                    <strong>Purpose:</strong> Define measures to mitigate identified risks.
                                </div>

                                <?php foreach ($existing_risks as $index => $risk): ?>
                                    <div class="risk-item">
                                        <h5>Risk: <?php echo htmlspecialchars($risk['risk_title']); ?></h5>
                                        <p><strong>Inherent Score:</strong> <?php echo $risk['inherent_score']; ?>
                                           (Likelihood: <?php echo $risk['likelihood']; ?> × Impact: <?php echo $risk['impact']; ?>)</p>

                                        <input type="hidden" name="risk_ids[]" value="<?php echo $risk['dpia_risk_id']; ?>">

                                        <div class="form-group">
                                            <label>Mitigation Measures <span class="text-danger">*</span></label>
                                            <textarea name="mitigations[]" class="form-control" rows="3" required
                                                      placeholder="Describe specific controls and measures to reduce this risk..."><?php echo htmlspecialchars($risk['mitigation_measures'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Residual Likelihood (after mitigation)</label>
                                                    <select name="residual_likelihoods[]" class="form-control">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <option value="<?php echo $i; ?>" <?php echo (($risk['residual_likelihood'] ?? $risk['likelihood']) == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Residual Impact (after mitigation)</label>
                                                    <select name="residual_impacts[]" class="form-control">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <option value="<?php echo $i; ?>" <?php echo (($risk['residual_impact'] ?? $risk['impact']) == $i) ? 'selected' : ''; ?>><?php echo $i; ?></option>
                                                        <?php endfor; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Responsible Person</label>
                                                    <select name="responsible_users[]" class="form-control">
                                                        <option value="">Select Person</option>
                                                        <?php foreach ($users as $user): ?>
                                                            <option value="<?php echo $user['user_id']; ?>"
                                                                    <?php echo (($risk['responsible_user_id'] ?? '') == $user['user_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Target Completion Date</label>
                                                    <input type="date" name="target_dates[]" class="form-control"
                                                           value="<?php echo $risk['target_date'] ?? ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <hr>
                                    </div>
                                <?php endforeach; ?>

                            <?php elseif ($current_step == 5): ?>
                                <!-- Step 5: Residual Risk Assessment -->
                                <div class="alert alert-info">
                                    <strong>Purpose:</strong> Calculate and assess overall residual risk after mitigation.
                                </div>

                                <?php
                                $query = "SELECT *, (residual_likelihood * residual_impact) as residual_score
                                          FROM dpia_risks WHERE dpia_id = ?";
                                $stmt = db_query($query, [$dpia_id]);
                                $risks_with_residual = db_fetch_all($stmt);

                                $total_residual = 0;
                                $count = 0;
                                ?>

                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Risk</th>
                                            <th>Inherent Score</th>
                                            <th>Residual Score</th>
                                            <th>Risk Reduction</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($risks_with_residual as $risk): ?>
                                            <?php
                                            $total_residual += $risk['residual_score'];
                                            $count++;
                                            $reduction = $risk['inherent_score'] - $risk['residual_score'];
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($risk['risk_title']); ?></td>
                                                <td><?php echo $risk['inherent_score']; ?></td>
                                                <td>
                                                    <strong><?php echo $risk['residual_score']; ?></strong>
                                                    <?php if ($risk['residual_score'] >= RISK_ACCEPTABLE_THRESHOLD): ?>
                                                        <span class="label label-danger">High</span>
                                                    <?php elseif ($risk['residual_score'] >= 3): ?>
                                                        <span class="label label-warning">Medium</span>
                                                    <?php else: ?>
                                                        <span class="label label-success">Low</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($reduction > 0): ?>
                                                        <span class="text-success">-<?php echo $reduction; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">No change</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>Average Residual Risk Score</th>
                                            <th colspan="3">
                                                <strong style="font-size: 24px;">
                                                    <?php echo round($total_residual / $count, 1); ?>
                                                </strong>
                                                <?php if (($total_residual / $count) >= RISK_ACCEPTABLE_THRESHOLD): ?>
                                                    <span class="label label-danger">UNACCEPTABLE - Further mitigation required</span>
                                                <?php else: ?>
                                                    <span class="label label-success">ACCEPTABLE</span>
                                                <?php endif; ?>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>

                                <div class="alert alert-warning">
                                    <i class="fa fa-info-circle"></i>
                                    <strong>Note:</strong> Residual risk scores of <?php echo RISK_ACCEPTABLE_THRESHOLD; ?> or higher require additional mitigation measures before processing can proceed.
                                </div>

                            <?php elseif ($current_step == 6): ?>
                                <!-- Step 6: Consultation & Evidence -->
                                <div class="alert alert-info">
                                    <strong>Purpose:</strong> Document consultations with DPO and other stakeholders.
                                </div>

                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="dpo_consulted" value="1"
                                               <?php echo (!empty($step_data['dpo_consulted'])) ? 'checked' : ''; ?>>
                                        <strong>Data Protection Officer (DPO) has been consulted</strong>
                                    </label>
                                </div>

                                <div class="form-group">
                                    <label>Other Stakeholders Consulted</label>
                                    <textarea name="stakeholders_consulted" class="form-control" rows="2"
                                              placeholder="List other individuals or teams consulted (IT, Legal, HR, etc.)"><?php echo htmlspecialchars($step_data['stakeholders_consulted'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Consultation Outcome</label>
                                    <textarea name="consultation_outcome" class="form-control" rows="3"
                                              placeholder="Summarize feedback received and how it has been incorporated..."><?php echo htmlspecialchars($step_data['consultation_outcome'] ?? ''); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>Evidence & Supporting Documentation</label>
                                    <textarea name="evidence_notes" class="form-control" rows="3"
                                              placeholder="Note any supporting documents, emails, meeting minutes, etc."><?php echo htmlspecialchars($step_data['evidence_notes'] ?? ''); ?></textarea>
                                    <small class="text-muted">You can upload documents from the Documents module and link them to this DPIA.</small>
                                </div>

                            <?php elseif ($current_step == 7): ?>
                                <!-- Step 7: Review & Submit -->
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle"></i> <strong>DPIA Complete!</strong> Review the summary below and submit for DPO approval.
                                </div>

                                <?php
                                // Load complete DPIA data
                                $query = "SELECT d.*, pa.activity_name FROM dpia d
                                          LEFT JOIN processing_activities pa ON d.ropa_id = pa.ropa_id
                                          WHERE d.dpia_id = ?";
                                $stmt = db_query($query, [$dpia_id]);
                                $dpia_summary = db_fetch_one($stmt);

                                $query = "SELECT * FROM dpia_risks WHERE dpia_id = ?";
                                $stmt = db_query($query, [$dpia_id]);
                                $all_risks = db_fetch_all($stmt);
                                ?>

                                <div class="panel panel-default">
                                    <div class="panel-heading"><strong>DPIA Summary</strong></div>
                                    <div class="panel-body">
                                        <p><strong>Title:</strong> <?php echo htmlspecialchars($dpia_summary['dpia_title']); ?></p>
                                        <p><strong>Linked ROPA:</strong> <?php echo htmlspecialchars($dpia_summary['activity_name'] ?? 'None'); ?></p>
                                        <p><strong>Screening Result:</strong> <?php echo ucfirst($dpia_summary['screening_result']); ?></p>
                                        <p><strong>Total Risks Identified:</strong> <?php echo count($all_risks); ?></p>
                                        <p><strong>Average Residual Risk:</strong>
                                            <strong><?php echo round($dpia_summary['residual_risk_score'], 1); ?></strong>
                                            <?php if ($dpia_summary['residual_risk_score'] >= RISK_ACCEPTABLE_THRESHOLD): ?>
                                                <span class="label label-danger">High Risk</span>
                                            <?php else: ?>
                                                <span class="label label-success">Acceptable</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <strong>Important:</strong> Once submitted, this DPIA will be sent to the Data Protection Officer for review and approval.
                                    You will not be able to edit it until it is returned for rework.
                                </div>

                            <?php endif; ?>

                        </div>
                        <div class="panel-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php if ($current_step > 1): ?>
                                        <a href="dpia_wizard.php?id=<?php echo $dpia_id; ?>&step=<?php echo $current_step - 1; ?>" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> Previous
                                        </a>
                                    <?php else: ?>
                                        <a href="dpia_list.php" class="btn btn-default">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-right">
                                    <?php if ($current_step < 7): ?>
                                        <button type="submit" class="btn btn-primary">
                                            Next <i class="fa fa-arrow-right"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="save" class="btn btn-default">
                                            <i class="fa fa-save"></i> Save as Draft
                                        </button>
                                        <button type="submit" name="action" value="submit" class="btn btn-success">
                                            <i class="fa fa-check"></i> Submit for Approval
                                        </button>
                                    <?php endif; ?>
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
    <script src="assets/js/custom.js"></script>
    <script>
        let riskCounter = <?php echo count($existing_risks) > 0 ? count($existing_risks) : 1; ?>;

        function addRisk() {
            riskCounter++;
            const riskHtml = `
                <div class="risk-item">
                    <h5>Risk #${riskCounter}</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Risk Title</label>
                                <input type="text" name="risk_titles[]" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="risk_categories[]" class="form-control">
                                    <option value="confidentiality">Confidentiality</option>
                                    <option value="integrity">Integrity</option>
                                    <option value="availability">Availability</option>
                                    <option value="compliance">Compliance</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Risk Description</label>
                        <textarea name="risk_descriptions[]" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Likelihood (1-5)</label>
                                <select name="likelihoods[]" class="form-control">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Impact (1-5)</label>
                                <select name="impacts[]" class="form-control">
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('risks-container').insertAdjacentHTML('beforeend', riskHtml);
        }
    </script>
</body>
</html>
