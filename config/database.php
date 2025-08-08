<?php
/**
 * Database Connection Configuration
 * Web Application Modernization Tracker
 * 
 * This file handles database connections using PDO with proper error handling
 * and security measures for the XAMPP environment.
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'web_app_tracker';
    private $username = 'root';
    private $password = '';  // XAMPP default - change for production
    private $charset = 'utf8mb4';
    private $port = 3306;
    public $conn;

    /**
     * Get database connection using PDO
     * @return PDO|null Returns PDO connection or null on failure
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . 
                   ";port=" . $this->port . 
                   ";dbname=" . $this->db_name . 
                   ";charset=" . $this->charset;
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Don't expose database details to users in production
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                echo "Connection Error: " . $e->getMessage();
            } else {
                echo "Database connection failed. Please try again later.";
            }
        }
        
        return $this->conn;
    }

    /**
     * Test database connection
     * @return bool Returns true if connection successful
     */
    public function testConnection() {
        $connection = $this->getConnection();
        if ($connection !== null) {
            try {
                $stmt = $connection->query("SELECT 1");
                return $stmt !== false;
            } catch(PDOException $e) {
                error_log("Database Test Error: " . $e->getMessage());
                return false;
            }
        }
        return false;
    }

    /**
     * Execute a prepared statement with parameters
     * @param string $query SQL query with placeholders
     * @param array $params Parameters to bind
     * @return PDOStatement|false Returns statement or false on failure
     */
    public function executeQuery($query, $params = []) {
        if ($this->conn === null) {
            error_log("Database connection is null - cannot execute query");
            return false;
        }
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Query Execution Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get last inserted ID
     * @return string Last insert ID
     */
    public function getLastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

/**
 * Global function to get database instance
 * @return Database|null Database instance or null if connection failed
 */
function getDatabase() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
        $conn = $database->getConnection();
        if ($conn === null) {
            error_log("Failed to establish database connection");
            return null;
        }
    }
    return $database;
}

/**
 * Helper function to execute a query and return results
 * @param string $query SQL query
 * @param array $params Parameters to bind
 * @return array|false Results array or false on failure
 */
function executeQuery($query, $params = []) {
    $database = getDatabase();
    if ($database === null) {
        return false;
    }
    
    $stmt = $database->executeQuery($query, $params);
    
    if ($stmt !== false) {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return false;
}

/**
 * Helper function to execute a query and return single row
 * @param string $query SQL query
 * @param array $params Parameters to bind
 * @return array|false Single row or false on failure
 */
function executeQuerySingle($query, $params = []) {
    $database = getDatabase();
    if ($database === null) {
        return false;
    }
    
    $stmt = $database->executeQuery($query, $params);
    
    if ($stmt !== false) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    return false;
}

/**
 * Helper function to execute insert/update/delete queries
 * @param string $query SQL query
 * @param array $params Parameters to bind
 * @return bool Success status
 */
function executeUpdate($query, $params = []) {
    $database = getDatabase();
    if ($database === null) {
        return false;
    }
    
    $stmt = $database->executeQuery($query, $params);
    
    return $stmt !== false;
}
?>