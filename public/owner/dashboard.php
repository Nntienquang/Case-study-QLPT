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

require_once __DIR__ . '/_owner_guard.php';

$owner_id = (int)$_SESSION['user_id'];

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
$ownerId = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Chủ phòng';

// --- CÁC HÀM TRỢ GIÚP ---
function owner_dash_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function owner_dash_money($value): string
{
    return number_format((int)$value) . ' VNĐ';
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
    if (!$stmt) return 0;
    if ($types !== '') $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
    $stmt->close();
    return $count;
}

function owner_dash_rows(mysqli $conn, string $sql, string $types = '', array $params = []): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if ($types !== '') $stmt->bind_param($types, ...$params);
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

// --- TRUY VẤN DỮ LIỆU BỔ SUNG ---

// 1. Lấy trạng thái tài khoản và số dư ví
$userData = owner_dash_rows($conn, "SELECT u.status, COALESCE(w.balance, 0) as balance 
                                    FROM users u 
                                    LEFT JOIN wallets w ON u.id = w.user_id 
                                    WHERE u.id = ?", 'i', [$ownerId]);
$accountStatus = $userData[0]['status'] ?? 'pending';
$walletBalance = $userData[0]['balance'] ?? 0;

// 2. Lấy thống kê đánh giá (Rating)
$ratingData = owner_dash_rows($conn, "SELECT COUNT(*) as count, AVG(r.rating) as avg_rating 
                                      FROM reviews r 
                                      JOIN motels m ON r.motel_id = m.id 
                                      WHERE m.user_id = ?", 'i', [$ownerId]);
$avgRating = round((float)($ratingData[0]['avg_rating'] ?? 0), 1);
$totalReviews = (int)($ratingData[0]['count'] ?? 0);

// 3. Lấy danh sách tin đăng và tính điểm chất lượng
$listings = owner_dash_rows($conn, 'SELECT * FROM motels WHERE user_id = ? ORDER BY created_at DESC', 'i', [$ownerId]);
$qualityTotal = 0;
$lowQualityCount = 0;
foreach ($listings as $index => $listing) {
    $quality = ListingQuality::sync($conn, $listing);
    $listings[$index]['health_score'] = $quality['score'];
    $qualityTotal += $quality['score'];
    if ($quality['score'] < 70) $lowQualityCount++;
}

// 4. Tổng hợp Stats
$stats = [
    'total_listings'   => count($listings),
    'avg_health_score' => count($listings) > 0 ? round($qualityTotal / count($listings)) : 0,
    'low_quality'      => $lowQualityCount,
    'pending_bookings' => owner_dash_count($conn, "SELECT COUNT(*) AS count FROM bookings b JOIN motels m ON b.motel_id = m.id WHERE m.user_id = ? AND b.status = 'pending'", 'i', [$ownerId]),
    'total_views'      => owner_dash_count($conn, 'SELECT COALESCE(SUM(count_view), 0) AS count FROM motels WHERE user_id = ?', 'i', [$ownerId]),
    'approved_listings' => owner_dash_count($conn, "SELECT COUNT(*) AS count FROM motels WHERE user_id = ? AND status = 'approved'", 'i', [$ownerId]),
    'wallet_balance'   => $walletBalance,
    'avg_rating'       => $avgRating,
    'total_reviews'    => $totalReviews
];

$hasViewingAppointments = owner_dash_table_exists($conn, 'viewing_appointments');
$stats['pending_viewings'] = $hasViewingAppointments
    ? owner_dash_count($conn, "SELECT COUNT(*) AS count FROM viewing_appointments WHERE owner_id = ? AND status = 'pending'", 'i', [$ownerId])
    : 0;

$recentBookings = owner_dash_rows($conn, "SELECT b.*, m.title AS motel_title, u.name AS tenant_name FROM bookings b JOIN motels m ON b.motel_id = m.id LEFT JOIN users u ON b.user_id = u.id WHERE m.user_id = ? ORDER BY b.created_at DESC LIMIT 5", 'i', [$ownerId]);

$upcomingViewings = $hasViewingAppointments
    ? owner_dash_rows($conn, "SELECT va.*, m.title AS motel_title, u.name AS tenant_name FROM viewing_appointments va JOIN motels m ON va.motel_id = m.id LEFT JOIN users u ON va.user_id = u.id WHERE va.owner_id = ? ORDER BY va.preferred_time ASC LIMIT 5", 'i', [$ownerId])
    : [];
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

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

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <?php if ($accountStatus === 'pending'): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-4">
                        <i class="fas fa-circle-exclamation me-2"></i>
                        <strong>Tài khoản đang chờ duyệt:</strong> Bạn có thể đăng tin nhưng chúng sẽ chưa được hiển thị
                        công khai cho đến khi Admin phê duyệt tài khoản của bạn.
                    </div>
                <?php elseif ($accountStatus === 'rejected'): ?>
                    <div class="alert alert-danger border-0 shadow-sm mb-4">
                        <i class="fas fa-circle-xmark me-2"></i>
                        <strong>Tài khoản bị từ chối:</strong> Vui lòng kiểm tra lại hồ sơ hoặc liên hệ Admin.
                    </div>
                <?php endif; ?>

                <div class="wb-hero owner mb-3">
                    <div>
                        <div class="wb-eyebrow">Không gian làm việc</div>
                        <h1>Xin chào, <?php echo owner_dash_e($ownerName); ?></h1>
                        <p>Theo dõi hiệu quả kinh doanh và quản lý khách thuê hiệu quả.</p>
                    </div>
                    <div class="wb-actions">
                        <a href="add-listing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Đăng phòng</a>
                    </div>
                </div>

                <div class="wb-grid wb-queue mb-3">
                    <a class="wb-card wb-queue-card" href="revenue.php">
                        <div class="wb-queue-top">
                            <div class="wb-queue-value text-success" style="font-size: 1.2rem;">
                                <?php echo owner_dash_money($stats['wallet_balance']); ?>
                            </div>
                            <i class="fas fa-wallet wb-card-icon"></i>
                        </div>
                        <div class="wb-queue-label">Số dư hiện tại</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="bookings.php">
                        <div class="wb-queue-top">
                            <div class="wb-queue-value"><?php echo $stats['pending_bookings']; ?></div><i
                                class="fas fa-inbox wb-card-icon"></i>
                        </div>
                        <div class="wb-queue-label">Booking mới</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="viewing-appointments.php">
                        <div class="wb-queue-top">
                            <div class="wb-queue-value"><?php echo $stats['pending_viewings']; ?></div><i
                                class="fas fa-calendar-day wb-card-icon"></i>
                        </div>
                        <div class="wb-queue-label">Lịch xem mới</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="listings.php">
                        <div class="wb-queue-top">
                            <div class="wb-queue-value text-warning"><?php echo $stats['avg_rating']; ?> <small
                                    style="font-size: 0.8rem">⭐</small></div><i class="fas fa-star wb-card-icon"></i>
                        </div>
                        <div class="wb-queue-label"><?php echo $stats['total_reviews']; ?> đánh giá</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="listings.php">
                        <div class="wb-queue-top">
                            <div class="wb-queue-value text-danger"><?php echo $stats['low_quality']; ?></div><i
                                class="fas fa-triangle-exclamation wb-card-icon text-danger"></i>
                        </div>
                        <div class="wb-queue-label">Tin cần bổ sung</div>
                    </a>
                </div>

                <div class="wb-grid wb-stats-5">
                    <div class="wb-card"><i class="fas fa-house wb-card-icon text-primary"></i>
                        <div class="wb-card-value"><?php echo $stats['total_listings']; ?></div>
                        <div class="wb-card-label">Tổng tin đăng</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-check-circle wb-card-icon text-success"></i>
                        <div class="wb-card-value"><?php echo $stats['approved_listings']; ?></div>
                        <div class="wb-card-label">Đang hiển thị</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-clock wb-card-icon text-warning"></i>
                        <div class="wb-card-value"><?php echo $stats['total_listings'] - $stats['approved_listings']; ?>
                        </div>
                        <div class="wb-card-label">Chờ duyệt / Đã ẩn</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-eye wb-card-icon text-info"></i>
                        <div class="wb-card-value"><?php echo $stats['total_views']; ?></div>
                        <div class="wb-card-label">Tổng lượt xem</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-gauge-high wb-card-icon text-secondary"></i>
                        <div class="wb-card-value"><?php echo $stats['avg_health_score']; ?>%</div>
                        <div class="wb-card-label">Chất lượng tin TB</div>
                    </div>
                </div>

                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <div class="wb-section-head">
                            <h2>Lịch xem sắp tới</h2>
                        </div>
                        <div class="wb-list-card">
                            <?php if ($upcomingViewings): ?>
                                <?php foreach ($upcomingViewings as $viewing): ?>
                                    <div class="wb-list-row">
                                        <div>
                                            <div class="wb-title"><?php echo owner_dash_e($viewing['motel_title'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="wb-muted">
                                                <?php echo owner_dash_e($viewing['tenant_name'] ?? 'Khách'); ?> ·
                                                <?php echo date('H:i d/m', strtotime((string)$viewing['preferred_time'])); ?>
                                            </div>
                                        </div>
                                        <span
                                            class="wb-pill warning"><?php echo owner_dash_booking_status($viewing['status']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="wb-empty">Không có lịch xem.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="wb-section-head">
                            <h2>Booking mới</h2>
                        </div>
                        <div class="wb-list-card">
                            <?php if ($recentBookings): ?>
                                <?php foreach ($recentBookings as $booking): ?>
                                    <div class="wb-list-row">
                                        <div>
                                            <div class="wb-title"><?php echo owner_dash_e($booking['motel_title'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="wb-muted">
                                                <?php echo owner_dash_e($booking['tenant_name'] ?? 'Khách'); ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="wb-price" style="font-size: 0.9rem">
                                                <?php echo owner_dash_money($booking['deposit_amount']); ?></div>
                                            <span class="wb-pill warning mt-1"
                                                style="font-size: 0.7rem"><?php echo owner_dash_booking_status($booking['status']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="wb-empty">Không có booking mới.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="wb-section-head mt-4">
                    <h2>Danh sách phòng nổi bật</h2>
                    <a href="listings.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                <div class="wb-list-card">
                    <?php if ($listings): ?>
                        <?php foreach (array_slice($listings, 0, 3) as $listing): ?>
                            <div class="wb-list-row">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="wb-title"><?php echo owner_dash_e($listing['title']); ?></div>
                                        <span class="wb-pill"><?php echo owner_dash_motel_status($listing['status']); ?></span>
                                    </div>
                                    <div class="wb-muted small mt-1"><i class="fas fa-location-dot me-1"></i>
                                        <?php echo owner_dash_e($listing['address']); ?></div>
                                    <div class="d-flex align-items-center gap-3 mt-2">
                                        <div style="width: 100px" class="wb-progress"><span
                                                style="width: <?php echo $listing['health_score']; ?>%;"></span></div>
                                        <span class="small text-muted">Chất lượng:
                                            <?php echo $listing['health_score']; ?>%</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="wb-price text-primary"><?php echo owner_dash_money($listing['price']); ?></div>
                                    <div class="small text-muted"><?php echo (int)$listing['count_view']; ?> lượt xem</div>
                                    <a href="edit-listing.php?id=<?php echo $listing['id']; ?>"
                                        class="btn btn-sm btn-light border mt-2">Sửa</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
