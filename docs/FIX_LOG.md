# Fix Log

## Product Foundation

- Tách trạng thái owner verification khỏi trạng thái tài khoản đăng nhập.
- Thêm backend guard cho toàn bộ owner workspace.
- Chuyển booking sang flow có mã booking, payment pending và hold phòng.
- Thêm màn `user/payment.php` để theo dõi/chuyển trạng thái thanh toán manual gateway-ready.
- Admin payment quản lý `payment_status` mới: pending, processing, paid, failed, cancelled, refunded.
- Owner chỉ được chấp nhận booking sau khi payment đã paid.
- Root `login.php` chỉ redirect về login chính trong `public/login.php`.
- Public/user nav đã đồng bộ bằng component `PublicNav.php`.

## Database

Migration nền tảng nằm tại `database/sql_updates/update_v2_product_foundation.sql`.
User đã chạy migration và export lại `phongtro_db.sql`.
