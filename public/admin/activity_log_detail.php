<?php
/**
 * Chi Tiết Nhật Ký Hoạt Động
 * 
 * Admin xem chi tiết một nhật ký hoạt động
 */

require_once __DIR__ . '/../admin_init.php';

// Kiểm tra đăng nhập
if (!$is_logged_in || $_SESSION['user_role'] !== 'admin') {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Get log ID
if (!isset($_GET['id'])) {
    header('Location: ' . ADMIN_URL . 'activity_logs.php');
    exit;
}

$id = (int)$_GET['id'];

// Get log details
$query = "SELECT l.*, u.id as admin_id, u.name as admin_name, u.email as admin_email, u.phone as admin_phone
          FROM activity_logs l
          LEFT JOIN users u ON l.admin_id = u.id
          WHERE l.id = {$id}";

$log = $db->getRow($query);

if (!$log) {
    $_SESSION['error'] = 'Nhật ký không tồn tại';
    header('Location: ' . ADMIN_URL . 'activity_logs.php');
    exit;
}

// Parse JSON fields
$old_value = [];
$new_value = [];

if (!empty($log['old_value'])) {
    $old_value = json_decode($log['old_value'], true) ?: [];
}

if (!empty($log['new_value'])) {
    $new_value = json_decode($log['new_value'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Nhật Ký Hoạt Động</title>
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
                        <a href="activity_logs.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                        <h1 class="h3 mt-2">
                            <i class="fas fa-info-circle"></i> Chi Tiết Nhật Ký
                        </h1>
                    </div>
                </div>

                <div class="row">
                    <!-- Main Content -->
                    <div class="col-md-8">
                        <!-- Basic Info -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">Thông Tin Cơ Bản</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">ID Nhật Ký</small>
                                        <div class="fw-bold"><?php echo $log['id']; ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">Hành Động</small>
                                        <div>
                                            <span class="badge bg-info"><?php echo str_replace('_', ' ', ucfirst($log['action'])); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <small class="text-muted">Loại Thực Thể</small>
                                        <div class="fw-bold"><?php echo ucfirst($log['entity_type']); ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">ID Thực Thể</small>
                                        <div class="fw-bold"><?php echo $log['entity_id']; ?></div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <small class="text-muted">Mô Tả</small>
                                        <div><?php echo nl2br(htmlspecialchars($log['description'] ?? '')); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Info -->
                        <?php if ($log['admin_name']): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Thông Tin Admin</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">Tên</small>
                                            <div class="fw-bold"><?php echo $log['admin_name']; ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Email</small>
                                            <div><?php echo $log['admin_email']; ?></div>
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <small class="text-muted">Điện Thoại</small>
                                            <div><?php echo $log['admin_phone'] ?? 'N/A'; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Changes Comparison -->
                        <?php if (!empty($old_value) || !empty($new_value)): ?>
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light border-bottom">
                                    <h5 class="mb-0">Thay Đổi Chi Tiết</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Trường</th>
                                                    <th>Giá Trị Cũ</th>
                                                    <th>Giá Trị Mới</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                    // Get all fields (from both old and new)
                                                    $all_fields = array_unique(array_merge(array_keys($old_value), array_keys($new_value)));
                                                    sort($all_fields);
                                                    
                                                    foreach ($all_fields as $field):
                                                        $old_val = $old_value[$field] ?? '';
                                                        $new_val = $new_value[$field] ?? '';
                                                ?>
                                                    <tr>
                                                        <td><strong><?php echo $field; ?></strong></td>
                                                        <td>
                                                            <?php if (empty($old_val)): ?>
                                                                <span class="text-muted">-</span>
                                                            <?php else: ?>
                                                                <code><?php echo htmlspecialchars($old_val); ?></code>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (empty($new_val)): ?>
                                                                <span class="text-muted">-</span>
                                                            <?php else: ?>
                                                                <code><?php echo htmlspecialchars($new_val); ?></code>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sidebar Info -->
                    <div class="col-md-4">
                        <!-- Timestamp Info -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">Thông Tin Thời Gian</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">Thời Gian</small>
                                    <div class="fw-bold"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></div>
                                </div>
                                <div class="alert alert-info" role="alert">
                                    <small>
                                        <i class="fas fa-clock"></i>
                                        <?php 
                                            $timestamp = strtotime($log['created_at']);
                                            $now = time();
                                            $diff = $now - $timestamp;
                                            
                                            if ($diff < 60) {
                                                echo "Vừa xong";
                                            } elseif ($diff < 3600) {
                                                echo floor($diff / 60) . " phút trước";
                                            } elseif ($diff < 86400) {
                                                echo floor($diff / 3600) . " giờ trước";
                                            } else {
                                                echo floor($diff / 86400) . " ngày trước";
                                            }
                                        ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Network Info -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-bottom">
                                <h5 class="mb-0">Thông Tin Kết Nối</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <small class="text-muted">IP Address</small>
                                    <div class="fw-bold font-monospace"><?php echo $log['ip_address'] ?? 'N/A'; ?></div>
                                </div>
                                <div class="mb-0">
                                    <small class="text-muted">User Agent</small>
                                    <div style="font-size: 0.85rem; word-break: break-all;">
                                        <?php echo $log['user_agent'] ? substr($log['user_agent'], 0, 100) . '...' : 'N/A'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <?php 
                                    // Get adjacent logs
                                    $prev_query = "SELECT id FROM activity_logs WHERE created_at < '{$log['created_at']}' ORDER BY created_at DESC LIMIT 1";
                                    $prev_log = $db->getRow($prev_query);
                                    
                                    $next_query = "SELECT id FROM activity_logs WHERE created_at > '{$log['created_at']}' ORDER BY created_at ASC LIMIT 1";
                                    $next_log = $db->getRow($next_query);
                                ?>
                                <div class="d-grid gap-2">
                                    <?php if ($prev_log): ?>
                                        <a href="activity_log_detail.php?id=<?php echo $prev_log['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-chevron-up"></i> Nhật Ký Trước
                                        </a>
                                    <?php endif; ?>
                                    <a href="activity_logs.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-list"></i> Quay Lại Danh Sách
                                    </a>
                                    <?php if ($next_log): ?>
                                        <a href="activity_log_detail.php?id=<?php echo $next_log['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-chevron-down"></i> Nhật Ký Tiếp Theo
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
