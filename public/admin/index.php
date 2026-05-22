<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

function admin_dashboard_scalar(mysqli $conn, string $sql, ?int $value = null): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    if ($value !== null) {
        $stmt->bind_param('i', $value);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    return (int)($row['value'] ?? 0);
}

function admin_dashboard_rows(mysqli $conn, string $sql): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function admin_dashboard_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $exists;
}

function admin_dashboard_activity_class(string $action): string
{
    $action = strtolower($action);
    if (str_contains($action, 'reject') || str_contains($action, 'delete') || str_contains($action, 'failed')) {
        return 'is-danger';
    }
    if (str_contains($action, 'approve') || str_contains($action, 'release') || str_contains($action, 'success')) {
        return 'is-success';
    }
    if (str_contains($action, 'pending') || str_contains($action, 'update')) {
        return 'is-warning';
    }
    return '';
}

$dashboardController = new DashboardController($db);
$data = $dashboardController->index();
$adminId = (int)($_SESSION['user_id'] ?? 0);
$hasPageViews = admin_dashboard_table_exists($conn, 'page_views');
$ownerModeration = new OwnerModeration($db);

$dashboardStats = [
    'owner_pending' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM users WHERE role = 'owner' AND owner_verification_status = 'submitted'"),
    'room_pending' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM motels WHERE status = 'pending'"),
    'report_pending' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM reports WHERE status = 'pending'"),
    'booking_pending' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM bookings WHERE status = 'pending'"),
    'payment_pending' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM payments WHERE payment_status IN ('pending', 'processing') OR status = 'held'"),
    'total_rooms' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM motels"),
    'total_users' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM users"),
    'total_owners' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM users WHERE role = 'owner'"),
    'monthly_revenue' => admin_dashboard_scalar($conn, "SELECT COALESCE(SUM(amount), 0) AS value FROM payments WHERE payment_status = 'paid' AND MONTH(COALESCE(paid_at, created_at)) = MONTH(CURDATE()) AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURDATE())"),
    'total_commission' => admin_dashboard_scalar($conn, "SELECT COALESCE(SUM(amount), 0) AS value FROM transactions WHERE type = 'fee' AND to_user = ?", $adminId),
    'total_reviews' => admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM reviews"),
    'page_views_today' => $hasPageViews ? admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM page_views WHERE DATE(viewed_at) = CURDATE()") : 0,
    'page_views_month' => $hasPageViews ? admin_dashboard_scalar($conn, "SELECT COUNT(*) AS value FROM page_views WHERE MONTH(viewed_at) = MONTH(CURDATE()) AND YEAR(viewed_at) = YEAR(CURDATE())") : 0,
];

$recentActivity = admin_dashboard_rows(
    $conn,
    "SELECT l.action, l.description, l.entity_type, l.entity_id, l.created_at, u.name AS admin_name
     FROM activity_logs l
     LEFT JOIN users u ON u.id = l.admin_id
     ORDER BY l.created_at DESC
     LIMIT 8"
);
$topPages = $hasPageViews
    ? admin_dashboard_rows(
        $conn,
        "SELECT page_url, page_type, COUNT(*) AS total, MAX(viewed_at) AS last_viewed_at
         FROM page_views
         GROUP BY page_url, page_type
         ORDER BY total DESC, last_viewed_at DESC
         LIMIT 8"
    )
    : [];
