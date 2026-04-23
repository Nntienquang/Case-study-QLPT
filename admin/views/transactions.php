<?php
$currentPage = 'transactions';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-4">
    <h2>Giao Dịch</h2>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Từ</th>
                    <th>Đến</th>
                    <th>Số Tiền</th>
                    <th>Phí</th>
                    <th>Loại</th>
                    <th>Ngày Tạo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $trans): ?>
                <tr>
                    <td><strong>#<?= $trans['id'] ?></strong></td>
                    <td><?= $trans['from_user_name'] ?? 'N/A' ?></td>
                    <td><?= $trans['to_user_name'] ?? 'N/A' ?></td>
                    <td><?= formatCurrency($trans['amount']) ?></td>
                    <td><?= formatCurrency($trans['fee']) ?></td>
                    <td>
                        <span class="badge bg-info">
                            <?= ucfirst($trans['type']) ?>
                        </span>
                    </td>
                    <td><?= formatDate($trans['created_at']) ?></td>
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
            <a class="page-link" href="?controller=transactions&action=index&page=<?= $i ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
