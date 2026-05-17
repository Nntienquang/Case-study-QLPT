<?php
require_once '../config/database.php';
require_once '../config/constants.php';
require_once '../core/Database.php';

session_start();

/** @var mysqli $conn */
$db = new Database($conn);

$currentUser = [
    'id' => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0,
    'name' => $_SESSION['name'] ?? $_SESSION['user_name'] ?? '',
    'role' => $_SESSION['role'] ?? $_SESSION['user_role'] ?? '',
];

$dashboardUrl = '#';
if ($currentUser['role'] === 'admin') {
    $dashboardUrl = 'admin/index.php';
} elseif ($currentUser['role'] === 'owner') {
    $dashboardUrl = 'owner/dashboard.php';
} elseif ($currentUser['id'] > 0) {
    $dashboardUrl = 'user/dashboard.php';
}

$districts = [];
$categories = [];
$hotRooms = [];
$newRooms = [];
$ownerHighlights = [];
$utilities = [];
$stats = ['rooms' => 0, 'owners' => 0, 'bookings' => 0, 'districts' => 0];

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money_vnd($value): string
{
    return number_format((int)$value, 0, ',', '.') . ' đ';
}

function room_image(array $room, int $index = 0): string
{
    $image = trim((string)($room['thumbnail'] ?? ''));
    if ($image !== '') {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        return 'uploads/' . ltrim($image, '/');
    }

    $fallbacks = [
        'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1560448204-603b3fc33ddc?auto=format&fit=crop&w=900&q=82',
        'https://images.unsplash.com/photo-1560185007-c5ca9d2c014d?auto=format&fit=crop&w=900&q=82',
    ];

    return $fallbacks[$index % count($fallbacks)];
}

function room_card(array $room, int $index, string $label): void
{
    $moveInCost = (int)($room['price'] ?? 0)
        + (int)($room['service_fee'] ?? 0)
        + (int)round((int)($room['price'] ?? 0) * (float)($room['deposit_months'] ?? 1));
    ?>
    <article class="room-card">
        <a class="room-media" href="user/motel-detail.php?id=<?php echo (int)$room['id']; ?>">
            <img src="<?php echo h(room_image($room, $index)); ?>" alt="<?php echo h($room['title'] ?? 'Phòng trọ'); ?>">
            <span class="room-badge"><?php echo h($label); ?></span>
        </a>
        <div class="room-body">
            <div class="room-topline">
                <span><?php echo h($room['district_name'] ?? 'Khu vực'); ?></span>
                <span><i class="fa-regular fa-eye"></i> <?php echo number_format((int)($room['count_view'] ?? 0)); ?></span>
            </div>
            <h3><a href="user/motel-detail.php?id=<?php echo (int)$room['id']; ?>"><?php echo h($room['title'] ?? 'Phòng trọ'); ?></a></h3>
            <div class="room-price"><?php echo money_vnd($room['price'] ?? 0); ?><small>/tháng</small></div>
            <p class="room-address"><i class="fa-solid fa-location-dot"></i><?php echo h($room['address'] ?: 'Chưa cập nhật địa chỉ'); ?></p>
            <div class="room-tags">
                <?php if (!empty($room['category_name'])): ?><span><?php echo h($room['category_name']); ?></span><?php endif; ?>
                <?php if (!empty($room['area'])): ?><span><?php echo (float)$room['area']; ?> m2</span><?php endif; ?>
                <?php if (!empty($room['owner_name'])): ?><span><?php echo h($room['owner_name']); ?></span><?php endif; ?>
            </div>
            <div class="room-footer">
                <span>Vào ở từ <?php echo money_vnd($moveInCost); ?></span>
                <a href="user/motel-detail.php?id=<?php echo (int)$room['id']; ?>">Chi tiết</a>
            </div>
        </div>
    </article>
    <?php
}

