<?php
/**
 * Test Backup Functionality
 * Code Bunker
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/includes/functions.php';

echo "<h2>Testing Database Backup Functionality</h2>";
echo "<hr>";

// Test 1: Create a backup
echo "<h3>Test 1: Creating Database Backup</h3>";
$backupResult = createDatabaseBackup();

if ($backupResult['success']) {
    echo "<p style='color: green;'>✓ Backup created successfully!</p>";
    echo "<ul>";
    echo "<li>Filename: " . htmlspecialchars($backupResult['filename']) . "</li>";
    echo "<li>File path: " . htmlspecialchars($backupResult['filepath']) . "</li>";
    echo "<li>File size: " . $backupResult['filesize'] . " bytes (" . round($backupResult['filesize'] / 1024 / 1024, 2) . " MB)</li>";
    echo "<li>Tables backed up: " . $backupResult['tables_count'] . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Backup failed: " . htmlspecialchars($backupResult['message']) . "</p>";
}

echo "<hr>";

// Test 2: Get list of backups
echo "<h3>Test 2: Getting List of Backups</h3>";
$backupFiles = getBackupFiles();

if (!empty($backupFiles)) {
    echo "<p style='color: green;'>✓ Found " . count($backupFiles) . " backup file(s)</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Filename</th><th>Created</th><th>Size</th></tr>";
    foreach ($backupFiles as $backup) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($backup['filename']) . "</td>";
        echo "<td>" . $backup['created_formatted'] . "</td>";
        echo "<td>" . $backup['size_mb'] . " MB</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>! No backup files found</p>";
}

echo "<hr>";

// Test 3: Validate backup file exists
if ($backupResult['success']) {
    echo "<h3>Test 3: Validating Backup File Exists</h3>";
    if (file_exists($backupResult['filepath'])) {
        echo "<p style='color: green;'>✓ Backup file exists and is readable</p>";

        // Read first few lines to verify it's a valid SQL file
        $handle = fopen($backupResult['filepath'], 'r');
        $firstLines = [];
        for ($i = 0; $i < 10; $i++) {
            $line = fgets($handle);
            if ($line !== false) {
                $firstLines[] = $line;
            }
        }
        fclose($handle);

        echo "<h4>First 10 lines of backup file:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(implode('', $firstLines));
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>✗ Backup file does not exist</p>";
    }
}

echo "<hr>";
echo "<h3>All Tests Completed!</h3>";
echo "<p><a href='pages/settings.php'>Go to Settings Page</a></p>";
?>
