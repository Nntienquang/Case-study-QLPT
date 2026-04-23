<?php
$currentPage = 'users';
?>

<div class="d-flex justify-content-between align-items-center mb-5 pt-2">
    <div>
        <h2 style="margin: 0; font-size: 2rem;">👥 Quản Lý Người Dùng</h2>
        <p style="color: #9CA3AF; margin: 5px 0 0 0;">Danh sách tất cả người dùng trong hệ thống</p>
    </div>
    <a href="?controller=users&action=create" class="btn btn-primary" style="font-weight: 600;">
        <i class="bi bi-plus-circle"></i> Thêm Người Dùng
    </a>
</div>

<?php if (!empty($users)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-people-fill"></i> Danh Sách Người Dùng
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Tên</th>
                        <th width="25%">Email</th>
                        <th width="15%">Điện Thoại</th>
                        <th width="10%">Vai Trò</th>
                        <th width="15%">Ngày Tạo</th>
                        <th width="10%">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><strong>#<?= $user['id'] ?></strong></td>
                        <td><?= $user['name'] ?></td>
                        <td><?= $user['email'] ?></td>
                        <td><?= $user['phone'] ?? 'N/A' ?></td>
                        <td>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'owner' ? 'warning' : 'info') ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td><?= formatDate($user['created_at']) ?></td>
                        <td>
                            <a href="?controller=users&action=edit&id=<?= $user['id'] ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Xóa người dùng này?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['pages'] > 1): ?>
        <nav class="mt-4">
            <ul class="pagination">
                <?php if ($pagination['page'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?controller=users&action=index&page=1">« Đầu</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=users&action=index&page=<?= $pagination['page'] - 1 ?>">‹ Trước</a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?controller=users&action=index&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?controller=users&action=index&page=<?= $pagination['page'] + 1 ?>">Sau ›</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=users&action=index&page=<?= $pagination['pages'] ?>">Cuối »</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Không có người dùng nào. <a href="?controller=users&action=create">Tạo mới</a></div>
<?php endif; ?>
