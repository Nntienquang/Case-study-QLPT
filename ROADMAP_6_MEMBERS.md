# Roadmap 6 Thanh Vien - QuanLyPhongTro PHP Thuan

## Muc Tieu

Hoan thien he thong quan ly phong tro voi 3 actor ro rang:

- Admin web: quan tri nguoi dung, owner, phong, booking, thanh toan, bao cao.
- Owner: dang phong, quan ly phong, xem booking, doanh thu, ho so.
- User: tim phong, xem chi tiet, luu yeu thich, dat phong, quan ly booking.

Giu PHP thuan, MySQL, Bootstrap/CSS/JS thuan. Khong chuyen sang framework backend.

## Phase 1 - Nen Tang Chay On Dinh

### Thanh vien 1: Database va migration

- Duy tri `phongtro_db.sql` va cac migration trong `migrations/`.
- Chuan hoa `users.role`: `admin`, `owner`, `user`.
- Chuan hoa `users.status`: `pending`, `approved`, `rejected`, `blocked`.
- Dam bao co bang: `activity_logs`, `reports`, `email_logs`, `favorites`, `wallets`, `transactions`.
- Dong bo cot booking: uu tien `check_in_date`, `check_out_date`; loai dan `checkin_date`.
- Tao seed du lieu dung hash password.

### Thanh vien 2: Auth, role va middleware

- Hoan thien login/register/logout/reset password.
- User dang ky xong la `approved`.
- Owner dang ky xong la `pending`, chi admin duyet moi duoc dang phong.
- Admin khong dang ky tu form public.
- Kiem tra redirect:
  - `admin` -> `public/admin/index.php`
  - `owner` -> `public/owner/dashboard.php`
  - `user` -> `public/user/dashboard.php`
- Them CSRF token cho form POST quan trong.

## Phase 2 - Module Theo Actor

### Thanh vien 3: Admin web

- Dashboard tong quan: user, owner pending, phong pending, booking, doanh thu.
- Quan ly owner: duyet, tu choi, khoa, mo khoa, xem ly do.
- Quan ly phong: duyet/an/xoa/xem chi tiet.
- Quan ly danh muc, quan/huyen, tien nghi.
- Quan ly booking/payment/report/review.
- Activity log cho hanh dong quan tri.

### Thanh vien 4: Owner

- Dashboard owner: tong phong, luot xem, booking cho xu ly, doanh thu.
- CRUD phong tro: thong tin, gia, dien tich, tien nghi, hinh anh.
- Danh sach booking theo phong, cap nhat trang thai hop le.
- Doanh thu theo thang, lich su giao dich.
- Ho so owner, doi mat khau, thong bao trang thai duyet.

### Thanh vien 5: User

- Trang tim kiem phong: keyword, quan, danh muc, gia, dien tich, tien nghi.
- Chi tiet phong: hinh anh, tien nghi, review, thong tin owner, dat phong.
- Luu yeu thich va bo yeu thich bang AJAX.
- Dat phong: ngay vao/ra, tien coc, ghi chu, trang thai booking.
- Quan ly booking ca nhan, huy booking theo dieu kien.

## Phase 3 - UI/UX Hien Dai

### Thanh vien 6: Giao dien va frontend thuan

- Thiet ke lai design system dung chung: mau, typography, button, form, table, badge, card.
- Tao layout dung chung cho admin, owner, user neu phu hop voi PHP include.
- Sua mojibake tieng Viet trong cac file bi loi encoding.
- Responsive mobile/tablet/desktop.
- Them empty state, loading state, confirm modal, toast message.
- Dam bao navigation khong tro den file khong ton tai.

## Quy Tac Lam Viec

- Moi task di kem test bang `php -l` va smoke test tren browser.
- Moi thay doi DB phai co file migration.
- Khong viet SQL noi chuoi voi input nguoi dung; dung prepared statement.
- Khong them framework backend.
- Giao dien co the dung Bootstrap 5, Font Awesome, CSS/JS thuan.
- Trang public, user, owner, admin phai tach ro luong va quyen.

