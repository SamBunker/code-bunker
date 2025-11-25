<?php
/**
 * Template Detail View Page
 * Code Bunker
 */

// Start output buffering to prevent header issues
ob_start();

$pageTitle = 'Template Details';

require_once dirname(__FILE__) . '/../includes/header.php';

// Require admin access for template management
requireLogin();
if (!isAdmin()) {
    ob_end_clean();
    header('Location: dashboard.php?error=Access denied');
    exit;
}

// Get current user
$currentUser = getCurrentUser();

// Get template ID from URL
$templateId = intval($_GET['id'] ?? 0);

if (!$templateId) {
    ob_end_clean();
    header('Location: templates.php');
    exit;
}

// Get template details
$template = getProjectTemplate($templateId);
if (!$template) {
    ob_end_clean();
    header('Location: templates.php?error=Template not found');
    exit;
}

// Get template tasks grouped by phases
$templateTasksByPhase = getTemplateTasksByPhase($templateId);
if ($templateTasksByPhase === false) $templateTasksByPhase = [];

// Get template phases
$templatePhases = getTemplatePhases($templateId);
if ($templatePhases === false) $templatePhases = [];

// Also get flat list for backwards compatibility
$templateTasks = getTemplateTasks($templateId);
if ($templateTasks === false) $templateTasks = [];

