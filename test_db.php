<?php
/**
 * Database Connection Test Script
 * 
 * This script tests the database connection with detailed error reporting.
 * DO NOT leave this file on production server after testing!
 */

// Disable error output to browser for security
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

echo "<h1>Database Connection Test</h1>";

// Database credentials from your config
$host = 'auth-db1151.hstgr.io';
$username = 'u609399718_yankesdokpol';
$password = 'Juari@2591';
$database = 'u609399718_yankesdokpol';

echo "<h2>Testing Connection</h2>";
echo "<pre>";
echo "Host: $host\n";
echo "Username: $username\n";
echo "Database: $database\n";
echo "</pre>";

// Test 1: Basic Connection
echo "<h3>Test 1: Basic Connection</h3>";
try {
    $conn = new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p style='color:green'>✓ Successfully connected to MySQL server</p>";
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Failed to connect to MySQL server: " . $e->getMessage() . "</p>";
}

// Test 2: Database Selection
echo "<h3>Test 2: Database Selection</h3>";
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    echo "<p style='color:green'>✓ Successfully connected to database '$database'</p>";
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Failed to connect to database: " . $e->getMessage() . "</p>";
}

// Test 3: Table Access
echo "<h3>Test 3: Table Access</h3>";
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Get list of tables
    $result = $conn->query("SHOW TABLES");
    if (!$result) {
        throw new Exception("Failed to query tables: " . $conn->error);
    }
    
    echo "<p style='color:green'>✓ Successfully queried tables</p>";
    echo "<p>Tables in database:</p>";
    echo "<ul>";
    while ($row = $result->fetch_array()) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Failed to access tables: " . $e->getMessage() . "</p>";
}

// Test 4: Connection Limits
echo "<h3>Test 4: Connection Limits</h3>";
try {
    // Try to get MySQL variables related to connection limits
    $conn = new mysqli($host, $username, $password);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $variables = [
        'max_connections',
        'max_user_connections',
        'max_connect_errors',
        'connect_timeout',
        'wait_timeout'
    ];
    
    echo "<p>MySQL Connection Limits:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    
    foreach ($variables as $var) {
        $result = $conn->query("SHOW VARIABLES LIKE '$var'");
        if ($result && $row = $result->fetch_assoc()) {
            echo "<tr><td>{$row['Variable_name']}</td><td>{$row['Value']}</td></tr>";
        }
    }
    
    echo "</table>";
    
    // Try to get user-specific resource limits
    echo "<p>User Resource Limits:</p>";
    $result = $conn->query("SHOW GRANTS FOR CURRENT_USER()");
    if ($result) {
        echo "<pre>";
        while ($row = $result->fetch_array()) {
            echo htmlspecialchars($row[0]) . "\n";
        }
        echo "</pre>";
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Failed to check connection limits: " . $e->getMessage() . "</p>";
}

echo "<h2>Recommendations</h2>";
echo "<ol>";
echo "<li>If any tests failed, verify your database credentials in the hosting control panel</li>";
echo "<li>Check if your IP is allowed to connect to the database (some hosts restrict access)</li>";
echo "<li>If you see low connection limits, contact your hosting provider to increase them</li>";
echo "<li>After testing, <strong>DELETE THIS FILE</strong> for security reasons</li>";
echo "</ol>";

// End output buffering and display
$output = ob_get_clean();
echo $output;

// Also log the output to a file for reference
file_put_contents(__DIR__ . '/db_test_results.txt', strip_tags($output));
?>
