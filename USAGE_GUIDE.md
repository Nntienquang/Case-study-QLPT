📚 **HƯỚNG DẪN SỬ DỤNG - HỆ THỐNG ADMIN HOÀN CHỈNH**

═══════════════════════════════════════════════════════════════════

## 🚀 QUICK START

1. **Tải database migration**:
   - Chạy SQL file: `migrations/add_email_logs_table.sql`
   - Đảm bảo table `email_logs` được tạo

2. **Login admin**:
   - URL: http://localhost/QuanLyPhongTro/public/admin/
   - Nếu chưa có admin: Chạy `setup.php` để tạo tài khoản

3. **Quyền truy cập**:
   - Chỉ user với role='admin' mới có quyền truy cập
   - Session timeout: 30 phút (tự động logout)

═══════════════════════════════════════════════════════════════════

## 📊 PHẦN 1: DASHBOARD & THỐNG KÊ

**URL**: /admin/index.php

**Thông tin hiển thị**:
- Tổng phòng trọ (approved + pending + hidden)
- Tổng người dùng (by role)
- Đơn đặt phòng (by status)
- Đánh giá trung bình
- Doanh thu tháng
- Số commission
- Đơn đặt phòng chưa thanh toán
- Top phòng trọ được yêu thích

**Chức năng**:
- Click vào stat card để lọc dữ liệu
- Tất cả số liệu cập nhật real-time

═══════════════════════════════════════════════════════════════════

## 🏠 PHẦN 2: QUẢN LÝ PHÒNG TRỢ

**URL**: /admin/motels.php

**Chức năng**:

1. **List Phòng Trọ**:
   - Lọc theo status (pending/approved/hidden)
   - Phân trang 10 items/page
   - Hiển thị: ảnh, tên, chủ trọ, giá, trạng thái, ngày tạo

2. **Approve Phòng Trọ**:
   - Click nút "✓ Duyệt"
   - Status thay đổi pending → approved
   - **ActivityLog**: Ghi lại (approve_motel)
   - Phòng hiển thị công khai trên website

3. **Hide Phòng Trọ**:
   - Click nút "⊘ Ẩn"
   - Status thay đổi → hidden
   - **ActivityLog**: Ghi lại (hide_motel)
   - Khách không thấy phòng trên website

4. **Delete Phòng Trọ**:
   - Click nút "🗑 Xóa"
   - Xóa phòng + hình ảnh + tiện nghi
   - **ActivityLog**: Ghi lại (delete_motel)
   - Cascade delete: bookings, reviews, images

5. **View Chi Tiết**:
   - Click nút "👁 Xem"
   - Hiển thị đầy đủ thông tin phòng
   - Hình ảnh, tiện nghi, chủ trọ info, giá cả

═══════════════════════════════════════════════════════════════════

## 👥 PHẦN 3: QUẢN LÝ NGƯỜI DÙNG

**URL**: /admin/users.php

**Chức năng**:

1. **List Người Dùng**:
   - Lọc theo role (admin/owner/user)
   - Phân trang
   - Hiển thị: avatar, tên, email, điện thoại, vai trò, ngày tạo

2. **View Chi Tiết**:
   - Thông tin cá nhân
   - Số phòng trọ (nếu owner)
   - Số đơn đặt phòng
   - Số đánh giá
   - **ActivityLog**: Ghi lại action

3. **Delete User**:
   - Click nút "🗑 Xóa"
   - Xóa tài khoản (cascade: posts, bookings, reviews)
   - **ActivityLog**: Ghi lại (delete_user)
   - Không thể xóa chính mình

═══════════════════════════════════════════════════════════════════

## ✅ PHẦN 4: DUYỆT TÀI KHOẢN CHỦ TRỢ

**URL**: /admin/user_approvals.php

**Quy trình duyệt**:

1. Owner đăng ký → status='pending'
2. Admin vào "Duyệt Chủ Trọ"
3. Admin duyệt hoặc từ chối
4. Hệ thống gửi email thông báo

**Chức năng**:

1. **Tab Chờ Duyệt**:
   - List owner pending
   - 4 stat cards (tổng pending, approved, rejected, blocked)
   - Search theo name/email/phone

