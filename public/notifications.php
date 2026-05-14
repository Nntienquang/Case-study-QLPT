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
$userName = $_SESSION['name'] ?? 'Người dùng';

// Xác định link quay về trang chủ dashboard dựa theo vai trò
$home = $role === 'owner' ? 'owner/dashboard.php' : ($role === 'admin' ? 'admin/index.php' : 'user/dashboard.php');

// Xử lý đánh dấu thông báo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $stmt = $db->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['message'] = 'Đã đánh dấu tất cả thông báo là đã đọc.';
        header("Location: notifications.php");
        exit;
    }

    if (isset($_POST['mark_read'], $_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        $stmt = $db->prepare('UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $notification_id, $user_id);
        $stmt->execute();
        $stmt->close();
        
        // Nếu có link đính kèm trong thông báo, nhấn "Đã đọc" xong thì chuyển hướng luôn
        if (!empty($_POST['redirect_link'])) {
            header("Location: " . $_POST['redirect_link']);
        } else {
            header("Location: notifications.php");
        }
        exit;
    }
}

// Lấy danh sách 50 thông báo gần nhất
$stmt = $db->prepare('
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY (read_at IS NULL) DESC, created_at DESC 
    LIMIT 50
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Đếm thông báo chưa đọc
$stmt = $db->prepare('SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND read_at IS NULL');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$unread_count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

// Hàm hiển thị icon theo loại thông báo
function get_notify_icon($type) {
    switch ($type) {
        case 'booking_status': return '<i class="fas fa-calendar-check text-primary"></i>';
        case 'viewing_status': return '<i class="fas fa-eye text-info"></i>';
        case 'payment': return '<i class="fas fa-money-bill-wave text-success"></i>';
        case 'system': return '<i class="fas fa-bullhorn text-warning"></i>';
        default: return '<i class="fas fa-bell text-secondary"></i>';
    }
}
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
    <link href="assets/css/workbench.css" rel="stylesheet">
    <style>
    .notify-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 40px 15px;
    }

    .notify-card {
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 12px;
        border: 1px solid #e9ecef;
        transition: 0.2s;
        background: white;
        display: flex;
        gap: 15px;
        position: relative;
    }

    .notify-card.unread {
        background: #f0f7ff;
        border-left: 4px solid #0d6efd;
    }

    .notify-card:hover {
        border-color: #dee2e6;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .notify-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .notify-content {
        flex-grow: 1;
    }

    .notify-title {
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 4px;
    }

    .notify-body {
        color: #4a5568;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .notify-time {
        font-size: 0.8rem;
        color: #a0aec0;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .unread-dot {
        width: 10px;
        height: 10px;
        background: #0d6efd;
        border-radius: 50%;
        position: absolute;
        right: 20px;
        top: 20px;
    }
    </style>
</head>

<body class="workbench">
    <header class="wb-topbar">
        <div class="container-lg wb-topbar-inner">
            <a class="wb-brand" href="index.php">
                <span class="wb-brand-mark"><i class="fas fa-house-chimney"></i></span>
                <span>QuanLyPhongTro</span>
            </a>
            <div class="wb-user">
                <span class="me-3 d-none d-sm-inline">Chào,
                    <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                <a class="btn btn-primary btn-sm px-3" href="<?php echo htmlspecialchars($home); ?>">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="notify-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Thông báo</h2>
                <p class="text-muted mb-0">Bạn có <?php echo $unread_count; ?> thông báo mới chưa đọc.</p>
            </div>
            <?php if ($unread_count > 0): ?>
            <form method="POST">
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3" name="mark_all_read" type="submit">
                    <i class="fas fa-check-double me-1"></i> Đọc tất cả
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if ($notifications): ?>
        <?php foreach ($notifications as $notification): ?>
        <?php $isUnread = empty($notification['read_at']); ?>
        <div class="notify-card <?php echo $isUnread ? 'unread' : ''; ?>">
            <div class="notify-icon">
                <?php echo get_notify_icon($notification['type']); ?>
            </div>
            <div class="notify-content">
                <div class="notify-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                <div class="notify-body"><?php echo nl2br(htmlspecialchars($notification['body'])); ?></div>
                <div class="notify-time">
                    <i class="far fa-clock"></i>
                    <?php echo date('H:i - d/m/Y', strtotime($notification['created_at'])); ?>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <?php if (!empty($notification['link'])): ?>
                    <a class="btn btn-sm btn-primary px-3"
                        href="<?php echo htmlspecialchars($notification['link']); ?>">
                        Xem chi tiết
                    </a>
                    <?php endif; ?>

                    <?php if ($isUnread): ?>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="notification_id" value="<?php echo (int)$notification['id']; ?>">
                        <input type="hidden" name="redirect_link"
                            value="<?php echo htmlspecialchars($notification['link']); ?>">
                        <button class="btn btn-sm btn-light border px-3" name="mark_read" type="submit">Đã đọc</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($isUnread): ?><div class="unread-dot"></div><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="wb-card text-center py-5">
            <div class="mb-3 opacity-25">
                <i class="fas fa-bell-slash fa-4x"></i>
            </div>
            <h5 class="text-dark">Chưa có thông báo nào</h5>
            <p class="text-muted">Các cập nhật quan trọng về phòng trọ sẽ hiện tại đây.</p>
            <a href="index.php" class="btn btn-link text-decoration-none">Quay về trang chủ</a>
        </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>