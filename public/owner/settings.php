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

// Get user settings
$stmt = $db->prepare("SELECT id, email, password FROM users WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password)) {
        $message = 'Vui lòng điền đầy đủ mật khẩu!';
        $message_type = 'danger';
    } elseif (strlen($new_password) < 6) {
        $message = 'Mật khẩu mới phải ít nhất 6 ký tự!';
        $message_type = 'danger';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Mật khẩu xác nhận không khớp!';
        $message_type = 'danger';
    } elseif (!password_verify($old_password, $user['password'])) {
        $message = 'Mật khẩu cũ không chính xác!';
        $message_type = 'danger';
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $owner_id);
        
        if ($stmt->execute()) {
            $message = 'Mật khẩu đã thay đổi thành công!';
            $message_type = 'success';
        } else {
            $message = 'Lỗi thay đổi mật khẩu!';
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
    <title>Cài đặt - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { font-size: 22px; font-weight: 700; color: white !important; }
        .main-content { padding: 30px; }
        .settings-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .settings-card h5 { font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-label { font-weight: 600; color: #333; }
        .form-control { border-radius: 6px; border: 1px solid #ddd; }
        .form-control:focus { border-color: #667eea; box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25); }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); border: none; }
        .danger-zone { background: #fff5f5; border-left: 4px solid #d32f2f; padding: 20px; border-radius: 6px; }
        .danger-zone h6 { color: #d32f2f; font-weight: 700; }
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
                $ownerNavActive = 'settings';
                require __DIR__ . '/_nav_sidebar.php';
                ?>
            </div>

            <div class="col-lg-9">
                <div class="main-content">
                    <h1 style="font-size: 28px; font-weight: 700; margin-bottom: 30px;">
                        <i class="fas fa-cog"></i> Cài đặt
                    </h1>

                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo $message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Password Change -->
                    <div class="settings-card">
                        <h5><i class="fas fa-lock"></i> Thay đổi mật khẩu</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu cũ</label>
                                <input type="password" name="old_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mật khẩu mới</label>
                                <input type="password" name="new_password" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-save"></i> Thay đổi mật khẩu
                            </button>
                        </form>
                    </div>

                    <!-- Notification Settings -->
                    <div class="settings-card">
                        <h5><i class="fas fa-bell"></i> Thông báo</h5>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_notify" checked>
                                <label class="form-check-label" for="email_notify">
                                    Nhận thông báo qua email
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="booking_notify" checked>
                                <label class="form-check-label" for="booking_notify">
                                    Thông báo khi có đơn đặt phòng
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div class="settings-card">
                        <h5><i class="fas fa-info-circle"></i> Thông tin tài khoản</h5>
                        <div class="info-item" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                            <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                            <small style="color: #666;">Không thể thay đổi email. Liên hệ admin nếu cần.</small>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="settings-card">
                        <div class="danger-zone">
                            <h6><i class="fas fa-exclamation-triangle"></i> Vùng nguy hiểm</h6>
                            <p style="color: #666; margin-top: 10px;">Thao tác này sẽ xóa vĩnh viễn tài khoản của bạn và tất cả dữ liệu.</p>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="fas fa-trash-alt"></i> Xóa tài khoản
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-circle"></i> Xóa tài khoản</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Cảnh báo:</strong> Thao tác này không thể hoàn tác!</p>
                    <p>Toàn bộ dữ liệu tài khoản của bạn sẽ bị xóa vĩnh viễn.</p>
                    <p>Bạn có chắc muốn tiếp tục?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger">Xóa tài khoản</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
