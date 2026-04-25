<?php
/**
 * UserApproval Controller - Duyệt tài khoản Owner/Staff
 * 
 * Admin duyệt hoặc từ chối tài khoản mới của Owner/Staff
 */

class UserApprovalController
{
    private $db;
    private $user;
    private $activityLog;
    private $emailNotification;
    
    public function __construct($db, $activityLog = null, $emailNotification = null)
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->activityLog = $activityLog;
        $this->emailNotification = $emailNotification;
    }
    
    /**
     * Lấy danh sách Owner/Staff chờ duyệt
     */
    public function listPendingUsers()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $role = isset($_GET['role']) ? $_GET['role'] : 'owner'; // owner, admin
        
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        $conn = $this->db->getConnection();
        $role = $conn->real_escape_string($role);
        
        // Lấy danh sách pending users
        $query = "SELECT * FROM users 
                  WHERE role = '{$role}' AND status = 'pending'
                  ORDER BY created_at DESC
                  LIMIT {$offset}, {$limit}";
        
        $users = $this->db->getRows($query);
        
        // Đếm tổng
        $count_query = "SELECT COUNT(*) as total FROM users 
                       WHERE role = '{$role}' AND status = 'pending'";
        $count_result = $this->db->getRow($count_query);
        $total = $count_result['total'] ?? 0;
        $total_pages = ceil($total / $limit);
        
        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'role' => $role
        ];
    }
    
    /**
     * Lấy danh sách tất cả users (approved, rejected, blocked)
     */
    public function listAllUsers()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        
        $conn = $this->db->getConnection();
        
        $query = "SELECT * FROM users WHERE 1=1";
        
        if ($status) {
            $status = $conn->real_escape_string($status);
            $query .= " AND status = '{$status}'";
        }
        
        if ($role) {
            $role = $conn->real_escape_string($role);
            $query .= " AND role = '{$role}'";
        }
        
        // Add pagination
        $query .= " ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
        
        $users = $this->db->getRows($query);
        
        // Count total
        $count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
        if ($status) {
            $count_query .= " AND status = '{$status}'";
        }
        if ($role) {
            $count_query .= " AND role = '{$role}'";
        }
        
        $count_result = $this->db->getRow($count_query);
        $total = $count_result['total'] ?? 0;
        $total_pages = ceil($total / $limit);
        
        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'status' => $status,
            'role' => $role
        ];
    }
    
    /**
     * Xem chi tiết user
     */
    public function viewUser()
    {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $user = $this->user->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Người dùng không tồn tại';
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }
        
        return ['user' => $user];
    }
    
    /**
     * Duyệt tài khoản
     */
    public function approveUser()
    {
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $admin_id = $_SESSION['user_id'];
        $user = $this->user->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Người dùng không tồn tại';
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }
        
        // Update status
        $conn = $this->db->getConnection();
        $query = "UPDATE users SET status = 'approved', approved_by = {$admin_id}, approved_at = NOW() WHERE id = {$id}";
        
        if ($this->db->query($query)) {
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $admin_id,
                    'approve_user',
                    'user',
                    $id,
                    [],
                    "Duyệt tài khoản {$user['role']}: {$user['name']} ({$user['email']})"
                );
            }
            
            // Send email notification
            if ($this->emailNotification) {
                $this->emailNotification->sendOwnerApprovalNotification($id);
            }
            
            $_SESSION['success'] = "Đã duyệt tài khoản {$user['name']}";
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'user_approvals.php');
        exit;
    }
    
    /**
     * Từ chối tài khoản
     */
    public function rejectUser()
    {
        if (!isset($_GET['id']) || !isset($_POST['rejection_reason'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $rejection_reason = $_POST['rejection_reason'];
        $admin_id = $_SESSION['user_id'];
        
        $user = $this->user->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Người dùng không tồn tại';
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }
        
        // Update status
        $conn = $this->db->getConnection();
        $rejection_reason_esc = $conn->real_escape_string($rejection_reason);
        $query = "UPDATE users SET status = 'rejected', approved_by = {$admin_id}, approved_at = NOW(), rejection_reason = '{$rejection_reason_esc}' WHERE id = {$id}";
        
        if ($this->db->query($query)) {
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $admin_id,
                    'reject_user',
                    'user',
                    $id,
                    [],
                    "Từ chối tài khoản {$user['role']}: {$user['name']}. Lý do: {$rejection_reason}"
                );
            }
            
            // Send email notification
            if ($this->emailNotification) {
                $this->emailNotification->sendOwnerRejectionNotification($id, $rejection_reason);
            }
            
            $_SESSION['success'] = "Đã từ chối tài khoản {$user['name']}";
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'user_approvals.php');
        exit;
    }
    
    /**
     * Khóa tài khoản
     */
    public function blockUser()
    {
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $admin_id = $_SESSION['user_id'];
        $user = $this->user->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Người dùng không tồn tại';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        // Prevent blocking self
        if ($id == $admin_id) {
            $_SESSION['error'] = 'Không thể khóa tài khoản của chính mình';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        // Update status
        $query = "UPDATE users SET status = 'blocked' WHERE id = {$id}";
        
        if ($this->db->query($query)) {
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $admin_id,
                    'block_user',
                    'user',
                    $id,
                    [],
                    "Khóa tài khoản: {$user['name']} ({$user['email']})"
                );
            }
            
            $_SESSION['success'] = "Đã khóa tài khoản {$user['name']}";
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }
    
    /**
     * Mở khóa tài khoản
     */
    public function unblockUser()
    {
        if (!isset($_GET['id'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $admin_id = $_SESSION['user_id'];
        $user = $this->user->getById($id);
        
        if (!$user) {
            $_SESSION['error'] = 'Người dùng không tồn tại';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        // Update status back to approved
        $query = "UPDATE users SET status = 'approved' WHERE id = {$id}";
        
        if ($this->db->query($query)) {
            // Log activity
            if ($this->activityLog) {
                $this->activityLog->log(
                    $admin_id,
                    'unblock_user',
                    'user',
                    $id,
                    [],
                    "Mở khóa tài khoản: {$user['name']} ({$user['email']})"
                );
            }
            
            $_SESSION['success'] = "Đã mở khóa tài khoản {$user['name']}";
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }
    
    /**
     * Lấy thống kê
     */
    public function getStats()
    {
        $stats = [];
        
        // Pending owners
        $stats['pending_owners'] = $this->db->count('users', "role = 'owner' AND status = 'pending'");
        
        // Pending staff
        $stats['pending_staff'] = $this->db->count('users', "role = 'admin' AND status = 'pending'");
        
        // Approved
        $stats['approved'] = $this->db->count('users', "status = 'approved'");
        
        // Rejected
        $stats['rejected'] = $this->db->count('users', "status = 'rejected'");
        
        // Blocked
        $stats['blocked'] = $this->db->count('users', "status = 'blocked'");
        
        return $stats;
    }
}
?>
