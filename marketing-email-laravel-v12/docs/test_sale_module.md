# Tài liệu kiểm thử chức năng — Sales Module (Quotation / Báo giá)

| Thông tin | Giá trị |
|---|---|
| **Module** | Sales (Báo giá / Quotation) |
| **Nền tảng** | Laravel 12 + Filament 3 (Panel admin: `/admin`) |
| **Tác nhân kiểm thử** | Admin, Customer Service Manager, Customer Service Staff |
| **Tác nhân bên ngoài** | Khách hàng (public link, không đăng nhập) |
| **Phương pháp** | Manual QA — thao tác qua UI trên trình duyệt |
| **Ngày soạn** | 01/08/2026 |
| **Trạng thái** | ✅ Đã được người viết quy trình xác nhận |

---

## 1. Giới thiệu & phạm vi

### 1.1. Mục đích
Tài liệu này mô tả quy trình kiểm thử **toàn bộ chức năng UI của Sales Module** theo góc nhìn Tester: các bước thực hiện cụ thể (click vào đâu, nhập gì), dữ liệu test, kết quả mong đợi cho từng trường hợp, và các điểm lỗi dự kiến cần báo cáo.

### 1.2. Phạm vi trong / ngoài

| Trong phạm vi | Ngoài phạm vi |
|---|---|
| Vòng đời báo giá: tạo → duyệt → gửi → khách phản hồi → thanh toán | Marketing module (Campaign, Landing Page, Email Template) |
| Phê duyệt nội bộ, hủy, hết hạn, revision (bản sửa) | Customer Care module (ngoài tab Báo giá) |
| Phân quyền Admin vs Staff | Audit log chi tiết (chỉ kiểm tra sự tồn tại) |
| Luồng khách hàng qua public link (`/q/{code}/{token}`) | Tích hợp SMTP thật, cổng thanh toán thật |
| Email nội bộ + email gửi khách (qua Mailpit) | Unit test tự động (test thủ công qua UI) |
| Tự động hoá: hết hạn + nhắc việc (`sales:process-reminders`) | |

### 1.3. Môi trường mục tiêu
Local dev: `php artisan serve` + queue worker + Mailpit (bắt email ảo) + DB MySQL đã seed.

---

## 2. Đối tượng & phân quyền

### 2.1. Vai trò liên quan (bảng `users.role`)

| Vai trò | Ký hiệu trong tài liệu | Mô tả |
|---|---|---|
| Admin | **Admin** | Toàn quyền trên Sales |
| Customer Service Manager | **CS Manager** | Quản lý bán hàng, duyệt, gửi, theo dõi thanh toán |
| Customer Service Staff | **CS Staff** | Nhân viên bán hàng — tạo/sửa báo giá của mình |

> Lưu ý: không có khái niệm "Staff" chung; Staff được liên kết qua bảng `crm_staff` (`users.staff_id` → `staffs`). Trong Sales, staff thuộc nhánh **customer_service_staff**.

### 2.2. Ma trận quyền thực tế (đã xác minh từ Gates/Policies/Resource)

| Chức năng | Admin | CS Manager | CS Staff | Marketing Staff/Manager | Viewer |
|---|---|---|---|---|---|
| Xem danh sách báo giá | ✅ tất cả | ✅ tất cả | ✅ chỉ của mình (assigned/created) | ✅ chỉ của mình | ✅ chỉ của mình |
| Xem chi tiết báo giá | ✅ tất cả | ✅ tất cả | ✅ nếu assigned/created | ⚠️ qua policy view | ⚠️ qua policy view |
| Tạo báo giá | ✅ | ✅ | ✅ | ❌ | ❌ |
| Sửa báo giá (draft/PA/revision) | ✅ | ✅ nếu người tạo | ✅ nếu người tạo | ❌ | ❌ |
| Gửi duyệt (submit approval) | ✅ | ✅ | ✅ | ❌ | ❌ |
| Duyệt / Từ chối duyệt | ✅ | ✅ | ❌ | ❌ | ❌ |
| Gửi email báo giá | ✅ | ✅ | ❌ | ❌ | ❌ |
| Hủy báo giá | ✅ | ✅ | ❌ | ❌ | ❌ |
| Tạo Revision (bản sửa) | ✅ | ❌ | ❌ | ❌ | ❌ |
| Theo dõi thanh toán (mark paid/pending/unpaid) | ✅ | ✅ | ❌ | ❌ | ❌ |
| Xem Dashboard Bán hàng | ✅ | ✅ | ❌ | ❌ | ❌ |
| Xem Phê duyệt báo giá | ✅ | ✅ | ❌ | ❌ | ❌ |
| Quản lý Dịch vụ / Gói / Bảng giá / Tài khoản NH | ✅ | ❌ | ❌ | ❌ | ❌ |

### 2.3. Màn hình chính (menu nhóm **"Bán hàng"**)

| Menu | URL dạng | Chức năng |
|---|---|---|
| Báo giá (Quotation) | `/admin/sales/quotations` | Danh sách + filter + actions |
| Tạo báo giá | `/admin/sales/quotations/create` | Form tạo (draft) |
| Xem báo giá | `/admin/sales/quotations/{id}` | Chi tiết + header actions + 5 tabs relation |
| Theo dõi thanh toán | `/admin/sales/payment-trackings` | Danh sách + mark paid/pending/unpaid |
| Phê duyệt báo giá | `/admin/sales/quotation-approvals` | Danh sách yêu cầu duyệt |
| Dịch vụ | `/admin/sales/services` | CRUD Dịch vụ |
| Gói dịch vụ | `/admin/sales/service-packages` | CRUD Gói dịch vụ |
| Bảng giá | `/admin/sales/price-books` | CRUD Bảng giá + quy tắc truy cập |
| Tài khoản ngân hàng | `/admin/sales/bank-accounts` | CRUD Tài khoản NH |
| Dashboard Bán hàng | `/admin/sales-dashboard` | KPI (chỉ Admin/CS Manager) |

> ⚠️ URL cụ thể phụ thuộc cấu hình panel; khi test hãy đi theo menu thay vì nhập URL tay.

---

## 3. Chuẩn bị môi trường local

### 3.1. Khởi động hệ thống
```bash
# 1. Cài đặt + chuẩn bị DB (nếu chưa)
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed            # seed kèm SalesSeeder (4 dịch vụ, 4 gói, 3 bảng giá, 2 tài khoản NH, 1 báo giá mẫu)

# 2. Chạy server + queue + frontend (dùng script dev có sẵn hoặc chạy tách)
php artisan serve                     # http://localhost:8000
php artisan queue:listen --tries=3    # xử lý queue 'quotations' + 'default'
npm run dev
```

### 3.2. Bắt email ảo (Mailpit / Mailhog)
- Cấu hình `.env`:
  ```
  MAIL_MAILER=smtp
  MAIL_HOST=127.0.0.1
  MAIL_PORT=1025
  MAIL_USERNAME=null
  MAIL_PASSWORD=null
  MAIL_ENCRYPTION=null
  MAIL_FROM_ADDRESS="no-reply@company.test"
  MAIL_FROM_NAME="Company CRM"
  ```
- Mở UI Mailpit: `http://localhost:8025` — mọi email gửi đi sẽ xuất hiện ở đây.

### 3.3. Chuẩn bị tài khoản test

| Tài khoản | Role | Cần thêm |
|---|---|---|
| `admin@example.com` | admin | — (thường có sẵn từ seeder) |
| `manager@example.com` | customer_service_manager | — |
| `staff1@example.com`, `staff2@example.com` | customer_service_staff | Staff profile (`crm_staff`) + **CustomerAssignment active** cho khách hàng test |
| Khách hàng test | — | 1 khách `personal` + 1 khách `business`, trạng thái active |

