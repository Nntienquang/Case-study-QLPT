<?php

function admin_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_money($value): string
{
    return number_format((int)$value, 0, ',', '.') . ' VNĐ';
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
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical',
        'reminder' => 'Nhắc nhở',
        'warning' => 'Cảnh cáo',
        'severe_warning' => 'Cảnh cáo nghiêm trọng',
        'posting_suspended' => 'Tạm khóa đăng phòng',
        'visible' => 'Hiển thị',
    ][strtolower($status)] ?? ucfirst($status);
}

function admin_pill_class(string $status): string
{
    return [
        'approved' => 'success',
        'accepted' => 'success',
        'confirmed' => 'success',
        'paid' => 'success',
        'released' => 'success',
        'completed' => 'success',
        'resolved' => 'success',
        'pending' => 'warning',
        'pending_verification' => 'warning',
        'submitted' => 'warning',
        'waiting_payment' => 'warning',
        'processing' => 'info',
        'held' => 'info',
        'investigating' => 'info',
        'hidden' => 'muted',
        'blocked' => 'muted',
        'rejected' => 'danger',
        'refunded' => 'danger',
        'failed' => 'danger',
        'cancelled' => 'danger',
        'closed' => 'danger',
        'low' => 'success',
        'medium' => 'warning',
        'high' => 'danger',
        'critical' => 'danger',
        'reminder' => 'info',
        'warning' => 'warning',
        'severe_warning' => 'danger',
        'posting_suspended' => 'muted',
        'visible' => 'success',
    ][strtolower($status)] ?? 'warning';
}

