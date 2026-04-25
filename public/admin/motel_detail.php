<?php
require_once __DIR__ . '/../admin_init.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$controller = new MotelController($db);
$data = $controller->viewMotel();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Phòng Trọ - Admin</title>
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
            <li><a href="<?php echo ADMIN_URL; ?>motels.php" class="active"><i class="fa fa-home"></i> Phòng Trọ</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>users.php"><i class="fa fa-users"></i> Người Dùng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>bookings.php"><i class="fa fa-calendar"></i> Đơn Đặt Phòng</a></li>
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
            <h1>Chi Tiết Phòng Trọ</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <a href="<?php echo ADMIN_URL; ?>logout.php">Đăng Xuất</a>
            </div>
        </div>
        
        <a href="<?php echo ADMIN_URL; ?>motels.php" class="btn btn-secondary mb-3">← Quay Lại</a>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông Tin Phòng Trọ</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Tiêu Đề</h6>
                        <p><?php echo htmlspecialchars($data['motel']['title']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Trạng Thái</h6>
                        <p>
                            <span class="<?php echo $data['motel']['status'] === 'pending' ? 'pending-badge' : ($data['motel']['status'] === 'approved' ? 'success-badge' : 'danger-badge'); ?>">
                                <?php echo $data['motel']['status']; ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Chủ Phòng</h6>
                        <p><?php echo htmlspecialchars($data['motel']['owner_name'] ?? 'N/A'); ?></p>
                        <small class="text-muted"><?php echo htmlspecialchars($data['motel']['owner_email'] ?? ''); ?> | <?php echo htmlspecialchars($data['motel']['owner_phone'] ?? ''); ?></small>
                    </div>
                    <div class="col-md-6">
                        <h6>Danh Mục</h6>
                        <p><?php echo htmlspecialchars($data['motel']['category_name'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <h6>Giá (VNĐ)</h6>
                        <p><?php echo number_format($data['motel']['price']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Diện Tích (m²)</h6>
                        <p><?php echo $data['motel']['area'] ?? 'N/A'; ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Quận</h6>
                        <p><?php echo htmlspecialchars($data['motel']['district_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-3">
                        <h6>Lượt Xem</h6>
                        <p><?php echo $data['motel']['count_view'] ?? 0; ?></p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h6>Địa Chỉ</h6>
                    <p><?php echo htmlspecialchars($data['motel']['address'] ?? 'N/A'); ?></p>
                </div>
                
                <div class="mb-3">
                    <h6>Số Điện Thoại</h6>
                    <p><?php echo htmlspecialchars($data['motel']['phone'] ?? 'N/A'); ?></p>
                </div>
                
                <div class="mb-3">
                    <h6>Mô Tả</h6>
                    <p><?php echo nl2br(htmlspecialchars($data['motel']['description'] ?? 'N/A')); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Images -->
        <?php if (!empty($data['images'])): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Hình Ảnh (<?php echo count($data['images']); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($data['images'] as $image): ?>
                    <div class="col-md-3 mb-3">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($image['image_url']); ?>" alt="Hình ảnh" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Utilities -->
        <?php if (!empty($data['utilities'])): ?>
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0">Tiện Nghi</h5>
            </div>
            <div class="card-body">
                <?php foreach ($data['utilities'] as $utility): ?>
                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($utility['name']); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Action Buttons -->
        <div class="mt-3">
            <?php if ($data['motel']['status'] === 'pending'): ?>
            <a href="<?php echo ADMIN_URL . 'motels.php?action=approve&id=' . $data['motel']['id']; ?>" class="btn btn-success" onclick="return confirm('Duyệt phòng này?');">
                <i class="fa fa-check"></i> Duyệt Phòng
            </a>
            <?php endif; ?>
            
            <?php if ($data['motel']['status'] !== 'hidden'): ?>
            <a href="<?php echo ADMIN_URL . 'motels.php?action=hide&id=' . $data['motel']['id']; ?>" class="btn btn-warning" onclick="return confirm('Ẩn phòng này?');">
                <i class="fa fa-times"></i> Ẩn Phòng
            </a>
            <?php endif; ?>
            
            <a href="<?php echo ADMIN_URL . 'motels.php?action=delete&id=' . $data['motel']['id']; ?>" class="btn btn-danger" onclick="return confirm('Xóa phòng này?');">
                <i class="fa fa-trash"></i> Xóa Phòng
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
