<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new DistrictController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!Csrf::validateRequest('admin_district_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'districts.php');
        exit;
    }

    $controller->deleteDistrict();
}

$data = $controller->listDistricts();

admin_layout_start('Quản lý quận', 'Danh sách khu vực để owner gắn vị trí tin đăng.', 'districts');
admin_flash_messages();
?>

<div class="wb-section-head">
    <h2>Danh sách quận</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo count($data['districts'] ?? []); ?> quận</span>
        <a href="<?php echo ADMIN_URL; ?>district_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Thêm quận</a>
    </div>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['districts'])): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>Tên quận</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($data['districts'] as $district): ?>
                    <tr>
                        <td>#<?php echo (int)$district['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($district['name'] ?? ''); ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'district_detail.php?id=' . (int)$district['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo ADMIN_URL . 'district_edit.php?id=' . (int)$district['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa quận này?');">
                                <?php echo Csrf::field('admin_district_action'); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$district['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có quận nào.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
