<?php
/**
 * Report Model - Báo cáo vi phạm
 * 
 * Quản lý báo cáo/khiếu nại từ users về phòng trọ hoặc chủ trọ
 */

class Report
{
    private $db;
    private $table = 'reports';
    
    public function __construct($database)
    {
        $this->db = $database;
    }
    
    /**
     * Tạo báo cáo mới
     * 
     * @param array $data Thông tin báo cáo
     * @return bool
     */
    public function create($data)
    {
        $conn = $this->db->getConnection();
        $reporter_id = $conn->real_escape_string($data['reporter_id'] ?? 0);
        $reported_user_id = isset($data['reported_user_id']) ? $conn->real_escape_string($data['reported_user_id']) : 'NULL';
        $motel_id = isset($data['motel_id']) ? $conn->real_escape_string($data['motel_id']) : 'NULL';
        $report_type = $conn->real_escape_string($data['report_type'] ?? 'other');
        $title = $conn->real_escape_string($data['title'] ?? '');
        $description = $conn->real_escape_string($data['description'] ?? '');
        $evidence_image = isset($data['evidence_image']) ? "'" . $conn->real_escape_string($data['evidence_image']) . "'" : 'NULL';
        
        $query = "INSERT INTO {$this->table} 
                  (reporter_id, reported_user_id, motel_id, report_type, title, description, evidence_image, status)
                  VALUES 
                  ({$reporter_id}, {$reported_user_id}, {$motel_id}, '{$report_type}', '{$title}', '{$description}', {$evidence_image}, 'pending')";
        
        return $this->db->query($query);
    }
    
    /**
     * Lấy tất cả báo cáo (có phân trang)
     * 
     * @param int $page Trang
     * @param int $limit Số item/trang
     * @param string $status Lọc theo status
     * @return array
     */
    public function getAll($page = 1, $limit = ITEMS_PER_PAGE, $status = '')
    {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT r.*, 
                         u.name as reporter_name, u.email as reporter_email,
                         ru.name as reported_user_name, ru.role as reported_user_role,
                         m.title as motel_title, m.price as motel_price,
                         a.name as handler_name
                  FROM {$this->table} r
                  LEFT JOIN users u ON r.reporter_id = u.id
                  LEFT JOIN users ru ON r.reported_user_id = ru.id
                  LEFT JOIN motels m ON r.motel_id = m.id
                  LEFT JOIN users a ON r.handled_by = a.id";
        
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $query .= " WHERE r.status = '{$status}'";
        }
        
        $query .= " ORDER BY r.created_at DESC LIMIT {$offset}, {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Đếm tổng báo cáo
     * 
     * @param string $status
     * @return int
     */
    public function getTotal($status = '')
    {
        $where = '';
        if ($status) {
            $status = $this->db->getConnection()->real_escape_string($status);
            $where = "WHERE status = '{$status}'";
        }
        return $this->db->count($this->table, $where);
    }
    
    /**
     * Lấy báo cáo theo ID
     * 
     * @param int $id
     * @return array
     */
    public function getById($id)
    {
        $id = (int)$id;
        $query = "SELECT r.*, 
                         u.name as reporter_name, u.email as reporter_email, u.phone as reporter_phone,
                         ru.name as reported_user_name, ru.email as reported_user_email, ru.phone as reported_user_phone,
                         m.title as motel_title, m.price as motel_price, m.address as motel_address,
                         a.name as handler_name, a.email as handler_email
                  FROM {$this->table} r
                  LEFT JOIN users u ON r.reporter_id = u.id
                  LEFT JOIN users ru ON r.reported_user_id = ru.id
                  LEFT JOIN motels m ON r.motel_id = m.id
                  LEFT JOIN users a ON r.handled_by = a.id
                  WHERE r.id = {$id}";
        
        return $this->db->getRow($query);
    }
    
    /**
     * Cập nhật trạng thái báo cáo
     * 
     * @param int $id ID báo cáo
     * @param string $status Trạng thái mới (investigating, resolved, rejected, closed)
     * @param int $admin_id Admin xử lý
     * @param string $admin_note Ghi chú
     * @return bool
     */
    public function updateStatus($id, $status, $admin_id, $admin_note = '')
    {
        $id = (int)$id;
        $conn = $this->db->getConnection();
        $status = $conn->real_escape_string($status);
        $admin_id = (int)$admin_id;
        $admin_note = $conn->real_escape_string($admin_note);
        
        $query = "UPDATE {$this->table} 
                  SET status = '{$status}', 
                      handled_by = {$admin_id},
                      admin_note = '{$admin_note}',
                      handled_at = NOW()
                  WHERE id = {$id}";
        
        return $this->db->query($query);
    }
    
    /**
     * Lấy báo cáo chưa xử lý (pending)
     * 
     * @return int Số báo cáo pending
     */
    public function getPendingCount()
    {
        return $this->db->count($this->table, "status = 'pending'");
    }
    
    /**
     * Lấy thống kê báo cáo
     * 
     * @return array
     */
    public function getStats()
    {
        $stats = [];
        
        // Tổng báo cáo
        $stats['total'] = $this->db->count($this->table);
        
        // Pending
        $stats['pending'] = $this->db->count($this->table, "status = 'pending'");
        
        // Investigating
        $stats['investigating'] = $this->db->count($this->table, "status = 'investigating'");
        
        // Resolved
        $stats['resolved'] = $this->db->count($this->table, "status = 'resolved'");
        
        // Rejected
        $stats['rejected'] = $this->db->count($this->table, "status = 'rejected'");
        
        // Loại báo cáo phổ biến
        $query = "SELECT report_type, COUNT(*) as count FROM {$this->table} GROUP BY report_type ORDER BY count DESC";
        $stats['by_type'] = $this->db->getRows($query);
        
        return $stats;
    }
    
    /**
     * Lấy báo cáo của người dùng
     * 
     * @param int $user_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getUserReports($user_id, $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $user_id = (int)$user_id;
        
        $query = "SELECT r.*, 
                         ru.name as reported_user_name,
                         m.title as motel_title,
                         a.name as handler_name
                  FROM {$this->table} r
                  LEFT JOIN users ru ON r.reported_user_id = ru.id
                  LEFT JOIN motels m ON r.motel_id = m.id
                  LEFT JOIN users a ON r.handled_by = a.id
                  WHERE r.reporter_id = {$user_id}
                  ORDER BY r.created_at DESC
                  LIMIT {$offset}, {$limit}";
        
        return $this->db->getRows($query);
    }
    
    /**
     * Đếm báo cáo của người dùng
     * 
     * @param int $user_id
     * @return int
     */
    public function getUserReportCount($user_id)
    {
        $user_id = (int)$user_id;
        return $this->db->count($this->table, "reporter_id = {$user_id}");
    }
    
    /**
     * Xóa báo cáo
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id)
    {
        $id = (int)$id;
        return $this->db->query("DELETE FROM {$this->table} WHERE id = {$id}");
    }
}
?>
