<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$allowed_statuses = ['accepted', 'rejected', 'completed'];
$status_filter = $_GET['status'] ?? '';

// Xử lý Form Cập nhật trạng thái Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];

    if (in_array($status, $allowed_statuses, true)) {
        // 1. Lấy thông tin chi tiết
        $stmt = $db->prepare('
            SELECT b.user_id as tenant_id, b.motel_id, b.deposit_amount, b.status as current_status, m.title, m.user_id as owner_id
            FROM bookings b 
            JOIN motels m ON b.motel_id = m.id 
            WHERE b.id = ? AND m.user_id = ?
        ');
        $stmt->bind_param('ii', $booking_id, $owner_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($booking) {
            $conn->begin_transaction();

            try {
                // HÀNH ĐỘNG 1: Cập nhật trạng thái mới cho Booking
                $stmt = $conn->prepare('UPDATE bookings SET status = ? WHERE id = ?');
                $stmt->bind_param('si', $status, $booking_id);
                $stmt->execute();

                // HÀNH ĐỘNG 2: Xử lý theo từng loại trạng thái
                if ($status === 'accepted') {
                    // Nếu CHẤP NHẬN: Tự động từ chối các khách khác (KHÔNG CỘNG TIỀN Ở BƯỚC NÀY)
                    $rejectOthers = $conn->prepare("
                        UPDATE bookings 
                        SET status = 'rejected' 
                        WHERE motel_id = ? AND id != ? AND status IN ('pending', 'paid')
                    ");
                    $rejectOthers->bind_param('ii', $booking['motel_id'], $booking_id);
                    $rejectOthers->execute();

                    $_SESSION['message'] = 'Đã chấp nhận giữ phòng. Vui lòng chờ đến ngày khách Check-in để hoàn tất giao dịch.';
                } elseif ($status === 'completed') {
                    // Nếu HOÀN TẤT: Khách đã vào ở -> BẮT ĐẦU GIẢI NGÂN TIỀN CHO CHỦ TRỌ
                    // (Chỉ giải ngân nếu trạng thái trước đó là 'accepted' và khách có đóng cọc)
                    if ($booking['current_status'] === 'accepted' && $booking['deposit_amount'] > 0) {

                        // Cộng tiền cọc vào ví
                        $updateWallet = $conn->prepare("
                            UPDATE wallets SET balance = balance + ? WHERE user_id = ?
                        ");
                        $updateWallet->bind_param('ii', $booking['deposit_amount'], $owner_id);
                        $updateWallet->execute();

                        // Ghi nhận lịch sử giao dịch (Giải ngân)
                        $insertTrans = $conn->prepare("
                            INSERT INTO transactions (from_user, to_user, amount, type, booking_id, created_at) 
                            VALUES (?, ?, ?, 'release', ?, NOW())
                        ");
                        $insertTrans->bind_param('iiii', $booking['tenant_id'], $owner_id, $booking['deposit_amount'], $booking_id);
                        $insertTrans->execute();

                        $_SESSION['message'] = 'Giao dịch hoàn tất! Tiền cọc đã được cộng vào ví của bạn.';
                    } else {
                        $_SESSION['message'] = 'Đã đánh dấu hoàn tất nhận phòng.';
                    }
                } elseif ($status === 'rejected') {
                    // Nếu TỪ CHỐI: Tiền (nếu khách đã nộp) sẽ được Admin hoàn trả sau
                    $_SESSION['message'] = 'Đã từ chối khách thuê thành công.';
                }

                // HÀNH ĐỘNG 3: Gửi thông báo cho khách thuê
                $title = 'Trạng thái Booking đã cập nhật';
                $status_vn = booking_label($status);
                $body = 'Yêu cầu đặt phòng "' . $booking['title'] . '" của bạn hiện có trạng thái: ' . $status_vn;
                $link = 'user/bookings.php';

                $notify = $conn->prepare("INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?, 'booking_status', ?, ?, ?, NOW())");
                $notify->bind_param('isss', $booking['tenant_id'], $title, $body, $link);
                $notify->execute();

                $conn->commit(); // Lưu thay đổi vào DB
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        } else {
            $_SESSION['message'] = 'Không tìm thấy Booking hoặc lỗi quyền truy cập.';
            $_SESSION['message_type'] = 'danger';
        }

        header("Location: bookings.php" . ($status_filter ? "?status=$status_filter" : ''));
        exit;
    }
}

// Xây dựng câu truy vấn Lấy danh sách Booking
$where = "WHERE m.user_id = ?";
$params = [$owner_id];
$types = "i";

if ($status_filter !== '') {
    $where .= " AND b.status = ?";
    $types .= "s";
    $params[] = $status_filter;
}

// Đếm tổng số để phân trang
$count_sql = "SELECT COUNT(*) as count FROM bookings b JOIN motels m ON b.motel_id = m.id $where";
$stmt = $db->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Lấy dữ liệu Booking
$sql = "
    SELECT b.*, m.title as motel_title, m.address, u.name as tenant_name, u.email as tenant_email, u.phone as tenant_phone
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    LEFT JOIN users u ON b.user_id = u.id
    $where
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
";
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Hàm trợ giúp hiển thị Tiếng Việt
function booking_label(string $status): string
{
    return [
        'pending' => 'Chờ phản hồi',
        'paid' => 'Đã đặt cọc',
        'accepted' => 'Đã chấp nhận',
        'completed' => 'Hoàn tất',
        'rejected' => 'Từ chối',
        'cancelled' => 'Khách đã hủy',
    ][$status] ?? ucfirst($status);
}

// Hàm trợ giúp màu sắc Badge
function booking_badge(string $status): string
{
    return [
        'pending' => 'warning',
        'paid' => 'info',
        'accepted' => 'success',
        'completed' => 'primary',
        'rejected' => 'danger',
        'cancelled' => 'secondary',
    ][$status] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Đặt Phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .booking-card {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid #e9ecef;
        border-left: 4px solid #0d6efd;
        transition: 0.2s;
    }

    .booking-card:hover {
        border-color: #dee2e6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .booking-card.status-pending {
        border-left-color: #ffc107;
    }

    .booking-card.status-paid {
        border-left-color: #0dcaf0;
    }

    .booking-card.status-accepted {
        border-left-color: #198754;
    }

    .booking-card.status-rejected,
    .booking-card.status-cancelled {
        border-left-color: #dc3545;
        opacity: 0.8;
    }

    .booking-card.status-completed {
        border-left-color: #6c757d;
    }

    .meta-info {
        font-size: 0.9rem;
        color: #495057;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-top: 15px;
    }

    .meta-item {
        background: #f8f9fa;
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #f1f3f5;
    }

    .meta-item i {
        color: #6c757d;
        width: 20px;
    }

    .tenant-note {
        background: #fff3cd;
        border: 1px solid #ffe69c;
        padding: 10px 15px;
        border-radius: 6px;
        margin-top: 12px;
        font-size: 0.9rem;
        color: #664d03;
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
                <span><?php echo htmlspecialchars($ownerName); ?></span>
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
                <a class="wb-side-link active" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

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

                <div class="wb-card mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="fw-bold mb-1"><i class="fas fa-calendar-check text-primary me-2"></i> Quản lý Booking
                        </h3>
                        <p class="text-muted mb-0 small">Theo dõi yêu cầu đặt cọc giữ phòng và phản hồi cho khách thuê.
                        </p>
                    </div>
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ
                                phản hồi</option>
                            <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Đã đặt cọc
                            </option>
                            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Đã
                                chấp nhận</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>
                                Hoàn tất</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Từ
                                chối</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                                Khách đã hủy</option>
                        </select>
                    </form>
                </div>

                <?php if ($bookings): ?>
                <?php foreach ($bookings as $booking): ?>
                <div class="wb-card booking-card status-<?php echo strtolower($booking['status']); ?>">
                    <div class="row">
                        <div class="col-lg-9">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <h5 class="fw-bold mb-0 text-dark">
                                    <?php echo htmlspecialchars($booking['motel_title']); ?></h5>
                                <span class="badge bg-<?php echo booking_badge($booking['status']); ?>">
                                    <?php echo booking_label($booking['status']); ?>
                                </span>
                            </div>
                            <div class="text-muted small mb-3"><i class="fas fa-location-dot"></i>
                                <?php echo htmlspecialchars($booking['address']); ?></div>

                            <div class="meta-info">
                                <div class="meta-item">
                                    <div class="small text-muted mb-1">Khách thuê</div>
                                    <div class="fw-bold"><i class="fas fa-user text-primary"></i>
                                        <?php echo htmlspecialchars($booking['tenant_name'] ?? 'Khách ẩn danh'); ?>
                                    </div>
                                    <div class="small mt-1"><i class="fas fa-phone text-secondary"></i> <a
                                            href="tel:<?php echo htmlspecialchars($booking['tenant_phone'] ?? ''); ?>"
                                            class="text-decoration-none"><?php echo htmlspecialchars($booking['tenant_phone'] ?? 'Chưa cập nhật SDT'); ?></a>
                                    </div>
                                </div>

                                <div class="meta-item">
                                    <div class="small text-muted mb-1">Thời gian dự kiến</div>
                                    <div><i class="fas fa-sign-in-alt text-success"></i> Vào:
                                        <strong><?php echo $booking['check_in_date'] ? date('d/m/Y', strtotime($booking['check_in_date'])) : '---'; ?></strong>
                                    </div>
                                    <div class="mt-1"><i class="fas fa-sign-out-alt text-danger"></i> Ra:
                                        <strong><?php echo $booking['check_out_date'] ? date('d/m/Y', strtotime($booking['check_out_date'])) : '---'; ?></strong>
                                    </div>
                                </div>

                                <div class="meta-item">
                                    <div class="small text-muted mb-1">Tài chính</div>
                                    <div class="text-success fw-bold fs-6"><i class="fas fa-money-bill-wave"></i>
                                        <?php echo number_format((int)$booking['deposit_amount']); ?> VNĐ</div>
                                    <div class="small mt-1 text-muted">Ngày tạo:
                                        <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?></div>
                                </div>
                            </div>

                            <?php if (!empty($booking['note'])): ?>
                            <div class="tenant-note">
                                <strong><i class="fas fa-pen"></i> Ghi chú của khách:</strong>
                                <?php echo nl2br(htmlspecialchars($booking['note'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div
                            class="col-lg-3 mt-3 mt-lg-0 text-lg-end border-start-lg ps-lg-4 d-flex flex-column justify-content-center">
                            <?php if (in_array($booking['status'], ['pending', 'paid'])): ?>
                            <form method="POST" class="mb-2" onsubmit="disableButton(this)">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                <button class="btn btn-success w-100" name="status" value="accepted"><i
                                        class="fas fa-check-circle me-1"></i> Chấp nhận Booking</button>
                            </form>
                            <form method="POST"
                                onsubmit="return confirmAction(this, 'Bạn có chắc chắn muốn TỪ CHỐI khách thuê này? Tiền cọc (nếu có) có thể sẽ được hoàn lại cho khách.');">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                <button class="btn btn-outline-danger w-100" name="status" value="rejected"><i
                                        class="fas fa-times-circle me-1"></i> Từ chối</button>
                            </form>
                            <?php elseif ($booking['status'] === 'accepted'): ?>
                            <div class="text-muted small mb-2 d-lg-block d-none"><i class="fas fa-info-circle"></i>
                                Khách chuẩn bị dọn vào. Khi khách đã nhận phòng, hãy đánh dấu hoàn tất.</div>
                            <form method="POST"
                                onsubmit="return confirmAction(this, 'Xác nhận khách đã dọn vào ở và hợp đồng bắt đầu có hiệu lực?');">
                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                <button class="btn btn-primary w-100" name="status" value="completed"><i
                                        class="fas fa-flag-checkered me-1"></i> Đã nhận phòng</button>
                            </form>
                            <?php elseif ($booking['status'] === 'completed'): ?>
                            <div class="text-success text-center"><i class="fas fa-check-circle fa-2x mb-1"></i><br>Giao
                                dịch hoàn tất</div>
                            <?php elseif ($booking['status'] === 'rejected'): ?>
                            <div class="text-danger text-center"><i class="fas fa-ban fa-2x mb-1"></i><br>Đã từ chối
                                khách</div>
                            <?php elseif ($booking['status'] === 'cancelled'): ?>
                            <div class="text-muted text-center"><i class="fas fa-user-times fa-2x mb-1"></i><br>Khách đã
                                hủy cọc</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?>">Trước</a>
                        </li>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $i; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link"
                                href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? "&status=$status_filter" : ''; ?>">Sau</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="wb-card text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-50"></i>
                    <h4 class="text-dark">Không có đơn đặt phòng nào</h4>
                    <p class="text-muted mb-0">Danh sách booking trống hoặc không có trạng thái nào phù hợp với bộ lọc
                        hiện tại.</p>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Xử lý chống click đúp
    function disableButton(form) {
        const btn = form.querySelector('button[type="submit"], button[name="status"]');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...';
            btn.classList.add('disabled');
            setTimeout(() => btn.disabled = true, 50);
        }
    }

    // Hiện cảnh báo
    function confirmAction(form, message) {
        if (confirm(message)) {
            disableButton(form);
            return true;
        }
        return false;
    }
    </script>
</body>

</html>