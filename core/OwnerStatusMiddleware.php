<?php
/**
 * Owner Status Middleware
 * 
 * Kiểm tra xem chủ trọ có được duyệt hay không trước khi posting phòng
 */

class OwnerStatusMiddleware {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Kiểm tra xem owner có thể posting phòng không
     * 
     * @param int $user_id - ID của owner
     * @return array - ['allowed' => bool, 'status' => string, 'message' => string]
     */
    public function canPostMotel($user_id) {
        $user_sql = "SELECT id, status, role FROM users WHERE id = " . (int)$user_id;
        $user = $this->db->getRow($user_sql);
        
        if (!$user) {
            return [
                'allowed' => false,
                'status' => 'not_found',
                'message' => 'Người dùng không tồn tại'
            ];
        }
        
        // Kiểm tra role
        if ($user['role'] !== 'owner') {
            return [
                'allowed' => false,
                'status' => 'invalid_role',
                'message' => 'Chỉ chủ trọ mới có thể đăng tin phòng'
            ];
        }
        
        // Kiểm tra status
        switch ($user['status']) {
            case 'pending':
                return [
                    'allowed' => false,
                    'status' => 'pending_approval',
                    'message' => 'Tài khoản của bạn đang chờ duyệt. Vui lòng quay lại sau.'
                ];
            
            case 'rejected':
                return [
                    'allowed' => false,
                    'status' => 'rejected',
                    'message' => 'Tài khoản của bạn bị từ chối. Vui lòng liên hệ quản trị viên để biết thêm thông tin.'
                ];
            
            case 'blocked':
                return [
                    'allowed' => false,
                    'status' => 'blocked',
                    'message' => 'Tài khoản của bạn bị khóa. Vui lòng liên hệ hỗ trợ khách hàng.'
                ];
            
            case 'approved':
                return [
                    'allowed' => true,
                    'status' => 'approved',
                    'message' => 'Bạn có quyền đăng tin phòng'
                ];
            
            default:
                return [
                    'allowed' => false,
                    'status' => 'unknown',
                    'message' => 'Trạng thái tài khoản không xác định'
                ];
        }
    }
    
    /**
     * Middleware check - redirect nếu không được phép
     * Gọi ở đầu trang owner
     */
    public function checkOwnerAccess($user_id, $redirect_url = null) {
        $check = $this->canPostMotel($user_id);
        
        if (!$check['allowed']) {
            $_SESSION['warning'] = $check['message'];
            
            if ($redirect_url) {
                header('Location: ' . $redirect_url);
                exit;
            }
        }
        
        return $check;
    }
    
    /**
     * Lấy thông tin trạng thái duyệt của owner
     */
    public function getOwnerApprovalInfo($user_id) {
        $sql = "SELECT 
                    id, 
                    status, 
                    approved_by, 
                    approved_at, 
                    rejection_reason,
                    created_at
                FROM users 
                WHERE id = " . (int)$user_id;
        
        return $this->db->getRow($sql);
    }
    
    /**
     * Lấy danh sách owner cần duyệt
     */
    public function getPendingOwners($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    id, 
                    name, 
                    email, 
                    phone, 
                    idcard_number,
                    status, 
                    created_at 
                FROM users 
                WHERE role = 'owner' AND status = 'pending'
                ORDER BY created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        return $this->db->getRows($sql);
    }
    
    /**
     * Lấy số lượng owner chờ duyệt
     */
    public function getPendingOwnersCount() {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'owner' AND status = 'pending'";
        $result = $this->db->getRow($sql);
        return $result['total'] ?? 0;
    }
    
    /**
     * Kiểm tra owner đã được duyệt bao lâu
     * @return string - Relative time (e.g., "3 days ago")
     */
    public function getApprovalDuration($user_id) {
        $user = $this->getOwnerApprovalInfo($user_id);
        
        if (!$user || !$user['approved_at']) {
            return 'Chưa được duyệt';
        }
        
        $approved_time = strtotime($user['approved_at']);
        $now = time();
        $diff = $now - $approved_time;
        
        if ($diff < 60) {
            return 'Vừa mới duyệt';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "{$minutes} phút trước";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "{$hours} giờ trước";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "{$days} ngày trước";
        } else {
            $weeks = floor($diff / 604800);
            return "{$weeks} tuần trước";
        }
    }
}
?>
