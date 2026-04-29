<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';

session_start();

// Check if logged in - redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    /** @var mysqli $conn */
    $db = new Database($conn);
    
    if ($_SESSION['role'] === 'admin') {
        header('Location: ./admin/index.php');
    } elseif ($_SESSION['role'] === 'owner') {
        header('Location: ./owner/dashboard.php');
    } else {
        header('Location: ./dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuanLyPhongTro - Tìm Phòng Trọ Dễ Dàng & An Toàn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }
        
        /* Header/Navbar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: white !important;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            margin-left: 20px;
            transition: 0.3s;
        }
        
        .navbar-nav .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        .btn-login, .btn-register {
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            margin-left: 10px;
            transition: 0.3s;
        }
        
        .btn-login {
            border: 2px solid white;
            color: white;
            background: transparent;
        }
        
        .btn-login:hover {
            background: white;
            color: #667eea;
        }
        
        .btn-register {
            background: white;
            color: #667eea;
            border: none;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .hero p {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        /* Search Section */
        .search-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: -40px;
            position: relative;
            z-index: 10;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .search-section .form-group {
            margin-bottom: 15px;
        }
        
        .search-section label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .search-section input, .search-section select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }
        
        .search-section input:focus, .search-section select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102,126,234,0.3);
        }
        
        .btn-search {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background: white;
        }
        
        .features h2 {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 60px;
            color: #333;
        }
        
        .feature-card {
            text-align: center;
            padding: 40px 30px;
            border-radius: 12px;
            transition: 0.3s;
            background: #f8f9fa;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
            background: white;
        }
        
        .feature-icon {
            font-size: 48px;
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        .cta-section p {
            font-size: 16px;
            margin-bottom: 40px;
        }
        
        .btn-cta {
            padding: 12px 40px;
            background: white;
            color: #667eea;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin: 0 10px;
        }
        
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer h5 {
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: 0.3s;
        }
        
        .footer a:hover {
            color: white;
            margin-left: 5px;
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            text-align: center;
            margin-top: 40px;
            color: rgba(255,255,255,0.7);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-home"></i> QuanLyPhongTro
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#listings">Duyệt Phòng</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">Về Chúng Tôi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Liên Hệ</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-login" href="login.php">Đăng Nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-register" href="register.php">Đăng Ký</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="container-lg">
            <h1>🏠 Tìm Phòng Trọ Hoàn Hảo</h1>
            <p>Nền tảng tìm kiếm phòng trọ an toàn, nhanh chóng và tin cậy</p>
        </div>
        
        <!-- Search Section -->
        <div class="container-lg">
            <div class="search-section">
                <form method="GET" action="listings.php">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Quận/Huyện</label>
                                <select name="district" class="form-select">
                                    <option value="">-- Chọn Quận --</option>
                                    <option value="1">Quận 1</option>
                                    <option value="2">Quận 3</option>
                                    <option value="3">Quận 7</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Giá từ - đến</label>
                                <input type="number" name="price_min" placeholder="Giá tối thiểu">
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-search">
                                <i class="fas fa-search"></i> Tìm Kiếm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features" id="about">
        <div class="container-lg">
            <h2>Tại Sao Chọn QuanLyPhongTro?</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3>Danh Sách Đáng Tin Cậy</h3>
                        <p>Tất cả phòng trọ được xác minh và kiểm duyệt bởi đội ngũ của chúng tôi</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <h3>Tìm Kiếm Nhanh</h3>
                        <p>Lọc theo khoảng giá, vị trí, loại phòng và tiện ích phù hợp với nhu cầu của bạn</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3>An Toàn & Bảo Mật</h3>
                        <p>Bảo vệ thông tin cá nhân của bạn với công nghệ mã hóa hiện đại</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Liên Lạc Trực Tiếp</h3>
                        <p>Gửi tin nhắn trực tiếp cho chủ phòng mà không cần tiết lộ số điện thoại</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-camera"></i>
                        </div>
                        <h3>Hình Ảnh & Video</h3>
                        <p>Xem các hình ảnh chất lượng cao và video của phòng trọ từ nhiều góc độ</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3>Đánh Giá & Review</h3>
                        <p>Đọc các đánh giá từ những người đã sống tại phòng để đưa ra quyết định tốt</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container-lg">
            <h2>Sẵn Sàng Tìm Phòng Của Bạn?</h2>
            <p>Tham gia hàng ngàn người đang tìm kiếm phòng trọ lý tưởng trên QuanLyPhongTro</p>
            <div>
                <a href="register.php" class="btn-cta">Đăng Ký Ngay</a>
                <a href="login.php" class="btn-cta">Đăng Nhập</a>
            </div>
            <div style="margin-top: 40px;">
                <p style="font-size: 18px; margin-bottom: 15px;">🏠 Bạn là chủ phòng trọ?</p>
                <a href="owner-register.php" class="btn-cta">Đăng Ký Làm Chủ Phòng</a>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container-lg">
            <div class="row">
                <div class="col-md-3">
                    <h5>QuanLyPhongTro</h5>
                    <p>Nền tảng tìm kiếm phòng trọ tăng năng số 1 Việt Nam</p>
                </div>
                <div class="col-md-3">
                    <h5>Dành Cho Người Thuê</h5>
                    <a href="register.php">Đăng Ký</a>
                    <a href="login.php">Đăng Nhập</a>
                    <a href="listings.php">Duyệt Phòng</a>
                </div>
                <div class="col-md-3">
                    <h5>Dành Cho Chủ Phòng</h5>
                    <a href="owner-register.php">Đăng Ký</a>
                    <a href="forgot.php">Quên Mật Khẩu</a>
                    <a href="#">Hướng Dẫn</a>
                </div>
                <div class="col-md-3">
                    <h5>Liên Hệ</h5>
                    <p>Email: support@quantyphongro.com</p>
                    <p>Hotline: 1900-xxxx</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 QuanLyPhongTro. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
