<?php
require_once 'database.php';

$message = "";
$type = "";
$token = $_GET["token"] ?? "";
$user_id = null;

// Validate token
if (!empty($token)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $message = "Liên kết không hợp lệ hoặc đã hết hạn";
        $type = "error";
        $token = ""; // Invalid token
    } else {
        $user = $result->fetch_assoc();
        $user_id = $user["id"];
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($token)) {
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";

    if (empty($password) || empty($confirm)) {
        $message = "Vui lòng nhập đầy đủ mật khẩu";
        $type = "error";

    } elseif (strlen($password) < 6) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự";
        $type = "error";

    } elseif ($password !== $confirm) {
        $message = "Mật khẩu không khớp";
        $type = "error";

    } else {
        // Hash and update password
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $update->bind_param("si", $hashed, $user_id);

        if ($update->execute()) {
            $message = "Mật khẩu đã được thay đổi thành công. Vui lòng đăng nhập";
            $type = "success";
            $token = ""; // Clear token to hide form
        } else {
            $message = "Lỗi hệ thống: " . htmlspecialchars($update->error);
            $type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Đặt lại mật khẩu</title>
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
    <h2>Đặt lại mật khẩu</h2>

    <?php if($message != ""): ?>
        <p class="msg <?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if(!empty($token)): ?>
    <form method="POST">
        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="Mật khẩu mới (ít nhất 6 ký tự)" required>
        </div>

        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="confirm" placeholder="Xác nhận mật khẩu" required>
        </div>

        <button type="submit">Cập nhật mật khẩu</button>
    </form>
    <?php else: ?>
        <?php if($type === "success"): ?>
            <div class="links">
                <a href="login.php">Đăng nhập tài khoản</a>
            </div>
        <?php else: ?>
            <div class="links">
                <a href="forgot.php">Quay lại yêu cầu đặt lại</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
