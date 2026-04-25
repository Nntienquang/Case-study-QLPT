<?php
/**
 * ActivityLog Model - Nhật ký hoạt động của Admin
 * 
 * Ghi lại tất cả hành động admin: xóa, sửa, duyệt, từ chối, etc.
 */

class ActivityLog
{
    private $db;
    private $table = 'activity_logs';
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Ghi log hành động
     * 
     * @param int $admin_id Admin thực hiện hành động
     * @param string $action Loại hành động (create, update, delete, approve, reject, etc)
     * @param string $entity_type Loại entity (user, motel, booking, payment, report, etc)
     * @param int $entity_id ID của entity
     * @param array $changes Thay đổi (old_value, new_value)
     * @param string $description Mô tả chi tiết
     * @return bool
     */
    public function log($admin_id, $action, $entity_type, $entity_id, $changes = [], $description = '')
    {
        $conn = $this->db->getConnection();
        $admin_id = (int)$admin_id;
        $action = $conn->real_escape_string($action);
        $entity_type = $conn->real_escape_string($entity_type);
        $entity_id = (int)$entity_id;
        $old_value = isset($changes['old']) ? "'" . $conn->real_escape_string(json_encode($changes['old'])) . "'" : 'NULL';
        $new_value = isset($changes['new']) ? "'" . $conn->real_escape_string(json_encode($changes['new'])) . "'" : 'NULL';
        $description = $conn->real_escape_string($description);
        $ip_address = $this->getClientIP();
        $user_agent = $conn->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $query = "INSERT INTO {$this->table} 
                  (admin_id, action, entity_type, entity_id, old_value, new_value, description, ip_address, user_agent)
                  VALUES 
                  ({$admin_id}, '{$action}', '{$entity_type}', {$entity_id}, {$old_value}, {$new_value}, '{$description}', '{$ip_address}', '{$user_agent}')";
        
        return $this->db->query($query);
    }
    
    /**
     * Lấy tất cả logs (có phân trang)
     * 
     * @param int $page
     * @param int $limit
     * @param string $entity_type Lọc theo loại entity
     * @return array
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $entity_type = '')
    {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT l.*, u.name as admin_name, u.email as admin_email
                  FROM {$this->table} l
                  LEFT JOIN users u ON l.admin_id = u.id";
        
        if ($entity_type) {
            $entity_type = $this->db->getConnection()->real_escape_string($entity_type);
            $query .= " WHERE l.entity_type = '{$entity_type}'";
        }
        
        $query .= " ORDER BY l.created_at DESC LIMIT {$offset}, {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Đếm tổng logs
     * 
     * @param string $entity_type
     * @return int
     */
    public function getTotal($entity_type = '')
    {
        $where = '';
        if ($entity_type) {
            $entity_type = $this->db->getConnection()->real_escape_string($entity_type);
            $where = "WHERE entity_type = '{$entity_type}'";
        }
        return $this->db->count($this->table, $where);
    }
    
    /**
     * Lấy logs của admin
     * 
     * @param int $admin_id
     * @param int $limit
     * @return array
     */
    public function getByAdmin($admin_id, $limit = 100)
    {
        $admin_id = (int)$admin_id;
        $query = "SELECT * FROM {$this->table} 
                  WHERE admin_id = {$admin_id}
                  ORDER BY created_at DESC
                  LIMIT {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Lấy logs của entity
     * 
     * @param string $entity_type
     * @param int $entity_id
     * @return array
     */
    public function getByEntity($entity_type, $entity_id)
    {
        $conn = $this->db->getConnection();
        $entity_type = $conn->real_escape_string($entity_type);
        $entity_id = (int)$entity_id;
        
        $query = "SELECT l.*, u.name as admin_name
                  FROM {$this->table} l
                  LEFT JOIN users u ON l.admin_id = u.id
                  WHERE l.entity_type = '{$entity_type}' AND l.entity_id = {$entity_id}
                  ORDER BY l.created_at DESC";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Lấy logs theo ngày
     * 
     * @param string $date Định dạng Y-m-d
     * @param int $limit
     * @return array
     */
    public function getByDate($date, $limit = 1000)
    {
        $conn = $this->db->getConnection();
        $date = $conn->real_escape_string($date);
        
        $query = "SELECT l.*, u.name as admin_name
                  FROM {$this->table} l
                  LEFT JOIN users u ON l.admin_id = u.id
                  WHERE DATE(l.created_at) = '{$date}'
                  ORDER BY l.created_at DESC
                  LIMIT {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Lấy logs gần đây
     * 
     * @param int $limit
     * @return array
     */
    public function getRecent($limit = 50)
    {
        $limit = (int)$limit;
        $query = "SELECT l.*, u.name as admin_name, u.email
                  FROM {$this->table} l
                  LEFT JOIN users u ON l.admin_id = u.id
                  ORDER BY l.created_at DESC
                  LIMIT {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Get IP Address
     * 
     * @return string
     */
    private function getClientIP()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        
        return trim($ip);
    }
    
    /**
     * Xóa logs cũ (hơn 90 ngày)
     * 
     * @param int $days Số ngày
     * @return bool
     */
    public function cleanOldLogs($days = 90)
    {
        $query = "DELETE FROM {$this->table} 
                  WHERE created_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)";
        
        return $this->db->query($query);
    }
    
    /**
     * Lấy thống kê activity
     * 
     * @return array
     */
    public function getStats()
    {
        $stats = [];
        
        // Tổng logs
        $stats['total'] = $this->db->count($this->table);
        
        // Hôm nay
        $stats['today'] = $this->db->count($this->table, "DATE(created_at) = CURDATE()");
        
        // Tuần này
        $stats['week'] = $this->db->count($this->table, "WEEK(created_at) = WEEK(NOW())");
        
        // Top actions
        $query = "SELECT action, COUNT(*) as count FROM {$this->table} GROUP BY action ORDER BY count DESC LIMIT 10";
        $stats['top_actions'] = $this->db->getRows($query);
        
        // Top admins
        $query = "SELECT u.id, u.name, COUNT(l.id) as action_count
                  FROM {$this->table} l
                  JOIN users u ON l.admin_id = u.id
                  GROUP BY l.admin_id
                  ORDER BY action_count DESC
                  LIMIT 10";
        $stats['top_admins'] = $this->db->getRows($query);
        
        return $stats;
    }
}
?>
