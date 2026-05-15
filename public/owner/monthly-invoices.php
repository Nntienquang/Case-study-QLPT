<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../../core/NotificationHelper.php';
@require_once '../../core/MonthlyInvoiceSchema.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../login.php');
    exit;
}

qlpt_ensure_monthly_invoices_table($conn);

$db = new Database($conn);
$ownerId = (int)$_SESSION['user_id'];
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_invoice'])) {
    $pair = (string)($_POST['tenant_pair'] ?? '');
    $parts = explode(':', $pair, 2);
    $tenantUserId = isset($parts[0]) ? (int)$parts[0] : 0;
    $motelId = isset($parts[1]) ? (int)$parts[1] : 0;
    $periodMonth = (int)($_POST['period_month'] ?? 0);
    $periodYear = (int)($_POST['period_year'] ?? 0);
    $electric = max(0, (int)($_POST['electricity_fee'] ?? 0));
    $water = max(0, (int)($_POST['water_fee'] ?? 0));
    $other = max(0, (int)($_POST['other_fee'] ?? 0));
    $note = trim((string)($_POST['note'] ?? ''));

    $chk = $db->prepare('SELECT m.id FROM motels m WHERE m.id = ? AND m.user_id = ?');
    $chk->bind_param('ii', $motelId, $ownerId);
    $chk->execute();
    $okMotel = (bool)$chk->get_result()->fetch_assoc();
    $chk->close();

    $chk2 = $db->prepare('
        SELECT 1 FROM bookings b
        JOIN motels m ON b.motel_id = m.id
        WHERE b.user_id = ? AND b.motel_id = ? AND m.user_id = ?
          AND b.status IN (\'accepted\',\'paid\',\'completed\')
        LIMIT 1
    ');
    $chk2->bind_param('iii', $tenantUserId, $motelId, $ownerId);
    $chk2->execute();
    $okTenant = (bool)$chk2->get_result()->fetch_assoc();
    $chk2->close();

    if (!$okMotel || !$okTenant || $periodMonth < 1 || $periodMonth > 12 || $periodYear < 2020 || $periodYear > 2100) {
        $flash = 'Dữ liệu không hợp lệ: chọn phòng của bạn và người thuê đã có đơn được chấp nhận trở lên.';
        $flashType = 'danger';
    } else {
        $stmt = $db->prepare('
            INSERT INTO monthly_invoices (owner_id, tenant_user_id, motel_id, period_month, period_year, electricity_fee, water_fee, other_fee, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE electricity_fee = VALUES(electricity_fee), water_fee = VALUES(water_fee), other_fee = VALUES(other_fee), note = VALUES(note)
        ');
        $stmt->bind_param('iiiiiiiis', $ownerId, $tenantUserId, $motelId, $periodMonth, $periodYear, $electric, $water, $other, $note);
        if ($stmt->execute()) {
            $total = $electric + $water + $other;
            qlpt_send_notification(
                $db,
                $tenantUserId,
                'monthly_invoice',
                'Hóa đơn tiền điện / nước tháng ' . $periodMonth . '/' . $periodYear,
                'Chủ trọ vừa gửi hóa đơn. Tổng tạm tính: ' . number_format($total) . ' VNĐ. Mở mục Hóa đơn điện nước để xem chi tiết.',
                'user/my-invoices.php'
            );
            $flash = 'Đã lưu hóa đơn và thông báo cho người thuê.';
        } else {
            $flash = 'Không thể lưu hóa đơn.';
            $flashType = 'danger';
        }
        $stmt->close();
    }
}

$stmt = $db->prepare('
    SELECT DISTINCT b.user_id AS tenant_id, u.name AS tenant_name, b.motel_id, m.title AS motel_title
    FROM bookings b
    JOIN motels m ON b.motel_id = m.id
    JOIN users u ON b.user_id = u.id
    WHERE m.user_id = ? AND b.status IN (\'accepted\',\'paid\',\'completed\')
    ORDER BY m.title, u.name
    LIMIT 80
');
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$tenantRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare('
    SELECT i.*, u.name AS tenant_name, m.title AS motel_title
    FROM monthly_invoices i
    JOIN users u ON i.tenant_user_id = u.id
    JOIN motels m ON i.motel_id = m.id
    WHERE i.owner_id = ?
    ORDER BY i.period_year DESC, i.period_month DESC, i.id DESC
    LIMIT 40
');
$stmt->bind_param('i', $ownerId);
$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn điện nước - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { color: #fff !important; font-weight: 700; }
        .main-content { padding: 30px; }
        .card-soft { background: #fff; border-radius: 12px; padding: 22px; border: 1px solid #e5eaf2; margin-bottom: 18px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-home"></i> QuanLyPhongTro</a>
        </div>
    </nav>
    <div class="container-lg py-4">
        <div class="row">
            <div class="col-lg-3">
                <?php $ownerNavActive = 'invoices'; require __DIR__ . '/_nav_sidebar.php'; ?>
            </div>
            <div class="col-lg-9">
                <div class="main-content">
                    <h1 class="fw-bold mb-2"><i class="fas fa-file-invoice-dollar"></i> Hóa đơn điện nước theo tháng</h1>
                    <p class="text-muted mb-4">Lập hóa đơn cho người thuê đang có đơn được chấp nhận trở lên. Trùng kỳ sẽ được cập nhật.</p>
                    <?php if ($flash !== ''): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($flashType); ?>"><?php echo htmlspecialchars($flash); ?></div>
                    <?php endif; ?>

                    <div class="card-soft">
                        <h5 class="fw-bold mb-3">Tạo / cập nhật hóa đơn</h5>
                        <form method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Người thuê & phòng</label>
                                <select name="tenant_pair" class="form-select" required>
                                    <option value="">-- Chọn --</option>
                                    <?php foreach ($tenantRows as $tr): ?>
                                        <option value="<?php echo (int)$tr['tenant_id']; ?>:<?php echo (int)$tr['motel_id']; ?>">
                                            <?php echo htmlspecialchars($tr['tenant_name']); ?> — <?php echo htmlspecialchars($tr['motel_title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tháng</label>
                                <select name="period_month" class="form-select" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php echo (int)date('n') === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Năm</label>
                                <input type="number" name="period_year" class="form-control" required value="<?php echo (int)date('Y'); ?>" min="2020" max="2100">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tiền điện (VNĐ)</label>
                                <input type="number" name="electricity_fee" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tiền nước (VNĐ)</label>
                                <input type="number" name="water_fee" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Phí khác (VNĐ)</label>
                                <input type="number" name="other_fee" class="form-control" min="0" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="note" class="form-control" rows="2" placeholder="Chỉ số cũ/mới, hạn thanh toán..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="create_invoice" value="1" class="btn btn-primary">Lưu & gửi thông báo</button>
                            </div>
                        </form>
                    </div>

                    <h5 class="fw-bold mb-3">Gần đây</h5>
                    <?php if ($list): ?>
                        <?php foreach ($list as $r): ?>
                            <div class="card-soft small">
                                <strong><?php echo htmlspecialchars($r['motel_title']); ?></strong> · <?php echo htmlspecialchars($r['tenant_name']); ?>
                                <span class="badge bg-secondary ms-1"><?php echo (int)$r['period_month']; ?>/<?php echo (int)$r['period_year']; ?></span>
                                <div class="mt-1">Điện <?php echo number_format((int)$r['electricity_fee']); ?> + Nước <?php echo number_format((int)$r['water_fee']); ?> + Khác <?php echo number_format((int)$r['other_fee']); ?> =
                                    <strong><?php echo number_format((int)$r['electricity_fee'] + (int)$r['water_fee'] + (int)$r['other_fee']); ?> VNĐ</strong>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">Chưa có hóa đơn nào được lưu.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
