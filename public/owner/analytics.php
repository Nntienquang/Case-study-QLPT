<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../dashboard.php');
    exit;
}

$ownerId = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Chủ phòng';

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $ownerId);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
require_once __DIR__ . '/_owner_guard.php';

// Helper functions
function owner_dash_e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function owner_dash_money($value): string
{
    return number_format((int)$value) . ' đ';
}

// --- GIẢ LẬP DỮ LIỆU AI & PHÂN TÍCH ---
// 1. Dữ liệu bản đồ nhiệt giá (Price Heatmap) giả lập
$marketAvgPrice = 1800000;
$myAvgPrice = 1650000;
$priceDiff = $myAvgPrice - $marketAvgPrice;

// 2. Dự báo trống phòng (Dựa trên số hợp đồng sắp hết hạn vào các tháng tới)
$vacancyForecast = [
    ['month' => 'Tháng 6', 'risk' => 15, 'color' => 'success'],
    ['month' => 'Tháng 7', 'risk' => 80, 'color' => 'danger'], // Tháng sinh viên nghỉ hè/tốt nghiệp
    ['month' => 'Tháng 8', 'risk' => 45, 'color' => 'warning'],
    ['month' => 'Tháng 9', 'risk' => 10, 'color' => 'success'],
];

// 3. Danh sách khách thuê (Tenant List)
// Lấy danh sách những người đang thuê, ngày dọn vào, và trạng thái nợ tiền (Mô phỏng bằng mảng)
$tenants = [
    [
        'room' => 'Phòng 101 - Tòa A',
        'name' => 'Nguyễn Thị Hương',
        'phone' => '0987654321',
        'move_in' => '2025-08-15',
        'debt' => 0,
        'status' => 'good'
    ],
    [
        'room' => 'Phòng 102 - Tòa A',
        'name' => 'Trần Văn Bình',
        'phone' => '0912345678',
        'move_in' => '2025-09-01',
        'debt' => 1500000,
        'status' => 'warning'
    ],
    [
        'room' => 'Phòng 201 - Tòa B',
        'name' => 'Lê Hoàng Hải',
        'phone' => '0933445566',
        'move_in' => '2026-01-10',
        'debt' => 0,
        'status' => 'good'
    ]
];
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân tích Thông minh - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .pro-gradient {
        background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        color: white;
    }

    .pro-gradient .wb-muted,
    .pro-gradient .text-muted {
        color: rgba(255, 255, 255, 0.8) !important;
    }

    .chart-bar-container {
        height: 12px;
        background: #e9ecef;
        border-radius: 6px;
        overflow: hidden;
        margin-top: 8px;
    }

    .chart-bar-fill {
        height: 100%;
        border-radius: 6px;
        transition: width 0.5s ease-in-out;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
                <span class="badge bg-warning text-dark me-2"><i class="fas fa-crown"></i> PRO</span>
                <span><?php echo owner_dash_e($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>
                <!-- <a class="wb-side-link" href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link active" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông
                    minh <span class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <div
                    class="wb-hero owner pro-gradient mb-4 rounded-4 shadow-sm p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-uppercase fw-bold mb-1"
                            style="color: #ffd700; font-size: 0.85rem; letter-spacing: 1px;">
                            <i class="fas fa-wand-magic-sparkles"></i> Trợ lý kinh doanh
                        </div>
                        <h1 class="text-white mb-2">Phân tích & Gợi ý thông minh</h1>
                        <p class="mb-0 text-white-50">Tối ưu hóa doanh thu, giảm tỷ lệ trống phòng bằng dữ liệu AI.</p>
                    </div>
                    <div class="d-none d-md-block">
                        <i class="fas fa-chart-line fa-4x opacity-50"></i>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="wb-list-card h-100 p-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-map-location-dot text-primary me-2"></i> Trinh sát
                                Giá khu vực</h5>
                            <p class="text-muted small">So sánh giá phòng của bạn với mặt bằng chung khu vực Đại học
                                Vinh.</p>

                            <div class="d-flex justify-content-between align-items-end mt-4 mb-2">
                                <div>
                                    <div class="small text-muted">TB Khu vực</div>
                                    <div class="fs-4 fw-bold"><?php echo owner_dash_money($marketAvgPrice); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="small text-muted">Phòng của bạn</div>
                                    <div class="fs-4 fw-bold text-primary"><?php echo owner_dash_money($myAvgPrice); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="chart-bar-container bg-light">
                                <div class="chart-bar-fill bg-primary"
                                    style="width: <?php echo ($myAvgPrice / $marketAvgPrice) * 100; ?>%;"></div>
                            </div>

                            <div class="alert alert-warning mt-4 border-0 mb-0">
                                <i class="fas fa-lightbulb me-1"></i> <strong>Gợi ý:</strong> Giá của bạn đang thấp hơn
                                mặt bằng chung khoảng <?php echo number_format(abs($priceDiff)); ?>đ. Bạn có thể cân
                                nhắc tăng giá khi ký hợp đồng mới.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="wb-list-card h-100 p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0"><i class="fas fa-calendar-alt text-danger me-2"></i> Dự báo
                                    trống phòng</h5>
                                <span class="badge bg-light text-dark border">4 tháng tới</span>
                            </div>
                            <p class="text-muted small mb-4">Mức độ rủi ro khách trả phòng dựa trên lịch sử và thời hạn
                                hợp đồng.</p>

                            <?php foreach ($vacancyForecast as $forecast): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-medium"><?php echo $forecast['month']; ?></span>
                                    <span
                                        class="text-<?php echo $forecast['color']; ?> fw-bold"><?php echo $forecast['risk']; ?>%
                                        nguy cơ</span>
                                </div>
                                <div class="chart-bar-container">
                                    <div class="chart-bar-fill bg-<?php echo $forecast['color']; ?>"
                                        style="width: <?php echo $forecast['risk']; ?>%;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                            <p class="small text-muted mt-3 mb-0"><i class="fas fa-circle-info"></i> <strong>Tháng
                                    7</strong> có rủi ro cao vì nhiều sinh viên hoàn thành năm học.</p>
                        </div>
                    </div>
                </div>

                <div class="wb-section-head mt-4">
                    <h2>Danh bạ Khách thuê & Công nợ</h2>
                    <button class="btn btn-outline-success btn-sm"><i class="fas fa-file-excel me-1"></i> Xuất
                        Excel</button>
                </div>

                <div class="wb-list-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng</th>
                                    <th>Khách thuê</th>
                                    <th>Liên hệ</th>
                                    <th>Ngày dọn vào</th>
                                    <th class="text-end">Tình trạng Nợ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tenants as $tenant): ?>
                                <tr>
                                    <td class="fw-medium"><?php echo owner_dash_e($tenant['room']); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center text-primary fw-bold"
                                                style="width: 32px; height: 32px;">
                                                <?php echo mb_substr($tenant['name'], 0, 1); ?>
                                            </div>
                                            <?php echo owner_dash_e($tenant['name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="tel:<?php echo owner_dash_e($tenant['phone']); ?>"
                                            class="text-decoration-none text-body">
                                            <i class="fas fa-phone-alt text-success me-1"
                                                style="font-size: 0.8rem;"></i>
                                            <?php echo owner_dash_e($tenant['phone']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($tenant['move_in'])); ?></td>
                                    <td class="text-end">
                                        <?php if ($tenant['debt'] > 0): ?>
                                        <span
                                            class="text-danger fw-bold me-2"><?php echo owner_dash_money($tenant['debt']); ?></span>
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-bell"></i> Nhắc
                                            nhở</button>
                                        <?php else: ?>
                                        <span class="badge bg-success bg-opacity-10 text-success"><i
                                                class="fas fa-check"></i> Đã thanh toán</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
