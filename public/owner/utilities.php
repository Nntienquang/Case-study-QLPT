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

// Lấy theme preference
$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $ownerId);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);

function owner_dash_e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function owner_dash_money($value): string {
    return number_format((int)$value, 0, ',', '.') . ' đ';
}
function bill_status_badge(string $status): string {
    return match (strtolower($status)) {
        'unpaid' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Chưa thu</span>',
        'paid' => '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Đã thanh toán</span>',
        'overdue' => '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Quá hạn</span>',
        default => '<span class="badge bg-secondary">Khác</span>',
    };
}

// Xử lý Lọc dữ liệu
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m'); // 0 = Tất cả các tháng
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';

// ==========================================
// XỬ LÝ POST REQUEST (THÊM / SỬA / XÓA / XÁC NHẬN)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. TẠO HÓA ĐƠN MỚI
    if (isset($_POST['action']) && $_POST['action'] === 'create_bill') {
        $motel_id = (int)$_POST['motel_id'];
        $tenant_id = (int)$_POST['tenant_id'];
        $room_price = (int)$_POST['room_price'];
        $elec_old = (int)$_POST['elec_old'];
        $elec_new = (int)$_POST['elec_new'];
        $water_old = (int)$_POST['water_old'];
        $water_new = (int)$_POST['water_new'];
        $trash_fee = (int)$_POST['trash_fee'];
        $internet_fee = (int)$_POST['internet_fee'];
        
        if ($elec_new < $elec_old || $water_new < $water_old) {
            header("Location: utilities.php?month=$filterMonth&year=$filterYear&status=$filterStatus&error=invalid_meter");
            exit;
        }

        $elec_price = 3500;
        $water_price = 20000;
        $total_amount = $room_price + (($elec_new - $elec_old) * $elec_price) + (($water_new - $water_old) * $water_price) + $trash_fee + $internet_fee;

        $insertQuery = "INSERT INTO monthly_bills 
                        (motel_id, user_id, billing_month, billing_year, elec_old, elec_new, elec_price, water_old, water_new, water_price, trash_fee, internet_fee, total_amount, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iiiiiiiiiiiii", $motel_id, $tenant_id, $filterMonth, $filterYear, $elec_old, $elec_new, $elec_price, $water_old, $water_new, $water_price, $trash_fee, $internet_fee, $total_amount);
        $stmt->execute();
        
        header("Location: utilities.php?month=$filterMonth&year=$filterYear&status=$filterStatus&success=bill_created");
        exit;
    }
    
    // 2. CHỈNH SỬA HÓA ĐƠN
    if (isset($_POST['action']) && $_POST['action'] === 'edit_bill') {
        $bill_id = (int)$_POST['bill_id'];
        $room_price = (int)$_POST['room_price'];
        $elec_new = (int)$_POST['elec_new'];
        $water_new = (int)$_POST['water_new'];
        $trash_fee = (int)$_POST['trash_fee'];
        $internet_fee = (int)$_POST['internet_fee'];

        // Lấy lại số cũ và giá từ DB để tính toán lại chính xác
        $checkQuery = "SELECT elec_old, elec_price, water_old, water_price FROM monthly_bills WHERE id = ? AND motel_id IN (SELECT id FROM motels WHERE user_id = ?)";
        $stmtCheck = $conn->prepare($checkQuery);
        $stmtCheck->bind_param("ii", $bill_id, $ownerId);
        $stmtCheck->execute();
        $billData = $stmtCheck->get_result()->fetch_assoc();

        if ($billData) {
            if ($elec_new < $billData['elec_old'] || $water_new < $billData['water_old']) {
                header("Location: utilities.php?month=$filterMonth&year=$filterYear&status=$filterStatus&error=invalid_meter");
                exit;
            }

            $total_amount = $room_price + (($elec_new - $billData['elec_old']) * $billData['elec_price']) + (($water_new - $billData['water_old']) * $billData['water_price']) + $trash_fee + $internet_fee;

            $updateQuery = "UPDATE monthly_bills SET elec_new=?, water_new=?, trash_fee=?, internet_fee=?, total_amount=? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("iiiiii", $elec_new, $water_new, $trash_fee, $internet_fee, $total_amount, $bill_id);
            $stmt->execute();
        }
        
        header("Location: utilities.php?month=$filterMonth&year=$filterYear&status=$filterStatus&success=bill_updated");
        exit;
    }

    // 3. XÁC NHẬN THANH TOÁN
    if (isset($_POST['action']) && $_POST['action'] === 'mark_paid') {
        $bill_id = (int)$_POST['bill_id'];
        $updateQuery = "UPDATE monthly_bills SET status = 'paid' WHERE id = ? AND motel_id IN (SELECT id FROM motels WHERE user_id = ?)";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $bill_id, $ownerId);
        $stmt->execute();
        header("Location: utilities.php?month=$filterMonth&year=$filterYear&status=$filterStatus&success=bill_paid");
        exit;
    }

    // 4. XÓA HÓA ĐƠN
    if (isset($_POST['action']) && $_POST['action'] === 'delete_bill') {
        $bill_id = (int)$_POST['bill_id'];
        $deleteQuery = "DELETE FROM monthly_bills WHERE id = ? AND status = 'unpaid' AND motel_id IN (SELECT id FROM motels WHERE user_id = ?)";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("ii", $bill_id, $ownerId);
        $stmt->execute();
        header("Location: utilities.php?month=$filterMonth&year=$filterYear&status=$filterStatus&success=bill_deleted");
        exit;
    }
}

