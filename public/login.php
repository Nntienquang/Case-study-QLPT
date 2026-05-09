<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

session_start();

/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

if (isset($_SESSION['user_id']) && $auth->checkSessionTimeout()) {
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
    if ($role === 'admin') {
        header('Location: ./admin/index.php');
    } elseif ($role === 'owner') {
        header('Location: ./owner/dashboard.php');
    } else {
        header('Location: ./user/dashboard.php');
    }
    exit;
}

$message = '';
$type = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $auth->login($email, $password);
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        if ($result['role'] === 'admin') {
            header('Location: ./admin/index.php');
        } elseif ($result['role'] === 'owner') {
            header('Location: ./owner/dashboard.php');
        } else {
            header('Location: ./user/dashboard.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - QuanLyPhongTro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body class="auth-dark">
    <div class="three-stage auth-scene" data-three-scene data-scene="housing" data-accent="#2563eb" data-accent2="#14b8a6"></div>

    <main class="auth-3d-page">
        <a href="index.php" class="auth-home-link"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <div class="auth-3d-shell">
            <section class="auth-3d-copy">
                <div class="eyebrow"><i class="fas fa-house"></i> Chào mừng trở lại</div>
                <h1>Quản lý việc thuê trọ dễ dàng hơn.</h1>
                <p>
                    Tiếp tục tìm phòng, quản lý tin đăng hoặc xử lý các yêu cầu đang chờ.
                </p>
                <div class="auth-3d-points">
                    <div class="auth-3d-point"><strong>Admin</strong><span>Kiểm duyệt và báo cáo</span></div>
                    <div class="auth-3d-point"><strong>Owner</strong><span>Phòng, lịch xem, doanh thu</span></div>
                    <div class="auth-3d-point"><strong>User</strong><span>Tìm, lưu, đặt phòng</span></div>
                </div>
            </section>

            <section class="auth-card-3d">
                <div class="auth-card-head">
                    <div class="brand-mark"><i class="fa-solid fa-user-lock"></i></div>
                    <div>
                        <h2>Đăng nhập</h2>
                        <p class="subtitle">Tiếp tục với tài khoản QuanLyPhongTro của bạn.</p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="msg <?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label>Email</label>
                    <div class="input-group">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email" placeholder="admin123@gmail.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" placeholder="Nhập mật khẩu" required>
                    </div>

                    <button type="submit"><i class="fas fa-arrow-right-to-bracket"></i> Đăng nhập</button>
                </form>

                <div class="links">
                    <a href="forgot.php">Quên mật khẩu?</a><br>
                    Chưa có tài khoản? <a href="register.php">Đăng ký người thuê</a><br>
                    Là chủ phòng? <a href="owner-register.php">Đăng ký owner</a>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="assets/js/three-interface.js"></script>
</body>
</html>
