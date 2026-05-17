# Role Policy

## Actors

- Admin: vận hành hệ thống, duyệt owner, duyệt phòng, kiểm tra booking/payment/report.
- Owner: chủ phòng. Được login sau đăng ký nhưng chỉ dùng workspace sau khi hồ sơ xác minh được duyệt.
- User: người thuê. Được tìm phòng, booking, thanh toán cọc, theo dõi đơn.

## Owner Verification

Owner account có hai lớp trạng thái:

- `users.status`: trạng thái tài khoản đăng nhập (`approved`, `blocked`).
- `users.owner_verification_status`: trạng thái hồ sơ owner.

Trạng thái hồ sơ:

- `pending_verification`: owner chưa gửi đủ hồ sơ.
- `submitted`: owner đã gửi hồ sơ, chờ admin duyệt.
- `approved`: owner được dùng workspace.
- `rejected`: owner bị từ chối, được cập nhật và gửi lại.

Backend chặn bằng `public/owner/_owner_guard.php` và `core/OwnerStatusMiddleware.php`. Owner chưa approved chỉ được vào `profile.php` và `settings.php`.
