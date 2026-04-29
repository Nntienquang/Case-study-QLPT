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
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get total
$stmt = $db->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Get favorites
$stmt = $db->prepare("
    SELECT m.id, m.title, m.price, m.address, m.count_view
    FROM favorites f
    JOIN motels m ON f.motel_id = m.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng Yêu Thích - QuanLyPhongTro</title>
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
        .motel-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; }
        .motel-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .motel-image { height: 200px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; position: relative; }
        .motel-image i { font-size: 50px; }
        .remove-btn { position: absolute; top: 10px; right: 10px; background: white; border: none; color: #d32f2f; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; }
        .motel-body { padding: 20px; }
        .motel-title { font-size: 16px; font-weight: 600; margin-bottom: 10px; }
        .motel-price { font-size: 20px; font-weight: 700; color: #667eea; margin-bottom: 10px; }
        .motel-info { color: #666; font-size: 13px; margin-bottom: 15px; }
        .btn-view { background: linear-gradient(135deg, #667eea, #764ba2); border: none; color: white; padding: 8px 15px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn-view:hover { color: white; }
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
                    <a href="my-bookings.php"><i class="fas fa-calendar"></i> Đơn Đặt của Tôi</a>
                    <a href="saved-motels.php" class="active"><i class="fas fa-heart"></i> Phòng Yêu Thích</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ Sơ</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-heart"></i> Phòng Yêu Thích
                    </h1>

                    <?php if (count($motels) > 0): ?>
                        <div class="row">
                            <?php foreach ($motels as $motel): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="motel-card">
                                        <div class="motel-image">
                                            <i class="fas fa-image"></i>
                                            <button class="remove-btn" onclick="removeFavorite(<?php echo $motel['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <div class="motel-body">
                                            <div class="motel-title"><?php echo htmlspecialchars($motel['title']); ?></div>
                                            <div class="motel-price"><?php echo number_format($motel['price']); ?> VNĐ</div>
                                            <div class="motel-info">
                                                <div style="margin-bottom: 8px;"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($motel['address']); ?></div>
                                                <div><i class="fas fa-eye"></i> <?php echo $motel['count_view']; ?> lượt xem</div>
                                            </div>
                                            <a href="motel-detail.php?id=<?php echo $motel['id']; ?>" class="btn-view" style="display: block; text-align: center;">
                                                Xem Chi Tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

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
                            <div style="font-size: 60px; color: #ddd; margin-bottom: 20px;"><i class="fas fa-heart"></i></div>
                            <p style="color: #999; margin-bottom: 20px;">Bạn chưa lưu phòng nào</p>
                            <a href="search.php" class="btn btn-primary">
                                <i class="fas fa-search"></i> Tìm Phòng
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function removeFavorite(motelId) {
            if (confirm('Xóa khỏi yêu thích?')) {
                fetch('../ajax/toggle-favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ motel_id: motelId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    }
                });
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
