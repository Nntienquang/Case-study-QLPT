<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

// Initialize
/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

$message = "";
$type = "";
$token = $_GET["token"] ?? "";
$show_form = !empty($token);

// Validate token on page load
if ($show_form) {
    $stmt = $db->getConnection()->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 0) {
            $message = "Liên kết không hợp lệ hoặc đã hết hạn";
            $type = "error";
            $show_form = false;
        }
        $stmt->close();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($token)) {
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    
    // Call AuthController
    $result = $auth->resetPassword($token, $password, $confirm);
    
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';
    
    if ($result['success']) {
        $show_form = false; // Hide form on success
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đặt Lại Mật Khẩu - QuanLyPhongTro</title>
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

.success-message {
    text-align: center;
    padding: 40px 0;
}

.success-icon {
    font-size: 60px;
    color: #667eea;
    margin-bottom: 20px;
}
</style>
</head>

<body>

<div class="container">
    <h2>🔄 Đặt Lại Mật Khẩu</h2>
    <p class="subtitle">Tạo mật khẩu mới cho tài khoản của bạn</p>

    <?php if($message != ""): ?>
        <div class="msg <?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($show_form)): ?>
    <form method="POST">
        <div class="input-group">
            <label for="password">Mật Khẩu Mới</label>
            <i class="fa fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="Nhập mật khẩu mới (ít nhất 6 ký tự)" required>
        </div>

        <div class="input-group">
            <label for="confirm">Xác Nhận Mật Khẩu</label>
            <i class="fa fa-lock"></i>
            <input type="password" id="confirm" name="confirm" placeholder="Xác nhận mật khẩu" required>
        </div>

        <button type="submit">✅ Cập Nhật Mật Khẩu</button>
    </form>
    <?php else: ?>
        <div class="success-message">
            <?php if($type === "success"): ?>
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 style="color: #333; margin: 10px 0;">✅ Thành Công!</h3>
                <p style="color: #666; margin-bottom: 20px;">Mật khẩu của bạn đã được cập nhật</p>
                <div class="links">
                    <a href="login.php">→ Đăng nhập ngay</a>
                </div>
            <?php else: ?>
                <h3 style="color: #c00; margin: 10px 0;">❌ Liên Kết Không Hợp Lệ</h3>
                <p style="color: #666; margin-bottom: 20px;">Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn</p>
                <div class="links">
                    <a href="forgot.php">← Yêu cầu liên kết mới</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
