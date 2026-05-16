<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new CategoryController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!Csrf::validateRequest('admin_category_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'categories.php');
        exit;
    }

    $controller->deleteCategory();
}

$data = $controller->listCategories();

admin_layout_start('Quản lý danh mục', 'Danh sách nhóm loại phòng. Thêm và sửa ở màn hình riêng.', 'categories');
admin_flash_messages();
?>

<div class="wb-section-head">
    <h2>Danh sách danh mục</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo count($data['categories'] ?? []); ?> danh mục</span>
        <a href="<?php echo ADMIN_URL; ?>category_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Thêm danh mục</a>
    </div>
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
                            <a href="<?php echo ADMIN_URL . 'category_detail.php?id=' . (int)$category['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo ADMIN_URL . 'category_edit.php?id=' . (int)$category['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa danh mục này?');">
                                <?php echo Csrf::field('admin_category_action'); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$category['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
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