## Definition Of Done

- Dang ky/dang nhap dung cho ca 3 actor.
- Admin duyet owner va phong thanh cong.
- Owner approved them/sua/an phong duoc.
- User tim phong, xem chi tiet, dat phong, luu yeu thich duoc.
- Khong con link 404 trong menu chinh.
- Database moi import xong chay duoc khong can sua tay.
- UI dong bo, hien dai, doc duoc tieng Viet dung encoding UTF-8.

## Phuong An Chia 6 Nguoi Khong Dong Admin

Phan nay dung khi admin da co nguoi khac lam hoac team muon tach hoan toan khoi `public/admin`.
Moi thanh vien chi can lam luong public, user, owner va core dung chung cua module minh.

### Nguyen tac tach viec

- Khong sua file trong `public/admin/` va `app/controller/*Admin*`.
- Uu tien bang va trang da co trong `phongtro_db.sql`: `viewing_appointments`, `conversations`, `messages`, `maintenance_requests`, `contracts`, `monthly_bills`, `monthly_invoices`.
- Moi module phai co it nhat mot trang tao du lieu va mot trang theo doi/cap nhat du lieu.
- Neu can notification thi dung `core/NotificationHelper.php`, khong tao alert rieng le roi bo mat trang lich su.
- Thong nhat navigation user/owner truoc khi merge de tranh moi nguoi them sidebar theo mot kieu.

### Thanh vien 1: Lich xem phong hai chieu

- Actor: User va Owner.
- Muc tieu: user dat lich xem, theo doi trang thai; owner chap nhan, tu choi, danh dau da xem.
- Da co diem bat dau:
  - User tao lich tu `public/user/motel-detail.php`.
  - Owner xu ly lich tai `public/owner/viewing-appointments.php`.
- Can bo sung:
  - Tao trang `public/user/viewing-appointments.php` cho user xem lich da gui va huy lich `pending`.
  - Them filter lich sap toi/lich cu va thong bao khi owner doi trang thai.
  - Hien lich sap toi tren user dashboard bang link dung, khong tro ve booking.
- Definition of Done:
  - User tao, xem, huy lich chua duoc owner xu ly.
  - Owner cap nhat trang thai va user nhan notification.
  - Khong co link `user/viewing-appointments.php` bi 404.

### Thanh vien 2: Lead inbox va nhan tin owner

- Actor: User va Owner.
- Muc tieu: user hoi chu phong theo tung tin; owner gom lead vao inbox.
- Da co diem bat dau:
  - User gui tin tu `public/user/motel-detail.php` va xem hoi thoai tai `public/user/messages.php`.
  - Schema da co `conversations` va `messages`.
- Can bo sung:
  - Tao `public/owner/messages.php` de owner xem hoi thoai, phong lien quan va tra loi.
  - Danh dau `messages.read_at`, dem tin chua doc va dua inbox vao owner navigation.
  - Kiem tra owner chi doc hoi thoai cua minh, user chi doc hoi thoai cua minh.
- Definition of Done:
  - Mot hoi thoai user-owner-motel gui nhan hai chieu duoc.
  - Notification tin moi tro den trang ton tai.
  - Query khong lo tin nhan cua owner khac.

### Thanh vien 3: Bao tri tu phia nguoi thue

- Actor: User va Owner.
- Muc tieu: phong da co booking hop le co kenh bao su co sau khi vao o.
- Da co diem bat dau:
  - Owner da co bang xu ly tai `public/owner/maintenance.php`.
  - Schema da co `maintenance_requests`.
- Can bo sung:
  - Tao `public/user/maintenance.php` de user tao ticket tu booking du dieu kien.
  - Cho user xem trang thai `open`, `in_progress`, `resolved` va anh dinh kem neu co.
  - Gui notification cho owner khi co ticket moi va cho user khi ticket duoc cap nhat.
