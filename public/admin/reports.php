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
        $_SESSION['error'] = 'PhiÃªn thao tÃ¡c khÃ´ng há»£p lá»‡, vui lÃ²ng thá»­ láº¡i.';
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
            $_SESSION['error'] = 'Vui lÃ²ng nháº­p ghi chÃº xá»­ lÃ½ cho tráº¡ng thÃ¡i nÃ y.';
            header('Location: reports.php');
            exit;
        }

        if ($id > 0 && in_array($status, $validStatuses, true)) {
            $report = $db->getRow("SELECT * FROM reports WHERE id = {$id}");
            $statusEsc = $conn->real_escape_string($status);
            $noteEsc = $conn->real_escape_string($adminNote);
            if ($report && $db->query("UPDATE reports SET status = '{$statusEsc}', admin_note = '{$noteEsc}', handled_by = {$adminId}, handled_at = NOW() WHERE id = {$id}")) {
                $activityLog->log($adminId, 'update_report_status', 'report', $id, ['old' => $report['status'], 'new' => $status], "Cáº­p nháº­t bÃ¡o cÃ¡o tá»« {$report['status']} thÃ nh {$status}");
                $_SESSION['success'] = 'Cáº­p nháº­t tráº¡ng thÃ¡i bÃ¡o cÃ¡o thÃ nh cÃ´ng';
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $report = $db->getRow("SELECT * FROM reports WHERE id = {$id}");
            if ($report && $db->query("DELETE FROM reports WHERE id = {$id}")) {
                $activityLog->log((int)$_SESSION['user_id'], 'delete_report', 'report', $id, [], "XÃ³a bÃ¡o cÃ¡o: {$report['reason']}");
                $_SESSION['success'] = 'XÃ³a bÃ¡o cÃ¡o thÃ nh cÃ´ng';
            }
        }
    }

    header('Location: reports.php');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$where = '1=1';
if ($status !== '') {
    $statusEsc = $conn->real_escape_string($status);
    $where .= " AND r.status = '{$statusEsc}'";
}
if ($type !== '') {
    $typeEsc = $conn->real_escape_string($type);
    $where .= " AND r.report_type = '{$typeEsc}'";
}

$total = (int)($db->getRow('SELECT COUNT(*) AS total FROM reports r WHERE ' . $where)['total'] ?? 0);
$totalPages = (int)ceil($total / $limit);
$reports = $db->getRows(
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

$stats = [
    'total' => $db->count('reports'),
    'pending' => $db->count('reports', "status = 'pending'"),
    'investigating' => $db->count('reports', "status = 'investigating'"),
    'resolved' => $db->count('reports', "status = 'resolved'"),
];

$reportTypes = [
    'spam' => 'Spam',
    'inappropriate' => 'Ná»™i dung khÃ´ng phÃ¹ há»£p',
    'fraud' => 'Gian láº­n',
    'unsafe' => 'KhÃ´ng an toÃ n',
    'false_info' => 'ThÃ´ng tin sai',
    'other' => 'KhÃ¡c',
];

admin_layout_start('BÃ¡o cÃ¡o vi pháº¡m', 'Tiáº¿p nháº­n, xÃ¡c minh vÃ  xá»­ lÃ½ bÃ¡o cÃ¡o tá»« ngÆ°á»i dÃ¹ng vá» phÃ²ng trá» hoáº·c tÃ i khoáº£n.', 'reports');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><i class="fa fa-flag wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['total']; ?></div><div class="wb-card-label">Tá»•ng bÃ¡o cÃ¡o</div></div>
    <div class="wb-card"><i class="fa fa-clock-o wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['pending']; ?></div><div class="wb-card-label">Chá» xá»­ lÃ½</div></div>
    <div class="wb-card"><i class="fa fa-search wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['investigating']; ?></div><div class="wb-card-label">Äang xÃ¡c minh</div></div>
    <div class="wb-card"><i class="fa fa-check-circle wb-card-icon"></i><div class="wb-card-value"><?php echo (int)$stats['resolved']; ?></div><div class="wb-card-label">ÄÃ£ xá»­ lÃ½</div></div>
</div>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Tráº¡ng thÃ¡i</label>
            <select name="status" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chá» xá»­ lÃ½</option>
                <option value="investigating" <?php echo $status === 'investigating' ? 'selected' : ''; ?>>Äang xÃ¡c minh</option>
                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>ÄÃ£ xá»­ lÃ½</option>
                <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Tá»« chá»‘i</option>
                <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>ÄÃ£ Ä‘Ã³ng</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Loáº¡i bÃ¡o cÃ¡o</label>
            <select name="type" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <?php foreach ($reportTypes as $key => $label): ?>
                    <option value="<?php echo admin_e($key); ?>" <?php echo $type === $key ? 'selected' : ''; ?>><?php echo admin_e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-filter"></i> Lá»c bÃ¡o cÃ¡o</button>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>Danh sÃ¡ch bÃ¡o cÃ¡o</h2>
    <span class="wb-pill"><?php echo $total; ?> bÃ¡o cÃ¡o</span>
</div>

<div class="wb-table-card">
    <?php if ($reports): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>TiÃªu Ä‘á»</th>
                    <th>Loáº¡i</th>
                    <th>Äá»‘i tÆ°á»£ng</th>
                    <th>NgÆ°á»i bÃ¡o cÃ¡o</th>
                    <th>Tráº¡ng thÃ¡i</th>
                    <th>NgÃ y táº¡o</th>
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
                                <div class="wb-muted">PhÃ²ng: <?php echo admin_e($report['motel_title']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="wb-pill warning"><?php echo admin_e($reportTypes[$report['report_type']] ?? $report['report_type'] ?? 'KhÃ¡c'); ?></span></td>
                        <td><?php echo admin_e($report['reported_name'] ?? $report['motel_title'] ?? '-'); ?></td>
                        <td>
                            <div><?php echo admin_e($report['reporter_name'] ?? 'áº¨n danh'); ?></div>
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
        <div class="wb-empty">KhÃ´ng cÃ³ bÃ¡o cÃ¡o phÃ¹ há»£p bá»™ lá»c.</div>
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
                    <h5 class="modal-title">Cáº­p nháº­t bÃ¡o cÃ¡o</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" id="reportId" name="id">
                    <label class="form-label">Tráº¡ng thÃ¡i má»›i</label>
                    <select name="status" class="form-select mb-3" required>
                        <option value="">Chá»n tráº¡ng thÃ¡i</option>
                        <option value="investigating">Äang xÃ¡c minh</option>
                        <option value="resolved">ÄÃ£ xá»­ lÃ½</option>
                        <option value="rejected">Tá»« chá»‘i</option>
                        <option value="closed">ÄÃ£ Ä‘Ã³ng</option>
                    </select>
                    <label class="form-label">Ghi chÃº admin</label>
                    <textarea name="admin_note" class="form-control" rows="4"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Há»§y</button>
                    <button type="submit" class="btn btn-primary">LÆ°u thay Ä‘á»•i</button>
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

