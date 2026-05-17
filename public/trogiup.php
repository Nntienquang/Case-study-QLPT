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
    <title>Trợ giúp - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5">
        <a href="index.php" class="btn btn-outline-secondary mb-4"><i class="fas fa-arrow-left"></i> Trang chủ</a>
        <div class="bg-white border rounded-4 shadow-sm p-4 p-lg-5">
            <div class="mb-4">
                <div class="text-uppercase fw-bold text-primary small mb-2">Trợ giúp hệ thống</div>
                <h1 class="fw-bold">Bạn cần hỗ trợ luồng nào?</h1>
                <p class="text-muted mb-0">Các lối tắt dưới đây đưa bạn tới đúng khu vực đang dùng trong hệ thống.</p>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <a class="d-block h-100 p-4 border rounded-3 text-decoration-none text-dark" href="phongtro.php">
                        <i class="fas fa-search fs-3 text-primary mb-3"></i>
                        <h5 class="fw-bold">Tìm phòng</h5>
                        <p class="text-muted mb-0">Xem danh sách phòng đã duyệt và lọc theo nhu cầu.</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="d-block h-100 p-4 border rounded-3 text-decoration-none text-dark" href="owner-register.php">
                        <i class="fas fa-house-user fs-3 text-success mb-3"></i>
                        <h5 class="fw-bold">Chủ phòng</h5>
                        <p class="text-muted mb-0">Đăng ký owner, cập nhật hồ sơ và đăng tin cho thuê.</p>
                    </a>
                </div>
                <div class="col-md-4">
                    <a class="d-block h-100 p-4 border rounded-3 text-decoration-none text-dark" href="policies/payment-policy.php">
                        <i class="fas fa-shield-halved fs-3 text-warning mb-3"></i>
                        <h5 class="fw-bold">Chính sách</h5>
                        <p class="text-muted mb-0">Xem quy định thanh toán, người thuê và chủ phòng.</p>
                    </a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
