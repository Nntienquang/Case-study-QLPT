<?php
/**
 * User Controller
 */

class UserController {
    private $user;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->user = new User($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all users
     */
    public function listUsers() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $role = isset($_GET['role']) ? $_GET['role'] : '';
        
        $users = $this->user->getAll($page, ITEMS_PER_PAGE, $role);
        $total = $this->user->getTotal($role);
        $total_pages = ceil($total / ITEMS_PER_PAGE);
        
        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'role' => $role
        ];
    }
    
    /**
     * View user details
     */
    public function viewUser() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $user = $this->user->getById($id);
        
        if (!$user) {
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        return ['user' => $user];
    }
    
    /**
     * Delete user
     */
    public function deleteUser() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        
        // Prevent deleting self
        if ($id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'Không thể xóa tài khoản của chính mình';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }
        
        $user = $this->user->getById($id);
        
        if ($this->user->delete($id)) {
            if ($this->activityLog && $user) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_user',
                    'user',
                    $id,
                    [],
                    "Xóa người dùng: {$user['name']} ({$user['email']})"
                );
            }
            $_SESSION['success'] = 'Xóa người dùng thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }
}

?>