**Cách tạo nhanh (tinker):**
```bash
php artisan tinker
```
```php
// Staff 1 & 2
$u1 = \App\Models\User::updateOrCreate(['email' => 'staff1@example.com'], ['name' => 'Staff 1', 'role' => 'customer_service_staff', 'password' => bcrypt('password')]);
$u2 = \App\Models\User::updateOrCreate(['email' => 'staff2@example.com'], ['name' => 'Staff 2', 'role' => 'customer_service_staff', 'password' => bcrypt('password')]);
$s1 = \App\Models\Crm\Staff::updateOrCreate(['user_id' => $u1->id, 'staff_code' => 'STF001'], ['full_name' => 'Staff Một', 'department' => 'customer_service', 'employment_status' => 'active']);
$s2 = \App\Models\Crm\Staff::updateOrCreate(['user_id' => $u2->id, 'staff_code' => 'STF002'], ['full_name' => 'Staff Hai', 'department' => 'customer_service', 'employment_status' => 'active']);
$u1->update(['staff_id' => $s1->id]);
$u2->update(['staff_id' => $s2->id]);

// Gán khách hàng "Nguyễn Văn A" cho Staff 1
$customer = \App\Models\Crm\Customer::where('display_name', 'Nguyễn Văn A')->first();
\App\Models\Crm\CustomerAssignment::create([
    'customer_id' => $customer->id, 'staff_id' => $s1->id,
    'status' => 'active', 'assigned_at' => now(), 'reason' => 'test',
]);
```
> Lưu ý: cần đảm bảo staff chưa có assignment active khác trùng khách hàng.

### 3.4. Dữ liệu tham chiếu cần có (từ `SalesSeeder`)
- 4 Dịch vụ (active), 4 Gói dịch vụ (active)
- 3 Bảng giá: ít nhất 1 bảng giá `audience_type = personal` + 1 `business`, trạng thái `active`
- 2 Tài khoản ngân hàng (active)
- Quy tắc truy cập bảng giá (PriceBookAccessRule) cho Staff 1 (nếu bảng giá không mở cho `all`)

### 3.5. Lưu ý chung khi test
- Mỗi TC đều cần **tạo dữ liệu riêng** nếu tiền điều kiện yêu cầu — không tái sử dụng kết quả TC trước trừ khi được nói rõ.
- Luồng khách hàng: test ở **cửa sổ ẩn danh (incognito)** — không đăng nhập.
- Quan sát queue: nếu job chưa chạy, chạy `php artisan queue:work --once --queue=quotations` để ép xử lý.

---

## 4. Sơ đồ vòng đời & ma trận trạng thái

### 4.1. State machine (QuotationStatus)

```
draft ──(Gửi duyệt)──▶ pending_approval ──(Duyệt)──▶ approved ──(Gửi email)──▶ sent ──▶ viewed
  ▲                        │                                                          │
  └────(Từ chối duyệt)─────┘                                                          │
                                                          ┌──────────────┬───────────┴──────────┐
                                                          ▼              ▼                      ▼
                                                     accepted     rejected           revision_requested
                                                     (terminal)   (terminal)                 │
                                                                                             ▼
                                                                              (Admin tạo bản sửa)
                                                                             bản cũ → superseded
                                                                             bản mới → draft

Mọi trạng thái chưa terminal ──(Hủy)──▶ cancelled
approved / sent / viewed quá hạn valid_until ──(job tự động)──▶ expired
```

### 4.2. Ma trận chuyển trạng thái hợp lệ / bất hợp lệ

| Từ \ Qua hành động | Gửi duyệt | Duyệt | Từ chối duyệt | Gửi email | Hủy | Khách accept/reject/revision | Hết hạn |
|---|---|---|---|---|---|---|---|
| draft | ✅ → pending_approval | ❌ | ❌ | ❌ | ✅ → cancelled | ❌ | ❌ |
| pending_approval | ❌ | ✅ → approved | ✅ → draft | ❌ | ✅ | ❌ | ❌ |
| approved | ❌ | ❌ | ❌ | ✅ → sent | ✅ | ❌ | ✅ → expired |
| sent | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ → accepted/rejected/revision_requested | ✅ → expired |
| viewed | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ → expired |
| revision_requested | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ | ❌ (chờ admin tạo bản mới) |
| accepted / rejected / expired / cancelled / superseded | ❌ | ❌ | ❌ | ❌ | ❌ (terminal) | ❌ | ❌ |

### 4.3. PaymentStatus & luồng chuyển hợp lệ

```
not_required ↔ unpaid ↔ pending_verification ↔ partially_paid → paid → refunded
                                ↕                   ↕
                            unpaid              unpaid/cancelled
paid → refunded ; refunded & cancelled = terminal
```

---

## 5. TC-01 → TC-08: Luồng happy path (luồng chính)

---

### TC-01 | Tạo báo giá thành công (Admin) | Ưu tiên: Cao | Tác nhân: Admin
- **Mục tiêu**: Admin tạo được báo giá draft mới, hệ thống tự sinh mã, tính toán đúng.
- **Tiền điều kiện**: Đã đăng nhập admin; có ≥1 khách active; có bảng giá active phù hợp; có tài khoản NH.
- **Bước thực hiện**:
  1. Vào menu **Bán hàng → Báo giá** (`/admin/sales/quotations`)
  2. Bấm nút **Tạo** (góc trên phải)
  3. Trong section **Thông tin báo giá**: chọn Khách hàng = `Nguyễn Văn A`; chọn Bảng giá = bảng giá personal; chọn Tài khoản ngân hàng; nhập Tiêu đề = `Báo giá test TC-01`; Ngày = hôm nay; Hạn hiệu lực = hôm nay + 30 ngày
  4. Trong section **Mục báo giá** (Repeater): nhập 2 dòng
     - Dòng 1: Tên dịch vụ = `Website Maintenance`; ĐVT = `tháng`; SL = `3`; Đơn giá = `1.000.000`; Giảm giá: kiểu `percent`, giá trị `10`; VAT = `10`
     - Dòng 2: Tên dịch vụ = `Hosting`; ĐVT = `tháng`; SL = `12`; Đơn giá = `200.000`; VAT = `10`
  5. Bấm **Lưu**
- **Dữ liệu test**: như bước 3–4.
- **Kết quả mong đợi**:
  - Redirect về trang **Xem báo giá**; mã có dạng `QT{YYYY}{00001}` (tăng dần); version = 1
  - Trạng thái = **Nháp**; Thanh toán = **Chưa thanh toán**
  - Tính toán: dòng 1: subtotal = 3×1.000.000 = 3.000.000; discount = 300.000; VAT = 270.000; line_total = 2.970.000. Dòng 2: subtotal = 2.400.000; VAT = 240.000; line_total = 2.640.000. Grand total = 5.610.000
  - Xuất hiện 5 tab: Mục báo giá, Xác nhận, Lịch sử email, Tài liệu, Phê duyệt
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-02 | Tạo báo giá thành công (CS Staff có assignment) | Ưu tiên: Cao | Tác nhân: CS Staff
- **Mục tiêu**: Staff được gán khách tạo được báo giá cho đúng khách của mình.
- **Tiền điều kiện**: Đăng nhập `staff1@example.com`; staff1 có assignment active cho `Nguyễn Văn A`; bảng giá personal mở quyền `can_create_quotation` cho staff1.
- **Bước thực hiện**: Lặp lại TC-01 nhưng với tài khoản staff1, khách = `Nguyễn Văn A`, bảng giá có quyền truy cập.
- **Kết quả mong đợi**: Tạo thành công; **Nhân viên phụ trách** (assigned_staff) trên báo giá = tên staff1.
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-03 | Gửi duyệt báo giá (draft → pending_approval) | Ưu tiên: Cao | Tác nhân: CS Staff (hoặc Admin)
- **Mục tiêu**: Submit báo giá draft lên bước duyệt; tạo phiếu duyệt step 1.
- **Tiền điều kiện**: Có báo giá draft (kết quả TC-02); đang ở trang Xem báo giá.
- **Bước thực hiện**:
  1. Mở báo giá draft (từ danh sách Báo giá → bấm **Xem**)
  2. Bấm nút **Gửi duyệt** (header)
  3. Quan sát trạng thái
