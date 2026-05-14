<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$owner_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Get user profile
$stmt = $db->prepare("SELECT id, name, email, phone, address, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    if (empty($name)) {
        $message = 'Vui lòng nhập tên!';
        $message_type = 'danger';
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $phone, $address, $owner_id);
        
        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $message = 'Hồ sơ cập nhật thành công!';
            $message_type = 'success';
            // Refresh data
            $stmt = $db->prepare("SELECT id, name, email, phone, address, created_at FROM users WHERE id = ?");
            $stmt->bind_param("i", $owner_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $message = 'Lỗi cập nhật!';
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
        <div class="row">
            <div class="col-lg-3">
                <?php
                $ownerNavActive = 'profile';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-user"></i> Hồ sơ chủ nhà 
                    </h1>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
