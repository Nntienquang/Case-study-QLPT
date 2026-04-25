<?php
require_once __DIR__ . '/../admin_init.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new ReviewController($db);
$data = $controller->viewReview();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Đánh Giá - Admin</title>
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
            <li><a href="<?php echo ADMIN_URL; ?>payments.php"><i class="fa fa-credit-card"></i> Thanh Toán</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>admin_revenue.php"><i class="fa fa-money"></i> Doanh Thu Admin</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>reviews.php" class="active"><i class="fa fa-star"></i> Đánh Giá</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>categories.php"><i class="fa fa-list"></i> Danh Mục</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>districts.php"><i class="fa fa-map"></i> Quận</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>utilities.php"><i class="fa fa-wrench"></i> Tiện Nghi</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1>Chi Tiết Đánh Giá</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <a href="<?php echo ADMIN_URL; ?>logout.php">Đăng Xuất</a>
            </div>
        </div>
        
        <a href="<?php echo ADMIN_URL; ?>reviews.php" class="btn btn-secondary mb-3">← Quay Lại</a>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông Tin Đánh Giá #<?php echo $data['review']['id']; ?></h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Người Đánh Giá</h6>
                        <p><?php echo htmlspecialchars($data['review']['user_name'] ?? 'N/A'); ?></p>
                        <small class="text-muted"><?php echo htmlspecialchars($data['review']['email'] ?? ''); ?></small>
                    </div>
                    <div class="col-md-6">
                        <h6>Phòng Trọ</h6>
                        <p><?php echo htmlspecialchars($data['review']['motel_title'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Đánh Giá (⭐)</h6>
                        <p>
                            <?php 
                            $rating = $data['review']['rating'] ?? 0;
                            for ($i = 0; $i < $rating; $i++) echo '⭐';
                            echo " ($rating/5)";
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Ngày Tạo</h6>
                        <p><?php echo date('d/m/Y H:i:s', strtotime($data['review']['created_at'])); ?></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Nhận Xét</h6>
                    <p><?php echo nl2br(htmlspecialchars($data['review']['comment'] ?? 'N/A')); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Delete -->
        <div class="mt-3">
            <a href="<?php echo ADMIN_URL . 'reviews.php?action=delete&id=' . $data['review']['id']; ?>" class="btn btn-danger" onclick="return confirm('Xóa đánh giá này?');">
                <i class="fa fa-trash"></i> Xóa Đánh Giá
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
