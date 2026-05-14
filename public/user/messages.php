<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';
@require_once '../../core/NotificationHelper.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    header('Location: ../login.php');
    exit;
}

$db = new Database($conn);
$userId = (int)$_SESSION['user_id'];
$convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'], $_POST['conversation_id'], $_POST['reply_body'])) {
    $cid = (int)$_POST['conversation_id'];
    $body = trim((string)$_POST['reply_body']);
    $stmt = $db->prepare('SELECT id, owner_id FROM conversations WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $cid, $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || mb_strlen($body) < 1) {
        $flash = 'Không gửi được tin nhắn.';
        $flashType = 'danger';
    } else {
        $stmtIns = $db->prepare('INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)');
        $stmtIns->bind_param('iis', $cid, $userId, $body);
        if ($stmtIns->execute()) {
            $stmtIns->close();
            $stmtUp = $db->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = ?');
            $stmtUp->bind_param('i', $cid);
            $stmtUp->execute();
            $stmtUp->close();
            $oid = (int)$row['owner_id'];
            qlpt_send_notification(
                $db,
                $oid,
                'tenant_message',
                'Tin nhắn mới từ người thuê',
                'Có phản hồi trong cuộc trò chuyện về phòng.',
                'owner/messages.php?conversation_id=' . $cid
            );
            $flash = 'Đã gửi.';
        } else {
            $stmtIns->close();
        }
    }
    header('Location: messages.php?conversation_id=' . $cid);
    exit;
}

$stmt = $db->prepare('
    SELECT c.id, c.last_message_at, m.title AS motel_title, u.name AS owner_name
    FROM conversations c
    JOIN motels m ON c.motel_id = m.id
    JOIN users u ON c.owner_id = u.id
    WHERE c.user_id = ?
    ORDER BY c.last_message_at DESC
');
$stmt->bind_param('i', $userId);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$messages = [];
$activeConv = null;
if ($convId > 0) {
    $stmt = $db->prepare('
        SELECT c.*, m.title AS motel_title, u.name AS owner_name
        FROM conversations c
        JOIN motels m ON c.motel_id = m.id
        JOIN users u ON c.owner_id = u.id
        WHERE c.id = ? AND c.user_id = ?
    ');
    $stmt->bind_param('ii', $convId, $userId);
    $stmt->execute();
    $activeConv = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($activeConv) {
        $stmt = $db->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY id ASC');
        $stmt->bind_param('i', $convId);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin nhắn - QuanLyPhongTro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/modern.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea, #764ba2); }
        .navbar-brand { color: #fff !important; font-weight: 700; }
        .bubble { max-width: 85%; padding: 10px 14px; border-radius: 14px; margin-bottom: 8px; }
        .bubble.me { background: #2563eb; color: #fff; margin-left: auto; }
        .bubble.them { background: #e5e7eb; color: #111; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark sticky-top">
        <div class="container-lg">
            <a class="navbar-brand" href="../index.php"><i class="fas fa-home"></i> QuanLyPhongTro</a>
        </div>
    </nav>
    <div class="container-lg py-4">
        <div class="row">
            <div class="col-lg-3 mb-3">
                <?php $userNavActive = 'messages'; require __DIR__ . '/_nav_sidebar.php'; ?>
            </div>
            <div class="col-lg-4 mb-3">
                <h5 class="fw-bold mb-3">Cuộc trò chuyện</h5>
                <?php if ($conversations): ?>
                    <?php foreach ($conversations as $c): ?>
                        <a href="messages.php?conversation_id=<?php echo (int)$c['id']; ?>" class="d-block p-2 mb-2 rounded text-decoration-none <?php echo $convId === (int)$c['id'] ? 'bg-primary text-white' : 'bg-white border'; ?>">
                            <div class="fw-bold small"><?php echo htmlspecialchars($c['motel_title'] ?? ''); ?></div>
                            <div class="small opacity-75"><?php echo htmlspecialchars($c['owner_name'] ?? ''); ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small">Chưa có tin nhắn. Hãy mở trang phòng và dùng &quot;Nhắn tin cho chủ trọ&quot;.</p>
                <?php endif; ?>
            </div>
            <div class="col-lg-5">
                <?php if ($activeConv): ?>
                    <h5 class="fw-bold"><?php echo htmlspecialchars($activeConv['motel_title'] ?? ''); ?></h5>
                    <p class="text-muted small mb-3">Chủ trọ: <?php echo htmlspecialchars($activeConv['owner_name'] ?? ''); ?></p>
                    <div class="d-flex flex-column mb-3">
                        <?php foreach ($messages as $msg): ?>
                            <?php $isMe = (int)$msg['sender_id'] === $userId; ?>
                            <div class="d-flex <?php echo $isMe ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="bubble <?php echo $isMe ? 'me' : 'them'; ?>">
                                    <?php echo nl2br(htmlspecialchars($msg['body'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="conversation_id" value="<?php echo (int)$convId; ?>">
                        <textarea name="reply_body" class="form-control mb-2" rows="2" required placeholder="Nhập tin nhắn..."></textarea>
                        <button type="submit" name="send_reply" value="1" class="btn btn-primary">Gửi</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">Chọn một cuộc trò chuyện bên trái.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
