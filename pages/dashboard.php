<?php
/**
 * Dashboard Page
 * Web Application Modernization Tracker
 */

$pageTitle = 'Dashboard';
require_once dirname(__FILE__) . '/../includes/header.php';

// Require login
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Check database connection
$database = getDatabase();
if ($database === null) {
    echo '<div class="container-fluid mt-4">';
    echo '<div class="alert alert-danger" role="alert">';
    echo '<h4 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Database Connection Error</h4>';
    echo '<p>Unable to connect to the database. Please check the following:</p>';
    echo '<ol>';
    echo '<li>Make sure XAMPP is running (both Apache and MySQL)</li>';
    echo '<li>Verify the database "web_app_tracker" exists</li>';
    echo '<li>Check database configuration in config/database.php</li>';
    echo '</ol>';
    echo '<hr>';
    echo '<p class="mb-0">For troubleshooting, you can run the <a href="test_db.php" class="alert-link">database connection test</a>.</p>';
    echo '</div>';
    echo '</div>';
    require_once dirname(__FILE__) . '/../includes/footer.php';
    exit;
}

// Get dashboard statistics
$stats = getDashboardStats(isAdmin() ? null : $currentUser['id']);
$recentProjects = getProjects([], 5); // Get 5 most recent projects
$myTasks = getTasks(null, ['assigned_to' => $currentUser['id']]); // Get tasks assigned to current user

// Handle cases where data retrieval failed
if ($stats === false) {
    $stats = [
        'projects' => ['total_projects' => 0, 'active_projects' => 0, 'completed_projects' => 0, 'on_hold_projects' => 0],
        'tasks' => ['total_tasks' => 0, 'completed_tasks' => 0, 'overdue_tasks' => 0],
        'users' => ['total_users' => 0]
    ];
}
if ($recentProjects === false) $recentProjects = [];
if ($myTasks === false) $myTasks = [];
?>

<div class="dashboard-container">
<!-- Dashboard Controls -->
<div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white shadow-sm" style="position: relative; z-index: 100;">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <div>
        <div class="btn-group me-2" role="group" id="panelManagementButtons" style="display: none;">
            <button type="button" class="btn btn-outline-success btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-plus-lg"></i> Add Panel
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="addPanel('weather')">
                    <i class="bi bi-cloud-sun"></i> Weather Widget
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="addPanel('notes')">
                    <i class="bi bi-sticky"></i> Notes Panel
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="addPanel('calendar-mini')">
                    <i class="bi bi-calendar3"></i> Mini Calendar
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="addPanel('activity')">
                    <i class="bi bi-activity"></i> Activity Feed
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="addPanel('shortcuts')">
                    <i class="bi bi-lightning"></i> Quick Actions
                </a></li>
            </ul>
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="resetLayout()">
            <i class="bi bi-arrow-clockwise"></i> Reset Layout
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleEditMode()">
            <i class="bi bi-layout-wtf"></i> <span id="editModeText">Edit Layout</span>
        </button>
    </div>
</div>

<div class="alert alert-info mx-3" id="editModeAlert" style="display: none; position: relative; z-index: 99;">
    <i class="bi bi-info-circle"></i> <strong>Edit Mode Active:</strong> Drag panels anywhere on the grid. They will snap to grid positions automatically. Your changes are saved.
</div>

