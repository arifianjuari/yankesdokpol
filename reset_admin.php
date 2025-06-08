<?php
// Include database connection
require_once 'config/database.php';

// Generate hash for admin123
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Update admin user with new password
$sql = "UPDATE users SET password = ? WHERE username = 'admin'";
$result = executeQuery($sql, [$hash]);

if ($result) {
    echo "Admin password has been reset successfully.\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
} else {
    echo "Failed to reset admin password.\n";
}
