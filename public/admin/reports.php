<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$activityLog = new ActivityLog($db);
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Csrf::validateRequest('admin_report_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: reports.php');
        exit;
    }

    if ($_POST['action'] === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $adminNote = trim((string)($_POST['admin_note'] ?? ''));
        $adminId = (int)$_SESSION['user_id'];
        $validStatuses = ['investigating', 'resolved', 'rejected', 'closed'];

        if (in_array($status, ['resolved', 'rejected', 'closed'], true) && $adminNote === '') {
            $_SESSION['error'] = 'Vui lòng nhập ghi chú xử lý cho trạng thái này.';
            header('Location: reports.php');
            exit;
        }

        if ($id > 0 && in_array($status, $validStatuses, true)) {
            $reportStmt = $conn->prepare('SELECT * FROM reports WHERE id = ? LIMIT 1');
            $reportStmt->bind_param('i', $id);
            $reportStmt->execute();
            $report = $reportStmt->get_result()->fetch_assoc();
            $reportStmt->close();

            $updateStmt = $conn->prepare('UPDATE reports SET status = ?, admin_note = ?, handled_by = ?, handled_at = NOW() WHERE id = ?');
            if ($updateStmt) {
                $updateStmt->bind_param('ssii', $status, $adminNote, $adminId, $id);
            }
            if ($report && $updateStmt && $updateStmt->execute()) {
                $activityLog->log($adminId, 'update_report_status', 'report', $id, ['old' => $report['status'], 'new' => $status], "Cập nhật báo cáo từ {$report['status']} thành {$status}");
                $_SESSION['success'] = 'Cập nhật trạng thái báo cáo thành công';
            }
            if ($updateStmt) {
                $updateStmt->close();
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $reportStmt = $conn->prepare('SELECT * FROM reports WHERE id = ? LIMIT 1');
            $reportStmt->bind_param('i', $id);
            $reportStmt->execute();
            $report = $reportStmt->get_result()->fetch_assoc();
            $reportStmt->close();

            $deleteStmt = $conn->prepare('DELETE FROM reports WHERE id = ?');
            if ($deleteStmt) {
                $deleteStmt->bind_param('i', $id);
            }
            if ($report && $deleteStmt && $deleteStmt->execute()) {
                $activityLog->log((int)$_SESSION['user_id'], 'delete_report', 'report', $id, [], "Xóa báo cáo: {$report['reason']}");
                $_SESSION['success'] = 'Xóa báo cáo thành công';
            }
            if ($deleteStmt) {
                $deleteStmt->close();
            }
        }
    }

    header('Location: reports.php');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$status = Report::filterStatus((string)($_GET['status'] ?? ''));
$reportTypes = [
    'spam' => 'Spam',
    'inappropriate' => 'Nội dung không phù hợp',
    'fraud' => 'Gian lận',
    'unsafe' => 'Không an toàn',
    'false_info' => 'Thông tin sai',
    'other' => 'Khác',
];
$type = (string)($_GET['type'] ?? '');
$type = array_key_exists($type, $reportTypes) ? $type : '';
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$where = '1=1';
if ($status !== '') {
    $where .= ' AND r.status = ?';
}
if ($type !== '') {
    $where .= ' AND r.report_type = ?';
}

$countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM reports r WHERE ' . $where);
if ($status !== '' && $type !== '') {
    $countStmt->bind_param('ss', $status, $type);
} elseif ($status !== '') {
    $countStmt->bind_param('s', $status);
} elseif ($type !== '') {
    $countStmt->bind_param('s', $type);
}
$countStmt->execute();
$total = (int)(($countStmt->get_result()->fetch_assoc() ?: [])['total'] ?? 0);
$countStmt->close();
$totalPages = (int)ceil($total / $limit);
$reportListStmt = $conn->prepare(
    "SELECT r.*,
            u_reporter.name AS reporter_name, u_reporter.email AS reporter_email,
            u_reported.name AS reported_name, u_reported.email AS reported_email,
            m.title AS motel_title,
            u_handler.name AS handler_name
     FROM reports r
     LEFT JOIN users u_reporter ON r.reporter_id = u_reporter.id
     LEFT JOIN users u_reported ON r.reported_user_id = u_reported.id
     LEFT JOIN motels m ON r.motel_id = m.id
     LEFT JOIN users u_handler ON r.handled_by = u_handler.id
     WHERE {$where}
     ORDER BY r.created_at DESC
     LIMIT {$offset}, {$limit}"
);
if ($status !== '' && $type !== '') {
    $reportListStmt->bind_param('ss', $status, $type);
} elseif ($status !== '') {
    $reportListStmt->bind_param('s', $status);
} elseif ($type !== '') {
    $reportListStmt->bind_param('s', $type);
}
$reportListStmt->execute();
$reports = $reportListStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$reportListStmt->close();

$stats = [
    'total' => $db->count('reports'),
    'pending' => $db->count('reports', "status = 'pending'"),
    'investigating' => $db->count('reports', "status = 'investigating'"),
    'resolved' => $db->count('reports', "status = 'resolved'"),
];

admin_layout_start('Báo cáo vi phạm', 'Tiếp nhận, xác minh và xử lý báo cáo từ người dùng về phòng trọ hoặc tài khoản.', 'reports');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><i class="fa fa-flag wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['total']; ?></div><div class="wb-card-label">Tổng báo cáo</div></div>
    <div class="wb-card"><i class="fa fa-clock-o wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['pending']; ?></div><div class="wb-card-label">Chờ xử lý</div></div>
    <div class="wb-card"><i class="fa fa-search wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['investigating']; ?></div><div class="wb-card-label">Đang xác minh</div></div>
    <div class="wb-card"><i class="fa fa-check-circle wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['resolved']; ?></div><div class="wb-card-label">Đã xử lý</div></div>
</div>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái</label>
            <select name="status" class="form-select">
                <option value="">Tất cả</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                <option value="investigating" <?php echo $status === 'investigating' ? 'selected' : ''; ?>>Đang xác minh</option>
                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Đã đóng</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Loại báo cáo</label>
            <select name="type" class="form-select">
                <option value="">Tất cả</option>
                <?php foreach ($reportTypes as $key => $label): ?>
                    <option value="<?php echo admin_e($key); ?>" <?php echo $type === $key ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-filter"></i> Lọc báo cáo</button>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sách báo cáo</h2>
    <span class="wb-pill"><?php echo $total; ?> báo cáo</span>
</div>

<div class="wb-table-card">
    <?php if ($reports): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Loại</th>
                    <th>Đối tượng</th>
                    <th>Người báo cáo</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <?php $reportStatus = (string)($report['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$report['id']; ?></td>
                        <td>
                            <div class="wb-title"><?php echo admin_e(substr((string)($report['reason'] ?? 'N/A'), 0, 55)); ?></div>
                            <?php if (!empty($report['motel_title'])): ?>
                                <div class="wb-muted">Phòng: <?php echo admin_e($report['motel_title']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="wb-pill warning"><?php echo admin_e($reportTypes[$report['report_type']] ?? $report['report_type'] ?? 'Khác'); ?></span></td>
                        <td><?php echo admin_e($report['reported_name'] ?? $report['motel_title'] ?? '-'); ?></td>
                        <td>
                            <div><?php echo admin_e($report['reporter_name'] ?? 'Ẩn danh'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($report['reporter_email'] ?? ''); ?></div>
                        </td>
                        <td><span class="wb-pill <?php echo admin_pill_class($reportStatus); ?>"><?php echo admin_status_label($reportStatus); ?></span></td>
                        <td><?php echo !empty($report['created_at']) ? date('d/m/Y H:i', strtotime((string)$report['created_at'])) : ''; ?></td>
                        <td class="text-end">
                            <a href="report_detail.php?id=<?php echo (int)$report['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i> Xem</a>
                            <?php if (in_array($reportStatus, ['pending', 'investigating'], true)): ?>
                                <button type="button" class="btn btn-sm btn-warning" onclick="openStatusModal(<?php echo (int)$report['id']; ?>)"><i class="fa fa-edit"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có báo cáo phù hợp bộ lọc.</div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status !== '' ? '&status=' . urlencode($status) : ''; ?><?php echo $type !== '' ? '&type=' . urlencode($type) : ''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <?php echo Csrf::field('admin_report_action'); ?>
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" id="reportId" name="id">
                    <label class="form-label">Trạng thái mới</label>
                    <select name="status" class="form-select mb-3" required>
                        <option value="">Chọn trạng thái</option>
                        <option value="investigating">Đang xác minh</option>
                        <option value="resolved">Đã xử lý</option>
                        <option value="rejected">Từ chối</option>
                        <option value="closed">Đã đóng</option>
                    </select>
                    <label class="form-label">Ghi chú admin</label>
                    <textarea name="admin_note" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$script = <<<'HTML'
<script>
const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
function openStatusModal(reportId) {
    document.getElementById('reportId').value = reportId;
    statusModal.show();
}
</script>
HTML;
admin_layout_end($script);
?>

