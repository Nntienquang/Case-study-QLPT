<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$userId = (int)$_SESSION['user_id'];

// Get monthly bills for this user
$stmt = $db->prepare('
    SELECT mb.*, m.title AS motel_title
    FROM monthly_bills mb
    JOIN motels m ON mb.motel_id = m.id
    WHERE mb.user_id = ?
    ORDER BY mb.billing_year DESC, mb.billing_month DESC, mb.id DESC
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
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .main-content { padding: 30px; }
        .inv-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid #e5eaf2; box-shadow: 0 4px 15px rgba(0,0,0,.03); transition: transform 0.2s; }
        .inv-card:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,.08); }
        .bill-detail { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-top: 15px; }
    </style>
</head>
<body class="workbench">
    <?php 
    @require_once __DIR__ . '/../components/PublicNav.php'; 
    qlpt_render_public_nav(['base' => '../', 'active' => 'user']); 
    ?>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <?php
                $userNavActive = 'invoices';
                $userNavVariant = 'workbench';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </aside>

            <section>
                <div class="main-content">
                    <h1 class="fw-bold mb-2"><i class="fas fa-file-invoice"></i> Hóa đơn hàng tháng</h1>
                    <p class="text-muted mb-4">Theo dõi chi tiết tiền điện, nước, rác và dịch vụ hàng tháng của bạn.</p>
                    
                    <?php if ($rows): ?>
                        <?php foreach ($rows as $r): ?>
                            <div class="inv-card">
                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-3">
                                    <div>
                                        <h5 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($r['motel_title'] ?? ''); ?></h5>
                                        <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Tháng <?php echo (int)$r['billing_month']; ?>/<?php echo (int)$r['billing_year']; ?></div>
                                    </div>
                                    <div class="text-end">
                                        <?php if ($r['status'] === 'paid'): ?>
                                            <span class="badge bg-success px-3 py-2"><i class="fas fa-check-circle"></i> Đã thanh toán</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark px-3 py-2"><i class="fas fa-clock"></i> Đang nợ / Chưa thanh toán</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-3 col-6">
                                        <div class="p-3 bg-light rounded text-center border">
                                            <i class="fas fa-bolt text-warning fs-4 mb-2"></i>
                                            <div class="small text-muted">Điện (<?php echo (int)($r['elec_new'] - $r['elec_old']); ?> chữ)</div>
                                            <div class="fw-bold"><?php echo number_format((int)(($r['elec_new'] - $r['elec_old']) * $r['elec_price'])); ?> đ</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="p-3 bg-light rounded text-center border">
                                            <i class="fas fa-faucet-drip text-info fs-4 mb-2"></i>
                                            <div class="small text-muted">Nước (<?php echo (int)($r['water_new'] - $r['water_old']); ?> khối)</div>
                                            <div class="fw-bold"><?php echo number_format((int)(($r['water_new'] - $r['water_old']) * $r['water_price'])); ?> đ</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="p-3 bg-light rounded text-center border">
                                            <i class="fas fa-trash text-secondary fs-4 mb-2"></i>
                                            <div class="small text-muted">Phí Rác</div>
                                            <div class="fw-bold"><?php echo number_format((int)$r['trash_fee']); ?> đ</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <div class="p-3 bg-light rounded text-center border">
                                            <i class="fas fa-wifi text-primary fs-4 mb-2"></i>
                                            <div class="small text-muted">DV / Mạng</div>
                                            <div class="fw-bold"><?php echo number_format((int)$r['internet_fee']); ?> đ</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-4 p-3 rounded d-flex justify-content-between align-items-center" style="background-color: <?php echo $r['status'] === 'paid' ? '#e8f5e9' : '#fff3e0'; ?>">
                                    <span class="fs-5 text-muted">Tổng cộng (đã bao gồm tiền phòng):</span>
                                    <h3 class="fw-bold text-danger mb-0"><?php echo number_format((int)$r['total_amount']); ?> VNĐ</h3>
                                </div>
                                <div class="text-end mt-2">
                                    <small class="text-muted">Lập ngày: <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="inv-card text-center text-muted py-5">
                            <i class="fas fa-file-invoice fa-3x mb-3 d-block opacity-50"></i>
                            <h5 class="fw-bold">Chưa có hóa đơn nào</h5>
                            <p>Khi chủ trọ lập hóa đơn tháng, bạn sẽ thấy chi tiết số tiền điện nước tại đây.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
