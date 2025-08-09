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

// Get template tasks
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
                // Refresh template tasks
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
                'days_after_start' => intval($_POST['days_after_start'])
            ];
            
            $result = updateTemplateTask($taskId, $taskData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh template tasks
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
                // Refresh template tasks
                $templateTasks = getTemplateTasks($templateId);
                if ($templateTasks === false) $templateTasks = [];
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

<!-- Template Tasks -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-task"></i> Template Tasks (<?= count($templateTasks) ?>)</h5>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
            <i class="bi bi-plus-lg"></i> Add Task
        </button>
    </div>
    <div class="card-body p-0">
        <?php if (empty($templateTasks)): ?>
        <div class="text-center p-5">
            <i class="bi bi-list-task fs-1 text-muted"></i>
            <h5 class="text-muted mt-3">No template tasks yet</h5>
            <p class="text-muted">Add tasks to define what will be created when this template is applied.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                <i class="bi bi-plus-lg"></i> Add First Task
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order</th>
                        <th>Task</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Hours</th>
                        <th>Start Day</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($templateTasks as $task): ?>
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark"><?= $task['order_index'] ?></span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($task['title']) ?></strong>
                            <?php if ($task['description']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($task['task_type']) ?></span>
                        </td>
                        <td><?= getPriorityBadge($task['priority']) ?></td>
                        <td><?= number_format($task['estimated_hours'], 1) ?>h</td>
                        <td>Day <?= $task['days_after_start'] ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editTemplateTask(<?= $task['id'] ?>)"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editTaskModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteTemplateTask(<?= $task['id'] ?>, '<?= htmlspecialchars($task['title']) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

<script>
// Template task data for edit modal
const templateTasksData = <?= json_encode($templateTasks) ?>;

function editTemplateTask(taskId) {
    const task = templateTasksData.find(t => t.id == taskId);
    if (!task) return;
    
    // Populate edit modal with task data
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_task_title').value = task.title;
    document.getElementById('edit_task_description').value = task.description || '';
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
</script>

<?php 
ob_end_flush();
require_once dirname(__FILE__) . '/../includes/footer.php'; 
?>