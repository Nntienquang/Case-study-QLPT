<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

session_start();

if (isset($_SESSION['user_id'])) {
    /** @var mysqli $conn */
    $db = new Database($conn);
    $auth = new AuthController($db->getConnection());
    if ($auth->checkSessionTimeout()) {
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
}

/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

$message = '';
$type = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    $result = $auth->register($name, $email, $password, $confirm, 'user');
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';

    if ($result['success']) {
        $name = '';
        $email = '';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký người thuê - QuanLyPhongTro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
</head>
<body class="auth-dark">
    <div class="three-stage auth-scene" data-three-scene data-scene="housing" data-accent="#2563eb" data-accent2="#8b5cf6"></div>

    <main class="auth-3d-page">
        <a href="index.php" class="auth-home-link"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <div class="auth-3d-shell">
            <section class="auth-3d-copy">
                <div class="eyebrow"><i class="fas fa-magnifying-glass-location"></i> Tìm phòng nhanh hơn</div>
                <h1>Lưu phòng đẹp và đặt lịch xem dễ dàng.</h1>
                <p>
                    Người thuê có thể lưu bộ lọc, yêu thích phòng, xem chi phí vào ở và gửi yêu cầu đặt phòng.
                </p>
                <div class="auth-3d-points">
                    <div class="auth-3d-point"><strong>Tìm</strong><span>Lọc theo khu vực và giá</span></div>
                    <div class="auth-3d-point"><strong>Lưu</strong><span>Phòng và bộ lọc yêu thích</span></div>
                    <div class="auth-3d-point"><strong>Đặt</strong><span>Lịch xem hoặc booking</span></div>
                </div>
            </section>

            <section class="auth-card-3d">
                <div class="auth-card-head">
                    <div class="brand-mark"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <h2>Đăng ký người thuê</h2>
                        <p class="subtitle">Lưu phòng yêu thích, đặt lịch xem và theo dõi booking.</p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="msg <?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label>Họ tên</label>
                    <div class="input-group">
                        <i class="fa fa-user"></i>
                        <input type="text" name="name" placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <label>Email</label>
                    <div class="input-group">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
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

                    <button type="submit"><i class="fas fa-user-plus"></i> Tạo tài khoản</button>
                </form>

                <div class="links">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a><br>
                    Là chủ phòng? <a href="owner-register.php">Đăng ký owner</a>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="assets/js/three-interface.js"></script>
</body>
</html>