- Definition of Done:
  - User khong the tao ticket cho phong khong phai booking cua minh.
  - Owner cap nhat trang thai va user thay lich su trang thai hien tai.
  - Ticket moi hien trong owner dashboard/maintenance queue.

### Thanh vien 4: Hop dong va ban giao

- Actor: Owner va User.
- Muc tieu: doi booking da nhan thanh ho so thue co ngay bat dau, ngay ket thuc va tai lieu.
- Da co diem bat dau:
  - Bang `contracts` va trang khung `public/owner/contracts.php`.
- Can bo sung:
  - Owner tao hop dong tu booking `accepted` hoac `completed`.
  - User co trang xem hop dong cua minh, trang thai ky va ngay sap het han.
  - Them checklist ban giao co ban: tien coc, ngay vao o, ghi chu hien trang phong.
- Definition of Done:
  - Hop dong chi gan dung owner, user, motel, booking lien quan.
  - Hop dong sap het han duoc loc rieng.
  - Nut dang demo/alert trong trang owner duoc thay bang thao tac that.

### Thanh vien 5: Hoa don dien nuoc cho nguoi thue

- Actor: Owner va User.
- Muc tieu: owner chot so hang thang, user xem duoc chi tiet hoa don va trang thai.
- Da co diem bat dau:
  - Owner da co `public/owner/utilities.php`.
  - User da co `public/user/my-invoices.php`.
  - Schema dang co ca `monthly_bills` va `monthly_invoices`, can chon mot nguon chinh.
- Can bo sung:
  - Chuan hoa owner tao hoa don va user doc cung mot bang du lieu.
  - Them chi tiet cach tinh tien phong, dien, nuoc, dich vu.
  - Them reminder hoa don chua thanh toan trong user dashboard/notification.
- Definition of Done:
  - Hoa don owner tao xuat hien o user cung ky thang.
  - Tong tien tren owner va user khop nhau.
  - Khong de hai schema hoa don song song ma UI hien du lieu lech nhau.

### Thanh vien 6: Cong cu quyet dinh phong

- Actor: Guest va User.
- Muc tieu: giup nguoi thue chon phong truoc khi booking, khong can them nghiep vu admin.
- Can bo sung:
  - Compare room: chon 2-3 phong tu search va so sanh gia, dien tich, coc, phi, tien nghi, khu vuc.
  - Shortlist/share: luu danh sach phong dang can nhac va tao link search/filter de quay lai.
  - Lam ro match score tren `public/user/search.php`: tieu chi nao dang lam phong phu hop.
- File nen so huu:
  - `public/user/search.php`, trang compare moi, JS/CSS compare rieng neu can.
- Definition of Done:
  - Guest co the so sanh phong dang hien cong khai.
  - User co the di tu compare ve detail/booking.
  - Cong cu chi doc phong `approved`, khong lo phong an/cho duyet.

### Thu tu merge de giam dung file

1. Thanh vien 1 va 2 merge navigation user/owner truoc vi cac module khac can link.
2. Thanh vien 3 va 4 merge sau khi chot dieu kien booking hop le.
3. Thanh vien 5 chot mot schema hoa don truoc khi sua UI owner va user.
4. Thanh vien 6 tach CSS/JS compare rieng de tranh xung dot `search.php`.

## Phase 4 - Chuc Nang Thi Truong De Ban Duoc

### Epic User

- Move-in cost calculator tren detail phong: tien coc, tien thue thang dau, phi dich vu.
- Saved search: user luu bo loc tim phong va bat thong bao.
- Compare rooms: so sanh gia, dien tich, khu vuc, tien nghi.
- Viewing appointment: dat lich xem phong thay vi chi dat coc.
- In-app message voi owner.
- Report listing/user neu nghi ngo lua dao.

### Epic Owner

