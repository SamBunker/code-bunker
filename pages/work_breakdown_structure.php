<?php
/**
 * Work Breakdown Structure Management
 * Create and manage hierarchical project breakdowns
 */

$pageTitle = 'Work Breakdown Structure';
require_once dirname(__FILE__) . '/../includes/header.php';

requireLogin();

$currentUser = getCurrentUser();
$projectId = $_GET['project_id'] ?? null;
$action = $_POST['action'] ?? $_GET['action'] ?? 'view';

// Get project information
$project = null;
if ($projectId) {
    $project = executeQuerySingle("SELECT * FROM projects WHERE id = ?", [$projectId]);
    if (!$project) {
        header('Location: projects.php');
        exit;
    }
}

// Handle WBS actions
$message = '';
$messageType = 'info';

if ($action === 'save_wbs' && $_POST) {
    try {
        $wbsId = $_POST['wbs_id'] ?? null;
        $parentId = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
        $wbsCode = sanitizeInput($_POST['wbs_code']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $workPackageType = $_POST['work_package_type'];
        $estimatedHours = floatval($_POST['estimated_hours'] ?? 0);
        $estimatedCost = floatval($_POST['estimated_cost'] ?? 0);
        $assignedTo = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $priority = $_POST['priority'];
        $status = $_POST['status'];
        
        if ($wbsId) {
            // Update existing WBS item
            $sql = "UPDATE work_breakdown_structure SET 
                    parent_id = ?, wbs_code = ?, name = ?, description = ?, 
                    work_package_type = ?, estimated_hours = ?, estimated_cost = ?,
                    assigned_to = ?, start_date = ?, due_date = ?, priority = ?, status = ?
                    WHERE id = ? AND project_id = ?";
            $params = [$parentId, $wbsCode, $name, $description, $workPackageType, 
                      $estimatedHours, $estimatedCost, $assignedTo, $startDate, $dueDate, 
                      $priority, $status, $wbsId, $projectId];
        } else {
            // Create new WBS item
            $sql = "INSERT INTO work_breakdown_structure 
                    (project_id, parent_id, wbs_code, name, description, work_package_type, 
                     estimated_hours, estimated_cost, assigned_to, start_date, due_date, 
                     priority, status, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [$projectId, $parentId, $wbsCode, $name, $description, $workPackageType,
                      $estimatedHours, $estimatedCost, $assignedTo, $startDate, $dueDate,
                      $priority, $status, $currentUser['id']];
        }
        
        if (executeUpdate($sql, $params)) {
            $message = $wbsId ? 'WBS item updated successfully!' : 'WBS item created successfully!';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to save WBS item');
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Handle delete action
if ($action === 'delete_wbs' && $_GET['wbs_id']) {
    $wbsId = $_GET['wbs_id'];
    try {
        // Check if this WBS item has children
        $childCount = executeQuerySingle("SELECT COUNT(*) as count FROM work_breakdown_structure WHERE parent_id = ?", [$wbsId])['count'];
        
        if ($childCount > 0) {
            throw new Exception('Cannot delete WBS item with child items. Delete children first.');
        }
        
        if (executeUpdate("DELETE FROM work_breakdown_structure WHERE id = ? AND project_id = ?", [$wbsId, $projectId])) {
            $message = 'WBS item deleted successfully!';
            $messageType = 'success';
        } else {
            throw new Exception('Failed to delete WBS item');
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'danger';
    }
}

// Get WBS items for the project
$wbsItems = [];
if ($projectId) {
    $wbsQuery = "
        SELECT w.*, u.first_name, u.last_name,
               (SELECT COUNT(*) FROM work_breakdown_structure w2 WHERE w2.parent_id = w.id) as child_count
        FROM work_breakdown_structure w
        LEFT JOIN users u ON w.assigned_to = u.id
        WHERE w.project_id = ?
        ORDER BY w.wbs_code
    ";
    $wbsItems = executeQuery($wbsQuery, [$projectId]) ?: [];
}

// Get users for assignment
$users = executeQuery("SELECT id, first_name, last_name FROM users WHERE is_active = 1 ORDER BY first_name") ?: [];

// Get all projects for project selection
$projects = executeQuery("SELECT id, name FROM projects ORDER BY name") ?: [];

/**
 * Build hierarchical WBS tree
 */
function buildWBSTree($items, $parentId = null, $level = 0) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $item['level'] = $level;
            $item['children'] = buildWBSTree($items, $item['id'], $level + 1);
            $tree[] = $item;
        }
    }
    return $tree;
}

/**
 * Render WBS tree HTML
 */
function renderWBSTree($tree, $level = 0) {
    $html = '';
    foreach ($tree as $item) {
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $icon = $item['work_package_type'] === 'project' ? 'folder' : 
                ($item['work_package_type'] === 'deliverable' ? 'box-seam' : 
                ($item['work_package_type'] === 'work_package' ? 'diagram-3' : 'check-square'));
        
        $statusClass = $item['status'] === 'completed' ? 'table-success' : 
                      ($item['status'] === 'in_progress' ? 'table-info' : 
                      ($item['status'] === 'on_hold' ? 'table-warning' : ''));
        
        $progressWidth = max(0, min(100, $item['progress_percentage']));
        
        $html .= '<tr class="' . $statusClass . '">';
        $html .= '<td>';
        $html .= $indent . '<i class="bi bi-' . $icon . ' me-2"></i>';
        $html .= '<strong>' . htmlspecialchars($item['wbs_code']) . '</strong>';
        $html .= '</td>';
        $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
        $html .= '<td>' . ucfirst(str_replace('_', ' ', $item['work_package_type'])) . '</td>';
        $html .= '<td>' . getPriorityBadge($item['priority']) . '</td>';
        $html .= '<td>' . getStatusBadge($item['status'], 'task') . '</td>';
        $html .= '<td>';
        if ($item['assigned_to']) {
            $html .= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']);
        } else {
            $html .= '<span class="text-muted">Unassigned</span>';
        }
        $html .= '</td>';
        $html .= '<td>';
        $html .= '<div class="progress" style="height: 8px;">';
        $html .= '<div class="progress-bar" style="width: ' . $progressWidth . '%"></div>';
        $html .= '</div>';
        $html .= '<small class="text-muted">' . $item['progress_percentage'] . '%</small>';
        $html .= '</td>';
        $html .= '<td class="text-end">';
        $html .= number_format($item['estimated_hours'], 1) . 'h';
        $html .= '</td>';
        $html .= '<td class="text-end">';
        $html .= '$' . number_format($item['estimated_cost']);
        $html .= '</td>';
        $html .= '<td>';
        $html .= '<div class="btn-group btn-group-sm">';
        $html .= '<button class="btn btn-outline-primary btn-sm" onclick="editWBS(' . $item['id'] . ')" title="Edit">';
        $html .= '<i class="bi bi-pencil"></i>';
        $html .= '</button>';
        $html .= '<button class="btn btn-outline-success btn-sm" onclick="addChildWBS(' . $item['id'] . ')" title="Add Child">';
        $html .= '<i class="bi bi-plus"></i>';
        $html .= '</button>';
        if ($item['child_count'] == 0) {
            $html .= '<button class="btn btn-outline-danger btn-sm" onclick="deleteWBS(' . $item['id'] . ')" title="Delete">';
            $html .= '<i class="bi bi-trash"></i>';
            $html .= '</button>';
        }
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        
        if (!empty($item['children'])) {
            $html .= renderWBSTree($item['children'], $level + 1);
        }
    }
    return $html;
}

$wbsTree = buildWBSTree($wbsItems);
?>

<style>
.wbs-container {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
}
.wbs-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.wbs-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}
.wbs-stat {
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
}
.wbs-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}
.wbs-stat-label {
    font-size: 0.875rem;
    opacity: 0.9;
}
.indent-1 { padding-left: 2rem; }
.indent-2 { padding-left: 4rem; }
.indent-3 { padding-left: 6rem; }
.indent-4 { padding-left: 8rem; }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2><i class="bi bi-diagram-3"></i> Work Breakdown Structure</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
                        <?php if ($project): ?>
                        <li class="breadcrumb-item"><a href="projects.php?id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['name']); ?></a></li>
                        <li class="breadcrumb-item active">WBS</li>
                        <?php else: ?>
                        <li class="breadcrumb-item active">Select Project</li>
                        <?php endif; ?>
                    </ol>
                </nav>
            </div>
            <?php if ($project): ?>
            <div>
                <button class="btn btn-primary" onclick="addRootWBS()" data-bs-toggle="modal" data-bs-target="#wbsModal">
                    <i class="bi bi-plus-circle"></i> Add WBS Item
                </button>
                <a href="advanced_reports.php?report_type=wbs&project_id=<?php echo $project['id']; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-file-text"></i> Generate Report
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!$project): ?>
<!-- Project Selection -->
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-diagram-3 text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">Select a Project for WBS Management</h4>
                <p class="text-muted">Choose a project to create and manage its work breakdown structure</p>
                
                <div class="row mt-4">
                    <?php foreach (array_slice($projects, 0, 6) as $proj): ?>
                    <div class="col-md-4 mb-3">
                        <a href="?project_id=<?php echo $proj['id']; ?>" class="text-decoration-none">
                            <div class="card h-100 border-2 card-hover">
                                <div class="card-body text-center">
                                    <i class="bi bi-folder text-primary fs-3"></i>
                                    <h6 class="mt-2"><?php echo htmlspecialchars($proj['name']); ?></h6>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($projects) > 6): ?>
                <div class="mt-3">
                    <select class="form-select w-50 mx-auto" onchange="if(this.value) window.location.href='?project_id='+this.value">
                        <option value="">Choose from all projects...</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?php echo $proj['id']; ?>"><?php echo htmlspecialchars($proj['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Project WBS Header -->