function admin_current_name(): string
{
    return (string)($_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Admin');
}

function admin_nav_items(): array
{
    return [
        'index' => ['icon' => 'bi bi-grid-1x2', 'label' => 'Dashboard', 'url' => 'index.php', 'group' => 'operations'],
        'user_approvals' => ['icon' => 'bi bi-person-check', 'label' => 'Duyệt owner', 'url' => 'user_approvals.php', 'group' => 'operations'],
        'motels' => ['icon' => 'bi bi-buildings', 'label' => 'Kiểm duyệt phòng', 'url' => 'motels.php', 'group' => 'operations'],
        'bookings' => ['icon' => 'bi bi-calendar-check', 'label' => 'Booking', 'url' => 'bookings.php', 'group' => 'operations'],
        'payments' => ['icon' => 'bi bi-credit-card', 'label' => 'Thanh toán', 'url' => 'payments.php', 'group' => 'operations'],
        'withdraw_requests' => ['icon' => 'bi bi-bank', 'label' => 'Rút tiền', 'url' => 'withdraw_requests.php', 'group' => 'operations'],
        'admin_revenue' => ['icon' => 'bi bi-graph-up-arrow', 'label' => 'Doanh thu', 'url' => 'admin_revenue.php', 'group' => 'operations'],
        'reports' => ['icon' => 'bi bi-flag', 'label' => 'Báo cáo vi phạm', 'url' => 'reports.php', 'group' => 'operations'],
        'reviews' => ['icon' => 'bi bi-star', 'label' => 'Đánh giá', 'url' => 'reviews.php', 'group' => 'operations'],
        'users' => ['icon' => 'bi bi-people', 'label' => 'Người dùng', 'url' => 'users.php', 'group' => 'operations'],
        'activity_logs' => ['icon' => 'bi bi-clock-history', 'label' => 'Nhật ký hệ thống', 'url' => 'activity_logs.php', 'group' => 'operations'],
        'categories' => ['icon' => 'bi bi-tags', 'label' => 'Danh mục phòng', 'url' => 'categories.php', 'group' => 'settings'],
        'utilities' => ['icon' => 'bi bi-sliders', 'label' => 'Tiện nghi', 'url' => 'utilities.php', 'group' => 'settings'],
    ];
}

function admin_layout_start(string $title, string $subtitle = '', ?string $active = null): void
{
    $active ??= basename($_SERVER['PHP_SELF'], '.php');
    $initial = strtoupper(substr(admin_current_name(), 0, 1));
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo admin_e($title); ?> - QuanLyPhongTro Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <link href="<?php echo ADMIN_URL; ?>assets/css/admin.css" rel="stylesheet">
</head>
<body class="workbench admin-body">
    <header class="wb-topbar admin-topbar">
        <div class="container-fluid wb-topbar-inner">
            <button class="btn admin-icon-button" type="button" data-admin-sidebar-toggle aria-label="Thu gọn thanh điều hướng">
                <i class="bi bi-list"></i>
            </button>
            <a class="wb-brand" href="<?php echo ADMIN_URL; ?>index.php">
                <span class="wb-brand-mark"><i class="bi bi-house-gear"></i></span>
                <span>QuanLyPhongTro Admin</span>
            </a>
            <form class="admin-search" method="GET" action="<?php echo ADMIN_URL; ?>users.php">
                <label class="visually-hidden" for="admin-top-search">Tìm người dùng</label>
                <i class="bi bi-search"></i>
                <input id="admin-top-search" type="search" name="search" placeholder="Tìm người dùng">
            </form>
            <div class="wb-user admin-user-tools">
                <a class="btn admin-icon-button" href="<?php echo BASE_URL; ?>notifications.php" aria-label="Thông báo"><i class="bi bi-bell"></i></a>
                <div class="dropdown">
                    <button class="btn admin-account" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="admin-avatar"><?php echo admin_e($initial !== '' ? $initial : 'A'); ?></span>
                        <span class="admin-account-name"><?php echo admin_e(admin_current_name()); ?></span>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end admin-account-menu">
                        <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>users.php?search=<?php echo urlencode((string)($_SESSION['user_email'] ?? admin_current_name())); ?>"><i class="bi bi-person"></i> Hồ sơ</a></li>
                        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>change-password.php"><i class="bi bi-key"></i> Đổi mật khẩu</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo ADMIN_URL; ?>logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
    <main class="wb-shell">
        <div class="container-fluid wb-layout">
            <aside class="wb-sidebar" data-admin-sidebar>
                <div class="wb-side-title">Vận hành hệ thống</div>
                <?php
                $items = admin_nav_items();
                $currentGroup = null;
                foreach ($items as $key => $item):
                    $itemGroup = $item['group'] ?? '';
                    if ($itemGroup !== $currentGroup && $currentGroup !== null): ?>
                        <div class="admin-side-divider"></div>
                        <div class="wb-side-title">Cấu hình</div>
                    <?php
                    endif;
                    $currentGroup = $itemGroup;
                    ?>
                    <a class="wb-side-link <?php echo $active === $key ? 'active' : ''; ?>" href="<?php echo ADMIN_URL . $item['url']; ?>" title="<?php echo admin_e($item['label']); ?>">
                        <i class="<?php echo admin_e($item['icon']); ?>"></i>
                        <span><?php echo admin_e($item['label']); ?></span>
                    </a>
                <?php endforeach; ?>
            </aside>
            <section class="admin-content">
                <div class="admin-page-head">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?php echo ADMIN_URL; ?>index.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo $active === 'index' ? 'Dashboard' : admin_e($title); ?></li>
                        </ol>
                    </nav>
                    <h1><?php echo admin_e($title); ?></h1>
                    <?php if ($subtitle !== ''): ?>
                        <p><?php echo admin_e($subtitle); ?></p>
                    <?php endif; ?>
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
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show admin-alert" role="alert">
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
    <div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-labelledby="adminConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="adminConfirmTitle">Xác nhận thao tác</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body" data-admin-confirm-body>Thao tác này sẽ thay đổi dữ liệu hiện tại.</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger" data-admin-confirm-submit>Xác nhận</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ADMIN_URL; ?>assets/js/admin.js"></script>
    <?php echo $extraScript; ?>
</body>
</html>
<?php
}
