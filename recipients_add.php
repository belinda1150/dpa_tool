<?php
/**
 * DPA Tool - Add New Recipient
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
        // Check if recipient name already exists in this organization
        $check_query = "SELECT recipient_id FROM recipients WHERE org_id = ? AND recipient_name = ?";
        $check_stmt = db_query($check_query, [$org_id, $recipient_name]);
        if (db_fetch_one($check_stmt)) {
            $errors[] = 'Recipient name already exists';
        }
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }

    if (empty($errors)) {
        $query = "INSERT INTO recipients (org_id, recipient_name, recipient_type, contact_person, email, phone, country)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = db_query($query, [
            $org_id,
            $recipient_name,
            $recipient_type,
            !empty($contact_person) ? $contact_person : null,
            !empty($email) ? $email : null,
            !empty($phone) ? $phone : null,
            !empty($country) ? $country : null
        ]);

        if ($stmt) {
            $recipient_id = db_insert_id();
            log_audit($org_id, get_current_user_id(), 'recipient', $recipient_id, 'create');
            set_flash_message('Recipient created successfully', 'success');
            redirect('recipients.php');
        } else {
            $errors[] = 'Failed to create recipient';
        }
    }
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Add New Recipient</title>
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
                        <h2>Add New Recipient</h2>
                        <h5>Create a new data recipient or third party</h5>
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
                                <i class="fa fa-building"></i> Recipient Information
                            </div>
                            <div class="panel-body">
                                <form method="post" action="recipients_add.php">
                                    <div class="form-group">
                                        <label>Recipient Name <span class="text-danger">*</span></label>
                                        <input type="text" name="recipient_name" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['recipient_name'] ?? ''); ?>"
                                               placeholder="e.g., Cloud Service Provider Ltd, Marketing Agency Inc"
                                               required>
                                    </div>

                                    <div class="form-group">
                                        <label>Recipient Type <span class="text-danger">*</span></label>
                                        <select name="recipient_type" class="form-control" required>
                                            <option value="processor" <?php echo (isset($_POST['recipient_type']) && $_POST['recipient_type'] == 'processor') ? 'selected' : ''; ?>>
                                                Processor (processes data on your behalf)
                                            </option>
                                            <option value="controller" <?php echo (isset($_POST['recipient_type']) && $_POST['recipient_type'] == 'controller') ? 'selected' : ''; ?>>
                                                Controller (determines purpose and means)
                                            </option>
                                            <option value="authority" <?php echo (isset($_POST['recipient_type']) && $_POST['recipient_type'] == 'authority') ? 'selected' : ''; ?>>
                                                Authority (regulatory/government body)
                                            </option>
                                            <option value="other" <?php echo (isset($_POST['recipient_type']) && $_POST['recipient_type'] == 'other') ? 'selected' : ''; ?>>
                                                Other
                                            </option>
                                        </select>
                                        <small class="help-block">Select the type of data recipient</small>
                                    </div>

                                    <div class="form-group">
                                        <label>Country</label>
                                        <input type="text" name="country" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['country'] ?? ''); ?>"
                                               placeholder="e.g., United Kingdom, United States, Germany">
                                        <small class="help-block">Where is this recipient located?</small>
                                    </div>

                                    <hr>
                                    <h4>Contact Information</h4>

                                    <div class="form-group">
                                        <label>Contact Person</label>
                                        <input type="text" name="contact_person" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['contact_person'] ?? ''); ?>"
                                               placeholder="e.g., John Smith, Data Protection Officer">
                                    </div>

                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                               placeholder="contact@example.com">
                                    </div>

                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" class="form-control"
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                               placeholder="+44 20 1234 5678">
                                    </div>

                                    <hr>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-save"></i> Create Recipient
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
                                <i class="fa fa-info-circle"></i> About Recipients
                            </div>
                            <div class="panel-body">
                                <p><strong>Recipients</strong> are organizations or entities that receive personal data from you.</p>

                                <p><strong>Common examples:</strong></p>
                                <ul>
                                    <li>Cloud hosting providers</li>
                                    <li>Payment processors</li>
                                    <li>Marketing agencies</li>
                                    <li>Legal advisors</li>
                                    <li>Regulatory authorities</li>
                                </ul>

                                <p class="text-muted"><small><i class="fa fa-lightbulb-o"></i> Tip: Under GDPR Article 30, you must document all recipients of personal data in your Record of Processing Activities (ROPA).</small></p>
                            </div>
                        </div>
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
