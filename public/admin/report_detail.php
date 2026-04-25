<?php
/**
 * Chi Tiết Báo Cáo - Trang Xử Lý
 * 
 * Admin xem chi tiết báo cáo và thực hiện xử lý
 */

require_once __DIR__ . '/../admin_init.php';

// Kiểm tra đăng nhập
if (!$is_logged_in || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Get report ID
if (!isset($_GET['id'])) {
    header('Location: ' . ADMIN_URL . 'reports.php');
    exit;
}

$id = (int)$_GET['id'];

// Initialize
$activityLog = new ActivityLog($db);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $status = $_POST['status'] ?? '';
        $admin_note = $_POST['admin_note'] ?? '';
        $admin_id = $_SESSION['user_id'];
        
        // Validate status
        $valid_status = ['investigating', 'resolved', 'rejected', 'closed'];
        if (in_array($status, $valid_status)) {
            $report = $db->getRow("SELECT * FROM reports WHERE id = {$id}");
            
            if ($report) {
                $conn = $db->getConnection();
                $admin_note_esc = $conn->real_escape_string($admin_note);
                
                $update_query = "UPDATE reports SET status = '{$status}', admin_note = '{$admin_note_esc}', handled_by = {$admin_id}, handled_at = NOW() WHERE id = {$id}";
                
                if ($db->query($update_query)) {
                    $activityLog->log(
                        $admin_id,
                        'update_report_status',
                        'report',
                        $id,
                        ['old' => $report['status'], 'new' => $status],
                        "Cập nhật báo cáo từ {$report['status']} thành {$status}. Ghi chú: {$admin_note}"
                    );
                    $_SESSION['success'] = 'Cập nhật trạng thái báo cáo thành công';
                    header('Location: reports.php');
                    exit;
                }
            }
        }
    }
}

// Get report details
$query = "SELECT r.*, 
                 u_reporter.name as reporter_name, u_reporter.email as reporter_email, u_reporter.phone as reporter_phone, u_reporter.created_at as reporter_joined,
                 u_reported.name as reported_name, u_reported.email as reported_email, u_reported.phone as reported_phone, u_reported.created_at as reported_joined,
                 m.id as motel_id, m.title as motel_title, m.description as motel_desc, m.price as motel_price,
                 u_handler.name as handler_name, u_handler.email as handler_email
          FROM reports r
          LEFT JOIN users u_reporter ON r.reporter_id = u_reporter.id
          LEFT JOIN users u_reported ON r.reported_user_id = u_reported.id
          LEFT JOIN motels m ON r.motel_id = m.id
          LEFT JOIN users u_handler ON r.handled_by = u_handler.id
          WHERE r.id = {$id}";

$report = $db->getRow($query);

if (!$report) {
    $_SESSION['error'] = 'Báo cáo không tồn tại';
    header('Location: ' . ADMIN_URL . 'reports.php');
    exit;
}

$report_type_labels = [
    'spam' => 'Spam',
    'inappropriate' => 'Nội dung không phù hợp',
    'fraud' => 'Gian lận',
    'unsafe' => 'Không an toàn',
    'false_info' => 'Thông tin sai sự thật',
    'other' => 'Khác'
];

$status_labels = [
    'pending' => 'Chờ Xử Lý',
    'investigating' => 'Đang Xác Minh',
    'resolved' => 'Đã Xử Lý',
    'rejected' => 'Từ Chối',
    'closed' => 'Đã Đóng'
];

