<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new DistrictController($db, $activityLog);
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $controller->createDistrict();
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $controller->updateDistrict();
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteDistrict();
}

$data = $controller->listDistricts();
$editDistrict = null;
if ($action === 'edit' && isset($_GET['id'])) {
    foreach ($data['districts'] as $district) {
        if ((int)$district['id'] === (int)$_GET['id']) {
            $editDistrict = $district;
            break;
        }
    }
}

admin_layout_start('Quản lý quận', 'Quản trị khu vực để tin đăng có dữ liệu vị trí rõ ràng và dễ lọc.', 'districts');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="POST" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tên quận</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($editDistrict['name'] ?? ''); ?>" placeholder="Ví dụ: Quận 1, Quận 7" required>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="fa fa-save"></i> <?php echo $editDistrict ? 'Cập nhật' : 'Thêm mới'; ?></button>
            <?php if ($editDistrict): ?>
                <a href="<?php echo ADMIN_URL; ?>districts.php" class="btn btn-outline-secondary">Hủy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách quận</h2>
    <span class="wb-pill"><?php echo count($data['districts'] ?? []); ?> quận</span>
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
                            <a href="<?php echo ADMIN_URL . 'districts.php?action=edit&id=' . (int)$district['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i> Sửa</a>
                            <a href="<?php echo ADMIN_URL . 'districts.php?action=delete&id=' . (int)$district['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa quận này?');"><i class="fa fa-trash"></i></a>
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
