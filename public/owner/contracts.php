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

// Helper functions
function owner_dash_e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function contract_status_badge(string $status): string {
    return match (strtolower($status)) {
        'active' => '<span class="wb-pill success"><i class="fas fa-file-shield me-1"></i> Hiệu lực</span>',
        'pending_signature' => '<span class="wb-pill warning">Chờ ký / Thiếu file scan</span>',
        'expiring_soon' => '<span class="wb-pill danger"><i class="fas fa-clock text-danger me-1"></i> Sắp hết hạn</span>',
        'expired' => '<span class="wb-pill secondary">Đã hết hạn</span>',
        'terminated' => '<span class="wb-pill secondary">Đã thanh lý</span>',
        default => '<span class="wb-pill secondary">Khác</span>',
    };
}

// 1. Lấy danh sách hợp đồng (Giả lập logic truy vấn)
// Bổ sung logic tính số ngày còn lại để tự động gán nhãn 'expiring_soon' nếu còn < 30 ngày
$contractsQuery = "
    SELECT c.*, m.title as room_title, u.name as tenant_name, u.phone, u.idcard_number,
           DATEDIFF(c.end_date, CURDATE()) as days_remaining
    FROM contracts c
    JOIN motels m ON c.motel_id = m.id
    JOIN users u ON c.user_id = u.id
    WHERE c.owner_id = ?
    ORDER BY c.end_date ASC
";
$stmt = $conn->prepare($contractsQuery);
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$contracts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Thống kê nhanh
$totalContracts = count($contracts);
$activeCount = 0;
$expiringCount = 0;
$pendingCount = 0;

foreach ($contracts as &$c) {
    if ($c['status'] == 'active' && $c['days_remaining'] > 0 && $c['days_remaining'] <= 30) {
        $c['status'] = 'expiring_soon'; // Auto update trạng thái hiển thị
    }
    
    if ($c['status'] == 'active') $activeCount++;
    if ($c['status'] == 'expiring_soon') $expiringCount++;
    if ($c['status'] == 'pending_signature') $pendingCount++;
}
unset($c);

?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hợp đồng & Pháp lý - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
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
                <!-- <a class="wb-side-link active" href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <div class="wb-hero owner mb-4">
                    <div>
                        <div class="wb-eyebrow">Quản lý Pháp lý</div>
                        <h1>Hợp đồng Thuê phòng</h1>
                        <p>Số hóa giấy tờ, tự động điền mẫu hợp đồng và nhận cảnh báo khi sắp đến hạn tái ký.</p>
                    </div>
                    <div class="wb-actions">
                        <button class="btn btn-primary"
                            onclick="alert('Tính năng tự động điền thông tin (Tên, CCCD, Giá phòng) vào mẫu hợp đồng PDF đang được tải...')">
                            <i class="fas fa-file-medical me-1"></i> Tạo hợp đồng mới
                        </button>
                    </div>
                </div>

                <?php if ($expiringCount > 0): ?>
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-3 mb-4">
                    <i class="fas fa-triangle-exclamation fa-2x text-danger"></i>
                    <div>
                        <strong>Chú ý: Có <?php echo $expiringCount; ?> hợp đồng sắp hết hạn trong 30 ngày
                            tới!</strong><br>
                        Hãy liên hệ khách thuê để gia hạn hoặc bắt đầu bật lại tin đăng để tìm khách mới tránh bị trống
                        phòng.
                    </div>
                </div>
                <?php endif; ?>

                <div class="wb-grid wb-stats-4 mb-4">
                    <div class="wb-card">
                        <i class="fas fa-folder-open wb-card-icon text-primary"></i>
                        <div class="wb-card-value"><?php echo $totalContracts; ?></div>
                        <div class="wb-card-label">Tổng hợp đồng</div>
                    </div>
                    <div class="wb-card">
                        <i class="fas fa-file-shield wb-card-icon text-success"></i>
                        <div class="wb-card-value"><?php echo $activeCount; ?></div>
                        <div class="wb-card-label">Đang hiệu lực</div>
                    </div>
                    <div class="wb-card">
                        <i class="fas fa-file-signature wb-card-icon text-warning"></i>
                        <div class="wb-card-value"><?php echo $pendingCount; ?></div>
                        <div class="wb-card-label">Chờ tải file scan</div>
                    </div>
                    <div class="wb-card">
                        <i class="fas fa-hourglass-end wb-card-icon text-danger"></i>
                        <div class="wb-card-value"><?php echo $expiringCount; ?></div>
                        <div class="wb-card-label">Sắp hết hạn (<30 ngày)</div>
                        </div>
                    </div>

                    <div class="wb-section-head mt-4">
                        <h2>Danh sách lưu trữ</h2>
                        <div class="input-group" style="width: 250px;">
                            <input type="text" class="form-control form-control-sm"
                                placeholder="Tìm theo tên khách, CMND...">
                            <button class="btn btn-outline-secondary btn-sm"><i class="fas fa-search"></i></button>
                        </div>
                    </div>

                    <div class="wb-list-card">
                        <?php if (count($contracts) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Phòng / Người thuê</th>
                                        <th>Thời hạn</th>
                                        <th>Trạng thái</th>
                                        <th>File đính kèm</th>
                                        <th class="text-end">Tác vụ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $contract): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?php echo owner_dash_e($contract['room_title']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-user me-1"></i>
                                                <?php echo owner_dash_e($contract['tenant_name']); ?>
                                                <br>
                                                <i class="fas fa-id-card me-1"></i> CCCD:
                                                <?php echo owner_dash_e($contract['idcard_number'] ?? 'Chưa cập nhật'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <strong>Từ:</strong>
                                                <?php echo date('d/m/Y', strtotime($contract['start_date'])); ?><br>
                                                <strong>Đến:</strong>
                                                <?php echo date('d/m/Y', strtotime($contract['end_date'])); ?>
                                            </div>
                                            <?php if ($contract['status'] === 'expiring_soon'): ?>
                                            <div class="small text-danger fw-bold mt-1">Còn
                                                <?php echo $contract['days_remaining']; ?> ngày</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo contract_status_badge($contract['status']); ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($contract['document_url'])): ?>
                                            <a href="#" class="btn btn-sm btn-outline-primary"><i
                                                    class="fas fa-eye me-1"></i> Xem Scan PDF</a>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-light border text-muted"><i
                                                    class="fas fa-upload me-1"></i> Tải file lên</button>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border" type="button"
                                                    data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <li><a class="dropdown-item" href="#"><i
                                                                class="fas fa-pen text-muted me-2"></i> Chỉnh sửa</a>
                                                    </li>
                                                    <li><a class="dropdown-item" href="#"><i
                                                                class="fas fa-print text-muted me-2"></i> In bản mẫu</a>
                                                    </li>
                                                    <?php if ($contract['status'] === 'expiring_soon' || $contract['status'] === 'expired'): ?>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item text-success" href="#"><i
                                                                class="fas fa-rotate text-success me-2"></i> Gia hạn hợp
                                                            đồng</a></li>
                                                    <?php endif; ?>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item text-danger" href="#"><i
                                                                class="fas fa-ban text-danger me-2"></i> Thanh lý</a>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="wb-empty">
                            Chưa có hợp đồng nào được tạo. Nhấn "Tạo hợp đồng mới" để bắt đầu số hóa giấy tờ.
                        </div>
                        <?php endif; ?>
                    </div>

            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>