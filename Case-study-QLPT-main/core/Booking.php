<?php
/**
 * Booking Model
 */

class Booking {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all bookings with pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $status = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT b.*, u.name as user_name, u.email as user_email, m.title as motel_title
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id";
        
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $sql .= " WHERE b.status = '$status'";
        }
        
        $sql .= " ORDER BY b.created_at DESC LIMIT $offset, $limit";
        
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
        return $this->db->count('bookings', $where);
    }
    
    /**
     * Get booking by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT b.*, u.name as user_name, u.email as user_email, u.phone as user_phone,
                        m.title as motel_title, m.price, m.address
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id
                WHERE b.id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Update booking status
     */
    public function updateStatus($id, $status) {
        $id = (int)$id;
        $status = $this->db->getConnection()->real_escape_string($status);
        $data = ['status' => $status];
        return $this->db->update('bookings', $data, "id = $id");
    }
    
    /**
     * Delete booking
     */
    public function delete($id) {
        $id = (int)$id;
        return $this->db->delete('bookings', "id = $id");
    }
    
    /**
     * Get booking statistics
     */
    public function getStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count('bookings');
        $stats['pending'] = $this->db->count('bookings', "status = 'pending'");
        $stats['paid'] = $this->db->count('bookings', "status = 'paid'");
        $stats['accepted'] = $this->db->count('bookings', "status = 'accepted'");
        $stats['completed'] = $this->db->count('bookings', "status = 'completed'");
        
        return $stats;
    }
}

?>
