<?php
/**
 * Settings Page - Redesigned with Tabbed Interface
 * Code Bunker
 *
 * Modern admin configuration panel with organized sections.
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

// Get active tab from URL parameter, default to 'features'
$activeTab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'features';

// Handle backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    $result = createDatabaseBackup();
    if ($result['success']) {
        $successMessage = $result['message'] . " Backup saved as: " . $result['filename'];
        logActivity($currentUser['id'], 'backup_created', 'database', 0, null, ['filename' => $result['filename'], 'size' => $result['filesize']], 'Database backup created');
    } else {
        $errorMessage = $result['message'];
    }
    $activeTab = 'backups';
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
    $activeTab = 'backups';
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
    $activeTab = 'backups';
}

// Handle file upload for restore
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $uploadedFile = $_FILES['backup_file'];

        if (pathinfo($uploadedFile['name'], PATHINFO_EXTENSION) !== 'sql') {
            $errorMessage = 'Invalid file type. Only .sql files are allowed.';
        } else {
            $backupDir = ROOT_PATH . '/backups';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $safeFilename = "code_bunker_backup_uploaded_{$timestamp}.sql";
            $destination = $backupDir . '/' . $safeFilename;

            if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                $result = restoreDatabaseBackup($destination, true);
                if ($result['success']) {
                    $successMessage = $result['message'];
                    logActivity($currentUser['id'], 'backup_uploaded_restored', 'database', 0, null, ['filename' => $safeFilename], 'Database backup uploaded and restored');
                } else {
                    $errorMessage = $result['message'];
                    unlink($destination);
                }
            } else {
                $errorMessage = 'Failed to upload backup file.';
            }
        }
    } else {
        $errorMessage = 'No file uploaded or upload error occurred.';
    }
    $activeTab = 'backups';
}

// Handle settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $updated = 0;
    $errors = 0;

    // Handle array-based customization inputs
    if (isset($_POST['project_categories']) && is_array($_POST['project_categories'])) {
        $categories = array_filter($_POST['project_categories'], 'strlen');
        $categoriesArray = array_values($categories);
        if (updateSetting('project_categories', $categoriesArray, $currentUser['id'])) {
            $updated++;
        } else {
            $errors++;
        }
    }

    if (isset($_POST['task_types']) && is_array($_POST['task_types'])) {
        $types = array_filter($_POST['task_types'], 'strlen');
        $typesArray = array_values($types);
        if (updateSetting('task_types', $typesArray, $currentUser['id'])) {
            $updated++;
        } else {
            $errors++;
        }
    }

    if (isset($_POST['project_statuses']) && is_array($_POST['project_statuses'])) {
        $labels = array_filter($_POST['project_statuses'], 'strlen');
        $statuses = [];
        foreach ($labels as $label) {
            // Auto-generate key: lowercase and replace spaces with underscores
            $key = strtolower(str_replace(' ', '_', trim($label)));
            $statuses[$key] = $label;
        }
        if (updateSetting('project_statuses', $statuses, $currentUser['id'])) {
            $updated++;
        } else {
            $errors++;
        }
    }

    if (isset($_POST['task_statuses']) && is_array($_POST['task_statuses'])) {
        $labels = array_filter($_POST['task_statuses'], 'strlen');
        $statuses = [];
        foreach ($labels as $label) {
            // Auto-generate key: lowercase and replace spaces with underscores
            $key = strtolower(str_replace(' ', '_', trim($label)));
            $statuses[$key] = $label;
        }
        if (updateSetting('task_statuses', $statuses, $currentUser['id'])) {
            $updated++;
        } else {
            $errors++;
        }
    }

    // Handle regular settings (non-customization)
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8);

            // Handle checkboxes
            if (in_array($settingKey, ['enable_budget_tracking', 'enable_time_tracking', 'enable_notifications', 'enable_calendar_view', 'enable_file_uploads', 'auto_assign_creator'])) {
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
        $activeTab = 'customization'; // Stay on customization tab
    } else if ($errors === 0) {
        $errorMessage = "No settings were updated.";
    }
}

// Get all settings
$categories = ['features', 'general', 'data', 'appearance', 'defaults'];
$allSettings = [];
foreach ($categories as $category) {
    $allSettings[$category] = getSettingsByCategory($category);
}

// Get backup files
$backupFiles = getBackupFiles();
?>

<style>
.settings-tabs {
    border-bottom: 2px solid #dee2e6;
    margin-bottom: 2rem;
}

.settings-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 500;
    padding: 1rem 1.5rem;
    transition: all 0.2s ease;
}

.settings-tabs .nav-link:hover {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background-color: transparent;
}

.settings-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
    background-color: transparent;
}

.settings-tabs .nav-link i {
    margin-right: 0.5rem;
}

.list-editor-section {
    margin-bottom: 2rem;
}

.list-editor-section .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
}
</style>

<!-- Page Header -->
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-gear-fill"></i> System Settings</h2>
                <p class="text-muted mb-0">Configure and customize your Code Bunker installation</p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/pages/dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Alert Messages -->
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

<!-- Tabbed Navigation -->
<ul class="nav nav-tabs settings-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'features' ? 'active' : ''; ?>"
           href="?tab=features">
            <i class="bi bi-toggles"></i> Features
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'customization' ? 'active' : ''; ?>"
           href="?tab=customization">
            <i class="bi bi-tags"></i> Customization
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'preferences' ? 'active' : ''; ?>"
           href="?tab=preferences">
            <i class="bi bi-sliders"></i> Preferences
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo $activeTab === 'backups' ? 'active' : ''; ?>"
           href="?tab=backups">
            <i class="bi bi-database"></i> Backups
        </a>
    </li>
</ul>

<!-- Tab Content -->
<div class="tab-content">

    <!-- FEATURES TAB -->
    <?php if ($activeTab === 'features'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-toggles"></i> Feature Controls</h5>
            <small class="text-muted">Enable or disable major application features</small>
        </div>
        <div class="card-body">
            <form method="POST" action="?tab=features">
                <div class="row">
                    <?php foreach ($allSettings['features'] as $setting): ?>
                    <div class="col-lg-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
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
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Feature Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- CUSTOMIZATION TAB -->
    <?php if ($activeTab === 'customization'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-tags"></i> Categories & Statuses</h5>
            <small class="text-muted">Manage your project categories, task types, and status options</small>
        </div>
        <div class="card-body">
            <form method="POST" action="?tab=customization" id="customizationForm">

                <!-- Project Categories -->
                <div class="list-editor-section">
                    <h6 class="section-title"><i class="bi bi-folder"></i> Project Categories</h6>
                    <?php
                    $projectCategories = getProjectCategories();
                    if (!is_array($projectCategories)) {
                        $projectCategories = [];
                    }
                    ?>
                    <div id="project-categories-list" class="mb-2">
                        <?php foreach ($projectCategories as $index => $category): ?>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="project_categories[]"
                                   value="<?php echo htmlspecialchars($category); ?>" required>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCategory()">
                        <i class="bi bi-plus"></i> Add Category
                    </button>
                </div>

                <hr class="my-4">

                <!-- Task Types -->
                <div class="list-editor-section">
                    <h6 class="section-title"><i class="bi bi-lightning"></i> Task Types</h6>
                    <?php
                    $taskTypes = getTaskTypes();
                    if (!is_array($taskTypes)) {
                        $taskTypes = [];
                    }
                    ?>
                    <div id="task-types-list" class="mb-2">
                        <?php foreach ($taskTypes as $index => $type): ?>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="task_types[]"
                                   value="<?php echo htmlspecialchars($type); ?>" required>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTaskType()">
                        <i class="bi bi-plus"></i> Add Task Type
                    </button>
                </div>

                <hr class="my-4">

                <!-- Project Statuses -->
                <div class="list-editor-section">
                    <h6 class="section-title"><i class="bi bi-flag"></i> Project Statuses</h6>
                    <?php
                    $projectStatuses = getProjectStatuses();
                    if (!is_array($projectStatuses)) {
                        $projectStatuses = [];
                    }
                    ?>
                    <div id="project-statuses-list" class="mb-2">
                        <?php foreach ($projectStatuses as $key => $label): ?>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="project_statuses[]"
                                   value="<?php echo htmlspecialchars($label); ?>" placeholder="Status Label" required>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addProjectStatus()">
                        <i class="bi bi-plus"></i> Add Status
                    </button>
                </div>

                <hr class="my-4">

                <!-- Task Statuses -->
                <div class="list-editor-section">
                    <h6 class="section-title"><i class="bi bi-check-circle"></i> Task Statuses</h6>
                    <?php
                    $taskStatuses = getTaskStatuses();
                    if (!is_array($taskStatuses)) {
                        $taskStatuses = [];
                    }
                    ?>
                    <div id="task-statuses-list" class="mb-2">
                        <?php foreach ($taskStatuses as $key => $label): ?>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" name="task_statuses[]"
                                   value="<?php echo htmlspecialchars($label); ?>" placeholder="Status Label" required>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTaskStatus()">
                        <i class="bi bi-plus"></i> Add Status
                    </button>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save All Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- PREFERENCES TAB -->
    <?php if ($activeTab === 'preferences'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-sliders"></i> General Preferences</h5>
            <small class="text-muted">Configure application defaults and behavior</small>
        </div>
        <div class="card-body">
            <form method="POST" action="?tab=preferences">
                <div class="row">
                <?php foreach ($allSettings['general'] as $setting): ?>
                <div class="col-lg-6 mb-3">
                    <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label fw-bold">
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

                <?php foreach ($allSettings['defaults'] as $setting): ?>
                <div class="col-lg-6 mb-3">
                    <?php if ($setting['setting_key'] === 'auto_assign_creator'): ?>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                               id="setting_<?php echo $setting['setting_key']; ?>"
                               name="setting_<?php echo $setting['setting_key']; ?>"
                               value="1"
                               <?php echo getSetting($setting['setting_key'], false) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-bold" for="setting_<?php echo $setting['setting_key']; ?>">
                            <?php echo str_replace('_', ' ', ucwords($setting['setting_key'], '_')); ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars($setting['description']); ?></small>
                        </label>
                    </div>
                    <?php else: ?>
                    <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label fw-bold">
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

                <div class="text-center mt-4">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Preferences
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- BACKUPS TAB -->
    <?php if ($activeTab === 'backups'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-database"></i> Database Backup & Restore</h5>
            <small class="text-muted">Manage database backups and restore points</small>
        </div>
        <div class="card-body">

        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-download"></i> Create Backup</h5>
                        <p class="card-text">Generate a complete backup of all your data</p>
                        <form method="POST" action="?tab=backups">
                            <button type="submit" name="create_backup" class="btn btn-success w-100">
                                <i class="bi bi-download"></i> Create New Backup
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card bg-light h-100">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-upload"></i> Upload & Restore</h5>
                        <p class="card-text">Upload a backup file to restore your database</p>
                        <form method="POST" action="?tab=backups" enctype="multipart/form-data">
                            <div class="input-group">
                                <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                                <button type="submit" name="upload_restore" class="btn btn-warning">
                                    <i class="bi bi-upload"></i> Restore
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="mb-3"><i class="bi bi-file-earmark-zip"></i> Available Backups (<?php echo count($backupFiles); ?>)</h5>

        <?php if (empty($backupFiles)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No backups available. Create your first backup above.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
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
                                <code><?php echo htmlspecialchars($backup['filename']); ?></code>
                            </td>
                            <td><?php echo $backup['created_formatted']; ?></td>
                            <td><?php echo $backup['size_mb']; ?> MB</td>
                            <td class="text-end">
                                <a href="?download_backup=<?php echo urlencode($backup['filename']); ?>"
                                   class="btn btn-sm btn-outline-primary" title="Download">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-warning" title="Restore"
                                        onclick="confirmRestore('<?php echo htmlspecialchars($backup['filename']); ?>')">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                <form method="POST" action="?tab=backups" style="display: inline;">
                                    <input type="hidden" name="backup_filename" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    <button type="submit" name="delete_backup" class="btn btn-sm btn-outline-danger" title="Delete"
                                            onclick="return confirm('Delete this backup?')">
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

        <form id="restoreForm" method="POST" action="?tab=backups" style="display: none;">
            <input type="hidden" name="restore_filename" id="restoreFilename">
            <input type="hidden" name="restore_backup">
        </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ADVANCED TAB -->
    <?php if ($activeTab === 'advanced'): ?>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-gear"></i> Advanced Options</h5>
            <small class="text-muted">Advanced configuration and data management</small>
        </div>
        <div class="card-body">
            <form method="POST" action="?tab=advanced">
                <?php foreach ($allSettings['data'] as $setting): ?>
                <div class="mb-4">
                    <label for="setting_<?php echo $setting['setting_key']; ?>" class="form-label fw-bold">
                        <?php echo str_replace('_', ' ', ucwords($setting['setting_key'], '_')); ?>
                    </label>
                    <textarea class="form-control font-monospace" rows="3"
                              id="setting_<?php echo $setting['setting_key']; ?>"
                              name="setting_<?php echo $setting['setting_key']; ?>"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                    <div class="form-text"><?php echo htmlspecialchars($setting['description']); ?></div>
                </div>
                <?php endforeach; ?>

                <div class="text-center mt-4">
                    <button type="submit" name="update_settings" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Save Advanced Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function confirmRestore(filename) {
    if (confirm('WARNING: This will replace all current data.\n\nA backup will be created first.\n\nRestore ' + filename + '?')) {
        document.getElementById('restoreFilename').value = filename;
        document.getElementById('restoreForm').submit();
    }
}

// Dynamic list management functions
function removeItem(button) {
    button.closest('.input-group').remove();
}

function addCategory() {
    const list = document.getElementById('project-categories-list');
    const item = document.createElement('div');
    item.className = 'input-group mb-2';
    item.innerHTML = `
        <input type="text" class="form-control" name="project_categories[]" placeholder="Category Name" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    list.appendChild(item);
    item.querySelector('input').focus();
}

function addTaskType() {
    const list = document.getElementById('task-types-list');
    const item = document.createElement('div');
    item.className = 'input-group mb-2';
    item.innerHTML = `
        <input type="text" class="form-control" name="task_types[]" placeholder="Task Type Name" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    list.appendChild(item);
    item.querySelector('input').focus();
}

function addProjectStatus() {
    const list = document.getElementById('project-statuses-list');
    const item = document.createElement('div');
    item.className = 'input-group mb-2';
    item.innerHTML = `
        <input type="text" class="form-control" name="project_statuses[]" placeholder="Status Label" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    list.appendChild(item);
    item.querySelector('input').focus();
}

function addTaskStatus() {
    const list = document.getElementById('task-statuses-list');
    const item = document.createElement('div');
    item.className = 'input-group mb-2';
    item.innerHTML = `
        <input type="text" class="form-control" name="task_statuses[]" placeholder="Status Label" required>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(this)" title="Remove">
            <i class="bi bi-x-lg"></i>
        </button>
    `;
    list.appendChild(item);
    item.querySelector('input').focus();
}
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>
