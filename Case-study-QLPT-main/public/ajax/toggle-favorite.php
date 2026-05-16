<?php
@require_once '../config/database.php';
@require_once '../core/Database.php';

session_start();

/** @var mysqli $conn */
if (!isset($_SESSION['user_id'])) {
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

// Check if favorite exists
$stmt = $db->prepare("SELECT id FROM favorites WHERE user_id = ? AND motel_id = ?");
$stmt->bind_param("ii", $user_id, $motel_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($exists) {
    // Remove favorite
    $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND motel_id = ?");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $result = $stmt->execute();
    $stmt->close();
} else {
    // Add favorite
    $stmt = $db->prepare("INSERT INTO favorites (user_id, motel_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $motel_id);
    $result = $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => $result ? true : false]);
