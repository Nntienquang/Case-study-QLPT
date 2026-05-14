<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../core/Captcha.php';
@require_once '../app/controller/AuthController.php';

session_start();

/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

function auth_redirect_for_role(string $role): string
{
    if ($role === 'admin') {
        return './admin/index.php';
    }
    if ($role === 'owner') {
        return './owner/index.php';
    }

    return './user/dashboard.php';
}

function set_login_session(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['status'] = $user['status'];
    $_SESSION['login_time'] = time();
}

function verify_public_credentials(mysqli $conn, string $email, string $password): array
{
    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập email và mật khẩu'];
    }

    $stmt = $conn->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ?');
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi hệ thống'];
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email hoặc mật khẩu không chính xác'];
    }

    if ($user['status'] === 'blocked') {
        return ['success' => false, 'message' => 'Tài khoản của bạn bị khóa'];
    }

    if ($user['status'] === 'rejected' && $user['role'] === 'owner') {
        return ['success' => false, 'message' => 'Đơn đăng ký owner của bạn bị từ chối'];
    }

    unset($user['password']);

    return ['success' => true, 'message' => 'Đăng nhập thành công', 'user' => $user];
}

if (isset($_SESSION['user_id']) && $auth->checkSessionTimeout()) {
    header('Location: ' . auth_redirect_for_role($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user'));
    exit;
}

$message = '';
$type = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha = trim($_POST['captcha'] ?? '');

    if (!Captcha::validate('login_captcha', $captcha)) {
        $message = 'Mã xác thực không đúng. Vui lòng nhập lại mã trong ảnh.';
        $type = 'error';
    } else {
        $result = verify_public_credentials($conn, $email, $password);

        if ($result['success']) {
            set_login_session($result['user']);
            header('Location: ' . auth_redirect_for_role($result['user']['role']));
            exit;
        }

        $message = $result['message'];
        $type = 'error';
    }
}

Captcha::ensure('login_captcha');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - QuanLyPhongTro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css?v=auth-captcha-only-1" rel="stylesheet">
</head>
<body class="auth-dark">
    <div class="three-stage auth-scene" data-three-scene data-scene="housing" data-accent="#2563eb" data-accent2="#14b8a6"></div>

    <main class="auth-3d-page">
        <a href="index.php" class="auth-home-link"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <div class="auth-3d-shell">
            <section class="auth-3d-copy">
                <div class="eyebrow"><i class="fas fa-house"></i> Chào mừng trở lại</div>
                <h1>Quản lý việc thuê trọ dễ dàng hơn.</h1>
                <p>Tiếp tục tìm phòng, quản lý tin đăng hoặc xử lý các yêu cầu đang chờ.</p>
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

                    <label>Mã xác thực</label>
                    <div class="captcha-widget">
                        <img class="captcha-image" src="captcha.php?key=login_captcha&v=<?php echo time(); ?>" alt="Mã xác thực">
                        <button type="button" class="captcha-refresh" aria-label="Đổi mã xác thực" onclick="refreshCaptcha(this)">
                            <i class="fa fa-rotate-right"></i>
                        </button>
                        <div class="input-group captcha-input">
                            <i class="fa fa-shield-halved"></i>
                            <input type="text" name="captcha" autocomplete="off" placeholder="Nhập mã" required>
                        </div>
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
    <script>
        function refreshCaptcha(button) {
            const image = button.parentElement.querySelector('.captcha-image');
            image.src = image.src.split('&v=')[0] + '&v=' + Date.now();
            button.parentElement.querySelector('input[name="captcha"]').value = '';
        }
    </script>
</body>
</html>
