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
        $stmt = $this->db->getConnection()->prepare(
            "SELECT r.*, u.name as user_name, u.email, m.title as motel_title
             FROM reviews r
             LEFT JOIN users u ON r.user_id = u.id
             LEFT JOIN motels m ON r.motel_id = m.id
             WHERE r.id = ?"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $review = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $review ?: false;
    }
    
    /**
     * Delete review
     */
    public function delete($id) {
        $id = (int)$id;
        $stmt = $this->db->getConnection()->prepare('DELETE FROM reviews WHERE id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $id);
        $deleted = $stmt->execute();
        $stmt->close();
        return $deleted;
    }

    public function setStatus($id, string $status) {
        $id = (int)$id;
        if (!in_array($status, ['visible', 'hidden'], true)) {
            return false;
        }

        $stmt = $this->db->getConnection()->prepare('UPDATE reviews SET status = ? WHERE id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $status, $id);
        $updated = $stmt->execute();
        $stmt->close();
        return $updated;
    }
}

?>
