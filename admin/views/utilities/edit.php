<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">✏️ Sửa Tiện Ích</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Cập nhật tiện ích</p>
                </div>
                <a href="?controller=utilities&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=utilities&action=index">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $utility['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">⚙️ Tên Tiện Ích</label>
                    <input type="text" name="name" class="form-control" value="<?= $utility['name'] ?>" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Cập Nhật
                    </button>
                    <a href="?controller=utilities&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
