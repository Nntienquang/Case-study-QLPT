<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ' . ADMIN_URL . 'activity_logs.php');
    exit;
}

$id = (int)$_GET['id'];
$log = $db->getRow(
    "SELECT l.*, u.id AS admin_id, u.name AS admin_name, u.email AS admin_email, u.phone AS admin_phone
     FROM activity_logs l
     LEFT JOIN users u ON l.admin_id = u.id
     WHERE l.id = {$id}"
);

if (!$log) {
    $_SESSION['error'] = 'Nhật ký không tồn tại';
    header('Location: ' . ADMIN_URL . 'activity_logs.php');
    exit;
}

$oldValue = !empty($log['old_values']) ? (json_decode($log['old_values'], true) ?: []) : [];
$newValue = !empty($log['new_values']) ? (json_decode($log['new_values'], true) ?: []) : [];

admin_layout_start('Chi tiết nhật ký', 'Xem thông tin truy vết của một thao tác quản trị.', 'activity_logs');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>activity_logs.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay lại</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Nhật ký</div><div class="wb-card-value">#<?php echo (int)$log['id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Hành động</div><div class="mt-2"><span class="wb-pill warning"><?php echo admin_e(str_replace('_', ' ', (string)$log['action'])); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Thực thể</div><div class="wb-card-value fs-4"><?php echo admin_e(ucfirst((string)$log['entity_type'])); ?> #<?php echo (int)$log['entity_id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Thời gian</div><div class="wb-card-value fs-5"><?php echo !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$log['created_at'])) : ''; ?></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Mô tả</div>
            <div><?php echo nl2br(admin_e($log['description'] ?? '')); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Admin thực hiện</div>
            <div><?php echo admin_e($log['admin_name'] ?? 'Ẩn danh'); ?></div>
            <div class="wb-muted"><?php echo admin_e($log['admin_email'] ?? ''); ?> · <?php echo admin_e($log['admin_phone'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Nguồn truy cập</div>
            <div class="wb-muted">IP: <?php echo admin_e($log['ip_address'] ?? '-'); ?> · User agent: <?php echo admin_e($log['user_agent'] ?? '-'); ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="wb-card h-100">
            <div class="wb-title mb-2">Giá trị cũ</div>
            <pre class="mb-0 p-3 bg-light rounded"><?php echo admin_e(json_encode($oldValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
    <div class="col-md-6">
        <div class="wb-card h-100">
            <div class="wb-title mb-2">Giá trị mới</div>
            <pre class="mb-0 p-3 bg-light rounded"><?php echo admin_e(json_encode($newValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

