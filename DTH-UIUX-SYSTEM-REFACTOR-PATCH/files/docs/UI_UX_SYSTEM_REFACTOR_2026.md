# UI/UX System Refactor 2026

## Mục tiêu

Đợt refactor này chuẩn hóa giao diện Filament mà **không thay đổi Golden Path nghiệp vụ đã PASS**. Trọng tâm là kiến trúc thông tin, tính nhất quán, khả năng vận hành theo vai trò và khả năng đọc báo cáo.

Nguyên tắc chính:

- Giữ nền và hành vi mặc định của Filament, không thêm nền màu theo module.
- Một thuật ngữ nghiệp vụ chỉ có một cách dịch trên toàn hệ thống.
- Form được chia theo nhóm quyết định của người dùng, không theo thứ tự cột database.
- Action chính của nghiệp vụ có thể hiển thị trực tiếp; action phụ/ít dùng nằm trong Action Group.
- Status / Role / Department Function dùng một hệ màu chung, có thể cấu hình bởi Admin.
- Ngày hiển thị theo `dd/mm/yyyy`, ngày giờ theo `dd/mm/yyyy HH:mm`.
- Dashboard/báo cáo ưu tiên: KPI → cảnh báo → so sánh → drill-down.
- Navigation hiển thị theo quyền hiện có; người dùng không phải nhìn các module không liên quan.

## Kiến trúc module sau refactor

### 1. Email

Phục vụ vận hành email, không trộn với dữ liệu Marketing tổng quát.

- Campaigns email
- Email Templates
- Email Template Categories
- Campaign Reports
- Sending Accounts
- Sending Domains
- Suppression Entries

### 2. Marketing

Phục vụ thu hút và quản lý nguồn khách hàng.

- Marketing Campaigns
- Landing Pages
- Form Templates
- Form Submissions
- Contact Lists
- Segments
- Tags
- Custom Fields

### 3. CRM

Phục vụ tiếp nhận và xác minh lead trước khi bàn giao cho Sales.

- Company Matching
- Companies
- Leads

Các resource kỹ thuật/legacy như Contact, Personal Contact, Business Contact, Contact Qualification vẫn giữ code nhưng không làm navigation chính khi Business Flow V2 đang hoạt động.

### 4. Kinh doanh

Phục vụ pipeline từ Opportunity tới Quotation.

- Sales Overview
- Opportunities
- Quotations
- Quotation Approvals
- Price Books
- Services
- Service Packages

### 5. Tài chính

Tách khỏi Kinh doanh để đúng trách nhiệm Finance.

- Payment Tracking
- Payment History
- Revenue Report
- Bank Accounts

### 6. Chăm sóc khách hàng

Phục vụ khách hàng sau conversion/Paid.

- Customers
- Customer Care
- Customer Distribution Batches

### 7. Cấu hình

Chỉ dành cho cấu hình tổ chức/hệ thống.

- Departments
- Positions
- Staff
- Users
- Role & Permission Matrix
- Badge Styles
- Company Settings

### 8. Hệ thống

- Audit Logs

## Role-based landing

Sau đăng nhập, hệ thống đưa người dùng tới ngữ cảnh làm việc chính thay vì bắt tất cả bắt đầu ở Dashboard Admin:

| Vai trò | Điểm vào ưu tiên |
|---|---|
| Admin / Executive / Viewer | Dashboard tổng quan |
| Finance | Theo dõi thanh toán |
| Sales Manager / Sales Staff | Opportunities |
| CSKH Manager / CSKH Staff | Leads |
| Marketing Manager / Marketing Staff | Marketing Campaigns |

Quyền backend hiện có vẫn là nguồn quyết định cuối cùng; refactor chỉ làm UX phù hợp hơn với quyền đó.

## Design system

File global: `resources/views/filament/ui-system.blade.php`.

Chuẩn hóa:

- bán kính section/table/modal/input/button;
- khoảng cách navigation;
- hierarchy của heading/label;
- hover row của table;
- badge dạng pill;
- report metric card;
- report table;
- filter label;
- note/alert block.

Không cần `npm build` vì style được nạp bằng Filament render hook.

## Badge system có thể cấu hình

### Kiến trúc

- `app/Support/Ui/BadgePalette.php`
- `app/Models/System/UiBadgeStyle.php`
- `ui_badge_styles` table
- Filament resource **Cấu hình → Màu badge**

Admin có thể override màu theo:

1. `status`
2. `role`
3. `department_function`

Màu được giới hạn vào palette Filament:

- gray
- primary
- info
- success
- warning
- danger

Nếu chưa có cấu hình DB, hệ thống tự fallback về semantic color mặc định để không làm vỡ UI.

### Department cụ thể

Mỗi Department vẫn dùng field `color` hiện có. Điều này cho phép Sales, CSKH, Marketing... có màu riêng ở badge tên phòng ban. `DepartmentFunction` dùng palette cấu hình chung.

## Form UX conventions

### Select thay vì text khi dữ liệu có tập giá trị hữu hạn

Ví dụ đã chuẩn hóa:

- gender → Select;
- status → Select từ Enum/options;
- role → Select;
- department function → Select;
- service/package/campaign/landing page → Select relationship/options;
- boolean → Toggle thay vì Select Yes/No trong form.

### TextInput

Chỉ dùng cho giá trị thực sự nhập tự do: tên, email, số điện thoại, số tiền, code, subject...

