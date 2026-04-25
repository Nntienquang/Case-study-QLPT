<?php
require_once __DIR__ . '/../admin_init.php';

if (!$is_logged_in) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

$activityLog = new ActivityLog($db);
$controller = new CategoryController($db, $activityLog);
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $controller->createCategory();
    } elseif ($action === 'edit' && isset($_GET['id'])) {
        $controller->updateCategory();
    }
}

if ($action === 'delete' && isset($_GET['id'])) {
    $controller->deleteCategory();
}

$data = $controller->listCategories();
$edit_category = null;

if ($action === 'edit' && isset($_GET['id'])) {
    $edit_category = $data['categories'][$_GET['id']] ?? null;
    if (!$edit_category) {
        $edit_category = null;
    } else {
        // Find the category with the matching ID
        foreach ($data['categories'] as $cat) {
            if ($cat['id'] == $_GET['id']) {
                $edit_category = $cat;
                break;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Danh Mục - Admin</title>
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
            <li><a href="<?php echo ADMIN_URL; ?>motels.php"><i class="fa fa-home"></i> Phòng Trọ</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>users.php"><i class="fa fa-users"></i> Người Dùng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>bookings.php"><i class="fa fa-calendar"></i> Đơn Đặt Phòng</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>payments.php"><i class="fa fa-credit-card"></i> Thanh Toán</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>admin_revenue.php"><i class="fa fa-money"></i> Doanh Thu Admin</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>reviews.php"><i class="fa fa-star"></i> Đánh Giá</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>categories.php" class="active"><i class="fa fa-list"></i> Danh Mục</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>districts.php"><i class="fa fa-map"></i> Quận</a></li>
            <li><a href="<?php echo ADMIN_URL; ?>utilities.php"><i class="fa fa-wrench"></i> Tiện Nghi</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="topbar">
            <h1>Quản Lý Danh Mục</h1>
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
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><?php echo $action === 'edit' ? 'Cập Nhật Danh Mục' : 'Thêm Danh Mục Mới'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Tên Danh Mục:</label>
                                <input type="text" name="name" class="form-control" placeholder="Nhập tên danh mục" value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fa fa-save"></i> <?php echo $action === 'edit' ? 'Cập Nhật' : 'Thêm'; ?>
                            </button>
                            <?php if ($action === 'edit'): ?>
                            <a href="<?php echo ADMIN_URL; ?>categories.php" class="btn btn-secondary" style="width: 100%; margin-left: 10px;">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Danh Sách Danh Mục (<?php echo count($data['categories']); ?> danh mục)</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($data['categories'])): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên Danh Mục</th>
                                <th>Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['categories'] as $category): ?>
                            <tr>
                                <td><?php echo $category['id']; ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td>
                                    <a href="<?php echo ADMIN_URL . 'categories.php?action=edit&id=' . $category['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fa fa-edit"></i> Sửa
                                    </a>
                                    <a href="<?php echo ADMIN_URL . 'categories.php?action=delete&id=' . $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa danh mục này?');">
                                        <i class="fa fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php else: ?>
                <div class="alert alert-info">Không có danh mục nào.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
