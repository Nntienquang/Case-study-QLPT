# Roadmap System

## Done In V2 Foundation

- Owner verification status riêng.
- Route guard backend cho owner workspace.
- Booking/payment schema gateway-ready.
- Room hold chống double booking cơ bản.
- Payment tự ghi nhận thành công khi khách xác nhận chuyển khoản hoặc khi gateway callback trả về; admin giữ tiền và chỉ giải ngân sau khi khách xác nhận nhận phòng.
- Public nav đồng bộ cho các trang user.

## Next Product Hardening

- Tự động expire `booking_room_holds` quá hạn bằng cron hoặc request hook.
- Tách bảng cấu hình payment provider.
- Upload validation nâng cao: dung lượng, mime type, virus scan.
- Foreign key đầy đủ sau khi dữ liệu cũ sạch hoàn toàn.
- Chuẩn hóa encoding các file cũ còn mojibake.
- Chuẩn hóa shared owner/admin sidebar component để giảm trùng lặp.

## Public Demo Criteria

- Import `phongtro_db.sql` sạch.
- Có dữ liệu mẫu đầy đủ cho 3 actor.
- Toàn bộ PHP syntax pass.
- Guest/user/owner/admin flow test theo `docs/TEST_FLOW.md`.
