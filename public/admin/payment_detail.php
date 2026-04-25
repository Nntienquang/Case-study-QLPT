<?php
require_once __DIR__ . '/../admin_init.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new PaymentController($db);
$data = $controller->viewPayment();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Thanh Toán - Admin</title>
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
            <li><a href="<?php echo ADMIN_URL; ?>bookings.php"><i class="fa fa-calendar"></i> Đơn Đặt Phòng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>payments.php" class="active"><i class="fa fa-credit-card"></i> Thanh Toán</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>admin_revenue.php"><i class="fa fa-money"></i> Doanh Thu Admin</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>reviews.php"><i class="fa fa-star"></i> Đánh Giá</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>categories.php"><i class="fa fa-list"></i> Danh Mục</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>districts.php"><i class="fa fa-map"></i> Quận</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>utilities.php"><i class="fa fa-wrench"></i> Tiện Nghi</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1>Chi Tiết Thanh Toán</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <a href="<?php echo ADMIN_URL; ?>logout.php">Đăng Xuất</a>
            </div>
        </div>
        
        <a href="<?php echo ADMIN_URL; ?>payments.php" class="btn btn-secondary mb-3">← Quay Lại</a>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông Tin Thanh Toán #<?php echo $data['payment']['id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Người Dùng</h6>
                        <p><?php echo htmlspecialchars($data['payment']['user_name'] ?? 'N/A'); ?></p>
                        <small class="text-muted"><?php echo htmlspecialchars($data['payment']['email'] ?? ''); ?></small>
                    </div>
                    <div class="col-md-6">
                        <h6>Phòng Trọ</h6>
                        <p><?php echo htmlspecialchars($data['payment']['motel_title'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <h6>Số Tiền (VNĐ)</h6>
                        <p><?php echo number_format($data['payment']['amount'] ?? 0); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Phí (VNĐ)</h6>
                        <p><?php echo number_format($data['payment']['fee'] ?? 0); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Phương Thức</h6>
                        <p><?php echo htmlspecialchars($data['payment']['method'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Trạng Thái</h6>
                        <p><span class="pending-badge"><?php echo $data['payment']['status']; ?></span></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Mã Giao Dịch</h6>
                    <p><?php echo htmlspecialchars($data['payment']['transaction_code'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Update Status -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Cập Nhật Trạng Thái</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form method="GET" action="">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $data['payment']['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Chọn Trạng Thái:</label>
                                <select name="status" class="form-select" required>
                                    <option value="">-- Chọn Trạng Thái --</option>
                                    <option value="pending" <?php echo $data['payment']['status'] === 'pending' ? 'selected' : ''; ?>>Chờ Xử Lý</option>
                                    <option value="held" <?php echo $data['payment']['status'] === 'held' ? 'selected' : ''; ?>>Giữ Lại</option>
                                    <option value="released" <?php echo $data['payment']['status'] === 'released' ? 'selected' : ''; ?>>Đã Giải Phóng</option>
                                    <option value="refunded" <?php echo $data['payment']['status'] === 'refunded' ? 'selected' : ''; ?>>Hoàn Tiền</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Cập Nhật</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
