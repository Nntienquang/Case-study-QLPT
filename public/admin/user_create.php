<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UserController($db, new ActivityLog($db));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->createUser();
    if (!isset($_SESSION['error'])) {
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }
}

admin_layout_start('ThÃªm tÃ i khoáº£n', 'Táº¡o tÃ i khoáº£n váº­n hÃ nh hoáº·c há»— trá»£ ngÆ°á»i dÃ¹ng.', 'users');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>users.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>

<div class="wb-card">
    <form method="POST" class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">TÃªn</label>
            <input type="text" name="name" class="form-control" value="<?php echo admin_e($_POST['name'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo admin_e($_POST['email'] ?? ''); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Äiá»‡n thoáº¡i</label>
            <input type="text" name="phone" class="form-control" value="<?php echo admin_e($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Máº­t kháº©u</label>
            <input type="password" name="password" class="form-control" minlength="6" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Vai trÃ²</label>
            <select name="role" class="form-select">
                <option value="user">NgÆ°á»i thuÃª</option>
                <option value="owner">Chá»§ phÃ²ng</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Tráº¡ng thÃ¡i</label>
            <select name="status" class="form-select">
                <option value="approved">ÄÃ£ duyá»‡t</option>
                <option value="pending">Chá» duyá»‡t</option>
                <option value="blocked">Bá»‹ khÃ³a</option>
                <option value="rejected">Tá»« chá»‘i</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Táº¡o tÃ i khoáº£n</button>
        </div>
    </form>
</div>

<?php admin_layout_end(); ?>

