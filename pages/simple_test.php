<?php
/**
 * Simple Test Page
 * Test the basic application without optimizations
 */

require_once dirname(__FILE__) . '/../config/config.php';
require_once dirname(__FILE__) . '/../includes/auth.php';

// Check if user is logged in for redirect test
$isLoggedIn = isLoggedIn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Test - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Simple Application Test</h1>
        
        <div class="alert alert-info">
            <h4>Application Status:</h4>
            <p>✅ PHP is working</p>
            <p>✅ Config loaded: <?php echo APP_NAME; ?></p>
            <p>✅ Authentication system: <?php echo $isLoggedIn ? 'Logged in' : 'Not logged in'; ?></p>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Navigation Test</h3>
                <div class="list-group">
                    <a href="<?php echo BASE_URL; ?>/pages/login.php" class="list-group-item">Login Page</a>
                    <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="list-group-item">Dashboard</a>
                    <a href="<?php echo BASE_URL; ?>/" class="list-group-item">Index Page</a>
                </div>
            </div>
            <div class="col-md-6">
                <h3>System Info</h3>
                <ul class="list-unstyled">
                    <li><strong>Base URL:</strong> <?php echo BASE_URL; ?></li>
                    <li><strong>Assets URL:</strong> <?php echo ASSETS_URL; ?></li>
                    <li><strong>Debug Mode:</strong> <?php echo DEBUG_MODE ? 'ON' : 'OFF'; ?></li>
                    <li><strong>PHP Version:</strong> <?php echo phpversion(); ?></li>
                </ul>
            </div>
        </div>
        
        <?php if (!$isLoggedIn): ?>
        <div class="alert alert-warning mt-3">
            <h4>Quick Login Test</h4>
            <form method="POST" action="<?php echo BASE_URL; ?>/pages/login.php">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" name="username" class="form-control" placeholder="Username" value="admin">
                    </div>
                    <div class="col-md-4">
                        <input type="password" name="password" class="form-control" placeholder="Password" value="admin123">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="login" class="btn btn-primary">Test Login</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>