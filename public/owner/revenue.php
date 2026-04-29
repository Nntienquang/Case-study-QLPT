<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$owner_id = $_SESSION['user_id'];

// Get revenue stats
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN b.deposit_amount ELSE 0 END) as total_revenue,
        COUNT(DISTINCT CASE WHEN b.status = 'pending' THEN b.id END) as pending_bookings,
        COUNT(DISTINCT m.id) as total_listings
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    WHERE m.user_id = ?
");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get recent transactions
$stmt = $db->prepare("
    SELECT b.*, m.title
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    WHERE m.user_id = ? AND b.status = 'completed'
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doanh Thu - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .sidebar { background: white; padding: 30px; border-radius: 12px; }
        .sidebar a { display: block; padding: 12px 15px; margin-bottom: 8px; border-radius: 6px; color: #666; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #f0f0f0; color: #667eea; }
        .main-content { padding: 30px; }
        .stat-card { background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stat-icon { font-size: 40px; color: #667eea; margin-bottom: 15px; }
        .stat-number { font-size: 32px; font-weight: 700; color: #333; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }
        .transaction-card { background: white; padding: 15px; border-radius: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .transaction-info { flex: 1; }
        .transaction-amount { font-size: 18px; font-weight: 700; color: #667eea; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
        </div>
    </nav>

    <div class="container-lg" style="padding: 30px 0;">
        <div class="row">
            <div class="col-lg-3">
                <div class="sidebar">
                    <h5>Menu</h5>
                    <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                    <a href="listings.php"><i class="fas fa-list"></i> Phòng của Tôi</a>
                    <a href="bookings.php"><i class="fas fa-calendar"></i> Đơn Đặt Phòng</a>
                    <a href="revenue.php" class="active"><i class="fas fa-chart-bar"></i> Doanh Thu</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ Sơ</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-chart-bar"></i> Doanh Thu của Tôi
                    </h1>

                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-home"></i></div>
                                <div class="stat-number"><?php echo $stats['total_listings']; ?></div>
                                <div class="stat-label">Phòng Đang Cho Thuê</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                                <div class="stat-label">Tổng Đơn Đặt</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-hourglass"></i></div>
                                <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                                <div class="stat-label">Đơn Chưa Hoàn Thành</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="stat-number"><?php echo number_format($stats['total_revenue'] ?? 0); ?></div>
                                <div class="stat-label">Tổng Doanh Thu (VNĐ)</div>
                            </div>
                        </div>
                    </div>

                    <h3 style="font-weight: 700; margin-top: 40px; margin-bottom: 20px;">
                        <i class="fas fa-list"></i> Lịch Sử Giao Dịch
                    </h3>

                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trans): ?>
                            <div class="transaction-card">
                                <div class="transaction-info">
                                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($trans['title']); ?></div>
                                    <small style="color: #666;">Check-in: <?php echo date('d/m/Y', strtotime($trans['check_in_date'])); ?></small>
                                </div>
                                <div class="transaction-amount">
                                    +<?php echo number_format($trans['deposit_amount']); ?> VNĐ
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; background: white; border-radius: 12px; color: #999;">
                            Không có giao dịch hoàn thành
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
