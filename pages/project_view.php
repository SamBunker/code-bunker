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

// Get project phases and tasks
$projectPhases = getProjectPhases($projectId);
$tasksByPhase = getProjectTasksByPhase($projectId);

// Get project notes (both project-level and task-level notes)
$projectNotes = getNotes($projectId); // Project-only notes
if ($projectNotes === false) $projectNotes = [];

// Get all notes for this project including task notes
$query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as user_name,
          u.username, t.title as task_title
          FROM notes n
          JOIN users u ON n.user_id = u.id
          LEFT JOIN tasks t ON n.task_id = t.id
          WHERE n.project_id = ?
          ORDER BY n.created_at DESC";

$notes = executeQuery($query, [$projectId]) ?: [];

// Calculate project statistics from all tasks across phases
$allTasks = [];
foreach ($tasksByPhase as $phase) {
    $allTasks = array_merge($allTasks, $phase['tasks']);
}

$totalTasks = count($allTasks);
$completedTasks = count(array_filter($allTasks, fn($task) => $task['status'] === 'completed'));
$progressPercentage = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

$overdueTasks = count(array_filter($allTasks, function($task) {
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
            
            $result = updateProject($projectId, $data, $currentUser['id']);
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
                // Refresh notes (both project and task notes)
                $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as user_name,
                          u.username, t.title as task_title
                          FROM notes n
                          JOIN users u ON n.user_id = u.id
                          LEFT JOIN tasks t ON n.task_id = t.id
                          WHERE n.project_id = ?
                          ORDER BY n.created_at DESC";
                
                $notes = executeQuery($query, [$projectId]) ?: [];
            }
            break;
            
        case 'delete_note':
            $noteId = intval($_POST['note_id']);
            $result = deleteNote($noteId, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes (both project and task notes)
                $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as user_name,
                          u.username, t.title as task_title
                          FROM notes n
                          JOIN users u ON n.user_id = u.id
                          LEFT JOIN tasks t ON n.task_id = t.id
                          WHERE n.project_id = ?
                          ORDER BY n.created_at DESC";
                
                $notes = executeQuery($query, [$projectId]) ?: [];
            }
            break;
            
        case 'update_note':
            $noteId = intval($_POST['note_id']);
            $noteData = [
                'title' => sanitizeInput($_POST['note_title']),
                'content' => sanitizeInput($_POST['note_content']),
                'note_type' => sanitizeInput($_POST['note_type']),
                'is_private' => isset($_POST['is_private']) ? 1 : 0
            ];
            
            $result = updateNote($noteId, $noteData, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh notes (both project and task notes)
                $query = "SELECT n.*, CONCAT(u.first_name, ' ', u.last_name) as user_name,
                          u.username, t.title as task_title
                          FROM notes n
                          JOIN users u ON n.user_id = u.id
                          LEFT JOIN tasks t ON n.task_id = t.id
                          WHERE n.project_id = ?
                          ORDER BY n.created_at DESC";
                
                $notes = executeQuery($query, [$projectId]) ?: [];
            }
            break;
            
        case 'update_task':
            $taskId = intval($_POST['task_id']);
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
            
            $result = updateTask($taskId, $taskData, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh phase and task data
                $tasksByPhase = getProjectTasksByPhase($projectId);
                $allTasks = [];
                foreach ($tasksByPhase as $phase) {
                    $allTasks = array_merge($allTasks, $phase['tasks']);
                }
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
                'phase_id' => intval($_POST['task_phase_id']) ?: null,
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
                // Refresh phase and task data
                $tasksByPhase = getProjectTasksByPhase($projectId);
                $allTasks = [];
                foreach ($tasksByPhase as $phase) {
                    $allTasks = array_merge($allTasks, $phase['tasks']);
                }
            }
            break;
            
        case 'add_phase':
            $phaseData = [
                'project_id' => $projectId,
                'name' => sanitizeInput($_POST['phase_name']),
                'description' => sanitizeInput($_POST['phase_description']),
                'order_index' => intval($_POST['order_index'])
            ];
            
            $result = createProjectPhase($phaseData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh phase data
                $projectPhases = getProjectPhases($projectId);
                $tasksByPhase = getProjectTasksByPhase($projectId);
            }
            break;
            
        case 'toggle_phase':
            $phaseId = intval($_POST['phase_id']);
            $result = togglePhaseCollapse($phaseId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh phase data
                $projectPhases = getProjectPhases($projectId);
                $tasksByPhase = getProjectTasksByPhase($projectId);
            }
            break;
            
        case 'update_phase':
            $phaseId = intval($_POST['phase_id']);
            $phaseData = [
                'name' => sanitizeInput($_POST['phase_name']),
                'description' => sanitizeInput($_POST['phase_description']),
                'order_index' => intval($_POST['order_index'])
            ];
            
            $result = updateProjectPhase($phaseId, $phaseData);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh phase data
                $projectPhases = getProjectPhases($projectId);
                $tasksByPhase = getProjectTasksByPhase($projectId);
            }
            break;
            
        case 'delete_phase':
            $phaseId = intval($_POST['phase_id']);
            error_log("DELETE PHASE: Attempting to delete phase ID: " . $phaseId);
            $result = deleteProjectPhase($phaseId);
            error_log("DELETE PHASE: Result - " . ($result['success'] ? 'SUCCESS' : 'FAILED') . ": " . $result['message']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh phase data
                $projectPhases = getProjectPhases($projectId);
                $tasksByPhase = getProjectTasksByPhase($projectId);
            }
            break;
            
        case 'move_phase':
            $phaseId = intval($_POST['phase_id']);
            $direction = $_POST['direction'];
            $result = moveProjectPhase($phaseId, $direction);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                // Refresh phase data
                $projectPhases = getProjectPhases($projectId);
                $tasksByPhase = getProjectTasksByPhase($projectId);
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
                            foreach ($allTasks as $task) {
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

            <!-- Work Breakdown Structure Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Work Breakdown Structure (<?= $totalTasks ?>)</h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addPhaseModal">
                        <i class="bi bi-plus-lg"></i> Add Phase
                    </button>
                </div>
                <div class="card-body">
                    <?php if (empty($tasksByPhase)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-diagram-3 fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No phases or tasks found for this project</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPhaseModal">
                            <i class="bi bi-plus-lg"></i> Create First Phase
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="wbs-container">
                        <?php 
                        $phaseNumber = 1;
                        foreach ($tasksByPhase as $phaseId => $phase): 
                        ?>
                        <div class="phase-section mb-4">
                            <div class="phase-header d-flex justify-content-between align-items-center p-3 bg-light rounded border">
                                <div class="d-flex align-items-center">
                                    <?php if ($phaseId !== 'unassigned' && is_numeric($phaseId)): ?>
                                    <button class="btn btn-sm btn-outline-secondary me-2 phase-toggle" 
                                            data-phase-id="<?= $phase['id'] ?>"
                                            onclick="togglePhase(<?= $phase['id'] ?>)">
                                        <i class="bi <?= $phase['is_collapsed'] ? 'bi-chevron-right' : 'bi-chevron-down' ?>"></i>
                                    </button>
                                    <?php else: ?>
                                    <div class="btn btn-sm btn-outline-secondary me-2" style="opacity: 0.3;">
                                        <i class="bi bi-chevron-down"></i>
                                    </div>
                                    <?php endif; ?>
                                    <h6 class="mb-0">
                                        <strong><?= $phaseNumber ?>. <?= htmlspecialchars($phase['name']) ?></strong>
                                        <span class="badge bg-secondary ms-2"><?= count($phase['tasks']) ?> tasks</span>
                                    </h6>
                                </div>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-success" 
                                            onclick="addTaskToPhase(<?= $phase['id'] ?? 'null' ?>)"
                                            data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                        <i class="bi bi-plus-lg"></i> Add Task
                                    </button>
                                    <?php if ($phaseId !== 'unassigned' && is_numeric($phaseId)): ?>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick="movePhase(<?= $phase['id'] ?>, 'up')"
                                            title="Move Up">
                                        <i class="bi bi-arrow-up"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" 
                                            onclick="movePhase(<?= $phase['id'] ?>, 'down')"
                                            title="Move Down">
                                        <i class="bi bi-arrow-down"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="editPhase(<?= $phase['id'] ?>, '<?= htmlspecialchars($phase['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($phase['description'] ?? '', ENT_QUOTES) ?>', <?= $phase['order_index'] ?? 1 ?>)"
                                            data-bs-toggle="modal" data-bs-target="#editPhaseModal">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" 
                                            onclick="deletePhase(<?= $phase['id'] ?>, '<?= htmlspecialchars($phase['name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <small class="text-muted ms-2">System Phase</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="phase-tasks <?= $phase['is_collapsed'] ? 'd-none' : '' ?>" id="phase-tasks-<?= $phaseId ?>">
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
                                                <th width="15%">Status</th>
                                                <th width="10%">Priority</th>
                                                <th width="15%">Assigned</th>
                                                <th width="10%">Due Date</th>
                                                <th width="10%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $taskNumber = 1;
                                            foreach ($phase['tasks'] as $task): 
                                            ?>
                                            <tr class="clickable-row" data-href="task_view.php?id=<?= $task['id'] ?>" style="cursor: pointer;">
                                                <td><code><?= $phaseNumber ?>.<?= $taskNumber ?></code></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                                                    <?php if ($task['description']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($task['description'], 0, 80)) ?>...</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button" 
                                                                data-bs-toggle="dropdown" 
                                                                data-bs-container="body"
                                                                onclick="event.stopPropagation();">
                                                            <?= getStatusBadge($task['status'], 'task') ?>
                                                        </button>
                                                        <ul class="dropdown-menu">
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
                                                <td>
                                                    <?php if ($task['assigned_to']): ?>
                                                        <small><?= htmlspecialchars($task['assigned_to_name']) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">Unassigned</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                        <small><?= formatDate($task['due_date']) ?></small>
                                                        <?php if ($task['due_date'] < date('Y-m-d') && $task['status'] !== 'completed'): ?>
                                                            <i class="bi bi-exclamation-triangle text-danger" title="Overdue"></i>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <small class="text-muted">Not set</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="task_view.php?id=<?= $task['id'] ?>" class="btn btn-xs btn-outline-primary" onclick="event.stopPropagation();">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <button class="btn btn-xs btn-outline-secondary" 
                                                                onclick="event.stopPropagation(); editTask(<?= $task['id'] ?>)"
                                                                data-bs-toggle="modal" data-bs-target="#editTaskModal">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
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
                    <div class="note-item mb-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="note-header flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h6 class="note-title mb-0"><?= htmlspecialchars($note['title'] ?: 'Untitled Note') ?></h6>
                                    <?php if ($note['is_private']): ?>
                                    <i class="bi bi-lock-fill text-muted" style="font-size: 0.8rem;" title="Private Note"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="note-meta text-muted small">
                                    <span class="note-type"><?= htmlspecialchars($note['note_type']) ?></span>
                                    <?php if ($note['task_title']): ?>
                                    • <span class="task-ref">Task: <?= htmlspecialchars($note['task_title']) ?></span>
                                    <?php endif; ?>
                                    • <span class="note-author"><?= htmlspecialchars($note['user_name']) ?></span>
                                    • <span class="note-date"><?= formatDateTime($note['created_at']) ?></span>
                                </div>
                            </div>
                            <?php if (isAdmin() || $note['user_id'] == $currentUser['id']): ?>
                            <div class="note-actions">
                                <button type="button" class="btn btn-link btn-sm text-muted p-1" 
                                        onclick="editNote(<?= $note['id'] ?>)"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editNoteModal"
                                        title="Edit Note">
                                    <i class="bi bi-pencil" style="font-size: 0.9rem;"></i>
                                </button>
                                <button type="button" class="btn btn-link btn-sm text-muted p-1" 
                                        onclick="deleteNote(<?= $note['id'] ?>, '<?= htmlspecialchars($note['title'] ?: 'this note') ?>')"
                                        title="Delete Note">
                                    <i class="bi bi-trash" style="font-size: 0.9rem;"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="note-content">
                            <?= nl2br(htmlspecialchars($note['content'])) ?>
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
                            <label for="task_phase_id" class="form-label">Phase</label>
                            <select class="form-select" id="task_phase_id" name="task_phase_id">
                                <option value="">Unassigned</option>
                                <?php foreach ($projectPhases as $phase): ?>
                                <option value="<?= $phase['id'] ?>">
                                    <?= htmlspecialchars($phase['name']) ?>
                                </option>
                                <?php endforeach; ?>
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

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="update_note">
                <input type="hidden" name="note_id" id="edit_note_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_note_title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="edit_note_title" name="note_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_note_content" class="form-label">Content *</label>
                        <textarea class="form-control" id="edit_note_content" name="note_content" rows="5" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_note_type" class="form-label">Type</label>
                            <select class="form-select" id="edit_note_type" name="note_type">
                                <option value="general">General</option>
                                <option value="technical">Technical</option>
                                <option value="meeting">Meeting</option>
                                <option value="decision">Decision</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3 d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_is_private" name="is_private">
                                <label class="form-check-label" for="edit_is_private">
                                    Private Note
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Clean Notes Design - 2024 Minimal UI */
.note-item {
    background: #fafafa;
    border-radius: 8px;
    padding: 1.25rem;
    border: 1px solid #f0f0f0;
    transition: all 0.15s ease;
    position: relative;
}

.note-item:hover {
    background: #f8f9fa;
    border-color: #e9ecef;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}

.note-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 0.95rem;
    letter-spacing: -0.01em;
}

.note-meta {
    color: #6c757d;
    font-size: 0.8rem;
    line-height: 1.4;
}

.note-meta .task-ref {
    color: #0d6efd;
    font-weight: 500;
}

.note-content {
    color: #495057;
    line-height: 1.6;
    font-size: 0.9rem;
    margin-top: 0.75rem;
    white-space: pre-line;
    word-wrap: break-word;
}

.note-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.note-item:hover .note-actions {
    opacity: 1;
}

.note-actions .btn-link {
    border: none;
    text-decoration: none;
    margin-left: 0.25rem;
}

.note-actions .btn-link:hover {
    color: #0d6efd !important;
    background: rgba(13, 110, 253, 0.1);
    border-radius: 4px;
}

.note-actions .btn-link:hover .bi-trash {
    color: #dc3545 !important;
}

/* Private note styling */
.note-item .bi-lock-fill {
    opacity: 0.6;
}
</style>

<script>
// Task data for edit modal
const tasksData = <?= json_encode($allTasks) ?>;

// Notes data for edit modal
const notesData = <?= json_encode($notes) ?>;

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

function editNote(noteId) {
    const note = notesData.find(n => n.id == noteId);
    if (!note) return;
    
    // Populate edit modal with note data
    document.getElementById('edit_note_id').value = note.id;
    document.getElementById('edit_note_title').value = note.title || '';
    document.getElementById('edit_note_content').value = note.content || '';
    document.getElementById('edit_note_type').value = note.note_type || 'general';
    document.getElementById('edit_is_private').checked = note.is_private == 1;
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
    // Handle clickable rows
    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on action buttons or dropdowns
            if (e.target.closest('.btn-group') || e.target.closest('button') || e.target.closest('a') || e.target.closest('.dropdown')) {
                return;
            }
            
            const href = this.dataset.href;
            if (href) {
                window.location.href = href;
            }
        });
    });
    
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

