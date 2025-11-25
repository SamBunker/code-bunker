-- Enhanced Reporting System Database Schema
-- Code Bunker - Advanced Reporting Features

-- Report Templates Table
CREATE TABLE IF NOT EXISTS report_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('executive', 'operational', 'financial', 'resource', 'custom', 'wbs') DEFAULT 'custom',
    report_type VARCHAR(100) NOT NULL,
    template_config JSON NOT NULL,
    filters_config JSON,
    layout_config JSON,
    chart_config JSON,
    is_public BOOLEAN DEFAULT FALSE,
    is_system_template BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_category (category),
    INDEX idx_report_type (report_type),
    INDEX idx_public (is_public),
    INDEX idx_system (is_system_template)
);

-- Scheduled Reports Table
CREATE TABLE IF NOT EXISTS scheduled_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    schedule_type ENUM('once', 'daily', 'weekly', 'monthly', 'quarterly') DEFAULT 'weekly',
    schedule_cron VARCHAR(100),
    schedule_day_of_week TINYINT, -- 0-6 for weekly
    schedule_day_of_month TINYINT, -- 1-31 for monthly
    recipients JSON NOT NULL, -- Array of email addresses
    export_formats JSON NOT NULL, -- Array: ['pdf', 'csv', 'excel']
    filters_override JSON,
    is_active BOOLEAN DEFAULT TRUE,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP NULL,
    run_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_active (is_active),
    INDEX idx_next_run (next_run)
);

-- Report Generation History
CREATE TABLE IF NOT EXISTS report_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    template_id INT,
    scheduled_report_id INT NULL,
    report_name VARCHAR(255) NOT NULL,
    filters_used JSON,
    export_format VARCHAR(20) NOT NULL,
    file_path VARCHAR(500),
    file_size INT,
    generation_time_seconds DECIMAL(8,3),
    status ENUM('pending', 'generating', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (scheduled_report_id) REFERENCES scheduled_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_generated_at (generated_at),
    INDEX idx_template (template_id)
);

-- Custom Report Fields (for flexible reporting)
CREATE TABLE IF NOT EXISTS custom_report_fields (
    id INT PRIMARY KEY AUTO_INCREMENT,
    field_name VARCHAR(100) NOT NULL,
    field_label VARCHAR(255) NOT NULL,
    data_source ENUM('projects', 'tasks', 'users', 'notes', 'calculated') NOT NULL,
    field_type ENUM('string', 'number', 'date', 'boolean', 'enum') NOT NULL,
    calculation_formula TEXT, -- For calculated fields
    field_options JSON, -- For enum fields
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data_source (data_source),
    INDEX idx_active (is_active),
    INDEX idx_display_order (display_order)
);

-- Work Breakdown Structure table for hierarchical reporting
CREATE TABLE IF NOT EXISTS work_breakdown_structure (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    parent_id INT NULL, -- Self-referencing for hierarchy
    wbs_code VARCHAR(50) NOT NULL, -- e.g., "1.2.3.1"
    name VARCHAR(255) NOT NULL,
    description TEXT,
    work_package_type ENUM('project', 'deliverable', 'work_package', 'task') DEFAULT 'work_package',
    estimated_hours DECIMAL(8,2) DEFAULT 0,
    actual_hours DECIMAL(8,2) DEFAULT 0,
    estimated_cost DECIMAL(12,2) DEFAULT 0,
    actual_cost DECIMAL(12,2) DEFAULT 0,
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('not_started', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'not_started',
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    assigned_to INT,
    start_date DATE,
    due_date DATE,
    completion_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES work_breakdown_structure(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_project_id (project_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_wbs_code (wbs_code),
    INDEX idx_status (status),
    UNIQUE KEY unique_wbs_code_per_project (project_id, wbs_code)
);

-- Enhanced project tracking for better reporting
ALTER TABLE projects 
ADD COLUMN IF NOT EXISTS estimated_cost DECIMAL(12,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS actual_cost DECIMAL(12,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS progress_percentage DECIMAL(5,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS health_status ENUM('green', 'yellow', 'red') DEFAULT 'green',
ADD COLUMN IF NOT EXISTS risk_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
ADD COLUMN IF NOT EXISTS project_manager_id INT,
ADD COLUMN IF NOT EXISTS client_name VARCHAR(255),
ADD COLUMN IF NOT EXISTS baseline_start_date DATE,
ADD COLUMN IF NOT EXISTS baseline_end_date DATE,
ADD COLUMN IF NOT EXISTS baseline_budget DECIMAL(12,2) DEFAULT 0,
ADD INDEX idx_health_status (health_status),
ADD INDEX idx_risk_level (risk_level),
ADD INDEX idx_progress (progress_percentage);

-- Enhanced task tracking
ALTER TABLE tasks 
ADD COLUMN IF NOT EXISTS baseline_hours DECIMAL(8,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS progress_percentage DECIMAL(5,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS billable_hours DECIMAL(8,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(8,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS task_category VARCHAR(100),
ADD COLUMN IF NOT EXISTS requires_approval BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS approved_by INT,
ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL,
ADD INDEX idx_task_category (task_category),
ADD INDEX idx_progress (progress_percentage);

-- Insert system report templates
INSERT INTO report_templates (name, description, category, report_type, template_config, is_public, is_system_template, created_by) 
VALUES 
(
    'Executive Dashboard',
    'High-level overview for executives and stakeholders',
    'executive',
    'executive_dashboard',
    '{"metrics": ["total_projects", "budget_utilization", "schedule_performance", "team_productivity"], "charts": ["project_status_pie", "budget_trend_line", "milestone_timeline"], "format": "dashboard"}',
    TRUE,
    TRUE,
    1
),
(
    'Work Breakdown Structure',
    'Hierarchical project breakdown with progress tracking',
    'wbs',
    'work_breakdown_structure', 
    '{"display_type": "hierarchical", "show_progress": true, "show_costs": true, "show_timeline": true, "export_formats": ["pdf", "excel"]}',
    TRUE,
    TRUE,
    1
),
(
    'Resource Utilization Report',
    'Team workload and capacity analysis',
    'resource',
    'resource_utilization',
    '{"metrics": ["utilization_rate", "capacity_vs_demand", "skills_matrix"], "grouping": "by_team_member", "time_period": "monthly"}',
    TRUE,
    TRUE,
    1
),
(
    'Budget Performance Analysis',
    'Financial tracking and variance analysis',
    'financial',
    'budget_analysis',
    '{"metrics": ["budget_vs_actual", "cost_variance", "burn_rate", "forecasted_completion"], "charts": ["budget_trend", "cost_breakdown"]}',
    TRUE,
    TRUE,
    1
),
(
    'Project Health Dashboard',
    'Real-time project health monitoring',
    'operational',
    'project_health',
    '{"indicators": ["schedule_health", "budget_health", "quality_health", "team_health"], "alerts": true, "threshold_settings": {"red": 25, "yellow": 50}}',
    TRUE,
    TRUE,
    1
);

-- Insert custom report fields
INSERT INTO custom_report_fields (field_name, field_label, data_source, field_type, display_order)
VALUES 
('project_roi', 'Return on Investment', 'calculated', 'number', 10),
('team_velocity', 'Team Velocity (tasks/week)', 'calculated', 'number', 20),
('quality_score', 'Quality Score', 'calculated', 'number', 30),
('client_satisfaction', 'Client Satisfaction', 'projects', 'enum', 40),
('technical_debt_hours', 'Technical Debt (Hours)', 'calculated', 'number', 50);