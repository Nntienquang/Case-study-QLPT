<?php

require_once __DIR__ . '/../../config/database.php';

session_start();
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$bookingId = (int)($_GET['booking_id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

/** @var mysqli $conn */
$stmt = $conn->prepare("
    SELECT b.status AS booking_status, b.payment_status, p.payment_status AS payment_status_current, p.paid_at
    FROM bookings b
    JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $bookingId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'payment_status' => $row['payment_status_current'],
    'booking_status' => $row['booking_status'],
    'paid_at' => $row['paid_at'],
], JSON_UNESCAPED_UNICODE);

