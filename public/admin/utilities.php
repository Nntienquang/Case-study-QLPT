<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new UtilityController($db, $activityLog);
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $controller->createUtility();
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $controller->updateUtility();
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteUtility();
}

$data = $controller->listUtilities();
$editUtility = null;
if ($action === 'edit' && isset($_GET['id'])) {
    foreach ($data['utilities'] as $utility) {
        if ((int)$utility['id'] === (int)$_GET['id']) {
            $editUtility = $utility;
            break;
        }
    }
}

admin_layout_start('Quản lý tiện nghi', 'Chuẩn hóa tiện nghi để tin đăng rõ thông tin và dễ so sánh hơn.', 'utilities');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="POST" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tên tiện nghi</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($editUtility['name'] ?? ''); ?>" placeholder="Ví dụ: Wifi, máy lạnh, chỗ để xe" required>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="fa fa-save"></i> <?php echo $editUtility ? 'Cập nhật' : 'Thêm mới'; ?></button>
            <?php if ($editUtility): ?>
                <a href="<?php echo ADMIN_URL; ?>utilities.php" class="btn btn-outline-secondary">Hủy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách tiện nghi</h2>
    <span class="wb-pill"><?php echo count($data['utilities'] ?? []); ?> tiện nghi</span>
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
                            <a href="<?php echo ADMIN_URL . 'utilities.php?action=edit&id=' . (int)$utility['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i> Sửa</a>
                            <a href="<?php echo ADMIN_URL . 'utilities.php?action=delete&id=' . (int)$utility['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa tiện nghi này?');"><i class="fa fa-trash"></i></a>
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
