<?php
/**
 * Nhật Ký Hoạt Động - Trang Quản Lý
 * 
 * Admin xem tất cả hoạt động của admin khác trong hệ thống
 */

require_once __DIR__ . '/../admin_init.php';

// Kiểm tra đăng nhập
if (!$is_logged_in || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Get data
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$admin_id = isset($_GET['admin_id']) ? (int)$_GET['admin_id'] : 0;
$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : '';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$conn = $db->getConnection();

// Build query
$query = "SELECT l.*, u.name as admin_name, u.email as admin_email
          FROM activity_logs l
          LEFT JOIN users u ON l.admin_id = u.id
          WHERE 1=1";

if ($admin_id > 0) {
    $query .= " AND l.admin_id = {$admin_id}";
}

if (!empty($entity_type)) {
    $entity_type_esc = $conn->real_escape_string($entity_type);
    $query .= " AND l.entity_type = '{$entity_type_esc}'";
}

if (!empty($action)) {
    $action_esc = $conn->real_escape_string($action);
    $query .= " AND l.action = '{$action_esc}'";
}

if (!empty($date_from)) {
    $date_from_esc = $conn->real_escape_string($date_from);
    $query .= " AND DATE(l.created_at) >= '{$date_from_esc}'";
}

if (!empty($date_to)) {
    $date_to_esc = $conn->real_escape_string($date_to);
    $query .= " AND DATE(l.created_at) <= '{$date_to_esc}'";
}

// Count total
$count_query = str_replace('SELECT l.*,', 'SELECT COUNT(*) as total', $query);
$count_query = str_replace('LEFT JOIN users u ON l.admin_id = u.id', '', $count_query);
$count_result = $db->getRow("SELECT COUNT(*) as total FROM activity_logs WHERE 1=1" . 
    (($admin_id > 0) ? " AND admin_id = {$admin_id}" : '') .
    ((!empty($entity_type)) ? " AND entity_type = '{$entity_type_esc}'" : '') .
    ((!empty($action)) ? " AND action = '{$action_esc}'" : '') .
    ((!empty($date_from)) ? " AND DATE(created_at) >= '{$date_from_esc}'" : '') .
    ((!empty($date_to)) ? " AND DATE(created_at) <= '{$date_to_esc}'" : '')
);
$total = $count_result['total'] ?? 0;
$total_pages = ceil($total / $limit);

// Get logs
$query .= " ORDER BY l.created_at DESC LIMIT {$offset}, {$limit}";
$logs = $db->getRows($query);

// Get stats
$stats_query = "SELECT 
    COUNT(*) as total,
    COUNT(IF(DATE(created_at) = CURDATE(), 1, NULL)) as today,
    COUNT(IF(YEARWEEK(created_at) = YEARWEEK(NOW()), 1, NULL)) as week,
    COUNT(IF(MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()), 1, NULL)) as month
    FROM activity_logs";
$stats = $db->getRow($stats_query);

// Get unique admins
$admins_query = "SELECT DISTINCT u.id, u.name FROM activity_logs l
                 LEFT JOIN users u ON l.admin_id = u.id
                 ORDER BY u.name";
$all_admins = $db->getRows($admins_query);

// Get unique entity types
$entity_types_query = "SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type";
$all_entity_types = $db->getRows($entity_types_query);

// Get unique actions
$actions_query = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
$all_actions = $db->getRows($actions_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhật Ký Hoạt Động</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require_once __DIR__ . '/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-area">
                <!-- Header -->
                <div class="row align-items-center border-bottom py-3 mb-4">
                    <div class="col">
                        <h1 class="h3">
                            <i class="fas fa-history"></i> Nhật Ký Hoạt Động
                        </h1>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Tổng Cộng</small>
                                        <h3 class="mb-0"><?php echo $stats['total'] ?? 0; ?></h3>
                                    </div>
                                    <i class="fas fa-list text-info" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Hôm Nay</small>
                                        <h3 class="mb-0"><?php echo $stats['today'] ?? 0; ?></h3>
                                    </div>
                                    <i class="fas fa-calendar-day text-primary" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Tuần Này</small>
                                        <h3 class="mb-0"><?php echo $stats['week'] ?? 0; ?></h3>
                                    </div>
                                    <i class="fas fa-calendar-week text-success" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Tháng Này</small>
                                        <h3 class="mb-0"><?php echo $stats['month'] ?? 0; ?></h3>
                                    </div>
                                    <i class="fas fa-calendar-alt text-warning" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <label for="admin_id" class="form-label">Admin</label>
                                <select id="admin_id" name="admin_id" class="form-select form-select-sm">
                                    <option value="">Tất Cả</option>
                                    <?php if (!empty($all_admins)): ?>
                                        <?php foreach ($all_admins as $a): ?>
                                            <option value="<?php echo $a['id']; ?>" <?php echo $admin_id === $a['id'] ? 'selected' : ''; ?>>
                                                <?php echo $a['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="entity_type" class="form-label">Loại Thực Thể</label>
                                <select id="entity_type" name="entity_type" class="form-select form-select-sm">
                                    <option value="">Tất Cả</option>
                                    <?php if (!empty($all_entity_types)): ?>
                                        <?php foreach ($all_entity_types as $et): ?>
                                            <option value="<?php echo $et['entity_type']; ?>" <?php echo $entity_type === $et['entity_type'] ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($et['entity_type']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="action" class="form-label">Hành Động</label>
                                <select id="action" name="action" class="form-select form-select-sm">
                                    <option value="">Tất Cả</option>
                                    <?php if (!empty($all_actions)): ?>
                                        <?php foreach ($all_actions as $act): ?>
                                            <option value="<?php echo $act['action']; ?>" <?php echo $action === $act['action'] ? 'selected' : ''; ?>>
                                                <?php echo str_replace('_', ' ', ucfirst($act['action'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Từ Ngày</label>
                                <input type="date" id="date_from" name="date_from" class="form-control form-control-sm" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Đến Ngày</label>
                                <input type="date" id="date_to" name="date_to" class="form-control form-control-sm" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100 btn-sm">
                                    <i class="fas fa-search"></i> Tìm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Logs Table -->
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Thời Gian</th>
                                    <th>Admin</th>
                                    <th>Hành Động</th>
                                    <th>Thực Thể</th>
                                    <th>Mô Tả</th>
                                    <th>IP Address</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mt-2">Không có dữ liệu</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo $log['admin_name'] ?? 'Ẩn danh'; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo str_replace('_', ' ', $log['action']); ?></span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo ucfirst($log['entity_type']); ?> #<?php echo $log['entity_id']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small><?php echo substr($log['description'] ?? '', 0, 50); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo $log['ip_address'] ?? '-'; ?></small>
                                            </td>
                                            <td>
                                                <a href="activity_log_detail.php?id=<?php echo $log['id']; ?>" class="btn btn-sm btn-outline-primary" title="Xem Chi Tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=1<?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($entity_type) ? '&entity_type=' . $entity_type : ''; ?><?php echo !empty($action) ? '&action=' . $action : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?>">Đầu</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($entity_type) ? '&entity_type=' . $entity_type : ''; ?><?php echo !empty($action) ? '&action=' . $action : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($entity_type) ? '&entity_type=' . $entity_type : ''; ?><?php echo !empty($action) ? '&action=' . $action : ''; ?><?php echo !empty($date_from) ? '&date_from=' . $date_from : ''; ?><?php echo !empty($date_to) ? '&date_to=' . $date_to : ''; ?>">Cuối</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
