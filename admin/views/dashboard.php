<?php
$currentPage = 'dashboard';
?>

<div class="d-flex justify-content-between align-items-center mb-5 pt-2">
    <div>
        <h2 style="margin: 0; font-size: 2rem;">📊 Dashboard</h2>
        <p style="color: #9CA3AF; margin: 5px 0 0 0;">Tổng quan hoạt động hệ thống</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h5 class="text-muted">👥 Tổng Người Dùng</h5>
                <h2 class="mb-0"><?= $stats['total_users'] ?></h2>
                <small style="color: #9CA3AF;"><i class="bi bi-people-fill"></i> người</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h5 class="text-muted">🏠 Tổng Phòng</h5>
                <h2 class="mb-0"><?= $stats['total_motels'] ?></h2>
                <small style="color: #9CA3AF;"><i class="bi bi-building"></i> phòng</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h5 class="text-muted">📅 Tổng Đặt Phòng</h5>
                <h2 class="mb-0"><?= $stats['total_bookings'] ?></h2>
                <small style="color: #9CA3AF;"><i class="bi bi-calendar-check"></i> đơn</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h5 class="text-muted">💳 Tổng Thanh Toán</h5>
                <h2 class="mb-0"><?= $stats['total_payments'] ?></h2>
                <small style="color: #9CA3AF;"><i class="bi bi-credit-card"></i> giao dịch</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card bg-success">
            <div class="card-body text-center">
                <h5>💰 Doanh Thu</h5>
                <h2 class="mb-0"><?= formatCurrency($stats['total_revenue']) ?></h2>
                <small style="color: #0d7c4e;"><i class="bi bi-cash-coin"></i> từ phí</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card stat-card">
            <div class="card-body text-center">
                <h5 class="text-muted">⭐ Tổng Review</h5>
                <h2 class="mb-0"><?= $stats['total_reviews'] ?></h2>
                <small style="color: #9CA3AF;"><i class="bi bi-star-fill"></i> đánh giá</small>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-list-check"></i> Đặt Phòng Gần Đây (Top 10)
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="20%">Người Dùng</th>
                        <th width="25%">Phòng</th>
                        <th width="15%">Ngày Check-in</th>
                        <th width="15%">Trạng Thái</th>
                        <th width="20%">Ngày Tạo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentBookings as $booking): ?>
                    <tr>
                        <td><strong>#<?= $booking['id'] ?></strong></td>
                        <td><?= $booking['user_name'] ?></td>
                        <td><strong><?= $booking['motel_title'] ?></strong></td>
                        <td><?= $booking['checkin_date'] ?></td>
                        <td>
                            <span class="badge bg-<?= getStatusBadge($booking['status']) ?>">
                                <?= getStatusText($booking['status'], 'booking') ?>
                            </span>
                        </td>
                        <td><?= formatDate($booking['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
