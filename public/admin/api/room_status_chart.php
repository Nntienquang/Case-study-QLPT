<?php
ini_set('display_errors', '0');
define('ADMIN_JSON_API', true);
require_once __DIR__ . '/../../admin_init.php';
require_once __DIR__ . '/_bootstrap.php';

try {
    $statuses = ['pending', 'approved', 'rejected', 'hidden'];
    $counts = array_fill_keys($statuses, 0);
    $stmt = $conn->prepare("SELECT status, COUNT(*) AS total FROM motels WHERE status IN ('pending', 'approved', 'rejected', 'hidden') GROUP BY status");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int)$row['total'];
            }
        }
        $stmt->close();
    }

    admin_api_json([
        'labels' => $statuses,
        'series' => array_values($counts),
    ]);
} catch (Throwable $exception) {
    admin_api_error(500, 'Không thể tải biểu đồ phòng.');
}
