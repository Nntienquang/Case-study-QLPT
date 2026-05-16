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
        'approved' => 'Đã duyệt',
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
        'held' => 'warning',
        'investigating' => 'warning',
        'hidden' => 'warning',
        'rejected' => 'danger',
        'refunded' => 'danger',
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
        'index' => ['icon' => 'fa fa-dashboard', 'label' => 'Tổng quan', 'url' => 'index.php'],
        'user_approvals' => ['icon' => 'fa fa-user-plus', 'label' => 'Duyệt owner', 'url' => 'user_approvals.php'],
        'motels' => ['icon' => 'fa fa-home', 'label' => 'Phòng trọ', 'url' => 'motels.php'],
        'bookings' => ['icon' => 'fa fa-calendar', 'label' => 'Booking', 'url' => 'bookings.php'],
        'payments' => ['icon' => 'fa fa-credit-card', 'label' => 'Thanh toán', 'url' => 'payments.php'],
        'reports' => ['icon' => 'fa fa-flag', 'label' => 'Báo cáo', 'url' => 'reports.php'],
        'admin_revenue' => ['icon' => 'fa fa-money', 'label' => 'Doanh thu', 'url' => 'admin_revenue.php'],
        'reviews' => ['icon' => 'fa fa-star', 'label' => 'Đánh giá', 'url' => 'reviews.php'],
        'users' => ['icon' => 'fa fa-users', 'label' => 'Người dùng', 'url' => 'users.php'],
        'categories' => ['icon' => 'fa fa-list', 'label' => 'Danh mục', 'url' => 'categories.php'],
        'districts' => ['icon' => 'fa fa-map', 'label' => 'Quận', 'url' => 'districts.php'],
        'utilities' => ['icon' => 'fa fa-wrench', 'label' => 'Tiện nghi', 'url' => 'utilities.php'],
        'activity_logs' => ['icon' => 'fa fa-history', 'label' => 'Nhật ký', 'url' => 'activity_logs.php'],
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
                <?php foreach (admin_nav_items() as $key => $item): ?>
                    <a class="wb-side-link <?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo ADMIN_URL . $item['url']; ?>">
                        <i class="<?php echo admin_e($item['icon']); ?>"></i>
                        <?php echo admin_e($item['label']); ?>
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