- **Kết quả mong đợi**:
  - Trạng thái chuyển **Nháp → Chờ duyệt**
  - Tab **Phê duyệt** xuất hiện 1 bản ghi: step = 1, vai trò duyệt = customer_service_manager, trạng thái = Chờ duyệt, có thời gian yêu cầu
  - Nút **Gửi duyệt** biến mất; nút **Duyệt / Từ chối duyệt** không hiện cho staff
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-04 | Duyệt báo giá (pending_approval → approved) — qua ViewQuotation | Ưu tiên: Cao | Tác nhân: CS Manager
- **Mục tiêu**: Manager duyệt báo giá từ trang chi tiết.
- **Tiền điều kiện**: Báo giá đang ở Chờ duyệt; đăng nhập CS Manager.
- **Bước thực hiện**:
  1. Mở báo giá đang **Chờ duyệt**
  2. Bấm nút **Duyệt** (header)
  3. Quan sát trạng thái + tab Phê duyệt
- **Kết quả mong đợi**:
  - Trạng thái = **Đã duyệt**; phiếu duyệt chuyển trạng thái **Đã duyệt**, có reviewed_at
  - Nút **Gửi** (gửi email) xuất hiện
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-05 | Duyệt báo giá từ danh sách Phê duyệt báo giá | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Mục tiêu**: Duyệt trực tiếp từ resource Phê duyệt.
- **Tiền điều kiện**: Có báo giá Chờ duyệt khác.
- **Bước thực hiện**:
  1. Menu **Bán hàng → Phê duyệt báo giá**
  2. Tìm phiếu duyệt trạng thái **Chờ duyệt** của báo giá vừa tạo
  3. Bấm **Duyệt** ở cột Thao tác
- **Kết quả mong đợi**: Báo giá → Đã duyệt; dòng phiếu duyệt đổi thành **Đã duyệt** (kèm thời gian).
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-06 | Gửi email báo giá (approved → sent) | Ưu tiên: Cao | Tác nhân: CS Manager
- **Mục tiêu**: Gửi báo giá cho khách qua email, kèm PDF; email đến Mailpit.
- **Tiền điều kiện**: Báo giá Approved; CS Manager đăng nhập; queue worker đang chạy; Mailpit bật.
- **Bước thực hiện**:
  1. Mở báo giá trạng thái **Đã duyệt**
  2. Bấm nút **Gửi** (header) → modal nhập email
  3. Nhập email nhận = địa chỉ bất kỳ (ví dụ `khach@example.com`) → bấm Gửi
  4. Vào Mailpit kiểm tra email vừa gửi
- **Kết quả mong đợi**:
  - Trạng thái báo giá = **Đã gửi**
  - Tab **Lịch sử email**: 1 bản ghi — recipient đúng, status = Đã gửi, có thời gian gửi
  - Email trong Mailpit: tiêu đề chứa mã báo giá + tên khách; nội dung có nút **Xem báo giá** trỏ `http://localhost:8000/q/{code}/{token}`; có **PDF đính kèm** tên `QT…-V1.pdf`
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

> ⚠️ **Liên quan BUG-02**: nếu PDF không tạo được (lỗi `DocumentType::Pdf`), luồng này sẽ FAIL. Ghi rõ trong kết quả thực tế.

---

### TC-07 | Khách mở link báo giá (sent → viewed) | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Mục tiêu**: Khách mở public link → báo giá chuyển Đã gửi → Đã xem, đếm view.
- **Tiền điều kiện**: Email đã gửi (TC-06); copy URL `http://localhost:8000/q/{code}/{token}` từ Mailpit.
- **Bước thực hiện**:
  1. Mở cửa sổ ẩn danh, dán URL public link
  2. Quan sát nội dung trang
  3. Refresh lần 2
  4. Quay lại panel (đăng nhập admin) xem chi tiết báo giá
- **Dữ liệu test**: URL hợp lệ từ email.
- **Kết quả mong đợi**:
  - Lần mở đầu: hiển thị đầy đủ thông tin khách, bảng items, tổng tiền, thông tin ngân hàng, điều khoản; khối **Xác nhận báo giá** với 3 nút Chấp nhận/Từ chối/Yêu cầu chỉnh sửa
  - Trạng thái báo giá trong panel = **Đã xem**; trường `first_viewed_at`/`last_viewed_at` có giá trị; **view_count = 1**
  - Refresh lần 2: trạng thái giữ nguyên Đã xem, **view_count = 2**
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-08 | Khách chấp nhận báo giá (viewed → accepted) | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Mục tiêu**: Khách accept → báo giá accepted, tạo QuotationConfirmation, email nội bộ báo cho accounting.
- **Tiền điều kiện**: Báo giá đang Đã xem (TC-07); cửa sổ ẩn danh còn mở.
- **Bước thực hiện**:
  1. Trên trang public, bấm nút **Chấp nhận**
  2. Modal hiện form: Họ tên, Email, Chức vụ, SĐT
  3. Nhập: Họ tên = `Nguyễn Văn A`; Email = `khach@example.com`; Chức vụ = `Giám đốc`; SĐT = `0912345678` → bấm **Gửi**
  4. Quan sát trang public sau redirect
  5. Vào Mailpit kiểm tra email nội bộ
  6. Panel: mở báo giá → tab **Xác nhận**
- **Kết quả mong đợi**:
  - Trang public hiển thị khối **✓ Đã xác nhận**: tên, email, thời gian, **mã xác nhận** (16 ký tự hex)
  - Trạng thái báo giá = **Đã chấp nhận**; **Trạng thái thanh toán = Chưa thanh toán**
  - Tab Xác nhận: 1 bản ghi loại accepted, đầy đủ thông tin người ký, IP/user-agent
  - Mailpit có email nội bộ gửi `accounting@company.com` (hoặc email cấu hình `sales.quotation.accepted_notification_email`) báo báo giá được chấp nhận
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-09 | Admin cập nhật thanh toán: Chờ xác nhận → Đã thanh toán | Ưu tiên: Cao | Tác nhân: Admin
- **Mục tiêu**: Theo dõi thanh toán: khách chuyển khoản → admin xác nhận → khách thành Active/Purchasing + email xác nhận thanh toán đến khách.
- **Tiền điều kiện**: Báo giá Accepted (TC-08); đăng nhập Admin; queue chạy.
- **Bước thực hiện**:
  1. Menu **Bán hàng → Theo dõi thanh toán**
  2. Tìm báo giá vừa accepted → bấm **Chờ xác nhận** (mark_pending)
  3. Quan sát trạng thái → bấm **Đã thanh toán** (mark_paid)
  4. Panel: mở khách hàng tương ứng (CRM) kiểm tra trạng thái/vòng đời
  5. Mailpit: kiểm tra email gửi khách
- **Kết quả mong đợi**:
  - Sau mark_pending: **Trạng thái thanh toán = Chờ xác nhận**
  - Sau mark_paid: **Trạng thái thanh toán = Đã thanh toán**
  - Khách hàng: `status = Active`, `lifecycle_stage = Purchasing`, có `first_purchase_at`/`latest_purchase_at`
  - Mailpit có email **PaymentConfirmedMail** gửi tới email khách
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 6. TC-10 → TC-22: Luồng phụ

---

### TC-10 | Từ chối duyệt (pending_approval → draft) — có lý do | Ưu tiên: Cao | Tác nhân: CS Manager
- **Mục tiêu**: Manager từ chối duyệt → báo giá quay về Nháp.
- **Tiền điều kiện**: Báo giá Chờ duyệt.
- **Bước thực hiện**:
  1. Mở báo giá Chờ duyệt
  2. Bấm **Từ chối duyệt** → modal nhập Lý do → nhập `Giá chưa hợp lý, vui lòng điều chỉnh` → bấm xác nhận
  3. Quan sát trạng thái + tab Phê duyệt
- **Kết quả mong đợi**: Trạng thái = **Nháp**; phiếu duyệt = **Từ chối** kèm reason + reviewed_at; nút **Gửi duyệt** xuất hiện lại.
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-11 | Từ chối duyệt mà không nhập lý do → validation lỗi | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Mục tiêu**: Form bắt buộc lý do.
- **Bước thực hiện**: Bấm **Từ chối duyệt** → bỏ trống Lý do → bấm xác nhận.
- **Kết quả mong đợi**: Hiện lỗi validation "bắt buộc nhập", không thực hiện từ chối; trạng thái giữ nguyên Chờ duyệt.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-12 | Khách từ chối báo giá (→ rejected) | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Tiền điều kiện**: Báo giá khác ở trạng thái Đã gửi/Đã xem (tạo + gửi mới nếu cần).
- **Bước thực hiện**:
  1. Cửa sổ ẩn danh mở public link
  2. Bấm **Từ chối** → modal: Họ tên, Email, Lý do → nhập `Không có nhu cầu` → Gửi
