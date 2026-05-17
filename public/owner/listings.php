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

// Chuyển việc gán biến này lên trước để fix lỗi undefined variable ở câu query bên dưới
$owner_id = $_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
require_once __DIR__ . '/_owner_guard.php';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Xử lý Xóa phòng
if (isset($_POST['delete_motel'])) {
    $motel_id = (int)$_POST['motel_id'];
    $stmt = $db->prepare("DELETE FROM motels WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $motel_id, $owner_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Xóa phòng thành công!";
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
    <title>Phòng của Tôi - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
        <div class="container-lg wb-topbar-inner d-flex justify-content-between align-items-center">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>

            <div class="wb-user d-flex align-items-center gap-3">
                <span class="fw-medium"><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link " href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link active" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>

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
                                    <td class="text-primary fw-bold">
                                        <?php echo number_format($listing['price']); ?> đ
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
