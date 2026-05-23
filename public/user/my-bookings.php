<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../../core/Csrf.php';


session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_received') {
    if (!Csrf::validateRequest('tenant_booking_action')) {
        $_SESSION['booking_message'] = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        $_SESSION['booking_message_type'] = 'danger';
        header('Location: my-bookings.php');
        exit;
    }

    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare("
            SELECT b.id, b.owner_id, b.motel_id, b.status, b.booking_status, b.payment_status, m.title
            FROM bookings b
            JOIN motels m ON m.id = b.motel_id
            WHERE b.id = ? AND b.user_id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param('ii', $bookingId, $user_id);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) {
            throw new RuntimeException('Không tìm thấy booking.');
        }

        if (($booking['status'] ?? '') !== 'accepted' || ($booking['payment_status'] ?? '') !== 'paid') {
            throw new RuntimeException('Chỉ xác nhận nhận phòng với booking đã được chủ phòng chấp nhận và đã đặt cọc.');
        }

        $stmt = $conn->prepare("UPDATE bookings SET status = 'completed', booking_status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $stmt->close();

        $title = 'Khách thuê đã xác nhận nhận phòng';
        $ownerBody = 'Khách thuê đã xác nhận nhận phòng "' . $booking['title'] . '". Admin sẽ kiểm tra và giải ngân tiền cọc.';
        $adminBody = 'Booking #' . $bookingId . ' đã được khách thuê xác nhận nhận phòng. Vui lòng kiểm tra payment để giải ngân cho chủ phòng.';

        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?, 'booking_completed', ?, ?, 'owner/bookings.php', NOW())");
        $ownerId = (int)$booking['owner_id'];
        $stmt->bind_param('iss', $ownerId, $title, $ownerBody);
        $stmt->execute();
        $stmt->close();

        $admins = $conn->query("SELECT id FROM users WHERE role = 'admin' AND status = 'approved'");
        while ($admin = $admins->fetch_assoc()) {
            $adminId = (int)$admin['id'];
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, body, link, created_at) VALUES (?, 'release_ready', ?, ?, 'admin/payments.php', NOW())");
            $stmt->bind_param('iss', $adminId, $title, $adminBody);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();
        $_SESSION['booking_message'] = 'Đã xác nhận nhận phòng. Admin sẽ kiểm tra và giải ngân tiền cọc cho chủ phòng.';
        $_SESSION['booking_message_type'] = 'success';
    } catch (Throwable $e) {
        $conn->rollback();
        $_SESSION['booking_message'] = $e->getMessage();
        $_SESSION['booking_message_type'] = 'danger';
    }

    header('Location: my-bookings.php');
    exit;
}

if (isset($_SESSION['booking_message'])) {
    $message = (string)$_SESSION['booking_message'];
    $messageType = (string)($_SESSION['booking_message_type'] ?? 'success');
    unset($_SESSION['booking_message'], $_SESSION['booking_message_type']);
}

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
    SELECT b.*, p.id AS payment_id, p.payment_code, p.payment_status AS current_payment_status, m.title, m.price, m.address
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<?php
function tenant_booking_status_label(string $status): string
{
    return [
        'pending' => 'Đang chờ duyệt',
        'waiting_payment' => 'Chờ thanh toán',
        'paid' => 'Đã đặt cọc',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang xử lý',
        'failed' => 'Thanh toán thất bại',
        'refunded' => 'Đã hoàn tiền',
        'accepted' => 'Đã được chấp nhận',
        'completed' => 'Hoàn tất',
        'rejected' => 'Bị từ chối',
        'cancelled' => 'Đã hủy',
    ][strtolower($status)] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn đặt của tôi - QuanLyPhongTro</title>
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
        .booking-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .booking-info-item { padding: 10px; background: #f8f9fa; border-radius: 6px; font-size: 13px; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d4edda; color: #155724; }
        .badge-completed { background: #cfe2ff; color: #084298; }
        .badge-cancelled { background: #f8d7da; color: #721c24; }
        .empty-state { text-align: center; padding: 60px 30px; background: white; border-radius: 12px; }
    </style>
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>
<body class="workbench">
    <?php 
    @require_once __DIR__ . '/../components/PublicNav.php'; 
    qlpt_render_public_nav(['base' => '../', 'active' => 'user']); 
    ?>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <?php
                $userNavActive = 'bookings';
                $userNavVariant = 'workbench';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </aside>

            <section>
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 10px;">
                        <i class="fas fa-calendar-check"></i> Đơn đặt của tôi
                    </h1>
                    <p class="text-muted mb-4">Lịch sử đặt cọc / đặt phòng và trạng thái xử lý của chủ trọ.</p>
                    <?php if ($message !== ''): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if (count($bookings) > 0): ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-header">
                                    <div class="booking-title">
                                        <a href="motel-detail.php?id=<?php echo (int)$booking['motel_id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($booking['title']); ?>
                                        </a>
                                    </div>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars(tenant_booking_status_label((string)($booking['booking_status'] ?? $booking['status']))); ?></span>
                                </div>
                                <div class="booking-info">
                                    <div class="booking-info-item">
                                        <strong>Địa chỉ:</strong><br><?php echo htmlspecialchars($booking['address']); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Check-in:</strong><br><?php echo date('d/m/Y', strtotime($booking['check_in_date'])); ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Check-out:</strong><br><?php echo !empty($booking['check_out_date']) ? date('d/m/Y', strtotime($booking['check_out_date'])) : 'Chưa xác định'; ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Đặt cọc:</strong><br><span style="color: #667eea; font-weight: 700;"><?php echo number_format($booking['deposit_amount']); ?> VNĐ</span>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Thanh toán:</strong><br><?php echo htmlspecialchars(tenant_booking_status_label((string)($booking['current_payment_status'] ?? $booking['payment_status'] ?? 'pending'))); ?>
                                        <?php if (in_array((string)($booking['current_payment_status'] ?? ''), ['pending','processing'], true)): ?>
                                            <br><a href="payment.php?booking_id=<?php echo (int)$booking['id']; ?>" class="btn btn-sm btn-primary mt-2">Xem thanh toán</a>
                                        <?php endif; ?>
                                        <?php if (($booking['status'] ?? '') === 'accepted' && ($booking['payment_status'] ?? '') === 'paid'): ?>
                                            <form method="POST" class="mt-2" onsubmit="return confirm('Bạn xác nhận đã nhận phòng đúng thông tin và cho phép admin giải ngân cho chủ phòng?');">
                                                <?php echo Csrf::field('tenant_booking_action'); ?>
                                                <input type="hidden" name="action" value="confirm_received">
                                                <input type="hidden" name="booking_id" value="<?php echo (int)$booking['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Tôi đã nhận phòng</button>
                                            </form>
                                        <?php elseif (($booking['status'] ?? '') === 'completed' && ($booking['current_payment_status'] ?? '') === 'paid'): ?>
                                            <div class="small text-success mt-2">Đã xác nhận nhận phòng. Chờ admin giải ngân cho chủ phòng.</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="booking-info-item">
                                        <strong>Ngày đặt:</strong><br><?php echo date('d/m/Y H:i', strtotime($booking['created_at'])); ?>
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
                                <i class="fas fa-search"></i> Tìm phòng
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                        </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
