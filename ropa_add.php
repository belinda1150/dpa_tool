<?php
/**
 * DPA Tool - Add ROPA Entry
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';
require_login();

$org_id = get_current_org_id();
$user_id = get_current_user_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $activity_name = sanitize_input($_POST['activity_name'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $dept_id = !empty($_POST['dept_id']) ? intval($_POST['dept_id']) : null;
    $purpose_id = !empty($_POST['purpose_id']) ? intval($_POST['purpose_id']) : null;
    $lawful_basis_id = !empty($_POST['lawful_basis_id']) ? intval($_POST['lawful_basis_id']) : null;
    $retention_id = !empty($_POST['retention_id']) ? intval($_POST['retention_id']) : null;
    $data_source = sanitize_input($_POST['data_source'] ?? 'internal');
    $estimated_subjects = intval($_POST['estimated_data_subjects'] ?? 0);
    $security_measures = sanitize_input($_POST['security_measures'] ?? '');
    $status = sanitize_input($_POST['status'] ?? 'draft');

    $has_special_categories = isset($_POST['has_special_categories']) ? 1 : 0;
    $has_minors = isset($_POST['has_minors']) ? 1 : 0;
    $has_cross_border = isset($_POST['has_cross_border']) ? 1 : 0;

    // Insert ROPA entry
    $query = "INSERT INTO processing_activities
              (org_id, dept_id, activity_name, description, purpose_id, lawful_basis_id, retention_id,
               data_source, has_special_categories, has_minors, has_cross_border, estimated_data_subjects,
               security_measures, status, created_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = db_query($query, [
        $org_id, $dept_id, $activity_name, $description, $purpose_id, $lawful_basis_id, $retention_id,
        $data_source, $has_special_categories, $has_minors, $has_cross_border, $estimated_subjects,
        $security_measures, $status, $user_id
    ]);

    if ($stmt) {
        $ropa_id = db_insert_id();

        // Insert subject categories
        if (!empty($_POST['subject_categories'])) {
            foreach ($_POST['subject_categories'] as $subject_cat_id) {
                $query = "INSERT INTO ropa_subject_categories (ropa_id, subject_category_id) VALUES (?, ?)";
                db_query($query, [$ropa_id, intval($subject_cat_id)]);
            }
        }

        // Insert data categories
        if (!empty($_POST['data_categories'])) {
            foreach ($_POST['data_categories'] as $data_cat_id) {
                $query = "INSERT INTO ropa_data_categories (ropa_id, data_category_id) VALUES (?, ?)";
                db_query($query, [$ropa_id, intval($data_cat_id)]);
            }
        }

        // Insert recipients
        if (!empty($_POST['recipients'])) {
            foreach ($_POST['recipients'] as $recipient_id) {
                $query = "INSERT INTO ropa_recipients (ropa_id, recipient_id) VALUES (?, ?)";
                db_query($query, [$ropa_id, intval($recipient_id)]);
            }
        }

        // Insert storage locations
        if (!empty($_POST['storage_locations'])) {
            foreach ($_POST['storage_locations'] as $location_id) {
                $query = "INSERT INTO ropa_storage_locations (ropa_id, location_id) VALUES (?, ?)";
                db_query($query, [$ropa_id, intval($location_id)]);
            }
        }

        // Log audit
        log_audit($org_id, $user_id, 'ropa', $ropa_id, 'create');

        // Check if DPIA is required
        if ($has_special_categories || $has_minors || $has_cross_border || $estimated_subjects >= DPIA_THRESHOLD_SUBJECTS) {
            create_notification($org_id, $user_id, 'dpia_pending', 'DPIA Required',
                "The processing activity '$activity_name' requires a Data Protection Impact Assessment.",
                'ropa', $ropa_id, "dpia_wizard.php?ropa_id=$ropa_id", 'high');
        }

        set_flash_message('Processing activity added successfully!', 'success');
        redirect('ropa_list.php');
    } else {
        $error_message = 'Failed to add processing activity. Please try again.';
    }
}

// Get departments
$query = "SELECT * FROM departments WHERE org_id = ? ORDER BY dept_name";
$stmt = db_query($query, [$org_id]);
$departments = db_fetch_all($stmt);

// Get purposes
$query = "SELECT * FROM purposes ORDER BY purpose_name";
$stmt = db_query($query);
$purposes = db_fetch_all($stmt);

// Get lawful basis
$query = "SELECT * FROM lawful_basis ORDER BY basis_name";
$stmt = db_query($query);
$lawful_bases = db_fetch_all($stmt);

// Get retention policies
$query = "SELECT * FROM retention_policies WHERE org_id = ? ORDER BY policy_name";
$stmt = db_query($query, [$org_id]);
$retention_policies = db_fetch_all($stmt);

// Get subject categories
$query = "SELECT * FROM subject_categories ORDER BY category_name";
$stmt = db_query($query);
$subject_categories = db_fetch_all($stmt);

// Get data categories
$query = "SELECT * FROM data_categories ORDER BY is_special_category DESC, category_name";
$stmt = db_query($query);
$data_categories = db_fetch_all($stmt);

// Get recipients
$query = "SELECT * FROM recipients WHERE org_id = ? ORDER BY recipient_name";
$stmt = db_query($query, [$org_id]);
$recipients = db_fetch_all($stmt);

// Get storage locations
$query = "SELECT * FROM storage_locations WHERE org_id = ? ORDER BY location_name";
$stmt = db_query($query, [$org_id]);
$storage_locations = db_fetch_all($stmt);

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add ROPA Entry</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .section-title {
            background-color: #f5f5f5;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #00a950;
            font-weight: bold;
        }
        .checkbox-group {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #fafafa;
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
                        <h2>Add Processing Activity</h2>
                        <h5>Create a new Record of Processing Activities (ROPA) entry</h5>
                    </div>
                </div>
                <hr />

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="ropa_add.php">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-edit"></i> Processing Activity Details
                                </div>
                                <div class="panel-body">

                                    <!-- Section 1: Basic Information -->
                                    <div class="section-title">1. Basic Information</div>

                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label>Activity Name <span class="text-danger">*</span></label>
                                                <input type="text" name="activity_name" class="form-control" required
                                                       placeholder="e.g., Employee Payroll Processing">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Department</label>
                                                <select name="dept_id" class="form-control">
                                                    <option value="">Select Department</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                        <option value="<?php echo $dept['dept_id']; ?>">
                                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3"
                                                  placeholder="Describe the processing activity in detail"></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Processing Purpose</label>
                                                <select name="purpose_id" class="form-control">
                                                    <option value="">Select Purpose</option>
                                                    <?php foreach ($purposes as $purpose): ?>
                                                        <option value="<?php echo $purpose['purpose_id']; ?>">
                                                            <?php echo htmlspecialchars($purpose['purpose_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Lawful Basis (CDPA s.22-23)</label>
                                                <select name="lawful_basis_id" class="form-control">
                                                    <option value="">Select Lawful Basis</option>
                                                    <?php foreach ($lawful_bases as $basis): ?>
                                                        <option value="<?php echo $basis['lawful_basis_id']; ?>">
                                                            <?php echo htmlspecialchars($basis['basis_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 2: Data Subjects and Categories -->
                                    <div class="section-title">2. Data Subjects and Categories</div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Categories of Data Subjects</label>
                                                <div class="checkbox-group">
                                                    <?php foreach ($subject_categories as $cat): ?>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="subject_categories[]"
                                                                       value="<?php echo $cat['subject_category_id']; ?>">
                                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                                                <?php if ($cat['is_vulnerable']): ?>
                                                                    <span class="label label-warning">Vulnerable</span>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Categories of Personal Data</label>
                                                <div class="checkbox-group">
                                                    <?php foreach ($data_categories as $cat): ?>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="data_categories[]"
                                                                       value="<?php echo $cat['data_category_id']; ?>">
                                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                                                <?php if ($cat['is_special_category']): ?>
                                                                    <span class="label label-danger">Special Category</span>
                                                                <?php endif; ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Data Source</label>
                                                <select name="data_source" class="form-control">
                                                    <option value="internal">Internal</option>
                                                    <option value="external">External</option>
                                                    <option value="both">Both</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Estimated Data Subjects</label>
                                                <input type="number" name="estimated_data_subjects" class="form-control"
                                                       value="0" min="0">
                                                <small class="text-muted">DPIA required if >= <?php echo DPIA_THRESHOLD_SUBJECTS; ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Retention Policy</label>
                                                <select name="retention_id" class="form-control">
                                                    <option value="">Select Policy</option>
                                                    <?php foreach ($retention_policies as $policy): ?>
                                                        <option value="<?php echo $policy['retention_id']; ?>">
                                                            <?php echo htmlspecialchars($policy['policy_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 3: Recipients and Storage -->
                                    <div class="section-title">3. Recipients and Storage</div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Recipients / Third Parties</label>
                                                <div class="checkbox-group">
                                                    <?php foreach ($recipients as $recipient): ?>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="recipients[]"
                                                                       value="<?php echo $recipient['recipient_id']; ?>">
                                                                <?php echo htmlspecialchars($recipient['recipient_name']); ?>
                                                                <small class="text-muted">(<?php echo htmlspecialchars($recipient['recipient_type']); ?>)</small>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($recipients)): ?>
                                                        <p class="text-muted">No recipients defined. <a href="recipients.php">Add recipients</a></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Storage Locations</label>
                                                <div class="checkbox-group">
                                                    <?php foreach ($storage_locations as $location): ?>
                                                        <div class="checkbox">
                                                            <label>
                                                                <input type="checkbox" name="storage_locations[]"
                                                                       value="<?php echo $location['location_id']; ?>">
                                                                <?php echo htmlspecialchars($location['location_name']); ?>
                                                                <small class="text-muted">(<?php echo htmlspecialchars($location['location_type']); ?>)</small>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($storage_locations)): ?>
                                                        <p class="text-muted">No storage locations defined. <a href="storage_locations.php">Add locations</a></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 4: Security and Flags -->
                                    <div class="section-title">4. Security Measures and Risk Flags</div>

                                    <div class="form-group">
                                        <label>Security Measures</label>
                                        <textarea name="security_measures" class="form-control" rows="3"
                                                  placeholder="Describe technical and organizational security measures (encryption, access controls, etc.)"></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="has_special_categories" value="1">
                                                    <strong>Contains Special Categories of Data</strong>
                                                    <br><small class="text-danger">Triggers DPIA requirement</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="has_minors" value="1">
                                                    <strong>Involves Minors (under 18)</strong>
                                                    <br><small class="text-warning">Triggers DPIA requirement</small>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" name="has_cross_border" value="1">
                                                    <strong>Involves Cross-Border Transfer</strong>
                                                    <br><small class="text-info">Triggers DPIA requirement</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Section 5: Status -->
                                    <div class="section-title">5. Entry Status</div>

                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="draft">Draft</option>
                                            <?php if (is_dpo()): ?>
                                                <option value="validated">Validated</option>
                                            <?php endif; ?>
                                        </select>
                                        <small class="text-muted">Save as draft to continue editing later</small>
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fa fa-save"></i> Save Processing Activity
                                        </button>
                                        <a href="ropa_list.php" class="btn btn-default btn-lg">
                                            <i class="fa fa-times"></i> Cancel
                                        </a>
                                    </div>

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
</body>
</html>
