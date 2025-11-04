<?php
/**
 * View Consent Details
 * Complete consent information with withdrawal option
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$consent_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($consent_id <= 0) {
    set_flash_message('Invalid consent ID.', 'danger');
    redirect('consent_list.php');
}

// Handle withdrawal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'withdraw') {
    $withdrawal_reason = sanitize_input($_POST['withdrawal_reason'] ?? '');
    $withdrawal_method = sanitize_input($_POST['withdrawal_method_used'] ?? '');

    $query = "UPDATE consents SET
              status = 'withdrawn',
              withdrawal_date = NOW(),
              withdrawal_reason = ?,
              withdrawal_method_used = ?,
              withdrawn_by = ?
              WHERE consent_id = ? AND org_id = ?";

    db_query($query, [$withdrawal_reason, $withdrawal_method, $user_id, $consent_id, $org_id]);

    log_audit($org_id, $user_id, 'consent', $consent_id, 'withdraw', "Consent withdrawn");

    set_flash_message('Consent withdrawn successfully. Processing based on this consent must stop immediately.', 'warning');
    redirect("consent_view.php?id=$consent_id");
}

// Fetch consent details
$query = "SELECT c.*,
          creator.first_name as creator_first, creator.last_name as creator_last,
          withdrawer.first_name as withdrawer_first, withdrawer.last_name as withdrawer_last,
          pa.activity_name,
          DATEDIFF(c.expiry_date, NOW()) as days_to_expiry,
          DATEDIFF(NOW(), c.consent_date) as consent_age_days
          FROM consents c
          LEFT JOIN users creator ON c.created_by = creator.user_id
          LEFT JOIN users withdrawer ON c.withdrawn_by = withdrawer.user_id
          LEFT JOIN processing_activities pa ON c.ropa_id = pa.ropa_id
          WHERE c.consent_id = ? AND c.org_id = ?";

$consent = db_fetch_one(db_query($query, [$consent_id, $org_id]));

if (!$consent) {
    set_flash_message('Consent not found.', 'danger');
    redirect('consent_list.php');
}

// Fetch activity history
$history_query = "SELECT * FROM audit_log
                  WHERE org_id = ? AND entity_type = 'consent' AND entity_id = ?
                  ORDER BY created_at DESC";
$history = db_fetch_all(db_query($history_query, [$org_id, $consent_id]));

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - View Consent</title>
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
                        <h2>Consent Details</h2>
                        <h5>CNS-<?php echo str_pad($consent_id, 5, '0', STR_PAD_LEFT); ?></h5>
                    </div>
                </div>
                <hr />

    <div class="row">
        <div class="col-md-8">
            <!-- Consent Summary -->
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Consent Summary</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Consent ID:</th>
                            <td><strong>CNS-<?php echo str_pad($consent_id, 5, '0', STR_PAD_LEFT); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Purpose:</th>
                            <td>
                                <strong><?php echo htmlspecialchars($consent['purpose']); ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php
                                $status_class = [
                                    'active' => 'success',
                                    'withdrawn' => 'warning',
                                    'expired' => 'danger'
                                ][$consent['status']] ?? 'default';
                                echo "<span class='label label-$status_class' style='font-size: 14px;'>" . ucfirst($consent['status']) . "</span>";
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Consent Date:</th>
                            <td><strong><?php echo date('d F Y', strtotime($consent['consent_date'])); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Consent Method:</th>
                            <td><?php echo htmlspecialchars($consent['consent_method']); ?></td>
                        </tr>
                        <tr>
                            <th>Consent Age:</th>
                            <td><?php echo $consent['consent_age_days']; ?> days</td>
                        </tr>
                        <?php if ($consent['expiry_date']): ?>
                        <tr>
                            <th>Expiry Date:</th>
                            <td>
                                <?php echo date('d F Y', strtotime($consent['expiry_date'])); ?>
                                <?php if ($consent['status'] == 'active'): ?>
                                    <?php if ($consent['days_to_expiry'] <= 0): ?>
                                        <span class="label label-danger"><i class="fa fa-exclamation-triangle"></i> Expired</span>
                                    <?php elseif ($consent['days_to_expiry'] <= 30): ?>
                                        <span class="label label-warning"><i class="fa fa-clock-o"></i> Expires in <?php echo $consent['days_to_expiry']; ?> days</span>
                                    <?php else: ?>
                                        <span class="label label-success"><i class="fa fa-check"></i> Valid</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Linked ROPA:</th>
                            <td>
                                <?php
                                if ($consent['activity_name']) {
                                    echo '<a href="ropa_view.php?id=' . $consent['ropa_id'] . '">' . htmlspecialchars($consent['activity_name']) . '</a>';
                                } else {
                                    echo '<span class="text-muted">Not Linked</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Data Subject Information -->
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-user"></i> Data Subject Information</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Name:</th>
                            <td><strong><?php echo htmlspecialchars($consent['subject_name']); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Email:</th>
                            <td><?php echo htmlspecialchars($consent['subject_email']); ?></td>
                        </tr>
                        <tr>
                            <th>Phone:</th>
                            <td><?php echo htmlspecialchars($consent['subject_phone'] ?? 'Not provided'); ?></td>
                        </tr>
                        <tr>
                            <th>ID Number:</th>
                            <td><?php echo htmlspecialchars($consent['subject_id_number'] ?? 'Not provided'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Purpose Details -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-file-text"></i> Purpose & Scope</h3>
                </div>
                <div class="panel-body">
                    <?php if ($consent['purpose_description']): ?>
                    <p><strong>Purpose Description:</strong></p>
                    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap; margin-bottom: 20px;">
<?php echo htmlspecialchars($consent['purpose_description']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($consent['data_categories']): ?>
                    <p><strong>Data Categories Covered:</strong></p>
                    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap; margin-bottom: 20px;">
<?php echo htmlspecialchars($consent['data_categories']); ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($consent['retention_period']): ?>
                    <p><strong>Retention Period:</strong></p>
                    <p><?php echo htmlspecialchars($consent['retention_period']); ?></p>
                    <?php endif; ?>

                    <?php if ($consent['withdrawal_method']): ?>
                    <p><strong>How to Withdraw Consent:</strong></p>
                    <div style="background-color: #fffbf0; padding: 15px; border: 1px solid #f0e68c; border-radius: 4px; white-space: pre-wrap; margin-bottom: 0;">
<?php echo htmlspecialchars($consent['withdrawal_method']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Consent Evidence -->
            <?php if ($consent['consent_evidence']): ?>
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-check-circle"></i> Consent Evidence</h3>
                </div>
                <div class="panel-body">
                    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px; white-space: pre-wrap;">
<?php echo htmlspecialchars($consent['consent_evidence']); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Withdrawal Details (if withdrawn) -->
            <?php if ($consent['status'] == 'withdrawn'): ?>
            <div class="panel panel-warning">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-times-circle"></i> Withdrawal Details</h3>
                </div>
                <div class="panel-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="25%">Withdrawal Date:</th>
                            <td><strong><?php echo date('d F Y, H:i', strtotime($consent['withdrawal_date'])); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Withdrawn By:</th>
                            <td>
                                <?php
                                if ($consent['withdrawer_first']) {
                                    echo htmlspecialchars($consent['withdrawer_first'] . ' ' . $consent['withdrawer_last']);
                                } else {
                                    echo 'System';
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Method Used:</th>
                            <td><?php echo htmlspecialchars($consent['withdrawal_method_used'] ?? 'Not specified'); ?></td>
                        </tr>
                    </table>

                    <?php if ($consent['withdrawal_reason']): ?>
                    <p><strong>Reason for Withdrawal:</strong></p>
                    <div style="background-color: #fff3cd; padding: 15px; border: 1px solid #f0ad4e; border-radius: 4px; white-space: pre-wrap;">
<?php echo htmlspecialchars($consent['withdrawal_reason']); ?>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                        <strong><i class="fa fa-warning"></i> CDPA s.22:</strong>
                        All processing based on this consent must have stopped immediately upon withdrawal.
                        Data may only be retained if another lawful basis exists.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Activity History -->
            <?php if (!empty($history)): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-history"></i> Activity History</h3>
                </div>
                <div class="panel-body">
                    <div class="timeline">
                        <?php foreach ($history as $log): ?>
                        <div style="margin-bottom: 15px; padding-left: 30px; border-left: 2px solid #3498db; position: relative;">
                            <div style="position: absolute; left: -8px; top: 0; width: 14px; height: 14px; border-radius: 50%; background: #3498db;"></div>
                            <div>
                                <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                                <small class="text-muted pull-right">
                                    <?php echo date('d M Y, H:i', strtotime($log['created_at'])); ?>
                                </small>
                            </div>
                            <p class="text-muted" style="margin: 5px 0 0 0; font-size: 12px;">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- Status Panel -->
            <div class="panel panel-<?php echo $consent['status'] == 'active' ? 'success' : 'warning'; ?>">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info-circle"></i> Consent Status</h3>
                </div>
                <div class="panel-body text-center">
                    <?php if ($consent['status'] == 'active'): ?>
                        <h2 style="margin: 10px 0; color: #27ae60;">
                            <i class="fa fa-check-circle"></i> ACTIVE
                        </h2>
                        <p class="text-muted">This consent is currently valid</p>
                    <?php elseif ($consent['status'] == 'withdrawn'): ?>
                        <h2 style="margin: 10px 0; color: #f39c12;">
                            <i class="fa fa-times-circle"></i> WITHDRAWN
                        </h2>
                        <p class="text-muted">Processing based on this consent must stop</p>
                    <?php else: ?>
                        <h2 style="margin: 10px 0; color: #c0392b;">
                            <i class="fa fa-calendar-times-o"></i> EXPIRED
                        </h2>
                        <p class="text-muted">This consent has expired</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions -->
            <?php if ($consent['status'] == 'active'): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-cogs"></i> Actions</h3>
                </div>
                <div class="panel-body">
                    <?php if ($consent['expiry_date'] && $consent['days_to_expiry'] <= 60): ?>
                    <a href="consent_renew.php?id=<?php echo $consent_id; ?>" class="btn btn-success btn-block">
                        <i class="fa fa-refresh"></i> Renew Consent
                    </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#withdrawModal">
                        <i class="fa fa-times-circle"></i> Withdraw Consent
                    </button>
                    <a href="consent_export.php?id=<?php echo $consent_id; ?>&format=pdf" class="btn btn-primary btn-block">
                        <i class="fa fa-file-pdf-o"></i> Export PDF
                    </a>
                </div>
            </div>
            <?php elseif ($consent['status'] == 'expired'): ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-cogs"></i> Actions</h3>
                </div>
                <div class="panel-body">
                    <a href="consent_renew.php?id=<?php echo $consent_id; ?>" class="btn btn-success btn-block">
                        <i class="fa fa-refresh"></i> Renew Consent
                    </a>
                    <a href="consent_export.php?id=<?php echo $consent_id; ?>&format=pdf" class="btn btn-primary btn-block">
                        <i class="fa fa-file-pdf-o"></i> Export PDF
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-download"></i> Export</h3>
                </div>
                <div class="panel-body">
                    <a href="consent_export.php?id=<?php echo $consent_id; ?>&format=pdf" class="btn btn-primary btn-block">
                        <i class="fa fa-file-pdf-o"></i> Export PDF
                    </a>
                    <a href="consent_export.php?id=<?php echo $consent_id; ?>&format=csv" class="btn btn-default btn-block">
                        <i class="fa fa-file-excel-o"></i> Export CSV
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- Metadata -->
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title"><i class="fa fa-info"></i> Metadata</h3>
                </div>
                <div class="panel-body">
                    <p class="text-muted" style="margin: 0; font-size: 12px;">
                        <strong>Recorded By:</strong><br>
                        <?php echo htmlspecialchars($consent['creator_first'] . ' ' . $consent['creator_last']); ?><br>
                        <?php echo date('d M Y, H:i', strtotime($consent['created_at'])); ?>
                    </p>
                    <hr style="margin: 10px 0;">
                    <p class="text-muted" style="margin: 0; font-size: 12px;">
                        <strong>Last Updated:</strong><br>
                        <?php echo date('d M Y, H:i', strtotime($consent['updated_at'])); ?>
                    </p>
                </div>
            </div>

            <a href="consent_list.php" class="btn btn-default btn-block">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
        </div>
    </div>
</div>

<!-- Withdrawal Modal -->
<div class="modal fade" id="withdrawModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="consent_view.php?id=<?php echo $consent_id; ?>">
                <input type="hidden" name="action" value="withdraw">
                <div class="modal-header" style="background-color: #f39c12; color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-times-circle"></i> Withdraw Consent</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong><i class="fa fa-exclamation-triangle"></i> Warning:</strong>
                        Withdrawing consent will immediately stop all processing activities based on this consent.
                        This action cannot be undone.
                    </div>

                    <div class="form-group">
                        <label>Withdrawal Method Used <span class="text-danger">*</span></label>
                        <select name="withdrawal_method_used" class="form-control" required>
                            <option value="">-- Select Method --</option>
                            <option value="Email Request">Email Request from Data Subject</option>
                            <option value="Online Form">Online Withdrawal Form</option>
                            <option value="Unsubscribe Link">Unsubscribe Link</option>
                            <option value="Phone Request">Phone Request</option>
                            <option value="Written Request">Written Request / Letter</option>
                            <option value="Account Settings">Account Settings / Portal</option>
                            <option value="In Person">In-Person Request</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reason for Withdrawal (Optional)</label>
                        <textarea name="withdrawal_reason" class="form-control" rows="4"
                                  placeholder="Document the data subject's reason for withdrawing consent, if provided"></textarea>
                    </div>

                    <p style="font-size: 12px; color: #666;">
                        <strong>CDPA s.22:</strong> The data subject has the right to withdraw consent at any time.
                        Withdrawal does not affect the lawfulness of processing based on consent before withdrawal.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-times-circle"></i> Confirm Withdrawal
                    </button>
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
