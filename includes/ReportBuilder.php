<?php
/**
 * Enhanced Report Builder Class
 * Comprehensive reporting system with PDF/CSV export, custom templates, and WBS support
 */

require_once 'functions.php';

class ReportBuilder {
    private $db;
    private $reportData;
    private $template;
    private $filters;
    private $chartData;
    
    public function __construct() {
        $this->db = getDatabase();
        $this->reportData = [];
        $this->template = [];
        $this->filters = [];
        $this->chartData = [];
    }
    
    /**
     * Generate Work Breakdown Structure Report
     */
    public function generateWBSReport($projectId, $exportFormat = 'html') {
        $startTime = microtime(true);
        
        try {
            $wbsData = $this->getWBSData($projectId);
            $projectInfo = $this->getProjectInfo($projectId);
            
            switch ($exportFormat) {
                case 'pdf':
                    return $this->exportToPDF($wbsData, 'wbs');
                case 'csv':
                    return $this->exportToCSV($wbsData, 'wbs');
                case 'excel':
                    return $this->exportToExcel($wbsData, 'wbs');
                default:
                    return $this->renderWBSHTML($wbsData, $projectInfo);
            }
        } catch (Exception $e) {
            error_log("WBS Report Generation Error: " . $e->getMessage());
            throw new Exception("Failed to generate WBS report: " . $e->getMessage());
        }
    }
    
