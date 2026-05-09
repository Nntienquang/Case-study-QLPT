<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Bạn';

$stmt = $db->prepare('SELECT COUNT(*) as count FROM bookings WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_bookings = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) as count FROM favorites WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_favorites = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$pending_bookings = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM viewing_appointments WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$pending_viewings = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$stmt = $db->prepare("
    SELECT b.*, m.title, m.price, m.address
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("
    SELECT m.id, m.title, m.price, m.address, m.area, m.health_score, d.name AS district_name
    FROM motels m
    LEFT JOIN districts d ON m.district_id = d.id
    WHERE m.status = 'approved'
    ORDER BY m.is_featured DESC, m.health_score DESC, m.count_view DESC
    LIMIT 6
");
$stmt->execute();
$featured_motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function status_label(string $status): string
{
    return [
        'pending' => 'Chờ xử lý',
        'accepted' => 'Đã chấp nhận',
        'confirmed' => 'Đã xác nhận',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
        'rejected' => 'Từ chối',
    ][strtolower($status)] ?? ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb !important; }
        .app-shell { padding: 28px 0 44px; }
        .app-nav { background: #fff !important; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06) !important; }
        .layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 22px; }
        .side-panel, .hero-panel, .stat-card, .content-card, .room-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 18px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .side-panel { padding: 18px; height: fit-content; position: sticky; top: 88px; }
        .side-title { font-weight: 950; color: #101828; margin-bottom: 12px; }
        .side-link { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border-radius: 12px; color: #475467; text-decoration: none; font-weight: 750; }
        .side-link:hover, .side-link.active { background: #f2f4f7; color: #101828; }
        .hero-panel { padding: 26px; display: flex; justify-content: space-between; gap: 18px; align-items: center; background: linear-gradient(135deg, #ffffff, #ecfeff); }
        .hero-panel h1 { font-size: 30px; font-weight: 950; margin-bottom: 8px; }
        .hero-panel p { color: #667085; margin: 0; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; margin: 18px 0; }
        .stat-card { padding: 18px; }
        .stat-card i { color: #0e7490; margin-bottom: 12px; font-size: 22px; }
        .stat-value { font-size: 30px; line-height: 1; font-weight: 950; color: #101828; }
        .stat-label { color: #667085; font-size: 13px; margin-top: 6px; }
        .section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 28px 0 14px; }
        .section-head h2 { font-size: 22px; font-weight: 950; margin: 0; }
        .content-card { padding: 18px; }
        .booking-row { display: flex; justify-content: space-between; gap: 16px; padding: 15px 0; border-bottom: 1px solid #edf0f5; }
        .booking-row:last-child { border-bottom: 0; }
        .booking-title { font-weight: 900; color: #101828; margin-bottom: 4px; }
        .muted { color: #667085; font-size: 14px; }
        .status-pill { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: #fff7ed; color: #9a3412; font-size: 12px; font-weight: 850; }
        .room-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .room-card { overflow: hidden; }
        .room-photo { height: 150px; background: linear-gradient(135deg, rgba(16,24,40,.06), rgba(14,116,144,.16)), url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=82') center/cover; }
        .room-body { padding: 16px; }
        .room-title { min-height: 44px; font-weight: 950; line-height: 1.32; }
        .room-price { color: #0e7490; font-size: 19px; font-weight: 950; margin: 8px 0; }
        .empty-state { padding: 28px; text-align: center; color: #667085; }
        @media (max-width: 991px) { .layout, .stats, .room-grid { grid-template-columns: 1fr; } .side-panel { position: static; } .hero-panel { align-items: start; flex-direction: column; } }
    </style>
</head>
<body>
    <nav class="navbar app-nav navbar-expand-lg sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-house-chimney"></i> QuanLyPhongTro</a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted d-none d-md-inline"><?php echo htmlspecialchars($user_name); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <main class="app-shell">
        <div class="container-lg layout">
            <aside class="side-panel">
                <div class="side-title">Tài khoản</div>
                <a class="side-link active" href="dashboard.php"><i class="fas fa-home"></i> Tổng quan</a>
                <a class="side-link" href="search.php"><i class="fas fa-search"></i> Tìm phòng</a>
                <a class="side-link" href="my-bookings.php"><i class="fas fa-calendar-check"></i> Booking của tôi</a>
                <a class="side-link" href="saved-motels.php"><i class="fas fa-heart"></i> Phòng đã lưu</a>
                <a class="side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <div class="hero-panel">
                    <div>
                        <h1>Chào <?php echo htmlspecialchars($user_name); ?></h1>
                        <p>Theo dõi booking, lịch xem và những phòng phù hợp với nhu cầu của bạn.</p>
                    </div>
                    <a href="search.php" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i> Tìm phòng</a>
                </div>

                <div class="stats">
                    <div class="stat-card"><i class="fas fa-calendar-check"></i><div class="stat-value"><?php echo $total_bookings; ?></div><div class="stat-label">Booking</div></div>
                    <div class="stat-card"><i class="fas fa-clock"></i><div class="stat-value"><?php echo $pending_bookings; ?></div><div class="stat-label">Đang chờ</div></div>
                    <div class="stat-card"><i class="fas fa-calendar-day"></i><div class="stat-value"><?php echo $pending_viewings; ?></div><div class="stat-label">Lịch xem</div></div>
                    <div class="stat-card"><i class="fas fa-heart"></i><div class="stat-value"><?php echo $total_favorites; ?></div><div class="stat-label">Đã lưu</div></div>
                </div>

                <div class="section-head">
                    <h2>Booking gần đây</h2>
                    <a href="my-bookings.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>
                <div class="content-card">
                    <?php if ($recent_bookings): ?>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <div class="booking-row">
                                <div>
                                    <div class="booking-title"><?php echo htmlspecialchars($booking['title']); ?></div>
                                    <div class="muted"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($booking['address']); ?></div>
                                    <?php if (!empty($booking['check_in_date'])): ?>
                                        <div class="muted"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold mb-2"><?php echo number_format((int)($booking['deposit_amount'] ?? 0)); ?> VND</div>
                                    <span class="status-pill"><?php echo status_label($booking['status']); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">Bạn chưa có booking nào. Hãy bắt đầu bằng việc tìm phòng phù hợp.</div>
                    <?php endif; ?>
                </div>

                <div class="section-head">
                    <h2>Gợi ý phòng tốt</h2>
                    <a href="search.php?sort=featured" class="btn btn-outline-primary btn-sm">Khám phá thêm</a>
                </div>
                <div class="room-grid">
                    <?php foreach ($featured_motels as $motel): ?>
                        <article class="room-card">
                            <div class="room-photo"></div>
                            <div class="room-body">
                                <div class="room-title"><?php echo htmlspecialchars($motel['title']); ?></div>
                                <div class="room-price"><?php echo number_format((int)$motel['price']); ?> VND/tháng</div>
                                <div class="muted mb-3"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($motel['district_name'] ?? $motel['address']); ?></div>
                                <a href="motel-detail.php?id=<?php echo (int)$motel['id']; ?>" class="btn btn-primary w-100">Xem chi tiết</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

