<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Yêu Cầu Rút Tiền</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm yêu cầu rút tiền mới</p>
                </div>
                <a href="?controller=withdraw&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=withdraw&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">👤 Người Dùng ID</label>
                    <input type="number" name="user_id" class="form-control" placeholder="0" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Số Tiền Rút</label>
                    <input type="number" name="amount" class="form-control" placeholder="0" step="1000" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=withdraw&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
