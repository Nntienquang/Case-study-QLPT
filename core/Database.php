<?php
/**
 * Database Class
 */

class Database {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Get connection
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Execute query
     */
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    /**
     * Execute prepared statement
     */
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    /**
     * Get single row
     */
    public function getRow($sql) {
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }
    
    /**
     * Get all rows
     */
    public function getRows($sql) {
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            return $rows;
        }
        return [];
    }
    
    /**
     * Insert data
     */
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $values = implode(',', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($values)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        return false;
    }
    
    /**
     * Update data
     */
    public function update($table, $data, $where) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
        }
        $set_str = implode(',', $set);
        
        $sql = "UPDATE $table SET $set_str WHERE $where";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $types = str_repeat('s', count($data));
        $stmt->bind_param($types, ...array_values($data));
        
        return $stmt->execute();
    }
    
    /**
     * Delete data
     */
    public function delete($table, $where) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->conn->query($sql);
    }
    
    /**
     * Count rows with an optional prepared WHERE clause.
     *
     * Existing callers may still pass a leading "WHERE"; new callers should
     * pass only the clause and the matching values.
     */
    public function count($table, $whereClause = '', array $params = []) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', (string)$table)) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total FROM `{$table}`";
        $whereClause = trim((string)$whereClause);
        $whereClause = preg_replace('/^WHERE\s+/i', '', $whereClause);
        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        if ($params !== []) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }

            $bindValues = [$types];
            foreach ($params as $key => $value) {
                $bindValues[] = &$params[$key];
            }
            call_user_func_array([$stmt, 'bind_param'], $bindValues);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            return 0;
        }

        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return (int)($row['total'] ?? 0);
    }
    
    /**
     * Get last error
     */
    public function getError() {
        return $this->conn->error;
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->conn->close();
    }
}

?>
