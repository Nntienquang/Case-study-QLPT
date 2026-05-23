<?php
@require_once '../../config/database.php';
@require_once '../../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$motel_id = (int)($data['motel_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$motel_id) {
    echo json_encode(['success' => false]);
    exit;
}

$db = new Database($conn);

$stmt = $db->prepare("SELECT id FROM motels WHERE id = ? AND status = 'approved' LIMIT 1");
$stmt->bind_param("i", $motel_id);
$stmt->execute();
$motel = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$motel) {
    echo json_encode(['success' => false, 'message' => 'Motel not available']);
    exit;
}

// Check if favorite exists
$stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND motel_id = ?");
$stmt->bind_param("ii", $user_id, $motel_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    // Remove favorite
    $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND motel_id = ?");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $result = $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND motel_id = ?");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Add favorite
    $stmt = $db->prepare("INSERT INTO wishlists (user_id, motel_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $result = $stmt->execute();
    $stmt->close();
    $stmt = $db->prepare("INSERT IGNORE INTO favorites (user_id, motel_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => $result ? true : false]);
