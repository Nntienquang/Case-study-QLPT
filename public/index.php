<?php
@require_once '../config/database.php';
@require_once '../config/constants.php';
@require_once '../core/Database.php';
@require_once '../core/PathHelper.php';

session_start();

if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'user';

    if ($role === 'admin') {
        header('Location: ./admin/index.php');
        exit;
    }
    // Owner và User ở lại trang chủ để duyệt phòng
}

/** @var mysqli $conn */
$db = new Database($conn);

$districts = [];
$categories = [];
$rooms = [];
$stats = ['rooms' => 0, 'owners' => 0, 'bookings' => 0, 'districts' => 0];

try {
    $districts = $db->getRows("SELECT id, name FROM districts ORDER BY name LIMIT 8");
    $categories = $db->getRows("SELECT id, name FROM categories ORDER BY name LIMIT 6");
    $rooms = $db->getRows("
        SELECT m.id, m.title, m.price, m.address, m.area, m.count_view, m.health_score, m.service_fee, m.deposit_months,
               (SELECT mi.image_url FROM motel_images mi WHERE mi.motel_id = m.id ORDER BY mi.id ASC LIMIT 1) AS image_url,
               d.name AS district_name, c.name AS category_name
        FROM motels m
        LEFT JOIN districts d ON m.district_id = d.id
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE m.status = 'approved'
        ORDER BY m.is_featured DESC, m.health_score DESC, m.created_at DESC
        LIMIT 6
    ");
    $stats['rooms'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM motels WHERE status = 'approved'")['total'] ?? 0);
    $stats['owners'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM users WHERE role = 'owner' AND status = 'approved'")['total'] ?? 0);
    $stats['bookings'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM bookings")['total'] ?? 0);
    $stats['districts'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM districts")['total'] ?? 0);
} catch (Throwable $e) {
    $districts = [];
    $categories = [];
    $rooms = [];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuanLyPhongTro - Tìm phòng trọ sạch đẹp, quản lý dễ dàng</title>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
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

        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-links a {
            color: #475467;
            text-decoration: none;
            font-weight: 750;
            padding: 10px 12px;
            border-radius: 12px;
        }

        .nav-links a:hover {
            color: #101828;
            background: rgba(16, 24, 40, .06);
        }

        .nav-cta {
            color: #fff !important;
            background: #101828 !important;
        }

        .hero {
            position: relative;
            min-height: 100vh;
            padding: 128px 0 58px;
            display: grid;
            align-items: center;
            overflow: hidden;
        }

        .hero-media {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            background: #dbe7f0;
        }

        .hero-media span {
            position: absolute;
            inset: -7%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transform: scale(1.08);
            animation: roomFilm 24s infinite;
        }

        .hero-media span:nth-child(1) {
            background-image: url('uploads/motels/motel_6a096b186de22.jpeg');
            animation-delay: 0s;
        }

        .hero-media span:nth-child(2) {
            background-image: url('uploads/motels/motel_6a09869feab09.jpg');
            animation-delay: 8s;
        }

        .hero-media span:nth-child(3) {
            background-image: url('https://images.unsplash.com/photo-1560448204-603b3fc33ddc?auto=format&fit=crop&w=2200&q=88');
            animation-delay: 16s;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 1;
            background:
                linear-gradient(90deg, rgba(246, 248, 251, .98) 0%, rgba(246, 248, 251, .86) 42%, rgba(246, 248, 251, .22) 76%),
                linear-gradient(0deg, #f6f8fb 0%, rgba(246, 248, 251, 0) 34%);
        }

        @keyframes roomFilm {
            0% {
                opacity: 0;
                transform: scale(1.08) translateX(0);
            }

            8% {
                opacity: 1;
            }

            34% {
                opacity: 1;
                transform: scale(1.02) translateX(-1.5%);
            }

            42% {
                opacity: 0;
                transform: scale(1.02) translateX(-1.5%);
            }

            100% {
                opacity: 0;
            }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 430px;
            gap: 46px;
            align-items: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .76);
            border: 1px solid rgba(16, 24, 40, .08);
            color: #344054;
            font-weight: 850;
            box-shadow: 0 12px 36px rgba(15, 23, 42, .08);
            margin-bottom: 18px;
        }

        .hero h1 {
            max-width: 780px;
            font-size: clamp(46px, 7.6vw, 92px);
            line-height: .95;
            font-weight: 950;
            letter-spacing: 0;
            margin-bottom: 22px;
        }

        .hero h1 span {
            color: #0e7490;
        }

        .hero-copy {
            max-width: 650px;
            color: #475467;
            font-size: 18px;
            line-height: 1.75;
            margin-bottom: 26px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 16px;
        }

        .hero-points {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 24px;
        }

        .hero-point {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .78);
            color: #344054;
            border: 1px solid rgba(16, 24, 40, .08);
            font-weight: 800;
            font-size: 13px;
        }

        .hero-point i {
            color: #0f766e;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 52px;
            padding: 0 18px;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 900;
        }

        .btn-home.primary {
            color: #fff;
            background: #101828;
            box-shadow: 0 18px 45px rgba(16, 24, 40, .22);
        }

        .btn-home.secondary {
            color: #101828;
            background: rgba(255, 255, 255, .74);
            border: 1px solid rgba(16, 24, 40, .1);
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            max-width: 770px;
        }

        .metric {
            background: rgba(255, 255, 255, .72);
            border: 1px solid rgba(16, 24, 40, .08);
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 14px 40px rgba(15, 23, 42, .08);
            backdrop-filter: blur(12px);
        }

        .metric strong {
            display: block;
            font-size: 28px;
            line-height: 1;
        }

        .metric span {
            color: #667085;
            font-size: 13px;
        }

        .search-card {
            border-radius: 24px;
            padding: 24px;
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(255, 255, 255, .88);
            box-shadow: 0 30px 80px rgba(15, 23, 42, .18);
            backdrop-filter: blur(18px);
        }

        .deal-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .deal-pill {
            min-height: 74px;
            border-radius: 16px;
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e5eaf2;
        }

        .deal-pill strong {
            display: block;
            color: #101828;
            font-size: 18px;
            line-height: 1.1;
        }

        .deal-pill span {
            display: block;
            color: #667085;
            font-size: 12px;
            margin-top: 4px;
        }

        .search-card h2 {
            font-weight: 950;
            font-size: 26px;
            margin-bottom: 8px;
        }

        .search-card p {
            color: #667085;
            margin-bottom: 20px;
        }

        .search-card label {
            color: #344054;
            font-size: 13px;
            font-weight: 850;
            margin-bottom: 7px;
        }

        .search-card .form-control,
        .search-card .form-select {
            min-height: 48px;
            background: #fff !important;
        }

        .section {
            padding: 78px 0;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 22px;
            margin-bottom: 28px;
        }

        .kicker {
            color: #0e7490;
            font-weight: 950;
            margin-bottom: 8px;
        }

        .section-title {
            font-size: clamp(32px, 4vw, 52px);
            line-height: 1.04;
            font-weight: 950;
            margin: 0;
        }

        .section-desc {
            max-width: 660px;
            color: #667085;
            line-height: 1.7;
            margin: 12px 0 0;
        }

        .feature-grid,
        .trust-grid,
        .room-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card,
        .trust-card,
        .room-card,
        .area-card {
            background: #fff;
            border: 1px solid #e5eaf2;
            border-radius: 16px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
        }

        .feature-card,
        .trust-card {
            padding: 24px;
        }

        .feature-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: #fff;
            background: #101828;
            margin-bottom: 18px;
        }

        .feature-card h3 {
            font-weight: 950;
            font-size: 21px;
        }

        .feature-card p {
            color: #667085;
            line-height: 1.65;
            margin: 0;
        }

        .trust-card {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .trust-card i {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: #fff7ed;
            color: #c2410c;
            flex: 0 0 44px;
        }

        .trust-card strong {
            display: block;
            font-size: 18px;
            margin-bottom: 4px;
        }

        .trust-card span {
            color: #667085;
            line-height: 1.55;
        }

        .area-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .area-card {
            min-height: 92px;
            padding: 18px;
            color: #101828;
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 950;
        }

        .area-card small {
            display: block;
            color: #667085;
            font-weight: 650;
            margin-top: 4px;
        }

        .room-card {
            overflow: hidden;
        }

        .room-photo {
            position: relative;
            height: 190px;
            background: #e5eaf2 center/cover no-repeat;
        }

        .room-photo::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(16, 24, 40, 0) 42%, rgba(16, 24, 40, .62) 100%);
        }

        .room-badge {
            position: absolute;
            left: 14px;
            bottom: 14px;
            z-index: 1;
            padding: 7px 10px;
            border-radius: 999px;
            color: #fff;
            background: rgba(15, 118, 110, .92);
            font-weight: 900;
            font-size: 12px;
        }

        .room-body {
            padding: 20px;
        }

        .room-body h3 {
            min-height: 48px;
            font-size: 19px;
            font-weight: 950;
            line-height: 1.32;
        }

        .room-price {
            color: #0e7490;
            font-size: 22px;
            font-weight: 950;
            margin-bottom: 10px;
        }

        .room-foot {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 10px;
            align-items: center;
            margin-top: 16px;
        }

        .room-foot .btn-home {
            min-height: 44px;
            padding: 0 14px;
        }

        .saving-note {
            color: #667085;
            font-size: 13px;
            line-height: 1.45;
        }

        .closing-band {
            color: #fff;
            background:
                linear-gradient(90deg, rgba(16, 24, 40, .96), rgba(14, 116, 144, .92)),
                url('uploads/motels/motel_6a096b186de22.jpeg') center/cover;
            border-radius: 0;
        }

        .closing-band .section-title,
        .closing-band .section-desc {
            color: #fff;
        }

        .closing-band .section-desc {
            opacity: .82;
        }

        .room-meta {
            color: #667085;
            min-height: 44px;
            line-height: 1.55;
        }

        .room-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 16px 0;
        }

        .room-tag {
            padding: 6px 10px;
            border-radius: 999px;
            background: #ecfeff;
            color: #0e7490;
            font-weight: 850;
            font-size: 12px;
        }

        .empty-market {
            padding: 32px;
            background: #fff;
            border: 1px solid #e5eaf2;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
        }

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

        @media (max-width: 991px) {
            .home-nav {
                inset: 10px 0 auto;
            }

            .nav-links {
                display: none;
            }

            .hero-grid,
            .metrics,
            .feature-grid,
            .trust-grid,
            .area-grid,
            .room-grid,
            .deal-strip {
                grid-template-columns: 1fr;
            }

            .hero {
                padding-top: 110px;
            }

            .hero::after {
                background: linear-gradient(0deg, rgba(246, 248, 251, .95), rgba(246, 248, 251, .76));
            }

            .section-head {
                align-items: start;
                flex-direction: column;
            }
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
            box-shadow: 0 4px 12px rgba(17, 24, 39, 0.3);
        }

        .user-dropdown {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: none;
            color: #111827;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .user-btn:hover {
            background-color: #f3f4f6;
        }

        .user-btn i:first-child {
            font-size: 20px;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            min-width: 220px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 8px;
            display: none;
            z-index: 1000;
        }

        .dropdown-menu.active {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #111827;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f3f4f6;
        }

        .dropdown-item i {
            width: 16px;
            text-align: center;
        }

        .dropdown-divider {
            margin: 8px 0;
            border: none;
            border-top: 1px solid #e5e7eb;
        }

        .dropdown-item.logout {
            color: #dc2626;
        }

        .dropdown-item.logout:hover {
            background-color: #fee2e2;
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
                            <button class="user-btn" id="user-menu-btn" onclick="toggleDropdown(event)">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu" id="user-dropdown-menu">
                                <?php if ($_SESSION['role'] === 'owner'): ?>
                                    <!-- Owner dropdown -->
                                    <a href="owner/dashboard.php" class="dropdown-item">
                                        <i class="fas fa-chart-line"></i> Dashboard
                                    </a>
                                    <a href="owner/listings.php" class="dropdown-item">
                                        <i class="fas fa-home"></i> Phòng của tôi
                                    </a>
                                    <a href="owner/bookings.php" class="dropdown-item">
                                        <i class="fas fa-calendar"></i> Booking
                                    </a>
                                    <a href="owner/revenue.php" class="dropdown-item">
                                        <i class="fas fa-money-bill-wave"></i> Doanh thu
                                    </a>
                                    <a href="owner/profile.php" class="dropdown-item">
                                        <i class="fas fa-user"></i> Hồ sơ
                                    </a>
                                <?php else: ?>
                                    <!-- User dropdown -->
                                    <a href="user/dashboard.php" class="dropdown-item">
                                        <i class="fas fa-chart-line"></i> Dashboard
                                    </a>
                                    <a href="user/my-bookings.php" class="dropdown-item">
                                        <i class="fas fa-calendar"></i> Booking của tôi
                                    </a>
                                    <a href="user/saved-motels.php" class="dropdown-item">
                                        <i class="fas fa-heart"></i> Yêu thích
                                    </a>
                                    <a href="user/profile.php" class="dropdown-item">
                                        <i class="fas fa-user"></i> Hồ sơ
                                    </a>
                                <?php endif; ?>
                                <hr class="dropdown-divider">
                                <a href="logout.php" class="dropdown-item logout">
                                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                </a>
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

    <header class="hero">
        <div class="hero-media" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="container-lg hero-content">
            <div class="hero-grid">
                <section>
                    <div class="eyebrow"><i class="fas fa-sparkles"></i> Phòng trọ sạch, tin rõ, quản lý gọn</div>
                    <h1>Tìm phòng sáng sạch. <span>Quản lý thuê trọ</span> dễ hơn.</h1>
                    <p class="hero-copy">
                        Tìm phòng theo nhu cầu, xem trước chi phí vào ở, đặt lịch xem nhanh
                        và theo dõi các yêu cầu thuê trong một nơi.
                    </p>
                    <div class="hero-actions">
                        <a href="user/search.php" class="btn-home primary"><i class="fas fa-magnifying-glass"></i> Tìm phòng ngay</a>
                        <a href="owner-register.php" class="btn-home secondary"><i class="fas fa-house-user"></i> Đăng phòng cho thuê</a>
                    </div>
                    <div class="hero-points">
                        <span class="hero-point"><i class="fas fa-check-circle"></i> Tin đã duyệt</span>
                        <span class="hero-point"><i class="fas fa-wallet"></i> Biết trước chi phí vào ở</span>
                        <span class="hero-point"><i class="fas fa-calendar-check"></i> Đặt lịch xem nhanh</span>
                    </div>
                    <div class="metrics">
                        <div class="metric"><strong><?php echo number_format($stats['rooms']); ?></strong><span>phòng đã duyệt</span></div>
                        <div class="metric"><strong><?php echo number_format($stats['owners']); ?></strong><span>chủ phòng</span></div>
                        <div class="metric"><strong><?php echo number_format($stats['bookings']); ?></strong><span>booking</span></div>
                        <div class="metric"><strong><?php echo number_format($stats['districts']); ?></strong><span>khu vực</span></div>
                    </div>
                </section>

                <aside class="search-card">
                    <h2>Tìm phòng phù hợp</h2>
                    <p>Lọc nhanh theo khu vực, loại phòng, ngân sách và diện tích.</p>
                    <div class="deal-strip">
                        <div class="deal-pill"><strong>5 phút</strong><span>lọc ra phòng hợp nhu cầu</span></div>
                        <div class="deal-pill"><strong>0 phí</strong><span>tìm kiếm và lưu phòng</span></div>
                        <div class="deal-pill"><strong>24/7</strong><span>theo dõi booking online</span></div>
                    </div>
                    <form method="GET" action="user/search.php">
                        <div class="mb-3">
                            <label for="keyword">Từ khóa</label>
                            <input id="keyword" class="form-control" name="keyword" placeholder="Gần trường, full nội thất, ban công">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="district">Khu vực</label>
                                <select id="district" name="district_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo (int)$district['id']; ?>"><?php echo htmlspecialchars($district['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category">Loại phòng</label>
                                <select id="category" name="category_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="max_price">Giá tối đa</label>
                                <input id="max_price" class="form-control" type="number" name="max_price" placeholder="4000000">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="area_min">Diện tích từ</label>
                                <input id="area_min" class="form-control" type="number" name="area_min" placeholder="20">
                            </div>
                        </div>
                        <button class="btn-home primary w-100" type="submit"><i class="fas fa-arrow-right"></i> Xem kết quả</button>
                    </form>
                </aside>
            </div>
        </div>
    </header>

    <main>
        <section class="section" id="system">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Trải nghiệm thuê trọ hiện đại</div>
                        <h2 class="section-title">Từ lúc tìm phòng đến khi vào ở đều rõ ràng.</h2>
                        <p class="section-desc">Người thuê có thông tin cần thiết trước khi quyết định, còn chủ phòng có nơi quản lý tin đăng, lịch xem và booking gọn gàng.</p>
                    </div>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <div class="feature-icon"><i class="fas fa-user"></i></div>
                        <h3>Người thuê</h3>
                        <p>Tìm phòng theo khu vực, ngân sách và diện tích; lưu phòng yêu thích, xem chi phí vào ở và đặt lịch xem.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon"><i class="fas fa-building-user"></i></div>
                        <h3>Chủ phòng</h3>
                        <p>Đăng phòng, nhận lịch xem, theo dõi booking, chỉnh sửa tin và quản lý doanh thu trong một nơi.</p>
                    </article>
                    <article class="feature-card">
                        <div class="feature-icon"><i class="fas fa-shield-halved"></i></div>
                        <h3>Độ tin cậy</h3>
                        <p>Tin đăng được kiểm duyệt, có điểm chất lượng và hỗ trợ báo cáo khi phát hiện thông tin không phù hợp.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section pt-0" id="trust">
            <div class="container-lg">
                <div class="trust-grid">
                    <article class="trust-card">
                        <i class="fas fa-camera"></i>
                        <div><strong>Ảnh thật từ tin đăng</strong><span>Card phòng ưu tiên hiển thị ảnh đầu tiên để người thuê đánh giá nhanh trước khi bấm chi tiết.</span></div>
                    </article>
                    <article class="trust-card">
                        <i class="fas fa-receipt"></i>
                        <div><strong>Giá vào ở minh bạch</strong><span>Tiền thuê, phí dịch vụ và cọc được gom thành con số dễ hiểu ngay tại danh sách.</span></div>
                    </article>
                    <article class="trust-card">
                        <i class="fas fa-route"></i>
                        <div><strong>Luồng thuê rõ ràng</strong><span>Từ tìm kiếm, xem chi tiết, đặt lịch, booking đến quản lý tài khoản đều có điểm vào nhanh.</span></div>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="areas">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Khu vực</div>
                        <h2 class="section-title">Tìm nhanh theo nơi muốn thuê.</h2>
                    </div>
                    <a href="user/search.php" class="btn-home secondary">Tìm nâng cao</a>
                </div>
                <div class="area-grid">
                    <?php foreach ($districts as $district): ?>
                        <a class="area-card" href="user/search.php?district_id=<?php echo (int)$district['id']; ?>">
                            <span><?php echo htmlspecialchars($district['name']); ?><small>Xem phòng trong khu vực</small></span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$districts): ?>
                        <div class="empty-market">Chưa có dữ liệu khu vực.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section" id="rooms">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Phòng đã duyệt</div>
                        <h2 class="section-title">Phòng mới, thông tin rõ ràng.</h2>
                    </div>
                    <a href="user/search.php" class="btn-home primary">Xem tất cả</a>
                </div>

                <?php if ($rooms): ?>
                    <div class="room-grid">
                        <?php foreach ($rooms as $room): ?>
                            <?php
                            $moveInCost = (int)$room['price'] + (int)($room['service_fee'] ?? 0) + (int)round((int)$room['price'] * (float)($room['deposit_months'] ?? 1));
                            $roomImage = function_exists('qlpt_relative_public_asset_url')
                                ? qlpt_relative_public_asset_url($room['image_url'] ?? null, 'uploads/motels/motel_6a096b186de22.jpeg')
                                : ($room['image_url'] ?: 'uploads/motels/motel_6a096b186de22.jpeg');
                            ?>
                            <article class="room-card">
                                <div class="room-photo" style="background-image: url('<?php echo htmlspecialchars($roomImage); ?>');">
                                    <span class="room-badge"><i class="fas fa-shield-heart"></i> Đã duyệt</span>
                                </div>
                                <div class="room-body">
                                    <h3><?php echo htmlspecialchars($room['title']); ?></h3>
                                    <div class="room-price"><?php echo number_format((int)$room['price']); ?> VND/tháng</div>
                                    <div class="room-meta"><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($room['address'] ?: 'Chưa cập nhật địa chỉ'); ?></div>
                                    <div class="room-tags">
                                        <?php if (!empty($room['district_name'])): ?><span class="room-tag"><?php echo htmlspecialchars($room['district_name']); ?></span><?php endif; ?>
                                        <?php if (!empty($room['category_name'])): ?><span class="room-tag"><?php echo htmlspecialchars($room['category_name']); ?></span><?php endif; ?>
                                        <?php if (!empty($room['area'])): ?><span class="room-tag"><?php echo (float)$room['area']; ?> m2</span><?php endif; ?>
                                        <span class="room-tag"><?php echo (int)$room['health_score']; ?>/100</span>
                                    </div>
                                    <div class="room-meta mb-3"><i class="fas fa-wallet"></i> Vào ở dự kiến: <?php echo number_format($moveInCost); ?> VND</div>
                                    <div class="room-foot">
                                        <div class="saving-note"><?php echo number_format((int)$room['count_view']); ?> lượt xem - chọn nhanh khi còn phòng.</div>
                                        <a href="user/motel-detail.php?id=<?php echo (int)$room['id']; ?>" class="btn-home primary">Xem phòng</a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-market">
                        <h3 class="fw-bold">Chưa có phòng đang hiển thị</h3>
                        <p class="text-muted mb-3">Khi owner đăng tin và admin duyệt, phòng sẽ xuất hiện tại đây.</p>
                        <a href="owner-register.php" class="btn-home primary">Đăng phòng đầu tiên</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section closing-band">
            <div class="container-lg">
                <div class="section-head mb-0">
                    <div>
                        <div class="kicker text-white">Sẵn sàng bắt đầu</div>
                        <h2 class="section-title">Tìm phòng hợp ngân sách hôm nay.</h2>
                        <p class="section-desc">Dữ liệu phòng, lịch xem và booking được gom về một luồng để người thuê ra quyết định nhanh hơn.</p>
                    </div>
                    <a href="user/search.php" class="btn-home secondary">Khám phá phòng</a>
                </div>
            </div>
        </section>
    </main>

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
                        <a href="trogiup.php" class="me-3" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="blog.php" class="me-3" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="trogiup.php" aria-label="X"><i class="fab fa-x-twitter"></i></a>
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
                    <a href="policies/owner-policy.php" class="footer-link me-3">Chính sách chủ phòng</a>
                    <a href="policies/payment-policy.php" class="footer-link me-3">Chính sách thanh toán</a>
                    <a href="policies/user-policy.php" class="footer-link">Điều khoản sử dụng</a>
                </div>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleDropdown(event) {
            event.stopPropagation();
            const dropdownMenu = document.getElementById('user-dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.classList.toggle('active');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdownMenu = document.getElementById('user-dropdown-menu');
            if (dropdownMenu && !e.target.closest('.user-dropdown')) {
                dropdownMenu.classList.remove('active');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý nút Hamburger trên Mobile
            const menuBtn = document.getElementById('mobile-menu-btn');
            const menuContent = document.getElementById('mobile-menu');
            const icon = menuBtn.querySelector('i');

            menuBtn.addEventListener('click', function() {
                menuContent.classList.toggle('show');
                if (menuContent.classList.contains('show')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });

            // Xử lý gạch chân cho menu
            const links = document.querySelectorAll('.nav-links .nav-item');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    links.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');

                    // Tự động đóng menu trên điện thoại khi chọn xong
                    if (window.innerWidth <= 991) {
                        menuContent.classList.remove('show');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            });

        });
    </script>
</body>

</html>
