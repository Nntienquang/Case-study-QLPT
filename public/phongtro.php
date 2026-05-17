<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<?php

require_once '../config/database.php'; 

$current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

if (!function_exists('phongtro_order_by')) {
    function phongtro_order_by(string $sort): string
    {
        if ($sort === 'price_asc') {
            return 'm.price ASC';
        }
        if ($sort === 'price_desc') {
            return 'm.price DESC';
        }

        return 'm.created_at DESC';
    }
}

if (!function_exists('phongtro_filter_sql')) {
    function phongtro_filter_sql(array $source, array &$params, string &$types): string
    {
        $where = '';
        $keyword = trim((string)($source['keyword'] ?? ''));
        $districtId = trim((string)($source['district_id'] ?? ''));
        $categoryId = trim((string)($source['category_id'] ?? ''));
        $maxPrice = trim((string)($source['max_price'] ?? ''));
        $areaMin = trim((string)($source['area_min'] ?? ''));

        if ($keyword !== '') {
            $where .= ' AND (m.title LIKE ? OR m.description LIKE ? OR m.address LIKE ? OR m.utilities LIKE ? OR m.province_name LIKE ? OR m.district_name LIKE ? OR m.ward_name LIKE ? OR m.street_address LIKE ?)';
            $kw = '%' . $keyword . '%';
            array_push($params, $kw, $kw, $kw, $kw, $kw, $kw, $kw, $kw);
            $types .= 'ssssssss';
        }
        if ($districtId !== '') {
            $where .= ' AND m.district_id = ?';
            $params[] = (int)$districtId;
            $types .= 'i';
        }
        if ($categoryId !== '') {
            $where .= ' AND m.category_id = ?';
            $params[] = (int)$categoryId;
            $types .= 'i';
        }
        if ($maxPrice !== '') {
            $where .= ' AND m.price <= ?';
            $params[] = (int)$maxPrice;
            $types .= 'i';
        }
        if ($areaMin !== '') {
            $where .= ' AND m.area >= ?';
            $params[] = (float)$areaMin;
            $types .= 'd';
        }

        return $where;
    }
}

