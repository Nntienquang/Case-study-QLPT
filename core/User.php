<?php
/**
 * User Model
 */

class User {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $role = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM users";
        
        if ($role && $role !== 'all') {
            $role = $this->db->getConnection()->real_escape_string($role);
            $sql .= " WHERE role = '$role'";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT $offset, $limit";
        
        return $this->db->getRows($sql);
    }
    
    /**
     * Get total count
     */
    public function getTotal($role = '') {
        $where = '';
        if ($role && $role !== 'all') {
            $role = $this->db->getConnection()->real_escape_string($role);
            $where = "WHERE role = '$role'";
        }
        return $this->db->count('users', $where);
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT * FROM users WHERE id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $email = $this->db->getConnection()->real_escape_string($email);
        $sql = "SELECT * FROM users WHERE email = '$email'";
        return $this->db->getRow($sql);
    }
    
    /**
     * Create user
     */
    public function create($data) {
        // Validate email not exists
        if ($this->getByEmail($data['email'])) {
            return false;
        }
        
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        return $this->db->insert('users', $data);
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        $id = (int)$id;
        return $this->db->update('users', $data, "id = $id");
    }
    
    /**
     * Delete user
     */
    public function delete($id) {
        $id = (int)$id;
        return $this->db->delete('users', "id = $id");
    }
    
    /**
     * Get user statistics
     */
    public function getStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count('users');
        $stats['admin'] = $this->db->count('users', "role = 'admin'");
        $stats['owner'] = $this->db->count('users', "role = 'owner'");
        $stats['user'] = $this->db->count('users', "role = 'user'");
        
        return $stats;
    }
}

?>
