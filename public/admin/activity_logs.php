<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$adminId = (int)($_GET['admin_id'] ?? 0);
$entityType = $_GET['entity_type'] ?? '';
$actionFilter = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;
$conn = $db->getConnection();

$where = '1=1';
if ($adminId > 0) {
    $where .= " AND l.admin_id = {$adminId}";
}
if ($entityType !== '') {
    $entityEsc = $conn->real_escape_string($entityType);
    $where .= " AND l.entity_type = '{$entityEsc}'";
}
if ($actionFilter !== '') {
    $actionEsc = $conn->real_escape_string($actionFilter);
    $where .= " AND l.action = '{$actionEsc}'";
}
if ($dateFrom !== '') {
    $fromEsc = $conn->real_escape_string($dateFrom);
    $where .= " AND DATE(l.created_at) >= '{$fromEsc}'";
}
if ($dateTo !== '') {
    $toEsc = $conn->real_escape_string($dateTo);
    $where .= " AND DATE(l.created_at) <= '{$toEsc}'";
}

$total = (int)($db->getRow("SELECT COUNT(*) AS total FROM activity_logs l WHERE {$where}")['total'] ?? 0);
$totalPages = (int)ceil($total / $limit);
$logs = $db->getRows(
    "SELECT l.*, u.name AS admin_name, u.email AS admin_email
     FROM activity_logs l
     LEFT JOIN users u ON l.admin_id = u.id
     WHERE {$where}
     ORDER BY l.created_at DESC
     LIMIT {$offset}, {$limit}"
);

$stats = $db->getRow(
    "SELECT COUNT(*) AS total,
            COUNT(IF(DATE(created_at) = CURDATE(), 1, NULL)) AS today,
            COUNT(IF(YEARWEEK(created_at) = YEARWEEK(NOW()), 1, NULL)) AS week,
            COUNT(IF(MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()), 1, NULL)) AS month
     FROM activity_logs"
) ?: [];

$allAdmins = $db->getRows("SELECT DISTINCT u.id, u.name FROM activity_logs l LEFT JOIN users u ON l.admin_id = u.id WHERE u.id IS NOT NULL ORDER BY u.name");
$allEntityTypes = $db->getRows("SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type");
$allActions = $db->getRows("SELECT DISTINCT action FROM activity_logs ORDER BY action");

$querySuffix = ($adminId > 0 ? '&admin_id=' . $adminId : '')
    . ($entityType !== '' ? '&entity_type=' . urlencode($entityType) : '')
    . ($actionFilter !== '' ? '&action=' . urlencode($actionFilter) : '')
    . ($dateFrom !== '' ? '&date_from=' . urlencode($dateFrom) : '')
    . ($dateTo !== '' ? '&date_to=' . urlencode($dateTo) : '');

admin_layout_start('Nháº­t kÃ½ hoáº¡t Ä‘á»™ng', 'Theo dÃµi thao tÃ¡c quáº£n trá»‹ Ä‘á»ƒ truy váº¿t thay Ä‘á»•i vÃ  kiá»ƒm soÃ¡t rá»§i ro váº­n hÃ nh.', 'activity_logs');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><i class="fa fa-list wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($stats['total'] ?? 0); ?></div><div class="wb-card-label">Tá»•ng hoáº¡t Ä‘á»™ng</div></div>
    <div class="wb-card"><i class="fa fa-calendar-o wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($stats['today'] ?? 0); ?></div><div class="wb-card-label">HÃ´m nay</div></div>
    <div class="wb-card"><i class="fa fa-calendar-check-o wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($stats['week'] ?? 0); ?></div><div class="wb-card-label">Tuáº§n nÃ y</div></div>
    <div class="wb-card"><i class="fa fa-calendar wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($stats['month'] ?? 0); ?></div><div class="wb-card-label">ThÃ¡ng nÃ y</div></div>
</div>

<div class="wb-card mb-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label fw-semibold">Admin</label>
            <select name="admin_id" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <?php foreach ($allAdmins as $admin): ?>
                    <option value="<?php echo (int)$admin['id']; ?>" <?php echo $adminId === (int)$admin['id'] ? 'selected' : ''; ?>><?php echo admin_e($admin['name'] ?? 'Admin'); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Thá»±c thá»ƒ</label>
            <select name="entity_type" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <?php foreach ($allEntityTypes as $type): ?>
                    <option value="<?php echo admin_e($type['entity_type']); ?>" <?php echo $entityType === $type['entity_type'] ? 'selected' : ''; ?>><?php echo admin_e(ucfirst($type['entity_type'])); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">HÃ nh Ä‘á»™ng</label>
            <select name="action" class="form-select">
                <option value="">Táº¥t cáº£</option>
                <?php foreach ($allActions as $item): ?>
                    <option value="<?php echo admin_e($item['action']); ?>" <?php echo $actionFilter === $item['action'] ? 'selected' : ''; ?>><?php echo admin_e(str_replace('_', ' ', $item['action'])); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Tá»« ngÃ y</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo admin_e($dateFrom); ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold">Äáº¿n ngÃ y</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo admin_e($dateTo); ?>">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100"><i class="fa fa-search"></i> Lá»c</button>
        </div>
    </form>
</div>

<div class="wb-section-head">
    <h2>DÃ²ng hoáº¡t Ä‘á»™ng</h2>
    <span class="wb-pill"><?php echo $total; ?> báº£n ghi</span>
</div>

<div class="wb-table-card">
    <?php if ($logs): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thá»i gian</th>
                    <th>Admin</th>
                    <th>HÃ nh Ä‘á»™ng</th>
                    <th>Thá»±c thá»ƒ</th>
                    <th>MÃ´ táº£</th>
                    <th>IP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td>#<?php echo (int)$log['id']; ?></td>
                        <td><?php echo !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime((string)$log['created_at'])) : ''; ?></td>
                        <td><?php echo admin_e($log['admin_name'] ?? 'áº¨n danh'); ?></td>
                        <td><span class="wb-pill warning"><?php echo admin_e(str_replace('_', ' ', (string)$log['action'])); ?></span></td>
                        <td><?php echo admin_e(ucfirst((string)$log['entity_type'])); ?> #<?php echo (int)$log['entity_id']; ?></td>
                        <td><?php echo admin_e(substr((string)($log['description'] ?? ''), 0, 80)); ?></td>
                        <td class="wb-muted"><?php echo admin_e($log['ip_address'] ?? '-'); ?></td>
                        <td class="text-end"><a href="activity_log_detail.php?id=<?php echo (int)$log['id']; ?>" class="btn btn-sm btn-primary"><i class="fa fa-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">KhÃ´ng cÃ³ hoáº¡t Ä‘á»™ng phÃ¹ há»£p bá»™ lá»c.</div>
    <?php endif; ?>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i . $querySuffix; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php admin_layout_end(); ?>

