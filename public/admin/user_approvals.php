<?php
/**
 * Duyệt Tài Khoản Chủ Trọ - Trang Quản Lý
 * 
 * Admin duyệt hoặc từ chối tài khoản mới của Owner
 */

require_once __DIR__ . '/../admin_init.php';

// Kiểm tra đăng nhập
if (!$is_logged_in || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Initialize
$activityLog = new ActivityLog($db);
$emailNotification = new EmailNotification($db);
$userApprovalController = new UserApprovalController($db, $activityLog, $emailNotification);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'approve') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
                if ($user && $user['role'] === 'owner') {
                    $admin_id = $_SESSION['user_id'];
                    if ($db->query("UPDATE users SET status = 'approved', approved_by = {$admin_id}, approved_at = NOW() WHERE id = {$id}")) {
                        $activityLog->log(
                            $admin_id,
                            'approve_user',
                            'user',
                            $id,
                            [],
                            "Duyệt tài khoản Owner: {$user['name']} ({$user['email']})"
                        );
                        $_SESSION['success'] = "Đã duyệt tài khoản {$user['name']}";
                    }
                }
            }
        } elseif ($_POST['action'] === 'reject') {
            $id = (int)($_POST['id'] ?? 0);
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            if ($id > 0 && !empty($rejection_reason)) {
                $user = $db->getRow("SELECT * FROM users WHERE id = {$id}");
                if ($user && $user['role'] === 'owner') {
                    $admin_id = $_SESSION['user_id'];
                    $conn = $db->getConnection();
                    $reason_esc = $conn->real_escape_string($rejection_reason);
                    if ($db->query("UPDATE users SET status = 'rejected', approved_by = {$admin_id}, approved_at = NOW(), rejection_reason = '{$reason_esc}' WHERE id = {$id}")) {
                        $activityLog->log(
                            $admin_id,
                            'reject_user',
                            'user',
                            $id,
                            [],
                            "Từ chối tài khoản Owner: {$user['name']}. Lý do: {$rejection_reason}"
                        );
                        $_SESSION['success'] = "Đã từ chối tài khoản {$user['name']}";
                    }
                }
            }
        }
        header('Location: user_approvals.php');
        exit;
    }
}

// Get data
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pending'; // pending, approved, rejected, all
$search = isset($_GET['search']) ? $_GET['search'] : '';

$limit = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$conn = $db->getConnection();

// Build query
$query = "SELECT * FROM users WHERE role = 'owner'";

if ($tab === 'pending') {
    $query .= " AND status = 'pending'";
} elseif ($tab === 'approved') {
    $query .= " AND status = 'approved'";
} elseif ($tab === 'rejected') {
    $query .= " AND status = 'rejected'";
}

if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $query .= " AND (name LIKE '%{$search_esc}%' OR email LIKE '%{$search_esc}%' OR phone LIKE '%{$search_esc}%')";
}

// Count total
$count_query = "SELECT COUNT(*) as total FROM users WHERE role = 'owner'" . 
    (($tab === 'pending') ? " AND status = 'pending'" : '') .
    (($tab === 'approved') ? " AND status = 'approved'" : '') .
    (($tab === 'rejected') ? " AND status = 'rejected'" : '') .
    ((!empty($search)) ? " AND (name LIKE '%{$search_esc}%' OR email LIKE '%{$search_esc}%' OR phone LIKE '%{$search_esc}%')" : '');
    
$count_result = $db->getRow($count_query);
$total = $count_result['total'] ?? 0;
$total_pages = ceil($total / $limit);

// Get users
$query .= " ORDER BY created_at DESC LIMIT {$offset}, {$limit}";
$users = $db->getRows($query);