<div class="dashboard-grid" id="dashboard-grid">
<!-- Grid Snap Indicator -->
<div class="grid-snap-indicator" id="snapIndicator"></div>
    <!-- Statistics Cards -->
    <div class="dashboard-panel" data-panel="total-projects" data-panel-type="stat-card" data-grid-x="0" data-grid-y="3" data-grid-width="7" data-grid-height="5">
        <div class="card dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                <i class="bi bi-grip-vertical text-white"></i>
                <div class="panel-controls">
                    <button class="btn btn-sm text-white" onclick="togglePanelSize(this)" title="Toggle Size">
                        <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                    </button>
                    <button class="btn btn-sm text-white" onclick="removePanel(this)" title="Remove Panel">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="card-title h6">Total Projects</div>
                        <div class="display-4"><?php echo $stats['projects']['total_projects'] ?? 0; ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-folder fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-panel" data-panel="completed-projects" data-panel-type="stat-card" data-grid-x="8" data-grid-y="3" data-grid-width="7" data-grid-height="5">
        <div class="card dashboard-card success">
            <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                <i class="bi bi-grip-vertical text-white"></i>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="card-title h6">Completed Projects</div>
                        <div class="display-4"><?php echo $stats['projects']['completed_projects'] ?? 0; ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-check-circle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-panel" data-panel="active-tasks" data-panel-type="stat-card" data-grid-x="16" data-grid-y="3" data-grid-width="7" data-grid-height="5">
        <div class="card dashboard-card warning">
            <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                <i class="bi bi-grip-vertical text-white"></i>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="card-title h6">Active Tasks</div>
                        <div class="display-4"><?php echo $stats['tasks']['active_tasks'] ?? 0; ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-list-task fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="dashboard-panel" data-panel="overdue-tasks" data-panel-type="stat-card" data-grid-x="24" data-grid-y="3" data-grid-width="7" data-grid-height="5">
        <div class="card dashboard-card danger">
            <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                <i class="bi bi-grip-vertical text-white"></i>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="card-title h6">Overdue Tasks</div>
                        <div class="display-4"><?php echo $stats['tasks']['overdue_tasks'] ?? 0; ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Status Chart -->
    <div class="dashboard-panel" data-panel="project-status-chart" data-panel-type="chart" data-grid-x="0" data-grid-y="6" data-grid-width="15" data-grid-height="10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart"></i> Project Status Overview
                </h5>
                <div class="d-flex align-items-center">
                    <div class="btn-group me-2" role="group" id="chartTypeButtons-project-status" style="display: none;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeChartType('projectStatusChart', 'doughnut')" title="Pie Chart">
                            <i class="bi bi-pie-chart"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeChartType('projectStatusChart', 'bar')" title="Bar Chart">
                            <i class="bi bi-bar-chart"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="changeChartType('projectStatusChart', 'line')" title="Line Chart">
                            <i class="bi bi-graph-up"></i>
                        </button>
                    </div>
                    <span class="drag-handle" style="cursor: grab;">
                        <i class="bi bi-grip-vertical text-muted"></i>
                        <button class="btn btn-sm text-muted ms-2" onclick="togglePanelSize(this)" title="Toggle Size">
                            <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                        </button>
                        <button class="btn btn-sm text-muted" onclick="removePanel(this)" title="Remove Panel">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="projectStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task Priority Chart -->
    <div class="dashboard-panel" data-panel="task-priority-chart" data-panel-type="chart" data-grid-x="16" data-grid-y="6" data-grid-width="15" data-grid-height="10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bar-chart"></i> Task Priority Distribution
                </h5>
                <div class="d-flex align-items-center">
                    <span class="drag-handle" style="cursor: grab;">
                        <i class="bi bi-grip-vertical text-muted"></i>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="taskPriorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Recent Projects -->
    <div class="dashboard-panel" data-panel="recent-projects" data-panel-type="content" data-grid-x="0" data-grid-y="10" data-grid-width="20" data-grid-height="8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                <i class="bi bi-grip-vertical"></i>
                <div class="panel-controls">
                    <button class="btn btn-sm" onclick="togglePanelSize(this)" title="Toggle Size">
                        <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                    </button>
                    <button class="btn btn-sm" onclick="removePanel(this)" title="Remove Panel">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-clock-history"></i> Recent Projects
                    </h5>
                    <a href="<?php echo BASE_URL; ?>/pages/projects.php" class="btn btn-sm btn-outline-primary">
                        View All <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                <?php if (empty($recentProjects)): ?>
                <div class="text-center p-4">
                    <i class="bi bi-folder-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No projects found</p>
                    <a href="<?php echo BASE_URL; ?>/pages/projects.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create First Project
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Due Date</th>
                                <th>Progress</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProjects as $project): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($project['category']); ?></small>
                                </td>
                                <td><?php echo getStatusBadge($project['status'], 'project'); ?></td>
                                <td><?php echo getPriorityBadge($project['priority']); ?></td>
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
                                <td style="width: 120px;">
                                    <?php $progress = ($project['total_tasks'] > 0) ? round(($project['completed_tasks'] / $project['total_tasks']) * 100) : 0; ?>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" 
                                             aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $progress; ?>%</small>
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
    
    <!-- My Tasks -->
    <div class="dashboard-panel" data-panel="my-tasks" data-panel-type="content" data-grid-x="21" data-grid-y="10" data-grid-width="12" data-grid-height="8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                <i class="bi bi-grip-vertical"></i>
                <div class="panel-controls">
                    <button class="btn btn-sm" onclick="togglePanelSize(this)" title="Toggle Size">
                        <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                    </button>
                    <button class="btn btn-sm" onclick="removePanel(this)" title="Remove Panel">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-check"></i> My Tasks
                    </h5>
                    <a href="<?php echo BASE_URL; ?>/pages/tasks.php" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($myTasks)): ?>
                <div class="text-center p-4">
                    <i class="bi bi-list-check fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No tasks assigned to you</p>
                </div>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php 
                    $displayTasks = array_slice($myTasks, 0, 8); // Show max 8 tasks
                    foreach ($displayTasks as $task): 
                    ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($task['title']); ?></h6>
                                <p class="mb-1 text-muted small"><?php echo htmlspecialchars($task['project_name']); ?></p>
                                <small>
                                    <?php echo getStatusBadge($task['status'], 'task'); ?>
                                    <?php echo getPriorityBadge($task['priority']); ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <?php if ($task['due_date']): ?>
                                    <small class="text-muted d-block">
                                        <?php 
                                        $daysUntilDue = $task['days_until_due'];
                                        if ($daysUntilDue < 0) {
                                            echo '<span class="text-danger">Overdue</span>';
                                        } elseif ($daysUntilDue == 0) {
                                            echo '<span class="badge bg-warning text-dark">Due today</span>';
                                        } elseif ($daysUntilDue <= 3) {
                                            echo '<span class="badge bg-warning text-dark">' . $daysUntilDue . ' days</span>';
                                        } else {
                                            echo $daysUntilDue . ' days';
                                        }
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<!-- Upcoming Deadlines -->
<?php if (!empty($stats['upcoming_deadlines'])): ?>
<div class="row">
    <div class="col-12 mb-4">
        <div class="card border-start-warning">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-event text-warning"></i> Upcoming Deadlines (Next 7 Days)
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($stats['upcoming_deadlines'] as $deadline): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo htmlspecialchars($deadline['name']); ?></h6>
                                <p class="card-text">
                                    <small class="text-muted">Due: <?php echo formatDate($deadline['due_date']); ?></small><br>
                                    <?php echo getStatusBadge($deadline['status'], 'project'); ?>
                                    <?php echo getPriorityBadge($deadline['priority']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activity -->
<?php if (!empty($stats['recent_activity'])): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-activity"></i> Recent Activity
                </h5>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($stats['recent_activity'] as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php
                        $icon = 'bi-circle';
                        switch ($activity['action']) {
                            case 'create': $icon = 'bi-plus-lg'; break;
                            case 'update': $icon = 'bi-pencil'; break;
                            case 'delete': $icon = 'bi-trash'; break;
                            case 'login': $icon = 'bi-box-arrow-in-right'; break;
                            case 'logout': $icon = 'bi-box-arrow-left'; break;
                        }
                        ?>
                        <i class="bi <?php echo $icon; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            <?php echo htmlspecialchars($activity['user_name']); ?>
                            <?php echo ucfirst($activity['action']) . 'd'; ?>
                            <?php echo $activity['entity_type']; ?>
                        </div>
                        <?php if ($activity['description']): ?>
                        <div class="activity-description">
                            <?php echo htmlspecialchars($activity['description']); ?>
                        </div>
                        <?php endif; ?>
                        <div class="activity-meta">
                            <?php echo formatDateTime($activity['created_at']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div> <!-- End dashboard-container -->

<!-- Include SortableJS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
let editMode = false;
let isDragging = false;
let dragPanel = null;
let dragOffset = { x: 0, y: 0 };
const GRID_SIZE = 40;
const DASHBOARD_HEADER_HEIGHT = 0; // Height to reserve for navbar + dashboard controls + edit alert (in pixels)
const MIN_GRID_Y = Math.ceil(DASHBOARD_HEADER_HEIGHT / GRID_SIZE); // Minimum Y position for panels

// Collision detection and bounds checking
function checkCollision(x, y, width, height, excludePanel = null) {
    const panels = document.querySelectorAll('.dashboard-panel');
    
    for (let panel of panels) {
        if (panel === excludePanel) continue;
        
        const panelX = parseInt(panel.getAttribute('data-grid-x')) || 0;
        const panelY = parseInt(panel.getAttribute('data-grid-y')) || 0;
        const panelWidth = parseInt(panel.getAttribute('data-grid-width')) || 8;
        const panelHeight = parseInt(panel.getAttribute('data-grid-height')) || 6;
        
        // Check if rectangles overlap
        if (x < panelX + panelWidth && 
            x + width > panelX && 
            y < panelY + panelHeight && 
            y + height > panelY) {
            return true;
        }
    }
    return false;
}

function checkBounds(x, y, width, height) {
    // Check if position is within bounds
    const minY = MIN_GRID_Y; // Don't allow panels above dashboard controls
    const maxX = Math.max(50, x + width); // Ensure some space on right
    const maxY = Math.max(30, y + height); // Ensure some space on bottom
    
    return x >= 0 && y >= minY && x + width <= maxX && y + height <= maxY;
}

function findValidPosition(width, height, preferredX = 0, preferredY = MIN_GRID_Y) {
    // Try preferred position first
    if (!checkCollision(preferredX, preferredY, width, height) && 
        checkBounds(preferredX, preferredY, width, height)) {
        return { x: preferredX, y: preferredY };
    }
    
    // Search for available position
    for (let y = MIN_GRID_Y; y < 50; y++) {
        for (let x = 0; x < 50; x++) {
            if (!checkCollision(x, y, width, height) && 
                checkBounds(x, y, width, height)) {
                return { x, y };
            }
        }
    }
    
    // Fallback: stack vertically at the right edge
    let stackY = MIN_GRID_Y;
    const stackX = 40; // Place at right edge
    
    const panels = document.querySelectorAll('.dashboard-panel');
    panels.forEach(panel => {
        const panelX = parseInt(panel.getAttribute('data-grid-x')) || 0;
        const panelY = parseInt(panel.getAttribute('data-grid-y')) || 0;
        const panelHeight = parseInt(panel.getAttribute('data-grid-height')) || 6;
        
        if (panelX >= stackX) {
            stackY = Math.max(stackY, panelY + panelHeight);
        }
    });
    
    return { x: stackX, y: stackY };
}

// Dashboard layout functions
function toggleEditMode() {
    editMode = !editMode;
    const editModeText = document.getElementById('editModeText');
    const editModeAlert = document.getElementById('editModeAlert');
    const panelButtons = document.getElementById('panelManagementButtons');
    const chartButtons = document.querySelectorAll('[id^="chartTypeButtons-"]');
    
    if (editMode) {
        editModeText.textContent = 'Exit Edit Mode';
        editModeAlert.style.display = 'block';
        panelButtons.style.display = 'block';
        chartButtons.forEach(btn => btn.style.display = 'block');
        initializeGridDragging();
        document.body.classList.add('dashboard-edit-mode');
    } else {
        editModeText.textContent = 'Edit Layout';
        editModeAlert.style.display = 'none';
        panelButtons.style.display = 'none';
        chartButtons.forEach(btn => btn.style.display = 'none');
        destroyGridDragging();
        document.body.classList.remove('dashboard-edit-mode');
    }
}

// Grid-based dragging system
function initializeGridDragging() {
    const panels = document.querySelectorAll('.dashboard-panel');
    
    panels.forEach(panel => {
        // Make panels draggable in edit mode
        panel.style.cursor = 'move';
        panel.addEventListener('mousedown', startDrag);
        panel.addEventListener('touchstart', startDrag, { passive: false });
    });
    
    // Position panels based on their grid coordinates
    positionAllPanels();
}

function destroyGridDragging() {
    const panels = document.querySelectorAll('.dashboard-panel');
    
    panels.forEach(panel => {
        panel.style.cursor = 'default';
        panel.removeEventListener('mousedown', startDrag);
        panel.removeEventListener('touchstart', startDrag);
    });
}

function startDrag(e) {
    if (!editMode) return;
    
    e.preventDefault();
    isDragging = true;
    dragPanel = e.currentTarget;
    
    const rect = dragPanel.getBoundingClientRect();
    const gridRect = document.getElementById('dashboard-grid').getBoundingClientRect();
    
    // Calculate offset from mouse to panel origin
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    dragOffset.x = clientX - rect.left;
    dragOffset.y = clientY - rect.top;
    
    // Add dragging class for visual feedback
    dragPanel.classList.add('dragging');
    
    // Add event listeners for drag and drop
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', endDrag);
    
    // Show snap indicator
    const snapIndicator = document.getElementById('snapIndicator');
    snapIndicator.classList.add('active');
}

function onDrag(e) {
    if (!isDragging || !dragPanel) return;
    
    e.preventDefault();
    
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    const gridRect = document.getElementById('dashboard-grid').getBoundingClientRect();
    
    // Calculate new position relative to grid
    const newX = clientX - gridRect.left - dragOffset.x;
    const newY = clientY - gridRect.top - dragOffset.y;
    
    // Snap to grid
    const snappedX = Math.round(newX / GRID_SIZE) * GRID_SIZE;
    const snappedY = Math.round(newY / GRID_SIZE) * GRID_SIZE;
    
    // Update panel position
    dragPanel.style.left = snappedX + 'px';
    dragPanel.style.top = snappedY + 'px';
    
    // Update snap indicator
    updateSnapIndicator(snappedX, snappedY, dragPanel);
}

function endDrag() {
    if (!isDragging || !dragPanel) return;
    
    isDragging = false;
    
    // Remove dragging class
    dragPanel.classList.remove('dragging');
    
    // Update grid coordinates
    const rect = dragPanel.getBoundingClientRect();
    const gridRect = document.getElementById('dashboard-grid').getBoundingClientRect();
    
    let gridX = Math.round((rect.left - gridRect.left) / GRID_SIZE);
    let gridY = Math.round((rect.top - gridRect.top) / GRID_SIZE);
    
    const gridWidth = parseInt(dragPanel.getAttribute('data-grid-width')) || 8;
    const gridHeight = parseInt(dragPanel.getAttribute('data-grid-height')) || 6;
    
    // Check for collision or bounds violation
    if (checkCollision(gridX, gridY, gridWidth, gridHeight, dragPanel) || 
        !checkBounds(gridX, gridY, gridWidth, gridHeight)) {
        
        // Find nearest valid position
        const validPos = findValidPosition(gridWidth, gridHeight, gridX, gridY);
        gridX = validPos.x;
        gridY = validPos.y;
        
        // Show feedback for invalid position
        AppTracker.showToast('Position adjusted to avoid overlap', 'warning');
        
        // Add visual feedback
        dragPanel.style.backgroundColor = 'rgba(255, 193, 7, 0.1)';
        setTimeout(() => {
            dragPanel.style.backgroundColor = '';
        }, 1000);
    } else {
        AppTracker.showToast('Panel moved!', 'success');
    }
    
    // Update data attributes with validated position
    dragPanel.setAttribute('data-grid-x', gridX);
    dragPanel.setAttribute('data-grid-y', gridY);
    
    // Position panel at validated coordinates
    dragPanel.style.left = (gridX * GRID_SIZE) + 'px';
    dragPanel.style.top = (gridY * GRID_SIZE) + 'px';
    
    // Save layout
    saveGridLayout();
    
    // Hide snap indicator
    const snapIndicator = document.getElementById('snapIndicator');
    snapIndicator.classList.remove('active');
    
    // Remove event listeners
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', endDrag);
    
    dragPanel = null;
    
    AppTracker.showToast('Panel moved!', 'success');
}

function updateSnapIndicator(x, y, panel) {
    const snapIndicator = document.getElementById('snapIndicator');
    const gridWidth = parseInt(panel.getAttribute('data-grid-width')) || 8;
    const gridHeight = parseInt(panel.getAttribute('data-grid-height')) || 6;
    const width = gridWidth * GRID_SIZE;
    const height = gridHeight * GRID_SIZE;
    
    // Calculate grid position
    const gridX = Math.round(x / GRID_SIZE);
    const gridY = Math.round(y / GRID_SIZE);
    
    // Check if position is valid
    const isValid = !checkCollision(gridX, gridY, gridWidth, gridHeight, panel) && 
                   checkBounds(gridX, gridY, gridWidth, gridHeight);
    
    // Position snap indicator at the snapped grid position
    // Since it's now inside the dashboard-grid container, coordinates are relative
    snapIndicator.style.left = (gridX * GRID_SIZE) + 'px';
    snapIndicator.style.top = (gridY * GRID_SIZE) + 'px';
    snapIndicator.style.width = width + 'px';
    snapIndicator.style.height = height + 'px';
    
    // Update visual feedback based on validity
    if (isValid) {
        snapIndicator.style.borderColor = 'var(--primary-color)';
        snapIndicator.style.backgroundColor = 'rgba(13, 110, 253, 0.1)';
    } else {
        snapIndicator.style.borderColor = '#dc3545';
        snapIndicator.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
    }
}

function positionAllPanels() {
    const panels = document.querySelectorAll('.dashboard-panel');
    
    panels.forEach(panel => {
        const gridX = parseInt(panel.getAttribute('data-grid-x')) || 0;
        const gridY = parseInt(panel.getAttribute('data-grid-y')) || 0;
        const gridWidth = parseInt(panel.getAttribute('data-grid-width')) || 8;
        const gridHeight = parseInt(panel.getAttribute('data-grid-height')) || 6;
        
        panel.style.left = (gridX * GRID_SIZE) + 'px';
        panel.style.top = (gridY * GRID_SIZE) + 'px';
        panel.style.width = (gridWidth * GRID_SIZE) + 'px';
        panel.style.height = (gridHeight * GRID_SIZE) + 'px';
    });
}

function saveGridLayout() {
    const panels = document.querySelectorAll('.dashboard-panel');
    const layout = {};
    
    panels.forEach(panel => {
        const panelId = panel.getAttribute('data-panel');
        layout[panelId] = {
            x: parseInt(panel.getAttribute('data-grid-x')),
            y: parseInt(panel.getAttribute('data-grid-y')),
            width: parseInt(panel.getAttribute('data-grid-width')),
            height: parseInt(panel.getAttribute('data-grid-height')),
            type: panel.getAttribute('data-panel-type')
        };
    });
    
    localStorage.setItem('dashboard-grid-layout', JSON.stringify(layout));
}

function loadGridLayout() {
    try {
        const layout = JSON.parse(localStorage.getItem('dashboard-grid-layout') || '{}');
        
        Object.keys(layout).forEach(panelId => {
            const panel = document.querySelector(`[data-panel="${panelId}"]`);
            if (panel) {
                const pos = layout[panelId];
                panel.setAttribute('data-grid-x', pos.x);
                panel.setAttribute('data-grid-y', pos.y);
                panel.setAttribute('data-grid-width', pos.width);
                panel.setAttribute('data-grid-height', pos.height);
            }
        });
        
        positionAllPanels();
    } catch (e) {
        console.log('No saved grid layout found, using defaults');
        positionAllPanels();
    }
}

function resetLayout() {
    localStorage.removeItem('dashboard-grid-layout');
    localStorage.removeItem('dashboard-panels');
    localStorage.removeItem('panel-sizes');
    location.reload();
}

// Panel Management Functions
function addPanel(type) {
    const grid = document.getElementById('dashboard-grid');
    const panelHtml = generatePanelHTML(type);
    
    if (panelHtml) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = panelHtml;
        const newPanel = tempDiv.firstElementChild;
        
        // Get panel dimensions
        const gridWidth = parseInt(newPanel.getAttribute('data-grid-width')) || 8;
        const gridHeight = parseInt(newPanel.getAttribute('data-grid-height')) || 6;
        
        // Find valid position for new panel
        const validPosition = findValidPosition(gridWidth, gridHeight);
        newPanel.setAttribute('data-grid-x', validPosition.x);
        newPanel.setAttribute('data-grid-y', validPosition.y);
        
        grid.appendChild(newPanel);
        
        // Position the new panel
        positionAllPanels();
        
        // Initialize dragging if in edit mode
        if (editMode) {
            newPanel.style.cursor = 'move';
            newPanel.addEventListener('mousedown', startDrag);
            newPanel.addEventListener('touchstart', startDrag, { passive: false });
        }
        
        saveGridLayout();
        AppTracker.showToast('Panel added successfully!', 'success');
    }
}

function findAvailableGridPosition() {
    // Simple algorithm to find next available position
    // In a real implementation, you'd check for overlaps
    const panels = document.querySelectorAll('.dashboard-panel');
    let maxY = 0;
    
    panels.forEach(panel => {
        const y = parseInt(panel.getAttribute('data-grid-y') || 0);
        const height = parseInt(panel.getAttribute('data-grid-height') || 6);
        maxY = Math.max(maxY, y + height);
    });
    
    return { x: 0, y: maxY + 1 };
}

function removePanel(button) {
    if (confirm('Are you sure you want to remove this panel?')) {
        const panel = button.closest('.dashboard-panel');
        panel.remove();
        saveGridLayout();
        AppTracker.showToast('Panel removed', 'info');
    }
}

function togglePanelSize(button) {
    const panel = button.closest('.dashboard-panel');
    const icon = button.querySelector('.panel-size-icon');
    
    let currentWidth = parseInt(panel.getAttribute('data-grid-width'));
    let currentHeight = parseInt(panel.getAttribute('data-grid-height'));
    
    // Store original dimensions if not already stored
    if (!panel.hasAttribute('data-original-width')) {
        panel.setAttribute('data-original-width', currentWidth);
        panel.setAttribute('data-original-height', currentHeight);
    }
    
    const originalWidth = parseInt(panel.getAttribute('data-original-width'));
    const originalHeight = parseInt(panel.getAttribute('data-original-height'));
    
    if (panel.classList.contains('panel-expanded')) {
        // Shrink to smaller size
        panel.classList.remove('panel-expanded');
        panel.classList.add('panel-collapsed');
        panel.setAttribute('data-grid-width', Math.max(4, Math.floor(originalWidth * 0.7)));
        panel.setAttribute('data-grid-height', Math.max(3, Math.floor(originalHeight * 0.7)));
        icon.className = 'bi bi-arrows-angle-expand panel-size-icon';
        button.title = 'Expand Panel';
    } else if (panel.classList.contains('panel-collapsed')) {
        // Return to normal size
        panel.classList.remove('panel-collapsed');
        panel.setAttribute('data-grid-width', originalWidth);
        panel.setAttribute('data-grid-height', originalHeight);
        icon.className = 'bi bi-arrows-angle-expand panel-size-icon';
        button.title = 'Toggle Size';
    } else {
        // Expand panel
        panel.classList.add('panel-expanded');
        panel.setAttribute('data-grid-width', Math.floor(originalWidth * 1.5));
        panel.setAttribute('data-grid-height', Math.floor(originalHeight * 1.3));
        icon.className = 'bi bi-arrows-angle-contract panel-size-icon';
        button.title = 'Shrink Panel';
    }
    
    positionAllPanels();
    saveGridLayout();
    AppTracker.showToast('Panel resized', 'info');
}

function changeChartType(chartId, newType) {
    const chart = Chart.getChart(chartId);
    if (chart) {
        chart.destroy();
    }
    
    // Re-create chart with new type
    const canvas = document.getElementById(chartId);
    const ctx = canvas.getContext('2d');
    
    const chartConfig = getChartConfig(chartId, newType);
    new Chart(ctx, chartConfig);
    
    // Save chart type preference
    const chartTypes = JSON.parse(localStorage.getItem('chart-types') || '{}');
    chartTypes[chartId] = newType;
    localStorage.setItem('chart-types', JSON.stringify(chartTypes));
    
    AppTracker.showToast(`Chart changed to ${newType}`, 'success');
}

function savePanelSizes() {
    const sizes = {};
    document.querySelectorAll('.sortable-item').forEach(panel => {
        const panelId = panel.dataset.panel;
        if (panel.classList.contains('panel-expanded')) {
            sizes[panelId] = 'expanded';
        } else if (panel.classList.contains('panel-collapsed')) {
            sizes[panelId] = 'collapsed';
        }
    });
    localStorage.setItem('panel-sizes', JSON.stringify(sizes));
}

function loadPanelSizes() {
    try {
        const sizes = JSON.parse(localStorage.getItem('panel-sizes') || '{}');
        Object.keys(sizes).forEach(panelId => {
            const panel = document.querySelector(`[data-panel="${panelId}"]`);
            if (panel) {
                panel.classList.add(`panel-${sizes[panelId]}`);
                const button = panel.querySelector('.panel-size-icon');
                if (button && sizes[panelId] === 'expanded') {
                    button.className = 'bi bi-arrows-angle-contract panel-size-icon';
                    button.closest('button').title = 'Shrink Panel';
                }
            }
        });
    } catch (e) {
        console.log('No saved panel sizes found');
    }
}

// Panel HTML generators
function generatePanelHTML(type) {
    const panelId = `panel-${Date.now()}`;
    
    switch (type) {
        case 'weather':
            return `
                <div class="dashboard-panel" data-panel="${panelId}" data-panel-type="widget" data-grid-x="0" data-grid-y="3" data-grid-width="8" data-grid-height="8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                            <i class="bi bi-grip-vertical"></i>
                            <div class="panel-controls">
                                <button class="btn btn-sm" onclick="togglePanelSize(this)" title="Toggle Size">
                                    <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                                </button>
                                <button class="btn btn-sm" onclick="removePanel(this)" title="Remove Panel">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body text-center">
                            <i class="bi bi-cloud-sun fs-1 text-primary mb-3"></i>
                            <h5>Weather Widget</h5>
                            <div class="weather-info">
                                <div class="h3 mb-0">72°F</div>
                                <div class="text-muted">Partly Cloudy</div>
                                <div class="small text-muted mt-2">Last updated: ${new Date().toLocaleTimeString()}</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
        case 'notes':
            return `
                <div class="dashboard-panel" data-panel="${panelId}" data-panel-type="notes" data-grid-x="0" data-grid-y="3" data-grid-width="12" data-grid-height="8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                            <i class="bi bi-grip-vertical"></i>
                            <div class="panel-controls">
                                <button class="btn btn-sm" onclick="togglePanelSize(this)" title="Toggle Size">
                                    <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                                </button>
                                <button class="btn btn-sm" onclick="removePanel(this)" title="Remove Panel">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" rows="6" placeholder="Add your notes here..." id="notes-${panelId}"></textarea>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-primary" onclick="saveNotes('${panelId}')">Save Notes</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
        case 'shortcuts':
            return `
                <div class="dashboard-panel" data-panel="${panelId}" data-panel-type="shortcuts" data-grid-x="0" data-grid-y="3" data-grid-width="8" data-grid-height="10">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center drag-handle">
                            <i class="bi bi-grip-vertical"></i>
                            <div class="panel-controls">
                                <button class="btn btn-sm" onclick="togglePanelSize(this)" title="Toggle Size">
                                    <i class="bi bi-arrows-angle-expand panel-size-icon"></i>
                                </button>
                                <button class="btn btn-sm" onclick="removePanel(this)" title="Remove Panel">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="location.href='projects.php?action=create'">
                                    <i class="bi bi-plus-lg"></i> New Project
                                </button>
                                <button class="btn btn-outline-success btn-sm" onclick="location.href='tasks.php?action=create'">
                                    <i class="bi bi-check-square"></i> New Task
                                </button>
                                <button class="btn btn-outline-info btn-sm" onclick="location.href='reports.php'">
                                    <i class="bi bi-graph-up"></i> View Reports
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
        default:
            return null;
    }
}

function getChartConfig(chartId, type) {
    // This would contain your chart configurations for different types
    const baseConfig = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    };
    
    if (chartId === 'projectStatusChart') {
        return {
            type: type,
            data: {
                labels: ['Planning', 'In Progress', 'Testing', 'Completed', 'On Hold'],
                datasets: [{
                    data: [5, 8, 2, 12, 3], // Sample data
                    backgroundColor: ['#6c757d', '#007bff', '#17a2b8', '#28a745', '#ffc107'],
                    borderWidth: type === 'doughnut' ? 2 : 1,
                    borderColor: '#fff'
                }]
            },
            options: baseConfig
        };
    }
    
    return { type: type, data: {}, options: baseConfig };
}

function saveNotes(panelId) {
    const textarea = document.getElementById(`notes-${panelId}`);
    const notes = textarea.value;
    localStorage.setItem(`notes-${panelId}`, notes);
    AppTracker.showToast('Notes saved!', 'success');
}

// Simple chart loading (we'll optimize later)
document.addEventListener('DOMContentLoaded', function() {
    loadGridLayout();
    positionAllPanels();
    
    // Load Chart.js if charts are present
    if (document.querySelector('canvas[id*="Chart"]')) {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js';
        script.onload = function() {
            // Project Status Chart
            const projectCanvas = document.getElementById('projectStatusChart');
            if (projectCanvas) {
                const projectCtx = projectCanvas.getContext('2d');
                new Chart(projectCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Planning', 'In Progress', 'Testing', 'Completed', 'On Hold'],
                        datasets: [{
                            data: [
                                <?php echo ($stats['projects']['total_projects'] ?? 0) - (($stats['projects']['completed_projects'] ?? 0) + ($stats['projects']['active_projects'] ?? 0) + ($stats['projects']['on_hold_projects'] ?? 0)); ?>,
                                <?php echo $stats['projects']['active_projects'] ?? 0; ?>,
                                0,
                                <?php echo $stats['projects']['completed_projects'] ?? 0; ?>,
                                <?php echo $stats['projects']['on_hold_projects'] ?? 0; ?>
                            ],
                            backgroundColor: ['#6c757d', '#007bff', '#17a2b8', '#28a745', '#ffc107'],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

            // Task Priority Chart
            const taskCanvas = document.getElementById('taskPriorityChart');
            if (taskCanvas) {
                const taskCtx = taskCanvas.getContext('2d');
                new Chart(taskCtx, {
                    type: 'bar',
                    data: {
                        labels: ['Critical', 'High', 'Medium', 'Low'],
                        datasets: [{
                            label: 'Tasks',
                            data: [10, 25, 40, 15], // Would need actual data
                            backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745'],
                            borderColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }
        };
        document.head.appendChild(script);
    }
});
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>