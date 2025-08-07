<?php
/**
 * Settings Page
 * Web Application Modernization Tracker
 * 
 * Admin configuration panel for system-wide settings and feature toggles.
 */

$pageTitle = 'System Settings';
require_once dirname(__FILE__) . '/../includes/header.php';

requireLogin();

// Require admin access
if (!isAdmin()) {
    header("Location: " . BASE_URL . "/pages/dashboard.php?error=" . urlencode("Access denied. Admin privileges required."));
    exit;
}

$currentUser = getCurrentUser();
$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $updated = 0;
    $errors = 0;
    
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8); // Remove 'setting_' prefix
            
            // Handle checkboxes (they're not sent if unchecked)
            if ($settingKey === 'enable_budget_tracking' || 
                $settingKey === 'enable_time_tracking' || 
                $settingKey === 'enable_notifications' || 
                $settingKey === 'enable_calendar_view' ||
                $settingKey === 'enable_file_uploads' ||
                $settingKey === 'auto_assign_creator') {
                $value = isset($_POST[$key]) ? true : false;
            }
            
            if (updateSetting($settingKey, $value, $currentUser['id'])) {
                $updated++;
            } else {
                $errors++;
            }
        }
    }
    
    if ($updated > 0) {
        $successMessage = "Successfully updated $updated setting(s).";
        if ($errors > 0) {
            $successMessage .= " $errors setting(s) could not be updated.";
        }
    } else {
        $errorMessage = "No settings were updated. Please check your changes and try again.";
    }
}

// Get all settings grouped by category
$categories = ['features', 'general', 'data', 'appearance', 'defaults'];
$allSettings = [];
foreach ($categories as $category) {
    $allSettings[$category] = getSettingsByCategory($category);
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-gear"></i> System Settings</h2>
            <div>
                <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        <p class="text-muted">Configure application features and system preferences</p>
    </div>
</div>

<?php if ($successMessage): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($errorMessage): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($errorMessage); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST" action="">
    <!-- Feature Toggles -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-toggles"></i> Feature Controls
                    </h5>
                    <p class="text-muted mb-0 mt-1">Enable or disable major application features</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($allSettings['features'] as $setting): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="setting_<?php echo $setting['setting_key']; ?>"
                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                       value="1"
                                       <?php echo getSetting($setting['setting_key'], false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="setting_<?php echo $setting['setting_key']; ?>">
                                    <strong><?php echo str_replace(['enable_', '_'], ['', ' '], ucwords($setting['setting_key'], '_')); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($setting['description']); ?></small>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- General Settings -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-sliders"></i> General Configuration
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($allSettings['general'] as $setting): ?>
                        <div class="col-lg-6 mb-3">
                            <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                <?php echo str_replace('_', ' ', ucwords($setting['setting_key'], '_')); ?>
                            </label>
                            <?php if ($setting['setting_type'] === 'number'): ?>
                            <input type="number" class="form-control" 
                                   id="setting_<?php echo $setting['setting_key']; ?>"
                                   name="setting_<?php echo $setting['setting_key']; ?>"
                                   value="<?php echo htmlspecialchars(getSetting($setting['setting_key'], '')); ?>">
                            <?php else: ?>
                            <input type="text" class="form-control" 
                                   id="setting_<?php echo $setting['setting_key']; ?>"
                                   name="setting_<?php echo $setting['setting_key']; ?>"
                                   value="<?php echo htmlspecialchars(getSetting($setting['setting_key'], '')); ?>">
                            <?php endif; ?>
                            <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Options -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul"></i> Data Options
                    </h5>
                    <p class="text-muted mb-0 mt-1">Configure available categories and types</p>
                </div>
                <div class="card-body">
                    <?php foreach ($allSettings['data'] as $setting): ?>
                    <div class="mb-3">
                        <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                            <?php echo str_replace('_', ' ', ucwords($setting['setting_key'], '_')); ?>
                        </label>
                        <textarea class="form-control" rows="3"
                                  id="setting_<?php echo $setting['setting_key']; ?>"
                                  name="setting_<?php echo $setting['setting_key']; ?>"
                                  placeholder='["Option 1", "Option 2", "Option 3"]'><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                        <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?> (JSON format)</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Default Settings -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear-fill"></i> Default Preferences
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($allSettings['defaults'] as $setting): ?>
                        <div class="col-lg-6 mb-3">
                            <?php if ($setting['setting_key'] === 'auto_assign_creator'): ?>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="setting_<?php echo $setting['setting_key']; ?>"
                                       name="setting_<?php echo $setting['setting_key']; ?>"
                                       value="1"
                                       <?php echo getSetting($setting['setting_key'], false) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="setting_<?php echo $setting['setting_key']; ?>">
                                    <?php echo str_replace('_', ' ', ucwords($setting['setting_key'], '_')); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($setting['description']); ?></small>
                                </label>
                            </div>
                            <?php else: ?>
                            <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label">
                                <?php echo str_replace('_', ' ', ucwords($setting['setting_key'], '_')); ?>
                            </label>
                            <select class="form-select" id="setting_<?php echo $setting['setting_key']; ?>"
                                    name="setting_<?php echo $setting['setting_key']; ?>">
                                <?php 
                                $currentValue = getSetting($setting['setting_key'], 'medium');
                                $priorities = ['critical', 'high', 'medium', 'low'];
                                foreach ($priorities as $priority): 
                                ?>
                                <option value="<?php echo $priority; ?>" <?php echo $currentValue === $priority ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($priority); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <button type="submit" name="update_settings" class="btn btn-primary btn-lg me-3">
                        <i class="bi bi-check-lg"></i> Save Settings
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Budget Tracking Info -->
<div class="row mt-4">
    <div class="col-12">
        <div class="alert alert-info">
            <h6><i class="bi bi-info-circle"></i> About Budget Tracking</h6>
            <p class="mb-0">
                Budget tracking is currently <strong><?php echo isFeatureEnabled('budget_tracking') ? 'enabled' : 'disabled'; ?></strong>. 
                When disabled, budget-related fields are hidden throughout the application, making it more suitable for 
                time-focused project management. You can toggle this feature at any time.
            </p>
        </div>
    </div>
</div>

<style>
.form-check-input:checked {
    background-color: #198754;
    border-color: #198754;
}

.card-header {
    border-bottom: 2px solid #dee2e6;
}

.form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
}

.alert-info {
    border-left: 4px solid #0dcaf0;
}
</style>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>