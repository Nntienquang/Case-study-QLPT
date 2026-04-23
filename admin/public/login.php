<?php
/**
 * PHASE 4: Login Page
 */

require_once '../config/database.php';
require_once '../includes/middleware.php';
require_once '../includes/helpers.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user'])) {
    header('Location: /QuanLyPhongTro/admin/public/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = '❌ Vui lòng nhập email và mật khẩu';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Simple password check (in real app, use password_hash)
            if ($user && $password === $user['password']) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ];
                header('Location: /QuanLyPhongTro/admin/public/index.php');
                exit();
            } else {
                $error = '❌ Email hoặc mật khẩu không đúng (hoặc không phải admin)';
            }
        } catch (PDOException $e) {
            $error = '❌ Lỗi database: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập Admin - Quản Lý Phòng Trọ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #5B6FFF;
            --secondary-color: #1FB5A0;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            width: 100%;
            max-width: 420px;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-header h1 {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: #6B7280;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-label {
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 2px solid #E5E7EB;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(91, 111, 255, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #9CA3AF;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #6B7FFF);
            border: none;
            padding: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 8px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(91, 111, 255, 0.3);
        }

        .btn-login:hover {
            box-shadow: 0 8px 24px rgba(91, 111, 255, 0.4);
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .test-accounts {
            background: linear-gradient(135deg, #F0F9FF 0%, #E0F7FF 100%);
            border-left: 4px solid var(--primary-color);
            padding: 18px;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-top: 25px;
            line-height: 1.6;
        }

        .test-accounts strong {
            color: var(--primary-color);
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .test-accounts code {
            background: rgba(91, 111, 255, 0.1);
            padding: 4px 8px;
            border-radius: 4px;
            color: var(--primary-color);
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .alert {
            border: none;
            border-left: 4px solid #FF6B6B;
            background: linear-gradient(90deg, rgba(255, 107, 107, 0.1), rgba(255, 136, 136, 0.1));
            color: #7a1d1d;
            border-radius: 8px;
            padding: 14px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="bi bi-speedometer2"></i> Admin</h1>
            <p>Hệ Thống Quản Lý Phòng Trọ</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mật Khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100">
                <i class="bi bi-box-arrow-in-right"></i> Đăng Nhập
            </button>
        </form>

        <div class="test-accounts">
            <strong>👤 Tài Khoản Test:</strong><br>
            Email: <code>admin@gmail.com</code><br>
            Mật khẩu: <code>123</code>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
