<?php
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'core/Database.php';
require_once 'core/User.php';
require_once 'app/controller/AuthController.php';

// Initialize
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
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quên mật khẩu</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    background: white;
    padding: 35px 30px;
    border-radius: 16px;
    width: 340px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    text-align: center;
}

h2 {
    margin-bottom: 20px;
}

.input-group {
    position: relative;
    margin-bottom: 15px;
}

.input-group i {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #888;
}

input {
    width: 100%;
    padding: 10px 10px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 8px;
    outline: none;
    transition: 0.3s;
    box-sizing: border-box;
}

input:focus {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102,126,234,0.5);
}

button {
    width: 100%;
    padding: 11px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px;
    transition: 0.3s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.msg {
    margin: 10px 0;
    padding: 10px;
    border-radius: 6px;
    font-size: 14px;
}

.error {
    background: #ffe6e6;
    color: #c00;
}

.success {
    background: #e6ffe6;
    color: #060;
}

.links {
    margin-top: 15px;
    font-size: 14px;
}

.links a {
    text-decoration: none;
    color: #667eea;
}

.links a:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="container">
    <h2>Quên mật khẩu</h2>

    <!-- message -->
    <?php if($message != ""): ?>
        <p class="msg <?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <i class="fa fa-envelope"></i>
            <input type="email" name="email" placeholder="Nhập email của bạn" required>
        </div>
        <button type="submit">Gửi liên kết đặt lại mật khẩu</button>
    </form>

    <div class="links">
        <a href="login.php">Quay lại đăng nhập</a>
    </div>
</div>

</body>
</html>
