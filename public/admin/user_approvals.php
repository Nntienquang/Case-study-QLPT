<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$activityLog = new ActivityLog($db);
$emailNotification = new EmailNotification($db);
$userApprovalController = new UserApprovalController($db, $activityLog, $emailNotification);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
            if ($user && $user['role'] === 'owner') {
                $adminId = (int)$_SESSION['user_id'];
                if ($db->query("UPDATE users SET status = 'approved', owner_verification_status = 'approved', approved_by = {$adminId}, approved_at = NOW(), verified_at = NOW(), verification_reviewed_by = {$adminId}, verification_reviewed_at = NOW(), verification_rejection_reason = NULL WHERE id = {$id}")) {
                    $activityLog->log($adminId, 'approve_user', 'user', $id, [], "Duyệt tài khoản owner: {$user['name']} ({$user['email']})");
                    $_SESSION['success'] = "Đã duyệt tài khoản {$user['name']}";
                }
            }
        }
    }

    if ($_POST['action'] === 'reject') {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($id > 0 && $reason !== '') {
            $user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
            if ($user && $user['role'] === 'owner') {
                $adminId = (int)$_SESSION['user_id'];
                $reasonEsc = $db->getConnection()->real_escape_string($reason);
                if ($db->query("UPDATE users SET status = 'approved', owner_verification_status = 'rejected', approved_by = {$adminId}, approved_at = NOW(), verification_reviewed_by = {$adminId}, verification_reviewed_at = NOW(), verification_rejection_reason = '{$reasonEsc}', rejection_reason = '{$reasonEsc}' WHERE id = {$id}")) {
                    $activityLog->log($adminId, 'reject_user', 'user', $id, [], "Từ chối tài khoản owner: {$user['name']}. Lý do: {$reason}");
                    $_SESSION['success'] = "Đã từ chối tài khoản {$user['name']}";
                }
            }
        }
    }

    header('Location: user_approvals.php');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$tab = $_GET['tab'] ?? 'pending';
$search = trim((string)($_GET['search'] ?? ''));
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;
$conn = $db->getConnection();

$where = "role = 'owner'";
if (in_array($tab, ['pending', 'approved', 'rejected'], true)) {
    $tabEsc = $tab === 'pending' ? 'submitted' : $conn->real_escape_string($tab);
    $where .= " AND owner_verification_status = '{$tabEsc}'";
}
if ($search !== '') {
    $searchEsc = $conn->real_escape_string($search);
    $where .= " AND (name LIKE '%{$searchEsc}%' OR email LIKE '%{$searchEsc}%' OR phone LIKE '%{$searchEsc}%')";
}

$total = (int)($db->getRow("SELECT COUNT(*) AS total FROM users WHERE {$where}")['total'] ?? 0);
$totalPages = (int)ceil($total / $limit);
$users = $db->getRows("SELECT * FROM users WHERE {$where} ORDER BY created_at DESC LIMIT {$offset}, {$limit}");

$stats = [
    'total' => $db->count('users', "role = 'owner'"),
    'pending' => $db->count('users', "role = 'owner' AND owner_verification_status = 'submitted'"),
    'approved' => $db->count('users', "role = 'owner' AND owner_verification_status = 'approved'"),
    'rejected' => $db->count('users', "role = 'owner' AND owner_verification_status = 'rejected'"),
];

$tabUrl = static fn(string $name): string => '?tab=' . urlencode($name) . ($search !== '' ? '&search=' . urlencode($search) : '');

admin_layout_start('Duyệt tài khoản owner', 'Xác minh tài khoản chủ phòng trước khi cho phép đăng và quản lý phòng trọ.', 'user_approvals');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><i class="fa fa-users wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['total']; ?></div><div class="wb-card-label">Tổng owner</div></div>
    <div class="wb-card"><i class="fa fa-hourglass-half wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['pending']; ?></div><div class="wb-card-label">Chờ duyệt</div></div>
    <div class="wb-card"><i class="fa fa-check-circle wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['approved']; ?></div><div class="wb-card-label">Đã duyệt</div></div>
    <div class="wb-card"><i class="fa fa-times-circle wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['rejected']; ?></div><div class="wb-card-label">Từ chối</div></div>
</div>

<div class="wb-card mb-3">
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach (['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'all' => 'Tất cả'] as $key => $label): ?>
            <a class="btn btn-sm <?php echo $tab === $key ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo $tabUrl($key); ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>
    <form method="GET" class="row g-3 align-items-end">
        <input type="hidden" name="tab" value="<?php echo admin_e($tab); ?>">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Tìm kiếm</label>
            <input type="text" name="search" class="form-control" value="<?php echo admin_e($search); ?>" placeholder="Tên, email hoặc số điện thoại">
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i> Tìm kiếm</button>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách owner</h2>
    <span class="wb-pill"><?php echo $total; ?> tài khoản</span>
</div>

<div class="wb-table-card">
    <?php if ($users): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Điện thoại</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $status = (string)($user['owner_verification_status'] ?? 'pending_verification'); ?>
                    <tr>
                        <td>#<?php echo (int)$user['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($user['name'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($user['email'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($user['phone'] ?? '-'); ?></td>
                        <td><?php echo !empty($user['created_at']) ? date('d/m/Y', strtotime((string)$user['created_at'])) : ''; ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></td>
                        <td class="text-end">
                            <a href="user_detail.php?id=<?php echo (int)$user['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if ($status === 'submitted'): ?>
                                <button type="button" class="btn btn-sm btn-success" onclick="approveUser(<?php echo (int)$user['id']; ?>)"><i class="fa fa-check"></i> Duyệt</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick='openRejectModal(<?php echo (int)$user['id']; ?>, <?php echo json_encode((string)($user['name'] ?? 'Owner')); ?>)'><i class="fa fa-times"></i> Từ chối</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có tài khoản owner phù hợp.</div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&tab=<?php echo urlencode($tab); ?><?php echo $search !== '' ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<form id="approveForm" method="POST" class="d-none">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="id" id="approveId">
</form>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Từ chối tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="id" id="rejectId">
                    <p>Bạn sắp từ chối tài khoản <strong id="rejectUserName"></strong>.</p>
                    <label class="form-label">Lý do từ chối</label>
                    <textarea name="rejection_reason" id="rejectionReason" class="form-control" rows="4" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger">Từ chối</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$script = <<<'HTML'
<script>
const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'));
function approveUser(userId) {
    if (confirm('Duyệt tài khoản owner này?')) {
        document.getElementById('approveId').value = userId;
        document.getElementById('approveForm').submit();
    }
}
function openRejectModal(userId, userName) {
    document.getElementById('rejectId').value = userId;
    document.getElementById('rejectUserName').textContent = userName;
    document.getElementById('rejectionReason').value = '';
    rejectModal.show();
}
</script>
HTML;
admin_layout_end($script);
?>

