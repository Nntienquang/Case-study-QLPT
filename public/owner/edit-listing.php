<?php
@require_once '../../config/database.php';
@require_once '../../config/constants.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$owner_id = $_SESSION['user_id'];
$motel_id = (int)($_GET['id'] ?? 0);
$message = '';
$message_type = '';

// Get motel data
$stmt = $db->prepare("SELECT * FROM motels WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $motel_id, $owner_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$motel) {
    header('Location: listings.php');
    exit;
}

// Get categories and districts
$stmt = $db->prepare("SELECT id, name FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare("SELECT id, name FROM districts ORDER BY name");
$stmt->execute();
$districts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = (int)($_POST['price'] ?? 0);
    $area = (float)($_POST['area'] ?? 0);
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $address = $_POST['address'] ?? '';
    $category_id = (int)($_POST['category_id'] ?? 0);
    $district_id = (int)($_POST['district_id'] ?? 0);
    $utilities = isset($_POST['utilities']) ? implode(',', $_POST['utilities']) : '';

    if (empty($title) || empty($price) || empty($address)) {
        $message = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare("
            UPDATE motels
            SET title = ?, description = ?, price = ?, area = ?, bedrooms = ?, bathrooms = ?, address = ?, category_id = ?, district_id = ?, utilities = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ssiiddsisii", $title, $description, $price, $area, $bedrooms, $bathrooms, $address, $category_id, $district_id, $utilities, $motel_id, $owner_id);
        
        if ($stmt->execute()) {
            $message = 'Phòng đã cập nhật thành công!';
            $message_type = 'success';
            // Refresh motel data
            $stmt = $db->prepare("SELECT * FROM motels WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $motel_id, $owner_id);
            $stmt->execute();
            $motel = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $message = 'Lỗi: ' . $stmt->error;
            $message_type = 'danger';
        }
        $stmt->close();
    }
}

$utilities_array = array_filter(explode(',', $motel['utilities']));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Phòng - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .sidebar { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .sidebar a { display: block; padding: 12px 15px; margin-bottom: 8px; border-radius: 6px; color: #666; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #f0f0f0; color: #667eea; }
        .main-content { padding: 30px; }
        .form-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-section { margin-bottom: 30px; }
        .form-section h5 { font-weight: 700; color: #333; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-control, .form-select { border-radius: 6px; border: 1px solid #ddd; }
        .form-control:focus, .form-select:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .form-label { font-weight: 600; color: #333; margin-bottom: 8px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
        .utilities-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
        .utility-check { display: flex; align-items: center; }
        .utility-check input { cursor: pointer; }
        .utility-check label { margin: 0 0 0 8px; cursor: pointer; }
        .alert { border-radius: 12px; }
    </style>
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
        <div class="row">
            <div class="col-lg-3">
                <div class="sidebar">
                    <h5>Menu</h5>
                    <a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                    <a href="listings.php" class="active"><i class="fas fa-list"></i> Phòng của Tôi</a>
                    <a href="bookings.php"><i class="fas fa-calendar"></i> Đơn Đặt Phòng</a>
                    <a href="revenue.php"><i class="fas fa-chart-bar"></i> Doanh Thu</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Hồ Sơ</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Đăng Xuất</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-edit"></i> Chỉnh Sửa Phòng
                    </h1>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="form-card">
                        <div class="form-section">
                            <h5><i class="fas fa-info-circle"></i> Thông Tin Cơ Bản</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tên Phòng *</label>
                                    <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($motel['title']); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Giá Thuê (VNĐ/tháng) *</label>
                                    <input type="number" name="price" class="form-control" required value="<?php echo $motel['price']; ?>">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô Tả Chi Tiết</label>
                                <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($motel['description']); ?></textarea>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5><i class="fas fa-map-marker-alt"></i> Địa Điểm</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Quận *</label>
                                    <select name="district_id" class="form-select" required>
                                        <option value="">-- Chọn Quận --</option>
                                        <?php foreach ($districts as $district): ?>
                                            <option value="<?php echo $district['id']; ?>" <?php echo $motel['district_id'] == $district['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($district['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Danh Mục *</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">-- Chọn Danh Mục --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $motel['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Địa Chỉ Chi Tiết *</label>
                                <input type="text" name="address" class="form-control" required value="<?php echo htmlspecialchars($motel['address']); ?>">
                            </div>
                        </div>

                        <div class="form-section">
                            <h5><i class="fas fa-ruler-combined"></i> Thông Tin Phòng</h5>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Diện Tích (m²)</label>
                                    <input type="number" step="0.01" name="area" class="form-control" value="<?php echo $motel['area']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Phòng Ngủ</label>
                                    <input type="number" name="bedrooms" class="form-control" value="<?php echo $motel['bedrooms']; ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Phòng Tắm</label>
                                    <input type="number" name="bathrooms" class="form-control" value="<?php echo $motel['bathrooms']; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-section">
                            <h5><i class="fas fa-star"></i> Tiện Nghi</h5>
                            <div class="utilities-grid">
                                <div class="utility-check">
                                    <input type="checkbox" name="utilities" value="wifi" id="util_wifi" <?php echo in_array('wifi', $utilities_array) ? 'checked' : ''; ?>>
                                    <label for="util_wifi">Wi-Fi</label>
                                </div>
                                <div class="utility-check">
                                    <input type="checkbox" name="utilities" value="air_conditioner" id="util_ac" <?php echo in_array('air_conditioner', $utilities_array) ? 'checked' : ''; ?>>
                                    <label for="util_ac">Điều Hòa</label>
                                </div>
                                <div class="utility-check">
                                    <input type="checkbox" name="utilities" value="water_heater" id="util_water" <?php echo in_array('water_heater', $utilities_array) ? 'checked' : ''; ?>>
                                    <label for="util_water">Nước Nóng</label>
                                </div>
                                <div class="utility-check">
                                    <input type="checkbox" name="utilities" value="kitchen" id="util_kitchen" <?php echo in_array('kitchen', $utilities_array) ? 'checked' : ''; ?>>
                                    <label for="util_kitchen">Bếp</label>
                                </div>
                                <div class="utility-check">
                                    <input type="checkbox" name="utilities" value="washing_machine" id="util_wash" <?php echo in_array('washing_machine', $utilities_array) ? 'checked' : ''; ?>>
                                    <label for="util_wash">Máy Giặt</label>
                                </div>
                                <div class="utility-check">
                                    <input type="checkbox" name="utilities" value="parking" id="util_parking" <?php echo in_array('parking', $utilities_array) ? 'checked' : ''; ?>>
                                    <label for="util_parking">Chỗ Đỗ Xe</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Cập Nhật
                            </button>
                            <a href="listings.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Quay Lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