### Date / DateTime

- ngày: `dd/mm/yyyy`;
- ngày giờ: `dd/mm/yyyy HH:mm`;
- các date picker quan trọng dùng non-native picker khi cần UX nhất quán.

### Form grouping

Các form lớn được chia theo quyết định nghiệp vụ. Ví dụ:

- Marketing Campaign: Thông tin chiến dịch → Phạm vi quảng bá → Thời gian & ngân sách → Ghi chú.
- User: Liên kết nhân sự → Tài khoản & phân quyền.
- Staff: Tổ chức & tài khoản → Hồ sơ → Năng lực phân phối & thời gian làm việc.
- Email Template: Thông tin mẫu → Nội dung.

## Language consistency

`lang/vi.json` và `lang/en.json` được mở rộng để loại bỏ các label/helper/notification tiếng Việt hard-code trong Filament Resource/Widget chính.

Quy ước:

- `Campaign` trong module Email = **Chiến dịch Email** trong ngữ cảnh cần phân biệt.
- `MarketingCampaign` = **Chiến dịch Marketing**.
- `utm_campaign` = **UTM Campaign**.
- `Service` = **Dịch vụ**.
- `Service Package` = **Gói dịch vụ**.
- `Payment` = **Thanh toán**.
- `Payment Notice` = **Thông báo chuyển khoản** / thông báo thanh toán theo ngữ cảnh.

Các thuật ngữ kỹ thuật như UTM, SMTP, DKIM, SPF, DMARC, VAT, CAC, ROAS được giữ nguyên khi dịch sẽ làm mất nghĩa.

## Table UX

- Status dùng badge và màu semantic.
- Department dùng badge màu của Department.
- Role dùng badge màu của Role palette.
- Ngày giờ thống nhất format.
- Action phụ được gom vào dấu `⋮`.
- Action workflow quan trọng (Paid, Reject, Accept Match...) có thể vẫn hiển thị rõ thay vì bị giấu vào menu.
- Các cột phụ dùng `toggleable()` ở các màn hình có mật độ thông tin cao.

## Report UX

### Sales Overview

Thứ tự hiển thị:

1. KPI chính;
2. Pipeline health;
3. Conversion rate;
4. Cảnh báo sắp hết hạn;
5. Follow-up theo nhân viên.

### Revenue Report

Thứ tự hiển thị:

1. Bộ lọc chung;
2. Gross collected / Net revenue / VAT / Payments / Customers / Average payment / Pending / Outstanding;
3. Hiệu quả Marketing Campaign;
4. Nguồn UTM;
5. Landing Page;
6. UTM Campaign / Content;
7. Service;
8. Package;
9. Sales;
10. Customer;
11. Email Campaign.

Mục tiêu là người quản lý có thể đi từ **“thu bao nhiêu tiền?”** tới **“tiền đến từ chiến dịch/kênh/dịch vụ/gói/sales nào?”** trong cùng một trang.

## Customer Care

Admin/Executive/Viewer vẫn có thể xem/audit nhưng thông báo read-only được hiển thị rõ. Nhân viên CSKH được phân công mới là người thực hiện email/call/message theo policy hiện tại.

## Những gì refactor KHÔNG thay đổi

- state machine Golden Path;
- conversion Lead → Opportunity → Quotation → Payment → Customer;
- authorization backend hiện hành;
- Payment ledger / attribution logic;
- SMTP/mail flow;
- quotation public/PDF business logic;
- queue/scheduler strategy.

## Migration mới

Chỉ có migration UI:

`2026_08_09_170000_create_ui_badge_styles_table.php`

Không sửa dữ liệu nghiệp vụ.

## Áp dụng

Sau khi copy patch:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Không cần `npm install`, `npm run build` hoặc queue worker cho riêng đợt refactor UI này.

## Regression checklist

Sau deploy nên smoke-test theo vai trò:

### Admin

- Dashboard mở được;
- navigation đủ 8 module theo quyền;
- Cấu hình → Màu badge hoạt động;
- chỉnh một màu status và kiểm tra badge thay đổi;
- Customer Care vẫn read-only theo policy.

### Marketing Manager

- Marketing Campaign;
- Landing Page;
- Form Template;
- Submission;
- báo cáo Campaign/UTM nếu có quyền.

### CSKH Manager

- Leads;
- Auto Distribute;
- Assign/Reassign chỉ thấy nhân sự CSKH hợp lệ;
- Customer Distribution.

### CSKH Staff

- Leads được giao;
- qualification/follow-up;
- Customer Care chỉ khách được phân công.

### Sales Manager / Staff

- Opportunities;
- Price Book theo access rule;
- Quotation/Approval;
- Sales Overview.

### Finance

- Payment Tracking;
- Evidence modal;
- Payment History;
- Revenue Report;
- Bank Accounts.

### Language

Chuyển VI ↔ EN và kiểm tra ít nhất:

- navigation;
- form label/helper;
- enum/status badge;
- dashboard;
- revenue report;
- payment evidence;
- Customer Care read-only notice.

## Rollback

Script patch tự backup các file bị ghi đè vào thư mục:

`_uiux_refactor_backup_<timestamp>`

Nếu cần rollback code, copy file từ thư mục backup trở lại project. Migration `ui_badge_styles` chỉ chứa cấu hình màu UI và không liên quan dữ liệu nghiệp vụ; có thể rollback migration riêng nếu thực sự cần.
