# Bao cao ra soat theo check.txt

Ngay ra soat: 2026-05-15

## Ket luan nhanh

Project da co nen admin/login dung duoc de demo, nhung chua dat muc "admin 100%" theo check.txt. Cac phan login chung, route admin, duyet/quan ly co ban da co. Cac diem can lam tiep de dat chuan gom: CSRF toan bo action, chuyen action nguy hiem tu GET sang POST, login-attempt luu DB, bat doi mat khau sau admin reset, tu choi tin phong kem ly do, an mem review, dashboard chart va module thong tin lien he.

## 1. Login/logout

Trang thai: Dat mot phan tot.

Da co:
- `public/login.php` dung chung cho admin/owner/user.
- Redirect theo role:
  - admin -> `public/admin/index.php`
  - owner -> `public/owner/dashboard.php`
  - user -> `public/user/dashboard.php`
- Session set cac key `user_id`, `role`, `user_role`, `user_name`.
- `public/admin/login.php` chi redirect ve login chung.
- `public/logout.php` va `public/admin/logout.php` huy session va redirect ve login chung.
- Co `session_regenerate_id(true)` sau login thanh cong.
- `public/admin_init.php` set no-store cache, giam rui ro Back lai admin sau logout.

Con thieu/rui ro:
- Login-attempt hien luu trong session, chua luu DB nen doi browser/session la mat lich su.
- `core/Auth.php` van con query login admin bang `real_escape_string`; route moi dang dung `public/login.php` prepared statement, nhung code cu van la rui ro neu duoc goi lai.

Moi bo sung:
- Form login da co CSRF token qua `core/Csrf.php`.
- Admin reset password da set `users.force_password_change = 1`.
- Sau login, user bi bat vao `public/change-password.php` neu `force_password_change = 1`.

## 2. Captcha login

Trang thai: Dat mot phan.

Da co:
- Captcha khong hien ngay lan dau.
- Theo doi sai theo email + IP bang session.
- Sau 3 lan sai thi lan tiep theo bat buoc captcha.
- Captcha validate server-side, co TTL 5 phut.
- Sai captcha thi tao captcha moi va khong check password.
- Sai nhieu lan thi lock tam 1/5/15 phut.
- Login thanh cong reset failed/captcha/lock.
- Ghi log success/failed vao `activity_logs` neu insert duoc.

Con thieu/rui ro:
- Chua co `login_attempts` va `login_security_state` trong DB.
- Chua co Turnstile/reCAPTCHA, moi co captcha noi bo fallback.
- Can chuyen log failed unauthenticated sang `admin_id` nullable/NULL de tranh FK loi neu schema rang buoc.

## 3. Admin middleware/route

Trang thai: Dat phan nen.

Da co:
- Tat ca file PHP trong `public/admin` hien deu include `../admin_init.php`.
- `admin_init.php` dung `Auth::isLoggedIn()` de chan nguoi khong phai admin.
- Neu session admin co status `blocked` thi logout va redirect login.

Con thieu/rui ro:
- Mot so page con check role rieng lap lai, nen chua gon nhung khong phai loi nghiem trong.
- Action nguy hiem o nhieu trang van xu ly bang GET, vi du:
  - `bookings.php?action=update_status/delete`
  - `payments.php?action=update_status`
  - `categories.php?action=delete`
  - `districts.php?action=delete`
  - `utilities.php?action=delete`
- `reviews.php?action=delete`
- Cac action nay can doi sang POST + CSRF.

Moi bo sung:
- `users.php` da doi delete/status sang POST + CSRF.
- `motels.php` da doi approve/hide/reject/delete sang POST + CSRF.
- `bookings.php` va `booking_detail.php` da doi update/delete booking sang POST + CSRF.
- `payments.php` va `payment_detail.php` da doi update payment sang POST + CSRF.
- `categories.php`, `districts.php`, `utilities.php` da doi delete sang POST + CSRF.
- `reviews.php` va `review_detail.php` da doi delete review sang POST + CSRF.
- `reports.php` va `report_detail.php` da them CSRF cho update report, bat buoc ghi chu khi resolved/rejected/closed.

## 4. Admin user/account

Trang thai: Dat mot phan.

Da co:
- Tach route dung hon:
  - `users.php`
  - `user_create.php`
  - `user_edit.php`
  - `user_detail.php`
- List/search/filter role/status.
- Admin them/sua/lock/unlock/reset/delete/detail.
- Khong cho tu xoa chinh minh trong controller.
- Khong cho sua role/status cua chinh minh tren UI edit.
- Co log cho create/update/reset/status/delete.

Con thieu/rui ro:
- Chua chan "ha quyen admin cuoi cung" day du trong moi luong.

Moi bo sung:
- Status/delete user da doi sang POST + CSRF.
- Reset password da set `force_password_change`.

## 5. Duyet owner

Trang thai: Dat mot phan.

Da co:
- `user_approvals.php` list owner, loc pending/approved/rejected/all, approve/reject kem ly do.
- `users.rejection_reason` da co trong DB.
- Owner rejected bi chan login va co thong bao sau khi xac thuc.
- Co log approve/reject.

Con thieu/rui ro:
- Chua co tab/filter `blocked` rieng trong `user_approvals.php`.
- Approve/reject POST chua co CSRF.
- Lock/unlock owner dang nam o quan ly users, chua tich hop ro trong man hinh duyet owner.

## 6. Quan ly phong/tin dang

Trang thai: Dat mot phan, da sua dung nghiep vu "admin khong them/sua tin".

