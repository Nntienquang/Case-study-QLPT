# Test Flow

## Accounts

- Admin: `admin123@gmail.com`
- Demo owner approved: `demo.owner@qlpt.test` / `123456`
- Demo owner pending: `demo.owner.pending@qlpt.test` / `123456`
- Demo user: `demo.user@qlpt.test` / `123456`
- Demo user 2: `demo.user2@qlpt.test` / `123456`

## End-to-End

1. Guest mở `public/index.php`, `public/phongtro.php`, `public/user/search.php`.
2. Guest xem chi tiết phòng, bấm booking thì bị yêu cầu login.
3. User login, vào chi tiết phòng, tạo booking ở `user/checkout.php`.
4. Hệ thống chuyển sang `user/payment.php`.
5. User bấm "Tôi đã chuyển khoản".
6. Admin vào `admin/payments.php`, xác nhận `paid`.
7. Owner approved vào `owner/bookings.php`, chấp nhận booking.
8. Owner đánh dấu khách đã dọn vào.
9. User kiểm tra `user/my-bookings.php`.
10. Admin kiểm tra `admin/bookings.php`, `admin/payments.php`, `admin/admin_revenue.php`.

## Owner Verification

1. Owner mới đăng ký.
2. Login owner được redirect sang `owner/profile.php?verify=1`.
3. Bổ sung phone, address, CCCD trước/sau, selfie, ngân hàng.
4. Hồ sơ chuyển `submitted`.
5. Admin vào `admin/user_approvals.php`, duyệt hoặc từ chối.
6. Nếu approved, owner dùng được dashboard/listings/add-listing/bookings.
