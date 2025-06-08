<?php
/**
 * Database Configuration File
 * 
 * This file contains the database connection settings for the YankesDokpol application.
 * 
 * @package YankesDokpol
 * @version 1.0
 */

// Include application configuration (includes timezone settings)
require_once __DIR__ . '/app_config.php';

// Database credentials
$host = 'auth-db1151.hstgr.io';
$username = 'u609399718_yankesdokpol';
$password = 'Juari@2591';
$database = 'u609399718_yankesdokpol';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Set timezone to GMT+7 (Western Indonesian Time)
$conn->query("SET time_zone = '+07:00'");
$conn->query("SET @@session.time_zone = '+07:00'");

// Set PHP timezone
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

/**
 * Helper function to execute queries
 * 
 * @param string $sql SQL query to execute
 * @param array $params Optional parameters for prepared statement
 * @return mysqli_result|bool Result object or boolean
 */
function executeQuery($sql, $params = []) {
    global $conn;
    
    if (empty($params)) {
        return $conn->query($sql);
    } else {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        
        // Determine types for bind_param
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b';
            }
        }
        
        // Bind parameters dynamically
        if (!empty($params)) {
            $bindParams = array_merge([$types], $params);
            $bindParamsRef = [];
            foreach ($bindParams as $key => $value) {
                $bindParamsRef[$key] = &$bindParams[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindParamsRef);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result : ($stmt->affected_rows > 0);
    }
}

/**
 * Helper function to get a single row
 * 
 * @param string|mysqli_result $sql SQL query to execute or mysqli_result object
 * @param array $params Optional parameters for prepared statement
 * @return array|null Result row as associative array or null
 */
function fetchRow($sql, $params = []) {
    if (is_string($sql)) {
        $result = executeQuery($sql, $params);
    } else {
        $result = $sql; // Already a result object
    }
    
    if ($result && $result !== true) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Helper function to get multiple rows
 * 
 * @param string|mysqli_result $sql SQL query to execute or mysqli_result object
 * @param array $params Optional parameters for prepared statement
 * @return array Result rows as associative arrays
 */
function fetchRows($sql, $params = []) {
    if (is_string($sql)) {
        $result = executeQuery($sql, $params);
    } else {
        $result = $sql; // Already a result object
    }
    
    $rows = [];
    if ($result && $result !== true) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

/**
 * Helper function to escape strings for SQL queries
 * 
 * @param string $value Value to escape
 * @return string Escaped value
 */
function escapeString($value) {
    global $conn;
    return $conn->real_escape_string($value);
}
