-- Web Application Modernization Tracker Database Schema
-- Created for XAMPP/MySQL environment

CREATE DATABASE IF NOT EXISTS web_app_tracker;
USE web_app_tracker;

-- Users table for authentication and user management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Projects table for tracking web applications and modernization efforts
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100) DEFAULT 'Web Application',
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('planning', 'in_progress', 'testing', 'completed', 'on_hold') DEFAULT 'planning',
    current_version VARCHAR(50),
    target_version VARCHAR(50),
    start_date DATE,
    due_date DATE,
    completion_date DATE NULL,
    estimated_hours DECIMAL(6,2) DEFAULT 0,
    actual_hours DECIMAL(6,2) DEFAULT 0,
    budget DECIMAL(10,2),
    created_by INT NOT NULL,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_due_date (due_date),
    INDEX idx_category (category)
);

-- Tasks table for specific work items within projects
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    task_type VARCHAR(100) DEFAULT 'General',
    priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'blocked', 'cancelled') DEFAULT 'pending',
    assigned_to INT,
    depends_on_task_id INT NULL,
    estimated_hours DECIMAL(5,2) DEFAULT 0,
    actual_hours DECIMAL(5,2) DEFAULT 0,
    start_date DATE,
    due_date DATE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (depends_on_task_id) REFERENCES tasks(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id),
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_due_date (due_date)
);

-- Notes table for project and task documentation
CREATE TABLE notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    task_id INT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    note_type ENUM('general', 'technical', 'meeting', 'issue', 'solution') DEFAULT 'general',
    is_private BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_project_id (project_id),
    INDEX idx_task_id (task_id),
    INDEX idx_created_at (created_at)
);

-- File attachments table for documents, screenshots, etc.
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    task_id INT NULL,
    note_id INT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100),
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_project_id (project_id),
    INDEX idx_task_id (task_id)
);

-- Activity log table for audit trail
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type ENUM('project', 'task', 'note', 'user') NOT NULL,
    entity_id INT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
);

-- Settings table for application configuration
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('boolean', 'string', 'number', 'json') DEFAULT 'string',
    description TEXT,
    is_editable BOOLEAN DEFAULT TRUE,
    category VARCHAR(50) DEFAULT 'general',
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default admin user (password: admin123)
-- Note: This should be changed immediately after first login
INSERT INTO users (username, email, password_hash, role, first_name, last_name) VALUES
('admin', 'admin@company.com', '$2y$10$vBP2U0kvwdH/YjLPN/hKMeZAUvGgjG/7bpij/dKTQANJowTclUf3y', 'admin', 'System', 'Administrator');

-- Insert default application settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, is_editable, category) VALUES
-- Feature toggles
('enable_budget_tracking', 'false', 'boolean', 'Enable budget tracking for projects and reporting', true, 'features'),
('enable_time_tracking', 'true', 'boolean', 'Enable time tracking for tasks and projects', true, 'features'),
('enable_notifications', 'true', 'boolean', 'Enable system notifications', true, 'features'),
('enable_calendar_view', 'true', 'boolean', 'Enable calendar/timeline view', true, 'features'),
('enable_file_uploads', 'true', 'boolean', 'Enable file attachments for projects/tasks', true, 'features'),

-- Application settings
('app_name', 'Web App Modernization Tracker', 'string', 'Application display name', true, 'general'),
('app_timezone', 'America/New_York', 'string', 'Default application timezone', true, 'general'),
('items_per_page', '20', 'number', 'Default number of items per page', true, 'general'),
('max_file_upload_size', '10', 'number', 'Maximum file upload size in MB', true, 'general'),

-- Data options
('project_categories', '["Web Application", "Mobile Application", "API/Service", "Database", "Infrastructure", "Security", "Documentation"]', 'json', 'Available project categories', true, 'data'),
('task_types', '["Security Update", "Version Upgrade", "Bug Fix", "Feature Enhancement", "Performance Optimization", "UI/UX Improvement", "Documentation", "Testing", "Deployment", "Configuration"]', 'json', 'Available task types', true, 'data'),
('priority_colors', '{"critical": "#dc3545", "high": "#fd7e14", "medium": "#ffc107", "low": "#28a745"}', 'json', 'Color coding for priority levels', true, 'appearance'),
('status_colors', '{"planning": "#6c757d", "in_progress": "#007bff", "testing": "#17a2b8", "completed": "#28a745", "on_hold": "#ffc107", "blocked": "#dc3545", "cancelled": "#6c757d"}', 'json', 'Color coding for status levels', true, 'appearance'),