- Listing health score: canh bao tin thieu anh, thieu mo ta, thieu dia chi, gia bat thuong.
- Lead inbox: tap trung tin nhan, lich xem phong, booking moi.
- Calendar xem phong.
- Maintenance ticket cho phong da co booking.
- Revenue report theo thang, export CSV.
- Quick status: an phong, danh dau da cho thue, bat noi bat.

### Epic Admin

- Smart moderation queue: owner pending, phong pending, report pending, payment pending.
- Trust score cho owner/user.
- Verified owner badge.
- Admin notes tren owner/phong/booking.
- Broadcast notification.
- Dispute center cho booking/payment.
- Export CSV cho users, motels, bookings, payments.

### Migration Nen

Da co file:

- `migrations/003_market_features.sql`

File nay tao nen cho:

- `viewing_appointments`
- `conversations`
- `messages`
- `notifications`
- `saved_searches`
- `maintenance_requests`
- `listing_quality_checks`
- `admin_notes`
- cac cot score/verification/featured tren `motels`, `users`

## Giai Thich Ro Cac Chuc Nang Se Them

Phan nay dung de chia task va tranh lam giao dien "trang tri". Moi chuc nang ben duoi phai gan voi luong su dung that cua admin, owner, user.

### 1. Listing Health Score

- Actor chinh: Owner, Admin.
- Muc dich: Cham diem chat luong tung tin phong de owner biet tin dang dang thieu gi truoc khi cho admin duyet.
- DB lien quan: `motels.health_score`, `listing_quality_checks`.
- Cach hoat dong:
  - He thong tinh diem tu 0-100 dua tren tieu de, mo ta, dia chi, gia, dien tich, tien nghi, hinh anh, ngay trong.
  - Tin diem thap hien canh bao trong owner dashboard/listings.
  - Admin thay diem nay trong hang cho duyet de uu tien tin ro rang truoc.
- Loi ich:
  - Owner biet can sua gi de tin de duoc duyet va de co booking.
  - Admin giam thoi gian kiem tra tin rac/tin thieu thong tin.
- Definition of Done:
  - Owner thay diem chat luong tren danh sach phong.
  - He thong hien it nhat 3 goi y sua tin khi diem thap.
  - Admin thay diem trong danh sach phong cho duyet.

### 2. Smart Admin Moderation Queue

- Actor chinh: Admin web.
- Muc dich: Gom tat ca viec can admin xu ly vao mot khu vuc uu tien, khong de admin phai mo tung menu.
- DB lien quan: `users`, `motels`, `reports`, `bookings`, `payments`, `activity_logs`.
- Cach hoat dong:
  - Dashboard admin hien cac queue: owner cho duyet, phong cho duyet, report cho xu ly, booking/payment bat thuong, tin diem chat luong thap.
  - Moi item co nut xem chi tiet va hanh dong nhanh neu co san trang xu ly.
  - Moi hanh dong quan tri ghi vao `activity_logs`.
- Loi ich:
  - Admin lam viec theo hang doi ro rang, giam sot viec.
  - He thong trong giong san pham van hanh that, khong chi la trang thong ke.
- Definition of Done:
  - Admin dashboard co card queue va so luong dung.
  - Link den trang xu ly dung, khong 404.
  - Duyet/tu choi owner va phong cap nhat trang thai dung.

### 3. Saved Search Alerts

- Actor chinh: User.
- Muc dich: User luu bo loc tim phong de quay lai nhanh va sau nay nhan thong bao khi co phong moi phu hop.
- DB lien quan: `saved_searches`, `notifications`, `motels`.
- Cach hoat dong:
  - Tren trang search, user co nut "Luu tim kiem".
  - He thong luu keyword, quan, danh muc, khoang gia, dien tich.
  - Khi owner dang phong moi duoc duyet, co the tao notification cho user co saved search phu hop.
- Loi ich:
  - User khong phai nhap lai bo loc.
  - Tang ty le user quay lai website.
- Definition of Done:
  - User luu duoc bo loc search.
  - User xem/xoa duoc saved search.
  - Notification duoc tao khi co phong moi match saved search.

