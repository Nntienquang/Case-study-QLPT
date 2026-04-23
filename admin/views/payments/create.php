<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Thanh Toán</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm bản ghi thanh toán mới</p>
                </div>
                <a href="?controller=payments&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=payments&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">📅 Đặt Phòng ID</label>
                    <input type="number" name="booking_id" class="form-control" placeholder="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Số Tiền</label>
                    <input type="number" name="amount" class="form-control" placeholder="0" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">🏪 Phí Admin</label>
                    <input type="number" name="fee" class="form-control" placeholder="0" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💳 Phương Thức</label>
                    <select name="method" class="form-control" required>
                        <option value="">-- Chọn --</option>
                        <option value="bank_transfer">Chuyển khoản</option>
                        <option value="cash">Tiền mặt</option>
                        <option value="card">Thẻ</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">📊 Trạng Thái</label>
                    <select name="status" class="form-control" required>
                        <option value="pending">Chờ</option>
                        <option value="held">Giữ</option>
                        <option value="released">Giải phóng</option>
                        <option value="refunded">Hoàn lại</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=payments&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