- **Kết quả mong đợi**: Trạng thái = **Từ chối**; tab Xác nhận có bản ghi `rejected` kèm reason; Mailpit có email nội bộ `sales@company.com`; trang public không còn khối Xác nhận, không còn nút hành động.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-13 | Khách yêu cầu chỉnh sửa (→ revision_requested) | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Tiền điều kiện**: Báo giá ở trạng thái Đã gửi/Đã xem.
- **Bước thực hiện**:
  1. Mở public link → bấm **Yêu cầu chỉnh sửa**
  2. Nhập Họ tên, Email, Lý do = `Cần thêm dịch vụ hỗ trợ 24/7` → Gửi
- **Kết quả mong đợi**: Trạng thái = **Yêu cầu sửa**; bản ghi Xác nhận loại `revision_requested`; Mailpit có email nội bộ; **cửa sổ confirm của khách không còn** (canConfirm=false); tab Phê duyệt **không** có bản ghi mới.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-14 | Tạo bản sửa (Revision) — bản cũ → superseded | Ưu tiên: Trung bình | Tác nhân: Admin
- **Mục tiêu**: Admin tạo phiên bản mới từ báo giá revision_requested.
- **Tiền điều kiện**: Báo giá ở trạng thái **Yêu cầu sửa**; đăng nhập Admin.
- **Bước thực hiện**:
  1. Mở báo giá Yêu cầu sửa → bấm **Sửa** (nếu action hiển thị — trạng thái này nằm trong `isEditable()`)
  2. Điều chỉnh nội dung (thêm 1 item mới) → **Lưu**
  3. Kiểm tra danh sách Báo giá
- **Kết quả mong đợi**: Sau tạo bản mới (qua service revision): bản cũ = **Thay thế** (superseded); bản mới = **Nháp**, cùng mã `QT…` nhưng **version tăng 1**, có `parent_quotation_id`/`replaces_quotation_id` trỏ bản cũ.
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:
> ⚠️ Chưa xác minh có action Filament trực tiếp gọi `QuotationRevisionService` — nếu không có action thì ghi Blocked + báo cáo là "thiếu UI".

---

### TC-15 | Khách mở link bản đã thay thế | Ưu tiên: Trung bình | Tác nhân: Khách hàng
- **Tiền điều kiện**: Báo giá superseded có public link cũ.
- **Bước thực hiện**: Mở public link của bản superseded ở cửa sổ ẩn danh.
- **Kết quả mong đợi**: Trang hiển thị banner vàng: *"Báo giá này đã được thay thế bởi phiên bản mới hơn."*; **không hiện** khối Xác nhận; vẫn xem được nội dung.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-16 | Hủy báo giá (draft → cancelled) | Ưu tiên: Cao | Tác nhân: Admin
- **Tiền điều kiện**: Báo giá Nháp.
- **Bước thực hiện**:
  1. Menu Báo giá → tìm báo giá Nháp → bấm **Hủy** (icon x) hoặc mở Xem → **Hủy**
  2. Xác nhận nếu có modal
- **Kết quả mong đợi**: Trạng thái = **Đã hủy**; có `cancelled_at`; không còn nút Hủy/Edit; báo giá không xuất hiện trong các luồng gửi/duyệt.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-17 | Hủy báo giá ở trạng thái Chờ duyệt / Đã duyệt | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Tạo báo giá → Gửi duyệt (hoặc duyệt) → bấm **Hủy**.
- **Kết quả mong đợi**: Hủy thành công từ cả hai trạng thái; trạng thái = Đã hủy.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-18 | Không thể hủy báo giá đã chấp nhận / từ chối / hết hạn (terminal) | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Mở báo giá accepted/rejected/expired → quan sát nút Hủy.
- **Kết quả mong đợi**: Không có nút **Hủy** ở trang chi tiết (visible=false vì `isTerminal`); ở danh sách cũng không có action Hủy.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-19 | Báo giá quá hạn tự chuyển expired (job) | Ưu tiên: Cao | Tác nhân: Hệ thống (Admin kích hoạt)
- **Mục tiêu**: Quotation approved/sent/viewed quá `valid_until` → **expired** + email nội bộ.
- **Tiền điều kiện**: Báo giá Approved có `valid_until` = hôm qua (tạo qua form với hạn ngày quá khứ); đăng nhập Admin.
- **Bước thực hiện**:
  1. Tạo báo giá, đặt **Hạn hiệu lực = ngày hôm qua**
  2. Gửi duyệt → Duyệt (trạng thái Approved)
  3. Chạy lệnh: `php artisan sales:process-reminders`
  4. Mở lại báo giá kiểm tra trạng thái; kiểm tra Mailpit
- **Kết quả mong đợi**: Trạng thái = **Hết hạn**; `expired_at` có giá trị; Mailpit có email `QuotationExpiredMail` gửi `sales@company.com`; khách không thể confirm.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-20 | Xuất PDF báo giá từ trang chi tiết | Ưu tiên: Cao | Tác nhân: Admin
- **Bước thực hiện**:
  1. Mở báo giá (bất kỳ chưa terminal)
  2. Bấm **Xuất PDF** (header action)
- **Kết quả mong đợi**: Tạo bản ghi Tài liệu (tab **Tài liệu**): tên `QT…-V1.pdf`, mime `application/pdf`, có kích thước/hash; nếu browser hiện file — nội dung khớp báo giá.
- **Kết quả thực tế**: _(tester điền)_
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:
> ⚠️ **Liên quan BUG-02** — xem phần 12.

---

### TC-21 | Tải PDF từ public link | Ưu tiên: Trung bình | Tác nhân: Khách hàng
- **Bước thực hiện**: Cửa sổ ẩn danh mở public link → bấm **Tải PDF**.
- **Kết quả mong đợi**: Tải về file PDF với nội dung báo giá; cũng làm tăng view_count.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:
> ⚠️ **Liên quan BUG-02**.

---

### TC-22 | In báo giá từ public link | Ưu tiên: Thấp | Tác nhân: Khách hàng
- **Bước thực hiện**: Mở public link → bấm **In báo giá**.
- **Kết quả mong đợi**: Hộp thoại in trình duyệt hiện ra; các nút thao tác (Chấp nhận/Tải PDF/In) bị ẩn khi in.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 7. TC-23 → TC-40: Phân quyền (Admin vs Staff)

---

### TC-23 | CS Staff chỉ thấy báo giá của mình | Ưu tiên: Cao | Tác nhân: CS Staff
- **Tiền điều kiện**: Có 2 staff (staff1, staff2); mỗi staff đã tạo ≥1 báo giá; admin tạo 1 báo giá nữa.
- **Bước thực hiện**:
  1. Đăng nhập staff1 → menu **Bán hàng → Báo giá**
  2. So sánh danh sách với admin
