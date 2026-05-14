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
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

@require_once '../../core/NotificationHelper.php';

$actionMessage = '';
$actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_action'], $_POST['booking_id'])) {
    $bookingId = (int)$_POST['booking_id'];
    $action = (string)$_POST['booking_action'];
    $stmt = $db->prepare("
        SELECT b.id, b.user_id AS tenant_id, b.status, b.motel_id, m.title AS motel_title
        FROM bookings b
        JOIN motels m ON b.motel_id = m.id
        WHERE b.id = ? AND m.user_id = ?
    ");
    $stmt->bind_param('ii', $bookingId, $owner_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        $actionMessage = 'Không tìm thấy đơn hoặc bạn không có quyền.';
        $actionType = 'danger';
    } else {
        $tenantId = (int)$row['tenant_id'];
        $status = (string)$row['status'];
        $newStatus = null;
        $tenantTitle = '';
        $tenantBody = '';
        $tenantLink = 'user/my-bookings.php';

        if ($action === 'accept' && $status === 'pending') {
            $newStatus = 'accepted';
            $tenantTitle = 'Đơn đặt phòng được chấp nhận';
            $tenantBody = 'Chủ trọ đã chấp nhận yêu cầu của bạn cho phòng: ' . ($row['motel_title'] ?? '') . '. Bạn có thể tiếp tục theo hướng dẫn đặt cọc / liên hệ chủ trọ.';
        } elseif ($action === 'reject' && $status === 'pending') {
            $newStatus = 'rejected';
            $tenantTitle = 'Đơn đặt phòng bị từ chối';
            $tenantBody = 'Chủ trọ đã từ chối yêu cầu của bạn cho phòng: ' . ($row['motel_title'] ?? '') . '.';
        } elseif ($action === 'mark_paid' && $status === 'accepted') {
            $newStatus = 'paid';
            $tenantTitle = 'Chủ trọ xác nhận đã nhận cọc';
            $tenantBody = 'Chủ trọ xác nhận đã nhận tiền cọc cho phòng: ' . ($row['motel_title'] ?? '') . '.';
        } elseif ($action === 'complete' && ($status === 'paid' || $status === 'accepted')) {
            $newStatus = 'completed';
            $tenantTitle = 'Đơn đặt phòng hoàn tất';
            $tenantBody = 'Chủ trọ đã đánh dấu hoàn tất cho phòng: ' . ($row['motel_title'] ?? '') . '. Bạn có thể để lại đánh giá trên trang phòng.';
            $tenantLink = 'user/motel-detail.php?id=' . (int)$row['motel_id'];
        }

        if ($newStatus !== null) {
            $u = $db->prepare('UPDATE bookings SET status = ? WHERE id = ?');
            $u->bind_param('si', $newStatus, $bookingId);
            if ($u->execute()) {
                qlpt_send_notification($db, $tenantId, 'booking_status', $tenantTitle, $tenantBody, $tenantLink);
                $actionMessage = 'Đã cập nhật trạng thái đơn và gửi thông báo cho người thuê.';
                $actionType = 'success';
            } else {
                $actionMessage = 'Không thể cập nhật trạng thái.';
                $actionType = 'danger';
            }
            $u->close();
        } else {
            $actionMessage = 'Thao tác không hợp lệ với trạng thái hiện tại.';
            $actionType = 'warning';
        }
    }
}

// Get total bookings
$stmt = $db->prepare("SELECT COUNT(*) as count FROM bookings b JOIN motels m ON b.motel_id = m.id WHERE m.user_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['count'];
$total_pages = ceil($total / $limit);
$stmt->close();

// Get bookings
$stmt = $db->prepare("
    SELECT b.*, m.title as motel_title, u.name as tenant_name, u.email as tenant_email
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    JOIN users u ON b.user_id = u.id
    WHERE m.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $owner_id, $limit, $offset);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php
function owner_booking_status_label(string $status): string
{
    return [
        'pending' => 'Chờ xử lý',
        'paid' => 'Đã cọc',
        'accepted' => 'Đã chấp nhận',
        'completed' => 'Hoàn thành',
        'rejected' => 'Từ chối',
        'cancelled' => 'Đã hủy',
    ][strtolower($status)] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đặt phòng - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 30px; }
        .booking-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; border-left: 4px solid #667eea; }
        .booking-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .booking-title { font-size: 18px; font-weight: 600; color: #333; }
        .booking-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; color: #666; font-size: 14px; }
        .booking-info-item { padding: 10px; background: #f8f9fa; border-radius: 6px; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 60px 30px; background: white; border-radius: 12px; }
    </style>
    <link href="../assets/css/modern.css" rel="stylesheet">
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
                <?php
                $ownerNavActive = 'bookings';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-calendar-check"></i> Đơn đặt phòng của tôi
                    </h1>

                    <?php if ($actionMessage !== ''): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($actionType); ?>"><?php echo htmlspecialchars($actionMessage); ?></div>
                    <?php endif; ?>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-title"><?php echo htmlspecialchars($booking['motel_title']); ?></div>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars(owner_booking_status_label((string)$booking['status'])); ?></span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-info-item">
                                        <strong>Khách:</strong> <?php echo htmlspecialchars($booking['tenant_name']); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($booking['tenant_email']); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Check-in:</strong> <?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Check-out:</strong> <?php echo date('d/m/Y', strtotime($booking['check_out_date'])); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Đặt cọc:</strong> <?php echo number_format($booking['deposit_amount']); ?> VNĐ
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Chấp nhận đơn này?');">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                            <button type="submit" name="booking_action" value="accept" class="btn btn-success btn-sm">Chấp nhận</button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Từ chối đơn này?');">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                            <button type="submit" name="booking_action" value="reject" class="btn btn-outline-danger btn-sm">Từ chối</button>
                                        </form>
                                    <?php elseif ($booking['status'] === 'accepted'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận đã nhận tiền cọc từ người thuê?');">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                            <button type="submit" name="booking_action" value="mark_paid" class="btn btn-primary btn-sm">Đã nhận cọc</button>
                                        </form>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Đánh dấu hoàn tất?');">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                            <button type="submit" name="booking_action" value="complete" class="btn btn-outline-secondary btn-sm">Hoàn tất</button>
                                        </form>
                                    <?php elseif ($booking['status'] === 'paid'): ?>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Đánh dấu hoàn tất thuê?');">
                                            <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                            <button type="submit" name="booking_action" value="complete" class="btn btn-primary btn-sm">Hoàn tất</button>
                                        </form>
                                    <?php endif; ?>
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
                            <p style="color: #999;">Không có đơn đặt phòng</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