// =================================================================
// 1. PHẦN XỬ LÝ AJAX (Sắp xếp & Tải thêm phòng)
// =================================================================
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'load_rooms') {
    
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $sort = isset($_POST['sort']) ? $_POST['sort'] : 'newest';

    $limit = 9; 
    $offset = ($page - 1) * $limit;

    $orderBy = phongtro_order_by($sort);
    $params = [$current_user_id];
    $types = 'i';
    $filterSql = phongtro_filter_sql($_POST, $params, $types);

    try {
        $sql = "SELECT m.id, m.title, m.price, m.area, m.address, m.bedrooms, m.bathrooms, 
                       (SELECT image_url FROM motel_images mi WHERE mi.motel_id = m.id LIMIT 1) as thumbnail,
                       IF(f.id IS NOT NULL, 1, 0) as is_favorited
                FROM motels m 
                LEFT JOIN favorites f ON f.motel_id = m.id AND f.user_id = ?
                WHERE m.status = 'approved' $filterSql
                ORDER BY $orderBy 
                LIMIT ? OFFSET ?";

        $stmt = $conn->prepare($sql);
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rooms = $result->fetch_all(MYSQLI_ASSOC);

        $html = "";
        $hasMore = count($rooms) == $limit; 

        if (count($rooms) > 0) {
            foreach ($rooms as $room) {
                $price_format = number_format($room['price'], 0, ',', '.');
                $raw_img = $room['thumbnail'];
                $image_src = empty($raw_img) ? 'assets/images/default-room.jpg' : (filter_var($raw_img, FILTER_VALIDATE_URL) ? $raw_img : 'uploads/' . htmlspecialchars($raw_img));
                $is_saved = $room['is_favorited'] == 1;
                $heart_class = $is_saved ? "fas" : "far";
                $saved_class = $is_saved ? "saved" : "";

                $html .= '
                <div class="room-card">
                    <div class="room-img-wrap">
                        <img src="'.$image_src.'" alt="'.htmlspecialchars($room['title']).'">
                        <span class="room-badge badge-available">Còn phòng</span>
                        <button class="btn-favorite '.$saved_class.'" data-id="'.$room['id'].'">
                            <i class="'.$heart_class.' fa-heart"></i>
                        </button>
                    </div>
                    
                    <div class="room-content">
                        <div class="room-price">'.$price_format.' đ<span>/tháng</span></div>
                        <a href="user/motel-detail.php?id='.$room['id'].'" class="room-title">'.htmlspecialchars($room['title']).'</a>
                        
                        <div class="room-specs">
                            <span><i class="fas fa-vector-square"></i> '.$room['area'].' m²</span>
                            <span><i class="fas fa-bed"></i> '.$room['bedrooms'].' PN</span>
                            <span><i class="fas fa-bath"></i> '.$room['bathrooms'].' WC</span>
                        </div>
                        
                        <div class="room-address">
                            <i class="fas fa-map-marker-alt"></i> '.htmlspecialchars($room['address']).'
                        </div>
                    </div>
                </div>';
            }
        } elseif ($page == 1) {
            $html = "<div class='w-100 text-center py-5'><p class='text-secondary'>Hiện tại chưa có phòng nào được đăng.</p></div>";
        }

        echo json_encode(['status' => 'success', 'html' => $html, 'hasMore' => $hasMore]);

    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    
    exit; // <--- CỰC KỲ QUAN TRỌNG: Dừng PHP lại tại đây, không in phần HTML bên dưới ra nữa
}

// =================================================================
// 2. PHẦN GIAO DIỆN HTML HIỂN THỊ BÌNH THƯỜNG
// =================================================================
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuanLyPhongTro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">  
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
<style>
   /* HIỆU ỨNG NỀN TRÔI CHO TRANG PHÒNG TRỌ */
   .rooms-hero {
            position: relative;
            margin-top: -120px; /* Luồn dưới menu */
            padding-top: 180px; /* Cách menu một khoảng an toàn */
            padding-bottom: 60px;
            
            /* Ép tràn viền 100% màn hình */
            width: 100vw;
            margin-left: calc(-50vw + 50%);
            margin-right: calc(-50vw + 50%);
            margin-bottom: 40px;
            
            z-index: 1; 
            overflow: hidden;
        }

        /* Lớp 1: Ảnh nền phòng trọ đẹp mắt */
        .rooms-hero::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            /* Ảnh một căn phòng đẹp, sáng sủa */
            background-image: url('https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover; 
            background-position: center;
            z-index: -2;
            
            /* Chuyển động trôi từ từ */
            animation: panBackgroundRooms 30s ease-in-out infinite alternate;
        }

        /* Lớp 2: Lớp phủ gradient (Sáng dần xuống dưới để hòa vào nền web) */
        .rooms-hero::after {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            /* Phủ trắng 40% ở trên và 100% ở dưới */
            background: linear-gradient(to bottom, rgba(255, 255, 255, 0.4) 0%, rgba(248, 249, 250, 1) 100%);
            z-index: -1;
        }

        @keyframes panBackgroundRooms {
            0% { transform: scale(1.05) translateX(0); }
            100% { transform: scale(1.1) translateX(-2%); }
        }

        .sort-box {
            transition: all 0.3s ease;
        }
        .sort-box:hover {
            border-color: #0d6efd !important;
            transform: translateY(-2px);
        }
        /* Loại bỏ cái mũi tên mặc định của Bootstrap để nó không đè chữ */
        .sort-box .form-select {
            background-position: right 0 center;
            padding-right: 25px;
        }
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
            background: rgba(255,255,255,.82);
            border: 1px solid rgba(255,255,255,.78);
            box-shadow: 0 18px 50px rgba(15,23,42,.12);
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
        
            box-shadow: 0 18px 50px rgba(15,23,42,.08);
        
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
            bo
            x-shadow: 0 4px 12px rgba(17, 24, 39, 0.3);
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

            
                .nav-links, .nav-actions {
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
                    
        .bg-light-gray { background-color: #f9fafb; } 
        .text-dark-blue { color: #111827; }
        .text-teal { color: #1185a1; }
.grid-rooms {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}


.sort-dropdown {
    border-radius: 20px;
    border: 1px solid #e5e7eb;
    background-color: white;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

.room-card {
    background: #ffffff;
    border-radius: 16px; 
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); 
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #f3f4f6;
}

.room-card:hover {
    transform: translateY(-5px); 
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}


.room-img-wrap {
    position: relative;
    height: 220px;
    width: 100%;
}

.room-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.room-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    backdrop-filter: blur(4px);
}
.badge-available { background: rgba(16, 185, 129, 0.9); color: white; } /* Xanh lá */
.badge-rented { background: rgba(239, 68, 68, 0.9); color: white; } /* Đỏ */


.btn-favorite {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    color: #9ca3af; 
    cursor: pointer;
    transition: all 0.2s;
}

.btn-favorite:hover {
    transform: scale(1.1);
}

.btn-favorite.saved {
    color: #ef4444; 
}

.room-content {
    padding: 20px;
}

.room-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1185a1; 
    margin-bottom: 8px;
}

.room-price span {
    font-size: 0.9rem;
    font-weight: 400;
    color: #6b7280;
}

.room-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #111827;
    text-decoration: none;
    display: -webkit-box;
    -webkit-line-clamp: 2; 
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 12px;
    transition: color 0.2s;
}

.room-title:hover {
    color: #1185a1;
}

.room-specs {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #4b5563;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f3f4f6;
}

.room-specs i { color: #9ca3af; }

.room-address {
    font-size: 13px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.skeleton-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f3f4f6;
    overflow: hidden;
}

.skeleton-img {
    height: 220px;
    background: #e5e7eb;
    animation: pulse 1.5s infinite ease-in-out;
}

.skeleton-text {
    height: 14px;
    background: #e5e7eb;
    border-radius: 4px;
    animation: pulse 1.5s infinite ease-in-out;
}

.skeleton-price {
    height: 24px;
    background: #d1d5db;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}


.btn-load-more:hover {
    background-color: #111827;
    color: white;
}
    </style>
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
                <a href="login.php" class="nav-item login-text">Đăng nhập</a>
                <a href="owner-register.php" class="btn-post">Đăng phòng</a>
            </div>
        </div>

    </div>
</nav>
</head>
<body>
<!-- Khu vực Danh sách phòng trọ -->
<section class="rooms-section py-5 bg-light-gray">
    <div class="container-lg">
        

    <div class="rooms-hero">
        <div class="container">
            <div class="text-center"> 
                <span class="badge bg-white text-success border border-success-subtle rounded-pill px-3 py-2 mb-3 shadow-sm">
                    <i class="fas fa-check-circle me-1"></i> Hàng ngàn phòng trống
                </span>
                
                <h1 class="fw-bold mb-3" style="color: #1a1d20; font-size: 3rem; letter-spacing: -1px;">
                    Danh sách phòng trọ
                </h1>

                <p class="text-secondary mx-auto mb-4" style="max-width: 600px; font-size: 1.1rem;">
                    Tìm kiếm chỗ ở ưng ý nhất với hệ thống bộ lọc thông minh và dữ liệu minh bạch.
                </p>

                <div class="d-flex justify-content-center">
                    <div class="sort-box p-1 px-3 rounded-pill shadow-sm d-inline-flex align-items-center bg-white border">
                        <label for="sortRooms" class="text-muted me-2 small fw-medium" style="white-space: nowrap;">Sắp xếp theo:</label>
                        <select class="form-select border-0 fw-bold bg-transparent py-2" id="sortRooms" style="box-shadow: none; cursor: pointer; width: auto; min-width: 170px;">
                            <option value="newest">Mới đăng nhất</option>
                            <option value="price_asc">Giá: Thấp đến Cao</option>
                            <option value="price_desc">Giá: Cao xuống Thấp</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
   

    
        <div class="grid-rooms d-none" id="skeleton-container">
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="p-3"><div class="skeleton-text skeleton-price w-50 mb-2"></div><div class="skeleton-text w-100 mb-2"></div><div class="skeleton-text w-75 mb-3"></div><div class="skeleton-text w-50"></div></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="p-3"><div class="skeleton-text skeleton-price w-50 mb-2"></div><div class="skeleton-text w-100 mb-2"></div><div class="skeleton-text w-75 mb-3"></div><div class="skeleton-text w-50"></div></div></div>
            <div class="skeleton-card"><div class="skeleton-img"></div><div class="p-3"><div class="skeleton-text skeleton-price w-50 mb-2"></div><div class="skeleton-text w-100 mb-2"></div><div class="skeleton-text w-75 mb-3"></div><div class="skeleton-text w-50"></div></div></div>
        </div>

        <div class="grid-rooms" id="real-rooms-container">
            <?php
            require_once '../config/database.php'; 

            $current_user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

            try {
                $sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'newest';
                $orderBy = phongtro_order_by($sort);
                $params = [$current_user_id];
                $types = 'i';
                $filterSql = phongtro_filter_sql($_GET, $params, $types);

                $sql = "SELECT m.id, m.title, m.price, m.area, m.address, m.bedrooms, m.bathrooms, 
                               (SELECT image_url FROM motel_images mi WHERE mi.motel_id = m.id LIMIT 1) as thumbnail,
                               IF(f.id IS NOT NULL, 1, 0) as is_favorited
                        FROM motels m 
                        LEFT JOIN favorites f ON f.motel_id = m.id AND f.user_id = ?
                        WHERE m.status = 'approved' $filterSql
                        ORDER BY $orderBy 
                        LIMIT 9";
                $stmt = $conn->prepare($sql);
                
                if ($stmt === false) {
                    throw new Exception("Lỗi prepare SQL: " . $conn->error);
                }

                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
                $result = $stmt->get_result();
                $rooms = $result->fetch_all(MYSQLI_ASSOC);

                if (count($rooms) > 0) {
                    foreach ($rooms as $room) {
                        $price_format = number_format($room['price'], 0, ',', '.');
                        $raw_img = $room['thumbnail'];
                        
                        if (empty($raw_img)) {
                            $image_src = 'assets/images/default-room.jpg';
                        } elseif (filter_var($raw_img, FILTER_VALIDATE_URL)) {
                            $image_src = $raw_img;
                        } else {
                            $image_src = 'uploads/' . htmlspecialchars($raw_img);
                        }

                        $is_saved = $room['is_favorited'] == 1;
                        $heart_class = $is_saved ? "fas" : "far";
                        $saved_class = $is_saved ? "saved" : "";
?>
                        <div class="room-card">
                            <div class="room-img-wrap">
                                <img src="<?php echo $image_src; ?>" alt="<?php echo htmlspecialchars($room['title']); ?>">
                                <span class="room-badge badge-available">Còn phòng</span>
                                <button class="btn-favorite <?php echo $saved_class; ?>" data-id="<?php echo $room['id']; ?>">
                                    <i class="<?php echo $heart_class; ?> fa-heart"></i>
                                </button>
                            </div>
                            
                            <div class="room-content">
                                <div class="room-price"><?php echo $price_format; ?> đ<span>/tháng</span></div>
                                <a href="user/motel-detail.php?id=<?php echo $room['id']; ?>" class="room-title">
                                    <?php echo htmlspecialchars($room['title']); ?>
                                </a>
                                
                                <div class="room-specs">
                                    <span><i class="fas fa-vector-square"></i> <?php echo $room['area']; ?> m²</span>
                                    <span><i class="fas fa-bed"></i> <?php echo $room['bedrooms']; ?> PN</span>
                                    <span><i class="fas fa-bath"></i> <?php echo $room['bathrooms']; ?> WC</span>
                                </div>
                                
                                <div class="room-address">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($room['address']); ?>
                                </div>
                            </div>
                        </div>
<?php
                    }
                } else {
                    echo "<div class='w-100 text-center py-5'><p class='text-secondary'>Hiện tại chưa có phòng nào được đăng.</p></div>";
                }
            } catch(Exception $e) {
                echo "<p class='text-center py-4 text-danger'>Đang có lỗi xử lý dữ liệu: " . $e->getMessage() . "</p>";
            }
            ?>
        </div>
    </div>
</section><footer class="footer py-5 mt-5 bg-dark text-white">
        <style>
                    .footer {
                        background-color: #121416 !important;
                        border-top: 1px solid #2d3238;
                    }

        
                    .footer-description, .footer-info, .footer-copyright {
                        color: #ced4da !important; 
                        font-size: 0.95rem;
                        line-height: 1.6;
                    }

                    .footer-link {
                        color: #adb5bd !important;
                        text-decoration: none;
                        transition: all 0.3s ease;
                        display: inline-block;
                        padding: 2px 0;
                    }

                    .footer-link:hover {
                        color: #ffffff !important; 
                        transform: translateX(5px); 
                    }

                    .footer-divider {
                        border-top: 1px solid rgba(255, 255, 255, 0.1);
                    }

                    .social-links a {
                        color: #ffffff;
                        font-size: 1.2rem;
                        opacity: 0.7;
                        transition: 0.3s;
                    }

                    .social-links a:hover {
                        opacity: 1;
                        color: #0d6efd; 
                    }

                    .btn-light {
                        background-color: #f8f9fa;
                        border: none;
                        color: #212529 !important;
                    }

                    .btn-light:hover {
                        background-color: #ffffff;
                        box-shadow: 0 0 10px rgba(255,255,255,0.3);
                    } 
                    </style> 
    <div class="container-lg">
        <div class="row gy-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold mb-3 text-white">🏠 QuanLyPhongTro</h5>
                <p class="footer-description">Tìm phòng sạch đẹp, quản lý thuê trọ dễ dàng và minh bạch. Nền tảng kết nối chủ trọ và người thuê hàng đầu.</p>
                <div class="social-links mt-3">
                    <a href="#" class="me-3"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="me-3"><i class="bi bi-instagram"></i></a>
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                </div>
            </div>
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
                    <li><a href="#" class="footer-link">Chính sách chủ trọ</a></li>
                </ul>
            </div>

            
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3 text-white text-uppercase small">Liên hệ hệ thống</h6>
                <p class="footer-info"><i class="bi bi-geo-alt-fill me-2"></i> Trường Đại học Vinh, Nghệ An</p>
                <p class="footer-info"><i class="bi bi-envelope-fill me-2"></i> ho-tro@quanlyphongtro.vn</p>
                <a href="admin/login.php" class="btn btn-light btn-sm fw-bold px-3 mt-2">Quản trị viên (Admin)</a>
            </div>
        </div>

        <hr class="my-4 footer-divider">

        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div class="footer-copyright">
                © 2026 <strong>QuanLyPhongTro</strong>. Bản quyền thuộc về Team dự án.
            </div>
            <div class="footer-bottom-links">
                <a href="#" class="footer-link me-3">Chính sách bảo mật</a>
                <a href="#" class="footer-link">Điều khoản sử dụng</a>
            </div>
        </div>
    </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('real-rooms-container');
    const skeletonContainer = document.getElementById('skeleton-container');
    const sortSelect = document.getElementById('sortRooms');
    const loadMoreContainer = document.getElementById('load-more-container');
  
    const loadMoreBtn = loadMoreContainer ? loadMoreContainer.querySelector('.btn-load-more') : null;
    const urlParams = new URLSearchParams(window.location.search);

    let currentPage = 1;
    let currentSort = urlParams.get('sort') || (sortSelect ? sortSelect.value : 'newest');
    let isLoading = false;

    if (sortSelect && urlParams.get('sort')) {
        sortSelect.value = currentSort;
    }

    function fetchRooms(page, sort, isLoadMore = false) {
        if (isLoading) return;
        isLoading = true;

        if (!isLoadMore) {
            container.classList.add('d-none');
            skeletonContainer.classList.remove('d-none');
            if(loadMoreContainer) loadMoreContainer.classList.add('d-none');
        } else {
            let originalText = loadMoreBtn.innerHTML;
            loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải...';
            loadMoreBtn.disabled = true;
        }

        const formData = new FormData();
        formData.append('ajax_action', 'load_rooms'); 
        formData.append('page', page);
        formData.append('sort', sort);
        ['keyword', 'district_id', 'category_id', 'max_price', 'area_min'].forEach((key) => {
            if (urlParams.get(key)) {
                formData.append(key, urlParams.get(key));
            }
        });

        fetch('phongtro.php', { 
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (!isLoadMore) {
                    skeletonContainer.classList.add('d-none');
                    container.innerHTML = data.html;
                    container.classList.remove('d-none');
                } else {
                    container.insertAdjacentHTML('beforeend', data.html);
                    loadMoreBtn.innerHTML = 'Tải thêm phòng <i class="fas fa-chevron-down ms-1"></i>';
                    loadMoreBtn.disabled = false;
                }

                if (data.hasMore && loadMoreContainer) {
                    loadMoreContainer.classList.remove('d-none');
                } else if (loadMoreContainer) {
                    loadMoreContainer.classList.add('d-none');
                }
            } else {
                console.error("Lỗi Server:", data.message);
            }
        })
        .catch(error => console.error("Lỗi Fetch API:", error))
        .finally(() => {
            isLoading = false;
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            currentSort = this.value; 
            currentPage = 1;         
            fetchRooms(currentPage, currentSort, false);
        });
    }
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            currentPage++;
            fetchRooms(currentPage, currentSort, true);
        });
    }
});
</script>
            </body>
    </html>
