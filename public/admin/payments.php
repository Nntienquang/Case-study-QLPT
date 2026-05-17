<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new PaymentController($db, new ActivityLog($db));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    if (!Csrf::validateRequest('admin_payment_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'payments.php');
        exit;
    }
    $controller->updateStatus();
}

$data = $controller->listPayments();

admin_layout_start('Quản lý thanh toán', 'Theo dõi giao dịch đặt cọc và xác nhận thanh toán trước khi owner xử lý booking.', 'payments');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <?php foreach (['pending' => 'Chờ thanh toán', 'processing' => 'Chờ xác nhận', 'paid' => 'Đã thanh toán', 'failed' => 'Thất bại', 'cancelled' => 'Đã hủy', 'refunded' => 'Hoàn tiền'] as $value => $label): ?>
                    <option value="<?php echo admin_e($value); ?>" <?php echo ($data['status'] ?? '') === $value ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
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
                    <th>Mã</th>
                    <th>Booking</th>
                    <th>Người thuê</th>
                    <th>Phòng trọ</th>
                    <th>Số tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['payments'] as $payment): ?>
                    <?php $status = (string)($payment['payment_status'] ?? 'pending'); ?>
                    <tr>
                        <td class="wb-title"><?php echo admin_e($payment['payment_code'] ?? ('#' . (int)$payment['id'])); ?></td>
                        <td><?php echo admin_e($payment['booking_code'] ?? ('#' . (int)($payment['booking_id'] ?? 0))); ?></td>
                        <td><?php echo admin_e($payment['user_name'] ?? 'N/A'); ?></td>
                        <td class="wb-title"><?php echo admin_e(substr((string)($payment['motel_title'] ?? 'N/A'), 0, 50)); ?></td>
                        <td class="wb-price"><?php echo admin_money($payment['amount'] ?? 0); ?></td>
                        <td><?php echo admin_e($payment['payment_method'] ?? $payment['method'] ?? 'N/A'); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'payment_detail.php?id=' . (int)$payment['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if (in_array($status, ['pending', 'processing'], true)): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận thanh toán này đã thành công?');">
                                    <?php echo Csrf::field('admin_payment_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="paid">
                                    <input type="hidden" name="id" value="<?php echo (int)$payment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Đánh dấu giao dịch thất bại?');">
                                    <?php echo Csrf::field('admin_payment_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="failed">
                                    <input type="hidden" name="id" value="<?php echo (int)$payment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-times"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($status === 'paid'): ?>
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

