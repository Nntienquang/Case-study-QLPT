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
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'payments.php');
        exit;
    }
    $controller->updateStatus();
}

$payment = $controller->viewPayment()['payment'] ?? [];
$status = (string)($payment['payment_status'] ?? 'pending');
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

admin_layout_start('Chi tiáº¿t thanh toÃ¡n', 'Kiá»ƒm tra giao dá»‹ch, mÃ£ tham chiáº¿u vÃ  cáº­p nháº­t tráº¡ng thÃ¡i thanh toÃ¡n.', 'payments');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>payments.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">MÃ£ thanh toÃ¡n</div><div class="wb-card-value fs-4"><?php echo admin_e($payment['payment_code'] ?? ('#' . (int)($payment['id'] ?? 0))); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Booking</div><div class="wb-card-value fs-4"><?php echo admin_e($payment['booking_code'] ?? 'N/A'); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Sá»‘ tiá»n</div><div class="wb-card-value fs-4"><?php echo admin_money($payment['amount'] ?? 0); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tráº¡ng thÃ¡i</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row"><div><div class="wb-title">NgÆ°á»i thuÃª</div><div><?php echo admin_e($payment['user_name'] ?? 'N/A'); ?></div><div class="wb-muted"><?php echo admin_e($payment['email'] ?? ''); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">PhÃ²ng trá»</div><div><?php echo admin_e($payment['motel_title'] ?? 'N/A'); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">PhÆ°Æ¡ng thá»©c vÃ  mÃ£ giao dá»‹ch</div><div><?php echo admin_e($payment['payment_method'] ?? $payment['method'] ?? 'N/A'); ?> Â· <?php echo admin_e($payment['transaction_code'] ?? 'ChÆ°a cÃ³'); ?></div></div></div>
    <div class="wb-list-row"><div><div class="wb-title">Gateway response</div><pre class="mb-0 small"><?php echo admin_e($payment['gateway_response'] ?? 'ChÆ°a cÃ³ dá»¯ liá»‡u gateway'); ?></pre></div></div>
</div>

<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_payment_action'); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo (int)($payment['id'] ?? 0); ?>">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Cáº­p nháº­t tráº¡ng thÃ¡i</label>
            <select name="status" class="form-select" required>
                <?php foreach ($options as $item => $label): ?>
                    <option value="<?php echo admin_e($item); ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cáº­p nháº­t</button>
        </div>
    </form>
</div>

<?php admin_layout_end(); ?>

