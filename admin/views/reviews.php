<?php
$currentPage = 'reviews';
?>

<div class="d-flex justify-content-between align-items-center mb-5 pt-2">
    <div>
        <h2 style="margin: 0; font-size: 2rem;">⭐ Đánh Giá & Review</h2>
        <p style="color: #9CA3AF; margin: 5px 0 0 0;">Quản lý tất cả đánh giá từ khách hàng</p>
    </div>
</div>

<?php if (!empty($reviews)): ?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-star-fill"></i> Danh Sách Đánh Giá
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Người Đánh Giá</th>
                        <th width="25%">Phòng</th>
                        <th width="10%">Điểm</th>
                        <th width="20%">Nội Dung</th>
                        <th width="20%">Ngày</th>
                        <th width="5%">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><strong>#<?= $review['id'] ?></strong></td>
                        <td><?= $review['user_name'] ?></td>
                        <td><strong><?= $review['motel_title'] ?></strong></td>
                        <td>
                            <span class="badge bg-warning" style="color: #000;">
                                ⭐ <?= $review['rating'] ?>/5
                            </span>
                        </td>
                        <td>
                            <small><?= substr($review['comment'], 0, 30) ?>...</small>
                        </td>
                        <td><?= formatDate($review['created_at']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Xóa đánh giá này?')">
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
                    <a class="page-link" href="?controller=reviews&action=index&page=1">« Đầu</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=reviews&action=index&page=<?= $pagination['page'] - 1 ?>">‹ Trước</a>
                </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['pages']; $i++): ?>
                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?controller=reviews&action=index&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['pages']): ?>
                <li class="page-item">
                    <a class="page-link" href="?controller=reviews&action=index&page=<?= $pagination['page'] + 1 ?>">Sau ›</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?controller=reviews&action=index&page=<?= $pagination['pages'] ?>">Cuối »</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info">Không có đánh giá nào</div>
<?php endif; ?>
