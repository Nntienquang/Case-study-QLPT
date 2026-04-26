<?php
require_once 'database.php';

$message = "";
$type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    if (empty($email)) {
        $message = "Vui lòng nhập email của bạn";
        $type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không hợp lệ";
        $type = "error";

    } else {

        
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            
            $message = "Email tồn tại! ";
            $type = "success";

        } else {
            $message = "Email không tồn tại";
            $type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<style>
body {
    margin: 0;
    font-family: Arial;
    background: linear-gradient(135deg, #667eea, #764ba2);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    background: white;
    padding: 30px;
    border-radius: 12px;
    width: 320px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
}

input {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 10px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
}

.msg {
    margin: 10px 0;
    padding: 8px;
    border-radius: 6px;
}

.error {
    background: #ffe6e6;
    color: red;
}

.success {
    background: #e6ffed;
    color: green;
}
</style>
</head>

<body>

<div class="container">
    <h2>Quên mật khẩu</h2>

    <!-- message -->
    <?php if($message != ""): ?>
        <p class="msg <?php echo $type; ?>">
            <?php echo $message; ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Nhập email của bạn" required>
        <button type="submit">Gửi liên kết đặt lại mật khẩu</button>
    </form>

    <br>
    <a href="login.php">Quay lại đăng nhập</a>
</div>

</body>
</html>
