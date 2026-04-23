<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Phòng Trọ</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm phòng trọ mới</p>
                </div>
                <a href="?controller=motels&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=motels&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">🏠 Tên Phòng</label>
                    <input type="text" name="title" class="form-control" placeholder="VD: Phòng đẹp gần trường ĐH" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📋 Mô Tả</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Mô tả chi tiết phòng"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">📍 Địa Chỉ</label>
                    <input type="text" name="address" class="form-control" placeholder="VD: Đường Nguyễn Huệ, Q1" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Giá (đ)</label>
                    <input type="number" name="price" class="form-control" placeholder="0" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📂 Danh Mục</label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">👤 Chủ Nhà (User ID)</label>
                    <input type="number" name="user_id" class="form-control" placeholder="0" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=motels&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
