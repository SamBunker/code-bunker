<?php
/**
 * Settings Page
 * Code Bunker
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

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $result = createDatabaseBackup();
    if ($result['success']) {
        $successMessage = $result['message'] . " Backup saved as: " . $result['filename'];
        logActivity($currentUser['id'], 'backup_created', 'database', 0, null, ['filename' => $result['filename'], 'size' => $result['filesize']], 'Database backup created');
    } else {
        $errorMessage = $result['message'];
    }
}

// Handle backup download
if (isset($_GET['download_backup'])) {
    $filename = $_GET['download_backup'];
    downloadBackupFile($filename);
}

// Handle backup deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $filename = $_POST['backup_filename'];
    $result = deleteBackupFile($filename);
    if ($result['success']) {
        $successMessage = $result['message'];
        logActivity($currentUser['id'], 'backup_deleted', 'database', 0, null, ['filename' => $filename], 'Database backup deleted');
    } else {
        $errorMessage = $result['message'];
    }
}

// Handle backup restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_backup'])) {
    $filename = $_POST['restore_filename'];
    $backupDir = ROOT_PATH . '/backups';
    $filepath = $backupDir . '/' . $filename;

    $result = restoreDatabaseBackup($filepath, true);
    if ($result['success']) {
        $successMessage = $result['message'];
        logActivity($currentUser['id'], 'backup_restored', 'database', 0, null, ['filename' => $filename], 'Database backup restored');
    } else {
        $errorMessage = $result['message'];
    }
}

// Handle file upload for restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['backup_file'];

        // Validate file
        if (pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'sql') {
            $errorMessage = 'Invalid file type. Only .sql files are allowed.';
        } else {
            // Create backups directory if it doesn't exist
            $backupDir = ROOT_PATH . '/backups';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate safe filename
            $timestamp = date('Y-m-d_H-i-s');
            $safeFilename = "code_bunker_backup_uploaded_{$timestamp}.sql";
            $destination = $backupDir . '/' . $safeFilename;

            if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                // Restore the uploaded backup
                $result = restoreDatabaseBackup($destination, true);
                if ($result['success']) {
                    $successMessage = $result['message'];
                    logActivity($currentUser['id'], 'backup_uploaded_restored', 'database', 0, null, ['filename' => $safeFilename], 'Database backup uploaded and restored');
                } else {
                    $errorMessage = $result['message'];
                    // Delete the uploaded file if restore failed
                    unlink($destination);
                }
            } else {
                $errorMessage = 'Failed to upload backup file.';
            }
        }
    } else {
        $errorMessage = 'No file uploaded or upload error occurred.';
    }
}

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

            // Validate JSON for categories and statuses
            if (in_array($settingKey, ['project_categories', 'task_types', 'project_statuses', 'task_statuses'])) {
                $decoded = json_decode($value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errorMessage = "Invalid JSON for {$settingKey}. Please check your syntax.";
                    $errors++;
                    continue;
                }
                // Ensure the value is stored as compact JSON
                $value = json_encode($decoded);
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
    } else if ($errors === 0) {
        $errorMessage = "No settings were updated. Please check your changes and try again.";
    }
}

// Get all settings grouped by category
$categories = ['features', 'general', 'data', 'appearance', 'defaults'];
$allSettings = [];
foreach ($categories as $category) {
    $allSettings[$category] = getSettingsByCategory($category);
}

// Ensure budget tracking setting exists in features if not already there
$budgetTrackingExists = false;
foreach ($allSettings['features'] as $setting) {
    if ($setting['setting_key'] === 'enable_budget_tracking') {
        $budgetTrackingExists = true;
        break;
    }
}

if (!$budgetTrackingExists) {
    // Add budget tracking setting manually if it doesn't exist
    $allSettings['features'][] = [
        'setting_key' => 'enable_budget_tracking',
        'setting_value' => 'false',
        'setting_type' => 'boolean',
        'description' => 'Enable budget tracking for projects and reporting',
        'is_editable' => true,
        'category' => 'features'
    ];
}

// Get list of available backups
$backupFiles = getBackupFiles();
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

    <!-- Categories & Statuses -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-tags"></i> Categories & Statuses
                    </h5>
                    <p class="text-white-50 mb-0 mt-1">Customize dropdown options for projects and tasks</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Project Categories -->
                        <div class="col-lg-6 mb-3">
                            <label for="setting_project_categories" class="form-label fw-bold">
                                <i class="bi bi-folder"></i> Project Categories
                            </label>
                            <textarea class="form-control font-monospace" rows="4"
                                      id="setting_project_categories"
                                      name="setting_project_categories"
                                      placeholder='["Web Application", "Mobile App", "API Service"]'><?php echo htmlspecialchars(json_encode(getProjectCategories(), JSON_PRETTY_PRINT)); ?></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> JSON array of category names. Used in project creation and filtering.
                            </div>
                        </div>

                        <!-- Task Types -->
                        <div class="col-lg-6 mb-3">
                            <label for="setting_task_types" class="form-label fw-bold">
                                <i class="bi bi-list-task"></i> Task Types
                            </label>
                            <textarea class="form-control font-monospace" rows="4"
                                      id="setting_task_types"
                                      name="setting_task_types"
                                      placeholder='["Bug Fix", "Feature", "Documentation"]'><?php echo htmlspecialchars(json_encode(getTaskTypes(), JSON_PRETTY_PRINT)); ?></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> JSON array of task type names. Used in task creation and categorization.
                            </div>
                        </div>

                        <!-- Project Statuses -->
                        <div class="col-lg-6 mb-3">
                            <label for="setting_project_statuses" class="form-label fw-bold">
                                <i class="bi bi-diagram-3"></i> Project Statuses
                            </label>
                            <textarea class="form-control font-monospace" rows="5"
                                      id="setting_project_statuses"
                                      name="setting_project_statuses"
                                      placeholder='{"planning": "Planning", "in_progress": "In Progress"}'><?php echo htmlspecialchars(json_encode(getProjectStatuses(), JSON_PRETTY_PRINT)); ?></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> JSON object with status keys and display labels. Keys are used in database, values are shown to users.
                            </div>
                        </div>

                        <!-- Task Statuses -->
                        <div class="col-lg-6 mb-3">
                            <label for="setting_task_statuses" class="form-label fw-bold">
                                <i class="bi bi-check2-square"></i> Task Statuses
                            </label>
                            <textarea class="form-control font-monospace" rows="5"
                                      id="setting_task_statuses"
                                      name="setting_task_statuses"
                                      placeholder='{"pending": "Pending", "in_progress": "In Progress"}'><?php echo htmlspecialchars(json_encode(getTaskStatuses(), JSON_PRETTY_PRINT)); ?></textarea>
                            <div class="form-text">
                                <i class="bi bi-info-circle"></i> JSON object with status keys and display labels. Keys must match existing data.
                            </div>
                        </div>
                    </div>

                    <!-- Warning Alert -->
                    <div class="alert alert-warning mt-3">
                        <h6><i class="bi bi-exclamation-triangle"></i> Important Notes</h6>
                        <ul class="mb-0">
                            <li><strong>Categories & Task Types</strong>: Use JSON array format <code>["Item 1", "Item 2"]</code></li>
                            <li><strong>Statuses</strong>: Use JSON object format <code>{"key": "Display Label"}</code></li>
                            <li><strong>Status Keys</strong>: Changing status keys may affect existing data. Only change display labels unless you know what you're doing.</li>
                            <li><strong>Validation</strong>: Invalid JSON will be ignored and defaults will be used.</li>
                        </ul>
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

<!-- Database Backup & Restore -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-database"></i> Database Backup & Restore
                </h5>
                <p class="text-white-50 mb-0 mt-1">Backup and restore your entire database</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Create Backup Section -->
                    <div class="col-lg-6 mb-3">
                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-download"></i> Create Backup</h6>
                        <p class="text-muted small">
                            Create a complete backup of all projects, tasks, notes, users, and settings.
                        </p>
                        <form method="POST" action="">
                            <button type="submit" name="create_backup" class="btn btn-success w-100">
                                <i class="bi bi-download"></i> Create New Backup
                            </button>
                        </form>
                    </div>

                    <!-- Upload & Restore Section -->
                    <div class="col-lg-6 mb-3">
                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-upload"></i> Upload & Restore</h6>
                        <p class="text-muted small">
                            Upload a backup file (.sql) to restore your database.
                        </p>
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                                <button type="submit" name="upload_restore" class="btn btn-warning">
                                    <i class="bi bi-upload"></i> Upload & Restore
                                </button>
                            </div>
                            <div class="form-text text-danger mt-2">
                                <i class="bi bi-exclamation-triangle"></i> Warning: This will replace all current data. A backup will be created first.
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Available Backups -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h6 class="border-bottom pb-2 mb-3"><i class="bi bi-file-earmark-zip"></i> Available Backups (<?php echo count($backupFiles); ?>)</h6>

                        <?php if (empty($backupFiles)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No backups available. Create your first backup above.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Filename</th>
                                            <th>Created</th>
                                            <th>Size</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($backupFiles as $backup): ?>
                                        <tr>
                                            <td>
                                                <i class="bi bi-file-earmark-code"></i>
                                                <small class="font-monospace"><?php echo htmlspecialchars($backup['filename']); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo $backup['created_formatted']; ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo $backup['size_mb']; ?> MB</small>
                                            </td>
                                            <td class="text-end">
                                                <a href="?download_backup=<?php echo urlencode($backup['filename']); ?>"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Download backup">
                                                    <i class="bi bi-download"></i>
                                                </a>

                                                <button type="button"
                                                        class="btn btn-sm btn-outline-warning"
                                                        title="Restore this backup"
                                                        onclick="confirmRestore('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>

                                                <form method="POST" action="" style="display: inline;">
                                                    <input type="hidden" name="backup_filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                                    <button type="submit"
                                                            name="delete_backup"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Delete backup"
                                                            onclick="return confirm('Are you sure you want to delete this backup?')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Warning Alert -->
                <div class="alert alert-warning mt-3">
                    <h6><i class="bi bi-exclamation-triangle"></i> Important Notes</h6>
                    <ul class="mb-0">
                        <li>Backups include all data: projects, tasks, notes, users, and settings.</li>
                        <li>Restoring a backup will replace ALL current database data.</li>
                        <li>A pre-restore backup is automatically created before restoring.</li>
                        <li>Keep regular backups in a secure location outside your server.</li>
                        <li>Test your backups periodically to ensure they can be restored.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for restore confirmation -->
<form id="restoreForm" method="POST" action="" style="display: none;">
    <input type="hidden" name="restore_filename" id="restoreFilename">
    <input type="hidden" name="restore_backup">
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

.font-monospace {
    font-family: 'Courier New', monospace;
}
</style>

<script>
function confirmRestore(filename) {
    if (confirm('WARNING: Restoring this backup will replace ALL current database data.\n\nA backup of the current database will be created automatically before restoring.\n\nFilename: ' + filename + '\n\nAre you sure you want to continue?')) {
        document.getElementById('restoreFilename').value = filename;
        document.getElementById('restoreForm').submit();
    }
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>