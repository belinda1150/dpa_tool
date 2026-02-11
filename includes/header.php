<?php
/**
 * DPA Tool - Header
 * Version: 2.0
 * Bootstrap 5 compatible with dark mode and global search
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
<nav class="navbar navbar-cls-top" role="navigation">
     <div class="navbar-header">
          <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Toggle Sidebar">
               <i class="fa fa-bars"></i>
          </button>
          <a class="navbar-brand" href="dashboard.php"><?php echo APP_NAME; ?></a>
     </div>

     <!-- Global Search -->
     <div class="navbar-search" id="globalSearch">
          <div class="search-wrapper">
               <i class="fa fa-search search-icon"></i>
               <input type="text" id="globalSearchInput" class="search-input"
                    placeholder="Search ROPA, DPIA, incidents, vendors... (Ctrl+K)"
                    autocomplete="off" />
               <div class="search-results" id="searchResults" style="display: none;"></div>
          </div>
     </div>

     <div class="navbar-user-info">
          <!-- Dark Mode Toggle -->
          <button type="button" class="theme-toggle-btn" id="themeToggle" title="Toggle Dark Mode">
               <i class="fa fa-moon-o" id="themeIcon"></i>
          </button>

          <div class="dropdown" style="display: inline-block;">
               <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown" style="color: white; text-decoration: none; position: relative;">
                    <?php if (!empty($current_user['profile_picture']) && file_exists($current_user['profile_picture'])): ?>
                         <img src="<?php echo htmlspecialchars($current_user['profile_picture']); ?>"
                              alt="Profile"
                              style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; vertical-align: middle; margin-right: 8px;">
                    <?php else: ?>
                         <i class="fa fa-user-circle" style="font-size: 30px; vertical-align: middle; margin-right: 8px;"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?>
                    <?php if ($unread_notifications > 0): ?>
                    <span class="badge bg-danger" style="position: absolute; top: -5px; right: -10px; font-size: 10px; padding: 3px 6px; border-radius: 10px;">
                         <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
                    </span>
                    <?php endif; ?>
                    <i class="fa fa-caret-down" style="margin-left: 5px;"></i>
               </a>
               <ul class="dropdown-menu dropdown-menu-end" style="min-width: 250px;">
                    <li><h6 class="dropdown-header" style="padding: 10px 20px; border-bottom: 1px solid var(--border-color, #e5e5e5);">
                         <strong><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></strong><br>
                         <small class="text-muted">
                              <i class="fa fa-shield"></i> <?php echo htmlspecialchars($current_user['role_name']); ?>
                         </small>
                    </h6></li>
                    <li><a class="dropdown-item" href="users_edit.php?id=<?php echo get_current_user_id(); ?>"><i class="fa fa-camera"></i> Change Profile Photo</a></li>
                    <li><a class="dropdown-item" href="notifications.php">
                         <i class="fa fa-bell"></i> Notifications
                         <?php if ($unread_notifications > 0): ?>
                              <span class="badge bg-danger" style="margin-left: 5px;">
                                   <?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?>
                              </span>
                         <?php endif; ?>
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="fa fa-sign-out"></i> Logout</a></li>
               </ul>
          </div>
     </div>
</nav>
