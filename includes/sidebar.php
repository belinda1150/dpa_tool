<?php
/**
 * DPA Tool - Sidebar Navigation
 * Version: 1.0
 * Date: October 2025
 */

$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
:root {
    /* Primary Colors */
    --primary-color: #003B73;
    --primary-dark: #002244;
    --primary-light: #60a3d9;

    /* Background Colors */
    --bg-main: #fdfdfd;
    --bg-secondary: #F3F3F3;
    --bg-white: #fff;
    --bg-panel: #F8F8F8;
    --bg-gray-light: #f3f3f3;

    /* Text Colors */
    --text-primary: #003B73;
    --text-secondary: #8d8888;
    --text-light: #fff;
    --text-muted: #bdc3c7;

    /* Status Colors */
    --success-color: #00CE6F;
    --success-dark: #009B50;
    --info-color: #A95DF0;
    --danger-color: #DB0630;
    --danger-dark: #AF0000;
    --warning-color: #B94A00;

    /* Navigation Colors */
    --nav-bg: #ffffff;
    --nav-header: #003B73;
    --nav-brand: #003B73;
    --nav-toggle: #003B73;
    --nav-toggle-hover: #002244;
    --nav-sidebar-bg: #003B73;
    --nav-sidebar-border: #002244;
    --nav-active: #002244;
    --nav-active-border: #002244;
    --nav-hover: #002244;
    --nav-accent: #002244;

    /* Special Colors */
    --purple-gradient: #8702A8;
    --border-color: #808080;
    --shadow-color: rgba(107, 108, 109, 0.19);
    --nav-border-light: #003B73;
}

/* Logo area */
.aside_nav-logo {
    text-align: center;
    padding: 20px 15px;
    border-bottom: 1px solid var(--nav-sidebar-border);
    font-weight: normal !important;
}

.aside_nav-logo img {
    max-width: 140px;
    max-height: 90px;
    margin: 0 auto;
    display: block;
}

/* Global styles for sidebar - Force bold font */
.aside_nav .nav li a,
.aside_nav.navbar-side .nav li a,
.aside_nav.navbar-side .nav > li > a,
.navbar-side .nav li a,
.navbar-side .nav > li > a,
.sidebar-collapse .nav li a,
.sidebar-collapse .nav > li > a,
#main-menu li a,
#main-menu > li > a {
    font-weight: 900 !important;
    background-color: var(--nav-sidebar-bg);
    color: var(--text-light);
}

.aside_nav.navbar-side .nav li a:hover,
.aside_nav.navbar-side .nav li a:focus {
    background-color: var(--nav-hover);
    color: var(--text-light);
}

/* Active menu styling */
.aside_nav.navbar-side .nav > li > a.active {
    background-color: #000000 !important;
    color: var(--text-light) !important;
    position: relative;
}

.aside_nav.navbar-side .nav > li > a.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background-color: var(--primary-light);
}

/* Dropdown menu styles */
.aside_nav-dropdown {
    list-style: none;
}

.aside_nav-dropdown > a {
    cursor: pointer;
    position: relative;
    display: block;
    transition: all 0.3s ease;
    font-weight: 900 !important;
    background-color: var(--nav-sidebar-bg);
    color: var(--text-light);
}

.aside_nav-dropdown > a .aside_nav-arrow {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    transition: transform 0.3s ease;
    font-size: 16px;
    font-weight: bold;
}

.aside_nav-dropdown.open > a .aside_nav-arrow {
    transform: translateY(-50%) rotate(90deg);
}

.aside_nav-dropdown .aside_nav-submenu {
    max-height: 0;
    overflow: hidden;
    list-style: none;
    padding-left: 0;
    background-color: var(--nav-accent);
    transition: max-height 0.4s ease-in-out;
}

.aside_nav-dropdown.open .aside_nav-submenu {
    max-height: 1000px;
}

.aside_nav-dropdown .aside_nav-submenu li {
    transition: all 0.3s ease;
}

.aside_nav-dropdown .aside_nav-submenu li a {
    padding: 10px 15px 10px 50px;
    font-size: 13px;
    display: block;
    color: var(--text-muted);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    font-weight: 900 !important;
    border-bottom: 1px solid var(--nav-sidebar-bg);
}

.aside_nav-dropdown .aside_nav-submenu li a:hover {
    background-color: var(--primary-dark);
    color: var(--text-light);
    padding-left: 55px;
    font-weight: 900 !important;
}

.aside_nav-dropdown .aside_nav-submenu li a.aside_nav-active-menu {
    background-color: #000000 !important;
    color: var(--text-light) !important;
    font-weight: 900 !important;
    position: relative;
}

.aside_nav-dropdown .aside_nav-submenu li a.aside_nav-active-menu::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background-color: var(--primary-light);
}

.aside_nav-dropdown .aside_nav-submenu li a i {
    margin-right: 10px;
    font-size: 14px;
    width: 18px;
    text-align: center;
}

/* Animation for submenu items */
.aside_nav-dropdown.open .aside_nav-submenu li {
    animation: aside_nav-slideIn 0.3s ease-out forwards;
    opacity: 0;
}

.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(1) { animation-delay: 0.05s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(2) { animation-delay: 0.1s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(3) { animation-delay: 0.15s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(4) { animation-delay: 0.2s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(5) { animation-delay: 0.3s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(6) { animation-delay: 0.35s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(7) { animation-delay: 0.4s; }
.aside_nav-dropdown.open .aside_nav-submenu li:nth-child(8) { animation-delay: 0.45s; }

@keyframes aside_nav-slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle dropdown toggles
    const dropdownToggles = document.querySelectorAll('.aside_nav-dropdown > a');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parent = this.parentElement;
            const wasOpen = parent.classList.contains('open');

            // Close all dropdowns
            document.querySelectorAll('.aside_nav-dropdown').forEach(dropdown => {
                dropdown.classList.remove('open');
            });

            // Toggle current dropdown
            if (!wasOpen) {
                parent.classList.add('open');
            }
        });
    });

    // Auto-open dropdown if current page is in submenu
    const activeMenuItem = document.querySelector('.aside_nav-dropdown .aside_nav-submenu a.aside_nav-active-menu');
    if (activeMenuItem) {
        const parentDropdown = activeMenuItem.closest('.aside_nav-dropdown');
        if (parentDropdown) {
            parentDropdown.classList.add('open');
        }
    }
});
</script>

