<?php
/**
 * Common Header Include
 * Code Bunker
 * 
 * Contains HTML head section and navigation for all pages.
 */

require_once dirname(__FILE__) . '/../includes/auth.php';
require_once dirname(__FILE__) . '/../includes/functions.php';

// Get current user
$currentUser = getCurrentUser();
$isLoggedIn = isLoggedIn();
$isAdmin = isAdmin();

// Get current page for navigation highlighting
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Code Bunker - Secure project management system for development teams">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Preconnect to external domains for faster loading -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
    <!-- Critical CSS inline for above-the-fold content -->
    <style>
        /* Critical styles for immediate rendering */
        body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;background-color:#f8f9fa}
        .navbar{background-color:#0d6efd!important}.navbar-brand{font-weight:600;font-size:1.25rem}
        .skip-link{position:absolute;top:-40px;left:6px;z-index:9999;background:#000;color:#fff;padding:8px;text-decoration:none;font-weight:bold;border-radius:0 0 4px 4px}
        .skip-link:focus{top:0}
        .sr-only{position:absolute!important;width:1px!important;height:1px!important;padding:0!important;margin:-1px!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}
        .sr-only-focusable:active,.sr-only-focusable:focus{position:static!important;width:auto!important;height:auto!important;padding:inherit!important;margin:inherit!important;overflow:visible!important;clip:auto!important;white-space:inherit!important}
        *:focus{outline:2px solid #0d6efd;outline-offset:2px}
        .main-loading{display:flex;justify-content:center;align-items:center;min-height:200px}
    </style>
    
    <!-- Non-critical CSS loaded asynchronously -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></noscript>
    
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"></noscript>
    
    <!-- Custom CSS -->
    <link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet" media="print" onload="this.media='all';this.onload=null;">
    <noscript><link href="<?php echo ASSETS_URL; ?>/css/style.css" rel="stylesheet"></noscript>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo BASE_URL; ?>/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="WebApp Tracker">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo ASSETS_URL; ?>/favicon.ico">
    <link rel="apple-touch-icon" href="<?php echo ASSETS_URL; ?>/icon-192x192.png">
</head>
<body>
    <!-- Skip Navigation Link -->
    <a class="skip-link sr-only sr-only-focusable" href="#main-content">Skip to main content</a>
    
    <?php if ($isLoggedIn): ?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" role="navigation" aria-label="Main navigation">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/pages/dashboard.php" aria-label="<?php echo APP_NAME; ?> - Go to Dashboard">
                <i class="bi bi-kanban" aria-hidden="true"></i>
                <?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/pages/dashboard.php"
                           <?php echo $currentPage === 'dashboard' ? 'aria-current="page"' : ''; ?>>
                            <i class="bi bi-house" aria-hidden="true"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'projects' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/pages/projects.php">
                            <i class="bi bi-folder"></i> Projects
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'tasks' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/pages/tasks.php">
                            <i class="bi bi-list-check"></i> Tasks
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'calendar' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/pages/calendar.php">
                            <i class="bi bi-calendar"></i> Calendar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'advanced_reports' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/pages/advanced_reports.php">
                            <i class="bi bi-graph-up-arrow"></i> Reports
                        </a>
                    </li>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage === 'templates' ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/pages/templates.php">
                            <i class="bi bi-file-earmark-text"></i> Templates
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i> Admin
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/users.php">
                                <i class="bi bi-people"></i> User Management
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/settings.php">
                                <i class="bi bi-sliders"></i> Settings
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/activity_log.php">
                                <i class="bi bi-clock-history"></i> Activity Log
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- User dropdown -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">
                                <?php echo htmlspecialchars($currentUser['email']); ?><br>
                                <small class="text-muted"><?php echo ucfirst($currentUser['role']); ?></small>
                            </h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/profile.php">
                                <i class="bi bi-person"></i> Profile
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/change_password.php">
                                <i class="bi bi-key"></i> Change Password
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="https://github.com/SamBunker" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-github"></i> About Developer
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Flash Messages -->
    <?php
    $flashMessage = getFlashMessage();
    if ($flashMessage):
    ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-<?php echo $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show" 
             role="alert" aria-live="polite" aria-atomic="true">
            <span class="visually-hidden"><?php echo ucfirst($flashMessage['type']); ?>: </span>
            <?php echo htmlspecialchars($flashMessage['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main id="main-content" class="<?php echo $isLoggedIn ? 'container-fluid mt-4' : ''; ?>" role="main">