### 4. Viewing Appointment

- Actor chinh: User, Owner.
- Muc dich: Tach "dat lich xem phong" khoi "dat phong/coc tien", giong hanh vi thuc te cua nguoi thue.
- DB lien quan: `viewing_appointments`, `notifications`.
- Cach hoat dong:
  - User o trang chi tiet phong chon ngay gio muon xem phong va ghi chu.
  - Owner nhan lich moi, co the chap nhan/doi lich/tu choi.
  - Trang owner dashboard hien lich xem phong sap toi.
- Loi ich:
  - Phu hop thuc te vi user thuong muon xem phong truoc khi coc.
  - Owner quan ly lead ro hon, khong bi tron voi booking da dat coc.
- Definition of Done:
  - User tao lich xem phong thanh cong.
  - Owner thay lich va cap nhat status.
  - User thay trang thai lich trong dashboard.

### 5. In-app Messages / Lead Inbox

- Actor chinh: User, Owner.
- Muc dich: Cho user hoi nhanh owner ve phong, owner gom lead vao mot inbox.
- DB lien quan: `conversations`, `messages`, `notifications`.
- Cach hoat dong:
  - User bam "Nhan tin owner" o detail phong.
  - Neu chua co conversation thi tao moi theo user-owner-motel.
  - Owner thay danh sach lead moi, tin chua doc va phong lien quan.
- Loi ich:
  - Tang chuyen doi tu xem phong sang hen xem/booking.
  - Owner khong mat lead vi thong tin roi rac.
- Definition of Done:
  - User gui/nhan tin duoc.
  - Owner dashboard co lead inbox.
  - Tin moi tao notification cho nguoi nhan.

### 6. Move-in Cost Calculator

- Actor chinh: User.
- Muc dich: Tinh tong tien can chuan bi truoc khi thue: tien thue, tien coc, phi dich vu.
- DB lien quan: `motels.price`, `motels.service_fee`, `motels.deposit_months`.
- Cach hoat dong:
  - Trang detail phong hien bang "Chi phi vao o".
  - Cong thuc co ban: tong = gia thue thang dau + service_fee + gia thue * deposit_months.
  - Neu owner chua nhap phi thi hien 0 va canh bao "can xac nhan voi chu phong".
- Loi ich:
  - User ra quyet dinh nhanh hon, tranh bat ngo ve chi phi.
  - Tin phong trong minh bach hon.
- Definition of Done:
  - Detail phong hien tong tien vao o.
  - Owner form co truong phi dich vu va so thang coc.
  - Gia tri tinh dung voi du lieu DB.

### 7. Room Match Score

- Actor chinh: User.
- Muc dich: Cham diem phong phu hop voi bo loc user, giup user quet ket qua nhanh hon.
- DB lien quan: `motels`, `districts`, `categories`, `favorites`.
- Cach hoat dong:
  - Ket qua search hien diem phu hop dua tren gia, quan, dien tich, danh muc va tien nghi.
  - Phong dung nhieu tieu chi len dau khi user chon sort "Phu hop nhat".
- Loi ich:
  - Trang search co tinh nang khac biet hon site CRUD co ban.
  - User khong phai doc tung card neu co nhieu ket qua.
- Definition of Done:
  - Search card hien match score.
  - Sort theo match score hoat dong.
  - Diem khong lam sai ket qua loc goc.

### 8. Verified Owner Badge va Trust Score

- Actor chinh: Admin, Owner, User.
- Muc dich: Tang do tin cay cho tin phong va nguoi dang.
- DB lien quan: `users.trust_score`, `users.verified_at`, `users.status`, `reports`, `reviews`.
- Cach hoat dong:
  - Admin duyet owner thi co the danh dau verified.
  - Trust score tang khi owner co tin duyet, booking thanh cong, review tot; giam khi bi report hop le.
  - User thay badge "Owner da xac minh" tren detail phong.
