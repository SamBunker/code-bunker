<?php
/**
 * Authentication Functions
 * Web Application Modernization Tracker
 * 
 * Handles user authentication, session management, and security functions.
 */

require_once dirname(__FILE__) . '/../config/config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDatabase();
    }

    /**
     * Register a new user
     * @param string $username Username
     * @param string $email Email address
     * @param string $password Plain text password
     * @param string $firstName First name
     * @param string $lastName Last name
     * @param string $role User role (default: user)
     * @return array Result with success status and message
     */
    public function register($username, $email, $password, $firstName, $lastName, $role = 'user') {
        try {
            // Validate input
            $validation = $this->validateRegistrationData($username, $email, $password);
            if (!$validation['success']) {
                return $validation;
            }

            // Check if username or email already exists
            $existingUser = $this->checkUserExists($username, $email);
            if ($existingUser) {
                return [
                    'success' => false,
                    'message' => 'Username or email already exists'
                ];
            }

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_HASH_ALGO);

            // Insert user into database
            $query = "INSERT INTO users (username, email, password_hash, first_name, last_name, role) 
                      VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$username, $email, $passwordHash, $firstName, $lastName, $role];

            $result = executeUpdate($query, $params);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user_id' => $this->db->getLastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to register user'
                ];
            }

        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during registration'
            ];
        }
    }

    /**
     * Authenticate user login
     * @param string $username Username or email
     * @param string $password Plain text password
     * @return array Result with success status and user data
     */
    public function login($username, $password) {
        try {
            // Check login attempts
            if ($this->isAccountLocked($username)) {
                return [
                    'success' => false,
                    'message' => 'Account temporarily locked due to multiple failed login attempts'
                ];
            }

            // Get user from database
            $user = $this->getUserByUsernameOrEmail($username);

            if (!$user) {
                $this->recordFailedLogin($username);
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->recordFailedLogin($username);
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }

            // Check if user is active
            if (!$user['is_active']) {
                return [
                    'success' => false,
                    'message' => 'Account is deactivated'
                ];
            }

            // Clear failed login attempts
            $this->clearFailedLogins($username);

            // Update last login
            $this->updateLastLogin($user['id']);

            // Create session
            $this->createUserSession($user);

            // Log successful login
            logActivity($user['id'], 'login', 'user', $user['id'], null, null, 'User logged in successfully');

            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $this->sanitizeUserData($user)
            ];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login'
            ];
        }
    }

    /**
     * Logout user
     * @return bool Success status
     */
    public function logout() {
        try {
            if (isset($_SESSION['user_id'])) {
                logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], null, null, 'User logged out');
            }

            // Clear session data
            session_unset();
            session_destroy();

            return true;
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is logged in
     * @return bool True if logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']) && $this->isSessionValid();
    }

    /**
     * Check if current user is admin
     * @return bool True if admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Get current user data
     * @return array|null User data or null if not logged in
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email'],
            'first_name' => $_SESSION['first_name'],
            'last_name' => $_SESSION['last_name'],
            'role' => $_SESSION['role']
        ];
    }

    /**
     * Require login (redirect if not logged in)
     * @param string $redirectUrl URL to redirect to after login
     */
    public function requireLogin($redirectUrl = null) {
        if (!$this->isLoggedIn()) {
            if ($redirectUrl) {
                $_SESSION['redirect_after_login'] = $redirectUrl;
            }
            header('Location: ' . BASE_URL . '/pages/login.php');
            exit();
        }
    }

    /**
     * Require admin role
     */
    public function requireAdmin() {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            redirectWithMessage(BASE_URL . '/pages/dashboard.php', 'Access denied. Administrator privileges required.', 'error');
        }
    }

    /**
     * Change user password
     * @param int $userId User ID
     * @param string $currentPassword Current password
     * @param string $newPassword New password
     * @return array Result with success status and message
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get user
            $user = $this->getUserById($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }

            // Verify current password
            if (!password_verify($currentPassword, $user['password_hash'])) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ];
            }

            // Validate new password
            if (strlen($newPassword) < 6) {
                return [
                    'success' => false,
                    'message' => 'New password must be at least 6 characters long'
                ];
            }

            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_HASH_ALGO);

            // Update password
            $query = "UPDATE users SET password_hash = ? WHERE id = ?";
            $result = executeUpdate($query, [$newPasswordHash, $userId]);

            if ($result) {
                logActivity($userId, 'password_change', 'user', $userId, null, null, 'User changed password');
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to change password'
                ];
            }

        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred while changing password'
            ];
        }
    }

    /**
     * Private helper methods
     */

    private function validateRegistrationData($username, $email, $password) {
        if (empty($username) || empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'All fields are required'
            ];
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            return [
                'success' => false,
                'message' => 'Username must be between 3 and 50 characters'
            ];
        }

        if (!isValidEmail($email)) {
            return [
                'success' => false,
                'message' => 'Invalid email address'
            ];
        }

        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'Password must be at least 6 characters long'
            ];
        }

        return ['success' => true];
    }

    private function checkUserExists($username, $email) {
        $query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $result = executeQuerySingle($query, [$username, $email]);
        return $result !== false;
    }

    private function getUserByUsernameOrEmail($identifier) {
        $query = "SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1";
        return executeQuerySingle($query, [$identifier, $identifier]);
    }

    private function getUserById($userId) {
        $query = "SELECT * FROM users WHERE id = ?";
        return executeQuerySingle($query, [$userId]);
    }

    private function updateLastLogin($userId) {
        $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
        executeUpdate($query, [$userId]);
    }

    private function createUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();

        // Regenerate session ID for security
        session_regenerate_id(true);
    }

    private function sanitizeUserData($user) {
        unset($user['password_hash']);
        return $user;
    }

    private function isSessionValid() {
        if (!isset($_SESSION['login_time'])) {
            return false;
        }

        // Check session timeout
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        // Update login time to extend session
        $_SESSION['login_time'] = time();
        return true;
    }

    private function recordFailedLogin($identifier) {
        // For simplicity, using session to track failed attempts
        // In production, this should be stored in database
        if (!isset($_SESSION['failed_logins'])) {
            $_SESSION['failed_logins'] = [];
        }
        
        $_SESSION['failed_logins'][$identifier] = [
            'count' => ($_SESSION['failed_logins'][$identifier]['count'] ?? 0) + 1,
            'last_attempt' => time()
        ];
    }

    private function isAccountLocked($identifier) {
        if (!isset($_SESSION['failed_logins'][$identifier])) {
            return false;
        }

        $failedLogin = $_SESSION['failed_logins'][$identifier];
        
        // Check if lockout period has expired
        if (time() - $failedLogin['last_attempt'] > LOCKOUT_TIME) {
            unset($_SESSION['failed_logins'][$identifier]);
            return false;
        }

        return $failedLogin['count'] >= MAX_LOGIN_ATTEMPTS;
    }

    private function clearFailedLogins($identifier) {
        if (isset($_SESSION['failed_logins'][$identifier])) {
            unset($_SESSION['failed_logins'][$identifier]);
        }
    }
}

// Global authentication instance
$auth = new Auth();

/**
 * Helper functions for easy access
 */
function isLoggedIn() {
    global $auth;
    return $auth->isLoggedIn();
}

function isAdmin() {
    global $auth;
    return $auth->isAdmin();
}

function getCurrentUser() {
    global $auth;
    return $auth->getCurrentUser();
}

function requireLogin($redirectUrl = null) {
    global $auth;
    $auth->requireLogin($redirectUrl);
}

function requireAdmin() {
    global $auth;
    $auth->requireAdmin();
}

?>