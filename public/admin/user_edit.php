<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UserController($db, new ActivityLog($db));
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = $_POST['form_action'] ?? 'update';
    if ($formAction === 'reset_password') {
        $controller->resetPassword($id, (string)($_POST['password'] ?? ''));
    } else {
        $controller->updateUser($id);
    }

    if (!isset($_SESSION['error'])) {
        header('Location: ' . ADMIN_URL . 'user_detail.php?id=' . $id);
        exit;
    }
}

$user = $db->getRow('SELECT * FROM users WHERE id = ' . $id);
if (!$user) {
    $_SESSION['error'] = 'TÃ i khoáº£n khÃ´ng tá»“n táº¡i.';
    header('Location: ' . ADMIN_URL . 'users.php');
    exit;
}

$isSelf = $id === (int)($_SESSION['user_id'] ?? 0);

admin_layout_start('Sá»­a tÃ i khoáº£n', 'Cáº­p nháº­t thÃ´ng tin, phÃ¢n quyá»n, tráº¡ng thÃ¡i hoáº·c reset máº­t kháº©u.', 'users');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL . 'user_detail.php?id=' . $id; ?>" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i chi tiáº¿t</a>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="wb-card">
            <form method="POST" class="row g-3">
                <input type="hidden" name="form_action" value="update">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">TÃªn</label>
                    <input type="text" name="name" class="form-control" value="<?php echo admin_e($user['name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo admin_e($user['email'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Äiá»‡n thoáº¡i</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo admin_e($user['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Vai trÃ²</label>
                    <select name="role" class="form-select" <?php echo $isSelf ? 'disabled' : ''; ?>>
                        <?php foreach (['user' => 'NgÆ°á»i thuÃª', 'owner' => 'Chá»§ phÃ²ng', 'admin' => 'Admin'] as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($user['role'] ?? 'user') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isSelf): ?><input type="hidden" name="role" value="admin"><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tráº¡ng thÃ¡i</label>
                    <select name="status" class="form-select" <?php echo $isSelf ? 'disabled' : ''; ?>>
                        <?php foreach (['approved' => 'ÄÃ£ duyá»‡t', 'pending' => 'Chá» duyá»‡t', 'blocked' => 'Bá»‹ khÃ³a', 'rejected' => 'Tá»« chá»‘i'] as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($user['status'] ?? 'approved') === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isSelf): ?><input type="hidden" name="status" value="approved"><?php endif; ?>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cáº­p nháº­t</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="wb-card">
            <div class="wb-title mb-3">Reset máº­t kháº©u</div>
            <form method="POST">
                <input type="hidden" name="form_action" value="reset_password">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                <label class="form-label fw-semibold">Máº­t kháº©u má»›i</label>
                <input type="password" name="password" class="form-control mb-3" minlength="6" required>
                <button type="submit" class="btn btn-outline-primary w-100"><i class="fa fa-key"></i> Reset máº­t kháº©u</button>
            </form>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

