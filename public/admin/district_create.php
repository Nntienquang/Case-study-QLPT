<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new DistrictController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest('admin_district_form')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'district_create.php');
        exit;
    }

    $_GET['action'] = 'add';
    $controller->createDistrict();
}

admin_layout_start('Thêm quận', 'Tạo khu vực mới cho tin đăng.', 'districts');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>districts.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>
<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_district_form'); ?>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tên quận</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Thêm quận</button>
        </div>
    </form>
</div>
<?php admin_layout_end(); ?>
