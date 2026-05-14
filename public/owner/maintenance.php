<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';

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

$ownerId = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Chủ phòng';

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $ownerId);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);

// Các hàm helper format
function owner_dash_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function req_priority_badge(string $priority): string
{
    return match (strtolower($priority)) {
        'high' => '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fas fa-fire me-1"></i> Khẩn cấp</span>',
        'normal' => '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning">Bình thường</span>',
        'low' => '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">Thấp</span>',
        default => '<span class="badge bg-light text-dark border">Chưa rõ</span>',
    };
}

function req_status_badge(string $status): string
{
    return match (strtolower($status)) {
        'open' => '<span class="badge bg-danger"><i class="fas fa-circle-exclamation me-1"></i> Mới tạo</span>',
        'in_progress' => '<span class="badge bg-warning text-dark"><i class="fas fa-person-digging me-1"></i> Đang sửa</span>',
        'resolved' => '<span class="badge bg-success"><i class="fas fa-check-double me-1"></i> Đã xong</span>',
        default => '<span class="badge bg-secondary">Khác</span>',
    };
}

// ==========================================
// 1. XỬ LÝ POST: CẬP NHẬT TRẠNG THÁI SỰ CỐ
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $req_id = (int)$_POST['request_id'];
    $new_status = $_POST['new_status'];

    // Validate trạng thái
    $allowed_statuses = ['open', 'in_progress', 'resolved'];
    if (in_array($new_status, $allowed_statuses)) {
        $resolved_sql = ($new_status === 'resolved') ? ", resolved_at = NOW()" : ", resolved_at = NULL";

        $updateQuery = "UPDATE maintenance_requests SET status = ?, updated_at = NOW() $resolved_sql WHERE id = ? AND owner_id = ?";
        $stmtUpdate = $conn->prepare($updateQuery);
        $stmtUpdate->bind_param("sii", $new_status, $req_id, $ownerId);
        $stmtUpdate->execute();

        $filter_param = isset($_GET['filter']) ? "&filter=" . $_GET['filter'] : "";
        header("Location: maintenance.php?success=status_updated" . $filter_param);
        exit;
    }
}

// ==========================================
// 2. LẤY DỮ LIỆU HIỂN THỊ VÀ LỌC
// ==========================================
$currentFilter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Thống kê tổng
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as count_open,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as count_progress,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as count_resolved
FROM maintenance_requests WHERE owner_id = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Build câu query theo Filter
$whereClause = "WHERE mr.owner_id = ?";
$params = [$ownerId];
$types = "i";

if ($currentFilter === 'open') {
    $whereClause .= " AND mr.status IN ('open', 'in_progress')";
} elseif ($currentFilter === 'history') {
    $whereClause .= " AND mr.status = 'resolved'";
}

// Lấy thêm cột image_url
$requestsQuery = "
    SELECT mr.*, m.title as room_title, u.name as tenant_name, u.phone 
    FROM maintenance_requests mr
    JOIN motels m ON mr.motel_id = m.id
    JOIN users u ON mr.user_id = u.id
    $whereClause
    ORDER BY 
        CASE mr.status 
            WHEN 'open' THEN 1 
            WHEN 'in_progress' THEN 2 
            WHEN 'resolved' THEN 3 
        END,
        mr.priority = 'high' DESC,
        mr.created_at DESC
