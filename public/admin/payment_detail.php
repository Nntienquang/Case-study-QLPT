<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new PaymentController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['update_status', 'release_held'], true)) {
    if (!Csrf::validateRequest('admin_payment_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'payments.php');
        exit;
    }
    if (($_POST['action'] ?? '') === 'release_held') {
        $controller->releaseHeldPayment();
    } else {
        $controller->updateStatus();
    }
}

$payment = $controller->viewPayment()['payment'] ?? [];
$status = (string)($payment['payment_status'] ?? 'pending');
$escrowStatus = (string)($payment['status'] ?? 'pending');
$bookingStatus = (string)($payment['booking_legacy_status'] ?? $payment['booking_status'] ?? 'pending');
$options = [$status => admin_status_label($status)];
foreach ([
    'pending' => ['processing', 'paid', 'failed', 'cancelled'],
    'processing' => ['paid', 'failed', 'cancelled'],
    'paid' => ['refunded'],
] as $from => $next) {
    if ($status === $from) {
        foreach ($next as $item) {
            $options[$item] = admin_status_label($item);
        }
    }
}

admin_layout_start('Chi tiết thanh toán', 'Kiểm tra giao dịch, mã tham chiếu và cập nhật trạng thái thanh toán.', 'payments');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>payments.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Mã thanh toán</div><div class="wb-card-value fs-4"><?php echo admin_e($payment['payment_code'] ?? ('#' . (int)($payment['id'] ?? 0))); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Booking</div><div class="wb-card-value fs-4"><?php echo admin_e($payment['booking_code'] ?? 'N/A'); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Số tiền</div><div class="wb-card-value fs-4"><?php echo admin_money($payment['amount'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Trạng thái</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
</div>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Trạng thái giữ tiền</div><div class="mt-2"><span class="wb-pill <?php echo $escrowStatus === 'released' ? '' : 'warning'; ?>"><?php echo admin_e($escrowStatus === 'held' ? 'Admin đang giữ tiền' : ($escrowStatus === 'released' ? 'Đã giải ngân' : admin_status_label($escrowStatus))); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Trạng thái booking</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($bookingStatus); ?>"><?php echo admin_status_label($bookingStatus); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Phí nền tảng</div><div class="wb-card-value fs-4"><?php echo admin_money($payment['fee'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Dự kiến trả owner</div><div class="wb-card-value fs-4"><?php echo admin_money(max(0, (int)($payment['amount'] ?? 0) - (int)($payment['fee'] ?? 0))); ?></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row"><div><div class="wb-title">Người thuê</div><div><?php echo admin_e($payment['user_name'] ?? 'N/A'); ?></div><div class="wb-muted"><?php echo admin_e($payment['email'] ?? ''); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">Chủ phòng nhận tiền</div><div><?php echo admin_e($payment['owner_name'] ?? 'N/A'); ?></div><div class="wb-muted"><?php echo admin_e($payment['owner_email'] ?? ''); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">Phòng trọ</div><div><?php echo admin_e($payment['motel_title'] ?? 'N/A'); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">Phương thức và mã giao dịch</div><div><?php echo admin_e($payment['payment_method'] ?? $payment['method'] ?? 'N/A'); ?> · <?php echo admin_e($payment['transaction_code'] ?? 'Chưa có'); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">Gateway response</div><pre class="mb-0 small"><?php echo admin_e($payment['gateway_response'] ?? 'Chưa có dữ liệu gateway'); ?></pre></div></div>
</div>

<div class="wb-card mb-3">
    <div class="wb-title mb-2">Giải ngân tiền cọc</div>
    <div class="wb-muted mb-3">
        Admin chỉ giải ngân cho chủ phòng sau khi khách thuê đã xác nhận nhận phòng. Khoản đang giữ: <?php echo admin_money($payment['amount'] ?? 0); ?>.
    </div>
    <?php if ($status === 'paid' && $escrowStatus === 'held' && $bookingStatus === 'completed'): ?>
        <form method="POST" onsubmit="return confirm('Xác nhận giải ngân tiền cọc cho chủ phòng?');">
            <?php echo Csrf::field('admin_payment_action'); ?>
            <input type="hidden" name="action" value="release_held">
            <input type="hidden" name="id" value="<?php echo (int)($payment['id'] ?? 0); ?>">
            <button type="submit" class="btn btn-success"><i class="fa fa-money"></i> Giải ngân cho chủ phòng</button>
        </form>
    <?php elseif ($status === 'paid' && $escrowStatus === 'held'): ?>
        <div class="alert alert-warning mb-0">Khoản tiền đang được admin giữ. Chưa thể giải ngân vì khách thuê chưa xác nhận đã nhận phòng.</div>
    <?php elseif ($escrowStatus === 'released'): ?>
        <div class="alert alert-success mb-0">Khoản tiền này đã được admin giải ngân cho chủ phòng.</div>
    <?php else: ?>
        <div class="alert alert-secondary mb-0">Chỉ khoản đã thanh toán và đang được admin giữ hộ mới có thể giải ngân.</div>
    <?php endif; ?>
</div>

<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_payment_action'); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo (int)($payment['id'] ?? 0); ?>">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Cập nhật trạng thái</label>
            <select name="status" class="form-select" required>
                <?php foreach ($options as $item => $label): ?>
                    <option value="<?php echo admin_e($item); ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cập nhật</button>
        </div>
    </form>
</div>

<?php admin_layout_end(); ?>

