<?php

class UserApprovalController
{
    private $db;
    private $user;
    private $activityLog;
    private $emailNotification;

    public function __construct($db, $activityLog = null, $emailNotification = null)
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->activityLog = $activityLog;
        $this->emailNotification = $emailNotification;
    }

    public function listPendingUsers(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $role = (string)($_GET['role'] ?? 'owner');
        $role = in_array($role, ['owner', 'admin'], true) ? $role : 'owner';
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $conn = $this->db->getConnection();

        $stmt = $conn->prepare("SELECT * FROM users WHERE role = ? AND status = 'pending' ORDER BY created_at DESC LIMIT {$offset}, {$limit}");
        $users = [];
        if ($stmt) {
            $stmt->bind_param('s', $role);
            $stmt->execute();
            $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        $total = $this->db->count('users', "role = ? AND status = 'pending'", [$role]);

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => (int)ceil($total / $limit),
            'role' => $role,
        ];
    }

    public function listAllUsers(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $status = (string)($_GET['status'] ?? '');
        $role = (string)($_GET['role'] ?? '');
        $status = in_array($status, ['approved', 'pending', 'rejected', 'blocked'], true) ? $status : '';
        $role = in_array($role, ['admin', 'owner', 'user'], true) ? $role : '';
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;
        $conn = $this->db->getConnection();

        $whereParts = ['1=1'];
        $params = [];
        $types = '';
        if ($status !== '') {
            $whereParts[] = 'status = ?';
            $params[] = $status;
            $types .= 's';
        }
        if ($role !== '') {
            $whereParts[] = 'role = ?';
            $params[] = $role;
            $types .= 's';
        }

        $where = implode(' AND ', $whereParts);
        $stmt = $conn->prepare("SELECT * FROM users WHERE {$where} ORDER BY created_at DESC LIMIT {$offset}, {$limit}");
        $users = [];
        if ($stmt) {
            $this->bindParams($stmt, $types, $params);
            $stmt->execute();
            $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        $total = $this->db->count('users', $where, $params);

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => (int)ceil($total / $limit),
            'status' => $status,
            'role' => $role,
        ];
    }

    public function viewUser(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->user->getById($id);
        if (!$user) {
            $_SESSION['error'] = 'User not found.';
            header('Location: ' . ADMIN_URL . 'user_approvals.php');
            exit;
        }

        return ['user' => $user];
    }

    public function approveUser(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->user->getById($id);
        if (!$user) {
            $this->redirectWithError('user_approvals.php', 'User not found.');
        }

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $adminId, $id);
        }

        if ($stmt && $stmt->execute()) {
            $this->log($adminId, 'approve_user', $id, "Approve {$user['role']} account: {$user['name']} ({$user['email']})");
            if ($this->emailNotification) {
                $this->emailNotification->sendOwnerApprovalNotification($id);
            }
            $_SESSION['success'] = 'Account approved.';
        } else {
            $_SESSION['error'] = 'Cannot approve account.';
        }
        if ($stmt) {
            $stmt->close();
        }

        header('Location: ' . ADMIN_URL . 'user_approvals.php');
        exit;
    }

    public function rejectUser(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $reason = trim((string)($_POST['rejection_reason'] ?? ''));
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->user->getById($id);
        if (!$user || $reason === '') {
            $this->redirectWithError('user_approvals.php', 'Rejection data is invalid.');
        }

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare("UPDATE users SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('isi', $adminId, $reason, $id);
        }

        if ($stmt && $stmt->execute()) {
            $this->log($adminId, 'reject_user', $id, "Reject {$user['role']} account: {$user['name']}. Reason: {$reason}");
            if ($this->emailNotification) {
                $this->emailNotification->sendOwnerRejectionNotification($id, $reason);
            }
            $_SESSION['success'] = 'Account rejected.';
        } else {
            $_SESSION['error'] = 'Cannot reject account.';
        }
        if ($stmt) {
            $stmt->close();
        }

        header('Location: ' . ADMIN_URL . 'user_approvals.php');
        exit;
    }

    public function blockUser(): void
    {
        $this->setBlockedStatus((int)($_GET['id'] ?? 0), 'blocked');
    }

    public function unblockUser(): void
    {
        $this->setBlockedStatus((int)($_GET['id'] ?? 0), 'approved');
    }

    public function getStats(): array
    {
        return [
            'pending_owners' => $this->db->count('users', "role = 'owner' AND status = 'pending'"),
            'pending_staff' => $this->db->count('users', "role = 'admin' AND status = 'pending'"),
            'approved' => $this->db->count('users', "status = 'approved'"),
            'rejected' => $this->db->count('users', "status = 'rejected'"),
            'blocked' => $this->db->count('users', "status = 'blocked'"),
        ];
    }

    private function setBlockedStatus(int $id, string $status): void
    {
        $adminId = (int)($_SESSION['user_id'] ?? 0);
        $user = $this->user->getById($id);
        if (!$user || ($status === 'blocked' && $id === $adminId)) {
            $this->redirectWithError('users.php', 'Account status cannot be updated.');
        }

        $conn = $this->db->getConnection();
        $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $status, $id);
        }

        if ($stmt && $stmt->execute()) {
            $action = $status === 'blocked' ? 'block_user' : 'unblock_user';
            $this->log($adminId, $action, $id, "Set account {$user['email']} to {$status}");
            $_SESSION['success'] = 'Account status updated.';
        } else {
            $_SESSION['error'] = 'Cannot update account status.';
        }
        if ($stmt) {
            $stmt->close();
        }

        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }

    private function bindParams(mysqli_stmt $stmt, string $types, array &$params): void
    {
        if ($types === '' || $params === []) {
            return;
        }

        $bindValues = [$types];
        foreach ($params as $key => $value) {
            $bindValues[] = &$params[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $bindValues);
    }

    private function log(int $adminId, string $action, int $userId, string $message): void
    {
        if ($this->activityLog) {
            $this->activityLog->log($adminId, $action, 'user', $userId, [], $message);
        }
    }

    private function redirectWithError(string $path, string $message): void
    {
        $_SESSION['error'] = $message;
        header('Location: ' . ADMIN_URL . $path);
        exit;
    }
}

