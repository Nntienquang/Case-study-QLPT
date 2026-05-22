<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/User.php';
@require_once '../core/Captcha.php';
@require_once '../app/controller/AuthController.php';

session_start();

if (isset($_SESSION['user_id'])) {
    /** @var mysqli $conn */
    $db = new Database($conn);
    $auth = new AuthController($db->getConnection());
    if ($auth->checkSessionTimeout()) {
        $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';
        if ($role === 'admin') {
            header('Location: ./admin/index.php');
        } elseif ($role === 'owner') {
            header('Location: ./owner/dashboard.php');
        } else {
            header('Location: ./user/dashboard.php');
        }
        exit;
    }
}

/** @var mysqli $conn */
$db = new Database($conn);
$auth = new AuthController($db->getConnection());

$message = '';
$type = '';
$name = '';
$email = '';





if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    
    $name = strip_tags($name);
    $email = strip_tags($email);

    $password = $_POST['password'] ?? '';

    
    $confirm = $_POST['confirm_password'] ?? '';

    $captcha = trim($_POST['captcha'] ?? '');

    

    if (
        empty($name) ||
        empty($email) ||
        empty($password) ||
        empty($confirm)
    ) {

        $message = 'Vui lòng nhập đầy đủ thông tin';
        $type = 'error';
    }

    
    elseif (strlen($name) < 3) {

        $message = 'Họ tên phải từ 3 ký tự';
        $type = 'error';
    }

    
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = 'Email không hợp lệ';
        $type = 'error';
    }

    
    elseif (strlen($password) < 6) {

        $message = 'Mật khẩu phải từ 6 ký tự';
        $type = 'error';
    }

 
    elseif (
        !preg_match('/[A-Z]/', $password) ||
        !preg_match('/[0-9]/', $password)
    ) {

        $message = 'Mật khẩu phải có ít nhất 1 chữ hoa và 1 số';
        $type = 'error';
    }

 
    elseif ($password !== $confirm) {

        $message = 'Xác nhận mật khẩu không đúng';
        $type = 'error';
    }

 
    elseif (!Captcha::validate('register_captcha', $captcha)) {

        $message = 'Mã xác thực không đúng';
        $type = 'error';
    }

    else {

       

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE email = ?
        ");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $checkEmail = $stmt->get_result();

        if ($checkEmail->num_rows > 0) {

            $message = 'Email đã tồn tại';
            $type = 'error';
        }

        else {


            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

        

            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    name,
                    email,
                    password,
                    role,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    'user',
                    'active'
                )
            ");

            $stmt->bind_param(
                "sss",
                $name,
                $email,
                $hashedPassword
            );

            if ($stmt->execute()) {

               
                session_regenerate_id(true);

                $message = 'Đăng ký thành công';
                $type = 'success';

              
                $name = '';
                $email = '';

            } else {

                $message = 'Có lỗi xảy ra khi đăng ký';
                $type = 'error';
            }
        }
    }
}









$captchaChallenge = Captcha::ensure('register_captcha');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký người thuê - QuanLyPhongTro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css?v=auth-captcha-2" rel="stylesheet">

<link href="assets/css/modern.css?v=auth-captcha-2" rel="stylesheet">

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



<style>

.password-wrapper{
    position:relative;
}

.password-wrapper .toggle-password{

    position:absolute;

    right:20px;

    top:50%;

    transform:translateY(-50%);

    cursor:pointer;

    color:#666;

    font-size:18px;

    z-index:999999;

}

.password-wrapper input{

    padding-right:55px !important;

}

</style>