// Get stats
$stats = [
    'pending' => $db->count('users', "role = 'owner' AND status = 'pending'"),
    'approved' => $db->count('users', "role = 'owner' AND status = 'approved'"),
    'rejected' => $db->count('users', "role = 'owner' AND status = 'rejected'"),
    'total' => $db->count('users', "role = 'owner'")
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt Tài Khoản Chủ Trọ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php require_once __DIR__ . '/sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content-area">
                <!-- Header -->
                <div class="row align-items-center border-bottom py-3 mb-4">
                    <div class="col">
                        <h1 class="h3">
                            <i class="fas fa-user-check"></i> Duyệt Tài Khoản Chủ Trọ
                        </h1>
                    </div>
                </div>

                <!-- Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Tổng Owner</small>
                                        <h3 class="mb-0"><?php echo $stats['total']; ?></h3>
                                    </div>
                                    <i class="fas fa-users text-primary" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Chờ Duyệt</small>
                                        <h3 class="mb-0 text-warning"><?php echo $stats['pending']; ?></h3>
                                    </div>
                                    <i class="fas fa-hourglass-half text-warning" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Đã Duyệt</small>
                                        <h3 class="mb-0 text-success"><?php echo $stats['approved']; ?></h3>
                                    </div>
                                    <i class="fas fa-check-circle text-success" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <small class="text-muted">Bị Từ Chối</small>
                                        <h3 class="mb-0 text-danger"><?php echo $stats['rejected']; ?></h3>
                                    </div>
                                    <i class="fas fa-times-circle text-danger" style="font-size: 2rem; opacity: 0.2;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'pending' ? 'active' : ''; ?>" href="?tab=pending">
                            <i class="fas fa-hourglass-half"></i> Chờ Duyệt (<?php echo $stats['pending']; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'approved' ? 'active' : ''; ?>" href="?tab=approved">
                            <i class="fas fa-check"></i> Đã Duyệt (<?php echo $stats['approved']; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'rejected' ? 'active' : ''; ?>" href="?tab=rejected">
                            <i class="fas fa-times"></i> Từ Chối (<?php echo $stats['rejected']; ?>)
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link <?php echo $tab === 'all' ? 'active' : ''; ?>" href="?tab=all">
                            <i class="fas fa-list"></i> Tất Cả
                        </a>
                    </li>
                </ul>

                <!-- Search -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                            <div class="col-md-8">
                                <label for="search" class="form-label">Tìm Kiếm (Tên, Email, Điện Thoại)</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Nhập thông tin..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Tìm Kiếm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Tên</th>
                                    <th>Email</th>
                                    <th>Điện Thoại</th>
                                    <th>Ngày Đăng Ký</th>
                                    <th>Trạng Thái</th>
                                    <th>Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="text-muted mt-2">Không có dữ liệu</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td>
                                                <div class="font-weight-bold"><?php echo $user['name']; ?></div>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo $user['phone'] ?? '-'; ?></td>
                                            <td>
                                                <small><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                    $status_classes = [
                                                        'pending' => 'warning',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'blocked' => 'danger'
                                                    ];
                                                    $status_labels = [
                                                        'pending' => 'Chờ Duyệt',
                                                        'approved' => 'Đã Duyệt',
                                                        'rejected' => 'Từ Chối',
                                                        'blocked' => 'Bị Khóa'
                                                    ];
                                                    $class = $status_classes[$user['status']] ?? 'secondary';
                                                    $label = $status_labels[$user['status']] ?? $user['status'];
                                                ?>
                                                <span class="badge bg-<?php echo $class; ?>"><?php echo $label; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary" title="Xem Chi Tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($user['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-outline-success" onclick="approveUser(<?php echo $user['id']; ?>)" title="Duyệt">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-danger" onclick="openRejectModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['name']); ?>')" title="Từ Chối">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=1&tab=<?php echo $tab; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Đầu</a>
                            </li>
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&tab=<?php echo $tab; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $total_pages; ?>&tab=<?php echo $tab; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Cuối</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Approve Form -->
    <form id="approveForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="approve">
        <input type="hidden" name="id" id="approveId">
    </form>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Từ Chối Tài Khoản</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">Bạn sắp từ chối tài khoản của <strong id="rejectUserName"></strong></p>
                        
                        <input type="hidden" name="action" value="reject">
                        <input type="hidden" name="id" id="rejectId">
                        
                        <div class="mb-0">
                            <label for="rejection_reason" class="form-label">Lý Do Từ Chối</label>
                            <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="4" placeholder="Nhập lý do từ chối..." required></textarea>
                            <small class="text-muted">Ví dụ: Số điện thoại không liên lạc được, Thông tin không hợp lệ, ...</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Từ Chối</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'), {});
        const approveForm = document.getElementById('approveForm');
        
        function approveUser(userId) {
            if (confirm('Bạn chắc chắn muốn duyệt tài khoản này?')) {
                document.getElementById('approveId').value = userId;
                approveForm.submit();
            }
        }
        
        function openRejectModal(userId, userName) {
            document.getElementById('rejectUserName').textContent = userName;
            document.getElementById('rejectId').value = userId;
            document.getElementById('rejection_reason').value = '';
            rejectModal.show();
        }
    </script>
</body>
</html>