<div class="wbs-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h3 class="mb-2"><?php echo htmlspecialchars($project['name']); ?></h3>
            <p class="mb-0 opacity-75"><?php echo htmlspecialchars($project['description']); ?></p>
        </div>
        <div class="col-md-4">
            <div class="wbs-stats">
                <div class="wbs-stat">
                    <div class="wbs-stat-value"><?php echo count($wbsItems); ?></div>
                    <div class="wbs-stat-label">Total Items</div>
                </div>
                <div class="wbs-stat">
                    <div class="wbs-stat-value"><?php echo count(array_filter($wbsItems, fn($item) => $item['status'] === 'completed')); ?></div>
                    <div class="wbs-stat-label">Completed</div>
                </div>
                <div class="wbs-stat">
                    <div class="wbs-stat-value"><?php echo number_format(array_sum(array_column($wbsItems, 'estimated_hours')), 0); ?>h</div>
                    <div class="wbs-stat-label">Est. Hours</div>
                </div>
                <div class="wbs-stat">
                    <div class="wbs-stat-value">$<?php echo number_format(array_sum(array_column($wbsItems, 'estimated_cost')), 0); ?></div>
                    <div class="wbs-stat-label">Est. Cost</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- WBS Table -->
<div class="wbs-container">
    <?php if (empty($wbsItems)): ?>
    <div class="text-center py-5">
        <i class="bi bi-diagram-3 text-muted" style="font-size: 3rem;"></i>
        <h5 class="mt-3 text-muted">No WBS Items Created Yet</h5>
        <p class="text-muted">Start by creating the main work packages for this project</p>
        <button class="btn btn-primary" onclick="addRootWBS()" data-bs-toggle="modal" data-bs-target="#wbsModal">
            <i class="bi bi-plus-circle"></i> Create First WBS Item
        </button>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>WBS Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Assigned To</th>
                    <th>Progress</th>
                    <th>Est. Hours</th>
                    <th>Est. Cost</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php echo renderWBSTree($wbsTree); ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- WBS Modal -->
