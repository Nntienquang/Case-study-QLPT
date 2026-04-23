<?php
$currentPage = 'categories';
?>

<div class="d-flex justify-content-between align-items-center mb-5 pt-2">
    <div>
        <h2 style="margin: 0; font-size: 2rem;">📂 Danh Mục Phòng</h2>
        <p style="color: #9CA3AF; margin: 5px 0 0 0;">Quản lý loại phòng trọ</p>
    </div>
    <a href="?controller=categories&action=create" class="btn btn-primary" style="font-weight: 600;">
        <i class="bi bi-plus-circle"></i> Thêm Danh Mục
    </a>
</div>

<?php if (!empty($categories)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list"></i> Danh Sách Danh Mục
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="30%">Tên Danh Mục</th>
                        <th width="40%">Mô Tả</th>
                        <th width="15%">Ngày Tạo</th>
                        <th width="10%">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong>#<?= $cat['id'] ?></strong></td>
                        <td><?= $cat['name'] ?></td>
                        <td><small><?= substr($cat['description'], 0, 40) ?></small></td>
                        <td><?= formatDate($cat['created_at']) ?></td>
                        <td>
                            <a href="?controller=categories&action=edit&id=<?= $cat['id'] ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Xóa danh mục này?')">
                                    <i class="bi bi-trash"></i> Xóa
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
                    <a class="page-link" href="?controller=categories&action=index&page=1">« Đầu</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=categories&action=index&page=<?= $pagination['page'] - 1 ?>">‹ Trước</a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?controller=categories&action=index&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?controller=categories&action=index&page=<?= $pagination['page'] + 1 ?>">Sau ›</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=categories&action=index&page=<?= $pagination['pages'] ?>">Cuối »</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Không có danh mục nào. <a href="?controller=categories&action=create">Tạo mới</a></div>
<?php endif; ?>
