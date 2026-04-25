<?php
/**
 * Báo cáo Vi Phạm - Trang Quản Lý
 * 
 * Admin quản lý, xem xét và xử lý các báo cáo từ user
 */

require_once __DIR__ . '/../admin_init.php';

// Kiểm tra đăng nhập
if (!$is_logged_in || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Initialize
$activityLog = new ActivityLog($db);
$reportController = new ReportController($db, $activityLog);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $admin_note = $_POST['admin_note'] ?? '';
        $admin_id = $_SESSION['user_id'];
        
        // Validate status
        $valid_status = ['investigating', 'resolved', 'rejected', 'closed'];
        if (in_array($status, $valid_status) && $id > 0) {
            $report = $db->getRow("SELECT * FROM reports WHERE id = {$id}");
            
            if ($report && $db->query("UPDATE reports SET status = '{$status}', admin_note = '" . $db->getConnection()->real_escape_string($admin_note) . "', handled_by = {$admin_id}, handled_at = NOW() WHERE id = {$id}")) {
                $activityLog->log(
                    $admin_id,
                    'update_report_status',
                    'report',
                    $id,
                    ['old' => $report['status'], 'new' => $status],
                    "Cập nhật báo cáo từ {$report['status']} thành {$status}"
                );
                $_SESSION['success'] = 'Cập nhật trạng thái báo cáo thành công';
            }
        }
    } elseif ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $report = $db->getRow("SELECT * FROM reports WHERE id = {$id}");
            if ($report && $db->query("DELETE FROM reports WHERE id = {$id}")) {
                $activityLog->log(
                    $_SESSION['user_id'],
                    'delete_report',
                    'report',
                    $id,
                    [],
                    "Xóa báo cáo: {$report['title']}"
                );
                $_SESSION['success'] = 'Xóa báo cáo thành công';
            }
        }
    }
}

// Get data
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';

$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$conn = $db->getConnection();

// Build query
$query = "SELECT r.*, 
                 u_reporter.name as reporter_name, u_reporter.email as reporter_email,
                 u_reported.name as reported_name, u_reported.email as reported_email,
                 m.title as motel_title,
                 u_handler.name as handler_name
          FROM reports r
          LEFT JOIN users u_reporter ON r.reporter_id = u_reporter.id
          LEFT JOIN users u_reported ON r.reported_user_id = u_reported.id
          LEFT JOIN motels m ON r.motel_id = m.id
          LEFT JOIN users u_handler ON r.handled_by = u_handler.id
          WHERE 1=1";

if ($status) {
    $status_esc = $conn->real_escape_string($status);
    $query .= " AND r.status = '{$status_esc}'";
}

if ($type) {
    $type_esc = $conn->real_escape_string($type);
    $query .= " AND r.report_type = '{$type_esc}'";
}

