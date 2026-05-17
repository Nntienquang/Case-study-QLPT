<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ' . ADMIN_URL . 'users.php');
    exit;
}

$id = (int)$_GET['id'];
$activityLog = new ActivityLog($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userForAction = $db->getRow("SELECT * FROM users WHERE id = {$id}");
    if ($userForAction && $userForAction['role'] === 'owner') {
        $adminId = (int)$_SESSION['user_id'];
        if ($_POST['action'] === 'approve') {
            if ($db->query("UPDATE users SET status = 'approved', approved_by = {$adminId}, approved_at = NOW() WHERE id = {$id}")) {
                $activityLog->log($adminId, 'approve_user', 'user', $id, [], "Duyệt tài khoản owner: {$userForAction['name']} ({$userForAction['email']})");
                $_SESSION['success'] = "Đã duyệt tài khoản {$userForAction['name']}";
            }
        }
        if ($_POST['action'] === 'reject') {
            $reason = trim((string)($_POST['rejection_reason'] ?? ''));
            if ($reason !== '') {
                $reasonEsc = $db->getConnection()->real_escape_string($reason);
                if ($db->query("UPDATE users SET status = 'rejected', approved_by = {$adminId}, approved_at = NOW(), rejection_reason = '{$reasonEsc}' WHERE id = {$id}")) {
                    $activityLog->log($adminId, 'reject_user', 'user', $id, [], "Từ chối tài khoản owner: {$userForAction['name']}. Lý do: {$reason}");
                    $_SESSION['success'] = "Đã từ chối tài khoản {$userForAction['name']}";
                }
            }
        }
    }
    header('Location: user_detail.php?id=' . $id);
    exit;
}

$user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
if (!$user) {
    $_SESSION['error'] = 'Người dùng không tồn tại';
    header('Location: ' . ADMIN_URL . 'users.php');
    exit;
}

$motels = $db->getRows("SELECT * FROM motels WHERE user_id = {$id} ORDER BY created_at DESC LIMIT 10");
$bookings = $db->getRows(
    "SELECT b.*, m.title AS motel_title
     FROM bookings b
     LEFT JOIN motels m ON b.motel_id = m.id
     WHERE b.user_id = {$id} OR m.user_id = {$id}
     ORDER BY b.created_at DESC
     LIMIT 10"
);
$reviews = $db->getRows("SELECT * FROM reviews WHERE user_id = {$id} ORDER BY created_at DESC LIMIT 5");
$status = (string)($user['status'] ?? 'pending');

admin_layout_start('Chi tiết tài khoản', 'Xem hồ sơ người dùng, lịch sử liên quan và xử lý trạng thái owner.', $user['role'] === 'owner' ? 'user_approvals' : 'users');
admin_flash_messages();
?>

<div class="wb-actions mb-3">
    <a href="<?php echo $user['role'] === 'owner' ? ADMIN_URL . 'user_approvals.php' : ADMIN_URL . 'users.php'; ?>" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Quay lại</a>
    <a href="<?php echo ADMIN_URL . 'user_edit.php?id=' . (int)$user['id']; ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Sửa tài khoản</a>
</div>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Tài khoản</div><div class="wb-card-value">#<?php echo (int)$user['id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Vai trò</div><div class="mt-2"><span class="wb-pill"><?php echo admin_status_label((string)$user['role']); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Trạng thái</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Ngày tạo</div><div class="wb-card-value fs-5"><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string)$user['created_at'])) : ''; ?></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title"><?php echo admin_e($user['name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($user['email'] ?? ''); ?> · <?php echo admin_e($user['phone'] ?? '-'); ?></div>
        </div>
    </div>
    <?php if (!empty($user['admin_note']) || !empty($user['rejection_reason'])): ?>
        <div class="wb-list-row">
            <div>
                <div class="wb-title">Ghi chú</div>
                <div><?php echo admin_e($user['admin_note'] ?? $user['rejection_reason'] ?? ''); ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($user['role'] === 'owner' && $status === 'pending'): ?>
    <div class="wb-card mb-3">
        <div class="wb-actions">
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success" onclick="return confirm('Duyệt owner này?');"><i class="fa fa-check"></i> Duyệt owner</button>
            </form>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="fa fa-times"></i> Từ chối</button>
        </div>
    </div>
