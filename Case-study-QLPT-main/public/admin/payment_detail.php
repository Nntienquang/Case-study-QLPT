<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new PaymentController($db, new ActivityLog($db));
if (($_GET['action'] ?? '') === 'update_status' && isset($_GET['id'], $_GET['status'])) {
    $controller->updateStatus();
}

$data = $controller->viewPayment();
$payment = $data['payment'] ?? [];
$status = (string)($payment['status'] ?? 'pending');

admin_layout_start('Chi tiết thanh toán', 'Kiểm tra giao dịch, phí, mã tham chiếu và cập nhật trạng thái tiền cọc.', 'payments');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>payments.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Thanh toán</div><div class="wb-card-value">#<?php echo (int)($payment['id'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Số tiền</div><div class="wb-card-value fs-4"><?php echo admin_money($payment['amount'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Phí</div><div class="wb-card-value fs-4"><?php echo admin_money($payment['fee'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Trạng thái</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Người thuê</div>
            <div><?php echo admin_e($payment['user_name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($payment['email'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Phòng trọ</div>
            <div><?php echo admin_e($payment['motel_title'] ?? 'N/A'); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Phương thức và mã giao dịch</div>
            <div><?php echo admin_e($payment['method'] ?? 'N/A'); ?> · <?php echo admin_e($payment['transaction_code'] ?? 'N/A'); ?></div>
        </div>
    </div>
</div>

<div class="wb-card">
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo (int)($payment['id'] ?? 0); ?>">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Cập nhật trạng thái</label>
            <select name="status" class="form-select" required>
                <?php foreach (['pending', 'held', 'released', 'refunded'] as $item): ?>
                    <option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo admin_status_label($item); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cập nhật</button>
        </div>
    </form>
</div>

<?php admin_layout_end(); ?>
