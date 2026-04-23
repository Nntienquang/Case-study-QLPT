<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">✏️ Sửa Thanh Toán</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Cập nhật thông tin thanh toán</p>
                </div>
                <a href="?controller=payments&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=payments&action=index">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $payment['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">📅 Đặt Phòng ID</label>
                    <input type="number" name="booking_id" class="form-control" value="<?= $payment['booking_id'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Số Tiền</label>
                    <input type="number" name="amount" class="form-control" value="<?= $payment['amount'] ?>" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">🏪 Phí Admin</label>
                    <input type="number" name="fee" class="form-control" value="<?= $payment['fee'] ?>" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💳 Phương Thức</label>
                    <select name="method" class="form-control" required>
                        <option value="bank_transfer" <?= $payment['method'] === 'bank_transfer' ? 'selected' : '' ?>>Chuyển khoản</option>
                        <option value="cash" <?= $payment['method'] === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                        <option value="card" <?= $payment['method'] === 'card' ? 'selected' : '' ?>>Thẻ</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">📊 Trạng Thái</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= $payment['status'] === 'pending' ? 'selected' : '' ?>>Chờ</option>
                        <option value="held" <?= $payment['status'] === 'held' ? 'selected' : '' ?>>Giữ</option>
                        <option value="released" <?= $payment['status'] === 'released' ? 'selected' : '' ?>>Giải phóng</option>
                        <option value="refunded" <?= $payment['status'] === 'refunded' ? 'selected' : '' ?>>Hoàn lại</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Cập Nhật
                    </button>
                    <a href="?controller=payments&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
