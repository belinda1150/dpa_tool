<?php
/**
 * DPA Tool - System Settings
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();
require_permission('Admin'); // Only Admin can access system settings

$org_id = get_current_org_id();
$errors = [];
$success = false;

// Get current settings
$query = "SELECT * FROM system_settings WHERE org_id IS NULL OR org_id = ? ORDER BY setting_key";
$stmt = db_query($query, [$org_id]);
$settings = db_fetch_all($stmt);

// Convert to associative array for easier access
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['setting_key']] = $setting['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dsr_sla_days = intval($_POST['dsr_sla_days'] ?? 30);
    $breach_notification_hours = intval($_POST['breach_notification_hours'] ?? 72);
    $consent_expiry_alert_days = intval($_POST['consent_expiry_alert_days'] ?? 30);
    $dpia_threshold_subjects = intval($_POST['dpia_threshold_subjects'] ?? 10000);
    $risk_acceptable_threshold = intval($_POST['risk_acceptable_threshold'] ?? 6);
    $session_timeout_minutes = intval($_POST['session_timeout_minutes'] ?? 30);

    // Validation
    if ($dsr_sla_days < 1 || $dsr_sla_days > 365) {
        $errors[] = 'DSR SLA days must be between 1 and 365';
    }
    if ($breach_notification_hours < 1 || $breach_notification_hours > 720) {
        $errors[] = 'Breach notification hours must be between 1 and 720';
    }
    if ($consent_expiry_alert_days < 1 || $consent_expiry_alert_days > 365) {
        $errors[] = 'Consent expiry alert days must be between 1 and 365';
    }
    if ($session_timeout_minutes < 5 || $session_timeout_minutes > 1440) {
        $errors[] = 'Session timeout must be between 5 and 1440 minutes';
    }

    if (empty($errors)) {
        // Update settings
        $settings_to_update = [
            'dsr_sla_days' => $dsr_sla_days,
            'breach_notification_hours' => $breach_notification_hours,
            'consent_expiry_alert_days' => $consent_expiry_alert_days,
            'dpia_threshold_subjects' => $dpia_threshold_subjects,
            'risk_acceptable_threshold' => $risk_acceptable_threshold,
            'session_timeout_minutes' => $session_timeout_minutes
        ];

        foreach ($settings_to_update as $key => $value) {
            $update_query = "INSERT INTO system_settings (org_id, setting_key, setting_value, setting_type)
                            VALUES (?, ?, ?, 'integer')
                            ON DUPLICATE KEY UPDATE setting_value = ?";
            db_query($update_query, [$org_id, $key, $value, $value]);
        }

        log_audit($org_id, get_current_user_id(), 'system_settings', $org_id, 'update');
        $success = true;

        // Reload settings
        $stmt = db_query($query, [$org_id]);
        $settings = db_fetch_all($stmt);
        $settings_array = [];
        foreach ($settings as $setting) {
            $settings_array[$setting['setting_key']] = $setting['setting_value'];
        }
    }
}

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - System Settings</title>
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
                        <h2>System Settings</h2>
                        <h5>Configure system-wide compliance parameters</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fa fa-check-circle"></i> Settings updated successfully
                </div>
                <?php endif; ?>

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
                        <form method="post" action="settings.php">
                            <!-- Compliance Settings -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-cog"></i> Compliance Settings
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>DSR SLA Days <span class="text-danger">*</span></label>
                                                <input type="number" name="dsr_sla_days" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['dsr_sla_days'] ?? $settings_array['dsr_sla_days'] ?? DSR_SLA_DAYS); ?>"
                                                       min="1" max="365" required>
                                                <small class="help-block">Days to respond to Data Subject Rights requests (CDPA default: 30 days)</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Breach Notification Hours <span class="text-danger">*</span></label>
                                                <input type="number" name="breach_notification_hours" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['breach_notification_hours'] ?? $settings_array['breach_notification_hours'] ?? BREACH_NOTIFICATION_HOURS); ?>"
                                                       min="1" max="720" required>
                                                <small class="help-block">Hours to notify POTRAZ of data breach (CDPA: 72 hours)</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Consent Expiry Alert Days <span class="text-danger">*</span></label>
                                                <input type="number" name="consent_expiry_alert_days" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['consent_expiry_alert_days'] ?? $settings_array['consent_expiry_alert_days'] ?? CONSENT_EXPIRY_ALERT_DAYS); ?>"
                                                       min="1" max="365" required>
                                                <small class="help-block">Days before consent expiry to send alert</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>DPIA Threshold (Data Subjects) <span class="text-danger">*</span></label>
                                                <input type="number" name="dpia_threshold_subjects" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['dpia_threshold_subjects'] ?? $settings_array['dpia_threshold_subjects'] ?? DPIA_THRESHOLD_SUBJECTS); ?>"
                                                       min="1" required>
                                                <small class="help-block">Minimum data subjects to trigger DPIA requirement</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Risk Management Settings -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-exclamation-triangle"></i> Risk Management Settings
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Risk Acceptable Threshold <span class="text-danger">*</span></label>
                                                <input type="number" name="risk_acceptable_threshold" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['risk_acceptable_threshold'] ?? $settings_array['risk_acceptable_threshold'] ?? RISK_ACCEPTABLE_THRESHOLD); ?>"
                                                       min="1" max="25" required>
                                                <small class="help-block">Maximum acceptable residual risk score (1-25 scale)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Security Settings -->
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-lock"></i> Security Settings
                                </div>
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Session Timeout (Minutes) <span class="text-danger">*</span></label>
                                                <input type="number" name="session_timeout_minutes" class="form-control"
                                                       value="<?php echo htmlspecialchars($_POST['session_timeout_minutes'] ?? $settings_array['session_timeout_minutes'] ?? (SESSION_TIMEOUT / 60)); ?>"
                                                       min="5" max="1440" required>
                                                <small class="help-block">User session timeout in minutes (5-1440)</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> Save Settings
                                </button>
                                <a href="dashboard.php" class="btn btn-default">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>

                    <div class="col-md-4">
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <i class="fa fa-info-circle"></i> About System Settings
                            </div>
                            <div class="panel-body">
                                <p><strong>System Settings</strong> configure compliance parameters and security controls for your organization.</p>

                                <h5>CDPA Compliance References:</h5>
                                <dl>
                                    <dt>DSR Requests</dt>
                                    <dd>CDPA Section 26-31 - Data subject rights must be fulfilled within reasonable timeframe (typically 30 days)</dd>

                                    <dt>Breach Notification</dt>
                                    <dd>CDPA Section 35 - Notify POTRAZ within 72 hours of becoming aware of a breach</dd>

                                    <dt>DPIA</dt>
                                    <dd>CDPA Section 33 - Required for high-risk processing activities</dd>
                                </dl>
                            </div>
                        </div>

                        <div class="panel panel-warning">
                            <div class="panel-heading">
                                <i class="fa fa-exclamation-triangle"></i> Important
                            </div>
                            <div class="panel-body">
                                <p><strong>Note:</strong> Changes to these settings affect compliance calculations and alerts across the entire system.</p>
                                <p>Ensure all values comply with Zimbabwe's Cyber and Data Protection Act (CDPA) requirements.</p>
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
