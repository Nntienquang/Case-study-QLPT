<?php
ini_set('display_errors', '0');
define('ADMIN_JSON_API', true);
require_once __DIR__ . '/../../admin_init.php';
require_once __DIR__ . '/_bootstrap.php';

function admin_revenue_date(?string $value): ?DateTimeImmutable
{
    if (!$value) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $date : null;
}

function admin_revenue_rows(mysqli $conn, string $sql, string $types, array $values): array
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    if ($types === 'ss') {
        [$start, $end] = $values;
        $stmt->bind_param($types, $start, $end);
    } elseif ($types === 'iss') {
        [$adminId, $start, $end] = $values;
        $stmt->bind_param($types, $adminId, $start, $end);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

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
    } elseif ($range === 'custom') {
        $customStart = admin_revenue_date($_GET['start'] ?? null);
        $customEnd = admin_revenue_date($_GET['end'] ?? null);
        if ($customStart && $customEnd) {
            $start = $customStart <= $customEnd ? $customStart : $customEnd;
            $end = $customStart <= $customEnd ? $customEnd : $customStart;
        }
    }

    $days = (int)$start->diff($end)->format('%a');
    $monthly = $range === 'year' || $days > 90;
    $bucketExpression = $monthly
        ? "DATE_FORMAT(COALESCE(paid_at, created_at), '%Y-%m')"
        : "DATE(COALESCE(paid_at, created_at))";
    $transactionBucketExpression = $monthly
        ? "DATE_FORMAT(created_at, '%Y-%m')"
        : "DATE(created_at)";
    $startSql = $start->format('Y-m-d 00:00:00');
    $endSql = $end->add(new DateInterval('P1D'))->format('Y-m-d 00:00:00');
    $adminId = (int)($_SESSION['user_id'] ?? 0);

    $paymentRows = admin_revenue_rows(
        $conn,
        "SELECT {$bucketExpression} AS bucket, COALESCE(SUM(amount), 0) AS total
         FROM payments
         WHERE payment_status = 'paid'
           AND COALESCE(paid_at, created_at) >= ?
           AND COALESCE(paid_at, created_at) < ?
         GROUP BY bucket
         ORDER BY bucket",
        'ss',
        [$startSql, $endSql]
    );
    $commissionRows = admin_revenue_rows(
        $conn,
        "SELECT {$transactionBucketExpression} AS bucket, COALESCE(SUM(amount), 0) AS total
         FROM transactions
         WHERE type = 'fee'
           AND to_user = ?
           AND created_at >= ?
           AND created_at < ?
         GROUP BY bucket
         ORDER BY bucket",
        'iss',
        [$adminId, $startSql, $endSql]
    );

    $revenueByBucket = [];
    foreach ($paymentRows as $row) {
        $revenueByBucket[(string)$row['bucket']] = (int)$row['total'];
    }
    $commissionByBucket = [];
    foreach ($commissionRows as $row) {
        $commissionByBucket[(string)$row['bucket']] = (int)$row['total'];
    }

    $labels = [];
    $revenue = [];
    $commission = [];
    $cursor = $monthly ? $start->modify('first day of this month') : $start;
    $last = $monthly ? $end->modify('first day of this month') : $end;
    $step = $monthly ? new DateInterval('P1M') : new DateInterval('P1D');

    while ($cursor <= $last) {
        $bucket = $cursor->format($monthly ? 'Y-m' : 'Y-m-d');
        $labels[] = $cursor->format($monthly ? 'm/Y' : 'd/m');
        $revenue[] = $revenueByBucket[$bucket] ?? 0;
        $commission[] = $commissionByBucket[$bucket] ?? 0;
        $cursor = $cursor->add($step);
    }

    admin_api_json([
        'labels' => $labels,
        'series' => [
            ['name' => 'Doanh thu', 'data' => $revenue],
            ['name' => 'Commission', 'data' => $commission],
        ],
    ]);
} catch (Throwable $exception) {
    admin_api_error(500, 'Không thể tải biểu đồ doanh thu.');
}