$status_classes = [
    'pending' => 'warning',
    'investigating' => 'info',
    'resolved' => 'success',
    'rejected' => 'danger',
    'closed' => 'secondary'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Báo Cáo</title>
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
                        <a href="reports.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                        <h1 class="h3 mt-2">
                            <i class="fas fa-info-circle"></i> Chi Tiết Báo Cáo
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

                <div class="row">
                    <!-- Main Info -->
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">
                                    <?php echo $report['title']; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <small class="text-muted">Loại Báo Cáo</small>
                                        <div>
                                            <span class="badge bg-info"><?php echo $report_type_labels[$report['report_type']] ?? $report['report_type']; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Trạng Thái</small>
                                        <div>
                                            <?php 
                                                $class = $status_classes[$report['status']] ?? 'secondary';
                                                $label = $status_labels[$report['status']] ?? $report['status'];
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>"><?php echo $label; ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <h6>Mô Tả Chi Tiết</h6>
                                    <p><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                </div>

                                <?php if ($report['evidence_image']): ?>
                                    <div class="mb-4">
                                        <h6>Hình Chứng Minh</h6>
                                        <img src="<?php echo $report['evidence_image']; ?>" class="img-fluid" style="max-width: 100%; max-height: 400px;">
                                    </div>
                                <?php endif; ?>

                                <?php if ($report['admin_note']): ?>
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-sticky-note"></i> Ghi Chú Admin</h6>
                                        <p><?php echo nl2br(htmlspecialchars($report['admin_note'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Người Báo Cáo -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">Người Báo Cáo</h5>
                            </div>
                            <div class="card-body">
                                <?php if ($report['reporter_name']): ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Tên</small>
                                            <div><?php echo $report['reporter_name']; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Email</small>
                                            <div><?php echo $report['reporter_email']; ?></div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <small class="text-muted">Điện Thoại</small>
                                            <div><?php echo $report['reporter_phone'] ?? 'N/A'; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Tham Gia Từ</small>
                                            <div><?php echo date('d/m/Y', strtotime($report['reporter_joined'])); ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0">Báo cáo ẩn danh</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Đối Tượng Bị Báo Cáo -->
                        <?php if ($report['reported_name'] || $report['motel_id']): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Đối Tượng Bị Báo Cáo</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($report['reported_name']): ?>
                                        <h6 class="mb-3">Chủ Trọ / Người Dùng</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">Tên</small>
                                                <div><?php echo $report['reported_name']; ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Email</small>
                                                <div><?php echo $report['reported_email']; ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($report['motel_id']): ?>
                                        <hr>
                                        <h6 class="mb-3">Phòng Trọ</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <small class="text-muted">Tên Phòng</small>
                                                <div><?php echo $report['motel_title']; ?></div>
                                            </div>
                                            <div class="col-md-6">
                                                <small class="text-muted">Giá Phòng</small>
                                                <div><?php echo number_format($report['motel_price']); ?> đ</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar Info -->
                    <div class="col-md-4">
                        <!-- Timeline -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">Thông Tin Xử Lý</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Ngày Báo Cáo</small>
                                    <div class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($report['created_at'])); ?></div>
                                </div>

                                <?php if ($report['handled_at']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">Ngày Xử Lý</small>
                                        <div class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($report['handled_at'])); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">Xử Lý Bởi</small>
                                        <div class="fw-bold"><?php echo $report['handler_name'] ?? 'N/A'; ?></div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning" role="alert">
                                        <small><i class="fas fa-exclamation-triangle"></i> Chưa được xử lý</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Update Status -->
                        <?php if ($report['status'] === 'pending' || $report['status'] === 'investigating'): ?>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Cập Nhật Trạng Thái</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_status">
                                        
                                        <div class="mb-3">
                                            <label for="status" class="form-label">Trạng Thái Mới</label>
                                            <select id="status" name="status" class="form-select" required>
                                                <option value="">Chọn trạng thái</option>
                                                <option value="investigating">Đang Xác Minh</option>
                                                <option value="resolved">Đã Xử Lý</option>
                                                <option value="rejected">Từ Chối</option>
                                                <option value="closed">Đã Đóng</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="admin_note" class="form-label">Ghi Chú</label>
                                            <textarea id="admin_note" name="admin_note" class="form-control" rows="4" placeholder="Ghi chú chi tiết về xử lý..." required><?php echo htmlspecialchars($report['admin_note'] ?? ''); ?></textarea>
                                        </div>

                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-save"></i> Lưu Thay Đổi
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                <small><i class="fas fa-info-circle"></i> Báo cáo đã được xử lý. Không thể chỉnh sửa.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
