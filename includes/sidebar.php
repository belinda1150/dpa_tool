<?php
/**
 * DPA Tool - Sidebar Navigation
 * Version: 1.0
 * Date: October 2025
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar-default navbar-side" role="navigation">
  <div class="sidebar-collapse">
    <div class="sidebar-logo">
      <img src="assets/img/zim.png" alt="Logo">
    </div>
    <ul class="nav" id="main-menu">

      <!-- Dashboard -->
      <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a href="dashboard.php" data-title="Dashboard"><i class="fa fa-dashboard fa-2x"></i><span class="menu-text"> Dashboard</span></a>
      </li>

      <!-- ROPA Module -->
      <li class="<?php echo (in_array($current_page, ['ropa_list.php', 'ropa_add.php', 'ropa_view.php', 'ropa_edit.php'])) ? 'active' : ''; ?>">
        <a href="ropa_list.php" data-title="ROPA"><i class="fa fa-list-alt fa-2x"></i><span class="menu-text"> ROPA</span></a>
      </li>

      <!-- DPIA Module -->
      <li class="<?php echo (in_array($current_page, ['dpia_list.php', 'dpia_wizard.php', 'dpia_view.php'])) ? 'active' : ''; ?>">
        <a href="dpia_list.php" data-title="DPIA"><i class="fa fa-shield fa-2x"></i><span class="menu-text"> DPIA</span></a>
      </li>

      <!-- Consent Management -->
      <li class="<?php echo (in_array($current_page, ['consent_list.php', 'consent_add.php'])) ? 'active' : ''; ?>">
        <a href="consent_list.php" data-title="Consent Register"><i class="fa fa-check-square-o fa-2x"></i><span class="menu-text"> Consent Register</span></a>
      </li>

      <!-- Cross-Border Transfers -->
      <li class="<?php echo (in_array($current_page, ['crossborder_list.php', 'crossborder_add.php', 'crossborder_view.php', 'crossborder_edit.php'])) ? 'active' : ''; ?>">
        <a href="crossborder_list.php" data-title="Cross-Border"><i class="fa fa-globe fa-2x"></i><span class="menu-text"> Cross-Border</span></a>
      </li>

      <!-- Risk Register -->
      <li class="<?php echo (in_array($current_page, ['risk_list.php', 'risk_heatmap.php', 'risk_add.php', 'risk_view.php', 'risk_edit.php', 'controls.php', 'controls_add.php', 'controls_edit.php'])) ? 'active' : ''; ?>">
        <a href="#" data-title="Risk & Controls"><i class="fa fa-exclamation-triangle fa-2x"></i><span class="menu-text"> Risk & Controls</span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level">
          <li><a href="risk_list.php">Risk Register</a></li>
          <li><a href="risk_heatmap.php">Risk Heat Map</a></li>
          <li><a href="controls.php">Control Library</a></li>
        </ul>
      </li>

      <!-- Incident & Breach -->
      <li class="<?php echo (in_array($current_page, ['incident_list.php', 'incident_add.php', 'incident_view.php'])) ? 'active' : ''; ?>">
        <a href="incident_list.php" data-title="Incidents & Breaches"><i class="fa fa-bolt fa-2x"></i><span class="menu-text"> Incidents & Breaches</span></a>
      </li>

      <!-- DSR (Data Subject Rights) -->
      <li class="<?php echo (in_array($current_page, ['dsr_list.php', 'dsr_add.php', 'dsr_view.php'])) ? 'active' : ''; ?>">
        <a href="dsr_list.php" data-title="DSR Requests"><i class="fa fa-user fa-2x"></i><span class="menu-text"> DSR Requests</span></a>
      </li>

      <!-- Vendor Management -->
      <li class="<?php echo (in_array($current_page, ['vendor_list.php', 'vendor_add.php', 'vendor_view.php', 'vendor_edit.php', 'vendor_assessment_add.php', 'vendor_assessment_view.php', 'vendor_certification_add.php', 'vendor_diligence_add.php'])) ? 'active' : ''; ?>">
        <a href="#" data-title="Vendor Management"><i class="fa fa-briefcase fa-2x"></i><span class="menu-text"> Vendor Management</span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level">
          <li><a href="vendor_list.php">Vendor Register</a></li>
          <li><a href="vendor_add.php">Add Vendor</a></li>
        </ul>
      </li>

      <!-- Policy & Training -->
      <li class="<?php echo (in_array($current_page, ['policy_list.php', 'policy_add.php', 'policy_view.php', 'training_list.php', 'training_add.php', 'training_view.php', 'training_assign.php', 'training_my.php'])) ? 'active' : ''; ?>">
        <a href="#" data-title="Policy & Training"><i class="fa fa-book fa-2x"></i><span class="menu-text"> Policy & Training</span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level">
          <li><a href="policy_list.php">Policies</a></li>
          <li><a href="training_list.php">Training</a></li>
          <li><a href="training_my.php">My Training</a></li>
        </ul>
      </li>

      <!-- Governance & Documents -->
      <li class="<?php echo (in_array($current_page, ['documents_list.php', 'documents_upload.php'])) ? 'active' : ''; ?>">
        <a href="documents_list.php" data-title="Document Repository"><i class="fa fa-folder-open fa-2x"></i><span class="menu-text"> Documents</span></a>
      </li>

      <!-- Reports -->
      <li class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
        <a href="reports.php" data-title="Reports"><i class="fa fa-file-text-o fa-2x"></i><span class="menu-text"> Reports</span></a>
      </li>

      <?php if (is_admin()): ?>
      <!-- Settings (Admin Only) -->
      <li class="<?php echo (in_array($current_page, ['settings.php', 'users.php', 'departments.php'])) ? 'active' : ''; ?>">
        <a href="#" data-title="Settings"><i class="fa fa-cog fa-2x"></i><span class="menu-text"> Settings</span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level">
          <li><a href="users.php">Users</a></li>
          <li><a href="departments.php">Departments</a></li>
          <li><a href="settings.php">System Settings</a></li>
        </ul>
      </li>
      <?php endif; ?>

    </ul>
  </div>
</nav>

<script>
// Sidebar Toggle - runs after DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('sidebarToggleBtn');
    var body = document.body;

    // Load saved state (default is expanded/not collapsed)
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        body.classList.add('sidebar-collapsed');
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            body.classList.toggle('sidebar-collapsed');
            // Save state
            localStorage.setItem('sidebarCollapsed', body.classList.contains('sidebar-collapsed'));
        });
    }
});
</script>
