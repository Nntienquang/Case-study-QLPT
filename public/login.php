<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../core/Database.php';
require_once '../core/User.php';
require_once '../core/Captcha.php';
require_once '../core/Csrf.php';
require_once '../app/controller/AuthController.php';
require_once __DIR__ . '/components/PasswordInput.php';

session_start();

/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

function auth_redirect_for_role(string $role): string
{
    // Admin thì vẫn vào thẳng trang quản trị
    if ($role === 'admin') {
        return './admin/index.php';
    }
    
    if ($role === 'owner') {
        // Nếu owner chưa được duyệt xác minh, bắt buộc vào trang hồ sơ để nạp giấy tờ
        // Nếu ĐÃ DUYỆT rồi thì cho ra trang chủ (index.php) giống như User bình thường
        return ($_SESSION['owner_verification_status'] ?? 'pending_verification') === 'approved'
            ? './index.php'
            : './owner/profile.php?verify=1';
    }

    // User bình thường ra trang chủ
    return './index.php';
}   

function login_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'local';
}

function login_turnstile_site_key(): string
{
    return trim((string)(getenv('TURNSTILE_SITE_KEY') ?: ''));
}

function login_turnstile_secret_key(): string
{
    return trim((string)(getenv('TURNSTILE_SECRET_KEY') ?: ''));
}

function login_turnstile_enabled(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = explode(':', $host)[0] ?? $host;
    $localHosts = ['localhost', '127.0.0.1', '::1'];
    return !in_array($host, $localHosts, true)
        && login_turnstile_site_key() !== ''
        && login_turnstile_secret_key() !== '';
}

function login_verify_turnstile(string $token): bool
{
    if (!login_turnstile_enabled() || $token === '') {
        return false;
    }

    $postData = http_build_query([
        'secret' => login_turnstile_secret_key(),
        'response' => $token,
        'remoteip' => login_client_ip(),
    ]);
    $endpoint = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    if (function_exists('curl_init')) {
        $curl = curl_init($endpoint);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $body = curl_exec($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 5,
            ],
        ]);
        $body = @file_get_contents($endpoint, false, $context);
    }

    $payload = is_string($body) ? json_decode($body, true) : null;
    return is_array($payload) && !empty($payload['success']);
}

function login_validate_captcha_challenge(string $captchaKey): bool
{
    if (login_turnstile_enabled()) {
        return login_verify_turnstile((string)($_POST['cf-turnstile-response'] ?? ''));
    }

    return Captcha::validate($captchaKey, (string)($_POST['captcha'] ?? ''));
}

function login_security_key(string $email): string
{
    $normalizedEmail = strtolower(trim($email));
    return hash('sha256', $normalizedEmail . '|' . login_client_ip());
}

function login_table_exists(mysqli $conn, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    if (!$stmt) {
        return $cache[$table] = false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $cache[$table] = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $cache[$table];
}

function login_security_default_state(): array
{
    return [
        'failures' => 0,
        'lock_until' => 0,
        'captcha_required' => false,
        'lock_level' => 0,
    ];
}

function login_security_state(mysqli $conn, string $email): array
{
    $key = login_security_key($email);
    if (!login_table_exists($conn, 'login_security_state')) {
        return $_SESSION['login_security'][$key] ?? login_security_default_state();
    }

    $stmt = $conn->prepare('SELECT failures, captcha_required, locked_until, lock_level FROM login_security_state WHERE identity_hash = ? LIMIT 1');
    if (!$stmt) {
        return $_SESSION['login_security'][$key] ?? login_security_default_state();
    }
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return $_SESSION['login_security'][$key] ?? login_security_default_state();
    }

    return [
        'failures' => (int)($row['failures'] ?? 0),
        'lock_until' => !empty($row['locked_until']) ? (strtotime((string)$row['locked_until']) ?: 0) : 0,
        'captcha_required' => (bool)($row['captcha_required'] ?? false),
        'lock_level' => (int)($row['lock_level'] ?? 0),
    ];
}

