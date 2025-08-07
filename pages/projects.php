<?php
/**
 * Projects Management Page
 * Web Application Modernization Tracker
 */

$pageTitle = 'Projects';
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
                'name' => sanitizeInput($_POST['name']),
                'description' => sanitizeInput($_POST['description']),
                'category' => sanitizeInput($_POST['category']),
                'priority' => sanitizeInput($_POST['priority']),
                'status' => sanitizeInput($_POST['status']),
                'current_version' => sanitizeInput($_POST['current_version']),
                'target_version' => sanitizeInput($_POST['target_version']),
                'start_date' => $_POST['start_date'] ?: null,
                'due_date' => $_POST['due_date'] ?: null,
                'estimated_hours' => floatval($_POST['estimated_hours']),
                'budget' => isFeatureEnabled('budget_tracking') ? (floatval($_POST['budget']) ?: null) : null,
                'assigned_to' => intval($_POST['assigned_to']) ?: null,
                'created_by' => $currentUser['id']
            ];
            
            $result = createProject($data);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'update':
            $projectId = intval($_POST['project_id']);
            $data = [
                'name' => sanitizeInput($_POST['name']),
                'description' => sanitizeInput($_POST['description']),
                'category' => sanitizeInput($_POST['category']),
                'priority' => sanitizeInput($_POST['priority']),
                'status' => sanitizeInput($_POST['status']),
                'current_version' => sanitizeInput($_POST['current_version']),
                'target_version' => sanitizeInput($_POST['target_version']),
                'start_date' => $_POST['start_date'] ?: null,
                'due_date' => $_POST['due_date'] ?: null,
                'estimated_hours' => floatval($_POST['estimated_hours']),
                'budget' => isFeatureEnabled('budget_tracking') ? (floatval($_POST['budget']) ?: null) : null,
                'assigned_to' => intval($_POST['assigned_to']) ?: null
            ];
            
            $result = updateProject($projectId, $data, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
            
        case 'delete':
            $projectId = intval($_POST['project_id']);
            $result = deleteProject($projectId, $currentUser['id']);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            break;
    }
}

