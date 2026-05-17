<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ' . ADMIN_URL . 'reports.php');
    exit;
}

$id = (int)$_GET['id'];
$activityLog = new ActivityLog($db);
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!Csrf::validateRequest('admin_report_action')) {
        $_SESSION['error'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        header('Location: report_detail.php?id=' . $id);
        exit;
    }

    $status = $_POST['status'] ?? '';
    $adminNote = trim((string)($_POST['admin_note'] ?? ''));
    $validStatuses = ['investigating', 'resolved', 'rejected', 'closed'];
    if (in_array($status, $validStatuses, true)) {
        if (in_array($status, ['resolved', 'rejected', 'closed'], true) && $adminNote === '') {
            $_SESSION['error'] = 'Vui lòng nhập ghi chú xử lý cho trạng thái này.';
            header('Location: report_detail.php?id=' . $id);
            exit;
        }

        $existing = $db->getRow("SELECT * FROM reports WHERE id = {$id}");
        $statusEsc = $conn->real_escape_string($status);
        $noteEsc = $conn->real_escape_string($adminNote);
        $adminId = (int)$_SESSION['user_id'];
        if ($existing && $db->query("UPDATE reports SET status = '{$statusEsc}', admin_note = '{$noteEsc}', handled_by = {$adminId}, handled_at = NOW() WHERE id = {$id}")) {
            $activityLog->log($adminId, 'update_report_status', 'report', $id, ['old' => $existing['status'], 'new' => $status], "Cập nhật báo cáo từ {$existing['status']} thành {$status}. Ghi chú: {$adminNote}");
            $_SESSION['success'] = 'Cập nhật trạng thái báo cáo thành công';
            header('Location: reports.php');
            exit;
        }
    }
}

$report = $db->getRow(
    "SELECT r.*,
            u_reporter.name AS reporter_name, u_reporter.email AS reporter_email, u_reporter.phone AS reporter_phone, u_reporter.created_at AS reporter_joined,
            u_reported.name AS reported_name, u_reported.email AS reported_email, u_reported.phone AS reported_phone, u_reported.created_at AS reported_joined,
            m.id AS motel_id, m.title AS motel_title, m.description AS motel_desc, m.price AS motel_price,
            u_handler.name AS handler_name, u_handler.email AS handler_email
     FROM reports r
     LEFT JOIN users u_reporter ON r.reporter_id = u_reporter.id
     LEFT JOIN users u_reported ON r.reported_user_id = u_reported.id
     LEFT JOIN motels m ON r.motel_id = m.id
     LEFT JOIN users u_handler ON r.handled_by = u_handler.id
     WHERE r.id = {$id}"
);

if (!$report) {
    $_SESSION['error'] = 'Báo cáo không tồn tại';
    header('Location: ' . ADMIN_URL . 'reports.php');
    exit;
}

$reportTypeLabels = [
    'spam' => 'Spam',
    'inappropriate' => 'Nội dung không phù hợp',
    'fraud' => 'Gian lận',
    'unsafe' => 'Không an toàn',
    'false_info' => 'Thông tin sai',
    'other' => 'Khác',
];
$status = (string)($report['status'] ?? 'pending');

admin_layout_start('Chi tiết báo cáo', 'Xem nội dung báo cáo, đối tượng liên quan và cập nhật kết quả xử lý.', 'reports');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>reports.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Báo cáo</div><div class="wb-card-value">#<?php echo (int)$report['id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Loại</div><div class="mt-2"><span class="wb-pill warning"><?php echo admin_e($reportTypeLabels[$report['report_type']] ?? $report['report_type'] ?? 'Khác'); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Trạng thái</div><div class="mt-2"><span class="wb-pill <?php echo admin_pill_class($status); ?>"><?php echo admin_status_label($status); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Ngày tạo</div><div class="wb-card-value fs-5"><?php echo !empty($report['created_at']) ? date('d/m/Y H:i', strtotime((string)$report['created_at'])) : ''; ?></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title"><?php echo admin_e($report['reason'] ?? 'N/A'); ?></div>
            <div><?php echo nl2br(admin_e($report['reason'] ?? '')); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Người báo cáo</div>
            <div><?php echo admin_e($report['reporter_name'] ?? 'Ẩn danh'); ?></div>
            <div class="wb-muted"><?php echo admin_e($report['reporter_email'] ?? ''); ?> · <?php echo admin_e($report['reporter_phone'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Đối tượng bị báo cáo</div>
            <div><?php echo admin_e($report['reported_name'] ?? $report['motel_title'] ?? '-'); ?></div>
            <?php if (!empty($report['motel_title'])): ?>
                <div class="wb-muted">Phòng: <?php echo admin_e($report['motel_title']); ?> · <?php echo admin_money($report['motel_price'] ?? 0); ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($report['admin_note'])): ?>
        <div class="wb-list-row">
            <div><div class="wb-title">Ghi chú xử lý</div><div><?php echo nl2br(admin_e($report['admin_note'])); ?></div></div>
        </div>
    <?php endif; ?>
</div>

<div class="wb-card">
    <form method="POST" class="row g-3">
        <?php echo Csrf::field('admin_report_action'); ?>
        <input type="hidden" name="action" value="update_status">
        <div class="col-md-4">
            <label class="form-label fw-semibold">Trạng thái mới</label>
            <select name="status" class="form-select" required>
                <option value="">Chọn trạng thái</option>
                <?php foreach (['investigating', 'resolved', 'rejected', 'closed'] as $item): ?>
                    <option value="<?php echo $item; ?>" <?php echo $status === $item ? 'selected' : ''; ?>><?php echo admin_status_label($item); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8">
            <label class="form-label fw-semibold">Ghi chú admin</label>
            <textarea name="admin_note" class="form-control" rows="3"><?php echo admin_e($report['admin_note'] ?? ''); ?></textarea>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Lưu xử lý</button>
        </div>
    </form>
</div>

<?php admin_layout_end(); ?>

