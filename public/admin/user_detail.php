<?php
/**
 * Chi Tiết Tài Khoản Chủ Trọ
 * 
 * Admin xem chi tiết tài khoản owner
 */

require_once __DIR__ . '/../admin_init.php';

// Kiểm tra đăng nhập
if (!$is_logged_in || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Get user ID
if (!isset($_GET['id'])) {
    header('Location: ' . ADMIN_URL . 'user_approvals.php');
    exit;
}

$id = (int)$_GET['id'];

// Initialize
$activityLog = new ActivityLog($db);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'approve') {
        $user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
        if ($user && $user['role'] === 'owner') {
            $admin_id = $_SESSION['user_id'];
            if ($db->query("UPDATE users SET status = 'approved', approved_by = {$admin_id}, approved_at = NOW() WHERE id = {$id}")) {
                $activityLog->log(
                    $admin_id,
                    'approve_user',
                    'user',
                    $id,
                    [],
                    "Duyệt tài khoản Owner: {$user['name']} ({$user['email']})"
                );
                $_SESSION['success'] = "Đã duyệt tài khoản {$user['name']}";
                header('Location: user_approvals.php');
                exit;
            }
        }
    } elseif ($_POST['action'] === 'reject') {
        $rejection_reason = $_POST['rejection_reason'] ?? '';
        if (!empty($rejection_reason)) {
            $user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
            if ($user && $user['role'] === 'owner') {
                $admin_id = $_SESSION['user_id'];
                $conn = $db->getConnection();
                $reason_esc = $conn->real_escape_string($rejection_reason);
                if ($db->query("UPDATE users SET status = 'rejected', approved_by = {$admin_id}, approved_at = NOW(), rejection_reason = '{$reason_esc}' WHERE id = {$id}")) {
                    $activityLog->log(
                        $admin_id,
                        'reject_user',
                        'user',
                        $id,
                        [],
                        "Từ chối tài khoản Owner: {$user['name']}. Lý do: {$rejection_reason}"
                    );
                    $_SESSION['success'] = "Đã từ chối tài khoản {$user['name']}";
                    header('Location: user_approvals.php');
                    exit;
                }
            }
        }
    }
}

// Get user details
$user = $db->getRow("SELECT * FROM users WHERE id = {$id}");

if (!$user) {
    $_SESSION['error'] = 'Người dùng không tồn tại';
    header('Location: ' . ADMIN_URL . 'user_approvals.php');
    exit;
}

// Get user's motels
$motels = $db->getRows("SELECT * FROM motels WHERE owner_id = {$id} ORDER BY created_at DESC");

