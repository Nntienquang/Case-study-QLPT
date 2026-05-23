<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';
@require_once '../../core/PathHelper.php';
@require_once '../components/PublicNav.php';

session_start();

/** @var mysqli $conn */
$isLoggedUser = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'user';

$db = new Database($conn);
$user_id = $isLoggedUser ? (int)$_SESSION['user_id'] : 0;
$user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';

// --- Lấy dữ liệu từ GET ---
$keyword = trim($_GET['keyword'] ?? '');
$district_id = $_GET['district_id'] ?? ($_GET['district'] ?? '');
$province_code = trim((string)($_GET['province_code'] ?? ''));
$province_name = trim((string)($_GET['province_name'] ?? ''));
$district_code = trim((string)($_GET['district_code'] ?? ''));
$district_name = trim((string)($_GET['district_name'] ?? ''));
$ward_code = trim((string)($_GET['ward_code'] ?? ''));
$ward_name = trim((string)($_GET['ward_name'] ?? ''));
$category_id = $_GET['category_id'] ?? ($_GET['category'] ?? '');
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$area_min = $_GET['area_min'] ?? '';
$available_from = $_GET['available_from'] ?? '';
$sort = $_GET['sort'] ?? 'featured';
$utilities_filter = $_GET['utilities'] ?? []; 
$flash_sale = isset($_GET['flash_sale']) ? (int)$_GET['flash_sale'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;
$flash = '';

// Lấy danh mục, khu vực, tiện ích để hiển thị lên form lọc
$districts_list = $db->getRows('SELECT id, name FROM districts ORDER BY name');
$categories_list = $db->getRows('SELECT id, name FROM categories ORDER BY name');
$utilities_list = $db->getRows('SELECT id, name FROM utilities ORDER BY name');

// Tính năng Lưu bộ lọc
if (isset($_GET['save_search']) && !$isLoggedUser) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['save_search']) && $isLoggedUser) {
    $hasFilter = $keyword !== '' || $district_id !== '' || $province_code !== '' || $district_code !== '' || $ward_code !== '' || $category_id !== '' || $min_price !== '' || $max_price !== '' || $area_min !== '';
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
            $flash = 'Đã lưu bộ lọc tìm kiếm. Hệ thống sẽ thông báo khi có phòng mới phù hợp.';
        }
        $stmt->close();
    }
}

// --- Xây dựng Query tìm kiếm động ---
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
if ($province_code !== '') {
    $where .= ' AND (m.province_code = ? OR m.province_name = ?)';
    $params[] = $province_code;
    $params[] = $province_name;
    $types .= 'ss';
}
if ($district_code !== '') {
    $where .= ' AND (m.district_code = ? OR m.district_name = ? OR d.name = ?)';
    $params[] = $district_code;
    $params[] = $district_name;
    $params[] = $district_name;
    $types .= 'sss';
}
if ($ward_code !== '') {
    $where .= ' AND (m.ward_code = ? OR m.ward_name = ?)';
    $params[] = $ward_code;
    $params[] = $ward_name;
    $types .= 'ss';
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
if ($flash_sale === 1) {
    $where .= ' AND m.is_flash_sale = 1';
}
if (!empty($utilities_filter) && is_array($utilities_filter)) {
    $util_count = count($utilities_filter);
    $placeholders = implode(',', array_fill(0, $util_count, '?'));
    $where .= " AND m.id IN (
        SELECT motel_id FROM motel_utilities 
        WHERE utility_id IN ($placeholders)
        GROUP BY motel_id 
        HAVING COUNT(DISTINCT utility_id) = $util_count
    )";
    foreach ($utilities_filter as $u_id) {
        $params[] = (int)$u_id;
        $types .= 'i';
    }
}

$stmt = $db->prepare('SELECT COUNT(*) as count ' . $fromSql . $where);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

$orderBy = 'm.is_featured DESC, m.health_score DESC, m.created_at DESC';
if ($sort === 'price_asc') { $orderBy = 'm.price ASC, m.health_score DESC'; } 
elseif ($sort === 'price_desc') { $orderBy = 'm.price DESC, m.health_score DESC'; } 
elseif ($sort === 'newest') { $orderBy = 'm.created_at DESC'; }
elseif ($sort === 'views') { $orderBy = 'm.count_view DESC, m.created_at DESC'; }
elseif ($sort === 'popular') { $orderBy = '((SELECT COUNT(*) FROM favorites f WHERE f.motel_id = m.id) + (SELECT COUNT(*) FROM wishlists w WHERE w.motel_id = m.id)) DESC, m.created_at DESC'; }

