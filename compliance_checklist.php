<?php
/**
 * DPA Tool - CDPA Compliance Gap Checklist
 * Assess organizational readiness against Zimbabwe's CDPA requirements
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

require_once 'config/checklist_items.php';

$org_id = get_current_org_id();
$user_id = get_current_user_id();
$can_edit = is_admin() || is_dpo();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_edit) {
    global $dpa_db;

    $responses = $_POST['response'] ?? [];
    $notes = $_POST['notes'] ?? [];

    foreach ($CHECKLIST_ITEMS as $cat_key => $category) {
        foreach ($category['items'] as $item_key => $item_text) {
            $response_val = $responses[$item_key] ?? null;
            $note_val = trim($notes[$item_key] ?? '');

            // Only save if a response was selected
            if ($response_val !== null && in_array($response_val, ['yes', 'no', 'partial', 'na'])) {
                $query = "INSERT INTO compliance_responses (org_id, item_key, response, notes, updated_by, updated_at)
                          VALUES (?, ?, ?, ?, ?, NOW())
                          ON DUPLICATE KEY UPDATE response = VALUES(response), notes = VALUES(notes),
                          updated_by = VALUES(updated_by), updated_at = NOW()";
                db_query($query, [$org_id, $item_key, $response_val, $note_val, $user_id]);
            }
        }
    }

    log_audit($org_id, $user_id, 'compliance_checklist', 0, 'updated', null, ['action' => 'Checklist responses updated']);
    set_flash_message('Compliance checklist saved successfully.', 'success');
    redirect('compliance_checklist.php');
}

// Load existing responses
$stmt = db_query("SELECT item_key, response, notes, updated_by, updated_at FROM compliance_responses WHERE org_id = ?", [$org_id]);
$rows = db_fetch_all($stmt);
$saved = [];
foreach ($rows as $row) {
    $saved[$row['item_key']] = $row;
}

// Calculate scores
$total_items = 0;
$applicable_items = 0;
$earned_points = 0;
$yes_count = 0;
$partial_count = 0;
$no_count = 0;
$na_count = 0;
$unanswered = 0;

$category_scores = [];

foreach ($CHECKLIST_ITEMS as $cat_key => $category) {
    $cat_total = 0;
    $cat_applicable = 0;
    $cat_earned = 0;
    $cat_yes = 0;
    $cat_partial = 0;
    $cat_no = 0;

    foreach ($category['items'] as $item_key => $item_text) {
        $total_items++;
        $cat_total++;
        $resp = $saved[$item_key]['response'] ?? null;

        if ($resp === 'yes') {
            $earned_points += 1;
            $cat_earned += 1;
            $applicable_items++;
            $cat_applicable++;
            $yes_count++;
            $cat_yes++;
        } elseif ($resp === 'partial') {
            $earned_points += 0.5;
            $cat_earned += 0.5;
            $applicable_items++;
            $cat_applicable++;
            $partial_count++;
            $cat_partial++;
        } elseif ($resp === 'no') {
            $applicable_items++;
            $cat_applicable++;
            $no_count++;
            $cat_no++;
        } elseif ($resp === 'na') {
            $na_count++;
        } else {
            $unanswered++;
        }
    }

    $cat_pct = $cat_applicable > 0 ? round(($cat_earned / $cat_applicable) * 100, 1) : 0;
    $category_scores[$cat_key] = [
        'total' => $cat_total,
        'applicable' => $cat_applicable,
        'earned' => $cat_earned,
        'percentage' => $cat_pct,
        'answered' => $cat_yes + $cat_partial + $cat_no,
    ];
}

$overall_pct = $applicable_items > 0 ? round(($earned_points / $applicable_items) * 100, 1) : 0;
$answered_count = $yes_count + $partial_count + $no_count + $na_count;
$not_started = ($answered_count === 0);

// Score color
if ($not_started) {
    $score_color = '#6c757d';
    $score_label = 'Not Started';
} elseif ($overall_pct >= 80) {
    $score_color = '#28a745';
    $score_label = 'Strong Compliance';
} elseif ($overall_pct >= 50) {
    $score_color = '#ffc107';
    $score_label = 'Needs Improvement';
} else {
    $score_color = '#dc3545';
    $score_label = 'Significant Gaps';
}

$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - CDPA Compliance Checklist</title>
    <script src="assets/js/theme.js"></script>
    <link href="assets/css/theme-variables.css" rel="stylesheet" />
    <link href="assets/css/bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/css/all.min.css" rel="stylesheet" />
    <link href="assets/css/css/v4-shims.min.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <link href="assets/css/module-styles.css" rel="stylesheet" />
    <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css' />
    <style>
        .checklist-score-ring {
            position: relative;
            width: 160px;
            height: 160px;
            margin: 0 auto;
        }
        .checklist-score-ring svg {
            transform: rotate(-90deg);
        }
        .checklist-score-ring .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .checklist-score-ring .score-text .pct {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
            color: var(--text-heading);
        }
        .checklist-score-ring .score-text .lbl {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .stat-mini-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            box-shadow: var(--card-shadow);
        }
        .stat-mini-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }
        .stat-mini-card .stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .checklist-header-row {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--card-shadow);
        }
        .category-score-badge {
            font-size: 13px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            color: #fff;
        }
        .checklist-item {
            padding: 14px 0;
            border-bottom: 1px solid var(--border-light);
        }
        .checklist-item:last-child {
            border-bottom: none;
        }
        .checklist-item .item-text {
            font-size: 14px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        .checklist-item .item-number {
            font-weight: 600;
            color: var(--text-muted);
            margin-right: 8px;
        }
        .checklist-radio-group {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            align-items: center;
        }
        .checklist-radio-group .form-check {
            margin-bottom: 0;
        }
        .checklist-radio-group .form-check-input:checked[value="yes"] {
            background-color: #28a745;
            border-color: #28a745;
        }
        .checklist-radio-group .form-check-input:checked[value="no"] {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .checklist-radio-group .form-check-input:checked[value="partial"] {
            background-color: #ffc107;
            border-color: #ffc107;
        }
        .checklist-radio-group .form-check-input:checked[value="na"] {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .notes-toggle {
            font-size: 13px;
            cursor: pointer;
            color: var(--text-link);
            border: none;
            background: none;
            padding: 0;
            margin-left: 8px;
        }
        .notes-toggle:hover {
            text-decoration: underline;
        }
        .notes-area {
            margin-top: 8px;
        }
        .notes-area textarea {
            font-size: 13px;
            resize: vertical;
        }
        .accordion-button {
            font-weight: 600;
            font-size: 15px;
        }
        .accordion-button:not(.collapsed) {
            background-color: var(--bg-tertiary);
            color: var(--text-heading);
        }
        [data-theme="dark"] .accordion-button {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
        [data-theme="dark"] .accordion-button:not(.collapsed) {
            background-color: var(--bg-tertiary);
            color: var(--text-heading);
        }
        [data-theme="dark"] .accordion-body {
            background-color: var(--bg-card);
        }
        [data-theme="dark"] .accordion-item {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        .cdpa-section-badge {
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 4px;
            background: #e9ecef;
            color: #495057;
            margin-left: 8px;
        }
        [data-theme="dark"] .cdpa-section-badge {
            background: var(--bg-tertiary);
            color: var(--text-secondary);
        }
        .save-bar {
            position: sticky;
            bottom: 0;
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 16px 24px;
            text-align: right;
            z-index: 10;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
        }
        .category-header-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        .category-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .category-progress-mini {
            width: 80px;
            height: 6px;
            background: var(--border-light);
            border-radius: 3px;
            overflow: hidden;
            margin-right: 8px;
        }
        .category-progress-mini .fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/sidebar.php'; ?>

        <div id="page-wrapper">
            <div id="page-inner">

                <div class="page-header-area">
                    <div class="row align-items-center">
                        <div class="col">
                            <h2>CDPA Compliance Checklist</h2>
                            <p class="text-muted mb-0">Assess compliance with Zimbabwe's Cyber and Data Protection Act</p>
                        </div>
                    </div>
                </div>

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'danger' ? 'danger' : 'warning'); ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Score Summary -->
                <div class="checklist-header-row">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <div class="checklist-score-ring">
                                <svg width="160" height="160" viewBox="0 0 160 160">
                                    <circle cx="80" cy="80" r="70" fill="none" stroke="var(--border-light)" stroke-width="12" />
                                    <circle cx="80" cy="80" r="70" fill="none"
                                        stroke="<?php echo $score_color; ?>"
                                        stroke-width="12"
                                        stroke-dasharray="<?php echo 2 * M_PI * 70; ?>"
                                        stroke-dashoffset="<?php echo 2 * M_PI * 70 * (1 - $overall_pct / 100); ?>"
                                        stroke-linecap="round" />
                                </svg>
                                <div class="score-text">
                                    <div class="pct"><?php echo $not_started ? '--' : $overall_pct . '%'; ?></div>
                                    <div class="lbl"><?php echo $score_label; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-4">
                                    <div class="stat-mini-card">
                                        <div class="stat-value" style="color: #28a745;"><?php echo $yes_count; ?></div>
                                        <div class="stat-label">Compliant</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-mini-card">
                                        <div class="stat-value" style="color: #ffc107;"><?php echo $partial_count; ?></div>
                                        <div class="stat-label">Partial</div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-mini-card">
                                        <div class="stat-value" style="color: #dc3545;"><?php echo $no_count; ?></div>
                                        <div class="stat-label">Gaps</div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <?php echo $answered_count; ?> of <?php echo $total_items; ?> items answered
                                    <?php if ($na_count > 0): ?> | <?php echo $na_count; ?> not applicable<?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checklist Form -->
                <form method="POST" id="checklistForm">
                    <div class="accordion" id="checklistAccordion">
                        <?php $cat_index = 0; foreach ($CHECKLIST_ITEMS as $cat_key => $category): $cat_index++; ?>
                        <?php
                            $cs = $category_scores[$cat_key];
                            $cat_pct = $cs['percentage'];
                            $cat_answered = $cs['answered'];
                            $cat_total = $cs['total'];

                            if ($cat_answered === 0) {
                                $badge_bg = '#6c757d';
                            } elseif ($cat_pct >= 80) {
                                $badge_bg = '#28a745';
                            } elseif ($cat_pct >= 50) {
                                $badge_bg = '#ffc107';
                            } else {
                                $badge_bg = '#dc3545';
                            }
                        ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading_<?php echo $cat_key; ?>">
                                <button class="accordion-button <?php echo $cat_index > 1 ? 'collapsed' : ''; ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse_<?php echo $cat_key; ?>"
                                    aria-expanded="<?php echo $cat_index === 1 ? 'true' : 'false'; ?>">
                                    <div class="category-header-info">
                                        <div class="category-header-left">
                                            <span><?php echo $cat_index; ?>. <?php echo htmlspecialchars($category['label']); ?></span>
                                            <span class="cdpa-section-badge"><?php echo htmlspecialchars($category['cdpa_section']); ?></span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="category-progress-mini">
                                                <div class="fill" style="width: <?php echo $cat_answered > 0 ? $cat_pct : 0; ?>%; background: <?php echo $badge_bg; ?>;"></div>
                                            </div>
                                            <span class="category-score-badge" style="background: <?php echo $badge_bg; ?>;">
                                                <?php echo $cat_answered > 0 ? $cat_pct . '%' : '--'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse_<?php echo $cat_key; ?>" class="accordion-collapse collapse <?php echo $cat_index === 1 ? 'show' : ''; ?>"
                                data-bs-parent="#checklistAccordion">
                                <div class="accordion-body">
                                    <?php $item_num = 0; foreach ($category['items'] as $item_key => $item_text): $item_num++; ?>
                                    <?php
                                        $current_response = $saved[$item_key]['response'] ?? '';
                                        $current_notes = $saved[$item_key]['notes'] ?? '';
                                        $has_notes = !empty($current_notes);
                                    ?>
                                    <div class="checklist-item">
                                        <div class="item-text">
                                            <span class="item-number"><?php echo $cat_index; ?>.<?php echo $item_num; ?></span>
                                            <?php echo htmlspecialchars($item_text); ?>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap gap-2">
                                            <div class="checklist-radio-group">
                                                <?php if ($can_edit): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response[<?php echo $item_key; ?>]"
                                                        id="<?php echo $item_key; ?>_yes" value="yes"
                                                        <?php echo $current_response === 'yes' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $item_key; ?>_yes">Yes</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response[<?php echo $item_key; ?>]"
                                                        id="<?php echo $item_key; ?>_no" value="no"
                                                        <?php echo $current_response === 'no' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $item_key; ?>_no">No</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response[<?php echo $item_key; ?>]"
                                                        id="<?php echo $item_key; ?>_partial" value="partial"
                                                        <?php echo $current_response === 'partial' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $item_key; ?>_partial">Partial</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="response[<?php echo $item_key; ?>]"
                                                        id="<?php echo $item_key; ?>_na" value="na"
                                                        <?php echo $current_response === 'na' ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="<?php echo $item_key; ?>_na">N/A</label>
                                                </div>
                                                <?php else: ?>
                                                    <?php if ($current_response === 'yes'): ?>
                                                        <span class="badge bg-success">Yes</span>
                                                    <?php elseif ($current_response === 'no'): ?>
                                                        <span class="badge bg-danger">No</span>
                                                    <?php elseif ($current_response === 'partial'): ?>
                                                        <span class="badge bg-warning text-dark">Partial</span>
                                                    <?php elseif ($current_response === 'na'): ?>
                                                        <span class="badge bg-secondary">N/A</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark">Not Answered</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="notes-toggle" onclick="toggleNotes('<?php echo $item_key; ?>')">
                                                <i class="fa fa-comment-o"></i> <?php echo $has_notes ? 'View notes' : 'Add notes'; ?>
                                            </button>
                                        </div>
                                        <div class="notes-area" id="notes_<?php echo $item_key; ?>" style="display: <?php echo $has_notes ? 'block' : 'none'; ?>;">
                                            <?php if ($can_edit): ?>
                                            <textarea class="form-control form-control-sm" name="notes[<?php echo $item_key; ?>]"
                                                rows="2" placeholder="Add notes or evidence reference..."><?php echo htmlspecialchars($current_notes); ?></textarea>
                                            <?php else: ?>
                                                <?php if ($has_notes): ?>
                                                <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($current_notes)); ?></p>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($can_edit): ?>
                    <div class="save-bar">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-save"></i> Save Checklist
                        </button>
                    </div>
                    <?php endif; ?>
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
    function toggleNotes(itemKey) {
        var el = document.getElementById('notes_' + itemKey);
        if (el.style.display === 'none') {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    }
    </script>
</body>
</html>
