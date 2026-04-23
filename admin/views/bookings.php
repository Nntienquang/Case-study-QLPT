<?php
$currentPage = 'bookings';
?>

<div class="d-flex justify-content-between align-items-center mb-4 pt-4">
    <h2>Quản Lý Đặt Phòng</h2>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Người Dùng</th>
                    <th>Phòng</th>
                    <th>Tiền Cọc</th>
                    <th>Ngày Check-in</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><strong>#<?= $booking['id'] ?></strong></td>
                    <td><?= $booking['user_name'] ?></td>
                    <td><?= $booking['motel_title'] ?></td>
                    <td><?= formatCurrency($booking['deposit_amount']) ?></td>
                    <td><?= $booking['checkin_date'] ?></td>
                    <td>
                        <span class="badge bg-<?= getStatusBadge($booking['status']) ?>">
                            <?= getStatusText($booking['status'], 'booking') ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($booking['status'] === 'pending'): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="accept">
                            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="bi bi-check"></i> Chấp nhận
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?= $booking['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
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
            <a class="page-link" href="?controller=bookings&action=index&page=<?= $i ?>">
                <?= $i ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>