function login_save_security_state(mysqli $conn, string $email, array $state): void
{
    $key = login_security_key($email);
    $_SESSION['login_security'][$key] = $state;
    if (!login_table_exists($conn, 'login_security_state')) {
        return;
    }

    $ip = login_client_ip();
    $failures = (int)($state['failures'] ?? 0);
    $captchaRequired = !empty($state['captcha_required']) ? 1 : 0;
    $lockLevel = (int)($state['lock_level'] ?? 0);
    $lockedUntil = !empty($state['lock_until']) ? date('Y-m-d H:i:s', (int)$state['lock_until']) : null;
    $stmt = $conn->prepare(
        'INSERT INTO login_security_state (identity_hash, email, ip_address, failures, captcha_required, locked_until, lock_level, last_failure_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE email = VALUES(email), ip_address = VALUES(ip_address), failures = VALUES(failures), captcha_required = VALUES(captcha_required), locked_until = VALUES(locked_until), lock_level = VALUES(lock_level), last_failure_at = NOW(), updated_at = NOW()'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('sssiisi', $key, $email, $ip, $failures, $captchaRequired, $lockedUntil, $lockLevel);
    $stmt->execute();
    $stmt->close();
}

function login_clear_security_state(mysqli $conn, string $email): void
{
    $key = login_security_key($email);
    unset($_SESSION['login_security'][$key]);
    if (!login_table_exists($conn, 'login_security_state')) {
        return;
    }

    $stmt = $conn->prepare('DELETE FROM login_security_state WHERE identity_hash = ?');
    if ($stmt) {
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $stmt->close();
    }
}

function login_record_failure(mysqli $conn, string $email, string $reason): array
{
    $state = login_security_state($conn, $email);
    $state['failures'] = (int)($state['failures'] ?? 0) + 1;
    $state['captcha_required'] = $state['failures'] >= 3;

    if ($state['failures'] >= 7) {
        $levels = [60, 300, 900];
        $level = min((int)($state['lock_level'] ?? 0), count($levels) - 1);
        $state['lock_until'] = time() + $levels[$level];
        $state['lock_level'] = min($level + 1, count($levels) - 1);
    }

    login_save_security_state($conn, $email, $state);
    login_write_log($conn, 0, 'login_failed', 'user', 0, "Login failed for {$email}: {$reason}");

    return $state;
}

function login_write_log(mysqli $conn, int $adminId, string $action, string $entityType, int $entityId, string $description): void
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $stmt = $conn->prepare('INSERT INTO activity_logs (admin_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('ississs', $adminId, $action, $entityType, $entityId, $description, $ip, $ua);
    $stmt->execute();
    $stmt->close();
}

function set_login_session(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['status'] = $user['status'];
    $_SESSION['owner_verification_status'] = $user['owner_verification_status'] ?? 'not_required';
    $_SESSION['force_password_change'] = (int)($user['force_password_change'] ?? 0);
    $_SESSION['login_time'] = time();
}

function verify_public_credentials(mysqli $conn, string $email, string $password): array
{
    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập email và mật khẩu', 'reason' => 'missing_credentials'];
    }

    $stmt = $conn->prepare('SELECT id, name, email, password, role, status, owner_verification_status, force_password_change FROM users WHERE email = ?');
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi hệ thống', 'reason' => 'system_error'];
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng.', 'reason' => 'bad_credentials'];
    }

    if (($user['status'] ?? '') === 'blocked') {
        return ['success' => false, 'message' => 'Tài khoản của bạn bị khóa.', 'reason' => 'blocked'];
    }

    unset($user['password']);

    return ['success' => true, 'message' => 'Đăng nhập thành công', 'user' => $user];
}

