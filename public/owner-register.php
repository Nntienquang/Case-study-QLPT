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
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    $result = $auth->register($name, $email, $password, $confirm, 'owner', $phone);
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        $name = '';
        $email = '';
        $phone = '';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký chủ phòng - QuanLyPhongTro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body class="auth-dark">
    <div class="three-stage auth-scene" data-three-scene data-scene="housing" data-accent="#14b8a6" data-accent2="#2563eb"></div>

    <main class="auth-3d-page">
        <a href="index.php" class="auth-home-link"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <div class="auth-3d-shell">
            <section class="auth-3d-copy">
                <div class="eyebrow"><i class="fas fa-building-user"></i> Dành cho chủ phòng</div>
                <h1>Đăng phòng đẹp, nhận lịch xem và quản lý doanh thu.</h1>
                <p>
                    Tài khoản owner cần admin duyệt trước khi đăng tin. Sau khi được duyệt,
                    bạn có dashboard riêng để quản lý phòng, booking và chất lượng tin đăng.
                </p>
                <div class="auth-3d-points">
                    <div class="auth-3d-point"><strong>Xác minh</strong><span>Admin duyệt owner</span></div>
                    <div class="auth-3d-point"><strong>Đăng tin</strong><span>Quản lý phòng và ảnh</span></div>
                    <div class="auth-3d-point"><strong>Vận hành</strong><span>Lịch xem, booking, doanh thu</span></div>
                </div>
            </section>

            <section class="auth-card-3d">
                <div class="auth-card-head">
                    <div class="brand-mark"><i class="fa-solid fa-building"></i></div>
                    <div>
                        <h2>Đăng ký chủ phòng</h2>
                        <p class="subtitle">Gửi thông tin để bắt đầu đăng và quản lý phòng cho thuê.</p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="msg <?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label>Họ tên chủ phòng</label>
                    <div class="input-group">
                        <i class="fa fa-user"></i>
                        <input type="text" name="name" placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <label>Số điện thoại</label>
                    <div class="input-group">
                        <i class="fa fa-phone"></i>
                        <input type="tel" name="phone" placeholder="09xxxxxxxx" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <label>Email</label>
                    <div class="input-group">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email" placeholder="owner@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="password" placeholder="Ít nhất 6 ký tự" required>
                    </div>

                    <label>Xác nhận mật khẩu</label>
                    <div class="input-group">
                        <i class="fa fa-lock"></i>
                        <input type="password" name="confirm" placeholder="Nhập lại mật khẩu" required>
                    </div>

                    <button type="submit"><i class="fas fa-paper-plane"></i> Gửi hồ sơ owner</button>
                </form>

                <div class="links">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a><br>
                    Là người thuê? <a href="register.php">Đăng ký người thuê</a>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="assets/js/three-interface.js"></script>
</body>
</html>
