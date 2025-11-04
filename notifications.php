<?php
/**
 * DPA Tool - Notifications Center
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/database.php';
require_once 'config/config.php';
require_once 'includes/auth.php';

require_login();

$user_id = get_current_user_id();
$org_id = get_current_org_id();

// Get filter
$filter = $_GET['filter'] ?? 'unread';

// Build query based on filter
$where_conditions = ["org_id = ?", "user_id = ?"];
$params = [$org_id, $user_id];

if ($filter === 'unread') {
    $where_conditions[] = "is_read = 0";
} elseif ($filter === 'read') {
    $where_conditions[] = "is_read = 1";
}

$where_clause = implode(' AND ', $where_conditions);

// Get notifications
$query = "SELECT * FROM notifications
          WHERE $where_clause
          ORDER BY created_at DESC
          LIMIT 100";

$stmt = db_query($query, $params);
$notifications = db_fetch_all($stmt);

// Get counts
$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE org_id = ? AND user_id = ? AND is_read = 0";
$unread_stmt = db_query($unread_query, [$org_id, $user_id]);
$unread_count = db_fetch_one($unread_stmt)['count'];

$total_query = "SELECT COUNT(*) as count FROM notifications WHERE org_id = ? AND user_id = ?";
$total_stmt = db_query($total_query, [$org_id, $user_id]);
$total_count = db_fetch_one($total_stmt)['count'];

// Get flash message
$flash = get_flash_message();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo APP_NAME; ?> - Notifications</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/custom.css" rel="stylesheet" />
    <style>
        .notification-item {
            padding: 15px;
            border-bottom: 1px solid #e7e7e7;
            transition: background-color 0.3s;
        }
        .notification-item:hover {
            background-color: #f9f9f9;
        }
        .notification-item.unread {
            background-color: #f0f8ff;
            border-left: 4px solid #337ab7;
        }
        .notification-icon {
            font-size: 24px;
            margin-right: 15px;
            float: left;
        }
        .notification-content {
            margin-left: 45px;
        }
        .notification-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .notification-message {
            color: #666;
            margin-bottom: 5px;
        }
        .notification-time {
            font-size: 12px;
            color: #999;
        }
        .notification-actions {
            margin-top: 10px;
        }
        .priority-critical { color: #d9534f; }
        .priority-high { color: #f0ad4e; }
        .priority-medium { color: #5bc0de; }
        .priority-low { color: #5cb85c; }
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
                        <h2>Notifications</h2>
                        <h5>Your system alerts and updates</h5>
                    </div>
                </div>
                <hr />

                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] == 'success' ? 'success' : 'danger'; ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
                <?php endif; ?>

                <!-- Statistics & Filters -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4 style="margin-top: 5px;">
                                            <span class="badge badge-danger" style="font-size: 16px;"><?php echo $unread_count; ?></span>
                                            Unread Notifications
                                        </h4>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <div class="btn-group">
                                            <a href="notifications.php?filter=unread" class="btn btn-<?php echo $filter === 'unread' ? 'primary' : 'default'; ?>">
                                                <i class="fa fa-envelope"></i> Unread (<?php echo $unread_count; ?>)
                                            </a>
                                            <a href="notifications.php?filter=read" class="btn btn-<?php echo $filter === 'read' ? 'primary' : 'default'; ?>">
                                                <i class="fa fa-envelope-open"></i> Read
                                            </a>
                                            <a href="notifications.php?filter=all" class="btn btn-<?php echo $filter === 'all' ? 'primary' : 'default'; ?>">
                                                <i class="fa fa-list"></i> All (<?php echo $total_count; ?>)
                                            </a>
                                        </div>
                                        <?php if ($unread_count > 0): ?>
                                        <a href="notifications_mark_all_read.php" class="btn btn-success" onclick="return confirm('Mark all notifications as read?');">
                                            <i class="fa fa-check-double"></i> Mark All Read
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <i class="fa fa-list"></i>
                                <?php
                                if ($filter === 'unread') echo 'Unread Notifications';
                                elseif ($filter === 'read') echo 'Read Notifications';
                                else echo 'All Notifications';
                                ?>
                            </div>
                            <div class="panel-body" style="padding: 0;">
                                <?php if (empty($notifications)): ?>
                                <div class="alert alert-info" style="margin: 15px;">
                                    <i class="fa fa-info-circle"></i>
                                    <?php if ($filter === 'unread'): ?>
                                        No unread notifications. You're all caught up!
                                    <?php elseif ($filter === 'read'): ?>
                                        No read notifications yet.
                                    <?php else: ?>
                                        No notifications yet.
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <div class="list-group" style="margin-bottom: 0;">
                                    <?php foreach ($notifications as $notif): ?>
                                    <div class="notification-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>">
                                        <div class="notification-icon priority-<?php echo $notif['priority']; ?>">
                                            <?php
                                            // Icon based on notification type
                                            $icon = 'bell';
                                            switch ($notif['notification_type']) {
                                                case 'consent_expiry':
                                                case 'consent_expiring': $icon = 'clock-o'; break;
                                                case 'dsr_sla':
                                                case 'dsr_assigned':
                                                case 'dsr_new': $icon = 'user-circle'; break;
                                                case 'breach_sla':
                                                case 'data_breach':
                                                case 'incident_assigned': $icon = 'exclamation-triangle'; break;
                                                case 'risk_overdue':
                                                case 'risk_assigned': $icon = 'tasks'; break;
                                                case 'training_due':
                                                case 'training_assigned': $icon = 'graduation-cap'; break;
                                                case 'document_expiry': $icon = 'file-text'; break;
                                                case 'dpia_pending':
                                                case 'dpia_approved':
                                                case 'dpia_rejected':
                                                case 'dpia_revision_required': $icon = 'shield'; break;
                                                case 'cross_border_high_risk': $icon = 'globe'; break;
                                                case 'policy_published': $icon = 'book'; break;
                                                default: $icon = 'bell';
                                            }
                                            ?>
                                            <i class="fa fa-<?php echo $icon; ?>"></i>
                                        </div>
                                        <div class="notification-content">
                                            <div class="notification-title">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                                <?php if (!$notif['is_read']): ?>
                                                <span class="label label-primary">New</span>
                                                <?php endif; ?>
                                                <?php if ($notif['priority'] === 'critical'): ?>
                                                <span class="label label-danger">Critical</span>
                                                <?php elseif ($notif['priority'] === 'high'): ?>
                                                <span class="label label-warning">High Priority</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notification-message">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </div>
                                            <div class="notification-time">
                                                <i class="fa fa-clock-o"></i>
                                                <?php
                                                $time_diff = time() - strtotime($notif['created_at']);
                                                if ($time_diff < 60) {
                                                    echo 'Just now';
                                                } elseif ($time_diff < 3600) {
                                                    echo floor($time_diff / 60) . ' minutes ago';
                                                } elseif ($time_diff < 86400) {
                                                    echo floor($time_diff / 3600) . ' hours ago';
                                                } elseif ($time_diff < 604800) {
                                                    echo floor($time_diff / 86400) . ' days ago';
                                                } else {
                                                    echo format_datetime($notif['created_at'], 'd M Y H:i');
                                                }
                                                ?>
                                            </div>
                                            <div class="notification-actions">
                                                <?php if ($notif['link_url']): ?>
                                                <a href="<?php echo htmlspecialchars($notif['link_url']); ?>" class="btn btn-primary btn-xs">
                                                    <i class="fa fa-external-link"></i> View Details
                                                </a>
                                                <?php endif; ?>
                                                <?php if (!$notif['is_read']): ?>
                                                <a href="notifications_mark_read.php?id=<?php echo $notif['notification_id']; ?>" class="btn btn-success btn-xs">
                                                    <i class="fa fa-check"></i> Mark as Read
                                                </a>
                                                <?php endif; ?>
                                                <a href="notifications_delete.php?id=<?php echo $notif['notification_id']; ?>" class="btn btn-danger btn-xs"
                                                   onclick="return confirm('Delete this notification?');">
                                                    <i class="fa fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
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