if (isset($_SESSION['user_id']) && $auth->checkSessionTimeout()) {
    if ((int)($_SESSION['force_password_change'] ?? 0) === 1) {
        header('Location: ./change-password.php');
        exit;
    }
    header('Location: ' . auth_redirect_for_role($_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user'));
    exit;
}

$message = '';
$type = '';
$email = '';
$showCaptcha = false;
$captchaKey = 'login_captcha';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $state = login_security_state($conn, $email);

    if (!Csrf::validateRequest('login')) {
        $message = 'Phiên đăng nhập không hợp lệ, vui lòng thử lại.';
        $type = 'error';
        $showCaptcha = (bool)($state['captcha_required'] ?? false);
    } elseif ((int)($state['lock_until'] ?? 0) > time()) {
        $minutes = max(1, (int)ceil(((int)$state['lock_until'] - time()) / 60));
        $message = "Bạn đăng nhập sai quá nhiều lần. Vui lòng thử lại sau {$minutes} phút.";
        $type = 'error';
        $showCaptcha = true;
    } else {
        $showCaptcha = (bool)($state['captcha_required'] ?? false);
        if ($showCaptcha && !login_validate_captcha_challenge($captchaKey)) {
            if (!login_turnstile_enabled()) {
                Captcha::generate($captchaKey);
            }
            $message = 'Mã xác minh không đúng.';
            $type = 'error';
        } else {
            $result = verify_public_credentials($conn, $email, $password);
            if ($result['success']) {



            if(isset($_POST['remember_me'])){

    setcookie(
        'remember_email',
        $email,
        time() + (86400 * 30),
        "/"
    );

}else{

    setcookie(
        'remember_email',
        '',
        time() - 3600,
        "/"
    );
}

                set_login_session($result['user']);
                login_clear_security_state($conn, $email);
                unset($_SESSION[$captchaKey]);
                Csrf::rotate('login');
                login_write_log($conn, (int)$result['user']['id'], 'login_success', 'user', (int)$result['user']['id'], 'Đăng nhập thành công');
                if ((int)($result['user']['force_password_change'] ?? 0) === 1) {
                    header('Location: ./change-password.php');
                    exit;
                }
                header('Location: ' . auth_redirect_for_role($result['user']['role']));
                exit;
            }

            if (($result['reason'] ?? '') === 'bad_credentials' || ($result['reason'] ?? '') === 'missing_credentials') {
                $state = login_record_failure($conn, $email, (string)($result['reason'] ?? 'failed'));
                $showCaptcha = (bool)($state['captcha_required'] ?? false);
            } else {
                login_write_log($conn, 0, 'login_failed', 'user', 0, "Login failed for {$email}: " . ($result['reason'] ?? 'failed'));
            }
            $message = $result['message'];
            $type = 'error';
        }
    }
}

if ($showCaptcha && !login_turnstile_enabled()) {
    Captcha::ensure($captchaKey);
}
$useTurnstile = $showCaptcha && login_turnstile_enabled();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - QuanLyPhongTro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css?v=auth-password-ui-3" rel="stylesheet">
</head>

<body class="auth-dark">
    <div class="three-stage auth-scene" data-three-scene data-scene="housing" data-accent="#2563eb"
        data-accent2="#14b8a6"></div>

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

                <form method="POST" autocomplete="off">
                    <?php echo Csrf::field('login'); ?>



                    <label>Email</label>

                    <div class="input-group">
                        <i class="fa fa-envelope"></i>

                        <input type="email" name="email" placeholder="admin123@gmail.com"
                            value="<?php echo htmlspecialchars($_COOKIE['remember_email'] ?? $email); ?>" required>
                    </div>

                    <?php PasswordInput::render([
                        'name' => 'password',
                        'label' => 'Mật khẩu',
                        'placeholder' => 'Nhập mật khẩu',
                        'autocomplete' => 'current-password',
                    ]); ?>

                    <?php if ($showCaptcha && $useTurnstile): ?>
                    <label>Mã xác minh</label>
                    <div class="captcha-widget">
                        <div class="cf-turnstile"
                            data-sitekey="<?php echo htmlspecialchars(login_turnstile_site_key(), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <?php elseif ($showCaptcha): ?>
                    <label>Mã xác minh</label>
                    <div class="captcha-widget">
                        <img class="captcha-image" src="captcha.php?key=login_captcha&v=<?php echo time(); ?>"
                            alt="Mã xác minh">
                        <button type="button" class="captcha-refresh" aria-label="Đổi mã xác minh"
                            onclick="refreshCaptcha(this)">
                            <i class="fa fa-rotate-right"></i>
                        </button>
                        <div class="input-group captcha-input">
                            <i class="fa fa-shield-halved"></i>
                            <input type="text" name="captcha" autocomplete="off" placeholder="Nhập mã" required>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div style="display:flex;align-items:center;gap:8px;margin:15px 0;color:#111827;font-weight:500;">

                        <input type="checkbox" name="remember_me" style="
            width:auto;
            height:auto;
            margin:0;
            transform:scale(1.1);
            cursor:pointer;
        ">

                        <span>Ghi nhớ đăng nhập</span>

                    </div>

                    <button type="submit">
                        <i class="fas fa-arrow-right-to-bracket"></i> Đăng nhập
                    </button>
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
    <script src="assets/js/password-toggle.js?v=auth-password-ui-3"></script>
    <?php if ($useTurnstile): ?>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <?php endif; ?>
    <script>
    function refreshCaptcha(button) {
        const image = button.parentElement.querySelector('.captcha-image');
        image.src = image.src.split('&v=')[0] + '&v=' + Date.now();
        button.parentElement.querySelector('input[name="captcha"]').value = '';
    }
    </script>
</body>

</html>