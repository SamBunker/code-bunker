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

// Get dashboard statistics
$stats = getDashboardStats(isAdmin() ? null : $currentUser['id']);
$recentProjects = getProjects([], 5); // Get 5 most recent projects
$myTasks = getTasks(null, ['assigned_to' => $currentUser['id']]); // Get tasks assigned to current user
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card">
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
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card success">
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
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card warning">
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
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card danger">
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
</div>

<div class="row">
    <!-- Project Status Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-pie-chart"></i> Project Status Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="projectStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Task Priority Chart -->
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-bar-chart"></i> Task Priority Distribution
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="taskPriorityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Projects -->
    <div class="col-lg-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-clock-history"></i> Recent Projects
                </h5>
                <a href="<?php echo BASE_URL; ?>/pages/projects.php" class="btn btn-sm btn-outline-primary">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
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
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="bi bi-person-check"></i> My Tasks
                </h5>
                <a href="<?php echo BASE_URL; ?>/pages/tasks.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
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
                                            echo '<span class="text-warning">Due today</span>';
                                        } elseif ($daysUntilDue <= 3) {
                                            echo '<span class="text-warning">' . $daysUntilDue . ' days</span>';
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

<script>
// Simple chart loading (we'll optimize later)
document.addEventListener('DOMContentLoaded', function() {
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