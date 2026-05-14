<?php
@require_once '../config/database.php';
@require_once '../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database($conn);
$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($role === 'owner') {
    header('Location: owner/notifications.php');
    exit;
}

if ($role === 'user') {
    header('Location: user/notifications.php');
    exit;
}

$home = 'admin/index.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $stmt = $db->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL');
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $message = 'Đã đánh dấu tất cả là đã đọc.';
        }
        $stmt->close();
    }

    if (isset($_POST['mark_read'], $_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        $stmt = $db->prepare('UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notification_id, $user_id);
        if ($stmt->execute()) {
            $message = 'Đã cập nhật thông báo.';
        }
        $stmt->close();
    }
}

$stmt = $db->prepare('
    SELECT *
    FROM notifications
    WHERE user_id = ?
    ORDER BY read_at IS NULL DESC, created_at DESC
    LIMIT 80
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $db->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND read_at IS NULL');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$unread_count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f6f8fb; }
        .app-nav { background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 8px 30px rgba(15,23,42,.06); }
        .shell { max-width: 980px; margin: 0 auto; padding: 30px 16px 48px; }
        .panel, .notification-card { background: #fff; border: 1px solid #e5eaf2; border-radius: 16px; box-shadow: 0 18px 50px rgba(15,23,42,.07); }
        .panel { padding: 24px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .notification-card { padding: 18px; margin-bottom: 12px; display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; }
        .notification-card.unread { border-left: 5px solid #2563eb; }
        .notification-title { font-weight: 900; color: #101828; }
        .notification-body { color: #667085; margin-top: 6px; }
        .notification-meta { color: #98a2b3; font-size: 13px; margin-top: 8px; }
        .empty-state { text-align: center; padding: 50px 20px; color: #667085; }
        @media (max-width: 768px) { .panel, .notification-card { grid-template-columns: 1fr; flex-direction: column; align-items: flex-start; } }
    </style>
</head>
<body>
    <nav class="navbar app-nav navbar-expand-lg sticky-top">
        <div class="container-lg">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-house-chimney"></i> QuanLyPhongTro</a>
            <div class="ms-auto d-flex gap-2">
                <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($home); ?>">Dashboard</a>
                <a class="btn btn-outline-secondary btn-sm" href="logout.php">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <main class="shell">
        <div class="panel">
            <div>
                <h1 class="fw-bold mb-2">Thông báo</h1>
                <p class="text-muted mb-0"><?php echo $unread_count; ?> thông báo chưa đọc.</p>
            </div>
            <form method="POST">
                <button class="btn btn-primary" name="mark_all_read" type="submit">Đánh dấu đã đọc</button>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($notifications): ?>
            <?php foreach ($notifications as $notification): ?>
                <?php $isUnread = empty($notification['read_at']); ?>
                <article class="notification-card <?php echo $isUnread ? 'unread' : ''; ?>">
                    <div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                            <?php if ($isUnread): ?><span class="badge text-bg-primary">Mới</span><?php endif; ?>
                        </div>
                        <?php if (!empty($notification['body'])): ?>
                            <div class="notification-body"><?php echo nl2br(htmlspecialchars($notification['body'])); ?></div>
                        <?php endif; ?>
                        <div class="notification-meta">
                            <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notification['created_at'])); ?>
                            <span class="ms-2"><?php echo htmlspecialchars($notification['type']); ?></span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end align-content-start">
                        <?php if (!empty($notification['link'])): ?>
                            <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($notification['link']); ?>">Mở</a>
                        <?php endif; ?>
                        <?php if ($isUnread): ?>
                            <form method="POST">
                                <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                                <button class="btn btn-primary btn-sm" name="mark_read" type="submit">Đã đọc</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="panel empty-state">
                <div>
                    <h4 class="fw-bold">Chưa có thông báo</h4>
                    <p>Những thay đổi quan trọng về đặt phòng, lịch xem và tin đăng sẽ xuất hiện tại đây.</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
