<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new DistrictController($db, new ActivityLog($db));
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest('admin_district_form')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'district_edit.php?id=' . $id);
        exit;
    }

    $_GET['action'] = 'edit';
    $_GET['id'] = $id;
    $controller->updateDistrict();
}

$district = $db->getRow("SELECT * FROM districts WHERE id = {$id}");
if (!$district) {
    $_SESSION['error'] = 'Quáº­n khÃ´ng tá»“n táº¡i.';
    header('Location: ' . ADMIN_URL . 'districts.php');
    exit;
}

admin_layout_start('Sá»­a quáº­n', 'Cáº­p nháº­t tÃªn khu vá»±c.', 'districts');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>district_detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i chi tiáº¿t</a>
<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_district_form'); ?>
        <div class="col-md-8">
            <label class="form-label fw-semibold">TÃªn quáº­n</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($district['name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cáº­p nháº­t</button>
        </div>
    </form>
</div>
<?php admin_layout_end(); ?>

