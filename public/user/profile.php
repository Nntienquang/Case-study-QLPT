<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../components/PublicNav.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Lấy thông tin user hiện tại (thêm trường avatar và password để xử lý)
$stmt = $db->prepare("SELECT id, name, email, phone, address, avatar, password, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // LƯU Ý: ĐẢM BẢO BẠN ĐÃ TẠO THƯ MỤC NÀY: public/uploads/avatars/
    $upload_dir = '../uploads/avatars/'; 
    
    // XỬ LÝ 1: CẬP NHẬT THÔNG TIN & ẢNH ĐẠI DIỆN
    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = preg_replace('/\s+/', '', trim((string)($_POST['phone'] ?? '')));
        $phone = $phone === '' ? null : $phone;
        $address = trim($_POST['address'] ?? '');
        $avatar_path = $user['avatar']; // Mặc định giữ nguyên ảnh cũ

        // Xử lý upload ảnh nếu có
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_exts)) {
                $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                    $avatar_path = 'uploads/avatars/' . $new_filename;
                } else {
                    $message = 'Lỗi khi lưu ảnh đại diện!';
                    $message_type = 'danger';
                }
            } else {
                $message = 'Chỉ chấp nhận file ảnh JPG, JPEG, PNG, GIF!';
                $message_type = 'danger';
            }
        }

        if (empty($name)) {
            $message = 'Vui lòng nhập tên!';
            $message_type = 'danger';
        } elseif ($phone !== null && !preg_match('/^[0-9]{9,11}$/', $phone)) {
            $message = 'Số điện thoại không hợp lệ!';
            $message_type = 'danger';
        } elseif ($message_type !== 'danger') {
            // Kiểm tra trùng số điện thoại
            if ($phone !== null) {
                $dup = $db->prepare("SELECT id FROM users WHERE phone = ? AND id <> ?");
                $dup->bind_param("si", $phone, $user_id);
                $dup->execute();
                $phoneExists = (bool)$dup->get_result()->fetch_assoc();
                $dup->close();
                if ($phoneExists) {
                    $message = 'Số điện thoại này đã được tài khoản khác sử dụng!';
                    $message_type = 'danger';
                }
            }

            if ($message_type !== 'danger') {
                $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ?, avatar = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $phone, $address, $avatar_path, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['name'] = $name;
                    $message = 'Cập nhật hồ sơ thành công!';
                    $message_type = 'success';
                    
                    // Refresh data
                    $user['name'] = $name;
                    $user['phone'] = $phone;
                    $user['address'] = $address;
                    $user['avatar'] = $avatar_path;
                } else {
                    $message = 'Lỗi cập nhật CSDL!';
                    $message_type = 'danger';
                }
                $stmt->close();
            }
        }
    } 
    
    // XỬ LÝ 2: ĐỔI MẬT KHẨU
    elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'Vui lòng nhập đầy đủ thông tin mật khẩu!';
            $message_type = 'danger';
        } elseif (!password_verify($current_password, $user['password'])) {
            $message = 'Mật khẩu hiện tại không chính xác!';
            $message_type = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
            $message_type = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'Mật khẩu xác nhận không khớp!';
            $message_type = 'danger';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = 'Đổi mật khẩu thành công!';
                $message_type = 'success';
                $user['password'] = $hashed_password; // Update local data
            } else {
                $message = 'Lỗi khi đổi mật khẩu!';
                $message_type = 'danger';
            }
            $stmt->close();
        }
    }
}

