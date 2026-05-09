# QuanLyPhongTro - Product Blueprint Theo Thi Truong

## Dinh Vi San Pham

QuanLyPhongTro khong chi la website dang tin phong tro. San pham nen la nen tang van hanh cho thue phong tro gom:

- Marketplace cho nguoi thue tim phong that, da duyet, co thong tin ro.
- Portal cho owner dang phong, nhan lead, quan ly booking, doanh thu va van hanh.
- Console cho admin kiem duyet, chong spam, xu ly tranh chap, quan ly thanh toan va chat luong tin.

## Insight Tu Thi Truong Hien Tai

Nhung nen tang rental/property management hien nay thuong tap trung vao cac nhom tinh nang:

- Tim va marketing phong: listing chat luong, anh/video, bo loc, luu yeu thich, day tin/noi bat.
- Tenant lifecycle: dang ky, dat lich xem phong, nop yeu cau thue, dat coc, hop dong, thanh toan.
- Owner operations: quan ly phong, booking, lead, doanh thu, bao tri, tin nhan, thong bao.
- Trust & safety: xac minh owner, duyet tin, bao cao vi pham, lich su hoat dong, danh gia.
- Financial/automation: thu tien online, phi, bao cao doanh thu, nhac han, export.

## Actor 1 - User

### Must-have

- Dang ky/dang nhap, ho so ca nhan.
- Tim phong theo keyword, quan/huyen, danh muc, gia, dien tich, tien nghi.
- Xem chi tiet phong: anh, gia, dia chi, tien nghi, owner, review, trang thai phong.
- Luu/xoa yeu thich.
- Dat phong/giu lich xem phong.
- Theo doi booking: pending, accepted, paid, completed, cancelled.
- Gui bao cao tin xau/lua dao.

### Nen co de than thien

- Compare phong: so sanh 2-3 phong theo gia, dien tich, tien nghi, khu vuc.
- Cost calculator: uoc tinh tien dau vao gom coc, gia thue, phi dich vu.
- Saved search: luu bo loc va nhan thong bao khi co phong moi.
- In-app messages voi owner.
- Review sau khi booking completed.

## Actor 2 - Owner

### Must-have

- Dang ky owner cho admin duyet.
- CRUD phong, upload anh, tien nghi, gia, dien tich, mo ta.
- Quan ly trang thai tin: pending, approved, hidden.
- Quan ly booking va lich xem phong.
- Dashboard: tong phong, luot xem, lead/booking, doanh thu.
- Ho so owner, doi mat khau.

### Nen co de de quan ly

- Lead inbox: danh sach user quan tam phong.
- Calendar xem phong.
- Maintenance requests: tiep nhan va theo doi yeu cau sua chua.
- Revenue report theo thang.
- Listing health score: goi y tin nao thieu anh, thieu mo ta, gia bat thuong.
- Quick actions: an phong, danh dau da cho thue, nhan/tat thong bao.

## Actor 3 - Admin

### Must-have

- Dashboard tong quan he thong.
- Quan ly user/owner/admin.
- Duyet owner, khoa/mo khoa.
- Duyet/an/xoa phong.
- Quan ly danh muc, quan/huyen, tien nghi.
- Quan ly booking, payments, reviews, reports.
- Activity log.

### Nen co de van hanh tot

- Moderation queue: hang cho duyet owner/phong/report.
- Risk flags: tin gia qua thap, phong thieu thong tin, owner bi report nhieu.
- Content quality score cho tin phong.
- Dispute center: xu ly tranh chap booking/payment.
- Broadcast notifications.
- Export CSV doanh thu, booking, user.
- Admin notes tren user/owner/listing.

## Chuc Nang Doc Dao Nen Lam

1. Room Match Score
   - Cham diem phong theo bo loc user: gia, khu vuc, dien tich, tien nghi.

2. Listing Health Score
   - Cham diem tin dang cua owner: anh, mo ta, gia, dia chi, tien nghi, ty le booking.

3. Move-in Cost Calculator
   - Tinh tong tien can chuan bi: tien coc + tien thue thang dau + phi dich vu.

4. Verified Owner Badge
   - Owner duoc admin duyet hien badge xac minh.

5. Saved Search Alerts
   - User luu bo loc, he thong tao notification khi co phong moi phu hop.

6. Maintenance Ticket
   - Sau khi thue/dat phong, user gui yeu cau sua chua, owner/admin theo doi.

7. Smart Admin Queue
   - Admin co man hinh uu tien: owner pending, phong pending, report pending, booking can xu ly.

## Uu Tien Trien Khai

### Phase A - San pham dung duoc

- Sua giao dien trang chu, search, detail, login/register.
- Hoan thien auth/role/status.
- User search, favorite, booking.
- Owner CRUD phong, booking.
- Admin duyet owner/phong.

### Phase B - Van hanh tot

- Notifications.
- Messages.
- Viewing appointments.
- Reports/moderation.
- Revenue/payment tracking.
- Listing health score.

### Phase C - Khac biet voi do an co ban

- Room match score.
- Saved search alerts.
- Move-in cost calculator.
- Maintenance tickets.
- Export/reporting.
- UI 3D co kiem soat, khong lam cham thao tac.