$query = '
    SELECT m.*, d.name as district_name, c.name as category_name, u.name as owner_name, u.verified_at, u.trust_score,
           (SELECT mi.image_url FROM motel_images mi WHERE mi.motel_id = m.id ORDER BY mi.id ASC LIMIT 1) AS image_url
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
if ($motels && isset($_SESSION['user_id'])) {
    $motel_ids = implode(',', array_map(fn($m) => (int)$m['id'], $motels));
    $fav_stmt = $db->prepare("SELECT motel_id FROM wishlists WHERE user_id = ? AND motel_id IN ($motel_ids)");
    $uid = (int)$_SESSION['user_id'];
    $fav_stmt->bind_param('i', $uid);
    $fav_stmt->execute();
    $favs = $fav_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $favorite_ids = array_map(fn($f) => (int)$f['motel_id'], $favs);
    $fav_stmt->close();
}

function match_score(array $motel, string $district_id, string $category_id, string $min_price, string $max_price, string $area_min): int {
    $score = 40;
    if ($district_id !== '' && (int)$motel['district_id'] === (int)$district_id) $score += 18;
    if ($category_id !== '' && (int)$motel['category_id'] === (int)$category_id) $score += 14;
    if ($min_price !== '' && (int)$motel['price'] >= (int)$min_price) $score += 8;
    if ($max_price !== '' && (int)$motel['price'] <= (int)$max_price) $score += 8;
    if ($area_min !== '' && (float)$motel['area'] >= (float)$area_min) $score += 7;
    if ((int)($motel['is_featured'] ?? 0) === 1) $score += 3;
    if ((int)($motel['health_score'] ?? 0) >= 80) $score += 2;
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
    body {
        background: #f6f8fb !important;
        color: #172033;
        overflow-x: hidden;
    }

    /* ============================================================
           CSS ĐIỀU CHỈNH BỐ CỤC SHELL & ĐỘ CAO ĐỀU NHAU GIỮA CÁC CARD
           ============================================================ */
    .search-shell {
        padding: 110px 0 60px;
    }

    /* Triệt tiêu khoảng cách trắng thừa bằng bù khoảng trống tuyệt đối */

    .filter-card,
    .result-toolbar,
    .motel-card {
        background: #fff;
        border: 1px solid #e5eaf2;
        border-radius: 16px;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .04);
    }

    .filter-card {
        padding: 24px;
        position: sticky;
        top: 100px;
        max-height: calc(100vh - 140px);
        overflow-y: auto;
    }

    .filter-card::-webkit-scrollbar {
        width: 5px;
    }

    .filter-card::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    .result-toolbar {
        padding: 18px 24px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 14px;
    }

    /* Khối lưới phân định chiều cao đều tăm tắp */
    .motel-card {
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .motel-image {
        height: 200px;
        background: #e2e8f0 center/cover no-repeat;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .motel-image::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(16, 24, 40, 0) 50%, rgba(16, 24, 40, .55));
    }

    .favorite-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 5;
        border: 0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, .95);
        color: #cbd5e1;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .15);
        display: grid;
        place-items: center;
        font-size: 18px;
        transition: all 0.2s ease;
    }

    .favorite-btn:hover {
        transform: scale(1.08);
    }

    .favorite-btn.active, .btn-wishlist.active {
        color: #dc2626 !important;
    }
    .btn-wishlist:not(.active) {
        color: #cbd5e1 !important;
    }

    .motel-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    /* Đồng bộ chiều cao tên phòng khống chế 2 dòng cố định */
    .motel-title {
        font-size: 17px;
        font-weight: 850;
        color: #101828;
        line-height: 1.4;
        height: 48px;
        margin-bottom: 6px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .motel-price {
        color: #0e7490;
        font-size: 20px;
        font-weight: 900;
        margin-bottom: 10px;
    }

    .meta-line {
        color: #475467;
        font-size: 13.5px;
        display: flex;
        gap: 8px;
        align-items: flex-start;
        margin-bottom: 6px;
    }

    .meta-line i {
        margin-top: 4px;
        color: #94a3b8;
    }

    .score-row {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }

    .score-pill {
        padding: 4px 10px;
        border-radius: 6px;
        background: #ecfeff;
        color: #0f766e;
        font-weight: 800;
        font-size: 12px;
    }

    .verified-pill {
        background: #eef2ff;
        color: #4338ca;
    }

    /* Đẩy kịch khung hành động và dự chi xuống đáy thẻ */
    .card-bottom-anchor {
        margin-top: auto;
        border-top: 1px solid #f1f5f9;
        padding-top: 14px;
    }

    .btn-view {
        display: block;
        text-align: center;
        text-decoration: none;
        border-radius: 12px;
        padding: 11px;
        color: #fff;
        background: #101828;
        font-weight: 800;
    }

    .btn-view:hover {
        background: #1d2939;
        color: #fff;
    }

    .util-checkbox {
        margin-bottom: 8px;
    }

    .util-checkbox label {
        color: #475467;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
    }

    /* Footer nội tuyến */
    .embedded-footer {
        background: #111827;
        color: #fff;
        padding: 40px 0 20px;
        margin-top: 60px;
    }

    .embedded-footer a {
        color: #9ca3af;
        text-decoration: none;
    }

    .embedded-footer a:hover {
        color: #fff;
    }
    </style>
</head>

<body>

    <?php qlpt_render_public_nav(['base' => '../', 'active' => 'rooms']); ?>

    <main class="search-shell">
        <div class="container-lg">
            <div class="mb-4">
                <h1 class="fw-black mb-1" style="font-size: 32px; color:#101828;">Tìm phòng phù hợp</h1>
                <p class="text-muted mb-0">Lọc theo nhu cầu thực tế và hệ thống tính điểm tương thích của phòng trọ.</p>
            </div>

            <?php if ($flash): ?>
            <div class="alert alert-success border-0 shadow-sm rounded-4"><i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($flash); ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-3">
                    <aside class="filter-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-black mb-0">Bộ lọc chi tiết</h6>
                            <a href="search.php" class="text-danger small fw-bold text-decoration-none"><i
                                    class="fas fa-rotate-right"></i> Thiết lập lại</a>
                        </div>
                        <form method="GET">
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small text-uppercase">Từ khóa</label>
                                <input type="text" name="keyword" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($keyword); ?>"
                                    placeholder="Trường học, tên đường...">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small text-uppercase">Khu vực</label>
                                <select name="district_id" class="form-select bg-light">
                                    <option value="">Tất cả khu vực</option>
                                    <?php foreach ($districts_list as $dist): ?>
                                    <option value="<?php echo $dist['id']; ?>"
                                        <?php echo (string)$district_id === (string)$dist['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dist['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <div class="mb-3" data-address-picker>
                                    <label class="form-label fw-bold text-dark small text-uppercase">Địa chỉ theo API</label>
                                    <input type="hidden" name="province_name" data-address-province-name value="<?php echo htmlspecialchars($province_name); ?>">
                                    <input type="hidden" name="district_name" data-address-district-name value="<?php echo htmlspecialchars($district_name); ?>">
                                    <input type="hidden" name="ward_name" data-address-ward-name value="<?php echo htmlspecialchars($ward_name); ?>">
                                    <input type="hidden" data-address-full>
                                    <div class="vstack gap-2">
                                        <select name="province_code" class="form-select bg-light" data-address-province data-selected="<?php echo htmlspecialchars($province_code); ?>">
                                            <option value="">-- Chọn tỉnh/thành --</option>
                                        </select>
                                        <select name="district_code" class="form-select bg-light" data-address-district data-selected="<?php echo htmlspecialchars($district_code); ?>" disabled>
                                            <option value="">-- Chọn quận/huyện --</option>
                                        </select>
                                        <select name="ward_code" class="form-select bg-light" data-address-ward data-selected="<?php echo htmlspecialchars($ward_code); ?>" disabled>
                                            <option value="">-- Chọn phường/xã --</option>
                                        </select>
                                    </div>
                                    <div class="form-text" data-address-status></div>
                                    <div class="form-text">Dùng dữ liệu provinces.open-api.vn; khu vực cũ phía trên là fallback cho tin cũ.</div>
                                </div>
                                <label class="form-label fw-bold text-dark small text-uppercase">Loại hình</label>
                                <select name="category_id" class="form-select bg-light">
                                    <option value="">Tất cả loại phòng</option>
                                    <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo (string)$category_id === (string)$cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small text-uppercase">Khoảng giá thuê</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" name="min_price" class="form-control bg-light"
                                            value="<?php echo htmlspecialchars($min_price); ?>" placeholder="Từ đ">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" name="max_price" class="form-control bg-light"
                                            value="<?php echo htmlspecialchars($max_price); ?>" placeholder="Đến đ">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small text-uppercase">Diện tích từ
                                    (m²)</label>
                                <input type="number" step="0.5" name="area_min" class="form-control bg-light"
                                    value="<?php echo htmlspecialchars($area_min); ?>" placeholder="Ví dụ: 25">
                            </div>

                            <div class="mb-4">
                                <label
                                    class="form-label fw-bold text-dark small text-uppercase mb-2 border-top pt-3 w-100">Tiện
                                    ích bắt buộc</label>
                                <?php foreach ($utilities_list as $u): ?>
                                <div class="form-check util-checkbox">
                                    <input class="form-check-input" type="checkbox" name="utilities[]"
                                        value="<?php echo $u['id']; ?>" id="util_<?php echo $u['id']; ?>"
                                        <?php echo in_array($u['id'], $utilities_filter) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="util_<?php echo $u['id']; ?>">
                                        <?php echo htmlspecialchars($u['name']); ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="flash_sale" value="1" id="flash_sale_toggle" <?php echo $flash_sale === 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold text-danger" for="flash_sale_toggle">
                                        <i class="fas fa-fire"></i> Chỉ hiện Flash Sale
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold text-dark small text-uppercase">Sắp xếp</label>
                                <select name="sort" class="form-select bg-light">
                                    <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Đề
                                        xuất nổi bật</option>
                                    <option value="match" <?php echo $sort === 'match' ? 'selected' : ''; ?>>Độ tương
                                        thích nhất</option>
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới đăng
                                        trước</option>
                                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá
                                        tăng dần</option>
                                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>
                                        Giá giảm dần</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-dark w-100 mb-2 py-2 fw-bold"
                                style="border-radius: 12px;">Áp dụng</button>
                            <button type="submit" name="save_search" value="1"
                                class="btn btn-outline-secondary w-100 py-2 fw-bold" style="border-radius: 12px;"><i
                                    class="far fa-bell me-1"></i> Lưu thông báo</button>
                        </form>
                    </aside>
                </div>

                <div class="col-lg-9">
                    <div class="result-toolbar rounded-4">
                        <div class="text-dark">Tổng số: <strong
                                class="fs-5 text-primary"><?php echo number_format($total); ?></strong> phòng trống đã
                            duyệt</div>
                    </div>

                    <?php if ($motels): ?>
                    <div class="row g-4">
                        <?php foreach ($motels as $motel): ?>
                        <?php
                                $moveInCost = (int)$motel['price'] + (int)($motel['service_fee'] ?? 0) + (int)round((int)$motel['price'] * (float)($motel['deposit_months'] ?? 1));
                                $motelImage = !empty($motel['image_url']) ? $motel['image_url'] : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=80';
                                if (!filter_var($motelImage, FILTER_VALIDATE_URL) && strpos($motelImage, '../') !== 0) {
                                    $motelImage = '../' . ltrim($motelImage, '/');
                                }
                            ?>
                        <div class="col-md-6 col-xl-4">
                            <article class="room-card border-0 shadow-sm rounded-3 overflow-hidden d-flex flex-column h-100 bg-white">
                                <div class="room-photo position-relative" style="aspect-ratio: 4/3; background: #f3f4f6;">
                                    <img src="<?php echo htmlspecialchars($motelImage); ?>" alt="Ảnh phòng" class="w-100 h-100" style="object-fit: cover; color: transparent;">
                                    
                                    <?php if (!empty($motel['badge_label'])): ?>
                                        <span class="badge bg-danger position-absolute top-0 start-0 m-2 z-3 px-2 py-1 shadow-sm"><?php echo htmlspecialchars($motel['badge_label']); ?></span>
                                    <?php elseif (!empty($motel['is_flash_sale'])): ?>
                                        <span class="badge-flash-sale position-absolute top-0 start-0 m-2 z-3 shadow-sm">Flash Sale</span>
                                    <?php endif; ?>
                                    
                                    <button class="btn-wishlist position-absolute z-3 d-flex align-items-center justify-content-center border-0 <?php echo in_array((int)$motel['id'], $favorite_ids, true) ? 'active' : ''; ?>" style="top:12px; right:12px; width:36px; height:36px; border-radius:50%; background:rgba(255,255,255,0.8); box-shadow:0 4px 8px rgba(0,0,0,0.15); cursor:pointer;" onclick="toggleFavorite(<?php echo (int)$motel['id']; ?>, this)" aria-label="Yêu thích"><i class="fas fa-heart"></i></button>
                                </div>
                                <div class="room-body p-3 d-flex flex-column flex-grow-1">
                                    <div class="score-row mb-2 d-flex gap-2">
                                        <span class="badge bg-info text-dark rounded-pill"><?php echo (int)$motel['match_score']; ?>% phù hợp</span>
                                        <?php if (!empty($motel['verified_at'])): ?>
                                            <span class="badge bg-primary rounded-pill"><i class="fas fa-check-circle"></i> Verified</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="room-title fw-bold mb-2" style="font-size: 1.1rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                        <?php echo htmlspecialchars($motel['title']); ?>
                                    </h3>
                                    
                                    <div class="room-price mb-2">
                                        <span class="text-price fs-5"><?php echo number_format((int)$motel['price']); ?> đ/tháng</span>
                                        <?php if (!empty($motel['old_price']) && $motel['old_price'] > $motel['price']): ?>
                                            <span class="text-old-price ms-2 text-muted text-decoration-line-through small"><?php echo number_format((int)$motel['old_price']); ?> đ</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="room-meta text-muted small mb-3">
                                        <div class="mb-1"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($motel['address'] ?: 'Chưa cập nhật địa chỉ'); ?></div>
                                        <div><i class="fas fa-cube"></i> <?php echo htmlspecialchars($motel['category_name'] ?? 'Phòng trọ'); ?> &bull; <?php echo (float)$motel['area']; ?> m²</div>
                                    </div>
                                    
                                    <div class="room-card-footer mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <div class="saving-note text-muted small fw-bold">
                                            <i class="fas fa-wallet text-secondary"></i> Cần: <?php echo number_format($moveInCost); ?> đ
                                        </div>
                                        <a href="motel-detail.php?id=<?php echo (int)$motel['id']; ?>" class="btn btn-primary btn-sm rounded-3 fw-bold">Xem phòng</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-5" aria-label="Pagination">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link shadow-sm mx-1 rounded-3 <?php echo $i === $page ? 'bg-dark border-dark text-white' : 'text-dark'; ?>"
                                    href="?<?php echo http_build_query(array_merge($baseQuery, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>

                    <?php else: ?>
                    <div class="empty-state bg-white border rounded-4 p-5 text-center shadow-sm">
                        <div class="display-3 mb-3 text-secondary"><i class="fas fa-box-open"></i></div>
                        <h4 class="fw-bold">Không tìm thấy phòng phù hợp</h4>
                        <p class="text-muted">Hãy thử bỏ bớt một vài tiêu chí tiện ích hoặc mở rộng khoảng giá thuê để
                            thấy nhiều phòng hơn.</p>
                        <a href="search.php" class="btn btn-dark px-4 py-2 mt-2 rounded-pill">Xóa toàn bộ bộ lọc</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="embedded-footer border-top border-secondary">
        <div class="container-lg text-center">
            <h5 class="fw-bold mb-3">🏠 QuanLyPhongTro</h5>
            <p class="text-secondary small mb-4">Nền tảng kết nối chủ trọ và người thuê thông minh, an toàn, minh bạch.
            </p>
            <div class="text-secondary small">© 2026 Bản quyền thuộc về Team dự án Đại học Vinh.</div>
        </div>
    </footer>

    <script>
    // Xử lý lưu Like List / Phòng yêu thích qua AJAX 
    function toggleFavorite(motelId, button) {
        <?php if (!isset($_SESSION['user_id'])): ?>
        window.location.href = '../login.php';
        return;
        <?php endif; ?>

        fetch('../ajax/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    motel_id: motelId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('active');
                } else {
                    alert('Có lỗi xảy ra, vui lòng thử lại sau!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/vn-address-picker.js"></script>
</body>

</html>
