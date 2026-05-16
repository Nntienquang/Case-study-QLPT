<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';
@require_once '../../core/ListingQuality.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../dashboard.php');
    exit;
}

$db = new Database($conn);
$ownerId = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Chủ phòng';

function owner_dash_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function owner_dash_money($value): string
{
    return number_format((int)$value) . ' VND';
}

function owner_dash_table_exists(mysqli $conn, string $table): bool
{
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

function owner_dash_count(mysqli $conn, string $sql, string $types = '', array $params = []): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
    $stmt->close();
    return $count;
}

function owner_dash_rows(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function owner_dash_motel_status(string $status): string
{
    return [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đang hiển thị',
        'hidden' => 'Đã ẩn',
        'rejected' => 'Từ chối',
    ][strtolower($status)] ?? ucfirst($status);
}

function owner_dash_booking_status(string $status): string
{
    return [
        'pending' => 'Chờ phản hồi',
        'paid' => 'Đã cọc',
        'accepted' => 'Đã nhận',
        'completed' => 'Hoàn tất',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy',
    ][strtolower($status)] ?? ucfirst($status);
}

$listings = owner_dash_rows($conn, 'SELECT * FROM motels WHERE user_id = ? ORDER BY created_at DESC', 'i', [$ownerId]);

$qualityTotal = 0;
$lowQualityCount = 0;
foreach ($listings as $index => $listing) {
    $quality = ListingQuality::sync($conn, $listing);
    $listings[$index]['health_score'] = $quality['score'];
    $qualityTotal += $quality['score'];
    if ($quality['score'] < 70) {
        $lowQualityCount++;
    }
}

$stats = [
    'total_listings' => count($listings),
    'avg_health_score' => count($listings) > 0 ? round($qualityTotal / count($listings)) : 0,
    'low_quality' => $lowQualityCount,
    'pending_bookings' => owner_dash_count($conn, "SELECT COUNT(*) AS count FROM bookings b JOIN motels m ON b.motel_id = m.id WHERE m.user_id = ? AND b.status = 'pending'", 'i', [$ownerId]),
    'total_views' => owner_dash_count($conn, 'SELECT COALESCE(SUM(count_view), 0) AS count FROM motels WHERE user_id = ?', 'i', [$ownerId]),
    'approved_listings' => owner_dash_count($conn, "SELECT COUNT(*) AS count FROM motels WHERE user_id = ? AND status = 'approved'", 'i', [$ownerId]),
];

$hasViewingAppointments = owner_dash_table_exists($conn, 'viewing_appointments');
$stats['pending_viewings'] = $hasViewingAppointments
    ? owner_dash_count($conn, "SELECT COUNT(*) AS count FROM viewing_appointments WHERE owner_id = ? AND status = 'pending'", 'i', [$ownerId])
    : 0;

$recentBookings = owner_dash_rows(
    $conn,
    "SELECT b.*, m.title AS motel_title, u.name AS tenant_name, u.email AS tenant_email
     FROM bookings b
     JOIN motels m ON b.motel_id = m.id
     LEFT JOIN users u ON b.user_id = u.id
     WHERE m.user_id = ?
     ORDER BY b.created_at DESC
     LIMIT 5",
    'i',
    [$ownerId]
);

$upcomingViewings = $hasViewingAppointments
    ? owner_dash_rows(
        $conn,
        "SELECT va.*, m.title AS motel_title, u.name AS tenant_name
         FROM viewing_appointments va
         JOIN motels m ON va.motel_id = m.id
         LEFT JOIN users u ON va.user_id = u.id
         WHERE va.owner_id = ?
         ORDER BY va.preferred_time ASC
         LIMIT 5",
        'i',
        [$ownerId]
    )
    : [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không gian chủ phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>
<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
                <span><?php echo owner_dash_e($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link active" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <?php echo owner_dash_e($_SESSION['warning']); unset($_SESSION['warning']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="wb-hero owner mb-3">
                    <div>
                        <div class="wb-eyebrow">Không gian làm việc</div>
                        <h1>Xin chào, <?php echo owner_dash_e($ownerName); ?></h1>
                        <p>Quản lý tin đăng, phản hồi lịch xem và theo dõi booking mới của các phòng bạn đang cho thuê.</p>
                    </div>
                    <div class="wb-actions">
                        <a href="add-listing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Đăng phòng</a>
                        <a href="viewing-appointments.php" class="btn btn-outline-primary"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                    </div>
                </div>

                <div class="wb-grid wb-queue mb-3">
                    <a class="wb-card wb-queue-card" href="bookings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['pending_bookings']; ?></div><i class="fas fa-inbox wb-card-icon"></i></div>
                        <div class="wb-queue-label">Booking chờ phản hồi</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="viewing-appointments.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['pending_viewings']; ?></div><i class="fas fa-calendar-day wb-card-icon"></i></div>
                        <div class="wb-queue-label">Lịch xem cần xác nhận</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="listings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['low_quality']; ?></div><i class="fas fa-gauge-high wb-card-icon"></i></div>
                        <div class="wb-queue-label">Tin cần bổ sung thông tin</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="listings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['approved_listings']; ?></div><i class="fas fa-check-circle wb-card-icon"></i></div>
                        <div class="wb-queue-label">Tin đang hiển thị</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="listings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['total_views']; ?></div><i class="fas fa-eye wb-card-icon"></i></div>
                        <div class="wb-queue-label">Tổng lượt xem</div>
                    </a>
                </div>

                <div class="wb-grid wb-stats-5">
                    <div class="wb-card"><i class="fas fa-house wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['total_listings']; ?></div><div class="wb-card-label">Tin đăng</div></div>
                    <div class="wb-card"><i class="fas fa-eye wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['total_views']; ?></div><div class="wb-card-label">Lượt xem</div></div>
                    <div class="wb-card"><i class="fas fa-calendar-day wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['pending_viewings']; ?></div><div class="wb-card-label">Lịch xem chờ</div></div>
                    <div class="wb-card"><i class="fas fa-calendar-check wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['pending_bookings']; ?></div><div class="wb-card-label">Booking chờ</div></div>
                    <div class="wb-card"><i class="fas fa-gauge-high wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['avg_health_score']; ?></div><div class="wb-card-label">Điểm tin trung bình</div></div>
                </div>

                <div class="wb-section-head">
                    <h2>Lịch xem sắp tới</h2>
                    <a href="viewing-appointments.php" class="btn btn-outline-primary btn-sm">Quản lý lịch xem</a>
                </div>
                <div class="wb-list-card">
                    <?php if ($upcomingViewings): ?>
                        <?php foreach ($upcomingViewings as $viewing): ?>
                            <div class="wb-list-row">
                                <div>
                                    <div class="wb-title"><?php echo owner_dash_e($viewing['motel_title'] ?? 'N/A'); ?></div>
                                    <div class="wb-muted"><?php echo owner_dash_e($viewing['tenant_name'] ?? 'Khách thuê'); ?> · <?php echo date('d/m/Y H:i', strtotime((string)$viewing['preferred_time'])); ?></div>
                                </div>
                                <span class="wb-pill warning"><?php echo owner_dash_booking_status((string)($viewing['status'] ?? 'pending')); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="wb-empty">Chưa có lịch xem mới.</div>
                    <?php endif; ?>
                </div>

                <div class="wb-section-head">
                    <h2>Booking mới</h2>
                    <a href="bookings.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                <div class="wb-list-card">
                    <?php if ($recentBookings): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <div class="wb-list-row">
                                <div>
                                    <div class="wb-title"><?php echo owner_dash_e($booking['motel_title'] ?? 'N/A'); ?></div>
                                    <div class="wb-muted"><?php echo owner_dash_e($booking['tenant_name'] ?? 'Khách thuê'); ?> · Check-in <?php echo owner_dash_e($booking['checkin_date'] ?? ''); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="wb-price"><?php echo owner_dash_money($booking['deposit_amount'] ?? 0); ?></div>
                                    <span class="wb-pill warning mt-2"><?php echo owner_dash_booking_status((string)($booking['status'] ?? 'pending')); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="wb-empty">Chưa có booking mới.</div>
                    <?php endif; ?>
                </div>

                <div class="wb-section-head">
                    <h2>Phòng đang quản lý</h2>
                    <a href="listings.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                <div class="wb-list-card">
                    <?php if ($listings): ?>
                        <?php foreach (array_slice($listings, 0, 6) as $listing): ?>
                            <?php $score = (int)($listing['health_score'] ?? 0); ?>
                            <div class="wb-list-row">
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <div class="wb-title"><?php echo owner_dash_e($listing['title'] ?? 'N/A'); ?></div>
                                        <span class="wb-pill"><?php echo owner_dash_motel_status((string)($listing['status'] ?? 'pending')); ?></span>
                                    </div>
                                    <div class="wb-muted mt-1"><?php echo owner_dash_e($listing['address'] ?? 'Chưa có địa chỉ'); ?></div>
                                    <div class="wb-progress"><span style="width: <?php echo max(0, min(100, $score)); ?>%;"></span></div>
                                    <div class="wb-muted mt-1">Chất lượng tin: <?php echo $score; ?>/100</div>
                                </div>
                                <div class="text-end">
                                    <div class="wb-price"><?php echo owner_dash_money($listing['price'] ?? 0); ?></div>
                                    <div class="wb-muted mb-2"><?php echo (int)($listing['count_view'] ?? 0); ?> lượt xem</div>
                                    <a href="edit-listing.php?id=<?php echo (int)$listing['id']; ?>" class="btn btn-sm btn-primary">Chỉnh sửa</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="wb-empty">
                            <div class="fw-bold mb-2">Bạn chưa có phòng nào</div>
                            <a href="add-listing.php" class="btn btn-primary">Đăng phòng đầu tiên</a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
