<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new UserController($db, new ActivityLog($db));
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['delete', 'status'], true)) {
    if (!Csrf::validateRequest('admin_user_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
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

admin_layout_start('Quản lý người dùng', 'Danh sách tài khoản hệ thống. Tạo và chỉnh sửa tài khoản ở các màn hình riêng.', 'users');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold">Từ khóa</label>
            <input type="text" name="search" class="form-control" value="<?php echo admin_e($data['search']); ?>" placeholder="Tên, email, SĐT">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Vai trò</label>
            <select name="role" class="form-select">
                <option value="">Tất cả</option>
                <option value="admin" <?php echo $data['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="owner" <?php echo $data['role'] === 'owner' ? 'selected' : ''; ?>>Chủ phòng</option>
                <option value="user" <?php echo $data['role'] === 'user' ? 'selected' : ''; ?>>Người thuê</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="approved" <?php echo $data['status'] === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                <option value="pending" <?php echo $data['status'] === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                <option value="blocked" <?php echo $data['status'] === 'blocked' ? 'selected' : ''; ?>>Bị khóa</option>
                <option value="rejected" <?php echo $data['status'] === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="fa fa-filter"></i> Lọc</button>
            <a href="<?php echo ADMIN_URL; ?>users.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách người dùng</h2>
    <div class="wb-actions">
        <span class="wb-pill"><?php echo (int)$data['total']; ?> tài khoản</span>
        <a href="<?php echo ADMIN_URL; ?>user_create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Thêm tài khoản</a>
    </div>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['users'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
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
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Khóa tài khoản này?');">
                                        <?php echo Csrf::field('admin_user_action'); ?>
                                        <input type="hidden" name="action" value="status">
                                        <input type="hidden" name="status" value="blocked">
                                        <input type="hidden" name="id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="fa fa-lock"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Xóa tài khoản này?');">
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
        <div class="wb-empty">Không có tài khoản phù hợp bộ lọc.</div>
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
