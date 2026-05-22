<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . ADMIN_URL . 'users.php');
    exit;
}

$conn = $db->getConnection();
$activityLog = new ActivityLog($db);
$ownerModeration = new OwnerModeration($db);

function admin_user_detail_one(mysqli $conn, string $sql, int $id): ?array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $row;
}

function admin_user_detail_many(mysqli $conn, string $sql, string $types, array $params): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($params !== []) {
        $bind = [$types];
        foreach ($params as $key => $value) {
            $bind[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

$user = admin_user_detail_one($conn, 'SELECT * FROM users WHERE id = ? LIMIT 1', $id);
if (!$user) {
    $_SESSION['error'] = 'Tài khoản không tồn tại.';
    header('Location: ' . ADMIN_URL . 'users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Csrf::validateRequest('admin_user_detail_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: user_detail.php?id=' . $id);
        exit;
    }

    $adminId = (int)($_SESSION['user_id'] ?? 0);
    $action = (string)$_POST['action'];
    if (($user['role'] ?? '') === 'owner' && $action === 'approve') {
        $stmt = $conn->prepare("UPDATE users SET status = 'approved', owner_verification_status = 'approved', approved_by = ?, approved_at = NOW(), verified_at = NOW(), verification_reviewed_by = ?, verification_reviewed_at = NOW(), verification_rejection_reason = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('iii', $adminId, $adminId, $id);
            if ($stmt->execute()) {
                $activityLog->log($adminId, 'approve_user', 'user', $id, ['old' => $user['owner_verification_status'] ?? null, 'new' => 'approved'], "Duyệt hồ sơ owner {$user['email']}");
                $_SESSION['success'] = 'Đã duyệt owner.';
            }
            $stmt->close();
        }
    }

    if (($user['role'] ?? '') === 'owner' && $action === 'reject') {
        $reason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($reason === '') {
            $_SESSION['error'] = 'Cần nhập lý do từ chối owner.';
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = 'approved', owner_verification_status = 'rejected', approved_by = ?, approved_at = NOW(), verification_reviewed_by = ?, verification_reviewed_at = NOW(), verification_rejection_reason = ?, rejection_reason = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('iissi', $adminId, $adminId, $reason, $reason, $id);
                if ($stmt->execute()) {
                    $activityLog->log($adminId, 'reject_user', 'user', $id, ['old' => $user['owner_verification_status'] ?? null, 'new' => 'rejected'], "Từ chối hồ sơ owner {$user['email']}. Lý do: {$reason}");
                    $_SESSION['success'] = 'Đã từ chối hồ sơ owner.';
                }
                $stmt->close();
            }
        }
    }

    if (($user['role'] ?? '') === 'owner' && $action === 'warning') {
        $level = (string)($_POST['warning_level'] ?? '');
        $reason = trim((string)($_POST['warning_reason'] ?? ''));
        $note = trim((string)($_POST['warning_note'] ?? ''));
        if (!$ownerModeration->validWarningLevel($level) || $reason === '') {
            $_SESSION['error'] = 'Cảnh báo owner cần cấp độ và lý do hợp lệ.';
        } elseif ($ownerModeration->createWarning($id, $adminId, $level, $reason, $note)) {
            $activityLog->log($adminId, 'warning_owner', 'user', $id, [], "Moderation owner {$user['email']}: {$level}. {$reason}");
            $_SESSION['success'] = 'Đã lưu cảnh báo owner.';
        } else {
            $_SESSION['error'] = 'Chưa thể lưu cảnh báo. Cần chạy migration owner_warnings.';
        }
    }

    if ($action === 'status') {
        $nextStatus = (string)($_POST['status'] ?? '');
        if ($id === $adminId || !in_array($nextStatus, ['approved', 'blocked'], true)) {
            $_SESSION['error'] = 'Không thể cập nhật trạng thái tài khoản này.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $nextStatus, $id);
                if ($stmt->execute()) {
                    $entityAction = (($user['role'] ?? '') === 'owner' ? ($nextStatus === 'blocked' ? 'block_owner' : 'unblock_owner') : 'update_user_status');
                    $activityLog->log($adminId, $entityAction, 'user', $id, ['old' => $user['status'], 'new' => $nextStatus], "Cập nhật tài khoản {$user['email']} sang {$nextStatus}");
                    $_SESSION['success'] = 'Đã cập nhật trạng thái tài khoản.';
                }
                $stmt->close();
            }
        }
    }

    header('Location: user_detail.php?id=' . $id);
    exit;
}

$motels = admin_user_detail_many($conn, 'SELECT * FROM motels WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 10', 'i', [$id]);
$bookings = admin_user_detail_many(
    $conn,
    'SELECT b.*, m.title AS motel_title
     FROM bookings b
     LEFT JOIN motels m ON m.id = b.motel_id
     WHERE b.user_id = ? OR m.user_id = ?
     ORDER BY b.created_at DESC, b.id DESC
     LIMIT 10',
    'ii',
    [$id, $id]
);
$reviews = admin_user_detail_many($conn, 'SELECT * FROM reviews WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5', 'i', [$id]);
$ownerRisk = ($user['role'] ?? '') === 'owner' ? $ownerModeration->getOwnerRisk($id) : null;
$warningTableExists = $ownerModeration->warningTableExists();
$warnings = $ownerRisk ? $ownerModeration->getWarnings($id, 10) : [];
$status = (string)($user['status'] ?? 'pending');
$verificationStatus = (string)($user['owner_verification_status'] ?? 'not_required');

admin_layout_start('Chi tiết tài khoản', 'Hồ sơ tài khoản, owner risk và moderation thủ công của admin.', ($user['role'] ?? '') === 'owner' ? 'user_approvals' : 'users');
admin_flash_messages();
?>

<div class="wb-actions mb-3">
    <a href="<?php echo ($user['role'] ?? '') === 'owner' ? ADMIN_URL . 'user_approvals.php' : ADMIN_URL . 'users.php'; ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Quay lại</a>
    <a href="<?php echo ADMIN_URL . 'user_edit.php?id=' . (int)$user['id']; ?>" class="btn btn-warning"><i class="bi bi-pencil"></i> Sửa tài khoản</a>
    <?php if ((int)$user['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
        <form method="POST" data-admin-confirm="Xác nhận cập nhật trạng thái tài khoản?">
            <?php echo Csrf::field('admin_user_detail_action'); ?>
            <input type="hidden" name="action" value="status">
            <input type="hidden" name="status" value="<?php echo $status === 'blocked' ? 'approved' : 'blocked'; ?>">
            <button class="btn <?php echo $status === 'blocked' ? 'btn-success' : 'btn-outline-secondary'; ?>" type="submit">
                <i class="bi <?php echo $status === 'blocked' ? 'bi-unlock' : 'bi-lock'; ?>"></i>
                <?php echo $status === 'blocked' ? 'Mở khóa' : 'Khóa'; ?>
            </button>
        </form>
    <?php endif; ?>
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
            <div class="wb-muted"><?php echo admin_e($user['email'] ?? ''); ?> · <?php echo admin_e($user['phone'] ?? 'Chưa cập nhật'); ?></div>
        </div>
        <?php if (($user['role'] ?? '') === 'owner'): ?>
            <span class="wb-pill <?php echo admin_pill_class($verificationStatus); ?>"><?php echo admin_status_label($verificationStatus); ?></span>
        <?php endif; ?>
    </div>
</div>

<?php if (($user['role'] ?? '') === 'owner'): ?>
    <div class="wb-section-head"><h2>Owner risk</h2><span class="wb-pill">Không tự động suspend</span></div>
    <div class="wb-grid wb-stats-4 mb-3">
        <div class="wb-card"><div class="wb-card-label">Tổng phòng</div><div class="wb-card-value"><?php echo (int)($ownerRisk['total_rooms'] ?? 0); ?></div></div>
        <div class="wb-card"><div class="wb-card-label">Approved / Rejected</div><div class="wb-card-value fs-4"><?php echo (int)($ownerRisk['approved_rooms'] ?? 0); ?> / <?php echo (int)($ownerRisk['rejected_rooms'] ?? 0); ?></div></div>
        <div class="wb-card"><div class="wb-card-label">Hidden / Reports</div><div class="wb-card-value fs-4"><?php echo (int)($ownerRisk['hidden_rooms'] ?? 0); ?> / <?php echo (int)($ownerRisk['total_reports'] ?? 0); ?></div></div>
        <div class="wb-card"><div class="wb-card-label">Risk score</div><div class="wb-card-value fs-4"><span class="wb-pill <?php echo admin_pill_class((string)($ownerRisk['risk_level'] ?? 'low')); ?>"><?php echo admin_status_label((string)($ownerRisk['risk_level'] ?? 'low')); ?> · <?php echo (int)($ownerRisk['risk_score'] ?? 0); ?></span></div><div class="wb-card-label mt-2">Booking hủy: <?php echo (int)($ownerRisk['cancelled_bookings'] ?? 0); ?></div></div>
    </div>

    <?php if (in_array($verificationStatus, ['submitted', 'pending_verification'], true)): ?>
        <div class="wb-card mb-3">
            <div class="wb-actions">
                <form method="POST">
                    <?php echo Csrf::field('admin_user_detail_action'); ?>
                    <input type="hidden" name="action" value="approve">
                    <button class="btn btn-success" type="submit" data-admin-confirm="Duyệt hồ sơ owner này?"><i class="bi bi-check-lg"></i> Duyệt owner</button>
                </form>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal"><i class="bi bi-ban"></i> Từ chối owner</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="wb-card h-100">
                <div class="wb-title mb-3">Tạo moderation note</div>
                <?php if ($warningTableExists): ?>
                    <form method="POST" class="row g-3">
                        <?php echo Csrf::field('admin_user_detail_action'); ?>
                        <input type="hidden" name="action" value="warning">
                        <div class="col-12">
                            <label class="form-label">Mức độ</label>
                            <select name="warning_level" class="form-select" required>
                                <?php foreach (['reminder', 'warning', 'severe_warning', 'posting_suspended'] as $level): ?>
                                    <option value="<?php echo admin_e($level); ?>"><?php echo admin_status_label($level); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Lý do</label>
                            <input type="text" name="warning_reason" class="form-control" maxlength="255" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="warning_note" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-shield-exclamation"></i> Lưu moderation</button></div>
                    </form>
                <?php else: ?>
                    <div class="wb-empty">Chạy migration `002_owner_warnings.sql` để lưu cảnh báo owner.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="wb-list-card h-100">
                <div class="p-3 border-bottom"><div class="wb-title">Lịch sử moderation owner</div></div>
                <?php if ($warnings): ?>
                    <?php foreach ($warnings as $warning): ?>
                        <div class="wb-list-row">
                            <div>
                                <div class="wb-title"><?php echo admin_e($warning['reason']); ?></div>
                                <div class="wb-muted"><?php echo admin_e($warning['admin_name'] ?? 'Admin'); ?> · <?php echo !empty($warning['created_at']) ? date('d/m/Y H:i', strtotime((string)$warning['created_at'])) : ''; ?></div>
                                <?php if (!empty($warning['note'])): ?><div><?php echo nl2br(admin_e($warning['note'])); ?></div><?php endif; ?>
                            </div>
                            <span class="wb-pill <?php echo admin_pill_class((string)$warning['warning_level']); ?>"><?php echo admin_status_label((string)$warning['warning_level']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="wb-empty">Chưa có moderation note cho owner này.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="wb-section-head"><h2>Hồ sơ xác minh</h2></div>
    <div class="wb-table-card mb-3">
        <table class="wb-table"><tbody>
            <tr><td class="fw-bold">CCCD</td><td><?php echo admin_e($user['idcard_number'] ?? 'Chưa cập nhật'); ?></td></tr>
            <tr><td class="fw-bold">Ngân hàng</td><td><?php echo admin_e($user['bank_name'] ?? 'Chưa cập nhật'); ?></td></tr>
            <tr><td class="fw-bold">Số tài khoản</td><td><?php echo admin_e($user['bank_account_no'] ?? 'Chưa cập nhật'); ?></td></tr>
            <tr><td class="fw-bold">Chủ tài khoản</td><td><?php echo admin_e($user['bank_account_name'] ?? 'Chưa cập nhật'); ?></td></tr>
            <tr><td class="fw-bold">Địa chỉ</td><td><?php echo admin_e($user['address'] ?? 'Chưa cập nhật'); ?></td></tr>
        </tbody></table>
    </div>
<?php endif; ?>

<div class="wb-section-head"><h2>Phòng liên quan</h2><span class="wb-pill"><?php echo count($motels); ?> phòng gần nhất</span></div>
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
                        <div><div class="wb-title"><?php echo admin_e($booking['motel_title'] ?? 'N/A'); ?></div><div class="wb-muted"><?php echo admin_e($booking['checkin_date'] ?? $booking['check_in_date'] ?? ''); ?></div></div>
                        <span class="wb-pill <?php echo admin_pill_class((string)$booking['status']); ?>"><?php echo admin_status_label((string)$booking['status']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?><div class="wb-empty">Không có booking.</div><?php endif; ?>
        </div>
    </div>
    <div class="col-md-5">
        <div class="wb-list-card h-100">
            <div class="p-3 border-bottom"><div class="wb-title">Đánh giá đã viết</div></div>
            <?php if ($reviews): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="wb-list-row"><div><div class="wb-title"><?php echo (int)($review['rating'] ?? 0); ?>/5</div><div class="wb-muted"><?php echo admin_e(substr((string)($review['comment'] ?? ''), 0, 80)); ?></div></div></div>
                <?php endforeach; ?>
            <?php else: ?><div class="wb-empty">Không có đánh giá.</div><?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content"><form method="POST">
        <?php echo Csrf::field('admin_user_detail_action'); ?>
        <div class="modal-header"><h5 class="modal-title">Từ chối owner</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="action" value="reject">
            <label class="form-label">Lý do từ chối</label>
            <textarea name="rejection_reason" class="form-control" rows="4" required></textarea>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-danger">Từ chối</button></div>
    </form></div></div>
</div>

<?php admin_layout_end(); ?>
