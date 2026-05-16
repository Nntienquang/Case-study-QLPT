<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new PaymentController($db, $activityLog);
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    if (!Csrf::validateRequest('admin_payment_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'payments.php');
        exit;
    }

    $controller->updateStatus();
}

$data = $controller->listPayments();

admin_layout_start('Quản lý thanh toán', 'Theo dõi trạng thái giao dịch, tiền cọc, phí và các khoản đang giữ trong hệ thống.', 'payments');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="pending" <?php echo ($data['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="held" <?php echo ($data['status'] ?? '') === 'held' ? 'selected' : ''; ?>>Đang giữ</option>
                <option value="released" <?php echo ($data['status'] ?? '') === 'released' ? 'selected' : ''; ?>>Đã giải ngân</option>
                <option value="refunded" <?php echo ($data['status'] ?? '') === 'refunded' ? 'selected' : ''; ?>>Hoàn tiền</option>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Lọc danh sách</button>
            <a href="<?php echo ADMIN_URL; ?>payments.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách thanh toán</h2>
    <span class="wb-pill"><?php echo (int)($data['total'] ?? 0); ?> giao dịch</span>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['payments'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người thuê</th>
                    <th>Phòng trọ</th>
                    <th>Số tiền</th>
                    <th>Phí</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['payments'] as $payment): ?>
                    <?php $status = (string)($payment['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$payment['id']; ?></td>
                        <td><?php echo admin_e($payment['user_name'] ?? 'N/A'); ?></td>
                        <td class="wb-title"><?php echo admin_e(substr((string)($payment['motel_title'] ?? 'N/A'), 0, 50)); ?></td>
                        <td class="wb-price"><?php echo admin_money($payment['amount'] ?? 0); ?></td>
                        <td><?php echo admin_money($payment['fee'] ?? 0); ?></td>
                        <td><?php echo admin_e($payment['method'] ?? 'N/A'); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'payment_detail.php?id=' . (int)$payment['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if ($status === 'pending'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận giữ khoản thanh toán này?');">
                                    <?php echo Csrf::field('admin_payment_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="held">
                                    <input type="hidden" name="id" value="<?php echo (int)$payment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning"><i class="fa fa-pause"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status === 'held'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Giải ngân khoản thanh toán này?');">
                                    <?php echo Csrf::field('admin_payment_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="released">
                                    <input type="hidden" name="id" value="<?php echo (int)$payment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($status, ['pending', 'held'], true)): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Hoàn tiền khoản thanh toán này?');">
                                    <?php echo Csrf::field('admin_payment_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="refunded">
                                    <input type="hidden" name="id" value="<?php echo (int)$payment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-undo"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có thanh toán phù hợp bộ lọc.</div>
    <?php endif; ?>
</div>

<?php if (($data['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo ADMIN_URL . 'payments.php?page=' . $i . (!empty($data['status']) ? '&status=' . urlencode($data['status']) : ''); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>
