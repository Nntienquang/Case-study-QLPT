<?php
/**
 * Motel Model
 */

class Motel {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all motels with pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $status = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT m.*, u.name as owner_name, c.name as category_name, d.name as district_name
                FROM motels m
                LEFT JOIN users u ON m.user_id = u.id
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN districts d ON m.district_id = d.id";
        
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $sql .= " WHERE m.status = '$status'";
        }
        
        $sql .= " ORDER BY m.created_at DESC LIMIT $offset, $limit";
        
        return $this->db->getRows($sql);
    }
    
    /**
     * Get total count
     */
    public function getTotal($status = '') {
        $where = '';
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $where = "WHERE status = '$status'";
        }
        return $this->db->count('motels', $where);
    }
    
    /**
     * Get motel by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT m.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
                        c.name as category_name, d.name as district_name
                FROM motels m
                LEFT JOIN users u ON m.user_id = u.id
                LEFT JOIN categories c ON m.category_id = c.id
                LEFT JOIN districts d ON m.district_id = d.id
                WHERE m.id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Get motel images
     */
    public function getImages($motel_id) {
        $motel_id = (int)$motel_id;
        $sql = "SELECT * FROM motel_images WHERE motel_id = $motel_id";
        return $this->db->getRows($sql);
    }
    
    /**
     * Get motel utilities
     */
    public function getUtilities($motel_id) {
        $motel_id = (int)$motel_id;
        $sql = "SELECT u.* FROM utilities u
                JOIN motel_utilities mu ON u.id = mu.utility_id
                WHERE mu.motel_id = $motel_id";
        return $this->db->getRows($sql);
    }
    
    /**
     * Approve motel
     */
    public function approve($id) {
        $id = (int)$id;
        $data = ['status' => STATUS_APPROVED];
        return $this->db->update('motels', $data, "id = $id");
    }
    
    /**
     * Reject/Hide motel
     */
    public function hide($id) {
        $id = (int)$id;
        $data = ['status' => STATUS_HIDDEN];
        return $this->db->update('motels', $data, "id = $id");
    }
    
    /**
     * Delete motel
     */
    public function delete($id) {
        $id = (int)$id;
        
        // Delete related images
        $this->db->delete('motel_images', "motel_id = $id");
        
        // Delete related utilities
        $this->db->delete('motel_utilities', "motel_id = $id");
        
        // Delete related bookings
        $this->db->delete('bookings', "motel_id = $id");
        
        // Delete related reviews
        $this->db->delete('reviews', "motel_id = $id");
        
        // Delete related favorites
        $this->db->delete('favorites', "motel_id = $id");
        
        // Delete motel
        return $this->db->delete('motels', "id = $id");
    }
    
    /**
     * Get statistics
     */
    public function getStats() {
        $stats = [];
        
        // Total motels
        $stats['total'] = $this->db->count('motels');
        
        // Pending motels
        $stats['pending'] = $this->db->count('motels', "status = 'pending'");
        
        // Approved motels
        $stats['approved'] = $this->db->count('motels', "status = 'approved'");
        
        // Hidden motels
        $stats['hidden'] = $this->db->count('motels', "status = 'hidden'");
        
        return $stats;
    }
}

?>
