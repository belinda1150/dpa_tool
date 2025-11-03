<?php
/**
 * Add New DSR Request
 * Register data subject rights request
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_type = sanitize_input($_POST['request_type'] ?? '');
    $subject_name = sanitize_input($_POST['subject_name'] ?? '');
    $subject_email = sanitize_input($_POST['subject_email'] ?? '');
    $subject_phone = sanitize_input($_POST['subject_phone'] ?? '');
    $subject_ref = sanitize_input($_POST['subject_ref'] ?? '');
    $received_at = sanitize_input($_POST['received_at'] ?? date('Y-m-d H:i:s'));
    $request_details = sanitize_input($_POST['request_details'] ?? '');
    $request_method = sanitize_input($_POST['request_method'] ?? '');
    $assigned_to = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
    $ropa_id = !empty($_POST['ropa_id']) ? intval($_POST['ropa_id']) : null;
    $priority = sanitize_input($_POST['priority'] ?? 'medium');

    // Validation
    $errors = [];
    if (empty($request_type)) $errors[] = "Request type is required.";
    if (empty($subject_name)) $errors[] = "Data subject name is required.";
    if (empty($subject_email)) $errors[] = "Data subject email is required.";
    if (empty($request_details)) $errors[] = "Request details are required.";
    // Request method removed - not in database

    // Validate email
    if (!empty($subject_email) && !filter_var($subject_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address format.";
    }

    if (empty($errors)) {
        // Generate unique request reference
        $request_ref = 'DSR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        $query = "INSERT INTO dsr_requests (
                  org_id, request_ref, request_type, subject_name, subject_email, subject_phone,
                  subject_ref, request_details,
                  assigned_to, status, created_by
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'received', ?)";

        $stmt = db_query($query, [
            $org_id, $request_ref, $request_type, $subject_name, $subject_email, $subject_phone,
            $subject_ref, $request_details,
            $assigned_to, $user_id
        ]);

        $dsr_id = db_last_insert_id();

        // Log audit
        log_audit($org_id, $user_id, 'dsr_request', $dsr_id, 'create', "Created DSR request: $request_type for $subject_name");

        // Notify assigned user
        if ($assigned_to) {
            create_notification(
                $org_id,
                $assigned_to,
                'dsr_assigned',
                'New DSR Request Assigned',
                "A $request_type request has been assigned to you for $subject_name. Response due within 30 days.",
                'dsr_request',
                $dsr_id,
                "dsr_view.php?id=$dsr_id",
                'medium'
            );
        }

        // Notify DPO
        $dpo_query = "SELECT user_id FROM users WHERE org_id = ? AND role_id = 2 LIMIT 1";
        $dpo = db_fetch_one(db_query($dpo_query, [$org_id]));
        if ($dpo && $dpo['user_id'] != $assigned_to) {
            create_notification(
                $org_id,
                $dpo['user_id'],
                'dsr_new',
                'New DSR Request Received',
                "A new $request_type request has been received from $subject_name.",
                'dsr_request',
                $dsr_id,
                "dsr_view.php?id=$dsr_id",
                'medium'
            );
        }

        set_flash_message('DSR request created successfully! Response due within 30 days.', 'success');
        redirect("dsr_view.php?id=$dsr_id");
    }
}

// Fetch users for assignment
$users_query = "SELECT user_id, first_name, last_name, email FROM users WHERE org_id = ? ORDER BY first_name, last_name";
$users = db_fetch_all(db_query($users_query, [$org_id]));

// Fetch ROPA entries
$ropa_query = "SELECT ropa_id, activity_name FROM processing_activities WHERE org_id = ? AND status = 'active' ORDER BY activity_name";
$ropa_entries = db_fetch_all(db_query($ropa_query, [$org_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - New DSR Request</title>
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
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1>
                    <i class="fa fa-plus-circle"></i> Register New DSR Request
                    <small>Data Subject Rights Request</small>
                </h1>
                <ol class="breadcrumb">
                    <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                    <li><a href="dsr_list.php">DSR Requests</a></li>
                    <li class="active">New Request</li>
                </ol>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <strong>Validation Errors:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="dsr_add.php" id="dsrForm">
        <div class="row">
            <div class="col-md-8">
                <!-- Request Type -->
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-gavel"></i> Request Type</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Type of Request <span class="text-danger">*</span></label>
                            <select name="request_type" id="requestType" class="form-control" required>
                                <option value="">-- Select Request Type --</option>
                                <option value="Right to Access">Right to Access (CDPA s.15) - Access to personal data</option>
                                <option value="Right to Rectification">Right to Rectification (CDPA s.16) - Correct inaccurate data</option>
                                <option value="Right to Erasure">Right to Erasure (CDPA s.17) - Delete personal data</option>
                                <option value="Right to Data Portability">Right to Data Portability (CDPA s.18) - Obtain data in structured format</option>
                                <option value="Right to Restriction">Right to Restriction (CDPA s.19) - Restrict processing</option>
                                <option value="Right to Object">Right to Object (CDPA s.20) - Object to processing</option>
                                <option value="Automated Decision-Making">Rights Related to Automated Decision-Making (CDPA s.21)</option>
                                <option value="Withdraw Consent">Right to Withdraw Consent (CDPA s.22)</option>
                            </select>
                        </div>

                        <div id="requestInfo" class="alert alert-info" style="display: none; margin-bottom: 0;">
                            <strong>About this request type:</strong>
                            <p id="requestInfoText" style="margin: 10px 0 0 0;"></p>
                        </div>
                    </div>
                </div>

                <!-- Data Subject Information -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-user"></i> Data Subject Information</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="subject_name" class="form-control" required
                                   placeholder="Full name of data subject making the request">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="subject_email" class="form-control" required
                                           placeholder="email@example.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="text" name="subject_phone" class="form-control"
                                           placeholder="+263...">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ID Number / Passport</label>
                            <input type="text" name="subject_id_number" class="form-control"
                                   placeholder="For identity verification purposes">
                            <small class="text-muted">Used to verify the identity of the data subject</small>
                        </div>
                    </div>
                </div>

                <!-- Request Details -->
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-file-text"></i> Request Details</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Request Date <span class="text-danger">*</span></label>
                                    <input type="date" name="request_date" class="form-control" required
                                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                                    <small class="text-muted">Date the request was received</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Request Method <span class="text-danger">*</span></label>
                                    <select name="request_method" class="form-control" required>
                                        <option value="">-- Select Method --</option>
                                        <option value="Email">Email</option>
                                        <option value="Letter">Letter / Post</option>
                                        <option value="Phone">Phone Call</option>
                                        <option value="In Person">In Person</option>
                                        <option value="Online Form">Online Form</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Request Description <span class="text-danger">*</span></label>
                            <textarea name="request_description" class="form-control" rows="6" required
                                      placeholder="Detailed description of what the data subject is requesting. Include all relevant details such as:&#10;- Specific data or processing activities they're referring to&#10;- Reasons for the request (if provided)&#10;- Any specific timeframes or conditions mentioned&#10;- Supporting documentation provided"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Linked Processing Activity (ROPA)</label>
                            <select name="ropa_id" class="form-control">
                                <option value="">-- Not Linked --</option>
                                <?php foreach ($ropa_entries as $ropa): ?>
                                    <option value="<?php echo $ropa['ropa_id']; ?>">
                                        <?php echo htmlspecialchars($ropa['activity_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Link to the processing activity this request relates to (if applicable)</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Assignment & Priority -->
                <div class="panel panel-warning">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-tasks"></i> Assignment & Priority</h3>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label>Assign To</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['user_id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Assign to staff member responsible for processing</small>
                        </div>

                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority" class="form-control">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                            <strong><i class="fa fa-clock-o"></i> SLA Reminder:</strong><br>
                            Response due within <strong>30 days</strong> of request date per CDPA requirements.
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="panel panel-default">
                    <div class="panel-body">
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            <i class="fa fa-save"></i> Register DSR Request
                        </button>
                        <a href="dsr_list.php" class="btn btn-default btn-block">
                            <i class="fa fa-times"></i> Cancel
                        </a>
                    </div>
                </div>

                <!-- Quick Reference -->
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-info-circle"></i> Quick Reference</h3>
                    </div>
                    <div class="panel-body">
                        <p><strong>Response Timeline:</strong></p>
                        <ul style="font-size: 12px; margin-bottom: 0;">
                            <li><strong>Day 1:</strong> Acknowledge receipt</li>
                            <li><strong>Day 1-7:</strong> Verify identity</li>
                            <li><strong>Day 7-25:</strong> Process request</li>
                            <li><strong>Day 25-30:</strong> Respond to data subject</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Show request type information
    $('#requestType').change(function() {
        var type = $(this).val();
        var infoText = '';

        switch(type) {
            case 'Right to Access':
                infoText = 'Provide confirmation of processing and access to a copy of their personal data. Must be provided free of charge in commonly used electronic format.';
                break;
            case 'Right to Rectification':
                infoText = 'Correct or complete any inaccurate or incomplete personal data. Update records and notify any third parties who received the data.';
                break;
            case 'Right to Erasure':
                infoText = 'Delete personal data when no longer necessary, consent withdrawn, or unlawful processing. Consider legal retention requirements.';
                break;
            case 'Right to Data Portability':
                infoText = 'Provide personal data in structured, commonly used, machine-readable format (e.g., CSV, JSON). Data subject can transmit to another controller.';
                break;
            case 'Right to Restriction':
                infoText = 'Temporarily restrict processing while verifying accuracy, assessing lawfulness, or during objection period. Data can be stored but not processed.';
                break;
            case 'Right to Object':
                infoText = 'Stop processing for direct marketing, legitimate interests, or research purposes. Must stop unless compelling legitimate grounds exist.';
                break;
            case 'Automated Decision-Making':
                infoText = 'Request human intervention in automated decisions. Explain the logic involved and provide opportunity to contest the decision.';
                break;
            case 'Withdraw Consent':
                infoText = 'Withdraw previously given consent. Must stop processing based on that consent. Withdrawal does not affect lawfulness of past processing.';
                break;
        }

        if (infoText) {
            $('#requestInfoText').text(infoText);
            $('#requestInfo').slideDown();
        } else {
            $('#requestInfo').slideUp();
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