2. **Approve User** (✓ Duyệt):
   - Click nút "✓ Duyệt"
   - Status: pending → approved
   - **Email**: Gửi thông báo duyệt thành công
   - **ActivityLog**: Ghi lại (approve_user)
   - Owner giờ có thể posting phòng

3. **Reject User** (✗ Từ Chối):
   - Click nút "✗ Từ Chối"
   - Modal yêu cầu nhập rejection_reason
   - Status: pending → rejected
   - **Email**: Gửi thông báo với lý do từ chối
   - **ActivityLog**: Ghi lại (reject_user)
   - Owner cần liên hệ để submit lại

4. **Block User** (🔒 Khóa):
   - Click nút "🔒 Khóa" trên user detail
   - Status: approved → blocked
   - **Email**: Gửi thông báo bị khóa
   - **ActivityLog**: Ghi lại (block_user)
   - Owner không thể đăng nhập

5. **View Chi Tiết User**:
   - Click "👁 Xem"
   - Hiển thị: thông tin cá nhân, CMND (hình ảnh)
   - Lịch sử duyệt (ai duyệt, lúc nào)
   - Tab chứng chỉ/bằng cấp (nếu có)

**Tab Khác**:
- **Tab Đã Duyệt**: List owner approved (có thể unblock)
- **Tab Từ Chối**: List owner rejected (có thể re-submit)
- **Tab Tất Cả**: Tất cả users với filter

═══════════════════════════════════════════════════════════════════

## 📋 PHẦN 5: QUẢN LÝ ĐƠN ĐẶT PHÒNG

**URL**: /admin/bookings.php

**Chức năng**:

1. **List Đơn Đặt Phòng**:
   - Lọc theo status (6 loại: pending/paid/accepted/completed/rejected/cancelled)
   - Phân trang
   - Hiển thị: khách hàng, phòng, giá, trạng thái, ngày đặt

2. **Update Status**:
   - Click "Cập Nhật Trạng Thái"
   - Chọn status mới
   - **ActivityLog**: Ghi lại (update_booking_status)
   - Thông báo khách + chủ trọ (email)

3. **View Chi Tiết**:
   - Khách hàng info
   - Phòng info
   - Giá + deposit
   - Số ngày thuê
   - Tình trạng payment
   - Lịch sử status updates

4. **Delete Booking**:
   - Click "🗑 Xóa"
   - **ActivityLog**: Ghi lại (delete_booking)

═══════════════════════════════════════════════════════════════════

## 💳 PHẦN 6: QUẢN LÝ THANH TOÁN

**URL**: /admin/payments.php

**Status Tracking**:
- pending: Chờ thanh toán
- held: Tiền được giữ
- released: Thanh toán cho chủ trọ ✓ **TÍNH COMMISSION 1%**
- refunded: Hoàn lại tiền

**Quy trình Commission**:

1. Khách hàng đặt phòng: 5.000.000 ₫
   ↓
2. Payment được tạo (status: pending)
   ↓
3. Khách thanh toán thành công (status: held)
   ↓
4. Admin vào Payments → Click "Release"
   ↓
5. Hệ thống tự động:
   - Status: held → released
   - Commission: 5.000.000 × 1% = 50.000 ₫
   - Tạo transaction record
   - **ActivityLog**: Ghi lại (update_payment_status)
   ↓
6. Admin vào "Doanh Thu Admin" → Xem 50.000 ₫ được thêm

**Chức năng**:

1. **List Thanh Toán**:
   - Lọc theo status
   - Phân trang
   - Hiển thị: booking ID, khách, phòng, số tiền, status, ngày

2. **View Chi Tiết**:
   - Thông tin payment
   - Booking info
   - Payment history
   - Commission info (nếu released)

3. **Update Status**:
   - Click "Cập Nhật Trạng Thái"
   - Chọn status mới
   - **Nếu chọn "Released"**:
     - Commission tính tự động
     - Transaction record tạo
     - ActivityLog ghi lại

═══════════════════════════════════════════════════════════════════

## 💰 PHẦN 7: DOANH THU ADMIN (1%)

**URL**: /admin/admin_revenue.php

**Thông Tin Hiển Thị**:

