<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in || ($_SESSION['role'] ?? '') !== ROLE_ADMIN) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$controller = new AdminRevenueController($db);
$revenueData = $controller->listRevenue();
$stats = $revenueData['stats'] ?? [];
$revenue = $revenueData['revenue'] ?? [];

admin_layout_start('Doanh thu admin', 'Theo dõi commission hệ thống nhận được từ các booking và thanh toán đã hoàn tất.', 'admin_revenue');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><i class="fa fa-money wb-card-icon"></i>
        <div class="wb-card-value fs-4"><?php echo admin_money($stats['total'] ?? 0); ?></div>
        <div class="wb-card-label">Tổng doanh thu</div>
    </div>
    <div class="wb-card"><i class="fa fa-calendar wb-card-icon"></i>
        <div class="wb-card-value fs-4"><?php echo admin_money($stats['month'] ?? 0); ?></div>
        <div class="wb-card-label">Tháng này</div>
    </div>
    <div class="wb-card"><i class="fa fa-check-circle wb-card-icon"></i>
        <div class="wb-card-value"><?php echo (int)($stats['count'] ?? 0); ?></div>
        <div class="wb-card-label">Lần nhận commission</div>
    </div>
    <div class="wb-card"><i class="fa fa-bar-chart wb-card-icon"></i>
        <div class="wb-card-value fs-4"><?php echo admin_money($stats['average'] ?? 0); ?></div>
        <div class="wb-card-label">Trung bình/commission</div>
    </div>
</div>

<div class="wb-section-head">
    <h2>Danh sách commission</h2>
    <span class="wb-pill">5% platform fee</span>
</div>

<div class="wb-table-card">
    <?php if ($revenue): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>Phòng trọ</th>
                    <th>Khách hàng</th>
                    <th>Giá phòng</th>
                    <th>Commission</th>
                    <th>Ngày nhận</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue as $item): ?>
                    <tr>
                        <td>#<?php echo (int)($item['booking_id'] ?? 0); ?></td>
                        <td>
                            <div class="wb-title"><?php echo admin_e($item['motel_title'] ?? 'N/A'); ?></div>
                            <div class="wb-muted">Chủ: <?php echo admin_e($item['owner_name'] ?? 'N/A'); ?></div>
                        </td>
                        <td>
                            <div><?php echo admin_e($item['user_name'] ?? 'N/A'); ?></div>
                            <div class="wb-muted"><?php echo admin_e($item['user_email'] ?? ''); ?></div>
                        </td>
                        <td><?php echo admin_money($item['motel_price'] ?? 0); ?></td>
                        <td class="wb-price"><?php echo admin_money($item['amount'] ?? 0); ?></td>
                        <td><?php echo !empty($item['created_at']) ? date('d/m/Y H:i', strtotime((string)$item['created_at'])) : ''; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="wb-empty">Chưa có commission nào. Commission phát sinh khi booking hoàn tất theo luồng thanh toán.</div>
    <?php endif; ?>
</div>

<?php if (($revenueData['total_pages'] ?? 0) > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $revenueData['total_pages']; $i++): ?>
                <li class="page-item <?php echo $i === ($revenueData['page'] ?? 1) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<div class="wb-grid wb-stats-4 mt-3">
    <div class="wb-card">
        <div class="wb-title mb-2">Cơ chế doanh thu</div>
        <div class="wb-muted">Admin nhận commission theo tỷ lệ hệ thống cấu hình trên mỗi booking hợp lệ.</div>
    </div>
    <div class="wb-card">
        <div class="wb-title mb-2">Dòng tiền</div>
        <div class="wb-muted">Tiền cọc được giữ, giải ngân cho chủ phòng và ghi nhận phần phí cho admin.</div>
    </div>
    <div class="wb-card">
        <div class="wb-title mb-2">Đối soát</div>
        <div class="wb-muted">So sánh commission với booking và trạng thái thanh toán trước khi chốt báo cáo.</div>
    </div>
    <div class="wb-card">
        <div class="wb-title mb-2">Rủi ro</div>
        <div class="wb-muted">Ưu tiên kiểm tra các giao dịch hoàn tiền, hủy hoặc bị tranh chấp.</div>
    </div>
</div>

<?php admin_layout_end(); ?>