// Get filters from URL parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'category' => $_GET['category'] ?? '',
    'assigned_to' => $_GET['assigned_to'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Pagination
$page = intval($_GET['page'] ?? 1);
$limit = DEFAULT_PAGE_SIZE;
$offset = ($page - 1) * $limit;

// Get projects with filters
$projects = getProjects($filters, $limit, $offset);

// Get total count for pagination
$totalProjects = count(getProjects($filters)); // Simple approach for demo
$totalPages = ceil($totalProjects / $limit);

// Get data for dropdowns
$users = getUsers();
$categories = getProjectCategories();

// Get current action for modal display
$currentAction = $_GET['action'] ?? 'list';
$editProject = null;
if ($currentAction === 'edit' && isset($_GET['id'])) {
    $editProject = getProject(intval($_GET['id']));
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1><i class="bi bi-folder"></i> Projects</h1>
        <p class="text-muted">Manage web application modernization projects</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="openCreateModal()">
        <i class="bi bi-plus-lg"></i> New Project
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
                    <input type="text" name="search" class="form-control" placeholder="Search projects..." 
                           value="<?php echo htmlspecialchars($filters['search']); ?>" data-search>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" data-filter="status">
                        <option value="">All Status</option>
                        <?php foreach (PROJECT_STATUS as $key => $label): ?>
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
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" data-filter="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category; ?>" <?php echo $filters['category'] === $category ? 'selected' : ''; ?>>
                            <?php echo $category; ?>
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

<!-- Projects Table -->
<div class="card">
    <div class="card-body p-0">
        <?php if (empty($projects)): ?>
        <div class="text-center p-5">
            <i class="bi bi-folder-x fs-1 text-muted"></i>
            <h5 class="text-muted mt-3">No projects found</h5>
            <p class="text-muted">Create your first project to get started.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#projectModal" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Create Project
            </button>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Project</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Progress</th>
                        <th>Assigned To</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                    <tr>
                        <td>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($project['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($project['category']); ?></small>
                            </div>
                        </td>
                        <td><?php echo getStatusBadge($project['status'], 'project'); ?></td>
                        <td><?php echo getPriorityBadge($project['priority']); ?></td>
                        <td>
                            <?php 
                            $progress = 0;
                            if ($project['total_tasks'] > 0) {
                                $progress = round(($project['completed_tasks'] / $project['total_tasks']) * 100);
                            }
                            ?>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 80px; height: 8px;">
                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $progress; ?>%</small>
                            </div>
                        </td>
                        <td>
                            <?php echo $project['assigned_to_name'] ?: '<span class="text-muted">Unassigned</span>'; ?>
                        </td>
                        <td>
                            <?php if ($project['due_date']): ?>
                                <?php echo formatDate($project['due_date']); ?>
                                <?php if ($project['due_date'] < date('Y-m-d') && $project['status'] !== 'completed'): ?>
                                    <i class="bi bi-exclamation-triangle text-danger ms-1" title="Overdue"></i>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="viewProject(<?php echo $project['id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                        onclick="editProject(<?php echo $project['id']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if (isAdmin() || $project['created_by'] == $currentUser['id']): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars($project['name']); ?>')">
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
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <?php 
        $baseUrl = '?' . http_build_query(array_merge($filters, ['page' => '']));
        echo generatePagination($page, $totalPages, $baseUrl);
        ?>
    </div>
    <?php endif; ?>
</div>

<!-- Project Modal -->
<div class="modal fade" id="projectModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="projectForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="projectModalTitle">Create Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="project_id" id="projectId">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Project Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
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
                                <?php foreach (PROJECT_STATUS as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="current_version" class="form-label">Current Version</label>
                            <input type="text" class="form-control" id="current_version" name="current_version" placeholder="e.g., PHP 5.6">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="target_version" class="form-label">Target Version</label>
                            <input type="text" class="form-control" id="target_version" name="target_version" placeholder="e.g., PHP 8.2">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" min="0" step="0.5">
                        </div>
                        
                        <?php if (isFeatureEnabled('budget_tracking')): ?>
                        <div class="col-md-6">
                            <label for="budget" class="form-label">Budget ($)</label>
                            <input type="number" class="form-control" id="budget" name="budget" min="0" step="0.01">
                        </div>
                        <?php endif; ?>
                        
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-lg"></i> Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('projectModalTitle').textContent = 'Create Project';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Create Project';
    document.getElementById('projectForm').reset();
    document.getElementById('projectId').value = '';
}

function editProject(id) {
    // In a real app, this would fetch data via AJAX
    // For now, redirect to edit page
    window.location.href = '?action=edit&id=' + id;
}

function viewProject(id) {
    window.location.href = 'project_view.php?id=' + id;
}

function deleteProject(id, name) {
    if (confirm('Are you sure you want to delete the project "' + name + '"? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="project_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
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

<?php if ($currentAction === 'create' || $currentAction === 'edit'): ?>
// Auto-open modal for create/edit actions
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($currentAction === 'edit' && $editProject): ?>
    // Populate form for editing
    document.getElementById('projectModalTitle').textContent = 'Edit Project';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-lg"></i> Update Project';
    document.getElementById('projectId').value = '<?php echo $editProject['id']; ?>';
    
    // Populate form fields
    <?php foreach ($editProject as $key => $value): ?>
    <?php if (in_array($key, ['name', 'description', 'category', 'priority', 'status', 'current_version', 'target_version', 'start_date', 'due_date', 'estimated_hours', 'budget', 'assigned_to'])): ?>
    const field<?php echo ucfirst($key); ?> = document.getElementById('<?php echo $key; ?>');
    if (field<?php echo ucfirst($key); ?>) {
        field<?php echo ucfirst($key); ?>.value = '<?php echo addslashes($value ?? ''); ?>';
    }
    <?php endif; ?>
    <?php endforeach; ?>
    <?php endif; ?>
    
    new bootstrap.Modal(document.getElementById('projectModal')).show();
});
<?php endif; ?>
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>