1. **4 Stat Cards** (Top):
   - 💰 Tổng Doanh Thu: Tất cả commission
   - 📈 Doanh Thu Tháng: Commission tháng hiện tại
   - ✓ Số Lần Nhận: Tổng số payment released
   - 📊 Trung Bình: Trung bình commission/lần

2. **Bảng Chi Tiết**:
   - ID Booking
   - Phòng Trọ (tên + chủ trọ)
   - Khách Hàng (tên + email)
   - Giá Phòng (deposit_amount)
   - Commission (1%)
   - Ngày Nhận

3. **Phân Trang**: 10 items/page

**Ví Dụ Tính Toán**:

```
Giá Phòng: 5.000.000 ₫

Commission Admin (1%): 50.000 ₫ ✓
Chủ Trọ Nhận (99%): 4.950.000 ₫ ✓

Total: 5.000.000 ₫ ✓
```

═══════════════════════════════════════════════════════════════════

## 📊 PHẦN 8: NHẬT KÝ HOẠT ĐỘNG (AUDIT TRAIL)

**URL**: /admin/activity_logs.php

**Mục đích**: Theo dõi tất cả hành động của admin

**Chức Năng Lọc** (6 bộ lọc):
1. Admin - Chọn admin nào thực hiện
2. Entity Type - Loại entity (motel, user, booking, payment, report, etc.)
3. Action - Hành động cụ thể (approve, delete, update, etc.)
4. Từ Ngày - Date from
5. Đến Ngày - Date to
6. Tìm Kiếm - Search by description

**Bảng Chi Tiết**:
- Thời Gian: Khi action xảy ra (với relative time)
- Admin: Tên admin thực hiện
- Hành Động: Tên action
- Thực Thể: Loại entity
- Mô Tả: Chi tiết action
- IP Address: IP admin
- [👁] View: Xem chi tiết

**Thống Kê** (4 stat cards):
- Tổng Logs (all time)
- Hôm Nay
- Tuần Này
- Tháng Này

**Chi Tiết Log** (activity_log_detail.php):
- Basic Info: ID, action, entity type, entity ID
- Admin Info: Tên, email, phone (link to user detail)
- Changes: Bảng so sánh old → new values
- Timestamp: Thời gian, relative time
- Network: IP address, user agent

**Các Action Được Ghi Lại**:

Motel:
- approve_motel: Admin duyệt phòng
- hide_motel: Admin ẩn phòng
- delete_motel: Admin xóa phòng

Payment:
- update_payment_status: Cập nhật status thanh toán

Booking:
- update_booking_status: Cập nhật status đơn đặt
- delete_booking: Xóa đơn đặt

Review:
- delete_review: Xóa đánh giá

User:
- delete_user: Xóa tài khoản

Category/District/Utility:
- create_category: Thêm danh mục
- update_category: Cập nhật danh mục
- delete_category: Xóa danh mục
- (Tương tự cho district & utility)

Reports:
- create_report: Tạo báo cáo (từ user)
- update_report_status: Cập nhật status báo cáo
- delete_report: Xóa báo cáo

User Approvals:
- approve_user: Duyệt user
- reject_user: Từ chối user
- block_user: Khóa user
- unblock_user: Mở khóa user

═══════════════════════════════════════════════════════════════════

## 📧 PHẦN 9: EMAIL NOTIFICATIONS

**Loại Email Được Gửi**:

1. **Owner Approval** ✅:
   - Tiêu đề: "✅ Tài khoản của bạn đã được duyệt"
   - Nội dung: 
     * Chúc mừng duyệt thành công
     * Các quyền mới: đăng tin, quản lý phòng, xem đơn đặt
     * Link đăng nhập
   - Trigger: Admin duyệt owner account

2. **Owner Rejection** ❌:
   - Tiêu đề: "❌ Tài khoản chủ trọ của bạn cần thêm thông tin"
   - Nội dung:
     * Thông báo bị từ chối
     * Lý do từ chối (nếu có)
     * Hướng dẫn cập nhật tài khoản
   - Trigger: Admin từ chối owner account

3. **Account Blocked** 🔒:
   - Tiêu đề: "🔒 Tài khoản của bạn đã bị khóa"
   - Nội dung:
     * Thông báo bị khóa
     * Lý do khóa (nếu có)
     * Liên hệ hỗ trợ
   - Trigger: Admin khóa account

