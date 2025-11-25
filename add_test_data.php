<?php
/**
 * Add Test Data for Calendar Testing
 * This script adds sample projects and tasks with dates spread across different months
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if database connection works
$db = getDatabase();
if (!$db || !$db->testConnection()) {
    die("❌ Database connection failed. Please ensure XAMPP MySQL is running and database exists.");
}

echo "<h1>Adding Test Data for Calendar</h1>";

// Check if we have users first
$users = executeQuery("SELECT id, username FROM users ORDER BY id LIMIT 5");
if (empty($users)) {
    echo "❌ No users found. Please create a user first using setup_admin.php<br>";
    exit;
}

echo "✅ Found " . count($users) . " users<br>";

// Add test projects with various due dates
$testProjects = [
    [
        'name' => 'Legacy PHP Application Modernization',
        'description' => 'Modernize old PHP 5.6 application to PHP 8.x',
        'category' => 'Web Application',
        'priority' => 'high',
        'status' => 'in_progress',
        'start_date' => '2025-08-01',
        'due_date' => '2025-08-15',
        'current_version' => '5.6',
        'target_version' => '8.2'
    ],
    [
        'name' => 'E-commerce Site Security Updates',
        'description' => 'Apply security patches and SSL certificate renewal',
        'category' => 'Security',
        'priority' => 'critical',
        'status' => 'planning',
        'start_date' => '2025-08-10',
        'due_date' => '2025-08-20',
        'current_version' => '1.4.2',
        'target_version' => '1.4.5'
    ],
    [
        'name' => 'Mobile App API Integration',
        'description' => 'Build REST API for mobile app connectivity',
        'category' => 'API Development',
        'priority' => 'medium',
        'status' => 'planning',
        'start_date' => '2025-08-20',
        'due_date' => '2025-09-15',
        'current_version' => '1.0',
        'target_version' => '2.0'
    ],
    [
        'name' => 'Database Performance Optimization',
        'description' => 'Optimize database queries and add indexing',
        'category' => 'Database',
        'priority' => 'medium',
        'status' => 'planning',
        'start_date' => '2025-09-01',
        'due_date' => '2025-09-30',
        'current_version' => '1.2',
        'target_version' => '1.3'
    ],
    [
        'name' => 'WordPress Site Migration',
        'description' => 'Migrate WordPress site to new hosting',
        'category' => 'Migration',
        'priority' => 'low',
        'status' => 'completed',
        'start_date' => '2025-07-15',
        'due_date' => '2025-08-05',
        'completion_date' => '2025-08-03',
        'current_version' => '6.2',
        'target_version' => '6.3'
    ]
];

$createdBy = $users[0]['id'];
$assignedTo = isset($users[1]) ? $users[1]['id'] : $users[0]['id'];

$projectIds = [];
foreach ($testProjects as $project) {
    $sql = "INSERT INTO projects (name, description, category, priority, status, current_version, target_version, start_date, due_date, completion_date, created_by, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $project['name'],
        $project['description'],
        $project['category'],
        $project['priority'],
        $project['status'],
        $project['current_version'],
        $project['target_version'],
        $project['start_date'],
        $project['due_date'],
        $project['completion_date'] ?? null,
        $createdBy,
        $assignedTo
    ];
    
    if (executeUpdate($sql, $params)) {
        $projectId = executeQuerySingle("SELECT LAST_INSERT_ID() as id")['id'];
        $projectIds[] = $projectId;
        echo "✅ Added project: {$project['name']} (ID: $projectId)<br>";
    } else {
        echo "❌ Failed to add project: {$project['name']}<br>";
    }
}

// Add test tasks for some of the projects
$testTasks = [
    [
        'project_id' => $projectIds[0] ?? 1,
        'title' => 'Update PHP version to 8.2',
        'description' => 'Upgrade PHP version and test compatibility',
        'task_type' => 'Version Upgrade',
        'priority' => 'high',
        'status' => 'in_progress',
        'due_date' => '2025-08-12',
        'estimated_hours' => 8.0
    ],
    [
        'project_id' => $projectIds[0] ?? 1,
        'title' => 'Fix deprecated function calls',
        'description' => 'Replace deprecated PHP functions with modern equivalents',
        'task_type' => 'Code Refactoring',
        'priority' => 'medium',
        'status' => 'pending',
        'due_date' => '2025-08-14',
        'estimated_hours' => 6.0
    ],
    [
        'project_id' => $projectIds[1] ?? 2,
        'title' => 'Apply security patches',
        'description' => 'Install latest security patches for vulnerabilities',
        'task_type' => 'Security Update',
        'priority' => 'critical',
        'status' => 'pending',
        'due_date' => '2025-08-18',
        'estimated_hours' => 4.0
    ],
    [
        'project_id' => $projectIds[2] ?? 3,
        'title' => 'Design API endpoints',
        'description' => 'Create RESTful API endpoint specifications',
        'task_type' => 'API Design',
        'priority' => 'medium',
        'status' => 'planning',
        'due_date' => '2025-08-25',
        'estimated_hours' => 12.0
    ],
    [
        'project_id' => $projectIds[3] ?? 4,
        'title' => 'Analyze slow queries',
        'description' => 'Identify and analyze database performance bottlenecks',
        'task_type' => 'Performance Analysis',
        'priority' => 'medium',
        'status' => 'planning',
        'due_date' => '2025-09-10',
        'estimated_hours' => 16.0
    ]
];

foreach ($testTasks as $task) {
    $sql = "INSERT INTO tasks (project_id, title, description, task_type, priority, status, due_date, estimated_hours, assigned_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $task['project_id'],
        $task['title'],
        $task['description'],
        $task['task_type'],
        $task['priority'],
        $task['status'],
        $task['due_date'],
        $task['estimated_hours'],
        $assignedTo
    ];
    
    if (executeUpdate($sql, $params)) {
        echo "✅ Added task: {$task['title']}<br>";
    } else {
        echo "❌ Failed to add task: {$task['title']}<br>";
    }
}

echo "<h2>✅ Test Data Added Successfully!</h2>";
echo "<p>You can now test the calendar functionality with:</p>";
echo "<ul>";
echo "<li>5 test projects with due dates in August and September 2025</li>";
echo "<li>5 test tasks with various due dates</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li><a href='pages/calendar.php?debug=1'>View Calendar with Debug Info</a></li>";
echo "<li><a href='pages/calendar.php'>View Normal Calendar</a></li>";
echo "<li><a href='pages/projects.php'>View Projects List</a></li>";
echo "<li><a href='pages/tasks.php'>View Tasks List</a></li>";
echo "</ol>";
?>