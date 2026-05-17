<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$allowUnverifiedOwner = true;
require_once __DIR__ . '/_owner_guard.php';
$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';
$message = '';
$message_type = '';

// 1. LẤY THÔNG TIN CÀI ĐẶT HIỆN TẠI
$stmt = $conn->prepare("SELECT email, password, notify_email, notify_booking, show_phone, dark_mode FROM users WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. XỬ LÝ CÁC ACTION TỪ FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ACTION: Thay đổi mật khẩu
    if (isset($_POST['change_password'])) {
        $old = $_POST['old_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (!password_verify($old, $user['password'])) {
            $message = 'Mật khẩu cũ không chính xác!';
            $message_type = 'danger';
        } elseif (strlen($new) < 6) {
            $message = 'Mật khẩu mới phải từ 6 ký tự trở lên!';
            $message_type = 'danger';
        } elseif ($new !== $confirm) {
            $message = 'Mật khẩu xác nhận không khớp!';
            $message_type = 'danger';
        } else {
            $hashed = password_hash($new, PASSWORD_BCRYPT);
            $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $up->bind_param("si", $hashed, $owner_id);
            if ($up->execute()) {
                $message = 'Đã cập nhật mật khẩu mới thành công!';
                $message_type = 'success';
            }
            $up->close();
        }
    }

    // ACTION: Cập nhật tùy chọn (Preferences)
    if (isset($_POST['update_preferences'])) {
        $n_email = isset($_POST['notify_email']) ? 1 : 0;
        $n_booking = isset($_POST['notify_booking']) ? 1 : 0;
        $s_phone = isset($_POST['show_phone']) ? 1 : 0;
        $d_mode = isset($_POST['dark_mode']) ? 1 : 0;

        $up = $conn->prepare("UPDATE users SET notify_email = ?, notify_booking = ?, show_phone = ?, dark_mode = ? WHERE id = ?");
        $up->bind_param("iiiii", $n_email, $n_booking, $s_phone, $d_mode, $owner_id);
        if ($up->execute()) {
            $message = 'Đã lưu các tùy chọn thay đổi!';
            $message_type = 'success';
            // Refresh dữ liệu để hiển thị
            $user['notify_email'] = $n_email;
            $user['notify_booking'] = $n_booking;
            $user['show_phone'] = $s_phone;
            $user['dark_mode'] = $d_mode;
        }
        $up->close();
    }

    // ACTION: Xuất dữ liệu phòng trọ (Export CSV)
    if (isset($_POST['export_data'])) {
        $stmt = $conn->prepare("SELECT title, price, address, status FROM motels WHERE user_id = ?");
        $stmt->bind_param("i", $owner_id);
        $stmt->execute();
        $result = $stmt->get_result();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=danh-sach-phong.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Ten Phong', 'Gia Thue', 'Dia Chi', 'Trang Thai'));
        while ($row = $result->fetch_assoc()) fputcsv($output, $row);
        fclose($output);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài Đặt Hệ Thống - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
    .settings-card {
        border: 1px solid #e9ecef;
        transition: 0.3s;
        margin-bottom: 25px;
    }

    .settings-card:hover {
        border-color: #0d6efd;
    }

    .card-header-custom {
        background: transparent;
        border-bottom: 1px solid #eee;
        padding: 15px 20px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .danger-zone {
        border: 1px solid #fee2e2;
        background: #fffafb;
    }

    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        cursor: pointer;
    }
    </style>
</head>

<body class="workbench <?php echo $user['dark_mode'] ? 'bg-dark text-white' : ''; ?>">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
                <span><?php echo htmlspecialchars($ownerName); ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link " href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link active" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>

            <section>
                <div class="wb-section-head">
                    <h2 class="mb-1">Cài Đặt Tài Khoản</h2>
                    <p class="text-muted">Tùy chỉnh thông báo, bảo mật và trải nghiệm cá nhân của bạn.</p>
                </div>

                <?php if ($message): ?>
                <div
                    class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show border-0 shadow-sm mb-4">
                    <i
                        class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-7">
                        <div class="wb-card settings-card p-0">
                            <div class="card-header-custom"><i class="fas fa-sliders text-primary"></i> Tùy Chọn & Trải
                                Nghiệm</div>
                            <div class="p-4">
                                <form method="POST">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <!-- <div>
                                            <div class="fw-bold">Chế độ tối (Dark Mode)</div>
                                            <small class="text-muted">Thay đổi giao diện sang tông màu tối để bảo vệ
                                                mắt.</small>
                                        </div> -->
                                        <!-- <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="dark_mode"
                                                <?php echo $user['dark_mode'] ? 'checked' : ''; ?>>
                                        </div> -->
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <div class="fw-bold">Công khai số điện thoại</div>
                                            <small class="text-muted">Hiển thị số điện thoại của bạn trên tất cả tin
                                                đăng phòng trọ.</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="show_phone"
                                                <?php echo $user['show_phone'] ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <div class="fw-bold">Thông báo qua Email</div>
                                            <small class="text-muted">Gửi email nhắc nhở khi có khách đặt lịch hẹn hoặc
                                                đặt phòng.</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notify_email"
                                                <?php echo $user['notify_email'] ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div>
                                            <div class="fw-bold">Âm báo Booking mới</div>
                                            <small class="text-muted">Phát âm thanh thông báo trên trình duyệt khi có
                                                đơn mới.</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="notify_booking"
                                                <?php echo $user['notify_booking'] ? 'checked' : ''; ?>>
                                        </div>
                                    </div>
                                    <button type="submit" name="update_preferences" class="btn btn-primary">Lưu Tùy
                                        Chọn</button>
                                </form>
                            </div>
                        </div>

                        <div class="wb-card settings-card p-0">
                            <div class="card-header-custom"><i class="fas fa-shield-halved text-success"></i> Bảo Mật
                                Mật Khẩu</div>
                            <div class="p-4">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Mật khẩu hiện tại</label>
                                        <input type="password" name="old_password" class="form-control" required>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Mật khẩu mới</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Xác nhận mật khẩu</label>
                                            <input type="password" name="confirm_password" class="form-control"
                                                required>
                                        </div>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-outline-primary">Cập
                                        Nhật Mật Khẩu</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="wb-card settings-card p-0">
                            <div class="card-header-custom"><i class="fas fa-file-export text-info"></i> Sao Lưu Dữ Liệu
                            </div>
                            <div class="p-4">
                                <p class="small text-muted">Tải về toàn bộ danh sách phòng trọ và trạng thái kinh doanh
                                    của bạn dưới dạng file .csv (Excel).</p>
                                <form method="POST">
                                    <button type="submit" name="export_data" class="btn btn-light border w-100"><i
                                            class="fas fa-download me-1"></i> Xuất dữ liệu .CSV</button>
                                </form>
                            </div>
                        </div>

                        <div class="wb-card danger-zone p-4">
                            <h6 class="text-danger fw-bold"><i class="fas fa-triangle-exclamation"></i> Vùng Nguy Hiểm
                            </h6>
                            <p class="small text-muted">Một khi bạn xóa tài khoản, mọi dữ liệu về phòng trọ và doanh thu
                                sẽ bị xóa vĩnh viễn và không thể khôi phục.</p>
                            <button class="btn btn-danger w-100" data-bs-toggle="modal"
                                data-bs-target="#deleteModal">Xóa Vĩnh Viễn Tài Khoản</button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Xác nhận xóa tài khoản</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Hệ thống yêu cầu bạn nhập lại mật khẩu để xác nhận hành động xóa tài khoản này.</p>
                    <input type="password" class="form-control" placeholder="Nhập mật khẩu của bạn">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-danger">Tôi đã hiểu, hãy xóa tài khoản</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
