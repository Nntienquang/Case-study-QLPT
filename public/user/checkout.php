<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../../core/NotificationHelper.php';
@require_once '../../core/Csrf.php';
@require_once '../components/PublicNav.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['name'];
$motel_id = (int)($_GET['id'] ?? 0);

// Get motel details
$stmt = $db->prepare("SELECT id, title, price, deposit_amount, service_fee, electricity_unit_price, water_fee_per_person, internet_fee, parking_fee, other_fee, address, user_id, room_status FROM motels WHERE id = ? AND status = 'approved'");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$motel) {
    header('Location: search.php');
    exit;
}

$stmt = $db->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$message = '';
$message_type = '';

// Lấy danh sách Voucher khả dụng
$stmt = $db->prepare("SELECT * FROM vouchers WHERE valid_until > NOW() AND used_count < usage_limit");
$stmt->execute();
$vouchers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Csrf::validateRequest('booking_checkout')) {
    $message = 'Phiên đặt phòng đã hết hạn. Vui lòng thử lại.';
    $message_type = 'danger';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $check_in = $_POST['check_in_date'] ?? '';
    $durationMonths = max(1, (int)($_POST['rental_duration_months'] ?? 1));
    $durationMonths = min(36, $durationMonths);
    $check_out = null;
    if ($check_in !== '') {
        $startDate = new DateTimeImmutable($check_in);
        $check_out = $startDate->modify('+' . $durationMonths . ' months')->format('Y-m-d');
    }
    $deposit = max(1, (int)($_POST['deposit_amount'] ?? ($motel['deposit_amount'] ?: $motel['price'])));
    $monthlyBase = (int)$motel['price'] + (int)$motel['service_fee'] + (int)$motel['internet_fee'] + (int)$motel['parking_fee'] + (int)$motel['other_fee'];
    $estimatedTotal = $deposit + ($monthlyBase * $durationMonths);
    $note = trim((string)($_POST['note'] ?? ''));
    $contactName = trim((string)($_POST['contact_name'] ?? ($currentUser['name'] ?? $user_name)));
    $contactPhone = preg_replace('/\s+/', '', trim((string)($_POST['contact_phone'] ?? ($currentUser['phone'] ?? ''))));
    $contactEmail = trim((string)($_POST['contact_email'] ?? ($currentUser['email'] ?? '')));
    $ownerId = (int)($motel['user_id'] ?? 0);

    if ($check_in === '') {
        $message = 'Vui lòng chọn ngày dự kiến vào ở!';
        $message_type = 'danger';
    } elseif ($contactName === '' || $contactPhone === '' || $contactEmail === '') {
        $message = 'Vui lòng nhập đủ thông tin liên hệ.';
        $message_type = 'danger';
    } elseif (!preg_match('/^[0-9]{9,11}$/', $contactPhone)) {
        $message = 'Số điện thoại liên hệ không hợp lệ.';
        $message_type = 'danger';
    } else {
        $conn->begin_transaction();
        try {
            $lock = $conn->prepare("
                SELECT id FROM booking_room_holds
                WHERE motel_id = ? AND hold_status = 'active' AND expires_at > NOW()
                LIMIT 1
                FOR UPDATE
            ");
            $lock->bind_param('i', $motel_id);
            $lock->execute();
            $activeHold = $lock->get_result()->fetch_assoc();
            $lock->close();

            $reserved = $conn->prepare("
                SELECT id FROM bookings
                WHERE motel_id = ? AND booking_status IN ('waiting_payment','paid','confirmed','completed')
                  AND payment_status IN ('pending','processing','paid')
                  AND (expires_at IS NULL OR expires_at > NOW() OR payment_status = 'paid')
                LIMIT 1
                FOR UPDATE
            ");
            $reserved->bind_param('i', $motel_id);
            $reserved->execute();
            $activeBooking = $reserved->get_result()->fetch_assoc();
            $reserved->close();

            if ($activeHold || $activeBooking || ($motel['room_status'] ?? 'available') !== 'available') {
                throw new RuntimeException('Phòng này đang được giữ chỗ hoặc không còn khả dụng. Vui lòng chọn phòng khác.');
            }

            $voucher_id = !empty($_POST['voucher_id']) ? (int)$_POST['voucher_id'] : null;
            $discount_applied = 0;
            $finalTotal = $estimatedTotal;

            if ($voucher_id) {
                $vStmt = $conn->prepare("SELECT * FROM vouchers WHERE id = ? AND valid_until > NOW() AND used_count < usage_limit FOR UPDATE");
                $vStmt->bind_param("i", $voucher_id);
                $vStmt->execute();
                $voucher = $vStmt->get_result()->fetch_assoc();
                $vStmt->close();

                if (!$voucher) {
                    throw new RuntimeException('Voucher không hợp lệ hoặc đã hết lượt sử dụng.');
                }
                if ($estimatedTotal < $voucher['min_spend']) {
                    throw new RuntimeException('Đơn hàng chưa đạt mức tối thiểu để sử dụng Voucher này.');
                }

                if ($voucher['discount_percent'] > 0) {
                    $discount_applied = (int)($estimatedTotal * $voucher['discount_percent'] / 100);
                } else {
                    $discount_applied = (int)$voucher['discount_amount'];
                }
                
                $finalTotal = $estimatedTotal - $discount_applied;
                if ($finalTotal < 0) $finalTotal = 0;
                
                if ($deposit > $finalTotal) {
                    $deposit = $finalTotal;
                }
            }

            $bookingCode = 'BK' . date('ymdHis') . random_int(10, 99);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            $legacyStatus = 'pending';
            $paymentStatus = 'pending';
            $bookingStatus = 'waiting_payment';

            $stmt = $conn->prepare("
                INSERT INTO bookings
                    (booking_code, user_id, owner_id, motel_id, check_in_date, check_out_date, expected_move_in_date,
                     rental_duration_months, deposit_amount, total_amount, voucher_id, discount_applied, final_amount, 
                     payment_status, booking_status, note, contact_name, contact_phone, contact_email, checkin_date, status, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param(
                "siiisssiiiiissssssssss",
                $bookingCode,
                $user_id,
                $ownerId,
                $motel_id,
                $check_in,
                $check_out,
                $check_in,
                $durationMonths,
                $deposit,
                $estimatedTotal,
                $voucher_id,
                $discount_applied,
                $finalTotal,
                $paymentStatus,
                $bookingStatus,
                $note,
                $contactName,
                $contactPhone,
                $contactEmail,
                $check_in,
                $legacyStatus,
                $expiresAt
            );
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $bookingId = (int)$stmt->insert_id;
            $stmt->close();
            
            if ($voucher_id) {
                $vUp = $conn->prepare("UPDATE vouchers SET used_count = used_count + 1 WHERE id = ?");
                $vUp->bind_param("i", $voucher_id);
                $vUp->execute();
                $vUp->close();
            }

            $paymentCode = 'PAY' . date('ymdHis') . random_int(10, 99);
            $method = 'bank_transfer';
            $paymentStatus = 'pending';
            $legacyPaymentStatus = 'pending';
            $stmt = $conn->prepare("
                INSERT INTO payments
                    (payment_code, booking_id, amount, fee, payment_method, payment_status, method, status, created_at)
                VALUES (?, ?, ?, 0, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("siissss", $paymentCode, $bookingId, $deposit, $method, $paymentStatus, $method, $legacyPaymentStatus);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            $holdStatus = 'active';
            $stmt = $conn->prepare("INSERT INTO booking_room_holds (booking_id, motel_id, user_id, hold_status, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $bookingId, $motel_id, $user_id, $holdStatus, $expiresAt);
            if (!$stmt->execute()) {
                throw new RuntimeException($stmt->error);
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE motels SET room_status = 'reserved' WHERE id = ?");
            $stmt->bind_param('i', $motel_id);
            $stmt->execute();
            $stmt->close();

            if ($ownerId > 0) {
                $tenantLabel = htmlspecialchars((string)($user_name ?? 'Người thuê'), ENT_QUOTES, 'UTF-8');
                $titleVi = htmlspecialchars((string)$motel['title'], ENT_QUOTES, 'UTF-8');
                qlpt_send_notification(
                    $db,
                    $ownerId,
                    'booking_request',
                    'Có yêu cầu đặt phòng / đặt cọc mới',
                    $tenantLabel . ' vừa gửi đơn cho phòng: ' . $titleVi . '. Hãy mở Đơn đặt phòng để duyệt hoặc từ chối.',
                    'owner/bookings.php'
                );
            }

            $conn->commit();
            header('Location: payment.php?booking_id=' . $bookingId);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $message = $e->getMessage();
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 124px 0 30px; }
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
        .price-item.total { border-top: 1px solid #dee2e6; padding-top: 12px; margin-top: 12px; font-weight: 800; color: #111827; font-size: 18px; }
        .readonly-date { background: #f8fafc; }
        .alert { border-radius: 12px; }
        @media (max-width: 768px) {
            .main-content { padding-top: 112px; }
            .form-card { padding: 24px; }
        }
    </style>
    <link href="../assets/css/modern.css" rel="stylesheet">
</head>
<body>
    <?php qlpt_render_public_nav(['base' => '../', 'active' => 'rooms']); ?>
    <?php /*
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
        </div>
    </nav>
    */ ?>

    <div class="container-lg main-content">
        <a href="motel-detail.php?id=<?php echo $motel_id; ?>" style="color: #667eea; text-decoration: none; margin-bottom: 20px; display: inline-block;">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>

        <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-calendar-plus"></i> Đặt phòng
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
                        <?php echo Csrf::field('booking_checkout'); ?>
                        <!-- Thông tin phòng -->
                        <div class="motel-summary">
                            <h3><i class="fas fa-home"></i> <?php echo htmlspecialchars($motel['title']); ?></h3>
                            <div class="summary-item">
                                <span class="summary-item-label"><i class="fas fa-map-marker-alt"></i> Địa chỉ</span>
                                <span class="summary-item-value"><?php echo htmlspecialchars($motel['address']); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-item-label"><i class="fas fa-tag"></i> Giá/tháng</span>
                                <span class="summary-item-value"><?php echo number_format((int)$motel['price']); ?> VNĐ</span>
                            </div>
                        </div>

                        <!-- Ngày đặt phòng -->
                        <div class="form-section">
                            <h5><i class="fas fa-calendar"></i> Ngày đặt phòng</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày dự kiến vào ở *</label>
                                    <input type="date" id="checkInDate" name="check_in_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày kết thúc dự kiến</label>
                                    <input type="date" id="checkOutDate" name="check_out_date" class="form-control readonly-date" readonly>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thời hạn thuê dự kiến</label>
                                <select id="rentalDuration" name="rental_duration_months" class="form-select">
                                    <option value="1">1 tháng</option>
                                    <option value="2">2 tháng</option>
                                    <option value="3">3 tháng</option>
                                    <option value="4">4 tháng</option>
                                    <option value="5">5 tháng</option>
                                    <option value="6">6 tháng</option>
                                    <option value="9">9 tháng</option>
                                    <option value="12">12 tháng</option>
                                    <option value="24">24 tháng</option>
                                </select>
                            </div>
                            <div class="price-breakdown">
                                <div class="price-item"><span>Giá phòng/tháng</span><span id="monthlyRoomPreview"></span></div>
                                <div class="price-item"><span>Phí dịch vụ cố định/tháng</span><span id="monthlyFeePreview"></span></div>
                                <div class="price-item"><span>Tiền phòng dự kiến theo thời hạn</span><span id="rentTotalPreview"></span></div>
                                <div class="price-item"><span>Tiền cọc giữ chỗ</span><span id="depositPreview"></span></div>
                                <div class="price-item text-danger" style="display:none;" id="discountRow"><span>Giảm giá Voucher</span><span id="discountPreview"></span></div>
                                <div class="price-item total"><span>Tổng chi phí dự kiến</span><span id="estimatedTotalPreview"></span></div>
                            </div>
                        </div>

                        <!-- Mã giảm giá (Voucher) -->
                        <?php if (!empty($vouchers)): ?>
                        <div class="form-section">
                            <h5><i class="fas fa-ticket-alt text-warning"></i> Mã giảm giá (Voucher)</h5>
                            <div class="mb-3">
                                <label class="form-label">Chọn Voucher áp dụng</label>
                                <select id="voucherSelect" name="voucher_id" class="form-select border-warning">
                                    <option value="">-- Không sử dụng voucher --</option>
                                    <?php foreach ($vouchers as $v): ?>
                                        <option value="<?php echo $v['id']; ?>" 
                                                data-discount-amount="<?php echo $v['discount_amount']; ?>" 
                                                data-discount-percent="<?php echo $v['discount_percent']; ?>"
                                                data-min-spend="<?php echo $v['min_spend']; ?>">
                                            <?php echo htmlspecialchars($v['code']); ?> - 
                                            <?php if ($v['discount_percent'] > 0) echo $v['discount_percent'] . '%'; ?>
                                            <?php if ($v['discount_amount'] > 0) echo number_format($v['discount_amount']) . 'đ'; ?>
                                            (Đơn tối thiểu: <?php echo number_format($v['min_spend']); ?>đ)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tiền đặt cọc -->
                        <div class="form-section">
                            <h5><i class="fas fa-money-bill-wave"></i> Tiền đặt cọc</h5>
                            <div class="mb-3">
                                <label class="form-label">Số tiền (VNĐ)</label>
                                <input type="number" id="depositAmount" name="deposit_amount" class="form-control" value="<?php echo (int)($motel['deposit_amount'] ?: $motel['price']); ?>" required min="1">
                            </div>
                            <div class="price-breakdown">
                                <div class="price-item">
                                    <span>Giá phòng/tháng:</span>
                                    <span><?php echo number_format((int)$motel['price']); ?> VNĐ</span>
                                </div>
                                <div class="price-item">
                                    <span>Điện:</span>
                                    <span><?php echo number_format((int)$motel['electricity_unit_price']); ?> VNĐ/kWh</span>
                                </div>
                                <div class="price-item">
                                    <span>Nước:</span>
                                    <span><?php echo number_format((int)$motel['water_fee_per_person']); ?> VNĐ/người</span>
                                </div>
                                <div class="price-item">
                                    <span>Internet / giữ xe / khác:</span>
                                    <span><?php echo number_format((int)$motel['internet_fee'] + (int)$motel['parking_fee'] + (int)$motel['other_fee']); ?> VNĐ</span>
                                </div>
                                <div class="price-item">
                                    <span>Tiền giữ chỗ/cọc:</span>
                                    <span id="depositBreakdown"><?php echo number_format((int)($motel['deposit_amount'] ?: $motel['price'])); ?> VNĐ</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5><i class="fas fa-address-card"></i> Thông tin liên hệ</h5>
                            <div class="mb-3">
                                <label class="form-label">Họ tên *</label>
                                <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($currentUser['name'] ?? $user_name); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Số điện thoại *</label>
                                    <input type="tel" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú -->
                        <div class="form-section">
                            <h5><i class="fas fa-sticky-note"></i> Ghi chú</h5>
                            <div class="mb-3">
                                <label class="form-label">Tin nhắn cho chủ nhà</label>
                                <textarea name="note" class="form-control" rows="4" placeholder="Ghi chú thêm về yêu cầu của bạn..."></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-credit-card"></i> Tạo booking và thanh toán cọc
                        </button>
                    </form>
                </div>
            </div>

            <!-- Thông tin người đặt -->
            <div class="col-lg-4">
                <div class="form-card">
                    <h5 style="font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 15px;">
                        <i class="fas fa-user"></i> Thông tin người đặt
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
                        <i class="fas fa-info-circle"></i> Quy định
                    </h5>
                    <ul style="color: #666; font-size: 13px; line-height: 1.8;">
                        <li>Chủ nhà sẽ xác nhận đơn đặt trong vòng 24 giờ</li>
                        <li>Tiền đặt cọc có thể hoàn lại nếu hủy trước 7 ngày</li>
                        <li>Cần có hợp đồng trước khi nhận phòng</li>
                        <li>Tuân thủ nội quy chung cư/nhà trọ</li>
                    </ul>

                    <div style="background: #e3f2fd; border-left: 4px solid #1976d2; padding: 15px; border-radius: 6px; margin-top: 20px;">
                        <div style="color: #1976d2; font-weight: 600; margin-bottom: 8px;">
                            <i class="fas fa-shield-alt"></i> An toàn
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
    <script>
        const roomPrice = <?php echo (int)$motel['price']; ?>;
        const monthlyFixedFee = <?php echo (int)$motel['service_fee'] + (int)$motel['internet_fee'] + (int)$motel['parking_fee'] + (int)$motel['other_fee']; ?>;
        const checkInInput = document.getElementById('checkInDate');
        const checkOutInput = document.getElementById('checkOutDate');
        const durationInput = document.getElementById('rentalDuration');
        const depositInput = document.getElementById('depositAmount');
        const voucherSelect = document.getElementById('voucherSelect');
        const formatter = new Intl.NumberFormat('vi-VN');

        function addMonths(date, months) {
            const next = new Date(date.getTime());
            const day = next.getDate();
            next.setMonth(next.getMonth() + months);
            if (next.getDate() !== day) {
                next.setDate(0);
            }
            return next;
        }

        function toDateInputValue(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function money(value) {
            return `${formatter.format(Math.max(0, value))} VNĐ`;
        }

        function refreshEstimate() {
            const months = Math.max(1, parseInt(durationInput.value || '1', 10));
            let deposit = Math.max(0, parseInt(depositInput.value || '0', 10));
            const monthlyTotal = roomPrice + monthlyFixedFee;
            const rentTotal = monthlyTotal * months;
            const estimatedTotal = rentTotal + deposit;
            
            let discount = 0;
            if (voucherSelect && voucherSelect.value !== '') {
                const opt = voucherSelect.options[voucherSelect.selectedIndex];
                const minSpend = parseInt(opt.getAttribute('data-min-spend') || '0', 10);
                if (estimatedTotal >= minSpend) {
                    const dAmount = parseInt(opt.getAttribute('data-discount-amount') || '0', 10);
                    const dPercent = parseInt(opt.getAttribute('data-discount-percent') || '0', 10);
                    if (dPercent > 0) {
                        discount = Math.floor(estimatedTotal * dPercent / 100);
                    } else {
                        discount = dAmount;
                    }
                } else {
                    voucherSelect.value = '';
                    alert('Đơn hàng không đạt giá trị tối thiểu để áp dụng Voucher này!');
                }
            }
            
            let finalTotal = estimatedTotal - discount;
            if (finalTotal < 0) finalTotal = 0;
            if (deposit > finalTotal) deposit = finalTotal;

            if (checkInInput.value) {
                const start = new Date(`${checkInInput.value}T00:00:00`);
                checkOutInput.value = toDateInputValue(addMonths(start, months));
            } else {
                checkOutInput.value = '';
            }

            document.getElementById('monthlyRoomPreview').textContent = money(roomPrice);
            document.getElementById('monthlyFeePreview').textContent = money(monthlyFixedFee);
            document.getElementById('rentTotalPreview').textContent = money(rentTotal);
            document.getElementById('depositPreview').textContent = money(deposit);
            
            const discountRow = document.getElementById('discountRow');
            if (discount > 0) {
                discountRow.style.display = 'flex';
                document.getElementById('discountPreview').textContent = '-' + money(discount);
            } else {
                discountRow.style.display = 'none';
            }
            
            document.getElementById('estimatedTotalPreview').textContent = money(finalTotal);
            document.getElementById('depositBreakdown').textContent = money(deposit);
        }

        checkInInput.addEventListener('change', refreshEstimate);
        durationInput.addEventListener('change', refreshEstimate);
        depositInput.addEventListener('input', refreshEstimate);
        if (voucherSelect) {
            voucherSelect.addEventListener('change', refreshEstimate);
        }
        refreshEstimate();
    </script>
</body>
</html>
