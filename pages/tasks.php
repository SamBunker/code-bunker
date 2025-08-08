<?php
/**
 * Tasks Management Page
 * Code Bunker
 */

$pageTitle = 'Tasks';
require_once dirname(__FILE__) . '/../includes/header.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Handle form submissions
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'project_id' => intval($_POST['project_id']),
                'title' => sanitizeInput($_POST['title']),
                'description' => sanitizeInput($_POST['description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['priority']),
                'status' => sanitizeInput($_POST['status']),
                'assigned_to' => intval($_POST['assigned_to']) ?: null,
                'depends_on_task_id' => intval($_POST['depends_on_task_id']) ?: null,
                'estimated_hours' => floatval($_POST['estimated_hours']),
                'start_date' => $_POST['start_date'] ?: null,
                'due_date' => $_POST['due_date'] ?: null
            ];
            
            $result = createTask($data);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'update':
            $taskId = intval($_POST['task_id']);
            $data = [
                'project_id' => intval($_POST['project_id']),
                'title' => sanitizeInput($_POST['title']),
                'description' => sanitizeInput($_POST['description']),
                'task_type' => sanitizeInput($_POST['task_type']),
                'priority' => sanitizeInput($_POST['priority']),
                'status' => sanitizeInput($_POST['status']),
                'assigned_to' => intval($_POST['assigned_to']) ?: null,
                'depends_on_task_id' => intval($_POST['depends_on_task_id']) ?: null,
                'estimated_hours' => floatval($_POST['estimated_hours']),
                'actual_hours' => floatval($_POST['actual_hours']),
                'start_date' => $_POST['start_date'] ?: null,
                'due_date' => $_POST['due_date'] ?: null
            ];
            
            $result = updateTask($taskId, $data, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'delete':
            $taskId = intval($_POST['task_id']);
            $result = deleteTask($taskId, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'update_status':
            $taskId = intval($_POST['task_id']);
            $newStatus = sanitizeInput($_POST['status']);
            
            // Get current task
            $task = getTask($taskId);
            if ($task) {
                $data = [
                    'status' => $newStatus,
                    'completed_at' => ($newStatus === 'completed') ? date('Y-m-d H:i:s') : null
                ];
                
                $result = updateTaskStatus($taskId, $data, $currentUser['id']);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
            break;
    }
}

// Get filters from URL parameters
$filters = [
    'project_id' => $_GET['project_id'] ?? '',
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'assigned_to' => $_GET['assigned_to'] ?? '',
    'task_type' => $_GET['task_type'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// If not admin, only show tasks assigned to current user by default
if (!isAdmin() && empty($filters['assigned_to'])) {
    $filters['assigned_to'] = $currentUser['id'];
}

// Get tasks with filters
$tasks = getTasks(null, $filters);

// Get data for dropdowns
$users = getUsers();
$taskTypes = getTaskTypes();
$projects = getProjects(); // Get all projects for dropdown

// Group tasks by project for better organization
$tasksByProject = [];
foreach ($tasks as $task) {
    $projectName = $task['project_name'];
    if (!isset($tasksByProject[$projectName])) {
        $tasksByProject[$projectName] = [];
    }
    $tasksByProject[$projectName][] = $task;
}

// Get current action for modal display
$currentAction = $_GET['action'] ?? 'list';
$editTask = null;
if ($currentAction === 'edit' && isset($_GET['id'])) {
    $editTask = getTask(intval($_GET['id']));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-list-check"></i> Tasks</h1>
        <p class="text-muted">Manage project tasks and track progress</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="openCreateModal()">
        <i class="bi bi-plus-lg"></i> New Task
    </button>
</div>

<!-- Flash message -->
<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : $messageType; ?> alert-dismissible fade show">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search tasks..." 
                           value="<?php echo htmlspecialchars($filters['search']); ?>" data-search>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select" data-filter="project_id">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>" <?php echo $filters['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($project['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" data-filter="status">
                        <option value="">All Status</option>
                        <?php foreach (TASK_STATUS as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $filters['status'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" data-filter="priority">
                        <option value="">All Priorities</option>
                        <?php foreach (PRIORITY_LEVELS as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $filters['priority'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Assigned To</label>
                    <select name="assigned_to" class="form-select" data-filter="assigned_to">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                        <option value="<?php echo $user['id']; ?>" <?php echo $filters['assigned_to'] == $user['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-secondary d-block" onclick="clearFilters()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Task Summary Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h6">Total Tasks</div>
                        <div class="h4"><?php echo count($tasks); ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-list-check fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h6">In Progress</div>
                        <div class="h4"><?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'in_progress')); ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h6">Completed</div>
                        <div class="h4"><?php echo count(array_filter($tasks, fn($t) => $t['status'] === 'completed')); ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="h6">Overdue</div>
                        <div class="h4"><?php echo count(array_filter($tasks, fn($t) => $t['due_date'] && $t['due_date'] < date('Y-m-d') && $t['status'] !== 'completed')); ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks by Project -->
<?php if (empty($tasks)): ?>
<div class="card">
    <div class="card-body text-center p-5">
        <i class="bi bi-list-check fs-1 text-muted"></i>
        <h5 class="text-muted mt-3">No tasks found</h5>
        <p class="text-muted">Create your first task to get started.</p>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="openCreateModal()">
            <i class="bi bi-plus-lg"></i> Create Task
        </button>
    </div>
</div>
<?php else: ?>
<?php foreach ($tasksByProject as $projectName => $projectTasks): ?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-folder me-2"></i>
            <?php echo htmlspecialchars($projectName); ?>
            <span class="badge bg-secondary ms-2"><?php echo count($projectTasks); ?> tasks</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="30%">Task</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Progress</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projectTasks as $task): ?>
                    <tr class="clickable-row" data-href="task_view.php?id=<?php echo $task['id']; ?>" style="cursor: pointer;">
                        <td>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                <?php if ($task['description']): ?>
                                <small class="text-muted text-truncate-2"><?php echo htmlspecialchars(substr($task['description'], 0, 100)) . (strlen($task['description']) > 100 ? '...' : ''); ?></small>
                                <?php endif; ?>
                                <?php if ($task['depends_on_task_id']): ?>
                                <br><small class="text-warning"><i class="bi bi-link-45deg"></i> Has dependencies</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($task['task_type']); ?></span>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <?php echo getStatusBadge($task['status'], 'task'); ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach (TASK_STATUS as $statusKey => $statusLabel): ?>
                                    <li>
                                        <button class="dropdown-item <?php echo $task['status'] === $statusKey ? 'active' : ''; ?>" 
                                                onclick="updateTaskStatus(<?php echo $task['id']; ?>, '<?php echo $statusKey; ?>')">
                                            <?php echo $statusLabel; ?>
                                        </button>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </td>
                        <td><?php echo getPriorityBadge($task['priority']); ?></td>
                        <td>
                            <?php echo $task['assigned_to_name'] ? htmlspecialchars($task['assigned_to_name']) : '<span class="text-muted">Unassigned</span>'; ?>
                        </td>
                        <td>
                            <?php if ($task['due_date']): ?>
                                <?php
                                $daysUntilDue = $task['days_until_due'];
                                $isOverdue = $daysUntilDue < 0 && $task['status'] !== 'completed';
                                $isDueSoon = $daysUntilDue <= 3 && $daysUntilDue >= 0;
                                ?>
                                <div class="<?php echo $isOverdue ? 'text-danger' : ($isDueSoon ? 'text-dark' : ''); ?>">
                                    <?php echo formatDate($task['due_date']); ?>
                                    <?php if ($isOverdue): ?>
                                        <br><small><i class="bi bi-exclamation-triangle"></i> Overdue</small>
                                    <?php elseif ($isDueSoon): ?>
                                        <br><small class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Due soon</small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($task['estimated_hours'] > 0): ?>
                                <?php 
                                $progress = $task['actual_hours'] > 0 ? round(($task['actual_hours'] / $task['estimated_hours']) * 100, 1) : 0;
                                if ($task['status'] === 'completed') $progress = 100;
                                ?>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 60px; height: 6px;">
                                        <div class="progress-bar" style="width: <?php echo min($progress, 100); ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $progress; ?>%</small>
                                </div>
                                <small class="text-muted d-block"><?php echo $task['actual_hours']; ?>h / <?php echo $task['estimated_hours']; ?>h</small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="viewTask(<?php echo $task['id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="editTask(<?php echo $task['id']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if (isAdmin() || $task['assigned_to'] == $currentUser['id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars($task['title']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="taskForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="taskModalTitle">Create Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="task_id" id="taskId">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="project_id" class="form-label">Project *</label>
                            <select class="form-select" id="project_id" name="project_id" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project): ?>
                                <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="title" class="form-label">Task Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="task_type" class="form-label">Task Type</label>
                            <select class="form-select" id="task_type" name="task_type">
                                <?php foreach ($taskTypes as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <?php foreach (PRIORITY_LEVELS as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <?php foreach (TASK_STATUS as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Select User</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="depends_on_task_id" class="form-label">Depends On Task</label>
                            <select class="form-select" id="depends_on_task_id" name="depends_on_task_id">
                                <option value="">No dependencies</option>
                                <!-- Will be populated by JavaScript based on selected project -->
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" min="0" step="0.5">
                        </div>
                        
                        <div class="col-md-4" id="actual_hours_container" style="display: none;">
                            <label for="actual_hours" class="form-label">Actual Hours</label>
                            <input type="number" class="form-control" id="actual_hours" name="actual_hours" min="0" step="0.5">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-lg"></i> Create Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('taskModalTitle').textContent = 'Create Task';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Create Task';
    document.getElementById('taskForm').reset();
    document.getElementById('taskId').value = '';
    document.getElementById('actual_hours_container').style.display = 'none';
}

function editTask(id) {
    window.location.href = '?action=edit&id=' + id;
}

function viewTask(id) {
    window.location.href = 'task_view.php?id=' + id;
}

function deleteTask(id, title) {
    if (confirm('Are you sure you want to delete the task "' + title + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="task_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function updateTaskStatus(id, status) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="task_id" value="${id}">
        <input type="hidden" name="status" value="${status}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function clearFilters() {
    window.location.href = window.location.pathname;
}

// Auto-submit filters on change
document.querySelectorAll('[data-filter]').forEach(element => {
    element.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});

// Search with debounce
let searchTimeout;
document.querySelector('[data-search]')?.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('filterForm').submit();
    }, 500);
});

// Handle clickable task rows
document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', function(e) {
        // Don't trigger if clicking on action buttons, dropdowns, or badges
        if (e.target.closest('.btn-group') || 
            e.target.closest('button') || 
            e.target.closest('a') ||
            e.target.closest('.dropdown') ||
            e.target.classList.contains('badge')) {
            return;
        }
        
        const href = this.dataset.href;
        if (href) {
            window.location.href = href;
        }
    });
});

// Load tasks for dependencies when project is selected
document.getElementById('project_id')?.addEventListener('change', function() {
    const projectId = this.value;
    const dependsSelect = document.getElementById('depends_on_task_id');
    
    // Clear existing options
    dependsSelect.innerHTML = '<option value="">No dependencies</option>';
    
    if (projectId) {
        // In a real app, this would fetch tasks via AJAX
        // For now, we'll keep it simple
    }
});

<?php if ($currentAction === 'create' || $currentAction === 'edit'): ?>
// Auto-open modal for create/edit actions
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($currentAction === 'edit' && $editTask): ?>
    // Populate form for editing
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Task';
    document.getElementById('taskId').value = '<?php echo $editTask['id']; ?>';
    document.getElementById('actual_hours_container').style.display = 'block';
    
    // Populate form fields
    <?php foreach ($editTask as $key => $value): ?>
    <?php if (in_array($key, ['project_id', 'title', 'description', 'task_type', 'priority', 'status', 'assigned_to', 'depends_on_task_id', 'estimated_hours', 'actual_hours', 'start_date', 'due_date'])): ?>
    const field<?php echo ucfirst(str_replace('_', '', $key)); ?> = document.getElementById('<?php echo $key; ?>');
    if (field<?php echo ucfirst(str_replace('_', '', $key)); ?>) {
        field<?php echo ucfirst(str_replace('_', '', $key)); ?>.value = '<?php echo addslashes($value ?? ''); ?>';
    }
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>
    
    new bootstrap.Modal(document.getElementById('taskModal')).show();
});
<?php endif; ?>
</script>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>