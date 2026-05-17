<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UserController($db, new ActivityLog($db));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->createUser();
    if (!isset($_SESSION['error'])) {
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }
}

admin_layout_start('Thêm tài khoản', 'Tạo tài khoản vận hành hoặc hỗ trợ người dùng.', 'users');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>users.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-card">
    <form method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Tên</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo admin_e($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Điện thoại</label>
            <input type="text" name="phone" class="form-control" value="<?php echo admin_e($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Mật khẩu</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Vai trò</label>
            <select name="role" class="form-select">
                <option value="user">Người thuê</option>
                <option value="owner">Chủ phòng</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="approved">Đã duyệt</option>
                <option value="pending">Chờ duyệt</option>
                <option value="blocked">Bị khóa</option>
                <option value="rejected">Từ chối</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Tạo tài khoản</button>
        </div>
    </form>
</div>

<?php admin_layout_end(); ?>

