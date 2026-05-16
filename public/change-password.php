<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../core/Csrf.php';

session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$type = '';
$mustChange = (int)($_SESSION['force_password_change'] ?? 0) === 1;

function change_password_redirect_for_role(string $role): string
{
    if ($role === 'admin') {
        return './admin/index.php';
    }
    if ($role === 'owner') {
        return './owner/dashboard.php';
    }
    return './user/dashboard.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string)($_POST['current_password'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!Csrf::validateRequest('change_password')) {
        $message = 'Phiên thao tác không hợp lệ, vui lòng thử lại.';
        $type = 'error';
    } elseif (strlen($password) < 6 || $password !== $confirm) {
        $message = 'Mật khẩu mới phải từ 6 ký tự và nhập lại khớp.';
        $type = 'error';
    } else {
        $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
        $userId = (int)$_SESSION['user_id'];
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($current, (string)$user['password'])) {
            $message = 'Mật khẩu hiện tại không đúng.';
            $type = 'error';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $update = $conn->prepare('UPDATE users SET password = ?, force_password_change = 0, reset_token = NULL, reset_expires = NULL WHERE id = ?');
            if (!$update) {
                $message = 'Không thể cập nhật mật khẩu.';
                $type = 'error';
            } else {
            $update->bind_param('si', $hash, $userId);
            if ($update->execute()) {
                $_SESSION['force_password_change'] = 0;
                Csrf::rotate('change_password');
                header('Location: ' . change_password_redirect_for_role((string)($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'user')));
                exit;
            }
            $message = 'Không thể cập nhật mật khẩu.';
            $type = 'error';
            $update->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đổi mật khẩu - QuanLyPhongTro</title>
    <link href="assets/css/modern.css?v=auth-security-overlay-1" rel="stylesheet">
</head>
<body class="auth-dark">
    <main class="auth-3d-page">
        <section class="auth-card-3d" style="margin:auto;max-width:480px;">
            <div class="auth-card-head">
                <div>
                    <h2>Đổi mật khẩu</h2>
                    <p class="subtitle"><?php echo $mustChange ? 'Tài khoản vừa được admin reset mật khẩu, vui lòng đổi mật khẩu mới.' : 'Cập nhật mật khẩu tài khoản.'; ?></p>
                </div>
            </div>

            <?php if ($message !== ''): ?>
                <div class="msg <?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <?php echo Csrf::field('change_password'); ?>
                <label>Mật khẩu hiện tại</label>
                <div class="input-group">
                    <input type="password" name="current_password" required>
                </div>
                <label>Mật khẩu mới</label>
                <div class="input-group">
                    <input type="password" name="password" required minlength="6">
                </div>
                <label>Nhập lại mật khẩu mới</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit">Cập nhật mật khẩu</button>
            </form>
        </section>
    </main>
</body>
</html>
