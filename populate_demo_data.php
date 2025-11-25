<?php
/**
 * Demo Data Population Script
 * Creates sample projects with realistic data for portfolio demonstration
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Populating Demo Data for Portfolio Presentation</h1>";

try {
    // Get admin user ID (assuming username 'admin' exists)
    $adminUser = executeQuerySingle("SELECT id FROM users WHERE username = 'admin'");
    $regularUser = executeQuerySingle("SELECT id FROM users WHERE username = 'user'");
    
    if (!$adminUser) {
        die("Admin user not found. Please run setup_admin.php first.");
    }
    
    $adminId = $adminUser['id'];
    $userId = $regularUser ? $regularUser['id'] : $adminId;
    
    echo "<h2>Creating Sample Projects...</h2>";
    
    // Project 1: E-Commerce Platform Upgrade (High Priority, In Progress)
    $project1Data = [
        'name' => 'E-Commerce Platform Upgrade',
        'description' => 'Major upgrade of the company e-commerce platform to handle Black Friday traffic. Includes performance optimization, security patches, and new payment gateway integration.',
        'category' => 'E-Commerce',
        'priority' => 'critical',
        'status' => 'in_progress',
        'health_status' => 'yellow',
        'risk_level' => 'medium',
        'progress_percentage' => 45,
        'estimated_hours' => 320,
        'actual_hours' => 144,
        'estimated_cost' => 75000,
        'actual_cost' => 33750,
        'start_date' => date('Y-m-d', strtotime('-5 days')),
        'due_date' => date('Y-m-d', strtotime('+18 days')),
        'created_by' => $adminId,
        'assigned_to' => $userId
    ];
    
    // Create project and get ID
    if (executeUpdate(
        "INSERT INTO projects (name, description, category, priority, status, health_status, risk_level,
                            progress_percentage, estimated_hours, actual_hours, estimated_cost, actual_cost,
                            start_date, due_date, created_by, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($project1Data)
    )) {
        // Get the inserted project ID
        $project1 = executeQuerySingle("SELECT id FROM projects WHERE name = ? AND created_by = ?", 
                                       [$project1Data['name'], $project1Data['created_by']]);
        $project1Id = $project1 ? $project1['id'] : null;
    } else {
        throw new Exception("Failed to create Project 1");
    }
    echo "✅ Created Project: E-Commerce Platform Upgrade<br>";
    
    // Add phases for Project 1
    $phases1 = [
        ['name' => 'Infrastructure Assessment', 'description' => 'Analyze current system capacity and bottlenecks'],
        ['name' => 'Development Sprint', 'description' => 'Core development and optimization work'],
        ['name' => 'Testing & Deployment', 'description' => 'Load testing and production rollout']
    ];
    
    foreach ($phases1 as $index => $phase) {
        executeUpdate("INSERT INTO project_phases (project_id, name, description, order_index) VALUES (?, ?, ?, ?)",
                     [$project1Id, $phase['name'], $phase['description'], $index + 1]);
    }
    
    // Add tasks for Project 1
    $tasks1 = [
        [
            'phase_id' => 1,
            'title' => 'Performance Audit',
            'description' => 'Complete system performance analysis and identify bottlenecks',
            'priority' => 'critical',
            'status' => 'completed',
            'estimated_hours' => 24,
            'actual_hours' => 26,
            'due_date' => date('Y-m-d', strtotime('-2 days')),
            'assigned_to' => $userId
        ],
        [
            'phase_id' => 1,
            'title' => 'Security Vulnerability Scan',
            'description' => 'Run comprehensive security audit and patch critical vulnerabilities',
            'priority' => 'critical',
            'status' => 'completed',
            'estimated_hours' => 16,
            'actual_hours' => 18,
            'due_date' => date('Y-m-d', strtotime('-1 day')),
            'assigned_to' => $adminId
        ],
        [
            'phase_id' => 2,
            'title' => 'Database Optimization',
            'description' => 'Optimize queries, add indexes, implement caching layer',
            'priority' => 'high',
            'status' => 'in_progress',
            'estimated_hours' => 40,
            'actual_hours' => 20,
            'due_date' => date('Y-m-d', strtotime('+3 days')),
            'assigned_to' => $userId
        ],
        [
            'phase_id' => 2,
            'title' => 'Payment Gateway Integration',
            'description' => 'Integrate Stripe and PayPal for seamless checkout',
            'priority' => 'high',
            'status' => 'in_progress',
            'estimated_hours' => 32,
            'actual_hours' => 12,
            'due_date' => date('Y-m-d', strtotime('+5 days')),
            'assigned_to' => $adminId
        ],
        [
            'phase_id' => 2,
            'title' => 'CDN Implementation',
            'description' => 'Set up CloudFlare CDN for static assets',
            'priority' => 'medium',
            'status' => 'pending',
            'estimated_hours' => 16,
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'assigned_to' => $userId
        ],
        [
            'phase_id' => 3,
            'title' => 'Load Testing',
            'description' => 'Simulate Black Friday traffic levels',
            'priority' => 'critical',
            'status' => 'pending',
            'estimated_hours' => 24,
            'due_date' => date('Y-m-d', strtotime('+12 days')),
            'assigned_to' => $adminId
        ],
        [
            'phase_id' => 3,
            'title' => 'Production Deployment',
            'description' => 'Deploy to production with zero-downtime strategy',
            'priority' => 'critical',
            'status' => 'pending',
            'estimated_hours' => 8,
            'due_date' => date('Y-m-d', strtotime('+15 days')),
            'assigned_to' => $adminId
        ]
    ];
    
    foreach ($tasks1 as $task) {
        $task['project_id'] = $project1Id;
        $columns = array_keys($task);
        $values = array_values($task);
        $placeholders = array_fill(0, count($values), '?');
        
        executeUpdate(
            "INSERT INTO tasks (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $values
        );
    }
    echo "✅ Added 7 tasks with phases for E-Commerce project<br><br>";
    
    // Project 2: Mobile App Development (Medium Priority, Planning)
    $project2Data = [
        'name' => 'Customer Mobile App v2.0',
        'description' => 'Native iOS and Android app development for customer self-service portal. Features include real-time notifications, biometric authentication, and offline mode.',
        'category' => 'Mobile Development',
        'priority' => 'high',
        'status' => 'planning',
        'health_status' => 'green',
        'risk_level' => 'low',
        'progress_percentage' => 15,
        'estimated_hours' => 480,
        'actual_hours' => 72,
        'estimated_cost' => 120000,
        'actual_cost' => 18000,
        'start_date' => date('Y-m-d', strtotime('+2 days')),
        'due_date' => date('Y-m-d', strtotime('+45 days')),
        'created_by' => $adminId,
        'assigned_to' => $userId
    ];
    
    // Create project 2 and get ID
    if (executeUpdate(
        "INSERT INTO projects (name, description, category, priority, status, health_status, risk_level,
                            progress_percentage, estimated_hours, actual_hours, estimated_cost, actual_cost,
                            start_date, due_date, created_by, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($project2Data)
    )) {
        $project2 = executeQuerySingle("SELECT id FROM projects WHERE name = ? AND created_by = ?", 
                                       [$project2Data['name'], $project2Data['created_by']]);
        $project2Id = $project2 ? $project2['id'] : null;
    } else {
        throw new Exception("Failed to create Project 2");
    }
    echo "✅ Created Project: Customer Mobile App v2.0<br>";
    
    // Add phases for Project 2
    $phases2 = [
        ['name' => 'Design & Prototyping', 'description' => 'UI/UX design and interactive prototypes'],
        ['name' => 'Core Development', 'description' => 'Native app development for iOS and Android'],
        ['name' => 'Beta Testing', 'description' => 'User acceptance testing and bug fixes']
    ];
    
    foreach ($phases2 as $index => $phase) {
        executeUpdate("INSERT INTO project_phases (project_id, name, description, order_index) VALUES (?, ?, ?, ?)",
                     [$project2Id, $phase['name'], $phase['description'], $index + 1]);
    }
    
    // Add tasks for Project 2
    $tasks2 = [
        [
            'phase_id' => 4, // First phase of project 2
            'title' => 'User Research & Surveys',
            'description' => 'Conduct user interviews and analyze requirements',
            'priority' => 'high',
            'status' => 'in_progress',
            'estimated_hours' => 32,
            'actual_hours' => 20,
            'due_date' => date('Y-m-d', strtotime('+4 days')),
            'assigned_to' => $userId
        ],
        [
            'phase_id' => 4,
            'title' => 'Wireframe Creation',
            'description' => 'Design low-fidelity wireframes for all screens',
            'priority' => 'medium',
            'status' => 'pending',
            'estimated_hours' => 24,
            'due_date' => date('Y-m-d', strtotime('+8 days')),
            'assigned_to' => $adminId
        ],
        [
            'phase_id' => 5,
            'title' => 'iOS Development',
            'description' => 'Native Swift development for iPhone and iPad',
            'priority' => 'high',
            'status' => 'pending',
            'estimated_hours' => 160,
            'due_date' => date('Y-m-d', strtotime('+25 days')),
            'assigned_to' => $userId
        ],
        [
            'phase_id' => 5,
            'title' => 'Android Development',
            'description' => 'Native Kotlin development for Android devices',
            'priority' => 'high',
            'status' => 'pending',
            'estimated_hours' => 160,
            'due_date' => date('Y-m-d', strtotime('+25 days')),
            'assigned_to' => $adminId
        ],
        [
            'phase_id' => 6,
            'title' => 'Beta Release',
            'description' => 'Release to internal beta testers',
            'priority' => 'medium',
            'status' => 'pending',
            'estimated_hours' => 8,
            'due_date' => date('Y-m-d', strtotime('+35 days')),
            'assigned_to' => $adminId
        ]
    ];
    
    foreach ($tasks2 as $task) {
        $task['project_id'] = $project2Id;
        $columns = array_keys($task);
        $values = array_values($task);
        $placeholders = array_fill(0, count($values), '?');
        
        executeUpdate(
            "INSERT INTO tasks (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $values
        );
    }
    echo "✅ Added 5 tasks with phases for Mobile App project<br><br>";
    
    // Project 3: Security Compliance Audit (Critical, Urgent)
    $project3Data = [
        'name' => 'SOC 2 Compliance Audit',
        'description' => 'Comprehensive security audit and compliance certification for SOC 2 Type II. Required for enterprise client contracts.',
        'category' => 'Security & Compliance',
        'priority' => 'critical',
        'status' => 'in_progress',
        'health_status' => 'red',
        'risk_level' => 'high',
        'progress_percentage' => 30,
        'estimated_hours' => 200,
        'actual_hours' => 60,
        'estimated_cost' => 50000,
        'actual_cost' => 15000,
        'start_date' => date('Y-m-d', strtotime('-3 days')),
        'due_date' => date('Y-m-d', strtotime('+10 days')),
        'created_by' => $adminId,
        'assigned_to' => $adminId
    ];
    
    // Create project 3 and get ID
    if (executeUpdate(
        "INSERT INTO projects (name, description, category, priority, status, health_status, risk_level,
                            progress_percentage, estimated_hours, actual_hours, estimated_cost, actual_cost,
                            start_date, due_date, created_by, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($project3Data)
    )) {
        $project3 = executeQuerySingle("SELECT id FROM projects WHERE name = ? AND created_by = ?", 
                                       [$project3Data['name'], $project3Data['created_by']]);
        $project3Id = $project3 ? $project3['id'] : null;
    } else {
        throw new Exception("Failed to create Project 3");
    }
    echo "✅ Created Project: SOC 2 Compliance Audit<br>";
    
    // Add tasks for Project 3 (no phases for urgent project)
    $tasks3 = [
        [
            'title' => 'Access Control Review',
            'description' => 'Audit user access controls and permissions',
            'priority' => 'critical',
            'status' => 'completed',
            'estimated_hours' => 24,
            'actual_hours' => 28,
            'due_date' => date('Y-m-d', strtotime('-1 day')),
            'assigned_to' => $adminId
        ],
        [
            'title' => 'Data Encryption Audit',
            'description' => 'Verify encryption at rest and in transit',
            'priority' => 'critical',
            'status' => 'in_progress',
            'estimated_hours' => 32,
            'actual_hours' => 16,
            'due_date' => date('Y-m-d', strtotime('+2 days')),
            'assigned_to' => $userId
        ],
        [
            'title' => 'Incident Response Plan',
            'description' => 'Document and test incident response procedures',
            'priority' => 'high',
            'status' => 'pending',
            'estimated_hours' => 40,
            'due_date' => date('Y-m-d', strtotime('+6 days')),
            'assigned_to' => $adminId
        ],
        [
            'title' => 'Vulnerability Assessment',
            'description' => 'Complete penetration testing and remediation',
            'priority' => 'critical',
            'status' => 'pending',
            'estimated_hours' => 48,
            'due_date' => date('Y-m-d', strtotime('+8 days')),
            'assigned_to' => $userId
        ]
    ];
    
    foreach ($tasks3 as $task) {
        $task['project_id'] = $project3Id;
        $columns = array_keys($task);
        $values = array_values($task);
        $placeholders = array_fill(0, count($values), '?');
        
        executeUpdate(
            "INSERT INTO tasks (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $values
        );
    }
    echo "✅ Added 4 tasks for Security Compliance project<br><br>";
    
    // Project 4: API Development (Completed - for showing success)
    $project4Data = [
        'name' => 'RESTful API v3.0',
        'description' => 'Complete REST API rebuild with GraphQL support, OAuth 2.0 authentication, and comprehensive documentation.',
        'category' => 'Backend Development',
        'priority' => 'medium',
        'status' => 'completed',
        'health_status' => 'green',
        'risk_level' => 'low',
        'progress_percentage' => 100,
        'estimated_hours' => 240,
        'actual_hours' => 235,
        'estimated_cost' => 60000,
        'actual_cost' => 58750,
        'start_date' => date('Y-m-d', strtotime('-30 days')),
        'due_date' => date('Y-m-d', strtotime('-2 days')),
        'completion_date' => date('Y-m-d', strtotime('-2 days')),
        'created_by' => $adminId,
        'assigned_to' => $userId
    ];
    
    // Create project 4 and get ID
    if (executeUpdate(
        "INSERT INTO projects (name, description, category, priority, status, health_status, risk_level,
                            progress_percentage, estimated_hours, actual_hours, estimated_cost, actual_cost,
                            start_date, due_date, completion_date, created_by, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($project4Data)
    )) {
        $project4 = executeQuerySingle("SELECT id FROM projects WHERE name = ? AND created_by = ?", 
                                       [$project4Data['name'], $project4Data['created_by']]);
        $project4Id = $project4 ? $project4['id'] : null;
    } else {
        throw new Exception("Failed to create Project 4");
    }
    echo "✅ Created Project: RESTful API v3.0 (Completed)<br>";
    
    // Add completed tasks for Project 4
    $tasks4 = [
        [
            'title' => 'API Architecture Design',
            'description' => 'Design RESTful endpoints and data models',
            'priority' => 'high',
            'status' => 'completed',
            'estimated_hours' => 40,
            'actual_hours' => 38,
            'due_date' => date('Y-m-d', strtotime('-20 days')),
            'completed_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
            'assigned_to' => $userId
        ],
        [
            'title' => 'OAuth 2.0 Implementation',
            'description' => 'Implement secure authentication flow',
            'priority' => 'critical',
            'status' => 'completed',
            'estimated_hours' => 48,
            'actual_hours' => 52,
            'due_date' => date('Y-m-d', strtotime('-15 days')),
            'completed_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
            'assigned_to' => $adminId
        ],
        [
            'title' => 'GraphQL Integration',
            'description' => 'Add GraphQL endpoint alongside REST',
            'priority' => 'medium',
            'status' => 'completed',
            'estimated_hours' => 60,
            'actual_hours' => 58,
            'due_date' => date('Y-m-d', strtotime('-8 days')),
            'completed_at' => date('Y-m-d H:i:s', strtotime('-9 days')),
            'assigned_to' => $userId
        ],
        [
            'title' => 'API Documentation',
            'description' => 'Swagger/OpenAPI documentation',
            'priority' => 'high',
            'status' => 'completed',
            'estimated_hours' => 24,
            'actual_hours' => 22,
            'due_date' => date('Y-m-d', strtotime('-3 days')),
            'completed_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'assigned_to' => $adminId
        ]
    ];
    
    foreach ($tasks4 as $task) {
        $task['project_id'] = $project4Id;
        $columns = array_keys($task);
        $values = array_values($task);
        $placeholders = array_fill(0, count($values), '?');
        
        executeUpdate(
            "INSERT INTO tasks (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $values
        );
    }
    echo "✅ Added 4 completed tasks for API project<br><br>";
    
    // Project 5: Infrastructure Migration (Future)
    $project5Data = [
        'name' => 'AWS Cloud Migration',
        'description' => 'Migrate on-premise infrastructure to AWS cloud with auto-scaling, disaster recovery, and multi-region deployment.',
        'category' => 'Infrastructure',
        'priority' => 'medium',
        'status' => 'planning',
        'health_status' => 'green',
        'risk_level' => 'medium',
        'progress_percentage' => 5,
        'estimated_hours' => 360,
        'actual_hours' => 18,
        'estimated_cost' => 90000,
        'actual_cost' => 4500,
        'start_date' => date('Y-m-d', strtotime('+7 days')),
        'due_date' => date('Y-m-d', strtotime('+60 days')),
        'created_by' => $adminId,
        'assigned_to' => $userId
    ];
    
    // Create project 5 and get ID
    if (executeUpdate(
        "INSERT INTO projects (name, description, category, priority, status, health_status, risk_level,
                            progress_percentage, estimated_hours, actual_hours, estimated_cost, actual_cost,
                            start_date, due_date, created_by, assigned_to)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        array_values($project5Data)
    )) {
        $project5 = executeQuerySingle("SELECT id FROM projects WHERE name = ? AND created_by = ?", 
                                       [$project5Data['name'], $project5Data['created_by']]);
        $project5Id = $project5 ? $project5['id'] : null;
    } else {
        throw new Exception("Failed to create Project 5");
    }
    echo "✅ Created Project: AWS Cloud Migration<br>";
    
    // Add tasks for Project 5
    $tasks5 = [
        [
            'title' => 'Infrastructure Assessment',
            'description' => 'Document current infrastructure and dependencies',
            'priority' => 'high',
            'status' => 'pending',
            'estimated_hours' => 40,
            'due_date' => date('Y-m-d', strtotime('+12 days')),
            'assigned_to' => $adminId
        ],
        [
            'title' => 'AWS Architecture Design',
            'description' => 'Design VPC, subnets, and security groups',
            'priority' => 'high',
            'status' => 'pending',
            'estimated_hours' => 48,
            'due_date' => date('Y-m-d', strtotime('+18 days')),
            'assigned_to' => $userId
        ],
        [
            'title' => 'Database Migration Plan',
            'description' => 'Plan RDS migration with minimal downtime',
            'priority' => 'critical',
            'status' => 'pending',
            'estimated_hours' => 32,
            'due_date' => date('Y-m-d', strtotime('+22 days')),
            'assigned_to' => $adminId
        ]
    ];
    
    foreach ($tasks5 as $task) {
        $task['project_id'] = $project5Id;
        $columns = array_keys($task);
        $values = array_values($task);
        $placeholders = array_fill(0, count($values), '?');
        
        executeUpdate(
            "INSERT INTO tasks (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $values
        );
    }
    echo "✅ Added 3 tasks for AWS Migration project<br><br>";
    
    // Add some notes to projects for realism
    $notes = [
        [
            'project_id' => $project1Id,
            'user_id' => $adminId,
            'title' => 'Client Meeting Notes',
            'content' => 'Client emphasized the importance of zero downtime during Black Friday. Load testing must simulate 10x normal traffic. Payment gateway must support Apple Pay and Google Pay.',
            'note_type' => 'meeting'
        ],
        [
            'project_id' => $project1Id,
            'user_id' => $userId,
            'title' => 'Performance Bottlenecks Identified',
            'content' => 'Database queries are the main bottleneck. Implemented query caching and added missing indexes. Response time improved by 40%.',
            'note_type' => 'technical'
        ],
        [
            'project_id' => $project3Id,
            'user_id' => $adminId,
            'title' => 'Urgent: Compliance Deadline',
            'content' => 'Enterprise client contract depends on SOC 2 certification. Must complete by deadline or risk losing $2M annual contract.',
            'note_type' => 'decision'
        ],
        [
            'project_id' => $project2Id,
            'user_id' => $userId,
            'title' => 'Design System Decision',
            'content' => 'Team agreed to use Material Design 3 for Android and Human Interface Guidelines for iOS. Dark mode support is mandatory.',
            'note_type' => 'decision'
        ]
    ];
    
    foreach ($notes as $note) {
        executeUpdate(
            "INSERT INTO notes (project_id, user_id, title, content, note_type, created_at) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            array_values($note)
        );
    }
    echo "✅ Added project notes for context<br><br>";
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Demo Data Successfully Created!</h3>";
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li>5 diverse projects demonstrating different categories and statuses</li>";
    echo "<li>28 tasks with realistic timelines and assignments</li>";
    echo "<li>7 project phases showing hierarchical structure</li>";
    echo "<li>4 contextual notes for added realism</li>";
    echo "<li>Projects span from 3 days ago to 60 days in the future</li>";
    echo "<li>Mix of critical, high, medium, and low priority items</li>";
    echo "<li>Various health statuses (green, yellow, red) for dashboard</li>";
    echo "</ul>";
    echo "<p><strong>Calendar will show:</strong></p>";
    echo "<ul>";
    echo "<li>Overdue items (red indicators)</li>";
    echo "<li>Items due this week (yellow indicators)</li>";
    echo "<li>Future deadlines (green indicators)</li>";
    echo "<li>Completed items for success metrics</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<p><a href='pages/dashboard.php' class='btn btn-primary'>View Dashboard</a> ";
    echo "<a href='pages/calendar.php' class='btn btn-success'>View Calendar</a> ";
    echo "<a href='pages/advanced_reports.php' class='btn btn-info'>View Reports</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error Creating Demo Data</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f8f9fa;
}
h1 {
    color: #333;
    border-bottom: 2px solid #007bff;
    padding-bottom: 10px;
}
h2 {
    color: #495057;
    margin-top: 30px;
}
.btn {
    display: inline-block;
    padding: 10px 20px;
    margin: 5px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
.btn-success { background: #28a745; }
.btn-info { background: #17a2b8; }
.btn:hover { opacity: 0.9; }
</style>