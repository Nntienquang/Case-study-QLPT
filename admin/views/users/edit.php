<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">✏️ Sửa Người Dùng</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Cập nhật thông tin người dùng</p>
                </div>
                <a href="?controller=users&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=users&action=index">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= $user['id'] ?>">

                <div class="mb-3">
                    <label class="form-label">👤 Tên Người Dùng</label>
                    <input type="text" name="name" class="form-control" value="<?= $user['name'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📧 Email</label>
                    <input type="email" name="email" class="form-control" value="<?= $user['email'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📱 Số Điện Thoại</label>
                    <input type="tel" name="phone" class="form-control" value="<?= $user['phone'] ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">🔐 Mật Khẩu (để trống nếu không đổi)</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới">
                </div>

                <div class="mb-3">
                    <label class="form-label">👑 Vai Trò</label>
                    <select name="role" class="form-control" required>
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Người Dùng Thường</option>
                        <option value="owner" <?= $user['role'] === 'owner' ? 'selected' : '' ?>>Chủ Nhà</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản Trị Viên</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Cập Nhật
                    </button>
                    <a href="?controller=users&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
