<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();
header('Content-Type: application/json; charset=UTF-8');

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'login_required' => true,
        'message' => 'Vui lòng đăng nhập để lưu phòng.',
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$motel_id = (int)($data['motel_id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

if (!$motel_id) {
    echo json_encode(['success' => false, 'message' => 'Phòng không hợp lệ.']);
    exit;
}

$db = new Database($conn);

$stmt = $db->prepare("SELECT id FROM motels WHERE id = ? AND status = 'approved' LIMIT 1");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$motel) {
    echo json_encode(['success' => false, 'message' => 'Phòng không tồn tại hoặc chưa được duyệt.']);
    exit;
}

// favorites is the canonical table; wishlists is read as legacy fallback.
$stmt = $db->prepare("
    SELECT motel_id FROM favorites WHERE user_id = ? AND motel_id = ?
    UNION
    SELECT motel_id FROM wishlists WHERE user_id = ? AND motel_id = ?
    LIMIT 1
");
$stmt->bind_param("iiii", $user_id, $motel_id, $user_id, $motel_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND motel_id = ?");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $result = $stmt->execute();
    $stmt->close();

    $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND motel_id = ?");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $stmt->execute();
    $stmt->close();
    $saved = false;
} else {
    $stmt = $db->prepare("INSERT IGNORE INTO favorites (user_id, motel_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $result = $stmt->execute();
    $stmt->close();
    $saved = true;
}

$stmt = $db->prepare("
    SELECT COUNT(*) AS count
    FROM (
        SELECT motel_id FROM favorites WHERE user_id = ?
        UNION
        SELECT motel_id FROM wishlists WHERE user_id = ?
    ) saved
    JOIN motels m ON m.id = saved.motel_id
    WHERE m.status = 'approved'
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$count = (int)($stmt->get_result()->fetch_assoc()['count'] ?? 0);
$stmt->close();

echo json_encode([
    'success' => $result ? true : false,
    'saved' => $saved,
    'message' => $saved ? 'Đã lưu phòng.' : 'Đã bỏ lưu phòng.',
    'count' => $count,
]);
