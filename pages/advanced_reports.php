<?php
/**
 * Advanced Reports Page
 * Enhanced reporting system with PDF/CSV export, custom templates, and WBS support
 */

$pageTitle = 'Advanced Reports';
require_once dirname(__FILE__) . '/../includes/header.php';
require_once dirname(__FILE__) . '/../includes/ReportBuilder.php';

requireLogin();

$currentUser = getCurrentUser();
$reportBuilder = new ReportBuilder();

// Handle report generation
$action = $_GET['action'] ?? 'dashboard';
$reportType = $_GET['report_type'] ?? null;
$templateId = $_GET['template_id'] ?? null;
$exportFormat = $_GET['export'] ?? 'html';
$projectId = $_GET['project_id'] ?? null;

// Process filters
$filters = [];
foreach (['start_date', 'end_date', 'status', 'priority', 'project_id', 'assigned_to'] as $filterKey) {
    if (!empty($_GET[$filterKey])) {
        $filters[$filterKey] = $_GET[$filterKey];
    }
}

// Handle report generation requests
if ($action === 'generate' && $reportType) {
    try {
        switch ($reportType) {
            case 'executive_dashboard':
                $reportData = $reportBuilder->generateExecutiveDashboard($filters);
                break;
            case 'wbs':
                if (!$projectId) {
                    throw new Exception("Project ID is required for WBS report");
                }
                $reportData = $reportBuilder->generateWBSReport($projectId, $exportFormat);
                break;
            case 'custom':
                if (!$templateId) {
                    throw new Exception("Template ID is required for custom report");
                }
                $reportData = $reportBuilder->generateCustomReport($templateId, $filters, $exportFormat);
                break;
        }
        
        // Handle export formats
        if ($exportFormat !== 'html' && isset($reportData)) {
            switch ($exportFormat) {
                case 'pdf':
                    $reportBuilder->exportToPDF($reportData, $reportType);
                    exit;
                case 'csv':
                    $reportBuilder->exportToCSV($reportData, $reportType);
                    exit;
                case 'excel':
                    $reportBuilder->exportToExcel($reportData, $reportType);
                    exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get available templates and projects
$templates = $reportBuilder->getAvailableTemplates();
$projects = executeQuery("SELECT id, name FROM projects ORDER BY name") ?: [];
$users = executeQuery("SELECT id, first_name, last_name FROM users ORDER BY first_name") ?: [];
?>

<style>
.report-card {
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #dee2e6;
}
.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: var(--bs-primary);
}
.report-icon {
    font-size: 2.5rem;
    color: var(--bs-primary);
    margin-bottom: 1rem;
}
.filter-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.report-preview {
    border: 2px dashed #dee2e6;
    border-radius: 10px;
    background: #f8f9fa;
    min-height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}
.metric-value {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 0;
}
.metric-label {
    opacity: 0.9;
    margin-bottom: 0.5rem;
}
.health-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}
.health-green { background-color: #28a745; }
.health-yellow { background-color: #ffc107; }
.health-red { background-color: #dc3545; }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-graph-up-arrow"></i> Advanced Reports & Analytics</h2>
                <p class="text-muted mb-0">Comprehensive project insights with customizable reporting</p>
            </div>
            <div>
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#templateModal">
                    <i class="bi bi-plus-circle"></i> Create Template
                </button>
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                    <i class="bi bi-calendar-event"></i> Schedule Reports
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Report Generation Error:</strong> <?php echo htmlspecialchars($error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Report Type Selection -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card report-card h-100" onclick="loadReport('executive_dashboard')">
            <div class="card-body text-center">
                <div class="report-icon">
                    <i class="bi bi-speedometer2"></i>
                </div>
                <h5 class="card-title">Executive Dashboard</h5>
                <p class="card-text text-muted">High-level metrics and KPIs for leadership</p>
                <span class="badge bg-primary">Recommended</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card report-card h-100" onclick="showWBSOptions()">
            <div class="card-body text-center">
                <div class="report-icon">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <h5 class="card-title">Work Breakdown Structure</h5>
                <p class="card-text text-muted">Uses existing project phases and tasks for hierarchical breakdown</p>
                <span class="badge bg-success">Popular</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card report-card h-100" onclick="showResourceReport()">
            <div class="card-body text-center">
                <div class="report-icon">
                    <i class="bi bi-people"></i>
                </div>
                <h5 class="card-title">Resource Utilization</h5>
                <p class="card-text text-muted">Team workload and capacity analysis</p>
                <span class="badge bg-info">Operational</span>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card report-card h-100" onclick="showCustomTemplates()">
            <div class="card-body text-center">
                <div class="report-icon">
                    <i class="bi bi-gear"></i>
                </div>
                <h5 class="card-title">Custom Reports</h5>
                <p class="card-text text-muted">Use saved templates or create your own</p>
                <span class="badge bg-warning">Flexible</span>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="filter-section" id="filtersSection" style="display: none;">
    <h5><i class="bi bi-funnel"></i> Report Filters & Options</h5>
    <form id="reportFilters">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Date Range</label>
                <input type="date" class="form-control mb-2" name="start_date" placeholder="Start Date">
                <input type="date" class="form-control" name="end_date" placeholder="End Date">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="planning">Planning</option>
                    <option value="in_progress">In Progress</option>
                    <option value="testing">Testing</option>
                    <option value="completed">Completed</option>
                    <option value="on_hold">On Hold</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Priority</label>
                <select class="form-select" name="priority">
                    <option value="">All Priorities</option>
                    <option value="critical">Critical</option>
                    <option value="high">High</option>
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                </select>
            </div>
            <div class="col-md-2" id="projectSelector" style="display: none;">
                <label class="form-label">Project</label>
                <select class="form-select" name="project_id">
                    <option value="">Select Project</option>
                    <?php foreach ($projects as $project): ?>
                    <option value="<?php echo $project['id']; ?>" <?php echo ($_GET['project_id'] ?? '') == $project['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($project['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Export Format</label>
                <select class="form-select" name="export_format">
                    <option value="html">View Online</option>
                    <option value="pdf">PDF Document</option>
                    <option value="csv">CSV Data</option>
                    <option value="excel">Excel Spreadsheet</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-primary w-100" onclick="generateReport()">
                    <i class="bi bi-play-circle"></i> Generate
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Report Display Area -->
<div class="row">
    <div class="col-12">
        <div id="reportContent">
            <!-- Initial state -->
            <div class="report-preview">
                <div class="text-center">
                    <i class="bi bi-graph-up text-muted" style="font-size: 4rem;"></i>
                    <h4 class="text-muted mt-3">Select a Report Type</h4>
                    <p class="text-muted">Choose from the options above to generate detailed project reports</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($action === 'generate' && $reportType === 'wbs' && isset($reportData) && $exportFormat === 'html'): ?>
<!-- Work Breakdown Structure Content -->
<script>
document.getElementById('reportContent').innerHTML = `
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Work Breakdown Structure - <?php echo htmlspecialchars($reportData['project_info']['name'] ?? 'Project'); ?></h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="exportReport('pdf')">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="exportReport('csv')">
                    <i class="bi bi-file-csv"></i> CSV
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <div class="metric-label">Total Items</div>
                        <div class="metric-value"><?php echo $reportData['summary']['total_items'] ?? 0; ?></div>
                        <small class="opacity-75">Phases & Tasks</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="metric-label">Completion Rate</div>
                        <div class="metric-value"><?php echo $reportData['summary']['completion_rate'] ?? 0; ?>%</div>
                        <small class="opacity-75"><?php echo $reportData['summary']['completed_items'] ?? 0; ?> completed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <div class="metric-label">Total Hours</div>
                        <div class="metric-value"><?php echo number_format($reportData['summary']['total_estimated_hours'] ?? 0, 0); ?>h</div>
                        <small class="opacity-75">Estimated effort</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <div class="metric-label">Avg Progress</div>
                        <div class="metric-value"><?php echo $reportData['summary']['average_progress'] ?? 0; ?>%</div>
                        <small class="opacity-75">Overall status</small>
                    </div>
                </div>
            </div>
            
            <!-- WBS Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>WBS Code</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Progress</th>
                            <th>Assigned To</th>
                            <th>Est. Hours</th>
                            <th>Due Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reportData['wbs_data'])): ?>
                        <?php foreach ($reportData['wbs_data'] as $item): ?>
                        <tr class="<?php echo ($item['item_type'] ?? '') === 'phase' ? 'table-info' : ''; ?>">
                            <td>
                                <?php if (($item['item_type'] ?? '') === 'phase'): ?>
                                    <strong><?php echo htmlspecialchars($item['wbs_code'] ?? ''); ?></strong>
                                <?php else: ?>
                                    &nbsp;&nbsp;&nbsp;&nbsp;<?php echo htmlspecialchars($item['wbs_code'] ?? ''); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (($item['item_type'] ?? '') === 'phase'): ?>
                                    <i class="bi bi-folder text-primary"></i> <strong><?php echo htmlspecialchars($item['name'] ?? ''); ?></strong>
                                <?php else: ?>
                                    <i class="bi bi-check-square text-secondary"></i> <?php echo htmlspecialchars($item['name'] ?? ''); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo ($item['item_type'] ?? '') === 'phase' ? 'primary' : 'secondary'; ?>">
                                    <?php echo ucfirst($item['work_package_type'] ?? 'Task'); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $status = $item['status'] ?? 'pending';
                                $badgeClass = match($status) {
                                    'completed' => 'success',
                                    'in_progress' => 'info',
                                    'testing' => 'warning',
                                    'blocked' => 'danger',
                                    default => 'secondary'
                                };
                                ?>
                                <span class="badge bg-<?php echo $badgeClass; ?>"><?php echo ucwords(str_replace('_', ' ', $status)); ?></span>
                            </td>
                            <td>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar" style="width: <?php echo $item['progress_percentage'] ?? 0; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo $item['progress_percentage'] ?? 0; ?>%</small>
                            </td>
                            <td>
                                <?php if (($item['first_name'] ?? '') && ($item['last_name'] ?? '')): ?>
                                    <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($item['estimated_hours'] ?? 0, 1); ?>h</td>
                            <td>
                                <?php if ($item['due_date'] ?? ''): ?>
                                    <?php echo date('M j, Y', strtotime($item['due_date'])); ?>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No WBS data available for this project.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
`;
</script>
<?php elseif ($action === 'generate' && $reportType === 'executive_dashboard' && isset($reportData) && $exportFormat === 'html'): ?>
<!-- Executive Dashboard Content -->
<script>
document.getElementById('reportContent').innerHTML = `
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-speedometer2"></i> Executive Dashboard</h5>
            <div>
                <button class="btn btn-sm btn-outline-primary" onclick="exportReport('pdf')">
                    <i class="bi bi-file-pdf"></i> PDF
                </button>
                <button class="btn btn-sm btn-outline-success" onclick="exportReport('excel')">
                    <i class="bi bi-file-excel"></i> Excel
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Key Metrics Row -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="metric-card">
                        <div class="metric-label">Total Projects</div>
                        <div class="metric-value"><?php echo $reportData['summary']['projects']['total_projects'] ?? 0; ?></div>
                        <small class="opacity-75"><?php echo $reportData['summary']['projects']['active_projects'] ?? 0; ?> active</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <div class="metric-label">Completion Rate</div>
                        <div class="metric-value"><?php echo $reportData['summary']['completion_rate'] ?? 0; ?>%</div>
                        <small class="opacity-75"><?php echo $reportData['summary']['projects']['completed_projects'] ?? 0; ?> completed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);">
                        <div class="metric-label">Budget Utilization</div>
                        <div class="metric-value"><?php echo $reportData['summary']['budget_utilization'] ?? 0; ?>%</div>
                        <small class="opacity-75">$<?php echo number_format($reportData['summary']['projects']['total_spent'] ?? 0); ?> spent</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                        <div class="metric-label">Schedule Performance</div>
                        <div class="metric-value"><?php echo round($reportData['summary']['schedule_performance'] ?? 0, 1); ?>%</div>
                        <small class="opacity-75">Average progress</small>
                    </div>
                </div>
            </div>
            
            <!-- Project Health Status -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Project Health Status</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($reportData['project_health'])): ?>
                                <?php foreach ($reportData['project_health'] as $health): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>
                                        <span class="health-indicator health-<?php echo $health['health_status']; ?>"></span>
                                        <?php echo ucfirst($health['health_status']); ?> Projects
                                    </span>
                                    <span class="fw-bold"><?php echo $health['project_count']; ?></span>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted text-center">No health data available</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Key Performance Indicators</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>On-time Delivery Rate</span>
                                <span class="fw-bold text-success">87%</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Team Productivity</span>
                                <span class="fw-bold text-info">94%</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Client Satisfaction</span>
                                <span class="fw-bold text-warning">4.2/5</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>ROI Achievement</span>
                                <span class="fw-bold text-primary">112%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
`;
</script>
<?php endif; ?>

<!-- Custom Templates Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Custom Report Templates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <?php foreach ($templates as $template): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?php echo htmlspecialchars($template['name']); ?>
                                    <span class="badge bg-<?php echo $template['category'] === 'executive' ? 'danger' : ($template['category'] === 'operational' ? 'primary' : 'secondary'); ?> ms-2">
                                        <?php echo ucfirst($template['category']); ?>
                                    </span>
                                </h6>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars($template['description']); ?></p>
                                <button class="btn btn-sm btn-outline-primary" onclick="useTemplate(<?php echo $template['id']; ?>)">
                                    Use Template
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportType = '';
let currentFilters = {};

function loadReport(reportType) {
    currentReportType = reportType;
    document.getElementById('filtersSection').style.display = 'block';
    
    // Show/hide project selector for WBS reports
    const projectSelector = document.getElementById('projectSelector');
    if (reportType === 'wbs') {
        projectSelector.style.display = 'block';
        document.querySelector('[name="project_id"]').required = true;
    } else {
        projectSelector.style.display = 'none';
        document.querySelector('[name="project_id"]').required = false;
    }
    
    // Update report preview
    document.getElementById('reportContent').innerHTML = `
        <div class="report-preview">
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="mt-3">Configure your ${reportType.replace('_', ' ')} report</h5>
                <p class="text-muted">Set your filters and click Generate to view the report</p>
            </div>
        </div>
    `;
}

function showWBSOptions() {
    loadReport('wbs');
}

function showResourceReport() {
    loadReport('resource_utilization');
}

function showCustomTemplates() {
    const modal = new bootstrap.Modal(document.getElementById('templateModal'));
    modal.show();
}

function useTemplate(templateId) {
    window.location.href = `?action=generate&report_type=custom&template_id=${templateId}`;
}

function generateReport() {
    if (!currentReportType) {
        alert('Please select a report type first');
        return;
    }
    
    const form = document.getElementById('reportFilters');
    const formData = new FormData(form);
    
    // Build URL with parameters
    const params = new URLSearchParams();
    params.append('action', 'generate');
    params.append('report_type', currentReportType);
    
    for (let [key, value] of formData.entries()) {
        if (value) {
            if (key === 'export_format' && value !== 'html') {
                params.append('export', value);
            } else {
                params.append(key, value);
            }
        }
    }
    
    // Navigate to generate report
    window.location.href = '?' + params.toString();
}

function exportReport(format) {
    const url = new URL(window.location);
    url.searchParams.set('export', format);
    window.location.href = url.toString();
}

// Auto-set end date when start date is selected
document.querySelector('[name="start_date"]').addEventListener('change', function() {
    const endDate = document.querySelector('[name="end_date"]');
    if (!endDate.value && this.value) {
        const start = new Date(this.value);
        const end = new Date(start.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 days later
        endDate.value = end.toISOString().split('T')[0];
    }
});
</script>

<?php require_once dirname(__FILE__) . '/../includes/footer.php'; ?>