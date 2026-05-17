<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../components/PublicNav.php';
@require_once '../../core/MonthlyInvoiceSchema.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

qlpt_ensure_monthly_invoices_table($conn);

$db = new Database($conn);
$userId = (int)$_SESSION['user_id'];

$stmt = $db->prepare('
    SELECT i.*, m.title AS motel_title
    FROM monthly_invoices i
    JOIN motels m ON i.motel_id = m.id
    WHERE i.tenant_user_id = ?
    ORDER BY i.period_year DESC, i.period_month DESC, i.id DESC
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hóa đơn điện nước - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 30px; }
        .inv-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 14px; border: 1px solid #e5eaf2; box-shadow: 0 2px 10px rgba(0,0,0,.04); }
    </style>
</head>
<body>
    <?php qlpt_render_public_nav(['base' => '../', 'active' => 'rooms']); ?>
    <?php /*
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-home"></i> QuanLyPhongTro</a>
        </div>
    </nav>
    */ ?>
    <div class="container-lg" style="padding: 30px 0;">
        <div class="row">
            <div class="col-lg-3">
                <?php $userNavActive = 'invoices'; require __DIR__ . '/_nav_sidebar.php'; ?>
            </div>
            <div class="col-lg-9">
                <div class="main-content">
                    <h1 class="fw-bold mb-2"><i class="fas fa-file-invoice"></i> Hóa đơn điện nước</h1>
                    <p class="text-muted mb-4">Chủ trọ gửi kỳ tiền điện, nước và phí khác theo tháng. Tổng tiền chỉ mang tính minh họa trong hệ thống.</p>
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $r): ?>
                            <div class="inv-card">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                    <strong><?php echo htmlspecialchars($r['motel_title'] ?? ''); ?></strong>
                                    <span class="badge bg-primary">Tháng <?php echo (int)$r['period_month']; ?>/<?php echo (int)$r['period_year']; ?></span>
                                </div>
                                <div class="row g-2 small">
                                    <div class="col-md-4"><span class="text-muted">Điện:</span> <strong><?php echo number_format((int)$r['electricity_fee']); ?> VNĐ</strong></div>
                                    <div class="col-md-4"><span class="text-muted">Nước:</span> <strong><?php echo number_format((int)$r['water_fee']); ?> VNĐ</strong></div>
                                    <div class="col-md-4"><span class="text-muted">Khác:</span> <strong><?php echo number_format((int)$r['other_fee']); ?> VNĐ</strong></div>
                                </div>
                                <?php if (!empty($r['note'])): ?>
                                    <p class="small text-muted mt-2 mb-0"><?php echo nl2br(htmlspecialchars((string)$r['note'])); ?></p>
                                <?php endif; ?>
                                <div class="mt-2 fw-bold">Tổng: <?php echo number_format((int)$r['electricity_fee'] + (int)$r['water_fee'] + (int)$r['other_fee']); ?> VNĐ</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="inv-card text-center text-muted py-5">
                            <i class="fas fa-receipt fa-3x mb-3 d-block opacity-50"></i>
                            Chưa có hóa đơn nào. Khi chủ trọ lập hóa đơn tháng, bạn sẽ thấy tại đây và nhận thông báo.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
