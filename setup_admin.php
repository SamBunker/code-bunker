<?php
/**
 * Admin User Setup Script
 * Run this once to create/update the admin user with correct password
 */

require_once 'config/config.php';

// Create correct password hash for "admin123"
$password = 'admin123';
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Admin User Setup</h2>";
echo "<p><strong>Username:</strong> admin</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "<p><strong>Generated Hash:</strong> " . $passwordHash . "</p>";

try {
    $database = getDatabase();
    
    // Check if admin user exists
    $checkQuery = "SELECT id FROM users WHERE username = 'admin'";
    $existingUser = executeQuerySingle($checkQuery);
    
    if ($existingUser) {
        // Update existing admin user
        $updateQuery = "UPDATE users SET password_hash = ?, email = 'admin@company.com', first_name = 'System', last_name = 'Administrator', role = 'admin', is_active = 1 WHERE username = 'admin'";
        $result = executeUpdate($updateQuery, [$passwordHash]);
        
        if ($result) {
            echo "<div style='color: green; padding: 10px; background: #f0f8f0; border: 1px solid #90ee90; margin: 10px 0;'>";
            echo "<strong>SUCCESS!</strong> Admin user password has been updated.<br>";
            echo "You can now login with username: <strong>admin</strong> and password: <strong>admin123</strong>";
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; background: #fdf0f0; border: 1px solid #ff9090; margin: 10px 0;'>";
            echo "ERROR: Failed to update admin user password.";
            echo "</div>";
        }
    } else {
        // Create new admin user
        $insertQuery = "INSERT INTO users (username, email, password_hash, role, first_name, last_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = ['admin', 'admin@company.com', $passwordHash, 'admin', 'System', 'Administrator', 1];
        $result = executeUpdate($insertQuery, $params);
        
        if ($result) {
            echo "<div style='color: green; padding: 10px; background: #f0f8f0; border: 1px solid #90ee90; margin: 10px 0;'>";
            echo "<strong>SUCCESS!</strong> Admin user has been created.<br>";
            echo "You can now login with username: <strong>admin</strong> and password: <strong>admin123</strong>";
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; background: #fdf0f0; border: 1px solid #ff9090; margin: 10px 0;'>";
            echo "ERROR: Failed to create admin user.";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; background: #fdf0f0; border: 1px solid #ff9090; margin: 10px 0;'>";
    echo "ERROR: " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Go to Application</a></p>";
echo "<p><em>Note: Delete this file after setup for security.</em></p>";
?>

<style>
body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
h2 { color: #333; }
</style>