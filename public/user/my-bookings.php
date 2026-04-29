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
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Get bookings
$stmt = $db->prepare("
    SELECT b.*, m.title, m.price, m.address
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Đặt của Tôi - QuanLyPhongTro</title>
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
        .booking-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; border-left: 4px solid #667eea; }
        .booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .booking-title { font-size: 18px; font-weight: 600; color: #333; }
        .booking-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .booking-info-item { padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 13px; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-completed { background: #cfe2ff; color: #084298; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 60px 30px; background: white; border-radius: 12px; }
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
                    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="search.php"><i class="fas fa-search"></i> Tìm Phòng</a>
                    <a href="my-bookings.php" class="active"><i class="fas fa-calendar"></i> Đơn Đặt của Tôi</a>
                    <a href="saved-motels.php"><i class="fas fa-heart"></i> Phòng Yêu Thích</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ Sơ</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-calendar"></i> Đơn Đặt của Tôi
                    </h1>

                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-title"><?php echo htmlspecialchars($booking['title']); ?></div>
                                    <span class="badge badge-<?php echo strtolower($booking['status']); ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-info-item">
                                        <strong>Địa Chỉ:</strong><br><?php echo htmlspecialchars($booking['address']); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Check-in:</strong><br><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Check-out:</strong><br><?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Đặt Cọc:</strong><br><span style="color: #667eea; font-weight: 700;"><?php echo number_format($booking['deposit_amount']); ?> VNĐ</span>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Ngày Đặt:</strong><br><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div style="font-size: 60px; color: #ddd; margin-bottom: 20px;"><i class="fas fa-inbox"></i></div>
                            <p style="color: #999; margin-bottom: 20px;">Bạn chưa đặt phòng nào</p>
                            <a href="search.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Tìm Phòng
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
