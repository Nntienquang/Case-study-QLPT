# Admin và Login - chức năng hiện có

## Login chung

- `public/login.php` là form đăng nhập chung cho `admin`, `owner`, `user`.
- Sau khi đăng nhập thành công, hệ thống redirect theo vai trò:
  - `admin` -> `public/admin/index.php`
  - `owner` -> `public/owner/dashboard.php`
  - `user` -> `public/user/dashboard.php`
- Có kiểm tra:
  - email và mật khẩu
  - trạng thái tài khoản `blocked`
  - owner bị `rejected`
  - captcha ảnh
  - slider captcha sau nhiều lần đăng nhập sai
- Session được set cả hai biến:
  - `$_SESSION['role']`
  - `$_SESSION['user_role']`
- `public/admin/login.php` không còn form riêng, chỉ redirect về form login chung.
- `public/logout.php` logout tài khoản public.
- `public/admin/logout.php` logout admin và redirect về login chung.

## Phân quyền admin

- Các route admin dùng `public/admin_init.php`.
- `Auth::isLoggedIn()` chỉ cho qua nếu:
  - có `$_SESSION['user_id']`
  - có `$_SESSION['user_role']`
  - `user_role === admin`
- Một số route admin kiểm tra thêm `role` hoặc `user_role`, login chung hiện đã set đủ cả hai.

## Admin dashboard

File: `public/admin/index.php`

- Hiển thị tổng quan vận hành:
  - owner chờ duyệt
  - phòng cần kiểm duyệt
  - báo cáo chưa xử lý
  - lịch xem đang chờ
  - thanh toán cần theo dõi
  - tổng phòng trọ
  - tin đang chờ duyệt
  - tổng người dùng
  - đánh giá đã ghi nhận
  - booking đã nhận
  - booking chờ xử lý
  - doanh thu tháng này
  - tổng commission
- Có link nhanh sang:
  - duyệt phòng
  - doanh thu
  - quản lý booking

## Quản lý owner

File: `public/admin/user_approvals.php`

- Xem danh sách tài khoản owner.
- Lọc theo trạng thái.
- Tìm kiếm theo tên, email, số điện thoại.
- Duyệt owner.
- Từ chối owner kèm lý do.
- Ghi activity log khi duyệt/từ chối.

## Quản lý người dùng

Files:

- `public/admin/users.php`: danh sách
- `public/admin/user_create.php`: tạo mới
- `public/admin/user_edit.php`: chỉnh sửa và reset mật khẩu
- `public/admin/user_detail.php`: chi tiết

- Xem danh sách tài khoản.
- Lọc theo vai trò:
  - admin
  - owner
  - user
- Lọc theo trạng thái:
  - approved
  - pending
  - blocked
  - rejected
- Tìm kiếm theo tên, email, số điện thoại.
- Thêm tài khoản mới ở route riêng.
- Sửa thông tin tài khoản:
  - tên
  - email
  - số điện thoại
  - vai trò
  - trạng thái
- Khóa/mở khóa tài khoản.
- Reset mật khẩu tài khoản ở màn hình edit riêng.
- Xóa tài khoản, trừ tài khoản đang đăng nhập.
- Xem chi tiết tài khoản qua `user_detail.php`.
- Ghi activity log cho thao tác tạo/sửa/xóa/reset/trạng thái.

## Chi tiết người dùng

File: `public/admin/user_detail.php`

- Xem hồ sơ tài khoản.
- Xem vai trò và trạng thái.
- Với owner đang `pending`:
  - duyệt owner
  - từ chối owner kèm lý do
- Xem phòng liên quan.
- Xem booking liên quan.
- Xem đánh giá người dùng đã viết.

## Quản lý phòng trọ

File: `public/admin/motels.php`

- Admin không thêm/sửa tin phòng.
- Tin phòng do owner đăng.
- Admin có thể:
  - xem danh sách phòng
  - lọc theo trạng thái
  - xem chi tiết phòng
  - duyệt tin đang chờ
  - phục hồi tin đã ẩn về trạng thái duyệt
  - ẩn tin
  - xóa tin

## Chi tiết phòng trọ

File: `public/admin/motel_detail.php`

- Xem thông tin phòng:
  - tiêu đề
  - địa chỉ
  - mô tả
  - giá
  - diện tích
  - danh mục
  - quận/khu vực
  - lượt xem
  - trạng thái
- Xem chủ phòng:
  - tên
  - email
  - số điện thoại
- Xem hình ảnh phòng.
- Xem tiện nghi phòng.
- Admin có thể:
  - duyệt/phục hồi phòng
  - ẩn phòng
  - xóa phòng

## Quản lý booking

File: `public/admin/bookings.php`

- Xem danh sách booking.
- Lọc theo trạng thái:
  - pending
  - paid
  - accepted
  - completed
- Xem chi tiết booking.
- Xóa booking.
- Xử lý nhanh trên danh sách:
  - chấp nhận booking pending
  - từ chối booking pending
  - đánh dấu hoàn tất booking paid/accepted

## Chi tiết booking

File: `public/admin/booking_detail.php`

- Xem thông tin booking:
  - mã booking
  - người thuê
  - phòng trọ
  - tiền cọc
  - ngày check-in
  - ngày tạo
  - trạng thái
