<?php
/**
 * Reports Page
 * Web Application Modernization Tracker
 * 
 * Comprehensive reporting dashboard with multiple report types and export functionality.
 */

$pageTitle = 'Reports & Analytics';
require_once dirname(__FILE__) . '/../includes/header.php';

requireLogin();

$currentUser = getCurrentUser();

// Process form submissions and filters
$filters = [];
$reportType = $_GET['type'] ?? 'summary';
$exportFormat = $_GET['export'] ?? null;

if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}

if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}

if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}

if (isset($_GET['priority']) && !empty($_GET['priority'])) {
    $filters['priority'] = $_GET['priority'];
}

if (isset($_GET['assigned_to']) && !empty($_GET['assigned_to'])) {
    $filters['assigned_to'] = $_GET['assigned_to'];
}

// Generate reports based on type
$reportData = [];
$summary = [];

switch ($reportType) {
    case 'project_status':
        $reportData = generateProjectStatusReport($filters);
        break;
    case 'task_completion':
        $reportData = generateTaskCompletionReport($filters);
        break;
    case 'productivity':
        $reportData = generateProductivityReport($filters);
        break;
    case 'timeline':
        $reportData = generateTimelineReport($filters);
        break;
    default:
        $summary = generateReportSummary($filters);
        break;
}

