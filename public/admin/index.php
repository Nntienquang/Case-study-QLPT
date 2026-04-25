<?php
require_once __DIR__ . '/../admin_init.php';

// Check login
if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$dashboard = new DashboardController($db);
$data = $dashboard->index();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Quản Lý Phòng Trọ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            width: 250px;
            left: 0;
            top: 0;
        }
        
        .sidebar .logo {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .logo h2 {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .sidebar .logo p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .sidebar .nav-menu {
            list-style: none;
        }
        
        .sidebar .nav-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar .nav-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 15px;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .sidebar .nav-menu a:hover,
        .sidebar .nav-menu a.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar .nav-menu a i {
            margin-right: 10px;
            width: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .topbar {
            background: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .topbar .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .topbar .user-info span {
            color: #333;
            font-weight: 500;
        }
        
        .topbar .user-info a {
            color: #dc3545;
            text-decoration: none;
            font-size: 14px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-card .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .stat-card.primary { border-top: 4px solid #667eea; }
        .stat-card.success { border-top: 4px solid #28a745; }
        .stat-card.warning { border-top: 4px solid #ffc107; }
        .stat-card.danger { border-top: 4px solid #dc3545; }
        
        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin: 30px 0 20px 0;
            color: #333;
        }
        
        .recent-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .recent-list table {
            width: 100%;
            margin-bottom: 0;
        }
        
        .recent-list thead {
            background-color: #f8f9fa;
        }
        
        .recent-list th,
        .recent-list td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .recent-list tr:hover {
            background-color: #f5f5f5;
        }
        
        .alert {
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success-badge {
            background-color: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .pending-badge {
            background-color: #fff3cd;
            color: #856404;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .danger-badge {
            background-color: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h2>🏠 Admin</h2>
            <p>Quản Lý Phòng Trọ</p>
        </div>
        
        <ul class="nav-menu">
            <li><a href="<?php echo ADMIN_URL; ?>index.php" class="active"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>motels.php"><i class="fa fa-home"></i> Phòng Trọ</a></li>
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
            <h1 style="margin: 0; font-size: 24px; color: #333;">Dashboard</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <a href="<?php echo ADMIN_URL; ?>logout.php">Đăng Xuất</a>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-value"><?php echo $data['motel_stats']['total']; ?></div>
                    <div class="stat-label">Tổng Phòng Trọ</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $data['motel_stats']['pending']; ?></div>
                    <div class="stat-label">Chờ Duyệt</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $data['motel_stats']['approved']; ?></div>
                    <div class="stat-label">Đã Duyệt</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $data['motel_stats']['hidden']; ?></div>
                    <div class="stat-label">Ẩn</div>
                </div>
            </div>
        </div>
        
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-value"><?php echo $data['user_stats']['total']; ?></div>
                    <div class="stat-label">Tổng Người Dùng</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $data['booking_stats']['accepted']; ?></div>
                    <div class="stat-label">Đơn Chấp Nhận</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $data['booking_stats']['pending']; ?></div>
                    <div class="stat-label">Đơn Chờ Duyệt</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-value"><?php echo $data['total_reviews']; ?></div>
                    <div class="stat-label">Tổng Đánh Giá</div>
                </div>
            </div>
        </div>

        <!-- Admin Revenue Stats -->
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-3">
                <div class="stat-card" style="border-top: 4px solid #28a745;">
                    <div class="stat-value" style="color: #28a745; font-size: 24px;">
                        <?php echo number_format($data['revenue_stats']['total'] ?? 0); ?> ₫
                    </div>
                    <div class="stat-label">Tổng Doanh Thu (1%)</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top: 4px solid #17a2b8;">
                    <div class="stat-value" style="color: #17a2b8; font-size: 24px;">
                        <?php echo number_format($data['revenue_stats']['month'] ?? 0); ?> ₫
                    </div>
                    <div class="stat-label">Doanh Thu Tháng Này</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top: 4px solid #fd7e14;">
                    <div class="stat-value" style="color: #fd7e14;">
                        <?php echo $data['revenue_stats']['count'] ?? 0; ?>
                    </div>
                    <div class="stat-label">Commission Đã Nhận</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="border-top: 4px solid #6f42c1;">
                    <div class="stat-value" style="color: #6f42c1; font-size: 24px;">
                        <?php echo number_format($data['revenue_stats']['average'] ?? 0); ?> ₫
                    </div>
                    <div class="stat-label">Trung Bình/Commission</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 15px;">
            <a href="<?php echo ADMIN_URL; ?>admin_revenue.php" class="btn btn-success btn-sm">
                <i class="fa fa-money"></i> Xem Chi Tiết Doanh Thu
            </a>
        </div>
        
        <!-- Recent Motels Pending -->
        <?php if (!empty($data['recent_motels'])): ?>
        <div class="section-title">Phòng Trọ Chờ Duyệt</div>
        <div class="recent-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu Đề</th>
                        <th>Chủ Phòng</th>
                        <th>Giá (VNĐ)</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['recent_motels'] as $motel): ?>
                    <tr>
                        <td><?php echo $motel['id']; ?></td>
                        <td><?php echo htmlspecialchars($motel['title']); ?></td>
                        <td><?php echo htmlspecialchars($motel['owner_name'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($motel['price']); ?></td>
                        <td><span class="pending-badge"><?php echo $motel['status']; ?></span></td>
                        <td>
                            <a href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . $motel['id']; ?>" class="btn btn-sm btn-info">Xem</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <!-- Recent Bookings -->
        <?php if (!empty($data['recent_bookings'])): ?>
        <div class="section-title">Đơn Đặt Phòng Gần Đây</div>
        <div class="recent-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Người Dùng</th>
                        <th>Phòng Trọ</th>
                        <th>Tiền Cọc (VNĐ)</th>
                        <th>Ngày Nhập Trọ</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['recent_bookings'] as $booking): ?>
                    <tr>
                        <td><?php echo $booking['id']; ?></td>
                        <td><?php echo htmlspecialchars($booking['user_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($booking['motel_title'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($booking['deposit_amount'] ?? 0); ?></td>
                        <td><?php echo $booking['checkin_date']; ?></td>
                        <td><span class="pending-badge"><?php echo $booking['status']; ?></span></td>
                        <td>
                            <a href="<?php echo ADMIN_URL . 'booking_detail.php?id=' . $booking['id']; ?>" class="btn btn-sm btn-info">Xem</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