- Cập nhật trạng thái booking:
  - pending
  - paid
  - accepted
  - completed
  - rejected
  - cancelled
- Xóa booking.

## Quản lý thanh toán

File: `public/admin/payments.php`

- Xem danh sách thanh toán.
- Lọc theo trạng thái:
  - pending
  - held
  - released
  - refunded
- Xem chi tiết thanh toán.
- Xử lý nhanh trên danh sách:
  - chuyển pending sang held
  - giải ngân pending/held sang released
  - hoàn tiền pending/held sang refunded

## Chi tiết thanh toán

File: `public/admin/payment_detail.php`

- Xem thông tin thanh toán:
  - mã thanh toán
  - người thuê
  - phòng trọ
  - số tiền
  - phí
  - phương thức
  - mã giao dịch
  - trạng thái
- Cập nhật trạng thái:
  - pending
  - held
  - released
  - refunded

## Doanh thu admin

File: `public/admin/admin_revenue.php`

- Xem thống kê commission:
  - tổng doanh thu
  - doanh thu tháng này
  - số lần nhận commission
  - trung bình mỗi commission
- Xem danh sách commission theo booking.
- Hiển thị:
  - booking
  - phòng trọ
  - chủ phòng
  - khách hàng
  - giá phòng
  - commission
  - ngày nhận

## Quản lý báo cáo

File: `public/admin/reports.php`

- Xem thống kê báo cáo:
  - tổng báo cáo
  - chờ xử lý
  - đang xác minh
  - đã xử lý
- Lọc theo trạng thái.
- Lọc theo loại báo cáo.
- Xem danh sách báo cáo.
- Cập nhật trạng thái báo cáo:
  - investigating
  - resolved
  - rejected
  - closed
- Ghi chú xử lý của admin.
- Xóa báo cáo.
- Ghi activity log.

## Chi tiết báo cáo

File: `public/admin/report_detail.php`

- Xem nội dung báo cáo.
- Xem người báo cáo.
- Xem đối tượng bị báo cáo hoặc phòng liên quan.
- Xem ghi chú xử lý.
- Xem người xử lý.

## Quản lý đánh giá

File: `public/admin/reviews.php`

- Xem danh sách đánh giá.
- Xem chi tiết đánh giá.
- Xóa đánh giá.

File: `public/admin/review_detail.php`

- Xem thông tin đánh giá:
  - người dùng
  - phòng trọ
  - điểm đánh giá
  - nội dung nhận xét
  - ngày tạo

## Danh mục, quận, tiện nghi

Files:

- `public/admin/categories.php`: danh sách
- `public/admin/category_create.php`: tạo mới
- `public/admin/category_edit.php`: chỉnh sửa
- `public/admin/category_detail.php`: chi tiết

- Thêm danh mục.
- Sửa danh mục.
- Xóa danh mục.
- Xem danh sách danh mục.

Files:

- `public/admin/districts.php`: danh sách
- `public/admin/district_create.php`: tạo mới
- `public/admin/district_edit.php`: chỉnh sửa
- `public/admin/district_detail.php`: chi tiết

- Thêm quận/khu vực.
- Sửa quận/khu vực.
- Xóa quận/khu vực.
- Xem danh sách quận/khu vực.

Files:

- `public/admin/utilities.php`: danh sách
- `public/admin/utility_create.php`: tạo mới
- `public/admin/utility_edit.php`: chỉnh sửa
- `public/admin/utility_detail.php`: chi tiết

- Thêm tiện nghi.
- Sửa tiện nghi.
- Xóa tiện nghi.
- Xem danh sách tiện nghi.

## Nhật ký hoạt động

File: `public/admin/activity_logs.php`

- Xem danh sách activity log.
- Lọc theo admin.
- Lọc theo entity type.
- Lọc theo action.
- Lọc theo ngày.
- Xem IP và user agent.

File: `public/admin/activity_log_detail.php`

- Xem chi tiết log.
- Xem admin thực hiện.
- Xem mô tả.
- Xem dữ liệu cũ/mới dạng JSON nếu có.

## Layout admin

File: `public/admin/layout.php`

- Header admin.
- Sidebar điều hướng.
- Flash message:
  - success
  - error
  - warning
- Helper hiển thị:
  - escape HTML
  - format tiền
  - nhãn trạng thái
  - màu pill trạng thái

## Dữ liệu hiện tại trong database local

Kết quả kiểm tra gần nhất:

- `users`: 6
- `owners`: 2
- `categories`: 3
- `districts`: 3
- `motels`: 0
- `bookings`: 0
- `payments`: 0
- `reports`: 0
- `reviews`: 0

Vì `motels`, `bookings`, `payments`, `reports`, `reviews` đang bằng 0 nên nhiều màn hình admin đang trống là do chưa có dữ liệu nghiệp vụ, không nhất thiết là lỗi route.

## Lưu ý nghiệp vụ

- Admin không đăng tin phòng.
- Admin không sửa nội dung tin phòng owner đã đăng.
- Owner tạo và sửa tin phòng.
- Admin chỉ kiểm duyệt, ẩn/phục hồi, xóa và xử lý báo cáo liên quan đến tin phòng.
- Admin có thể tạo/sửa tài khoản để vận hành hệ thống.
- Admin có thể reset mật khẩu tài khoản khi cần hỗ trợ.
