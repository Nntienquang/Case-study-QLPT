# 💰 Tính Năng: Doanh Thu Admin (1% Commission)

## Tổng Quan

Admin sẽ nhận **1% commission** từ mỗi booking được hoàn tất thành công. Tính năng này giúp tracking doanh thu admin một cách tự động và minh bạch.

---

## 🔄 Luồng Hoạt Động

```
1. Khách Hàng Đặt Phòng
        ↓
2. Thanh Toán (Status = 'pending')
        ↓
3. Admin Duyệt → Cập nhật Status = 'released'
        ↓
4. System Tự Động Tính 1% Commission
        ↓
5. Tạo Transaction Record (type='commission') cho Admin
        ↓
6. Admin Xem Doanh Thu trong "Doanh Thu Admin" Page
```

---

## 💡 Cơ Chế Tính Toán

### Công Thức
```
Commission Admin = Giá Phòng × 1%
Chủ Trọ Nhận = Giá Phòng × 99%
```

### Ví Dụ
```
Giá phòng: 5.000.000 ₫
Commission Admin (1%): 50.000 ₫   ✓
Chủ Trọ Nhận (99%): 4.950.000 ₫   ✓
```

---

## 📊 Các Thành Phần Được Tạo

### 1. Model: AdminRevenue
**File**: `core/AdminRevenue.php`

**Chức Năng**:
- Tính commission (1%)
- Lấy tổng doanh thu admin
- Lấy danh sách commission theo trang
- Thống kê doanh thu (total, month, count, average)
- Lấy doanh thu theo ngày (30 ngày)

**Methods**:
```php
// Tính 1% commission
$admin_revenue->calculateCommission($amount);

// Lấy tổng doanh thu
$admin_revenue->getTotalRevenue($admin_id);

// Lấy danh sách commission
$admin_revenue->getRevenue($admin_id, $page, $limit);

// Thống kê
$admin_revenue->getStats($admin_id);
```

### 2. Controller: AdminRevenueController
**File**: `app/controller/AdminRevenueController.php`

**Methods**:
- `listRevenue()` - Lấy danh sách commission với phân trang
- `getStats()` - Lấy thống kê doanh thu
- `getChartData()` - Lấy dữ liệu biểu đồ (30 ngày)

### 3. Page: Doanh Thu Admin
**File**: `public/admin/admin_revenue.php`

**Hiển Thị**:
- 📊 **4 Stat Cards**:
  - Tổng doanh thu (tất cả thời gian)
  - Doanh thu tháng này
  - Số lần nhận commission
  - Trung bình commission/lần

- 📋 **Bảng Commission**:
  - ID Booking
  - Tên Phòng Trọ
  - Tên Khách Hàng & Email
  - Giá Phòng
  - Commission (1%)
  - Ngày Nhận

- 📝 **Thông Tin Bổ Sung**:
  - Cơ chế doanh thu (giải thích)
  - Ví dụ tính toán

### 4. Update PaymentController
**File**: `app/controller/PaymentController.php`

**Thay Đổi**:
Khi admin cập nhật payment status thành `'released'`:
1. Lấy booking info (deposit_amount)
2. Tính commission = deposit_amount × 1%
3. Thêm transaction record: type='commission' cho admin

```php
// Tự động khi status = 'released'
INSERT INTO transactions 
(to_user, booking_id, amount, type, created_at) 
VALUES 
(admin_id, booking_id, commission_1%, 'commission', NOW())
```

### 5. Update Dashboard
**File**: `public/admin/index.php`

**Thêm Row Mới**: 
Hiển thị 4 card doanh thu admin:
- Tổng doanh thu (tất cả thời gian)
- Doanh thu tháng này
- Số lần nhận commission
- Trung bình commission

---

## 📁 File Cấu Trúc

```
core/
├── AdminRevenue.php                    ← Model mới

app/controller/
├── AdminRevenueController.php          ← Controller mới
├── PaymentController.php (updated)     ← Commission logic

public/admin/
├── admin_revenue.php                   ← Page doanh thu
├── index.php (updated)                 ← Dashboard + revenue card
├── payments.php (updated)              ← Sidebar updated
└── sidebar.php                         ← Shared sidebar (tùy chọn)

public/
├── admin_init.php (updated)            ← Include AdminRevenue & Controller
```

---

## 🚀 Cách Sử Dụng

