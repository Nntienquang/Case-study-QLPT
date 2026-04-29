<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get stats
$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_bookings = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_favorites = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending_bookings = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// Get recent bookings
$stmt = $db->prepare("
    SELECT b.*, m.title, m.price, m.address
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get featured motels
$stmt = $db->prepare("
    SELECT m.id, m.title, m.price, m.address, m.count_view
    FROM motels m
    WHERE m.status = 'approved'
    ORDER BY m.count_view DESC
    LIMIT 6
");
$stmt->execute();
$featured_motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .navbar-nav .nav-link { color: rgba(255,255,255,0.9) !important; margin-left: 20px; transition: 0.3s; }
        .navbar-nav .nav-link:hover { color: white !important; }
        .sidebar { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); height: fit-content; position: sticky; top: 80px; }
        .sidebar h5 { font-weight: 700; margin-bottom: 20px; color: #333; }
        .sidebar a { display: block; padding: 12px 15px; margin-bottom: 8px; border-radius: 6px; color: #666; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #f0f0f0; color: #667eea; }
        .main-content { padding: 30px; }
        .welcome-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; border-radius: 12px; margin-bottom: 30px; }
        .welcome-card h1 { font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .welcome-card p { font-size: 16px; opacity: 0.9; }
        .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: center; }
        .stat-icon { font-size: 40px; color: #667eea; margin-bottom: 15px; }
        .stat-number { font-size: 32px; font-weight: 700; color: #333; margin-bottom: 5px; }
        .stat-label { font-size: 14px; color: #666; }
        .section-title { font-size: 24px; font-weight: 700; margin-bottom: 25px; color: #333; }
        .booking-item { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .booking-info h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
        .booking-info p { color: #666; font-size: 14px; margin: 5px 0; }
        .booking-status { padding: 8px 15px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-completed { background: #cfe2ff; color: #084298; }
        .motel-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .motel-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .motel-card img { width: 100%; height: 200px; object-fit: cover; }
        .motel-card-body { padding: 20px; }
        .motel-card-title { font-size: 16px; font-weight: 600; margin-bottom: 10px; }
        .motel-card-price { font-size: 20px; font-weight: 700; color: #667eea; margin-bottom: 10px; }
        .motel-card-location { color: #666; font-size: 13px; margin-bottom: 15px; }
        .btn-view { background: linear-gradient(135deg, #667eea, #764ba2); border: none; color: white; padding: 8px 15px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn-view:hover { color: white; }
        .empty-state { text-align: center; padding: 40px; background: white; border-radius: 12px; }
        .empty-state-icon { font-size: 50px; color: #ddd; margin-bottom: 15px; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php">Hồ Sơ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Đăng Xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-lg" style="padding: 30px 0;">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="sidebar">
                    <h5>Menu</h5>
                    <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="search.php"><i class="fas fa-search"></i> Tìm Phòng</a>
                    <a href="my-bookings.php"><i class="fas fa-calendar"></i> Đơn Đặt của Tôi</a>
                    <a href="saved-motels.php"><i class="fas fa-heart"></i> Phòng Yêu Thích</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ Sơ</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Cài Đặt</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="main-content">
                    <!-- Welcome Card -->
                    <div class="welcome-card">
                        <h1>👋 Xin chào, <?php echo htmlspecialchars($user_name); ?>!</h1>
                        <p>Tìm kiếm phòng trọ lý tưởng của bạn ngay hôm nay</p>
                    </div>

                    <!-- Stats -->
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-number"><?php echo $total_bookings; ?></div>
                            <div class="stat-label">Tổng Đơn Đặt</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-hourglass-end"></i></div>
                            <div class="stat-number"><?php echo $pending_bookings; ?></div>
                            <div class="stat-label">Đơn Chờ Xử Lý</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-heart"></i></div>
                            <div class="stat-number"><?php echo $total_favorites; ?></div>
                            <div class="stat-label">Phòng Yêu Thích</div>
                        </div>
                    </div>

                    <!-- Recent Bookings -->
                    <div style="margin-top: 40px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                            <h2 class="section-title" style="margin: 0;">
                                <i class="fas fa-clock"></i> Đơn Đặt Gần Đây
                            </h2>
                            <a href="my-bookings.php" class="btn-view">Xem Tất Cả</a>
                        </div>

                        <?php if (count($recent_bookings) > 0): ?>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <div class="booking-item">
                                    <div class="booking-info" style="flex: 1;">
                                        <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['address']); ?></p>
                                        <p>
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?> - 
                                            <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 16px; font-weight: 700; color: #667eea; margin-bottom: 10px;">
                                            <?php echo number_format($booking['deposit_amount']); ?> VNĐ
                                        </div>
                                        <span class="booking-status badge-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                                <p>Chưa có đơn đặt phòng nào</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Featured Motels -->
                    <div style="margin-top: 40px;">
                        <h2 class="section-title">
                            <i class="fas fa-star"></i> Phòng Nổi Bật
                        </h2>
                        <div class="row">
                            <?php foreach ($featured_motels as $motel): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="motel-card">
                                        <div style="background: linear-gradient(135deg, #667eea, #764ba2); height: 200px; display: flex; align-items: center; justify-content: center; color: white;">
                                            <i class="fas fa-image" style="font-size: 50px;"></i>
                                        </div>
                                        <div class="motel-card-body">
                                            <div class="motel-card-title"><?php echo htmlspecialchars($motel['title']); ?></div>
                                            <div class="motel-card-price"><?php echo number_format($motel['price']); ?> VNĐ</div>
                                            <div class="motel-card-location">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($motel['address']); ?>
                                            </div>
                                            <a href="motel-detail.php?id=<?php echo $motel['id']; ?>" class="btn-view" style="display: inline-block;">
                                                Xem Chi Tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
