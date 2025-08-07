<?php
/**
 * Logout Page
 * Web Application Modernization Tracker
 */

require_once dirname(__FILE__) . '/../includes/auth.php';

// Perform logout
global $auth;
$auth->logout();

// Redirect to login page with success message
redirectWithMessage(BASE_URL . '/pages/login.php', 'You have been logged out successfully.', 'success');
?>