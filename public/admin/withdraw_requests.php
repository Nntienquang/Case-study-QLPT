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
        $_SESSION['error'] = 'Phien thao tac khong hop le, vui long thu lai.';
        withdraw_redirect();
    }

    $id = (int)($_POST['id'] ?? 0);
    $action = (string)$_POST['action'];
    $adminId = (int)($_SESSION['user_id'] ?? 0);
    $activityLog = new ActivityLog($db);

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
            throw new RuntimeException('Yeu cau rut tien khong ton tai.');
        }
        if (($request['status'] ?? '') !== 'pending') {
            throw new RuntimeException('Yeu cau nay da duoc xu ly.');
        }

        $ownerId = (int)$request['user_id'];
        $amount = (int)$request['amount'];
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';

        $stmt = $conn->prepare('UPDATE withdraw_requests SET status = ? WHERE id = ? AND status = "pending"');
        $stmt->bind_param('si', $newStatus, $id);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            throw new RuntimeException('Khong the cap nhat trang thai yeu cau.');
        }
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

        $title = $action === 'approve' ? 'Yeu cau rut tien da duoc duyet' : 'Yeu cau rut tien bi tu choi';
        $body = $action === 'approve'
            ? 'Admin da duyet yeu cau rut ' . number_format($amount) . ' VND. Vui long kiem tra tai khoan ngan hang.'
            : 'Admin da tu choi yeu cau rut ' . number_format($amount) . ' VND. So tien da duoc hoan lai vi.';
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?, 'withdraw', ?, ?, 'owner/revenue.php', NOW())");
        $stmt->bind_param('iss', $ownerId, $title, $body);
        $stmt->execute();
        $stmt->close();

        $activityLog->log(
            $adminId,
            $action === 'approve' ? 'approve_withdraw' : 'reject_withdraw',
            'withdraw_request',
            $id,
            ['old' => 'pending', 'new' => $newStatus],
            ($action === 'approve' ? 'Duyệt' : 'Từ chối') . " yêu cầu rút tiền #{$id} của owner #{$ownerId}"
        );

        $conn->commit();
        $_SESSION['success'] = $action === 'approve' ? 'Da duyet yeu cau rut tien.' : 'Da tu choi va hoan tien ve vi owner.';
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Khong the xu ly yeu cau: ' . $e->getMessage();
    }

    withdraw_redirect();
}

$status = (string)($_GET['status'] ?? '');
if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
    $status = '';
}
$where = $status !== '' ? 'WHERE wr.status = ?' : '';

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

$requestStmt = $conn->prepare("
    SELECT wr.*, u.name, u.email, u.phone, u.bank_name, u.bank_account_no, u.bank_account_name, COALESCE(w.balance, 0) AS current_balance
    FROM withdraw_requests wr
    LEFT JOIN users u ON u.id = wr.user_id
    LEFT JOIN wallets w ON w.user_id = wr.user_id
    {$where}
    ORDER BY wr.created_at DESC, wr.id DESC
");
if ($status !== '') {
    $requestStmt->bind_param('s', $status);
}
$requestStmt->execute();
$requests = $requestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$requestStmt->close();

admin_layout_start('Duyet rut tien', 'Kiem tra thong tin ngan hang va xu ly yeu cau rut tien cua chu phong.', 'withdraw_requests');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Cho xu ly</div><div class="wb-card-value"><?php echo (int)$stats['pending']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tong tien cho rut</div><div class="wb-card-value fs-4"><?php echo admin_money($stats['pending_amount']); ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Da duyet</div><div class="wb-card-value"><?php echo (int)$stats['approved']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Tu choi</div><div class="wb-card-value"><?php echo (int)$stats['rejected']; ?></div></div>
</div>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trang thai</label>
            <select name="status" class="form-select">
                <option value="">Tat ca</option>
                <?php foreach (['pending' => 'Cho xu ly', 'approved' => 'Da duyet', 'rejected' => 'Tu choi'] as $value => $label): ?>
                    <option value="<?php echo admin_e($value); ?>" <?php echo $status === $value ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Loc</button>
            <a href="<?php echo ADMIN_URL; ?>withdraw_requests.php" class="btn btn-outline-secondary">Xoa loc</a>
        </div>
    </form>
</div>

<div class="wb-table-card">
    <?php if ($requests): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Chu phong</th>
                    <th>Ngan hang</th>
                    <th>So tien</th>
                    <th>So du con lai</th>
                    <th>Trang thai</th>
                    <th>Ngay tao</th>
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
                            <div class="wb-muted"><?php echo admin_e($request['email'] ?? ''); ?> - <?php echo admin_e($request['phone'] ?? ''); ?></div>
                        </td>
                        <td>
                            <div><?php echo admin_e($request['bank_name'] ?: 'Chua cap nhat'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($request['bank_account_no'] ?: 'Chua co STK'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($request['bank_account_name'] ?: 'Chua co ten TK'); ?></div>
                        </td>
                        <td class="wb-price"><?php echo admin_money($request['amount'] ?? 0); ?></td>
                        <td><?php echo admin_money($request['current_balance'] ?? 0); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($requestStatus); ?>"><?php echo admin_status_label($requestStatus); ?></span></td>
                        <td><?php echo !empty($request['created_at']) ? date('d/m/Y H:i', strtotime((string)$request['created_at'])) : ''; ?></td>
                        <td class="text-end">
                            <?php if ($requestStatus === 'pending'): ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Da chuyen khoan cho owner va duyet yeu cau nay?');">
                                    <?php echo Csrf::field('admin_withdraw_action'); ?>
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?php echo (int)$request['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success"><i class="fa fa-check"></i> Duyet</button>
                                </form>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Tu choi yeu cau va hoan tien ve vi owner?');">
                                    <?php echo Csrf::field('admin_withdraw_action'); ?>
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?php echo (int)$request['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-times"></i> Tu choi</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Khong co yeu cau rut tien phu hop.</div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
