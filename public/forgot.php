<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

session_start();

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    /** @var mysqli $conn */
    $db = new Database($conn);
    $auth = new AuthController($db->getConnection());
    if ($auth->checkSessionTimeout()) {
        header('Location: ./dashboard.php');
        exit;
    }
}

// Initialize
/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

$message = "";
$type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    
    // Call AuthController
    $result = $auth->requestPasswordReset($email);
    
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';
    
    // TODO: Gửi email reset nếu token tồn tại
    // if ($result['success'] && isset($result['token'])) {
    //     $reset_url = $result['reset_url'];
    //     // require_once 'core/EmailNotification.php';
    //     // EmailNotification::sendPasswordReset($email, $reset_url);
    // }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quên Mật Khẩu - QuanLyPhongTro</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.container {
    background: white;
    padding: 40px;
    border-radius: 16px;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 10px;
    font-weight: 700;
}

.subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
    font-size: 14px;
}

.input-group {
    position: relative;
    margin-bottom: 20px;
}

.input-group label {
    display: block;
    color: #333;
    font-weight: 500;
    margin-bottom: 8px;
    font-size: 13px;
}

.input-group i {
    position: absolute;
    top: 38px;
    left: 12px;
    color: #888;
    font-size: 14px;
}

input {
    width: 100%;
    padding: 12px 12px 12px 35px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    transition: 0.3s;
    font-size: 14px;
    font-family: inherit;
}

input:focus {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102,126,234,0.3);
}

button {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    margin-top: 10px;
    transition: 0.3s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

.msg {
    margin: 20px 0;
    padding: 15px;
    border-radius: 8px;
    font-size: 14px;
    text-align: center;
    border: 1px solid;
}

.error {
    background: #ffe6e6;
    color: #c00;
    border-color: #ffcccc;
}

.success {
    background: #e6ffe6;
    color: #060;
    border-color: #ccffcc;
}

.info-box {
    background: #f0f7ff;
    border-left: 4px solid #667eea;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #555;
    line-height: 1.6;
}

.links {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #666;
}

.links a {
    text-decoration: none;
    color: #667eea;
    font-weight: 600;
}

.links a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="container">
    <h2>🔑 Quên Mật Khẩu?</h2>
    <p class="subtitle">Nhập email của bạn để nhận liên kết đặt lại</p>

    <div class="info-box">
        <i class="fas fa-info-circle"></i> Nhập email liên kết với tài khoản của bạn, chúng tôi sẽ gửi cho bạn liên kết để đặt lại mật khẩu.
    </div>

    <?php if($message != ""): ?>
        <div class="msg <?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <label for="email">Email</label>
            <i class="fa fa-envelope"></i>
            <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
        </div>

        <button type="submit">📧 Gửi Liên Kết Đặt Lại</button>
    </form>

    <div class="links">
        ← <a href="login.php">Quay lại đăng nhập</a><br style="margin: 10px 0;">
        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
    </div>
</div>

</body>
</html>
