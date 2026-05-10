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
                $message = 'Da cap nhat lich xem phong.';
                $title = 'Lich xem phong da cap nhat';
                $body = 'Lich xem phong "' . $appointment['title'] . '" hien co trang thai: ' . $status;
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
            $message = 'Khong tim thay lich xem cua ban.';
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
        'pending' => 'Cho xac nhan',
        'accepted' => 'Da chap nhan',
        'rejected' => 'Tu choi',
        'completed' => 'Da xem',
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
    <title>Lich xem phong - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .app-shell { padding: 28px 0 44px; }
        .app-nav { background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06); }
        .layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 22px; }
        .side-panel, .content-card, .appointment-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .side-panel { padding: 18px; height: fit-content; position: sticky; top: 88px; }
        .side-link { display: flex; align-items: center; gap: 10px; padding: 11px 12px; border-radius: 12px; color: #475467; text-decoration: none; font-weight: 750; }
        .side-link:hover, .side-link.active { background: #f2f4f7; color: #101828; }
        .content-card { padding: 24px; margin-bottom: 18px; }
        .appointment-card { padding: 18px; margin-bottom: 14px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 16px; }
        .appointment-title { font-weight: 900; font-size: 18px; color: #101828; }
        .meta { color: #667085; font-size: 14px; display: flex; flex-wrap: wrap; gap: 8px 14px; margin-top: 8px; }
        .note { background: #f8fafc; border-radius: 10px; padding: 10px 12px; margin-top: 12px; color: #475467; }
        .empty-state { text-align: center; padding: 44px 20px; color: #667085; }
        @media (max-width: 991px) { .layout, .appointment-card { grid-template-columns: 1fr; } .side-panel { position: static; } }
    </style>
</head>
<body>
    <nav class="navbar app-nav navbar-expand-lg sticky-top">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="../index.php"><i class="fas fa-house-chimney"></i> QuanLyPhongTro</a>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="../notifications.php"><i class="fas fa-bell"></i> Thong bao</a>
                <a class="btn btn-outline-secondary btn-sm" href="../logout.php">Dang xuat</a>
            </div>
        </div>
    </nav>

    <main class="app-shell">
        <div class="container-lg layout">
            <aside class="side-panel">
                <div class="fw-bold mb-3">Chu phong</div>
                <a class="side-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Tong quan</a>
                <a class="side-link" href="listings.php"><i class="fas fa-list"></i> Phong cua toi</a>
                <a class="side-link" href="add-listing.php"><i class="fas fa-plus"></i> Dang phong</a>
                <a class="side-link active" href="viewing-appointments.php"><i class="fas fa-calendar-day"></i> Lich xem</a>
                <a class="side-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Booking</a>
                <a class="side-link" href="revenue.php"><i class="fas fa-chart-column"></i> Doanh thu</a>
                <a class="side-link" href="profile.php"><i class="fas fa-user"></i> Ho so</a>
            </aside>

            <section>
                <div class="content-card d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h1 class="fw-bold mb-2">Lich xem phong</h1>
                        <p class="text-muted mb-0">Xac nhan, tu choi hoac danh dau da xem cho lead moi.</p>
                    </div>
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select">
                            <option value="">Tat ca</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Cho xac nhan</option>
                            <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Da chap nhan</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Da xem</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Tu choi</option>
                        </select>
                        <button class="btn btn-primary" type="submit">Loc</button>
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
                                    <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($appointment['phone'] ?: 'Chua co SDT'); ?></span>
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
                                        <button class="btn btn-success btn-sm" name="status" value="accepted">Chap nhan</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <button class="btn btn-outline-danger btn-sm" name="status" value="rejected">Tu choi</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($appointment['status'] === 'accepted'): ?>
                                    <form method="POST">
                                        <input type="hidden" name="appointment_id" value="<?php echo (int)$appointment['id']; ?>">
                                        <button class="btn btn-primary btn-sm" name="status" value="completed">Da xem</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="content-card empty-state">
                        <h4 class="fw-bold">Chua co lich xem nao</h4>
                        <p>Khi user dat lich xem phong, danh sach se hien tai day.</p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