### 1. Xem Doanh Thu Admin
1. Đăng nhập vào admin panel
2. Click **"Doanh Thu Admin"** từ sidebar
3. Xem stats và danh sách commission

### 2. Thêm Commission Tự Động
1. Vào **"Thanh Toán"** page
2. Tìm payment cần approve
3. Cập nhật status thành **"Released"**
4. System sẽ tự động:
   - Tính 1% commission
   - Tạo transaction record
   - Admin sẽ nhìn thấy ngay trong "Doanh Thu Admin"

### 3. Xem Dashboard
1. Vào **Dashboard** (Home)
2. Scroll xuống, sẽ thấy **4 card doanh thu admin**:
   - Tổng doanh thu
   - Doanh thu tháng
   - Số commission
   - Trung bình

---

## 📊 Database

### Table: transactions
Sử dụng table hiện tại với thêm type='commission':

```sql
INSERT INTO transactions 
(to_user, booking_id, amount, type, created_at) 
VALUES 
(admin_id, booking_id, commission_amount, 'commission', NOW());
```

**Existing Types**:
- `deposit` - Khách nạp tiền
- `release` - Chủ nhận tiền
- `refund` - Hoàn tiền
- `withdraw` - Rút tiền
- `fee` - Phí (existing)
- `commission` - Commission admin (NEW)

---

## 🔐 Bảo Mật

✅ Chỉ admin (role='admin') có thể xem doanh thu
✅ Commission được tính tự động (không thể thay đổi)
✅ Transaction được ghi lại đầy đủ (audit trail)
✅ Admin ID từ $_SESSION (không thể giả mạo)

---

## 📈 Thống Kê

Admin có thể xem:
- ✅ Tổng doanh thu tất cả thời gian
- ✅ Doanh thu tháng hiện tại
- ✅ Số lần nhận commission
- ✅ Trung bình commission/lần
- ✅ Danh sách chi tiết từng commission

---

## 💡 Ví Dụ Cụ Thể

### Scenario 1: Khách đặt phòng 3.000.000 ₫

```
Khách đặt phòng: 3.000.000 ₫
     ↓
Admin release payment
     ↓
Commission tính: 3.000.000 × 1% = 30.000 ₫
Chủ trọ nhận: 3.000.000 × 99% = 2.970.000 ₫
```

**Transaction Records**:
```
Transaction 1:
- to_user: admin_id
- booking_id: 123
- amount: 30.000
- type: 'commission'  ← NEW
- created_at: 2026-04-25 10:30:00

Transaction 2:
- to_user: owner_id
- booking_id: 123
- amount: 2.970.000
- type: 'release'
- created_at: 2026-04-25 10:30:00
```

---

## 🎯 Tính Năng Mở Rộng (Tương Lai)

Có thể thêm:
- 📊 Biểu đồ doanh thu theo ngày/tháng
- 📧 Email thông báo khi có commission mới
- 💰 Tính năng rút tiền commission
- 📈 Report doanh thu chi tiết
- 🔄 Tính thuế từ commission

---

## ❓ Câu Hỏi Thường Gặp

**Q: Commission được tính khi nào?**
A: Khi admin cập nhật payment status thành 'released'

**Q: Nếu hoàn tiền thì sao?**
A: Commission vẫn được giữ. Nếu muốn hoàn lại commission, cần thêm logic riêng.

**Q: Commission có thể thay đổi không?**
A: Không, được tính tự động dựa trên giá phòng × 1%

**Q: Ai có thể xem doanh thu?**
A: Chỉ admin (role='admin')

**Q: Doanh thu được lưu ở đâu?**
A: Table `transactions` với type='commission'

---

## 🔧 Cài Đặt Lệnh

```bash
# Không cần cài đặt đặc biệt
# Tích hợp vào hệ thống hiện tại
# Chỉ cần:
1. Update database: Import phongtro_db.sql (nếu chưa)
2. Restart server
3. Admin dashboard sẽ hiển thị doanh thu
```

---

## 📝 Chú Ý

⚠️ **Điều Quan Trọng**:
- Commission được tính CHỈ khi payment status = 'released'
- Admin ID được lấy từ session ($_SESSION['user_id'])
- Commission là 1%, không có cách thay đổi (cấu hình trong AdminRevenue model)
- Tất cả giao dịch được log lại trong transactions table

---

**Phiên bản**: 1.0
**Ngày cập nhật**: 25/04/2026
**Status**: ✅ Hoạt động
