<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new CategoryController($db, $activityLog);
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $controller->createCategory();
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $controller->updateCategory();
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteCategory();
}

$data = $controller->listCategories();
$editCategory = null;
if ($action === 'edit' && isset($_GET['id'])) {
    foreach ($data['categories'] as $category) {
        if ((int)$category['id'] === (int)$_GET['id']) {
            $editCategory = $category;
            break;
        }
    }
}

admin_layout_start('Quản lý danh mục', 'Chuẩn hóa nhóm loại phòng để người thuê lọc và tìm kiếm chính xác hơn.', 'categories');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="POST" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tên danh mục</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($editCategory['name'] ?? ''); ?>" placeholder="Ví dụ: Phòng trọ, căn hộ mini" required>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="fa fa-save"></i> <?php echo $editCategory ? 'Cập nhật' : 'Thêm mới'; ?></button>
            <?php if ($editCategory): ?>
                <a href="<?php echo ADMIN_URL; ?>categories.php" class="btn btn-outline-secondary">Hủy</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách danh mục</h2>
    <span class="wb-pill"><?php echo count($data['categories'] ?? []); ?> danh mục</span>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['categories'])): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>Tên danh mục</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($data['categories'] as $category): ?>
                    <tr>
                        <td>#<?php echo (int)$category['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($category['name'] ?? ''); ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'categories.php?action=edit&id=' . (int)$category['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i> Sửa</a>
                            <a href="<?php echo ADMIN_URL . 'categories.php?action=delete&id=' . (int)$category['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa danh mục này?');"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có danh mục nào.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
