# Redesign UI/UX Phase 1 - 2026

## Muc tieu

Phase nay la mot goi tinh chinh UI/UX co kiem soat sau UI/UX Round 2. Khong thay doi state machine, Golden Path, authorization hay nghiep vu thanh toan/bao gia da PASS.

## Pham vi da sua

### 1. Lead Detail
- Tach thong tin thanh cac khoi: Tong quan Lead, Xu ly & danh gia Lead, Thong tin nhu cau tu bieu mau.
- Giam mat do thong tin, tang kha nang scan nhanh.
- Snapshot form duoc hien thi thanh bang 2 cot thay vi mot card cho moi field.
- Gom cac action phu/nguy hiem vao `Thao tac khac`.
- Viet hoa `Qualification Notes` thanh `Ghi chu danh gia` va sua empty state.

### 2. Company Settings va logo
- Viet hoa tieu de page thanh `Cai dat cong ty`.
- File upload logo duoc khoa ve public disk.
- Logo tren thanh dieu huong duoc phuc vu qua Laravel controller thay vi phu thuoc truc tiep vao `/storage` symlink.
- Them cache busting theo `lastModified` cua file logo.

### 3. Lich su thanh toan
- `verified` duoc hien thi thanh nhan nghiep vu da dich.
- `Sales` -> `Nhan vien kinh doanh`.
- `Finance xac minh` -> `Nhan vien tai chinh xac minh`.
- Hai action `Xem bien lai` va `Mo bao gia` duoc gom vao Action Group.
- An mac dinh 2 cot Net/VAT de giam horizontal scroll; nguoi dung van co the bat lai bang column toggle.
- Nguon UTM su dung danh muc ten hien thi thay vi raw value khi co mapping.

### 4. Form Dich vu
- Ma dich vu, Ten, Trang thai cung mot hang tren desktop.
- Mo ta full width.
- Pham vi mac dinh va Dieu khoan mac dinh dat song song tren man hinh lon.

### 5. Form Bang gia
- Bo cuc 12-column responsive.
- `Tax mode` thanh `Che do thue` va dung Select thong nhat.
- Nhom pham vi/loai doi tuong/tien te/thue va thoi gian hieu luc ro rang.
- Repeater Goi dich vu & gia duoc can lai 12 cot, khong con khoang trong lon.
- Discount fields chia deu mot hang; Scope/Terms chia 2 cot.
- Price Book Access Rule dich `access_type`, role, department va label nut tao.

### 6. Opportunity
- Detail chia thanh Tong quan co hoi, Lien he chinh, Thong tin ban giao tu CSKH.
- An field Won/Lost khong lien quan theo stage de tranh hien dau gach vo nghia.
- Doi `Sales` thanh `Nhan vien kinh doanh`.
- Role contact khong con hien `primary_contact`, `other` dang raw.
- Loai activity khong con `quotation_sent`, `quotation_viewed`, `quotation_accepted` dang raw.
- Cac tab Opportunity duoc Viet hoa: Kham pha nhu cau, Du dieu kien, De xuat giai phap, Dam phan.
- Noi dung activity do Quotation sinh moi tu nay cung dung translation key VI/EN.

## Nguyen tac BA/UIUX
- Technical identifiers duoc giu trong DB, nhung khong hien raw cho nguoi dung nghiep vu.
- Action chinh de lo; action ngoai le/nguy hiem gom vao menu.
- Field chi hien khi co y nghia voi state hien tai.
- Form desktop uu tien 3-4 field/hang khi field ngan; textarea full/half width tuy noi dung.
- Khong thay doi schema DB trong phase nay.

## Regression checklist
1. Admin > Cai dat cong ty: upload logo, save, reload, logo header phai hien.
2. Lead detail: layout moi, action group, Ghi chu danh gia.
3. Finance > Lich su thanh toan: status VI, action group, mo bien lai/mo bao gia.
4. Kinh doanh > Dich vu: create/edit va save khong thay doi du lieu.
5. Kinh doanh > Bang gia: edit price book, items, access rules, save.
6. Kinh doanh > Co hoi kinh doanh: list tabs VI; detail role/activity VI.
7. Golden Path smoke test: Lead -> Opportunity -> Quotation -> Payment van giu nguyen state va action.
