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
    $_SESSION['error'] = 'Nháº­t kÃ½ khÃ´ng tá»“n táº¡i';
    header('Location: ' . ADMIN_URL . 'activity_logs.php');
    exit;
}

$oldValue = !empty($log['old_values']) ? (json_decode($log['old_values'], true) ?: []) : [];
$newValue = !empty($log['new_values']) ? (json_decode($log['new_values'], true) ?: []) : [];

admin_layout_start('Chi tiáº¿t nháº­t kÃ½', 'Xem thÃ´ng tin truy váº¿t cá»§a má»™t thao tÃ¡c quáº£n trá»‹.', 'activity_logs');
admin_flash_messages();
?>

<a href="<?php echo ADMIN_URL; ?>activity_logs.php" class="btn btn-outline-secondary mb-3"><i class="fa fa-arrow-left"></i> Quay láº¡i</a>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><div class="wb-card-label">Nháº­t kÃ½</div><div class="wb-card-value">#<?php echo (int)$log['id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">HÃ nh Ä‘á»™ng</div><div class="mt-2"><span class="wb-pill warning"><?php echo admin_e(str_replace('_', ' ', (string)$log['action'])); ?></span></div></div>
    <div class="wb-card"><div class="wb-card-label">Thá»±c thá»ƒ</div><div class="wb-card-value fs-4"><?php echo admin_e(ucfirst((string)$log['entity_type'])); ?> #<?php echo (int)$log['entity_id']; ?></div></div>
    <div class="wb-card"><div class="wb-card-label">Thá»i gian</div><div class="wb-card-value fs-5"><?php echo !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$log['created_at'])) : ''; ?></div></div>
</div>

<div class="wb-list-card mb-3">
    <div class="wb-list-row">
        <div>
            <div class="wb-title">MÃ´ táº£</div>
            <div><?php echo nl2br(admin_e($log['description'] ?? '')); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Admin thá»±c hiá»‡n</div>
            <div><?php echo admin_e($log['admin_name'] ?? 'áº¨n danh'); ?></div>
            <div class="wb-muted"><?php echo admin_e($log['admin_email'] ?? ''); ?> Â· <?php echo admin_e($log['admin_phone'] ?? ''); ?></div>
        </div>
    </div>
    <div class="wb-list-row">
        <div>
            <div class="wb-title">Nguá»“n truy cáº­p</div>
            <div class="wb-muted">IP: <?php echo admin_e($log['ip_address'] ?? '-'); ?> Â· User agent: <?php echo admin_e($log['user_agent'] ?? '-'); ?></div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="wb-card h-100">
            <div class="wb-title mb-2">GiÃ¡ trá»‹ cÅ©</div>
            <pre class="mb-0 p-3 bg-light rounded"><?php echo admin_e(json_encode($oldValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
    <div class="col-md-6">
        <div class="wb-card h-100">
            <div class="wb-title mb-2">GiÃ¡ trá»‹ má»›i</div>
            <pre class="mb-0 p-3 bg-light rounded"><?php echo admin_e(json_encode($newValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
        </div>
    </div>
</div>

<?php admin_layout_end(); ?>

