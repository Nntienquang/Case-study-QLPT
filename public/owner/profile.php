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

$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
$message = '';
$message_type = '';

// Lấy thông tin user hiện tại
$stmt = $db->prepare("SELECT id, name, email, phone, address, idcard_number, id_card_front, id_card_back, bank_name, bank_account_no, bank_account_name, status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Xử lý cập nhật hồ sơ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $idcard_number = trim($_POST['idcard_number'] ?? '');
    
    // Lấy thông tin ngân hàng
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account_no = trim($_POST['bank_account_no'] ?? '');
    $bank_account_name = trim($_POST['bank_account_name'] ?? '');

    if (empty($name)) {
<<<<<<< HEAD
        $message = 'Vui lòng nhập tên đầy đủ!';
=======
        $message = 'Vui lòng nhập tên!';
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
        $message_type = 'danger';
    } else {
        $upload_dir = '../../public/uploads/kyc/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $id_card_front = $user['id_card_front'];
        $id_card_back = $user['id_card_back'];

        // Upload mặt trước
        if (!empty($_FILES['id_card_front']['name'])) {
            $ext = pathinfo($_FILES['id_card_front']['name'], PATHINFO_EXTENSION);
            $new_name = 'front_' . $owner_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['id_card_front']['tmp_name'], $upload_dir . $new_name)) {
                $id_card_front = 'uploads/kyc/' . $new_name;
            }
        }

        // Upload mặt sau
        if (!empty($_FILES['id_card_back']['name'])) {
            $ext = pathinfo($_FILES['id_card_back']['name'], PATHINFO_EXTENSION);
            $new_name = 'back_' . $owner_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['id_card_back']['tmp_name'], $upload_dir . $new_name)) {
                $id_card_back = 'uploads/kyc/' . $new_name;
            }
        }

        // Cập nhật Database
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, idcard_number = ?, id_card_front = ?, id_card_back = ?, bank_name = ?, bank_account_no = ?, bank_account_name = ? WHERE id = ?");
        $stmt->bind_param("sssssssssi", $name, $phone, $address, $idcard_number, $id_card_front, $id_card_back, $bank_name, $bank_account_no, $bank_account_name, $owner_id);

        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
<<<<<<< HEAD
            $message = 'Hồ sơ và thông tin thanh toán đã được cập nhật thành công!';
=======
            $message = 'Hồ sơ cập nhật thành công!';
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
            $message_type = 'success';

            // Refresh lại dữ liệu hiển thị
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $owner_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
<<<<<<< HEAD
            $message = 'Lỗi cập nhật cơ sở dữ liệu!';
=======
            $message = 'Lỗi cập nhật!';
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
            $message_type = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Hồ Sơ Chủ Nhà - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
