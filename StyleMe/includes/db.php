<?php
require_once 'config.php';

class Database
{
    private $conn;
    private $transactionActive = false;

    public function __construct()
    {
        // Use mysqli exceptions so we can catch detailed errors
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // Set charset and timezone
            $this->conn->set_charset("utf8mb4");
            $this->conn->query("SET time_zone = '+05:30'"); // Sri Lanka timezone

            // Set SQL mode for better data integrity (removed NO_AUTO_CREATE_USER which is removed in MySQL 8+)
            $this->conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        } catch (mysqli_sql_exception $e) {
            // Log the real mysqli error
            error_log("Database connection error: " . $e->getMessage());

            // In production hide details, in development propagate the real message for debugging
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
                throw new Exception("Database connection failed. Please try again later.");
            } else {
                throw new Exception($e->getMessage());
            }
        }
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->conn->prepare($sql);

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            if (!empty($params)) {
                $types = '';
                $values = [];

                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param)) {
                        $types .= 'd';
                    } elseif (is_null($param)) {
                        $types .= 's';
                        $param = null;
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }

                if (!$stmt->bind_param($types, ...$values)) {
                    throw new Exception("Bind param failed: " . $stmt->error);
                }
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Database operation failed");
        }
    }

    public function fetchAll($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchOne($sql, $params = [])
    {
        $stmt = $this->query($sql, $params);
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function lastInsertId()
    {
        return $this->conn->insert_id;
    }

    public function beginTransaction()
    {
        if (!$this->transactionActive) {
            $this->conn->autocommit(false);
            $this->transactionActive = true;
        }
    }

    public function commit()
    {
        if ($this->transactionActive) {
            $this->conn->commit();
            $this->conn->autocommit(true);
            $this->transactionActive = false;
        }
    }

    public function rollback()
    {
        if ($this->transactionActive) {
            $this->conn->rollback();
            $this->conn->autocommit(true);
            $this->transactionActive = false;
        }
    }

    public function escape($string)
    {
        return $this->conn->real_escape_string($string);
    }

    public function __destruct()
    {
        if ($this->transactionActive) {
            $this->rollback();
        }

        if ($this->conn) {
            $this->conn->close();
        }
    }
}

// Initialize database connection with error handling
try {
    $db = new Database();
} catch (Exception $e) {
    error_log("Failed to initialize database: " . $e->getMessage());

    // In production, show a generic error message
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
        die(json_encode(['success' => false, 'message' => 'Service temporarily unavailable. Please try again later.']));
    } else {
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
    }
}
