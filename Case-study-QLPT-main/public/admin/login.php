<?php
/**
 * Admin Login Page
 */

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Captcha.php';

$db = new Database($conn);
$auth = new Auth($db);

function verify_admin_credentials(Database $db, string $email, string $password): array
{
    if ($email === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập email và mật khẩu'];
    }

    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = ? AND role = 'admin'");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Lỗi hệ thống'];
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email hoặc mật khẩu không đúng'];
    }

    unset($user['password']);

    return ['success' => true, 'user' => $user];
}

function set_admin_session(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['status'] = $user['status'];
    $_SESSION['login_time'] = time();
}

if ($auth->isLoggedIn()) {
    header('Location: ' . ADMIN_URL . 'index.php');
    exit;
}

$error = '';
$email = '';
$showSecurityOverlay = false;
$failureKey = 'login_failures_admin';
$failureCount = Captcha::failureCount($failureKey);
$securityThreshold = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'security_check') {
        $sliderPosition = $_POST['slider_position'] ?? '';
        $sliderToken = $_POST['slider_token'] ?? '';
        $pendingUser = $_SESSION['pending_admin_login'] ?? null;

        if ($pendingUser && Captcha::validateSlider('admin_login_slider_captcha', $sliderPosition, $sliderToken)) {
            set_admin_session($pendingUser);
            Captcha::clearFailures($failureKey);
            unset($_SESSION['pending_admin_login'], $_SESSION['admin_login_slider_captcha']);
            header('Location: ' . ADMIN_URL . 'index.php');
            exit;
        }

        unset($_SESSION['pending_admin_login'], $_SESSION['admin_login_slider_captcha']);
        $error = 'Xác minh không thành công. Vui lòng đăng nhập lại.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');

        if (!Captcha::validate('admin_login_captcha', $captcha)) {
            $error = 'Mã xác thực không đúng. Vui lòng nhập lại mã trong ảnh.';
        } else {
            $result = verify_admin_credentials($db, $email, $password);

            if ($result['success']) {
                if (Captcha::failureCount($failureKey) >= $securityThreshold) {
                    $_SESSION['pending_admin_login'] = $result['user'];
                    $sliderChallenge = Captcha::generateSlider('admin_login_slider_captcha');
                    $showSecurityOverlay = true;
                } else {
                    set_admin_session($result['user']);
                    Captcha::clearFailures($failureKey);
                    unset($_SESSION['admin_login_slider_captcha']);
                    header('Location: ' . ADMIN_URL . 'index.php');
                    exit;
                }
            } else {
                $error = $result['message'];
                $failureCount = Captcha::recordFailure($failureKey);
            }
        }
    }
}