// ==========================================
// LẤY DỮ LIỆU HIỂN THỊ
// ==========================================

// 1. Dữ liệu Hóa đơn đã xuất (có áp dụng bộ lọc)
$sqlBills = "SELECT mb.*, m.title, u.name as tenant_name 
             FROM monthly_bills mb
             JOIN motels m ON mb.motel_id = m.id
             JOIN users u ON mb.user_id = u.id
             WHERE m.user_id = ? AND mb.billing_year = ?";
$paramsBills = [$ownerId, $filterYear];
$typesBills = "ii";

if ($filterMonth > 0) {
    $sqlBills .= " AND mb.billing_month = ?";
    $paramsBills[] = $filterMonth;
    $typesBills .= "i";
}
if ($filterStatus !== 'all') {
    $sqlBills .= " AND mb.status = ?";
    $paramsBills[] = $filterStatus;
    $typesBills .= "s";
}
$sqlBills .= " ORDER BY mb.created_at DESC";

$stmtBills = $conn->prepare($sqlBills);
$stmtBills->bind_param($typesBills, ...$paramsBills);
$stmtBills->execute();
$monthlyBills = $stmtBills->get_result()->fetch_all(MYSQLI_ASSOC);

// Thống kê tổng quan dựa trên bộ lọc
$stat_total_bills = count($monthlyBills);
$stat_paid = 0; $stat_unpaid = 0; $stat_expected_revenue = 0;

// Tính lại room_price_billed cho mục đích Edit form (vì db không lưu giá phòng cứng lúc chốt)
foreach ($monthlyBills as &$bill) {
    if ($bill['status'] === 'paid') $stat_paid++;
    elseif ($bill['status'] === 'unpaid') $stat_unpaid++;
    $stat_expected_revenue += $bill['total_amount'];
    
    // Reverse engineer Tiền phòng = Tổng tiền - (Điện + Nước + Rác + Internet)
    $bill['room_price_billed'] = $bill['total_amount'] 
                                 - (($bill['elec_new'] - $bill['elec_old']) * $bill['elec_price'])
                                 - (($bill['water_new'] - $bill['water_old']) * $bill['water_price'])
                                 - $bill['trash_fee'] - $bill['internet_fee'];
}

