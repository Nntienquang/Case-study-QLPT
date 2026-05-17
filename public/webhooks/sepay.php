<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/SepayPayment.php';
require_once __DIR__ . '/../../core/NotificationHelper.php';

header('Content-Type: application/json; charset=UTF-8');

function sepay_json(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function sepay_extract_payment_code(array $payload): string
{
    $candidates = [
        (string)($payload['code'] ?? ''),
        (string)($payload['content'] ?? ''),
        (string)($payload['description'] ?? ''),
    ];

    foreach ($candidates as $candidate) {
        if (preg_match('/\bPAY[0-9A-Z]{8,}\b/i', $candidate, $matches)) {
            return strtoupper($matches[0]);
        }
    }

    return '';
}

$rawBody = file_get_contents('php://input') ?: '';

$hasConfiguredApiKey = SepayPayment::webhookApiKey() !== '' && SepayPayment::webhookApiKey() !== 'change-me';
$hasConfiguredSecret = SepayPayment::webhookSecret() !== '';
if (
    ($hasConfiguredApiKey || $hasConfiguredSecret) &&
    !SepayPayment::verifyApiKeyHeader() &&
    !SepayPayment::verifyHmacSignature($rawBody) &&
    !SepayPayment::verifyLegacySecretHeader() &&
    !SepayPayment::isTrustedSepayIp()
) {
    sepay_json(401, ['success' => false, 'message' => 'Invalid SePay signature or secret']);
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    sepay_json(400, ['success' => false, 'message' => 'Invalid JSON payload']);
}

$sepayId = (string)($payload['id'] ?? '');
$transferType = strtolower((string)($payload['transferType'] ?? ''));
$transferAmount = (int)($payload['transferAmount'] ?? 0);
$referenceCode = (string)($payload['referenceCode'] ?? '');
$accountNumber = (string)($payload['accountNumber'] ?? '');
$paymentCode = sepay_extract_payment_code($payload);

if ($sepayId === '' || $paymentCode === '') {
    sepay_json(200, ['success' => true, 'message' => 'Ignored: missing transaction id or payment code']);
}

if ($transferType !== 'in') {
    sepay_json(200, ['success' => true, 'message' => 'Ignored: not an incoming transfer']);
}

if (SepayPayment::bankAccount() !== '' && $accountNumber !== '' && $accountNumber !== SepayPayment::bankAccount()) {
    sepay_json(200, ['success' => true, 'message' => 'Ignored: bank account mismatch']);
}

/** @var mysqli $conn */
$db = new Database($conn);

$conn->query("
    CREATE TABLE IF NOT EXISTS sepay_webhook_events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sepay_id VARCHAR(80) NOT NULL,
        reference_code VARCHAR(120) DEFAULT NULL,
        payment_code VARCHAR(40) DEFAULT NULL,
        transfer_amount INT NOT NULL DEFAULT 0,
        payload LONGTEXT NOT NULL,
        processed_status VARCHAR(30) NOT NULL DEFAULT 'received',
        processed_message VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_sepay_webhook_events_sepay_id (sepay_id),
        KEY idx_sepay_webhook_events_payment_code (payment_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $conn->prepare("
    INSERT IGNORE INTO sepay_webhook_events
        (sepay_id, reference_code, payment_code, transfer_amount, payload, processed_status)
    VALUES (?, ?, ?, ?, ?, 'received')
");
$stmt->bind_param('sssis', $sepayId, $referenceCode, $paymentCode, $transferAmount, $rawBody);
$stmt->execute();
$inserted = $stmt->affected_rows;
$stmt->close();

if ($inserted === 0) {
    sepay_json(200, ['success' => true, 'message' => 'Duplicate webhook ignored']);
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("
        SELECT p.*, b.user_id, b.owner_id, b.motel_id, b.booking_code
        FROM payments p
        JOIN bookings b ON b.id = p.booking_id
        WHERE p.payment_code = ?
        LIMIT 1
        FOR UPDATE
    ");
    $stmt->bind_param('s', $paymentCode);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        throw new RuntimeException('Payment code not found');
    }

    if ((string)$payment['payment_status'] === 'paid') {
        $stmt = $conn->prepare("UPDATE sepay_webhook_events SET processed_status = 'duplicate_paid', processed_message = 'Payment already paid' WHERE sepay_id = ?");
        $stmt->bind_param('s', $sepayId);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        sepay_json(200, ['success' => true, 'message' => 'Payment already paid']);
    }

    $expectedAmount = (int)$payment['amount'];
    if ($transferAmount < $expectedAmount) {
        $stmt = $conn->prepare("UPDATE sepay_webhook_events SET processed_status = 'amount_mismatch', processed_message = ? WHERE sepay_id = ?");
        $message = 'Transfer amount is lower than expected';
        $stmt->bind_param('ss', $message, $sepayId);
        $stmt->execute();
        $stmt->close();
        throw new RuntimeException($message);
    }

    $gatewayResponse = json_encode([
        'provider' => 'sepay',
        'webhook' => $payload,
        'matched_at' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
    $legacyPaymentStatus = 'held';
    $paidStatus = 'paid';
    $platformFee = (int)ceil($expectedAmount * 0.05);
    $paymentId = (int)$payment['id'];
    $bookingId = (int)$payment['booking_id'];
    $tenantId = (int)$payment['user_id'];
    $ownerId = (int)$payment['owner_id'];

    $stmt = $conn->prepare("
        UPDATE payments
        SET payment_status = ?, status = ?, transaction_code = ?, gateway_response = ?, fee = ?, paid_at = NOW(), updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param('ssssii', $paidStatus, $legacyPaymentStatus, $referenceCode, $gatewayResponse, $platformFee, $paymentId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'paid', booking_status = 'paid', status = 'paid', updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE booking_room_holds SET hold_status = 'converted', updated_at = NOW() WHERE booking_id = ?");
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $stmt->close();

    $admin = $conn->query("SELECT id FROM users WHERE role = 'admin' AND status = 'approved' ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $adminId = (int)($admin['id'] ?? 0);
    if ($adminId > 0) {
        $stmt = $conn->prepare("INSERT INTO transactions (from_user, to_user, amount, fee, type, booking_id, created_at) VALUES (?, ?, ?, ?, 'deposit', ?, NOW())");
        $stmt->bind_param('iiiii', $tenantId, $adminId, $expectedAmount, $platformFee, $bookingId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("INSERT INTO transactions (to_user, amount, type, booking_id, created_at) VALUES (?, ?, 'fee', ?, NOW())");
        $stmt->bind_param('iii', $adminId, $platformFee, $bookingId);
        $stmt->execute();
        $stmt->close();
    }

    qlpt_send_notification(
        $db,
        $tenantId,
        'payment',
        'Thanh toán đã được ghi nhận',
        'Khoản đặt cọc cho booking ' . $payment['booking_code'] . ' đã được hệ thống xác nhận và đang được admin giữ an toàn.',
        'user/my-bookings.php'
    );

    qlpt_send_notification(
        $db,
        $ownerId,
        'payment_paid',
        'Khách đã đặt cọc',
        'Booking ' . $payment['booking_code'] . ' đã thanh toán cọc. Bạn có thể kiểm tra và chấp nhận booking.',
        'owner/bookings.php'
    );

    $stmt = $conn->prepare("UPDATE sepay_webhook_events SET processed_status = 'paid', processed_message = 'Payment matched and held' WHERE sepay_id = ?");
    $stmt->bind_param('s', $sepayId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    sepay_json(200, ['success' => true, 'message' => 'Payment confirmed']);
} catch (Throwable $e) {
    $conn->rollback();

    $message = $e->getMessage();
    $stmt = $conn->prepare("UPDATE sepay_webhook_events SET processed_status = 'failed', processed_message = ? WHERE sepay_id = ?");
    $stmt->bind_param('ss', $message, $sepayId);
    $stmt->execute();
    $stmt->close();

    sepay_json(200, ['success' => true, 'message' => 'Webhook received but not applied: ' . $message]);
}
