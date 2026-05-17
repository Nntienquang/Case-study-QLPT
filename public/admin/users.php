<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new UserController($db, new ActivityLog($db));
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['delete', 'status'], true)) {
    if (!Csrf::validateRequest('admin_user_action')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }

    if ($action === 'delete') {
        $controller->deleteUser();
    }

    if ($action === 'status') {
        $controller->updateStatus((int)($_POST['id'] ?? 0), (string)($_POST['status'] ?? ''));
        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }
}

$data = $controller->listUsers();
$queryBase = '&role=' . urlencode((string)$data['role']) . '&status=' . urlencode((string)$data['status']) . '&search=' . urlencode((string)$data['search']);
$adminId = (int)($_SESSION['user_id'] ?? 0);

admin_layout_start('Quáº£n lÃ½ ngÆ°á»i dÃ¹ng', 'Danh sÃ¡ch tÃ i khoáº£n há»‡ thá»‘ng. Táº¡o vÃ  chá»‰nh sá»­a tÃ i khoáº£n á»Ÿ cÃ¡c mÃ n hÃ¬nh riÃªng.', 'users');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Tá»« khÃ³a</label>
            <input type="text" name="search" class="form-control" value="<?php echo admin_e($data['search']); ?>" placeholder="TÃªn, email, SÄT">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Vai trÃ²</label>
            <select name="role" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <option value="admin" <?php echo $data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="owner" <?php echo $data['role'] === 'owner' ? 'selected' : ''; ?>>Chá»§ phÃ²ng</option>
                <option value="user" <?php echo $data['role'] === 'user' ? 'selected' : ''; ?>>NgÆ°á»i thuÃª</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Tráº¡ng thÃ¡i</label>
            <select name="status" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <option value="approved" <?php echo $data['status'] === 'approved' ? 'selected' : ''; ?>>ÄÃ£ duyá»‡t</option>
                <option value="pending" <?php echo $data['status'] === 'pending' ? 'selected' : ''; ?>>Chá» duyá»‡t</option>
                <option value="blocked" <?php echo $data['status'] === 'blocked' ? 'selected' : ''; ?>>Bá»‹ khÃ³a</option>
                <option value="rejected" <?php echo $data['status'] === 'rejected' ? 'selected' : ''; ?>>Tá»« chá»‘i</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="fa fa-filter"></i> Lá»c</button>
            <a href="<?php echo ADMIN_URL; ?>users.php" class="btn btn-outline-secondary">XÃ³a lá»c</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sÃ¡ch ngÆ°á»i dÃ¹ng</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo (int)$data['total']; ?> tÃ i khoáº£n</span>
        <a href="<?php echo ADMIN_URL; ?>user_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> ThÃªm tÃ i khoáº£n</a>
    </div>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['users'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TÃªn</th>
                    <th>Email</th>
                    <th>Äiá»‡n thoáº¡i</th>
                    <th>Vai trÃ²</th>
                    <th>Tráº¡ng thÃ¡i</th>
                    <th>NgÃ y táº¡o</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['users'] as $user): ?>
                    <?php $status = (string)($user['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$user['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($user['name'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($user['email'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($user['phone'] ?? '-'); ?></td>
                        <td><span class="wb-pill"><?php echo admin_status_label((string)($user['role'] ?? 'user')); ?></span></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string)$user['created_at'])) : ''; ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'user_detail.php?id=' . (int)$user['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a>
                            <a href="<?php echo ADMIN_URL . 'user_edit.php?id=' . (int)$user['id']; ?>" class="btn btn-sm btn-warning"><i class="fa fa-edit"></i></a>
                            <?php if ((int)$user['id'] !== $adminId): ?>
                                <?php if ($status === 'blocked'): ?>
                                    <form method="POST" class="d-inline">
                                        <?php echo Csrf::field('admin_user_action'); ?>
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="status" value="approved">
                                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-unlock"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('KhÃ³a tÃ i khoáº£n nÃ y?');">
                                        <?php echo Csrf::field('admin_user_action'); ?>
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="status" value="blocked">
                                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fa fa-lock"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('XÃ³a tÃ i khoáº£n nÃ y?');">
                                    <?php echo Csrf::field('admin_user_action'); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">KhÃ´ng cÃ³ tÃ i khoáº£n phÃ¹ há»£p bá»™ lá»c.</div>
    <?php endif; ?>
</div>

<?php if (($data['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo ADMIN_URL . 'users.php?page=' . $i . $queryBase; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>