$ownersToWatch = $ownerModeration->getOwnersToWatch(8);
$topRoomPanels = [
    [
        'title' => 'Top phòng yêu thích',
        'note' => 'Tin có nhiều lượt lưu nhất',
        'rows' => admin_dashboard_rows(
            $conn,
            "SELECT m.id, m.title, m.status, u.name AS owner_name, COUNT(f.id) AS metric
             FROM favorites f
             JOIN motels m ON m.id = f.motel_id
             LEFT JOIN users u ON u.id = m.user_id
             GROUP BY m.id, m.title, m.status, u.name
             ORDER BY metric DESC, m.id DESC
             LIMIT 5"
        ),
    ],
    [
        'title' => 'Top phòng được xem',
        'note' => 'Dựa trên lượt xem tin hiện có',
        'rows' => admin_dashboard_rows(
            $conn,
            "SELECT m.id, m.title, m.status, u.name AS owner_name, m.count_view AS metric
             FROM motels m
             LEFT JOIN users u ON u.id = m.user_id
             WHERE m.count_view > 0
             ORDER BY m.count_view DESC, m.id DESC
             LIMIT 5"
        ),
    ],
    [
        'title' => 'Top phòng booking',
        'note' => 'Tin có nhiều booking nhất',
        'rows' => admin_dashboard_rows(
            $conn,
            "SELECT m.id, m.title, m.status, u.name AS owner_name, COUNT(b.id) AS metric
             FROM bookings b
             JOIN motels m ON m.id = b.motel_id
             LEFT JOIN users u ON u.id = m.user_id
             GROUP BY m.id, m.title, m.status, u.name
             ORDER BY metric DESC, m.id DESC
             LIMIT 5"
        ),
    ],
    [
        'title' => 'Top phòng bị report',
        'note' => 'Tin cần ưu tiên rà soát',
        'rows' => admin_dashboard_rows(
            $conn,
            "SELECT m.id, m.title, m.status, u.name AS owner_name, COUNT(r.id) AS metric
             FROM reports r
             JOIN motels m ON m.id = r.motel_id
             LEFT JOIN users u ON u.id = m.user_id
             GROUP BY m.id, m.title, m.status, u.name
             ORDER BY metric DESC, m.id DESC
             LIMIT 5"
        ),
    ],
];

$statCards = [
    ['key' => 'owner_pending', 'title' => 'Owner chờ duyệt', 'note' => 'Hồ sơ cần xác minh', 'icon' => 'bi-person-check', 'tone' => 'is-amber', 'href' => 'user_approvals.php'],
    ['key' => 'room_pending', 'title' => 'Phòng cần kiểm duyệt', 'note' => 'Tin owner đang chờ', 'icon' => 'bi-buildings', 'tone' => 'is-amber', 'href' => 'motels.php?status=pending'],
    ['key' => 'report_pending', 'title' => 'Báo cáo chưa xử lý', 'note' => 'Phản ánh cần rà soát', 'icon' => 'bi-flag', 'tone' => 'is-red', 'href' => 'reports.php'],
    ['key' => 'booking_pending', 'title' => 'Booking chờ xử lý', 'note' => 'Yêu cầu đặt phòng mới', 'icon' => 'bi-calendar2-week', 'tone' => 'is-cyan', 'href' => 'bookings.php?status=pending'],
    ['key' => 'payment_pending', 'title' => 'Thanh toán cần theo dõi', 'note' => 'Pending, processing hoặc held', 'icon' => 'bi-credit-card', 'tone' => 'is-cyan', 'href' => 'payments.php'],
    ['key' => 'total_rooms', 'title' => 'Tổng phòng trọ', 'note' => 'Tất cả trạng thái tin', 'icon' => 'bi-house-door', 'tone' => 'is-green', 'href' => 'motels.php'],
    ['key' => 'total_users', 'title' => 'Tổng người dùng', 'note' => 'Admin, owner, người thuê', 'icon' => 'bi-people', 'tone' => '', 'href' => 'users.php'],
    ['key' => 'total_owners', 'title' => 'Tổng owner', 'note' => 'Chủ phòng trong hệ thống', 'icon' => 'bi-person-badge', 'tone' => '', 'href' => 'users.php?role=owner'],
    ['key' => 'monthly_revenue', 'title' => 'Doanh thu tháng này', 'note' => 'Tổng thanh toán đã trả', 'icon' => 'bi-cash-coin', 'tone' => 'is-green', 'href' => 'payments.php?status=paid', 'currency' => true],
    ['key' => 'total_commission', 'title' => 'Tổng commission', 'note' => 'Phí hệ thống đã ghi nhận', 'icon' => 'bi-graph-up-arrow', 'tone' => 'is-green', 'href' => 'admin_revenue.php', 'currency' => true],
    ['key' => 'total_reviews', 'title' => 'Đánh giá đã ghi nhận', 'note' => 'Phản hồi từ người thuê', 'icon' => 'bi-star', 'tone' => '', 'href' => 'reviews.php'],
    ['key' => 'page_views_today', 'title' => 'Lượt truy cập hôm nay', 'note' => 'Trang public chính', 'icon' => 'bi-eye', 'tone' => 'is-cyan', 'href' => '#traffic'],
    ['key' => 'page_views_month', 'title' => 'Lượt truy cập tháng này', 'note' => 'Không tính admin/assets/API', 'icon' => 'bi-bar-chart', 'tone' => 'is-cyan', 'href' => '#traffic'],
];