<div class="modal fade" id="wbsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit WBS Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_wbs">
                    <input type="hidden" name="wbs_id" id="wbs_id">
                    <input type="hidden" name="parent_id" id="parent_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="wbs_code" class="form-label">WBS Code *</label>
                            <input type="text" class="form-control" id="wbs_code" name="wbs_code" required 
                                   placeholder="e.g., 1.1.2" pattern="[0-9]+(\.[0-9]+)*">
                            <div class="form-text">Use hierarchical numbering (e.g., 1.1.1, 1.1.2)</div>
                        </div>
                        <div class="col-md-6">
                            <label for="work_package_type" class="form-label">Type *</label>
                            <select class="form-select" id="work_package_type" name="work_package_type" required>
                                <option value="deliverable">Deliverable</option>
                                <option value="work_package">Work Package</option>
                                <option value="task">Task</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name *</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <label for="estimated_hours" class="form-label">Estimated Hours</label>
                            <input type="number" step="0.5" class="form-control" id="estimated_hours" name="estimated_hours" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="estimated_cost" class="form-label">Estimated Cost</label>
                            <input type="number" step="0.01" class="form-control" id="estimated_cost" name="estimated_cost" min="0">
                        </div>
                        <div class="col-md-4">
                            <label for="assigned_to" class="form-label">Assigned To</label>
                            <select class="form-select" id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="not_started" selected>Not Started</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="on_hold">On Hold</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date">
                        </div>
                        <div class="col-md-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save WBS Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let wbsData = <?php echo json_encode($wbsItems); ?>;

