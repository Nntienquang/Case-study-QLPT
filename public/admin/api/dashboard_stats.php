<?php
ini_set('display_errors', '0');
define('ADMIN_JSON_API', true);
require_once __DIR__ . '/../../admin_init.php';
require_once __DIR__ . '/_bootstrap.php';

try {
    $adminId = (int)($_SESSION['user_id'] ?? 0);

    $commissionStmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS value FROM transactions WHERE type = 'fee' AND to_user = ?");
    $commission = 0;
    if ($commissionStmt) {
        $commissionStmt->bind_param('i', $adminId);
        $commissionStmt->execute();
        $commission = (int)(($commissionStmt->get_result()->fetch_assoc() ?: [])['value'] ?? 0);
        $commissionStmt->close();
    }

    $hasPageViews = admin_api_table_exists($conn, 'page_views');

    admin_api_json([
        'owner_pending' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM users WHERE role = 'owner' AND owner_verification_status = 'submitted'"),
        'room_pending' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM motels WHERE status = 'pending'"),
        'report_pending' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM reports WHERE status = 'pending'"),
        'booking_pending' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM bookings WHERE status = 'pending'"),
        'payment_pending' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM payments WHERE payment_status IN ('pending', 'processing') OR status = 'held'"),
        'total_rooms' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM motels"),
        'total_users' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM users"),
        'total_owners' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM users WHERE role = 'owner'"),
        'monthly_revenue' => admin_api_scalar($conn, "SELECT COALESCE(SUM(amount), 0) AS value FROM payments WHERE payment_status = 'paid' AND MONTH(COALESCE(paid_at, created_at)) = MONTH(CURDATE()) AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURDATE())"),
        'total_commission' => $commission,
        'total_reviews' => admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM reviews"),
        'page_views_today' => $hasPageViews ? admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM page_views WHERE DATE(viewed_at) = CURDATE()") : 0,
        'page_views_month' => $hasPageViews ? admin_api_scalar($conn, "SELECT COUNT(*) AS value FROM page_views WHERE MONTH(viewed_at) = MONTH(CURDATE()) AND YEAR(viewed_at) = YEAR(CURDATE())") : 0,
    ]);
} catch (Throwable $exception) {
    admin_api_error(500, 'Không thể tải số liệu dashboard.');
}
