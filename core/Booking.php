<?php
/**
 * Booking Model
 */

class Booking {
    private $db;
    private const FILTER_STATUSES = ['pending', 'paid', 'accepted', 'completed', 'rejected', 'cancelled'];
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get all bookings with pagination
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $status = '') {
        $page = max(1, (int)$page);
        $limit = max(1, (int)$limit);
        $offset = ($page - 1) * $limit;
        $status = self::filterStatus((string)$status);
        
        $sql = "SELECT b.*, u.name as user_name, u.email as user_email, m.title as motel_title
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN motels m ON b.motel_id = m.id";
        
        if ($status !== '') {
            $sql .= ' WHERE b.status = ?';
        }
        
        $sql .= " ORDER BY b.created_at DESC LIMIT $offset, $limit";
        
        $stmt = $this->db->getConnection()->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($status !== '') {
            $stmt->bind_param('s', $status);
        }
        $stmt->execute();
        $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $bookings;
    }
    
    /**
     * Get total count
     */
    public function getTotal($status = '') {
        $status = self::filterStatus((string)$status);
        return $status === ''
            ? $this->db->count('bookings')
            : $this->db->count('bookings', 'status = ?', [$status]);
    }

    public static function filterStatus(string $status): string {
        return in_array($status, self::FILTER_STATUSES, true) ? $status : '';
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
