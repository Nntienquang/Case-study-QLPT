<?php
$currentPage = 'motels';
?>

<div class="d-flex justify-content-between align-items-center mb-5 pt-2">
    <div>
        <h2 style="margin: 0; font-size: 2rem;">🏠 Quản Lý Phòng Trọ</h2>
        <p style="color: #9CA3AF; margin: 5px 0 0 0;">Danh sách tất cả phòng trọ và duyệt phòng mới</p>
    </div>
    <a href="?controller=motels&action=create" class="btn btn-primary" style="font-weight: 600;">
        <i class="bi bi-plus-circle"></i> Thêm Phòng
    </a>
</div>

<?php if (!empty($motels)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list"></i> Danh Sách Phòng Trọ
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Tên Phòng</th>
                        <th width="15%">Chủ Phòng</th>
                        <th width="12%">Giá</th>
                        <th width="12%">Loại</th>
                        <th width="12%">Trạng Thái</th>
                        <th width="12%">Ngày Tạo</th>
                        <th width="12%">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($motels as $motel): ?>
                    <tr>
                        <td><strong>#<?= $motel['id'] ?></strong></td>
                        <td><?= substr($motel['title'], 0, 20) ?></td>
                        <td><small><?= $motel['user_name'] ?></small></td>
                        <td><?= formatCurrency($motel['price']) ?></td>
                        <td><?= $motel['category_name'] ?></td>
                        <td>
                            <span class="badge bg-<?= getStatusBadge($motel['status']) ?>">
                                <?= getStatusText($motel['status'], 'motel') ?>
                            </span>
                        </td>
                        <td><?= formatDate($motel['created_at']) ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="?controller=motels&action=edit&id=<?= $motel['id'] ?>" 
                                   class="btn btn-sm btn-warning" title="Sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php if ($motel['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= $motel['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-success" title="Duyệt">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?= $motel['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Từ chối">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $motel['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Xóa"
                                            onclick="return confirm('Xóa phòng này?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
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
                    <a class="page-link" href="?controller=motels&action=index&page=1">« Đầu</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=motels&action=index&page=<?= $pagination['page'] - 1 ?>">‹ Trước</a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?controller=motels&action=index&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?controller=motels&action=index&page=<?= $pagination['page'] + 1 ?>">Sau ›</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=motels&action=index&page=<?= $pagination['pages'] ?>">Cuối »</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Không có phòng nào. <a href="?controller=motels&action=create">Tạo mới</a></div>
<?php endif; ?>
