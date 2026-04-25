<?php
/**
 * Admin Revenue Page
 * Hiển thị doanh thu (commission) của admin
 */

require_once __DIR__ . '/../admin_init.php';

// Check admin access
if (!$is_logged_in || $_SESSION['role'] !== ROLE_ADMIN) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Initialize controller
$controller = new AdminRevenueController($db);

// Get revenue data
$revenue_data = $controller->listRevenue();
$stats = $revenue_data['stats'];
$revenue = $revenue_data['revenue'];

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doanh Thu Admin - Quản Lý Phòng Trọ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="<?php echo ADMIN_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h4>🏠 Admin Panel</h4>
            </div>
            <nav class="navbar-nav">
                <a href="<?php echo ADMIN_URL; ?>index.php" class="nav-link">
                    <i class="fa fa-dashboard"></i> Dashboard
                </a>
                <a href="<?php echo ADMIN_URL; ?>motels.php" class="nav-link">
                    <i class="fa fa-home"></i> Phòng Trọ
                </a>
                <a href="<?php echo ADMIN_URL; ?>users.php" class="nav-link">
                    <i class="fa fa-users"></i> Người Dùng
                </a>
                <a href="<?php echo ADMIN_URL; ?>bookings.php" class="nav-link">
                    <i class="fa fa-calendar"></i> Đơn Đặt Phòng
                </a>
                <a href="<?php echo ADMIN_URL; ?>payments.php" class="nav-link">
                    <i class="fa fa-credit-card"></i> Thanh Toán
                </a>
                <a href="<?php echo ADMIN_URL; ?>admin_revenue.php" class="nav-link active">
                    <i class="fa fa-money"></i> Doanh Thu Admin
                </a>
                <a href="<?php echo ADMIN_URL; ?>reviews.php" class="nav-link">
                    <i class="fa fa-star"></i> Đánh Giá
                </a>
                <div class="divider"></div>
                <a href="<?php echo ADMIN_URL; ?>categories.php" class="nav-link">
                    <i class="fa fa-list"></i> Danh Mục
                </a>
                <a href="<?php echo ADMIN_URL; ?>districts.php" class="nav-link">
                    <i class="fa fa-map"></i> Quận
                </a>
                <a href="<?php echo ADMIN_URL; ?>utilities.php" class="nav-link">
                    <i class="fa fa-cog"></i> Tiện Nghi
                </a>
                <div class="divider"></div>
                <a href="<?php echo ADMIN_URL; ?>logout.php" class="nav-link logout">
                    <i class="fa fa-sign-out"></i> Đăng Xuất
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <div class="top-nav">
                <div class="container-fluid">
                    <div class="nav-brand">
                        <h3>💰 Doanh Thu Admin</h3>
                    </div>
                    <div class="nav-user">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="content">
                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo formatCurrency($stats['total']); ?> ₫</div>
                                <div class="stat-label">Tổng Doanh Thu</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fa fa-money"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo formatCurrency($stats['month']); ?> ₫</div>
                                <div class="stat-label">Doanh Thu Tháng Này</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fa fa-calendar"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['count']; ?></div>
                                <div class="stat-label">Số Lần Nhận Commission</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fa fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-content">
                                <div class="stat-value"><?php echo formatCurrency($stats['average']); ?> ₫</div>
                                <div class="stat-label">Trung Bình Commission</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fa fa-bar-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>📋 Danh Sách Commission (1% mỗi booking)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($revenue) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 10%;">#ID</th>
                                            <th style="width: 15%;">Phòng Trọ</th>
                                            <th style="width: 15%;">Khách Hàng</th>
                                            <th style="width: 12%;">Giá Phòng</th>
                                            <th style="width: 12%;">Commission (1%)</th>
                                            <th style="width: 15%;">Ngày Nhận</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($revenue as $item): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary">#<?php echo $item['booking_id']; ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['motel_title']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">Chủ: <?php echo htmlspecialchars($item['owner_name']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($item['user_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($item['user_email']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo formatCurrency($item['motel_price']); ?> ₫</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success" style="font-size: 14px; padding: 8px;">
                                                        <?php echo formatCurrency($item['amount']); ?> ₫
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($revenue_data['total_pages'] > 1): ?>
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-center">
                                        <?php for ($i = 1; $i <= $revenue_data['total_pages']; $i++): ?>
                                            <li class="page-item <?php echo ($i === $revenue_data['page']) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> Chưa có commission nào. Commission sẽ được nhận khi khách hàng đặt phòng thành công.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card" style="border-left: 4px solid #667eea;">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #667eea;">
                                    <i class="fa fa-info-circle"></i> Cơ Chế Doanh Thu
                                </h6>
                                <ul style="margin-bottom: 0; font-size: 14px;">
                                    <li>Admin nhận <strong>1% commission</strong> từ mỗi booking</li>
                                    <li>Commission được tính từ <strong>số tiền đặt cọc</strong> của khách</li>
                                    <li>Tiền được phát sinh khi <strong>payment status = 'Released'</strong></li>
                                    <li>Phần còn lại <strong>99%</strong> sẽ được chuyển cho chủ phòng</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card" style="border-left: 4px solid #28a745;">
                            <div class="card-body">
                                <h6 class="card-title" style="color: #28a745;">
                                    <i class="fa fa-calculator"></i> Ví Dụ Tính Toán
                                </h6>
                                <div style="font-size: 14px;">
                                    <p><strong>Phòng có giá:</strong> 5.000.000 ₫</p>
                                    <p style="margin-bottom: 5px;"><strong>Commission Admin (1%):</strong> 50.000 ₫ ✓</p>
                                    <p style="margin-bottom: 0;"><strong>Chủ trọ nhận (99%):</strong> 4.950.000 ₫</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
