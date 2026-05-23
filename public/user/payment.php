<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/SepayPayment.php';
require_once __DIR__ . '/../../core/NotificationHelper.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../components/PublicNav.php';

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
           p.transaction_code, p.paid_at, m.title AS motel_title, m.address, m.user_id AS owner_id
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_transfer' && !Csrf::validateRequest('payment_confirm_transfer')) {
    $message = 'Phiên xác nhận thanh toán đã hết hạn. Vui lòng thử lại.';
    $messageType = 'danger';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_transfer') {
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

        $message = 'Đã ghi nhận thông tin chuyển khoản. Nếu webhook SePay chưa về, admin vẫn có thể đối soát thủ công.';
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
        'processing' => 'Chờ xác nhận',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thất bại',
        'cancelled' => 'Đã hủy',
        'refunded' => 'Đã hoàn tiền',
    ][$status] ?? $status;
}

$paymentCode = (string)$payment['payment_code'];
$amount = (int)$payment['amount'];
$qrUrl = SepayPayment::isConfigured() ? SepayPayment::qrUrl($amount, $paymentCode) : '';
$isPaid = ($payment['current_payment_status'] ?? '') === 'paid';
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
        .payment-shell { padding: 124px 0 56px; }
        .pay-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.08); padding: 24px; }
        .pay-code { font-size: 30px; font-weight: 900; letter-spacing: 0; color: #101828; overflow-wrap: anywhere; }
        .qr-box { border: 1px solid #dbe4f0; border-radius: 16px; min-height: 280px; display: grid; place-items: center; text-align: center; color: #64748b; background: #f8fafc; padding: 18px; }
        .qr-box img { width: min(280px, 100%); height: auto; display: block; }
        .info-row { display: flex; justify-content: space-between; gap: 18px; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
        .info-row span:last-child { text-align: right; overflow-wrap: anywhere; }
        .copy-btn { min-width: 92px; }
        .status-dot { width: 10px; height: 10px; border-radius: 999px; display: inline-block; background: #f59e0b; }
        .status-dot.paid { background: #16a34a; }
        @media (max-width: 768px) {
            .payment-shell { padding-top: 112px; }
            .pay-card { padding: 20px; }
        }
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

        <?php if (!SepayPayment::isConfigured()): ?>
            <div class="alert alert-warning">
                Chưa cấu hình tài khoản nhận tiền SePay trong file .env. Hãy nhập SEPAY_BANK_NAME và SEPAY_BANK_ACCOUNT.
            </div>
        <?php endif; ?>

        <div id="paidAlert" class="alert alert-success <?php echo $isPaid ? '' : 'd-none'; ?>">
            Thanh toán đã thành công. Tiền cọc đang được admin giữ an toàn cho đến khi bạn nhận phòng.
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <section class="pay-card">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <div class="text-muted fw-semibold">Mã booking</div>
                            <div class="pay-code"><?php echo pay_e($payment['booking_code']); ?></div>
                        </div>
                        <span id="statusBadge" class="badge <?php echo $isPaid ? 'bg-success' : 'bg-warning text-dark'; ?>">
                            <span id="statusDot" class="status-dot <?php echo $isPaid ? 'paid' : ''; ?>"></span>
                            <span id="statusText"><?php echo pay_e(pay_status_label((string)$payment['current_payment_status'])); ?></span>
                        </span>
                    </div>

                    <div class="info-row"><strong>Phòng</strong><span><?php echo pay_e($payment['motel_title']); ?></span></div>
                    <div class="info-row"><strong>Địa chỉ</strong><span><?php echo pay_e($payment['address']); ?></span></div>
                    <div class="info-row"><strong>Mã thanh toán</strong><span class="fw-bold text-primary"><?php echo pay_e($paymentCode); ?></span></div>
                    <div class="info-row"><strong>Số tiền cần thanh toán</strong><span class="fw-bold text-primary"><?php echo pay_money($amount); ?></span></div>
                    <div class="info-row"><strong>Hạn giữ chỗ</strong><span><?php echo !empty($payment['expires_at']) ? date('d/m/Y H:i', strtotime($payment['expires_at'])) : 'N/A'; ?></span></div>

                    <div class="alert alert-info mt-4 mb-0">
                        Nội dung chuyển khoản bắt buộc là <strong><?php echo pay_e($paymentCode); ?></strong>.
                        SePay sẽ gửi webhook về hệ thống sau khi ngân hàng ghi nhận tiền vào. Hệ thống chỉ xác nhận khi đúng mã và số tiền chuyển khoản không thấp hơn số tiền cần thanh toán.
                    </div>
                </section>
            </div>

            <div class="col-lg-5">
                <section class="pay-card">
                    <h4 class="fw-bold mb-3">Chuyển khoản ngân hàng</h4>
                    <div class="qr-box mb-3">
                        <?php if ($qrUrl !== ''): ?>
                            <img src="<?php echo pay_e($qrUrl); ?>" alt="QR thanh toán SePay">
                        <?php else: ?>
                            <div>
                                <i class="fas fa-qrcode fa-4x mb-3"></i>
                                <div class="fw-bold">Chưa thể tạo QR</div>
                                <div class="small">Thiếu cấu hình ngân hàng trong .env.</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="info-row"><strong>Ngân hàng</strong><span><?php echo pay_e(SepayPayment::bankName() ?: 'Chưa cấu hình'); ?></span></div>
                    <div class="info-row"><strong>Số tài khoản</strong><span><?php echo pay_e(SepayPayment::bankAccount() ?: 'Chưa cấu hình'); ?></span></div>
                    <div class="info-row"><strong>Chủ tài khoản</strong><span><?php echo pay_e(SepayPayment::accountName() ?: 'Chưa cấu hình'); ?></span></div>
                    <div class="info-row"><strong>Số tiền</strong><span><?php echo pay_money($amount); ?></span></div>
                    <div class="info-row"><strong>Nội dung</strong><span><?php echo pay_e($paymentCode); ?></span></div>

                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-outline-primary copy-btn" type="button" data-copy="<?php echo pay_e($paymentCode); ?>">
                            <i class="fas fa-copy"></i> Copy nội dung
                        </button>
                        <a id="doneButton" class="btn btn-primary <?php echo $isPaid ? '' : 'd-none'; ?>" href="my-bookings.php">
                            Theo dõi booking
                        </a>
                    </div>

                    <?php if (($payment['current_payment_status'] ?? '') === 'pending'): ?>
                        <form method="POST" class="mt-3">
                            <?php echo Csrf::field('payment_confirm_transfer'); ?>
                            <input type="hidden" name="action" value="confirm_transfer">
                            <input type="hidden" name="booking_id" value="<?php echo (int)$bookingId; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mã giao dịch nếu cần đối soát thủ công</label>
                                <input type="text" name="transaction_code" class="form-control" placeholder="VD: FTxxxx">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Ghi chú</label>
                                <textarea name="note" class="form-control" rows="2"></textarea>
                            </div>
                            <button class="btn btn-outline-secondary w-100" type="submit">
                                Tôi đã chuyển khoản nhưng chưa tự xác nhận
                            </button>
                        </form>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('[data-copy]').forEach((button) => {
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.copy);
        button.innerHTML = '<i class="fas fa-check"></i> Đã copy';
        setTimeout(() => button.innerHTML = '<i class="fas fa-copy"></i> Copy nội dung', 1800);
    });
});

const bookingId = <?php echo (int)$bookingId; ?>;
const paidAlert = document.getElementById('paidAlert');
const doneButton = document.getElementById('doneButton');
const statusBadge = document.getElementById('statusBadge');
const statusDot = document.getElementById('statusDot');
const statusText = document.getElementById('statusText');

async function pollPaymentStatus() {
    try {
        const response = await fetch(`payment-status.php?booking_id=${bookingId}`, {headers: {'Accept': 'application/json'}});
        const data = await response.json();
        if (!data.success) return;

        if (data.payment_status === 'paid') {
            paidAlert.classList.remove('d-none');
            doneButton.classList.remove('d-none');
            statusBadge.className = 'badge bg-success';
            statusDot.classList.add('paid');
            statusText.textContent = 'Đã thanh toán';
            clearInterval(window.paymentPoller);
        }
    } catch (error) {
        console.error('Payment polling failed', error);
    }
}

window.paymentPoller = setInterval(pollPaymentStatus, 3000);
pollPaymentStatus();
</script>
</body>
</html>
