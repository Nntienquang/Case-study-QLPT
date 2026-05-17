<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new DistrictController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!Csrf::validateRequest('admin_district_action')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'districts.php');
        exit;
    }

    $controller->deleteDistrict();
}

$data = $controller->listDistricts();

admin_layout_start('Quáº£n lÃ½ quáº­n', 'Danh sÃ¡ch khu vá»±c Ä‘á»ƒ owner gáº¯n vá»‹ trÃ­ tin Ä‘Äƒng.', 'districts');
admin_flash_messages();
?>

<div class="wb-section-head">
    <h2>Danh sÃ¡ch quáº­n</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo count($data['districts'] ?? []); ?> quáº­n</span>
        <a href="<?php echo ADMIN_URL; ?>district_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> ThÃªm quáº­n</a>
    </div>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['districts'])): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>TÃªn quáº­n</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($data['districts'] as $district): ?>
                    <tr>
                        <td>#<?php echo (int)$district['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($district['name'] ?? ''); ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'district_detail.php?id=' . (int)$district['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo ADMIN_URL . 'district_edit.php?id=' . (int)$district['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('XÃ³a quáº­n nÃ y?');">
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
        <div class="wb-empty">ChÆ°a cÃ³ quáº­n nÃ o.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>

