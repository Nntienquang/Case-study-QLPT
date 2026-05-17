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
        
        $sql = "SELECT p.*, b.user_id, b.booking_code, b.payment_status AS booking_payment_status, b.booking_status, u.name as user_name, b.motel_id, m.title as motel_title
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id";
        
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $sql .= " WHERE p.payment_status = '$status'";
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
            $where = "payment_status = '$status'";
        }
        return $this->db->count('payments', $where);
    }
    
    /**
     * Get payment by ID
     */
    public function getById($id) {
        $id = (int)$id;
        $sql = "SELECT p.*, b.user_id, b.owner_id, b.motel_id, b.status AS booking_legacy_status,
                        u.name as user_name, u.email,
                        owner.name AS owner_name, owner.email AS owner_email,
                        m.title as motel_title, m.price, b.booking_code, b.booking_status, b.payment_status AS booking_payment_status
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN users owner ON b.owner_id = owner.id
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
        $legacy = match ($status) {
            'paid' => 'held',
            'refunded' => 'refunded',
            default => 'pending',
        };
        $data = ['payment_status' => $status, 'status' => $legacy, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'paid') {
            $data['paid_at'] = date('Y-m-d H:i:s');
        }
        return $this->db->update('payments', $data, "id = $id");
    }
    
    /**
     * Get statistics
     */
    public function getStats() {
        $stats = [];
        
        $stats['total'] = $this->db->count('payments');
        $stats['pending'] = $this->db->count('payments', "payment_status = 'pending'");
        $stats['processing'] = $this->db->count('payments', "payment_status = 'processing'");
        $stats['paid'] = $this->db->count('payments', "payment_status = 'paid'");
        
        // Get total amount
        $result = $this->db->getRow("SELECT SUM(amount) as total_amount FROM payments WHERE payment_status = 'paid'");
        $stats['total_amount'] = $result['total_amount'] ?? 0;
        
        return $stats;
    }
}

?>
