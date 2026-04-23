<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Admin Dashboard' ?> - Quản Lý Phòng Trọ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/QuanLyPhongTro/admin/public/css/admin.css">
</head>
<body>
    <nav class="navbar navbar-dark sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-speedometer2"></i> Quản Lý Phòng Trọ
            </span>
            <div>
                <span class="text-light me-3" style="font-weight: 500;">
                    <i class="bi bi-person-circle"></i> <?= $_SESSION['user']['name'] ?? 'Admin' ?>
                </span>
                <a href="/QuanLyPhongTro/admin/public/logout.php" class="btn btn-sm btn-outline-light" style="font-weight: 600;">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row g-0">
            <!-- Sidebar -->
            <nav class="col-lg-2 sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=dashboard&action=index">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=users&action=index">
                                <i class="bi bi-people-fill"></i> Người Dùng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=categories&action=index">
                                <i class="bi bi-folder-fill"></i> Danh Mục
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'utilities' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=utilities&action=index">
                                <i class="bi bi-tools"></i> Tiện Ích
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'motels' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=motels&action=index">
                                <i class="bi bi-building"></i> Phòng Trọ
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'bookings' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=bookings&action=index">
                                <i class="bi bi-calendar-check"></i> Đặt Phòng
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'payments' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=payments&action=index">
                                <i class="bi bi-credit-card"></i> Thanh Toán
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'transactions' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=transactions&action=index">
                                <i class="bi bi-arrow-left-right"></i> Giao Dịch
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'withdraw' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=withdraw&action=index">
                                <i class="bi bi-arrow-down-circle"></i> Rút Tiền
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $currentPage === 'reviews' ? 'active' : '' ?>" 
                               href="/QuanLyPhongTro/admin/public/index.php?controller=reviews&action=index">
                                <i class="bi bi-star-fill"></i> Đánh Giá
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <?php if ($flash = getFlash()): ?>
                    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show mt-3" role="alert">
                        <?= $flash['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php include $viewPath; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/QuanLyPhongTro/admin/public/js/admin.js"></script>
</body>
</html>
