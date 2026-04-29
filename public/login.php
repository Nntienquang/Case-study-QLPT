<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../app/controller/AuthController.php';

session_start();

// Initialize
/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    if ($auth->checkSessionTimeout()) {
        // Redirect based on role
        if ($_SESSION['role'] === 'admin') {
            header("Location: ./admin/index.php");
        } elseif ($_SESSION['role'] === 'owner') {
            header("Location: ./owner/dashboard.php");
        } else {
            header("Location: ./user/dashboard.php");
        }
        exit();
    }
}

$message = "";
$type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    
    // Call AuthController
    $result = $auth->login($email, $password);
    
    $message = $result['message'];
    $type = $result['success'] ? 'success' : 'error';
    
    // Redirect on success
    if ($result['success']) {
        if ($result['role'] === 'admin') {
            header("Location: ./admin/index.php");
        } elseif ($result['role'] === 'owner') {
            header("Location: ./owner/dashboard.php");
        } else {
            header("Location: ./user/dashboard.php");
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    * {
    box-sizing: border-box;
}
body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* card */
.container {
    background: white;
    padding: 35px 30px;
    border-radius: 16px;
    width: 340px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.2);
    text-align: center;
    animation: fadeIn 0.5s ease;
}
.msg {
    margin: 10px 0;
    padding: 8px;
    border-radius: 6px;
    font-size: 14px;
}

.error {
    background: #ffe6e6;
    color: red;
}

.success {
    background: #e6ffed;
    color: green;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px);}
    to { opacity: 1; transform: translateY(0);}
}

h2 {
    margin-bottom: 20px;
}

/* input group */
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
}

input:focus {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102,126,234,0.5);
}

/* button */
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

/* links */
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

/* extra */
.logo {
    font-size: 30px;
    margin-bottom: 10px;
    color: #667eea;
}
</style>
</head>

<body>

<div class="container">
    <div class="logo">
        <i class="fa-solid fa-user-lock"></i>
    </div>

    <h2>Đăng nhập</h2>
    <?php if($message != ""): ?>
    <p class="msg <?php echo $type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </p>
<?php endif; ?>

    <form method="POST">
        <div class="input-group">
            <i class="fa fa-envelope"></i>
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div class="input-group">
            <i class="fa fa-lock"></i>
            <input type="password" name="password" placeholder="Mật khẩu" required>
        </div>

        <button type="submit">Đăng nhập</button>
    </form>

    <div class="links">
        <a href="forgot.php">Quên mật khẩu?</a><br>
        <a href="register.php">Tạo tài khoản</a>
    </div>
</div>

</body>
</html>