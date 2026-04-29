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

// Get search parameters
$keyword = $_GET['keyword'] ?? '';
$district = $_GET['district'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$category = $_GET['category'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get districts and categories for filter
$stmt = $db->prepare("SELECT id, name FROM districts ORDER BY name");
$stmt->execute();
$districts_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Build search query
$query = "SELECT m.*, d.name as district_name FROM motels m LEFT JOIN districts d ON m.district_id = d.id WHERE m.status = 'approved'";
$params = [];
$types = "";

if (!empty($keyword)) {
    $query .= " AND (m.title LIKE ? OR m.description LIKE ? OR m.address LIKE ?)";
    $kw = "%$keyword%";
    array_push($params, $kw, $kw, $kw);
    $types .= "sss";
}
if (!empty($district)) {
    $query .= " AND m.district_id = ?";
    array_push($params, (int)$district);
    $types .= "i";
}
if (!empty($min_price)) {
    $query .= " AND m.price >= ?";
    array_push($params, (int)$min_price);
    $types .= "i";
}
if (!empty($max_price)) {
    $query .= " AND m.price <= ?";
    array_push($params, (int)$max_price);
    $types .= "i";
}
if (!empty($category)) {
    $query .= " AND m.category_id = ?";
    array_push($params, (int)$category);
    $types .= "i";
}

// Get total count
$count_query = $query;
$stmt = $db->prepare($count_query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc();
$total = $total ? $total['COUNT(*)'] : 0;

if (!$total) {
    $count_stmt = $db->prepare(str_replace("SELECT m.*, d.name as district_name", "SELECT COUNT(*) as cnt", $count_query));
    if ($params) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['cnt'];
    $count_stmt->close();
}
$stmt->close();

$total_pages = ceil($total / $limit);

// Get motels
$query .= " ORDER BY m.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $db->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$motels = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check favorites
$favorite_ids = [];
if ($motels) {
    $motel_ids = implode(',', array_map(fn($m) => (int)$m['id'], $motels));
    $fav_stmt = $db->prepare("SELECT motel_id FROM favorites WHERE user_id = ? AND motel_id IN ($motel_ids)");
    $fav_stmt->bind_param("i", $user_id);
    $fav_stmt->execute();
    $favs = $fav_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $favorite_ids = array_map(fn($f) => $f['motel_id'], $favs);
    $fav_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm Phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .navbar-nav .nav-link { color: rgba(255,255,255,0.9) !important; margin-left: 20px; }
        .navbar-nav .nav-link:hover { color: white !important; }
        .filter-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .filter-card h5 { font-weight: 700; margin-bottom: 20px; color: #333; }
        .form-label { font-weight: 600; color: #333; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #ddd; }
        .btn-search { background: linear-gradient(135deg, #667eea, #764ba2); border: none; color: white; }
        .btn-search:hover { color: white; }
        .results-container { padding: 30px 0; }
        .motel-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); transition: 0.3s; height: 100%; }
        .motel-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .motel-image { height: 200px; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; position: relative; }
        .motel-image i { font-size: 50px; }
        .favorite-btn { position: absolute; top: 10px; right: 10px; background: white; border: none; color: #667eea; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; }
        .favorite-btn.active { color: #d32f2f; }
        .motel-body { padding: 20px; }
        .motel-title { font-size: 16px; font-weight: 600; margin-bottom: 10px; color: #333; }
        .motel-price { font-size: 20px; font-weight: 700; color: #667eea; margin-bottom: 10px; }
        .motel-info { color: #666; font-size: 13px; margin-bottom: 15px; }
        .motel-info-item { margin-bottom: 8px; }
        .btn-view { background: linear-gradient(135deg, #667eea, #764ba2); border: none; color: white; padding: 8px 15px; border-radius: 6px; cursor: pointer; text-decoration: none; }
        .btn-view:hover { color: white; }
        .empty-state { text-align: center; padding: 60px 30px; background: white; border-radius: 12px; }
        .empty-state-icon { font-size: 60px; color: #ddd; margin-bottom: 20px; }
        .pagination { justify-content: center; margin-top: 30px; }
        .result-count { color: #666; margin-bottom: 25px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user_name); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="../logout.php">Đăng Xuất</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-lg" style="padding: 30px 0;">
        <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
            <i class="fas fa-search"></i> Tìm Phòng
        </h1>

        <div class="row">
            <!-- Filters -->
            <div class="col-lg-3">
                <div class="filter-card">
                    <h5>Bộ Lọc</h5>
                    <form method="GET">
                        <div class="mb-3">
                            <label class="form-label">Từ Khóa</label>
                            <input type="text" name="keyword" class="form-control" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tên, địa chỉ...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quận</label>
                            <select name="district" class="form-select">
                                <option value="">-- Tất Cả --</option>
                                <?php foreach ($districts_list as $dist): ?>
                                    <option value="<?php echo $dist['id']; ?>" <?php echo $district == $dist['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dist['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Danh Mục</label>
                            <select name="category" class="form-select">
                                <option value="">-- Tất Cả --</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Giá Từ</label>
                                <input type="number" name="min_price" class="form-control" value="<?php echo htmlspecialchars($min_price); ?>" placeholder="VNĐ">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Đến</label>
                                <input type="number" name="max_price" class="form-control" value="<?php echo htmlspecialchars($max_price); ?>" placeholder="VNĐ">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-search w-100">
                            <i class="fas fa-search"></i> Tìm Kiếm
                        </button>
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="col-lg-9">
                <div class="result-count">
                    Tìm thấy <strong><?php echo $total; ?></strong> phòng
                </div>

                <?php if (count($motels) > 0): ?>
                    <div class="row">
                        <?php foreach ($motels as $motel): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="motel-card">
                                    <div class="motel-image">
                                        <i class="fas fa-image"></i>
                                        <button class="favorite-btn <?php echo in_array($motel['id'], $favorite_ids) ? 'active' : ''; ?>" onclick="toggleFavorite(<?php echo $motel['id']; ?>)">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </div>
                                    <div class="motel-body">
                                        <div class="motel-title"><?php echo htmlspecialchars($motel['title']); ?></div>
                                        <div class="motel-price"><?php echo number_format($motel['price']); ?> VNĐ</div>
                                        <div class="motel-info">
                                            <div class="motel-info-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($motel['address']); ?></div>
                                            <div class="motel-info-item"><i class="fas fa-door-open"></i> Quận: <?php echo htmlspecialchars($motel['district_name']); ?></div>
                                            <div class="motel-info-item"><i class="fas fa-eye"></i> <?php echo $motel['count_view']; ?> lượt xem</div>
                                        </div>
                                        <a href="motel-detail.php?id=<?php echo $motel['id']; ?>" class="btn-view" style="display: block; text-align: center;">
                                            Xem Chi Tiết
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Pagination">
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                        <p>Không tìm thấy phòng nào phù hợp</p>
                        <a href="search.php" class="btn btn-primary" style="margin-top: 15px;">Xóa Bộ Lọc</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleFavorite(motelId) {
            fetch('../ajax/toggle-favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ motel_id: motelId })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    event.target.closest('.favorite-btn').classList.toggle('active');
                }
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
