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

admin_layout_start('Doanh thu admin', 'Theo dÃµi commission há»‡ thá»‘ng nháº­n Ä‘Æ°á»£c tá»« cÃ¡c booking vÃ  thanh toÃ¡n Ä‘Ã£ hoÃ n táº¥t.', 'admin_revenue');
admin_flash_messages();
?>

<div class="wb-grid wb-stats-4 mb-3">
    <div class="wb-card"><i class="fa fa-money wb-card-icon"></i>
        <div class="wb-card-value fs-4"><?php echo admin_money($stats['total'] ?? 0); ?></div>
        <div class="wb-card-label">Tá»•ng doanh thu</div>
    </div>
    <div class="wb-card"><i class="fa fa-calendar wb-card-icon"></i>
        <div class="wb-card-value fs-4"><?php echo admin_money($stats['month'] ?? 0); ?></div>
        <div class="wb-card-label">ThÃ¡ng nÃ y</div>
    </div>
    <div class="wb-card"><i class="fa fa-check-circle wb-card-icon"></i>
        <div class="wb-card-value"><?php echo (int)($stats['count'] ?? 0); ?></div>
        <div class="wb-card-label">Láº§n nháº­n commission</div>
    </div>
    <div class="wb-card"><i class="fa fa-bar-chart wb-card-icon"></i>
        <div class="wb-card-value fs-4"><?php echo admin_money($stats['average'] ?? 0); ?></div>
        <div class="wb-card-label">Trung bÃ¬nh/commission</div>
    </div>
</div>

<div class="wb-section-head">
    <h2>Danh sÃ¡ch commission</h2>
    <span class="wb-pill">5% platform fee</span>
</div>

<div class="wb-table-card">
    <?php if ($revenue): ?>
        <table class="wb-table">
            <thead>
                <tr>
                    <th>Booking</th>
                    <th>PhÃ²ng trá»</th>
                    <th>KhÃ¡ch hÃ ng</th>
                    <th>GiÃ¡ phÃ²ng</th>
                    <th>Commission</th>
                    <th>NgÃ y nháº­n</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenue as $item): ?>
                    <tr>
                        <td>#<?php echo (int)($item['booking_id'] ?? 0); ?></td>
                        <td>
                            <div class="wb-title"><?php echo admin_e($item['motel_title'] ?? 'N/A'); ?></div>
                            <div class="wb-muted">Chá»§: <?php echo admin_e($item['owner_name'] ?? 'N/A'); ?></div>
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
        <div class="wb-empty">ChÆ°a cÃ³ commission nÃ o. Commission phÃ¡t sinh khi booking hoÃ n táº¥t theo luá»“ng thanh toÃ¡n.</div>
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
        <div class="wb-title mb-2">CÆ¡ cháº¿ doanh thu</div>
        <div class="wb-muted">Admin nháº­n commission theo tá»· lá»‡ há»‡ thá»‘ng cáº¥u hÃ¬nh trÃªn má»—i booking há»£p lá»‡.</div>
    </div>
    <div class="wb-card">
        <div class="wb-title mb-2">DÃ²ng tiá»n</div>
        <div class="wb-muted">Tiá»n cá»c Ä‘Æ°á»£c giá»¯, giáº£i ngÃ¢n cho chá»§ phÃ²ng vÃ  ghi nháº­n pháº§n phÃ­ cho admin.</div>
    </div>
    <div class="wb-card">
        <div class="wb-title mb-2">Äá»‘i soÃ¡t</div>
        <div class="wb-muted">So sÃ¡nh commission vá»›i booking vÃ  tráº¡ng thÃ¡i thanh toÃ¡n trÆ°á»›c khi chá»‘t bÃ¡o cÃ¡o.</div>
    </div>
    <div class="wb-card">
        <div class="wb-title mb-2">Rá»§i ro</div>
        <div class="wb-muted">Æ¯u tiÃªn kiá»ƒm tra cÃ¡c giao dá»‹ch hoÃ n tiá»n, há»§y hoáº·c bá»‹ tranh cháº¥p.</div>
    </div>
</div>

<?php admin_layout_end(); ?>
