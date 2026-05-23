<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$motel_id = (int)($data['motel_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);
$comment = trim((string)($data['comment'] ?? ''));
$user_id = $_SESSION['user_id'];
$reviewer_name = $_SESSION['name'] ?? 'Guest';

if (!$motel_id || $rating < 1 || $rating > 5 || mb_strlen($comment) < 5) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

$db = new Database($conn);

// Check if user already reviewed
$stmt = $db->prepare('SELECT id FROM reviews WHERE user_id = ? AND motel_id = ?');
$stmt->bind_param('ii', $user_id, $motel_id);
$stmt->execute();
$dup = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($dup) {
    echo json_encode(['success' => false, 'message' => 'Bạn đã đánh giá phòng này rồi.']);
    exit;
}

// Check eligibility
$stmt = $db->prepare("SELECT id FROM bookings WHERE user_id = ? AND motel_id = ? AND status IN ('accepted','paid','completed') LIMIT 1");
$stmt->bind_param('ii', $user_id, $motel_id);
$stmt->execute();
$eligible = (bool)$stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$eligible) {
    echo json_encode(['success' => false, 'message' => 'Chỉ có thể đánh giá sau khi đã đặt phòng thành công.']);
    exit;
}

// Insert review
$stmt = $db->prepare('INSERT INTO reviews (user_id, motel_id, rating, comment) VALUES (?, ?, ?, ?)');
$stmt->bind_param('iiis', $user_id, $motel_id, $rating, $comment);
if ($stmt->execute()) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $rating ? '★' : '☆';
    }
    
    $html = '
    <div class="border-bottom py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>' . htmlspecialchars($reviewer_name) . '</strong>
            <span class="text-warning">' . $stars . '</span>
        </div>
        <p class="mb-1 text-muted">' . nl2br(htmlspecialchars($comment)) . '</p>
        <small class="text-muted">Vừa xong</small>
    </div>';
    
    echo json_encode(['success' => true, 'html' => $html]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
}
$stmt->close();
