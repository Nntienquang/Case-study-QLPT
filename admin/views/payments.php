<?php
$currentPage = 'payments';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-4">
    <h2>Quản Lý Thanh Toán</h2>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Người Dùng</th>
                    <th>Phòng</th>
                    <th>Số Tiền</th>
                    <th>Phí Admin</th>
                    <th>Phương Thức</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><strong>#<?= $payment['id'] ?></strong></td>
                    <td><?= $payment['user_name'] ?></td>
                    <td><?= $payment['motel_title'] ?></td>
                    <td><?= formatCurrency($payment['amount']) ?></td>
                    <td><span class="badge bg-success"><?= formatCurrency($payment['fee']) ?></span></td>
                    <td><?= ucfirst($payment['method']) ?></td>
                    <td>
                        <span class="badge bg-<?= getStatusBadge($payment['status']) ?>">
                            <?= getStatusText($payment['status'], 'payment') ?>
                        </span>
                    </td>
                    <td><?= formatDate($payment['created_at']) ?></td>
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
            <a class="page-link" href="?controller=payments&action=index&page=<?= $i ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
