<?php

class OwnerStatusMiddleware
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getOwnerApprovalInfo(int $userId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT id, role, status, owner_verification_status, verification_submitted_at,
                   verification_reviewed_by, verification_reviewed_at, verification_rejection_reason,
                   rejection_reason, approved_by, approved_at, phone, address, idcard_number,
                   id_card_front, id_card_back, selfie_image, bank_name, bank_account_no, bank_account_name
            FROM users
            WHERE id = ?
            LIMIT 1
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function canUseOwnerWorkspace(int $userId): array
    {
        $owner = $this->getOwnerApprovalInfo($userId);
        if (!$owner) {
            return ['allowed' => false, 'status' => 'not_found', 'message' => 'Tài khoản không tồn tại.'];
        }

        if (($owner['role'] ?? '') !== 'owner') {
            return ['allowed' => false, 'status' => 'invalid_role', 'message' => 'Chỉ chủ phòng mới được truy cập khu vực này.'];
        }

        if (($owner['status'] ?? '') === 'blocked') {
            return ['allowed' => false, 'status' => 'blocked', 'message' => 'Tài khoản của bạn đang bị khóa.'];
        }

        $verification = (string)($owner['owner_verification_status'] ?? 'pending_verification');
        if ($verification !== 'approved') {
            return [
                'allowed' => false,
                'status' => $verification,
                'message' => $this->verificationMessage($verification, $owner),
            ];
        }

        return ['allowed' => true, 'status' => 'approved', 'message' => 'Owner đã được xác minh.'];
    }

    public function canPostMotel($userId): array
    {
        return $this->canUseOwnerWorkspace((int)$userId);
    }

    public function checkOwnerAccess(int $userId, string $redirectUrl = 'profile.php?verify=1'): array
    {
        $check = $this->canUseOwnerWorkspace($userId);
        if (!$check['allowed']) {
            $_SESSION['warning'] = $check['message'];
            header('Location: ' . $redirectUrl);
            exit;
        }

        return $check;
    }

    public static function verificationMessage(string $status, ?array $owner = []): string
    {
        return match ($status) {
            'pending_verification' => 'Bạn cần hoàn tất hồ sơ xác minh chủ phòng trước khi sử dụng khu vực owner.',
            'submitted' => 'Hồ sơ xác minh của bạn đang chờ admin duyệt.',
            'rejected' => 'Hồ sơ xác minh bị từ chối: ' . (($owner['verification_rejection_reason'] ?? $owner['rejection_reason'] ?? '') ?: 'Vui lòng cập nhật lại hồ sơ.'),
            default => 'Tài khoản owner chưa đủ điều kiện sử dụng chức năng này.',
        };
    }

    public static function verificationLabel(string $status): string
    {
        return [
            'not_required' => 'Không yêu cầu',
            'pending_verification' => 'Chưa gửi hồ sơ',
            'submitted' => 'Chờ admin duyệt',
            'approved' => 'Đã xác minh',
            'rejected' => 'Bị từ chối',
        ][$status] ?? $status;
    }
}
