<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new UtilityController($db, new ActivityLog($db));
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest('admin_utility_form')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'utility_edit.php?id=' . $id);
        exit;
    }

    $_GET['action'] = 'edit';
    $_GET['id'] = $id;
    $controller->updateUtility();
}

$utility = $db->getRow("SELECT * FROM utilities WHERE id = {$id}");
if (!$utility) {
    $_SESSION['error'] = 'Tiện nghi không tồn tại.';
    header('Location: ' . ADMIN_URL . 'utilities.php');
    exit;
}

admin_layout_start('Sửa tiện nghi', 'Cập nhật tên tiện nghi.', 'utilities');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>utility_detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại chi tiết</a>
<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_utility_form'); ?>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tên tiện nghi</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($utility['name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cập nhật</button>
        </div>
    </form>
</div>
<?php admin_layout_end(); ?>
