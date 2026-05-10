<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new UserController($db, $activityLog);
$action = $_GET['action'] ?? '';

if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteUser();
}

$data = $controller->listUsers();

admin_layout_start('Quản lý người dùng', 'Theo dõi tài khoản admin, chủ phòng và người thuê trong hệ thống.', 'users');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Vai trò</label>
            <select name="role" class="form-select">
                <option value="">Tất cả</option>
                <option value="admin" <?php echo ($data['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="owner" <?php echo ($data['role'] ?? '') === 'owner' ? 'selected' : ''; ?>>Chủ phòng</option>
                <option value="user" <?php echo ($data['role'] ?? '') === 'user' ? 'selected' : ''; ?>>Người thuê</option>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Lọc danh sách</button>
            <a href="<?php echo ADMIN_URL; ?>users.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách người dùng</h2>
    <span class="wb-pill"><?php echo (int)($data['total'] ?? 0); ?> tài khoản</span>
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
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['users'] as $user): ?>
                    <tr>
                        <td>#<?php echo (int)$user['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($user['name'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($user['email'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($user['phone'] ?? 'N/A'); ?></td>
                        <td><span class="wb-pill"><?php echo admin_status_label((string)($user['role'] ?? 'user')); ?></span></td>
                        <td><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string)$user['created_at'])) : ''; ?></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'user_detail.php?id=' . (int)$user['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?>
                                <a href="<?php echo ADMIN_URL . 'users.php?action=delete&id=' . (int)$user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa người dùng này?');"><i class="fa fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có người dùng phù hợp bộ lọc.</div>
    <?php endif; ?>
</div>

<?php if (($data['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo ADMIN_URL . 'users.php?page=' . $i . (!empty($data['role']) ? '&role=' . urlencode($data['role']) : ''); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>
