<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
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
                $activityLog->log($adminId, 'approve_user', 'user', $id, [], "Duyá»‡t tÃ i khoáº£n owner: {$userForAction['name']} ({$userForAction['email']})");
                $_SESSION['success'] = "ÄÃ£ duyá»‡t tÃ i khoáº£n {$userForAction['name']}";
            }
        }
        if ($_POST['action'] === 'reject') {
            $reason = trim((string)($_POST['rejection_reason'] ?? ''));
            if ($reason !== '') {
                $reasonEsc = $db->getConnection()->real_escape_string($reason);
                if ($db->query("UPDATE users SET status = 'rejected', approved_by = {$adminId}, approved_at = NOW(), rejection_reason = '{$reasonEsc}' WHERE id = {$id}")) {
                    $activityLog->log($adminId, 'reject_user', 'user', $id, [], "Tá»« chá»‘i tÃ i khoáº£n owner: {$userForAction['name']}. LÃ½ do: {$reason}");
                    $_SESSION['success'] = "ÄÃ£ tá»« chá»‘i tÃ i khoáº£n {$userForAction['name']}";
                }
            }
        }
    }
    header('Location: user_detail.php?id=' . $id);
    exit;
}

$user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
if (!$user) {
    $_SESSION['error'] = 'NgÆ°á»i dÃ¹ng khÃ´ng tá»“n táº¡i';
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

admin_layout_start('Chi tiáº¿t tÃ i khoáº£n', 'Xem há»“ sÆ¡ ngÆ°á»i dÃ¹ng, lá»‹ch sá»­ liÃªn quan vÃ  xá»­ lÃ½ tráº¡ng thÃ¡i owner.', $user['role'] === 'owner' ? 'user_approvals' : 'users');
admin_flash_messages();
?>

<div class="wb-actions mb-3">
    <a href="<?php echo $user['role'] === 'owner' ? ADMIN_URL . 'user_approvals.php' : ADMIN_URL . 'users.php'; ?>" class="btn btn-outline-secondary"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>
    <a href="<?php echo ADMIN_URL . 'user_edit.php?id=' . (int)$user['id']; ?>" class="btn btn-warning"><i class="fa fa-edit"></i> Sá»­a tÃ i khoáº£n</a>
</div>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">TÃ i khoáº£n</div><div class="wb-card-value">#<?php echo (int)$user['id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Vai trÃ²</div><div class="mt-2"><span class="wb-pill"><?php echo admin_status_label((string)$user['role']); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Tráº¡ng thÃ¡i</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">NgÃ y táº¡o</div><div class="wb-card-value fs-5"><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string)$user['created_at'])) : ''; ?></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title"><?php echo admin_e($user['name'] ?? 'N/A'); ?></div>
            <div class="wb-muted"><?php echo admin_e($user['email'] ?? ''); ?> Â· <?php echo admin_e($user['phone'] ?? '-'); ?></div>
        </div>
    </div>
    <?php if (!empty($user['admin_note']) || !empty($user['rejection_reason'])): ?>
        <div class="wb-list-row">
            <div>
                <div class="wb-title">Ghi chÃº</div>
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
                <button type="submit" class="btn btn-success" onclick="return confirm('Duyá»‡t owner nÃ y?');"><i class="fa fa-check"></i> Duyá»‡t owner</button>
            </form>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="fa fa-times"></i> Tá»« chá»‘i</button>
        </div>
    </div>
<?php endif; ?>

