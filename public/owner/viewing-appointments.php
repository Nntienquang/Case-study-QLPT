<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$userQuery = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
$userQuery->bind_param("i", $owner_id);
$userQuery->execute();
$userTheme = $userQuery->get_result()->fetch_assoc();
$is_dark = $userTheme['dark_mode'] ?? 0;

$db = new Database($conn);
$owner_id = (int)$_SESSION['user_id'];
$ownerName = $_SESSION['name'] ?? 'Chủ phòng';
$allowed_statuses = ['accepted', 'rejected', 'completed'];

// Nhận filter trạng thái từ URL để lúc redirect giữ nguyên bộ lọc
$status_filter = $_GET['status'] ?? '';

// Xử lý Form Cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = (int)$_POST['appointment_id'];
    $status = $_POST['status'];

    if (in_array($status, $allowed_statuses, true)) {
        $stmt = $db->prepare('
            SELECT va.user_id, m.title
            FROM viewing_appointments va
            JOIN motels m ON va.motel_id = m.id
            WHERE va.id = ? AND va.owner_id = ?
        ');
        $stmt->bind_param('ii', $appointment_id, $owner_id);
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($appointment) {
            $stmt = $db->prepare('UPDATE viewing_appointments SET status = ?, updated_at = NOW() WHERE id = ? AND owner_id = ?');
            $stmt->bind_param('sii', $status, $appointment_id, $owner_id);

            if ($stmt->execute()) {
<<<<<<< HEAD
                // Gửi thông báo cho User
                $title = 'Lịch xem phòng đã được cập nhật';
                $status_vn = appointment_label($status);
                $body = 'Lịch hẹn xem phòng "' . $appointment['title'] . '" của bạn hiện có trạng thái: ' . $status_vn;
                $link = 'user/viewing-appointments.php'; // Điều hướng user về trang lịch xem của họ

=======
                $message = 'Đã cập nhật lịch xem phòng.';
                $statusLabels = [
                    'pending' => 'chờ xác nhận',
                    'accepted' => 'đã chấp nhận',
                    'rejected' => 'từ chối',
                    'completed' => 'đã xem',
                ];
                $statusVi = $statusLabels[$status] ?? $status;
                $title = 'Lịch xem phòng đã cập nhật';
                $body = 'Lịch xem phòng "' . $appointment['title'] . '" hiện có trạng thái: ' . $statusVi;
                $link = 'user/dashboard.php';
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                $notify = $db->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, 'viewing_status', ?, ?, ?)");
                if ($notify) {
                    $userId = (int)$appointment['user_id'];
                    $notify->bind_param('isss', $userId, $title, $body, $link);
                    $notify->execute();
                    $notify->close();
                }

                $_SESSION['message'] = 'Đã cập nhật trạng thái lịch xem phòng thành công!';
                $_SESSION['message_type'] = 'success';
            } else {
                $_SESSION['message'] = 'Lỗi hệ thống: Không thể cập nhật.';
                $_SESSION['message_type'] = 'danger';
            }
            $stmt->close();
        } else {
<<<<<<< HEAD
            $_SESSION['message'] = 'Không tìm thấy lịch xem, hoặc bạn không có quyền thao tác.';
            $_SESSION['message_type'] = 'danger';
=======
            $message = 'Không tìm thấy lịch xem của bạn.';
            $message_type = 'danger';
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
        }

        // Redirect để tránh lỗi F5 (Double Submit)
        $redirect_url = 'viewing-appointments.php' . ($status_filter ? "?status=$status_filter" : '');
        header("Location: $redirect_url");
        exit;
    }
}

// Truy vấn danh sách lịch hẹn
$where = 'WHERE va.owner_id = ?';
$types = 'i';
$params = [$owner_id];

if ($status_filter !== '') {
    $where .= ' AND va.status = ?';
    $types .= 's';
    $params[] = $status_filter;
}

