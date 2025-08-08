<?php
/**
 * Database Connection Test
 * Use this to verify XAMPP MySQL is running and database exists
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Test 1: Check if MySQL extension is loaded
echo "<h2>1. PHP MySQL Extensions</h2>";
echo "PDO: " . (extension_loaded('pdo') ? '✅ Available' : '❌ Not Available') . "<br>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ Available' : '❌ Not Available') . "<br>";

// Test 2: Try basic connection
echo "<h2>2. MySQL Connection Test</h2>";
$host = 'localhost';
$username = 'root';
$password = '';
$port = 3306;

try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "✅ MySQL server connection successful<br>";
    
    // Test 3: Check if database exists
    echo "<h2>3. Database Check</h2>";
    $stmt = $pdo->query("SHOW DATABASES LIKE 'web_app_tracker'");
    $database_exists = $stmt->rowCount() > 0;
    
    if ($database_exists) {
        echo "✅ Database 'web_app_tracker' exists<br>";
        
        // Test 4: Connect to specific database
        $dsn_with_db = "mysql:host=$host;port=$port;dbname=web_app_tracker;charset=utf8mb4";
        $pdo_db = new PDO($dsn_with_db, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "✅ Connection to 'web_app_tracker' database successful<br>";
        
        // Test 5: Check tables
        echo "<h2>4. Table Check</h2>";
        $tables = $pdo_db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            echo "⚠️ Database exists but no tables found<br>";
            echo "<strong>You need to import the database schema!</strong><br>";
        } else {
            echo "✅ Found " . count($tables) . " tables:<br>";
            foreach ($tables as $table) {
                echo "- $table<br>";
            }
        }
        
    } else {
        echo "❌ Database 'web_app_tracker' does not exist<br>";
        echo "<strong>You need to create the database and import the schema!</strong><br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "<br>";
    echo "<strong>Make sure XAMPP MySQL is running!</strong><br>";
}

echo "<h2>5. Next Steps</h2>";
echo "<p>If you see errors above:</p>";
echo "<ol>";
echo "<li>Make sure XAMPP is running (start Apache and MySQL)</li>";
echo "<li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>";
echo "<li>Create database 'web_app_tracker' if it doesn't exist</li>";
echo "<li>Import the database schema from database/schema.sql</li>";
echo "</ol>";

// Test our database class
echo "<h2>6. Testing Our Database Class</h2>";
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

if ($conn !== null) {
    echo "✅ Our Database class connection successful<br>";
    if ($db->testConnection()) {
        echo "✅ Database test query successful<br>";
    } else {
        echo "❌ Database test query failed<br>";
    }
} else {
    echo "❌ Our Database class connection failed<br>";
}
?>