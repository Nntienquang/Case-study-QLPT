<?php
/**
 * District Controller
 */

class DistrictController {
    private $district;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->district = new District($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all districts
     */
    public function listDistricts() {
        $districts = $this->district->getAll();
        return ['districts' => $districts];
    }
    
    /**
     * View district details
     */
    public function viewDistrict() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'districts.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $district = $this->district->getById($id);
        
        if (!$district) {
            header('Location: ' . ADMIN_URL . 'districts.php');
            exit;
        }
        
        return ['district' => $district];
    }
    
    /**
     * Create district
     */
    public function createDistrict() {
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $_SESSION['error'] = 'Vui lòng nhập tên quận';
            header('Location: ' . ADMIN_URL . 'districts.php?action=add');
            exit;
        }
        
        $name = $_POST['name'];
        
        if ($this->district->create($name)) {
            if ($this->activityLog) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'create_district',
                    'district',
                    0,
                    [],
                    "Thêm quận mới: {$name}"
                );
            }
            $_SESSION['success'] = 'Thêm quận thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'districts.php');
        exit;
    }
    
    /**
     * Update district
     */
    public function updateDistrict() {
        if (!isset($_GET['id']) || !isset($_POST['name']) || empty($_POST['name'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'districts.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $name = $_POST['name'];
        $district_old = $this->district->getById($id);
        
        if ($this->district->update($id, $name)) {
            if ($this->activityLog && $district_old) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'update_district',
                    'district',
                    $id,
                    ['old_name' => $district_old['name'], 'new_name' => $name],
                    "Cập nhật quận từ '{$district_old['name']}' thành '{$name}'"
                );
            }
            $_SESSION['success'] = 'Cập nhật quận thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'districts.php');
        exit;
    }
    
    /**
     * Delete district
     */
    public function deleteDistrict() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'districts.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $district = $this->district->getById($id);
        
        if ($this->district->delete($id)) {
            if ($this->activityLog && $district) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_district',
                    'district',
                    $id,
                    [],
                    "Xóa quận: {$district['name']}"
                );
            }
            $_SESSION['success'] = 'Xóa quận thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'districts.php');
        exit;
    }
}

?>