// Count total
$count_query = str_replace('SELECT r.*,', 'SELECT COUNT(*) as total', $query);
$count_query = str_replace('LEFT JOIN users u_reporter ON r.reporter_id = u_reporter.id
          LEFT JOIN users u_reported ON r.reported_user_id = u_reported.id
          LEFT JOIN motels m ON r.motel_id = m.id
          LEFT JOIN users u_handler ON r.handled_by = u_handler.id', '', $count_query);
$count_result = $db->getRow("SELECT COUNT(*) as total FROM reports WHERE 1=1" . (($status) ? " AND status = '{$status_esc}'" : '') . (($type) ? " AND report_type = '{$type_esc}'" : ''));
$total = $count_result['total'] ?? 0;
$total_pages = ceil($total / $limit);

// Get reports
$query .= " ORDER BY r.created_at DESC LIMIT {$offset}, {$limit}";
$reports = $db->getRows($query);

// Get stats
$stats = [
    'total' => $db->count('reports'),
    'pending' => $db->count('reports', "status = 'pending'"),
    'investigating' => $db->count('reports', "status = 'investigating'"),
    'resolved' => $db->count('reports', "status = 'resolved'"),
    'rejected' => $db->count('reports', "status = 'rejected'"),
];

$report_types = ['spam', 'inappropriate', 'fraud', 'unsafe', 'false_info', 'other'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Báo Cáo Vi Phạm</title>
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
                            <i class="fas fa-exclamation-circle"></i> Báo Cáo Vi Phạm
                        </h1>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Tổng Báo Cáo</small>
                                        <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    </div>
                                    <i class="fas fa-exclamation-triangle text-info" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Chờ Xử Lý</small>
                                        <h3 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h3>
                                    </div>
                                    <i class="fas fa-clock text-warning" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Đang Xác Minh</small>
                                        <h3 class="mb-0 text-info"><?php echo $stats['investigating']; ?></h3>
                                    </div>
                                    <i class="fas fa-magnifying-glass text-info" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Đã Xử Lý</small>
                                        <h3 class="mb-0 text-success"><?php echo $stats['resolved']; ?></h3>
                                    </div>
                                    <i class="fas fa-check-circle text-success" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Trạng Thái</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Tất Cả</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Chờ Xử Lý</option>
                                    <option value="investigating" <?php echo $status === 'investigating' ? 'selected' : ''; ?>>Đang Xác Minh</option>
                                    <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Đã Xử Lý</option>
                                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Từ Chối</option>
                                    <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Đã Đóng</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">Loại Báo Cáo</label>
                                <select id="type" name="type" class="form-select">
                                    <option value="">Tất Cả</option>
                                    <?php foreach ($report_types as $rt): ?>
                                        <option value="<?php echo $rt; ?>" <?php echo $type === $rt ? 'selected' : ''; ?>>
                                            <?php 
                                                $labels = [
                                                    'spam' => 'Spam',
                                                    'inappropriate' => 'Nội dung không phù hợp',
                                                    'fraud' => 'Gian lận',
                                                    'unsafe' => 'Không an toàn',
                                                    'false_info' => 'Thông tin sai sự thật',
                                                    'other' => 'Khác'
                                                ];
                                                echo $labels[$rt];
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Tìm Kiếm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Reports Table -->
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tiêu Đề</th>
                                    <th>Loại</th>
                                    <th>Đối Tượng</th>
                                    <th>Người Báo Cáo</th>
                                    <th>Trạng Thái</th>
                                    <th>Ngày Tạo</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reports)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mt-2">Không có báo cáo nào</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr>
                                            <td><?php echo $report['id']; ?></td>
                                            <td>
                                                <div class="font-weight-bold"><?php echo substr($report['title'], 0, 40); ?></div>
                                                <?php if (isset($report['motel_title']) && $report['motel_title']): ?>
                                                    <small class="text-muted">Phòng: <?php echo $report['motel_title']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php
                                                        $labels = [
                                                            'spam' => 'Spam',
                                                            'inappropriate' => 'Không phù hợp',
                                                            'fraud' => 'Gian lận',
                                                            'unsafe' => 'Không an toàn',
                                                            'false_info' => 'Sai sự thật',
                                                            'other' => 'Khác'
                                                        ];
                                                        echo $labels[$report['report_type']] ?? $report['report_type'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (isset($report['reported_name']) && $report['reported_name']): ?>
                                                    <div><?php echo $report['reported_name']; ?></div>
                                                    <small class="text-muted"><?php echo $report['reported_email']; ?></small>
                                                <?php elseif (isset($report['motel_title']) && $report['motel_title']): ?>
                                                    Phòng: <?php echo substr($report['motel_title'], 0, 30); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><?php echo $report['reporter_name'] ?? 'Ẩn danh'; ?></div>
                                                <small class="text-muted"><?php echo $report['reporter_email'] ?? ''; ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                    $status_classes = [
                                                        'pending' => 'warning',
                                                        'investigating' => 'info',
                                                        'resolved' => 'success',
                                                        'rejected' => 'danger',
                                                        'closed' => 'secondary'
                                                    ];
                                                    $status_labels = [
                                                        'pending' => 'Chờ Xử Lý',
                                                        'investigating' => 'Đang Xác Minh',
                                                        'resolved' => 'Đã Xử Lý',
                                                        'rejected' => 'Từ Chối',
                                                        'closed' => 'Đã Đóng'
                                                    ];
                                                    $class = $status_classes[$report['status']] ?? 'secondary';
                                                    $label = $status_labels[$report['status']] ?? $report['status'];
                                                ?>
                                                <span class="badge bg-<?php echo $class; ?>"><?php echo $label; ?></span>
                                            </td>
                                            <td>
                                                <small><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="report_detail.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-primary" title="Xem Chi Tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($report['status'] === 'pending' || $report['status'] === 'investigating'): ?>
                                                        <button type="button" class="btn btn-outline-warning" onclick="openStatusModal(<?php echo $report['id']; ?>, '<?php echo $report['status']; ?>')" title="Cập Nhật">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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
                                <a class="page-link" href="?page=1<?php echo $status ? '&status=' . $status : ''; ?><?php echo $type ? '&type=' . $type : ''; ?>">Đầu</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $type ? '&type=' . $type : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo $status ? '&status=' . $status : ''; ?><?php echo $type ? '&type=' . $type : ''; ?>">Cuối</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Cập Nhật Trạng Thái Báo Cáo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" id="reportId" name="id">
                        
                        <div class="mb-3">
                            <label for="newStatus" class="form-label">Trạng Thái Mới</label>
                            <select id="newStatus" name="status" class="form-select" required>
                                <option value="">Chọn trạng thái</option>
                                <option value="investigating">Đang Xác Minh</option>
                                <option value="resolved">Đã Xử Lý</option>
                                <option value="rejected">Từ Chối</option>
                                <option value="closed">Đã Đóng</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adminNote" class="form-label">Ghi Chú Admin</label>
                            <textarea id="adminNote" name="admin_note" class="form-control" rows="3" placeholder="Nhập ghi chú xử lý báo cáo..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">Lưu Thay Đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const statusModal = new bootstrap.Modal(document.getElementById('statusModal'), {});
        
        function openStatusModal(reportId, currentStatus) {
            document.getElementById('reportId').value = reportId;
            document.getElementById('newStatus').value = '';
            document.getElementById('adminNote').value = '';
            statusModal.show();
        }
    </script>
</body>
</html>