// Phase management functions
function togglePhase(phaseId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="toggle_phase">
        <input type="hidden" name="phase_id" value="${phaseId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function editPhase(phaseId, phaseName, phaseDescription, phaseOrder) {
    console.log('editPhase called with:', phaseId, phaseName, phaseDescription, phaseOrder);
    document.getElementById('edit_phase_id').value = phaseId;
    document.getElementById('edit_phase_name').value = phaseName;
    document.getElementById('edit_phase_description').value = phaseDescription || '';
    document.getElementById('edit_order_index').value = phaseOrder || 1;
}

function deletePhase(phaseId, phaseName) {
    // Set the phase data in the delete modal
    document.getElementById('delete_phase_id').value = phaseId;
    document.getElementById('delete_phase_name').textContent = phaseName;
    
    // Show the delete confirmation modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deletePhaseModal'));
    deleteModal.show();
}

function confirmDeletePhase() {
    const phaseId = document.getElementById('delete_phase_id').value;
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="delete_phase">
        <input type="hidden" name="phase_id" value="${phaseId}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function addTaskToPhase(phaseId) {
    // Set the selected phase in the task modal
    const phaseSelect = document.getElementById('task_phase_id');
    if (phaseSelect) {
        phaseSelect.value = phaseId || '';
    }
}

