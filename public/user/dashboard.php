<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userName = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Bạn';

function user_dash_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function user_dash_money($value): string
{
    return number_format((int)$value) . ' VNĐ';
}

function user_dash_table_exists(mysqli $conn, string $table): bool
{
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

function user_dash_column_exists(mysqli $conn, string $table, string $column): bool
{
    $safeTable = $conn->real_escape_string($table);
    $safeColumn = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
    return $result && $result->num_rows > 0;
}

function user_dash_count(mysqli $conn, string $sql, string $types = '', array $params = []): int
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

function user_dash_rows(mysqli $conn, string $sql, string $types = '', array $params = []): array
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

function user_dash_status(string $status): string
{
    return [
        'pending' => 'Chờ xử lý',
        'paid' => 'Đã cọc',
        'accepted' => 'Đã chấp nhận',
        'confirmed' => 'Đã xác nhận',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
        'rejected' => 'Từ chối',
    ][strtolower($status)] ?? ucfirst($status);
}

$hasViewingAppointments = user_dash_table_exists($conn, 'viewing_appointments');
$hasSavedSearches = user_dash_table_exists($conn, 'saved_searches');
$hasHealthScore = user_dash_column_exists($conn, 'motels', 'health_score');
$hasFeatured = user_dash_column_exists($conn, 'motels', 'is_featured');

$stats = [
    'total_bookings' => user_dash_count($conn, 'SELECT COUNT(*) AS count FROM bookings WHERE user_id = ?', 'i', [$userId]),
    'pending_bookings' => user_dash_count($conn, "SELECT COUNT(*) AS count FROM bookings WHERE user_id = ? AND status = 'pending'", 'i', [$userId]),
    'total_favorites' => user_dash_count(
        $conn,
        "SELECT COUNT(*) AS count
         FROM (
             SELECT motel_id FROM favorites WHERE user_id = ?
             UNION
             SELECT motel_id FROM wishlists WHERE user_id = ?
         ) saved
         JOIN motels m ON m.id = saved.motel_id
         WHERE m.status = 'approved'",
        'ii',
        [$userId, $userId]
    ),
    'pending_viewings' => $hasViewingAppointments ? user_dash_count($conn, "SELECT COUNT(*) AS count FROM viewing_appointments WHERE user_id = ? AND status = 'pending'", 'i', [$userId]) : 0,
    'saved_searches' => $hasSavedSearches ? user_dash_count($conn, 'SELECT COUNT(*) AS count FROM saved_searches WHERE user_id = ?', 'i', [$userId]) : 0,
];

$recentBookings = user_dash_rows(
    $conn,
    "SELECT b.*, m.title, m.price, m.address
     FROM bookings b
     JOIN motels m ON b.motel_id = m.id
     WHERE b.user_id = ?
     ORDER BY b.created_at DESC
     LIMIT 5",
    'i',
    [$userId]
);

$recentViewings = $hasViewingAppointments
    ? user_dash_rows(
        $conn,
        "SELECT va.*, m.title, m.address
         FROM viewing_appointments va
         JOIN motels m ON va.motel_id = m.id
         WHERE va.user_id = ?
         ORDER BY va.preferred_time ASC
         LIMIT 4",
        'i',
        [$userId]
    )
    : [];

$healthSelect = $hasHealthScore ? 'm.health_score' : '0 AS health_score';
$orderParts = [];
if ($hasFeatured) {
    $orderParts[] = 'm.is_featured DESC';
}
if ($hasHealthScore) {
    $orderParts[] = 'm.health_score DESC';
}
$orderParts[] = 'm.count_view DESC';
$orderBy = implode(', ', $orderParts);

$featuredMotels = user_dash_rows(
    $conn,
    "SELECT m.id, m.title, m.price, m.address, m.area, {$healthSelect}, d.name AS district_name
     FROM motels m
     LEFT JOIN districts d ON m.district_id = d.id
     WHERE m.status = 'approved'
     ORDER BY {$orderBy}
     LIMIT 6"
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Không gian người thuê - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>
<body class="workbench">
    <?php 
    @require_once __DIR__ . '/../components/PublicNav.php'; 
    qlpt_render_public_nav(['base' => '../', 'active' => 'user']); 
    ?>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <?php
                $userNavActive = 'dashboard';
                $userNavVariant = 'workbench';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </aside>

            <section>
                <div class="wb-hero mb-3">
                    <div>
                        <div class="wb-eyebrow">Không gian tìm phòng</div>
                        <h1>Chào <?php echo user_dash_e($userName); ?></h1>
                        <p>Theo dõi đơn đặt phòng, lịch xem, phòng đã lưu và các gợi ý phù hợp để ra quyết định nhanh hơn.</p>
                    </div>
                    <div class="wb-actions">
                        <a href="search.php" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Tìm phòng</a>
                        <a href="saved-searches.php" class="btn btn-outline-primary"><i class="fas fa-bell"></i> Bộ lọc đã lưu</a>
                    </div>
                </div>

                <div class="wb-grid wb-queue mb-3">
                    <a class="wb-card wb-queue-card" href="my-bookings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['pending_bookings']; ?></div><i class="fas fa-clock wb-card-icon"></i></div>
                        <div class="wb-queue-label">Booking đang chờ</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="my-bookings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['pending_viewings']; ?></div><i class="fas fa-calendar-day wb-card-icon"></i></div>
                        <div class="wb-queue-label">Lịch xem sắp tới</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="saved-motels.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['total_favorites']; ?></div><i class="fas fa-heart wb-card-icon"></i></div>
                        <div class="wb-queue-label">Phòng đã lưu</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="saved-searches.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $stats['saved_searches']; ?></div><i class="fas fa-bell wb-card-icon"></i></div>
                        <div class="wb-queue-label">Bộ lọc theo dõi</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="search.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo count($featuredMotels); ?></div><i class="fas fa-star wb-card-icon"></i></div>
                        <div class="wb-queue-label">Gợi ý đang nổi bật</div>
                    </a>
                </div>

                <div class="wb-grid wb-stats-4">
                    <div class="wb-card"><i class="fas fa-calendar-check wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['total_bookings']; ?></div><div class="wb-card-label">Tổng đơn đặt</div></div>
                    <div class="wb-card"><i class="fas fa-clock wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['pending_bookings']; ?></div><div class="wb-card-label">Đang chờ xử lý</div></div>
                    <div class="wb-card"><i class="fas fa-calendar-day wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['pending_viewings']; ?></div><div class="wb-card-label">Lịch xem chờ</div></div>
                    <div class="wb-card"><i class="fas fa-heart wb-card-icon"></i><div class="wb-card-value"><?php echo $stats['total_favorites']; ?></div><div class="wb-card-label">Phòng đã lưu</div></div>
                </div>

                <div class="wb-section-head">
                    <h2>Lịch xem của bạn</h2>
                    <a href="my-bookings.php" class="btn btn-outline-primary btn-sm">Xem đơn đặt</a>
                </div>
                <div class="wb-list-card">
                    <?php if ($recentViewings): ?>
                        <?php foreach ($recentViewings as $viewing): ?>
                            <div class="wb-list-row">
                                <div>
                                    <div class="wb-title"><?php echo user_dash_e($viewing['title'] ?? 'N/A'); ?></div>
                                    <div class="wb-muted"><?php echo user_dash_e($viewing['address'] ?? ''); ?> · <?php echo date('d/m/Y H:i', strtotime((string)$viewing['preferred_time'])); ?></div>
                                </div>
                                <span class="wb-pill warning"><?php echo user_dash_status((string)($viewing['status'] ?? 'pending')); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="wb-empty">Bạn chưa có lịch xem nào. Hãy lưu phòng hoặc đặt lịch xem khi tìm được phòng phù hợp.</div>
                    <?php endif; ?>
                </div>

                <div class="wb-section-head">
                    <h2>Booking gần đây</h2>
                    <a href="my-bookings.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                <div class="wb-list-card">
                    <?php if ($recentBookings): ?>
                        <?php foreach ($recentBookings as $booking): ?>
                            <div class="wb-list-row">
                                <div>
                                    <div class="wb-title"><?php echo user_dash_e($booking['title'] ?? 'N/A'); ?></div>
                                    <div class="wb-muted"><?php echo user_dash_e($booking['address'] ?? ''); ?> · Check-in <?php echo user_dash_e($booking['checkin_date'] ?? ''); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="wb-price"><?php echo user_dash_money($booking['deposit_amount'] ?? 0); ?></div>
                                    <span class="wb-pill warning mt-2"><?php echo user_dash_status((string)($booking['status'] ?? 'pending')); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="wb-empty">Bạn chưa có đơn đặt phòng nào. Hãy bắt đầu bằng việc tìm phòng phù hợp.</div>
                    <?php endif; ?>
                </div>

                <div class="wb-section-head">
                    <h2>Gợi ý phòng tốt</h2>
                    <a href="search.php?sort=featured" class="btn btn-outline-primary btn-sm">Khám phá thêm</a>
                </div>
                <?php if ($featuredMotels): ?>
                    <div class="wb-room-grid">
                        <?php foreach ($featuredMotels as $motel): ?>
                            <article class="wb-card wb-room-card">
                                <div class="wb-room-photo"></div>
                                <div class="wb-room-body">
                                    <div class="wb-title mb-2"><?php echo user_dash_e($motel['title'] ?? 'N/A'); ?></div>
                                    <div class="wb-price"><?php echo user_dash_money($motel['price'] ?? 0); ?>/tháng</div>
                                    <div class="wb-muted mb-3">
                                        <i class="fas fa-location-dot"></i>
                                        <?php echo user_dash_e($motel['district_name'] ?? $motel['address'] ?? ''); ?>
                                        <?php if (!empty($motel['area'])): ?> · <?php echo (int)$motel['area']; ?> m²<?php endif; ?>
                                    </div>
                                    <a href="motel-detail.php?id=<?php echo (int)$motel['id']; ?>" class="btn btn-primary w-100">Xem chi tiết</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="wb-list-card"><div class="wb-empty">Chưa có phòng được duyệt để gợi ý.</div></div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
