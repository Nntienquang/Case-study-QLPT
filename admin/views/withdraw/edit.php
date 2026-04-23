<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">✏️ Sửa Yêu Cầu Rút Tiền</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Cập nhật yêu cầu rút tiền</p>
                </div>
                <a href="?controller=withdraw&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=withdraw&action=index">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $withdraw['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">👤 Người Dùng ID</label>
                    <input type="number" name="user_id" class="form-control" value="<?= $withdraw['user_id'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Số Tiền Rút</label>
                    <input type="number" name="amount" class="form-control" value="<?= $withdraw['amount'] ?>" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📊 Trạng Thái</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= $withdraw['status'] === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                        <option value="approved" <?= $withdraw['status'] === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                        <option value="rejected" <?= $withdraw['status'] === 'rejected' ? 'selected' : '' ?>>Từ chối</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Cập Nhật
                    </button>
                    <a href="?controller=withdraw&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
