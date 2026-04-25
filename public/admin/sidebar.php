<?php
/**
 * Admin Sidebar Navigation
 */

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$pages = [
    'index' => ['icon' => 'fa fa-dashboard', 'label' => 'Dashboard', 'url' => 'index.php'],
    'motels' => ['icon' => 'fa fa-home', 'label' => 'Phòng Trọ', 'url' => 'motels.php'],
    'users' => ['icon' => 'fa fa-users', 'label' => 'Người Dùng', 'url' => 'users.php'],
    'user_approvals' => ['icon' => 'fa fa-user-check', 'label' => 'Duyệt Chủ Trọ', 'url' => 'user_approvals.php'],
    'bookings' => ['icon' => 'fa fa-calendar', 'label' => 'Đơn Đặt Phòng', 'url' => 'bookings.php'],
    'payments' => ['icon' => 'fa fa-credit-card', 'label' => 'Thanh Toán', 'url' => 'payments.php'],
    'admin_revenue' => ['icon' => 'fa fa-money', 'label' => 'Doanh Thu Admin', 'url' => 'admin_revenue.php'],
    'reviews' => ['icon' => 'fa fa-star', 'label' => 'Đánh Giá', 'url' => 'reviews.php'],
    'reports' => ['icon' => 'fa fa-exclamation-circle', 'label' => 'Báo Cáo Vi Phạm', 'url' => 'reports.php'],
    'activity_logs' => ['icon' => 'fa fa-history', 'label' => 'Nhật Ký Hoạt Động', 'url' => 'activity_logs.php'],
    'categories' => ['icon' => 'fa fa-list', 'label' => 'Danh Mục', 'url' => 'categories.php'],
    'districts' => ['icon' => 'fa fa-map', 'label' => 'Quận', 'url' => 'districts.php'],
    'utilities' => ['icon' => 'fa fa-wrench', 'label' => 'Tiện Nghi', 'url' => 'utilities.php'],
];
?>

<div class="sidebar">
    <div class="logo">
        <h2>🏠 Admin</h2>
        <p>Quản Lý Phòng Trọ</p>
    </div>
    
    <ul class="nav-menu">
        <?php foreach ($pages as $key => $page): ?>
            <li>
                <a href="<?php echo ADMIN_URL . $page['url']; ?>" 
                   class="<?php echo ($current_page === $key) ? 'active' : ''; ?>">
                    <i class="<?php echo $page['icon']; ?>"></i> 
                    <?php echo $page['label']; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
