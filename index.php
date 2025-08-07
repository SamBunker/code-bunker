<?php
/**
 * Entry Point
 * Web Application Modernization Tracker
 * 
 * Main entry point that redirects users to appropriate page
 */

require_once dirname(__FILE__) . '/includes/auth.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
} else {
    // Redirect to login page
    header('Location: ' . BASE_URL . '/pages/login.php');
}

exit();
?>