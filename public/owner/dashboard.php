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
$owner_id = (int)$_SESSION['user_id'];
$owner_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Chủ phòng';

$stmt = $db->prepare('SELECT * FROM motels WHERE user_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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

$stats = [];
$stats['total_listings'] = count($listings);
$stats['avg_health_score'] = $stats['total_listings'] > 0 ? round($qualityTotal / $stats['total_listings']) : 0;
$stats['low_quality'] = $lowQualityCount;

$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings b JOIN motels m ON b.motel_id = m.id WHERE m.user_id = ? AND b.status = 'pending'");
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$stats['pending_bookings'] = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$stmt = $db->prepare('SELECT COALESCE(SUM(count_view), 0) as total FROM motels WHERE user_id = ?');
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$stats['total_views'] = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM viewing_appointments WHERE owner_id = ? AND status = 'pending'");
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$stats['pending_viewings'] = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

function motel_status_label(string $status): string
{
    return [
        'pending' => 'Chờ duyệt',
        'approved' => 'Đang hiển thị',
        'hidden' => 'Đã ẩn',
        'rejected' => 'Từ chối',
    ][strtolower($status)] ?? ucfirst($status);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb !important; }
        .app-shell { padding: 28px 0 44px; }
        .app-nav { background: #fff !important; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06) !important; }
        .layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 22px; }
        .side-panel, .hero-panel, .stat-card, .listing-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 18px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .side-panel { padding: 18px; height: fit-content; position: sticky; top: 88px; }
        .side-title { font-weight: 950; color: #101828; margin-bottom: 12px; }
        .side-link { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border-radius: 12px; color: #475467; text-decoration: none; font-weight: 750; }
        .side-link:hover, .side-link.active { background: #f2f4f7; color: #101828; }
        .hero-panel { padding: 26px; display: flex; justify-content: space-between; gap: 18px; align-items: center; background: linear-gradient(135deg, #ffffff, #f0fdf4); }
        .hero-panel h1 { font-size: 30px; font-weight: 950; margin-bottom: 8px; }
        .hero-panel p { color: #667085; margin: 0; }
        .stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; margin: 18px 0; }
        .stat-card { padding: 18px; }
        .stat-card i { color: #0e7490; margin-bottom: 12px; font-size: 22px; }
        .stat-value { font-size: 30px; line-height: 1; font-weight: 950; color: #101828; }
        .stat-label { color: #667085; font-size: 13px; margin-top: 6px; }
        .section-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin: 28px 0 14px; }
        .section-head h2 { font-size: 22px; font-weight: 950; margin: 0; }
        .listing-card { padding: 18px; margin-bottom: 14px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; }
        .listing-title { font-weight: 950; color: #101828; font-size: 18px; }
        .muted { color: #667085; font-size: 14px; }
        .pill { display: inline-flex; padding: 6px 10px; border-radius: 999px; background: #ecfeff; color: #0e7490; font-size: 12px; font-weight: 850; }
        .quality { margin-top: 10px; max-width: 360px; }
        .quality-top { display: flex; justify-content: space-between; color: #475467; font-size: 13px; margin-bottom: 5px; }
        .quality-bar { height: 8px; border-radius: 999px; background: #e5e7eb; overflow: hidden; }
        .quality-fill { height: 100%; border-radius: 999px; background: #0e7490; }
        .price { color: #0e7490; font-size: 20px; font-weight: 950; white-space: nowrap; }
        .empty-state { background:#fff; border:1px solid #e5eaf2; border-radius:18px; padding:34px; text-align:center; color:#667085; }
        @media (max-width: 991px) { .layout, .stats, .listing-card { grid-template-columns: 1fr; } .side-panel { position: static; } .hero-panel { align-items: start; flex-direction: column; } }
    </style>
</head>
<body>
    <nav class="navbar app-nav navbar-expand-lg sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-house-chimney"></i> QuanLyPhongTro</a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="text-muted d-none d-md-inline"><?php echo htmlspecialchars($owner_name); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <main class="app-shell">
        <div class="container-lg layout">
            <aside class="side-panel">
                <div class="side-title">Chủ phòng</div>
                <a class="side-link active" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>
                <a class="side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <?php if (isset($_SESSION['warning'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show">
                        <?php echo htmlspecialchars($_SESSION['warning']); unset($_SESSION['warning']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="hero-panel">
                    <div>
                        <h1>Xin chào, <?php echo htmlspecialchars($owner_name); ?></h1>
                        <p>Theo dõi hiệu quả tin đăng, lịch xem và booking mới của phòng bạn đang cho thuê.</p>
                    </div>
                    <a href="add-listing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Đăng phòng</a>
                </div>

                <div class="stats">
                    <div class="stat-card"><i class="fas fa-house"></i><div class="stat-value"><?php echo $stats['total_listings']; ?></div><div class="stat-label">Tin đăng</div></div>
                    <div class="stat-card"><i class="fas fa-eye"></i><div class="stat-value"><?php echo $stats['total_views']; ?></div><div class="stat-label">Lượt xem</div></div>
                    <div class="stat-card"><i class="fas fa-calendar-day"></i><div class="stat-value"><?php echo $stats['pending_viewings']; ?></div><div class="stat-label">Lịch xem</div></div>
                    <div class="stat-card"><i class="fas fa-inbox"></i><div class="stat-value"><?php echo $stats['pending_bookings']; ?></div><div class="stat-label">Booking chờ</div></div>
                    <div class="stat-card"><i class="fas fa-gauge-high"></i><div class="stat-value"><?php echo $stats['avg_health_score']; ?></div><div class="stat-label">Điểm tin TB</div></div>
                </div>

                <div class="section-head">
                    <h2>Phòng đang quản lý</h2>
                    <a href="listings.php" class="btn btn-outline-primary btn-sm">Xem tất cả</a>
                </div>

                <?php if ($listings): ?>
                    <?php foreach (array_slice($listings, 0, 6) as $listing): ?>
                        <?php
                            $score = (int)($listing['health_score'] ?? 0);
                        ?>
                        <article class="listing-card">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <div class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                                    <span class="pill"><?php echo motel_status_label($listing['status']); ?></span>
                                </div>
                                <div class="muted"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($listing['address']); ?></div>
                                <div class="quality">
                                    <div class="quality-top">
                                        <span>Chất lượng tin</span>
                                        <strong><?php echo $score; ?>/100</strong>
                                    </div>
                                    <div class="quality-bar"><div class="quality-fill" style="width: <?php echo max(0, min(100, $score)); ?>%;"></div></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="price"><?php echo number_format((int)$listing['price']); ?> VND</div>
                                <div class="muted mb-3"><?php echo (int)$listing['count_view']; ?> lượt xem</div>
                                <a href="edit-listing.php?id=<?php echo (int)$listing['id']; ?>" class="btn btn-primary btn-sm">Chỉnh sửa</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <h3 class="fw-bold">Bạn chưa có phòng nào</h3>
                        <p>Đăng phòng đầu tiên để bắt đầu nhận lịch xem và booking.</p>
                        <a href="add-listing.php" class="btn btn-primary">Đăng phòng ngay</a>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

