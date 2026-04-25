<?php
/**
 * Review Model
 */

class Review {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all reviews with pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT r.*, u.name as user_name, u.email, m.title as motel_title
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN motels m ON r.motel_id = m.id
                ORDER BY r.created_at DESC LIMIT $offset, $limit";
        
        return $this->db->getRows($sql);
    }
    
    /**
     * Get total count
     */
    public function getTotal() {
        return $this->db->count('reviews');
    }
    
    /**
     * Get review by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT r.*, u.name as user_name, u.email, m.title as motel_title
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN motels m ON r.motel_id = m.id
                WHERE r.id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Delete review
     */
    public function delete($id) {
        $id = (int)$id;
        return $this->db->delete('reviews', "id = $id");
    }
}

?>
