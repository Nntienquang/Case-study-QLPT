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

$db = new Database($conn);
$owner_id = $_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';

// --- BƯỚC MỚI: KIỂM TRA ĐỊNH DANH (KYC) ---
$userCheck = $conn->prepare("SELECT idcard_number, status FROM users WHERE id = ?");
$userCheck->bind_param("i", $owner_id);
$userCheck->execute();
$userData = $userCheck->get_result()->fetch_assoc();
$userCheck->close();

$is_verified = !empty($userData['idcard_number']);
$is_approved = ($userData['status'] === 'approved');

// Kiểm tra trạng thái truy cập của chủ phòng
$ownerStatus = new OwnerStatusMiddleware($db);
$ownerStatus->checkOwnerAccess($owner_id, 'add-listing.php');

$message = '';
$message_type = '';

// Lấy danh sách danh mục, quận, tiện ích từ DB để hiển thị form
$categories = $conn->query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$districts = $conn->query("SELECT id, name FROM districts ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$utilities_list = $conn->query("SELECT id, name FROM utilities ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Xử lý Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_verified) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (int)($_POST['price'] ?? 0);
    $area = (int)($_POST['area'] ?? 0);
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $address = trim($_POST['address'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $district_id = (int)($_POST['district_id'] ?? 0);

    // Tọa độ Bản đồ
    $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
    $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;

    // Tiện ích
    $selected_utilities = $_POST['utilities'] ?? [];
    $utilities_string = !empty($selected_utilities) ? implode(',', $selected_utilities) : '';

    $available_from = !empty($_POST['available_from']) ? $_POST['available_from'] : null;
    $service_fee = (int)($_POST['service_fee'] ?? 0);
    $deposit_months = (float)($_POST['deposit_months'] ?? 1);

    // Đánh giá chất lượng tin đăng
    $quality = ListingQuality::evaluate([
        'title' => $title, 'description' => $description, 'price' => $price, 'area' => $area,
        'address' => $address, 'category_id' => $category_id, 'district_id' => $district_id,
        'utilities' => $utilities_string, 'available_from' => $available_from,
        'service_fee' => $service_fee, 'deposit_months' => $deposit_months,
    ]);
    $health_score = $quality['score'];

    if (empty($title) || empty($price) || empty($address)) {
        $message = 'Vui lòng điền đầy đủ các thông tin bắt buộc!';
        $message_type = 'danger';
    } else {
        // Câu lệnh INSERT (17 dấu ? ứng với 17 biến trong bind_param)
        $stmt = $conn->prepare("
            INSERT INTO motels (user_id, title, description, price, area, bedrooms, bathrooms, address, category_id, district_id, utilities, available_from, service_fee, deposit_months, health_score, status, lat, lng)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)
        ");

        // Sửa lỗi ArgumentCountError: Chuỗi định dạng 17 ký tự cho 17 biến
        $stmt->bind_param("issiiiisiissididd", $owner_id, $title, $description, $price, $area, $bedrooms, $bathrooms, $address, $category_id, $district_id, $utilities_string, $available_from, $service_fee, $deposit_months, $health_score, $lat, $lng);

        if ($stmt->execute()) {
            $motel_id = $stmt->insert_id;

            // Xử lý upload hình ảnh
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../../public/uploads/motels/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $stmt_img = $conn->prepare("INSERT INTO motel_images (motel_id, image_url) VALUES (?, ?)");
                foreach ($_FILES['images']['name'] as $key => $name) {
                    if ($_FILES['images']['error'][$key] === 0) {
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_name = uniqid('motel_') . '.' . $ext;
                        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_dir . $new_name)) {
                            $image_url = 'uploads/motels/' . $new_name;
                            $stmt_img->bind_param("is", $motel_id, $image_url);
                            $stmt_img->execute();
                        }
                    }
                }
                $stmt_img->close();
            }

            // Lưu tiện ích vào bảng phụ
            if (!empty($selected_utilities)) {
                $stmt_util = $conn->prepare("INSERT INTO motel_utilities (motel_id, utility_id) VALUES (?, ?)");
                foreach ($selected_utilities as $util_name) {
                    $res = $conn->query("SELECT id FROM utilities WHERE name = '" . $conn->real_escape_string($util_name) . "'");
                    if ($res && $res->num_rows > 0) {
                        $util_id = $res->fetch_assoc()['id'];
                        $stmt_util->bind_param("ii", $motel_id, $util_id);
                        $stmt_util->execute();
                    }
                }
                $stmt_util->close();
            }

            $_SESSION['message'] = 'Đăng phòng thành công! Tin đang chờ Admin phê duyệt.';
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
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Phòng Mới - QuanLyPhongTro</title>
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
        border: 2px solid white;
    }

    #map {
        height: 350px;
        width: 100%;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .map-wrapper {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
        margin-bottom: 20px;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
                <span><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link " href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link active" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
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
                <div class="wb-section-head">
                    <h2>Đăng Phòng Cho Thuê Mới</h2>
                    <a href="listings.php" class="btn btn-outline-secondary btn-sm">Hủy & Quay lại</a>
                </div>

                <?php if (!$is_verified): ?>
                <div class="wb-card text-center py-5 border-danger">
                    <i class="fas fa-id-card fa-4x text-danger mb-3"></i>
                    <h4 class="text-danger">Yêu cầu xác minh danh tính</h4>
                    <p class="text-muted mx-auto" style="max-width: 500px;">Bạn cần cập nhật <strong>Số CCCD</strong>
                        trước khi đăng tin.</p>
                    <div class="mt-4"><a href="profile.php" class="btn btn-primary">Cập nhật hồ sơ ngay</a></div>
                </div>
                <?php else: ?>
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="wb-card p-4"
                    onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.innerHTML = 'Đang gửi...';">
                    <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-camera text-primary"></i> Hình Ảnh</h5>
                    <div class="mb-4">
                        <label class="form-label">Chọn hình ảnh *</label>
                        <input type="file" name="images[]" id="imageInput" class="form-control" multiple
                            accept="image/*" required>
                        <div class="image-preview-container" id="imagePreviewContainer"></div>
                    </div>

                    <h5 class="mb-3 border-bottom pb-2"><i class="fas fa-info-circle text-primary"></i> Thông Tin</h5>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tiêu đề *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Giá (VNĐ/tháng) *</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Danh Mục *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Loại hình --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quận/Huyện *</label>
                            <select name="district_id" class="form-select" required>
                                <option value="">-- Khu vực --</option>
                                <?php foreach ($districts as $district): ?>
                                <option value="<?php echo $district['id']; ?>">
                                    <?php echo htmlspecialchars($district['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Địa chỉ *</label>
                            <input type="text" name="address" class="form-control" required>
                        </div>
                    </div>

                    <div class="map-wrapper">
                        <label class="form-label">📍 Ghim Vị Trí</label>
                        <div id="map" class="mb-3"></div>
                        <div class="row g-2">
                            <div class="col-6"><input type="text" name="lat" id="latitude" class="form-control"
                                    placeholder="Vĩ độ" readonly></div>
                            <div class="col-6"><input type="text" name="lng" id="longitude" class="form-control"
                                    placeholder="Kinh độ" readonly></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>

                    <h5 class="mb-3 border-bottom pb-2">Tiện Nghi & Khác</h5>
                    <div class="row mb-4">
                        <?php foreach ($utilities_list as $util): ?>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="utilities[]"
                                    value="<?php echo htmlspecialchars($util['name']); ?>"
                                    id="u<?php echo $util['id']; ?>">
                                <label class="form-check-label"
                                    for="u<?php echo $util['id']; ?>"><?php echo htmlspecialchars($util['name']); ?></label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="text-end"><button type="submit" class="btn btn-primary px-5">Gửi Tin Duyệt</button>
                    </div>
                </form>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
    let selectedFiles = [];
    document.getElementById('imageInput').addEventListener('change', function(e) {
        Array.from(this.files).forEach(file => {
            if (!selectedFiles.some(f => f.name === file.name)) selectedFiles.push(file);
        });
        updatePreview();
    });

    function updatePreview() {
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '';
        const dt = new DataTransfer();
        selectedFiles.forEach((file, index) => {
            dt.items.add(file);
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'image-preview-wrapper';
                div.innerHTML =
                    `<img src="${e.target.result}" class="image-preview"><span class="remove-img-btn" onclick="removeImg(${index})">&times;</span>`;
                container.appendChild(div);
            }
            reader.readAsDataURL(file);
        });
        document.getElementById('imageInput').files = dt.files;
    }

    function removeImg(i) {
        selectedFiles.splice(i, 1);
        updatePreview();
    }

    const map = L.map('map').setView([18.679585, 105.681335], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    let marker;
    map.on('click', e => {
        if (marker) map.removeLayer(marker);
        marker = L.marker(e.latlng).addTo(map);
        document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
    });
    </script>
</body>

</html>