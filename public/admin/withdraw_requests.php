<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['role'] ?? $_SESSION['user_role'] ?? '') !== ROLE_ADMIN) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

function withdraw_redirect(): void
{
    header('Location: ' . ADMIN_URL . 'withdraw_requests.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(($_POST['action'] ?? ''), ['approve', 'reject'], true)) {
    if (!Csrf::validateRequest('admin_withdraw_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        withdraw_redirect();
    }

    $id = (int)($_POST['id'] ?? 0);
    $action = (string)$_POST['action'];
    $adminId = (int)($_SESSION['user_id'] ?? 0);

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            SELECT wr.*, u.name, u.email, u.bank_name, u.bank_account_no, u.bank_account_name
            FROM withdraw_requests wr
            JOIN users u ON u.id = wr.user_id
            WHERE wr.id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$request) {
            throw new RuntimeException('Yêu cầu rút tiền không tồn tại.');
        }
        if (($request['status'] ?? '') !== 'pending') {
            throw new RuntimeException('Yêu cầu này đã được xử lý.');
        }

        $ownerId = (int)$request['user_id'];
        $amount = (int)$request['amount'];
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $conn->prepare('UPDATE withdraw_requests SET status = ? WHERE id = ? AND status = "pending"');
        $stmt->bind_param('si', $newStatus, $id);
        $stmt->execute();
        $stmt->close();

        if ($action === 'reject') {
            $stmt = $conn->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)');
            $stmt->bind_param('ii', $ownerId, $amount);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO transactions (from_user, to_user, amount, type, created_at) VALUES (?, ?, ?, 'refund', NOW())");
            $stmt->bind_param('iii', $adminId, $ownerId, $amount);
            $stmt->execute();
            $stmt->close();
        }

        $title = $action === 'approve' ? 'Yêu cầu rút tiền đã được duyệt' : 'Yêu cầu rút tiền bị từ chối';
        $body = $action === 'approve'
            ? 'Admin đã duyệt yêu cầu rút ' . number_format($amount) . ' VNĐ. Vui lòng kiểm tra tài khoản ngân hàng.'
            : 'Admin đã từ chối yêu cầu rút ' . number_format($amount) . ' VNĐ. Số tiền đã được hoàn lại ví.';
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?, 'withdraw', ?, ?, 'owner/revenue.php', NOW())");
        $stmt->bind_param('iss', $ownerId, $title, $body);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $_SESSION['success'] = $action === 'approve' ? 'Đã duyệt yêu cầu rút tiền.' : 'Đã từ chối và hoàn tiền về ví owner.';
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Không thể xử lý yêu cầu: ' . $e->getMessage();
    }

    withdraw_redirect();
}

$status = (string)($_GET['status'] ?? '');
$where = '';
if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $where = "WHERE wr.status = '" . $conn->real_escape_string($status) . "'";
}

$stats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'pending_amount' => 0,
];
$result = $conn->query("SELECT status, COUNT(*) AS count, COALESCE(SUM(amount),0) AS amount FROM withdraw_requests GROUP BY status");
while ($row = $result->fetch_assoc()) {
    $key = (string)$row['status'];
    if (isset($stats[$key])) {
        $stats[$key] = (int)$row['count'];
    }
    if ($key === 'pending') {
        $stats['pending_amount'] = (int)$row['amount'];
    }
}

$requests = $db->getRows("
    SELECT wr.*, u.name, u.email, u.phone, u.bank_name, u.bank_account_no, u.bank_account_name, COALESCE(w.balance, 0) AS current_balance
    FROM withdraw_requests wr
    LEFT JOIN users u ON u.id = wr.user_id
    LEFT JOIN wallets w ON w.user_id = wr.user_id
    {$where}
    ORDER BY wr.created_at DESC, wr.id DESC
");

admin_layout_start('Duyệt rút tiền', 'Kiểm tra thông tin ngân hàng và xử lý yêu cầu rút tiền của chủ phòng.', 'withdraw_requests');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Chờ xử lý</div><div class="wb-card-value"><?php echo (int)$stats['pending']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tổng tiền chờ rút</div><div class="wb-card-value fs-4"><?php echo admin_money($stats['pending_amount']); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Đã duyệt</div><div class="wb-card-value"><?php echo (int)$stats['approved']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Từ chối</div><div class="wb-card-value"><?php echo (int)$stats['rejected']; ?></div></div>
</div>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <?php foreach (['pending' => 'Chờ xử lý', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'] as $value => $label): ?>
                    <option value="<?php echo admin_e($value); ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Lọc</button>
            <a href="<?php echo ADMIN_URL; ?>withdraw_requests.php" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </form>
</div>

<div class="wb-table-card">
    <?php if ($requests): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Chủ phòng</th>
                    <th>Ngân hàng</th>
                    <th>Số tiền</th>
                    <th>Số dư còn lại</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                    <?php $requestStatus = (string)($request['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$request['id']; ?></td>
                        <td>
                            <div class="wb-title"><?php echo admin_e($request['name'] ?? 'N/A'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($request['email'] ?? ''); ?> · <?php echo admin_e($request['phone'] ?? ''); ?></div>
                        </td>
                        <td>
                            <div><?php echo admin_e($request['bank_name'] ?: 'Chưa cập nhật'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($request['bank_account_no'] ?: 'Chưa có STK'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($request['bank_account_name'] ?: 'Chưa có tên TK'); ?></div>
                        </td>
                        <td class="wb-price"><?php echo admin_money($request['amount'] ?? 0); ?></td>
                        <td><?php echo admin_money($request['current_balance'] ?? 0); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($requestStatus); ?>"><?php echo admin_status_label($requestStatus); ?></span></td>
                        <td><?php echo !empty($request['created_at']) ? date('d/m/Y H:i', strtotime((string)$request['created_at'])) : ''; ?></td>
                        <td class="text-end">
                            <?php if ($requestStatus === 'pending'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Đã chuyển khoản cho owner và duyệt yêu cầu này?');">
                                    <?php echo Csrf::field('admin_withdraw_action'); ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?php echo (int)$request['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i> Duyệt</button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Từ chối yêu cầu và hoàn tiền về ví owner?');">
                                    <?php echo Csrf::field('admin_withdraw_action'); ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?php echo (int)$request['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-times"></i> Từ chối</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có yêu cầu rút tiền phù hợp.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
