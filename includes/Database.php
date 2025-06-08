<?php
/**
 * Database Connection Class
 * 
 * This class handles the database connection using singleton pattern
 * to ensure only one database connection is used per request.
 */
class Database {
    private static $instance = null;
    private $connection;
    
    // Database configuration
    private $host = DB_HOST;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $dbname = DB_NAME;
    
    /**
     * Constructor - establishes the database connection
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            // Log the error and show a user-friendly message
            error_log("Database Connection Error: " . $e->getMessage());
            die("Terjadi kesalahan koneksi database. Silakan coba lagi nanti.");
        }
    }
    
    /**
     * Get the database connection instance using singleton pattern
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the PDO connection
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize database connection");
    }
}

// Define database constants if not already defined
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'yankesdokpol');
