<?php
require_once __DIR__ . '/../admin_init.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new MotelController($db, $activityLog);
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle actions
if ($action === 'approve' && isset($_GET['id'])) {
    $controller->approveMotel();
}
if ($action === 'hide' && isset($_GET['id'])) {
    $controller->hideMotel();
}
if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteMotel();
}

$data = $controller->listMotels();

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Phòng Trọ - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="<?php echo ADMIN_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            <h2>🏠 Admin</h2>
            <p>Quản Lý Phòng Trọ</p>
        </div>
        
        <ul class="nav-menu">
            <li><a href="<?php echo ADMIN_URL; ?>index.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>motels.php" class="active"><i class="fa fa-home"></i> Phòng Trọ</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>users.php"><i class="fa fa-users"></i> Người Dùng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>bookings.php"><i class="fa fa-calendar"></i> Đơn Đặt Phòng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>payments.php"><i class="fa fa-credit-card"></i> Thanh Toán</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>admin_revenue.php"><i class="fa fa-money"></i> Doanh Thu Admin</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>reviews.php"><i class="fa fa-star"></i> Đánh Giá</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>categories.php"><i class="fa fa-list"></i> Danh Mục</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>districts.php"><i class="fa fa-map"></i> Quận</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>utilities.php"><i class="fa fa-wrench"></i> Tiện Nghi</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1>Quản Lý Phòng Trọ</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?></span>
                <a href="<?php echo ADMIN_URL; ?>logout.php">Đăng Xuất</a>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <!-- Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Trạng Thái:</label>
                        <select name="status" class="form-select">
                            <option value="">-- Tất Cả --</option>
                            <option value="pending" <?php echo $data['status'] === 'pending' ? 'selected' : ''; ?>>Chờ Duyệt</option>
                            <option value="approved" <?php echo $data['status'] === 'approved' ? 'selected' : ''; ?>>Đã Duyệt</option>
                            <option value="hidden" <?php echo $data['status'] === 'hidden' ? 'selected' : ''; ?>>Ẩn</option>
                        </select>
                    </div>
                    <div class="col-md-6" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Lọc</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Motels List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh Sách Phòng Trọ (<?php echo $data['total']; ?> phòng)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['motels'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tiêu Đề</th>
                                <th>Chủ Phòng</th>
                                <th>Giá (VNĐ)</th>
                                <th>Diện Tích</th>
                                <th>Quận</th>
                                <th>Trạng Thái</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['motels'] as $motel): ?>
                            <tr>
                                <td><?php echo $motel['id']; ?></td>
                                <td><?php echo htmlspecialchars(substr($motel['title'], 0, 30)); ?></td>
                                <td><?php echo htmlspecialchars($motel['owner_name'] ?? 'N/A'); ?></td>
                                <td><?php echo number_format($motel['price']); ?></td>
                                <td><?php echo $motel['area'] ?? 'N/A'; ?> m²</td>
                                <td><?php echo htmlspecialchars($motel['district_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="<?php echo $motel['status'] === 'pending' ? 'pending-badge' : ($motel['status'] === 'approved' ? 'success-badge' : 'danger-badge'); ?>">
                                        <?php echo $motel['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo ADMIN_URL . 'motel_detail.php?id=' . $motel['id']; ?>" class="btn btn-sm btn-info" title="Xem">
                                        <i class="fa fa-eye"></i> Xem
                                    </a>
                                    <?php if ($motel['status'] === 'pending'): ?>
                                    <a href="<?php echo ADMIN_URL . 'motels.php?action=approve&id=' . $motel['id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Duyệt phòng này?');" title="Duyệt">
                                        <i class="fa fa-check"></i> Duyệt
                                    </a>
                                    <?php endif; ?>
                                    <?php if ($motel['status'] !== 'hidden'): ?>
                                    <a href="<?php echo ADMIN_URL . 'motels.php?action=hide&id=' . $motel['id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Ẩn phòng này?');" title="Ẩn">
                                        <i class="fa fa-times"></i> Ẩn
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?php echo ADMIN_URL . 'motels.php?action=delete&id=' . $motel['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa phòng này?');" title="Xóa">
                                        <i class="fa fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($data['total_pages'] > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($data['page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'motels.php?page=1'; ?>">Đầu tiên</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'motels.php?page=' . ($data['page'] - 1); ?>">Trước</a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $data['page'] - 2); $i <= min($data['total_pages'], $data['page'] + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $data['page'] ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'motels.php?page=' . $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($data['page'] < $data['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'motels.php?page=' . ($data['page'] + 1); ?>">Sau</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo ADMIN_URL . 'motels.php?page=' . $data['total_pages']; ?>">Cuối cùng</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                
                <?php else: ?>
                <div class="alert alert-info">Không có phòng trọ nào.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