// Handle export functionality
if ($exportFormat && !empty($reportData)) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($reportData)) {
        // Output CSV headers
        fputcsv($output, array_keys($reportData[0]));
        
        // Output data
        foreach ($reportData as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

// Get users for filtering
$users = getUsers();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
            <?php if ($reportType !== 'summary' && !empty($reportData)): ?>
            <div>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" 
                   class="btn btn-outline-success">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Report Type Navigation -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="btn-group flex-wrap" role="group">
                            <a href="?type=summary" 
                               class="btn <?php echo $reportType === 'summary' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-speedometer2"></i> Summary
                            </a>
                            <a href="?type=project_status" 
                               class="btn <?php echo $reportType === 'project_status' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-folder-check"></i> Projects
                            </a>
                            <a href="?type=task_completion" 
                               class="btn <?php echo $reportType === 'task_completion' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-list-task"></i> Tasks
                            </a>
                            <a href="?type=productivity" 
                               class="btn <?php echo $reportType === 'productivity' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-person-check"></i> Productivity
                            </a>
                            <a href="?type=timeline" 
                               class="btn <?php echo $reportType === 'timeline' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-calendar3"></i> Timeline
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <button class="btn btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#filterCollapse" aria-expanded="false">
                            <i class="bi bi-funnel"></i> Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Panel -->
<div class="collapse mb-4" id="filterCollapse">
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="bi bi-funnel"></i> Report Filters</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <input type="hidden" name="type" value="<?php echo $reportType; ?>">
                
                <div class="row">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo $filters['start_date'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo $filters['end_date'] ?? ''; ?>">
                    </div>
                    
                    <?php if ($reportType === 'timeline' || $reportType === 'project_status'): ?>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="planning" <?php echo ($filters['status'] ?? '') === 'planning' ? 'selected' : ''; ?>>Planning</option>
                            <option value="in_progress" <?php echo ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="testing" <?php echo ($filters['status'] ?? '') === 'testing' ? 'selected' : ''; ?>>Testing</option>
                            <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="on_hold" <?php echo ($filters['status'] ?? '') === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority">
                            <option value="">All Priorities</option>
                            <option value="critical" <?php echo ($filters['priority'] ?? '') === 'critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="high" <?php echo ($filters['priority'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="medium" <?php echo ($filters['priority'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="low" <?php echo ($filters['priority'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($reportType === 'task_completion' || $reportType === 'productivity'): ?>
                    <div class="col-md-2">
                        <label for="assigned_to" class="form-label">Assigned To</label>
                        <select class="form-select" id="assigned_to" name="assigned_to">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo ($filters['assigned_to'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search"></i> Apply
                        </button>
                        <a href="?type=<?php echo $reportType; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($reportType === 'summary'): ?>
<!-- Summary Report -->
<div class="row">
    <!-- Summary Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="card-title h6">Total Projects</div>
                        <div class="display-4"><?php echo $summary['projects']['total_projects'] ?? 0; ?></div>
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
                        <div class="card-title h6">Completion Rate</div>
                        <div class="display-4"><?php echo $summary['completion_rate'] ?? 0; ?>%</div>
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
                        <div class="card-title h6">Total Tasks</div>
                        <div class="display-4"><?php echo $summary['tasks']['total_tasks'] ?? 0; ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-list-task fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card dashboard-card info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="card-title h6">Total Hours</div>
                        <div class="display-4"><?php echo number_format($summary['tasks']['total_hours'] ?? 0, 0); ?></div>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-clock fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><?php echo isFeatureEnabled('budget_tracking') ? 'Budget Summary' : 'Project Summary'; ?></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (isFeatureEnabled('budget_tracking')): ?>
                    <div class="col-6">
                        <div class="metric">
                            <h3 class="text-primary">$<?php echo number_format($summary['projects']['total_budget'] ?? 0, 0); ?></h3>
                            <p class="text-muted mb-0">Total Budget</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric">
                            <h3 class="text-success">$<?php echo number_format($summary['projects']['avg_budget'] ?? 0, 0); ?></h3>
                            <p class="text-muted mb-0">Average per Project</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="col-6">
                        <div class="metric">
                            <h3 class="text-info"><?php echo $summary['projects']['active_projects'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Active Projects</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric">
                            <h3 class="text-warning"><?php echo number_format($summary['tasks']['avg_hours_per_task'] ?? 0, 1); ?></h3>
                            <p class="text-muted mb-0">Avg Hours per Task</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Task Efficiency</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6">
                        <div class="metric">
                            <h3 class="text-info"><?php echo number_format($summary['tasks']['avg_hours_per_task'] ?? 0, 1); ?></h3>
                            <p class="text-muted mb-0">Hours per Task</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="metric">
                            <h3 class="text-warning"><?php echo $summary['tasks']['completed_tasks'] ?? 0; ?></h3>
                            <p class="text-muted mb-0">Completed Tasks</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'project_status'): ?>
<!-- Project Status Report -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Project Status Report</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-folder-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No project data found for the selected filters.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Project Count</th>
                                <th>Avg Duration (Days)</th>
                                <?php if (isFeatureEnabled('budget_tracking')): ?>
                                <th>Total Budget</th>
                                <th>Average Budget</th>
                                <?php else: ?>
                                <th>Total Tasks</th>
                                <th>Avg Tasks per Project</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?php echo getStatusBadge($row['status'], 'project'); ?></td>
                                <td><?php echo $row['project_count']; ?></td>
                                <td><?php echo $row['avg_duration_days'] ? number_format($row['avg_duration_days'], 1) : 'N/A'; ?></td>
                                <?php if (isFeatureEnabled('budget_tracking')): ?>
                                <td>$<?php echo number_format($row['total_budget'] ?? 0, 0); ?></td>
                                <td>$<?php echo number_format($row['avg_budget'] ?? 0, 0); ?></td>
                                <?php else: ?>
                                <td><?php echo $row['total_tasks'] ?? 0; ?></td>
                                <td><?php echo $row['project_count'] > 0 ? number_format(($row['total_tasks'] ?? 0) / $row['project_count'], 1) : 'N/A'; ?></td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'task_completion'): ?>
<!-- Task Completion Report -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Task Completion Report</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-list-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No task data found for the selected filters.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Task Count</th>
                                <th>Total Hours</th>
                                <th>Avg Hours</th>
                                <th>Avg Completion Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?php echo getStatusBadge($row['status'], 'task'); ?></td>
                                <td><?php echo getPriorityBadge($row['priority']); ?></td>
                                <td><?php echo $row['task_count']; ?></td>
                                <td><?php echo number_format($row['total_hours'] ?? 0, 1); ?></td>
                                <td><?php echo number_format($row['avg_hours'] ?? 0, 1); ?></td>
                                <td><?php echo $row['avg_completion_days'] ? number_format($row['avg_completion_days'], 1) : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'productivity'): ?>
<!-- Productivity Report -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Team Productivity Report</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-person-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No productivity data found for the selected filters.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Team Member</th>
                                <th>Projects</th>
                                <th>Total Tasks</th>
                                <th>Completed Tasks</th>
                                <th>Completion Rate</th>
                                <th>Total Hours</th>
                                <th>Avg Task Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                <td><?php echo $row['projects_worked_on']; ?></td>
                                <td><?php echo $row['total_tasks']; ?></td>
                                <td><?php echo $row['completed_tasks']; ?></td>
                                <td>
                                    <?php 
                                    $completionRate = $row['total_tasks'] > 0 ? round(($row['completed_tasks'] / $row['total_tasks']) * 100, 1) : 0;
                                    echo $completionRate . '%';
                                    ?>
                                </td>
                                <td><?php echo number_format($row['total_hours'] ?? 0, 1); ?></td>
                                <td><?php echo $row['avg_task_completion_days'] ? number_format($row['avg_task_completion_days'], 1) : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php elseif ($reportType === 'timeline'): ?>
<!-- Timeline Report -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Project Timeline Report</h5>
            </div>
            <div class="card-body">
                <?php if (empty($reportData)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x fs-1 text-muted"></i>
                    <p class="text-muted mt-2">No timeline data found for the selected filters.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Project Name</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created</th>
                                <th>Due Date</th>
                                <th>Days Until Due</th>
                                <th>Progress</th>
                                <th>Tasks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo getStatusBadge($row['status'], 'project'); ?></td>
                                <td><?php echo getPriorityBadge($row['priority']); ?></td>
                                <td><?php echo formatDate($row['created_at']); ?></td>
                                <td>
                                    <?php if ($row['due_date']): ?>
                                        <?php echo formatDate($row['due_date']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['days_until_due'] !== null): ?>
                                        <?php if ($row['days_until_due'] < 0): ?>
                                            <span class="text-danger"><?php echo abs($row['days_until_due']); ?> days overdue</span>
                                        <?php elseif ($row['days_until_due'] == 0): ?>
                                            <span class="text-warning">Due today</span>
                                        <?php else: ?>
                                            <?php echo $row['days_until_due']; ?> days
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $row['progress_percentage']; ?>%"
                                             aria-valuenow="<?php echo $row['progress_percentage']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <small class="text-muted"><?php echo $row['progress_percentage']; ?>%</small>
                                </td>
                                <td><?php echo $row['completed_tasks'] . '/' . $row['total_tasks']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
.dashboard-card {
    border-left: 4px solid #007bff;
}
.dashboard-card.success {
    border-left-color: #28a745;
}
.dashboard-card.warning {
    border-left-color: #ffc107;
}
.dashboard-card.danger {
    border-left-color: #dc3545;
}
.dashboard-card.info {
    border-left-color: #17a2b8;
}

.metric h3 {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}
</style>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>