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
$user_id = (int)$_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'User';

$keyword = trim($_GET['keyword'] ?? '');
$district_id = $_GET['district_id'] ?? ($_GET['district'] ?? '');
$category_id = $_GET['category_id'] ?? ($_GET['category'] ?? '');
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$area_min = $_GET['area_min'] ?? '';
$available_from = $_GET['available_from'] ?? '';
$sort = $_GET['sort'] ?? 'featured';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$flash = '';

$stmt = $db->prepare('SELECT id, name FROM districts ORDER BY name');
$stmt->execute();
$districts_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare('SELECT id, name FROM categories ORDER BY name');
$stmt->execute();
$categories_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (isset($_GET['save_search'])) {
    $hasFilter = $keyword !== '' || $district_id !== '' || $category_id !== '' || $min_price !== '' || $max_price !== '' || $area_min !== '';
    if ($hasFilter) {
        $searchName = $keyword !== '' ? $keyword : 'Bộ lọc phòng trọ';
        $stmt = $db->prepare('
            INSERT INTO saved_searches (user_id, name, keyword, district_id, category_id, price_min, price_max, area_min, alert_enabled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ');
        $districtValue = $district_id !== '' ? (int)$district_id : null;
        $categoryValue = $category_id !== '' ? (int)$category_id : null;
        $minValue = $min_price !== '' ? (int)$min_price : null;
        $maxValue = $max_price !== '' ? (int)$max_price : null;
        $areaValue = $area_min !== '' ? (int)$area_min : null;
        $stmt->bind_param('issiiiii', $user_id, $searchName, $keyword, $districtValue, $categoryValue, $minValue, $maxValue, $areaValue);
        if ($stmt->execute()) {
            $flash = 'Đã lưu bộ lọc tìm kiếm. Sau này hệ thống có thể dùng bộ lọc này để tạo thông báo phòng mới.';
        }
        $stmt->close();
    }
}

$fromSql = '
    FROM motels m
    LEFT JOIN districts d ON m.district_id = d.id
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN users u ON m.user_id = u.id
    WHERE m.status = "approved"
';
$where = '';
$params = [];
$types = '';

if ($keyword !== '') {
    $where .= ' AND (m.title LIKE ? OR m.description LIKE ? OR m.address LIKE ?)';
    $kw = '%' . $keyword . '%';
    array_push($params, $kw, $kw, $kw);
    $types .= 'sss';
}
if ($district_id !== '') {
    $where .= ' AND m.district_id = ?';
    $params[] = (int)$district_id;
    $types .= 'i';
}
if ($category_id !== '') {
    $where .= ' AND m.category_id = ?';
    $params[] = (int)$category_id;
    $types .= 'i';
}
if ($min_price !== '') {
    $where .= ' AND m.price >= ?';
    $params[] = (int)$min_price;
    $types .= 'i';
}
if ($max_price !== '') {
    $where .= ' AND m.price <= ?';
    $params[] = (int)$max_price;
    $types .= 'i';
}
if ($area_min !== '') {
    $where .= ' AND m.area >= ?';
    $params[] = (float)$area_min;
    $types .= 'd';
}
if ($available_from !== '') {
    $where .= ' AND (m.available_from IS NULL OR m.available_from <= ?)';
    $params[] = $available_from;
    $types .= 's';
}

$stmt = $db->prepare('SELECT COUNT(*) as count ' . $fromSql . $where);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$orderBy = 'm.is_featured DESC, m.health_score DESC, m.created_at DESC';
if ($sort === 'price_asc') {
    $orderBy = 'm.price ASC, m.health_score DESC';
} elseif ($sort === 'price_desc') {
    $orderBy = 'm.price DESC, m.health_score DESC';
} elseif ($sort === 'newest') {
    $orderBy = 'm.created_at DESC';
}

$query = '
    SELECT m.*, d.name as district_name, c.name as category_name, u.name as owner_name, u.verified_at, u.trust_score
    ' . $fromSql . $where . '
    ORDER BY ' . $orderBy . '
    LIMIT ? OFFSET ?
';
$queryParams = $params;
$queryTypes = $types . 'ii';
$queryParams[] = $limit;
$queryParams[] = $offset;

$stmt = $db->prepare($query);
$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();
$motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$favorite_ids = [];
if ($motels) {
    $motel_ids = implode(',', array_map(fn($m) => (int)$m['id'], $motels));
    $fav_stmt = $db->prepare("SELECT motel_id FROM favorites WHERE user_id = ? AND motel_id IN ($motel_ids)");
    $fav_stmt->bind_param('i', $user_id);
    $fav_stmt->execute();
    $favs = $fav_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $favorite_ids = array_map(fn($f) => (int)$f['motel_id'], $favs);
    $fav_stmt->close();
}

function match_score(array $motel, string $district_id, string $category_id, string $min_price, string $max_price, string $area_min): int
{
    $score = 40;
    if ($district_id !== '' && (int)$motel['district_id'] === (int)$district_id) {
        $score += 18;
    }
    if ($category_id !== '' && (int)$motel['category_id'] === (int)$category_id) {
        $score += 14;
    }
    if ($min_price !== '' && (int)$motel['price'] >= (int)$min_price) {
        $score += 8;
    }
    if ($max_price !== '' && (int)$motel['price'] <= (int)$max_price) {
        $score += 8;
    }
    if ($area_min !== '' && (float)$motel['area'] >= (float)$area_min) {
        $score += 7;
    }
    if ((int)($motel['is_featured'] ?? 0) === 1) {
        $score += 3;
    }
    if ((int)($motel['health_score'] ?? 0) >= 80) {
        $score += 2;
    }
    return min(100, $score);
}

foreach ($motels as $index => $motel) {
    $motels[$index]['match_score'] = match_score($motel, (string)$district_id, (string)$category_id, (string)$min_price, (string)$max_price, (string)$area_min);
}

if ($sort === 'match') {
    usort($motels, fn($a, $b) => $b['match_score'] <=> $a['match_score']);
}

$total_pages = (int)ceil($total / $limit);
$baseQuery = $_GET;
unset($baseQuery['page'], $baseQuery['save_search']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; font-family: 'Segoe UI', sans-serif; color: #111827; }
        .navbar { background: #0f172a; }
        .search-shell { padding: 28px 0 44px; }
        .filter-card, .result-toolbar, .motel-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 16px 45px rgba(15, 23, 42, .07); }
        .filter-card { padding: 22px; position: sticky; top: 84px; }
        .result-toolbar { padding: 18px 20px; margin-bottom: 18px; display: flex; justify-content: space-between; gap: 14px; align-items: center; flex-wrap: wrap; }
        .motel-card { overflow: hidden; height: 100%; display: flex; flex-direction: column; }
        .motel-image { height: 190px; background: linear-gradient(135deg, #1d4ed8, #14b8a6); color: white; position: relative; display: flex; align-items: center; justify-content: center; }
        .motel-image i { font-size: 46px; opacity: .82; }
        .favorite-btn { position: absolute; top: 12px; right: 12px; border: 0; width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,.95); color: #64748b; box-shadow: 0 12px 30px rgba(15,23,42,.18); }
        .favorite-btn.active { color: #dc2626; }
        .motel-body { padding: 18px; display: flex; flex-direction: column; gap: 10px; flex: 1; }
        .motel-title { font-size: 17px; font-weight: 800; color: #0f172a; line-height: 1.35; min-height: 46px; }
        .motel-price { color: #2563eb; font-size: 20px; font-weight: 900; }
        .meta-line { color: #64748b; font-size: 13px; display: flex; gap: 8px; align-items: flex-start; }
        .score-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .score-pill { padding: 6px 10px; border-radius: 999px; background: #ecfeff; color: #0f766e; font-weight: 700; font-size: 12px; }
        .verified-pill { background: #eef2ff; color: #4338ca; }
        .btn-view { display: block; margin-top: auto; text-align: center; text-decoration: none; border-radius: 10px; padding: 10px 14px; color: #fff; background: linear-gradient(135deg, #2563eb, #14b8a6); font-weight: 800; }
        .btn-view:hover { color: #fff; }
        .empty-state { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 54px 24px; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-home"></i> QuanLyPhongTro</a>
            <div class="ms-auto dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="dashboard.php">Tổng quan</a></li>
                    <li><a class="dropdown-item" href="my-bookings.php">Đơn đặt của tôi</a></li>
                    <li><a class="dropdown-item" href="saved-motels.php">Phòng đã lưu</a></li>
                    <li><a class="dropdown-item" href="saved-searches.php">Bộ lọc đã lưu</a></li>
                    <li><a class="dropdown-item" href="settings.php">Cài đặt</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="search-shell">
        <div class="container-lg">
            <div class="mb-4">
                <h1 class="fw-black mb-2">Tìm phòng phù hợp</h1>
                <p class="text-muted mb-0">Lọc theo nhu cầu thực tế, lưu bộ lọc và xem điểm phù hợp của từng phòng.</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($flash); ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-3">
                    <aside class="filter-card">
                        <h5 class="fw-bold mb-3">Bộ lọc</h5>
                        <form method="GET">
                            <div class="mb-3">
                                <label class="form-label">Từ khóa</label>
                                <input type="text" name="keyword" class="form-control" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Gần trường, tên đường...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Quận/Huyện</label>
                                <select name="district_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($districts_list as $dist): ?>
                                        <option value="<?php echo $dist['id']; ?>" <?php echo (string)$district_id === (string)$dist['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dist['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($categories_list as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo (string)$category_id === (string)$cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Giá từ</label>
                                    <input type="number" name="min_price" class="form-control" value="<?php echo htmlspecialchars($min_price); ?>">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">Đến</label>
                                    <input type="number" name="max_price" class="form-control" value="<?php echo htmlspecialchars($max_price); ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Diện tích tối thiểu</label>
                                <input type="number" step="0.5" name="area_min" class="form-control" value="<?php echo htmlspecialchars($area_min); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Cần vào ở trước ngày</label>
                                <input type="date" name="available_from" class="form-control" value="<?php echo htmlspecialchars($available_from); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sắp xếp</label>
                                <select name="sort" class="form-select">
                                    <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Nổi bật</option>
                                    <option value="match" <?php echo $sort === 'match' ? 'selected' : ''; ?>>Phù hợp nhất</option>
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-search"></i> Tìm kiếm
                            </button>
                            <button type="submit" name="save_search" value="1" class="btn btn-outline-primary w-100">
                                <i class="fas fa-bell"></i> Lưu bộ lọc
                            </button>
                        </form>
                    </aside>
                </div>

                <div class="col-lg-9">
                    <div class="result-toolbar">
                        <div>
                            <strong><?php echo number_format($total); ?></strong> phòng đang được duyệt
                            <?php if ($keyword !== ''): ?>
                                <span class="text-muted">với từ khóa "<?php echo htmlspecialchars($keyword); ?>"</span>
                            <?php endif; ?>
                        </div>
                        <a href="search.php" class="btn btn-sm btn-outline-secondary">Xóa bộ lọc</a>
                    </div>

                    <?php if ($motels): ?>
                        <div class="row g-4">
                            <?php foreach ($motels as $motel): ?>
                                <?php
                                    $moveInCost = (int)$motel['price'] + (int)($motel['service_fee'] ?? 0) + (int)round((int)$motel['price'] * (float)($motel['deposit_months'] ?? 1));
                                ?>
                                <div class="col-md-6 col-xl-4">
                                    <article class="motel-card">
                                        <div class="motel-image">
                                            <i class="fas fa-building"></i>
                                            <button class="favorite-btn <?php echo in_array((int)$motel['id'], $favorite_ids, true) ? 'active' : ''; ?>" onclick="toggleFavorite(<?php echo (int)$motel['id']; ?>, this)" type="button" aria-label="Yêu thích">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </div>
                                        <div class="motel-body">
                                            <div class="score-row">
                                                <span class="score-pill"><?php echo (int)$motel['match_score']; ?>% phù hợp</span>
                                                <?php if (!empty($motel['verified_at'])): ?>
                                                    <span class="score-pill verified-pill"><i class="fas fa-shield-alt"></i> Owner verified</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="motel-title"><?php echo htmlspecialchars($motel['title']); ?></div>
                                            <div class="motel-price"><?php echo number_format((int)$motel['price']); ?> VNĐ/tháng</div>
                                            <div class="meta-line"><i class="fas fa-map-marker-alt"></i><span><?php echo htmlspecialchars($motel['address']); ?></span></div>
                                            <div class="meta-line"><i class="fas fa-location-dot"></i><span><?php echo htmlspecialchars($motel['district_name'] ?? 'Chưa rõ quận'); ?> · <?php echo htmlspecialchars($motel['category_name'] ?? 'Phòng trọ'); ?></span></div>
                                            <div class="meta-line"><i class="fas fa-ruler-combined"></i><span><?php echo (float)$motel['area']; ?> m² · <?php echo (int)$motel['count_view']; ?> lượt xem</span></div>
                                            <div class="meta-line"><i class="fas fa-wallet"></i><span>Ước tính vào ở: <?php echo number_format($moveInCost); ?> VNĐ</span></div>
                                            <a href="motel-detail.php?id=<?php echo (int)$motel['id']; ?>" class="btn-view">Xem chi tiết</a>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-4" aria-label="Pagination">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($baseQuery, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="display-6 mb-3"><i class="fas fa-magnifying-glass"></i></div>
                            <h4>Chưa có phòng phù hợp</h4>
                            <p class="text-muted">Hãy giảm bớt bộ lọc giá, khu vực hoặc diện tích để mở rộng kết quả.</p>
                            <a href="search.php" class="btn btn-primary">Đặt lại bộ lọc</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleFavorite(motelId, button) {
            fetch('../ajax/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ motel_id: motelId })
            })
            .then(response => response.json())
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

