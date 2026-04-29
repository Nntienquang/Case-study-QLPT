<?php
require_once 'database.php';

$message = ""; 
$type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirm"] ?? "";
    $role = $_POST["role"] ?? "user";

    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "Vui lòng nhập đầy đủ thông tin";
        $type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email không hợp lệ";
        $type = "error";

    } elseif (strlen($password) < 6) {
        $message = "Mật khẩu phải có ít nhất 6 ký tự";
        $type = "error";

    } elseif ($password !== $confirm) {
        $message = "Mật khẩu không khớp";
        $type = "error";

    } else {

        // Check if email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Email này đã tồn tại";
            $type = "error";

        } else {

            // Hash password with bcrypt
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert into database
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $message = "Đăng ký thành công! Vui lòng đăng nhập";
                $type = "success";
                // Clear form
                $name = $email = $password = $confirm = "";
            } else {
                $message = "Lỗi hệ thống: " . htmlspecialchars($stmt->error);
                $type = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
* { box-sizing: border-box; }

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
    padding: 30px;
    border-radius: 16px;
    width: 350px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    text-align: center;
}

h2 { margin-bottom: 15px; }

.input-group {
    position: relative;
    margin-bottom: 12px;
}

.input-group i {
    position: absolute;
    top: 12px;
    left: 12px;
    color: #888;
}

input, select {
    width: 100%;
    padding: 10px 40px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 8px;
}
select {
    appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg fill='%23667eea' height='20' viewBox='0 0 20 20' width='20' xmlns='http://www.w3.org/2000/svg'><path d='M5 7l5 5 5-5z'/></svg>");
    background-repeat: no-repeat;
    background-position: right 15px center; /* 👈 ขยับลูกศร */
}

button {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
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
</style>
</head>

<body>

<div class="container">
    <h2>Đăng ký tài khoản</h2>

    <?php if($message != ""): ?>
        <p class="msg <?php echo $type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="input-group">
            <i class="fa fa-user"></i>
            <input type="text" name="name" placeholder="Họ và Tên" value="<?php echo htmlspecialchars($name ?? ""); ?>" required>
        </div>

        <div class="input-group">
            <i class="fa fa-envelope"></i>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email ?? ""); ?>" required>
        </div>

       <div class="input-group">
    <i class="fa fa-lock"></i>
    <input type="password" name="password" placeholder="Mật khẩu (ít nhất 6 ký tự)" required>
</div>

<div class="input-group">
    <i class="fa fa-lock"></i>
    <input type="password" id="confirm" name="confirm" placeholder="Xác nhận mật khẩu" required>
</div>

        <div class="input-group">
            <select name="role" id="role">
             <option value="user">Người dùng</option>
             <option value="owner">Owner</option>
            </select>
        </div>

        <!-- CCCD -->
        <div class="input-group" id="cccd_field" style="display:none;">
            <input type="file" name="cccd">
        </div>

        <button type="submit">Đăng ký</button>
    </form>

    <div class="links">
        <a href="login.php">Quay lại đăng nhập</a>
    </div>
</div>

<script>
document.getElementById("role").addEventListener("change", function(){
    let cccd = document.getElementById("cccd_field");
    if(this.value === "owner"){
        cccd.style.display = "block";
    } else {
        cccd.style.display = "none";
    }
});
</script>

</body>
</html>