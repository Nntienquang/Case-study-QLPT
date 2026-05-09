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

$message = '';
$message_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_viewing'])) {
    $preferred_time = $_POST['preferred_time'] ?? '';
    $note = trim($_POST['note'] ?? '');
    if ($preferred_time === '') {
        $message = 'Vui long chon thoi gian xem phong.';
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare("
            INSERT INTO viewing_appointments (user_id, motel_id, owner_id, preferred_time, note, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $ownerId = (int)$motel['user_id'];
        $stmt->bind_param("iiiss", $user_id, $motel_id, $ownerId, $preferred_time, $note);
        if ($stmt->execute()) {
            $message = 'Da gui lich xem phong. Owner se xac nhan lai voi ban.';
            $notifyTitle = 'Co lich xem phong moi';
            $notifyBody = 'User ' . ($_SESSION['name'] ?? 'User') . ' muon xem phong: ' . $motel['title'];
            $notifyLink = 'owner/dashboard.php';
            $notify = $db->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, 'viewing_request', ?, ?, ?)");
            if ($notify) {
                $notify->bind_param("isss", $ownerId, $notifyTitle, $notifyBody, $notifyLink);
                $notify->execute();
                $notify->close();
            }
        } else {
            $message = 'Khong the gui lich xem phong luc nay.';
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

// Increment view count
$stmt = $db->prepare("UPDATE motels SET count_view = count_view + 1 WHERE id = ?");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$stmt->close();

// Check if favorite
$stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND motel_id = ?");
$stmt->bind_param("ii", $user_id, $motel_id);
$stmt->execute();
$is_favorite = $stmt->get_result()->fetch_assoc() ? true : false;
$stmt->close();

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
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .header { background: white; padding: 30px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .main-image { background: linear-gradient(135deg, #667eea, #764ba2); height: 400px; display: flex; align-items: center; justify-content: center; color: white; border-radius: 12px; margin-bottom: 30px; }
        .main-image i { font-size: 100px; }
        .title-section { display: flex; justify-content: space-between; align-items: start; margin-bottom: 30px; }
        .motel-title { font-size: 32px; font-weight: 700; color: #333; margin-bottom: 10px; }
        .motel-price { font-size: 28px; font-weight: 700; color: #667eea; }
        .motel-location { color: #666; font-size: 16px; }
        .info-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .info-card h5 { font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .info-item { padding: 15px; background: #f8f9fa; border-radius: 6px; text-align: center; }
        .info-item-label { font-weight: 600; color: #333; margin-bottom: 8px; }
        .info-item-value { color: #667eea; font-size: 18px; font-weight: 700; }
        .utilities-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; }
        .utility-badge { background: #e3f2fd; color: #1976d2; padding: 10px; border-radius: 6px; text-align: center; font-size: 13px; font-weight: 600; }
        .description { line-height: 1.8; color: #666; margin-bottom: 20px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; padding: 12px 30px; font-weight: 600; }
        .btn-primary:hover { color: white; }
        .btn-heart { background: white; border: 2px solid #667eea; color: #667eea; padding: 12px 30px; font-weight: 600; }
        .btn-heart.active { background: #667eea; color: white; }
        .review-item { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .review-author { font-weight: 600; color: #333; }
        .review-rating { color: #ffc107; }
        .review-text { color: #666; line-height: 1.6; }
        .review-date { color: #999; font-size: 13px; }
        .owner-card { background: white; padding: 25px; border-radius: 12px; border-left: 4px solid #667eea; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .owner-name { font-size: 18px; font-weight: 700; margin-bottom: 10px; }
        .btn-contact { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; }
        .cost-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #4b5563; }
        .cost-row strong { color: #0f172a; }
        .cost-total { font-size: 20px; font-weight: 800; color: #2563eb; }
        .verified-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px; background: #eef2ff; color: #4338ca; font-weight: 700; font-size: 12px; }
    </style>
    <link href="../assets/css/modern.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
        </div>
    </nav>

    <div class="container-lg" style="padding: 30px 0;">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <a href="search.php" style="color: #667eea; text-decoration: none; margin-bottom: 20px; display: inline-block;">
            <i class="fas fa-arrow-left"></i> Quay Láº¡i
        </a>

        <div class="row">
            <div class="col-lg-8">
                <div class="main-image">
                    <i class="fas fa-image"></i>
                </div>

                <div class="title-section">
                    <div>
                        <h1 class="motel-title"><?php echo htmlspecialchars($motel['title']); ?></h1>
                        <p class="motel-location">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($motel['address']); ?>
                        </p>
                    </div>
                    <div class="motel-price"><?php echo number_format($motel['price']); ?> VNÄ/thÃ¡ng</div>
                </div>

                <!-- Information -->
                <div class="info-card">
                    <h5><i class="fas fa-info-circle"></i> ThÃ´ng Tin Chi Tiáº¿t</h5>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-item-label">Diá»‡n TÃ­ch</div>
                            <div class="info-item-value"><?php echo $motel['area']; ?> mÂ²</div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">PhÃ²ng Ngá»§</div>
                            <div class="info-item-value"><?php echo $motel['bedrooms']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">PhÃ²ng Táº¯m</div>
                            <div class="info-item-value"><?php echo $motel['bathrooms']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Danh Má»¥c</div>
                            <div class="info-item-value"><?php echo htmlspecialchars($motel['category_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-item-label">Ngay Trong</div>
                            <div class="info-item-value"><?php echo !empty($motel['available_from']) ? date('d/m/Y', strtotime($motel['available_from'])) : 'Lien he'; ?></div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="info-card">
                    <h5><i class="fas fa-align-left"></i> MÃ´ Táº£</h5>
                    <p class="description"><?php echo nl2br(htmlspecialchars($motel['description'])); ?></p>
                </div>

                <!-- Utilities -->
                <?php if (count($utilities_array) > 0): ?>
                    <div class="info-card">
                        <h5><i class="fas fa-star"></i> Tiá»‡n Nghi</h5>
                        <div class="utilities-list">
                            <?php foreach ($utilities_array as $util): ?>
                                <div class="utility-badge">
                                    <i class="fas fa-check"></i> <?php echo ucfirst(str_replace('_', ' ', $util)); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reviews -->
                <div class="info-card">
                    <h5><i class="fas fa-star"></i> ÄÃ¡nh GiÃ¡</h5>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div>
                                        <div class="review-author"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                        <div class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999;">ChÆ°a cÃ³ Ä‘Ã¡nh giÃ¡ nÃ o</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Booking Card -->
                <div class="info-card" style="position: sticky; top: 80px;">
                    <h5 style="margin-bottom: 20px;">Äáº·t PhÃ²ng Ngay</h5>
                    <div class="mb-4">
                        <div class="cost-row"><span>Tien thue thang dau</span><strong><?php echo number_format((int)$motel['price']); ?> VND</strong></div>
                        <div class="cost-row"><span>Tien coc (<?php echo $deposit_months; ?> thang)</span><strong><?php echo number_format($deposit_amount); ?> VND</strong></div>
                        <div class="cost-row"><span>Phi dich vu/thang</span><strong><?php echo number_format($service_fee); ?> VND</strong></div>
                        <div class="d-flex justify-content-between align-items-center pt-3">
                            <span>Uoc tinh can chuan bi</span>
                            <span class="cost-total"><?php echo number_format($move_in_total); ?> VND</span>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="checkout.php?id=<?php echo $motel['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-plus"></i> Äáº·t PhÃ²ng
                        </a>
                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#viewingForm">
                            <i class="fas fa-calendar-check"></i> Dat lich xem
                        </button>
                        <button class="btn btn-heart <?php echo $is_favorite ? 'active' : ''; ?>" onclick="toggleFavorite(<?php echo $motel['id']; ?>, this)">
                            <i class="fas fa-heart"></i> YÃªu ThÃ­ch
                        </button>
                    </div>
                    <form method="POST" class="collapse mt-3" id="viewingForm">
                        <div class="mb-2">
                            <label class="form-label">Thoi gian muon xem</label>
                            <input type="datetime-local" name="preferred_time" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Ghi chu</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="VD: Minh muon xem phong sau 18h"></textarea>
                        </div>
                        <button type="submit" name="schedule_viewing" class="btn btn-primary w-100">Gui lich xem</button>
                    </form>
                </div>

                <!-- Owner Card -->
                <div class="owner-card">
                    <div class="owner-name">
                        <i class="fas fa-user-circle" style="color: #667eea;"></i> <?php echo htmlspecialchars($motel['owner_name']); ?>
                    </div>
                    <?php if (!empty($motel['verified_at'])): ?>
                        <div class="verified-badge mb-2"><i class="fas fa-shield-alt"></i> Owner da xac minh</div>
                    <?php endif; ?>
                    <p style="color: #666; margin-bottom: 15px;">Chá»§ nhÃ  / NgÆ°á»i cho thuÃª</p>
                    <button class="btn-contact w-100">
                        <i class="fas fa-envelope"></i> LiÃªn Há»‡ Chá»§ NhÃ 
                    </button>
                </div>

                <!-- Stats -->
                <div class="info-card">
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <div style="font-size: 24px; font-weight: 700; color: #667eea;"><?php echo $motel['count_view']; ?></div>
                            <div style="color: #666; font-size: 13px;">LÆ°á»£t Xem</div>
                        </div>
                        <div>
                            <div style="font-size: 24px; font-weight: 700; color: #667eea;">5â˜…</div>
                            <div style="color: #666; font-size: 13px;">ÄÃ¡nh GiÃ¡</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleFavorite(motelId, button) {
            fetch('../ajax/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ motel_id: motelId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('active');
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
