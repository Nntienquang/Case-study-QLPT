# 🎯 ADMIN PANEL - HƯỚNG DẪN SỬ DỤNG

## 📋 PROJECT STRUCTURE

```
admin/
├── config/
│   └── database.php          # Database connection
├── includes/
│   ├── middleware.php        # Auth & middleware
│   └── helpers.php           # Helper functions
├── models/
│   ├── BaseModel.php         # ORM base class
│   ├── User.php
│   ├── Motel.php
│   ├── Booking.php
│   ├── Payment.php
│   ├── Transaction.php
│   ├── Wallet.php
│   ├── Review.php
│   └── WithdrawRequest.php
├── controllers/
│   ├── DashboardController.php
│   ├── UserController.php
│   ├── MotelController.php
│   ├── BookingController.php
│   ├── PaymentController.php
│   ├── TransactionController.php
│   └── WithdrawController.php
├── views/
│   ├── layout.php            # Main layout + sidebar
│   ├── dashboard.php
│   ├── users.php
│   ├── motels.php
│   ├── bookings.php
│   ├── payments.php
│   ├── transactions.php
│   └── withdraw.php
└── public/
    ├── index.php             # Main router
    ├── login.php             # Login page
    ├── logout.php            # Logout
    ├── css/admin.css
    └── js/admin.js
```

## ⚡ QUICK START

### 1. Import Database
```bash
# Mở phpMyAdmin
# Import file: c:/xampp/htdocs/QuanLyPhongTro/phongtro_db.sql
```

### 2. Access Admin Panel
```
http://localhost/QuanLyPhongTro/admin/public/login.php
```

### 3. Login Credentials
```
Email: admin@gmail.com
Password: 123
```

## 📊 FEATURES

### Dashboard
- 📈 Thống kê tổng: Users, Motels, Bookings, Payments, Reviews
- 💰 Tổng doanh thu (tổng phí từ các booking)
- 📋 Danh sách 10 booking gần đây nhất

### Quản Lý Người Dùng (Users)
- ✅ Xem danh sách tất cả users
- 📄 Phân loại theo role: user/owner/admin
- 📱 Xem thông tin liên hệ

### Quản Lý Phòng (Motels)
- ✅ Xem danh sách phòng đang chờ duyệt
- ✅ Duyệt (approve) → status = 'approved'
- ❌ Từ chối (reject) → status = 'hidden'
- 💰 Xem giá phòng

### Quản Lý Đặt Phòng (Bookings)
- ✅ Xem danh sách tất cả bookings
- ✅ Chấp nhận (accept) → status = 'accepted'
- ❌ Từ chối (reject) → status = 'rejected'
- 📅 Xem ngày check-in

### Thanh Toán (Payments)
- 💳 Xem tất cả giao dịch thanh toán
- 💰 Xem số tiền + phí admin
- 📋 Xem trạng thái (pending/held/released/refunded)

### Giao Dịch (Transactions)
- 📊 Lịch sử tất cả giao dịch
- 👥 Xem từ user → đến user
- 💸 Xem loại: deposit/release/refund/fee/withdraw

### Duyệt Rút Tiền (Withdraw)
- ✅ Duyệt (approve) → trừ tiền từ wallet
- ❌ Từ chối (reject) → status = 'rejected'
- 📋 Xem lịch sử rút tiền

## 🔐 AUTH SYSTEM

- Session-based authentication
- Chỉ admin role = 'admin' mới vào được
- Middleware: `checkAdmin()`
- Logout: xóa session

## 🎨 STYLING

- Bootstrap 5
- Custom CSS: `public/css/admin.css`
- Responsive design (sidebar on desktop, collapsible on mobile)
- Color-coded status badges

## 🛠️ TECHNOLOGY STACK

- **Language**: PHP 8+
- **Database**: MySQL (MariaDB)
- **ORM**: Custom BaseModel
- **Pattern**: MVC
- **Frontend**: Bootstrap 5, JavaScript
- **Session**: PHP $_SESSION

## 📝 NOTES

1. **Database**: Tất cả data được lưu trong database `phongtro`
2. **Models**: Sử dụng BaseModel với ORM methods (all, find, count, query, create, update, delete)
3. **Routes**: Query string: `?controller=X&action=Y&page=Z`
4. **Pagination**: Mặc định 10 items/page
5. **Status**: 
   - Motel: pending → approved / hidden
   - Booking: pending → accepted / rejected / completed / cancelled
   - Payment: pending → held → released / refunded
   - Withdraw: pending → approved / rejected

## ⚠️ IMPROVEMENTS (TODO)

- [ ] Form validation
- [ ] Edit/Delete users
- [ ] Edit/Delete motels
- [ ] View motel details
- [ ] Search & filter
- [ ] Export to Excel
- [ ] Statistics charts
- [ ] Email notifications
- [ ] Password hashing (use password_hash)
- [ ] Audit logs

## 🚀 USAGE EXAMPLE

```php
// Controller example:
$motelModel = new Motel($conn);
$motels = $motelModel->paginate(1, 10);  // Get 10 motels on page 1
$total = $motelModel->count();            // Count total motels

// Custom query:
$pendingMotels = $motelModel->where('status', '=', 'pending');

// Update:
$motelModel->update($id, ['status' => 'approved']);
```

---

✅ **READY TO USE!** Thích hợp cho team project. Dễ mở rộng và maintain.
