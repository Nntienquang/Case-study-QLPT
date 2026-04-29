<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';

session_start();

/** @var mysqli $conn */
// Check if logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect admins to admin panel
if ($_SESSION['role'] === 'admin') {
    header('Location: ./admin/index.php');
    exit;
}

// Redirect owners to owner dashboard
if ($_SESSION['role'] === 'owner') {
    header('Location: ./owner/dashboard.php');
    exit;
}

$db = new Database($conn);
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Get user's favorite listings
$stmt = $db->prepare("
    SELECT m.id, m.title, m.price, m.address, m.count_view
    FROM favorites f
    JOIN motels m ON f.motel_id = m.id
    WHERE f.user_id = ?
    ORDER BY f.id DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get user's bookings
$stmt = $db->prepare("
    SELECT b.id, b.status, m.title, m.price, b.checkin_date
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count stats
$stats = [];
$stats['favorites'] = count($favorites);
$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['bookings'] = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-size: 22px;
            font-weight: 700;
            color: white !important;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin-left: 20px;
            transition: 0.3s;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
        }
        
        .sidebar {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 80px;
        }
        
        .sidebar h5 {
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
        }
        
        .sidebar a {
            display: block;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            color: #666;
            text-decoration: none;
            transition: 0.3s;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: #f0f0f0;
            color: #667eea;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .welcome-card h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #333;
        }
        
        .listing-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        
        .listing-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .listing-info h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .listing-info p {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .listing-price {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-state-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state p {
            color: #999;
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="listings.php">Duyệt Phòng</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php">Hồ Sơ</a></li>
                            <li><a class="dropdown-item" href="settings.php">Cài Đặt</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Đăng Xuất</a></li>
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
                    <a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i> Dashboard</a>
                    <a href="listings.php"><i class="fas fa-list"></i> Duyệt Phòng</a>
                    <a href="favorites.php"><i class="fas fa-heart"></i> Yêu Thích</a>
                    <a href="bookings.php"><i class="fas fa-calendar"></i> Đơn Đặt Phòng</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ Sơ</a>
                    <a href="settings.php"><i class="fas fa-cog"></i> Cài Đặt</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="main-content">
                    <!-- Welcome Card -->
                    <div class="welcome-card">
                        <h1>👋 Xin Chào, <?php echo htmlspecialchars($user_name); ?>!</h1>
                        <p>Chào mừng bạn quay lại. Tiếp tục tìm kiếm phòng trọ hoàn hảo của bạn.</p>
                    </div>
                    
                    <!-- Stats -->
                    <div class="stats-container">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-heart"></i></div>
                            <div class="stat-number"><?php echo $stats['favorites']; ?></div>
                            <div class="stat-label">Phòng Yêu Thích</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-number"><?php echo $stats['bookings']; ?></div>
                            <div class="stat-label">Đơn Đặt Phòng</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fas fa-bell"></i></div>
                            <div class="stat-number">0</div>
                            <div class="stat-label">Thông Báo Mới</div>
                        </div>
                    </div>
                    
                    <!-- Favorites Section -->
                    <h2 class="section-title">
                        <i class="fas fa-heart"></i> Phòng Yêu Thích Gần Đây
                    </h2>
                    
                    <?php if (count($favorites) > 0): ?>
                        <?php foreach ($favorites as $fav): ?>
                            <div class="listing-card">
                                <div class="listing-info">
                                    <h3><?php echo htmlspecialchars($fav['title']); ?></h3>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($fav['address']); ?></p>
                                    <p><i class="fas fa-eye"></i> Lượt xem: <?php echo $fav['count_view']; ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <div class="listing-price"><?php echo number_format($fav['price']); ?> VNĐ</div>
                                    <button class="btn-primary" style="margin-top: 10px;">Xem Chi Tiết</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-heart"></i></div>
                            <p>Bạn chưa lưu phòng nào</p>
                            <a href="listings.php" class="btn-primary">Duyệt Phòng Ngay</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Bookings Section -->
                    <h2 class="section-title" style="margin-top: 40px;">
                        <i class="fas fa-calendar-check"></i> Đơn Đặt Phòng Của Tôi
                    </h2>
                    
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="listing-card">
                                <div class="listing-info">
                                    <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                                    <p><i class="fas fa-calendar"></i> Nhận phòng: <?php echo date('d/m/Y', strtotime($booking['checkin_date'])); ?></p>
                                    <p>
                                        <span class="badge-status badge-<?php echo strtolower($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div style="text-align: right;">
                                    <div class="listing-price"><?php echo number_format($booking['price']); ?> VNĐ</div>
                                    <button class="btn-primary" style="margin-top: 10px;">Chi Tiết</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fas fa-calendar"></i></div>
                            <p>Bạn chưa có đơn đặt phòng nào</p>
                            <a href="listings.php" class="btn-primary">Tìm Phòng Ngay</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
