<?php
/**
 * Payment Model
 */

class Payment {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all payments with pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $status = '') {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, b.user_id, u.name as user_name, b.motel_id, m.title as motel_title
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id";
        
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $sql .= " WHERE p.status = '$status'";
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT $offset, $limit";
        
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
        return $this->db->count('payments', $where);
    }
    
    /**
     * Get payment by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT p.*, b.user_id, b.motel_id, u.name as user_name, u.email,
                        m.title as motel_title, m.price
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id
                WHERE p.id = $id";
        return $this->db->getRow($sql);
    }
    
    /**
     * Update payment status
     */
    public function updateStatus($id, $status) {
        $id = (int)$id;
        $status = $this->db->getConnection()->real_escape_string($status);
        $data = ['status' => $status];
        return $this->db->update('payments', $data, "id = $id");
    }
    
    /**
     * Get statistics
     */
    public function getStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count('payments');
        $stats['pending'] = $this->db->count('payments', "status = 'pending'");
        $stats['held'] = $this->db->count('payments', "status = 'held'");
        $stats['released'] = $this->db->count('payments', "status = 'released'");
        
        // Get total amount
        $result = $this->db->getRow("SELECT SUM(amount) as total_amount FROM payments WHERE status = 'released'");
        $stats['total_amount'] = $result['total_amount'] ?? 0;
        
        return $stats;
    }
}

?>
