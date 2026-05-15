<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);

// ==========================================
// XỬ LÝ POST: YÊU CẦU RÚT TIỀN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'withdraw') {
    $amount = (int)$_POST['amount'];

    // Check số dư hiện tại
    $stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $wallet = $stmt->get_result()->fetch_assoc();
    $current_balance = $wallet['balance'] ?? 0;

    if ($amount > 0 && $amount <= $current_balance) {
        // 1. Trừ tiền ví ngay lập tức để tránh rút lố
        $new_balance = $current_balance - $amount;
        $conn->query("UPDATE wallets SET balance = $new_balance WHERE user_id = $owner_id");

        // 2. Tạo request rút tiền
        $stmt = $conn->prepare("INSERT INTO withdraw_requests (user_id, amount, status) VALUES (?, ?, 'pending')");
        $stmt->bind_param("ii", $owner_id, $amount);
        $stmt->execute();

        // 3. Ghi log giao dịch (Loại tiền ra)
        $conn->query("INSERT INTO transactions (from_user, to_user, amount, type) VALUES ($owner_id, NULL, $amount, 'withdraw')");

        header('Location: revenue.php?success=withdraw_requested');
        exit;
    } else {
        header('Location: revenue.php?error=invalid_amount');
        exit;
    }
}

// ==========================================
// LẤY DỮ LIỆU HIỂN THỊ
// ==========================================

// 1. Lấy số dư ví hiện tại
$stmt = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$wallet = $stmt->get_result()->fetch_assoc();
$current_balance = $wallet['balance'] ?? 0;
$stmt->close();

// Lấy tổng tiền đang chờ Admin duyệt rút
$stmt = $conn->prepare("SELECT SUM(amount) as pending_withdraw FROM withdraw_requests WHERE user_id = ? AND status = 'pending'");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$pending_withdraw = $stmt->get_result()->fetch_assoc()['pending_withdraw'] ?? 0;

// 2. Lấy thống kê tổng quan
$stmt = $conn->prepare("
    SELECT 
        COUNT(DISTINCT m.id) as total_listings,
        COUNT(DISTINCT b.id) as total_bookings,
        SUM(CASE WHEN b.status = 'completed' THEN b.deposit_amount ELSE 0 END) as lifetime_deposit,
        COUNT(DISTINCT CASE WHEN b.status = 'pending' OR b.status = 'paid' THEN b.id END) as pending_actions
    FROM motels m
    LEFT JOIN bookings b ON m.id = b.motel_id
    WHERE m.user_id = ?
");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Tính tổng doanh thu từ hóa đơn đã thanh toán
$stmt = $conn->prepare("SELECT SUM(total_amount) as total_bill_paid FROM monthly_bills mb JOIN motels m ON mb.motel_id = m.id WHERE m.user_id = ? AND mb.status = 'paid'");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$lifetime_bills = $stmt->get_result()->fetch_assoc()['total_bill_paid'] ?? 0;
$lifetime_revenue = ($stats['lifetime_deposit'] ?? 0) + $lifetime_bills;

// 3. Chuẩn bị dữ liệu cho Biểu đồ (6 tháng gần nhất)
$chartLabels = [];
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('m', strtotime("-$i months"));
    $year = date('Y', strtotime("-$i months"));
    $label = "T$month/$year";
    $chartLabels[] = $label;
    $chartData[$label] = 0;
}

