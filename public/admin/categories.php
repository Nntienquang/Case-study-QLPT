<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new CategoryController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!Csrf::validateRequest('admin_category_action')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'categories.php');
        exit;
    }

    $controller->deleteCategory();
}

$data = $controller->listCategories();

admin_layout_start('Quáº£n lÃ½ danh má»¥c', 'Danh sÃ¡ch nhÃ³m loáº¡i phÃ²ng. ThÃªm vÃ  sá»­a á»Ÿ mÃ n hÃ¬nh riÃªng.', 'categories');
admin_flash_messages();
?>

<div class="wb-section-head">
    <h2>Danh sÃ¡ch danh má»¥c</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo count($data['categories'] ?? []); ?> danh má»¥c</span>
        <a href="<?php echo ADMIN_URL; ?>category_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> ThÃªm danh má»¥c</a>
    </div>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['categories'])): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>TÃªn danh má»¥c</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($data['categories'] as $category): ?>
                    <tr>
                        <td>#<?php echo (int)$category['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($category['name'] ?? ''); ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'category_detail.php?id=' . (int)$category['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo ADMIN_URL . 'category_edit.php?id=' . (int)$category['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('XÃ³a danh má»¥c nÃ y?');">
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
        <div class="wb-empty">ChÆ°a cÃ³ danh má»¥c nÃ o.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>

