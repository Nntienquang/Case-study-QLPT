<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Danh Mục</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm loại phòng mới</p>
                </div>
                <a href="?controller=categories&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=categories&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">📝 Tên Danh Mục</label>
                    <input type="text" name="name" class="form-control" placeholder="VD: Studio, 1 Phòng Ngủ, 2 Phòng Ngủ" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📋 Mô Tả</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Mô tả chi tiết về danh mục"></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=categories&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
