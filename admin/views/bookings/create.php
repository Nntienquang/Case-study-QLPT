<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Đặt Phòng</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm đặt phòng mới</p>
                </div>
                <a href="?controller=bookings&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=bookings&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">👤 Người Dùng ID</label>
                    <input type="number" name="user_id" class="form-control" placeholder="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">🏠 Phòng ID</label>
                    <input type="number" name="motel_id" class="form-control" placeholder="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📅 Ngày Check-in</label>
                    <input type="date" name="checkin_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📅 Ngày Check-out</label>
                    <input type="date" name="checkout_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📝 Ghi Chú</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Ghi chú thêm"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=bookings&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
