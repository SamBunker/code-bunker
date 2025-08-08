<?php
/**
 * Login Page
 * Code Bunker
 */

require_once dirname(__FILE__) . '/../includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit();
}

$pageTitle = 'Login';
$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!empty($username) && !empty($password)) {
            global $auth;
            $result = $auth->login($username, $password);
            
            if ($result['success']) {
                // Check for redirect URL
                $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/pages/dashboard.php';
                unset($_SESSION['redirect_after_login']);
                
                header("Location: $redirect");
                exit();
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = 'Please enter both username and password.';
        }
    }
    
    // Handle registration form submission
    elseif (isset($_POST['register'])) {
        $username = sanitizeInput($_POST['reg_username'] ?? '');
        $email = sanitizeInput($_POST['reg_email'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirmPassword = $_POST['reg_confirm_password'] ?? '';
        $firstName = sanitizeInput($_POST['reg_first_name'] ?? '');
        $lastName = sanitizeInput($_POST['reg_last_name'] ?? '');
        
        if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName)) {
            $error_message = 'All fields are required.';
        } elseif ($password !== $confirmPassword) {
            $error_message = 'Passwords do not match.';
        } else {
            global $auth;
            $result = $auth->register($username, $email, $password, $firstName, $lastName);
            
            if ($result['success']) {
                $success_message = 'Registration successful! Please login with your credentials.';
            } else {
                $error_message = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . APP_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card card">
            <div class="card-body p-4">
                <div class="login-header">
                    <h2><i class="bi bi-kanban text-primary"></i></h2>
                    <h2><?php echo APP_NAME; ?></h2>
                    <p class="text-muted">Secure project management for development teams</p>
                </div>
                
                <!-- Display messages -->
                <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert" aria-live="polite" aria-atomic="true">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> 
                    <span class="visually-hidden">Error: </span>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-3" role="tablist" aria-label="Login and Registration Options">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" 
                                type="button" role="tab" aria-controls="login" aria-selected="true">
                            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" 
                                type="button" role="tab" aria-controls="register" aria-selected="false">
                            <i class="bi bi-person-plus" aria-hidden="true"></i> Register
                        </button>
                    </li>
                </ul>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Login Tab -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
                        <form method="POST" action="" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text" aria-hidden="true"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required aria-describedby="username-help"
                                           autocomplete="username">
                                </div>
                                <div id="username-help" class="form-text">Enter your username or email address</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                        <i class="bi bi-eye" id="password-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                            </div>
                            
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Demo credentials: <strong>admin</strong> / <strong>admin123</strong>
                                </small>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Register Tab -->
                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="reg_first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="reg_first_name" name="reg_first_name" 
                                           value="<?php echo isset($_POST['reg_first_name']) ? htmlspecialchars($_POST['reg_first_name']) : ''; ?>" 
                                           required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="reg_last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="reg_last_name" name="reg_last_name" 
                                           value="<?php echo isset($_POST['reg_last_name']) ? htmlspecialchars($_POST['reg_last_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="reg_username" name="reg_username" 
                                           value="<?php echo isset($_POST['reg_username']) ? htmlspecialchars($_POST['reg_username']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="reg_email" name="reg_email" 
                                           value="<?php echo isset($_POST['reg_email']) ? htmlspecialchars($_POST['reg_email']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('reg_password')">
                                        <i class="bi bi-eye" id="reg_password-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg_confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="reg_confirm_password" name="reg_confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('reg_confirm_password')">
                                        <i class="bi bi-eye" id="reg_confirm_password-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="register" class="btn btn-success">
                                    <i class="bi bi-person-plus"></i> Register
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?><br>
                        Built for enterprise web application management
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const eye = document.getElementById(fieldId + '-eye');
            
            if (field.type === 'password') {
                field.type = 'text';
                eye.className = 'bi bi-eye-slash';
            } else {
                field.type = 'password';
                eye.className = 'bi bi-eye';
            }
        }
        
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>