<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new BookingController($db, $activityLog);
$action = $_GET['action'] ?? '';

if ($action === 'update_status' && isset($_GET['id'], $_GET['status'])) {
    $controller->updateStatus();
}
if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteBooking();
}

$data = $controller->listBookings();

admin_layout_start('Quản lý booking', 'Theo dõi các đơn đặt phòng, tiền cọc và trạng thái xử lý giữa người thuê với chủ phòng.', 'bookings');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="pending" <?php echo ($data['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="paid" <?php echo ($data['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>Đã cọc</option>
                <option value="accepted" <?php echo ($data['status'] ?? '') === 'accepted' ? 'selected' : ''; ?>>Đã chấp nhận</option>
                <option value="completed" <?php echo ($data['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Hoàn tất</option>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Lọc danh sách</button>
            <a href="<?php echo ADMIN_URL; ?>bookings.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách booking</h2>
    <span class="wb-pill"><?php echo (int)($data['total'] ?? 0); ?> đơn</span>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['bookings'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người thuê</th>
                    <th>Phòng trọ</th>
                    <th>Tiền cọc</th>
                    <th>Check-in</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['bookings'] as $booking): ?>
                    <?php $status = (string)($booking['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$booking['id']; ?></td>
                        <td><?php echo admin_e($booking['user_name'] ?? 'N/A'); ?></td>
                        <td class="wb-title"><?php echo admin_e(substr((string)($booking['motel_title'] ?? 'N/A'), 0, 55)); ?></td>
                        <td class="wb-price"><?php echo admin_money($booking['deposit_amount'] ?? 0); ?></td>
                        <td><?php echo admin_e($booking['checkin_date'] ?? ''); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td class="text-end">
                            <a href="<?php echo ADMIN_URL . 'booking_detail.php?id=' . (int)$booking['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <a href="<?php echo ADMIN_URL . 'bookings.php?action=delete&id=' . (int)$booking['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xóa đơn này?');"><i class="fa fa-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có booking phù hợp bộ lọc.</div>
    <?php endif; ?>
</div>

<?php if (($data['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo ADMIN_URL . 'bookings.php?page=' . $i . (!empty($data['status']) ? '&status=' . urlencode($data['status']) : ''); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>
