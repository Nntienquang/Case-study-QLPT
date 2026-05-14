<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';
@require_once '../../core/ListingQuality.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$owner_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Delete motel if requested
if (isset($_POST['delete_motel'])) {
    $motel_id = (int)$_POST['motel_id'];
    $stmt = $db->prepare("DELETE FROM motels WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $motel_id, $owner_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Phòng đã bị xóa thành công!";
        $_SESSION['message_type'] = "success";
    }
    $stmt->close();
    header('Location: listings.php');
    exit;
}

// Get total count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM motels WHERE user_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Get listings
$stmt = $db->prepare("
    SELECT m.*, c.name as category_name
    FROM motels m
    LEFT JOIN categories c ON m.category_id = c.id
    WHERE m.user_id = ?
    ORDER BY m.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $owner_id, $limit, $offset);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$qualityMap = [];
foreach ($listings as $index => $listing) {
    $quality = ListingQuality::sync($conn, $listing);
    $qualityMap[$listing['id']] = $quality;
    $listings[$index]['health_score'] = $quality['score'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng của tôi - Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .navbar-nav .nav-link { color: rgba(255,255,255,0.9) !important; margin-left: 20px; transition: 0.3s; }
        .navbar-nav .nav-link:hover { color: white !important; }
        .main-content { padding: 30px; }
        .page-header { margin-bottom: 30px; }
        .page-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 10px; }
        .page-subtitle { color: #666; font-size: 14px; }
        .listing-table { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .table { margin: 0; }
        .table thead { background: #f8f9fa; }
        .table th { border: none; padding: 15px; font-weight: 600; color: #333; }
        .table td { padding: 15px; border-top: 1px solid #eee; }
        .table tbody tr:hover { background: #f8f9fa; }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-hidden { background: #f8d7da; color: #721c24; }
        .action-buttons { display: flex; gap: 8px; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
        .btn-primary:hover { color: white; }
        .empty-state { text-align: center; padding: 60px 30px; background: white; border-radius: 12px; }
        .empty-state-icon { font-size: 60px; color: #ddd; margin-bottom: 20px; }
        .pagination { margin-top: 20px; }
        .alert { border-radius: 12px; }
        .quality-meter { min-width: 150px; }
        .quality-bar { height: 8px; background: #e9ecef; border-radius: 999px; overflow: hidden; margin: 6px 0; }
        .quality-fill { height: 100%; border-radius: 999px; }
        .quality-fill.success { background: #16a34a; }
        .quality-fill.warning { background: #f59e0b; }
        .quality-fill.danger { background: #dc2626; }
        .quality-hint { max-width: 260px; color: #6b7280; font-size: 12px; line-height: 1.4; }
    </style>
    <link href="../assets/css/modern.css" rel="stylesheet">
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
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> Tài khoản
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Tổng quan</a></li>
                            <li><a class="dropdown-item" href="listings.php">Phòng của tôi</a></li>
                            <li><a class="dropdown-item" href="add-listing.php">Đăng phòng</a></li>
                            <li><a class="dropdown-item" href="viewing-appointments.php">Lịch xem</a></li>
                            <li><a class="dropdown-item" href="bookings.php">Đơn đặt phòng</a></li>
                            <li><a class="dropdown-item" href="revenue.php">Doanh thu</a></li>
                            <li><a class="dropdown-item" href="settings.php">Cài đặt</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Đăng xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-lg" style="padding: 30px 0;">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <?php
                $ownerNavActive = 'listings';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="main-content">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['message']); ?>
                    <?php endif; ?>

                    <div class="page-header">
                        <h1 class="page-title"><i class="fas fa-list"></i> Phòng của tôi</h1>
                        <p class="page-subtitle">Quản lý tất cả phòng trọ của bạn</p>
                    </div>

                    <?php if (count($listings) > 0): ?>
                        <div class="listing-table">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tên phòng</th>
                                        <th>Danh mục</th>
                                        <th>Giá (VNĐ)</th>
                                        <th>Lượt xem</th>
                                        <th>Chat luong tin</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($listings as $listing): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($listing['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($listing['address']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($listing['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($listing['price']); ?></td>
                                            <td><?php echo $listing['count_view']; ?></td>
                                            <td>
                                                <?php
                                                    $score = (int)$listing['health_score'];
                                                    $quality = $qualityMap[$listing['id']] ?? ['suggestions' => ''];
                                                    $qualityClass = ListingQuality::badgeClass($score);
                                                ?>
                                                <div class="quality-meter">
                                                    <strong><?php echo $score; ?>/100</strong>
                                                    <span class="badge text-bg-<?php echo $qualityClass; ?>"><?php echo ListingQuality::label($score); ?></span>
                                                    <div class="quality-bar">
                                                        <div class="quality-fill <?php echo $qualityClass; ?>" style="width: <?php echo $score; ?>%;"></div>
                                                    </div>
                                                    <?php if (!empty($quality['suggestions'])): ?>
                                                        <div class="quality-hint"><?php echo htmlspecialchars($quality['suggestions']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge-status badge-<?php echo strtolower($listing['status']); ?>">
                                                    <?php echo ucfirst($listing['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="edit-listing.php?id=<?php echo $listing['id']; ?>" class="btn btn-warning btn-sm">Sửa</a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn chắc chắn muốn xóa?');">
                                                        <input type="hidden" name="motel_id" value="<?php echo $listing['id']; ?>">
                                                        <button type="submit" name="delete_motel" class="btn btn-danger btn-sm">Xóa</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="pagination justify-content-center mt-4">
                                <ul class="pagination">
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
                            <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                            <p>Bạn chưa có phòng nào</p>
                            <a href="add-listing.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Thêm phòng mới
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
