# ⚡ Cập Nhật: Tính Năng Doanh Thu Admin

## 📢 Thông Báo

Hệ thống admin đã được cập nhật với **tính năng doanh thu (1% commission)** cho admin.

---

## 🎯 Tóm Tắt Nhanh

Admin sẽ tự động nhận **1% doanh thu** từ mỗi booking thành công:

```
Giá Phòng: 5.000.000 ₫
    ↓
Admin Nhận: 50.000 ₫ (1%)  ✓
Chủ Trọ Nhận: 4.950.000 ₫ (99%)  ✓
```

---

## 📁 File Mới Được Tạo

### 1. **core/AdminRevenue.php**
- Model quản lý doanh thu
- Tính 1% commission tự động
- Lấy thống kê doanh thu

### 2. **app/controller/AdminRevenueController.php**
- Controller xử lý logic doanh thu
- Hỗ trợ phân trang & statistics

### 3. **public/admin/admin_revenue.php**
- 🌐 Trang xem doanh thu admin
- Hiển thị 4 stat cards
- Bảng chi tiết commission
- Thông tin cơ chế tính toán

### 4. **public/admin/sidebar.php** (optional)
- Sidebar chung (có thể tái sử dụng)

### 5. **ADMIN_REVENUE_FEATURE.md**
- Tài liệu chi tiết tính năng
- Hướng dẫn cài đặt & sử dụng

---

## 🔄 Cách Hoạt Động

### Bước 1: Khách Hàng Đặt Phòng
```
Khách đặt phòng → Payment = 5.000.000 ₫ (Status: pending)
```

### Bước 2: Admin Release Payment
```
Admin → Payments Page
Click "Release Payment" 
Status: pending → released
```

### Bước 3: System Tính Commission
```
Automatically:
✓ Tính commission: 5.000.000 × 1% = 50.000 ₫
✓ Tạo transaction record (type='commission')
✓ Admin có thể xem ngay
```

### Bước 4: Admin Xem Doanh Thu
```
Admin → Click "Doanh Thu Admin" từ sidebar
✓ Xem tổng doanh thu
✓ Xem doanh thu tháng
✓ Xem danh sách commission chi tiết
```

---

## 📊 Page Doanh Thu Admin

### URL
```
http://localhost/QuanLyPhongTro/public/admin/admin_revenue.php
```

### Nội Dung
1. **4 Stat Cards**:
   - 💰 Tổng Doanh Thu (tất cả thời gian)
   - 📈 Doanh Thu Tháng Này
   - ✓ Số Commission Đã Nhận
   - 📊 Trung Bình Commission/Lần

2. **Bảng Chi Tiết**:
   - ID Booking
   - Tên Phòng Trọ & Chủ Trọ
   - Tên Khách Hàng
   - Giá Phòng
   - Commission (1%)
   - Ngày Nhận

3. **Phân Trang**: 10 item/page

4. **Thông Tin Bổ Sung**:
   - Giải thích cơ chế
   - Ví dụ tính toán

---

## 🔧 Update Trong Các File Hiện Tại

### 1. **app/controller/PaymentController.php**
```php
// Khi status = 'released':
// Tự động tính 1% commission
// Thêm transaction record cho admin
```

### 2. **app/controller/DashboardController.php**
```php
// Thêm revenue stats vào dashboard
$data['revenue_stats'] = $this->revenue->getStats($admin_id);
```

### 3. **public/admin/index.php (Dashboard)**
```php
// Thêm 4 stat cards doanh thu admin
// + Button "Xem Chi Tiết Doanh Thu"
```

### 4. **public/admin_init.php**
```php
// Include AdminRevenue model
// Include AdminRevenueController
```

### 5. **Sidebar của Tất Cả Pages**
```php
// Thêm link: "💰 Doanh Thu Admin"
// Tất cả pages đều có link này
```

---

## 📋 Sidebar Navigation Update

Link "Doanh Thu Admin" được thêm vào:
- ✅ index.php (Dashboard)
- ✅ motels.php, motel_detail.php
- ✅ users.php, user_detail.php
- ✅ bookings.php, booking_detail.php
- ✅ payments.php, payment_detail.php
- ✅ reviews.php, review_detail.php
- ✅ categories.php
- ✅ districts.php
- ✅ utilities.php

---

## 💾 Database

### Table: transactions
**Sử dụng table hiện tại**, thêm type='commission':

```sql
INSERT INTO transactions 
(to_user, booking_id, amount, type, created_at) 
VALUES 
(admin_id, booking_id, 50000, 'commission', NOW());
```

---

## 🚀 Sử Dụng

### 1. Xem Doanh Thu
```
1. Dashboard → Scroll xuống → Xem 4 stat cards doanh thu
2. Hoặc click "Doanh Thu Admin" từ sidebar
3. Xem chi tiết commission
```

### 2. Commission Được Tạo Tự Động
```
1. Vào Payments → Tìm booking cần approve
2. Click "Release" 
3. Status: pending → released
4. Commission tự động tính (1%)
5. Transaction record được tạo
```

### 3. Kiểm Tra Stats
```
Dashboard có 4 card:
- Tổng doanh thu
- Doanh thu tháng
- Số commission
- Trung bình commission
```

---

## ⚙️ Cấu Hình

### Commission Rate (1%)
**File**: `core/AdminRevenue.php`
```php
private $commission_rate = 0.01;  // 1%
```

Nếu muốn đổi:
```php
private $commission_rate = 0.02;  // 2%
private $commission_rate = 0.005; // 0.5%
```

---

## 🔒 Bảo Mật

✅ Chỉ admin (role='admin') có thể xem
✅ Commission tính tự động (không thể thay đổi)
✅ Admin ID lấy từ session (không giả mạo được)
✅ Tất cả giao dịch được log (audit trail)

---

## ❓ FAQ

**Q: Commission được tính khi nào?**
A: Khi payment status = 'released'

**Q: Nếu hoàn tiền thì commission bị hoàn không?**
A: Không, commission được giữ. (Có thể thêm logic sau nếu cần)

**Q: Admin ID lấy từ đâu?**
A: Từ $_SESSION['user_id'] (admin đang đăng nhập)

**Q: Commission % có thể thay đổi không?**
A: Có, sửa $commission_rate trong AdminRevenue.php

**Q: Doanh thu được lưu ở đâu?**
A: Trong table 'transactions' với type='commission'

---

## 📞 Hỗ Trợ

Xem chi tiết trong:
- `ADMIN_REVENUE_FEATURE.md` - Tài liệu đầy đủ
- `README.md` - Hướng dẫn chung
- `SUMMARY.md` - Tóm tắt dự án

---

## 📈 Statistics Có Sẵn

### Dashboard
- Tổng doanh thu (tất cả thời gian)
- Doanh thu tháng hiện tại
- Số lần nhận commission
- Trung bình commission

### Doanh Thu Admin Page
- Tất cả stats trên + danh sách chi tiết
- Phân trang
- Tìm kiếm (thêm sau nếu cần)

---

## 🎉 Tính Năng Mở Rộng (Tương Lai)

- 📊 Biểu đồ doanh thu
- 📅 Lọc theo ngày/tháng
- 📧 Email thông báo commission mới
- 💰 Rút tiền commission
- 🔄 Tính hoàn lại commission khi refund

---

**Status**: ✅ Hoạt động bình thường
**Ngày Cập Nhật**: 25/04/2026
**Phiên Bản**: 1.1 (Admin Revenue Added)