// Get user's bookings
$bookings = $db->getRows("SELECT b.*, m.title as motel_title FROM bookings b 
                          LEFT JOIN motels m ON b.motel_id = m.id
                          WHERE b.user_id = {$id} OR b.owner_id = {$id}
                          ORDER BY b.created_at DESC LIMIT 10");

// Get user's reviews
$reviews = $db->getRows("SELECT * FROM reviews WHERE user_id = {$id} ORDER BY created_at DESC LIMIT 5");

$status_labels = [
    'pending' => 'Chờ Duyệt',
    'approved' => 'Đã Duyệt',
    'rejected' => 'Từ Chối',
    'blocked' => 'Bị Khóa'
];

$status_classes = [
    'pending' => 'warning',
    'approved' => 'success',
    'rejected' => 'danger',
    'blocked' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Tài Khoản</title>
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
                        <a href="user_approvals.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                        <h1 class="h3 mt-2">
                            <i class="fas fa-user"></i> Chi Tiết Tài Khoản
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
                        <!-- User Info Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo $user['name']; ?></h5>
                                    <?php 
                                        $class = $status_classes[$user['status']] ?? 'secondary';
                                        $label = $status_labels[$user['status']] ?? $user['status'];
                                    ?>
                                    <span class="badge bg-<?php echo $class; ?>"><?php echo $label; ?></span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <small class="text-muted">Email</small>
                                        <div><?php echo $user['email']; ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Điện Thoại</small>
                                        <div><?php echo $user['phone'] ?? '-'; ?></div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <small class="text-muted">Loại Tài Khoản</small>
                                        <div><?php echo ucfirst($user['role']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Ngày Đăng Ký</small>
                                        <div><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">Địa Chỉ</small>
                                        <div><?php echo $user['address'] ?? '-'; ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Quận/Huyện</small>
                                        <div>
                                            <?php 
                                                if ($user['district_id']) {
                                                    $district = $db->getRow("SELECT name FROM districts WHERE id = {$user['district_id']}");
                                                    echo $district ? $district['name'] : '-';
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ID Card Info -->
                        <?php if ($user['idcard_front'] || $user['idcard_back']): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Thông Tin CCCD</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php if ($user['idcard_front']): ?>
                                            <div class="col-md-6">
                                                <small class="text-muted">Mặt Trước</small>
                                                <img src="<?php echo $user['idcard_front']; ?>" class="img-fluid" style="max-width: 100%;">
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($user['idcard_back']): ?>
                                            <div class="col-md-6">
                                                <small class="text-muted">Mặt Sau</small>
                                                <img src="<?php echo $user['idcard_back']; ?>" class="img-fluid" style="max-width: 100%;">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Approval History -->
                        <?php if ($user['approved_at']): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Lịch Sử Duyệt</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <small class="text-muted">Thời Gian</small>
                                        <div><?php echo date('d/m/Y H:i', strtotime($user['approved_at'])); ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted">Xử Lý Bởi</small>
                                        <div>
                                            <?php 
                                                if ($user['approved_by']) {
                                                    $admin = $db->getRow("SELECT name FROM users WHERE id = {$user['approved_by']}");
                                                    echo $admin ? $admin['name'] : 'Admin #' . $user['approved_by'];
                                                } else {
                                                    echo '-';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    <?php if ($user['rejection_reason']): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <h6>Lý Do Từ Chối</h6>
                                            <p><?php echo nl2br(htmlspecialchars($user['rejection_reason'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- User's Motels -->
                        <?php if (!empty($motels)): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Phòng Trọ (<?php echo count($motels); ?>)</h5>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Tên</th>
                                                <th>Giá</th>
                                                <th>Trạng Thái</th>
                                                <th>Ngày Tạo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($motels as $motel): ?>
                                                <tr>
                                                    <td><?php echo substr($motel['title'], 0, 40); ?></td>
                                                    <td><?php echo number_format($motel['price']); ?> đ</td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($motel['status'] === 'approved' ? 'success' : ($motel['status'] === 'pending' ? 'warning' : 'danger')); ?>">
                                                            <?php echo ucfirst($motel['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('d/m/Y', strtotime($motel['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-md-4">
                        <!-- Actions -->
                        <?php if ($user['status'] === 'pending'): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Hành Động</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn duyệt tài khoản này?');">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success w-100 mb-2">
                                            <i class="fas fa-check"></i> Duyệt Tài Khoản
                                        </button>
                                    </form>
                                    <button class="btn btn-danger w-100" onclick="openRejectModal()">
                                        <i class="fas fa-times"></i> Từ Chối
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Stats -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">Thống Kê</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Phòng Trọ</small>
                                    <div class="h5 mb-0"><?php echo count($motels); ?></div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Đơn Đặt</small>
                                    <div class="h5 mb-0"><?php echo $db->count('bookings', "user_id = {$id} OR owner_id = {$id}"); ?></div>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted">Đánh Giá</small>
                                    <div class="h5 mb-0"><?php echo count($reviews); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Reviews -->
                        <?php if (!empty($reviews)): ?>
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Đánh Giá Gần Nhất</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div>
                                                        <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                                            <i class="fas fa-star text-warning"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Từ Chối Tài Khoản</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Bạn sắp từ chối tài khoản của <strong><?php echo $user['name']; ?></strong></p>
                        
                        <input type="hidden" name="action" value="reject">
                        
                        <div class="mb-0">
                            <label for="rejection_reason" class="form-label">Lý Do Từ Chối</label>
                            <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Nhập lý do từ chối..." required></textarea>
                            <small class="text-muted">Ví dụ: Số điện thoại không liên lạc được, Thông tin không hợp lệ, ...</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Từ Chối</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'), {});
        
        function openRejectModal() {
            document.getElementById('rejection_reason').value = '';
            rejectModal.show();
        }
    </script>
</body>
</html>
