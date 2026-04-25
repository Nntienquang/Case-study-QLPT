<?php
/**
 * Category Controller
 */

class CategoryController {
    private $category;
    private $db;
    private $activityLog;
    
    public function __construct($db, $activityLog = null) {
        $this->category = new Category($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }
    
    /**
     * List all categories
     */
    public function listCategories() {
        $categories = $this->category->getAll();
        return ['categories' => $categories];
    }
    
    /**
     * View category details
     */
    public function viewCategory() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'categories.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $category = $this->category->getById($id);
        
        if (!$category) {
            header('Location: ' . ADMIN_URL . 'categories.php');
            exit;
        }
        
        return ['category' => $category];
    }
    
    /**
     * Create category
     */
    public function createCategory() {
        if (!isset($_POST['name']) || empty($_POST['name'])) {
            $_SESSION['error'] = 'Vui lòng nhập tên danh mục';
            header('Location: ' . ADMIN_URL . 'categories.php?action=add');
            exit;
        }
        
        $name = $_POST['name'];
        
        if ($this->category->create($name)) {
            if ($this->activityLog) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'create_category',
                    'category',
                    0,
                    [],
                    "Thêm danh mục mới: {$name}"
                );
            }
            $_SESSION['success'] = 'Thêm danh mục thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'categories.php');
        exit;
    }
    
    /**
     * Update category
     */
    public function updateCategory() {
        if (!isset($_GET['id']) || !isset($_POST['name']) || empty($_POST['name'])) {
            $_SESSION['error'] = 'Dữ liệu không hợp lệ';
            header('Location: ' . ADMIN_URL . 'categories.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $name = $_POST['name'];
        $category_old = $this->category->getById($id);
        
        if ($this->category->update($id, $name)) {
            if ($this->activityLog && $category_old) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'update_category',
                    'category',
                    $id,
                    ['old_name' => $category_old['name'], 'new_name' => $name],
                    "Cập nhật danh mục từ '{$category_old['name']}' thành '{$name}'"
                );
            }
            $_SESSION['success'] = 'Cập nhật danh mục thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'categories.php');
        exit;
    }
    
    /**
     * Delete category
     */
    public function deleteCategory() {
        if (!isset($_GET['id'])) {
            header('Location: ' . ADMIN_URL . 'categories.php');
            exit;
        }
        
        $id = (int)$_GET['id'];
        $category = $this->category->getById($id);
        
        if ($this->category->delete($id)) {
            if ($this->activityLog && $category) {
                $this->activityLog->log(
                    $_SESSION['user_id'],
                    'delete_category',
                    'category',
                    $id,
                    [],
                    "Xóa danh mục: {$category['name']}"
                );
            }
            $_SESSION['success'] = 'Xóa danh mục thành công';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra';
        }
        
        header('Location: ' . ADMIN_URL . 'categories.php');
        exit;
    }
}

?>