function addRootWBS() {
    resetWBSModal();
    document.getElementById('parent_id').value = '';
    
    // Auto-generate next WBS code at root level
    const rootCodes = wbsData
        .filter(item => !item.parent_id)
        .map(item => parseInt(item.wbs_code.split('.')[0]))
        .sort((a, b) => b - a);
    
    const nextCode = rootCodes.length > 0 ? (rootCodes[0] + 1) : 1;
    document.getElementById('wbs_code').value = nextCode.toString();
}

function addChildWBS(parentId) {
    resetWBSModal();
    document.getElementById('parent_id').value = parentId;
    
    // Find parent and generate child code
    const parent = wbsData.find(item => item.id == parentId);
    if (parent) {
        const parentCode = parent.wbs_code;
        const siblings = wbsData
            .filter(item => item.parent_id == parentId)
            .map(item => {
                const parts = item.wbs_code.split('.');
                return parseInt(parts[parts.length - 1]);
            })
            .sort((a, b) => b - a);
        
        const nextChildNumber = siblings.length > 0 ? (siblings[0] + 1) : 1;
        document.getElementById('wbs_code').value = parentCode + '.' + nextChildNumber;
    }
}

function editWBS(wbsId) {
    const item = wbsData.find(item => item.id == wbsId);
    if (!item) return;
    
    document.getElementById('wbs_id').value = item.id;
    document.getElementById('parent_id').value = item.parent_id || '';
    document.getElementById('wbs_code').value = item.wbs_code;
    document.getElementById('name').value = item.name;
    document.getElementById('description').value = item.description || '';
    document.getElementById('work_package_type').value = item.work_package_type;
    document.getElementById('estimated_hours').value = item.estimated_hours || '';
    document.getElementById('estimated_cost').value = item.estimated_cost || '';
    document.getElementById('assigned_to').value = item.assigned_to || '';
    document.getElementById('priority').value = item.priority;
    document.getElementById('status').value = item.status;
    document.getElementById('start_date').value = item.start_date || '';
    document.getElementById('due_date').value = item.due_date || '';
    
    const modal = new bootstrap.Modal(document.getElementById('wbsModal'));
    modal.show();
}

function deleteWBS(wbsId) {
    const item = wbsData.find(item => item.id == wbsId);
    if (!item) return;
    
    if (confirm(`Are you sure you want to delete "${item.name}"?`)) {
        window.location.href = `?project_id=<?php echo $projectId; ?>&action=delete_wbs&wbs_id=${wbsId}`;
    }
}

function resetWBSModal() {
    document.getElementById('wbs_id').value = '';
    document.getElementById('wbs_code').value = '';
    document.getElementById('name').value = '';
    document.getElementById('description').value = '';
    document.getElementById('work_package_type').value = 'deliverable';
    document.getElementById('estimated_hours').value = '';
    document.getElementById('estimated_cost').value = '';
    document.getElementById('assigned_to').value = '';
    document.getElementById('priority').value = 'medium';
    document.getElementById('status').value = 'not_started';
    document.getElementById('start_date').value = '';
    document.getElementById('due_date').value = '';
}

// Auto-suggest WBS codes based on hierarchy
document.getElementById('wbs_code').addEventListener('blur', function() {
    const code = this.value;
    const parts = code.split('.');
    
    if (parts.length > 1) {
        // This might be a child - try to find parent
        const parentCode = parts.slice(0, -1).join('.');
        const parent = wbsData.find(item => item.wbs_code === parentCode);
        if (parent) {
            document.getElementById('parent_id').value = parent.id;
        }
    }
});
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>