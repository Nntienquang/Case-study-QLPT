<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$motel_id = (int)($_GET['id'] ?? 0);

// Get motel details
$stmt = $db->prepare("SELECT id, title, price, address FROM motels WHERE id = ? AND status = 'approved'");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$motel) {
    header('Location: search.php');
    exit;
}

$message = '';
$message_type = '';

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in_date'] ?? '';
    $check_out = $_POST['check_out_date'] ?? '';
    $deposit = (int)($_POST['deposit_amount'] ?? $motel['price']);
    $note = $_POST['note'] ?? '';

    if (empty($check_in) || empty($check_out)) {
        $message = 'Vui lòng chọn ngày check-in và check-out!';
        $message_type = 'danger';
    } elseif (strtotime($check_in) >= strtotime($check_out)) {
        $message = 'Ngày check-out phải sau check-in!';
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare("
            INSERT INTO bookings (user_id, motel_id, check_in_date, check_out_date, deposit_amount, note, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param("iissds", $user_id, $motel_id, $check_in, $check_out, $deposit, $note);
        
        if ($stmt->execute()) {
            $message = 'Đặt phòng thành công! Chờ chủ nhà xác nhận.';
            $message_type = 'success';
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 30px 0; }
        .form-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-section { margin-bottom: 40px; }
        .form-section h5 { font-weight: 700; color: #333; margin-bottom: 25px; border-bottom: 2px solid #667eea; padding-bottom: 15px; }
        .form-label { font-weight: 600; color: #333; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #ddd; padding: 12px; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .motel-summary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        .motel-summary h3 { font-weight: 700; margin-bottom: 15px; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 12px; }
        .summary-item-label { opacity: 0.9; }
        .summary-item-value { font-weight: 600; }
        .summary-total { border-top: 2px solid rgba(255,255,255,0.3); padding-top: 15px; margin-top: 15px; display: flex; justify-content: space-between; font-size: 20px; font-weight: 700; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; padding: 14px 30px; font-weight: 600; font-size: 16px; }
        .btn-primary:hover { color: white; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        .price-breakdown { background: #f8f9fa; padding: 20px; border-radius: 6px; margin-top: 20px; }
        .price-item { display: flex; justify-content: space-between; margin-bottom: 10px; color: #666; }
        .alert { border-radius: 12px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
        </div>
    </nav>

    <div class="container-lg main-content">
        <a href="motel-detail.php?id=<?php echo $motel_id; ?>" style="color: #667eea; text-decoration: none; margin-bottom: 20px; display: inline-block;">
            <i class="fas fa-arrow-left"></i> Quay Lại
        </a>

        <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-calendar-plus"></i> Đặt Phòng
        </h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="form-card">
                    <form method="POST">
                        <!-- Thông tin phòng -->
                        <div class="motel-summary">
                            <h3><i class="fas fa-home"></i> <?php echo htmlspecialchars($motel['title']); ?></h3>
                            <div class="summary-item">
                                <span class="summary-item-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</span>
                                <span class="summary-item-value"><?php echo htmlspecialchars($motel['address']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-item-label"><i class="fas fa-tag"></i> Giá/tháng</span>
                                <span class="summary-item-value"><?php echo number_format($motel['price']); ?> VNĐ</span>
                            </div>
                        </div>

                        <!-- Ngày đặt phòng -->
                        <div class="form-section">
                            <h5><i class="fas fa-calendar"></i> Ngày Đặt Phòng</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày Check-in *</label>
                                    <input type="date" name="check_in_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày Check-out *</label>
                                    <input type="date" name="check_out_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Tiền đặt cọc -->
                        <div class="form-section">
                            <h5><i class="fas fa-money-bill-wave"></i> Tiền Đặt Cọc</h5>
                            <div class="mb-3">
                                <label class="form-label">Số Tiền (VNĐ)</label>
                                <input type="number" name="deposit_amount" class="form-control" value="<?php echo $motel['price']; ?>" required min="1">
                            </div>
                            <div class="price-breakdown">
                                <div class="price-item">
                                    <span>Giá phòng/tháng:</span>
                                    <span><?php echo number_format($motel['price']); ?> VNĐ</span>
                                </div>
                                <div class="price-item">
                                    <span>Phí đặt cọc (tính từ đầu):</span>
                                    <span><?php echo number_format($motel['price']); ?> VNĐ</span>
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú -->
                        <div class="form-section">
                            <h5><i class="fas fa-sticky-note"></i> Ghi Chú</h5>
                            <div class="mb-3">
                                <label class="form-label">Tin Nhắn cho Chủ Nhà</label>
                                <textarea name="note" class="form-control" rows="4" placeholder="Ghi chú thêm về yêu cầu của bạn..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check-circle"></i> Xác Nhận Đặt Phòng
                        </button>
                    </form>
                </div>
            </div>

            <!-- Thông tin người đặt -->
            <div class="col-lg-4">
                <div class="form-card">
                    <h5 style="font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 15px;">
                        <i class="fas fa-user"></i> Thông Tin Người Đặt
                    </h5>
                    <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                        <div style="margin-bottom: 15px;">
                            <div style="color: #666; font-size: 13px;">Tên</div>
                            <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($user_name); ?></div>
                        </div>
                        <div>
                            <div style="color: #666; font-size: 13px;">Email</div>
                            <div style="font-weight: 600; color: #333;"><?php echo htmlspecialchars($_SESSION['email'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <h5 style="font-weight: 700; margin-bottom: 20px; margin-top: 30px; border-bottom: 2px solid #667eea; padding-bottom: 15px;">
                        <i class="fas fa-info-circle"></i> Quy Định
                    </h5>
                    <ul style="color: #666; font-size: 13px; line-height: 1.8;">
                        <li>Chủ nhà sẽ xác nhận đơn đặt trong vòng 24 giờ</li>
                        <li>Tiền đặt cọc có thể hoàn lại nếu hủy trước 7 ngày</li>
                        <li>Cần có hợp đồng trước khi nhận phòng</li>
                        <li>Tuân thủ nội quy chung cư/nhà trọ</li>
                    </ul>

                    <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; border-radius: 6px; margin-top: 20px;">
                        <div style="color: #1976d2; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-shield-alt"></i> An Toàn
                        </div>
                        <div style="color: #666; font-size: 13px;">
                            Các giao dịch được bảo vệ. Chúng tôi sẽ giúp nếu có tranh chấp.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
