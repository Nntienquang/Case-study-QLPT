<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin tức & Cẩm nang - QuanLyPhongTro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuanLyPhongTro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        a {
            text-decoration: none !important;
        }

        .home-nav {
            width: 100%;
            position: fixed;
            top: 20px;
            left: 0;
            z-index: 999;
            display: flex;
            justify-content: center;
        }

        .rooms-section {
            padding-top: 120px !important;
            padding-bottom: 50px;
        }

        .grid-rooms {
            display: grid;

            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }


        .btn-load-more {
            background-color: #ffffff;
            border: 1px solid #111827;
            color: #111827;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }



        body {
            background: #f6f8fb !important;
            color: #172033;
            overflow-x: hidden;
        }

        .home-nav {
            position: fixed;
            inset: 18px 0 auto;
            z-index: 30;
        }

        .nav-frame {
            height: 66px;
            padding: 0 16px 0 20px;
            border-radius: 18px;
            background: rgba(255, 255, 255, .82);
            border: 1px solid rgba(255, 255, 255, .78);
            box-shadow: 0 18px 50px rgba(15, 23, 42, .12);
            backdrop-filter: blur(18px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #101828;
            text-decoration: none;
            font-weight: 900;
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #101828;
            color: #fff;
        }

        box-shadow: 0 18px 50px rgba(15, 23, 42, .08);

        .footer {
            padding: 34px 0;
            border-top: 1px solid #e5eaf2;
            color: #667085;
        }

        .footer a {
            color: #475467;
            text-decoration: none;
            margin-left: 18px;
            font-weight: 750;
        }


        .home-nav {
            width: 100%;
            position: absolute;
            top: 20px;
            left: 0;
            z-index: 100;
            display: flex;
            justify-content: center;
        }


        .nav-container {
            background-color: #ffffff;
            width: 90%;
            max-width: 1200px;
            border-radius: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px 10px 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
        }

        .nav-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #1185a1;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .login-text {
            color: #111827;
        }

        .btn-post {
            background-color: #111827;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 24px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-post:hover {
            background-color: #1f2937;
            transform: translateY(-2px);
            bo x-shadow: 0 4px 12px rgba(17, 24, 39, 0.3);
        }

        .hamburger-btn {
            display: none;
            background: transparent;
            border: none;
            font-size: 24px;
            color: #111827;
            cursor: pointer;
        }

        .menu-content {
            display: flex;
            flex-grow: 1;
            justify-content: space-between;
            align-items: center;
        }

        .menu-content {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
        }


        .nav-links {
            display: flex;
            gap: 45px;
            margin-left: auto;
            margin-right: auto;
        }


        .nav-item {
            text-decoration: none;
            color: #1f2937;
            font-size: 18px;
            font-weight: 600;
            padding: 10px 0;
            transition: color 0.2s ease, transform 0.2s ease;
            display: inline-block;
        }


        .nav-item:hover {
            color: #000;
            transform: translateY(-1px);
        }


        .nav-actions {
            min-width: 200px;
            display: flex;
            justify-content: flex-end;
            gap: 20px;
        }


        @media (max-width: 991px) {
            .hamburger-btn {
                display: block;
            }


            .menu-content {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #ffffff;
                border-radius: 24px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
                padding: 20px;
                margin-top: 15px;
            }


            .menu-content.show {
                display: flex;
            }


            .nav-links,
            .nav-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                width: 100%;
            }


            .nav-actions {
                margin-top: 15px;
                padding-top: 20px;
                border-top: 1px solid #f3f4f6;
                align-items: stretch;
            }

            .btn-post {
                text-align: center;
            }
        }

        .bg-light-gray {
            background-color: #f9fafb;
        }

        .text-dark-blue {
            color: #111827;
        }

        .text-teal {
            color: #1185a1;
        }
    </style>
</head>