=======
    <title>Hồ sơ - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 30px; }
        .profile-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .form-label { font-weight: 600; color: #333; }
        .form-control { border-radius: 6px; border: 1px solid #ddd; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
        .info-item { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
        .info-label { font-weight: 600; color: #333; }
        .info-value { color: #666; margin-top: 5px; }
    </style>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .kyc-img-preview {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px dashed #ddd;
        margin-top: 10px;
    }

    .verification-status {
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .status-unverified {
        background: #fff5f5;
        border: 1px solid #feb2b2;
        color: #c53030;
    }

    .status-verified {
        background: #f0fff4;
        border: 1px solid #9ae6b4;
        color: #276749;
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
<<<<<<< HEAD
            <div class="wb-user">
                <span><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
=======
        </div>
    </nav>

    <div class="container-lg" style="padding: 30px 0;">
        <div class="row">
            <div class="col-lg-3">
                <?php
                $ownerNavActive = 'profile';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
            </div>
        </div>
    </header>

<<<<<<< HEAD
    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>
=======
            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-user"></i> Hồ sơ chủ nhà 
                    </h1>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>
                <!-- <a class="wb-side-link" href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link active" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <div class="wb-section-head d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-user-circle text-primary me-2"></i> Hồ Sơ Cá Nhân</h2>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show border-0 shadow-sm">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <?php if (empty($user['idcard_number'])): ?>
                <div class="verification-status status-unverified">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <div>
                        <strong>Tài khoản chưa định danh!</strong><br>
                        Bạn cần cập nhật số CCCD và ảnh chụp để có thể đăng tin cho thuê phòng.
                    </div>
                </div>
                <?php else: ?>
                <div class="verification-status status-verified">
                    <i class="fas fa-check-circle fa-2x"></i>
                    <div>
                        <strong>Đã cập nhật thông tin định danh!</strong><br>
                        Hệ thống đã lưu trữ thông tin của bạn. Bạn có thể cập nhật lại nếu cần thiết.
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
<<<<<<< HEAD
                        <div class="col-lg-7">
                            <div class="wb-card p-4 mb-4">
                                <h5 class="mb-4 border-bottom pb-2">Thông Tin Liên Hệ</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Họ và Tên *</label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email (Liên kết tài khoản)</label>
                                    <input type="email" class="form-control bg-light"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số Điện Thoại</label>
                                    <input type="tel" name="phone" class="form-control"
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                        placeholder="Ví dụ: 0912345678">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Địa Chỉ Liên Hệ</label>
                                    <textarea name="address" class="form-control" rows="3"
                                        placeholder="Địa chỉ thường trú hoặc tạm trú"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="wb-card p-4 mb-4 border-success" style="border-top: 4px solid #198754;">
                                <h5 class="mb-4 border-bottom pb-2 text-success"><i
                                        class="fas fa-building-columns me-2"></i>Tài Khoản Nhận Tiền</h5>
                                <div class="alert alert-light border small text-muted mb-4">
                                    <i class="fas fa-info-circle me-1"></i> Thông tin này sẽ được dùng để nền tảng
                                    chuyển khoản tiền cọc và doanh thu rút từ ví về cho bạn.
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ngân hàng</label>
                                    <input type="text" name="bank_name" class="form-control"
                                        value="<?php echo htmlspecialchars($user['bank_name'] ?? ''); ?>"
                                        placeholder="VD: Vietcombank, Techcombank, MB Bank...">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Số tài khoản</label>
                                        <input type="text" name="bank_account_no" class="form-control"
                                            value="<?php echo htmlspecialchars($user['bank_account_no'] ?? ''); ?>"
                                            placeholder="Nhập số tài khoản">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Tên chủ tài khoản</label>
                                        <input type="text" name="bank_account_name" class="form-control"
                                            value="<?php echo htmlspecialchars($user['bank_account_name'] ?? ''); ?>"
                                            placeholder="VD: NGUYEN VAN A">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="wb-card p-4 mb-4">
                                <h5 class="mb-4 border-bottom pb-2">Xác Minh Danh Tính (KYC)</h5>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Số CCCD / Định danh *</label>
                                    <input type="text" name="idcard_number" class="form-control"
                                        value="<?php echo htmlspecialchars($user['idcard_number'] ?? ''); ?>"
                                        placeholder="Nhập 12 số trên thẻ CCCD">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Ảnh mặt trước CCCD</label>
                                    <input type="file" name="id_card_front" class="form-control form-control-sm"
                                        accept="image/*" onchange="previewImage(this, 'preview_front')">
                                    <div id="container_front">
                                        <?php if (!empty($user['id_card_front'])): ?>
                                        <img src="../<?php echo htmlspecialchars($user['id_card_front']); ?>"
                                            id="preview_front" class="kyc-img-preview">
                                        <?php else: ?>
                                        <div id="preview_front_placeholder"
                                            class="kyc-img-preview d-flex align-items-center justify-content-center bg-light text-muted small">
                                            Chưa có ảnh</div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Ảnh mặt sau CCCD</label>
                                    <input type="file" name="id_card_back" class="form-control form-control-sm"
                                        accept="image/*" onchange="previewImage(this, 'preview_back')">
                                    <div id="container_back">
                                        <?php if (!empty($user['id_card_back'])): ?>
                                        <img src="../<?php echo htmlspecialchars($user['id_card_back']); ?>"
                                            id="preview_back" class="kyc-img-preview">
                                        <?php else: ?>
                                        <div id="preview_back_placeholder"
                                            class="kyc-img-preview d-flex align-items-center justify-content-center bg-light text-muted small">
                                            Chưa có ảnh</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-1"></i> Lưu Tất Cả Thay Đổi
                                </button>
                                <div class="text-center mt-2 small text-muted">
                                    <i class="fas fa-shield-halved text-success me-1"></i> Dữ liệu của bạn được mã hóa
                                    bảo mật
=======
                        <div class="col-lg-8">
                            <div class="profile-card">
                                <h5 style="font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                    Thông tin cơ bản
                                </h5>

                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Tên đầy đủ</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Email (Không thể thay đổi)</label>
                                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="0912345678">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <textarea name="address" class="form-control" rows="3" placeholder="Địa chỉ của bạn"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Lưu thay đổi
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="profile-card">
                                <h5 style="font-weight: 700; margin-bottom: 20px;">Thông tin tài khoản</h5>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-user"></i> Vai trò</div>
                                    <div class="info-value">Chủ nhà / Chủ phòng trọ</div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-calendar"></i> Ngày tạo</div>
                                    <div class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label"><i class="fas fa-shield-alt"></i> Trạng thái</div>
                                    <div class="info-value"><span class="badge bg-success">Hoạt động</span></div>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function previewImage(input, previewId) {
        const file = input.files[0];
        const previewContainer = document.getElementById(previewId + '_placeholder');
        const parent = input.parentElement;

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                let img = document.getElementById(previewId);

                if (!img) {
                    if (previewContainer) previewContainer.remove();
                    img = document.createElement('img');
                    img.id = previewId;
                    img.className = 'kyc-img-preview';
                    input.parentNode.appendChild(img);
                }

                img.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }
    </script>
</body>

</html>