- **Kết quả mong đợi**: staff1 chỉ thấy: báo giá do staff1 tạo + báo giá có `assigned_staff = staff1`; **không thấy** báo giá của staff2 và của admin.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-24 | Admin / CS Manager thấy tất cả báo giá | Ưu tiên: Cao | Tác nhân: Admin, CS Manager
- **Bước thực hiện**: Đăng nhập Admin (và CS Manager) → vào danh sách Báo giá.
- **Kết quả mong đợi**: Thấy đầy đủ mọi báo giá của tất cả staff.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-25 | Staff không thể mở trực tiếp báo giá của người khác (URL) | Ưu tiên: Trung bình | Tác nhân: CS Staff
- **Bước thực hiện**: Lấy URL chi tiết báo giá của staff2 (nhờ admin mở rồi copy) → đăng nhập staff1 → dán URL vào trình duyệt.
- **Kết quả mong đợi**: **403 / Not Found** (policy view + getEloquentQuery chặn).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-26 | Staff không thể gửi email báo giá | Ưu tiên: Cao | Tác nhân: CS Staff
- **Tiền điều kiện**: Báo giá Approved của staff1.
- **Bước thực hiện**: Đăng nhập staff1 → mở báo giá Approved → quan sát header actions.
- **Kết quả mong đợi**: **Không có nút Gửi** (gate `sales.send-quotations` + visible chỉ admin/CS manager).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-27 | Staff không thể duyệt / từ chối duyệt | Ưu tiên: Cao | Tác nhân: CS Staff
- **Bước thực hiện**: Đăng nhập staff1 → mở báo giá Chờ duyệt của mình.
- **Kết quả mong đợi**: Không có nút **Duyệt / Từ chối duyệt**; menu **Phê duyệt báo giá** không hiển thị.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-28 | Staff không thể hủy báo giá | Ưu tiên: Cao | Tác nhân: CS Staff
- **Bước thực hiện**: staff1 mở báo giá Nháp của mình.
- **Kết quả mong đợi**: Không có nút **Hủy** (gate `sales.cancel-quotations`).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-29 | Staff không thể vào Theo dõi thanh toán | Ưu tiên: Cao | Tác nhân: CS Staff
- **Bước thực hiện**: staff1 tìm menu **Theo dõi thanh toán**.
- **Kết quả mong đợi**: Menu không hiển thị; dán URL trực tiếp → 403.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-30 | Staff không thể vào Dashboard Bán hàng | Ưu tiên: Trung bình | Tác nhân: CS Staff
- **Bước thực hiện**: staff1 tìm menu **Dashboard Bán hàng** (nếu hiển thị) hoặc dán URL.
- **Kết quả mong đợi**: Không truy cập được (canAccess chỉ admin + CS manager).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-31 | Chỉ Admin quản lý Dịch vụ / Gói dịch vụ | Ưu tiên: Trung bình | Tác nhân: Admin, CS Manager, CS Staff
- **Bước thực hiện**: Lần lượt đăng nhập từng role, kiểm tra menu **Dịch vụ** và **Gói dịch vụ**.
- **Kết quả mong đợi**: Admin thấy + CRUD đầy đủ; CS Manager/Staff **không thấy menu**, truy cập URL → 403.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-32 | Chỉ Admin quản lý Bảng giá & Tài khoản ngân hàng | Ưu tiên: Trung bình | Tác nhân: Admin, CS Manager
- **Bước thực hiện**: Kiểm tra menu **Bảng giá** và **Tài khoản ngân hàng** với từng role.
- **Kết quả mong đợi**: Chỉ Admin CRUD được; CS Manager không thấy menu.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-33 | CS Manager được duyệt và gửi nhưng không quản lý danh mục | Ưu tiên: Cao | Tác nhân: CS Manager
- **Bước thực hiện**: Đăng nhập CS Manager: (1) duyệt 1 báo giá Chờ duyệt; (2) gửi 1 báo giá Approved; (3) kiểm tra menu Dịch vụ/Bảng giá.
- **Kết quả mong đợi**: (1)(2) thành công; (3) menu danh mục không hiển thị.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-34 | CS Manager thao tác Theo dõi thanh toán | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Bước thực hiện**: CS Manager vào **Theo dõi thanh toán** → mark_paid 1 báo giá accepted.
- **Kết quả mong đợi**: Thành công (được phép theo gate + canViewAny).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-35 | Marketing Staff không tạo được báo giá | Ưu tiên: Trung bình | Tác nhân: Marketing Staff
- **Bước thực hiện**: Đăng nhập user `marketing_staff` → menu Báo giá → nút **Tạo**.
- **Kết quả mong đợi**: Không có nút Tạo (gate `sales.create-quotations` = false); URL /create → 403.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-36 | Staff không tạo được báo giá khi không có quyền bảng giá | Ưu tiên: Cao | Tác nhân: CS Staff
- **Tiền điều kiện**: Có bảng giá `access_type = staff` **không** cấp `can_create_quotation` cho staff1.
- **Bước thực hiện**: staff1 mở form Tạo báo giá → chọn bảng giá không có quyền.
- **Kết quả mong đợi**: Bảng giá đó **không xuất hiện** trong dropdown (bộ lọc `PriceBookAccessService::getAccessiblePriceBooks`); nếu chọn được thì save → lỗi "You do not have permission to use this price book."
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-37 | Admin tạo báo giá không cần assignment | Ưu tiên: Cao | Tác nhân: Admin
- **Bước thực hiện**: Admin tạo báo giá cho khách **không** được gán cho ai.
- **Kết quả mong đợi**: Tạo thành công (bỏ qua validateAssignment cho admin/CS manager).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-38 | Staff tạo báo giá cho khách chưa được gán → lỗi | Ưu tiên: Cao | Tác nhân: CS Staff
- **Tiền điều kiện**: Khách hàng chưa có assignment active cho staff1.
- **Bước thực hiện**: staff1 vào form Tạo → chọn khách không được gán → Lưu.
- **Kết quả mong đợi**: Không tạo được; lỗi *"You are not assigned to this customer."* (hoặc message tương tự); danh sách vẫn không có báo giá mới.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-39 | Staff sửa báo giá của người khác → bị chặn | Ưu tiên: Trung bình | Tác nhân: CS Staff
- **Bước thực hiện**: staff1 mở báo giá Draft do staff2 tạo → bấm **Sửa**.
- **Kết quả mong đợi**: Không có nút Sửa hoặc bị chặn (policy `update`: admin hoặc người tạo).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-40 | Sửa báo giá đã gửi/đã xem → không cho sửa | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Mở báo giá trạng thái Đã gửi → bấm **Sửa**.
- **Kết quả mong đợi**: Không có nút Sửa (`isEditable()` = false).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 8. TC-41 → TC-60: Ràng buộc nghiệp vụ

---

