<?php

class OwnerModeration
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function warningTableExists(): bool
    {
        $conn = $this->db->getConnection();
        $table = 'owner_warnings';
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

    public function riskLevel(int $score): string
    {
        if ($score >= 25) {
            return 'critical';
        }
        if ($score >= 14) {
            return 'high';
        }
        if ($score >= 6) {
            return 'medium';
        }
        return 'low';
    }

    public function getOwnerRisk(int $ownerId): ?array
    {
        $stmt = $this->db->getConnection()->prepare($this->ownerRiskSql() . ' WHERE u.id = ? AND u.role = \'owner\' GROUP BY u.id, u.name, u.email, u.status LIMIT 1');
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
        $risk = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
        return $this->withRiskLevel($risk);
    }

    public function getOwnersToWatch(int $limit = 8): array
    {
        $limit = max(1, min(25, $limit));
        $sql = 'SELECT owner_risk.*
                FROM (' . $this->ownerRiskSql() . ' WHERE u.role = \'owner\' GROUP BY u.id, u.name, u.email, u.status) owner_risk
                WHERE owner_risk.total_reports > 0
                   OR owner_risk.rejected_rooms > 0
                   OR owner_risk.hidden_rooms > 0
                   OR owner_risk.cancelled_bookings > 0
                ORDER BY risk_score DESC, owner_risk.total_reports DESC, owner_risk.id DESC
                LIMIT ' . $limit;
        $stmt = $this->db->getConnection()->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $owners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return array_map(fn(array $owner): array => $this->withRiskLevel($owner), $owners);
    }

    public function getWarnings(int $ownerId, int $limit = 10): array
    {
        if (!$this->warningTableExists()) {
            return [];
        }

        $limit = max(1, min(30, $limit));
        $stmt = $this->db->getConnection()->prepare(
            "SELECT w.*, a.name AS admin_name, a.email AS admin_email
             FROM owner_warnings w
             LEFT JOIN users a ON a.id = w.admin_id
             WHERE w.owner_id = ?
             ORDER BY w.created_at DESC, w.id DESC
             LIMIT {$limit}"
        );
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $ownerId);
        $stmt->execute();
        $warnings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $warnings;
    }

    public function createWarning(int $ownerId, int $adminId, string $level, string $reason, string $note): bool
    {
        if (!$this->warningTableExists() || !$this->validWarningLevel($level) || $reason === '') {
            return false;
        }

        $stmt = $this->db->getConnection()->prepare(
            'INSERT INTO owner_warnings (owner_id, admin_id, warning_level, reason, note, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('iisss', $ownerId, $adminId, $level, $reason, $note);
        $created = $stmt->execute();
        $stmt->close();
        return $created;
    }

    public function validWarningLevel(string $level): bool
    {
        return in_array($level, ['reminder', 'warning', 'severe_warning', 'posting_suspended'], true);
    }

    private function ownerRiskSql(): string
    {
        return "SELECT u.id, u.name, u.email, u.status,
                       COUNT(DISTINCT m.id) AS total_rooms,
                       COUNT(DISTINCT CASE WHEN m.status = 'approved' THEN m.id END) AS approved_rooms,
                       COUNT(DISTINCT CASE WHEN m.status = 'rejected' THEN m.id END) AS rejected_rooms,
                       COUNT(DISTINCT CASE WHEN m.status = 'hidden' THEN m.id END) AS hidden_rooms,
                       (
                           SELECT COUNT(DISTINCT r.id)
                           FROM reports r
                           LEFT JOIN motels reported_motel ON reported_motel.id = r.motel_id
                           WHERE r.reported_user_id = u.id OR reported_motel.user_id = u.id
                       ) AS total_reports,
                       (
                           SELECT COUNT(*)
                           FROM bookings cancelled_booking
                           WHERE cancelled_booking.owner_id = u.id
                             AND cancelled_booking.status = 'cancelled'
                       ) AS cancelled_bookings,
                       (
                           (
                               SELECT COUNT(DISTINCT r.id)
                               FROM reports r
                               LEFT JOIN motels reported_motel ON reported_motel.id = r.motel_id
                               WHERE r.reported_user_id = u.id OR reported_motel.user_id = u.id
                           ) * 4
                           + COUNT(DISTINCT CASE WHEN m.status = 'rejected' THEN m.id END) * 3
                           + COUNT(DISTINCT CASE WHEN m.status = 'hidden' THEN m.id END) * 2
                           + (
                               SELECT COUNT(*)
                               FROM bookings cancelled_booking
                               WHERE cancelled_booking.owner_id = u.id
                                 AND cancelled_booking.status = 'cancelled'
                           )
                       ) AS risk_score
                FROM users u
                LEFT JOIN motels m ON m.user_id = u.id";
    }

    private function withRiskLevel(?array $risk): ?array
    {
        if (!$risk) {
            return null;
        }

        $risk['risk_score'] = (int)($risk['risk_score'] ?? 0);
        $risk['risk_level'] = $this->riskLevel($risk['risk_score']);
        return $risk;
    }
}
