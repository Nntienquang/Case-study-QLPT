<?php
require_once __DIR__ . '/../admin_init.php';
require_once __DIR__ . '/layout.php';

if (!$is_logged_in) {
    header('Location: ' . BASE_URL . 'login.php?area=admin');
    exit;
}

$dashboard = new DashboardController($db);
$data = $dashboard->index();

function admin_dash_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_dash_count(Database $db, string $sql): int
{
    $row = $db->getRow($sql);
    return (int)($row['count'] ?? 0);
}

function admin_dash_table_exists(mysqli $conn, string $table): bool
{
    $safeTable = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");
    return $result && $result->num_rows > 0;
}

function admin_dash_money($value): string
{
    return number_format((int)$value) . ' VNÄ';
}

function admin_dash_status(string $status): string
{
    return [
        'pending' => 'Chá» xá»­ lÃ½',
        'approved' => 'ÄÃ£ duyá»‡t',
        'accepted' => 'ÄÃ£ nháº­n',
        'paid' => 'ÄÃ£ cá»c',
        'completed' => 'HoÃ n táº¥t',
        'hidden' => 'ÄÃ£ áº©n',
        'rejected' => 'Tá»« chá»‘i',
        'cancelled' => 'ÄÃ£ há»§y',
    ][strtolower($status)] ?? ucfirst($status);
}

$hasReports = admin_dash_table_exists($conn, 'reports');
$hasViewingAppointments = admin_dash_table_exists($conn, 'viewing_appointments');

$adminQueue = [
    'pending_owners' => admin_dash_count($db, "SELECT COUNT(*) AS count FROM users WHERE role = 'owner' AND owner_verification_status = 'submitted'"),
    'pending_motels' => admin_dash_count($db, "SELECT COUNT(*) AS count FROM motels WHERE status = 'pending'"),
    'pending_reports' => $hasReports ? admin_dash_count($db, "SELECT COUNT(*) AS count FROM reports WHERE status = 'pending'") : 0,
    'pending_viewings' => $hasViewingAppointments ? admin_dash_count($db, "SELECT COUNT(*) AS count FROM viewing_appointments WHERE status = 'pending'") : 0,
    'held_payments' => admin_dash_count($db, "SELECT COUNT(*) AS count FROM payments WHERE status IN ('pending', 'held')"),
];

