<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';
@require_once '../../core/OwnerStatusMiddleware.php';
@require_once '../../core/ListingQuality.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

// Chuyển việc gán biến này lên trước để fix lỗi undefined variable
$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';

function owner_edit_column_exists(mysqli $conn, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $conn->prepare('
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ');
    $stmt->bind_param('ss', $table, $column);
    $stmt->execute();
    $cache[$key] = ((int)($stmt->get_result()->fetch_assoc()['total'] ?? 0)) > 0;
    $stmt->close();

    return $cache[$key];
}

function owner_edit_sync_district_id(mysqli $conn, string $districtName, int $fallbackId = 0, string $provinceCode = '', string $districtCode = ''): int
{
    $districtName = trim($districtName);
    if ($districtName === '') {
        return $fallbackId;
    }

    $stmt = $conn->prepare('SELECT id FROM districts WHERE name = ? LIMIT 1');
    $stmt->bind_param('s', $districtName);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        return (int)$row['id'];
    }

    if (owner_edit_column_exists($conn, 'districts', 'province_code') && owner_edit_column_exists($conn, 'districts', 'district_code')) {
        $stmt = $conn->prepare('INSERT INTO districts (name, province_code, district_code) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $districtName, $provinceCode, $districtCode);
    } else {
        $stmt = $conn->prepare('INSERT INTO districts (name) VALUES (?)');
        $stmt->bind_param('s', $districtName);
    }
    $stmt->execute();
    $newId = (int)$stmt->insert_id;
    $stmt->close();

    return $newId > 0 ? $newId : $fallbackId;
}

function owner_edit_update_standard_address(mysqli $conn, int $motelId, array $data): void
{
    if (!owner_edit_column_exists($conn, 'motels', 'province_code')) {
        return;
    }

    $stmt = $conn->prepare('
        UPDATE motels
        SET province_code = ?, province_name = ?, district_code = ?, district_name = ?,
            ward_code = ?, ward_name = ?, street_address = ?, address_api_source = ?
        WHERE id = ?
    ');
    $source = 'provinces.open-api.vn';
    $stmt->bind_param(
        'ssssssssi',
        $data['province_code'],
        $data['province_name'],
        $data['district_code'],
        $data['district_name'],
        $data['ward_code'],
        $data['ward_name'],
        $data['street_address'],
        $source,
        $motelId
    );
    $stmt->execute();
    $stmt->close();
}

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);

$ownerStatus = new OwnerStatusMiddleware($db);
$ownerStatus->checkOwnerAccess($owner_id, 'edit-listing.php');

$motel_id = (int)($_GET['id'] ?? 0);
$message = '';
$message_type = '';

// Lấy thông tin phòng hiện tại
$stmt = $db->prepare("SELECT * FROM motels WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $motel_id, $owner_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$motel) {
    header('Location: listings.php');
    exit;
}

// Lấy danh sách hình ảnh hiện tại
$existing_images = $conn->query("SELECT id, image_url FROM motel_images WHERE motel_id = $motel_id")->fetch_all(MYSQLI_ASSOC);

// Lấy danh mục, quận, tiện ích
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$districts = $conn->query("SELECT id, name FROM districts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$utilities_list = $conn->query("SELECT id, name FROM utilities ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$utilities_array = array_filter(explode(',', $motel['utilities']));

// Xử lý Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $area = (int)($_POST['area'] ?? 0);
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $province_code = trim($_POST['province_code'] ?? '');
    $province_name = trim($_POST['province_name'] ?? '');
    $district_code = trim($_POST['district_code'] ?? '');
    $district_name = trim($_POST['district_name'] ?? '');
    $ward_code = trim($_POST['ward_code'] ?? '');
    $ward_name = trim($_POST['ward_name'] ?? '');
    $street_address = trim($_POST['street_address'] ?? '');
    $address = trim($_POST['address'] ?? '');
    if ($address === '') {
        $address = implode(', ', array_filter([$street_address, $ward_name, $district_name, $province_name]));
    }
    $category_id = (int)($_POST['category_id'] ?? 0);
    $district_id = owner_edit_sync_district_id($conn, $district_name, (int)($_POST['district_id'] ?? 0), $province_code, $district_code);

    // Tọa độ Bản đồ
    $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;

    $selected_utilities = $_POST['utilities'] ?? [];
    $utilities_string = !empty($selected_utilities) ? implode(',', $selected_utilities) : '';

    $available_from = !empty($_POST['available_from']) ? $_POST['available_from'] : null;
    $service_fee = (int)($_POST['service_fee'] ?? 0);
    $deposit_months = (float)($_POST['deposit_months'] ?? 1);

    // Tính điểm chất lượng
    $quality = ListingQuality::evaluate([
        'title' => $title,
        'description' => $description,
        'price' => $price,
        'area' => $area,
        'address' => $address,
        'category_id' => $category_id,
        'district_id' => $district_id,
        'utilities' => $utilities_string,
        'available_from' => $available_from,
        'service_fee' => $service_fee,
        'deposit_months' => $deposit_months,
    ]);
    $health_score = $quality['score'];

    if (empty($title) || empty($price) || empty($address)) {
        $message = 'Vui lòng điền đầy đủ thông tin bắt buộc (*)!';
        $message_type = 'danger';
    } else {
        // Cập nhật thông tin phòng
        $stmt = $db->prepare("
            UPDATE motels
            SET title=?, description=?, price=?, area=?, bedrooms=?, bathrooms=?, address=?, 
                category_id=?, district_id=?, utilities=?, available_from=?, service_fee=?, 
                deposit_months=?, health_score=?, lat=?, lng=?
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param("ssiiiisiissididdii", $title, $description, $price, $area, $bedrooms, $bathrooms, $address, $category_id, $district_id, $utilities_string, $available_from, $service_fee, $deposit_months, $health_score, $lat, $lng, $motel_id, $owner_id);

        if ($stmt->execute()) {
            owner_edit_update_standard_address($conn, $motel_id, [
                'province_code' => $province_code,
                'province_name' => $province_name,
                'district_code' => $district_code,
                'district_name' => $district_name,
                'ward_code' => $ward_code,
                'ward_name' => $ward_name,
                'street_address' => $street_address,
            ]);

            // Xóa ảnh cũ nếu người dùng chọn xóa
            if (!empty($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $del_img_id) {
                    $del_img_id = (int)$del_img_id;
                    $res = $conn->query("SELECT image_url FROM motel_images WHERE id = $del_img_id AND motel_id = $motel_id");
                    if ($res && $res->num_rows > 0) {
                        $img_path = '../../public/' . $res->fetch_assoc()['image_url'];
                        if (file_exists($img_path)) unlink($img_path);
                        $conn->query("DELETE FROM motel_images WHERE id = $del_img_id");
                    }
                }
            }

            // Upload ảnh mới
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../../public/uploads/motels/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

                $stmt_img = $conn->prepare("INSERT INTO motel_images (motel_id, image_url) VALUES (?, ?)");
                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] === 0) {
                        $tmp_name = $_FILES['images']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_name = uniqid('motel_') . '.' . $ext;
                        if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                            $image_url = 'uploads/motels/' . $new_name;
                            $stmt_img->bind_param("is", $motel_id, $image_url);
                            $stmt_img->execute();
                        }
                    }
                }
                $stmt_img->close();
            }

            // Cập nhật lại tiện ích trong bảng motel_utilities
            $conn->query("DELETE FROM motel_utilities WHERE motel_id = $motel_id");
            if (!empty($selected_utilities)) {
                $stmt_util = $conn->prepare("INSERT INTO motel_utilities (motel_id, utility_id) VALUES (?, ?)");
                foreach ($selected_utilities as $util_name) {
                    $util_query = $conn->query("SELECT id FROM utilities WHERE name = '" . $conn->real_escape_string($util_name) . "'");
                    if ($util_query && $util_query->num_rows > 0) {
                        $util_id = $util_query->fetch_assoc()['id'];
                        $stmt_util->bind_param("ii", $motel_id, $util_id);
                        $stmt_util->execute();
                    }
                }
                $stmt_util->close();
            }

            $_SESSION['message'] = 'Phòng đã cập nhật thành công!';
            $_SESSION['message_type'] = 'success';
            header('Location: listings.php');
            exit;
        } else {
            $message = 'Lỗi hệ thống: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Phòng - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .image-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }

    .image-preview-wrapper {
        position: relative;
        display: inline-block;
    }

    .image-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ddd;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .remove-img-btn {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        justify-content: center;
        align-items: center;
        cursor: pointer;
        font-size: 14px;
        font-weight: bold;
        border: 2px solid white;
        transition: 0.2s;
    }

    .remove-img-btn:hover {
        background: #c82333;
        transform: scale(1.1);
    }

    #map {
        height: 350px;
        width: 100%;
        border-radius: 8px;
        border: 1px solid #ddd;
        z-index: 1;
    }

    .map-wrapper {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 20px;
    }

    /* Giao diện chọn ảnh xóa */
    .existing-img-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .existing-img-item {
        position: relative;
    }

    .existing-img-label {
        display: block;
        cursor: pointer;
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 2px solid #ddd;
        transition: all 0.2s ease-in-out;
    }

    .existing-img-label img {
        width: 110px;
        height: 110px;
        object-fit: cover;
        display: block;
        transition: 0.3s;
    }

    .existing-img-label .overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(220, 53, 69, 0.85);
        color: white;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: 0.2s;
    }

    .existing-img-label:hover {
        border-color: #dc3545;
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.2);
    }

    /* Hiệu ứng khi được chọn (Checked) */
    .delete-img-check:checked+.existing-img-label {
        border-color: #dc3545;
        transform: scale(0.92);
        box-shadow: none;
    }

    .delete-img-check:checked+.existing-img-label img {
        filter: grayscale(80%);
    }

    .delete-img-check:checked+.existing-img-label .overlay {
        opacity: 1;
    }

    .delete-img-check {
        display: none;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner d-flex justify-content-between align-items-center">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user d-flex align-items-center gap-3">
                <span class="fw-medium"><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link active" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <div class="wb-section-head d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1"><i class="fas fa-edit text-primary me-2"></i> Chỉnh Sửa Phòng</h2>
                    </div>
                    <a href="listings.php" class="btn btn-outline-secondary btn-sm">Hủy & Quay lại</a>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show border-0 shadow-sm">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="wb-card p-4"
                    onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = '<i class=\'fas fa-spinner fa-spin me-1\'></i> Đang lưu...';">

                    <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-camera text-primary"></i> Hình Ảnh Phòng</h5>

                    <?php if (count($existing_images) > 0): ?>
                    <div class="mb-4">
                        <label class="form-label text-muted d-block mb-3">Ảnh hiện tại (Nhấp vào ảnh để đánh dấu
                            xóa):</label>
                        <div class="existing-img-container">
                            <?php foreach ($existing_images as $img): ?>
                            <div class="existing-img-item">
                                <input type="checkbox" class="delete-img-check" name="delete_images[]"
                                    value="<?php echo $img['id']; ?>" id="del_img_<?php echo $img['id']; ?>"
                                    autocomplete="off">
                                <label class="existing-img-label" for="del_img_<?php echo $img['id']; ?>"
                                    title="Chọn để xóa ảnh này">
                                    <img src="../<?php echo htmlspecialchars($img['image_url']); ?>" alt="Ảnh phòng">
                                    <div class="overlay">
                                        <i class="fas fa-trash-alt fs-4 mb-1"></i>
                                        <span class="small fw-bold">Sẽ bị xóa</span>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">Thêm ảnh mới</label>
                            <input type="file" name="images[]" id="imageInput" class="form-control" multiple
                                accept="image/*">
                            <div class="image-preview-container" id="imagePreviewContainer"></div>
                        </div>
                    </div>

                    <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-info-circle text-primary"></i> Thông Tin Cơ Bản
                    </h5>
                    <div class="row mb-4">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tiêu đề tin đăng *</label>
                            <input type="text" name="title" class="form-control" required
                                value="<?php echo htmlspecialchars($motel['title']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giá Thuê (VNĐ/tháng) *</label>
                            <input type="number" name="price" class="form-control" required
                                value="<?php echo $motel['price']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Danh Mục *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Chọn Loại Hình --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"
                                    <?php echo $motel['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3 d-none">
                            <label class="form-label">Quận/Huyện *</label>
                            <select name="district_id" class="form-select d-none" aria-hidden="true">
                                <option value="">-- Chọn Khu Vực --</option>
                                <?php foreach ($districts as $district): ?>
                                <option value="<?php echo $district['id']; ?>"
                                    <?php echo $motel['district_id'] == $district['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($district['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Địa Chỉ Chi Tiết *</label>
                            <input type="hidden" name="address" data-address-full value="<?php echo htmlspecialchars($motel['address']); ?>">
                            <div data-address-picker>
                                <input type="hidden" name="province_name" data-address-province-name value="<?php echo htmlspecialchars($motel['province_name'] ?? ''); ?>">
                                <input type="hidden" name="district_name" data-address-district-name value="<?php echo htmlspecialchars($motel['district_name'] ?? ''); ?>">
                                <input type="hidden" name="ward_name" data-address-ward-name value="<?php echo htmlspecialchars($motel['ward_name'] ?? ''); ?>">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tỉnh/Thành phố *</label>
                                        <select name="province_code" class="form-select" data-address-province data-selected="<?php echo htmlspecialchars($motel['province_code'] ?? ''); ?>" required>
                                            <option value="">-- Chọn tỉnh/thành --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Quận/Huyện *</label>
                                        <select name="district_code" class="form-select" data-address-district data-selected="<?php echo htmlspecialchars($motel['district_code'] ?? ''); ?>" required disabled>
                                            <option value="">-- Chọn quận/huyện --</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Phường/Xã *</label>
                                        <select name="ward_code" class="form-select" data-address-ward data-selected="<?php echo htmlspecialchars($motel['ward_code'] ?? ''); ?>" required disabled>
                                            <option value="">-- Chọn phường/xã --</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Số nhà, tên đường *</label>
                                        <input type="text" name="street_address" class="form-control" data-address-street required value="<?php echo htmlspecialchars($motel['street_address'] ?? $motel['address']); ?>">
                                        <div class="form-text" data-address-status></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <div class="map-wrapper">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0"><strong>📍 Vị Trí Bản Đồ</strong></label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-success" id="btnGetLocation">
                                            <i class="fas fa-location-crosshairs"></i> Vị trí hiện tại
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="resetMap()">
                                            <i class="fas fa-rotate-right"></i> Phục hồi vị trí cũ
                                        </button>
                                    </div>
                                </div>
                                <div id="map" class="mb-3"></div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text" name="lat" id="latitude" class="form-control bg-white"
                                            placeholder="Vĩ độ" readonly value="<?php echo $motel['lat']; ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="lng" id="longitude" class="form-control bg-white"
                                            placeholder="Kinh độ" readonly value="<?php echo $motel['lng']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 mb-3 mt-2">
                            <label class="form-label">Mô Tả Chi Tiết</label>
                            <textarea name="description" class="form-control"
                                rows="5"><?php echo htmlspecialchars($motel['description']); ?></textarea>
                        </div>
                    </div>

                    <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-ruler-combined text-primary"></i> Thông Số &
                        Chi Phí</h5>
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Diện Tích (m²)</label>
                            <input type="number" name="area" class="form-control" value="<?php echo $motel['area']; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Phòng Ngủ</label>
                            <input type="number" name="bedrooms" class="form-control" min="0"
                                value="<?php echo $motel['bedrooms']; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Phòng Tắm</label>
                            <input type="number" name="bathrooms" class="form-control" min="0"
                                value="<?php echo $motel['bathrooms']; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Trống Từ Ngày</label>
                            <input type="date" name="available_from" class="form-control"
                                value="<?php echo htmlspecialchars($motel['available_from'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phí Dịch Vụ / Tháng (VNĐ)</label>
                            <input type="number" name="service_fee" class="form-control" min="0"
                                value="<?php echo (int)($motel['service_fee'] ?? 0); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Số Tháng Cọc</label>
                            <input type="number" step="0.5" min="0" name="deposit_months" class="form-control"
                                value="<?php echo htmlspecialchars($motel['deposit_months'] ?? '1'); ?>">
                        </div>
                    </div>

                    <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-couch text-primary"></i> Tiện Nghi Có Sẵn</h5>
                    <div class="row mb-4">
                        <?php foreach ($utilities_list as $util): ?>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="utilities[]"
                                    value="<?php echo htmlspecialchars($util['name']); ?>"
                                    id="util_<?php echo $util['id']; ?>"
                                    <?php echo in_array($util['name'], $utilities_array) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="util_<?php echo $util['id']; ?>">
                                    <?php echo htmlspecialchars($util['name']); ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="listings.php" class="btn btn-light border">Hủy</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Lưu Thay
                            Đổi</button>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="../assets/js/vn-address-picker.js"></script>

    <script>
    // --- 1. XỬ LÝ PREVIEW HÌNH ẢNH MỚI (CỘNG DỒN) ---
    let selectedFiles = [];
    document.getElementById('imageInput').addEventListener('change', function(e) {
        const newFiles = Array.from(this.files);
        newFiles.forEach(file => {
            const isDuplicate = selectedFiles.some(f => f.name === file.name && f.size === file.size);
            if (!isDuplicate && file.type.startsWith('image/')) selectedFiles.push(file);
        });
        updateImagePreviewAndInput();
    });

    function updateImagePreviewAndInput() {
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '';
        const dataTransfer = new DataTransfer();

        selectedFiles.forEach((file, index) => {
            dataTransfer.items.add(file);
            const reader = new FileReader();
            reader.onload = function(e) {
                const wrapper = document.createElement('div');
                wrapper.className = 'image-preview-wrapper';

                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'image-preview';

                const removeBtn = document.createElement('span');
                removeBtn.className = 'remove-img-btn';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = function() {
                    selectedFiles.splice(index, 1);
                    updateImagePreviewAndInput();
                };

                wrapper.appendChild(img);
                wrapper.appendChild(removeBtn);
                container.appendChild(wrapper);
            }
            reader.readAsDataURL(file);
        });
        document.getElementById('imageInput').files = dataTransfer.files;
    }

    // --- 2. XỬ LÝ BẢN ĐỒ LEAFLET ---
    let map = null;
    let currentMarker = null;

    // Lấy tọa độ cũ từ PHP, nếu không có thì mặc định về TP. Vinh
    const savedLat = parseFloat("<?php echo $motel['lat']; ?>");
    const savedLng = parseFloat("<?php echo $motel['lng']; ?>");
    const defaultLat = !isNaN(savedLat) ? savedLat : 18.679585;
    const defaultLng = !isNaN(savedLng) ? savedLng : 105.681335;
    const defaultZoom = 15;

    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });

    function initMap() {
        map = L.map('map').setView([defaultLat, defaultLng], defaultZoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Cắm ghim vị trí ban đầu
        if (!isNaN(savedLat) && !isNaN(savedLng)) {
            setMarker(defaultLat, defaultLng);
        }

        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });

        document.getElementById('btnGetLocation').addEventListener('click', getLocationAndSetMarker);
    }

    function setMarker(lat, lng) {
        if (currentMarker) map.removeLayer(currentMarker);
        currentMarker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);

        document.getElementById('latitude').value = lat.toFixed(8);
        document.getElementById('longitude').value = lng.toFixed(8);

        currentMarker.on('dragend', function(e) {
            const newLat = e.target.getLatLng().lat;
            const newLng = e.target.getLatLng().lng;
            document.getElementById('latitude').value = newLat.toFixed(8);
            document.getElementById('longitude').value = newLng.toFixed(8);
        });
    }

    function getLocationAndSetMarker() {
        const btn = document.getElementById('btnGetLocation');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang định vị...';

        if ('geolocation' in navigator) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    setMarker(lat, lng);
                    map.setView([lat, lng], 16);
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                },
                function(error) {
                    alert('⚠️ Không thể lấy vị trí hiện tại.');
                    btn.disabled = false;
                    btn.innerHTML = originalHTML;
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            alert('⚠️ Trình duyệt không hỗ trợ định vị!');
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    }

    function resetMap() {
        if (!isNaN(savedLat) && !isNaN(savedLng)) {
            setMarker(savedLat, savedLng);
            map.setView([savedLat, savedLng], defaultZoom);
        } else {
            if (currentMarker) {
                map.removeLayer(currentMarker);
                currentMarker = null;
            }
            document.getElementById('latitude').value = '';
            document.getElementById('longitude').value = '';
            map.setView([18.679585, 105.681335], 13);
        }
    }
    </script>
</body>

</html>