### TC-41 | Khách hàng archived/blocked → không tạo báo giá | Ưu tiên: Cao | Tác nhân: Admin
- **Tiền điều kiện**: Khách có `status = archived`.
- **Bước thực hiện**: Admin tạo báo giá cho khách archived.
- **Kết quả mong đợi**: Không tạo được; lỗi *"Customer is not available for quotations."*
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-42 | Bảng giá sai audience_type → không tạo được | Ưu tiên: Trung bình | Tác nhân: Admin
- **Tiền điều kiện**: Khách personal + bảng giá `audience_type = business`.
- **Bước thực hiện**: Admin tạo báo giá, cố chọn bảng giá business cho khách personal.
- **Kết quả mong đợi**: Bảng giá không xuất hiện trong dropdown hoặc save → lỗi *"Price book audience type does not match customer."*
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-43 | Tạo báo giá thiếu trường bắt buộc → validation | Ưu tiên: Cao | Tác nhân: Admin
- **Bước thực hiện**: Form Tạo → bỏ trống Khách hàng, Tiêu đề, Hạn hiệu lực; item để trống tên dịch vụ/đơn giá → Lưu.
- **Kết quả mong đợi**: Hiện lỗi validation đỏ cho từng trường; không tạo được bản ghi.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-44 | Hạn hiệu lực nhỏ hơn ngày báo giá | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: Ngày = hôm nay; Hạn hiệu lực = hôm qua → Lưu.
- **Kết quả mong đợi**: (xác minh hành vi) — nếu không có rule chặn, ghi nhận là hành vi hiện tại; báo giá vẫn tạo nhưng sẽ hết hạn ngay khi job chạy.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-45 | Item số lượng = 0 hoặc âm | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Tạo báo giá với item `quantity = 0`, `unit_price = 0` → Lưu.
- **Kết quả mong đợi**: Xác minh validation (numeric cho phép 0?) — ghi nhận hành vi; tổng tiền nên = 0.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-46 | Discount % vượt 100 | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: discount_type = percent, discount_value = 150 → Lưu.
- **Kết quả mong đợi**: Ghi nhận hành vi (nếu cho phép → line_total âm; cần báo cáo nếu là lỗi logic).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-47 | VAT âm | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: vat_rate = -5 → Lưu.
- **Kết quả mong đợi**: Ghi nhận hành vi validation.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-48 | Khách mở link sai token → 404 | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Bước thực hiện**: Sửa token trong URL thành chuỗi sai → mở.
- **Kết quả mong đợi**: Trang **404 Not Found**.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-49 | Khách mở link đúng mã nhưng sai token | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Bước thực hiện**: URL `/q/QT…/xxxx` với token sai.
- **Kết quả mong đợi**: 404 (khớp cả 2 trường).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-50 | Rate limit public route (30 req/phút) | Ưu tiên: Trung bình | Tác nhân: Khách hàng
- **Bước thực hiện**: Dùng curl/script gửi >30 request GET đến public link trong 1 phút.
- **Kết quả mong đợi**: Request thứ 31 trả về **429 Too Many Requests**.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-51 | Khách confirm báo giá đã hết hạn → bị chặn | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Tiền điều kiện**: Báo giá hết hạn (expired) — dùng TC-19 tạo.
- **Bước thực hiện**: Mở public link báo giá expired → bấm Chấp nhận → nhập form → Gửi.
- **Kết quả mong đợi**: Không có khối Xác nhận trên trang (canConfirm=false); nếu post tay → lỗi *"This quotation has expired."*; trạng thái giữ nguyên.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-52 | Khách confirm 2 lần → lần 2 bị chặn | Ưu tiên: Cao | Tác nhân: Khách hàng
- **Tiền điều kiện**: Báo giá đã accepted.
- **Bước thực hiện**: Mở lại public link → quan sát; post tay accept lần 2.
- **Kết quả mong đợi**: Trang không hiển thị khối Xác nhận (status accepted = không canConfirm); post tay → lỗi; không tạo bản ghi confirmation thứ 2.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-53 | Khách confirm thiếu trường bắt buộc (accept) | Ưu tiên: Trung bình | Tác nhân: Khách hàng
- **Bước thực hiện**: Bấm **Chấp nhận** → bỏ trống Họ tên/Email → Gửi.
- **Kết quả mong đợi**: Validation hiện lỗi "bắt buộc"; trạng thái không đổi; không tạo confirmation.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-54 | Request revision thiếu lý do → validation | Ưu tiên: Trung bình | Tác nhân: Khách hàng
- **Bước thực hiện**: Bấm **Yêu cầu chỉnh sửa** → để trống Lý do → Gửi.
- **Kết quả mong đợi**: Lỗi validation (reason bắt buộc trên server); trạng thái giữ nguyên.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-55 | Email người nhận không hợp lệ khi gửi | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Bước thực hiện**: Nút **Gửi** → nhập email `abc@xyz` (không hợp lệ) → gửi.
- **Kết quả mong đợi**: Lỗi validation email; không tạo EmailLog.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-56 | Gửi email khi báo giá chưa duyệt → không có nút Gửi | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Bước thực hiện**: Mở báo giá Nháp → tìm nút **Gửi**.
- **Kết quả mong đợi**: Không có nút Gửi (chỉ Approved mới `canSend()`).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-57 | Nút Gửi trên bảng danh sách (table action) | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Bước thực hiện**: Danh sách Báo giá → báo giá Approved → bấm **Gửi** ở cột Thao tác.
- **Kết quả mong đợi**: ⚠️ **Xem BUG-01** (route không tồn tại → 404). Nếu có lỗi, ghi nhận và đánh dấu Fail.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-58 | Tab RelationManager hiển thị đúng dữ liệu | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Mở báo giá đã trải qua accept + gửi email + tạo PDF: kiểm tra 5 tab **Mục báo giá / Xác nhận / Lịch sử email / Tài liệu / Phê duyệt**.
- **Kết quả mong đợi**: Từng tab hiển thị đúng các bản ghi tương ứng; cột không trống lệch.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-59 | Search & filter danh sách báo giá | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: (1) Gõ tìm theo mã báo giá, tên khách, tiêu đề; (2) Filter theo Trạng thái = Accepted; (3) Filter theo Trạng thái thanh toán = Đã thanh toán; (4) Filter theo Nhân viên phụ trách.
- **Kết quả mong đợi**: Kết quả lọc đúng; search tìm đúng chuỗi.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-60 | Pagination & sort danh sách | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: Tạo ≥11 báo giá → phân trang 10/25/50; sort theo grand_total, quotation_date, view_count.
- **Kết quả mong đợi**: Phân trang đúng số dòng; sort đúng thứ tự.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 9. TC-61 → TC-72: UI / Form

---

### TC-61 | Tính toán tổng tiền chính xác (fixed discount) | Ưu tiên: Cao | Tác nhân: Admin
- **Bước thực hiện**: 1 item: SL=2, đơn giá=500.000, discount_type=fixed, discount_value=50.000, VAT=10 → Lưu.
- **Kết quả mong đợi**: subtotal=1.000.000; discount=50.000; tax=(1.000.000−50.000)×10%=95.000; grand_total=1.045.000. Kiểm tra số hiển thị trên trang Xem.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-62 | Repeater: thêm / xóa dòng item | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Form Tạo → bấm **+** thêm 3 dòng → bấm xóa 1 dòng → Lưu.
- **Kết quả mong đợi**: Báo giá lưu đúng 2 dòng còn lại, đúng sort_order.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-63 | Currency hiển thị | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: Tạo báo giá với bảng giá currency = VND; xem cột grand_total.
- **Kết quả mong đợi**: Hiển thị đúng định dạng tiền tệ + ký hiệu VND.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-64 | Xem báo giá của khách business (company snapshot) | Ưu tiên: Trung bình | Tác nhân: Admin → Khách
- **Bước thực hiện**: Tạo báo giá cho khách business → mở public link.
- **Kết quả mong đợi**: Trang public hiển thị tên công ty, MST, địa chỉ; thông tin khớp snapshot.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-65 | Header actions ẩn/hiện theo trạng thái | Ưu tiên: Cao | Tác nhân: Admin
- **Bước thực hiện**: Lần lượt mở báo giá ở các trạng thái: Nháp, Chờ duyệt, Đã duyệt, Đã gửi, Đã xem, Đã chấp nhận, Đã hủy.
- **Kết quả mong đợi**:
  - Nháp: Sửa, Gửi duyệt, Xuất PDF, Hủy, Link public
  - Chờ duyệt (admin): Sửa, Duyệt, Từ chối duyệt, Xuất PDF, Hủy, Link public
  - Đã duyệt: Gửi, Xuất PDF, Hủy, Link public (hết Sửa, Gửi duyệt, Duyệt)
  - Đã gửi/Đã xem: Xuất PDF, Hủy, Link public
  - Accepted/Rejected/Expired/Cancelled: chỉ Xuất PDF? + Link public (không Hủy)
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-66 | Nút "Link public" mở tab mới đúng URL | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Mở báo giá → bấm **Link public**.
- **Kết quả mong đợi**: Mở tab mới đến `/q/{code}/{token}`, hiển thị báo giá.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-67 | Form tạo: dropdown bảng giá bị giới hạn theo quyền | Ưu tiên: Trung bình | Tác nhân: CS Staff
- **Bước thực hiện**: staff1 mở form Tạo → mở dropdown Bảng giá.
- **Kết quả mong đợi**: Chỉ thấy các bảng giá staff1 có quyền `can_create_quotation`.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-68 | Giao diện dashboard bán hàng | Ưu tiên: Trung bình | Tác nhân: Admin, CS Manager
- **Bước thực hiện**: Vào **Dashboard Bán hàng**; so sánh số liệu với danh sách Báo giá.
- **Kết quả mong đợi**: Các KPI (tổng, theo trạng thái, tổng giá trị, cần follow-up, tỷ lệ chuyển đổi) khớp dữ liệu thực tế (sai số chấp nhận theo cách đếm).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-69 | Đổi ngôn ngữ ảnh hưởng giao diện Sales | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: Chuyển ngôn ngữ Việt/English → duyệt các màn hình Sales.
- **Kết quả mong đợi**: Label chuyển đúng ngôn ngữ, không lỗi hiển thị.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-70 | Form edit giữ nguyên dữ liệu snapshot | Ưu tiên: Trung bình | Tác nhân: Admin
- **Bước thực hiện**: Sửa báo giá Draft: đổi tên tiêu đề, đổi SL 1 item → Lưu → xem lại.
- **Kết quả mong đợi**: Dữ liệu mới được lưu; các field không sửa giữ nguyên; tổng tiền tính lại đúng.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-71 | Sau khi sửa, các mốc thời gian được cập nhật | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: Xem audit trước/sau khi sửa (tab hoặc bảng audit_logs).
- **Kết quả mong đợi**: Có bản ghi audit `quotation.updated` với dữ liệu mới.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-72 | Xem chi tiết báo giá hiển thị snapshot khách hàng | Ưu tiên: Thấp | Tác nhân: Admin
- **Bước thực hiện**: Đổi email khách sau khi tạo báo giá → mở lại báo giá.
- **Kết quả mong đợi**: Báo giá vẫn hiển thị email cũ (snapshot được chụp lúc tạo).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 10. TC-73 → TC-78: Email & queue

