<?php
/**
 * Utility Controller
 */

class UtilityController {
    private $utility;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->utility = new Utility($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all utilities
     */
    public function listUtilities() {
        $utilities = $this->utility->getAll();
        return ['utilities' => $utilities];
    }
    
    /**
     * View utility details
     */
    public function viewUtility() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'utilities.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $utility = $this->utility->getById($id);
        
        if (!$utility) {
            header('Location: ' . ADMIN_URL . 'utilities.php');
            exit;
        }
        
        return ['utility' => $utility];
    }
    
    /**
     * Create utility
     */
    public function createUtility() {
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $_SESSION['error'] = 'Vui lòng nhập tên tiện nghi';
            header('Location: ' . ADMIN_URL . 'utilities.php?action=add');
            exit;
        }
        
        $name = $_POST['name'];
        
        if ($this->utility->create($name)) {
            if ($this->activityLog) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'create_utility',
                    'utility',
                    0,
                    [],
                    "Thêm tiện nghi mới: {$name}"
                );
            }
            $_SESSION['success'] = 'Thêm tiện nghi thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'utilities.php');
        exit;
    }
    
    /**
     * Update utility
     */
    public function updateUtility() {
        if (!isset($_GET['id']) || !isset($_POST['name']) || empty($_POST['name'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'utilities.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $name = $_POST['name'];
        $utility_old = $this->utility->getById($id);
        
        if ($this->utility->update($id, $name)) {
            if ($this->activityLog && $utility_old) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'update_utility',
                    'utility',
                    $id,
                    ['old_name' => $utility_old['name'], 'new_name' => $name],
                    "Cập nhật tiện nghi từ '{$utility_old['name']}' thành '{$name}'"
                );
            }
            $_SESSION['success'] = 'Cập nhật tiện nghi thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'utilities.php');
        exit;
    }
    
    /**
     * Delete utility
     */
    public function deleteUtility() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'utilities.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $utility = $this->utility->getById($id);
        
        if ($this->utility->delete($id)) {
            if ($this->activityLog && $utility) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_utility',
                    'utility',
                    $id,
                    [],
                    "Xóa tiện nghi: {$utility['name']}"
                );
            }
            $_SESSION['success'] = 'Xóa tiện nghi thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'utilities.php');
        exit;
    }
}

?>
