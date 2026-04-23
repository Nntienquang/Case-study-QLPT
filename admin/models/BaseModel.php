<?php
/**
 * PHASE 3: Base Model with Global Database Connection
 */

class BaseModel {
    protected $table;
    protected $conn;

    public function __construct() {
        // Get global database connection from index.php or database.php
        global $conn;
        if (!isset($conn)) {
            require_once __DIR__ . '/../config/database.php';
        }
        $this->conn = $conn;
    }

    // Get all records
    public function all() {
        $query = "SELECT * FROM " . $this->table;
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll();
    }

    // Find by ID
    public function find($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Count records
    public function count() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table;
        $stmt = $this->conn->query($query);
        $result = $stmt->fetch();
        return $result['count'];
    }

    // Get paginated records
    public function paginate($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $query = "SELECT * FROM " . $this->table . " LIMIT ? OFFSET ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Custom query
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Get single result from custom query
    public function queryOne($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // Create
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $query = "INSERT INTO " . $this->table . " (" . $columns . ") VALUES (" . $placeholders . ")";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(array_values($data));
    }

    // Update
    public function update($id, $data) {
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $query = "UPDATE " . $this->table . " SET " . $set . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $values = array_values($data);
        $values[] = $id;
        return $stmt->execute($values);
    }

    // Delete
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Where clause
    public function where($column, $operator, $value) {
        $query = "SELECT * FROM " . $this->table . " WHERE " . $column . " " . $operator . " ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }
}
?>
