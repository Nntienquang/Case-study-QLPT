<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 style="margin: 0; font-size: 2rem;">➕ Tạo Người Dùng</h2>
                    <p style="color: #9CA3AF; margin: 5px 0 0 0;">Thêm người dùng mới vào hệ thống</p>
                </div>
                <a href="?controller=users&action=index" class="btn btn-outline-secondary">← Quay Lại</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="?controller=users&action=index">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label class="form-label">👤 Tên Người Dùng</label>
                    <input type="text" name="name" class="form-control" placeholder="VD: Nguyễn Văn A" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📧 Email</label>
                    <input type="email" name="email" class="form-control" placeholder="VD: user@gmail.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📱 Số Điện Thoại</label>
                    <input type="tel" name="phone" class="form-control" placeholder="VD: 0123456789">
                </div>

                <div class="mb-3">
                    <label class="form-label">🔐 Mật Khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">👑 Vai Trò</label>
                    <select name="role" class="form-control" required>
                        <option value="user">Người Dùng Thường</option>
                        <option value="owner">Chủ Nhà</option>
                        <option value="admin">Quản Trị Viên</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" style="font-weight: 600;">
                        <i class="bi bi-check-circle"></i> Tạo Mới
                    </button>
                    <a href="?controller=users&action=index" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