// 2. Truy vấn danh sách phòng cần chốt (chỉ hiện khi chọn 1 tháng cụ thể)
$activeRooms = [];
if ($filterMonth > 0) {
    $activeRoomsQuery = "
        SELECT b.id as booking_id, m.id as motel_id, m.title, m.price as room_price, 
               u.id as tenant_id, u.name as tenant_name, u.phone,
               COALESCE((SELECT elec_new FROM monthly_bills WHERE motel_id = m.id ORDER BY id DESC LIMIT 1), 0) as last_elec,
               COALESCE((SELECT water_new FROM monthly_bills WHERE motel_id = m.id ORDER BY id DESC LIMIT 1), 0) as last_water
        FROM bookings b 
        JOIN motels m ON b.motel_id = m.id 
        JOIN users u ON b.user_id = u.id 
        WHERE m.user_id = ? AND b.status IN ('accepted', 'completed')
          AND NOT EXISTS (
              SELECT 1 FROM monthly_bills mb 
              WHERE mb.motel_id = m.id AND mb.billing_month = ? AND mb.billing_year = ?
          )";
    $stmtActive = $conn->prepare($activeRoomsQuery);
    $stmtActive->bind_param("iii", $ownerId, $filterMonth, $filterYear);
    $stmtActive->execute();
    $activeRooms = $stmtActive->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Điện, Nước & Dịch vụ - QuanLyPhongTro</title>
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
                <a class="wb-side-link active" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>
                <!-- <a class="wb-side-link" href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                            if($_GET['success'] == 'bill_created') echo "✅ Đã chốt số và tạo hóa đơn thành công!";
                            if($_GET['success'] == 'bill_updated') echo "📝 Đã cập nhật hóa đơn thành công!";
                            if($_GET['success'] == 'bill_paid') echo "✅ Đã xác nhận thu tiền thành công!";
                            if($_GET['success'] == 'bill_deleted') echo "🗑️ Đã hủy hóa đơn thành công.";
                        ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid_meter'): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    ⚠️ Lỗi: Chỉ số điện/nước mới nhập vào không được nhỏ hơn số cũ!
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="wb-hero owner mb-4 p-4 rounded bg-light border">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h2 class="mb-1 text-dark"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>
                                Điện, Nước & Dịch vụ</h2>
                            <p class="text-muted mb-0">Quản lý hóa đơn định kỳ, kiểm tra nợ đọng dễ dàng.</p>
                        </div>
                        <div class="wb-actions">
                            <form method="GET" class="d-flex gap-2">
                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="all">Tất cả trạng thái</option>
                                    <option value="unpaid" <?php echo $filterStatus === 'unpaid' ? 'selected' : ''; ?>>
                                        Đang nợ / Chưa thu</option>
                                    <option value="paid" <?php echo $filterStatus === 'paid' ? 'selected' : ''; ?>>Đã
                                        thu tiền</option>
                                </select>
                                <select name="month" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <option value="0" <?php echo $filterMonth === 0 ? 'selected' : ''; ?>>-- Tất cả các
                                        tháng --</option>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                        <?php echo $i === $filterMonth ? 'selected' : ''; ?>>Tháng <?php echo $i; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <?php for ($i = date('Y') - 1; $i <= date('Y') + 1; $i++): ?>
                                    <option value="<?php echo $i; ?>"
                                        <?php echo $i === $filterYear ? 'selected' : ''; ?>>Năm <?php echo $i; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="wb-grid wb-stats-5 mb-4">
                    <div class="wb-card"><i class="fas fa-file-invoice-dollar wb-card-icon text-primary"></i>
                        <div class="wb-card-value"><?php echo $stat_total_bills; ?></div>
                        <div class="wb-card-label">Hóa đơn hiển thị</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-check-circle wb-card-icon text-success"></i>
                        <div class="wb-card-value"><?php echo $stat_paid; ?></div>
                        <div class="wb-card-label">Đã thanh toán</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-clock wb-card-icon text-warning"></i>
                        <div class="wb-card-value text-danger"><?php echo $stat_unpaid; ?></div>
                        <div class="wb-card-label">Đang nợ</div>
                    </div>
                    <div class="wb-card"><i class="fas fa-sack-dollar wb-card-icon text-success"></i>
                        <div class="wb-card-value text-success"><?php echo owner_dash_money($stat_expected_revenue); ?>
                        </div>
                        <div class="wb-card-label">Tổng tiền (theo bộ lọc)</div>
                    </div>
                </div>

                <?php if ($filterMonth > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Danh sách cần chốt (Tháng <?php echo $filterMonth; ?>)</h4>
                    <span class="badge bg-secondary">Còn <?php echo count($activeRooms); ?> phòng</span>
                </div>
                <div class="card mb-5 border-0 shadow-sm">
                    <?php if (count($activeRooms) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Phòng / Khách thuê</th>
                                    <th>Tiền phòng</th>
                                    <th>Số điện / Số nước cũ</th>
                                    <th class="text-end">Tác vụ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeRooms as $room): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo owner_dash_e($room['title']); ?>
                                        </div>
                                        <div class="text-muted small"><i class="fas fa-user me-1"></i>
                                            <?php echo owner_dash_e($room['tenant_name']); ?></div>
                                    </td>
                                    <td><?php echo owner_dash_money($room['room_price']); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border me-2"><i
                                                class="fas fa-bolt text-warning"></i> Cũ:
                                            <?php echo $room['last_elec']; ?></span>
                                        <span class="badge bg-light text-dark border"><i
                                                class="fas fa-faucet-drip text-info"></i> Cũ:
                                            <?php echo $room['last_water']; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-primary btn-sm btn-open-create-modal"
                                            data-bs-toggle="modal" data-bs-target="#createBillModal"
                                            data-motel-id="<?php echo $room['motel_id']; ?>"
                                            data-tenant-id="<?php echo $room['tenant_id']; ?>"
                                            data-title="<?php echo owner_dash_e($room['title']); ?>"
                                            data-price="<?php echo $room['room_price']; ?>"
                                            data-elecold="<?php echo $room['last_elec']; ?>"
                                            data-waterold="<?php echo $room['last_water']; ?>">
                                            <i class="fas fa-pen me-1"></i> Chốt sổ & Chỉnh phí
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">Đã chốt xong toàn bộ phòng cho tháng
                        <?php echo $filterMonth; ?>/<?php echo $filterYear; ?>.</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <h4 class="mb-3">Danh sách hóa đơn
                    <?php echo ($filterMonth === 0) ? '(Tất cả các tháng)' : "(Tháng $filterMonth)"; ?></h4>
                <div class="card border-0 shadow-sm mb-5">
                    <?php if (count($monthlyBills) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($monthlyBills as $bill): ?>
                        <div class="list-group-item p-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <h6 class="mb-0 fw-bold"><?php echo owner_dash_e($bill['title']); ?></h6>
                                        <?php echo bill_status_badge($bill['status']); ?>
                                        <span class="badge bg-light text-dark border ms-1">Kỳ:
                                            <?php echo sprintf('%02d/%d', $bill['billing_month'], $bill['billing_year']); ?></span>
                                    </div>
                                    <div class="text-muted small mb-2">
                                        Khách: <strong><?php echo owner_dash_e($bill['tenant_name']); ?></strong> |
                                        Xuất: <?php echo date('d/m/Y H:i', strtotime($bill['created_at'])); ?>
                                    </div>
                                    <div class="d-flex gap-2 small flex-wrap text-muted">
                                        <span>Phòng: <?php echo owner_dash_money($bill['room_price_billed']); ?></span>
                                        •
                                        <span><i class="fas fa-bolt text-warning"></i> Đ:
                                            <?php echo ($bill['elec_new'] - $bill['elec_old']); ?> chữ</span> •
                                        <span><i class="fas fa-faucet-drip text-info"></i> N:
                                            <?php echo ($bill['water_new'] - $bill['water_old']); ?> khối</span> •
                                        <span><i class="fas fa-trash"></i> Rác:
                                            <?php echo number_format($bill['trash_fee']); ?></span> •
                                        <span><i class="fas fa-wifi"></i> Net:
                                            <?php echo number_format($bill['internet_fee']); ?></span>
                                    </div>
                                </div>
                                <div class="text-end text-nowrap">
                                    <h5 class="text-danger fw-bold mb-2">
                                        <?php echo owner_dash_money($bill['total_amount']); ?></h5>

                                    <?php if ($bill['status'] === 'unpaid'): ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="mark_paid">
                                            <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success"
                                                onclick="return confirm('Xác nhận đã nhận đủ tiền hóa đơn này?');">Đã
                                                thu</button>
                                        </form>

                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary btn-open-edit-modal"
                                            data-bs-toggle="modal" data-bs-target="#editBillModal"
                                            data-bill-id="<?php echo $bill['id']; ?>"
                                            data-title="<?php echo owner_dash_e($bill['title']); ?>"
                                            data-price="<?php echo $bill['room_price_billed']; ?>"
                                            data-elecold="<?php echo $bill['elec_old']; ?>"
                                            data-elecnew="<?php echo $bill['elec_new']; ?>"
                                            data-waterold="<?php echo $bill['water_old']; ?>"
                                            data-waternew="<?php echo $bill['water_new']; ?>"
                                            data-trash="<?php echo $bill['trash_fee']; ?>"
                                            data-internet="<?php echo $bill['internet_fee']; ?>">
                                            <i class="fas fa-pen"></i>
                                        </button>

                                        <form method="POST" action="">
                                            <input type="hidden" name="action" value="delete_bill">
                                            <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                title="Hủy hóa đơn"
                                                onclick="return confirm('Bạn muốn hủy hóa đơn này?');"><i
                                                    class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-4 text-center text-muted">Không tìm thấy hóa đơn nào phù hợp với bộ lọc hiện tại.
                    </div>
                    <?php endif; ?>
                </div>

            </section>
        </div>
    </main>

    <div class="modal fade" id="createBillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Chốt sổ: <span id="create_modal_title" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_bill">
                        <input type="hidden" name="motel_id" id="create_motel_id">
                        <input type="hidden" name="tenant_id" id="create_tenant_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tiền phòng (VNĐ)</label>
                            <input type="number" class="form-control calc-input-create" name="room_price"
                                id="create_room_price" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold"><i class="fas fa-bolt text-warning"></i> Điện (Số
                                    mới)</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="create_elec_old_display"
                                        style="min-width: 60px;">Cũ: 0</span>
                                    <input type="hidden" name="elec_old" id="create_elec_old">
                                    <input type="number" class="form-control calc-input-create" name="elec_new"
                                        id="create_elec_new" placeholder="Nhập số" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold"><i class="fas fa-faucet-drip text-info"></i> Nước (Số
                                    mới)</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="create_water_old_display"
                                        style="min-width: 60px;">Cũ: 0</span>
                                    <input type="hidden" name="water_old" id="create_water_old">
                                    <input type="number" class="form-control calc-input-create" name="water_new"
                                        id="create_water_new" placeholder="Nhập số" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Phí rác (VNĐ)</label>
                                <input type="number" class="form-control calc-input-create" name="trash_fee"
                                    id="create_trash_fee" value="50000" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Phí DV / Mạng (VNĐ)</label>
                                <input type="number" class="form-control calc-input-create" name="internet_fee"
                                    id="create_internet_fee" value="100000" required>
                            </div>
                        </div>
                        <div class="alert alert-secondary text-center mb-0 mt-4">
                            <span class="text-muted">Tổng tiền dự kiến thu:</span><br>
                            <h3 class="text-success mb-0 fw-bold" id="create_total_preview">0 đ</h3>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i> Chốt & Tạo
                            hóa đơn</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBillModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-warning">
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title">Chỉnh sửa hóa đơn: <span id="edit_modal_title" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_bill">
                        <input type="hidden" name="bill_id" id="edit_bill_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Tiền phòng (VNĐ)</label>
                            <input type="number" class="form-control calc-input-edit" name="room_price"
                                id="edit_room_price" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold"><i class="fas fa-bolt text-warning"></i> Điện (Số
                                    mới)</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="edit_elec_old_display"
                                        style="min-width: 60px;">Cũ: 0</span>
                                    <input type="hidden" id="edit_elec_old"> <input type="number"
                                        class="form-control calc-input-edit" name="elec_new" id="edit_elec_new"
                                        required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold"><i class="fas fa-faucet-drip text-info"></i> Nước (Số
                                    mới)</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="edit_water_old_display"
                                        style="min-width: 60px;">Cũ: 0</span>
                                    <input type="hidden" id="edit_water_old"> <input type="number"
                                        class="form-control calc-input-edit" name="water_new" id="edit_water_new"
                                        required>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">Phí rác (VNĐ)</label>
                                <input type="number" class="form-control calc-input-edit" name="trash_fee"
                                    id="edit_trash_fee" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">Phí DV / Mạng (VNĐ)</label>
                                <input type="number" class="form-control calc-input-edit" name="internet_fee"
                                    id="edit_internet_fee" required>
                            </div>
                        </div>
                        <div class="alert alert-secondary text-center mb-0 mt-4">
                            <span class="text-muted">Tổng tiền sau khi sửa:</span><br>
                            <h3 class="text-danger mb-0 fw-bold" id="edit_total_preview">0 đ</h3>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Lưu thay
                            đổi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // HÀM TÍNH TOÁN CHUNG CHO CẢ 2 MODAL
    function calculateTotal(modalPrefix) {
        const roomPrice = parseFloat(document.getElementById(`${modalPrefix}_room_price`).value) || 0;
        const elecOld = parseFloat(document.getElementById(`${modalPrefix}_elec_old`).value) || 0;
        const elecNew = parseFloat(document.getElementById(`${modalPrefix}_elec_new`).value) || 0;
        const waterOld = parseFloat(document.getElementById(`${modalPrefix}_water_old`).value) || 0;
        const waterNew = parseFloat(document.getElementById(`${modalPrefix}_water_new`).value) || 0;
        const trashFee = parseFloat(document.getElementById(`${modalPrefix}_trash_fee`).value) || 0;
        const internetFee = parseFloat(document.getElementById(`${modalPrefix}_internet_fee`).value) || 0;

        let total = 0;
        if (elecNew >= elecOld && waterNew >= waterOld) {
            total = roomPrice + ((elecNew - elecOld) * 3500) + ((waterNew - waterOld) * 20000) + trashFee + internetFee;
        }

        document.getElementById(`${modalPrefix}_total_preview`).innerText = total > 0 ? total.toLocaleString('vi-VN') +
            ' đ' : '-- đ';
    }

    // Gắn event listener cho Modal CREATE
    document.querySelectorAll('.calc-input-create').forEach(input => {
        input.addEventListener('input', () => calculateTotal('create'));
    });

    // Đổ dữ liệu vào Modal CREATE khi click
    document.querySelectorAll('.btn-open-create-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('create_modal_title').innerText = this.dataset.title;
            document.getElementById('create_motel_id').value = this.dataset.motelId;
            document.getElementById('create_tenant_id').value = this.dataset.tenantId;

            document.getElementById('create_room_price').value = this.dataset.price;

            document.getElementById('create_elec_old').value = this.dataset.elecold;
            document.getElementById('create_elec_old_display').innerText = 'Cũ: ' + this.dataset
                .elecold;
            document.getElementById('create_elec_new').value = '';
            document.getElementById('create_elec_new').min = this.dataset.elecold;

            document.getElementById('create_water_old').value = this.dataset.waterold;
            document.getElementById('create_water_old_display').innerText = 'Cũ: ' + this.dataset
                .waterold;
            document.getElementById('create_water_new').value = '';
            document.getElementById('create_water_new').min = this.dataset.waterold;

            calculateTotal('create'); // Reset preview
        });
    });

    // Gắn event listener cho Modal EDIT
    document.querySelectorAll('.calc-input-edit').forEach(input => {
        input.addEventListener('input', () => calculateTotal('edit'));
    });

    // Đổ dữ liệu vào Modal EDIT khi click
    document.querySelectorAll('.btn-open-edit-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_modal_title').innerText = this.dataset.title;
            document.getElementById('edit_bill_id').value = this.dataset.billId;

            document.getElementById('edit_room_price').value = this.dataset.price;

            document.getElementById('edit_elec_old').value = this.dataset.elecold;
            document.getElementById('edit_elec_old_display').innerText = 'Cũ: ' + this.dataset.elecold;
            document.getElementById('edit_elec_new').value = this.dataset.elecnew;
            document.getElementById('edit_elec_new').min = this.dataset.elecold;

            document.getElementById('edit_water_old').value = this.dataset.waterold;
            document.getElementById('edit_water_old_display').innerText = 'Cũ: ' + this.dataset
                .waterold;
            document.getElementById('edit_water_new').value = this.dataset.waternew;
            document.getElementById('edit_water_new').min = this.dataset.waterold;

            document.getElementById('edit_trash_fee').value = this.dataset.trash;
            document.getElementById('edit_internet_fee').value = this.dataset.internet;

            calculateTotal('edit'); // Generate current preview
        });
    });
    </script>
</body>

</html>