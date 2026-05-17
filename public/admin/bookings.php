<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new BookingController($db, $activityLog);
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['update_status', 'delete'], true)) {
    if (!Csrf::validateRequest('admin_booking_action')) {
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
        header('Location: ' . ADMIN_URL . 'bookings.php');
        exit;
    }

    if ($action === 'update_status') {
        $controller->updateStatus();
    }

    if ($action === 'delete') {
        $controller->deleteBooking();
    }
}

$data = $controller->listBookings();

admin_layout_start('Quáº£n lÃ½ booking', 'Theo dÃµi cÃ¡c Ä‘Æ¡n Ä‘áº·t phÃ²ng, tiá»n cá»c vÃ  tráº¡ng thÃ¡i xá»­ lÃ½ giá»¯a ngÆ°á»i thuÃª vá»›i chá»§ phÃ²ng.', 'bookings');
admin_flash_messages();
?>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Tráº¡ng thÃ¡i</label>
            <select name="status" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <option value="pending" <?php echo ($data['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Chá» xá»­ lÃ½</option>
                <option value="paid" <?php echo ($data['status'] ?? '') === 'paid' ? 'selected' : ''; ?>>ÄÃ£ cá»c</option>
                <option value="accepted" <?php echo ($data['status'] ?? '') === 'accepted' ? 'selected' : ''; ?>>ÄÃ£ cháº¥p nháº­n</option>
                <option value="completed" <?php echo ($data['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>HoÃ n táº¥t</option>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Lá»c danh sÃ¡ch</button>
            <a href="<?php echo ADMIN_URL; ?>bookings.php" class="btn btn-outline-secondary">XÃ³a lá»c</a>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sÃ¡ch booking</h2>
    <span class="wb-pill"><?php echo (int)($data['total'] ?? 0); ?> Ä‘Æ¡n</span>
</div>

<div class="wb-table-card">
    <?php if (!empty($data['bookings'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>NgÆ°á»i thuÃª</th>
                    <th>PhÃ²ng trá»</th>
                    <th>Tiá»n cá»c</th>
                    <th>Check-in</th>
                    <th>Tráº¡ng thÃ¡i</th>
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
                            <?php if ($status === 'pending'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Cháº¥p nháº­n booking nÃ y?');">
                                    <?php echo Csrf::field('admin_booking_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="accepted">
                                    <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i></button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Tá»« chá»‘i booking nÃ y?');">
                                    <?php echo Csrf::field('admin_booking_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="rejected">
                                    <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-times"></i></button>
                                </form>
                            <?php elseif (in_array($status, ['paid', 'accepted'], true)): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('ÄÃ¡nh dáº¥u booking hoÃ n táº¥t?');">
                                    <?php echo Csrf::field('admin_booking_action'); ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="status" value="completed">
                                    <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-flag-checkered"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('XÃ³a Ä‘Æ¡n nÃ y?');">
                                <?php echo Csrf::field('admin_booking_action'); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$booking['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">KhÃ´ng cÃ³ booking phÃ¹ há»£p bá»™ lá»c.</div>
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

