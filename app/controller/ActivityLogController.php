<?php
/**
 * ActivityLog Controller - Quản lý nhật ký hoạt động
 * 
 * Admin theo dõi tất cả hoạt động của admin khác
 */

class ActivityLogController
{
    private $db;
    private $activityLog;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->activityLog = new ActivityLog($db);
    }
    
    /**
     * Lấy danh sách nhật ký hoạt động (phân trang)
     */
    public function listLogs()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $admin_id = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : null;
        $entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : '';
        $date = isset($_GET['date']) ? $_GET['date'] : '';
        
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        $conn = $this->db->getConnection();
        
        // Build query
        $query = "SELECT * FROM activity_logs WHERE 1=1";
        
        if ($admin_id) {
            $query .= " AND admin_id = {$admin_id}";
        }
        
        if ($entity_type) {
            $entity_type = $conn->real_escape_string($entity_type);
            $query .= " AND entity_type = '{$entity_type}'";
        }
        
        if ($date) {
            $date = $conn->real_escape_string($date);
            $query .= " AND DATE(created_at) = '{$date}'";
        }
        
        // Count total before pagination
        $count_query = str_replace('SELECT *', 'SELECT COUNT(*) as total', $query);
        $count_result = $this->db->getRow($count_query);
        $total = $count_result['total'] ?? 0;
        $total_pages = ceil($total / $limit);
        
        // Add pagination and ordering
        $query .= " ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
        
        $logs = $this->db->getRows($query);
        
        // Get admin names for logs
        if (!empty($logs)) {
            $logs = $this->enrichLogsWithAdminData($logs);
        }
        
        return [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'admin_id' => $admin_id,
            'entity_type' => $entity_type,
            'date' => $date
        ];
    }
    
    /**
     * Lấy danh sách nhật ký cho admin cụ thể
     */
    public function getAdminLogs()
    {
        if (!isset($_GET['admin_id'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'activity_logs.php');
            exit;
        }
        
        $admin_id = (int)$_GET['admin_id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        // Get admin info
        $admin_query = "SELECT * FROM users WHERE id = {$admin_id} AND role = 'admin'";
        $admin = $this->db->getRow($admin_query);
        
        if (!$admin) {
            $_SESSION['error'] = 'Admin không tồn tại';
            header('Location: ' . ADMIN_URL . 'activity_logs.php');
            exit;
        }
        
        // Get logs
        $query = "SELECT * FROM activity_logs WHERE admin_id = {$admin_id}
                  ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
        $logs = $this->db->getRows($query);
        
        // Count total
        $count_query = "SELECT COUNT(*) as total FROM activity_logs WHERE admin_id = {$admin_id}";
        $count_result = $this->db->getRow($count_query);
        $total = $count_result['total'] ?? 0;
        $total_pages = ceil($total / $limit);
        
        return [
            'admin' => $admin,
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages
        ];
    }
    
    /**
     * Lấy danh sách nhật ký cho entity cụ thể
     */
    public function getEntityLogs()
    {
        if (!isset($_GET['entity_type']) || !isset($_GET['entity_id'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'activity_logs.php');
            exit;
        }
        
        $entity_type = $_GET['entity_type'];
        $entity_id = (int)$_GET['entity_id'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        $conn = $this->db->getConnection();
        $entity_type = $conn->real_escape_string($entity_type);
        
        // Get logs
        $query = "SELECT * FROM activity_logs 
                  WHERE entity_type = '{$entity_type}' AND entity_id = {$entity_id}
                  ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
        $logs = $this->db->getRows($query);
        
        // Count total
        $count_query = "SELECT COUNT(*) as total FROM activity_logs 
                       WHERE entity_type = '{$entity_type}' AND entity_id = {$entity_id}";
        $count_result = $this->db->getRow($count_query);
        $total = $count_result['total'] ?? 0;
        $total_pages = ceil($total / $limit);
        
        // Enrich with admin data
        $logs = $this->enrichLogsWithAdminData($logs);
        
        return [
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages
        ];
    }
    
    /**
     * Xem chi tiết nhật ký
     */
    public function viewLog()
    {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'activity_logs.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        
        $query = "SELECT * FROM activity_logs WHERE id = {$id}";
        $log = $this->db->getRow($query);
        
        if (!$log) {
            $_SESSION['error'] = 'Nhật ký không tồn tại';
            header('Location: ' . ADMIN_URL . 'activity_logs.php');
            exit;
        }
        
        // Get admin info
        $admin_query = "SELECT id, name, email FROM users WHERE id = {$log['admin_id']}";
        $admin = $this->db->getRow($admin_query);
        $log['admin'] = $admin;
        
        // Parse JSON fields
        if ($log['old_value']) {
            $log['old_value_parsed'] = json_decode($log['old_value'], true) ?: [];
        }
        if ($log['new_value']) {
            $log['new_value_parsed'] = json_decode($log['new_value'], true) ?: [];
        }
        
        return ['log' => $log];
    }
    
    /**
     * Lấy thống kê hoạt động
     */
    public function getStats()
    {
        // Tổng nhật ký hôm nay
        $today_query = "SELECT COUNT(*) as total FROM activity_logs WHERE DATE(created_at) = CURDATE()";
        $today = $this->db->getRow($today_query);
        
        // Tổng nhật ký tuần này
        $week_query = "SELECT COUNT(*) as total FROM activity_logs WHERE YEARWEEK(created_at) = YEARWEEK(NOW())";
        $week = $this->db->getRow($week_query);
        
        // Tổng nhật ký tháng này
        $month_query = "SELECT COUNT(*) as total FROM activity_logs WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())";
        $month = $this->db->getRow($month_query);
        
        // Tổng tất cả
        $total_query = "SELECT COUNT(*) as total FROM activity_logs";
        $total = $this->db->getRow($total_query);
        
        // Top 5 actions
        $actions_query = "SELECT action, COUNT(*) as count FROM activity_logs GROUP BY action ORDER BY count DESC LIMIT 5";
        $top_actions = $this->db->getRows($actions_query);
        
        // Top 5 admins
        $admins_query = "SELECT admin_id, COUNT(*) as count FROM activity_logs GROUP BY admin_id ORDER BY count DESC LIMIT 5";
        $top_admins_raw = $this->db->getRows($admins_query);
        
        $top_admins = [];
        if (!empty($top_admins_raw)) {
            foreach ($top_admins_raw as $admin_log) {
                $admin_query = "SELECT id, name FROM users WHERE id = {$admin_log['admin_id']}";
                $admin = $this->db->getRow($admin_query);
                $top_admins[] = [
                    'admin' => $admin,
                    'count' => $admin_log['count']
                ];
            }
        }
        
        return [
            'today' => $today['total'] ?? 0,
            'week' => $week['total'] ?? 0,
            'month' => $month['total'] ?? 0,
            'total' => $total['total'] ?? 0,
            'top_actions' => $top_actions,
            'top_admins' => $top_admins
        ];
    }
    
    /**
     * Xóa nhật ký cũ (dữ liệu lưu trữ)
     */
    public function cleanOldLogs()
    {
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 90;
        
        if ($this->activityLog->cleanOldLogs($days)) {
            $_SESSION['success'] = "Đã xóa nhật ký cũ hơn {$days} ngày";
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'activity_logs.php');
        exit;
    }
    
    /**
     * Helper: Enriches logs with admin data
     */
    private function enrichLogsWithAdminData($logs)
    {
        $admin_cache = [];
        
        foreach ($logs as &$log) {
            if (!isset($admin_cache[$log['admin_id']])) {
                $admin_query = "SELECT id, name, email FROM users WHERE id = {$log['admin_id']}";
                $admin_cache[$log['admin_id']] = $this->db->getRow($admin_query);
            }
            $log['admin'] = $admin_cache[$log['admin_id']];
            
            // Parse JSON if present
            if ($log['old_value']) {
                $log['old_value_parsed'] = json_decode($log['old_value'], true) ?: [];
            }
            if ($log['new_value']) {
                $log['new_value_parsed'] = json_decode($log['new_value'], true) ?: [];
            }
        }
        
        return $logs;
    }
}
?>
