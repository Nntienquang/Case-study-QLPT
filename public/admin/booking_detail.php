<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new BookingController($db, new ActivityLog($db));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['update_status', 'delete'], true)) {
    if (!Csrf::validateRequest('admin_booking_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: ' . ADMIN_URL . 'bookings.php');
        exit;
    }

    if ($_POST['action'] === 'update_status') {
        $controller->updateStatus();
    }

    if ($_POST['action'] === 'delete') {
        $controller->deleteBooking();
    }
}

$data = $controller->viewBooking();
$booking = $data['booking'] ?? [];
$status = (string)($booking['status'] ?? 'pending');

admin_layout_start('Chi tiết booking', 'Xem thông tin đặt phòng và cập nhật trạng thái xử lý.', 'bookings');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>bookings.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card">
        <div class="wb-card-label">Booking</div>
        <div class="wb-card-value">#<?php echo (int)($booking['id'] ?? 0); ?></div>
    </div>
    <div class="wb-card">
        <div class="wb-card-label">Tiền cọc</div>
        <div class="wb-card-value fs-4"><?php echo admin_money($booking['deposit_amount'] ?? 0); ?></div>
    </div>
    <div class="wb-card">
        <div class="wb-card-label">Check-in</div>
        <div class="wb-card-value fs-4"><?php echo admin_e($booking['checkin_date'] ?? ''); ?></div>
    </div>
    <div class="wb-card">
        <div class="wb-card-label">Trạng thái</div>
        <div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div>
    </div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Người thuê</div>
            <div><?php echo admin_e($booking['user_name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($booking['user_email'] ?? ''); ?> · <?php echo admin_e($booking['user_phone'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Phòng trọ</div>
            <div><?php echo admin_e($booking['motel_title'] ?? 'N/A'); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Ngày tạo</div>
            <div><?php echo !empty($booking['created_at']) ? date('d/m/Y H:i', strtotime((string)$booking['created_at'])) : ''; ?></div>
        </div>
    </div>
</div>

<div class="wb-card">
    <form method="POST" class="row g-3 align-items-end">
        <?php echo Csrf::field('admin_booking_action'); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" value="<?php echo (int)($booking['id'] ?? 0); ?>">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Cập nhật trạng thái</label>
            <select name="status" class="form-select" required>
                <?php foreach (['pending', 'paid', 'accepted', 'completed', 'rejected', 'cancelled'] as $item): ?>
                    <option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo admin_status_label($item); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Cập nhật</button>
        </div>
    </form>
    <form method="POST" class="d-inline" onsubmit="return confirm('Xóa đơn này?');">
        <?php echo Csrf::field('admin_booking_action'); ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo (int)($booking['id'] ?? 0); ?>">
        <button type="submit" class="btn btn-outline-danger mt-3"><i class="fa fa-trash"></i> Xóa đơn</button>
    </form>
</div>

<?php admin_layout_end(); ?>
