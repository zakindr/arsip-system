<?php

class Database {
    private $host = 'localhost';
    private $dbname = 'arsip';
    private $username = 'root';
    private $password = '';
    private $pdo;
    private static $instance = null;

    private function __construct() {
        $this->connect();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Backward compatibility method
    public static function connect() {
        return self::getInstance()->getConnection();
    }

    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                
                // Log successful connection
                Security::logSecurityEvent('database_connection', ['status' => 'success']);
                
            } catch (PDOException $e) {
                // Log connection failure
                Security::logSecurityEvent('database_connection', [
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ]);
                
                // Don't expose database errors to users
                error_log("Database connection failed: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
        }
        return $this->pdo;
    }

    /**
     * Execute a prepared statement with parameters
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                Security::logSecurityEvent('database_query_failed', [
                    'sql' => $sql,
                    'params' => $params,
                    'error' => $stmt->errorInfo()
                ]);
            }
            
            return $stmt;
        } catch (PDOException $e) {
            Security::logSecurityEvent('database_query_exception', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetch();
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->execute($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Insert data and return last insert ID
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->execute($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        $params = array_merge($data, $whereParams);
        $stmt = $this->execute($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Delete data
     */
    public function delete($table, $where, $whereParams = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->execute($sql, $whereParams);
        return $stmt->rowCount();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }

    /**
     * Get connection instance (backward compatibility)
     */
    public function getConnection() {
        return $this->connect();
    }

    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>