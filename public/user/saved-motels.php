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
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// favorites is the canonical table; wishlists is included as legacy fallback.
$savedSourceSql = "
    SELECT motel_id, MAX(saved_id) AS saved_id
    FROM (
        SELECT motel_id, id AS saved_id FROM favorites WHERE user_id = ?
        UNION
        SELECT motel_id, id AS saved_id FROM wishlists WHERE user_id = ?
    ) saved_source
    GROUP BY motel_id
";

// Get total approved saved rooms.
$stmt = $db->prepare("
    SELECT COUNT(*) as count
    FROM ({$savedSourceSql}) saved
    JOIN motels m ON m.id = saved.motel_id
    WHERE m.status = 'approved'
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$total_pages = ceil($total / $limit);
$stmt->close();

// Get saved motels.
$stmt = $db->prepare("
    SELECT m.id, m.title, m.price, m.area, m.address, m.count_view, m.status, u.verified_at,
           (SELECT image_url FROM motel_images WHERE motel_id = m.id LIMIT 1) as cover_image
    FROM ({$savedSourceSql}) saved
    JOIN motels m ON m.id = saved.motel_id
    LEFT JOIN users u ON u.id = m.user_id
    WHERE m.status = 'approved'
    ORDER BY saved.saved_id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iiii", $user_id, $user_id, $limit, $offset);
$stmt->execute();
$motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phòng đã lưu - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .main-content { padding: 30px; }
        
        .motel-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
        }
        .motel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .motel-image-wrapper {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #e9ecef;
        }
        .motel-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .motel-card:hover .motel-image {
            transform: scale(1.05);
        }
        .motel-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
            color: #adb5bd;
            font-size: 3rem;
        }
        .btn-favorite {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff4757;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 2;
        }
        .btn-favorite:hover {
            transform: scale(1.1);
            background: #fff;
        }
        .motel-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .motel-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2b2b2b;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .motel-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #e53935;
            margin-bottom: 12px;
        }
        .motel-meta {
            display: flex;
            gap: 15px;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        .motel-address {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-view {
            margin-top: auto;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-view:hover {
            background: #e9ecef;
            color: #212529;
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        }
        .empty-icon {
            font-size: 80px;
            color: #f1f3f5;
            margin-bottom: 25px;
        }
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
                $userNavActive = 'favorites';
                $userNavVariant = 'workbench';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </aside>

            <section>
                <div class="main-content">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="fw-bold m-0 fs-3">
                            <i class="fas fa-heart text-danger me-2"></i> Phòng đã lưu
                        </h1>
                        <span id="saved-count-badge" class="badge bg-danger rounded-pill px-3 py-2 fs-6"><?php echo $total; ?> phòng</span>
                    </div>

                    <?php if (count($motels) > 0): ?>
                        <div class="row g-4">
                            <?php foreach ($motels as $motel): ?>
                                <?php
                                    $coverImage = !empty($motel['cover_image']) ? (string)$motel['cover_image'] : '';
                                    if ($coverImage !== '' && !filter_var($coverImage, FILTER_VALIDATE_URL)) {
                                        $coverImage = '../' . ltrim(str_replace('\\', '/', $coverImage), '/');
                                    }
                                ?>
                                <div class="col-md-6 col-lg-4" id="motel-card-<?php echo $motel['id']; ?>">
                                    <div class="motel-card">
                                        <div class="motel-image-wrapper">
                                            <button class="btn-favorite" onclick="toggleFavorite(<?php echo $motel['id']; ?>)" title="Bỏ lưu">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                            <?php if ($coverImage !== ''): ?>
                                                <img src="<?php echo htmlspecialchars($coverImage); ?>" class="motel-image" alt="Hình ảnh phòng">
                                            <?php else: ?>
                                                <div class="motel-placeholder"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="motel-body">
                                            <div class="motel-title"><?php echo htmlspecialchars($motel['title']); ?></div>
                                            <div class="motel-price"><?php echo number_format($motel['price']); ?> đ/tháng</div>
                                            
                                            <div class="motel-meta">
                                                <span><i class="fas fa-vector-square me-1"></i> <?php echo htmlspecialchars($motel['area'] ?? '--'); ?> m²</span>
                                                <span><i class="fas fa-eye me-1"></i> <?php echo $motel['count_view']; ?> xem</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <span class="badge bg-success"><i class="fas fa-circle-check me-1"></i> Approved</span>
                                                <?php if (!empty($motel['verified_at'])): ?>
                                                    <span class="badge bg-primary"><i class="fas fa-shield-check me-1"></i> Verified</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="motel-address" title="<?php echo htmlspecialchars($motel['address']); ?>">
                                                <i class="fas fa-map-marker-alt text-muted me-1"></i> <?php echo htmlspecialchars($motel['address']); ?>
                                            </div>
                                            
                                            <a href="motel-detail.php?id=<?php echo $motel['id']; ?>" class="btn-view">
                                                Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-5">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-heart-crack"></i></div>
                            <h4 class="fw-bold mb-3">Bạn chưa lưu phòng nào</h4>
                            <p class="text-muted mb-4">Hãy lưu lại những phòng bạn thích để xem lại sau nhé.</p>
                            <a href="../index.php" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                                <i class="fas fa-search me-2"></i> Khám phá phòng ngay
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleFavorite(motelId) {
        if (!confirm('Bạn có chắc muốn bỏ lưu phòng này?')) return;
        
        fetch('../ajax/toggle-favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ motel_id: motelId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('saved-count-badge');
                if (badge && typeof data.count !== 'undefined') {
                    badge.textContent = data.count + ' phòng';
                }
                // Remove card from UI
                const card = document.getElementById('motel-card-' + motelId);
                if (card) {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        card.remove();
                        // Check if list is empty
                        const remaining = document.querySelectorAll('.motel-card').length;
                        if (remaining === 0) {
                            window.location.reload();
                        }
                    }, 300);
                }
            } else if (data.login_required) {
                window.location.href = '../login.php';
            } else {
                alert(data.message || 'Có lỗi xảy ra!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra!');
        });
    }
    </script>
</body>
</html>
