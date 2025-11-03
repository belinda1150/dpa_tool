<?php
/**
 * DPA Tool - Logout
 * Version: 1.0
 * Date: October 2025
 */

require_once 'config/config.php';
require_once 'includes/auth.php';

logout_user();
redirect('index.php?status=Logged%20out%20successfully');
?>