Da co:
- `motels.php` chi list/filter/detail/approve/hide/restore/delete.
- `motel_detail.php` hien thong tin chi tiet, anh, tien nghi, chu phong, trang thai.
- Admin khong co route create/edit phong.
- Co log approve/hide/delete.

Con thieu/rui ro:
- Owner chua co UI xem ly do tu choi tin.

Moi bo sung:
- Bang `motels` da duoc migrate co `rejection_reason`, `rejected_by`, `rejected_at`, va status `rejected`.
- Admin da co reject tin phong kem ly do tu `motels.php`/`motel_detail.php`.
- Approve/hide/reject/delete tin phong da doi sang POST + CSRF.

## 7. Booking/payment

Trang thai: Dat mot phan.

Da co:
- `bookings.php`, `booking_detail.php` list/filter/detail/update/delete.
- `payments.php`, `payment_detail.php` list/filter/detail/update status.
- Co log qua controller/core o cac action chinh.

Moi bo sung:
- Update/delete booking da doi sang POST + CSRF o list va detail.
- Update payment da doi sang POST + CSRF o list va detail.
- Payment flow da chan pending -> held/refunded, held -> released/refunded; khong cho released truc tiep tu pending.

## 8. Bao cao/review

Trang thai: Dat mot phan.

Bao cao da co:
- List/filter status/type, detail.
- Cap nhat investigating/resolved/rejected/closed.
- Luu `handled_by`, `handled_at`, `admin_note`.
- Co activity log.

Bao cao con thieu:
- Check.txt noi `processed_by/processed_at`, DB hien dung `handled_by/handled_at`. Nen giu ten cu de khong pha code, hoac them alias column neu bat buoc theo tai lieu.
- Delete report hien chua co nut UI rieng tren list, nhung code server van co nhanh xu ly.

Review da co:
- List/detail/delete.

Review con thieu:
- Chua co an mem review.
- Bang `reviews` chua co `status`.

Moi bo sung:
- Delete review da doi sang POST + CSRF o list va detail.

## 9. Danh muc/khu vuc/tien nghi

Trang thai: Dat mot phan.

Da co:
- Tach route MVC-like:
  - `categories.php`, `category_create.php`, `category_edit.php`, `category_detail.php`
  - `districts.php`, `district_create.php`, `district_edit.php`, `district_detail.php`
  - `utilities.php`, `utility_create.php`, `utility_edit.php`, `utility_detail.php`
- Co validate trung ten trong controller/model hien tai.

Moi bo sung:
- Create/edit/delete danh muc, quan, tien nghi da co CSRF.
- Delete danh muc, quan, tien nghi da doi sang POST.

Con thieu/rui ro:
- Can kiem tra/chan xoa neu dang duoc motel su dung.

## 10. Dashboard/UI

Trang thai: Dat mot phan.

Da co:
- Dashboard lay so lieu tu DB, khong hardcode.
- Co empty state o cac list.
- Sidebar/header layout thong nhat.
- Badge trang thai co mau.

Con thieu:
- Chua co bieu do doanh thu/booking/phong theo trang thai.
- Breadcrumb chua thong nhat.
- Mot so text bi mojibake/loi encoding o file admin cu, can chuan hoa UTF-8.

## 11. Database

Da co:
- `activity_logs`.
- `users.rejection_reason`.
- `reports.handled_by`, `reports.handled_at`.
- `users.reset_token`, `users.reset_expires`.

Da bo sung sau khi chay `SQL_ADMIN_SECURITY_HARDENING.sql`:
- `login_attempts`.
- `login_security_state`.
- `password_resets` tach rieng, an toan hon reset_token trong users.
- `users.force_password_change`.
- `motels.rejection_reason`, `motels.rejected_by`, `motels.rejected_at`, va status `rejected`.
- `reviews.status` de an mem.

SQL de bo sung da tao o: `SQL_ADMIN_SECURITY_HARDENING.sql`.

## 12. Bao mat chung

Trang thai: Chua dat day du.

Dat:
- Login moi dung prepared statement va `password_verify`.
- Dang ky/reset trong `AuthController` co dung `password_hash`, `password_verify`, prepared statement o cac query chinh.
- Admin route bi chan server-side qua `admin_init.php`.

Chua dat:
- Nhieu query trong core/controller/admin van dung string SQL + escape thu cong.
- Nhieu action thay doi du lieu dung GET.
- Chua test het flow demo vi DB local dang it du lieu nghiep vu.

## 13. Kiem tra da chay

- `php -l public/login.php`: OK.
- `php -l core/Captcha.php`: OK.
- `php -l public/admin_init.php`: OK.
- `php -l public/logout.php`: OK.
- `php -l public/admin/logout.php`: OK.
- `php -l public/admin/*.php`: OK o lan kiem tra truoc.
- `php -l app/controller/*.php`: OK o lan kiem tra truoc.
- HTTP smoke:
  - `public/login.php`: 200 OK.
  - `public/admin/login.php`: 302 redirect ve login chung.
- DB schema da doi chieu bang `SHOW COLUMNS`.

## 14. Viec nen lam tiep theo thu tu uu tien

1. Gan CSRF cho cac POST public con lai: register, forgot/reset, owner/user settings.
2. Chuyen login-attempt tu session sang DB.
3. Owner xem ly do tu choi tin phong.
4. Hoan thien `password_resets` tach bang neu muon thay the reset_token trong users.
5. Bo sung hide/restore review thay vi chi delete.
6. Kiem tra/chan xoa category/district/utility neu dang duoc motel su dung.
7. Them chart dashboard va module contact/site settings neu can demo day du.
