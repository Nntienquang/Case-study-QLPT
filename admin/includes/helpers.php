<?php
/**
 * PHASE 1: Helper Functions
 */

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' VND';
}

// Format date
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Get status badge class
function getStatusBadge($status) {
    $map = [
        'pending' => 'warning',
        'approved' => 'success',
        'hidden' => 'danger',
        'completed' => 'success',
        'accepted' => 'info',
        'rejected' => 'danger',
        'paid' => 'success',
        'released' => 'success',
        'held' => 'warning',
        'refunded' => 'secondary'
    ];
    return $map[$status] ?? 'secondary';
}

// Get status text in Vietnamese
function getStatusText($status, $context = 'booking') {
    $map = [
        'booking' => [
            'pending' => 'Chờ xử lý',
            'paid' => 'Đã thanh toán',
            'accepted' => 'Đã chấp nhận',
            'completed' => 'Hoàn thành',
            'rejected' => 'Từ chối',
            'cancelled' => 'Hủy'
        ],
        'motel' => [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'hidden' => 'Ẩn'
        ],
        'payment' => [
            'pending' => 'Chờ xử lý',
            'held' => 'Giữ tiền',
            'released' => 'Đã chuyển',
            'refunded' => 'Hoàn tiền'
        ],
        'withdraw' => [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối'
        ]
    ];
    return $map[$context][$status] ?? $status;
}

// Pagination
function getPagination($total, $page, $perPage = 10) {
    $pages = ceil($total / $perPage);
    return [
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
        'limit' => $perPage
    ];
}
?>
