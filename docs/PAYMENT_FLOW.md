# Payment Flow

## Current Flow

1. User tạo booking ở `user/checkout.php`.
2. Hệ thống tạo:
   - `bookings.booking_code`
   - `bookings.booking_status = waiting_payment`
   - `bookings.payment_status = pending`
   - `payments.payment_code`
   - `payments.payment_status = pending`
   - `booking_room_holds.hold_status = active`
3. User mở `user/payment.php` và chuyển khoản theo `payment_code`.
4. User bấm "Tôi đã chuyển khoản", payment chuyển sang `processing`.
5. Admin vào `admin/payments.php` kiểm tra và xác nhận `paid` hoặc `failed`.
6. Khi `paid`, booking chuyển `booking_status = paid`, legacy `status = paid`.
7. Owner vào `owner/bookings.php` chấp nhận booking đã thanh toán.
8. Khi khách dọn vào, owner hoàn tất booking và tiền cọc được ghi nhận vào ví owner.

## Gateway-Ready Fields

- `payments.payment_code`
- `payments.payment_method`
- `payments.transaction_code`
- `payments.gateway_response`
- `payments.paid_at`

MoMo QR/webhook sau này sẽ cập nhật các trường này thay cho thao tác admin thủ công.