<nav class="navbar-default navbar-side aside_nav" role="navigation">
  <div class="sidebar-collapse">
    <ul class="nav" id="main-menu">

      <!-- Logo -->
      <li class="aside_nav-logo">
        <img src="assets/img/logo.png" alt="DPA Tool Logo" />
      </li>

      <!-- Dashboard -->
      <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a href="dashboard.php"><i class="fa fa-tachometer-alt fa-2x"></i> Dashboard</a>
      </li>

      <!-- ROPA Module -->
      <li class="<?php echo (in_array($current_page, ['ropa_list.php', 'ropa_add.php', 'ropa_view.php', 'ropa_edit.php'])) ? 'active' : ''; ?>">
        <a href="ropa_list.php"><i class="fa fa-list-alt fa-2x"></i> ROPA</a>
      </li>

      <!-- DPIA Module -->
      <li class="<?php echo (in_array($current_page, ['dpia_list.php', 'dpia_wizard.php', 'dpia_view.php'])) ? 'active' : ''; ?>">
        <a href="dpia_list.php"><i class="fa fa-shield fa-2x"></i> DPIA</a>
      </li>

      <!-- Consent Management -->
      <li class="<?php echo (in_array($current_page, ['consent_list.php', 'consent_add.php'])) ? 'active' : ''; ?>">
        <a href="consent_list.php"><i class="fa fa-check-square-o fa-2x"></i> Consent Register</a>
      </li>

      <!-- Cross-Border Transfers -->
      <li class="<?php echo (in_array($current_page, ['crossborder_list.php', 'crossborder_add.php', 'crossborder_view.php', 'crossborder_edit.php'])) ? 'active' : ''; ?>">
        <a href="crossborder_list.php"><i class="fa fa-globe fa-2x"></i> Cross-Border</a>
      </li>

      <!-- Risk Register -->
      <li class="aside_nav-dropdown">
        <a href="#"><i class="fa fa-exclamation-triangle fa-2x"></i> Risk & Controls <span class="aside_nav-arrow">›</span></a>
        <ul class="aside_nav-submenu">
          <li><a href="risk_list.php" class="<?php echo ($current_page == 'risk_list.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-clipboard-list"></i> Risk Register</a></li>
          <li><a href="risk_heatmap.php" class="<?php echo ($current_page == 'risk_heatmap.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-fire"></i> Risk Heat Map</a></li>
          <li><a href="controls.php" class="<?php echo ($current_page == 'controls.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-shield-alt"></i> Control Library</a></li>
        </ul>
      </li>

      <!-- Incident & Breach -->
      <li class="<?php echo (in_array($current_page, ['incident_list.php', 'incident_add.php', 'incident_view.php'])) ? 'active' : ''; ?>">
        <a href="incident_list.php"><i class="fa fa-exclamation-circle fa-2x"></i> Incidents & Breaches</a>
      </li>

      <!-- DSR (Data Subject Rights) -->
      <li class="<?php echo (in_array($current_page, ['dsr_list.php', 'dsr_add.php', 'dsr_view.php'])) ? 'active' : ''; ?>">
        <a href="dsr_list.php"><i class="fa fa-user-circle fa-2x"></i> DSR Requests</a>
      </li>

      <!-- Policy & Training -->
      <li class="aside_nav-dropdown">
        <a href="#"><i class="fa fa-graduation-cap fa-2x"></i> Policy & Training <span class="aside_nav-arrow">›</span></a>
        <ul class="aside_nav-submenu">
          <li><a href="policy_list.php" class="<?php echo ($current_page == 'policy_list.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-file-alt"></i> Policies</a></li>
          <li><a href="training_list.php" class="<?php echo ($current_page == 'training_list.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-chalkboard-teacher"></i> Training</a></li>
          <li><a href="training_my.php" class="<?php echo ($current_page == 'training_my.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-user-graduate"></i> My Training</a></li>
        </ul>
      </li>

      <!-- Governance & Documents -->
      <li class="<?php echo (in_array($current_page, ['documents_list.php', 'documents_upload.php'])) ? 'active' : ''; ?>">
        <a href="documents_list.php"><i class="fa fa-folder-open fa-2x"></i> Document Repository</a>
      </li>

      <!-- Reports -->
      <li class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">
        <a href="reports.php"><i class="fa fa-chart-bar fa-2x"></i> Reports</a>
      </li>

      <?php if (is_admin()): ?>
      <!-- Settings (Admin Only) -->
      <li class="aside_nav-dropdown">
        <a href="#"><i class="fa fa-cogs fa-2x"></i> Settings <span class="aside_nav-arrow">›</span></a>
        <ul class="aside_nav-submenu">
          <li><a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-users"></i> Users</a></li>
          <li><a href="departments.php" class="<?php echo ($current_page == 'departments.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-building"></i> Departments</a></li>
          <li><a href="settings.php" class="<?php echo ($current_page == 'settings.php') ? 'aside_nav-active-menu' : ''; ?>"><i class="fa fa-sliders-h"></i> System Settings</a></li>
        </ul>
      </li>
      <?php endif; ?>

    </ul>
  </div>
</nav>