---

### TC-73 | EmailLog: queued → sending → sent | Ưu tiên: Cao | Tác nhân: CS Manager + System
- **Bước thực hiện**: Gửi báo giá → quan sát tab Lịch sử email ngay lập tức (trước khi job chạy) → chạy `php artisan queue:work --once --queue=quotations` → xem lại.
- **Kết quả mong đợi**: Status EmailLog đi qua các trạng thái và kết thúc **Đã gửi**; `sent_at` có giá trị; quotation `email_status` = sent.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-74 | Gửi email thất bại → EmailLog failed + email_status failed | Ưu tiên: Cao | Tác nhân: CS Manager
- **Tiền điều kiện**: Cấu hình `MAIL_MAILER=log` hoặc SMTP sai host (để mailer fail).
- **Bước thực hiện**: Gửi báo giá → chạy queue → xem EmailLog.
- **Kết quả mong đợi**: EmailLog status = **Thất bại** kèm error_message; quotation `email_status` = failed; trạng thái báo giá giữ nguyên (approved/sent).
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-75 | Email khách chứa đúng thông tin | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Bước thực hiện**: Sau khi gửi, mở email trong Mailpit.
- **Kết quả mong đợi**: Tiêu đề chứa mã + tên; body có tổng tiền, hạn hiệu lực, nút **Xem báo giá** với URL đúng; không có lỗi render.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-76 | Email nội bộ khi khách accept/reject/revision | Ưu tiên: Trung bình | Tác nhân: Khách + System
- **Bước thực hiện**: Lần lượt thực hiện accept/reject/request-revision trên 3 báo giá → kiểm tra Mailpit.
- **Kết quả mong đợi**: 3 email nội bộ đến đúng địa chỉ cấu hình (accepted→accounting@, rejected/revision→sales@), nội dung chứa mã báo giá.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-77 | Email xác nhận thanh toán đến khách | Ưu tiên: Trung bình | Tác nhân: Admin + System
- **Bước thực hiện**: Sau mark_paid (TC-09) → kiểm tra Mailpit.
- **Kết quả mong đợi**: Email PaymentConfirmed đến email khách, nội dung rõ ràng.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-78 | Gửi lại email (resend) | Ưu tiên: Trung bình | Tác nhân: CS Manager
- **Bước thực hiện**: Báo giá Approved → gửi 2 lần (2 email khác nhau).
- **Kết quả mong đợi**: Có 2 EmailLog; email gửi đến đúng 2 địa chỉ; `QuotationMail` + template `quotation-resent` (nếu có) hoạt động.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 11. TC-79 → TC-82: Tự động hoá (reminder + expire)

> Chạy thủ công để test: `php artisan sales:process-reminders` (thường chạy mỗi phút qua schedule).

### TC-79 | Nhắc follow-up: sent chưa xem ≥ 2 ngày | Ưu tiên: Trung bình | Tác nhân: Hệ thống
- **Tiền điều kiện**: Báo giá `sent` với `sent_at` cách đây ≥2 ngày (sửa DB qua tinker nếu cần).
- **Bước thực hiện**: Chạy `php artisan sales:process-reminders`.
- **Kết quả mong đợi**: Tạo CustomerInteraction loại follow_up (status=scheduled, next_follow_up_at = +1 ngày); email nhắc (nếu có) gửi nội bộ; xuất hiện trong danh sách "cần theo dõi" của Customer Care.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-80 | Nhắc follow-up: viewed chưa phản hồi ≥ 3 ngày | Ưu tiên: Trung bình | Tác nhân: Hệ thống
- **Tiền điều kiện**: Báo giá `viewed` cách đây ≥3 ngày.
- **Bước thực hiện**: Chạy `php artisan sales:process-reminders`.
- **Kết quả mong đợi**: Tạo follow-up tương tự TC-79.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-81 | Nhắc sắp hết hạn (valid_until = +2 ngày) | Ưu tiên: Trung bình | Tác nhân: Hệ thống
- **Tiền điều kiện**: Báo giá sent/viewed có `valid_until` đúng 2 ngày tới.
- **Bước thực hiện**: Chạy command.
- **Kết quả mong đợi**: Email `QuotationExpiringMail` nội bộ; follow-up scheduled.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

### TC-82 | Nhắc accepted chưa thanh toán | Ưu tiên: Trung bình | Tác nhân: Hệ thống
- **Tiền điều kiện**: Báo giá accepted, payment_status = unpaid.
- **Bước thực hiện**: Chạy command.
- **Kết quả mong đợi**: Follow-up được tạo cho staff phụ trách.
- **Kết luận**: ☐ Pass ☐ Fail ☐ Blocked — Ghi chú:

---

## 12. TC-BUG-01 → TC-BUG-04: Các test dự kiến FAIL (báo cáo bug)

> Các trường hợp này **dự kiến thất bại** dựa trên rà soát mã nguồn. Tester **phải thực hiện đúng bước**, ghi nhận lỗi, và đối chiếu với mô tả dưới đây để xác nhận bug.

---

### TC-BUG-01 | Nút "Gửi" trên bảng danh sách Báo giá trỏ route không tồn tại | Ưu tiên: Cao | Tác nhân: CS Manager
- **Mục tiêu**: Xác nhận lỗi route của table action Send.
- **Tiền điều kiện**: Báo giá trạng thái **Approved**; đăng nhập user có quyền gửi (Admin/CS Manager).
- **Bước thực hiện**:
  1. Menu **Bán hàng → Báo giá**
  2. Tìm báo giá Approved
  3. Bấm nút **Gửi** ở cột Thao tác (hàng tương ứng)
- **Kết quả mong đợi**: (nếu đúng thiết kế) mở modal gửi email hoặc chuyển đến trang gửi.
- **Trạng thái: ✅ ĐÃ SỬA** — Table action `send` không còn trỏ route chết; giờ mở modal gửi tại chỗ (khách hàng + email + chọn mẫu + sửa tiêu đề/nội dung). Test E2E: PASS.
- **Kết luận**: ☑ Pass

---

### TC-BUG-02 | Xuất PDF / gửi email / tải PDF public → lỗi DocumentType::Pdf | Ưu tiên: Cao | Tác nhân: Admin
- **Mục tiêu**: Xác nhận luồng PDF bị lỗi do enum thiếu case.
- **Tiền điều kiện**: Báo giá chưa terminal.
- **Bước thực hiện**:
  1. Mở báo giá → bấm **Xuất PDF** (hoặc qua luồng Gửi email TC-06, hoặc mở URL `/q/{code}/{token}/pdf`)
- **Kết quả mong đợi**: Tải về / tạo file PDF thành công.
- **Trạng thái: ✅ ĐÃ SỬA** — `DocumentType` đã có `case Pdf = 'pdf'`; ngoài ra đã sửa chuỗi lỗi kế tiếp: `Hash::file()` → `hash_file()`, `QuotationMail::$subject` → `$subjectText`. Test: xuất PDF, gửi email, tải PDF public đều OK.
- **Kết luận**: ☑ Pass

---

