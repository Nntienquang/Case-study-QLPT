<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'owner') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$owner_id = (int)$_SESSION['user_id'];
$message = '';
$message_type = 'success';
$allowed_statuses = ['accepted', 'rejected', 'completed'];

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
                $notify = $db->prepare("INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, 'viewing_status', ?, ?, ?)");
                if ($notify) {
                    $userId = (int)$appointment['user_id'];
                    $notify->bind_param('isss', $userId, $title, $body, $link);
                    $notify->execute();
                    $notify->close();
                }
            }
            $stmt->close();
        } else {
            $message = 'Không tìm thấy lịch xem của bạn.';
            $message_type = 'danger';
        }
    }
}

$status_filter = $_GET['status'] ?? '';
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
        'completed' => 'Đã xem',
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
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch xem phòng - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
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
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Đăng xuất</a>
            </div>
        </div>
    </nav>

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
                    </form>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <?php if ($appointments): ?>
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
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
