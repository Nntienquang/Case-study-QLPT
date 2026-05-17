<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UtilityController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!Csrf::validateRequest('admin_utility_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'utilities.php');
        exit;
    }

    $controller->deleteUtility();
}

$data = $controller->listUtilities();

admin_layout_start('Quản lý tiện nghi', 'Danh sách tiện nghi chuẩn hóa cho tin đăng.', 'utilities');
admin_flash_messages();
?>

<div class="wb-section-head">
    <h2>Danh sách tiện nghi</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo count($data['utilities'] ?? []); ?> tiện nghi</span>
        <a href="<?php echo ADMIN_URL; ?>utility_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Thêm tiện nghi</a>
    </div>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['utilities'])): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>Tên tiện nghi</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($data['utilities'] as $utility): ?>
                    <tr>
                        <td>#<?php echo (int)$utility['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($utility['name'] ?? ''); ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'utility_detail.php?id=' . (int)$utility['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo ADMIN_URL . 'utility_edit.php?id=' . (int)$utility['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa tiện nghi này?');">
                                <?php echo Csrf::field('admin_utility_action'); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$utility['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có tiện nghi nào.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>

