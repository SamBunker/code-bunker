<?php
/**
 * Database Recovery and Setup Script
 * Use this to fix database corruption and set up a clean database
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Recovery and Setup</h1>";

function connectToMySQLServer() {
    try {
        $pdo = new PDO("mysql:host=localhost;port=3306;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

function createCleanDatabase() {
    $pdo = connectToMySQLServer();
    if (!$pdo) {
        echo "❌ Cannot connect to MySQL server. Make sure XAMPP MySQL is running.<br>";
        return false;
    }
    
    try {
        // Drop existing database if it exists
        echo "<h2>Step 1: Cleaning up existing database</h2>";
        $pdo->exec("DROP DATABASE IF EXISTS web_app_tracker");
        echo "✅ Dropped existing database (if it existed)<br>";
        
        // Create new database
        echo "<h2>Step 2: Creating fresh database</h2>";
        $pdo->exec("CREATE DATABASE web_app_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Created new database 'web_app_tracker'<br>";
        
        // Select the new database
        $pdo->exec("USE web_app_tracker");
        
        return $pdo;
    } catch (PDOException $e) {
        echo "❌ Error creating database: " . $e->getMessage() . "<br>";
        return false;
    }
}

function createTables($pdo) {
    echo "<h2>Step 3: Creating database tables</h2>";
    
    $tables = [
        'users' => "
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role ENUM('admin', 'user') DEFAULT 'user',
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                INDEX idx_username (username),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'projects' => "
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
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_status (status),
                INDEX idx_priority (priority),
                INDEX idx_due_date (due_date),
                INDEX idx_category (category)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'project_phases' => "
            CREATE TABLE project_phases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                order_index INT DEFAULT 0,
                is_collapsed BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                INDEX idx_project_id (project_id),
                INDEX idx_order (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'tasks' => "
            CREATE TABLE tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                phase_id INT,
                order_index INT DEFAULT 0,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                task_type VARCHAR(100) DEFAULT 'General',
                priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
                status ENUM('pending', 'in_progress', 'testing', 'completed', 'blocked') DEFAULT 'pending',
                assigned_to INT,
                depends_on_task_id INT,
                estimated_hours DECIMAL(5,2) DEFAULT 0,
                actual_hours DECIMAL(5,2) DEFAULT 0,
                start_date DATE,
                due_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (phase_id) REFERENCES project_phases(id) ON DELETE CASCADE,
                FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (depends_on_task_id) REFERENCES tasks(id) ON DELETE SET NULL,
                INDEX idx_project_id (project_id),
                INDEX idx_phase_id (phase_id),
                INDEX idx_order_index (order_index),
                INDEX idx_status (status),
                INDEX idx_priority (priority),
                INDEX idx_due_date (due_date),
                INDEX idx_assigned_to (assigned_to)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'notes' => "
            CREATE TABLE notes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
                task_id INT NULL,
                user_id INT NOT NULL,
                title VARCHAR(255),
                content TEXT NOT NULL,
                note_type ENUM('general', 'technical', 'meeting', 'decision') DEFAULT 'general',
                is_private BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
                FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_project_id (project_id),
                INDEX idx_task_id (task_id),
                INDEX idx_user_id (user_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'settings' => "
            CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type ENUM('boolean', 'string', 'number', 'json') DEFAULT 'string',
                description TEXT,
                is_editable BOOLEAN DEFAULT TRUE,
                category VARCHAR(50) DEFAULT 'general',
                updated_by INT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_setting_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'activity_log' => "
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
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_activity_log_created_at_desc (created_at DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'project_templates' => "
            CREATE TABLE project_templates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100) DEFAULT 'General',
                default_priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
                estimated_duration_days INT DEFAULT 0,
                estimated_hours DECIMAL(6,2) DEFAULT 0,
                created_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_active BOOLEAN DEFAULT TRUE,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_name (name),
                INDEX idx_category (category),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'template_phases' => "
            CREATE TABLE template_phases (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                order_index INT DEFAULT 0,
                is_collapsed BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (template_id) REFERENCES project_templates(id) ON DELETE CASCADE,
                INDEX idx_template_id (template_id),
                INDEX idx_order (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            
        'template_tasks' => "
            CREATE TABLE template_tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                template_id INT NOT NULL,
                phase_id INT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                task_type VARCHAR(100) DEFAULT 'General',
                priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
                estimated_hours DECIMAL(5,2) DEFAULT 0,
                order_index INT DEFAULT 0,
                depends_on_template_task_id INT NULL,
                days_after_start INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (template_id) REFERENCES project_templates(id) ON DELETE CASCADE,
                FOREIGN KEY (phase_id) REFERENCES template_phases(id) ON DELETE CASCADE,
                FOREIGN KEY (depends_on_template_task_id) REFERENCES template_tasks(id) ON DELETE SET NULL,
                INDEX idx_template_id (template_id),
                INDEX idx_phase_id (phase_id),
                INDEX idx_order_index (order_index)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    try {
        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
            echo "✅ Created table '$tableName'<br>";
        }
        return true;
    } catch (PDOException $e) {
        echo "❌ Error creating tables: " . $e->getMessage() . "<br>";
        return false;
    }
}

function insertSampleData($pdo) {
    echo "<h2>Step 4: Adding sample data</h2>";
    
    try {
        // Create admin user
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role, first_name, last_name) 
            VALUES (?, ?, ?, 'admin', 'Admin', 'User')
        ")->execute(['admin', 'admin@example.com', $hashedPassword]);
        echo "✅ Created admin user (username: admin, password: admin123)<br>";
        
        // Create regular user
        $hashedPassword = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (username, email, password_hash, role, first_name, last_name) 
            VALUES (?, ?, ?, 'user', 'Unassigned', 'User')
        ")->execute(['user', 'user@example.com', $hashedPassword]);
        echo "✅ Created regular user (username: user, password: user123)<br>";
        
        // Add sample project
        $pdo->prepare("
            INSERT INTO projects (name, description, category, priority, status, created_by, assigned_to, start_date, due_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            'Sample Web Application Update', 
            'Modernize legacy web application with latest security patches',
            'Web Application',
            'high',
            'in_progress',
            1, // admin user
            2, // regular user
            date('Y-m-d'),
            date('Y-m-d', strtotime('+30 days'))
        ]);
        echo "✅ Created sample project<br>";
        
        // Add sample task
        $pdo->prepare("
            INSERT INTO tasks (project_id, title, description, priority, status, assigned_to, due_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            1, // project id
            'Update PHP to latest version',
            'Upgrade PHP from 7.4 to 8.2 for security and performance improvements',
            'high',
            'in_progress',
            2, // regular user
            date('Y-m-d', strtotime('+7 days'))
        ]);
        echo "✅ Created sample task<br>";
        
        // Add default settings
        $defaultSettings = [
            ['enable_budget_tracking', 'false', 'boolean', 'Enable budget tracking for projects and reporting', 'features'],
            ['enable_time_tracking', 'true', 'boolean', 'Enable time tracking for tasks', 'features'],
            ['enable_notifications', 'true', 'boolean', 'Enable email notifications for task updates', 'features'],
            ['default_priority', 'medium', 'string', 'Default priority for new projects and tasks', 'defaults'],
            ['items_per_page', '25', 'number', 'Number of items to display per page in lists', 'general']
        ];
        
        foreach ($defaultSettings as $setting) {
            $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_type, description, category, updated_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$setting[0], $setting[1], $setting[2], $setting[3], $setting[4], 1]);
        }
        echo "✅ Added default settings<br>";
        
        // Add sample activity log entries
        $sampleActivities = [
            [1, 'login', 'user', 1, 'User logged in', '127.0.0.1', 'NOW() - INTERVAL 5 MINUTE'],
            [1, 'create', 'project', 1, 'Created new project', '127.0.0.1', 'NOW() - INTERVAL 3 MINUTE'],
            [1, 'update', 'task', 1, 'Updated task status', '127.0.0.1', 'NOW() - INTERVAL 1 MINUTE']
        ];
        
        foreach ($sampleActivities as $activity) {
            $pdo->prepare("
                INSERT INTO activity_log (user_id, action, entity_type, entity_id, description, ip_address, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, {$activity[6]})
            ")->execute([$activity[0], $activity[1], $activity[2], $activity[3], $activity[4], $activity[5]]);
        }
        echo "✅ Added sample activity log entries<br>";
        
        // Add sample project templates
        $sampleTemplates = [
            [
                'name' => 'Web Application Update',
                'description' => 'Standard web application modernization and security update template',
                'category' => 'Web Development',
                'default_priority' => 'high',
                'estimated_duration_days' => 30,
                'estimated_hours' => 120,
                'created_by' => 1
            ],
            [
                'name' => 'Mobile App Development',
                'description' => 'Complete mobile application development lifecycle template',
                'category' => 'Mobile Development',
                'default_priority' => 'medium',
                'estimated_duration_days' => 90,
                'estimated_hours' => 360,
                'created_by' => 1
            ]
        ];
        
        foreach ($sampleTemplates as $template) {
            $pdo->prepare("
                INSERT INTO project_templates (name, description, category, default_priority, estimated_duration_days, estimated_hours, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([$template['name'], $template['description'], $template['category'], $template['default_priority'], $template['estimated_duration_days'], $template['estimated_hours'], $template['created_by']]);
        }
        echo "✅ Added sample project templates<br>";
        
        // Add sample template phases for Web Application Update template
        $webAppPhases = [
            ['template_id' => 1, 'name' => 'Planning & Assessment', 'description' => 'Initial analysis and planning phase', 'order_index' => 1],
            ['template_id' => 1, 'name' => 'Development & Updates', 'description' => 'Core development and system updates', 'order_index' => 2],
            ['template_id' => 1, 'name' => 'Testing & Deployment', 'description' => 'Quality assurance and production deployment', 'order_index' => 3]
        ];
        
        foreach ($webAppPhases as $phase) {
            $pdo->prepare("
                INSERT INTO template_phases (template_id, name, description, order_index) 
                VALUES (?, ?, ?, ?)
            ")->execute([$phase['template_id'], $phase['name'], $phase['description'], $phase['order_index']]);
        }
        echo "✅ Added sample template phases<br>";
        
        // Add sample template tasks for Web Application Update template (clean titles, WBS shown in interface)
        $webAppTasks = [
            ['phase_id' => 1, 'title' => 'Security Assessment', 'description' => 'Perform comprehensive security audit', 'task_type' => 'Security Updates', 'priority' => 'critical', 'estimated_hours' => 16, 'order_index' => 1, 'days_after_start' => 0],
            ['phase_id' => 1, 'title' => 'Requirements Analysis', 'description' => 'Analyze current system and define upgrade requirements', 'task_type' => 'Documentation Updates', 'priority' => 'high', 'estimated_hours' => 12, 'order_index' => 2, 'days_after_start' => 1],
            ['phase_id' => 2, 'title' => 'Update Framework Dependencies', 'description' => 'Update all framework and library dependencies to latest secure versions', 'task_type' => 'Version Upgrades', 'priority' => 'high', 'estimated_hours' => 24, 'order_index' => 1, 'days_after_start' => 3],
            ['phase_id' => 2, 'title' => 'Database Migration', 'description' => 'Update database schema and migrate data', 'task_type' => 'Version Upgrades', 'priority' => 'high', 'estimated_hours' => 20, 'order_index' => 2, 'days_after_start' => 7],
            ['phase_id' => 2, 'title' => 'UI/UX Modernization', 'description' => 'Update user interface with modern design patterns', 'task_type' => 'UI/UX Improvements', 'priority' => 'medium', 'estimated_hours' => 32, 'order_index' => 3, 'days_after_start' => 10],
            ['phase_id' => 2, 'title' => 'Performance Optimization', 'description' => 'Optimize application performance and loading times', 'task_type' => 'Performance Optimization', 'priority' => 'medium', 'estimated_hours' => 16, 'order_index' => 4, 'days_after_start' => 15],
            ['phase_id' => 3, 'title' => 'Testing & QA', 'description' => 'Comprehensive testing including unit, integration, and user acceptance tests', 'task_type' => 'Testing', 'priority' => 'high', 'estimated_hours' => 24, 'order_index' => 1, 'days_after_start' => 20],
            ['phase_id' => 3, 'title' => 'Documentation Update', 'description' => 'Update user and technical documentation', 'task_type' => 'Documentation Updates', 'priority' => 'medium', 'estimated_hours' => 8, 'order_index' => 2, 'days_after_start' => 25],
            ['phase_id' => 3, 'title' => 'Production Deployment', 'description' => 'Deploy updated application to production environment', 'task_type' => 'Deployment', 'priority' => 'critical', 'estimated_hours' => 12, 'order_index' => 3, 'days_after_start' => 27]
        ];
        
        foreach ($webAppTasks as $task) {
            $pdo->prepare("
                INSERT INTO template_tasks (template_id, phase_id, title, description, task_type, priority, estimated_hours, order_index, days_after_start) 
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$task['phase_id'], $task['title'], $task['description'], $task['task_type'], $task['priority'], $task['estimated_hours'], $task['order_index'], $task['days_after_start']]);
        }
        echo "✅ Added sample template tasks with phases<br>";
        
        // Add sample project phase for existing project
        $pdo->prepare("
            INSERT INTO project_phases (project_id, name, description, order_index) 
            VALUES (1, 'Implementation Phase', 'Main development and implementation tasks', 1)
        ")->execute();
        echo "✅ Added sample project phase<br>";
        
        // Update existing task to be in the phase
        $pdo->prepare("UPDATE tasks SET phase_id = 1 WHERE id = 1")->execute();
        echo "✅ Updated existing task to be in phase<br>";
        
        return true;
    } catch (PDOException $e) {
        echo "❌ Error inserting sample data: " . $e->getMessage() . "<br>";
        return false;
    }
}

function fixExistingDatabase() {
    echo "<h2>🔧 Applying Database Fixes to Existing Database</h2>";
    
    try {
        $pdo = new PDO("mysql:host=localhost;port=3306;dbname=web_app_tracker;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Check if order_index column exists in tasks table
        $result = $pdo->query("SHOW COLUMNS FROM tasks LIKE 'order_index'");
        if ($result->rowCount() == 0) {
            echo "Adding missing order_index column to tasks table...<br>";
            $pdo->exec("ALTER TABLE tasks ADD COLUMN order_index INT DEFAULT 0 AFTER phase_id");
            $pdo->exec("ALTER TABLE tasks ADD INDEX idx_order_index (order_index)");
            echo "✅ Added order_index column to tasks table<br>";
        } else {
            echo "✅ order_index column already exists in tasks table<br>";
        }
        
        // Check if setting_type column exists in settings table
        $result = $pdo->query("SHOW COLUMNS FROM settings LIKE 'setting_type'");
        if ($result->rowCount() == 0) {
            echo "Adding missing setting_type column to settings table...<br>";
            $pdo->exec("ALTER TABLE settings ADD COLUMN setting_type ENUM('boolean', 'string', 'number', 'json') DEFAULT 'string' AFTER setting_value");
            echo "✅ Added setting_type column to settings table<br>";
        } else {
            echo "✅ setting_type column already exists in settings table<br>";
        }
        
        // Check if activity_log table exists
        $result = $pdo->query("SHOW TABLES LIKE 'activity_log'");
        if ($result->rowCount() == 0) {
            echo "Creating missing activity_log table...<br>";
            $pdo->exec("
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
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_activity_log_created_at_desc (created_at DESC)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ Created activity_log table<br>";
        } else {
            echo "✅ activity_log table already exists<br>";
        }
        
        // Check if project_templates table exists
        $result = $pdo->query("SHOW TABLES LIKE 'project_templates'");
        if ($result->rowCount() == 0) {
            echo "Creating missing project_templates table...<br>";
            $pdo->exec("
                CREATE TABLE project_templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    category VARCHAR(100) DEFAULT 'General',
                    default_priority ENUM('critical', 'high', 'medium', 'low') DEFAULT 'medium',
                    estimated_duration_days INT DEFAULT 0,
                    estimated_hours DECIMAL(6,2) DEFAULT 0,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX idx_name (name),
                    INDEX idx_category (category),
                    INDEX idx_is_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ Created project_templates table<br>";
        } else {
            echo "✅ project_templates table already exists<br>";
        }
        
        // Check if template_phases table exists
        $result = $pdo->query("SHOW TABLES LIKE 'template_phases'");
        if ($result->rowCount() == 0) {
            echo "Creating missing template_phases table...<br>";
            $pdo->exec("
                CREATE TABLE template_phases (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    template_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    order_index INT DEFAULT 0,
                    is_collapsed BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (template_id) REFERENCES project_templates(id) ON DELETE CASCADE,
                    INDEX idx_template_id (template_id),
                    INDEX idx_order (order_index)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ Created template_phases table<br>";
        } else {
            echo "✅ template_phases table already exists<br>";
        }
        
        // Check if template_tasks table has phase_id column
        $result = $pdo->query("SHOW COLUMNS FROM template_tasks LIKE 'phase_id'");
        if ($result->rowCount() == 0) {
            echo "Adding missing phase_id column to template_tasks table...<br>";
            $pdo->exec("ALTER TABLE template_tasks ADD COLUMN phase_id INT AFTER template_id");
            $pdo->exec("ALTER TABLE template_tasks ADD FOREIGN KEY (phase_id) REFERENCES template_phases(id) ON DELETE CASCADE");
            $pdo->exec("ALTER TABLE template_tasks ADD INDEX idx_phase_id (phase_id)");
            echo "✅ Added phase_id column to template_tasks table<br>";
        } else {
            echo "✅ phase_id column already exists in template_tasks table<br>";
        }
        
        // Check if template_tasks table has order_index column
        $result = $pdo->query("SHOW COLUMNS FROM template_tasks LIKE 'order_index'");
        if ($result->rowCount() == 0) {
            echo "Adding missing order_index column to template_tasks table...<br>";
            $pdo->exec("ALTER TABLE template_tasks ADD COLUMN order_index INT DEFAULT 0");
            $pdo->exec("ALTER TABLE template_tasks ADD INDEX idx_order_index (order_index)");
            echo "✅ Added order_index column to template_tasks table<br>";
        } else {
            echo "✅ order_index column already exists in template_tasks table<br>";
        }
        
        // Check if report_templates table exists
        $result = $pdo->query("SHOW TABLES LIKE 'report_templates'");
        if ($result->rowCount() == 0) {
            echo "Creating missing report_templates table...<br>";
            $pdo->exec("
                CREATE TABLE report_templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    category ENUM('executive', 'operational', 'financial', 'resource', 'custom', 'wbs') DEFAULT 'custom',
                    report_type VARCHAR(100) NOT NULL,
                    template_config JSON NOT NULL,
                    filters_config JSON,
                    is_public BOOLEAN DEFAULT FALSE,
                    is_system_template BOOLEAN DEFAULT FALSE,
                    created_by INT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id),
                    INDEX idx_category (category),
                    INDEX idx_report_type (report_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ Created report_templates table<br>";
        } else {
            echo "✅ report_templates table already exists<br>";
        }
        
        // Check if work_breakdown_structure table exists
        $result = $pdo->query("SHOW TABLES LIKE 'work_breakdown_structure'");
        if ($result->rowCount() == 0) {
            echo "Creating missing work_breakdown_structure table...<br>";
            $pdo->exec("
                CREATE TABLE work_breakdown_structure (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    project_id INT NOT NULL,
                    parent_id INT NULL,
                    wbs_code VARCHAR(50) NOT NULL,
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✅ Created work_breakdown_structure table<br>";
        } else {
            echo "✅ work_breakdown_structure table already exists<br>";
        }
        
        // Check if projects table has enhanced reporting columns
        $enhancedColumns = [
            'estimated_cost' => 'DECIMAL(12,2) DEFAULT 0',
            'actual_cost' => 'DECIMAL(12,2) DEFAULT 0',
            'progress_percentage' => 'DECIMAL(5,2) DEFAULT 0',
            'health_status' => "ENUM('green', 'yellow', 'red') DEFAULT 'green'",
            'risk_level' => "ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low'"
        ];
        
        foreach ($enhancedColumns as $column => $definition) {
            $result = $pdo->query("SHOW COLUMNS FROM projects LIKE '$column'");
            if ($result->rowCount() == 0) {
                echo "Adding missing $column column to projects table...<br>";
                $pdo->exec("ALTER TABLE projects ADD COLUMN $column $definition");
                echo "✅ Added $column column to projects table<br>";
            }
        }
        
        // Add indexes for the new columns if they don't exist
        try {
            $pdo->exec("ALTER TABLE projects ADD INDEX idx_health_status (health_status)");
            echo "✅ Added health_status index to projects table<br>";
        } catch (Exception $e) {
            // Index might already exist, that's ok
        }
        
        try {
            $pdo->exec("ALTER TABLE projects ADD INDEX idx_progress (progress_percentage)");
            echo "✅ Added progress index to projects table<br>";
        } catch (Exception $e) {
            // Index might already exist, that's ok
        }
        
        // Insert system report templates
        $result = $pdo->query("SELECT COUNT(*) as count FROM report_templates WHERE is_system_template = 1");
        $systemTemplateCount = $result->fetch()['count'];
        
        if ($systemTemplateCount == 0) {
            echo "Adding system report templates...<br>";
            $systemTemplates = [
                [
                    'Executive Dashboard',
                    'High-level overview for executives and stakeholders',
                    'executive',
                    'executive_dashboard',
                    '{"metrics": ["total_projects", "budget_utilization", "schedule_performance", "team_productivity"], "charts": ["project_status_pie", "budget_trend_line", "milestone_timeline"], "format": "dashboard"}'
                ],
                [
                    'Work Breakdown Structure',
                    'Hierarchical project breakdown with progress tracking',
                    'wbs',
                    'work_breakdown_structure', 
                    '{"display_type": "hierarchical", "show_progress": true, "show_costs": true, "show_timeline": true, "export_formats": ["pdf", "excel"]}'
                ]
            ];
            
            foreach ($systemTemplates as $template) {
                $pdo->prepare("
                    INSERT INTO report_templates (name, description, category, report_type, template_config, is_public, is_system_template, created_by) 
                    VALUES (?, ?, ?, ?, ?, TRUE, TRUE, 1)
                ")->execute($template);
            }
            echo "✅ Added system report templates<br>";
        } else {
            echo "✅ System report templates already exist<br>";
        }
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3>✅ Database Fixes Applied Successfully!</h3>";
        echo "<p>Your existing database has been updated with the missing components.</p>";
        echo "</div>";
        
        return true;
    } catch (PDOException $e) {
        echo "❌ Error applying database fixes: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Main execution
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

if (isset($_POST['fix_existing'])) {
    fixExistingDatabase();
} elseif (isset($_POST['fix_database'])) {
    echo "<h2>🔧 Starting Database Recovery Process</h2>";
    
    $pdo = createCleanDatabase();
    if ($pdo) {
        if (createTables($pdo)) {
            if (insertSampleData($pdo)) {
                echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                echo "<h3>✅ Database Recovery Complete!</h3>";
                echo "<p><strong>Next steps:</strong></p>";
                echo "<ol>";
                echo "<li>Test the connection with <a href='test_db.php'>test_db.php</a></li>";
                echo "<li>Try accessing the <a href='pages/dashboard.php'>dashboard</a></li>";
                echo "<li>Login with: admin / admin123 or user / user123</li>";
                echo "</ol>";
                echo "</div>";
            }
        }
    }
} else {
    // Show the form
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Database Corruption Detected</h3>";
    echo "<p>The MariaDB logs show InnoDB corruption. This script will:</p>";
    echo "<ol>";
    echo "<li>Drop the existing corrupted database</li>";
    echo "<li>Create a fresh 'web_app_tracker' database</li>";
    echo "<li>Set up all required tables</li>";
    echo "<li>Add sample data for testing</li>";
    echo "</ol>";
    echo "<p><strong>Warning:</strong> This will delete all existing data!</p>";
    echo "</div>";
    
    echo "<div style='background: #e2e3e5; border: 1px solid #d6d8db; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>💡 Choose Your Fix Option:</h4>";
    echo "<p><strong>Option 1:</strong> Fix existing database (preserves your data, just adds missing columns/tables)</p>";
    echo "<p><strong>Option 2:</strong> Complete database rebuild (deletes all data, creates fresh database with sample data)</p>";
    echo "</div>";
    
    echo "<form method='POST' style='margin-bottom: 10px;'>";
    echo "<button type='submit' name='fix_existing' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-right: 10px;'>🔧 Fix Existing Database (Recommended)</button>";
    echo "</form>";
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='fix_database' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🗑️ Complete Database Rebuild (Deletes All Data)</button>";
    echo "</form>";
}

echo "</div>";
?>