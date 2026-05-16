<?php

class UserController
{
    private $user;
    private $db;
    private $activityLog;

    public function __construct($db, $activityLog = null)
    {
        $this->user = new User($db);
        $this->db = $db;
        $this->activityLog = $activityLog;
    }

    public function listUsers(): array
    {
        $conn = $this->db->getConnection();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        $search = trim((string)($_GET['search'] ?? ''));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $whereParts = ['1=1'];
        if ($role !== '' && in_array($role, ['admin', 'owner', 'user'], true)) {
            $roleEsc = $conn->real_escape_string($role);
            $whereParts[] = "role = '{$roleEsc}'";
        }
        if ($status !== '' && in_array($status, ['approved', 'pending', 'blocked', 'rejected'], true)) {
            $statusEsc = $conn->real_escape_string($status);
            $whereParts[] = "status = '{$statusEsc}'";
        }
        if ($search !== '') {
            $searchEsc = $conn->real_escape_string($search);
            $whereParts[] = "(name LIKE '%{$searchEsc}%' OR email LIKE '%{$searchEsc}%' OR phone LIKE '%{$searchEsc}%')";
        }

        $where = implode(' AND ', $whereParts);
        $total = (int)($this->db->getRow("SELECT COUNT(*) AS total FROM users WHERE {$where}")['total'] ?? 0);
        $totalPages = max(1, (int)ceil($total / $limit));
        $users = $this->db->getRows("SELECT * FROM users WHERE {$where} ORDER BY created_at DESC, id DESC LIMIT {$offset}, {$limit}");

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'total_pages' => $totalPages,
            'role' => $role,
            'status' => $status,
            'search' => $search,
        ];
    }

    public function viewUser(): array
    {
        $id = (int)($_GET['id'] ?? 0);
        $user = $this->user->getById($id);

        if (!$user) {
            $_SESSION['error'] = 'Tài khoản không tồn tại';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }

        return ['user' => $user];
    }

    public function createUser(): void
    {
        $conn = $this->db->getConnection();
        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = $this->safeRole($_POST['role'] ?? 'user');
        $status = $this->safeStatus($_POST['status'] ?? 'approved');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
            $_SESSION['error'] = 'Vui lòng nhập tên, email hợp lệ và mật khẩu từ 6 ký tự.';
            return;
        }

        if ($this->emailExists($email)) {
            $_SESSION['error'] = 'Email này đã tồn tại.';
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, phone, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        if (!$stmt) {
            $_SESSION['error'] = 'Không thể tạo tài khoản.';
            return;
        }

        $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $role, $status);
        if ($stmt->execute()) {
            $newId = (int)$stmt->insert_id;
            $this->log('create_user', $newId, [], "Tạo tài khoản {$email}");
            $_SESSION['success'] = 'Đã tạo tài khoản mới.';
        } else {
            $_SESSION['error'] = 'Không thể tạo tài khoản.';
        }
        $stmt->close();
    }

    public function updateUser(int $id): void
    {
        $conn = $this->db->getConnection();
        $oldUser = $this->user->getById($id);
        $currentId = (int)($_SESSION['user_id'] ?? 0);

        if (!$oldUser) {
            $_SESSION['error'] = 'Tài khoản không tồn tại.';
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $role = $this->safeRole($_POST['role'] ?? 'user');
        $status = $this->safeStatus($_POST['status'] ?? 'approved');

        if ($id === $currentId) {
            $role = 'admin';
            $status = 'approved';
        }

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Tên và email không hợp lệ.';
            return;
        }

        if ($this->emailExists($email, $id)) {
            $_SESSION['error'] = 'Email này đã được tài khoản khác sử dụng.';
            return;
        }

        $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?');
        if (!$stmt) {
            $_SESSION['error'] = 'Không thể cập nhật tài khoản.';
            return;
        }

        $stmt->bind_param('sssssi', $name, $email, $phone, $role, $status, $id);
        if ($stmt->execute()) {
            $this->log('update_user', $id, ['old' => $oldUser, 'new' => compact('name', 'email', 'phone', 'role', 'status')], "Cập nhật tài khoản {$email}");
            $_SESSION['success'] = 'Đã cập nhật tài khoản.';
        } else {
            $_SESSION['error'] = 'Không thể cập nhật tài khoản.';
        }
        $stmt->close();
    }

    public function resetPassword(int $id, string $password): void
    {
        $conn = $this->db->getConnection();
        $target = $this->user->getById($id);

        if (!$target || strlen($password) < 6) {
            $_SESSION['error'] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL, force_password_change = 1 WHERE id = ?');
        if (!$stmt) {
            $_SESSION['error'] = 'Không thể reset mật khẩu.';
            return;
        }

        $stmt->bind_param('si', $hash, $id);
        if ($stmt->execute()) {
            $this->log('reset_user_password', $id, [], "Reset mật khẩu cho {$target['email']}");
            $_SESSION['success'] = 'Đã reset mật khẩu.';
        }
        $stmt->close();
    }

    public function updateStatus(int $id, string $status): void
    {
        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['error'] = 'Không thể khóa tài khoản đang đăng nhập.';
            return;
        }

        if (!in_array($status, ['approved', 'blocked'], true)) {
            $_SESSION['error'] = 'Trạng thái không hợp lệ.';
            return;
        }

        $user = $this->user->getById($id);
        if (!$user) {
            $_SESSION['error'] = 'Tài khoản không tồn tại.';
            return;
        }

        if ($this->db->update('users', ['status' => $status], "id = {$id}")) {
            $this->log('update_user_status', $id, ['old' => $user['status'], 'new' => $status], "Cập nhật trạng thái tài khoản {$user['email']}");
            $_SESSION['success'] = 'Đã cập nhật trạng thái tài khoản.';
        }
    }

    public function deleteUser(): void
    {
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

        if ($id === (int)($_SESSION['user_id'] ?? 0)) {
            $_SESSION['error'] = 'Không thể xóa tài khoản đang đăng nhập.';
            header('Location: ' . ADMIN_URL . 'users.php');
            exit;
        }

        $user = $this->user->getById($id);
        if ($user && $this->user->delete($id)) {
            $this->log('delete_user', $id, [], "Xóa tài khoản {$user['email']}");
            $_SESSION['success'] = 'Đã xóa tài khoản.';
        }

        header('Location: ' . ADMIN_URL . 'users.php');
        exit;
    }

    private function emailExists(string $email, int $exceptId = 0): bool
    {
        $conn = $this->db->getConnection();
        $sql = 'SELECT id FROM users WHERE email = ?';
        if ($exceptId > 0) {
            $sql .= ' AND id <> ?';
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return true;
        }

        if ($exceptId > 0) {
            $stmt->bind_param('si', $email, $exceptId);
        } else {
            $stmt->bind_param('s', $email);
        }

        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function safeRole(string $role): string
    {
        return in_array($role, ['admin', 'owner', 'user'], true) ? $role : 'user';
    }

    private function safeStatus(string $status): string
    {
        return in_array($status, ['approved', 'pending', 'blocked', 'rejected'], true) ? $status : 'approved';
    }

    private function log(string $action, int $entityId, array $changes, string $description): void
    {
        if (!$this->activityLog) {
            return;
        }

        $this->activityLog->log((int)($_SESSION['user_id'] ?? 0), $action, 'user', $entityId, $changes, $description);
    }
}
