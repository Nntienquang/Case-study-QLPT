<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new MotelController($db, $activityLog);
$action = $_GET['action'] ?? '';

if ($action === 'approve' && isset($_GET['id'])) {
    $controller->approveMotel();
}
if ($action === 'hide' && isset($_GET['id'])) {
    $controller->hideMotel();
}
if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteMotel();
}

$data = $controller->listMotels();

admin_layout_start('Quản lý phòng trọ', 'Kiểm duyệt tin đăng, rà soát trạng thái hiển thị và xử lý phòng không đạt chuẩn.', 'motels');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="pending" <?php echo ($data['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Chờ duyệt</option>
                <option value="approved" <?php echo ($data['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Đã duyệt</option>
                <option value="hidden" <?php echo ($data['status'] ?? '') === 'hidden' ? 'selected' : ''; ?>>Đã ẩn</option>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Lọc danh sách</button>
            <a href="<?php echo ADMIN_URL; ?>motels.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách phòng trọ</h2>
    <span class="wb-pill"><?php echo (int)($data['total'] ?? 0); ?> phòng</span>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['motels'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Chủ phòng</th>
                    <th>Giá</th>
                    <th>Diện tích</th>
                    <th>Quận</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['motels'] as $motel): ?>
                    <?php $status = (string)($motel['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$motel['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e(substr((string)($motel['title'] ?? 'N/A'), 0, 55)); ?></td>
                        <td><?php echo admin_e($motel['owner_name'] ?? 'N/A'); ?></td>
                        <td class="wb-price"><?php echo admin_money($motel['price'] ?? 0); ?></td>
                        <td><?php echo admin_e((string)($motel['area'] ?? 'N/A')); ?> m²</td>
                        <td><?php echo admin_e($motel['district_name'] ?? 'N/A'); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . (int)$motel['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if ($status === 'pending'): ?>
                                <a href="<?php echo ADMIN_URL . 'motels.php?action=approve&id=' . (int)$motel['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Duyệt phòng này?');"><i class="fa fa-check"></i> Duyệt</a>
                            <?php endif; ?>
                            <?php if ($status !== 'hidden'): ?>
                                <a href="<?php echo ADMIN_URL . 'motels.php?action=hide&id=' . (int)$motel['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Ẩn phòng này?');"><i class="fa fa-times"></i> Ẩn</a>
                            <?php endif; ?>
                            <a href="<?php echo ADMIN_URL . 'motels.php?action=delete&id=' . (int)$motel['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa phòng này?');"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có phòng trọ phù hợp bộ lọc.</div>
    <?php endif; ?>
</div>

<?php if (($data['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo ADMIN_URL . 'motels.php?page=' . $i . (!empty($data['status']) ? '&status=' . urlencode($data['status']) : ''); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>