-- Default tracking preferences
('default_project_priority', 'medium', 'string', 'Default priority for new projects', true, 'defaults'),
('default_task_priority', 'medium', 'string', 'Default priority for new tasks', true, 'defaults'),
('auto_assign_creator', 'true', 'boolean', 'Automatically assign project creator as assignee', true, 'defaults');

-- Insert sample data for demonstration
INSERT INTO projects (name, description, category, priority, status, start_date, due_date, estimated_hours, created_by) VALUES
('Legacy Intranet Portal Upgrade', 'Modernize the employee intranet portal from PHP 5.6 to PHP 8.x with security updates', 'Web Application', 'high', 'in_progress', '2025-08-07', '2025-08-31', 120, 1),
('Customer Support System Migration', 'Migrate customer support system to new framework with improved UI/UX', 'Web Application', 'critical', 'planning', '2025-08-15', '2025-09-30', 200, 1),
('API Security Enhancement', 'Implement OAuth 2.0 and rate limiting for all public APIs', 'API/Service', 'critical', 'in_progress', '2025-08-01', '2025-08-20', 80, 1);

INSERT INTO tasks (project_id, title, description, task_type, priority, status, estimated_hours, due_date) VALUES
(1, 'Update PHP version from 5.6 to 8.x', 'Upgrade PHP runtime and update deprecated functions', 'Version Upgrade', 'high', 'in_progress', 24, '2025-08-12'),
(1, 'Update Bootstrap from v3 to v5', 'Modernize frontend framework and fix responsive issues', 'UI/UX Improvement', 'medium', 'pending', 16, '2025-08-15'),
(1, 'Implement HTTPS and SSL certificate', 'Add SSL certificate and force HTTPS redirects', 'Security Update', 'critical', 'pending', 8, '2025-08-10'),
(2, 'Requirements gathering and analysis', 'Meet with stakeholders to define new system requirements', 'Documentation', 'high', 'completed', 12, '2025-08-08'),
(3, 'Implement OAuth 2.0 authentication', 'Replace basic auth with OAuth 2.0 for better security', 'Security Update', 'critical', 'in_progress', 32, '2025-08-15');

INSERT INTO notes (project_id, user_id, title, content, note_type) VALUES
(1, 1, 'PHP Compatibility Issues', 'Found several deprecated mysql_* functions that need to be updated to PDO or mysqli. List of affected files: login.php, database.php, user_management.php', 'technical'),
(2, 1, 'Stakeholder Meeting Summary', 'Key requirements identified:\n- Single sign-on integration\n- Mobile responsive design\n- Real-time notifications\n- Improved search functionality', 'meeting'),
(3, 1, 'Security Audit Findings', 'Current API endpoints lack proper authentication and rate limiting. Priority items:\n1. Implement OAuth 2.0\n2. Add rate limiting (100 requests/hour per user)\n3. Enable CORS with whitelist', 'issue');

-- Create views for common queries
CREATE VIEW project_summary AS
SELECT 
    p.id,
    p.name,
    p.category,
    p.priority,
    p.status,
    p.start_date,
    p.due_date,
    p.estimated_hours,
    p.actual_hours,
    CONCAT(u1.first_name, ' ', u1.last_name) as created_by_name,
    CONCAT(u2.first_name, ' ', u2.last_name) as assigned_to_name,
    COUNT(t.id) as total_tasks,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    ROUND((SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) / COUNT(t.id)) * 100, 1) as completion_percentage
FROM projects p
LEFT JOIN users u1 ON p.created_by = u1.id
LEFT JOIN users u2 ON p.assigned_to = u2.id
LEFT JOIN tasks t ON p.id = t.project_id
GROUP BY p.id, p.name, p.category, p.priority, p.status, p.start_date, p.due_date, p.estimated_hours, p.actual_hours, u1.first_name, u1.last_name, u2.first_name, u2.last_name;

CREATE VIEW task_summary AS
SELECT 
    t.id,
    t.project_id,
    p.name as project_name,
    t.title,
    t.task_type,
    t.priority,
    t.status,
    t.estimated_hours,
    t.actual_hours,
    t.due_date,
    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
    DATEDIFF(t.due_date, CURDATE()) as days_until_due
FROM tasks t
JOIN projects p ON t.project_id = p.id
LEFT JOIN users u ON t.assigned_to = u.id;

-- Create indexes for better performance
CREATE INDEX idx_projects_status_priority ON projects(status, priority);
CREATE INDEX idx_tasks_status_due_date ON tasks(status, due_date);
CREATE INDEX idx_activity_log_created_at_desc ON activity_log(created_at DESC);

COMMIT;