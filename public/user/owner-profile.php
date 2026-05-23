<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';
@require_once '../../core/PathHelper.php';
@require_once '../components/PublicNav.php';

session_start();

$db = new Database($conn);
$owner_id = (int)($_GET['id'] ?? 0);

if ($owner_id <= 0) {
    header('Location: search.php');
    exit;
}

// 1. Lấy thông tin chủ trọ
$stmt = $db->prepare("SELECT id, name, email, phone, avatar, created_at, verified_at, trust_score FROM users WHERE id = ? AND role IN ('owner', 'admin')");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$owner) {
    header('Location: search.php');
    exit;
}

// 2. Lấy thống kê đánh giá
$stmt = $db->prepare("
    SELECT COUNT(r.id) as total_reviews, AVG(r.rating) as avg_rating
    FROM reviews r
    JOIN motels m ON r.motel_id = m.id
    WHERE m.user_id = ? AND r.status = 'visible'
");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total_reviews = (int)($stats['total_reviews'] ?? 0);
$avg_rating = round((float)($stats['avg_rating'] ?? 0), 1);

// 3. Lấy danh sách phòng của chủ trọ
$stmt = $db->prepare("
    SELECT m.id, m.title, m.price, m.address, m.area, d.name as district_name,
    (SELECT image_url FROM motel_images mi WHERE mi.motel_id = m.id ORDER BY id ASC LIMIT 1) as image_url
    FROM motels m
    LEFT JOIN districts d ON m.district_id = d.id
    WHERE m.user_id = ? AND m.status = 'approved'
    ORDER BY m.created_at DESC
");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 4. Lấy danh sách đánh giá
$stmt = $db->prepare("
    SELECT r.id, r.rating, r.comment, r.created_at, u.name as reviewer_name, m.title as motel_title
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN motels m ON r.motel_id = m.id
    WHERE m.user_id = ? AND r.status = 'visible'
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ chủ trọ: <?php echo htmlspecialchars($owner['name']); ?> - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body {
            background: #f6f8fb;
            color: #172033;
            font-family: 'Segoe UI', system-ui, sans-serif;
            padding-top: 80px;
        }

        .profile-header {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.04);
            border: 1px solid #e5eaf2;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .profile-cover {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 120px;
            background: linear-gradient(135deg, #0e7490 0%, #0891b2 100%);
            z-index: 1;
        }

        .profile-content {
            position: relative;
            z-index: 2;
            margin-top: 40px;
            display: flex;
            gap: 32px;
            align-items: flex-start;
        }

        .profile-avatar {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 6px solid #fff;
            background: #f1f5f9;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.1);
            object-fit: cover;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 800;
            color: #0e7490;
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 900;
            color: #101828;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .verified-badge {
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #ecfeff;
            color: #0e7490;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .trust-score-badge {
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fdf4ff;
            color: #c026d3;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .profile-stats {
            display: flex;
            gap: 32px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px dashed #e5eaf2;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 900;
            color: #101828;
        }

        .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
        }

        /* Card Section */
        .section-card {
            background: #fff;
            border-radius: 20px;
            padding: 32px;
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.04);
            border: 1px solid #e5eaf2;
            margin-bottom: 24px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 900;
            color: #101828;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #0e7490;
        }

        /* Motel Card */
        .motel-card {
            border: 1px solid #e5eaf2;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            display: flex;
            flex-direction: column;
            background: #fff;
            text-decoration: none;
            color: inherit;
        }

        .motel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
            color: inherit;
        }

        .motel-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
            background: #f1f5f9;
        }

        .motel-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .motel-title {
            font-size: 16px;
            font-weight: 800;
            color: #101828;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .motel-price {
            color: #ef4444;
            font-weight: 900;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .motel-address {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 6px;
            flex-grow: 1;
        }

        .motel-footer {
            padding-top: 16px;
            border-top: 1px dashed #e5eaf2;
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #475467;
            font-weight: 600;
        }

        /* Review Item */
        .review-item {
            padding: 20px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .review-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reviewer-name {
            font-weight: 800;
            color: #101828;
            font-size: 15px;
        }

        .review-motel {
            font-size: 12px;
            color: #64748b;
            background: #f8fafc;
            padding: 4px 10px;
            border-radius: 6px;
            margin-left: 8px;
            display: inline-block;
        }

        .review-rating {
            color: #f59e0b;
            font-size: 14px;
        }

        .review-comment {
            color: #475467;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .review-date {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 8px;
        }
        
        @media (max-width: 768px) {
            .profile-content {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 20px;
            }
            .profile-name {
                justify-content: center;
            }
            .profile-info .d-flex {
                justify-content: center;
            }
            .profile-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php qlpt_render_public_nav(['base' => '../', 'active' => '']); ?>

    <main class="container-lg" style="margin-top: 40px; margin-bottom: 60px;">
        <a href="javascript:history.back()" class="btn btn-light rounded-pill mb-4 border fw-bold" style="color: #475467; padding: 10px 20px;">
            <i class="fas fa-arrow-left me-2"></i> Quay lại
        </a>

        <!-- Header Profile -->
        <div class="profile-header">
            <div class="profile-cover"></div>
            <div class="profile-content">
                <?php if (!empty($owner['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars(qlpt_public_asset_url($owner['avatar'])); ?>" alt="Avatar" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar">
                        <?php echo mb_strtoupper(mb_substr($owner['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-info">
                    <h1 class="profile-name">
                        <?php echo htmlspecialchars($owner['name']); ?>
                    </h1>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php if (!empty($owner['verified_at'])): ?>
                            <div class="verified-badge">
                                <i class="fas fa-check-circle"></i> Đã xác minh CCCD
                            </div>
                        <?php endif; ?>
                        
                        <div class="trust-score-badge">
                            <i class="fas fa-shield-alt"></i> Điểm tin cậy: <?php echo (int)($owner['trust_score'] ?? 0); ?>/100
                        </div>
                    </div>

                    <div class="text-muted fw-medium d-flex flex-wrap gap-4">
                        <span><i class="fas fa-calendar-alt me-2"></i> Tham gia: <?php echo date('m/Y', strtotime($owner['created_at'])); ?></span>
                        <?php if (!empty($owner['phone'])): ?>
                        <span><i class="fas fa-phone-alt me-2"></i> <?php echo substr($owner['phone'], 0, 4) . '***' . substr($owner['phone'], -3); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?php echo count($motels); ?></span>
                            <span class="stat-label">Phòng đang cho thuê</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">
                                <?php echo $avg_rating > 0 ? $avg_rating . ' <i class="fas fa-star text-warning" style="font-size:16px;"></i>' : 'N/A'; ?>
                            </span>
                            <span class="stat-label">Đánh giá trung bình</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?php echo $total_reviews; ?></span>
                            <span class="stat-label">Lượt đánh giá</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Cột trái: Danh sách phòng -->
            <div class="col-lg-8">
                <div class="section-card">
                    <h3 class="section-title"><i class="fas fa-building"></i> Phòng trọ của <?php echo htmlspecialchars($owner['name']); ?></h3>
                    
                    <?php if (empty($motels)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-box-open fa-3x mb-3" style="color: #cbd5e1;"></i>
                            <p>Chủ trọ này hiện chưa có phòng nào đang cho thuê.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($motels as $motel): ?>
                                <div class="col-md-6">
                                    <a href="motel-detail.php?id=<?php echo $motel['id']; ?>" class="motel-card">
                                        <?php if (!empty($motel['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars(qlpt_public_asset_url($motel['image_url'])); ?>" class="motel-img" alt="Motel image">
                                        <?php else: ?>
                                            <div class="motel-img d-flex align-items-center justify-content-center text-muted" style="background:#f1f5f9;">
                                                <i class="fas fa-image fa-3x"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="motel-body">
                                            <h4 class="motel-title"><?php echo htmlspecialchars($motel['title']); ?></h4>
                                            <div class="motel-price"><?php echo number_format($motel['price']); ?> đ/tháng</div>
                                            <div class="motel-address">
                                                <i class="fas fa-map-marker-alt mt-1"></i>
                                                <span><?php echo htmlspecialchars($motel['address']); ?></span>
                                            </div>
                                            <div class="motel-footer">
                                                <span><i class="fas fa-vector-square me-1"></i> <?php echo (int)$motel['area']; ?> m²</span>
                                                <span><i class="fas fa-map me-1"></i> <?php echo htmlspecialchars($motel['district_name']); ?></span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cột phải: Đánh giá & Chính sách -->
            <div class="col-lg-4">
                <div class="section-card">
                    <h3 class="section-title"><i class="fas fa-star text-warning"></i> Đánh giá gần đây</h3>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="text-center text-muted py-4">
                            <p class="mb-0">Chưa có đánh giá nào.</p>
                        </div>
                    <?php else: ?>
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-name">
                                            <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                            <span class="review-motel"><?php echo htmlspecialchars(mb_strimwidth($review['motel_title'], 0, 25, '...')); ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <p class="review-comment"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                    <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="section-card bg-light border-0">
                    <h3 class="section-title fs-5"><i class="fas fa-shield-alt text-success"></i> Cam kết & An toàn</h3>
                    <ul class="list-unstyled mb-0 text-muted" style="font-size: 14px; line-height: 1.8;">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Giao dịch minh bạch qua QuanLyPhongTro</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Thông tin chủ trọ đã được hệ thống lưu trữ</li>
                        <li><i class="fas fa-check text-success me-2"></i> Có thể báo cáo nếu phát hiện sai phạm</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <?php 
    if (function_exists('qlpt_render_public_footer')) {
        qlpt_render_public_footer(['base' => '../']); 
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