function movePhase(phaseId, direction) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="move_phase">
        <input type="hidden" name="phase_id" value="${phaseId}">
        <input type="hidden" name="direction" value="${direction}">
    `;
    document.body.appendChild(form);
    form.submit();
}

// Clear modal when closed
document.addEventListener('DOMContentLoaded', function() {
    const editPhaseModal = document.getElementById('editPhaseModal');
    if (editPhaseModal) {
        editPhaseModal.addEventListener('hidden.bs.modal', function () {
            // Clear all fields when modal is closed
            document.getElementById('edit_phase_id').value = '';
            document.getElementById('edit_phase_name').value = '';
            document.getElementById('edit_phase_description').value = '';
            document.getElementById('edit_order_index').value = '';
        });
    }
    
    const addPhaseModal = document.getElementById('addPhaseModal');
    if (addPhaseModal) {
        addPhaseModal.addEventListener('hidden.bs.modal', function () {
            // Clear all fields when modal is closed
            document.getElementById('phase_name').value = '';
            document.getElementById('phase_description').value = '';
            document.getElementById('order_index').value = '<?= count($projectPhases) + 1 ?>';
        });
    }
    
    const addTaskModal = document.getElementById('addTaskModal');
    if (addTaskModal) {
        addTaskModal.addEventListener('hidden.bs.modal', function () {
            // Clear all fields when modal is closed
            document.getElementById('task_title').value = '';
            document.getElementById('task_description').value = '';
            document.getElementById('task_type').value = 'General';
            document.getElementById('task_phase_id').value = '';
            document.getElementById('task_assigned_to').value = '';
            document.getElementById('task_priority').value = 'medium';
            document.getElementById('task_status').value = 'pending';
            document.getElementById('task_start_date').value = '';
            document.getElementById('task_due_date').value = '';
        });
    }
});
</script>

<style>
.wbs-container {
    margin: 0;
}

.phase-section {
    border-left: 3px solid #007bff;
    margin-bottom: 1.5rem;
    padding-left: 0;
}

.phase-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: none;
}

.phase-tasks {
    border-left: 2px solid #dee2e6;
    margin-left: 1rem;
    padding-left: 1rem;
}

.btn-xs {
    padding: 0.125rem 0.25rem;
    font-size: 0.75rem;
    line-height: 1.25;
    border-radius: 0.25rem;
}

.phase-toggle {
    transition: transform 0.2s ease;
}

.phase-toggle:hover {
    transform: scale(1.1);
}

code {
    font-size: 0.8rem;
    color: #6f42c1;
    background-color: #f8f9fa;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
}

.table-sm td {
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

@media (max-width: 768px) {
    .phase-tasks {
        margin-left: 0.5rem;
        padding-left: 0.5rem;
    }
    
    .btn-group .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
}

/* Shallow red button with hover effect */
.btn-shallow-red {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
    transition: all 0.15s ease-in-out;
}

.btn-shallow-red:hover {
    background-color: #f1b0b7;
    border-color: #ea868f;
    color: #491217;
}
</style>

<!-- Add Phase Modal -->
<div class="modal fade" id="addPhaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Phase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_phase">
                    
                    <div class="mb-3">
                        <label for="phase_name" class="form-label">Phase Name *</label>
                        <input type="text" class="form-control" id="phase_name" name="phase_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phase_description" class="form-label">Description</label>
                        <textarea class="form-control" id="phase_description" name="phase_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_index" class="form-label">Order</label>
                        <input type="number" class="form-control" id="order_index" name="order_index" value="<?= count($projectPhases) + 1 ?>" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Add Phase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Phase Modal -->
<div class="modal fade" id="editPhaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Phase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_phase">
                    <input type="hidden" name="phase_id" id="edit_phase_id">
                    
                    <div class="mb-3">
                        <label for="edit_phase_name" class="form-label">Phase Name *</label>
                        <input type="text" class="form-control" id="edit_phase_name" name="phase_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_phase_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_phase_description" name="phase_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_order_index" class="form-label">Order</label>
                        <input type="number" class="form-control" id="edit_order_index" name="order_index" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Update Phase
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Phase Confirmation Modal -->
<div class="modal fade" id="deletePhaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bi bi-question-circle"></i> Delete Phase
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="delete_phase_id">
                
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle"></i>
                    <strong>Note:</strong> This action cannot be undone.
                </div>
                
                <p>Are you sure you want to delete the phase <strong id="delete_phase_name"></strong>?</p>
                
                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2"><i class="bi bi-info-circle"></i> What will happen:</h6>
                    <ul class="mb-0 small">
                        <li>The phase will be permanently deleted</li>
                        <li>Any tasks in this phase will become unassigned</li>
                        <li>Task data will be preserved but moved to "Unassigned Tasks"</li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Cancel
                </button>
                <button type="button" class="btn btn-shallow-red" onclick="confirmDeletePhase()">
                    <i class="bi bi-trash"></i> Delete Phase
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>