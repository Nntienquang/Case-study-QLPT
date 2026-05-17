<?php

function admin_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_money($value): string
{
    return number_format((int)$value) . ' VNĐ';
}

function admin_status_label(string $status): string
{
    return [
        'pending' => 'Chờ xử lý',
        'pending_verification' => 'Chưa gửi hồ sơ',
        'submitted' => 'Chờ duyệt hồ sơ',
        'processing' => 'Đang xử lý',
        'failed' => 'Thất bại',
        'approved' => 'Đã duyệt',
        'confirmed' => 'Đã xác nhận',
        'waiting_payment' => 'Chờ thanh toán',
        'accepted' => 'Đã nhận',
        'paid' => 'Đã cọc',
        'held' => 'Đang giữ',
        'released' => 'Đã giải ngân',
        'refunded' => 'Hoàn tiền',
        'completed' => 'Hoàn tất',
        'hidden' => 'Đã ẩn',
        'investigating' => 'Đang xác minh',
        'resolved' => 'Đã xử lý',
        'closed' => 'Đã đóng',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy',
        'blocked' => 'Bị khóa',
        'admin' => 'Admin',
        'owner' => 'Chủ phòng',
        'user' => 'Người thuê',
    ][strtolower($status)] ?? ucfirst($status);
}

function admin_pill_class(string $status): string
{
    return [
        'approved' => '',
        'accepted' => '',
        'paid' => '',
        'released' => '',
        'completed' => '',
        'resolved' => '',
        'pending' => 'warning',
        'pending_verification' => 'warning',
        'submitted' => 'warning',
        'processing' => 'warning',
        'waiting_payment' => 'warning',
        'held' => 'warning',
        'investigating' => 'warning',
        'hidden' => 'warning',
        'rejected' => 'danger',
        'refunded' => 'danger',
        'failed' => 'danger',
        'cancelled' => 'danger',
        'closed' => 'danger',
        'blocked' => 'danger',
    ][strtolower($status)] ?? 'warning';
}

function admin_current_name(): string
{
    return $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Admin';
}

function admin_nav_items(): array
{
    return [
        // Tổng quan
        'index' => ['icon' => 'fa fa-dashboard', 'label' => 'Tổng quan', 'url' => 'index.php', 'group' => 'overview'],
        
        // Quản lý phê duyệt
        'user_approvals' => ['icon' => 'fa fa-user-check', 'label' => 'Duyệt owner', 'url' => 'user_approvals.php', 'group' => 'approval'],
        
        // Quản lý nội dung
        'motels' => ['icon' => 'fa fa-building', 'label' => 'Phòng trọ', 'url' => 'motels.php', 'group' => 'content'],
        'categories' => ['icon' => 'fa fa-list', 'label' => 'Danh mục', 'url' => 'categories.php', 'group' => 'content'],
        
        // Quản lý giao dịch
        'bookings' => ['icon' => 'fa fa-calendar', 'label' => 'Booking', 'url' => 'bookings.php', 'group' => 'transaction'],
        'payments' => ['icon' => 'fa fa-credit-card', 'label' => 'Thanh toán', 'url' => 'payments.php', 'group' => 'transaction'],
        'withdraw_requests' => ['icon' => 'fa fa-bank', 'label' => 'Rút tiền', 'url' => 'withdraw_requests.php', 'group' => 'transaction'],
        'admin_revenue' => ['icon' => 'fa fa-bar-chart', 'label' => 'Doanh thu', 'url' => 'admin_revenue.php', 'group' => 'transaction'],
        
        // Quản lý cộng đồng
        'reviews' => ['icon' => 'fa fa-star', 'label' => 'Đánh giá', 'url' => 'reviews.php', 'group' => 'community'],
        'reports' => ['icon' => 'fa fa-flag', 'label' => 'Báo cáo vi phạm', 'url' => 'reports.php', 'group' => 'community'],
        'users' => ['icon' => 'fa fa-users', 'label' => 'Người dùng', 'url' => 'users.php', 'group' => 'community'],
        
        // Nhật ký hệ thống
        'activity_logs' => ['icon' => 'fa fa-history', 'label' => 'Nhật ký hệ thống', 'url' => 'activity_logs.php', 'group' => 'logs'],
    ];
}

function admin_layout_start(string $title, string $subtitle = '', ?string $active = null): void
{
    $active ??= basename($_SERVER['PHP_SELF'], '.php');
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo admin_e($title); ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>
<body class="workbench">
    <header class="wb-topbar">
        <div class="container-fluid px-4 wb-topbar-inner">
            <a class="wb-brand" href="<?php echo ADMIN_URL; ?>index.php">
                <span class="wb-brand-mark"><i class="fa fa-shield"></i></span>
                <span>QuanLyPhongTro Admin</span>
            </a>
            <div class="wb-user">
                <span><?php echo admin_e(admin_current_name()); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo ADMIN_URL; ?>logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>
    <main class="wb-shell">
        <div class="container-fluid px-4 wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Vận hành hệ thống</div>
                <?php 
                    $items = admin_nav_items();
                    $current_group = null;
                    
                    foreach ($items as $key => $item): 
                        $item_group = $item['group'] ?? '';
                        
                        // Render group header nếu group thay đổi
                        if ($item_group !== $current_group && !empty($item_group)):
                            if ($current_group !== null): ?>
                                <div style="height: 1px; background: #e0e0e0; margin: 8px 0;"></div>
                            <?php endif;
                            $current_group = $item_group;
                        endif;
                ?>
                    <a class="wb-side-link <?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo ADMIN_URL . $item['url']; ?>" title="<?php echo admin_e($item['label']); ?>">
                        <i class="<?php echo admin_e($item['icon']); ?>"></i>
                        <span><?php echo admin_e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </aside>
            <section>
                <div class="wb-hero admin mb-3">
                    <div>
                        <div class="wb-eyebrow">Admin workspace</div>
                        <h1><?php echo admin_e($title); ?></h1>
                        <?php if ($subtitle !== ''): ?>
                            <p><?php echo admin_e($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
<?php
}

function admin_flash_messages(): void
{
    foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $type) {
        if (!isset($_SESSION[$key])) {
            continue;
        }
        ?>
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show" role="alert">
            <?php echo admin_e($_SESSION[$key]); unset($_SESSION[$key]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php
    }
}

function admin_layout_end(string $extraScript = ''): void
{
    ?>
            </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php echo $extraScript; ?>
</body>
</html>
<?php
}