// Handle form submissions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_template':
            $templateData = [
                'name' => sanitizeInput($_POST['name']),
                'description' => sanitizeInput($_POST['description']),
                'category' => sanitizeInput($_POST['category']),
                'default_priority' => sanitizeInput($_POST['default_priority']),
                'estimated_duration_days' => intval($_POST['estimated_duration_days']),
                'estimated_hours' => floatval($_POST['estimated_hours'])
            ];
            
            $result = updateProjectTemplate($templateId, $templateData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template data
                $template = getProjectTemplate($templateId);
            }
            break;
            
        case 'add_template_task':
            $taskData = [
                'template_id' => $templateId,
                'phase_id' => $_POST['phase_id'] === '' ? null : intval($_POST['phase_id']),
                'title' => sanitizeInput($_POST['task_title']),
                'description' => sanitizeInput($_POST['task_description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['task_priority']),
                'estimated_hours' => floatval($_POST['task_estimated_hours']),
                'order_index' => intval($_POST['order_index']),
                'days_after_start' => intval($_POST['days_after_start'])
            ];
            
            $result = createTemplateTask($taskData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template data
                $templateTasksByPhase = getTemplateTasksByPhase($templateId);
                $templateTasks = getTemplateTasks($templateId);
                if ($templateTasks === false) $templateTasks = [];
            }
            break;
            
        case 'update_template_task':
            $taskId = intval($_POST['task_id']);
            $taskData = [
                'title' => sanitizeInput($_POST['task_title']),
                'description' => sanitizeInput($_POST['task_description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['task_priority']),
                'estimated_hours' => floatval($_POST['task_estimated_hours']),
                'order_index' => intval($_POST['order_index']),
                'days_after_start' => intval($_POST['days_after_start']),
                'phase_id' => $_POST['phase_id'] === '' ? null : intval($_POST['phase_id'])
            ];
            
            $result = updateTemplateTask($taskId, $taskData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template data
                $templateTasksByPhase = getTemplateTasksByPhase($templateId);
                $templateTasks = getTemplateTasks($templateId);
                if ($templateTasks === false) $templateTasks = [];
            }
            break;
            
        case 'delete_template_task':
            $taskId = intval($_POST['task_id']);
            $result = deleteTemplateTask($taskId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template data
                $templateTasksByPhase = getTemplateTasksByPhase($templateId);
                $templateTasks = getTemplateTasks($templateId);
                if ($templateTasks === false) $templateTasks = [];
            }
            break;
            
        case 'move_template_task_within_phase':
            $taskId = intval($_POST['task_id']);
            $direction = sanitizeInput($_POST['direction']);
            $result = moveTemplateTaskWithinPhase($taskId, $direction);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template data
                $templateTasksByPhase = getTemplateTasksByPhase($templateId);
                $templateTasks = getTemplateTasks($templateId);
            }
            break;
            
        case 'move_template_task_to_phase':
            $taskId = intval($_POST['task_id']);
            $newPhaseId = $_POST['new_phase_id'] === '' ? null : intval($_POST['new_phase_id']);
            $result = moveTemplateTaskToPhase($taskId, $newPhaseId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template data
                $templateTasksByPhase = getTemplateTasksByPhase($templateId);
                $templateTasks = getTemplateTasks($templateId);
            }
            break;
            
        case 'reorder_template_tasks':
            $taskIds = $_POST['task_ids'] ?? [];
            $phaseId = $_POST['phase_id'] === '' ? null : intval($_POST['phase_id']);
            
            // Validate task_ids is an array of integers
            $taskIds = array_map('intval', $taskIds);
            $taskIds = array_filter($taskIds, function($id) { return $id > 0; });
            
            if (!empty($taskIds)) {
                $result = reorderTemplateTasks($taskIds, $phaseId, $templateId);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                
                if ($result['success']) {
                    // Refresh template data
                    $templateTasksByPhase = getTemplateTasksByPhase($templateId);
                    $templateTasks = getTemplateTasks($templateId);
                }
            } else {
                $message = 'No valid template task IDs provided for reordering';
                $messageType = 'error';
            }
            break;
    }
}

// Get data for dropdowns
$taskTypes = getTaskTypes();

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="templates.php">Templates</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($template['name']) ?></li>
            </ol>
        </nav>
        <h1><i class="bi bi-file-earmark-text"></i> <?= htmlspecialchars($template['name']) ?></h1>
        <p class="text-muted">Template details and task management</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTemplateModal">
            <i class="bi bi-pencil"></i> Edit Template
        </button>
        <a href="templates.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Templates
        </a>
    </div>
</div>

<!-- Flash message -->
<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : $messageType; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Template Information Cards -->
<div class="row g-4 mb-4">
    <!-- Template Details Card -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Template Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Category</label>
                        <div><?= htmlspecialchars($template['category']) ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Default Priority</label>
                        <div><?= getPriorityBadge($template['default_priority']) ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Estimated Duration</label>
                        <div><?= $template['estimated_duration_days'] ?> days</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Estimated Hours</label>
                        <div><?= number_format($template['estimated_hours'], 1) ?>h</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Created By</label>
                        <div><?= htmlspecialchars($template['created_by_name']) ?></div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Created</label>
                        <div><?= formatDateTime($template['created_at']) ?></div>
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-muted small">Description</label>
                        <div class="p-3 bg-light rounded">
                            <?= $template['description'] ? nl2br(htmlspecialchars($template['description'])) : '<em class="text-muted">No description provided</em>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Template Statistics Card -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Template Stats</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-muted small">Total Tasks</label>
                    <div class="fs-4 text-primary"><?= count($templateTasks) ?></div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Total Estimated Hours</label>
                    <div class="fs-4 text-info">
                        <?php
                        $totalHours = array_sum(array_column($templateTasks, 'estimated_hours'));
                        echo number_format($totalHours, 1);
                        ?>h
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small">Task Types</label>
                    <div>
                        <?php
                        $taskTypeCounts = array_count_values(array_column($templateTasks, 'task_type'));
                        foreach ($taskTypeCounts as $type => $count):
                        ?>
                        <span class="badge bg-secondary me-1"><?= htmlspecialchars($type) ?> (<?= $count ?>)</span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Work Breakdown Structure Section -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Work Breakdown Structure (<?= count($templateTasks) ?>)</h5>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPhaseModal">
            <i class="bi bi-plus-lg"></i> Add Phase
        </button>
    </div>
    <div class="card-body">
        <?php if (empty($templateTasksByPhase)): ?>
        <div class="text-center py-4">
            <i class="bi bi-diagram-3 fs-1 text-muted"></i>
            <p class="text-muted mt-2">No phases or tasks found for this template</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPhaseModal">
                <i class="bi bi-plus-lg"></i> Create First Phase
            </button>
        </div>
        <?php else: ?>
        <div class="wbs-container">
            <?php 
            $phaseNumber = 1;
            foreach ($templateTasksByPhase as $phaseId => $phase): 
            ?>
            <div class="phase-section mb-4">
                <div class="phase-header d-flex justify-content-between align-items-center p-3 bg-light rounded border">
                    <div class="d-flex align-items-center">
                        <?php if ($phaseId !== 'no_phase' && is_numeric($phaseId)): ?>
                        <button class="btn btn-sm btn-outline-secondary me-2 phase-toggle" 
                                data-phase-id="<?= $phase['phase_id'] ?>"
                                onclick="toggleTemplatePhase(<?= $phase['phase_id'] ?>)">
                            <i class="bi bi-chevron-down" id="phase-icon-<?= $phase['phase_id'] ?>"></i>
                        </button>
                        <?php else: ?>
                        <div class="btn btn-sm btn-outline-secondary me-2" style="opacity: 0.3;">
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <?php endif; ?>
                        <h6 class="mb-0">
                            <strong><?= htmlspecialchars($phase['phase_name']) ?></strong>
                            <span class="badge bg-secondary ms-2"><?= count($phase['tasks']) ?> tasks</span>
                        </h6>
                    </div>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-success" 
                                onclick="setTaskPhase(<?= $phase['phase_id'] === 'no_phase' ? 'null' : $phase['phase_id'] ?>)"
                                data-bs-toggle="modal" data-bs-target="#addTaskModal">
                            <i class="bi bi-plus-lg"></i> Add Task
                        </button>
                        <?php if ($phaseId !== 'no_phase' && is_numeric($phaseId)): ?>
                        <button class="btn btn-sm btn-outline-secondary" 
                                onclick="moveTemplatePhase(<?= $phase['phase_id'] ?>, 'up')"
                                title="Move Up">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" 
                                onclick="moveTemplatePhase(<?= $phase['phase_id'] ?>, 'down')"
                                title="Move Down">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="editTemplatePhase(<?= $phase['phase_id'] ?>)"
                                data-bs-toggle="modal" data-bs-target="#editPhaseModal">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" 
                                onclick="deleteTemplatePhase(<?= $phase['phase_id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                        <?php else: ?>
                        <small class="text-muted ms-2">System Phase</small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="phase-tasks" id="template-phase-tasks-<?= $phaseId ?>">
                    <?php if (empty($phase['tasks'])): ?>
                    <div class="text-center p-4 text-muted">
                        <i class="bi bi-list-task"></i>
                        <p class="mb-0">No tasks in this phase</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive mt-3">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">WBS</th>
                                    <th width="35%">Task</th>
                                    <th width="15%">Type</th>
                                    <th width="10%">Priority</th>
                                    <th width="10%">Hours</th>
                                    <th width="10%">Start Day</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="sortable-tbody" data-phase-id="<?= $phase['phase_id'] === 'no_phase' ? '' : $phase['phase_id'] ?>">
                                <?php 
                                $taskNumber = 1;
                                foreach ($phase['tasks'] as $task): 
                                ?>
                                <tr class="clickable-row task-row" data-task-id="<?= $task['id'] ?>" data-phase-id="<?= $task['phase_id'] ?? '' ?>" style="cursor: pointer;">
                                    <td>
                                        <span class="drag-handle me-1" style="cursor: grab; color: #999;" title="Drag to move">
                                            <i class="bi bi-grip-vertical" style="font-size: 0.7rem;"></i>
                                        </span>
                                        <code><?= $phaseNumber ?>.<?= $taskNumber ?></code>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                                        <?php if ($task['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 80)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($task['task_type']) ?></span>
                                    </td>
                                    <td><?= getPriorityBadge($task['priority']) ?></td>
                                    <td><?= number_format($task['estimated_hours'], 1) ?>h</td>
                                    <td>Day <?= $task['days_after_start'] ?></td>
                                    <td>
                                        <div class="task-actions-toolbar">
                                            <!-- Primary Action Buttons -->
                                            <div class="task-actions-primary">
                                                <button class="task-action-btn task-action-edit" 
                                                        onclick="editTemplateTask(<?= $task['id'] ?>)"
                                                        data-bs-toggle="modal" data-bs-target="#editTaskModal"
                                                        title="Edit Task">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="task-action-btn task-action-view" 
                                                        onclick="deleteTemplateTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title']) ?>')"
                                                        title="Delete Task"
                                                        style="color: #dc3545;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                            
                                            <!-- Movement Controls -->
                                            <div class="task-movement-controls">
                                                <!-- Reorder Buttons -->
                                                <div class="task-reorder-group">
                                                    <button class="task-move-btn task-move-up" 
                                                            onclick="moveTemplateTaskWithinPhase(<?= $task['id'] ?>, 'up')"
                                                            title="Move Up">
                                                        <i class="bi bi-chevron-up"></i>
                                                    </button>
                                                    <button class="task-move-btn task-move-down" 
                                                            onclick="moveTemplateTaskWithinPhase(<?= $task['id'] ?>, 'down')"
                                                            title="Move Down">
                                                        <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Phase Transfer -->
                                                <div class="dropdown">
                                                    <button class="task-action-btn dropdown-toggle" 
                                                            type="button" 
                                                            data-bs-toggle="dropdown" 
                                                            data-bs-boundary="clippingParents"
                                                            data-bs-auto-close="true"
                                                            aria-expanded="false"
                                                            title="Move to Different Phase"
                                                            style="color: #20c997;">
                                                        <i class="bi bi-layers"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <div class="dropdown-header">Move to Phase</div>
                                                        <div class="dropdown-divider"></div>
                                                        <?php if ($task['phase_id']): ?>
                                                        <a class="dropdown-item" href="#" onclick="moveTemplateTaskToPhase(<?= $task['id'] ?>, null)">
                                                            <i class="bi bi-folder-x text-muted"></i> Unassigned Tasks
                                                        </a>
                                                        <?php endif; ?>
                                                        <?php foreach ($templatePhases as $targetPhase): ?>
                                                        <?php if ($targetPhase['id'] != $task['phase_id']): ?>
                                                        <a class="dropdown-item" href="#" onclick="moveTemplateTaskToPhase(<?= $task['id'] ?>, <?= $targetPhase['id'] ?>)">
                                                            <i class="bi bi-folder2 text-primary"></i> <?= htmlspecialchars($targetPhase['name']) ?>
                                                        </a>
                                                        <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                $taskNumber++;
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
            $phaseNumber++;
            endforeach; 
            ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Template Modal -->
<div class="modal fade" id="editTemplateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_template">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Template Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($template['name']) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($template['description']) ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="General" <?= $template['category'] === 'General' ? 'selected' : '' ?>>General</option>
                                <option value="Web Development" <?= $template['category'] === 'Web Development' ? 'selected' : '' ?>>Web Development</option>
                                <option value="Mobile Development" <?= $template['category'] === 'Mobile Development' ? 'selected' : '' ?>>Mobile Development</option>
                                <option value="UI/UX Design" <?= $template['category'] === 'UI/UX Design' ? 'selected' : '' ?>>UI/UX Design</option>
                                <option value="DevOps" <?= $template['category'] === 'DevOps' ? 'selected' : '' ?>>DevOps</option>
                                <option value="Testing" <?= $template['category'] === 'Testing' ? 'selected' : '' ?>>Testing</option>
                                <option value="Maintenance" <?= $template['category'] === 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="default_priority" class="form-label">Default Priority</label>
                            <select class="form-select" id="default_priority" name="default_priority">
                                <option value="low" <?= $template['default_priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= $template['default_priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= $template['default_priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="critical" <?= $template['default_priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estimated_duration_days" class="form-label">Estimated Duration (Days)</label>
                            <input type="number" class="form-control" id="estimated_duration_days" 
                                   name="estimated_duration_days" min="1" 
                                   value="<?= $template['estimated_duration_days'] ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="estimated_hours" 
                                   name="estimated_hours" min="0" step="0.5" 
                                   value="<?= $template['estimated_hours'] ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Update Template
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Template Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_template_task">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="task_title" class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="task_title" name="task_title" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="task_description" class="form-label">Description</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="phase_id" class="form-label">Phase</label>
                            <select class="form-select" id="phase_id" name="phase_id">
                                <option value="">Unassigned Tasks</option>
                                <?php foreach ($templatePhases as $phase): ?>
                                <option value="<?= $phase['id'] ?>"><?= htmlspecialchars($phase['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="task_type" name="task_type">
                                <?php foreach ($taskTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_priority" class="form-label">Priority</label>
                            <select class="form-select" id="task_priority" name="task_priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="task_estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="task_estimated_hours" 
                                   name="task_estimated_hours" min="0" step="0.5" value="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="order_index" class="form-label">Order</label>
                            <input type="number" class="form-control" id="order_index" 
                                   name="order_index" min="0" value="<?= count($templateTasks) + 1 ?>">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="days_after_start" class="form-label">Start Day</label>
                            <input type="number" class="form-control" id="days_after_start" 
                                   name="days_after_start" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Add Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Template Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_template_task">
                    <input type="hidden" name="task_id" id="edit_task_id">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="edit_task_title" class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="edit_task_title" name="task_title" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="edit_task_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_task_description" name="task_description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_phase_id" class="form-label">Phase</label>
                            <select class="form-select" id="edit_phase_id" name="phase_id">
                                <option value="">Unassigned Tasks</option>
                                <?php foreach ($templatePhases as $phase): ?>
                                <option value="<?= $phase['id'] ?>"><?= htmlspecialchars($phase['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="edit_task_type" name="task_type">
                                <?php foreach ($taskTypes as $type): ?>
                                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="edit_task_priority" class="form-label">Priority</label>
                            <select class="form-select" id="edit_task_priority" name="task_priority">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="edit_task_estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="edit_task_estimated_hours" 
                                   name="task_estimated_hours" min="0" step="0.5">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="edit_order_index" class="form-label">Order</label>
                            <input type="number" class="form-control" id="edit_order_index" 
                                   name="order_index" min="0">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="edit_days_after_start" class="form-label">Start Day</label>
                            <input type="number" class="form-control" id="edit_days_after_start" 
                                   name="days_after_start" min="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Update Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Task movement controls styling */
.task-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.task-row:hover .task-actions {
    opacity: 1;
}

.task-actions .btn-group {
    margin-right: 2px;
}

.task-actions .dropdown-menu {
    z-index: 99999 !important;
}

.task-handle:active {
    cursor: grabbing !important;
}

.sortable-tbody {
    min-height: 50px;
}

.drag-over {
    background-color: rgba(0, 123, 255, 0.1);
}

.task-row.dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}

/* WBS nesting style - tasks indented under phases */
.phase-tasks {
    border-left: 2px solid #dee2e6;
    margin-left: 1rem;
    padding-left: 1rem;
    min-height: 60px;
    transition: all 0.2s ease;
}

@media (max-width: 768px) {
    .phase-tasks {
        margin-left: 0.5rem;
        padding-left: 0.5rem;
    }
}

/* Phase section hover effects */
.phase-section {
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.phase-section:hover {
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border-left-color: #0d6efd;
}

/* Task row hover effects */
.table tbody tr {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
}

.table tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.03);
    border-left-color: #0d6efd;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

/* WBS code styling */
code {
    font-size: 0.8rem;
    color: #6f42c1;
    background-color: #f8f9fa;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
}

/* Table cell vertical alignment */
.table-sm td {
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

/* Task Action Button Styles (matching project_view.php) */
.task-actions-toolbar {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.task-actions-primary {
    display: flex;
    align-items: center;
    gap: 2px;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 2px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.task-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    color: #6c757d;
    border-radius: 4px;
    transition: all 0.15s ease;
    font-size: 13px;
    text-decoration: none;
}

.task-action-btn:hover {
    background: #ffffff;
    color: #495057;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.task-action-view:hover {
    color: #0d6efd;
    background: #e7f3ff;
}

.task-action-edit:hover {
    color: #6f42c1;
    background: #f3e5ff;
}

.task-movement-controls {
    display: flex;
    align-items: center;
    gap: 3px;
}

.task-reorder-group {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.task-move-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 16px;
    background: transparent;
    border: none;
    color: #6c757d;
    border-radius: 3px;
    transition: all 0.15s ease;
    font-size: 11px;
    padding: 0;
    margin: 0;
}

.task-move-btn:hover {
    background: #e9ecef;
    color: #495057;
}

.task-move-btn:active {
    background: #dee2e6;
    transform: translateY(0);
}

/* Show toolbar on row hover */
.table tbody tr:hover .task-actions-toolbar {
    opacity: 1;
}
</style>

<script>
// Template task data for edit modal
const templateTasksData = <?= json_encode($templateTasks) ?>;
const templatePhasesData = <?= json_encode($templatePhases) ?>;

function editTemplateTask(taskId) {
    const task = templateTasksData.find(t => t.id == taskId);
    if (!task) return;
    
    // Populate edit modal with task data
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_task_title').value = task.title;
    document.getElementById('edit_task_description').value = task.description || '';
    document.getElementById('edit_phase_id').value = task.phase_id || '';
    document.getElementById('edit_task_type').value = task.task_type;
    document.getElementById('edit_task_priority').value = task.priority;
    document.getElementById('edit_task_estimated_hours').value = task.estimated_hours;
    document.getElementById('edit_order_index').value = task.order_index;
    document.getElementById('edit_days_after_start').value = task.days_after_start;
}

function deleteTemplateTask(taskId, taskTitle) {
    if (confirm('Are you sure you want to delete the task "' + taskTitle + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_template_task">
            <input type="hidden" name="task_id" value="${taskId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function setTaskPhase(phaseId) {
    const phaseSelect = document.getElementById('phase_id');
    if (phaseSelect && phaseId !== null) {
        phaseSelect.value = phaseId;
    }
}

// Template phase toggle function
function toggleTemplatePhase(phaseId) {
    const tasksDiv = document.getElementById(`template-phase-tasks-${phaseId}`);
    const icon = document.getElementById(`phase-icon-${phaseId}`);
    
    if (tasksDiv.classList.contains('d-none')) {
        tasksDiv.classList.remove('d-none');
        icon.className = 'bi bi-chevron-down';
    } else {
        tasksDiv.classList.add('d-none');
        icon.className = 'bi bi-chevron-right';
    }
    
    // You could add AJAX call here to save the collapsed state
    // saveTemplatePhaseState(phaseId, tasksDiv.classList.contains('d-none'));
}

// Template task movement functions
function moveTemplateTaskWithinPhase(taskId, direction) {
    // Create and submit form immediately
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="move_template_task_within_phase">
        <input type="hidden" name="task_id" value="${taskId}">
        <input type="hidden" name="direction" value="${direction}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function moveTemplateTaskToPhase(taskId, newPhaseId) {
    // Create and submit form immediately - let Bootstrap handle dropdown closing
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="move_template_task_to_phase">
        <input type="hidden" name="task_id" value="${taskId}">
        <input type="hidden" name="new_phase_id" value="${newPhaseId || ''}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Drag and drop functionality for template tasks (future enhancement)
let draggedTemplateTaskId = null;
let draggedFromTemplatePhase = null;

function enableTemplateTaskDragDrop() {
    // Add drag and drop to task rows
    document.querySelectorAll('.task-row').forEach(row => {
        row.draggable = true;
        
        row.addEventListener('dragstart', function(e) {
            draggedTemplateTaskId = this.dataset.taskId;
            draggedFromTemplatePhase = this.dataset.phaseId || null;
            this.classList.add('dragging');
            
            // Set drag effect
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.outerHTML);
        });
        
        row.addEventListener('dragend', function(e) {
            this.classList.remove('dragging');
            draggedTemplateTaskId = null;
            draggedFromTemplatePhase = null;
        });
    });
    
    // Handle drop zones (table bodies)
    document.querySelectorAll('.sortable-tbody').forEach(tbody => {
        tbody.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        
        tbody.addEventListener('dragleave', function(e) {
            this.classList.remove('drag-over');
        });
        
        tbody.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            
            if (draggedTemplateTaskId) {
                const targetPhaseId = this.dataset.phaseId || null;
                
                // Only move if dropping on a different phase
                if (targetPhaseId !== draggedFromTemplatePhase) {
                    moveTemplateTaskToPhase(draggedTemplateTaskId, targetPhaseId);
                }
            }
        });
    });
}

// Template phase management functions (placeholders)
function moveTemplatePhase(phaseId, direction) {
    alert('Phase movement functionality coming soon!');
    // TODO: Implement template phase movement
}

function editTemplatePhase(phaseId) {
    alert('Phase editing functionality coming soon!');
    // TODO: Implement template phase editing modal
}

function deleteTemplatePhase(phaseId) {
    if (confirm('Are you sure you want to delete this phase? All tasks in this phase will be moved to unassigned.')) {
        alert('Phase deletion functionality coming soon!');
        // TODO: Implement template phase deletion
    }
}

// Initialize drag and drop when page loads
document.addEventListener('DOMContentLoaded', function() {
    enableTemplateTaskDragDrop();
});
</script>

<?php 
ob_end_flush();
require_once dirname(__FILE__) . '/../includes/footer.php'; 
?>