**Email Fallback System**:
- Nếu SMTP chưa cấu hình → Lưu vào `email_logs` table
- Admin có thể xem lịch sử email
- Khi SMTP bật → Gửi thực sự

═══════════════════════════════════════════════════════════════════

## 📝 PHẦN 10: BÁO CÁO VÀ MODERATION

**URL**: /admin/reports.php

**Chức Năng**:

1. **List Báo Cáo**:
   - Lọc theo status (pending/investigating/resolved/rejected/closed)
   - Lọc theo loại (spam/inappropriate/fraud/unsafe/false_info/other)
   - Phân trang
   - 4 stat cards (tổng/pending/investigating/resolved)

2. **View Chi Tiết** (report_detail.php):
   - Tiêu đề + Loại báo cáo
   - Người báo cáo info
   - Người/Phòng bị báo cáo
   - Mô tả + Hình ảnh
   - Admin notes (nếu có)
   - Lịch sử xử lý

3. **Update Status**:
   - pending → investigating: Đang kiểm tra
   - investigating → resolved: Vấn đề được giải quyết
   - investigating → rejected: Báo cáo không hợp lệ
   - resolved/rejected → closed: Đóng case
   - Nhập admin_note khi cập nhật
   - **ActivityLog**: Ghi lại (update_report_status)

4. **Delete Report**:
   - Xóa báo cáo khỏi hệ thống
   - **ActivityLog**: Ghi lại (delete_report)

═══════════════════════════════════════════════════════════════════

## 🎛️ PHẦN 11: DANH MỤC, QUẬN, TIỆN NGHI

**URL**: /admin/categories.php | districts.php | utilities.php

**Chức Năng**:

1. **List** (phân trang):
   - Hiển thị: ID, tên, số phòng sử dụng, ngày tạo

2. **Create**:
   - Click "+ Thêm Mới"
   - Nhập tên
   - **ActivityLog**: Ghi lại (create_category)

3. **Update**:
   - Click nút "✏️ Sửa"
   - Chỉnh sửa tên
   - **ActivityLog**: Ghi lại (update_category)

4. **Delete**:
   - Click nút "🗑 Xóa"
   - Xóa khỏi hệ thống
   - **ActivityLog**: Ghi lại (delete_category)

═══════════════════════════════════════════════════════════════════

## 🔑 KEY FEATURES SUMMARY

✅ **ActivityLog Integration**: 
   - Tất cả admin action ghi log
   - Change tracking (old → new)
   - IP + User agent tracking
   - Advanced filtering & search

✅ **Email Notifications**:
   - Duyệt/Từ chối owner → email
   - Fallback logging nếu SMTP chưa cấu hình
   - HTML formatted templates

✅ **Commission System**:
   - 1% auto-calculation
   - Trigger khi payment.status='released'
   - Transaction tracking

✅ **Owner Status Workflow**:
   - pending → approved/rejected
   - blocked state
   - Email notifications

✅ **Full Audit Trail**:
   - 6-filter advanced search
   - Per-action logging
   - Change comparison view

═══════════════════════════════════════════════════════════════════

## 🚨 TROUBLESHOOTING

**Q: Email không gửi được?**
A: Kiểm tra:
   - SMTP cấu hình
   - Email logs table tạo
   - Fallback system lưu email vào table

**Q: ActivityLog chưa ghi log?**
A: Kiểm tra:
   - ActivityLog được pass vào controller
   - Table activity_logs tồn tại
   - Admin role chính xác

**Q: Commission không tính?**
A: Kiểm tra:
   - Payment status='released'
   - Booking có deposit_amount
   - Transactions table tồn tại

**Q: Owner không thể posting?**
A: Kiểm tra:
   - User status='approved' (không phải pending/rejected/blocked)
   - Role='owner' (không admin hoặc user)
   - OwnerStatusMiddleware check

═══════════════════════════════════════════════════════════════════

## 📱 RESPONSIVE DESIGN

✅ Desktop: Tất cả tính năng
✅ Tablet: Responsive layout
✅ Mobile: Optimized view

═══════════════════════════════════════════════════════════════════

**Ready to Use!** 🚀

Tất cả tính năng đã hoàn chỉnh và sẵn sàng production.

═══════════════════════════════════════════════════════════════════
