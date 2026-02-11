<?php
/**
 * Process DSR Request
 * Complete, reject, or update DSR request
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();
$dsr_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($dsr_id <= 0) {
    set_flash_message('Invalid DSR request ID.', 'danger');
    redirect('dsr_list.php');
}

// Fetch DSR details
$query = "SELECT d.*, DATEDIFF(NOW(), d.received_at) as days_elapsed
          FROM dsr_requests d
          WHERE d.dsr_id = ? AND d.org_id = ?";
$dsr = db_fetch_one(db_query($query, [$dsr_id, $org_id]));

if (!$dsr) {
    set_flash_message('DSR request not found.', 'danger');
    redirect('dsr_list.php');
}

if ($dsr['status'] == 'completed' || $dsr['status'] == 'rejected') {
    set_flash_message('This DSR request has already been processed.', 'warning');
    redirect("dsr_view.php?id=$dsr_id");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize_input($_POST['action'] ?? '');

    if ($action == 'verify_identity') {
        // Verify identity
        $verification_method = sanitize_input($_POST['verification_method'] ?? '');
        $verification_notes = sanitize_input($_POST['verification_notes'] ?? '');

        if (empty($verification_method)) {
            set_flash_message('Verification method is required.', 'danger');
        } else {
            $query = "UPDATE dsr_requests SET
                      verified_at = NOW(),
                      verified_by = ?,
                      verification_method = ?,
                      status = 'in_progress'
                      WHERE dsr_id = ? AND org_id = ?";
            db_query($query, [$user_id, $verification_method, $dsr_id, $org_id]);

            log_audit($org_id, $user_id, 'dsr_request', $dsr_id, 'verify', "Identity verified via $verification_method");
            set_flash_message('Identity verified successfully. Request moved to In Progress.', 'success');
            redirect("dsr_process.php?id=$dsr_id");
        }
    } elseif ($action == 'update_notes') {
        // Update internal notes
        $internal_notes = sanitize_input($_POST['internal_notes'] ?? '');

        $query = "UPDATE dsr_requests SET internal_notes = ? WHERE dsr_id = ? AND org_id = ?";
        db_query($query, [$internal_notes, $dsr_id, $org_id]);

        log_audit($org_id, $user_id, 'dsr_request', $dsr_id, 'update', "Updated internal notes");
        set_flash_message('Internal notes updated.', 'success');
        redirect("dsr_process.php?id=$dsr_id");
    } elseif ($action == 'complete') {
        // Complete request
        $response_notes = sanitize_input($_POST['response_notes'] ?? '');

        if (empty($response_notes)) {
            set_flash_message('Response notes are required to complete the request.', 'danger');
        } else {
            $query = "UPDATE dsr_requests SET
                      status = 'completed',
                      completed_at = NOW(),
                      response_summary = ?
                      WHERE dsr_id = ? AND org_id = ?";
            db_query($query, [$response_notes, $dsr_id, $org_id]);

            log_audit($org_id, $user_id, 'dsr_request', $dsr_id, 'complete', "Completed DSR request: {$dsr['request_type']}");

            // Notify data subject (in real implementation, send email)
            // For now, just log it
            set_flash_message('DSR request completed successfully! Data subject should be notified of the response.', 'success');
            redirect("dsr_view.php?id=$dsr_id");
        }
    } elseif ($action == 'reject') {
        // Reject request
        $rejection_reason = sanitize_input($_POST['rejection_reason'] ?? '');

        if (empty($rejection_reason)) {
            set_flash_message('Rejection reason is required.', 'danger');
        } else {
            $query = "UPDATE dsr_requests SET
                      status = 'rejected',
                      completed_at = NOW(),
                      rejection_reason = ?
                      WHERE dsr_id = ? AND org_id = ?";
            db_query($query, [$rejection_reason, $dsr_id, $org_id]);

            log_audit($org_id, $user_id, 'dsr_request', $dsr_id, 'reject', "Rejected DSR request");
            set_flash_message('DSR request rejected. Data subject should be notified with the reason.', 'warning');
            redirect("dsr_view.php?id=$dsr_id");
        }
    }
}

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Process DSR Request</title>
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
                        <h2>Process DSR Request</h2>
                        <h5>DSR-<?php echo str_pad($dsr_id, 5, '0', STR_PAD_LEFT); ?></h5>
                    </div>
                </div>
                <hr />

    <div class="row">
        <div class="col-md-8">
            <!-- Request Summary -->
            <div class="card border-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-info-circle"></i> Request Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Request Type:</strong></p>
                            <p><span class="badge bg-info text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($dsr['request_type']); ?></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Data Subject:</strong></p>
                            <p>
                                <?php echo htmlspecialchars($dsr['subject_name']); ?><br>
                                <small class="text-muted"><?php echo htmlspecialchars($dsr['subject_email']); ?></small>
                            </p>
                        </div>
                    </div>
                    <p><strong>Request Description:</strong></p>
                    <div style="background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
                        <?php echo nl2br(htmlspecialchars($dsr['request_description'])); ?>
                    </div>
                </div>
            </div>

            <!-- Step 1: Verify Identity -->
            <?php if (!$dsr['identity_verified']): ?>
            <div class="card border-warning">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-shield"></i> Step 1: Verify Identity
                        <span class="badge bg-warning text-dark float-end">Required</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <strong><i class="fa fa-exclamation-triangle"></i> Important:</strong>
                        You must verify the identity of the data subject before processing their request.
                        Failure to verify identity could result in unauthorized data disclosure.
                    </div>

                    <form method="POST" action="dsr_process.php?id=<?php echo $dsr_id; ?>">
                        <input type="hidden" name="action" value="verify_identity">

                        <div class="form-group">
                            <label>Verification Method <span class="text-danger">*</span></label>
                            <select name="verification_method" class="form-control" required>
                                <option value="">-- Select Method --</option>
                                <option value="ID Document">ID Document (National ID / Passport)</option>
                                <option value="Email Verification">Email Verification (Reply from registered email)</option>
                                <option value="Security Questions">Security Questions</option>
                                <option value="Phone Verification">Phone Verification</option>
                                <option value="In-Person">In-Person Verification</option>
                                <option value="Two-Factor Authentication">Two-Factor Authentication</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Verification Notes</label>
                            <textarea name="verification_notes" class="form-control" rows="3"
                                      placeholder="Document details, verification steps taken, etc."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fa fa-check"></i> Verify Identity &amp; Proceed
                        </button>
                    </form>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fa fa-check-circle"></i> Step 1: Identity Verified
                    </h3>
                </div>
                <div class="card-body">
                    <p><strong>Verified on:</strong> <?php echo date('d M Y, H:i', strtotime($dsr['verification_date'])); ?></p>
                    <p><strong>Method:</strong> <?php echo htmlspecialchars($dsr['verification_method']); ?></p>
                    <?php if ($dsr['verification_notes']): ?>
                        <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($dsr['verification_notes'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 2: Internal Notes -->
            <?php if ($dsr['identity_verified']): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-sticky-note"></i> Step 2: Internal Notes (Optional)</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="dsr_process.php?id=<?php echo $dsr_id; ?>">
                        <input type="hidden" name="action" value="update_notes">

                        <div class="form-group">
                            <label>Internal Processing Notes</label>
                            <textarea name="internal_notes" class="form-control" rows="5"
                                      placeholder="Document your processing steps, systems checked, data found, actions taken, etc."><?php echo htmlspecialchars($dsr['internal_notes'] ?? ''); ?></textarea>
                            <small class="text-muted">These notes are for internal use only and will not be shared with the data subject.</small>
                        </div>

                        <button type="submit" class="btn btn-info">
                            <i class="fa fa-save"></i> Save Notes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Step 3: Complete or Reject -->
            <div class="card border-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-check-square"></i> Step 3: Complete or Reject Request</h3>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" style="margin-bottom: 20px;">
                        <li class="active"><a href="#complete" data-bs-toggle="tab">
                            <i class="fa fa-check-circle"></i> Complete Request
                        </a></li>
                        <li><a href="#reject" data-bs-toggle="tab">
                            <i class="fa fa-times-circle"></i> Reject Request
                        </a></li>
                    </ul>

                    <div class="tab-content">
                        <!-- Complete Tab -->
                        <div class="tab-pane active" id="complete">
                            <form method="POST" action="dsr_process.php?id=<?php echo $dsr_id; ?>">
                                <input type="hidden" name="action" value="complete">

                                <div class="alert alert-info">
                                    <strong><i class="fa fa-info-circle"></i> Response Guidelines:</strong>
                                    <ul style="margin: 10px 0 0 20px;">
                                        <li>Provide clear, concise information addressing the request</li>
                                        <li>For access requests, attach a copy of their personal data</li>
                                        <li>For rectification, confirm what was updated</li>
                                        <li>For erasure, confirm what was deleted (with any exceptions)</li>
                                        <li>Use plain language, avoid technical jargon</li>
                                    </ul>
                                </div>

                                <div class="form-group">
                                    <label>Response to Data Subject <span class="text-danger">*</span></label>
                                    <textarea name="response_notes" class="form-control" rows="10" required
                                              placeholder="Draft your response to the data subject. This should address their request comprehensively and professionally.&#10;&#10;Example for Access Request:&#10;'Dear [Name], We have processed your request for access to your personal data. Attached is a copy of all personal data we hold about you, including: [list categories]. This data was collected from [sources] and is processed for [purposes] under the legal basis of [basis].'"></textarea>
                                </div>

                                <button type="submit" class="btn btn-success btn-lg btn-block">
                                    <i class="fa fa-check-circle"></i> Complete Request &amp; Notify Data Subject
                                </button>
                            </form>
                        </div>

                        <!-- Reject Tab -->
                        <div class="tab-pane" id="reject">
                            <div class="alert alert-warning">
                                <strong><i class="fa fa-warning"></i> Rejection Criteria:</strong>
                                <p>You may only reject a DSR request if:</p>
                                <ul style="margin: 0;">
                                    <li>The request is manifestly unfounded or excessive</li>
                                    <li>You cannot verify the identity of the requester</li>
                                    <li>The request conflicts with other legal obligations</li>
                                    <li>An exemption applies under CDPA</li>
                                </ul>
                                <p style="margin-top: 10px;"><strong>Important:</strong> The data subject has the right to complain to POTRAZ if their request is rejected.</p>
                            </div>

                            <form method="POST" action="dsr_process.php?id=<?php echo $dsr_id; ?>">
                                <input type="hidden" name="action" value="reject">

                                <div class="form-group">
                                    <label>Rejection Reason <span class="text-danger">*</span></label>
                                    <textarea name="rejection_reason" class="form-control" rows="8" required
                                              placeholder="Provide a clear explanation for rejecting this request, including:&#10;- The specific legal grounds for rejection&#10;- Any alternative options available to the data subject&#10;- Information about their right to complain to POTRAZ"></textarea>
                                </div>

                                <button type="submit" class="btn btn-danger btn-lg btn-block">
                                    <i class="fa fa-times-circle"></i> Reject Request &amp; Notify Data Subject
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            <!-- SLA Warning -->
            <div class="card border-<?php echo $dsr['days_elapsed'] > 30 ? 'danger' : ($dsr['days_elapsed'] >= 25 ? 'warning' : 'info'); ?>">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-clock-o"></i> SLA Status</h3>
                </div>
                <div class="card-body text-center">
                    <h2 style="margin: 10px 0;"><?php echo $dsr['days_elapsed']; ?> / 30 Days</h2>
                    <p class="text-muted">Days Elapsed</p>

                    <div class="progress" style="height: 25px;">
                        <?php
                        $progress = min(100, ($dsr['days_elapsed'] / 30) * 100);
                        $progress_class = $dsr['days_elapsed'] > 30 ? 'danger' : ($dsr['days_elapsed'] >= 25 ? 'warning' : 'info');
                        ?>
                        <div class="progress-bar bg-<?php echo $progress_class; ?>" style="width: <?php echo $progress; ?>%">
                            <?php echo round($progress); ?>%
                        </div>
                    </div>

                    <?php if ($dsr['days_elapsed'] > 30): ?>
                        <div class="alert alert-danger" style="margin-top: 15px; margin-bottom: 0;">
                            <i class="fa fa-exclamation-triangle"></i> <strong>OVERDUE</strong><br>
                            Exceeded 30-day deadline
                        </div>
                    <?php elseif ($dsr['days_elapsed'] >= 25): ?>
                        <div class="alert alert-warning" style="margin-top: 15px; margin-bottom: 0;">
                            <i class="fa fa-clock-o"></i> <strong>Due Soon</strong><br>
                            <?php echo (30 - $dsr['days_elapsed']); ?> days remaining
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="margin-top: 15px; margin-bottom: 0;">
                            <i class="fa fa-check-circle"></i> <strong>On Track</strong><br>
                            <?php echo (30 - $dsr['days_elapsed']); ?> days remaining
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Processing Checklist -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-check-square-o"></i> Processing Checklist</h3>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled" style="margin: 0;">
                        <li style="margin-bottom: 10px;">
                            <?php if ($dsr['identity_verified']): ?>
                                <i class="fa fa-check-square text-success"></i>
                            <?php else: ?>
                                <i class="fa fa-square-o text-muted"></i>
                            <?php endif; ?>
                            Verify Identity
                        </li>
                        <li style="margin-bottom: 10px;">
                            <?php if ($dsr['status'] == 'in_progress'): ?>
                                <i class="fa fa-check-square text-success"></i>
                            <?php else: ?>
                                <i class="fa fa-square-o text-muted"></i>
                            <?php endif; ?>
                            Process Request
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fa fa-square-o text-muted"></i>
                            Draft Response
                        </li>
                        <li style="margin-bottom: 10px;">
                            <i class="fa fa-square-o text-muted"></i>
                            Notify Data Subject
                        </li>
                        <li>
                            <i class="fa fa-square-o text-muted"></i>
                            Document Completion
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="card border-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa fa-book"></i> Quick Reference</h3>
                </div>
                <div class="card-body">
                    <p style="font-size: 12px; margin-bottom: 10px;">
                        <strong><?php echo htmlspecialchars($dsr['request_type']); ?></strong>
                    </p>
                    <p style="font-size: 11px; margin: 0;">
                        <?php
                        $guidance = [
                            'Right to Access' => 'Provide copy of personal data in commonly used electronic format. Must be free of charge.',
                            'Right to Rectification' => 'Correct or complete inaccurate/incomplete data. Notify third parties if applicable.',
                            'Right to Erasure' => 'Delete data unless retention required by law. Notify third parties if applicable.',
                            'Right to Data Portability' => 'Provide data in structured, machine-readable format (CSV/JSON).',
                            'Right to Restriction' => 'Mark data as restricted. Can store but not process.',
                            'Right to Object' => 'Stop processing unless compelling legitimate grounds exist.',
                            'Automated Decision-Making' => 'Provide human intervention and explanation of decision logic.',
                            'Withdraw Consent' => 'Stop processing based on consent. Does not affect past processing.'
                        ];
                        echo htmlspecialchars($guidance[$dsr['request_type']] ?? 'Process according to CDPA requirements.');
                        ?>
                    </p>
                </div>
            </div>

            <a href="dsr_view.php?id=<?php echo $dsr_id; ?>" class="btn btn-secondary btn-block">
                <i class="fa fa-eye"></i> View Request Details
            </a>

            <a href="dsr_list.php" class="btn btn-secondary btn-block">
                <i class="fa fa-arrow-left"></i> Back to List
            </a>
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