// Xử lý hiển thị ảnh đại diện mặc định nếu không có
$display_avatar = !empty($user['avatar']) ? '../' . $user['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=667eea&color=fff';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
    body {
        background: #f8f9fa;
    }

    /* CÁCH KHẮC PHỤC LỖI MENU ĐÈ CHỮ: Thêm khoảng đệm lớn ở trên cùng */
    .page-wrapper {
        padding-top: 100px;
        /* Đẩy nội dung xuống dưới thanh menu */
        padding-bottom: 50px;
    }

    .profile-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        margin-bottom: 25px;
        border: 1px solid #f0f0f0;
    }

    .card-header-title {
        font-weight: 700;
        font-size: 18px;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e9ecef;
        color: #2d3748;
    }

    .form-label {
        font-weight: 600;
        color: #4a5568;
        font-size: 14px;
    }

    .form-control {
        border-radius: 8px;
        border-color: #e2e8f0;
        padding: 10px 15px;
    }

    .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
    }

    .btn-primary {
        background: #101828;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
    }

    .btn-primary:hover {
        background: #1f2937;
    }

    .info-item {
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid #edf2f7;
    }

    .info-label {
        font-weight: 600;
        color: #4a5568;
        font-size: 13px;
        text-transform: uppercase;
    }

    .info-value {
        color: #1a202c;
        margin-top: 5px;
        font-weight: 500;
    }

    .avatar-preview-wrap {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 25px;
    }

    .avatar-preview {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e2e8f0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .action-link {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        color: #4a5568;
        text-decoration: none;
        border-radius: 8px;
        background: #f8fafc;
        margin-bottom: 10px;
        font-weight: 600;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
    }

    .action-link:hover {
        background: #edf2f7;
        color: #101828;
        transform: translateX(5px);
    }

    .action-link i {
        width: 30px;
        font-size: 18px;
        color: #667eea;
    }
    </style>
</head>

<body>
    <?php qlpt_render_public_nav(['base' => '../', 'active' => 'profile']); ?>

    <div class="container-lg page-wrapper">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <?php
                $userNavActive = 'profile';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </div>

            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 style="font-size: 26px; font-weight: 800; color: #1a202c; margin: 0;">
                        Tài khoản của tôi
                    </h1>
                </div>

                <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm border-0">
                    <i
                        class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">

                        <div class="profile-card">
                            <h5 class="card-header-title"><i class="fas fa-user-edit me-2 text-primary"></i> Chỉnh sửa
                                thông tin & Ảnh đại diện</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="avatar-preview-wrap">
                                    <img src="<?php echo $display_avatar; ?>" alt="Avatar" class="avatar-preview"
                                        id="preview-img">
                                    <div>
                                        <label class="form-label d-block">Ảnh đại diện mới</label>
                                        <input type="file" name="avatar" class="form-control form-control-sm"
                                            accept="image/png, image/jpeg, image/gif" onchange="previewAvatar(this)">
                                        <small class="text-muted mt-1 d-block">Định dạng: JPG, PNG, GIF. Tối đa
                                            2MB.</small>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tên đầy đủ</label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Số điện thoại</label>
                                    <input type="tel" name="phone" class="form-control"
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                        placeholder="Ví dụ: 0912345678">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Địa chỉ liên hệ</label>
                                    <textarea name="address" class="form-control" rows="2"
                                        placeholder="Nhập địa chỉ của bạn"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    Lưu thông tin
                                </button>
                            </form>
                        </div>

                        <div class="profile-card">
                            <h5 class="card-header-title"><i class="fas fa-lock me-2 text-primary"></i> Đổi mật khẩu
                            </h5>
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label class="form-label">Mật khẩu hiện tại</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mật khẩu mới</label>
                                        <input type="password" name="new_password" class="form-control" required
                                            minlength="6">
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Xác nhận mật khẩu mới</label>
                                        <input type="password" name="confirm_password" class="form-control" required
                                            minlength="6">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    Cập nhật mật khẩu
                                </button>
                            </form>
                        </div>

                    </div>

                    <div class="col-lg-4">

                        <div class="profile-card">
                            <h5 class="card-header-title">Thông tin hệ thống</h5>
                            <div class="info-item">
                                <div class="info-label">Email đăng nhập</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Ngày tạo tài khoản</div>
                                <div class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Trạng thái</div>
                                <div class="info-value">
                                    <span
                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">Đang
                                        hoạt động</span>
                                </div>
                            </div>
                        </div>

                        <div class="profile-card">
                            <h5 class="card-header-title">Hoạt động của tôi</h5>
                            <a href="#" class="action-link"
                                onclick="alert('Tính năng dành cho chủ phòng. Vui lòng nâng cấp tài khoản!'); return false;">
                                <i class="fas fa-list-alt"></i> Quản lý tin đăng
                            </a>
                            <a href="#" class="action-link"
                                onclick="alert('Chức năng gửi báo cáo đang được phát triển!'); return false;">
                                <i class="fas fa-flag"></i> Gửi báo cáo đã thuê
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php qlpt_render_public_footer(['base' => '../']); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // JS giúp hiển thị trước ảnh đại diện khi chọn file
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-img').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    </script>
</body>

</html>