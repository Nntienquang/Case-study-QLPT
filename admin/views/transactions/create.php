<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Giao Dịch</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm giao dịch mới</p>
                </div>
                <a href="?controller=transactions&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=transactions&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">👤 Từ Người Dùng ID</label>
                    <input type="number" name="from_user" class="form-control" placeholder="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">👤 Đến Người Dùng ID</label>
                    <input type="number" name="to_user" class="form-control" placeholder="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Số Tiền</label>
                    <input type="number" name="amount" class="form-control" placeholder="0" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">🏪 Phí</label>
                    <input type="number" name="fee" class="form-control" placeholder="0" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📝 Loại Giao Dịch</label>
                    <select name="type" class="form-control" required>
                        <option value="">-- Chọn --</option>
                        <option value="deposit">Nạp tiền</option>
                        <option value="release">Giải phóng</option>
                        <option value="refund">Hoàn lại</option>
                        <option value="fee">Phí</option>
                        <option value="withdraw">Rút tiền</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=transactions&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
