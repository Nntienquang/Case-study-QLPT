<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/track_page_view.php';
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
    <?php
require_once __DIR__ . '/components/PublicNav.php';
qlpt_render_public_nav(['base' => './', 'active' => 'blog']);
?>
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
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $search_term = $conn->real_escape_string(trim($_GET['search']));
        $where_clause .= " AND (a.title LIKE '%$search_term%' OR a.summary LIKE '%$search_term%')";
    }

    $articles_sql = "SELECT a.*, c.name as category_name, c.slug as cat_slug 
                            FROM articles a 
                            LEFT JOIN news_categories c ON a.category_id = c.id
                            $where_clause
                            ORDER BY a.published_at DESC LIMIT 9";
    $articles = $conn->query($articles_sql)->fetch_all(MYSQLI_ASSOC);

    // FETCH HOT MOTELS
    $hot_motels_sql = "SELECT m.id, m.title, m.price, m.address, 
                      (SELECT image_url FROM motel_images WHERE motel_id = m.id LIMIT 1) as thumbnail 
                      FROM motels m WHERE m.status = 'approved' ORDER BY m.id DESC LIMIT 3";
    $hot_motels = $conn->query($hot_motels_sql)->fetch_all(MYSQLI_ASSOC);

    // MOCK DATA IF NO ARTICLES
    if (empty($articles)) {
        $articles = [
            [
                'slug' => '#',
                'title' => 'Kinh nghiệm tìm trọ khu vực quanh Đại học Vinh không qua môi giới',
                'thumbnail' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'summary' => 'Nhiều tân sinh viên CNTT thường gặp khó khăn khi tìm phòng trọ tự chủ mà không qua cò mồi. Dưới đây là những kinh nghiệm xương máu giúp bạn tránh mất tiền oan.',
                'category_name' => 'Cẩm nang',
                'published_at' => date('Y-m-d H:i:s'),
                'views' => 1250,
                'author_name' => 'Admin IT'
            ],
            [
                'slug' => '#',
                'title' => 'Cách setup góc học tập cho sinh viên IT trong phòng trọ 15m2',
                'thumbnail' => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'summary' => 'Diện tích nhỏ không có nghĩa là góc làm việc bừa bộn. Khám phá cách bài trí màn hình đôi, laptop và dây cáp gọn gàng chỉ với vài món đồ thông minh.',
                'category_name' => 'Kinh nghiệm',
                'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'views' => 980,
                'author_name' => 'Hacker X'
            ],
            [
                'slug' => '#',
                'title' => 'Hợp đồng thuê trọ: 5 điểm pháp lý dân IT cần lưu ý',
                'thumbnail' => 'https://images.unsplash.com/photo-1450101499163-c8848c66cb85?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                'summary' => 'Đừng cắm đầu vào code mà quên đọc hợp đồng! Hãy check ngay 5 điều khoản về tiền cọc, chi phí phát sinh và điều khoản phá vỡ hợp đồng.',
                'category_name' => 'Pháp lý',
                'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'views' => 3102,
                'author_name' => 'Luật sư Trọ'
            ]
        ];
    }

    $featured_article = null;
    if ($category_slug === '' && !empty($articles)) {
        $featured_article = array_shift($articles);
    }
    ?>


    <style>
        body { background-color: #f8f9fa; }
        .blog-hero {
            position: relative;
            margin-top: -120px;
            padding-top: 180px;
            padding-bottom: 80px;
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            z-index: 1;
            background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
        }
        .blog-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(17, 24, 39, 0.75); /* Dark overlay */
            z-index: -1;
        }
        .blog-hero h1 {
            color: #ffffff !important;
            font-weight: 800;
            text-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .blog-hero p {
            color: #e5e7eb !important;
            font-size: 1.15rem;
        }
        
        .category-pills .nav-link {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 8px 24px;
            margin: 0 5px 10px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .category-pills .nav-link:hover, .category-pills .nav-link.active {
            background-color: #ffffff;
            color: #111827;
            border-color: #ffffff;
        }

        /* E-commerce Style Cards */
        .article-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            height: 100%;
            display: flex;
            flex-direction: column;
            text-decoration: none !important;
        }
        .article-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .article-img-wrap {
            position: relative;
            aspect-ratio: 16/9;
            overflow: hidden;
        }
        .article-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        .article-card:hover .article-img-wrap img {
            transform: scale(1.05);
        }
        .article-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: #111827;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .article-content {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .article-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            transition: color 0.3s ease;
        }
        .article-card:hover .article-title {
            color: #2563eb;
        }
        .article-summary {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            flex-grow: 1;
        }
        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.8rem;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }

        /* Sidebar Styling */
        .sidebar-widget {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
            border: 1px solid #f1f5f9;
        }
        .sidebar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mini-article {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
            align-items: center;
            text-decoration: none !important;
        }
        .mini-article:last-child {
            margin-bottom: 0;
        }
        .mini-article img {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
        }
        .mini-article-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
            transition: color 0.3s ease;
        }
        .mini-article:hover .mini-article-title { color: #2563eb; }

        .search-input {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 16px 12px 40px;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        /* Cross-sell Motel Card */
        .hot-motel-card {
            display: block;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            margin-bottom: 12px;
            text-decoration: none !important;
        }
        .hot-motel-card img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .hot-motel-card:hover img {
            transform: scale(1.05);
        }
        .hot-motel-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 12px;
            color: #fff;
        }
        .hot-motel-price {
            font-weight: 700;
            color: #fbbf24; /* Warning/Yellow color */
            font-size: 1.05rem;
        }
    </style>
    </head>

    <body>

        <!-- HEADER IS INCLUDED ABOVE -->

        <div class="blog-hero">
            <div class="container">
                <div class="text-center">
                    <span class="badge bg-primary text-white rounded-pill px-3 py-2 mb-3 shadow-sm" style="font-weight: 600; letter-spacing: 0.5px;">
                        <i class="fas fa-bolt me-1 text-warning"></i> CHUYÊN TRANG CẨM NANG
                    </span>
                    <h1 class="mb-3">Góc Tin Tức & Cẩm Nang</h1>
                    <p class="mx-auto mb-5" style="max-width: 650px;">
                        Tuyển tập những kinh nghiệm xương máu, mẹo vặt IT và kiến thức pháp lý không thể bỏ qua dành cho sinh viên thuê trọ.
                    </p>

                    <!-- Filter Pills -->
                    <ul class="nav nav-pills category-pills justify-content-center">
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($category_slug === '') ? 'active' : ''; ?>" href="blog.php">
                                Tất cả
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
        </div>

        <div class="container my-5">
            <div class="row g-4">
                <!-- MAIN CONTENT (70%) -->
                <div class="col-lg-8">
                    
                    <?php if ($featured_article): ?>
                        <!-- FEATURED ARTICLE -->
                        <div class="mb-5">
                            <a href="blog_detail.php?slug=<?php echo htmlspecialchars($featured_article['slug']); ?>" class="article-card">
                                <div class="article-img-wrap" style="aspect-ratio: 21/9;">
                                    <img src="<?php echo htmlspecialchars($featured_article['thumbnail']); ?>" alt="Featured">
                                    <span class="article-badge"><?php echo htmlspecialchars($featured_article['category_name'] ?? 'Tiêu điểm'); ?></span>
                                </div>
                                <div class="article-content" style="padding: 24px;">
                                    <h2 class="fw-bold text-dark mb-3" style="font-size: 1.5rem; line-height: 1.4;">
                                        <?php echo htmlspecialchars($featured_article['title']); ?>
                                    </h2>
                                    <p class="article-summary" style="font-size: 1.05rem; -webkit-line-clamp: 2;">
                                        <?php echo htmlspecialchars($featured_article['summary']); ?>
                                    </p>
                                    <div class="article-meta mt-2" style="border-top: none; padding-top: 0;">
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="text-dark fw-semibold"><i class="fas fa-pen-nib me-1"></i> <?php echo htmlspecialchars($featured_article['author_name'] ?? 'Admin'); ?></span>
                                            <span><i class="far fa-calendar-alt me-1"></i> <?php echo date('d/m/Y', strtotime($featured_article['published_at'])); ?></span>
                                        </div>
                                        <span class="badge bg-light text-dark border"><i class="far fa-clock me-1"></i> 5 phút đọc</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- ARTICLE GRID -->
                    <div class="row g-4">
                        <?php if (count($articles) > 0): ?>
                            <?php foreach ($articles as $article): ?>
                                <div class="col-md-6">
                                    <a href="blog_detail.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="article-card">
                                        <div class="article-img-wrap">
                                            <img src="<?php echo htmlspecialchars($article['thumbnail']); ?>" alt="Article">
                                            <span class="article-badge"><?php echo htmlspecialchars($article['category_name'] ?? 'Tin tức'); ?></span>
                                        </div>
                                        <div class="article-content">
                                            <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                                            <p class="article-summary"><?php echo htmlspecialchars($article['summary']); ?></p>
                                            <div class="article-meta">
                                                <span><?php echo date('d/m/Y', strtotime($article['published_at'])); ?></span>
                                                <span class="badge bg-light text-secondary"><i class="far fa-clock me-1"></i> 3 phút đọc</span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-secondary">Chưa có bài viết nào.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- PAGINATION / LOAD MORE -->
                    <div class="text-center mt-5">
                        <button class="btn btn-outline-dark rounded-pill px-5 py-2 fw-bold shadow-sm">
                            Xem thêm bài viết <i class="fas fa-chevron-down ms-1"></i>
                        </button>
                    </div>

                </div>

                <!-- SIDEBAR (30%) -->
                <div class="col-lg-4">
                    
                    <!-- Search Widget -->
                    <div class="sidebar-widget">
                        <form action="blog.php" method="GET" class="position-relative">
                            <?php if (isset($_GET['category']) && $_GET['category'] !== ''): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
                            <?php endif; ?>
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="form-control search-input" placeholder="Tìm kiếm cẩm nang..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </form>
                    </div>

                    <!-- Popular Articles Widget -->
                    <div class="sidebar-widget">
                        <h4 class="sidebar-title"><i class="fas fa-fire text-danger"></i> Có thể bạn quan tâm</h4>
                        <div class="mt-3">
                            <?php 
                            // Render 3 bài viết đầu tiên làm dummy bài phổ biến
                            $popular_articles = array_slice($articles, 0, 3);
                            foreach ($popular_articles as $pop): ?>
                                <a href="blog_detail.php?slug=<?php echo htmlspecialchars($pop['slug']); ?>" class="mini-article">
                                    <img src="<?php echo htmlspecialchars($pop['thumbnail']); ?>" alt="Pop">
                                    <div>
                                        <div class="mini-article-title"><?php echo htmlspecialchars($pop['title']); ?></div>
                                        <small class="text-muted"><?php echo date('d/m/Y', strtotime($pop['published_at'])); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Cross-sell Widget: Hot Motels -->
                    <div class="sidebar-widget">
                        <h4 class="sidebar-title"><i class="fas fa-building text-primary"></i> Phòng trọ đang Hot</h4>
                        <div class="mt-3">
                            <?php if (!empty($hot_motels)): ?>
                                <?php foreach ($hot_motels as $hot): ?>
                                    <?php 
                                    $thumb = $hot['thumbnail'];
                                    // Kiểm tra xem file có thực sự tồn tại trong public/ hay không
                                    if (empty($thumb) || !file_exists(__DIR__ . '/' . $thumb)) {
                                        $thumb_url = 'https://placehold.co/600x400/1e293b/ffffff?text=Phong+Tro';
                                    } else {
                                        $thumb_url = $thumb; 
                                    }
                                    ?>
                                    <a href="motel-detail.php?id=<?php echo $hot['id']; ?>" class="hot-motel-card">
                                        <img src="<?php echo htmlspecialchars($thumb_url); ?>" alt="Motel">
                                        <div class="hot-motel-overlay">
                                            <div class="text-truncate fw-semibold mb-1" style="font-size:0.9rem;"><?php echo htmlspecialchars($hot['title']); ?></div>
                                            <div class="hot-motel-price"><?php echo number_format($hot['price']); ?>đ/tháng</div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted small">Chưa có phòng trọ nào.</p>
                            <?php endif; ?>
                            <a href="search.php" class="btn btn-primary w-100 mt-3 rounded-pill fw-bold text-white shadow-sm">
                                Xem tất cả phòng
                            </a>
                        </div>
                    </div>

                </div>
            </div>
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

