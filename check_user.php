<?php
// Include database connection
require_once 'config/database.php';

// Check if users table exists
$result = executeQuery("SHOW TABLES LIKE 'users'");
$tableExists = ($result && $result->num_rows > 0);

echo "Users table exists: " . ($tableExists ? "Yes" : "No") . "\n";

if ($tableExists) {
    // Check if admin user exists
    $result = executeQuery("SELECT * FROM users WHERE username = 'admin'");
    $adminExists = ($result && $result->num_rows > 0);
    
    echo "Admin user exists: " . ($adminExists ? "Yes" : "No") . "\n";
    
    if ($adminExists) {
        $user = fetchRow($result);
        echo "Admin password hash: " . $user['password'] . "\n";
        
        // Test password verification
        $testPassword = 'admin123';
        $passwordVerifies = password_verify($testPassword, $user['password']);
        echo "Password 'admin123' verifies: " . ($passwordVerifies ? "Yes" : "No") . "\n";
    }
}
