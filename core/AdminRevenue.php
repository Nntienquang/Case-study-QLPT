<?php
/**
 * AdminRevenue Model - Quản lý doanh thu của admin
 * 
 * Admin nhận 1% commission từ mỗi booking thành công
 */

class AdminRevenue
{
    private $db;
    private $table = 'transactions';
    
    // Commission rate (1%)
    private $commission_rate = 0.01;
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Tính commission cho admin (1% của amount)
     * 
     * @param int $amount Số tiền của booking
     * @return int Commission amount
     */
    public function calculateCommission($amount)
    {
        return ceil($amount * $this->commission_rate);
    }
    
    /**
     * Lấy tổng doanh thu của admin
     * 
     * @param int $admin_id ID của admin
     * @return int Tổng doanh thu
     */
    public function getTotalRevenue($admin_id)
    {
        $query = "SELECT SUM(amount) as total 
                  FROM {$this->table} 
                  WHERE to_user = {$admin_id} 
                  AND type = 'commission'";
        
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        
        return $row['total'] ?? 0;
    }
    
    /**
     * Lấy danh sách commission của admin
     * 
     * @param int $admin_id ID của admin
     * @param int $page Trang
     * @param int $limit Số item mỗi trang
     * @return array Danh sách commission
     */
    public function getRevenue($admin_id, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT t.*, 
                         b.user_id, b.motel_id, b.deposit_amount,
                         m.title as motel_title, m.price as motel_price,
                         u.name as user_name, u.email as user_email,
                         uo.name as owner_name
                  FROM {$this->table} t
                  LEFT JOIN bookings b ON t.booking_id = b.id
                  LEFT JOIN motels m ON b.motel_id = m.id
                  LEFT JOIN users u ON b.user_id = u.id
                  LEFT JOIN users uo ON m.user_id = uo.id
                  WHERE t.to_user = {$admin_id}
                  AND t.type = 'commission'
                  ORDER BY t.created_at DESC
                  LIMIT {$offset}, {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Đếm tổng commission records của admin
     * 
     * @param int $admin_id ID của admin
     * @return int Số lượng
     */
    public function getRevenueCount($admin_id)
    {
        $query = "SELECT COUNT(*) as total 
                  FROM {$this->table} 
                  WHERE to_user = {$admin_id} 
                  AND type = 'commission'";
        
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        
        return $row['total'] ?? 0;
    }
    
    /**
     * Lấy stats doanh thu của admin
     * 
     * @param int $admin_id ID của admin
     * @return array Stats
     */
    public function getStats($admin_id)
    {
        $stats = [];
        
        // Tổng doanh thu
        $total = $this->getTotalRevenue($admin_id);
        $stats['total'] = $total;
        
        // Doanh thu tháng này
        $query = "SELECT SUM(amount) as total 
                  FROM {$this->table} 
                  WHERE to_user = {$admin_id}
                  AND type = 'commission'
                  AND MONTH(created_at) = MONTH(NOW())
                  AND YEAR(created_at) = YEAR(NOW())";
        
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        $stats['month'] = $row['total'] ?? 0;
        
        // Số lần nhận commission
        $query = "SELECT COUNT(*) as total 
                  FROM {$this->table} 
                  WHERE to_user = {$admin_id}
                  AND type = 'commission'";
        
        $result = $this->db->query($query);
        $row = $result->fetch_assoc();
        $stats['count'] = $row['total'] ?? 0;
        
        // Average commission
        $stats['average'] = $stats['count'] > 0 ? ceil($stats['total'] / $stats['count']) : 0;
        
        return $stats;
    }
    
    /**
     * Thêm commission cho admin
     * 
     * @param int $admin_id ID của admin
     * @param int $booking_id ID của booking
     * @param int $amount Commission amount
     * @return bool
     */
    public function addCommission($admin_id, $booking_id, $amount)
    {
        // Escaped values
        $admin_id_esc = $this->db->getConnection()->real_escape_string($admin_id);
        $booking_id_esc = $this->db->getConnection()->real_escape_string($booking_id);
        $amount_esc = $this->db->getConnection()->real_escape_string($amount);
        
        $query = "INSERT INTO {$this->table} 
                  (to_user, booking_id, amount, type, created_at) 
                  VALUES 
                  ({$admin_id_esc}, {$booking_id_esc}, {$amount_esc}, 'commission', NOW())";
        
        return $this->db->query($query);
    }
    
    /**
     * Lấy doanh thu theo ngày
     * 
     * @param int $admin_id ID của admin
     * @param int $days Số ngày
     * @return array Danh sách doanh thu theo ngày
     */
    public function getRevenueByDay($admin_id, $days = 30)
    {
        $query = "SELECT DATE(created_at) as date, SUM(amount) as total
                  FROM {$this->table}
                  WHERE to_user = {$admin_id}
                  AND type = 'commission'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC";
        
        return $this->db->getRows($query);
    }
}
?>
