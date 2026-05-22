<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new MotelController($db, $activityLog);
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['approve', 'hide', 'reject', 'delete'], true)) {
    if (!Csrf::validateRequest('admin_motel_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'motels.php');
        exit;
    }

    if ($action === 'approve') {
        $controller->approveMotel();
    }
    if ($action === 'hide') {
        $controller->hideMotel();
    }
    if ($action === 'reject') {
        $controller->rejectMotel();
    }
    if ($action === 'delete') {
        $controller->deleteMotel();
    }
}

$data = $controller->listMotels();

admin_layout_start('Quản lý phòng trọ', 'Kiểm duyệt tin đăng của owner, rà soát trạng thái hiển thị và xử lý phòng không đạt chuẩn.', 'motels');
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
                <option value="rejected" <?php echo ($data['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
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
                    <th>Địa chỉ</th>
                    <th>Report</th>
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
                        <td>
                            <div><?php echo admin_e($motel['street_address'] ?? $motel['address'] ?? 'N/A'); ?></div>
                            <div class="wb-muted"><?php echo admin_e(trim(($motel['district_name'] ?? '') . ', ' . ($motel['province_name'] ?? ''), ', ')); ?></div>
                        </td>
                        <td><?php echo (int)($motel['report_count'] ?? 0); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . (int)$motel['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if (in_array($status, ['pending', 'hidden', 'rejected'], true)): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Duyệt/phục hồi phòng này?');">
                                    <?php echo Csrf::field('admin_motel_action'); ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?php echo (int)$motel['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status === 'pending'): ?>
                                <form method="POST" class="d-inline" onsubmit="const reason = prompt('Nhập lý do từ chối tin phòng:'); if (!reason) return false; this.rejection_reason.value = reason; return true;">
                                    <?php echo Csrf::field('admin_motel_action'); ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?php echo (int)$motel['id']; ?>">
                                    <input type="hidden" name="rejection_reason" value="">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-ban"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status === 'approved'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Ẩn phòng này?');">
                                    <?php echo Csrf::field('admin_motel_action'); ?>
                                    <input type="hidden" name="action" value="hide">
                                    <input type="hidden" name="id" value="<?php echo (int)$motel['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning"><i class="fa fa-times"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Xóa phòng này?');">
                                <?php echo Csrf::field('admin_motel_action'); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$motel['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
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