- Loi ich:
  - Giam cam giac rui ro khi user tim phong.
  - Admin co co so uu tien owner uy tin.
- Definition of Done:
  - Admin cap/thu hoi verified owner.
  - Detail phong hien badge neu owner verified.
  - Dashboard admin hien trust score.

### 9. Reports va Dispute Center

- Actor chinh: User, Admin.
- Muc dich: Cho user bao cao tin sai/lua dao va admin theo doi xu ly tranh chap.
- DB lien quan: `reports`, `admin_notes`, `activity_logs`.
- Cach hoat dong:
  - User report listing/owner voi ly do.
  - Admin xem danh sach report pending, ghi note, doi status.
  - Neu report hop le, admin co the an phong/khoa user.
- Loi ich:
  - He thong co lop an toan, phu hop san pham dang tin that.
  - Admin co lich su xu ly ro rang.
- Definition of Done:
  - User tao report tu detail phong.
  - Admin xem va cap nhat status report.
  - Hanh dong xu ly ghi activity log.

### 10. Maintenance Tickets

- Actor chinh: User, Owner.
- Muc dich: Sau khi user da thue/dat phong, co noi gui yeu cau sua chua.
- DB lien quan: `maintenance_requests`, `bookings`, `motels`.
- Cach hoat dong:
  - User co booking hop le tao ticket: tieu de, mo ta, muc uu tien.
  - Owner cap nhat status: open, in_progress, resolved.
  - Admin co the xem neu can xu ly tranh chap.
- Loi ich:
  - Owner van hanh phong sau khi cho thue, khong chi dang tin.
  - User co trai nghiem hau thue ro rang.
- Definition of Done:
  - User tao ticket tu booking/phong da thue.
  - Owner xem va cap nhat ticket.
  - Dashboard owner hien so ticket dang mo.

### 11. Notification Center

- Actor chinh: Admin, Owner, User.
- Muc dich: Gom thong bao quan trong trong he thong thay vi chi dung alert tam thoi.
- DB lien quan: `notifications`.
- Cach hoat dong:
  - Tao notification khi owner duoc duyet, phong duoc duyet/tu choi, co booking moi, lich xem moi, tin nhan moi, report duoc xu ly.
  - Navbar/dashboard hien so thong bao chua doc.
- Loi ich:
  - User/owner khong bo lo thay doi trang thai.
  - He thong co cam giac san pham that va de van hanh.
- Definition of Done:
  - Moi actor co trang danh sach notification.
  - Co trang thai read/unread.
  - Cac hanh dong chinh tao notification dung nguoi nhan.

### 12. Export CSV va Bao Cao

- Actor chinh: Admin, Owner.
- Muc dich: Xuat du lieu de bao cao doanh thu, booking, user, phong.
- DB lien quan: `users`, `motels`, `bookings`, `payments`, `transactions`.
- Cach hoat dong:
  - Admin xuat CSV users/motels/bookings/payments theo khoang ngay.
  - Owner xuat CSV doanh thu/booking cua rieng minh.
- Loi ich:
  - Phu hop nhu cau quan ly that va bao cao mon hoc/do an.
  - Giam viec copy tay tu bang HTML.
- Definition of Done:
  - File CSV tai ve mo duoc bang Excel.
  - Du lieu owner chi gom phong cua owner do.
  - Admin export co filter ngay/trang thai.

## Thu Tu Trien Khai Ngay Sau Migration 003

1. Gan `Listing Health Score` vao owner listings/dashboard va admin motel queue.
2. Sua user search: bo loc dung `district_id/category_id`, match score, save search.
3. Them `Move-in Cost Calculator` vao detail phong va field phi/coc vao form owner.
4. Them `Viewing Appointment` tren detail phong va owner dashboard.
5. Them `Notification Center` de cac hanh dong moi co thong bao.
6. Them `Messages/Lead Inbox`.
7. Hoan thien `Reports/Dispute Center`.
8. Them export CSV va cac bao cao.