Captcha::ensure('admin_login_captcha');
$failureCount = Captcha::failureCount($failureKey);
if ($showSecurityOverlay) {
    $sliderChallenge = $sliderChallenge ?? Captcha::ensureSlider('admin_login_slider_captcha');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - Quản lý phòng trọ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            color: #101828;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                linear-gradient(90deg, rgba(246, 248, 251, .94), rgba(246, 248, 251, .72)),
                url("https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=2200&q=88") center/cover;
        }
        .login-container {
            width: min(100%, 430px);
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.85);
            border-radius: 18px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.18);
            padding: 32px 30px 28px;
            backdrop-filter: blur(18px);
        }
        .login-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
        .login-mark {
            width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center;
            color: #fff; background: #101828; box-shadow: 0 14px 34px rgba(16, 24, 40, 0.22);
        }
        .login-header h1 { font-size: 26px; line-height: 1.15; font-weight: 900; color: #101828; margin: 0 0 5px; }
        .login-header p { color: #667085; font-size: 14px; margin: 0; }
        .form-group { margin-bottom: 17px; }
        .form-group label { display: block; margin-bottom: 7px; color: #344054; font-weight: 800; font-size: 13px; }
        .input-shell { position: relative; }
        .input-shell i { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: #64748b; }
        .form-group input {
            width: 100%; min-height: 46px; padding: 12px 13px 12px 38px; border: 1px solid #d0d5dd;
            border-radius: 8px; font-size: 14px; background: #fff; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12); }
        .captcha-widget { display: grid; grid-template-columns: 190px 44px; gap: 10px; align-items: center; }
        .captcha-image { width: 190px; height: 56px; border-radius: 10px; border: 1px solid #d7e7ff; background: #eef6ff; }
        .captcha-refresh {
            width: 44px; height: 44px; border: 1px solid #d0d5dd; border-radius: 8px;
            color: #344054; background: #fff; cursor: pointer;
        }
        .captcha-input { grid-column: 1 / -1; position: relative; }
        .btn-login, .security-submit {
            width: 100%; min-height: 48px; background: #101828; color: #fff; border: none; border-radius: 8px;
            font-weight: 800; cursor: pointer; box-shadow: 0 14px 34px rgba(16, 24, 40, 0.22);
        }
        .error-message { color: #b91c1c; font-size: 14px; margin-bottom: 18px; padding: 12px 14px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; }
        .security-overlay {
            position: fixed; inset: 0; z-index: 20; display: grid; place-items: center; padding: 24px;
            background: rgba(15, 23, 42, .45); backdrop-filter: blur(10px);
        }
        .security-panel {
            width: min(100%, 460px); padding: 28px; border-radius: 20px; background: #fff;
            box-shadow: 0 30px 90px rgba(15, 23, 42, .32);
        }
        .security-mark {
            width: 54px; height: 54px; display: grid; place-items: center; border-radius: 16px;
            color: #fff; background: #101828; margin-bottom: 16px;
        }
        .security-panel h2 { margin: 0 0 8px; font-size: 24px; font-weight: 900; }
        .security-panel p { margin: 0 0 18px; color: #667085; line-height: 1.55; }
        .slider-captcha { margin: 18px 0; padding: 12px; border: 1px solid #d7e7ff; border-radius: 12px; background: #f8fbff; }
        .slider-title { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 10px; color: #344054; font-size: 13px; font-weight: 800; }
        .slider-title small { color: #b45309; font-weight: 800; }
        .slider-image {
            position: relative; height: 86px; overflow: hidden; border-radius: 10px;
            background: linear-gradient(135deg, rgba(37,99,235,.18), rgba(20,184,166,.22)), url("https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=800&q=80") center/cover;
        }
        .slider-target { position: absolute; top: 21px; width: 44px; height: 44px; border: 2px dashed #fff; border-radius: 10px; background: rgba(255,255,255,.22); }
        .slider-piece { position: absolute; top: 21px; left: 0; width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: #101828; border-radius: 10px; box-shadow: 0 12px 26px rgba(15,23,42,.25); pointer-events: none; }
        .slider-track { position: relative; height: 44px; margin-top: 10px; border-radius: 999px; background: #e5eefb; color: #64748b; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 750; overflow: hidden; }
        .slider-handle { position: absolute; left: 0; top: 0; width: 44px; height: 44px; border: 0; border-radius: 999px; background: #101828; color: #fff; cursor: grab; touch-action: none; z-index: 2; }
        .slider-captcha.is-close .slider-track { background: #dcfce7; color: #166534; }
        @media (max-width: 520px) {
            .login-container, .security-panel { padding: 26px 18px 22px; }
            .captcha-widget { grid-template-columns: minmax(0, 1fr) 44px; }
            .captcha-image { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-mark"><i class="fa-solid fa-user-shield"></i></div>
            <div>
                <h1>Admin</h1>
                <p>Quản lý phòng trọ</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-shell">
                    <i class="fa fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="admin@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-shell">
                    <i class="fa fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
            </div>

            <div class="form-group">
                <label for="captcha">Mã xác thực</label>
                <div class="captcha-widget">
                    <img class="captcha-image" src="../captcha.php?key=admin_login_captcha&v=<?php echo time(); ?>" alt="Mã xác thực">
                    <button type="button" class="captcha-refresh" aria-label="Đổi mã xác thực" onclick="refreshCaptcha(this)">
                        <i class="fa fa-rotate-right"></i>
                    </button>
                    <div class="input-shell captcha-input">
                        <i class="fa fa-shield-halved"></i>
                        <input type="text" id="captcha" name="captcha" autocomplete="off" placeholder="Nhập mã" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-login">Đăng nhập</button>
        </form>
    </div>

    <?php if ($showSecurityOverlay && !empty($sliderChallenge)): ?>
        <div class="security-overlay" role="dialog" aria-modal="true">
            <form method="POST" class="security-panel">
                <input type="hidden" name="action" value="security_check">
                <div class="security-mark"><i class="fa fa-shield-halved"></i></div>
                <h2>Web nhận thấy truy cập bất thường</h2>
                <p>Thông tin đăng nhập đã đúng, nhưng bạn cần xác minh thao tác kéo thả trước khi vào hệ thống.</p>
                <div class="slider-captcha" data-target="<?php echo (int)$sliderChallenge['target']; ?>">
                    <div class="slider-title">
                        <span>Kéo mảnh ghép để xác minh</span>
                        <small>Lần thử <?php echo (int)$failureCount + 1; ?></small>
                    </div>
                    <div class="slider-image">
                        <div class="slider-target" style="left: <?php echo (int)$sliderChallenge['target']; ?>px;"></div>
                        <div class="slider-piece"><i class="fa fa-house"></i></div>
                    </div>
                    <div class="slider-track">
                        <button type="button" class="slider-handle" aria-label="Kéo để xác minh"><i class="fa fa-arrow-right"></i></button>
                        <span>Kéo sang phải để khớp vùng sáng</span>
                    </div>
                    <input type="hidden" name="slider_position" value="">
                    <input type="hidden" name="slider_token" value="<?php echo htmlspecialchars($sliderChallenge['token']); ?>">
                </div>
                <button type="submit" class="security-submit">Xác minh và tiếp tục</button>
            </form>
        </div>
    <?php endif; ?>

    <script>
        function refreshCaptcha(button) {
            const image = button.parentElement.querySelector('.captcha-image');
            image.src = image.src.split('&v=')[0] + '&v=' + Date.now();
            button.parentElement.querySelector('input[name="captcha"]').value = '';
        }

        document.querySelectorAll('.slider-captcha').forEach((captcha) => {
            const handle = captcha.querySelector('.slider-handle');
            const piece = captcha.querySelector('.slider-piece');
            const track = captcha.querySelector('.slider-track');
            const position = captcha.querySelector('input[name="slider_position"]');
            const target = Number(captcha.dataset.target || 0);
            let dragging = false;

            const setPosition = (clientX) => {
                const rect = track.getBoundingClientRect();
                const max = rect.width - handle.offsetWidth;
                const x = Math.max(0, Math.min(max, clientX - rect.left - handle.offsetWidth / 2));
                handle.style.transform = `translateX(${x}px)`;
                piece.style.transform = `translateX(${x}px)`;
                position.value = Math.round(x);
                captcha.classList.toggle('is-close', Math.abs(x - target) <= 8);
            };

            handle.addEventListener('pointerdown', (event) => {
                dragging = true;
                handle.setPointerCapture(event.pointerId);
            });
            handle.addEventListener('pointermove', (event) => {
                if (dragging) setPosition(event.clientX);
            });
            handle.addEventListener('pointerup', () => {
                dragging = false;
            });
        });
    </script>
</body>
</html>