</head>
<body class="auth-dark">
    <div class="three-stage auth-scene" data-three-scene data-scene="housing" data-accent="#2563eb" data-accent2="#8b5cf6"></div>

    <main class="auth-3d-page">
        <a href="index.php" class="auth-home-link"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <div class="auth-3d-shell">
            <section class="auth-3d-copy">
                <div class="eyebrow"><i class="fas fa-magnifying-glass-location"></i> Tìm phòng nhanh hơn</div>
                <h1>Lưu phòng đẹp và đặt lịch xem dễ dàng.</h1>
                <p>
                    Người thuê có thể lưu bộ lọc, yêu thích phòng, xem chi phí vào ở và gửi yêu cầu đặt phòng.
                </p>
                <div class="auth-3d-points">
                    <div class="auth-3d-point"><strong>Tìm</strong><span>Lọc theo khu vực và giá</span></div>
                    <div class="auth-3d-point"><strong>Lưu</strong><span>Phòng và bộ lọc yêu thích</span></div>
                    <div class="auth-3d-point"><strong>Đặt</strong><span>Lịch xem hoặc booking</span></div>
                </div>
            </section>

            <section class="auth-card-3d">
                <div class="auth-card-head">
                    <div class="brand-mark"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <h2>Đăng ký người thuê</h2>
                        <p class="subtitle">Lưu phòng yêu thích, đặt lịch xem và theo dõi booking.</p>
                    </div>
                </div>

                <?php if ($message !== ''): ?>
                    <div class="msg <?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label>Họ tên</label>
                    <div class="input-group">
                        <i class="fa fa-user"></i>
                        <input type="text" name="name" placeholder="Nguyễn Văn A" value="<?php echo htmlspecialchars($name); ?>" required>
                    </div>

                    <label>Email</label>
                    <div class="input-group">
                        <i class="fa fa-envelope"></i>
                        <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                  

<label>Mật khẩu</label>

<div class="password-wrapper">

    <div class="input-group">

        <i class="fa fa-lock"></i>

        <input
            type="password"
            name="password"
            id="password"
            placeholder="Ít nhất 6 ký tự"
            required
        >

    </div>

    <span class="toggle-password"
          id="togglePassword">

        <i class="fa fa-eye"></i>

    </span>

</div>



                 

<label>Xác nhận mật khẩu</label>

<div class="password-wrapper">

    <div class="input-group">

        <i class="fa fa-lock"></i>

        <input
            type="password"
            name="confirm_password"
            id="confirm_password"
            placeholder="Nhập lại mật khẩu"
            required
        >

    </div>

    <span class="toggle-password"
          id="toggleConfirmPassword">

        <i class="fa fa-eye"></i>

    </span>

</div>




                    <label>Mã xác thực</label>
                    <div class="captcha-widget">
                        <img class="captcha-image" src="captcha.php?key=register_captcha&v=<?php echo time(); ?>" alt="Mã xác thực">
                        <button type="button" class="captcha-refresh" aria-label="Đổi mã xác thực" onclick="refreshCaptcha(this)">
                            <i class="fa fa-rotate-right"></i>
                        </button>
                        <div class="input-group captcha-input">
                            <i class="fa fa-shield-halved"></i>
                            <input type="text" name="captcha" autocomplete="off" placeholder="Nhập mã" required>
                        </div>
                    </div>

                    <button type="submit"><i class="fas fa-user-plus"></i> Tạo tài khoản</button>
                </form>

                <div class="links">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a><br>
                    Là chủ phòng? <a href="owner-register.php">Đăng ký owner</a>
                </div>
            </section>
        </div>
    </main>

    <script type="module" src="assets/js/three-interface.js"></script>
    <script>
        function refreshCaptcha(button) {
            const image = button.parentElement.querySelector('.captcha-image');
            image.src = image.src.split('&v=')[0] + '&v=' + Date.now();
            button.parentElement.querySelector('input[name="captcha"]').value = '';
        }

const togglePassword = document.getElementById('togglePassword');
const password = document.getElementById('password');

togglePassword.onclick = function () {

    const icon = this.querySelector('i');

    if(password.type === "password"){

        password.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");

    }else{

        password.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}

const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
const confirmPassword = document.getElementById('confirm_password');

toggleConfirmPassword.onclick = function () {

    const icon = this.querySelector('i');

    if(confirmPassword.type === "password"){

        confirmPassword.type = "text";
        icon.classList.replace("fa-eye", "fa-eye-slash");

    }else{

        confirmPassword.type = "password";
        icon.classList.replace("fa-eye-slash", "fa-eye");
    }
}






    </script>
</body>
</html>
