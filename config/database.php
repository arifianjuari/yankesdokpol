<?php

/**
 * Database Configuration File
 * 
 * This file contains the database connection settings for the HUT Bhayangkara 79 application.
 * With optimized persistent connection management to avoid max_connections_per_hour limits.
 * 
 * @package HUTBhayangkara79
 * @version 1.1
 */

// Include application configuration (includes timezone settings)
require_once __DIR__ . '/app_config.php';

// Database connection singleton instance
class DatabaseConnection
{
    private static $instance = null;
    private $connection = null;
    private $host = 'srv859992.hstgr.cloud';
    private $username = 'u609399718_yankesdokpol';
    private $password = 'Juari@2591';
    private $database = 'u609399718_yankesdokpol';

    // Private constructor for singleton pattern
    private function __construct()
    {
        $this->connect();
    }

    // Get singleton instance
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Create connection with persistent connection and retry mechanism
    private function connect($retryCount = 0)
    {
        // Maximum number of retry attempts
        $maxRetries = 3;
        $retryDelay = 2; // seconds

        try {
            // Set default socket timeout to be longer
            $defaultTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 60); // 60 seconds timeout

            // Create mysqli object without connecting
            $this->connection = mysqli_init();

            // Set connection options
            $this->connection->options(MYSQLI_OPT_CONNECT_TIMEOUT, 20); // 20 seconds
            $this->connection->options(MYSQLI_OPT_READ_TIMEOUT, 60);    // 60 seconds
            $this->connection->options(MYSQLI_CLIENT_COMPRESS, true);   // Use compression
            $this->connection->options(MYSQLI_SET_CHARSET_NAME, 'utf8mb4');

            // Try to connect with persistent connection
            $connected = @$this->connection->real_connect('p:' . $this->host, $this->username, $this->password, $this->database);

            // Restore default timeout
            ini_set('default_socket_timeout', $defaultTimeout);

            // Check connection
            if (!$connected) {
                $errorMsg = $this->connection->connect_error;
                $errorNo = $this->connection->connect_errno;

                // Log detailed error
                error_log("Database connection failed (Attempt {$retryCount}): Error #{$errorNo}: {$errorMsg}");

                // Retry logic
                if ($retryCount < $maxRetries) {
                    error_log("Retrying database connection in {$retryDelay} seconds... (Attempt " . ($retryCount + 1) . ")");
                    sleep($retryDelay);
                    return $this->connect($retryCount + 1);
                }

                // All retries failed
                throw new Exception("Database connection failed after {$maxRetries} attempts: {$errorMsg}");
            }

            // Set connection properties
            $this->connection->set_charset("utf8mb4");
            $this->connection->query("SET time_zone = '+07:00'");
            $this->connection->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
            $this->connection->query("SET SESSION wait_timeout=300"); // 5 minutes wait timeout
            $this->connection->query("SET SESSION interactive_timeout=300"); // 5 minutes interactive timeout

            return true;
        } catch (Exception $e) {
            error_log("Critical database error: " . $e->getMessage());
            die("<h3>Maaf, terjadi gangguan koneksi database.</h3><p>Tim teknis kami sudah diberitahu. Silakan coba beberapa saat lagi.</p>");
        }
    }

    // Get the mysqli connection
    public function getConnection()
    {
        // Check if connection is valid and reconnect if needed
        // Avoid using ping() as it's deprecated in PHP 8.4
        if (!$this->connection) {
            $this->connect();
            return $this->connection;
        }

        // Alternative to ping(): Try a simple query to test connection
        try {
            $testResult = @$this->connection->query('SELECT 1');
            if ($testResult === false) {
                // Connection likely lost, try to reconnect
                $this->connect();
            } else {
                // Free the result
                $testResult->free();
            }
        } catch (Exception $e) {
            // Exception occurred, try to reconnect
            $this->connect();
        }

        return $this->connection;
    }

    // Close connection (generally not needed with persistent connections, but good practice)
    public function close()
    {
        if ($this->connection) {
            $this->connection->close();
            $this->connection = null;
        }
    }
}

// Get the database connection (using singleton)
$conn = DatabaseConnection::getInstance()->getConnection();

// Register shutdown function to handle connection cleanup
register_shutdown_function(function () {
    // Cleanup is handled by PHP for persistent connections
    // This function is here for potential future cleanup needs
});

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
function executeQuery($sql, $params = [])
{
    global $conn;

    try {
        // Get connection from singleton
        if (!($conn instanceof mysqli)) {
            $conn = DatabaseConnection::getInstance()->getConnection();
        }

        // Prepare statement with error handling
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Database prepare failed: {$conn->error} for query: {$sql}");
            return false;
        }

        // Bind parameters if any
        if (!empty($params)) {
            $types = '';
            $bindParams = [];

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
                $bindParams[] = $param;
            }

            if (!empty($types)) {
                // Prepare the parameter reference list
                $bindParams = array_merge([&$types], array_map(function (&$param) {
                    return $param;
                }, $bindParams));
                call_user_func_array([$stmt, 'bind_param'], $bindParams);
            }
        }

        // Execute query with error handling
        $executeResult = $stmt->execute();
        if (!$executeResult) {
            error_log("Database execute failed: {$stmt->error} for query: {$sql}");
            $stmt->close();
            return false;
        }

        // Get result if it's a SELECT query
        if ($stmt->result_metadata()) {
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        }

        // For non-SELECT queries, return affected rows or insert ID
        $affectedRows = $stmt->affected_rows;
        $insertId = $stmt->insert_id;
        $stmt->close();

        // Return insert ID if available, otherwise affected rows
        return $insertId > 0 ? $insertId : $affectedRows;
    } catch (Exception $e) {
        error_log("Database exception: {$e->getMessage()} for query: {$sql}");
        return false;
    }
}

/**
 * Helper function to get a single row with improved handling
 * 
 * @param string|mysqli_result $sql SQL query to execute or mysqli_result object
 * @param array $params Optional parameters for prepared statement
 * @return array|null Result row as associative array or null
 */
function fetchRow($sql, $params = [])
{
    $result = executeQuery($sql, $params);

    // Handle failed query
    if ($result === false) {
        return null;
    }

    // Handle successful query result
    if ($result instanceof mysqli_result) {
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $result->free();
            return $row;
        } else {
            $result->free();
        }
    }

    return null;
}

/**
 * Helper function to get multiple rows with improved handling
 * 
 * @param string|mysqli_result $sql SQL query to execute or mysqli_result object
 * @param array $params Optional parameters for prepared statement
 * @return array Result rows as associative arrays
 */
function fetchRows($sql, $params = [])
{
    $rows = [];
    $result = executeQuery($sql, $params);

    // Handle failed query
    if ($result === false) {
        return $rows;
    }

    // Process result set
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }

    return $rows;
}

/**
 * Helper function to escape strings for SQL queries with improved handling
 * 
 * @param string $value Value to escape
 * @return string Escaped value
 */
function escapeString($value)
{
    global $conn;

    // Ensure connection is valid
    if (!($conn instanceof mysqli)) {
        $conn = DatabaseConnection::getInstance()->getConnection();
    }

    return $conn->real_escape_string($value);
}