$stmt = $db->prepare("
    SELECT va.*, m.title AS motel_title, m.address, u.name AS user_name, u.email, u.phone
    FROM viewing_appointments va
    JOIN motels m ON va.motel_id = m.id
    JOIN users u ON va.user_id = u.id
    $where
    ORDER BY va.preferred_time ASC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function appointment_label(string $status): string
{
    return [
        'pending' => 'Chờ xác nhận',
        'accepted' => 'Đã chấp nhận',
        'rejected' => 'Từ chối',
<<<<<<< HEAD
        'completed' => 'Đã xem xong',
=======
        'completed' => 'Đã xem',
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
    ][$status] ?? ucfirst($status);
}

function appointment_badge(string $status): string
{
    return [
        'pending' => 'warning',
        'accepted' => 'success',
        'rejected' => 'danger',
        'completed' => 'secondary',
    ][$status] ?? 'secondary';
}
?>
<!DOCTYPE html>
<html lang="vi" <?php echo $is_dark ? 'data-bs-theme="dark"' : ''; ?>>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>Lịch Xem Phòng - QuanLyPhongTro</title>
=======
    <title>Lịch xem phòng - Owner</title>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <link href="../assets/css/workbench.css" rel="stylesheet">
    <style>
<<<<<<< HEAD
    .appointment-card {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid #e9ecef;
        transition: 0.2s;
    }

    .appointment-card:hover {
        border-color: #dee2e6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .meta-info {
        font-size: 0.9rem;
        color: #6c757d;
        display: flex;
        flex-wrap: wrap;
        gap: 12px 20px;
        margin-top: 10px;
    }

    .meta-info span {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tenant-note {
        background: #f8f9fa;
        border-left: 3px solid #0d6efd;
        padding: 10px 15px;
        border-radius: 4px;
        margin-top: 15px;
        font-size: 0.9rem;
        color: #495057;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="../index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
                <span><?php echo htmlspecialchars($ownerName); ?></span>
=======
        body { background: #f6f8fb; }
        .app-shell { padding: 28px 0 44px; }
        .app-nav { background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06); }
        .content-card, .appointment-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .content-card { padding: 24px; margin-bottom: 18px; }
        .appointment-card { padding: 18px; margin-bottom: 14px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; }
        .appointment-title { font-weight: 900; font-size: 18px; color: #101828; }
        .meta { color: #667085; font-size: 14px; display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 8px; }
        .note { background: #f8fafc; border-radius: 10px; padding: 10px 12px; margin-top: 12px; color: #475467; }
        .empty-state { text-align: center; padding: 44px 20px; color: #667085; }
        @media (max-width: 991px) { .appointment-card { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar app-nav navbar-expand-lg sticky-top">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-house-chimney"></i> QuanLyPhongTro</a>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </header>

<<<<<<< HEAD
    <main class="wb-shell">
        <div class="container-lg wb-layout">
            <aside class="wb-sidebar">
                <div class="wb-side-title">Chủ phòng</div>
                <a class="wb-side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tổng quan</a>
                <a class="wb-side-link" href="listings.php"><i class="fas fa-list"></i> Phòng của tôi</a>
                <a class="wb-side-link" href="add-listing.php"><i class="fas fa-plus"></i> Đăng phòng</a>
                <a class="wb-side-link active" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lịch
                    xem</a>
                <a class="wb-side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>

                <div class="wb-side-title mt-4">Quản lý Vận hành</div>
                <a class="wb-side-link" href="utilities.php"><i class="fas fa-bolt"></i> Điện, Nước & Dịch vụ</a>
                <a class="wb-side-link" href="maintenance.php"><i class="fas fa-screwdriver-wrench"></i> Bảo trì & Sự
                    cố</a>
                <!-- <a class="wb-side-link " href="contracts.php"><i class="fas fa-file-signature"></i> Hợp đồng</a>
                <a class="wb-side-link" href="analytics.php"><i class="fas fa-chart-pie"></i> Phân tích thông minh <span
                        class="badge bg-warning text-dark ms-2" style="font-size: 0.65em;">PRO</span></a> -->

                <div class="wb-side-title mt-4">Tài khoản</div>
                <a class="wb-side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="wb-side-link" href="../notifications.php"><i class="fas fa-bell"></i> Thông báo</a>
                <a class="wb-side-link" href="profile.php"><i class="fas fa-user"></i> Hồ sơ</a>
                <a class="wb-side-link" href="settings.php"><i class="fas fa-gear"></i> Cài đặt</a>
            </aside>


            <section>
                <?php if (isset($_SESSION['message'])): ?>
                <div
                    class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show border-0 shadow-sm">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                <?php endif; ?>

                <div class="wb-card mb-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="fw-bold mb-1"><i class="fas fa-calendar-day text-primary me-2"></i> Lịch Xem Phòng
                        </h3>
                        <p class="text-muted mb-0 small">Xác nhận, từ chối hoặc đánh dấu đã xem cho các lịch hẹn từ
                            khách hàng.</p>
                    </div>
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Tất cả trạng thái --</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ
                                xác nhận</option>
                            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Đã
                                chấp nhận</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Đã
                                xem xong</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Từ
                                chối</option>
                        </select>
=======
    <main class="app-shell">
        <div class="container-lg">
            <div class="row">
                <div class="col-lg-3 mb-4 mb-lg-0">
                    <?php
                    $ownerNavActive = 'viewings';
                    require __DIR__ . '/_nav_sidebar.php';
                    ?>
                </div>

                <div class="col-lg-9">
                <div class="content-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h1 class="fw-bold mb-2">Lịch xem phòng</h1>
                        <p class="text-muted mb-0">Xác nhận, từ chối hoặc đánh dấu đã xem cho khách quan tâm.</p>
                    </div>
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select">
                            <option value="">Tất cả</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Đã chấp nhận</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Đã xem</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Từ chối</option>
                        </select>
                        <button class="btn btn-primary" type="submit">Lọc</button>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                    </form>
                </div>

                <?php if ($appointments): ?>
<<<<<<< HEAD
                <?php foreach ($appointments as $appointment): ?>
                <div class="wb-card appointment-card">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <h5 class="fw-bold mb-0 text-dark">
                                    <?php echo htmlspecialchars($appointment['motel_title']); ?></h5>
                                <span class="badge bg-<?php echo appointment_badge($appointment['status']); ?>">
                                    <?php echo appointment_label($appointment['status']); ?>
                                </span>
                            </div>

                            <div class="meta-info">
                                <span class="text-primary fw-bold"><i class="fas fa-clock"></i>
                                    <?php echo date('H:i - d/m/Y', strtotime($appointment['preferred_time'])); ?></span>
                                <span><i class="fas fa-user text-secondary"></i>
                                    <?php echo htmlspecialchars($appointment['user_name']); ?></span>
                                <span><i class="fas fa-phone text-secondary"></i> <a
                                        href="tel:<?php echo htmlspecialchars($appointment['phone']); ?>"
                                        class="text-decoration-none"><?php echo htmlspecialchars($appointment['phone'] ?: 'Chưa cập nhật SDT'); ?></a></span>
                            </div>
                            <div class="meta-info mt-1">
                                <span><i class="fas fa-location-dot text-secondary"></i>
                                    <?php echo htmlspecialchars($appointment['address']); ?></span>
                            </div>

                            <?php if (!empty($appointment['note'])): ?>
                            <div class="tenant-note">
                                <strong><i class="fas fa-comment-dots"></i> Lời nhắn từ khách:</strong><br>
                                <?php echo nl2br(htmlspecialchars($appointment['note'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-lg-4 mt-3 mt-lg-0 text-lg-end border-start-lg ps-lg-4">
                            <?php if ($appointment['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline-block" onsubmit="disableButton(this)">
                                <input type="hidden" name="appointment_id"
                                    value="<?php echo (int)$appointment['id']; ?>">
                                <button class="btn btn-success mb-2 w-100" name="status" value="accepted"><i
                                        class="fas fa-check me-1"></i> Chấp nhận lịch hẹn</button>
                            </form>
                            <form method="POST" class="d-inline-block w-100"
                                onsubmit="return confirmAction(this, 'Bạn có chắc chắn muốn TỪ CHỐI lịch hẹn này?');">
                                <input type="hidden" name="appointment_id"
                                    value="<?php echo (int)$appointment['id']; ?>">
                                <button class="btn btn-outline-danger w-100" name="status" value="rejected"><i
                                        class="fas fa-times me-1"></i> Từ chối</button>
                            </form>
                            <?php elseif ($appointment['status'] === 'accepted'): ?>
                            <div class="text-muted small mb-2 d-lg-block d-none"><i class="fas fa-info-circle"></i> Vui
                                lòng xác nhận sau khi khách đã đến xem phòng.</div>
                            <form method="POST"
                                onsubmit="return confirmAction(this, 'Xác nhận khách đã đến xem phòng xong?');">
                                <input type="hidden" name="appointment_id"
                                    value="<?php echo (int)$appointment['id']; ?>">
                                <button class="btn btn-primary w-100" name="status" value="completed"><i
                                        class="fas fa-flag-checkered me-1"></i> Khách đã xem xong</button>
                            </form>
                            <?php elseif ($appointment['status'] === 'completed'): ?>
                            <div class="text-success"><i class="fas fa-check-circle fs-4"></i><br>Hoàn tất</div>
                            <?php elseif ($appointment['status'] === 'rejected'): ?>
                            <div class="text-danger"><i class="fas fa-ban fs-4"></i><br>Đã từ chối</div>
                            <?php endif; ?>
                        </div>
=======
                    <?php foreach ($appointments as $appointment): ?>
                        <article class="appointment-card">
                            <div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <div class="appointment-title"><?php echo htmlspecialchars($appointment['motel_title']); ?></div>
                                    <span class="badge text-bg-<?php echo appointment_badge($appointment['status']); ?>"><?php echo appointment_label($appointment['status']); ?></span>
                                </div>
                                <div class="meta">
                                    <span><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($appointment['preferred_time'])); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($appointment['user_name']); ?></span>
                                    <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($appointment['email']); ?></span>
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone'] ?: 'Chưa có SĐT'); ?></span>
                                    <span><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($appointment['address']); ?></span>
                                </div>
                                <?php if (!empty($appointment['note'])): ?>
                                    <div class="note"><?php echo nl2br(htmlspecialchars($appointment['note'])); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end align-content-start">
                                <?php if ($appointment['status'] === 'pending'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <button class="btn btn-success btn-sm" name="status" value="accepted">Chấp nhận</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm" name="status" value="rejected">Từ chối</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($appointment['status'] === 'accepted'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <button class="btn btn-primary btn-sm" name="status" value="completed">Đã xem</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card empty-state">
                        <h4 class="fw-bold">Chưa có lịch xem nào</h4>
                        <p>Khi người thuê đặt lịch xem phòng, danh sách sẽ hiển thị tại đây.</p>
>>>>>>> 92a21b256ef57b3d3c0eac465598c9a102eac9f4
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="wb-card text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3 opacity-50"></i>
                    <h4 class="text-dark">Không có lịch xem nào</h4>
                    <p class="text-muted">Danh sách lịch hẹn trống hoặc không có trạng thái nào phù hợp với bộ lọc hiện
                        tại.</p>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Xử lý chống click đúp
    function disableButton(form) {
        const btn = form.querySelector('button[type="submit"], button[name="status"]');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...';
            btn.classList.add('disabled');
            // Tránh khóa nút lập tức làm mất value của nút (PHP cần value này)
            setTimeout(() => btn.disabled = true, 50);
        }
    }

    // Hiện cảnh báo trước khi Từ chối / Hoàn tất
    function confirmAction(form, message) {
        if (confirm(message)) {
            disableButton(form);
            return true;
        }
        return false;
    }
    </script>
</body>

</html>