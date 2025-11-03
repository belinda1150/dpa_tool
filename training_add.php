<?php
/**
 * Add/Edit Training Course
 * Create or update training courses
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_role(['Admin', 'DPO']);

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Check if editing existing training
$training_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $training_id > 0;

if ($is_edit) {
    $query = "SELECT * FROM training WHERE training_id = ? AND org_id = ?";
    $stmt = db_query($query, [$training_id, $org_id]);
    $training = db_fetch_one($stmt);

    if (!$training) {
        set_flash_message('Training course not found.', 'error');
        redirect('training_list.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $training_title = trim($_POST['training_title']);
    $training_type = $_POST['training_type'];
    $description = trim($_POST['description']);
    $duration_minutes = !empty($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : null;
    $passing_score = !empty($_POST['passing_score']) ? intval($_POST['passing_score']) : null;
    $due_days = !empty($_POST['due_days']) ? intval($_POST['due_days']) : 30;
    $status = $_POST['status'];

    // Handle file upload
    $content_path = $is_edit ? $training['content_path'] : null;

    if (isset($_FILES['training_content']) && $_FILES['training_content']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/training/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = pathinfo($_FILES['training_content']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'mp4', 'zip'];

        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $filename = 'training_' . $org_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['training_content']['tmp_name'], $target_path)) {
                $content_path = $target_path;

                // Delete old file if updating
                if ($is_edit && $training['content_path'] && file_exists($training['content_path'])) {
                    unlink($training['content_path']);
                }
            } else {
                set_flash_message('Failed to upload training content.', 'error');
                redirect('training_add.php' . ($is_edit ? '?id=' . $training_id : ''));
            }
        } else {
            set_flash_message('Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, MP4, ZIP.', 'error');
            redirect('training_add.php' . ($is_edit ? '?id=' . $training_id : ''));
        }
    }

    if ($is_edit) {
        // Update existing training
        $query = "UPDATE training SET
                  training_title = ?, training_type = ?, description = ?,
                  content_path = ?, duration_minutes = ?, passing_score = ?,
                  due_days = ?, status = ?, updated_at = NOW()
                  WHERE training_id = ? AND org_id = ?";

        $params = [
            $training_title, $training_type, $description,
            $content_path, $duration_minutes, $passing_score,
            $due_days, $status, $training_id, $org_id
        ];

        db_query($query, $params);

        log_audit($org_id, $user_id, 'UPDATE', 'training', $training_id,
            "Updated training: $training_title");

        set_flash_message('Training course updated successfully.', 'success');
        redirect('training_view.php?id=' . $training_id);

    } else {
        // Create new training
        $query = "INSERT INTO training (
                  org_id, training_title, training_type, description,
                  content_path, duration_minutes, passing_score, due_days, status, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $org_id, $training_title, $training_type, $description,
            $content_path, $duration_minutes, $passing_score, $due_days, $status, $user_id
        ];

        $stmt = db_query($query, $params);
        $new_training_id = db_insert_id();

        log_audit($org_id, $user_id, 'CREATE', 'training', $new_training_id,
            "Created training: $training_title");

        set_flash_message('Training course created successfully.', 'success');
        redirect('training_view.php?id=' . $new_training_id);
    }
}

// Set default values
if (!$is_edit) {
    $training = [
        'training_title' => '',
        'training_type' => 'data_protection',
        'description' => '',
        'duration_minutes' => '',
        'passing_score' => 80,
        'due_days' => 30,
        'status' => 'draft'
    ];
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - <?php echo $is_edit ? 'Edit' : 'Add'; ?> Training</title>
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
                    <i class="fa fa-<?php echo $is_edit ? 'edit' : 'plus'; ?>"></i> <?php echo $is_edit ? 'Edit' : 'Create New'; ?> Training Course
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="training_list.php">Training</a></li>
                    <li class="active"><?php echo $is_edit ? 'Edit' : 'Add'; ?> Training</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <i class="fa fa-graduation-cap"></i> Training Course Details
                </div>
                <div class="panel-body">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Training Title -->
                        <div class="form-group">
                            <label for="training_title">Training Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="training_title" name="training_title"
                                   value="<?php echo htmlspecialchars($training['training_title']); ?>" required>
                            <span class="help-block">e.g., "Data Protection Awareness", "Phishing Prevention"</span>
                        </div>

                        <!-- Training Type -->
                        <div class="form-group">
                            <label for="training_type">Training Type <span class="text-danger">*</span></label>
                            <select class="form-control" id="training_type" name="training_type" required>
                                <option value="data_protection" <?php echo $training['training_type'] === 'data_protection' ? 'selected' : ''; ?>>Data Protection</option>
                                <option value="security_awareness" <?php echo $training['training_type'] === 'security_awareness' ? 'selected' : ''; ?>>Security Awareness</option>
                                <option value="privacy" <?php echo $training['training_type'] === 'privacy' ? 'selected' : ''; ?>>Privacy</option>
                                <option value="compliance" <?php echo $training['training_type'] === 'compliance' ? 'selected' : ''; ?>>Compliance</option>
                                <option value="incident_response" <?php echo $training['training_type'] === 'incident_response' ? 'selected' : ''; ?>>Incident Response</option>
                                <option value="other" <?php echo $training['training_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($training['description']); ?></textarea>
                            <span class="help-block">Describe the learning objectives and content covered</span>
                        </div>

                        <!-- Training Content Upload -->
                        <div class="form-group">
                            <label for="training_content">Training Content</label>
                            <?php if ($is_edit && $training['content_path']): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-file"></i> Current file: <strong><?php echo basename($training['content_path']); ?></strong>
                                    <br>Upload a new file to replace it (optional)
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="training_content" name="training_content" accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.zip">
                            <span class="help-block">Supported formats: PDF, DOC, DOCX, PPT, PPTX, MP4, ZIP (Max 50MB)</span>
                        </div>

                        <div class="row">
                            <!-- Duration -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="duration_minutes">Duration (Minutes)</label>
                                    <input type="number" class="form-control" id="duration_minutes" name="duration_minutes"
                                           value="<?php echo htmlspecialchars($training['duration_minutes']); ?>" min="1">
                                    <span class="help-block">Estimated completion time</span>
                                </div>
                            </div>

                            <!-- Passing Score -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="passing_score">Passing Score (%)</label>
                                    <input type="number" class="form-control" id="passing_score" name="passing_score"
                                           value="<?php echo htmlspecialchars($training['passing_score']); ?>" min="0" max="100">
                                    <span class="help-block">Required score to pass (if quiz)</span>
                                </div>
                            </div>

                            <!-- Due Days -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="due_days">Due Within (Days) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="due_days" name="due_days"
                                           value="<?php echo htmlspecialchars($training['due_days']); ?>" min="1" required>
                                    <span class="help-block">Days to complete after assignment</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="form-group">
                            <label for="status">Status <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="draft" <?php echo $training['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="active" <?php echo $training['status'] === 'active' ? 'selected' : ''; ?>>Active (available for assignment)</option>
                                <option value="archived" <?php echo $training['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                            <span class="help-block">Only active training can be assigned to staff</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> <?php echo $is_edit ? 'Update' : 'Create'; ?> Training Course
                            </button>
                            <a href="training_list.php" class="btn btn-default btn-lg">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Help Panel -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <i class="fa fa-info-circle"></i> Training Management Best Practices
                </div>
                <div class="panel-body">
                    <ul>
                        <li><strong>Annual Training:</strong> Data protection training should be completed by all staff annually</li>
                        <li><strong>Role-Based Training:</strong> Consider creating different training for different roles (e.g., DPO, IT, HR)</li>
                        <li><strong>Completion Tracking:</strong> Set realistic due dates and monitor completion rates</li>
                        <li><strong>CDPA Compliance:</strong> Zimbabwe CDPA requires organizations to train staff on data protection principles</li>
                        <li><strong>Content Formats:</strong> Use PDF for documents, PPT for presentations, MP4 for videos, or ZIP for multi-file courses</li>
                        <li><strong>Assessments:</strong> Include quizzes to verify understanding and set a passing score (typically 70-80%)</li>
                    </ul>
                </div>
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
