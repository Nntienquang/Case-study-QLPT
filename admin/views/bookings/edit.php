<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">✏️ Sửa Đặt Phòng</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Cập nhật thông tin đặt phòng</p>
                </div>
                <a href="?controller=bookings&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=bookings&action=index">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $booking['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">👤 Người Dùng ID</label>
                    <input type="number" name="user_id" class="form-control" value="<?= $booking['user_id'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">🏠 Phòng ID</label>
                    <input type="number" name="motel_id" class="form-control" value="<?= $booking['motel_id'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📅 Ngày Check-in</label>
                    <input type="date" name="checkin_date" class="form-control" value="<?= $booking['checkin_date'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📅 Ngày Check-out</label>
                    <input type="date" name="checkout_date" class="form-control" value="<?= $booking['checkout_date'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📝 Ghi Chú</label>
                    <textarea name="notes" class="form-control" rows="3"><?= $booking['notes'] ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">📊 Trạng Thái</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>Chờ</option>
                        <option value="accepted" <?= $booking['status'] === 'accepted' ? 'selected' : '' ?>>Chấp nhận</option>
                        <option value="rejected" <?= $booking['status'] === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Cập Nhật
                    </button>
                    <a href="?controller=bookings&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