<body>
    <nav class="home-nav">
        <div class="nav-container">


            <a href="index.php" class="brand">
                <span class="brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span class="brand-name">QuanLyPhongTro</span>
            </a>


            <button class="hamburger-btn" id="mobile-menu-btn">
                <i class="fas fa-bars"></i>
            </button>

            <div class="menu-content" id="mobile-menu">

                <div class="nav-links">
                    <a href="phongtro.php" class="nav-item">Phòng trọ</a>
                    <a href="khuvuc.php" class="nav-item">Khu vực</a>
                    <a href="blog.php" class="nav-item">Tin tức</a>
                    <a href="trogiup.php" class="nav-item">Trợ giúp</a>
                </div>

                <div class="nav-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="user-dropdown">
                            <button class="user-btn" type="button" onclick="togglePublicDropdown(event)">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['user_name'] ?? 'User'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu" id="public-user-dropdown">
                                <?php if (($_SESSION['role'] ?? $_SESSION['user_role'] ?? '') === 'owner'): ?>
                                    <a href="owner/dashboard.php" class="dropdown-item"><i class="fas fa-chart-line"></i> Dashboard</a>
                                    <a href="owner/listings.php" class="dropdown-item"><i class="fas fa-home"></i> Phòng của tôi</a>
                                    <a href="owner/bookings.php" class="dropdown-item"><i class="fas fa-calendar"></i> Booking</a>
                                    <a href="owner/profile.php" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ</a>
                                <?php else: ?>
                                    <a href="user/dashboard.php" class="dropdown-item"><i class="fas fa-chart-line"></i> Dashboard</a>
                                    <a href="user/my-bookings.php" class="dropdown-item"><i class="fas fa-calendar"></i> Booking của tôi</a>
                                    <a href="user/saved-motels.php" class="dropdown-item"><i class="fas fa-heart"></i> Yêu thích</a>
                                    <a href="user/profile.php" class="dropdown-item"><i class="fas fa-user"></i> Hồ sơ</a>
                                <?php endif; ?>
                                <hr class="dropdown-divider">
                                <a href="logout.php" class="dropdown-item logout"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="nav-item login-text">Đăng nhập</a>
                        <a href="owner-register.php" class="btn-post">Đăng phòng</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </nav>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    require_once '../config/database.php';

    $category_slug = isset($_GET['category']) ? $_GET['category'] : '';

    $cat_sql = "SELECT * FROM news_categories ORDER BY id ASC";
    $categories = $conn->query($cat_sql)->fetch_all(MYSQLI_ASSOC);

    $where_clause = "WHERE a.status = 'published'";
    if ($category_slug !== '') {
        $where_clause .= " AND c.slug = '" . $conn->real_escape_string($category_slug) . "'";
    }

    $articles_sql = "SELECT a.*, c.name as category_name, c.slug as cat_slug 
                            FROM articles a 
                            LEFT JOIN news_categories c ON a.category_id = c.id
                            $where_clause
                            ORDER BY a.published_at DESC LIMIT 9";
    $articles = $conn->query($articles_sql)->fetch_all(MYSQLI_ASSOC);

    $featured_article = null;
    if ($category_slug === '' && !empty($articles)) {
        $featured_article = array_shift($articles);
    }
    ?>


    <style>
        body {
            background-color: #f8f9fa;
        }

        .category-pills .nav-link {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            padding: 10px 24px;
            font-size: 0.95rem;
        }

        .category-pills .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .blog-hero {
            position: relative;
            margin-top: -120px;
            padding-top: 180px;
            padding-bottom: 60px;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            margin-bottom: 40px;
            z-index: 1;
            overflow: hidden;
        }

        .blog-hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: left center;
            z-index: -2;
            animation: panBackground 25s ease-in-out infinite alternate;
        }

        .blog-hero h1,
        .rooms-hero h1 {
            color: #1a1d20 !important;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.8), 0 0 10px rgba(255, 255, 255, 0.5);
            position: relative;
        }

        .blog-hero p,
        .rooms-hero p {
            color: #343a40 !important;
            font-weight: 500;
            text-shadow: 0 0 15px rgba(255, 255, 255, 0.9);
        }

        .blog-hero::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.25) 0%, rgba(248, 249, 250, 1) 100%);
            z-index: -1;
        }

        @keyframes panBackground {
            0% {
                background-position: 0% 50%;
                transform: scale(1.1);
            }

            100% {
                background-position: 100% 50%;
                transform: scale(1.75);
            }
        }

        .blog-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        /* Xử lý ảnh bìa */
        .blog-img-wrap {
            position: relative;
            padding-top: 60%;
            /* Tỉ lệ 16:9 */
            overflow: hidden;
        }

        .blog-img-wrap img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .blog-card:hover .blog-img-wrap img {
            transform: scale(1.05);
        }

        /* Tag danh mục nổi trên ảnh */
        .blog-category-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        /* Nội dung bài viết */
        .blog-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .blog-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #2c3e50;
            text-decoration: none;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            /* Cắt chữ sau 2 dòng */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .blog-title:hover {
            color: #0d6efd;
        }

        .blog-summary {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }

        .blog-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #adb5bd;
            border-top: 1px solid #f1f1f1;
            padding-top: 15px;
            margin-top: auto;
        }

        /* Nút phân loại */
        .category-pills .nav-link {
            color: #495057;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 50px;
            padding: 8px 20px;
            margin-right: 10px;
            margin-bottom: 10px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .category-pills .nav-link.active,
        .category-pills .nav-link:hover {
            background-color: #212529;
            /* Tông đen/đậm giống Header của bạn */
            color: #fff;
            border-color: #212529;
        }
    </style>
    </head>

    <body>

        <!-- GIẢ SỬ ĐÂY LÀ PHẦN HEADER ĐƯỢC INCLUDE VÀO -->
        <!-- <?php include 'includes/header.php'; ?> -->

        <div class="container my-5 pt-5">

            <div class="blog-hero">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="badge bg-white text-primary border border-primary-subtle rounded-pill px-3 py-2 mb-3 shadow-sm" style="font-weight: 500;">
                            <i class="fas fa-fire me-1 text-warning"></i> Cập nhật liên tục
                        </span>
                        <h1 class="fw-bold mb-3" style="color: #1a1d20; font-size: 2.8rem; letter-spacing: -0.5px;">
                            Góc Tin Tức & Cẩm Nang
                        </h1>
                        <p class="text-secondary mx-auto" style="max-width: 600px; font-size: 1.1rem; line-height: 1.6;">
                            Tuyển tập những kinh nghiệm xương máu, mẹo hay và kiến thức pháp lý không thể bỏ qua dành cho người thuê trọ.
                        </p>
                    </div>

                    <!-- Thanh Danh mục (Lọc bài viết) -->
                    <ul class="nav nav-pills category-pills justify-content-center">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($category_slug === '') ? 'active' : ''; ?>" href="blog.php">
                                <i class="fas fa-layer-group me-1"></i> Tất cả
                            </a>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($category_slug === $cat['slug']) ? 'active' : ''; ?>"
                                    href="blog.php?category=<?php echo $cat['slug']; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <?php if ($featured_article): ?>
                <!-- BÀI VIẾT NỔI BẬT (FEATURED) -->
                <div class="card border-0 rounded-4 overflow-hidden shadow-sm mb-5" style="background: #fff;">
                    <div class="row g-0">
                        <div class="col-lg-7">
                            <div style="height: 100%; min-height: 350px; position: relative;">
                                <img src="<?php echo $featured_article['thumbnail']; ?>" class="w-100 h-100 object-fit-cover" alt="Featured image" style="position: absolute; top:0; left:0;">
                            </div>
                        </div>
                        <div class="col-lg-5 p-4 p-md-5 d-flex flex-column justify-content-center">
                            <span class="badge bg-primary mb-3 align-self-start px-3 py-2 rounded-pill">
                                <?php echo htmlspecialchars($featured_article['category_name']); ?>
                            </span>
                            <h2 class="fw-bold mb-3">
                                <a href="blog_detail.php?slug=<?php echo $featured_article['slug']; ?>" class="text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($featured_article['title']); ?>
                                </a>
                            </h2>
                            <p class="text-secondary mb-4" style="line-height: 1.6;">
                                <?php echo htmlspecialchars($featured_article['summary']); ?>
                            </p>
                            <div class="d-flex align-items-center text-muted small mt-auto">
                                <div class="me-3"><i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($featured_article['author_name']); ?></div>
                                <div class="me-3"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d/m/Y', strtotime($featured_article['published_at'])); ?></div>
                                <div><i class="far fa-eye me-1"></i> <?php echo $featured_article['views']; ?> lượt xem</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- LƯỚI BÀI VIẾT THÔNG THƯỜNG -->
            <div class="row g-4">
                <?php if (count($articles) > 0): ?>
                    <?php foreach ($articles as $article): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="blog-card">
                                <div class="blog-img-wrap">
                                    <a href="blog_detail.php?slug=<?php echo $article['slug']; ?>">
                                        <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                    </a>
                                    <span class="blog-category-badge"><?php echo htmlspecialchars($article['category_name']); ?></span>
                                </div>
                                <div class="blog-content">
                                    <a href="blog_detail.php?slug=<?php echo $article['slug']; ?>" class="blog-title">
                                        <?php echo htmlspecialchars($article['title']); ?>
                                    </a>
                                    <div class="blog-summary">
                                        <?php echo htmlspecialchars($article['summary']); ?>
                                    </div>
                                    <div class="blog-footer">
                                        <span><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($article['published_at'])); ?></span>
                                        <span><i class="far fa-eye"></i> <?php echo $article['views']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-secondary">Chưa có bài viết nào trong danh mục này.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Nút Hiển thị thêm -->
            <?php if (count($articles) >= 6): ?>
                <div class="text-center mt-5">
                    <button class="btn btn-outline-dark rounded-pill px-5 py-2 fw-bold">
                        Xem thêm bài viết <i class="fas fa-chevron-down ms-1"></i>
                    </button>
                </div>
            <?php endif; ?>

        </div>

        <footer class="footer py-5 mt-5 bg-dark text-white">
            <style>
                /* Tối ưu nền và độ sáng */
                .footer {
                    background-color: #121416 !important;
                    /* Đen sâu hơn để chữ nổi lên */
                    border-top: 1px solid #2d3238;
                }

                /* Làm sáng mô tả và thông tin liên hệ */
                .footer-description,
                .footer-info,
                .footer-copyright {
                    color: #ced4da !important;
                    /* Màu xám bạc sáng, rất rõ trên nền đen */
                    font-size: 0.95rem;
                    line-height: 1.6;
                }

                /* Tùy chỉnh các đường link */
                .footer-link {
                    color: #adb5bd !important;
                    text-decoration: none;
                    transition: all 0.3s ease;
                    display: inline-block;
                    padding: 2px 0;
                }

                .footer-link:hover {
                    color: #ffffff !important;
                    /* Khi di chuột vào sẽ sáng trắng rực rỡ */
                    transform: translateX(5px);
                    /* Hiệu ứng trượt nhẹ sang phải */
                }

                /* Đường kẻ ngăn cách */
                .footer-divider {
                    border-top: 1px solid rgba(255, 255, 255, 0.1);
                }

                /* Icon mạng xã hội */
                .social-links a {
                    color: #ffffff;
                    font-size: 1.2rem;
                    opacity: 0.7;
                    transition: 0.3s;
                }

                .social-links a:hover {
                    opacity: 1;
                    color: #0d6efd;
                    /* Đổi màu xanh khi hover */
                }

                /* Nút Admin sáng hơn */
                .btn-light {
                    background-color: #f8f9fa;
                    border: none;
                    color: #212529 !important;
                }

                .btn-light:hover {
                    background-color: #ffffff;
                    box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
                }
            </style>
            <div class="container-lg">
                <div class="row gy-4">
                    <!-- Cột 1: Giới thiệu -->
                    <div class="col-lg-4 col-md-6">
                        <h5 class="fw-bold mb-3 text-white">🏠 QuanLyPhongTro</h5>
                        <p class="footer-description">Tìm phòng sạch đẹp, quản lý thuê trọ dễ dàng và minh bạch. Nền tảng kết nối chủ trọ và người thuê hàng đầu.</p>
                        <div class="social-links mt-3">
                            <a href="#" class="me-3"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="me-3"><i class="bi bi-instagram"></i></a>
                            <a href="#"><i class="bi bi-twitter-x"></i></a>
                        </div>
                    </div>

                    <!-- Cột 2: Dành cho người thuê -->
                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-3 text-white text-uppercase small">Người Thuê</h6>
                        <ul class="list-unstyled">
                            <li><a href="index.php" class="footer-link">Tìm phòng trọ</a></li>
                            <li><a href="register.php" class="footer-link">Đăng ký tài khoản</a></li>
                            <li><a href="login.php" class="footer-link">Đăng nhập</a></li>
                        </ul>
                    </div>


                    <div class="col-lg-2 col-md-6">
                        <h6 class="fw-bold mb-3 text-white text-uppercase small">Chủ Phòng</h6>
                        <ul class="list-unstyled">
                            <li><a href="owner-register.php" class="footer-link">Đăng tin cho thuê</a></li>
                            <li><a href="login.php" class="footer-link">Quản lý phòng</a></li>
                            <li><a href="policies/owner-policy.php" class="footer-link">Chính sách chủ trọ</a></li>
                        </ul>
                    </div>


                    <div class="col-lg-4 col-md-6">
                        <h6 class="fw-bold mb-3 text-white text-uppercase small">Liên hệ hệ thống</h6>
                        <p class="footer-info"><i class="bi bi-geo-alt-fill me-2"></i> Trường Đại học Vinh, Nghệ An</p>
                        <p class="footer-info"><i class="bi bi-envelope-fill me-2"></i> ho-tro@quanlyphongtro.vn</p>
                        <a href="login.php?area=admin" class="btn btn-light btn-sm fw-bold px-3 mt-2">Quản trị viên (Admin)</a>
                    </div>
                </div>

                <hr class="my-4 footer-divider">

                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="footer-copyright">
                        © 2026 <strong>QuanLyPhongTro</strong>. Bản quyền thuộc về Team dự án.
                    </div>
                    <div class="footer-bottom-links">
                        <a href="policies/user-policy.php" class="footer-link me-3">Chính sách người thuê</a>
                        <a href="policies/payment-policy.php" class="footer-link">Chính sách thanh toán</a>
                    </div>
                </div>
            </div>
        </footer>
        <script>
            function togglePublicDropdown(event) {
                event.stopPropagation();
                const dropdown = document.getElementById('public-user-dropdown');
                if (dropdown) {
                    dropdown.classList.toggle('active');
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                const menuBtn = document.getElementById('mobile-menu-btn');
                const menuContent = document.getElementById('mobile-menu');
                if (menuBtn && menuContent) {
                    menuBtn.addEventListener('click', function() {
                        menuContent.classList.toggle('show');
                        const icon = menuBtn.querySelector('i');
                        if (icon) {
                            icon.classList.toggle('fa-bars');
                            icon.classList.toggle('fa-times');
                        }
                    });
                }
                document.addEventListener('click', function(e) {
                    const dropdown = document.getElementById('public-user-dropdown');
                    if (dropdown && !e.target.closest('.user-dropdown')) {
                        dropdown.classList.remove('active');
                    }
                });
            });
        </script>
    </body>

</html>
