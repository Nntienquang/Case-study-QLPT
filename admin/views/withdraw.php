<?php
$currentPage = 'withdraw';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-4">
    <h2>Duyệt Rút Tiền</h2>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Người Dùng</th>
                    <th>Email</th>
                    <th>Số Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($withdraws as $withdraw): ?>
                <tr>
                    <td><strong>#<?= $withdraw['id'] ?></strong></td>
                    <td><?= $withdraw['user_name'] ?></td>
                    <td><?= $withdraw['email'] ?></td>
                    <td><strong><?= formatCurrency($withdraw['amount']) ?></strong></td>
                    <td>
                        <span class="badge bg-<?= getStatusBadge($withdraw['status']) ?>">
                            <?= getStatusText($withdraw['status'], 'withdraw') ?>
                        </span>
                    </td>
                    <td><?= formatDate($withdraw['created_at']) ?></td>
                    <td>
                        <?php if ($withdraw['status'] === 'pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="id" value="<?= $withdraw['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Duyệt rút tiền này?')">
                                <i class="bi bi-check"></i> Duyệt
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?= $withdraw['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Từ chối rút tiền này?')">
                                <i class="bi bi-x"></i> Từ chối
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<nav class="mt-4">
    <ul class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a class="page-link" href="?controller=withdraw&action=index&page=<?= $i ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
