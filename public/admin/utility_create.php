<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UtilityController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::validateRequest('admin_utility_form')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'utility_create.php');
        exit;
    }

    $_GET['action'] = 'add';
    $controller->createUtility();
}

admin_layout_start('ThÃªm tiá»‡n nghi', 'Táº¡o tiá»‡n nghi má»›i cho tin Ä‘Äƒng.', 'utilities');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>utilities.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>
<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_utility_form'); ?>
        <div class="col-md-8">
            <label class="form-label fw-semibold">TÃªn tiá»‡n nghi</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> ThÃªm tiá»‡n nghi</button>
        </div>
    </form>
</div>
<?php admin_layout_end(); ?>