### TC-BUG-03 | Báo giá mới tạo — đọc `email_status` bị lỗi enum | Ưu tiên: Trung bình | Tác nhân: Admin
- **Mục tiêu**: Xác nhận giá trị `email_status` không hợp lệ khi khởi tạo.
- **Tiền điều kiện**: Tạo báo giá mới (TC-01).
- **Bước thực hiện**:
  1. Tạo báo giá mới
  2. Mở trang Xem báo giá, quan sát (nếu UI hiển thị email_status) hoặc kiểm tra qua `php artisan tinker`: `App\Models\Sales\Quotation::latest()->first()->email_status`
- **Kết quả mong đợi**: Trả về enum hợp lệ (`unsent`/`queued`/...) hoặc hiển thị trạng thái email.
- **Trạng thái: ✅ ĐÃ SỬA** — `QuotationCreationService` gán `email_status` hợp lệ; migration default đã đổi. Test: tạo báo giá mới đọc được enum `unsent`.
- **Kết luận**: ☑ Pass

---

### TC-BUG-04 | Luồng OTP & QR thanh toán chưa hoàn thiện UI | Ưu tiên: Thấp | Tác nhân: Khách hàng
- **Mục tiêu**: Ghi nhận hiện trạng OTP/QR chưa có UI.
- **Bước thực hiện**:
  1. Mở public link báo giá ở trạng thái canConfirm → quan sát: có ô nhập OTP/xác thực SĐT không?
  2. Quan sát khối Thông tin thanh toán: có mã QR chuyển khoản không?
  3. (Tùy chọn) Test API: `POST /q/{code}/{token}/send-otp` với `{email}` → kiểm tra `storage/logs/laravel.log` có dòng "OTP sent" chứa mã OTP.
- **Kết quả mong đợi**: (theo spec) có giao diện OTP và QR thanh toán.
- **Hiện trạng (cập nhật)**: **QR đã có** — PDF báo giá hiển thị mã QR VietQR (ảnh `img.vietqr.io`, không cần API key) trong khối Thông tin thanh toán khi báo giá có `payment_snapshot` với `bank_code` + `account_number` (test PASS). **OTP vẫn chưa có UI nhập** — chỉ log, không gửi email thật → vẫn **Blocked** cho phần OTP.
- **Kết luận**: ☐ Pass ☑ Blocked (chỉ phần OTP)

---

## 12b. Tính năng mới bổ sung (đã test PASS)

| Tính năng | Mô tả | Vị trí |
|---|---|---|
| Cài đặt công ty | Trang Cài đặt → Cài đặt công ty (admin): tên công ty, mã số thuế, địa chỉ, SĐT, email, website, logo. Tên công ty thay cho `APP_NAME` trong PDF + mọi email báo giá | `app/Filament/Pages/CompanySettingsPage.php`; helper `company_name()` |
| Chọn mẫu email khi gửi | Modal Gửi (trang Xem + bảng danh sách) có dropdown "Mẫu email" → tự điền tiêu đề + nội dung đã thay placeholder; vẫn sửa tay được | `ViewQuotation.php`, `QuotationResource.php` |
| Placeholder chèn 1 chạm | Email Template: chọn loại mẫu (Marketing/Báo giá); bảng nút biến `{{...}}` (bằng tiếng Việt) — bấm là chèn tại con trỏ trong trình soạn Trix | `EmailTemplateResource.php`, `resources/views/filament/placeholders/template-tokens.blade.php` |
| Renderer mẫu báo giá | `QuotationTemplateRenderer`: 13 biến báo giá (mã, tiêu đề, tổng tiền, ngày, hiệu lực, khách hàng, nhân viên, link public...) | `app/Services/Sales/QuotationTemplateRenderer.php` |
| Form tài khoản ngân hàng | Dropdown 37 ngân hàng VN (tự đồng bộ qua `sales:sync-vn-banks`, có fallback nhúng) → tự điền mã ngân hàng + SWIFT; bỏ trường Chi nhánh/QR khỏi form | `BankAccountResource.php`, `SyncVnBanks.php` |
| QR VietQR trong PDF | PDF báo giá hiển thị QR chuyển khoản khi có thông tin tài khoản | `quotation-pdf.blade.php` |

---

## 13. Checklist tổng hợp kết quả

| Nhóm | Số TC | Pass | Fail | Blocked | Ghi chú |
|---|---|---|---|---|---|
| Luồng chính (TC-01 → 09) | 9 | | | | |
| Luồng phụ (TC-10 → 22) | 13 | | | | |
| Phân quyền (TC-23 → 40) | 18 | | | | |
| Ràng buộc nghiệp vụ (TC-41 → 60) | 20 | | | | |
| UI/Form (TC-61 → 72) | 12 | | | | |
| Email & queue (TC-73 → 78) | 6 | | | | |
| Tự động hoá (TC-79 → 82) | 4 | | | | |
| Bug dự kiến (TC-BUG-01 → 04) | 4 | | | | |
| **Tổng** | **86** | | | | |

**Tỷ lệ Pass:** ___ / 86 (___ %)

---

## 14. Mẫu báo cáo bug

```markdown
### [BUG] Tiêu đề ngắn gọn

| Mục | Nội dung |
|---|---|
| ID / liên kết TC | TC-xx hoặc TC-BUG-xx |
| Môi trường | Local / Staging — PHP 8.2, Laravel 12, Filament 3 |
| Trình duyệt | Chrome/Firefox/Safari + phiên bản |
| Tác nhân | Admin / CS Manager / CS Staff / Khách |
| Mức độ | Cao / Trung bình / Thấp |

**Bước tái hiện:**
1. ...
2. ...

**Kết quả thực tế:**
...

**Kết quả mong đợi:**
...

**File/dòng nghi vấn (nếu có):**
`app/.../File.php:12`

**Ảnh chụp / log:**
[đính kèm]
```

---

## 15. Phụ lục: dữ liệu test tham chiếu

### 15.1. Tài khoản

| Email | Mật khẩu | Role |
|---|---|---|
| `admin@example.com` | `password` (tùy seeder) | admin |
| `manager@example.com` | `password` | customer_service_manager |
| `staff1@example.com` | `password` | customer_service_staff (Staff 1, có assignment) |
| `staff2@example.com` | `password` | customer_service_staff (Staff 2) |

### 15.2. Khách hàng (CRM)
- `Nguyễn Văn A` — personal, active, email `khach@example.com` (gán cho Staff 1)
- 1 khách `business` active (để test company snapshot)

### 15.3. Danh mục (SalesSeeder)
- 4 Dịch vụ + 4 Gói dịch vụ (active)
- 3 Bảng giá (personal/business/both) + 2 Tài khoản ngân hàng
- 1 báo giá mẫu (dùng để test đọc dữ liệu có sẵn)

### 15.4. URL tham chiếu

| Mục | URL |
|---|---|
| Trang chủ panel | `http://localhost:8000/admin` |
| Public báo giá | `http://localhost:8000/q/{quotation_code}/{public_token}` |
| PDF public | `.../pdf` |
| Accept | `POST .../accept` |
| Reject | `POST .../reject` |
| Request revision | `POST .../request-revision` |
| Send OTP | `POST .../send-otp` |
| Verify OTP | `POST .../verify-otp` |
| Mailpit UI | `http://localhost:8025` |
| Log | `storage/logs/laravel.log` |

---

## Phụ lục A: Danh sách rút gọn (quick run) cho smoke test

> Dùng khi cần test nhanh lần đầu (khoảng 30 phút): chạy theo thứ tự TC-01 → TC-09 → TC-BUG-01/02/03 (3 bug này **đã sửa xong** — cần xác nhận lại như regression).

| Bước | TC | Kiểm tra nhanh |
|---|---|---|
| 1 | TC-01 | Tạo báo giá (Admin) → mã tự sinh, tổng đúng |
| 2 | TC-03 | Gửi duyệt |
| 3 | TC-04 | Duyệt |
| 4 | TC-06 | Gửi email → Mailpit có email + link |
| 5 | TC-07 | Mở link ẩn danh → viewed |
| 6 | TC-08 | Accept → accepted |
| 7 | TC-09 | Mark paid → khách Active/Purchasing |
| 8 | TC-BUG-01..03 | Xác nhận 3 bug dự kiến |

---

*Hết tài liệu.*
