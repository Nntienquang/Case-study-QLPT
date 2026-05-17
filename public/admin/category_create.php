<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new CategoryController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest('admin_category_form')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'category_create.php');
        exit;
    }

    $_GET['action'] = 'add';
    $controller->createCategory();
}

admin_layout_start('Thêm danh mục', 'Tạo nhóm loại phòng mới.', 'categories');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>categories.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>
<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_category_form'); ?>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tên danh mục</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Thêm danh mục</button>
        </div>
    </form>
</div>
<?php admin_layout_end(); ?>

