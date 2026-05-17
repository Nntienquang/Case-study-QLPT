<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../../core/NotificationHelper.php';
@require_once '../components/PublicNav.php';

session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

/** @var mysqli $conn */
$db = new Database($conn);
$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $conn->prepare("
    SELECT b.*, p.id AS payment_id, p.payment_code, p.amount, p.payment_method, p.payment_status AS current_payment_status,
           m.title AS motel_title, m.address, m.user_id AS owner_id
    FROM bookings b
    JOIN payments p ON p.booking_id = b.id
    JOIN motels m ON m.id = b.motel_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $bookingId, $userId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    header('Location: my-bookings.php');
    exit;
}

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_transfer') {
    if (($payment['current_payment_status'] ?? '') === 'pending') {
        $transactionCode = trim((string)($_POST['transaction_code'] ?? ''));
        $gateway = json_encode([
            'type' => 'manual_bank_transfer',
            'confirmed_by_user_at' => date('c'),
            'note' => trim((string)($_POST['note'] ?? '')),
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $conn->prepare("
            UPDATE payments
            SET payment_status = 'processing', transaction_code = NULLIF(?, ''), gateway_response = ?, updated_at = NOW()
            WHERE id = ? AND payment_status = 'pending'
        ");
        $stmt->bind_param('ssi', $transactionCode, $gateway, $payment['payment_id']);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'processing', updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $stmt->close();

        qlpt_send_notification(
            $db,
            (int)$payment['owner_id'],
            'payment_processing',
            'Khách đã báo chuyển khoản',
            'Booking ' . $payment['booking_code'] . ' đang chờ admin xác nhận thanh toán.',
            'owner/bookings.php'
        );

        $message = 'Đã ghi nhận thông tin chuyển khoản. Admin sẽ xác nhận trước khi booking được chốt.';
        $payment['current_payment_status'] = 'processing';
    } else {
        $message = 'Thanh toán này không còn ở trạng thái chờ chuyển khoản.';
        $messageType = 'warning';
    }
}

function pay_e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pay_money($value): string
{
    return number_format((int)$value) . ' VNĐ';
}

function pay_status_label(string $status): string
{
    return [
        'pending' => 'Chờ thanh toán',
        'processing' => 'Chờ admin xác nhận',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thất bại',
        'cancelled' => 'Đã hủy',
        'refunded' => 'Đã hoàn tiền',
    ][$status] ?? $status;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán cọc - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .payment-shell { padding: 34px 0 56px; }
        .pay-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.08); padding: 24px; }
        .pay-code { font-size: 30px; font-weight: 900; letter-spacing: 0; color: #101828; }
        .qr-box { border: 2px dashed #cbd5e1; border-radius: 16px; min-height: 260px; display: grid; place-items: center; text-align: center; color: #64748b; background: #f8fafc; }
        .info-row { display: flex; justify-content: space-between; gap: 18px; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
    </style>
</head>
<body>
<?php qlpt_render_public_nav(['base' => '../', 'active' => 'rooms']); ?>

<main class="payment-shell">
    <div class="container-lg">
        <a href="my-bookings.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left"></i> Đơn đặt của tôi</a>

        <?php if ($message !== ''): ?>
            <div class="alert alert-<?php echo pay_e($messageType); ?>"><?php echo pay_e($message); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="pay-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="text-muted fw-semibold">Mã booking</div>
                            <div class="pay-code"><?php echo pay_e($payment['booking_code']); ?></div>
                        </div>
                        <span class="badge bg-warning text-dark"><?php echo pay_e(pay_status_label((string)$payment['current_payment_status'])); ?></span>
                    </div>

                    <div class="info-row"><strong>Phòng</strong><span><?php echo pay_e($payment['motel_title']); ?></span></div>
                    <div class="info-row"><strong>Địa chỉ</strong><span><?php echo pay_e($payment['address']); ?></span></div>
                    <div class="info-row"><strong>Mã thanh toán</strong><span><?php echo pay_e($payment['payment_code']); ?></span></div>
                    <div class="info-row"><strong>Số tiền cần thanh toán</strong><span class="fw-bold text-primary"><?php echo pay_money($payment['amount']); ?></span></div>
                    <div class="info-row"><strong>Hạn giữ chỗ</strong><span><?php echo !empty($payment['expires_at']) ? date('d/m/Y H:i', strtotime($payment['expires_at'])) : 'N/A'; ?></span></div>

                    <div class="alert alert-info mt-4 mb-0">
                        Nội dung chuyển khoản bắt buộc: <strong><?php echo pay_e($payment['payment_code']); ?></strong>.
                        Sau khi admin xác nhận, booking sẽ chuyển sang trạng thái đã thanh toán.
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="pay-card">
                    <h4 class="fw-bold mb-3">Thanh toán chuyển khoản</h4>
                    <div class="qr-box mb-3">
                        <div>
                            <i class="fas fa-qrcode fa-4x mb-3"></i>
                            <div class="fw-bold">QR MoMo/Bank sẽ tích hợp tại đây</div>
                            <div class="small">Schema hiện đã sẵn sàng cho callback/gateway_response.</div>
                        </div>
                    </div>

                    <?php if (($payment['current_payment_status'] ?? '') === 'pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="confirm_transfer">
                            <input type="hidden" name="booking_id" value="<?php echo (int)$bookingId; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mã giao dịch nếu có</label>
                                <input type="text" name="transaction_code" class="form-control" placeholder="VD: FTxxxx / MoMo transId">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Ghi chú</label>
                                <textarea name="note" class="form-control" rows="3"></textarea>
                            </div>
                            <button class="btn btn-primary w-100" type="submit"><i class="fas fa-paper-plane"></i> Tôi đã chuyển khoản</button>
                        </form>
                    <?php else: ?>
                        <a class="btn btn-primary w-100" href="my-bookings.php">Theo dõi booking</a>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
