<?php

/**
 * Gửi thông báo trong hệ thống (bảng notifications).
 */
function qlpt_send_notification(Database $db, int $userId, string $type, string $title, string $body, ?string $link = null): void
{
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, title, body, link) VALUES (?, ?, ?, ?, ?)');
    if (!$stmt) {
        return;
    }
    $linkVal = $link ?? '';
    $stmt->bind_param('issss', $userId, $type, $title, $body, $linkVal);
    $stmt->execute();
    $stmt->close();
}