admin_layout_start('Dashboard', 'Theo dõi kiểm duyệt, giao dịch, doanh thu và hoạt động admin bằng dữ liệu hiện tại.', 'index');
admin_flash_messages();
?>

<div
    data-admin-dashboard
    data-stats-endpoint="<?php echo ADMIN_URL; ?>api/dashboard_stats.php"
    data-revenue-endpoint="<?php echo ADMIN_URL; ?>api/revenue_chart.php"
    data-booking-endpoint="<?php echo ADMIN_URL; ?>api/booking_status_chart.php"
    data-room-endpoint="<?php echo ADMIN_URL; ?>api/room_status_chart.php"
    data-page-views-endpoint="<?php echo ADMIN_URL; ?>api/page_views_chart.php"
>
    <div class="admin-stat-grid">
        <?php foreach ($statCards as $card): ?>
            <a class="wb-card admin-stat-card <?php echo admin_e($card['tone']); ?>" href="<?php echo ADMIN_URL . admin_e($card['href']); ?>">
                <span class="admin-stat-icon"><i class="bi <?php echo admin_e($card['icon']); ?>"></i></span>
                <span>
                    <span class="admin-stat-title"><?php echo admin_e($card['title']); ?></span>
                    <span
                        class="admin-stat-value d-block"
                        data-stat-key="<?php echo admin_e($card['key']); ?>"
                        data-target="<?php echo (int)$dashboardStats[$card['key']]; ?>"
                        data-currency="<?php echo !empty($card['currency']) ? '1' : '0'; ?>"
                    ><?php echo !empty($card['currency']) ? admin_money($dashboardStats[$card['key']]) : number_format((int)$dashboardStats[$card['key']], 0, ',', '.'); ?></span>
                    <span class="admin-stat-note d-block"><?php echo admin_e($card['note']); ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="admin-dashboard-grid">
        <section class="wb-card admin-panel admin-chart-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Doanh thu</h2>
                    <p>Thanh toán đã trả và commission theo kỳ</p>
                </div>
                <div class="admin-filter-row">
                    <button type="button" class="btn btn-sm btn-primary" data-revenue-range="7">7 ngày</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-revenue-range="30">30 ngày</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-revenue-range="month">Tháng này</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-revenue-range="year">Năm nay</button>
                </div>
            </div>
            <form class="admin-date-range" data-revenue-custom>
                <input class="form-control form-control-sm" type="date" name="start" aria-label="Từ ngày">
                <input class="form-control form-control-sm" type="date" name="end" aria-label="Đến ngày">
                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-funnel"></i> Khoảng ngày</button>
            </form>
            <div class="admin-chart-loading"><span class="spinner-border spinner-border-sm"></span> Đang tải biểu đồ</div>
            <div id="adminRevenueChart" class="admin-chart" aria-label="Biểu đồ doanh thu"></div>
        </section>

        <section class="wb-card admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Recent activity</h2>
                    <p>Nhật ký quản trị mới nhất</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="<?php echo ADMIN_URL; ?>activity_logs.php">Xem log</a>
            </div>
            <?php if ($recentActivity): ?>
                <ol class="admin-activity-list">
                    <?php foreach ($recentActivity as $activity): ?>
                        <li class="admin-activity-item">
                            <span class="admin-activity-dot <?php echo admin_e(admin_dashboard_activity_class((string)$activity['action'])); ?>"></span>
                            <span>
                                <span class="admin-activity-meta d-block">
                                    <?php echo !empty($activity['created_at']) ? date('d/m/Y H:i', strtotime((string)$activity['created_at'])) : ''; ?>
                                    · <?php echo admin_e($activity['admin_name'] ?? 'Hệ thống'); ?>
                                </span>
                                <span class="admin-activity-text d-block"><?php echo admin_e($activity['description'] ?: str_replace('_', ' ', (string)$activity['action'])); ?></span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <div class="wb-empty">Chưa có nhật ký hoạt động.</div>
            <?php endif; ?>
        </section>
    </div>

    <div class="admin-small-chart-grid">
        <section class="wb-card admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Trạng thái booking</h2>
                    <p>Pending, paid, accepted, completed, rejected, cancelled</p>
                </div>
            </div>
            <div id="adminBookingStatusChart" class="admin-chart is-compact" aria-label="Biểu đồ trạng thái booking"></div>
        </section>
        <section class="wb-card admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Trạng thái phòng</h2>
                    <p>Luồng kiểm duyệt phòng trọ</p>
                </div>
            </div>
            <div id="adminRoomStatusChart" class="admin-chart is-compact" aria-label="Biểu đồ trạng thái phòng"></div>
        </section>
    </div>

    <div id="traffic" class="admin-dashboard-grid">
        <section class="wb-card admin-panel admin-chart-card">
            <div class="admin-panel-head">
                <div>
                    <h2>Traffic website</h2>
                    <p>Lượt xem public được chống lặp theo session trong 10 phút</p>
                </div>
                <div class="admin-filter-row">
                    <button type="button" class="btn btn-sm btn-primary" data-traffic-range="7">7 ngày</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-traffic-range="30">30 ngày</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-traffic-range="month">Tháng này</button>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-traffic-range="year">Năm nay</button>
                </div>
            </div>
            <div class="admin-chart-loading"><span class="spinner-border spinner-border-sm"></span> Đang tải traffic</div>
            <div id="adminPageViewsChart" class="admin-chart is-compact" aria-label="Biểu đồ traffic website"></div>
        </section>
        <section class="wb-card admin-panel">
            <div class="admin-panel-head">
                <div>
                    <h2>Top trang được xem</h2>
                    <p>URL public có lượt xem cao nhất</p>
                </div>
            </div>
            <?php if ($topPages): ?>
                <div class="wb-table-card shadow-none">
                    <table class="wb-table">
                        <thead><tr><th>Trang</th><th>Loại</th><th>Lượt xem</th></tr></thead>
                        <tbody>
                            <?php foreach ($topPages as $pageView): ?>
                                <tr>
                                    <td class="wb-title"><?php echo admin_e($pageView['page_url']); ?></td>
                                    <td><?php echo admin_e($pageView['page_type']); ?></td>
                                    <td><?php echo number_format((int)$pageView['total'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="wb-empty">Chưa có page view hoặc chưa chạy SQL tạo bảng traffic.</div>
            <?php endif; ?>
        </section>
    </div>
</div>

<div class="wb-section-head">
    <h2>Owner cần chú ý</h2>
    <span class="wb-pill">Risk hỗ trợ moderation thủ công</span>
</div>
<div class="wb-table-card mb-3">
    <?php if ($ownersToWatch): ?>
        <table class="wb-table">
            <thead><tr><th>Owner</th><th>Report</th><th>Rejected</th><th>Hidden</th><th>Booking hủy</th><th>Risk</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($ownersToWatch as $ownerRisk): ?>
                    <tr>
                        <td>
                            <div class="wb-title"><?php echo admin_e($ownerRisk['name'] ?? 'Owner'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($ownerRisk['email'] ?? ''); ?></div>
                        </td>
                        <td><?php echo (int)$ownerRisk['total_reports']; ?></td>
                        <td><?php echo (int)$ownerRisk['rejected_rooms']; ?></td>
                        <td><?php echo (int)$ownerRisk['hidden_rooms']; ?></td>
                        <td><?php echo (int)$ownerRisk['cancelled_bookings']; ?></td>
                        <td>
                            <span class="wb-pill <?php echo admin_e(admin_pill_class((string)$ownerRisk['risk_level'])); ?>">
                                <?php echo admin_e(strtoupper((string)$ownerRisk['risk_level'])); ?> · <?php echo (int)$ownerRisk['risk_score']; ?>
                            </span>
                        </td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?php echo ADMIN_URL . 'user_detail.php?id=' . (int)$ownerRisk['id']; ?>">Xem</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có owner có tín hiệu rủi ro từ report, phòng rejected/hidden hoặc booking hủy.</div>
    <?php endif; ?>
</div>

<div class="wb-section-head">
    <h2>Top phòng thống kê</h2>
    <span class="wb-pill">Dữ liệu marketplace hiện tại</span>
</div>
<div class="row g-3 mb-3">
    <?php foreach ($topRoomPanels as $panel): ?>
        <div class="col-xl-6">
            <section class="wb-card admin-panel h-100">
                <div class="admin-panel-head">
                    <div>
                        <h2><?php echo admin_e($panel['title']); ?></h2>
                        <p><?php echo admin_e($panel['note']); ?></p>
                    </div>
                </div>
                <?php if ($panel['rows']): ?>
                    <div class="wb-table-card shadow-none">
                        <table class="wb-table">
                            <thead><tr><th>Phòng</th><th>Owner</th><th>Count</th><th>Trạng thái</th></tr></thead>
                            <tbody>
                                <?php foreach ($panel['rows'] as $topRoom): ?>
                                    <tr>
                                        <td class="wb-title"><a href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . (int)$topRoom['id']; ?>"><?php echo admin_e($topRoom['title'] ?? 'N/A'); ?></a></td>
                                        <td><?php echo admin_e($topRoom['owner_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo number_format((int)($topRoom['metric'] ?? 0), 0, ',', '.'); ?></td>
                                        <td><span class="wb-pill <?php echo admin_pill_class((string)($topRoom['status'] ?? '')); ?>"><?php echo admin_status_label((string)($topRoom['status'] ?? '')); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="wb-empty">Chưa có dữ liệu cho thống kê này.</div>
                <?php endif; ?>
            </section>
        </div>
    <?php endforeach; ?>
</div>

<div class="wb-section-head">
    <h2>Phòng chờ duyệt</h2>
    <a class="btn btn-sm btn-outline-primary" href="<?php echo ADMIN_URL; ?>motels.php?status=pending">Xem tất cả</a>
</div>
<div class="wb-table-card">
    <?php if (!empty($data['recent_motels'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Chủ phòng</th>
                    <th>Giá</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['recent_motels'] as $motel): ?>
                    <?php $motelStatus = (string)($motel['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$motel['id']; ?></td>
                        <td class="wb-title"><?php echo admin_e($motel['title'] ?? 'N/A'); ?></td>
                        <td><?php echo admin_e($motel['owner_name'] ?? 'N/A'); ?></td>
                        <td class="wb-price"><?php echo admin_money($motel['price'] ?? 0); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($motelStatus); ?>"><?php echo admin_status_label($motelStatus); ?></span></td>
                        <td>
                            <div class="admin-table-actions">
                                <a class="btn btn-sm btn-primary" href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . (int)$motel['id']; ?>"><i class="bi bi-eye"></i> Xem</a>
                                <?php if ($motelStatus === 'pending'): ?>
                                    <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" data-admin-confirm="Duyệt phòng đang chờ kiểm duyệt này?">
                                        <?php echo Csrf::field('admin_motel_action'); ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?php echo (int)$motel['id']; ?>">
                                        <button class="btn btn-sm btn-success" type="submit" title="Duyệt"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form method="POST" action="<?php echo ADMIN_URL; ?>motels.php" data-admin-reject="Nhập lý do từ chối tin phòng:">
                                        <?php echo Csrf::field('admin_motel_action'); ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="id" value="<?php echo (int)$motel['id']; ?>">
                                        <input type="hidden" name="rejection_reason" value="">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Từ chối"><i class="bi bi-ban"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Không có phòng đang chờ duyệt.</div>
    <?php endif; ?>
</div>

<div class="wb-section-head">
    <h2>Booking gần đây</h2>
    <a class="btn btn-sm btn-outline-primary" href="<?php echo ADMIN_URL; ?>bookings.php">Quản lý booking</a>
</div>
<div class="wb-table-card">
    <?php if (!empty($data['recent_bookings'])): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Người thuê</th>
                    <th>Phòng</th>
                    <th>Tiền cọc</th>
                    <th>Check-in</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['recent_bookings'] as $booking): ?>
                    <?php $bookingStatus = (string)($booking['status'] ?? 'pending'); ?>
                    <tr>
                        <td>#<?php echo (int)$booking['id']; ?></td>
                        <td><?php echo admin_e($booking['user_name'] ?? 'N/A'); ?></td>
                        <td class="wb-title"><?php echo admin_e($booking['motel_title'] ?? 'N/A'); ?></td>
                        <td class="wb-price"><?php echo admin_money($booking['deposit_amount'] ?? 0); ?></td>
                        <td><?php echo admin_e($booking['checkin_date'] ?? $booking['check_in_date'] ?? ''); ?></td>
                        <td><span class="wb-pill <?php echo admin_pill_class($bookingStatus); ?>"><?php echo admin_status_label($bookingStatus); ?></span></td>
                        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?php echo ADMIN_URL . 'booking_detail.php?id=' . (int)$booking['id']; ?>"><i class="bi bi-eye"></i> Xem</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có booking mới.</div>
    <?php endif; ?>
</div>

<?php
$dashboardScripts = '<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>'
    . '<script src="' . ADMIN_URL . 'assets/js/dashboard.js"></script>';
admin_layout_end($dashboardScripts);
?>
