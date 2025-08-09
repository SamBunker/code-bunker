<?php
/**
 * Project Detail View Page
 * Code Bunker
 */

$pageTitle = 'Project Details';
require_once dirname(__FILE__) . '/../includes/header.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Get project ID from URL
$projectId = intval($_GET['id'] ?? 0);

if (!$projectId) {
    header('Location: projects.php');
    exit;
}

// Get project details
$project = getProject($projectId);
if (!$project) {
    header('Location: projects.php?error=Project not found');
    exit;
}

// Check permissions (basic check - admins can view all, users can view assigned projects)
if (!isAdmin() && $project['created_by'] != $currentUser['id'] && $project['assigned_to'] != $currentUser['id']) {
    header('Location: projects.php?error=Access denied');
    exit;
}

// Get project tasks
$tasks = getTasks($projectId);
if ($tasks === false) $tasks = [];

// Get project notes
$notes = getNotes($projectId);
if ($notes === false) $notes = [];

// Calculate project statistics
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, fn($task) => $task['status'] === 'completed'));
$progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$overdueTasks = count(array_filter($tasks, function($task) {
    return $task['due_date'] && $task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed';
}));

// Handle form submissions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_project':
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'description' => sanitizeInput($_POST['description']),
                'category' => sanitizeInput($_POST['category']),
                'priority' => sanitizeInput($_POST['priority']),
                'status' => sanitizeInput($_POST['status']),
                'start_date' => $_POST['start_date'] ?: null,
                'due_date' => $_POST['due_date'] ?: null,
                'estimated_hours' => floatval($_POST['estimated_hours']),
                'budget' => isFeatureEnabled('budget_tracking') ? (floatval($_POST['budget']) ?: null) : null,
                'assigned_to' => intval($_POST['assigned_to']) ?: null
            ];
            
            $result = updateProject($projectId, $data);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh project data
                $project = getProject($projectId);
            }
            break;
            
        case 'add_note':
            $noteData = [
                'project_id' => $projectId,
                'user_id' => $currentUser['id'],
                'title' => sanitizeInput($_POST['note_title']),
                'content' => sanitizeInput($_POST['note_content']),
                'note_type' => sanitizeInput($_POST['note_type']),
                'is_private' => isset($_POST['is_private']) ? 1 : 0
            ];
            
            $result = createNote($noteData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes
                $notes = getNotes($projectId);
            }
            break;
            
        case 'delete_note':
            $noteId = intval($_POST['note_id']);
            $result = deleteNote($noteId, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes
                $notes = getNotes($projectId);
            }
            break;
            
        case 'update_task':
            $taskId = intval($_POST['task_id']);
            $taskData = [
                'title' => sanitizeInput($_POST['task_title']),
                'description' => sanitizeInput($_POST['task_description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['task_priority']),
                'status' => sanitizeInput($_POST['task_status']),
                'assigned_to' => intval($_POST['task_assigned_to']) ?: null,
                'start_date' => $_POST['task_start_date'] ?: null,
                'due_date' => $_POST['task_due_date'] ?: null,
                'estimated_hours' => floatval($_POST['task_estimated_hours'])
            ];
            
            $result = updateTask($taskId, $taskData, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh tasks
                $tasks = getTasks($projectId);
                if ($tasks === false) $tasks = [];
            }
            break;
            
        case 'quick_status_update':
            $newStatus = sanitizeInput($_POST['status']);
            $result = updateProject($projectId, ['status' => $newStatus], $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh project data
                $project = getProject($projectId);
            }
            break;
            
        case 'update_task_status':
            $taskId = intval($_POST['task_id']);
            $newStatus = sanitizeInput($_POST['status']);
            
            // Get current task
            $task = getTask($taskId);
            if ($task) {
                $data = [
                    'status' => $newStatus,
                    'completed_at' => ($newStatus === 'completed') ? date('Y-m-d H:i:s') : null
                ];
                
                // Handle actual hours based on status change
                if ($newStatus === 'completed' && (floatval($task['actual_hours']) == 0)) {
                    // If marking as completed and actual hours is 0, set it to estimated hours
                    $data['actual_hours'] = floatval($task['estimated_hours']);
                } elseif ($task['status'] === 'completed' && $newStatus !== 'completed') {
                    // If changing from completed to any other status, reset actual hours to 0
                    $data['actual_hours'] = 0;
                }
                
                $result = updateTaskStatus($taskId, $data, $currentUser['id']);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                
                if ($result['success']) {
                    // Refresh tasks
                    $tasks = getTasks($projectId);
                    if ($tasks === false) $tasks = [];
                }
            }
            break;
            
        case 'create_task':
            $taskData = [
                'project_id' => $projectId,
                'title' => sanitizeInput($_POST['task_title']),
                'description' => sanitizeInput($_POST['task_description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['task_priority']),
                'status' => sanitizeInput($_POST['task_status']),
                'assigned_to' => intval($_POST['task_assigned_to']) ?: null,
                'start_date' => $_POST['task_start_date'] ?: null,
                'due_date' => $_POST['task_due_date'] ?: null,
                'estimated_hours' => floatval($_POST['task_estimated_hours'])
            ];
            
            $result = createTask($taskData, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh tasks
                $tasks = getTasks($projectId);
                if ($tasks === false) $tasks = [];
            }
            break;
    }
}

// Get all users for assignment dropdown
$users = getUsers();
if ($users === false) $users = [];

?>

<div class="container-fluid mt-4">
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType === 'error' ? 'danger' : $messageType ?> alert-dismissible fade show">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Project Header -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($project['name']) ?></li>
                </ol>
            </nav>
            <h1><?= htmlspecialchars($project['name']) ?></h1>
            <p class="text-muted"><?= htmlspecialchars($project['category']) ?></p>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProjectModal">
                <i class="bi bi-pencil"></i> Edit Project
            </button>
            <a href="projects.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Projects
            </a>
        </div>
    </div>

    <!-- Project Overview Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="display-6 mb-2"><?= $totalTasks ?></div>
                    <div class="text-muted">Total Tasks</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="display-6 mb-2 text-success"><?= $completedTasks ?></div>
                    <div class="text-muted">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="display-6 mb-2 text-danger"><?= $overdueTasks ?></div>
                    <div class="text-muted">Overdue</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <div class="display-6 mb-2"><?= $progressPercentage ?>%</div>
                    <div class="text-muted">Progress</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Details -->
    <div class="row">
        
        <!-- Left Column: Project Info -->
        <div class="col-md-8">
            
            <!-- Project Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Project Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <div class="dropdown">
                                <span class="badge-clickable" data-bs-toggle="dropdown" aria-expanded="false" 
                                      data-bs-boundary="window" data-bs-reference="parent"
                                      style="cursor: pointer;" title="Click to change status">
                                    <?= getStatusBadge($project['status'], 'project') ?>
                                </span>
                                <ul class="dropdown-menu" style="z-index: 9999; position: fixed;">
                                    <li class="dropdown-header">Change Status</li>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach (['planning', 'in_progress', 'testing', 'completed', 'on_hold'] as $status): ?>
                                    <?php if ($status !== $project['status']): ?>
                                    <li>
                                        <a class="dropdown-item" href="#" 
                                           onclick="updateProjectStatus('<?= $status ?>')">
                                            <?= getStatusBadge($status, 'project') ?>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div><br>
                            
                            <strong>Priority:</strong><br>
                            <?= getPriorityBadge($project['priority']) ?><br><br>
                            
                            <strong>Created By:</strong><br>
                            <?= htmlspecialchars($project['created_by_name'] ?? 'Unknown') ?><br><br>
                            
                            <strong>Assigned To:</strong><br>
                            <?= $project['assigned_to'] ? htmlspecialchars($project['assigned_to_name']) : 'Unassigned' ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Start Date:</strong><br>
                            <?= $project['start_date'] ? formatDate($project['start_date']) : 'Not set' ?><br><br>
                            
                            <strong>Due Date:</strong><br>
                            <?= $project['due_date'] ? formatDate($project['due_date']) : 'Not set' ?><br><br>
                            
                            <strong>Estimated Hours:</strong><br>
                            <?= $project['estimated_hours'] ?: '0' ?> hours<br><br>
                            
                            <strong>Actual Hours:</strong><br>
                            <?php
                            // Calculate total actual hours from tasks
                            $actualHours = 0;
                            foreach ($tasks as $task) {
                                $actualHours += floatval($task['actual_hours'] ?? 0);
                            }
                            $estimatedHours = floatval($project['estimated_hours'] ?? 0);
                            $hoursRemaining = max(0, $estimatedHours - $actualHours);
                            $isOverBudget = $actualHours > $estimatedHours && $estimatedHours > 0;
                            ?>
                            <div class="d-flex align-items-center gap-2">
                                <span class="<?= $isOverBudget ? 'text-danger fw-bold' : '' ?>">
                                    <?= number_format($actualHours, 1) ?> hours
                                </span>
                                <?php if ($isOverBudget): ?>
                                    <small class="badge bg-danger">Over Budget</small>
                                <?php endif; ?>
                            </div>
                            <?php if ($estimatedHours > 0): ?>
                            <div class="small text-muted mt-1">
                                <?= number_format($hoursRemaining, 1) ?> hours remaining
                                <?php if (!$isOverBudget): ?>
                                    (<?= $actualHours > 0 ? round(($actualHours / $estimatedHours) * 100, 1) : 0 ?>% used)
                                <?php endif; ?>
                            </div>
                            <!-- Hours Progress Bar -->
                            <div class="progress mt-2" style="height: 8px;">
                                <?php 
                                $hoursPercentage = $actualHours > 0 ? min(100, round(($actualHours / $estimatedHours) * 100, 1)) : 0;
                                $progressClass = $isOverBudget ? 'bg-danger' : ($hoursPercentage > 80 ? 'bg-warning' : 'bg-primary');
                                ?>
                                <div class="progress-bar <?= $progressClass ?>" style="width: <?= $hoursPercentage ?>%"></div>
                            </div>
                            <?php endif; ?><br>
                            
                            <?php if (isFeatureEnabled('budget_tracking')): ?>
                            <strong>Budget:</strong><br>
                            <?= $project['budget'] ? '$' . number_format($project['budget'], 2) : 'Not set' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($project['description']): ?>
                    <hr>
                    <strong>Description:</strong><br>
                    <div class="mt-2"><?= nl2br(htmlspecialchars($project['description'])) ?></div>
                    <?php endif; ?>
                    
                    <hr>
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar" role="progressbar" style="width: <?= $progressPercentage ?>%"
                             aria-valuenow="<?= $progressPercentage ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $progressPercentage ?>%
                        </div>
                    </div>
                    <small class="text-muted">Overall Progress</small>
                </div>
            </div>

            <!-- Tasks Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-list-task"></i> Tasks (<?= $totalTasks ?>)</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                        <i class="bi bi-plus-lg"></i> Add Task
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-list-task fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No tasks found for this project</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                            <i class="bi bi-plus-lg"></i> Create First Task
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Priority</th>
                                    <th>Assigned To</th>
                                    <th>Due Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($task['title']) ?></strong>
                                        <?php if ($task['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button" 
                                                    data-bs-toggle="dropdown" 
                                                    data-bs-container="body"
                                                    data-bs-boundary="clippingParents"
                                                    data-bs-placement="bottom-start">
                                                <?= getStatusBadge($task['status'], 'task') ?>
                                            </button>
                                            <ul class="dropdown-menu" style="z-index: 9999;">
                                                <?php foreach (['pending', 'in_progress', 'testing', 'completed', 'blocked'] as $statusKey): ?>
                                                <li>
                                                    <button class="dropdown-item <?= $task['status'] === $statusKey ? 'active' : '' ?>" 
                                                            onclick="updateTaskStatus(<?= $task['id'] ?>, '<?= $statusKey ?>')">
                                                        <?= getStatusBadge($statusKey, 'task') ?>
                                                    </button>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </td>
                                    <td><?= getPriorityBadge($task['priority']) ?></td>
                                    <td><?= $task['assigned_to'] ? htmlspecialchars($task['assigned_to_name']) : 'Unassigned' ?></td>
                                    <td>
                                        <?php if ($task['due_date']): ?>
                                            <?= formatDate($task['due_date']) ?>
                                            <?php if ($task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed'): ?>
                                                <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="editTask(<?= $task['id'] ?>)"
                                                data-bs-toggle="modal" data-bs-target="#editTaskModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        
        <!-- Right Column: Notes and Timeline -->
        <div class="col-md-4">
            
            <!-- Project Notes -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-sticky"></i> Notes</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                        <i class="bi bi-plus-lg"></i> Add Note
                    </button>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($notes)): ?>
                    <div class="text-center py-3">
                        <i class="bi bi-sticky fs-3 text-muted"></i>
                        <p class="text-muted mt-2 small">No notes yet</p>
                    </div>
                    <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <strong><?= htmlspecialchars($note['title']) ?></strong>
                                <?php if ($note['is_private']): ?>
                                <i class="bi bi-lock text-warning" title="Private Note"></i>
                                <?php endif; ?>
                            </div>
                            <?php if (isAdmin() || $note['user_id'] == $currentUser['id']): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteNote(<?= $note['id'] ?>, '<?= htmlspecialchars($note['title']) ?>')"
                                    title="Delete Note">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="small mb-2"><?= nl2br(htmlspecialchars($note['content'])) ?></div>
                        <div class="small text-muted">
                            by <?= htmlspecialchars($note['user_name']) ?> • 
                            <?= formatDateTime($note['created_at']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="update_project">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">Project Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($project['name']) ?>" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category"
                                   value="<?= htmlspecialchars($project['category']) ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $project['assigned_to'] == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low" <?= $project['priority'] === 'low' ? 'selected' : '' ?>>Low</option>
                                <option value="medium" <?= $project['priority'] === 'medium' ? 'selected' : '' ?>>Medium</option>
                                <option value="high" <?= $project['priority'] === 'high' ? 'selected' : '' ?>>High</option>
                                <option value="critical" <?= $project['priority'] === 'critical' ? 'selected' : '' ?>>Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="planning" <?= $project['status'] === 'planning' ? 'selected' : '' ?>>Planning</option>
                                <option value="in_progress" <?= $project['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="testing" <?= $project['status'] === 'testing' ? 'selected' : '' ?>>Testing</option>
                                <option value="completed" <?= $project['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="on_hold" <?= $project['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                   value="<?= $project['start_date'] ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date"
                                   value="<?= $project['due_date'] ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="estimated_hours" name="estimated_hours"
                                   step="0.5" min="0" value="<?= $project['estimated_hours'] ?>">
                        </div>
                        
                        <?php if (isFeatureEnabled('budget_tracking')): ?>
                        <div class="col-md-6 mb-3">
                            <label for="budget" class="form-label">Budget</label>
                            <input type="number" class="form-control" id="budget" name="budget"
                                   step="0.01" min="0" value="<?= $project['budget'] ?>">
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($project['description']) ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add_note">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="note_title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="note_title" name="note_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="note_content" class="form-label">Content *</label>
                        <textarea class="form-control" id="note_content" name="note_content" rows="5" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="note_type" class="form-label">Type</label>
                            <select class="form-select" id="note_type" name="note_type">
                                <option value="general">General</option>
                                <option value="technical">Technical</option>
                                <option value="meeting">Meeting</option>
                                <option value="decision">Decision</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_private" name="is_private">
                                <label class="form-check-label" for="is_private">
                                    Private Note
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="create_task">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="task_title" class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="task_title" name="task_title" required>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="task_description" class="form-label">Description</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="task_type" name="task_type">
                                <option value="General">General</option>
                                <option value="Security Updates">Security Updates</option>
                                <option value="Version Upgrades">Version Upgrades</option>
                                <option value="UI/UX Improvements">UI/UX Improvements</option>
                                <option value="Performance Optimization">Performance Optimization</option>
                                <option value="Documentation Updates">Documentation Updates</option>
                                <option value="Testing">Testing</option>
                                <option value="Deployment">Deployment</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="task_assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="task_assigned_to" name="task_assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="task_priority" class="form-label">Priority</label>
                            <select class="form-select" id="task_priority" name="task_priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="task_status" class="form-label">Status</label>
                            <select class="form-select" id="task_status" name="task_status">
                                <option value="pending" selected>Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="testing">Testing</option>
                                <option value="completed">Completed</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="task_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="task_start_date" name="task_start_date">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="task_due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="task_due_date" name="task_due_date">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="task_estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="task_estimated_hours" name="task_estimated_hours"
                                   step="0.5" min="0" value="0">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="update_task">
                <input type="hidden" name="task_id" id="edit_task_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="edit_task_title" class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="edit_task_title" name="task_title" required>
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="edit_task_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_task_description" name="task_description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="edit_task_type" name="task_type">
                                <option value="General">General</option>
                                <option value="Security Updates">Security Updates</option>
                                <option value="Version Upgrades">Version Upgrades</option>
                                <option value="UI/UX Improvements">UI/UX Improvements</option>
                                <option value="Performance Optimization">Performance Optimization</option>
                                <option value="Documentation Updates">Documentation Updates</option>
                                <option value="Testing">Testing</option>
                                <option value="Deployment">Deployment</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_task_assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="edit_task_assigned_to" name="task_assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_task_priority" class="form-label">Priority</label>
                            <select class="form-select" id="edit_task_priority" name="task_priority">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_task_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_task_status" name="task_status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="testing">Testing</option>
                                <option value="completed">Completed</option>
                                <option value="blocked">Blocked</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_task_start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="edit_task_start_date" name="task_start_date">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="edit_task_due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="edit_task_due_date" name="task_due_date">
                        </div>
                        
                        <div class="col-md-12 mb-3">
                            <label for="edit_task_estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="edit_task_estimated_hours" name="task_estimated_hours"
                                   step="0.5" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Task data for edit modal
const tasksData = <?= json_encode($tasks) ?>;

function editTask(taskId) {
    const task = tasksData.find(t => t.id == taskId);
    if (!task) return;
    
    // Populate edit modal with task data
    document.getElementById('edit_task_id').value = task.id;
    document.getElementById('edit_task_title').value = task.title;
    document.getElementById('edit_task_description').value = task.description || '';
    document.getElementById('edit_task_type').value = task.task_type;
    document.getElementById('edit_task_assigned_to').value = task.assigned_to || '';
    document.getElementById('edit_task_priority').value = task.priority;
    document.getElementById('edit_task_status').value = task.status;
    document.getElementById('edit_task_start_date').value = task.start_date || '';
    document.getElementById('edit_task_due_date').value = task.due_date || '';
    document.getElementById('edit_task_estimated_hours').value = task.estimated_hours || 0;
}

function deleteNote(noteId, noteTitle) {
    if (confirm('Are you sure you want to delete the note "' + noteTitle + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_note">
            <input type="hidden" name="note_id" value="${noteId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function updateProjectStatus(newStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="quick_status_update">
        <input type="hidden" name="status" value="${newStatus}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function updateTaskStatus(taskId, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_task_status">
        <input type="hidden" name="task_id" value="${taskId}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Fix dropdown positioning on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all dropdowns with body container
    document.querySelectorAll('.dropdown-toggle').forEach(function(toggle) {
        toggle.addEventListener('show.bs.dropdown', function() {
            const dropdown = this.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                // Move dropdown to body to avoid container clipping
                document.body.appendChild(dropdown);
                
                // Position it relative to the toggle button
                const rect = this.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 2) + 'px';
                dropdown.style.left = rect.left + 'px';
                dropdown.style.zIndex = '9999';
            }
        });
        
        toggle.addEventListener('hide.bs.dropdown', function() {
            const dropdown = document.body.querySelector('.dropdown-menu[style*="position: fixed"]');
            if (dropdown) {
                // Move dropdown back to its original position
                this.parentNode.appendChild(dropdown);
                dropdown.style.position = '';
                dropdown.style.top = '';
                dropdown.style.left = '';
            }
        });
    });
});
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>