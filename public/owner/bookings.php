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
require_once __DIR__ . '/_owner_guard.php';
$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';
$message = '';
$message_type = '';

// Hàm map trạng thái sang tiếng Việt và màu sắc Bootstrap
function get_booking_status_ui($status)
{
    switch ($status) {
        case 'pending':
            return ['label' => 'Chờ phản hồi', 'class' => 'bg-warning text-dark'];
        case 'paid':
            return ['label' => 'Đã đặt cọc', 'class' => 'bg-info text-dark'];
        case 'waiting_payment':
            return ['label' => 'Chờ thanh toán', 'class' => 'bg-warning text-dark'];
        case 'confirmed':
            return ['label' => 'Đã xác nhận', 'class' => 'bg-primary'];
        case 'accepted':
            return ['label' => 'Đã chấp nhận', 'class' => 'bg-primary'];
        case 'completed':
            return ['label' => 'Đã nhận phòng', 'class' => 'bg-success'];
        case 'cancelled':
            return ['label' => 'Khách hủy', 'class' => 'bg-secondary'];
        case 'rejected':
            return ['label' => 'Từ chối', 'class' => 'bg-danger'];
        default:
            return ['label' => 'Không rõ', 'class' => 'bg-light text-dark'];
    }
}

// --- XỬ LÝ DUYỆT / TỪ CHỐI / HOÀN TẤT BOOKING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $status = $_POST['status'];
    $allowed_statuses = ['accepted', 'rejected'];

    if (in_array($status, $allowed_statuses, true)) {
        // Lấy thông tin chi tiết
        $stmt = $conn->prepare('
            SELECT b.user_id as tenant_id, b.motel_id, b.deposit_amount, b.status as current_status, b.payment_status, b.booking_status,
                   p.fee as payment_fee, p.status as escrow_status, m.title, m.user_id as owner_id
            FROM bookings b 
            JOIN motels m ON b.motel_id = m.id 
            LEFT JOIN payments p ON p.booking_id = b.id
            WHERE b.id = ? AND m.user_id = ?
        ');
        $stmt->bind_param('ii', $booking_id, $owner_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($booking) {
            $conn->begin_transaction();
            try {
                if ($status === 'accepted' && ($booking['payment_status'] ?? '') !== 'paid') {
                    throw new RuntimeException('Chỉ được chấp nhận booking sau khi thanh toán đã được admin xác nhận.');
                }

                $newBookingStatus = match ($status) {
                    'accepted' => 'confirmed',
                    'rejected' => 'rejected',
                    default => 'pending',
                };
                $up_stmt = $conn->prepare('UPDATE bookings SET status = ?, booking_status = ?, updated_at = NOW() WHERE id = ?');
                $up_stmt->bind_param('ssi', $status, $newBookingStatus, $booking_id);
                $up_stmt->execute();

                // 2. Xử lý nghiệp vụ theo trạng thái
                if ($status === 'accepted') {
                    // Tự động hủy các khách khác đang tranh phòng này
                    $rejectOthers = $conn->prepare("UPDATE bookings SET status = 'rejected', booking_status = 'rejected', updated_at = NOW() WHERE motel_id = ? AND id != ? AND booking_status IN ('pending','waiting_payment','paid')");
                    $rejectOthers->bind_param('ii', $booking['motel_id'], $booking_id);
                    $rejectOthers->execute();
                    $room = $conn->prepare("UPDATE motels SET room_status = 'reserved' WHERE id = ?");
                    $room->bind_param('i', $booking['motel_id']);
                    $room->execute();
                    $room->close();
                    $_SESSION['message'] = 'Đã chấp nhận giữ phòng! Vui lòng chờ khách dọn đến để hoàn tất.';
                } elseif ($status === 'rejected') {
                    $_SESSION['message'] = 'Đã từ chối khách thuê thành công.';
                }

                // 3. Gửi thông báo cho khách
                $title = 'Cập nhật trạng thái Booking';
                $status_ui = get_booking_status_ui($status);
                $body = 'Yêu cầu thuê phòng "' . $booking['title'] . '" của bạn hiện có trạng thái: ' . $status_ui['label'];
                $conn->query("INSERT INTO notifications (user_id, type, title, body, created_at) VALUES ({$booking['tenant_id']}, 'booking_status', '$title', '$body', NOW())");

                $conn->commit();
                $_SESSION['message_type'] = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['message'] = 'Lỗi hệ thống: ' . $e->getMessage();
                $_SESSION['message_type'] = 'danger';
            }
        }
        header("Location: bookings.php" . (isset($_GET['status']) ? "?status={$_GET['status']}" : ''));
        exit;
    }
}

// --- LẤY DANH SÁCH BOOKING ---
$status_filter = $_GET['status'] ?? '';
$where_clause = "m.user_id = $owner_id";
if ($status_filter && array_key_exists($status_filter, ['pending' => 1, 'paid' => 1, 'accepted' => 1, 'completed' => 1, 'cancelled' => 1, 'rejected' => 1])) {
    $where_clause .= " AND b.status = '" . $conn->real_escape_string($status_filter) . "'";
}

$query = "
    SELECT b.*, m.title as motel_title, m.address, u.name as tenant_name, u.phone as tenant_phone 
    FROM bookings b 
    JOIN motels m ON b.motel_id = m.id 
    JOIN users u ON b.user_id = u.id 
    WHERE $where_clause 
    ORDER BY b.created_at DESC
";
$bookings = $conn->query($query)->fetch_all(MYSQLI_ASSOC);

// --- ĐÃ SỬA LỖI Ở ĐÂY: Thêm b.status thay vì chỉ status ---
$counts = $conn->query("SELECT b.status, COUNT(*) as cnt FROM bookings b JOIN motels m ON b.motel_id = m.id WHERE m.user_id = $owner_id GROUP BY b.status")->fetch_all(MYSQLI_ASSOC);
$status_counts = ['all' => 0];
foreach ($counts as $c) {
    $status_counts[$c['status']] = $c['cnt'];
    $status_counts['all'] += $c['cnt'];
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo (isset($_SESSION['dark_mode']) && $_SESSION['dark_mode']) ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <title>Quản Lý Đặt Phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .booking-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 20px;
        background: #fff;
        overflow: hidden;
    }

    [data-bs-theme="dark"] .booking-card {
        background: #1e293b;
        border-color: #334155;
    }

    .bc-header {
        padding: 15px 20px;
        border-bottom: 1px solid #f1f5f9;
    }

    [data-bs-theme="dark"] .bc-header {
        border-bottom-color: #334155;
    }

    .bc-body {
        padding: 20px;
    }

    .bc-info-box {
        background: #f8fafc;
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #f1f5f9;
        height: 100%;
    }

    [data-bs-theme="dark"] .bc-info-box {
        background: #0f172a;
        border-color: #1e293b;
    }

    .bc-note {
        background: #fffbeb;
        padding: 12px 15px;
        border-radius: 8px;
        border-left: 4px solid #f59e0b;
        color: #92400e;
        margin-top: 15px;
    }

    [data-bs-theme="dark"] .bc-note {
        background: #451a03;
        color: #fde68a;
        border-left-color: #d97706;
    }

    .btn-status-filter {
        color: #64748b;
        font-weight: 500;
        border-bottom: 2px solid transparent;
        padding: 10px 15px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-status-filter:hover {
        color: #0f172a;
        background: #f8fafc;
    }

    .btn-status-filter.active {
        color: #0d6efd;
        border-bottom-color: #0d6efd;
        background: #f0f7ff;
    }

    [data-bs-theme="dark"] .btn-status-filter:hover {
        color: #f8fafc;
        background: #0f172a;
    }

    [data-bs-theme="dark"] .btn-status-filter.active {
        color: #38bdf8;
        border-bottom-color: #38bdf8;
        background: #0c4a6e;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="dashboard.php">QuanLyPhongTro</a>
            <div class="wb-user"><span><?php echo htmlspecialchars($ownerName); ?></span></div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link " href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link active" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

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
                <div class="wb-section-head">
                    <h2>Danh Sách Đặt Phòng / Giữ Chỗ</h2>
                </div>

                <?php if (isset($_SESSION['message'])): ?>
                <div
                    class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show shadow-sm border-0">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                <?php endif; ?>

                <div class="wb-card p-0 mb-4 overflow-hidden">
                    <div class="d-flex flex-wrap border-bottom">
                        <a href="bookings.php"
                            class="btn-status-filter <?php echo $status_filter === '' ? 'active' : ''; ?>">
                            Tất cả (<?php echo $status_counts['all']; ?>)
                        </a>
                        <a href="?status=pending"
                            class="btn-status-filter <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                            Chờ phản hồi (<?php echo $status_counts['pending'] ?? 0; ?>)
                        </a>
                        <a href="?status=paid"
                            class="btn-status-filter <?php echo $status_filter === 'paid' ? 'active' : ''; ?>">
                            Đã cọc (<?php echo $status_counts['paid'] ?? 0; ?>)
                        </a>
                        <a href="?status=accepted"
                            class="btn-status-filter <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>">
                            Chờ Check-in (<?php echo $status_counts['accepted'] ?? 0; ?>)
                        </a>
                        <a href="?status=completed"
                            class="btn-status-filter <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">
                            Đã nhận phòng (<?php echo $status_counts['completed'] ?? 0; ?>)
                        </a>
                    </div>
                </div>

                <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted mb-3 opacity-25"></i>
                    <h5 class="text-muted">Không có đơn đặt phòng nào phù hợp.</h5>
                </div>
                <?php else: ?>
                <?php foreach ($bookings as $b):
                        $ui = get_booking_status_ui($b['status']);
                        $border_color = '';
                        if ($b['status'] == 'pending') $border_color = 'border-warning';
                        if ($b['status'] == 'paid') $border_color = 'border-info';
                        if ($b['status'] == 'accepted') $border_color = 'border-primary';
                        if ($b['status'] == 'completed') $border_color = 'border-success';
                    ?>
                <div class="booking-card <?php echo $border_color; ?>">
                    <div class="bc-header d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
                                <?php echo htmlspecialchars($b['motel_title']); ?>
                                <span
                                    class="badge <?php echo $ui['class']; ?> fs-6 rounded-pill px-3"><?php echo $ui['label']; ?></span>
                            </h5>
                            <div class="text-muted small"><i class="fas fa-location-dot me-1"></i>
                                <?php echo htmlspecialchars($b['address']); ?></div>
                        </div>
                    </div>

                    <div class="bc-body">
                        <div class="row g-3">
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-sm-4">
                                        <div class="bc-info-box">
                                            <div class="text-muted small mb-1">Khách thuê</div>
                                            <div class="fw-bold text-primary"><i class="fas fa-user me-1"></i>
                                                <?php echo htmlspecialchars($b['tenant_name']); ?></div>
                                            <div><i class="fas fa-phone-alt me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($b['tenant_phone']); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="bc-info-box">
                                            <div class="text-muted small mb-1">Thời gian dự kiến</div>
                                            <div class="fw-bold text-success"><i
                                                    class="fas fa-arrow-right-to-bracket me-1"></i> Vào:
                                                <?php echo date('d/m/Y', strtotime($b['check_in_date'])); ?></div>
                                            <div class="fw-bold text-danger"><i
                                                    class="fas fa-arrow-right-from-bracket me-1"></i> Ra:
                                                <?php echo date('d/m/Y', strtotime($b['check_out_date'])); ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="bc-info-box">
                                            <div class="text-muted small mb-1">Tài chính</div>
                                            <div class="fw-bold fs-5 text-secondary"><i
                                                    class="fas fa-money-bill-wave me-1"></i>
                                                <?php echo number_format($b['deposit_amount']); ?> VNĐ</div>
                                            <div class="small text-muted mt-1">Ngày tạo:
                                                <?php echo date('d/m/Y H:i', strtotime($b['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($b['note'])): ?>
                                <div class="bc-note">
                                    <i class="fas fa-pen me-1"></i> <strong>Ghi chú của khách:</strong>
                                    <?php echo htmlspecialchars($b['note']); ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-3 d-flex flex-column justify-content-center gap-2 border-start ps-md-4">
                                <?php if ($b['status'] === 'paid'): ?>
                                <form method="POST" class="d-grid gap-2">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" name="status" value="accepted"
                                        class="btn btn-success fw-bold rounded-pill">
                                        <i class="fas fa-check-circle me-1"></i> Chấp nhận Booking
                                    </button>
                                    <button type="submit" name="status" value="rejected"
                                        class="btn btn-outline-danger fw-bold rounded-pill">
                                        <i class="fas fa-times-circle me-1"></i> Từ chối
                                    </button>
                                </form>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                <div class="text-center text-muted"><i class="fas fa-credit-card fa-2x mb-2"></i><br><strong>Chờ khách thanh toán</strong></div>
                                <?php elseif ($b['status'] === 'accepted'): ?>
                                <div class="text-center text-primary"><i class="fas fa-user-check fa-2x mb-2"></i><br><strong>Chờ khách xác nhận nhận phòng</strong></div>
                                <div class="text-center small text-muted mt-2">Admin chỉ giải ngân sau khi khách xác nhận đã nhận phòng.</div>
                                <?php elseif ($b['status'] === 'completed'): ?>
                                <div class="text-center text-success"><i
                                        class="fas fa-check-circle fa-2x mb-2"></i><br><strong>Khách đã xác nhận nhận phòng</strong></div>
                                <div class="text-center small text-muted mt-2">Chờ admin kiểm tra và giải ngân tiền cọc.</div>
                                <?php else: ?>
                                <div class="text-center text-muted"><i class="fas fa-ban fa-2x mb-2"></i><br><strong>Đã
                                        kết thúc</strong></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
