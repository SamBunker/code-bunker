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
            
        'tasks' => "
            CREATE TABLE tasks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT NOT NULL,
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
                FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (depends_on_task_id) REFERENCES tasks(id) ON DELETE SET NULL,
                INDEX idx_project_id (project_id),
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
            VALUES (?, ?, ?, 'user', 'John', 'Doe')
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
        
        return true;
    } catch (PDOException $e) {
        echo "❌ Error inserting sample data: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Main execution
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

if (isset($_POST['fix_database'])) {
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
    
    echo "<form method='POST'>";
    echo "<button type='submit' name='fix_database' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🔧 Fix Database Now</button>";
    echo "</form>";
}

echo "</div>";
?>