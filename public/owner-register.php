<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

// Initialize
/** @var mysqli $conn */
$db = new Database($conn);
$activityLog = null; // No activity logging for public registration
$auth = new AuthController($db->getConnection());

$message = "";
$type = "";
$name = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $phone = $_POST["phone"] ?? "";
    
    // Call AuthController with 'owner' role
    $result = $auth->register($name, $email, $password, $confirm, 'owner');
    
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';
    
    // Clear form on success
    if ($result['success']) {
        $name = $email = $password = $confirm = $phone = "";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Đăng Ký Làm Chủ Phòng - QuanLyPhongTro</title>
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
    max-width: 500px;
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

.input-group i {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #888;
}

input, textarea {
    width: 100%;
    padding: 12px 12px 12px 35px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    transition: 0.3s;
    font-size: 14px;
    font-family: inherit;
}

input:focus, textarea:focus {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102,126,234,0.3);
}

textarea {
    resize: vertical;
    min-height: 80px;
    padding-left: 12px;
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
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.msg {
    margin: 20px 0;
    padding: 15px;
    border-radius: 8px;
    font-size: 14px;
    text-align: center;
}

.error {
    background: #ffe6e6;
    color: #c00;
    border: 1px solid #ffcccc;
}

.success {
    background: #e6ffe6;
    color: #060;
    border: 1px solid #ccffcc;
}

.benefits {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.benefits h4 {
    color: #667eea;
    margin-bottom: 15px;
    font-size: 16px;
    font-weight: 600;
}

.benefits ul {
    list-style: none;
    padding: 0;
}

.benefits li {
    padding: 8px 0;
    color: #666;
    font-size: 14px;
}

.benefits li:before {
    content: "✓ ";
    color: #667eea;
    font-weight: 600;
    margin-right: 10px;
}

.links {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #666;
}

.links a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.links a:hover {
    text-decoration: underline;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .container {
        padding: 25px;
    }
}
</style>
</head>

<body>

<div class="container">
    <h2>🏠 Đăng Ký Làm Chủ Phòng</h2>
    <p class="subtitle">Bắt đầu kinh doanh và quản lý phòng trọ của bạn</p>

    <?php if($message != ""): ?>
        <div class="msg <?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="benefits">
        <h4>✨ Lợi Ích Khi Là Chủ Phòng:</h4>
        <ul>
            <li>Quản lý phòng của bạn dễ dàng</li>
            <li>Tiếp cận hàng ngàn người thuê</li>
            <li>Nhận thông báo về các yêu cầu mới</li>
            <li>Nhận thanh toán an toàn</li>
            <li>Hỗ trợ khách hàng 24/7</li>
        </ul>
    </div>

    <form method="POST">
        <div class="form-row">
            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="text" name="name" placeholder="Họ tên" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            <div class="input-group">
                <i class="fa fa-phone"></i>
                <input type="tel" name="phone" placeholder="Số điện thoại">
            </div>
        </div>

        <div class="input-group">
            <i class="fa fa-envelope"></i>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>

        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="Mật khẩu (ít nhất 6 ký tự)" required>
        </div>

        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="confirm" placeholder="Xác nhận mật khẩu" required>
        </div>

        <button type="submit">Tạo Tài Khoản Chủ Phòng</button>
    </form>

    <div class="links">
        Đã có tài khoản? <a href="login.php">Đăng Nhập</a><br>
        Bạn là người thuê? <a href="register.php">Đăng Ký Tìm Phòng</a>
    </div>
</div>

</body>
</html>
