<?php
/**
 * Add/Edit Policy
 * Create or update organizational policies with version control
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_role(['Admin', 'DPO']);

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Check if editing existing policy
$policy_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $policy_id > 0;

if ($is_edit) {
    $query = "SELECT * FROM policies WHERE policy_id = ? AND org_id = ?";
    $stmt = db_query($query, [$policy_id, $org_id]);
    $policy = db_fetch_one($stmt);

    if (!$policy) {
        set_flash_message('Policy not found.', 'error');
        redirect('policy_list.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $policy_title = trim($_POST['policy_title']);
    $policy_type = $_POST['policy_type'];
    $version = trim($_POST['version']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $review_due_date = !empty($_POST['review_due_date']) ? $_POST['review_due_date'] : null;
    $published_at = null;

    // Handle file upload
    $document_path = $is_edit ? $policy['document_path'] : null;

    if (isset($_FILES['policy_document']) && $_FILES['policy_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/policies/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['policy_document']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'txt'];

        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = 'policy_' . $org_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['policy_document']['tmp_name'], $target_path)) {
                $document_path = $target_path;

                // Delete old file if updating
                if ($is_edit && $policy['document_path'] && file_exists($policy['document_path'])) {
                    unlink($policy['document_path']);
                }
            } else {
                set_flash_message('Failed to upload document.', 'error');
                redirect('policy_add.php' . ($is_edit ? '?id=' . $policy_id : ''));
            }
        } else {
            set_flash_message('Invalid file type. Only PDF, DOC, DOCX, and TXT files are allowed.', 'error');
            redirect('policy_add.php' . ($is_edit ? '?id=' . $policy_id : ''));
        }
    }

    // Set published date if status is published
    if ($status === 'published') {
        $published_at = date('Y-m-d H:i:s');
    }

    if ($is_edit) {
        // Update existing policy
        $query = "UPDATE policies SET
                  policy_title = ?, policy_type = ?, version = ?, description = ?,
                  document_path = ?, status = ?, review_due_date = ?, published_at = ?,
                  updated_at = NOW()
                  WHERE policy_id = ? AND org_id = ?";

        $params = [
            $policy_title, $policy_type, $version, $description,
            $document_path, $status, $review_due_date, $published_at,
            $policy_id, $org_id
        ];

        db_query($query, $params);

        log_audit($org_id, $user_id, 'UPDATE', 'policy', $policy_id,
            "Updated policy: $policy_title (v$version)");

        set_flash_message('Policy updated successfully.', 'success');
        redirect('policy_view.php?id=' . $policy_id);

    } else {
        // Create new policy
        $query = "INSERT INTO policies (
                  org_id, policy_title, policy_type, version, description,
                  document_path, status, review_due_date, published_at, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $org_id, $policy_title, $policy_type, $version, $description,
            $document_path, $status, $review_due_date, $published_at, $user_id
        ];

        $stmt = db_query($query, $params);
        $new_policy_id = db_insert_id();

        log_audit($org_id, $user_id, 'CREATE', 'policy', $new_policy_id,
            "Created policy: $policy_title (v$version)");

        // Notify DPO if policy is published
        if ($status === 'published') {
            $dpo_query = "SELECT user_id FROM users WHERE org_id = ? AND role = 'DPO' AND status = 'active' LIMIT 1";
            $dpo_stmt = db_query($dpo_query, [$org_id]);
            $dpo = db_fetch_one($dpo_stmt);

            if ($dpo) {
                create_notification(
                    $org_id, $dpo['user_id'], 'policy_published',
                    'New Policy Published',
                    "A new policy '$policy_title' has been published and requires staff acknowledgement.",
                    'policy', $new_policy_id, "policy_view.php?id=$new_policy_id", 'medium'
                );
            }
        }

        set_flash_message('Policy created successfully.', 'success');
        redirect('policy_view.php?id=' . $new_policy_id);
    }
}

// Set default values
if (!$is_edit) {
    $policy = [
        'policy_title' => '',
        'policy_type' => 'data_protection',
        'version' => '1.0',
        'description' => '',
        'status' => 'draft',
        'review_due_date' => ''
    ];
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - <?php echo $is_edit ? 'Edit' : 'Add'; ?> Policy</title>
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
                        <h2><?php echo $is_edit ? 'Edit' : 'Add New'; ?> Policy</h2>
                        <h5>Policy management and version control</h5>
                    </div>
                </div>
                <hr />

    <!-- Form -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="card border-primary">
                <div class="card-header">
                    <i class="fa fa-file-text"></i> Policy Details
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Policy Title -->
                        <div class="form-group">
                            <label for="policy_title">Policy Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="policy_title" name="policy_title"
                                   value="<?php echo htmlspecialchars($policy['policy_title']); ?>" required>
                            <span class="form-text">e.g., "Data Protection Policy", "Information Security Policy"</span>
                        </div>

                        <!-- Policy Type -->
                        <div class="form-group">
                            <label for="policy_type">Policy Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="policy_type" name="policy_type" required>
                                <option value="data_protection" <?php echo $policy['policy_type'] === 'data_protection' ? 'selected' : ''; ?>>Data Protection</option>
                                <option value="privacy" <?php echo $policy['policy_type'] === 'privacy' ? 'selected' : ''; ?>>Privacy</option>
                                <option value="security" <?php echo $policy['policy_type'] === 'security' ? 'selected' : ''; ?>>Security</option>
                                <option value="retention" <?php echo $policy['policy_type'] === 'retention' ? 'selected' : ''; ?>>Data Retention</option>
                                <option value="breach" <?php echo $policy['policy_type'] === 'breach' ? 'selected' : ''; ?>>Breach Response</option>
                                <option value="other" <?php echo $policy['policy_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <!-- Version -->
                        <div class="form-group">
                            <label for="version">Version <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="version" name="version"
                                   value="<?php echo htmlspecialchars($policy['version']); ?>" required>
                            <span class="form-text">e.g., "1.0", "2.1", "3.0-DRAFT"</span>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($policy['description']); ?></textarea>
                            <span class="form-text">Brief summary of the policy scope and purpose</span>
                        </div>

                        <!-- Policy Document Upload -->
                        <div class="form-group">
                            <label for="policy_document">Policy Document</label>
                            <?php if ($is_edit && $policy['document_path']): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-file"></i> Current file: <strong><?php echo basename($policy['document_path']); ?></strong>
                                    <br>Upload a new file to replace it (optional)
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="policy_document" name="policy_document" accept=".pdf,.doc,.docx,.txt">
                            <span class="form-text">Supported formats: PDF, DOC, DOCX, TXT (Max 10MB)</span>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="draft" <?php echo $policy['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $policy['status'] === 'published' ? 'selected' : ''; ?>>Published (visible to all staff)</option>
                                <option value="archived" <?php echo $policy['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                            <span class="form-text">Only published policies require staff acknowledgement</span>
                        </div>

                        <!-- Review Due Date -->
                        <div class="form-group">
                            <label for="review_due_date">Review Due Date</label>
                            <input type="date" class="form-control" id="review_due_date" name="review_due_date"
                                   value="<?php echo htmlspecialchars($policy['review_due_date'] ?? ''); ?>">
                            <span class="form-text">Set a date for policy review (typically annual)</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> <?php echo $is_edit ? 'Update' : 'Create'; ?> Policy
                            </button>
                            <a href="policy_list.php" class="btn btn-secondary btn-lg">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Help Panel -->
            <div class="card border-info">
                <div class="card-header">
                    <i class="fa fa-info-circle"></i> Policy Management Best Practices
                </div>
                <div class="card-body">
                    <ul>
                        <li><strong>Version Control:</strong> Increment version numbers when making significant changes (e.g., 1.0 to 2.0)</li>
                        <li><strong>Annual Review:</strong> Set review dates 12 months from publication to ensure policies remain current</li>
                        <li><strong>Staff Acknowledgement:</strong> Published policies require staff to acknowledge they have read and understood them</li>
                        <li><strong>CDPA Compliance:</strong> Ensure policies align with Zimbabwe's Cyber and Data Protection Act requirements</li>
                        <li><strong>Documentation:</strong> Always upload the full policy document in PDF format for official records</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>

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
