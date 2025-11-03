<?php
/**
 * DPA Tool - Edit Recipient
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

// Get recipient ID
$recipient_id = intval($_GET['id'] ?? 0);

if (!$recipient_id) {
    set_flash_message('Invalid recipient ID', 'error');
    redirect('recipients.php');
}

// Get recipient details
$recipient_query = "SELECT * FROM recipients WHERE recipient_id = ? AND org_id = ?";
$recipient_stmt = db_query($recipient_query, [$recipient_id, $org_id]);
$recipient = db_fetch_one($recipient_stmt);

if (!$recipient) {
    set_flash_message('Recipient not found', 'error');
    redirect('recipients.php');
}

// Get usage count
$count_query = "SELECT COUNT(*) as usage_count FROM ropa_recipients WHERE recipient_id = ?";
$count_stmt = db_query($count_query, [$recipient_id]);
$count_result = db_fetch_one($count_stmt);
$usage_count = $count_result['usage_count'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_name = sanitize_input($_POST['recipient_name'] ?? '');
    $recipient_type = sanitize_input($_POST['recipient_type'] ?? 'processor');
    $contact_person = sanitize_input($_POST['contact_person'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $country = sanitize_input($_POST['country'] ?? '');

    // Validation
    if (empty($recipient_name)) {
        $errors[] = 'Recipient name is required';
    } else {
        // Check if recipient name already exists (excluding current recipient)
        $check_query = "SELECT recipient_id FROM recipients WHERE org_id = ? AND recipient_name = ? AND recipient_id != ?";
        $check_stmt = db_query($check_query, [$org_id, $recipient_name, $recipient_id]);
        if (db_fetch_one($check_stmt)) {
            $errors[] = 'Recipient name already exists';
        }
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }

    if (empty($errors)) {
        $query = "UPDATE recipients SET
                  recipient_name = ?,
                  recipient_type = ?,
                  contact_person = ?,
                  email = ?,
                  phone = ?,
                  country = ?
                  WHERE recipient_id = ? AND org_id = ?";

        $stmt = db_query($query, [
            $recipient_name,
            $recipient_type,
            !empty($contact_person) ? $contact_person : null,
            !empty($email) ? $email : null,
            !empty($phone) ? $phone : null,
            !empty($country) ? $country : null,
            $recipient_id,
            $org_id
        ]);

        if ($stmt) {
            log_audit($org_id, get_current_user_id(), 'recipient', $recipient_id, 'update');
            set_flash_message('Recipient updated successfully', 'success');
            redirect('recipients.php');
        } else {
            $errors[] = 'Failed to update recipient';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Edit Recipient</title>
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
                        <h2>Edit Recipient</h2>
                        <h5>Update recipient information</h5>
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
                                <i class="fa fa-edit"></i> Recipient Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="recipients_edit.php?id=<?php echo $recipient_id; ?>">
                                    <div class="form-group">
                                        <label>Recipient Name <span class="text-danger">*</span></label>
                                        <input type="text" name="recipient_name" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['recipient_name'] ?? $recipient['recipient_name']); ?>"
                                               placeholder="e.g., Cloud Service Provider Ltd"
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label>Recipient Type <span class="text-danger">*</span></label>
                                        <select name="recipient_type" class="form-control" required>
                                            <?php
                                            $selected_type = $_POST['recipient_type'] ?? $recipient['recipient_type'];
                                            ?>
                                            <option value="processor" <?php echo ($selected_type == 'processor') ? 'selected' : ''; ?>>
                                                Processor (processes data on your behalf)
                                            </option>
                                            <option value="controller" <?php echo ($selected_type == 'controller') ? 'selected' : ''; ?>>
                                                Controller (determines purpose and means)
                                            </option>
                                            <option value="authority" <?php echo ($selected_type == 'authority') ? 'selected' : ''; ?>>
                                                Authority (regulatory/government body)
                                            </option>
                                            <option value="other" <?php echo ($selected_type == 'other') ? 'selected' : ''; ?>>
                                                Other
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label>Country</label>
                                        <input type="text" name="country" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['country'] ?? $recipient['country'] ?? ''); ?>"
                                               placeholder="e.g., United Kingdom, United States">
                                    </div>

                                    <hr>
                                    <h4>Contact Information</h4>

                                    <div class="form-group">
                                        <label>Contact Person</label>
                                        <input type="text" name="contact_person" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['contact_person'] ?? $recipient['contact_person'] ?? ''); ?>"
                                               placeholder="e.g., John Smith">
                                    </div>

                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? $recipient['email'] ?? ''); ?>"
                                               placeholder="contact@example.com">
                                    </div>

                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $recipient['phone'] ?? ''); ?>"
                                               placeholder="+44 20 1234 5678">
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Update Recipient
                                        </button>
                                        <a href="recipients.php" class="btn btn-default">
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
                                <i class="fa fa-info-circle"></i> Recipient Details
                            </div>
                            <div class="panel-body">
                                <dl>
                                    <dt>Recipient ID</dt>
                                    <dd><?php echo $recipient['recipient_id']; ?></dd>

                                    <dt>Usage in ROPAs</dt>
                                    <dd>
                                        <span class="badge badge-info"><?php echo $usage_count; ?></span>
                                        <?php echo $usage_count == 1 ? 'ROPA entry' : 'ROPA entries'; ?>
                                    </dd>

                                    <dt>Created</dt>
                                    <dd><?php echo format_datetime($recipient['created_at'], 'd M Y H:i'); ?></dd>
                                </dl>
                            </div>
                        </div>

                        <?php if ($usage_count > 0): ?>
                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <i class="fa fa-exclamation-triangle"></i> Warning
                            </div>
                            <div class="panel-body">
                                <p><strong>Note:</strong> This recipient is used in <?php echo $usage_count; ?> ROPA <?php echo $usage_count == 1 ? 'entry' : 'entries'; ?>.</p>
                                <p>Deleting this recipient will remove it from all associated ROPA entries.</p>
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
    <script src="assets/js/jquery.metisMenu.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
