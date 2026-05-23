<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';
@require_once '../../core/NotificationHelper.php';
@require_once '../../core/PathHelper.php';
@require_once '../../core/Csrf.php';
@require_once '../components/PublicNav.php';

session_start();

/** @var mysqli $conn */
$db = new Database($conn);
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? (int)$_SESSION['user_id'] : 0;
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
$is_renter = $is_logged_in && $user_role === 'user';
$motel_id = (int)($_GET['id'] ?? 0);

// Get motel details
$stmt = $db->prepare("
    SELECT m.*, c.name as category_name, d.name as district_name, u.name as owner_name, u.verified_at, u.trust_score
    FROM motels m
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN districts d ON m.district_id = d.id
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.id = ? AND m.status = 'approved'
");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$motel) {
    header('Location: search.php');
    exit;
}

$stmt = $db->prepare('SELECT image_url FROM motel_images WHERE motel_id = ? ORDER BY id ASC');
$stmt->bind_param('i', $motel_id);
$stmt->execute();
$motel_images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$message = '';
$message_type = 'success';

if (!$is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: ../login.php?redirect=' . urlencode('user/motel-detail.php?id=' . $motel_id));
    exit;
}

if ($is_renter && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_viewing'])) {
    $preferred_time = $_POST['preferred_time'] ?? '';
    $note = trim($_POST['note'] ?? '');
    $preferredTs = $preferred_time !== '' ? strtotime($preferred_time) : false;
    if (!Csrf::validateRequest('schedule_viewing')) {
        $message = 'Phiên gửi lịch xem đã hết hạn. Vui lòng thử lại.';
        $message_type = 'danger';
    } elseif ($preferred_time === '' || $preferredTs === false) {
        $message = 'Vui lòng chọn thời gian xem phòng.';
        $message_type = 'danger';
    } elseif ($preferredTs < time() + 1800) {
        $message = 'Vui lòng chọn thời gian xem phòng sau thời điểm hiện tại ít nhất 30 phút.';
        $message_type = 'danger';
    } elseif ((int)$motel['user_id'] === $user_id) {
        $message = 'Bạn không thể đặt lịch xem phòng của chính mình.';
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare("
            INSERT INTO viewing_appointments (user_id, motel_id, owner_id, preferred_time, note, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $ownerId = (int)$motel['user_id'];
        $stmt->bind_param("iiiss", $user_id, $motel_id, $ownerId, $preferred_time, $note);
        if ($stmt->execute()) {
            $message = 'Đã gửi lịch xem phòng. Chủ phòng sẽ xác nhận lại với bạn.';
            $notifyTitle = 'Có lịch xem phòng mới';
            $notifyBody = 'Người thuê ' . ($_SESSION['name'] ?? 'User') . ' muốn xem phòng: ' . $motel['title'];
            $notifyLink = 'owner/viewing-appointments.php';
            qlpt_send_notification($db, $ownerId, 'viewing_request', $notifyTitle, $notifyBody, $notifyLink);
        } else {
            $message = 'Không thể gửi lịch xem phòng lúc này.';
            $message_type = 'danger';
        }
        $stmt->close();
    }
}



if ($is_renter && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_owner'])) {
    $body = trim((string)($_POST['contact_message'] ?? ''));
    $ownerId = (int)($motel['user_id'] ?? 0);
    if ($ownerId <= 0 || $ownerId === (int)$user_id) {
        $message = 'Không thể gửi tin nhắn.';
        $message_type = 'danger';
    } elseif (mb_strlen($body) < 5) {
        $message = 'Nội dung tin nhắn cần ít nhất 5 ký tự.';
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare('SELECT id FROM conversations WHERE user_id = ? AND owner_id = ? AND motel_id = ?');
        $stmt->bind_param('iii', $user_id, $ownerId, $motel_id);
        $stmt->execute();
        $conv = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($conv) {
            $convId = (int)$conv['id'];
        } else {
            $stmt = $db->prepare('INSERT INTO conversations (user_id, owner_id, motel_id, last_message_at) VALUES (?, ?, ?, NOW())');
            $stmt->bind_param('iii', $user_id, $ownerId, $motel_id);
            if (!$stmt->execute()) {
                $message = 'Không thể tạo cuộc trò chuyện.';
                $message_type = 'danger';
                $convId = 0;
            } else {
                $convId = (int)$db->getConnection()->insert_id;
            }
            $stmt->close();
        }
        if (!empty($convId)) {
            $stmtMsg = $db->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
            $stmtMsg->bind_param('iis', $convId, $user_id, $body);
            if ($stmtMsg->execute()) {
                $stmtMsg->close();
                $stmtUp = $db->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?');
                $stmtUp->bind_param('i', $convId);
                $stmtUp->execute();
                $stmtUp->close();
                $link = 'owner/messages.php?conversation_id=' . $convId;
                qlpt_send_notification(
                    $db,
                    $ownerId,
                    'tenant_message',
                    'Tin nhắn mới từ người thuê',
                    'Có tin nhắn về phòng: ' . ($motel['title'] ?? '') . '.',
                    $link
                );
                $message = 'Đã gửi tin nhắn cho chủ trọ.';
                $message_type = 'success';
            } else {
                $message = 'Không thể gửi tin nhắn.';
                $message_type = 'danger';
                $stmtMsg->close();
            }
        }
    }
}

// Increment view count with anti-spam
if (!isset($_SESSION['viewed_motels'])) {
    $_SESSION['viewed_motels'] = [];
}

if (!in_array($motel_id, $_SESSION['viewed_motels'])) {
    $stmt = $db->prepare("UPDATE motels SET count_view = count_view + 1 WHERE id = ?");
    $stmt->bind_param("i", $motel_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['viewed_motels'][] = $motel_id;
}

// Check if favorite
$is_favorite = false;
if ($is_renter) {
    $stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND motel_id = ?");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $stmt->execute();
    $is_favorite = $stmt->get_result()->fetch_assoc() ? true : false;
    $stmt->close();
}

// Get reviews
$stmt = $db->prepare("
    SELECT r.*, u.name as reviewer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.motel_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$has_user_review = false;
$can_write_review = false;
if ($is_renter) {
    $stmt = $db->prepare('SELECT id FROM reviews WHERE user_id = ? AND motel_id = ?');
    $stmt->bind_param('ii', $user_id, $motel_id);
    $stmt->execute();
    $has_user_review = (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
    $stmt = $db->prepare("SELECT id FROM bookings WHERE user_id = ? AND motel_id = ? AND status IN ('accepted','paid','completed') LIMIT 1");
    $stmt->bind_param('ii', $user_id, $motel_id);
    $stmt->execute();
    $can_write_review = !$has_user_review && (bool)$stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$utilities_array = array_filter(explode(',', $motel['utilities']));
$service_fee = (int)($motel['service_fee'] ?? 0);
$deposit_months = (float)($motel['deposit_months'] ?? 1);
$deposit_amount = (int)round((int)$motel['price'] * $deposit_months);
$move_in_total = (int)$motel['price'] + $service_fee + $deposit_amount;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($motel['title']); ?> - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
    body {
        background: #f6f8fb;
        color: #172033;
        font-family: system-ui, -apple-system, sans-serif;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #475467;
        text-decoration: none;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 12px;
        background: #fff;
        border: 1px solid #e5eaf2;
        margin-bottom: 24px;
        transition: all 0.2s;
    }

    .btn-back:hover {
        background: #f8fafc;
        color: #101828;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }

    /* Tối ưu Carousel Ảnh */
    .carousel-inner {
        border-radius: 24px;
        overflow: hidden;
        height: 500px;
        background: #e5eaf2;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
        margin-bottom: 32px;
    }

    .carousel-item img {
        width: 100%;
        height: 500px;
        object-fit: cover;
    }

    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        background-color: rgba(16, 24, 40, 0.5);
        border-radius: 50%;
        padding: 20px;
    }

    /* Card Thông tin */
    .detail-card {
        background: #fff;
        border-radius: 20px;
        padding: 32px;
        border: 1px solid #e5eaf2;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.04);
        margin-bottom: 24px;
    }

    .detail-card h5 {
        font-weight: 900;
        font-size: 20px;
        color: #101828;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .detail-card h5 i {
        color: #0e7490;
    }

    .motel-title {
        font-size: 32px;
        font-weight: 950;
        color: #101828;
        line-height: 1.3;
        margin-bottom: 12px;
    }

    .motel-location {
        color: #475467;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .motel-price-box {
        background: #ecfeff;
        padding: 16px 24px;
        border-radius: 16px;
        border: 1px solid #cffafe;
        display: inline-block;
        margin-top: 20px;
    }

    .motel-price {
        color: #0e7490;
        font-size: 28px;
        font-weight: 950;
    }

    /* Grid Thông số & Phí */
    .specs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 16px;
    }

    .spec-item {
        padding: 16px;
        background: #f8fafc;
        border-radius: 14px;
        border: 1px solid #f1f5f9;
    }

    .spec-label {
        color: #667085;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .spec-value {
        color: #101828;
        font-size: 18px;
        font-weight: 900;
    }

    .fee-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px dashed #e5eaf2;
    }

    .fee-item:last-child {
        border-bottom: none;
    }

    .fee-item strong {
        color: #0f766e;
    }

    /* Tiện ích */
    .utility-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .utility-tag {
        padding: 10px 16px;
        border-radius: 999px;
        background: #f8fafc;
        border: 1px solid #e5eaf2;
        color: #344054;
        font-weight: 700;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    /* Map */
    #motelMap {
        height: 400px;
        border-radius: 16px;
        z-index: 1;
    }

    /* Nút & Layout bên phải */
    .btn-modern {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-weight: 800;
        padding: 14px 24px;
        border-radius: 14px;
        transition: all 0.3s;
        border: none;
    }

    .btn-primary-modern {
        background: #101828;
        color: #fff;
    }

    .btn-outline-modern {
        background: #fff;
        color: #101828;
        border: 2px solid #e5eaf2;
    }

    .btn-heart-modern {
        background: #fff;
        color: #ef4444;
        border: 2px solid #fee2e2;
    }

    .btn-heart-modern.active {
        background: #ef4444;
        color: #fff;
    }

    .booking-card {
        position: sticky;
        top: 100px;
        background: #fff;
        border-radius: 20px;
        padding: 32px;
        border: 1px solid #e5eaf2;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
    }

    .cost-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px dashed #e5eaf2;
        color: #475467;
        font-size: 15px;
    }

    .cost-row strong {
        color: #101828;
        font-weight: 800;
    }

    .cost-total {
        font-size: 24px;
        font-weight: 950;
        color: #0e7490;
    }

    .owner-box {
        background: #f8fafc;
        border-radius: 16px;
        padding: 20px;
        margin-top: 24px;
        border: 1px solid #e5eaf2;
    }

    .verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #ecfeff;
        color: #0e7490;
        font-weight: 800;
        font-size: 12px;
    }

    @media (max-width: 991px) {
        .carousel-inner {
            height: 300px;
        }

        .carousel-item img {
            height: 300px;
        }
    }
    </style>
</head>

<body>
    <?php qlpt_render_public_nav(['base' => '../', 'active' => 'rooms']); ?>

    <div class="container-lg" style="padding: 100px 0 60px;">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" style="border-radius: 16px;">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <a href="search.php" class="btn-back"><i class="fas fa-arrow-left"></i> Quay lại </a>

        <div id="motelImageSlider" class="carousel slide" data-bs-ride="carousel">
            <?php if (count($motel_images) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($motel_images as $index => $image): ?>
                <button type="button" data-bs-target="#motelImageSlider" data-bs-slide-to="<?php echo $index; ?>"
                    class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="carousel-inner">
                <?php if (!empty($motel_images)): ?>
                <?php foreach ($motel_images as $index => $image): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                    <img src="<?php echo htmlspecialchars(qlpt_public_asset_url($image['image_url'])); ?>"
                        alt="Hình ảnh phòng trọ">
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="carousel-item active d-flex align-items-center justify-content-center bg-light"
                    style="height: 100%;">
                    <i class="fas fa-image fa-4x text-muted"></i>
                </div>
                <?php endif; ?>
            </div>

            <?php if (count($motel_images) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#motelImageSlider" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#motelImageSlider" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </button>
            <?php endif; ?>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="detail-card">
                    <h1 class="motel-title">
                        <?php if (!empty($motel['is_flash_sale'])): ?>
                            <span class="badge bg-danger align-middle me-2 fs-6"><i class="fas fa-fire"></i> Flash Sale</span>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($motel['title']); ?>
                    </h1>
                    <div class="motel-location mb-3">
                        <i class="fas fa-location-dot" style="color: #ef4444;"></i>
                        <?php echo htmlspecialchars($motel['address']); ?>
                    </div>
                    
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="motel-price-box m-0">
                            <div class="motel-price"><?php echo number_format($motel['price']); ?> <small>VNĐ/tháng</small></div>
                        </div>
                        <?php if (!empty($motel['old_price']) && $motel['old_price'] > $motel['price']): ?>
                            <div class="text-decoration-line-through text-muted fs-4 fw-bold ms-3">
                                <?php echo number_format($motel['old_price']); ?> VNĐ
                            </div>
                            <div class="badge bg-success fs-6 ms-2 pb-1">
                                <i class="fas fa-arrow-down"></i> Giảm <?php echo number_format($motel['old_price'] - $motel['price']); ?> đ
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="detail-card">
                    <h5><i class="fas fa-layer-group"></i> Tổng quan</h5>
                    <div class="specs-grid">
                        <div class="spec-item">
                            <div class="spec-label">Diện tích</div>
                            <div class="spec-value"><?php echo (int)$motel['area']; ?> m²</div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Sức chứa tối đa</div>
                            <div class="spec-value">
                                <?php echo !empty($motel['max_people']) ? $motel['max_people'] . ' người' : 'Không giới hạn'; ?>
                            </div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Phòng ngủ / Tắm</div>
                            <div class="spec-value"><?php echo $motel['bedrooms']; ?> /
                                <?php echo $motel['bathrooms']; ?></div>
                        </div>
                        <div class="spec-item">
                            <div class="spec-label">Ngày trống phòng</div>
                            <div class="spec-value text-success">
                                <i class="fas fa-calendar-check"></i>
                                <?php echo !empty($motel['available_from']) ? date('d/m/Y', strtotime($motel['available_from'])) : 'Vào ở ngay'; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <h5><i class="fas fa-file-invoice-dollar"></i> Chi tiết các loại phí</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="fee-item"><span>Điện:</span>
                                <strong><?php echo number_format($motel['electricity_unit_price']); ?> đ/kWh</strong>
                            </div>
                            <div class="fee-item"><span>Nước:</span>
                                <strong><?php echo number_format($motel['water_fee_per_person']); ?>
                                    đ/người/tháng</strong>
                            </div>
                            <div class="fee-item"><span>Internet:</span>
                                <strong><?php echo $motel['internet_fee'] > 0 ? number_format($motel['internet_fee']) . ' đ/tháng' : 'Miễn phí'; ?></strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="fee-item"><span>Gửi xe:</span>
                                <strong><?php echo $motel['parking_fee'] > 0 ? number_format($motel['parking_fee']) . ' đ/xe' : 'Miễn phí'; ?></strong>
                            </div>
                            <div class="fee-item"><span>Phí dịch vụ chung:</span>
                                <strong><?php echo number_format($motel['service_fee']); ?> đ/tháng</strong>
                            </div>
                            <div class="fee-item"><span>Phí khác:</span>
                                <strong><?php echo $motel['other_fee'] > 0 ? number_format($motel['other_fee']) . ' đ' : 'Không có'; ?></strong>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($motel['service_note'])): ?>
                    <div class="mt-3 p-3 bg-light rounded text-muted small"><i class="fas fa-info-circle"></i> Ghi chú
                        phí: <?php echo htmlspecialchars($motel['service_note']); ?></div>
                    <?php endif; ?>
                </div>

                <?php if (count($utilities_array) > 0): ?>
                <div class="detail-card">
                    <h5><i class="fas fa-sparkles"></i> Tiện nghi có sẵn</h5>
                    <div class="utility-tags">
                        <?php foreach ($utilities_array as $util): ?>
                        <span class="utility-tag"><i class="fas fa-check-circle"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $util)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="detail-card">
                    <h5><i class="fas fa-align-left"></i> Mô tả chi tiết</h5>
                    <div style="line-height: 1.8; color: #475467; font-size: 16px;">
                        <?php echo nl2br(htmlspecialchars($motel['description'])); ?>
                    </div>
                </div>

                <?php if (!empty($motel['lat']) && !empty($motel['lng'])): ?>
                <div class="detail-card">
                    <h5><i class="fas fa-map"></i> Vị trí trên bản đồ</h5>
                    <div id="motelMap"></div>
                </div>
                <?php endif; ?>

                <div class="detail-card">
                    <h5><i class="fas fa-star text-warning"></i> Đánh giá từ người thuê</h5>
                    <div id="reviews-list">
                        <?php if (empty($reviews)): ?>
                            <p class="text-muted">Chưa có đánh giá nào.</p>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div class="border-bottom py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong><?php echo htmlspecialchars($rev['reviewer_name']); ?></strong>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++) { echo $i <= $rev['rating'] ? '★' : '☆'; } ?>
                                        </span>
                                    </div>
                                    <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($rev['created_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($can_write_review): ?>
                        <div class="mt-4 pt-4 border-top">
                            <h6>Viết đánh giá của bạn</h6>
                            <form id="reviewForm" onsubmit="submitReview(event)">
                                <input type="hidden" id="reviewMotelId" value="<?php echo $motel_id; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Chất lượng phòng (1-5 sao)</label>
                                    <select class="form-select" id="reviewRating" required>
                                        <option value="">Chọn số sao...</option>
                                        <option value="5">5 sao - Rất tuyệt vời</option>
                                        <option value="4">4 sao - Tốt</option>
                                        <option value="3">3 sao - Tạm được</option>
                                        <option value="2">2 sao - Kém</option>
                                        <option value="1">1 sao - Quá tệ</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Bình luận</label>
                                    <textarea class="form-control" id="reviewComment" rows="3" minlength="5" required placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btnSubmitReview">Gửi đánh giá</button>
                            </form>
                        </div>
                    <?php elseif ($has_user_review): ?>
                        <div class="alert alert-success mt-4">Bạn đã đánh giá phòng này. Cảm ơn phản hồi của bạn!</div>
                    <?php elseif ($is_renter): ?>
                        <div class="alert alert-info mt-4">Chỉ có thể đánh giá sau khi bạn thuê phòng này (Đơn đặt phòng đã được chấp nhận).</div>
                    <?php endif; ?>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="booking-card">
                    <h4 class="fw-bold mb-4">Dự tính chi phí tháng đầu</h4>
                    <div class="mb-4">
                        <div class="cost-row"><span>Tiền thuê (tháng đầu)</span>
                            <div class="text-end">
                                <?php if (!empty($motel['old_price']) && $motel['old_price'] > $motel['price']): ?>
                                    <div class="text-muted text-decoration-line-through small"><?php echo number_format((int)$motel['old_price']); ?> đ</div>
                                <?php endif; ?>
                                <strong><?php echo number_format((int)$motel['price']); ?> đ</strong>
                            </div>
                        </div>
                        <div class="cost-row"><span>Tiền cọc (<?php echo $deposit_months; ?>
                                tháng)</span><strong><?php echo number_format($deposit_amount); ?> đ</strong></div>
                        <div class="cost-row"><span>Phí dịch vụ chung</span><strong><?php echo number_format($service_fee); ?> đ</strong></div>
                        
                        <div class="d-flex justify-content-between align-items-center pt-4">
                            <span class="fw-bold text-dark">Tạm tính (chưa Voucher)</span>
                            <span class="cost-total text-danger"><?php echo number_format($move_in_total); ?> đ</span>
                        </div>
                        <div class="small text-muted mt-2 text-end"><i class="fas fa-info-circle"></i> Có thể dùng Voucher ở bước Thanh toán</div>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <?php if ($is_renter): ?>
                        <a href="checkout.php?id=<?php echo $motel['id']; ?>"
                            class="btn-modern btn-primary-modern w-100 text-decoration-none">Gửi yêu cầu Booking</a>
                        <div class="d-flex gap-2">
                            <button class="btn-modern btn-outline-modern flex-grow-1" type="button"
                                data-bs-toggle="collapse" data-bs-target="#viewingForm"><i class="fas fa-calendar"></i>
                                Đặt lịch xem</button>
                            <button class="btn-modern btn-heart-modern <?php echo $is_favorite ? 'active' : ''; ?>"
                                onclick="toggleFavorite(<?php echo $motel['id']; ?>, this)"><i
                                    class="fas fa-heart"></i></button>
                        </div>
                        <div class="collapse mt-3" id="viewingForm">
                            <form method="POST" class="p-3 rounded-4 border bg-light">
                                <?php echo Csrf::field('schedule_viewing'); ?>
                                <input type="hidden" name="schedule_viewing" value="1">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Thời gian muốn xem phòng</label>
                                    <input type="datetime-local" name="preferred_time" class="form-control"
                                        min="<?php echo date('Y-m-d\TH:i', time() + 1800); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ghi chú cho chủ phòng</label>
                                    <textarea name="note" class="form-control" rows="3"
                                        placeholder="Ví dụ: Em muốn xem phòng sau giờ hành chính."></textarea>
                                </div>
                                <button type="submit" class="btn-modern btn-primary-modern w-100">
                                    <i class="fas fa-paper-plane"></i> Gửi lịch xem
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <?php if (!$is_logged_in): ?>
                        <a href="../login.php?redirect=<?php echo urlencode('user/motel-detail.php?id=' . (int)$motel['id']); ?>" class="btn-modern btn-primary-modern w-100 text-decoration-none">Đăng
                            nhập để thuê</a>
                        <?php else: ?>
                        <div class="alert alert-secondary text-center small rounded-4">Tính năng đặt phòng chỉ dành cho
                            Người thuê.</div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <div class="owner-box">
                        <div class="text-muted fw-bold small mb-2 text-uppercase tracking-wide">Chủ phòng / Người quản
                            lý</div>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div
                                style="width: 48px; height: 48px; border-radius: 50%; background: #0e7490; color: white; display: grid; place-items: center; font-size: 20px; font-weight: bold;">
                                <?php echo strtoupper(substr($motel['owner_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="fw-black fs-5 text-dark">
                                    <?php echo htmlspecialchars($motel['owner_name']); ?></div>
                                <?php if (!empty($motel['verified_at'])): ?>
                                <div class="verified-badge mt-1"><i class="fas fa-check-circle"></i> Đã xác minh CCCD
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <a href="owner-profile.php?id=<?php echo $motel['user_id']; ?>"
                            class="btn-modern btn-outline-modern w-100 py-2 fs-6 text-decoration-none">
                            <i class="fas fa-external-link-alt"></i> Xem hồ sơ chủ trọ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    function submitReview(e) {
        e.preventDefault();
        const motelId = document.getElementById('reviewMotelId').value;
        const rating = document.getElementById('reviewRating').value;
        const comment = document.getElementById('reviewComment').value;
        const btn = document.getElementById('btnSubmitReview');
        
        btn.disabled = true;
        btn.innerHTML = 'Đang gửi...';

        fetch('../ajax/submit-review.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ motel_id: motelId, rating: rating, comment: comment })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const list = document.getElementById('reviews-list');
                const emptyMsg = list.querySelector('.text-muted');
                if (emptyMsg && emptyMsg.innerText === 'Chưa có đánh giá nào.') {
                    emptyMsg.remove();
                }
                list.innerHTML = data.html + list.innerHTML;
                document.getElementById('reviewForm').parentElement.innerHTML = '<div class="alert alert-success mt-4">Bạn đã đánh giá phòng này. Cảm ơn phản hồi của bạn!</div>';
            } else {
                alert(data.message || 'Lỗi khi gửi đánh giá.');
                btn.disabled = false;
                btn.innerHTML = 'Gửi đánh giá';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi kết nối.');
            btn.disabled = false;
            btn.innerHTML = 'Gửi đánh giá';
        });
    }

    function toggleFavorite(motelId, btn) {
        fetch('../ajax/toggle-favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ motel_id: motelId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success !== undefined) {
                btn.classList.toggle('active');
            } else if (data.message === 'Not authenticated') {
                window.location.href = '../login.php';
            } else {
                alert('Có lỗi xảy ra, vui lòng thử lại.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi kết nối.');
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($motel['lat']) && !empty($motel['lng'])): ?>
        var lat = <?php echo $motel['lat']; ?>;
        var lng = <?php echo $motel['lng']; ?>;
        var map = L.map('motelMap').setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map)
            .bindPopup(
                '<b><?php echo htmlspecialchars($motel['title'], ENT_QUOTES); ?></b><br>Khu vực phòng trọ.')
            .openPopup();
        <?php endif; ?>
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