<?php endif; ?>

<div class="wb-section-head"><h2>Hồ sơ xác minh</h2></div>
<div class="wb-table-card mb-3">
    <table class="wb-table">
        <tbody>
            <tr>
                <td class="fw-bold">Số CCCD:</td>
                <td><?php echo admin_e($user['idcard_number'] ?? 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Ngân hàng:</td>
                <td><?php echo admin_e($user['bank_name'] ?? 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Số tài khoản:</td>
                <td><?php echo admin_e($user['bank_account_no'] ?? 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Chủ tài khoản:</td>
                <td><?php echo admin_e($user['bank_account_name'] ?? 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Địa chỉ:</td>
                <td><?php echo admin_e($user['address'] ?? 'Chưa cập nhật'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Số điện thoại:</td>
                <td><?php echo admin_e($user['phone'] ?? 'Chưa cập nhật'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php if (!empty($user['id_card_front']) || !empty($user['id_card_back'])): ?>
<div class="wb-section-head"><h2>Ảnh tài liệu CCCD</h2></div>
<div class="row g-3 mb-3">
    <?php if (!empty($user['id_card_front'])): ?>
    <div class="col-md-6">
        <div class="wb-card">
            <div class="p-2 text-center border-bottom"><small class="text-muted">Mặt trước CCCD</small></div>
            <div class="p-3">
                <img src="../<?php echo htmlspecialchars($user['id_card_front']); ?>" alt="Mặt trước CCCD" class="img-fluid border rounded" style="max-height: 300px; object-fit: contain;">
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($user['id_card_back'])): ?>
    <div class="col-md-6">
        <div class="wb-card">
            <div class="p-2 text-center border-bottom"><small class="text-muted">Mặt sau CCCD</small></div>
            <div class="p-3">
                <img src="../<?php echo htmlspecialchars($user['id_card_back']); ?>" alt="Mặt sau CCCD" class="img-fluid border rounded" style="max-height: 300px; object-fit: contain;">
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="wb-card mb-3">
    <div class="p-3 text-center text-muted">
        <i class="fa fa-info-circle me-2"></i> Owner chưa upload ảnh CCCD
    </div>
</div>
<?php endif; ?>

<div class="wb-section-head"><h2>Phòng liên quan</h2><span class="wb-pill"><?php echo count($motels); ?> phòng</span></div>
<div class="wb-table-card mb-3">
    <?php if ($motels): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>Tiêu đề</th><th>Giá</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($motels as $motel): ?>
                    <tr>
                        <td>#<?php echo (int)$motel['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($motel['title'] ?? 'N/A'); ?></td>
                        <td class="wb-price"><?php echo admin_money($motel['price'] ?? 0); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class((string)$motel['status']); ?>"><?php echo admin_status_label((string)$motel['status']); ?></span></td>
                        <td class="text-end"><a href="motel_detail.php?id=<?php echo (int)$motel['id']; ?>" class="btn btn-sm btn-primary">Xem</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có phòng liên quan.</div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="wb-list-card h-100">
            <div class="p-3 border-bottom"><div class="wb-title">Booking liên quan</div></div>
            <?php if ($bookings): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="wb-list-row">
                        <div><div class="wb-title"><?php echo admin_e($booking['motel_title'] ?? 'N/A'); ?></div><div class="wb-muted"><?php echo admin_e($booking['checkin_date'] ?? ''); ?></div></div>
                        <span class="wb-pill <?php echo admin_pill_class((string)$booking['status']); ?>"><?php echo admin_status_label((string)$booking['status']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="wb-empty">Không có booking.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-5">
        <div class="wb-list-card h-100">
            <div class="p-3 border-bottom"><div class="wb-title">Đánh giá đã viết</div></div>
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="wb-list-row">
                        <div><div class="wb-title"><?php echo (int)($review['rating'] ?? 0); ?>/5</div><div class="wb-muted"><?php echo admin_e(substr((string)($review['comment'] ?? ''), 0, 80)); ?></div></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="wb-empty">Không có đánh giá.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Từ chối owner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <label class="form-label">Lý do từ chối</label>
                    <textarea name="rejection_reason" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-danger">Từ chối</button></div>
            </form>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>