";
$stmt = $conn->prepare($requestsQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì & Sự cố - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .request-desc {
        background-color: var(--bs-light);
        padding: 10px 15px;
        border-radius: 8px;
        border-left: 3px solid var(--bs-primary);
        font-size: 0.95rem;
        color: var(--bs-body-color);
        margin-top: 10px;
    }

    .resolved-row {
        opacity: 0.7;
    }

    .issue-img-thumb {
        max-height: 100px;
        border-radius: 6px;
        cursor: pointer;
        transition: transform 0.2s;
        object-fit: cover;
    }

    .issue-img-thumb:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Giao diện modal xem ảnh đen bóng mờ */
    #imageModal .modal-content {
        background-color: transparent;
        border: none;
    }

    #imageModal .modal-header {
        border-bottom: none;
    }

    #imageModal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
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
            <div class="wb-user">
                <span><?php echo owner_dash_e($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link active" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì
                    & Sự cố</a>
                <!-- <a class="wb-side-link" href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <?php if (isset($_GET['success']) && $_GET['success'] == 'status_updated'): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i> Cập nhật trạng thái sự cố thành công!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="wb-hero owner mb-4 p-4 rounded bg-light border">
                    <div>
                        <h2 class="mb-1 text-dark"><i class="fas fa-screwdriver-wrench text-primary me-2"></i> Bảo trì &
                            Sự cố</h2>
                        <p class="text-muted mb-0">Tiếp nhận yêu cầu sửa chữa, xem hình ảnh thực tế từ khách thuê và cập
                            nhật tiến độ.</p>
                    </div>
                </div>

                <div class="wb-grid wb-stats-4 mb-4">
                    <div class="wb-card border-0 shadow-sm"><i class="fas fa-list-check wb-card-icon text-primary"></i>
                        <div class="wb-card-value"><?php echo (int)($stats['total'] ?? 0); ?></div>
                        <div class="wb-card-label">Tổng yêu cầu</div>
                    </div>
                    <div class="wb-card border-0 shadow-sm border-bottom border-danger border-3"><i
                            class="fas fa-circle-exclamation wb-card-icon text-danger"></i>
                        <div class="wb-card-value text-danger"><?php echo (int)($stats['count_open'] ?? 0); ?></div>
                        <div class="wb-card-label">Chờ xử lý (Mới)</div>
                    </div>
                    <div class="wb-card border-0 shadow-sm border-bottom border-warning border-3"><i
                            class="fas fa-person-digging wb-card-icon text-warning"></i>
                        <div class="wb-card-value text-warning"><?php echo (int)($stats['count_progress'] ?? 0); ?>
                        </div>
                        <div class="wb-card-label">Đang tiến hành sửa</div>
                    </div>
                    <div class="wb-card border-0 shadow-sm border-bottom border-success border-3"><i
                            class="fas fa-check-double wb-card-icon text-success"></i>
                        <div class="wb-card-value text-success"><?php echo (int)($stats['count_resolved'] ?? 0); ?>
                        </div>
                        <div class="wb-card-label">Đã giải quyết xong</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
                    <h4 class="mb-0">Danh sách yêu cầu sửa chữa</h4>
                    <div class="btn-group shadow-sm">
                        <a href="?filter=all"
                            class="btn btn-outline-primary btn-sm <?php echo $currentFilter === 'all' ? 'active' : ''; ?>">Tất
                            cả</a>
                        <a href="?filter=open"
                            class="btn btn-outline-primary btn-sm <?php echo $currentFilter === 'open' ? 'active' : ''; ?>">Chờ
                            & Đang xử lý</a>
                        <a href="?filter=history"
                            class="btn btn-outline-primary btn-sm <?php echo $currentFilter === 'history' ? 'active' : ''; ?>">Đã
                            xong (Lịch sử)</a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-5">
                    <?php if (count($requests) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($requests as $req): ?>
                        <div
                            class="list-group-item p-4 <?php echo $req['status'] === 'resolved' ? 'resolved-row bg-light' : ''; ?>">
                            <div class="row align-items-start">
                                <div class="col-md-9">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <h5 class="mb-0 fw-bold text-dark"><?php echo owner_dash_e($req['title']); ?>
                                        </h5>
                                        <?php echo req_priority_badge($req['priority']); ?>
                                    </div>
                                    <div class="text-muted small mb-2 d-flex flex-wrap gap-3">
                                        <span><i class="fas fa-house text-primary me-1"></i>
                                            <strong><?php echo owner_dash_e($req['room_title']); ?></strong></span>
                                        <span><i class="fas fa-user text-secondary me-1"></i>
                                            <?php echo owner_dash_e($req['tenant_name']); ?>
                                            (<?php echo owner_dash_e($req['phone']); ?>)</span>
                                        <span><i class="fas fa-clock text-secondary me-1"></i> Gửi lúc:
                                            <?php echo date('H:i d/m/Y', strtotime($req['created_at'])); ?></span>

                                        <?php if ($req['status'] === 'resolved' && $req['resolved_at']): ?>
                                        <span class="text-success"><i class="fas fa-check text-success me-1"></i> Xong
                                            lúc: <?php echo date('H:i d/m/Y', strtotime($req['resolved_at'])); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="request-desc">
                                        <div class="mb-2"><?php echo nl2br(owner_dash_e($req['description'])); ?></div>

                                        <?php if (!empty($req['image_url'])): ?>
                                        <div class="mt-3">
                                            <div class="small text-muted fw-bold mb-1"><i class="fas fa-image me-1"></i>
                                                Ảnh thực tế:</div>
                                            <img src="../<?php echo htmlspecialchars($req['image_url']); ?>"
                                                class="img-thumbnail issue-img-thumb" alt="Ảnh sự cố"
                                                title="Click để phóng to ảnh">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div
                                    class="col-md-3 mt-3 mt-md-0 d-flex flex-column align-items-md-end border-md-start">
                                    <div class="mb-2">
                                        <?php echo req_status_badge($req['status']); ?>
                                    </div>

                                    <form method="POST" action="?filter=<?php echo $currentFilter; ?>"
                                        class="w-100 mt-2">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">

                                        <select class="form-select form-select-sm shadow-sm border-secondary"
                                            name="new_status" onchange="this.form.submit()"
                                            <?php echo $req['status'] === 'resolved' ? 'disabled' : ''; ?>>
                                            <option value="open"
                                                <?php echo $req['status'] === 'open' ? 'selected' : ''; ?>>Mới nhận
                                            </option>
                                            <option value="in_progress"
                                                <?php echo $req['status'] === 'in_progress' ? 'selected' : ''; ?>>Đang
                                                gọi thợ sửa</option>
                                            <option value="resolved"
                                                <?php echo $req['status'] === 'resolved' ? 'selected' : ''; ?>>Đánh dấu
                                                Đã xong</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-5 text-center">
                        <div class="text-muted mb-3"><i
                                class="fas fa-face-smile-beam fa-4x text-success opacity-50"></i></div>
                        <h5 class="text-muted">Tốt quá, không có sự cố hỏng hóc nào cần xử lý.</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img src="" id="modalImage" class="img-fluid" alt="Phóng to ảnh sự cố">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Bắt sự kiện click vào ảnh thumbnail để mở Modal xem ảnh lớn
    document.querySelectorAll('.issue-img-thumb').forEach(img => {
        img.addEventListener('click', function() {
            // Lấy src của ảnh được click gán vào thẻ img trong modal
            document.getElementById('modalImage').src = this.src;
            // Hiển thị modal
            var imageModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            imageModal.show();
        });
    });
    </script>
</body>

</html>