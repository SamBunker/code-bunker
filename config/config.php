<?php
/**
 * Application Configuration
 * Web Application Modernization Tracker
 * 
 * Central configuration file for application settings, constants,
 * and environment-specific configurations.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error Reporting (set to false in production)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Application Settings
define('APP_NAME', 'Web App Modernization Tracker');
define('APP_VERSION', '1.0.1');
define('APP_DESCRIPTION', 'Track and manage web application modernization projects');
define('APP_AUTHOR', 'Samuel Bunker');

// Path Constants
define('ROOT_PATH', dirname(dirname(__FILE__)));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');
define('API_PATH', ROOT_PATH . '/api');

// URL Constants (adjust for your XAMPP setup)
define('BASE_URL', 'http://localhost/juniata/web-app-project-management/');
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/uploads');
define('API_URL', BASE_URL . '/api');

// Security Settings
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes in seconds

// File Upload Settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'rtf', 'jpg', 'jpeg', 'png', 'gif', 'bmp',
    'zip', 'rar', 'tar', 'gz'
]);

// Pagination Settings
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Email Settings (configure for production use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@company.com');
define('SMTP_FROM_NAME', APP_NAME);

// Date and Time Settings
define('DEFAULT_TIMEZONE', 'America/New_York');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'm/d/Y');
define('DISPLAY_DATETIME_FORMAT', 'm/d/Y g:i A');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Application Status Constants
define('PROJECT_STATUS', [
    'planning' => 'Planning',
    'in_progress' => 'In Progress',
    'testing' => 'Testing',
    'completed' => 'Completed',
    'on_hold' => 'On Hold'
]);

define('TASK_STATUS', [
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'blocked' => 'Blocked',
    'cancelled' => 'Cancelled'
]);

define('PRIORITY_LEVELS', [
    'critical' => 'Critical',
    'high' => 'High',
    'medium' => 'Medium',
    'low' => 'Low'
]);

define('USER_ROLES', [
    'admin' => 'Administrator',
    'user' => 'User'
]);

// Status Colors for UI
define('STATUS_COLORS', [
    'planning' => '#6c757d',
    'in_progress' => '#007bff',
    'testing' => '#17a2b8',
    'completed' => '#28a745',
    'on_hold' => '#ffc107',
    'blocked' => '#dc3545',
    'cancelled' => '#6c757d',
    'pending' => '#6c757d'
]);

define('PRIORITY_COLORS', [
    'critical' => '#dc3545',
    'high' => '#fd7e14',
    'medium' => '#ffc107',
    'low' => '#28a745'
]);

/**
 * Helper function to get configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value
 */
function getConfig($key, $default = null) {
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

/**
 * Helper function to format dates for display
 * @param string $date Date string
 * @param string $format Format to use (default is display format)
 * @return string Formatted date
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (empty($date) || $date === '0000-00-00') {
        return 'Not set';
    }
    
    try {
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Helper function to format datetime for display
 * @param string $datetime Datetime string
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    return formatDate($datetime, DISPLAY_DATETIME_FORMAT);
}

/**
 * Helper function to get status badge HTML
 * @param string $status Status value
 * @param string $type Type of status (project, task, etc.)
 * @return string HTML for status badge
 */
function getStatusBadge($status, $type = 'project') {
    $statusLabels = ($type === 'task') ? TASK_STATUS : PROJECT_STATUS;
    $label = isset($statusLabels[$status]) ? $statusLabels[$status] : ucfirst($status);
    $color = isset(STATUS_COLORS[$status]) ? STATUS_COLORS[$status] : '#6c757d';
    
    $icon = '';
    switch($status) {
        case 'completed': $icon = '✓ '; break;
        case 'in_progress': $icon = '⏳ '; break;
        case 'blocked': $icon = '🚫 '; break;
        case 'on_hold': $icon = '⏸ '; break;
    }
    
    return '<span class="badge badge-' . $status . '" style="background-color: ' . $color . '" aria-label="Status: ' . $label . '">' . $icon . $label . '</span>';
}

/**
 * Helper function to get priority badge HTML
 * @param string $priority Priority value
 * @return string HTML for priority badge
 */
function getPriorityBadge($priority) {
    $label = isset(PRIORITY_LEVELS[$priority]) ? PRIORITY_LEVELS[$priority] : ucfirst($priority);
    $color = isset(PRIORITY_COLORS[$priority]) ? PRIORITY_COLORS[$priority] : '#6c757d';
    
    $icon = '';
    switch($priority) {
        case 'critical': $icon = '⚠ '; break;
        case 'high': $icon = '🔴 '; break;
        case 'medium': $icon = '🟡 '; break;
        case 'low': $icon = '🟢 '; break;
    }
    
    return '<span class="badge badge-' . $priority . '" style="background-color: ' . $color . '" aria-label="Priority: ' . $label . '">' . $icon . $label . '</span>';
}

/**
 * Helper function to sanitize input data
 * @param mixed $data Input data
 * @return mixed Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper function to validate email
 * @param string $email Email address
 * @return bool True if valid email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Helper function to generate CSRF token
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Helper function to validate CSRF token
 * @param string $token Token to validate
 * @return bool True if valid token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Helper function to redirect with message
 * @param string $url URL to redirect to
 * @param string $message Message to display
 * @param string $type Message type (success, error, warning, info)
 */
function redirectWithMessage($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Helper function to get and clear flash message
 * @return array|null Flash message data
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        return $message;
    }
    
    return null;
}

/**
 * Helper function to log activity
 * @param int $userId User ID
 * @param string $action Action performed
 * @param string $entityType Entity type (project, task, etc.)
 * @param int $entityId Entity ID
 * @param array $oldValues Old values (optional)
 * @param array $newValues New values (optional)
 * @param string $description Additional description (optional)
 */
function logActivity($userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null, $description = null) {
    try {
        $query = "INSERT INTO activity_log (user_id, action, entity_type, entity_id, old_values, new_values, description, ip_address, user_agent) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        executeUpdate($query, $params);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

// Auto-include required files
require_once CONFIG_PATH . '/database.php';

// Set error log file
ini_set('error_log', ROOT_PATH . '/logs/error.log');

// Create necessary directories if they don't exist
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0755, true);
}

if (!file_exists(ROOT_PATH . '/logs')) {
    mkdir(ROOT_PATH . '/logs', 0755, true);
}

?>