$motelStats = $data['motel_stats'] ?? [];
$userStats = $data['user_stats'] ?? [];
$bookingStats = $data['booking_stats'] ?? [];
$revenueStats = $data['revenue_stats'] ?? [];
$adminName = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báº£ng Ä‘iá»u hÃ nh admin - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>
<body class="workbench">
    <header class="wb-topbar">
        <div class="container-fluid px-4 wb-topbar-inner">
            <a class="wb-brand" href="<?php echo ADMIN_URL; ?>index.php">
                <span class="wb-brand-mark"><i class="fa fa-shield"></i></span>
                <span>QuanLyPhongTro Admin</span>
            </a>
            <div class="wb-user">
                <span><?php echo admin_dash_e($adminName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo ADMIN_URL; ?>logout.php">ÄÄƒng xuáº¥t</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-fluid px-4 wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Váº­n hÃ nh há»‡ thá»‘ng</div>
                <?php foreach (admin_nav_items() as $key => $item): ?>
                    <a class="wb-side-link <?php echo $key === 'index' ? 'active' : ''; ?>" href="<?php echo ADMIN_URL . $item['url']; ?>">
                        <i class="<?php echo admin_dash_e($item['icon']); ?>"></i>
                        <?php echo admin_dash_e($item['label']); ?>
                    </a>
                <?php endforeach; ?>
            </aside>

            <section>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo admin_dash_e($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo admin_dash_e($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="wb-hero admin mb-3">
                    <div>
                        <div class="wb-eyebrow">Trung tÃ¢m Ä‘iá»u phá»‘i</div>
                        <h1>Báº£ng Ä‘iá»u hÃ nh admin</h1>
                        <p>Theo dÃµi tin Ä‘Äƒng, duyá»‡t tÃ i khoáº£n chá»§ phÃ²ng, kiá»ƒm soÃ¡t booking, thanh toÃ¡n vÃ  pháº£n Ã¡nh trong má»™t mÃ n hÃ¬nh lÃ m viá»‡c.</p>
                    </div>
                    <div class="wb-actions">
                        <a class="btn btn-primary" href="<?php echo ADMIN_URL; ?>motels.php"><i class="fa fa-check"></i> Duyá»‡t phÃ²ng</a>
                        <a class="btn btn-outline-primary" href="<?php echo ADMIN_URL; ?>admin_revenue.php"><i class="fa fa-line-chart"></i> Doanh thu</a>
                    </div>
                </div>

                <div class="wb-grid wb-queue mb-3">
                    <a class="wb-card wb-queue-card" href="<?php echo ADMIN_URL; ?>user_approvals.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $adminQueue['pending_owners']; ?></div><i class="fa fa-user-plus wb-card-icon"></i></div>
                        <div class="wb-queue-label">Owner chá» duyá»‡t</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="<?php echo ADMIN_URL; ?>motels.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $adminQueue['pending_motels']; ?></div><i class="fa fa-home wb-card-icon"></i></div>
                        <div class="wb-queue-label">PhÃ²ng cáº§n kiá»ƒm duyá»‡t</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="<?php echo ADMIN_URL; ?>reports.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $adminQueue['pending_reports']; ?></div><i class="fa fa-flag wb-card-icon"></i></div>
                        <div class="wb-queue-label">BÃ¡o cÃ¡o chÆ°a xá»­ lÃ½</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="<?php echo ADMIN_URL; ?>bookings.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $adminQueue['pending_viewings']; ?></div><i class="fa fa-calendar wb-card-icon"></i></div>
                        <div class="wb-queue-label">Lá»‹ch xem Ä‘ang chá»</div>
                    </a>
                    <a class="wb-card wb-queue-card" href="<?php echo ADMIN_URL; ?>payments.php">
                        <div class="wb-queue-top"><div class="wb-queue-value"><?php echo $adminQueue['held_payments']; ?></div><i class="fa fa-credit-card wb-card-icon"></i></div>
                        <div class="wb-queue-label">Thanh toÃ¡n cáº§n theo dÃµi</div>
                    </a>
                </div>

                <div class="wb-grid wb-stats-4 mb-3">
                    <div class="wb-card"><i class="fa fa-building wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($motelStats['total'] ?? 0); ?></div><div class="wb-card-label">Tá»•ng phÃ²ng trá»</div></div>
                    <div class="wb-card"><i class="fa fa-clock-o wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($motelStats['pending'] ?? 0); ?></div><div class="wb-card-label">Tin Ä‘ang chá» duyá»‡t</div></div>
                    <div class="wb-card"><i class="fa fa-users wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($userStats['total'] ?? 0); ?></div><div class="wb-card-label">Tá»•ng ngÆ°á»i dÃ¹ng</div></div>
                    <div class="wb-card"><i class="fa fa-star wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($data['total_reviews'] ?? 0); ?></div><div class="wb-card-label">ÄÃ¡nh giÃ¡ Ä‘Ã£ ghi nháº­n</div></div>
                </div>

                <div class="wb-grid wb-stats-4">
                    <div class="wb-card"><i class="fa fa-calendar-check-o wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($bookingStats['accepted'] ?? 0); ?></div><div class="wb-card-label">Booking Ä‘Ã£ nháº­n</div></div>
                    <div class="wb-card"><i class="fa fa-hourglass-half wb-card-icon"></i><div class="wb-card-value"><?php echo (int)($bookingStats['pending'] ?? 0); ?></div><div class="wb-card-label">Booking chá» xá»­ lÃ½</div></div>
                    <div class="wb-card"><i class="fa fa-money wb-card-icon"></i><div class="wb-card-value fs-4"><?php echo admin_dash_money($revenueStats['month'] ?? 0); ?></div><div class="wb-card-label">Doanh thu thÃ¡ng nÃ y</div></div>
                    <div class="wb-card"><i class="fa fa-line-chart wb-card-icon"></i><div class="wb-card-value fs-4"><?php echo admin_dash_money($revenueStats['total'] ?? 0); ?></div><div class="wb-card-label">Tá»•ng commission</div></div>
                </div>

                <div class="wb-section-head">
                    <h2>PhÃ²ng chá» duyá»‡t</h2>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo ADMIN_URL; ?>motels.php">Xem táº¥t cáº£</a>
                </div>
                <div class="wb-table-card">
                    <?php if (!empty($data['recent_motels'])): ?>
                        <table class="wb-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>TiÃªu Ä‘á»</th>
                                    <th>Chá»§ phÃ²ng</th>
                                    <th>GiÃ¡</th>
                                    <th>Tráº¡ng thÃ¡i</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent_motels'] as $motel): ?>
                                    <tr>
                                        <td>#<?php echo (int)$motel['id']; ?></td>
                                        <td class="wb-title"><?php echo admin_dash_e($motel['title'] ?? 'N/A'); ?></td>
                                        <td><?php echo admin_dash_e($motel['owner_name'] ?? 'N/A'); ?></td>
                                        <td class="wb-price"><?php echo admin_dash_money($motel['price'] ?? 0); ?></td>
                                        <td><span class="wb-pill warning"><?php echo admin_dash_status((string)($motel['status'] ?? 'pending')); ?></span></td>
                                        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . (int)$motel['id']; ?>">Xem</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="wb-empty">KhÃ´ng cÃ³ phÃ²ng Ä‘ang chá» duyá»‡t.</div>
                    <?php endif; ?>
                </div>

                <div class="wb-section-head">
                    <h2>Booking gáº§n Ä‘Ã¢y</h2>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo ADMIN_URL; ?>bookings.php">Quáº£n lÃ½ booking</a>
                </div>
                <div class="wb-table-card">
                    <?php if (!empty($data['recent_bookings'])): ?>
                        <table class="wb-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>NgÆ°á»i thuÃª</th>
                                    <th>PhÃ²ng</th>
                                    <th>Tiá»n cá»c</th>
                                    <th>Check-in</th>
                                    <th>Tráº¡ng thÃ¡i</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['recent_bookings'] as $booking): ?>
                                    <tr>
                                        <td>#<?php echo (int)$booking['id']; ?></td>
                                        <td><?php echo admin_dash_e($booking['user_name'] ?? 'N/A'); ?></td>
                                        <td class="wb-title"><?php echo admin_dash_e($booking['motel_title'] ?? 'N/A'); ?></td>
                                        <td class="wb-price"><?php echo admin_dash_money($booking['deposit_amount'] ?? 0); ?></td>
                                        <td><?php echo admin_dash_e($booking['checkin_date'] ?? ''); ?></td>
                                        <td><span class="wb-pill warning"><?php echo admin_dash_status((string)($booking['status'] ?? 'pending')); ?></span></td>
                                        <td class="text-end"><a class="btn btn-sm btn-primary" href="<?php echo ADMIN_URL . 'booking_detail.php?id=' . (int)$booking['id']; ?>">Xem</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="wb-empty">ChÆ°a cÃ³ booking má»›i.</div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