try {
    $districts = $db->getRows("SELECT id, name FROM districts ORDER BY name LIMIT 8");
    $categories = $db->getRows("SELECT id, name FROM categories ORDER BY name LIMIT 8");
    $utilities = $db->getRows("SELECT id, name FROM utilities ORDER BY name LIMIT 8");

    $roomSelect = "
        SELECT m.id, m.title, m.price, m.address, m.area, m.count_view, m.health_score, m.service_fee,
               m.deposit_months, m.created_at, m.bedrooms, m.bathrooms,
               COALESCE(m.district_name, d.name) AS district_name, c.name AS category_name, u.name AS owner_name,
               (SELECT mi.image_url FROM motel_images mi WHERE mi.motel_id = m.id LIMIT 1) AS thumbnail
        FROM motels m
        LEFT JOIN districts d ON m.district_id = d.id
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.status = 'approved'
    ";

    $hotRooms = $db->getRows($roomSelect . "
        ORDER BY m.is_featured DESC, m.count_view DESC, m.health_score DESC, m.created_at DESC
        LIMIT 6
    ");
    $newRooms = $db->getRows($roomSelect . "
        ORDER BY m.created_at DESC, m.id DESC
        LIMIT 6
    ");
    $ownerHighlights = $db->getRows("
        SELECT u.id, u.name, u.trust_score, COUNT(m.id) AS approved_rooms
        FROM users u
        INNER JOIN motels m ON m.user_id = u.id AND m.status = 'approved'
        WHERE u.role = 'owner' AND u.status = 'approved'
        GROUP BY u.id, u.name, u.trust_score
        ORDER BY approved_rooms DESC, u.trust_score DESC
        LIMIT 4
    ");

    $stats['rooms'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM motels WHERE status = 'approved'")['total'] ?? 0);
    $stats['owners'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM users WHERE role = 'owner' AND status = 'approved'")['total'] ?? 0);
    $stats['bookings'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM bookings")['total'] ?? 0);
    $stats['districts'] = (int)($db->getRow("SELECT COUNT(*) AS total FROM districts")['total'] ?? 0);
} catch (Throwable $e) {
    $districts = [];
    $categories = [];
    $hotRooms = [];
    $newRooms = [];
    $ownerHighlights = [];
    $utilities = [];
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuanLyPhongTro - Tìm phòng trọ rõ ràng, quản lý dễ dàng</title>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
        :root {
            --ink: #101828;
            --muted: #667085;
            --line: #e4e7ec;
            --soft: #f3f6fa;
            --brand: #0f766e;
            --brand-dark: #134e4a;
            --blue: #2563eb;
            --shadow: 0 20px 60px rgba(15, 23, 42, .10);
            --radius: 22px;
        }

        body {
            margin: 0;
            background: #f6f8fb !important;
            color: var(--ink);
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        .home-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(246, 248, 251, .82);
            border-bottom: 1px solid rgba(228, 231, 236, .78);
            backdrop-filter: blur(18px);
        }

        .nav-shell {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            color: var(--ink);
            font-weight: 900;
            letter-spacing: 0;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--ink);
            box-shadow: 0 12px 28px rgba(16, 24, 40, .18);
        }

        .nav-center,
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-link-soft {
            color: #475467;
            font-weight: 760;
            padding: 10px 12px;
            border-radius: 999px;
            transition: .22s ease;
        }

        .nav-link-soft:hover {
            color: var(--ink);
            background: #fff;
        }

        .btn-home {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border-radius: 999px;
            padding: 0 18px;
            border: 1px solid var(--line);
            font-weight: 850;
            color: var(--ink);
            background: #fff;
            transition: .22s ease;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
        }

        .btn-home:hover {
            transform: translateY(-1px);
            color: var(--ink);
            box-shadow: 0 16px 34px rgba(15, 23, 42, .10);
        }

        .btn-home.primary {
            border-color: var(--ink);
            background: var(--ink);
            color: #fff;
        }

        .btn-home.brand {
            border-color: var(--brand);
            background: var(--brand);
            color: #fff;
        }

        .account-dropdown .dropdown-toggle {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            border-radius: 999px;
            padding: 7px 12px 7px 7px;
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-weight: 850;
            box-shadow: 0 10px 26px rgba(15, 23, 42, .06);
        }

        .avatar-chip {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #ecfdf5;
            color: var(--brand-dark);
        }

        .dropdown-menu {
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 8px;
        }

        .hero {
            position: relative;
            min-height: 690px;
            display: grid;
            align-items: center;
            padding: 64px 0 46px;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 88%;
            background:
                linear-gradient(90deg, rgba(246, 248, 251, .98) 0%, rgba(246, 248, 251, .88) 45%, rgba(246, 248, 251, .35) 78%),
                url('https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=2200&q=86') center/cover;
            z-index: 0;
        }

        .hero::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 35%;
            background: linear-gradient(0deg, #f6f8fb 18%, rgba(246, 248, 251, 0));
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 430px;
            gap: 42px;
            align-items: center;
        }

        .eyebrow,
        .kicker {
            color: #087083;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: .82rem;
        }

        h1 {
            margin: 14px 0 18px;
            max-width: 780px;
            font-size: clamp(2.65rem, 6vw, 5.35rem);
            line-height: .95;
            font-weight: 950;
            letter-spacing: 0;
        }

        .hero-copy {
            max-width: 650px;
            color: #475467;
            font-size: 1.08rem;
            line-height: 1.8;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 28px;
        }

        .metric-row {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-top: 34px;
            max-width: 720px;
        }

        .metric {
            padding: 16px;
            border: 1px solid rgba(255, 255, 255, .74);
            border-radius: 18px;
            background: rgba(255, 255, 255, .72);
            backdrop-filter: blur(14px);
        }

        .metric strong {
            display: block;
            font-size: 1.5rem;
            font-weight: 950;
        }

        .metric span {
            color: var(--muted);
            font-size: .88rem;
            font-weight: 700;
        }

        .search-card {
            border: 1px solid rgba(255, 255, 255, .82);
            border-radius: 28px;
            padding: 24px;
            background: rgba(255, 255, 255, .90);
            box-shadow: var(--shadow);
            backdrop-filter: blur(18px);
        }

        .search-card h2 {
            margin: 0 0 6px;
            font-weight: 950;
            letter-spacing: 0;
        }

        .search-card p {
            color: var(--muted);
            margin-bottom: 20px;
        }

        .search-card label {
            font-size: .82rem;
            color: #344054;
            font-weight: 850;
            margin-bottom: 7px;
        }

        .search-card .form-control,
        .search-card .form-select {
            min-height: 48px;
            border-radius: 14px;
            border: 1px solid #d0d5dd;
            background-color: #fff;
            transition: .22s ease;
        }

        .search-card .form-control:focus,
        .search-card .form-select:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 4px rgba(15, 118, 110, .12);
        }

        .section {
            padding: 72px 0 0;
        }

        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 22px;
            margin-bottom: 24px;
        }

        .section-title {
            margin: 8px 0 0;
            font-size: clamp(2rem, 4vw, 3.25rem);
            line-height: 1.05;
            font-weight: 950;
            letter-spacing: 0;
        }

        .section-desc {
            margin: 12px 0 0;
            max-width: 680px;
            color: var(--muted);
            line-height: 1.8;
        }

        .trust-grid,
        .room-grid,
        .need-grid,
        .owner-grid {
            display: grid;
            gap: 18px;
        }

        .trust-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .room-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .need-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .owner-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .panel,
        .room-card,
        .need-card,
        .owner-card,
        .area-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: 0 16px 46px rgba(15, 23, 42, .06);
        }

        .panel {
            padding: 24px;
        }

        .panel-icon,
        .need-icon {
            width: 46px;
            height: 46px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            color: #fff;
            background: var(--ink);
            margin-bottom: 18px;
        }

        .panel h3,
        .need-card h3,
        .owner-card h3 {
            font-size: 1.15rem;
            font-weight: 950;
            margin-bottom: 8px;
            letter-spacing: 0;
        }

        .panel p,
        .need-card p,
        .owner-card p {
            color: var(--muted);
            line-height: 1.7;
            margin: 0;
        }

        .room-card {
            overflow: hidden;
            transition: .24s ease;
        }

        .room-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }

        .room-media {
            display: block;
            position: relative;
            aspect-ratio: 16 / 10;
            overflow: hidden;
            background: #e4e7ec;
        }

        .room-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .35s ease;
        }

        .room-card:hover .room-media img {
            transform: scale(1.04);
        }

        .room-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            border-radius: 999px;
            padding: 7px 11px;
            color: #fff;
            background: rgba(16, 24, 40, .86);
            font-size: .78rem;
            font-weight: 850;
        }

        .room-body {
            padding: 18px;
        }

        .room-topline,
        .room-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 800;
        }

        .room-body h3 {
            min-height: 52px;
            margin: 10px 0 8px;
            font-size: 1.12rem;
            line-height: 1.35;
            font-weight: 950;
        }

        .room-body h3 a {
            color: var(--ink);
        }

        .room-price {
            color: #047857;
            font-size: 1.28rem;
            font-weight: 950;
            margin-bottom: 8px;
        }

        .room-price small {
            color: var(--muted);
            font-size: .84rem;
            font-weight: 700;
        }

        .room-address {
            min-height: 44px;
            display: flex;
            gap: 8px;
            color: var(--muted);
            line-height: 1.55;
            margin: 0 0 12px;
        }

        .room-tags {
            min-height: 34px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 14px;
        }

        .room-tags span {
            border: 1px solid #dbe3ef;
            border-radius: 999px;
            padding: 6px 9px;
            color: #344054;
            background: #f8fafc;
            font-size: .78rem;
            font-weight: 800;
        }

        .room-footer {
            border-top: 1px solid #eef2f6;
            padding-top: 14px;
        }

        .room-footer a {
            color: var(--blue);
            font-weight: 900;
        }

        .area-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .area-card,
        .need-card,
        .owner-card {
            display: block;
            padding: 18px;
            color: var(--ink);
            transition: .22s ease;
        }

        .area-card:hover,
        .need-card:hover,
        .owner-card:hover {
            transform: translateY(-2px);
            color: var(--ink);
            box-shadow: 0 22px 52px rgba(15, 23, 42, .10);
        }

        .area-card strong {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 950;
        }

        .area-card span {
            display: block;
            color: var(--muted);
            margin-top: 6px;
            font-weight: 700;
        }

        .need-card {
            min-height: 184px;
        }

        .need-icon {
            color: var(--brand-dark);
            background: #ccfbf1;
        }

        .owner-card .owner-avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #eef4ff;
            color: #1d4ed8;
            font-weight: 950;
            margin-bottom: 16px;
        }

        .utility-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .utility-pill {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 11px 14px;
            color: #344054;
            background: #fff;
            font-weight: 850;
        }

        .owner-cta {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 44px;
            color: #fff;
            background:
                linear-gradient(135deg, rgba(16, 24, 40, .96), rgba(19, 78, 74, .92)),
                url('https://images.unsplash.com/photo-1580587771525-78b9dba3b914?auto=format&fit=crop&w=1600&q=84') center/cover;
            box-shadow: var(--shadow);
        }

        .owner-cta h2 {
            max-width: 720px;
            font-weight: 950;
            font-size: clamp(2rem, 4vw, 3.3rem);
            line-height: 1.05;
            letter-spacing: 0;
        }

        .owner-cta p {
            max-width: 680px;
            color: rgba(255, 255, 255, .78);
            line-height: 1.8;
        }

        .empty-market {
            border: 1px dashed #cbd5e1;
            border-radius: var(--radius);
            padding: 34px;
            text-align: center;
            background: #fff;
            color: var(--muted);
        }

        .footer {
            margin-top: 72px;
            background: #101828;
            color: #fff;
            padding: 54px 0 30px;
        }

        .footer p,
        .footer a {
            color: rgba(255, 255, 255, .68);
        }

        .footer a:hover {
            color: #fff;
        }

        @media (max-width: 1100px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }

            .search-card {
                max-width: 720px;
            }

            .room-grid,
            .trust-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .need-grid,
            .owner-grid,
            .area-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .nav-shell {
                min-height: auto;
                padding: 12px 0;
                align-items: stretch;
                flex-direction: column;
                gap: 12px;
            }

            .nav-center,
            .nav-actions {
                width: 100%;
                overflow-x: auto;
                justify-content: flex-start;
                padding-bottom: 2px;
            }

            .hero {
                min-height: auto;
                padding-top: 42px;
            }

            .hero-grid,
            .metric-row,
            .room-grid,
            .trust-grid,
            .need-grid,
            .owner-grid,
            .area-grid {
                grid-template-columns: 1fr;
            }

            .hero-actions,
            .section-head {
                align-items: stretch;
                flex-direction: column;
            }

            .btn-home {
                width: 100%;
            }

            .search-card,
            .owner-cta {
                border-radius: 22px;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <nav class="home-nav">
        <div class="container-lg nav-shell">
            <a class="brand" href="index.php">
                <span class="brand-mark"><i class="fa-solid fa-shield-halved"></i></span>
                <span>QuanLyPhongTro</span>
            </a>

            <div class="nav-center">
                <a class="nav-link-soft" href="phongtro.php">Phòng trọ</a>
                <a class="nav-link-soft" href="#hot">Phòng hot</a>
                <a class="nav-link-soft" href="#areas">Khu vực</a>
                <a class="nav-link-soft" href="#owners">Chủ trọ</a>
            </div>

            <div class="nav-actions">
                <?php if ($currentUser['id'] > 0): ?>
                    <div class="dropdown account-dropdown">
                        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-chip"><i class="fa-regular fa-user"></i></span>
                            <span><?php echo h($currentUser['name'] ?: 'Tài khoản'); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header"><?php echo h(ucfirst($currentUser['role'] ?: 'user')); ?></h6></li>
                            <li><a class="dropdown-item" href="<?php echo h($dashboardUrl); ?>"><i class="fa-solid fa-table-columns me-2"></i>Dashboard</a></li>
                            <?php if ($currentUser['role'] === 'owner'): ?>
                                <li><a class="dropdown-item" href="owner/listings.php"><i class="fa-solid fa-house-circle-check me-2"></i>Quản lý tin phòng</a></li>
                            <?php elseif ($currentUser['role'] === 'user'): ?>
                                <li><a class="dropdown-item" href="user/saved-motels.php"><i class="fa-regular fa-heart me-2"></i>Phòng đã lưu</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-link-soft">Đăng nhập</a>
                    <a href="register.php" class="btn-home">Đăng ký</a>
                <?php endif; ?>
                <a href="<?php echo $currentUser['role'] === 'owner' ? 'owner/add-listing.php' : 'owner-register.php'; ?>" class="btn-home primary">
                    <i class="fa-solid fa-plus"></i> Đăng phòng
                </a>
            </div>
        </div>
    </nav>

    <header class="hero">
        <div class="container-lg hero-content">
            <div class="hero-grid">
                <section>
                    <div class="eyebrow">Marketplace thuê trọ cho người thuê và chủ phòng</div>
                    <h1>Tìm phòng đúng nhu cầu. Chủ trọ quản lý tin rõ ràng.</h1>
                    <p class="hero-copy">
                        Người thuê xem phòng hot, phòng mới, tiện ích, chi phí vào ở và đặt lịch xem. Chủ trọ có nơi đưa tin lên, theo dõi booking và tối ưu tin đăng sau khi admin duyệt.
                    </p>
                    <div class="hero-actions">
                        <a href="phongtro.php" class="btn-home brand"><i class="fa-solid fa-magnifying-glass"></i> Khám phá phòng</a>
                        <a href="#hot" class="btn-home"><i class="fa-solid fa-fire"></i> Xem phòng hot</a>
                        <a href="<?php echo $currentUser['role'] === 'owner' ? 'owner/dashboard.php' : 'owner-register.php'; ?>" class="btn-home primary"><i class="fa-solid fa-house-user"></i> Kênh chủ trọ</a>
                    </div>
                    <div class="metric-row">
                        <div class="metric"><strong><?php echo number_format($stats['rooms']); ?></strong><span>phòng đã duyệt</span></div>
                        <div class="metric"><strong><?php echo number_format($stats['owners']); ?></strong><span>chủ trọ</span></div>
                        <div class="metric"><strong><?php echo number_format($stats['bookings']); ?></strong><span>booking</span></div>
                        <div class="metric"><strong><?php echo number_format($stats['districts']); ?></strong><span>khu vực</span></div>
                    </div>
                </section>

                <aside class="search-card">
                    <h2>Tìm phòng nhanh</h2>
                    <p>Lọc theo khu vực, loại phòng, ngân sách và diện tích.</p>
                    <form method="GET" action="phongtro.php">
                        <div class="mb-3">
                            <label for="keyword">Từ khóa</label>
                            <input id="keyword" class="form-control" name="keyword" placeholder="Gần trường, full nội thất, có ban công">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="district_id">Khu vực</label>
                                <select id="district_id" name="district_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($districts as $district): ?>
                                        <option value="<?php echo (int)$district['id']; ?>"><?php echo h($district['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category_id">Loại phòng</label>
                                <select id="category_id" name="category_id" class="form-select">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo (int)$category['id']; ?>"><?php echo h($category['name']); ?></option>
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
                        <button class="btn-home brand w-100" type="submit"><i class="fa-solid fa-arrow-right"></i> Xem kết quả</button>
                    </form>
                </aside>
            </div>
        </div>
    </header>

    <main>
        <section class="section">
            <div class="container-lg">
                <div class="trust-grid">
                    <article class="panel">
                        <div class="panel-icon"><i class="fa-solid fa-user-check"></i></div>
                        <h3>Người thuê lướt web trước</h3>
                        <p>Đăng nhập xong vẫn ở trang chủ để tiếp tục tìm phòng, lưu tin, xem chi tiết và đặt lịch khi cần.</p>
                    </article>
                    <article class="panel">
                        <div class="panel-icon"><i class="fa-solid fa-building-user"></i></div>
                        <h3>Chủ trọ có kênh riêng</h3>
                        <p>Owner vẫn xem được marketplace như user, dashboard nằm trong menu tài khoản để quản lý tin, lịch xem và doanh thu.</p>
                    </article>
                    <article class="panel">
                        <div class="panel-icon"><i class="fa-solid fa-shield-halved"></i></div>
                        <h3>Tin phòng qua kiểm duyệt</h3>
                        <p>Trang chủ chỉ ưu tiên tin đã được duyệt, có thông tin khu vực, giá, diện tích và chủ phòng rõ ràng.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="hot">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Phòng hot</div>
                        <h2 class="section-title">Tin nổi bật từ nhiều chủ trọ.</h2>
                        <p class="section-desc">Ưu tiên phòng có lượt xem, điểm chất lượng và trạng thái duyệt tốt để người thuê có lựa chọn đáng tin cậy hơn.</p>
                    </div>
                    <a href="phongtro.php" class="btn-home">Xem tất cả</a>
                </div>

                <?php if ($hotRooms): ?>
                    <div class="room-grid">
                        <?php foreach ($hotRooms as $index => $room): ?>
                            <?php room_card($room, $index, $index === 0 ? 'Đang được quan tâm' : 'Phòng hot'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-market">Chưa có phòng hot. Khi owner đăng tin và admin duyệt, dữ liệu sẽ xuất hiện tại đây.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section" id="new">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Phòng mới</div>
                        <h2 class="section-title">Tin mới lên để người thuê không bỏ lỡ.</h2>
                        <p class="section-desc">Khu này giúp owner mới có cơ hội xuất hiện, không chỉ các tin nhiều lượt xem mới được thấy.</p>
                    </div>
                    <a href="phongtro.php?sort=newest" class="btn-home primary">Xem phòng mới</a>
                </div>

                <?php if ($newRooms): ?>
                    <div class="room-grid">
                        <?php foreach ($newRooms as $index => $room): ?>
                            <?php room_card($room, $index + 2, 'Mới cập nhật'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-market">Chưa có phòng mới được duyệt.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section" id="areas">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Khu vực</div>
                        <h2 class="section-title">Tìm theo nơi muốn thuê.</h2>
                    </div>
                    <a href="phongtro.php" class="btn-home">Tìm nâng cao</a>
                </div>
                <div class="area-grid">
                    <?php foreach ($districts as $district): ?>
                        <a class="area-card" href="phongtro.php?district_id=<?php echo (int)$district['id']; ?>">
                            <strong><?php echo h($district['name']); ?><i class="fa-solid fa-arrow-right"></i></strong>
                            <span>Xem phòng đã duyệt trong khu vực</span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$districts): ?>
                        <div class="empty-market">Chưa có dữ liệu khu vực.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Nhu cầu phổ biến</div>
                        <h2 class="section-title">Dẫn người thuê vào đúng lựa chọn.</h2>
                    </div>
                </div>
                <div class="need-grid">
                    <a class="need-card" href="phongtro.php?keyword=gần trường">
                        <div class="need-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                        <h3>Gần trường học</h3>
                        <p>Phù hợp sinh viên cần đi lại nhanh, chi phí rõ và khu vực quen thuộc.</p>
                    </a>
                    <a class="need-card" href="phongtro.php?keyword=full nội thất">
                        <div class="need-icon"><i class="fa-solid fa-couch"></i></div>
                        <h3>Full nội thất</h3>
                        <p>Ưu tiên phòng vào ở nhanh, giảm chi phí mua sắm ban đầu.</p>
                    </a>
                    <a class="need-card" href="phongtro.php?max_price=3000000">
                        <div class="need-icon"><i class="fa-solid fa-wallet"></i></div>
                        <h3>Ngân sách tốt</h3>
                        <p>Lọc phòng vừa túi tiền và xem trước chi phí cần chuẩn bị.</p>
                    </a>
                    <a class="need-card" href="phongtro.php?keyword=an ninh">
                        <div class="need-icon"><i class="fa-solid fa-shield"></i></div>
                        <h3>An ninh, tiện nghi</h3>
                        <p>Dành cho người cần nơi ở ổn định, riêng tư và dễ sinh hoạt.</p>
                    </a>
                </div>
            </div>
        </section>

        <section class="section" id="owners">
            <div class="container-lg">
                <div class="section-head">
                    <div>
                        <div class="kicker">Chủ trọ nổi bật</div>
                        <h2 class="section-title">Hiển thị công bằng theo owner.</h2>
                        <p class="section-desc">Không dồn toàn bộ sự chú ý vào một tin. Trang chủ có khu riêng để nhiều chủ trọ được nhận diện, còn phòng hot và phòng mới cân bằng giữa chất lượng và thời gian đăng.</p>
                    </div>
                </div>
                <?php if ($ownerHighlights): ?>
                    <div class="owner-grid">
                        <?php foreach ($ownerHighlights as $owner): ?>
                            <article class="owner-card">
                                <div class="owner-avatar"><?php echo h(strtoupper(substr((string)($owner['name'] ?: 'O'), 0, 1))); ?></div>
                                <h3><?php echo h($owner['name']); ?></h3>
                                <p><?php echo (int)$owner['approved_rooms']; ?> tin đã duyệt · Điểm tin cậy <?php echo (int)$owner['trust_score']; ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-market">Chưa có chủ trọ nào có tin đã duyệt để hiển thị.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section">
            <div class="container-lg">
                <div class="panel">
                    <div class="section-head mb-3">
                        <div>
                            <div class="kicker">Tiện ích</div>
                            <h2 class="section-title">Lọc nhanh theo tiện ích cần có.</h2>
                        </div>
                    </div>
                    <div class="utility-strip">
                        <?php foreach ($utilities as $utility): ?>
                            <a class="utility-pill" href="phongtro.php?keyword=<?php echo urlencode($utility['name']); ?>">
                                <i class="fa-solid fa-check me-1"></i><?php echo h($utility['name']); ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if (!$utilities): ?>
                            <?php foreach (['Wifi', 'Điều hòa', 'Máy giặt', 'Ban công', 'Bãi xe', 'Giờ giấc tự do'] as $utility): ?>
                                <a class="utility-pill" href="phongtro.php?keyword=<?php echo urlencode($utility); ?>">
                                    <i class="fa-solid fa-check me-1"></i><?php echo h($utility); ?>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container-lg">
                <div class="owner-cta">
                    <div class="kicker text-white">Dành cho chủ trọ</div>
                    <h2>Đăng tin có cấu trúc, được duyệt rõ ràng và xuất hiện ở đúng nơi người thuê đang tìm.</h2>
                    <p>Owner có dashboard riêng để quản lý phòng, booking và doanh thu, nhưng sau khi đăng nhập vẫn có thể xem trang chủ như người dùng bình thường.</p>
                    <div class="hero-actions">
                        <a href="<?php echo $currentUser['role'] === 'owner' ? 'owner/dashboard.php' : 'owner-register.php'; ?>" class="btn-home brand">
                            <i class="fa-solid fa-house-user"></i> Vào kênh chủ trọ
                        </a>
                        <a href="phongtro.php" class="btn-home">
                            <i class="fa-solid fa-store"></i> Xem marketplace
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container-lg">
            <div class="row gy-4">
                <div class="col-lg-5">
                    <h5 class="fw-black mb-3">QuanLyPhongTro</h5>
                    <p class="mb-0">Nền tảng tìm phòng và quản lý thuê trọ cho người thuê, chủ trọ và admin kiểm duyệt.</p>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white fw-bold">Người thuê</h6>
                    <a class="d-block mb-2" href="phongtro.php">Tìm phòng</a>
                    <a class="d-block mb-2" href="register.php">Đăng ký</a>
                    <a class="d-block" href="login.php">Đăng nhập</a>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h6 class="text-white fw-bold">Chủ trọ</h6>
                    <a class="d-block mb-2" href="owner-register.php">Đăng ký owner</a>
                    <a class="d-block mb-2" href="owner/dashboard.php">Dashboard</a>
                    <a class="d-block" href="#owners">Chủ trọ nổi bật</a>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h6 class="text-white fw-bold">Quản trị</h6>
                    <a class="d-block mb-2" href="admin/index.php">Admin dashboard</a>
                    <p class="mb-0">Tin owner đăng lên sẽ hiển thị tốt hơn sau khi được admin duyệt.</p>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <span class="text-white-50">© 2026 QuanLyPhongTro</span>
                <span class="text-white-50">Thiết kế theo luồng marketplace: xem trước, dashboard sau.</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
