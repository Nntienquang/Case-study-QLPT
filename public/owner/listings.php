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

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
$owner_id = $_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý Xóa phòng
if (isset($_POST['delete_motel'])) {
    $motel_id = (int)$_POST['motel_id'];
    $stmt = $db->prepare("DELETE FROM motels WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $motel_id, $owner_id);
    if ($stmt->execute()) {
<<<<<<< HEAD
        $_SESSION['message'] = "Xóa phòng thành công!";
=======
        $_SESSION['message'] = "Phòng đã bị xóa thành công!";
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Có lỗi xảy ra khi xóa phòng!";
        $_SESSION['message_type'] = "danger";
    }
    $stmt->close();
    header('Location: listings.php');
    exit;
}

// Đếm tổng số phòng để phân trang
$stmt = $db->prepare("SELECT COUNT(*) as count FROM motels WHERE user_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Lấy danh sách phòng
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

// Đánh giá chất lượng tin đăng
$qualityMap = [];
foreach ($listings as $index => $listing) {
    $quality = ListingQuality::sync($conn, $listing);
    $qualityMap[$listing['id']] = $quality;
    $listings[$index]['health_score'] = $quality['score'];
}

// Hàm trợ giúp hiển thị trạng thái
function get_status_badge($status)
{
    switch (strtolower($status)) {
        case 'pending':
            return '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
        case 'approved':
            return '<span class="badge bg-success">Đang hiển thị</span>';
        case 'hidden':
            return '<span class="badge bg-secondary">Đã ẩn</span>';
        case 'rejected':
            return '<span class="badge bg-danger">Từ chối</span>';
        default:
            return '<span class="badge bg-light text-dark">' . htmlspecialchars($status) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Phòng của Tôi - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
=======
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
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .table-align-middle td,
    .table-align-middle th {
        vertical-align: middle;
    }

    .quality-wrapper {
        width: 120px;
    }

    .quality-bar {
        height: 6px;
        background-color: #e9ecef;
        border-radius: 4px;
        overflow: hidden;
        margin-top: 5px;
    }

    .quality-fill {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .q-danger {
        background-color: #dc3545;
    }

    .q-warning {
        background-color: #ffc107;
    }

    .q-success {
        background-color: #198754;
    }

    .title-text {
        font-weight: 600;
        color: #2b3440;
        display: block;
        margin-bottom: 2px;
    }

    .address-text {
        font-size: 0.85rem;
        color: #6c757d;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
<<<<<<< HEAD
            <div class="wb-user">
                <span><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
=======
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
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
            </div>
        </div>
    </header>

<<<<<<< HEAD
    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link active" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>
                <!-- <a class="wb-side-link " href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>


            <section>
                <?php if (isset($_SESSION['message'])): ?>
                <div
                    class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show border-0 shadow-sm">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                <?php endif; ?>

                <div class="wb-section-head d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="fas fa-list me-2 text-primary"></i> Danh Sách Phòng</h2>
                        <p class="text-muted mb-0">Quản lý, chỉnh sửa tin đăng và theo dõi chất lượng phòng của bạn.</p>
                    </div>
                    <a href="add-listing.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm Phòng Mới</a>
=======
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
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                </div>

                <?php if (count($listings) > 0): ?>
                <div class="wb-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover table-align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Tên Phòng & Địa Chỉ</th>
                                    <th>Loại Hình</th>
                                    <th>Giá (VNĐ)</th>
                                    <th class="text-center">Lượt Xem</th>
                                    <th>Chất Lượng Tin</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-end pe-4">Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listings as $listing): ?>
                                <tr>
                                    <td class="ps-4">
                                        <span
                                            class="title-text"><?php echo htmlspecialchars($listing['title']); ?></span>
                                        <span class="address-text"><i class="fas fa-location-dot me-1"></i>
                                            <?php echo htmlspecialchars($listing['address']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($listing['category_name'] ?? 'Chưa cập nhật'); ?>
                                    </td>
                                    <td class="text-primary fw-bold"><?php echo number_format($listing['price']); ?> đ
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border"><i
                                                class="fas fa-eye text-muted"></i>
                                            <?php echo (int)$listing['count_view']; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                                $score = (int)$listing['health_score'];
                                                $colorClass = $score >= 80 ? 'q-success' : ($score >= 50 ? 'q-warning' : 'q-danger');
                                                ?>
                                        <div class="quality-wrapper">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="fw-bold"><?php echo $score; ?>/100</small>
                                            </div>
                                            <div class="quality-bar">
                                                <div class="quality-fill <?php echo $colorClass; ?>"
                                                    style="width: <?php echo $score; ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo get_status_badge($listing['status']); ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="edit-listing.php?id=<?php echo $listing['id']; ?>"
                                            class="btn btn-sm btn-outline-primary" title="Sửa phòng">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <form method="POST" style="display: inline-block;"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa tin đăng này? Hành động này không thể hoàn tác.');">
                                            <input type="hidden" name="motel_id" value="<?php echo $listing['id']; ?>">
                                            <button type="submit" name="delete_motel"
                                                class="btn btn-sm btn-outline-danger" title="Xóa phòng">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Trước</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Sau</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="wb-card text-center py-5">
                    <i class="fas fa-house-crack fa-4x text-muted mb-3 opacity-50"></i>
                    <h4 class="text-dark">Bạn chưa có phòng nào</h4>
                    <p class="text-muted mb-4">Hãy đăng tin cho thuê phòng đầu tiên của bạn để tiếp cận khách hàng.</p>
                    <a href="add-listing.php" class="btn btn-primary px-4 py-2">
                        <i class="fas fa-plus me-2"></i> Đăng Phòng Mới Ngay
                    </a>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>