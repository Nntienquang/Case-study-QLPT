<?php
ini_set('display_errors', '0');
define('ADMIN_JSON_API', true);
require_once __DIR__ . '/../../admin_init.php';
require_once __DIR__ . '/_bootstrap.php';

try {
    $today = new DateTimeImmutable('today');
    $range = (string)($_GET['range'] ?? '7');
    $start = $today->sub(new DateInterval('P6D'));
    $end = $today;
    if ($range === '30') {
        $start = $today->sub(new DateInterval('P29D'));
    } elseif ($range === 'month') {
        $start = $today->modify('first day of this month');
    } elseif ($range === 'year') {
        $start = $today->setDate((int)$today->format('Y'), 1, 1);
    }

    $monthly = $range === 'year';
    $viewsByBucket = [];
    if (admin_api_table_exists($conn, 'page_views')) {
        $bucket = $monthly ? "DATE_FORMAT(viewed_at, '%Y-%m')" : 'DATE(viewed_at)';
        $startSql = $start->format('Y-m-d 00:00:00');
        $endSql = $end->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00');
        $stmt = $conn->prepare(
            "SELECT {$bucket} AS bucket, COUNT(*) AS total
             FROM page_views
             WHERE viewed_at >= ? AND viewed_at < ?
             GROUP BY bucket
             ORDER BY bucket"
        );
        if ($stmt) {
            $stmt->bind_param('ss', $startSql, $endSql);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $viewsByBucket[(string)$row['bucket']] = (int)$row['total'];
            }
            $stmt->close();
        }
    }

    $labels = [];
    $views = [];
    $cursor = $monthly ? $start->modify('first day of this month') : $start;
    $last = $monthly ? $end->modify('first day of this month') : $end;
    $step = $monthly ? new DateInterval('P1M') : new DateInterval('P1D');
    while ($cursor <= $last) {
        $bucketKey = $cursor->format($monthly ? 'Y-m' : 'Y-m-d');
        $labels[] = $cursor->format($monthly ? 'm/Y' : 'd/m');
        $views[] = $viewsByBucket[$bucketKey] ?? 0;
        $cursor = $cursor->add($step);
    }

    admin_api_json([
        'labels' => $labels,
        'series' => [
            ['name' => 'Lượt truy cập', 'data' => $views],
        ],
    ]);
} catch (Throwable $exception) {
    admin_api_error(500, 'Không thể tải biểu đồ traffic.');
}
