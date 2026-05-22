<?php

header('Content-Type: application/json; charset=UTF-8');

function admin_api_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function admin_api_error(int $status, string $message): void
{
    admin_api_json(['error' => $message], $status);
}

function admin_api_require_admin(): void
{
    if (empty($GLOBALS['is_logged_in'])) {
        admin_api_error(401, 'Vui lòng đăng nhập admin.');
    }

    $role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
    if ($role !== 'admin') {
        admin_api_error(403, 'Bạn không có quyền truy cập API admin.');
    }
}

function admin_api_scalar(mysqli $conn, string $sql): int
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    return (int)($row['value'] ?? 0);
}

function admin_api_table_exists(mysqli $conn, string $table): bool
{
    $stmt = $conn->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $exists = (bool)$stmt->get_result()->fetch_row();
    $stmt->close();
    return $exists;
}

admin_api_require_admin();