<div class="wb-section-head"><h2>Há»“ sÆ¡ xÃ¡c minh</h2></div>
<div class="wb-table-card mb-3">
    <table class="wb-table">
        <tbody>
            <tr>
                <td class="fw-bold">Sá»‘ CCCD:</td>
                <td><?php echo admin_e($user['idcard_number'] ?? 'ChÆ°a cáº­p nháº­t'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">NgÃ¢n hÃ ng:</td>
                <td><?php echo admin_e($user['bank_name'] ?? 'ChÆ°a cáº­p nháº­t'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Sá»‘ tÃ i khoáº£n:</td>
                <td><?php echo admin_e($user['bank_account_no'] ?? 'ChÆ°a cáº­p nháº­t'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Chá»§ tÃ i khoáº£n:</td>
                <td><?php echo admin_e($user['bank_account_name'] ?? 'ChÆ°a cáº­p nháº­t'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Äá»‹a chá»‰:</td>
                <td><?php echo admin_e($user['address'] ?? 'ChÆ°a cáº­p nháº­t'); ?></td>
            </tr>
            <tr>
                <td class="fw-bold">Sá»‘ Ä‘iá»‡n thoáº¡i:</td>
                <td><?php echo admin_e($user['phone'] ?? 'ChÆ°a cáº­p nháº­t'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php if (!empty($user['id_card_front']) || !empty($user['id_card_back'])): ?>
<div class="wb-section-head"><h2>áº¢nh tÃ i liá»‡u CCCD</h2></div>
<div class="row g-3 mb-3">
    <?php if (!empty($user['id_card_front'])): ?>
    <div class="col-md-6">
        <div class="wb-card">
            <div class="p-2 text-center border-bottom"><small class="text-muted">Máº·t trÆ°á»›c CCCD</small></div>
            <div class="p-3">
                <img src="../<?php echo htmlspecialchars($user['id_card_front']); ?>" alt="Máº·t trÆ°á»›c CCCD" class="img-fluid border rounded" style="max-height: 300px; object-fit: contain;">
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($user['id_card_back'])): ?>
    <div class="col-md-6">
        <div class="wb-card">
            <div class="p-2 text-center border-bottom"><small class="text-muted">Máº·t sau CCCD</small></div>
            <div class="p-3">
                <img src="../<?php echo htmlspecialchars($user['id_card_back']); ?>" alt="Máº·t sau CCCD" class="img-fluid border rounded" style="max-height: 300px; object-fit: contain;">
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="wb-card mb-3">
    <div class="p-3 text-center text-muted">
        <i class="fa fa-info-circle me-2"></i> Owner chÆ°a upload áº£nh CCCD
    </div>
</div>
<?php endif; ?>

<div class="wb-section-head"><h2>PhÃ²ng liÃªn quan</h2><span class="wb-pill"><?php echo count($motels); ?> phÃ²ng</span></div>
<div class="wb-table-card mb-3">
    <?php if ($motels): ?>
        <table class="wb-table">
            <thead><tr><th>ID</th><th>TiÃªu Ä‘á»</th><th>GiÃ¡</th><th>Tráº¡ng thÃ¡i</th><th></th></tr></thead>
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
        <div class="wb-empty">KhÃ´ng cÃ³ phÃ²ng liÃªn quan.</div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="wb-list-card h-100">
            <div class="p-3 border-bottom"><div class="wb-title">Booking liÃªn quan</div></div>
            <?php if ($bookings): ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="wb-list-row">
                        <div><div class="wb-title"><?php echo admin_e($booking['motel_title'] ?? 'N/A'); ?></div><div class="wb-muted"><?php echo admin_e($booking['checkin_date'] ?? ''); ?></div></div>
                        <span class="wb-pill <?php echo admin_pill_class((string)$booking['status']); ?>"><?php echo admin_status_label((string)$booking['status']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="wb-empty">KhÃ´ng cÃ³ booking.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-5">
        <div class="wb-list-card h-100">
            <div class="p-3 border-bottom"><div class="wb-title">ÄÃ¡nh giÃ¡ Ä‘Ã£ viáº¿t</div></div>
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="wb-list-row">
                        <div><div class="wb-title"><?php echo (int)($review['rating'] ?? 0); ?>/5</div><div class="wb-muted"><?php echo admin_e(substr((string)($review['comment'] ?? ''), 0, 80)); ?></div></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="wb-empty">KhÃ´ng cÃ³ Ä‘Ã¡nh giÃ¡.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header"><h5 class="modal-title">Tá»« chá»‘i owner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <label class="form-label">LÃ½ do tá»« chá»‘i</label>
                    <textarea name="rejection_reason" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Há»§y</button><button type="submit" class="btn btn-danger">Tá»« chá»‘i</button></div>
            </form>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

