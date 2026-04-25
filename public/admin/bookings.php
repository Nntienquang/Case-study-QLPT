<?php
require_once __DIR__ . '/../admin_init.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new BookingController($db, $activityLog);
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
    $controller->updateStatus();
}
if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteBooking();
}

$data = $controller->listBookings();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Đơn Đặt Phòng - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="<?php echo ADMIN_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h2>🏠 Admin</h2>
            <p>Quản Lý Phòng Trọ</p>
        </div>
        
        <ul class="nav-menu">
            <li><a href="<?php echo ADMIN_URL; ?>index.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>motels.php"><i class="fa fa-home"></i> Phòng Trọ</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>users.php"><i class="fa fa-users"></i> Người Dùng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>bookings.php" class="active"><i class="fa fa-calendar"></i> Đơn Đặt Phòng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>payments.php"><i class="fa fa-credit-card"></i> Thanh Toán</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>admin_revenue.php"><i class="fa fa-money"></i> Doanh Thu Admin</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>reviews.php"><i class="fa fa-star"></i> Đánh Giá</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>categories.php"><i class="fa fa-list"></i> Danh Mục</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>districts.php"><i class="fa fa-map"></i> Quận</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>utilities.php"><i class="fa fa-wrench"></i> Tiện Nghi</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1>Quản Lý Đơn Đặt Phòng</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <a href="<?php echo ADMIN_URL; ?>logout.php">Đăng Xuất</a>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Trạng Thái:</label>
                        <select name="status" class="form-select">
                            <option value="">-- Tất Cả --</option>
                            <option value="pending" <?php echo $data['status'] === 'pending' ? 'selected' : ''; ?>>Chờ Duyệt</option>
                            <option value="paid" <?php echo $data['status'] === 'paid' ? 'selected' : ''; ?>>Đã Thanh Toán</option>
                            <option value="accepted" <?php echo $data['status'] === 'accepted' ? 'selected' : ''; ?>>Chấp Nhận</option>
                            <option value="completed" <?php echo $data['status'] === 'completed' ? 'selected' : ''; ?>>Hoàn Thành</option>
                        </select>
                    </div>
                    <div class="col-md-6" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bookings List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh Sách Đơn Đặt Phòng (<?php echo $data['total']; ?> đơn)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['bookings'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Khách Hàng</th>
                                <th>Phòng Trọ</th>
                                <th>Tiền Cọc (VNĐ)</th>
                                <th>Ngày Nhập Trọ</th>
                                <th>Trạng Thái</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['bookings'] as $booking): ?>
                            <tr>
                                <td><?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['user_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(substr($booking['motel_title'] ?? 'N/A', 0, 30)); ?></td>
                                <td><?php echo number_format($booking['deposit_amount'] ?? 0); ?></td>
                                <td><?php echo $booking['checkin_date']; ?></td>
                                <td><span class="pending-badge"><?php echo $booking['status']; ?></span></td>
                                <td>
                                    <a href="<?php echo ADMIN_URL . 'booking_detail.php?id=' . $booking['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fa fa-eye"></i> Xem
                                    </a>
                                    <a href="<?php echo ADMIN_URL . 'bookings.php?action=delete&id=' . $booking['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa đơn này?');">
                                        <i class="fa fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($data['total_pages'] > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($data['page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'bookings.php?page=1'; ?>">Đầu tiên</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'bookings.php?page=' . $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($data['page'] < $data['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'bookings.php?page=' . ($data['page'] + 1); ?>">Sau</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="alert alert-info">Không có đơn đặt phòng nào.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