// Lấy doanh thu từ Hóa đơn (theo tháng)
$stmtChart = $conn->prepare("
    SELECT billing_month, billing_year, SUM(total_amount) as total
    FROM monthly_bills mb
    JOIN motels m ON mb.motel_id = m.id
    WHERE m.user_id = ? AND mb.status = 'paid'
    GROUP BY billing_year, billing_month
");
$stmtChart->bind_param("i", $owner_id);
$stmtChart->execute();
$resChart = $stmtChart->get_result();
while ($row = $resChart->fetch_assoc()) {
    $label = "T" . sprintf("%02d", $row['billing_month']) . "/" . $row['billing_year'];
    if (isset($chartData[$label])) {
        $chartData[$label] += $row['total'];
    }
}

// Lấy doanh thu từ Tiền cọc nhận được (theo tháng)
$stmtTrans = $conn->prepare("
    SELECT MONTH(created_at) as m, YEAR(created_at) as y, SUM(amount) as total
    FROM transactions
    WHERE to_user = ? AND type = 'release'
    GROUP BY YEAR(created_at), MONTH(created_at)
");
$stmtTrans->bind_param("i", $owner_id);
$stmtTrans->execute();
$resTrans = $stmtTrans->get_result();
while ($row = $resTrans->fetch_assoc()) {
    $label = "T" . sprintf("%02d", $row['m']) . "/" . $row['y'];
    if (isset($chartData[$label])) {
        $chartData[$label] += $row['total'];
    }
}

// 4. Lấy lịch sử giao dịch chi tiết
$stmt = $conn->prepare("
    SELECT t.*, m.title as motel_title, u.name as from_name
    FROM transactions t
    LEFT JOIN bookings b ON t.booking_id = b.id
    LEFT JOIN motels m ON b.motel_id = m.id
    LEFT JOIN users u ON t.from_user = u.id
    WHERE t.to_user = ? OR t.from_user = ?
    ORDER BY t.created_at DESC
    LIMIT 30
");
$stmt->bind_param("ii", $owner_id, $owner_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function get_trans_type_label($type)
{
    return match ($type) {
        'release', 'deposit' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-arrow-down me-1"></i> Nhận tiền</span>',
        'withdraw' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-arrow-up me-1"></i> Rút tiền</span>',
        'refund' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-rotate-left me-1"></i> Hoàn tiền</span>',
        'fee' => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fas fa-percent me-1"></i> Trừ phí DV</span>',
        default => '<span class="badge bg-light text-dark border">Khác</span>',
    };
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Quản Lý Doanh Thu - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
=======
    <title>Doanh thu - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 30px; }
        .stat-card { background: white; padding: 30px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .stat-icon { font-size: 40px; color: #667eea; margin-bottom: 15px; }
        .stat-number { font-size: 32px; font-weight: 700; color: #333; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }
        .transaction-card { background: white; padding: 15px; border-radius: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .transaction-info { flex: 1; }
        .transaction-amount { font-size: 18px; font-weight: 700; color: #667eea; }
    </style>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .balance-card {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .balance-card::after {
            content: '\f53d';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -30px;
            font-size: 10rem;
            color: rgba(255, 255, 255, 0.1);
        }

        .balance-amount {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .stat-small-card {
            border: 1px solid #e9ecef;
            transition: 0.2s;
        }

        .stat-small-card:hover {
            border-color: #dee2e6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .trans-item {
            padding: 15px;
            border-bottom: 1px solid #f1f3f5;
            transition: 0.2s;
        }

        .trans-item:hover {
            background-color: #f8f9fa;
        }

        .amount-plus {
            color: #198754;
            font-weight: 700;
        }

        .amount-minus {
            color: #dc3545;
            font-weight: 700;
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
<<<<<<< HEAD
            <div class="wb-user">
                <span><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
=======
        </div>
    </nav>

    <div class="container-lg" style="padding: 30px 0;">
        <div class="row">
            <div class="col-lg-3">
                <?php
                $ownerNavActive = 'revenue';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
            </div>
        </div>
    </header>

<<<<<<< HEAD
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
                <a class="wb-side-link" href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a>

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link active" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <?php if (isset($_GET['success']) && $_GET['success'] == 'withdraw_requested'): ?>
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Yêu cầu rút tiền đã được gửi! Quản trị viên sẽ xử lý trong
                        24h.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'invalid_amount'): ?>
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        ⚠️ Số tiền rút không hợp lệ hoặc vượt quá số dư trong ví!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="wb-section-head d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <h2 class="mb-1">Tài Chính & Doanh Thu</h2>
                        <p class="text-muted mb-0">Theo dõi dòng tiền, biểu đồ tăng trưởng và quản lý ví điện tử.</p>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#withdrawModal"
                        <?php echo $current_balance <= 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-building-columns me-1"></i> Rút tiền về NH
                    </button>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-lg-5">
                        <div class="wb-card balance-card p-4 h-100 d-flex flex-column justify-content-center">
                            <div class="small opacity-75 mb-1">Số dư ví có thể rút</div>
                            <div class="balance-amount mb-2"><?php echo number_format($current_balance); ?> <small
                                    class="fs-4">đ</small></div>
                            <div class="d-flex gap-2 align-items-center mt-2">
                                <span class="badge bg-white text-primary bg-opacity-25"><i
                                        class="fas fa-shield-check"></i> An toàn</span>
                                <?php if ($pending_withdraw > 0): ?>
                                    <span class="small opacity-75"><i class="fas fa-hourglass-half"></i> Đang chờ duyệt:
                                        <?php echo number_format($pending_withdraw); ?>đ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="wb-card stat-small-card p-3 h-100">
                                    <div class="text-muted small mb-1">Tổng doanh thu lịch sử</div>
                                    <h4 class="fw-bold mb-0 text-primary">
                                        <?php echo number_format($lifetime_revenue); ?> đ</h4>
=======
            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-chart-column"></i> Doanh thu của tôi
                    </h1>

                    <div class="row mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-home"></i></div>
                                <div class="stat-number"><?php echo $stats['total_listings']; ?></div>
                                <div class="stat-label">Phòng đang cho thuê</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-calendar"></i></div>
                                <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                                <div class="stat-label">Tổng đơn đặt</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-hourglass"></i></div>
                                <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                                <div class="stat-label">Đơn chưa hoàn thành</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="stat-number"><?php echo number_format($stats['total_revenue'] ?? 0); ?></div>
                                <div class="stat-label">Tổng doanh thu (VNĐ)</div>
                            </div>
                        </div>
                    </div>

                    <h3 style="font-weight: 700; margin-top: 40px; margin-bottom: 20px;">
                        <i class="fas fa-list"></i> Lịch sử giao dịch
                    </h3>

                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $trans): ?>
                            <div class="transaction-card">
                                <div class="transaction-info">
                                    <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($trans['title']); ?></div>
                                    <small style="color: #666;">Check-in: <?php echo date('d/m/Y', strtotime($trans['check_in_date'])); ?></small>
                                </div>
                                <div class="transaction-amount">
                                    +<?php echo number_format($trans['deposit_amount']); ?> VNĐ
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="wb-card stat-small-card p-3 h-100">
                                    <div class="text-muted small mb-1">Tỷ lệ phòng lấp đầy</div>
                                    <h4 class="fw-bold mb-0"><?php echo $stats['total_listings']; ?> phòng</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="wb-card stat-small-card p-3 h-100">
                                    <div class="text-muted small mb-1">Tổng lượt khách đã cọc</div>
                                    <h4 class="fw-bold mb-0"><?php echo $stats['total_bookings']; ?> đơn</h4>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="wb-card stat-small-card p-3 h-100">
                                    <div class="text-muted small mb-1">Hành động đang chờ</div>
                                    <h4 class="fw-bold mb-0 text-warning"><?php echo $stats['pending_actions']; ?> việc
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wb-card mb-5 border-0 shadow-sm p-4">
                    <h5 class="fw-bold mb-4"><i class="fas fa-chart-area text-primary me-2"></i> Tăng trưởng doanh thu 6
                        tháng qua</h5>
                    <div style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
                    <h4 class="fw-bold mb-0"><i class="fas fa-file-invoice-dollar text-primary me-2"></i> Sao Kê Giao
                        Dịch</h4>
                    <div class="btn-group shadow-sm">
                        <button class="btn btn-outline-secondary btn-sm active" onclick="filterTrans('all', this)">Tất
                            cả</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="filterTrans('in', this)">Tiền
                            vào</button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="filterTrans('out', this)">Tiền
                            ra</button>
                    </div>
                </div>

                <div class="wb-card p-0 overflow-hidden mb-5 border-0 shadow-sm">
                    <?php if (count($transactions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4 py-3">Thời gian</th>
                                        <th>Loại GD</th>
                                        <th>Nội dung / Phòng trọ</th>
                                        <th>Đối tác</th>
                                        <th class="text-end pe-4">Số tiền (VNĐ)</th>
                                    </tr>
                                </thead>
                                <tbody id="transTableBody">
                                    <?php foreach ($transactions as $trans):
                                        $isIn = ($trans['to_user'] == $owner_id);
                                        $rowClass = $isIn ? 'trans-in' : 'trans-out';
                                    ?>
                                        <tr class="trans-item <?php echo $rowClass; ?>">
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">
                                                    <?php echo date('d/m/Y', strtotime($trans['created_at'])); ?></div>
                                                <div class="text-muted small">
                                                    <?php echo date('H:i', strtotime($trans['created_at'])); ?></div>
                                            </td>
                                            <td><?php echo get_trans_type_label($trans['type']); ?></td>
                                            <td>
                                                <div class="fw-bold text-dark">
                                                    <?php echo htmlspecialchars($trans['motel_title'] ?? 'Giao dịch hệ thống'); ?>
                                                </div>
                                                <?php if ($trans['booking_id']): ?>
                                                    <div class="text-muted small">Mã Booking:
                                                        #BK-<?php echo $trans['booking_id']; ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-user-circle text-muted"></i>
                                                <?php echo htmlspecialchars($trans['from_name'] ?? 'Hệ thống Admin'); ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($isIn): ?>
                                                    <span class="amount-plus">+<?php echo number_format($trans['amount']); ?>
                                                        đ</span>
                                                <?php else: ?>
                                                    <span class="amount-minus">-<?php echo number_format($trans['amount']); ?>
                                                        đ</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
<<<<<<< HEAD
                        <div class="text-center py-5">
                            <i class="fas fa-receipt fa-4x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted">Bạn chưa có bất kỳ giao dịch tài chính nào phát sinh.</p>
=======
                        <div style="text-align: center; padding: 40px; background: white; border-radius: 12px; color: #999;">
                            Không có giao dịch hoàn thành
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <div class="modal fade" id="withdrawModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-building-columns me-2"></i> Rút tiền về Ngân hàng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body p-4">
                        <input type="hidden" name="action" value="withdraw">

                        <div class="d-flex justify-content-between mb-3 border-bottom pb-2">
                            <span class="text-muted">Khả dụng:</span>
                            <strong class="text-success"><?php echo number_format($current_balance); ?> VNĐ</strong>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nhập số tiền muốn rút (VNĐ)</label>
                            <input type="number" class="form-control form-control-lg text-end fw-bold text-success"
                                name="amount" max="<?php echo $current_balance; ?>" min="50000" step="10000"
                                placeholder="Tối thiểu 50.000đ" required>
                        </div>

                        <div class="alert alert-info small mb-0">
                            <i class="fas fa-info-circle me-1"></i> Tiền sẽ được chuyển về tài khoản ngân hàng bạn đã
                            cài đặt trong phần <strong>Hồ sơ</strong> trong vòng 24h.
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i> Gửi Yêu
                            Cầu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 1. Cấu hình Chart.js
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode(array_values($chartData)); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 3,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#0d6efd',
                    fill: true,
                    tension: 0.4 // Làm cong đường line
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let value = context.raw || 0;
                                return value.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000) return (value / 1000000) + ' Tr';
                                if (value >= 1000) return (value / 1000) + ' K';
                                return value;
                            }
                        }
                    }
                }
            }
        });

        // 2. Script Lọc giao dịch bằng JS (Không tải lại trang)
        function filterTrans(type, btn) {
            // Đổi active class cho nút
            const buttons = btn.parentElement.querySelectorAll('button');
            buttons.forEach(b => b.classList.remove('active', 'btn-secondary'));
            buttons.forEach(b => b.classList.add('btn-outline-secondary'));
            btn.classList.remove('btn-outline-secondary');
            btn.classList.add('active', 'btn-secondary');

            // Lọc các hàng trong bảng
            const rows = document.querySelectorAll('.trans-item');
            rows.forEach(row => {
                if (type === 'all') {
                    row.style.display = '';
                } else if (type === 'in' && row.classList.contains('trans-in')) {
                    row.style.display = '';
                } else if (type === 'out' && row.classList.contains('trans-out')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>

</html>