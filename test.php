<?php
// Simple test file to check if PHP is working
echo "<h1>PHP Test</h1>";
echo "<p>PHP is working correctly!</p>";
echo "<p>Server time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connection
require_once 'config/database.php';

try {
    $database = new Database();
    $connection = $database->getConnection();
    
    if ($connection) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        
        // Test a simple query
        $stmt = $connection->query("SELECT COUNT(*) as count FROM users");
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p style='color: green;'>✓ Database query successful! Users count: " . $result['count'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

// Test includes
try {
    require_once 'config/config.php';
    echo "<p style='color: green;'>✓ Config loaded successfully!</p>";
    echo "<p>App Name: " . APP_NAME . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Config error: " . $e->getMessage() . "</p>";
}

phpinfo();
?>