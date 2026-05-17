<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UtilityController($db, new ActivityLog($db));
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest('admin_utility_form')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'utility_edit.php?id=' . $id);
        exit;
    }

    $_GET['action'] = 'edit';
    $_GET['id'] = $id;
    $controller->updateUtility();
}

$utility = $db->getRow("SELECT * FROM utilities WHERE id = {$id}");
if (!$utility) {
    $_SESSION['error'] = 'Tiá»‡n nghi khÃ´ng tá»“n táº¡i.';
    header('Location: ' . ADMIN_URL . 'utilities.php');
    exit;
}

admin_layout_start('Sá»­a tiá»‡n nghi', 'Cáº­p nháº­t tÃªn tiá»‡n nghi.', 'utilities');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>utility_detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i chi tiáº¿t</a>
<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_utility_form'); ?>
        <div class="col-md-8">
            <label class="form-label fw-semibold">TÃªn tiá»‡n nghi</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($utility['name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cáº­p nháº­t</button>
        </div>
    </form>
</div>
<?php admin_layout_end(); ?>

