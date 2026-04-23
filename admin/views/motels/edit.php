<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">✏️ Sửa Phòng Trọ</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Cập nhật thông tin phòng</p>
                </div>
                <a href="?controller=motels&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=motels&action=index">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $motel['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">🏠 Tên Phòng</label>
                    <input type="text" name="title" class="form-control" value="<?= $motel['title'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📋 Mô Tả</label>
                    <textarea name="description" class="form-control" rows="3"><?= $motel['description'] ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">📍 Địa Chỉ</label>
                    <input type="text" name="address" class="form-control" value="<?= $motel['address'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">💰 Giá (đ)</label>
                    <input type="number" name="price" class="form-control" value="<?= $motel['price'] ?>" step="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📂 Danh Mục</label>
                    <select name="category_id" class="form-control" required>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $motel['category_id'] ? 'selected' : '' ?>><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">📊 Trạng Thái</label>
                    <select name="status" class="form-control" required>
                        <option value="pending" <?= $motel['status'] === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                        <option value="approved" <?= $motel['status'] === 'approved' ? 'selected' : '' ?>>Đã duyệt</option>
                        <option value="hidden" <?= $motel['status'] === 'hidden' ? 'selected' : '' ?>>Ẩn</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Cập Nhật
                    </button>
                    <a href="?controller=motels&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