    /**
     * Get hierarchical WBS data using existing project phases and tasks
     */
    private function getWBSData($projectId) {
        $wbsData = [];
        
        // Get project phases as main WBS items
        $phases = executeQuery("
            SELECT 
                pp.*,
                'phase' as item_type,
                CONCAT(pp.order_index, '.0') as wbs_code,
                pp.name,
                pp.description,
                'deliverable' as work_package_type,
                0 as estimated_hours,
                0 as estimated_cost,
                0 as progress_percentage,
                'in_progress' as status,
                'medium' as priority,
                NULL as assigned_to,
                NULL as first_name,
                NULL as last_name,
                NULL as start_date,
                NULL as due_date
            FROM project_phases pp
            WHERE pp.project_id = ?
            ORDER BY pp.order_index
        ", [$projectId]) ?: [];
        
        // Add phases to WBS data
        foreach ($phases as $phase) {
            $wbsData[] = $phase;
            
            // Get tasks within this phase
            $tasks = executeQuery("
                SELECT 
                    t.*,
                    'task' as item_type,
                    CONCAT(?, '.', t.order_index) as wbs_code,
                    t.title as name,
                    t.description,
                    'task' as work_package_type,
                    t.estimated_hours,
                    0 as estimated_cost,
                    CASE 
                        WHEN t.status = 'completed' THEN 100
                        WHEN t.status = 'in_progress' THEN 50
                        ELSE 0
                    END as progress_percentage,
                    u.first_name,
                    u.last_name,
                    t.start_date,
                    t.due_date
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.project_id = ? AND t.phase_id = ?
                ORDER BY t.order_index
            ", [$phase['order_index'], $projectId, $phase['id']]) ?: [];
            
            // Add tasks to WBS data
            foreach ($tasks as $task) {
                $wbsData[] = $task;
            }
        }
        
        // Get tasks not in any phase
        $orphanTasks = executeQuery("
            SELECT 
                t.*,
                'task' as item_type,
                CONCAT('0.', t.id) as wbs_code,
                t.title as name,
                t.description,
                'task' as work_package_type,
                t.estimated_hours,
                0 as estimated_cost,
                CASE 
                    WHEN t.status = 'completed' THEN 100
                    WHEN t.status = 'in_progress' THEN 50
                    ELSE 0
                END as progress_percentage,
                u.first_name,
                u.last_name,
                t.start_date,
                t.due_date
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE t.project_id = ? AND t.phase_id IS NULL
            ORDER BY t.order_index, t.id
        ", [$projectId]) ?: [];
        
        // Add orphan tasks to WBS data
        foreach ($orphanTasks as $task) {
            $wbsData[] = $task;
        }
        
        return $wbsData;
    }
    
    /**
     * Render WBS as HTML for display
     */
    private function renderWBSHTML($wbsData, $projectInfo) {
        return [
            'project_info' => $projectInfo,
            'wbs_data' => $wbsData,
            'summary' => $this->calculateWBSSummary($wbsData)
        ];
    }
    
    /**
     * Calculate WBS Summary Statistics
     */
    private function calculateWBSSummary($wbsData) {
        $totalItems = count($wbsData);
        $totalHours = 0;
        $totalCost = 0;
        $completedItems = 0;
        $avgProgress = 0;
        
        foreach ($wbsData as $item) {
            $totalHours += $item['estimated_hours'] ?? 0;
            $totalCost += $item['estimated_cost'] ?? 0;
            if (($item['status'] ?? '') === 'completed') {
                $completedItems++;
            }
            $avgProgress += $item['progress_percentage'] ?? 0;
        }
        
        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'completion_rate' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 1) : 0,
            'total_estimated_hours' => $totalHours,
            'total_estimated_cost' => $totalCost,
            'average_progress' => $totalItems > 0 ? round($avgProgress / $totalItems, 1) : 0
        ];
    }
    
    /**
     * Generate Executive Dashboard Report
     */
    public function generateExecutiveDashboard($filters = []) {
        $this->filters = $filters;
        
        $data = [
            'summary' => $this->getExecutiveSummary(),
            'project_health' => $this->getProjectHealthMetrics(),
            'budget_analysis' => $this->getBudgetAnalysis(),
            'resource_utilization' => $this->getResourceUtilization(),
            'timeline_performance' => $this->getTimelinePerformance(),
            'risk_analysis' => $this->getRiskAnalysis(),
            'charts' => $this->generateExecutiveCharts()
        ];
        
        return $data;
    }
    
    /**
     * Generate Custom Report from Template
     */
    public function generateCustomReport($templateId, $filters = [], $exportFormat = 'html') {
        $template = $this->getReportTemplate($templateId);
        if (!$template) {
            throw new Exception("Report template not found");
        }
        
        $this->template = $template;
        $this->filters = $filters;
        
        $config = json_decode($template['template_config'], true);
        
        switch ($template['report_type']) {
            case 'executive_dashboard':
                return $this->generateExecutiveDashboard($filters);
            case 'work_breakdown_structure':
                $projectId = $filters['project_id'] ?? null;
                if (!$projectId) {
                    throw new Exception("Project ID required for WBS report");
                }
                return $this->generateWBSReport($projectId, $exportFormat);
            case 'resource_utilization':
                return $this->generateResourceUtilizationReport($filters);
            case 'budget_analysis':
                return $this->generateBudgetAnalysisReport($filters);
            case 'project_health':
                return $this->generateProjectHealthReport($filters);
            default:
                return $this->generateGenericReport($config, $filters);
        }
    }
    
    /**
     * Export to PDF using HTML conversion
     */
    public function exportToPDF($reportData, $reportType, $options = []) {
        $html = $this->generatePDFHTML($reportData, $reportType, $options);
        $filename = 'report_' . $reportType . '_' . date('Y-m-d') . '.pdf';
        
        // Use browser printing for PDF generation
        header('Content-Type: text/html');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        
        echo $html;
        exit;
    }
    
    /**
     * Generate HTML formatted for PDF printing
     */
    private function generatePDFHTML($reportData, $reportType, $options = []) {
        $title = ucwords(str_replace('_', ' ', $reportType)) . ' Report';
        $date = date('F j, Y');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @media print {
            body { margin: 0; font-family: Arial, sans-serif; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .header { text-align: center; margin-bottom: 30px; }
            .section { margin-bottom: 20px; }
        }
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px; }
        .company-info { font-size: 14px; color: #666; }
        .report-title { font-size: 24px; font-weight: bold; color: #333; margin: 10px 0; }
        .report-date { font-size: 12px; color: #888; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .section-title { font-size: 18px; font-weight: bold; margin: 20px 0 10px 0; color: #333; }
        .metric-box { display: inline-block; margin: 10px; padding: 15px; border: 1px solid #ddd; text-align: center; }
        .metric-value { font-size: 24px; font-weight: bold; color: #007bff; }
        .metric-label { font-size: 12px; color: #666; }
        .print-btn { background: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 20px 0; }
    </style>
    <script>
        window.onload = function() {
            // Auto-print when page loads
            setTimeout(function() {
                window.print();
            }, 500);
        };
        function printReport() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="header">
        <div class="company-info">Code Bunker Project Management System</div>
        <div class="report-title">' . htmlspecialchars($title) . '</div>
        <div class="report-date">Generated on ' . $date . '</div>
    </div>
    
    <button class="print-btn no-print" onclick="printReport()">🖨️ Print Report</button>';
    
        switch ($reportType) {
            case 'wbs':
                $html .= $this->generateWBSPDFContent($reportData);
                break;
            case 'executive_dashboard':
                $html .= $this->generateExecutivePDFContent($reportData);
                break;
            default:
                $html .= $this->generateGenericPDFContent($reportData);
                break;
        }
        
        $html .= '</body></html>';
        return $html;
    }
    
    /**
     * Generate WBS PDF Content
     */
    private function generateWBSPDFContent($reportData) {
        $html = '<div class="section-title">Work Breakdown Structure</div>';
        
        if (empty($reportData)) {
            return $html . '<p>No WBS data available.</p>';
        }
        
        // Add summary statistics
        $summary = $this->calculateWBSSummary($reportData);
        $html .= '<div style="margin-bottom: 20px;">
            <h6>Summary Statistics</h6>
            <p><strong>Total Items:</strong> ' . $summary['total_items'] . ' | 
               <strong>Completion Rate:</strong> ' . $summary['completion_rate'] . '% | 
               <strong>Total Hours:</strong> ' . number_format($summary['total_estimated_hours'], 1) . 'h</p>
        </div>';
        
        $html .= '<table>
            <thead>
                <tr>
                    <th>WBS Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Assigned To</th>
                    <th>Est. Hours</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($reportData as $item) {
            // Add indentation for tasks under phases
            $indent = ($item['item_type'] ?? '') === 'task' ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '';
            $itemName = $item['name'] ?? $item['title'] ?? '';
            $assignedTo = '';
            if (($item['first_name'] ?? '') && ($item['last_name'] ?? '')) {
                $assignedTo = $item['first_name'] . ' ' . $item['last_name'];
            } else {
                $assignedTo = 'Unassigned';
            }
            
            $html .= '<tr' . (($item['item_type'] ?? '') === 'phase' ? ' style="background-color: #f8f9fa; font-weight: bold;"' : '') . '>
                <td>' . $indent . htmlspecialchars($item['wbs_code'] ?? '') . '</td>
                <td>' . $indent . htmlspecialchars($itemName) . '</td>
                <td>' . ucfirst(str_replace('_', ' ', $item['work_package_type'] ?? 'Task')) . '</td>
                <td>' . ucfirst(str_replace('_', ' ', $item['status'] ?? 'pending')) . '</td>
                <td>' . ($item['progress_percentage'] ?? 0) . '%</td>
                <td>' . htmlspecialchars($assignedTo) . '</td>
                <td>' . number_format($item['estimated_hours'] ?? 0, 1) . 'h</td>
            </tr>';
        }
        
        $html .= '</tbody></table>';
        return $html;
    }
    
    /**
     * Generate Executive Dashboard PDF Content
     */
    private function generateExecutivePDFContent($reportData) {
        $html = '<div class="section-title">Executive Dashboard Summary</div>';
        
        // Key Metrics
        if (isset($reportData['summary'])) {
            $summary = $reportData['summary'];
            $html .= '<div style="margin-bottom: 30px;">';
            
            $html .= '<div class="metric-box">
                <div class="metric-value">' . ($summary['projects']['total_projects'] ?? 0) . '</div>
                <div class="metric-label">Total Projects</div>
            </div>';
            
            $html .= '<div class="metric-box">
                <div class="metric-value">' . ($summary['completion_rate'] ?? 0) . '%</div>
                <div class="metric-label">Completion Rate</div>
            </div>';
            
            $html .= '<div class="metric-box">
                <div class="metric-value">' . ($summary['budget_utilization'] ?? 0) . '%</div>
                <div class="metric-label">Budget Utilization</div>
            </div>';
            
            $html .= '<div class="metric-box">
                <div class="metric-value">' . number_format($summary['schedule_performance'] ?? 0, 1) . '%</div>
                <div class="metric-label">Schedule Performance</div>
            </div>';
            
            $html .= '</div>';
        }
        
        // Project Health
        if (isset($reportData['project_health']) && !empty($reportData['project_health'])) {
            $html .= '<div class="section-title">Project Health Status</div>';
            $html .= '<table>
                <thead>
                    <tr>
                        <th>Health Status</th>
                        <th>Project Count</th>
                        <th>Average Progress</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($reportData['project_health'] as $health) {
                $html .= '<tr>
                    <td>' . ucfirst($health['health_status']) . '</td>
                    <td>' . $health['project_count'] . '</td>
                    <td>' . number_format($health['avg_progress'] ?? 0, 1) . '%</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
        }
        
        return $html;
    }
    
    /**
     * Generate Generic PDF Content
     */
    private function generateGenericPDFContent($reportData) {
        $html = '<div class="section-title">Report Data</div>';
        
        if (empty($reportData) || !is_array($reportData)) {
            return $html . '<p>No data available for this report.</p>';
        }
        
        // Convert associative array to table
        $firstItem = reset($reportData);
        if (is_array($firstItem)) {
            $html .= '<table><thead><tr>';
            foreach (array_keys($firstItem) as $header) {
                $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            
            foreach ($reportData as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . htmlspecialchars($value ?? '') . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        }
        
        return $html;
    }
    
    /**
     * Export to CSV
     */
    public function exportToCSV($reportData, $reportType, $options = []) {
        $filename = 'report_' . $reportType . '_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        if (is_array($reportData) && !empty($reportData)) {
            // Handle different report structures
            switch ($reportType) {
                case 'wbs':
                    $this->exportWBSToCSVStream($output, $reportData);
                    break;
                case 'executive_dashboard':
                    $this->exportExecutiveToCSVStream($output, $reportData);
                    break;
                default:
                    $this->exportGenericToCSVStream($output, $reportData);
                    break;
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export WBS data to CSV stream
     */
    private function exportWBSToCSVStream($output, $reportData) {
        // CSV Headers
        $headers = ['WBS Code', 'Name', 'Description', 'Type', 'Status', 'Priority', 
                   'Progress %', 'Est. Hours', 'Est. Cost', 'Assigned To', 'Start Date', 'Due Date'];
        fputcsv($output, $headers);
        
        // Data rows
        foreach ($reportData as $item) {
            $row = [
                $item['wbs_code'] ?? '',
                $item['name'] ?? '',
                $item['description'] ?? '',
                ucfirst(str_replace('_', ' ', $item['work_package_type'] ?? '')),
                ucfirst($item['status'] ?? ''),
                ucfirst($item['priority'] ?? ''),
                ($item['progress_percentage'] ?? 0) . '%',
                number_format($item['estimated_hours'] ?? 0, 2),
                number_format($item['estimated_cost'] ?? 0, 2),
                ($item['first_name'] && $item['last_name']) ? 
                    $item['first_name'] . ' ' . $item['last_name'] : 'Unassigned',
                $item['start_date'] ?? '',
                $item['due_date'] ?? ''
            ];
            fputcsv($output, $row);
        }
    }
    
    /**
     * Export Executive Dashboard data to CSV stream
     */
    private function exportExecutiveToCSVStream($output, $reportData) {
        // Summary metrics
        fputcsv($output, ['EXECUTIVE DASHBOARD SUMMARY']);
        fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        if (isset($reportData['summary'])) {
            $summary = $reportData['summary'];
            fputcsv($output, ['SUMMARY METRICS']);
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Total Projects', $summary['projects']['total_projects'] ?? 0]);
            fputcsv($output, ['Active Projects', $summary['projects']['active_projects'] ?? 0]);
            fputcsv($output, ['Completed Projects', $summary['projects']['completed_projects'] ?? 0]);
            fputcsv($output, ['Completion Rate', ($summary['completion_rate'] ?? 0) . '%']);
            fputcsv($output, ['Budget Utilization', ($summary['budget_utilization'] ?? 0) . '%']);
            fputcsv($output, ['Schedule Performance', number_format($summary['schedule_performance'] ?? 0, 1) . '%']);
            fputcsv($output, []);
        }
        
        if (isset($reportData['project_health']) && !empty($reportData['project_health'])) {
            fputcsv($output, ['PROJECT HEALTH STATUS']);
            fputcsv($output, ['Health Status', 'Project Count', 'Average Progress']);
            foreach ($reportData['project_health'] as $health) {
                fputcsv($output, [
                    ucfirst($health['health_status']),
                    $health['project_count'],
                    number_format($health['avg_progress'] ?? 0, 1) . '%'
                ]);
            }
        }
    }
    
    /**
     * Export generic data to CSV stream
     */
    private function exportGenericToCSVStream($output, $reportData) {
        if (empty($reportData) || !is_array($reportData)) {
            fputcsv($output, ['No data available']);
            return;
        }
        
        $firstItem = reset($reportData);
        if (is_array($firstItem)) {
            // Export headers
            $headers = array_map(function($key) {
                return ucwords(str_replace('_', ' ', $key));
            }, array_keys($firstItem));
            fputcsv($output, $headers);
            
            // Export data
            foreach ($reportData as $row) {
                fputcsv($output, array_values($row));
            }
        }
    }
    
    /**
     * Export to Excel (using PHPSpreadsheet if available)
     */
    public function exportToExcel($reportData, $reportType, $options = []) {
        // Check if PHPSpreadsheet is available
        if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV
            return $this->exportToCSV($reportData, $reportType, $options);
        }
        
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set sheet title
        $sheet->setTitle(ucwords(str_replace('_', ' ', $reportType)));
        
        switch ($reportType) {
            case 'wbs':
                $this->generateWBSExcelContent($sheet, $reportData);
                break;
            case 'executive':
                $this->generateExecutiveExcelContent($sheet, $reportData);
                break;
            default:
                $this->generateGenericExcelContent($sheet, $reportData);
                break;
        }
        
        // Save and download
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'report_' . $reportType . '_' . date('Y-m-d') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Get Executive Summary Metrics
     */
    private function getExecutiveSummary() {
        $summary = [];
        
        // Basic project metrics
        $projectQuery = "
            SELECT 
                COUNT(*) as total_projects,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_projects,
                COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_projects,
                COUNT(CASE WHEN status = 'on_hold' THEN 1 END) as on_hold_projects,
                COUNT(CASE WHEN IFNULL(health_status, 'green') = 'red' THEN 1 END) as at_risk_projects,
                AVG(IFNULL(progress_percentage, 0)) as avg_progress,
                SUM(IFNULL(estimated_cost, 0)) as total_budget,
                SUM(IFNULL(actual_cost, 0)) as total_spent,
                COUNT(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 END) as overdue_projects
            FROM projects
            WHERE 1=1
        ";
        
        $whereClause = $this->buildWhereClause('projects', 'p');
        $params = [];
        if ($whereClause && !empty($whereClause['clause'])) {
            $projectQuery .= " AND " . $whereClause['clause'];
            $params = $whereClause['params'] ?? [];
        }
        
        $projectResult = executeQuerySingle($projectQuery, $params);
        $summary['projects'] = $projectResult ?: [
            'total_projects' => 0,
            'completed_projects' => 0,
            'active_projects' => 0,
            'on_hold_projects' => 0,
            'at_risk_projects' => 0,
            'avg_progress' => 0,
            'total_budget' => 0,
            'total_spent' => 0,
            'overdue_projects' => 0
        ];
        
        // Task metrics
        $taskQuery = "
            SELECT 
                COUNT(*) as total_tasks,
                COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_tasks,
                COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as active_tasks,
                AVG(CASE 
                    WHEN t.status = 'completed' THEN 100
                    WHEN t.status = 'in_progress' THEN 50
                    ELSE 0
                END) as avg_task_progress,
                SUM(IFNULL(t.estimated_hours, 0)) as total_estimated_hours,
                SUM(IFNULL(t.actual_hours, 0)) as total_actual_hours,
                COUNT(CASE WHEN t.due_date < CURDATE() AND t.status != 'completed' THEN 1 END) as overdue_tasks
            FROM tasks t
            JOIN projects p ON t.project_id = p.id
            WHERE 1=1
        ";
        
        if ($whereClause && !empty($whereClause['clause'])) {
            $taskQuery .= " AND " . str_replace('p.', 'p.', $whereClause['clause']);
        }
        
        $taskResult = executeQuerySingle($taskQuery, $params);
        $summary['tasks'] = $taskResult ?: [
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'active_tasks' => 0,
            'avg_task_progress' => 0,
            'total_estimated_hours' => 0,
            'total_actual_hours' => 0,
            'overdue_tasks' => 0
        ];
        
        // Calculate derived metrics safely
        $totalProjects = $summary['projects']['total_projects'] ?? 0;
        $completedProjects = $summary['projects']['completed_projects'] ?? 0;
        $totalBudget = $summary['projects']['total_budget'] ?? 0;
        $totalSpent = $summary['projects']['total_spent'] ?? 0;
        
        $summary['completion_rate'] = $totalProjects > 0 
            ? round(($completedProjects / $totalProjects) * 100, 1) 
            : 0;
            
        $summary['budget_utilization'] = $totalBudget > 0 
            ? round(($totalSpent / $totalBudget) * 100, 1) 
            : 0;
            
        $summary['schedule_performance'] = round($summary['projects']['avg_progress'] ?? 0, 1);
        
        return $summary;
    }
    
    /**
     * Get Project Health Metrics
     */
    private function getProjectHealthMetrics() {
        $query = "
            SELECT 
                IFNULL(health_status, 'green') as health_status,
                COUNT(*) as project_count,
                AVG(progress_percentage) as avg_progress,
                SUM(actual_cost) as total_cost
            FROM projects 
            WHERE 1=1
        ";
        
        $whereClause = $this->buildWhereClause('projects');
        if ($whereClause && !empty($whereClause['clause'])) {
            $query .= " AND " . $whereClause['clause'];
            $query .= " GROUP BY health_status ORDER BY FIELD(health_status, 'green', 'yellow', 'red')";
            return executeQuery($query, $whereClause['params']);
        }
        
        $query .= " GROUP BY health_status ORDER BY FIELD(health_status, 'green', 'yellow', 'red')";
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Budget Analysis
     */
    private function getBudgetAnalysis() {
        $query = "
            SELECT 
                status,
                COUNT(*) as project_count,
                SUM(estimated_cost) as total_estimated,
                SUM(actual_cost) as total_actual,
                AVG(estimated_cost) as avg_estimated,
                AVG(actual_cost) as avg_actual,
                SUM(CASE WHEN actual_cost > estimated_cost THEN 1 ELSE 0 END) as over_budget_count
            FROM projects 
            WHERE estimated_cost > 0
        ";
        
        $whereClause = $this->buildWhereClause('projects');
        if ($whereClause && !empty($whereClause['clause'])) {
            $query .= " AND " . $whereClause['clause'];
            $query .= " GROUP BY status";
            return executeQuery($query, $whereClause['params']);
        }
        
        $query .= " GROUP BY status";
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Resource Utilization
     */
    private function getResourceUtilization() {
        $query = "
            SELECT 
                u.id,
                u.first_name,
                u.last_name,
                COUNT(DISTINCT p.id) as projects_assigned,
                COUNT(DISTINCT t.id) as tasks_assigned,
                SUM(t.estimated_hours) as total_estimated_hours,
                SUM(t.actual_hours) as total_actual_hours,
                AVG(CASE WHEN t.status = 'completed' THEN 100 ELSE 0 END) as completion_rate
            FROM users u
            LEFT JOIN projects p ON u.id = p.assigned_to
            LEFT JOIN tasks t ON u.id = t.assigned_to
            WHERE u.is_active = 1
            GROUP BY u.id, u.first_name, u.last_name
            ORDER BY total_actual_hours DESC
        ";
        
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Timeline Performance
     */
    private function getTimelinePerformance() {
        $query = "
            SELECT 
                p.id,
                p.name,
                p.status,
                p.start_date,
                p.due_date,
                p.progress_percentage,
                DATEDIFF(CURDATE(), p.start_date) as days_elapsed,
                DATEDIFF(p.due_date, CURDATE()) as days_remaining,
                CASE 
                    WHEN p.due_date < CURDATE() AND p.status != 'completed' THEN 'overdue'
                    WHEN DATEDIFF(p.due_date, CURDATE()) <= 7 THEN 'due_soon'
                    ELSE 'on_track'
                END as timeline_status
            FROM projects p
            WHERE p.due_date IS NOT NULL
        ";
        
        $whereClause = $this->buildWhereClause('projects', 'p');
        if ($whereClause && !empty($whereClause['clause'])) {
            $query .= " AND " . $whereClause['clause'];
            return executeQuery($query, $whereClause['params']);
        }
        
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Risk Analysis
     */
    private function getRiskAnalysis() {
        $query = "
            SELECT 
                IFNULL(risk_level, 'low') as risk_level,
                COUNT(*) as project_count,
                AVG(progress_percentage) as avg_progress,
                COUNT(CASE WHEN due_date < CURDATE() AND status != 'completed' THEN 1 END) as overdue_projects
            FROM projects 
            WHERE 1=1
        ";
        
        $whereClause = $this->buildWhereClause('projects');
        if ($whereClause && !empty($whereClause['clause'])) {
            $query .= " AND " . $whereClause['clause'];
            $query .= " GROUP BY risk_level ORDER BY FIELD(risk_level, 'low', 'medium', 'high', 'critical')";
            return executeQuery($query, $whereClause['params']);
        }
        
        $query .= " GROUP BY risk_level ORDER BY FIELD(risk_level, 'low', 'medium', 'high', 'critical')";
        return executeQuery($query) ?: [];
    }
    
    /**
     * Generate Executive Charts Data
     */
    private function generateExecutiveCharts() {
        return [
            'project_status_distribution' => $this->getProjectStatusDistribution(),
            'budget_vs_actual' => $this->getBudgetVsActualChart(),
            'timeline_performance' => $this->getTimelineChart()
        ];
    }
    
    /**
     * Get Project Status Distribution for Charts
     */
    private function getProjectStatusDistribution() {
        $query = "
            SELECT 
                status,
                COUNT(*) as count
            FROM projects 
            GROUP BY status
            ORDER BY FIELD(status, 'planning', 'in_progress', 'testing', 'completed', 'on_hold')
        ";
        
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Budget vs Actual Chart Data
     */
    private function getBudgetVsActualChart() {
        $query = "
            SELECT 
                name,
                estimated_cost,
                actual_cost
            FROM projects 
            WHERE estimated_cost > 0
            ORDER BY estimated_cost DESC
            LIMIT 10
        ";
        
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Timeline Chart Data
     */
    private function getTimelineChart() {
        $query = "
            SELECT 
                name,
                start_date,
                due_date,
                progress_percentage
            FROM projects 
            WHERE start_date IS NOT NULL AND due_date IS NOT NULL
            ORDER BY start_date
        ";
        
        return executeQuery($query) ?: [];
    }
    
    /**
     * Get Project Info
     */
    private function getProjectInfo($projectId) {
        $query = "SELECT * FROM projects WHERE id = ?";
        return executeQuerySingle($query, [$projectId]);
    }
    
    /**
     * Build dynamic WHERE clause from filters
     */
    private function buildWhereClause($table, $alias = '') {
        $conditions = [];
        $params = [];
        $prefix = $alias ? $alias . '.' : '';
        
        if (!empty($this->filters['start_date'])) {
            $conditions[] = $prefix . "created_at >= ?";
            $params[] = $this->filters['start_date'];
        }
        
        if (!empty($this->filters['end_date'])) {
            $conditions[] = $prefix . "created_at <= ?";
            $params[] = $this->filters['end_date'] . ' 23:59:59';
        }
        
        if (!empty($this->filters['status'])) {
            $conditions[] = $prefix . "status = ?";
            $params[] = $this->filters['status'];
        }
        
        if (!empty($this->filters['priority'])) {
            $conditions[] = $prefix . "priority = ?";
            $params[] = $this->filters['priority'];
        }
        
        if (!empty($this->filters['project_id'])) {
            if ($table === 'tasks') {
                $conditions[] = $prefix . "project_id = ?";
            } else {
                $conditions[] = $prefix . "id = ?";
            }
            $params[] = $this->filters['project_id'];
        }
        
        return [
            'clause' => implode(' AND ', $conditions),
            'params' => $params
        ];
    }
    
    /**
     * Get report template by ID
     */
    private function getReportTemplate($templateId) {
        $query = "SELECT * FROM report_templates WHERE id = ?";
        return executeQuerySingle($query, [$templateId]);
    }
    
    /**
     * Save report generation history
     */
    public function saveReportHistory($templateId, $reportName, $filters, $exportFormat, $filePath, $generationTime, $status = 'completed', $errorMessage = null) {
        $query = "
            INSERT INTO report_history (
                template_id, report_name, filters_used, export_format, 
                file_path, generation_time_seconds, status, error_message, generated_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $currentUser = getCurrentUser();
        $userId = $currentUser ? $currentUser['id'] : null;
        
        return executeUpdate($query, [
            $templateId,
            $reportName,
            json_encode($filters),
            $exportFormat,
            $filePath,
            $generationTime,
            $status,
            $errorMessage,
            $userId
        ]);
    }
    
    /**
     * Get available report templates
     */
    public function getAvailableTemplates($category = null, $includeCustom = true) {
        $query = "
            SELECT t.*, u.first_name, u.last_name 
            FROM report_templates t
            LEFT JOIN users u ON t.created_by = u.id
            WHERE (t.is_public = 1 OR t.created_by = ?)
        ";
        
        $params = [getCurrentUser()['id'] ?? 0];
        
        if ($category) {
            $query .= " AND t.category = ?";
            $params[] = $category;
        }
        
        if (!$includeCustom) {
            $query .= " AND t.is_system_template = 1";
        }
        
        $query .= " ORDER BY t.is_system_template DESC, t.category, t.name";
        
        return executeQuery($query, $params) ?: [];
    }
}
?>