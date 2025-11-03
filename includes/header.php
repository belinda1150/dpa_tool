<?php
/**
 * DPA Tool - Header
 * Version: 1.0
 * Date: October 2025
 */

require_once dirname(__DIR__) . '/includes/auth.php';
require_login();

$current_user = get_current_dpa_user();

// Get unread notification count
$_user_id = get_current_user_id();
$_org_id = get_current_org_id();
$notif_query = "SELECT COUNT(*) as count FROM notifications WHERE org_id = ? AND user_id = ? AND is_read = 0";
$notif_stmt = db_query($notif_query, [$_org_id, $_user_id]);
$unread_notifications = db_fetch_one($notif_stmt)['count'] ?? 0;
?>
<nav class="navbar navbar-default navbar-cls-top" role="navigation">
     <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
               <span class="sr-only">Toggle navigation</span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
               <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="dashboard.php"><?php echo APP_NAME; ?></a>
     </div>
     <div class="navbar-user-info">
          Welcome <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
          <span class="badge role-badge"><?php echo htmlspecialchars($current_user['role_name']); ?></span>
          &nbsp;
          <a href="notifications.php" class="btn btn-info square-btn-adjust" style="position: relative;" title="Notifications">
               <i class="fa fa-bell"></i>
               <?php if ($unread_notifications > 0): ?>
               <span class="badge" style="position: absolute; top: -5px; right: -5px; background-color: #d9534f; color: white; font-size: 10px; padding: 2px 5px; border-radius: 10px;">
                    <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
               </span>
               <?php endif; ?>
          </a>
          &nbsp;
          <a href="logout.php" class="btn btn-danger square-btn-adjust">Logout</a>
     </div>
</nav>
