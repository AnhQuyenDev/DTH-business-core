# MASTER PROMPT TRIỂN KHAI PHÂN HỆ SALES, BẢNG GIÁ, BÁO GIÁ VÀ CHĂM SÓC KHÁCH HÀNG

## 1. Vai trò của AI

Bạn là một **Principal Software Engineer kiêm Solution Architect, Business Analyst và QA Lead**, có kinh nghiệm chuyên sâu với:

- PHP 8.3
- Laravel 12
- Filament 3
- Livewire
- Alpine.js
- MySQL
- Laravel Queue
- Laravel Policies, Gates và phân quyền theo vai trò
- Thiết kế hệ thống CRM
- Thiết kế nghiệp vụ Sales, CPQ, Customer Care
- Transactional Email
- Xuất PDF từ HTML
- Audit Log
- Kiến trúc module hóa bằng `nwidart/laravel-modules`

Nhiệm vụ của bạn là **phân tích codebase hiện tại và triển khai phân hệ mới phục vụ quản lý dịch vụ, gói dịch vụ, bảng giá, báo giá, gửi báo giá, xác nhận báo giá và chăm sóc khách hàng**.

Bạn phải làm việc như một kỹ sư phần mềm thực tế đang triển khai trên hệ thống có dữ liệu và đang vận hành. Không được sinh code theo kiểu minh họa, code rời rạc hoặc bỏ qua các ràng buộc nghiệp vụ.

---

# 2. Bối cảnh hệ thống hiện tại

Hệ thống hiện tại là nền tảng **Email Marketing & CRM**, sử dụng:

- Laravel 12
- PHP 8.3
- Filament 3
- MySQL
- Laravel Queue với database driver
- Blade cho public frontend
- Đa ngôn ngữ Việt/Anh
- Cấu trúc module bằng `nwidart/laravel-modules`

Hệ thống đang có các nhóm nghiệp vụ chính:

## 2.1. CRM

Đã có các chức năng và dữ liệu liên quan đến:

- Contacts
- Contact Qualification
- Customers
- Customer Assignments
- Customer Interactions
- Customer Distribution
- Staff
- Positions
- Lead Distribution
- Customer lifecycle
- Customer owner và support staff

Luồng hiện tại:

```text
Landing Page
    ↓
Contact
    ↓
Contact Qualification
    ↓
Qualified
    ↓
Convert thành Customer
    ↓
Phân công Staff
    ↓
Customer Interaction
```

## 2.2. Marketing và Email

Đã có:

- Campaigns
- Campaign Recipients
- Email Templates
- Sending Accounts
- Sending Domains
- Segments
- Contact Lists
- Tags
- Custom Fields
- Landing Pages
- Form Templates
- Form Submissions
- Suppression Entries
- Email Events
- Open tracking
- Click tracking
- Unsubscribe
- Queue gửi email
- `EmailSendingService`

## 2.3. Quy tắc quan trọng đang áp dụng

- Contact là dữ liệu đầu vào.
- Contact chỉ trở thành Customer sau khi nhân viên gọi điện hoặc trao đổi và xác nhận:
  - Khách hàng có nhu cầu; hoặc
  - Khách hàng đã mua dịch vụ.
- Customer có thể là:
  - Cá nhân
  - Doanh nghiệp
- Customer có thể có owner và support staff.
- Nhân viên chỉ nên thao tác trên khách hàng được phân công.
- Email Marketing Campaign là email gửi hàng loạt.
- Email báo giá là email giao dịch một-một, không được coi là Campaign Email Marketing.
- Có thể tái sử dụng hạ tầng gửi mail, queue và sending account hiện có, nhưng phải tách dữ liệu nghiệp vụ báo giá khỏi campaign.

---

# 3. Mục tiêu triển khai

Xây dựng phân hệ mới có tên nghiệp vụ:

> **Sales & Quotation Management**

Phân hệ phải hỗ trợ đầy đủ:

1. Quản lý dịch vụ.
2. Quản lý gói dịch vụ.
3. Quản lý nhiều bảng giá cho cùng một dịch vụ hoặc gói dịch vụ.
4. Phân loại bảng giá theo khách hàng cá nhân và doanh nghiệp.
5. Phân quyền nhân viên được xem và sử dụng bảng giá nào.
6. Tạo báo giá từ Customer.
7. Tính số lượng, đơn giá, giảm giá, VAT và tổng thanh toán.
8. Quản lý thông tin ngân hàng và QR thanh toán.
9. Tạo trang public để khách xem báo giá.
10. Xuất PDF.
11. Gửi email báo giá kèm PDF và public link.
12. Theo dõi trạng thái đã gửi, đã xem, đã xác nhận, từ chối, yêu cầu chỉnh sửa và hết hạn.
13. Quản lý phiên bản báo giá.
14. Ghi nhận toàn bộ hoạt động vào timeline chăm sóc khách hàng.
15. Bổ sung các chức năng cần thiết cho nhân viên CSKH.
16. Hỗ trợ quy trình duyệt báo giá và kiểm soát giảm giá.
17. Hỗ trợ theo dõi thanh toán ở mức CRM.
18. Ghi audit log cho các hành động quan trọng.

---

# 4. Nguyên tắc nghiệp vụ bắt buộc

## 4.1. Tách biệt bốn khái niệm

Phải tách rõ:

### Service

Là dịch vụ doanh nghiệp cung cấp.

Ví dụ:

```text
Email doanh nghiệp
Thiết kế website
Dịch vụ hosting
Tư vấn chuyển đổi số
```

### Service Package

Là một cấu hình hoặc gói cụ thể của một dịch vụ.

Ví dụ:

```text
Email Pro 12 tháng
Email Business #2 - 24 tháng
Email Enterprise 36 tháng
```

### Price Book

Là bảng giá áp dụng cho một nhóm đối tượng hoặc một giai đoạn.

Ví dụ:

```text
Bảng giá cá nhân 2026
Bảng giá doanh nghiệp 2026
Bảng giá đại lý
Bảng giá khuyến mãi quý III
```

### Quotation

Là hồ sơ báo giá được tạo cho một Customer cụ thể tại một thời điểm cụ thể.

Không được đồng nhất Service với Package, Price Book hoặc Quotation.

---

## 4.2. Một dịch vụ có nhiều gói

Quan hệ bắt buộc:

```text
Service 1 ─── n ServicePackage
```

Ví dụ:

```text
Service: Email doanh nghiệp

Packages:
- Email Pro 12 tháng
- Email Pro 24 tháng
- Email Business 24 tháng
- Email Enterprise 36 tháng
```

---

## 4.3. Một gói có nhiều mức giá

Không được đặt một trường `price` duy nhất trong `services` hoặc `service_packages`.

Quan hệ đúng:

```text
PriceBook 1 ─── n PriceBookItem
ServicePackage 1 ─── n PriceBookItem
```

Một gói có thể có giá khác nhau trong nhiều bảng giá.

---

## 4.4. Bảng giá phải phân loại đối tượng

Hỗ trợ:

```text
personal
business
both
```

Quy tắc:

- Customer cá nhân chỉ được dùng bảng giá `personal` hoặc `both`.
- Customer doanh nghiệp chỉ được dùng bảng giá `business` hoặc `both`.
- Admin có thể override nếu có lý do.
- Mọi override phải được ghi audit log.

---

## 4.5. Báo giá phải lưu snapshot

Khi báo giá được tạo, phải snapshot dữ liệu tại thời điểm đó.

Các dữ liệu cần snapshot:

- Thông tin Customer
- Người liên hệ
- Địa chỉ
- Mã số thuế
- Email
- Điện thoại
- Tên dịch vụ
- Tên gói
- Đơn vị tính
- Đơn giá
- VAT
- Giảm giá
- Phạm vi cung cấp
- Điều khoản
- Thông tin thanh toán
- Tài khoản ngân hàng
- Nội dung chuyển khoản
- QR data

Nếu Customer, bảng giá, dịch vụ hoặc tài khoản ngân hàng thay đổi sau này, báo giá cũ không được thay đổi theo.

---

## 4.6. Báo giá đã gửi không được sửa trực tiếp

Quy tắc:

- Báo giá `draft` được phép sửa.
- Báo giá đã gửi không được cập nhật trực tiếp phần nội dung thương mại.
- Muốn thay đổi phải tạo phiên bản mới.
- Phiên bản cũ chuyển sang `superseded`.
- Chỉ phiên bản hiện hành mới được xác nhận.

Ví dụ:

```text
QT202600001-V1
QT202600001-V2
QT202600001-V3
```

---

## 4.7. Email báo giá không phải Email Marketing

Không được tạo Campaign để gửi mỗi báo giá.

Email báo giá phải là **Transactional Email**.

Có thể tái sử dụng:

- Sending Account
- Sending Domain
- `EmailSendingService`
- Queue
- Provider SMTP/API
- Cơ chế ghi email event nếu phù hợp

Không được phụ thuộc vào:

- Campaign
- Campaign Recipient
- Segment
- Contact List
- Marketing unsubscribe workflow như email hàng loạt

Tuy nhiên phải kiểm tra trạng thái liên hệ:

| Trạng thái | Email Marketing | Email báo giá |
|---|---:|---:|
| subscribed | Cho phép | Cho phép |
| unsubscribed | Không | Cho phép khi báo giá được khách yêu cầu |
| bounced | Không | Chặn hoặc yêu cầu sửa email |
| complained | Không | Cảnh báo mạnh hoặc chặn theo cấu hình |
| do_not_contact | Không | Không |
| invalid_email | Không | Không |

Hãy triển khai quy tắc trên theo cấu hình hoặc service riêng, không hard-code rải rác trong controller.

---

# 5. Mô hình dữ liệu yêu cầu

Hãy kiểm tra codebase trước khi tạo migration. Nếu hệ thống đã có bảng hoặc model tương đương thì ưu tiên mở rộng hợp lý, không tạo bảng trùng nghiệp vụ.

## 5.1. `services`

Đề xuất:

```text
id
service_code
name
slug
description
service_category_id nullable
default_scope nullable
default_terms nullable
status
sort_order
created_by
updated_by
created_at
updated_at
deleted_at
```

Yêu cầu:

- `service_code` unique.
- `slug` unique nếu dùng.
- Có enum hoặc value object cho `status`.
- Có soft delete.
- Không cho xóa cứng nếu đã được dùng trong báo giá.

---

## 5.2. `service_packages`

Đề xuất:

```text
id
service_id
package_code
name
description
audience_type
billing_period nullable
billing_period_unit nullable
unit
default_quantity
status
sort_order
created_by
updated_by
created_at
updated_at
deleted_at
```

Enum đề xuất:

```text
AudienceType:
- personal
- business
- both

BillingPeriodUnit:
- day
- month
- year
- one_time

PackageStatus:
- active
- inactive
- archived
```

---

## 5.3. `price_books`

Đề xuất:

```text
id
price_book_code
name
description
audience_type
currency
tax_mode
valid_from
valid_until nullable
status
is_default
created_by
updated_by
approved_by nullable
approved_at nullable
created_at
updated_at
deleted_at
```

Enum đề xuất:

```text
PriceBookStatus:
- draft
- active
- inactive
- expired
- archived

TaxMode:
- tax_inclusive
- tax_exclusive
- no_tax
```

Business rule:

- Chỉ một bảng giá default hoạt động cho mỗi tổ hợp đối tượng và tiền tệ nếu nghiệp vụ cần.
- Không cho active bảng giá có thời gian hiệu lực không hợp lệ.
- Tự chuyển `expired` bằng scheduler khi quá hạn hoặc xác định expired bằng domain method.

---

## 5.4. `price_book_items`

Đề xuất:

```text
id
price_book_id
service_package_id
unit_price
minimum_quantity nullable
maximum_quantity nullable
default_discount_type nullable
default_discount_value nullable
maximum_discount_value nullable
vat_rate
description nullable
scope_override nullable
terms_override nullable
sort_order
created_at
updated_at
deleted_at nullable
```

Enum:

```text
DiscountType:
- fixed
- percentage
```

Quy tắc:

- `unit_price >= 0`
- `vat_rate >= 0`
- Percentage discount không vượt 100.
- Số lượng tối thiểu và tối đa phải hợp lệ.
- Không cho nhân viên dùng item ngoài thời hạn hiệu lực của Price Book.

---

## 5.5. `price_book_access_rules`

Đề xuất:

```text
id
price_book_id
access_type
role nullable
department nullable
staff_id nullable
branch_id nullable
can_view
can_create_quotation
discount_limit_type nullable
discount_limit_value nullable
created_at
updated_at
```

Enum:

```text
PriceBookAccessType:
- all
- role
- department
- staff
- branch
```

Yêu cầu:

- Tạo service để xác định quyền truy cập bảng giá.
- Không viết điều kiện phân quyền rải rác trong Filament Resource.
- Admin luôn có toàn quyền.
- CSKH Manager có quyền theo phạm vi phòng ban.
- CSKH Staff chỉ dùng bảng giá được cấp.
- Nhân viên chỉ được tạo báo giá cho Customer mà họ đang là owner hoặc support active.

---

## 5.6. `bank_accounts`

Đề xuất:

```text
id
bank_code
bank_name
account_number
account_name
branch_name nullable
swift_code nullable
qr_provider nullable
qr_template nullable
status
is_default
created_by
updated_by
created_at
updated_at
deleted_at
```

Yêu cầu:

- Không lưu thông tin ngân hàng trực tiếp bằng chuỗi hard-code trong view.
- Có chức năng bật/tắt.
- Có thể chọn tài khoản khi tạo báo giá.
- Báo giá phải lưu payment snapshot.
- Không hiển thị tài khoản inactive trong báo giá mới.
- Báo giá cũ vẫn giữ dữ liệu snapshot.

---

## 5.7. `quotations`

Đề xuất:

```text
id
quotation_code
customer_id
assigned_staff_id
price_book_id nullable
bank_account_id nullable

title
version
parent_quotation_id nullable
replaces_quotation_id nullable

quotation_date
valid_until
currency

subtotal
discount_total
tax_total
grand_total

status
payment_status
email_status

customer_snapshot json
company_snapshot json nullable
payment_snapshot json
terms_snapshot json
metadata json nullable

public_token
sent_at nullable
first_viewed_at nullable
last_viewed_at nullable
view_count default 0
accepted_at nullable
rejected_at nullable
revision_requested_at nullable
expired_at nullable
cancelled_at nullable

created_by
updated_by
approved_by nullable
approved_at nullable

created_at
updated_at
deleted_at
```

Yêu cầu:

- `quotation_code` unique theo quy tắc được thống nhất.
- `public_token` random, khó đoán, không dùng ID tuần tự.
- Dùng decimal phù hợp cho tiền.
- Tiền tệ mặc định `VND`.
- Format hiển thị theo locale `vi-VN`.
- Không dùng float cho tiền.
- Dùng `decimal` và Money helper/service nếu codebase chưa có.
- `version` bắt đầu từ 1.
- `parent_quotation_id` liên kết chuỗi phiên bản.
- Có soft delete.
- Không hard delete nếu đã gửi.

---

## 5.8. `quotation_items`

Đề xuất:

```text
id
quotation_id
service_id nullable
service_package_id nullable
price_book_item_id nullable

service_code_snapshot
service_name_snapshot
package_code_snapshot nullable
package_name_snapshot
description_snapshot nullable
scope_snapshot nullable
terms_snapshot nullable

unit
quantity
unit_price

discount_type nullable
discount_value default 0
discount_amount default 0

vat_rate default 0
vat_amount default 0
line_subtotal
line_total

sort_order
created_at
updated_at
```

Yêu cầu:

- Tất cả số tiền được tính ở backend.
- Không tin dữ liệu total gửi từ frontend.
- Khi lưu phải kiểm tra lại Price Book, quyền và giới hạn giảm giá.
- Sử dụng DB transaction khi tạo hoặc cập nhật báo giá.

---

## 5.9. `quotation_confirmations`

Đề xuất:

```text
id
quotation_id
confirmation_type
signer_name
signer_position nullable
signer_email
signer_phone nullable
confirmation_code
otp_verified_at nullable
confirmed_at
ip_address nullable
user_agent nullable
confirmation_data json nullable
created_at
updated_at
```

Enum:

```text
QuotationConfirmationType:
- accepted
- rejected
- revision_requested
```

Yêu cầu:

- Chỉ báo giá hợp lệ, chưa hết hạn, chưa bị superseded mới được xác nhận.
- Chỉ phiên bản hiện hành mới được xác nhận.
- Lưu IP, User-Agent, thời gian, mã xác nhận.
- Chống submit lặp.
- Có rate limit.
- Có thể triển khai OTP email ở giai đoạn sau, nhưng thiết kế phải hỗ trợ.

---

## 5.10. `quotation_email_logs`

Đề xuất:

```text
id
quotation_id
recipient_email
cc json nullable
bcc json nullable
subject
body_snapshot longtext
attachment_path nullable
provider_message_id nullable
status
error_message nullable
queued_at nullable
sent_at nullable
failed_at nullable
created_by
created_at
updated_at
```

Enum:

```text
QuotationEmailStatus:
- draft
- queued
- sending
- sent
- failed
- cancelled
```

Mỗi lần gửi hoặc gửi lại phải tạo log mới.

---

## 5.11. `quotation_documents`

Đề xuất:

```text
id
quotation_id
version
document_type
file_path
file_name
mime_type
file_size nullable
file_hash nullable
generated_by nullable
generated_at
created_at
updated_at
```

Enum:

```text
QuotationDocumentType:
- pdf
- signed_snapshot
```

---

## 5.12. Approval

Nếu codebase chưa có approval engine, có thể triển khai MVP bằng:

### `quotation_approvals`

```text
id
quotation_id
step
approver_user_id nullable
approver_role nullable
status
reason nullable
requested_at
reviewed_at nullable
created_at
updated_at
```

Enum:

```text
ApprovalStatus:
- pending
- approved
- rejected
- cancelled
```

Hãy thiết kế đủ để mở rộng nhiều cấp duyệt, nhưng MVP có thể chỉ cần một cấp theo ngưỡng giảm giá.

---

# 6. Enum bắt buộc

Hãy tạo PHP backed enum theo convention hiện tại của dự án.

## 6.1. Quotation Status

```text
draft
pending_approval
approved
sent
viewed
accepted
rejected
revision_requested
expired
cancelled
superseded
```

Yêu cầu:

- Có methods như:
  - `label()`
  - `color()`
  - `isEditable()`
  - `canSend()`
  - `canConfirm()`
  - `isTerminal()`
- Không dùng magic strings rải rác.

## 6.2. Payment Status

```text
not_required
unpaid
pending_verification
partially_paid
paid
refunded
cancelled
```

## 6.3. Email Status

```text
not_sent
queued
sending
sent
failed
```

---

# 7. Công thức tính tiền

Tất cả phép tính phải được thực hiện ở domain service/backend.

## 7.1. Từng dòng

```text
line_subtotal = quantity × unit_price
discount_amount = fixed hoặc percentage
taxable_amount = line_subtotal - discount_amount
vat_amount = taxable_amount × vat_rate / 100
line_total = taxable_amount + vat_amount
```

## 7.2. Tổng báo giá

```text
subtotal = tổng line_subtotal
discount_total = tổng discount_amount
tax_total = tổng vat_amount
grand_total = subtotal - discount_total + tax_total
```

Yêu cầu:

- Không sử dụng float.
- Làm tròn theo quy tắc tiền tệ.
- VND hiển thị không có phần thập phân.
- Chấp nhận số lượng decimal nếu đơn vị nghiệp vụ yêu cầu.
- Viết test cho tính tiền.
- Tổng tiền trên web và PDF phải cùng dùng một nguồn dữ liệu.
- Không viết hai bộ logic tính toán độc lập.

Tạo service đề xuất:

```text
QuotationPricingService
```

Nhiệm vụ:

- Validate item.
- Áp dụng discount.
- Kiểm tra discount limit.
- Tính VAT.
- Tính tổng.
- Trả về money breakdown.
- Có unit test riêng.

---

# 8. Luồng nghiệp vụ mục tiêu

## 8.1. Luồng từ Customer

```text
Customer
    ↓
Nhân viên ghi nhận nhu cầu
    ↓
Chọn "Tạo báo giá"
    ↓
Hệ thống kiểm tra quyền với Customer
    ↓
Chọn Price Book hợp lệ
    ↓
Chọn Service Package
    ↓
Tính giá
    ↓
Lưu Draft
    ↓
Preview
    ↓
Duyệt nếu cần
    ↓
Gửi email + PDF + Public Link
    ↓
Khách xem
    ├── Chấp nhận
    ├── Từ chối
    └── Yêu cầu điều chỉnh
```

## 8.2. Sau khi chấp nhận

```text
Accepted
    ↓
Payment Status = Unpaid
    ↓
CSKH theo dõi chuyển khoản
    ↓
Pending Verification
    ↓
Paid
    ↓
Customer lifecycle chuyển theo quy tắc
```

Không tự động coi khách đã thanh toán chỉ vì đã accepted.

---

# 9. Business Rules bắt buộc

## BR-01: Customer hợp lệ

Chỉ tạo báo giá khi:

- Customer không bị archived.
- Customer không bị blocked.
- Nhân viên có assignment active hoặc có quyền quản lý.
- Customer có thông tin tối thiểu:
  - Tên hiển thị
  - Email hợp lệ nếu muốn gửi mail
  - Loại cá nhân/doanh nghiệp
- Nếu là doanh nghiệp và nghiệp vụ yêu cầu, cảnh báo khi thiếu:
  - Mã số thuế
  - Địa chỉ
  - Người liên hệ

---

## BR-02: Price Book hợp lệ

Chỉ hiển thị Price Book khi:

```text
status = active
valid_from <= current_date
valid_until >= current_date hoặc null
audience_type phù hợp Customer
nhân viên có can_view
nhân viên có can_create_quotation nếu muốn tạo báo giá
```

---

## BR-03: Discount Limit

- Nhân viên không được giảm vượt giới hạn.
- Nếu vượt giới hạn nhưng thuộc trường hợp được đề nghị:
  - Lưu báo giá.
  - Chuyển `pending_approval`.
  - Không được gửi trước khi duyệt.
- Nếu giảm giá không vượt giới hạn:
  - Có thể `approved` tự động hoặc tiếp tục `draft` tùy cấu hình.

---

## BR-04: Lock sau khi gửi

- Báo giá đã gửi không được sửa item, giá, VAT, terms hoặc payment information.
- Chỉ được cập nhật các trường kỹ thuật như view count.
- Muốn thay đổi phải `createRevision()`.

---

## BR-05: Expiration

Scheduler chạy hàng ngày:

```text
valid_until < today
AND status IN (approved, sent, viewed)
→ expired
```

Không expire báo giá đã accepted.

---

## BR-06: Version

- Version mới copy snapshot và items từ version trước.
- `version = previous.version + 1`
- Version trước chuyển `superseded`.
- Link version cũ chỉ xem, không xác nhận.
- Có banner “Báo giá này đã có phiên bản mới”.

---

## BR-07: View Tracking

Khi khách mở public link:

- Tăng `view_count`.
- Ghi `first_viewed_at` lần đầu.
- Ghi `last_viewed_at` mỗi lần.
- Chuyển `sent → viewed` lần đầu.
- Ghi event hoặc audit log.
- Không tăng vô hạn do refresh từ bot nếu có thể; có thể cân nhắc debounce hoặc unique theo session/IP trong khoảng thời gian.

---

## BR-08: Confirmation

Khi khách accepted:

- Kiểm tra status.
- Kiểm tra thời hạn.
- Kiểm tra token.
- Kiểm tra version hiện hành.
- Tạo confirmation record.
- Cập nhật quotation.
- Tạo customer interaction.
- Gửi email xác nhận cho Customer và Staff.
- Không cho xác nhận lại.

---

## BR-09: Revision Request

Khi khách yêu cầu chỉnh sửa:

- Ghi nội dung yêu cầu.
- Chuyển `revision_requested`.
- Tạo task cho assigned staff.
- Không tự động sửa báo giá.
- Staff chọn “Tạo phiên bản mới”.

---

## BR-10: Audit

Ghi audit cho:

- Tạo báo giá.
- Sửa draft.
- Thêm/xóa item.
- Đổi bảng giá.
- Đổi tài khoản ngân hàng.
- Gửi duyệt.
- Duyệt.
- Từ chối duyệt.
- Gửi email.
- Gửi lại.
- Khách xem.
- Khách accepted.
- Khách rejected.
- Khách yêu cầu chỉnh sửa.
- Tạo revision.
- Hủy.
- Đổi payment status.
- Xuất PDF.

---

# 10. Các service cần triển khai

Hãy ưu tiên domain/service layer thay vì nhồi logic vào Filament Resource hoặc Controller.

Đề xuất:

```text
ServiceCatalogService
PriceBookAccessService
PriceBookResolverService
QuotationPricingService
QuotationCreationService
QuotationRevisionService
QuotationApprovalService
QuotationPdfService
QuotationMailService
QuotationPublicAccessService
QuotationConfirmationService
QuotationPaymentService
QuotationInteractionService
QuotationReminderService
QrPaymentService
```

## 10.1. `QuotationCreationService`

Trách nhiệm:

- Kiểm tra Customer.
- Kiểm tra assignment.
- Kiểm tra Price Book.
- Tạo code.
- Tạo snapshot.
- Tạo items.
- Tính total.
- Dùng DB transaction.
- Ghi audit.

## 10.2. `QuotationRevisionService`

Trách nhiệm:

- Kiểm tra báo giá có thể tạo revision.
- Copy dữ liệu.
- Tăng version.
- Liên kết parent.
- Mark version cũ superseded.
- Reset sent/view/accept fields.
- Ghi audit.

## 10.3. `QuotationPdfService`

Trách nhiệm:

- Render từ cùng nguồn dữ liệu với public view.
- Sinh PDF.
- Lưu file.
- Tính hash.
- Không ghi đè PDF đã gửi.
- Hỗ trợ regenerate cho draft.
- Với bản đã gửi, tạo document version mới hoặc không cho regenerate tùy quy tắc.

## 10.4. `QuotationMailService`

Trách nhiệm:

- Validate recipient.
- Kiểm tra trạng thái email.
- Render transactional email template.
- Đính kèm PDF.
- Gắn public link.
- Dispatch job.
- Tạo email log.
- Cập nhật status.
- Ghi interaction.
- Ghi audit.

## 10.5. `QuotationConfirmationService`

Trách nhiệm:

- Validate token, status, expiration và version.
- Tạo confirmation.
- Cập nhật quotation.
- Tạo interaction.
- Gửi notification.
- Chống double-submit.

---

# 11. Queue Jobs

Đề xuất:

```text
GenerateQuotationPdfJob
SendQuotationEmailJob
SendQuotationAcceptedNotificationJob
SendQuotationRevisionRequestedNotificationJob
ExpireQuotationsJob
SendQuotationReminderJob
```

Yêu cầu:

- Job idempotent.
- Có retry hợp lý.
- Có backoff.
- Không gửi email trùng nếu retry.
- Sử dụng unique key hoặc kiểm tra status/log.
- Log lỗi có ngữ cảnh quotation code.
- Không làm block request lâu.

---

# 12. Public Routes

Không dùng route theo ID tuần tự.

Đề xuất:

```text
GET  /q/{quotationCode}/{token}
GET  /q/{quotationCode}/{token}/pdf
POST /q/{quotationCode}/{token}/accept
POST /q/{quotationCode}/{token}/reject
POST /q/{quotationCode}/{token}/request-revision
POST /q/{quotationCode}/{token}/send-otp
POST /q/{quotationCode}/{token}/verify-otp
```

Yêu cầu:

- Route có rate limit.
- Token dùng random secure string.
- Không lộ dữ liệu nếu token sai.
- Không cho index public quotation.
- Không dùng ID trong URL.
- Có CSRF phù hợp với form public.
- Có chống spam và validation.
- Có thể cân nhắc signed URL nhưng vẫn cần public token để quản lý vòng đời.

---

# 13. Giao diện public báo giá

Trang public cần hiển thị:

## 13.1. Header

- Tên báo giá
- Số báo giá
- Ngày báo giá
- Ngày hết hạn
- Trạng thái
- Trạng thái xác nhận

## 13.2. Thông tin khách hàng

- Tên khách hàng
- Mã số thuế
- Địa chỉ
- Người liên hệ
- Email
- Điện thoại

## 13.3. Chi tiết dịch vụ

- STT
- Sản phẩm/dịch vụ
- Gói
- Đơn vị tính
- Số lượng
- Đơn giá
- Giảm giá
- VAT
- Thành tiền

## 13.4. Phạm vi cung cấp

- Hạng mục
- Nội dung cung cấp

## 13.5. Tổng tiền

- Tạm tính
- Giảm giá
- Thuế VAT
- Tổng thanh toán

## 13.6. Thanh toán

- Ngân hàng
- Số tài khoản
- Chủ tài khoản
- Chi nhánh
- SWIFT
- Số tiền
- Nội dung chuyển khoản
- QR code
- Nút sao chép số tài khoản
- Nút sao chép nội dung chuyển khoản

## 13.7. Điều khoản

- Hiệu lực
- Điều kiện thanh toán
- Lưu ý VAT
- Điều khoản tùy chỉnh

## 13.8. Hành động

Khi chưa xác nhận:

- Chấp nhận
- Từ chối
- Yêu cầu điều chỉnh
- Tải PDF
- In báo giá

Khi đã xác nhận:

- Ẩn các nút xác nhận.
- Hiển thị:
  - Người xác nhận
  - Chức vụ
  - Email
  - Số điện thoại
  - Thời gian
  - Mã xác nhận

Khi superseded:

- Hiển thị cảnh báo.
- Có link sang phiên bản mới nếu hợp lệ.
- Không cho thao tác xác nhận.

---

# 14. Xuất PDF

PDF phải có cùng dữ liệu với trang public.

Yêu cầu:

- Tái sử dụng view model hoặc presenter.
- Không tính lại tiền riêng trong template PDF.
- Có logo công ty nếu cấu hình.
- Có thông tin công ty.
- Có Customer snapshot.
- Có item table.
- Có payment snapshot.
- Có QR.
- Có terms.
- Có phần đại diện công ty.
- Có phần xác nhận khách hàng.
- Hỗ trợ tiếng Việt.
- Font phải hiển thị tiếng Việt đúng.
- Không phụ thuộc asset public không truy cập được trong môi trường worker.
- File PDF phải được lưu với tên rõ ràng.

Ví dụ:

```text
QT202600001-V1.pdf
```

---

# 15. Transactional Email

Tạo email template riêng cho báo giá.

Đề xuất template keys:

```text
quotation_sent
quotation_resent
quotation_accepted
quotation_rejected
quotation_revision_requested
quotation_expiring
quotation_expired
payment_confirmed
```

Email gửi báo giá cần có:

- Customer name
- Quotation title
- Quotation code
- Grand total
- Valid until
- Public URL
- Staff name
- Staff phone
- Staff email
- PDF attachment

Không dùng campaign template nếu cấu trúc hiện tại không phù hợp.

Nếu tái sử dụng `email_templates`, phải thêm loại template:

```text
marketing
transactional
quotation
system
```

và phải đảm bảo transactional email không bị đưa vào campaign workflow.

---

# 16. Customer Care Workspace

Hiện tại nhân viên chưa có đủ công cụ chăm sóc khách hàng. Hãy bổ sung giao diện làm việc cho CSKH.

## 16.1. My Customers

Hiển thị:

- Khách hàng đang phụ trách.
- Khách mới được giao.
- Khách chưa liên hệ.
- Khách cần follow-up.
- Khách có báo giá đang chờ.
- Khách đã xem nhưng chưa phản hồi.
- Khách có báo giá sắp hết hạn.
- Khách đã accepted nhưng chưa thanh toán.

## 16.2. Customer Timeline

Timeline hợp nhất:

- Landing page submission
- Qualification
- Assignment
- Call
- Email
- Note
- Follow-up
- Quotation created
- Quotation approved
- Quotation sent
- Quotation viewed
- Quotation accepted
- Quotation rejected
- Revision requested
- Payment status changed

Nếu hệ thống đã có `customer_interactions`, hãy mở rộng enum/type thay vì tạo timeline trùng.

Đề xuất interaction types:

```text
call
meeting
email
note
follow_up
quotation_created
quotation_sent
quotation_viewed
quotation_accepted
quotation_rejected
quotation_revision_requested
payment_updated
```

## 16.3. Quick Actions

Trong trang Customer:

- Gọi điện
- Gửi email
- Tạo ghi chú
- Lên lịch follow-up
- Tạo báo giá
- Xem báo giá
- Gửi lại báo giá
- Tạo revision
- Cập nhật thanh toán

---

# 17. Reminder và task chăm sóc

Tạo reminder theo quy tắc có thể cấu hình:

- Đã gửi 2 ngày nhưng chưa xem.
- Đã xem 3 ngày nhưng chưa phản hồi.
- Còn 2 ngày hết hạn.
- Đã yêu cầu chỉnh sửa.
- Đã accepted nhưng chưa thanh toán.
- Thanh toán pending verification quá thời gian.

Nếu hệ thống chưa có task table, có thể:

1. Mở rộng `customer_interactions` với loại `follow_up`; hoặc
2. Tạo `customer_tasks`.

Không tạo thêm bảng nếu không cần thiết. Hãy phân tích codebase và chọn phương án ít trùng lặp nhất.

---

# 18. Filament Resources và Pages

Đề xuất menu:

```text
Sales
├── Services
├── Service Packages
├── Price Books
├── Quotations
├── Quotation Approvals
├── Bank Accounts
└── Payment Tracking

CRM
├── Contacts
├── Qualification
├── Customers
├── Customer Care
├── Interactions
└── Follow-up Tasks
```

Các Resource cần cân nhắc:

```text
ServiceResource
ServicePackageResource
PriceBookResource
QuotationResource
QuotationApprovalResource
BankAccountResource
```

## 18.1. `QuotationResource`

Table cần có:

- Mã báo giá
- Phiên bản
- Customer
- Title
- Assigned staff
- Tổng thanh toán
- Ngày báo giá
- Hết hạn
- Status
- Payment status
- Email status
- Sent at
- Viewed at
- Accepted at

Filters:

- Status
- Payment status
- Assigned staff
- Customer type
- Price book
- Date range
- Expiring soon
- Viewed but not responded
- Accepted but unpaid

Actions:

- View
- Edit draft
- Preview
- Generate PDF
- Send for approval
- Approve
- Reject approval
- Send email
- Resend email
- Copy public link
- Open public page
- Create revision
- Cancel
- Mark payment pending
- Mark paid
- View audit log

Visibility phải dựa trên Policy và trạng thái.

Không chỉ ẩn nút ở UI; backend vẫn phải kiểm tra quyền.

---

# 19. Phân quyền

Các role hiện tại:

```text
admin
marketing_manager
marketing_staff
customer_service_manager
customer_service_staff
viewer
```

Ma trận đề xuất:

| Chức năng | Admin | CSKH Manager | CSKH Staff | Marketing Manager | Marketing Staff | Viewer |
|---|---:|---:|---:|---:|---:|---:|
| Quản lý Service | Toàn quyền | Xem | Xem | Xem | Xem | Xem |
| Quản lý Package | Toàn quyền | Xem | Xem | Xem | Xem | Xem |
| Quản lý Price Book | Toàn quyền | Xem/duyệt | Xem theo quyền | Xem | Hạn chế | Xem |
| Cấu hình Access Rule | Toàn quyền | Không | Không | Không | Không | Không |
| Tạo Quotation | Có | Có | Có với Customer được giao | Không | Không | Không |
| Sửa Draft | Có | Có | Báo giá của mình | Không | Không | Không |
| Duyệt | Có | Theo ngưỡng/phòng ban | Không | Không | Không | Không |
| Gửi | Có | Có | Có sau khi approved | Không | Không | Không |
| Hủy | Có | Có | Draft của mình | Không | Không | Không |
| Payment update | Có | Có hoặc Finance | Hạn chế | Không | Không | Không |
| Báo cáo | Toàn bộ | Theo phòng ban | Cá nhân | Xem hạn chế | Không | Xem hạn chế |

Hãy triển khai bằng Policy/Gate và query scoping.

Không chỉ dùng `visible()` trong Filament.

---

# 20. Security

Bắt buộc:

- Secure random public token.
- Không dùng incremental ID public.
- Route public có throttle.
- Validate tất cả input.
- Không tin giá gửi từ browser.
- Không tin Customer ID nếu staff không có quyền.
- Không cho mass assignment ngoài fillable/casts hợp lý.
- Không lưu secret SMTP trong plaintext.
- Không log dữ liệu nhạy cảm quá mức.
- Không log OTP.
- Chống double-submit.
- Dùng DB transaction cho nghiệp vụ nhiều bảng.
- Dùng authorization ở service/controller/action.
- Có audit trail.
- Public PDF chỉ truy cập khi token hợp lệ.
- Escape dữ liệu trong HTML.
- Nếu cho phép HTML tùy chỉnh, phải sanitize.

---

# 21. Code Quality

Yêu cầu:

- Tuân thủ convention codebase.
- Không tự ý đổi namespace hoặc cấu trúc module.
- Không refactor diện rộng không liên quan.
- Không sửa file production config.
- Không hard-code role, label và route nếu dự án đã có enum/config.
- Sử dụng Form Request hoặc validation tương ứng.
- Dùng typed properties.
- Dùng PHP enum.
- Dùng casts cho JSON, decimal, datetime và enum.
- Dùng action/service cho nghiệp vụ.
- Controller mỏng.
- Resource không chứa domain logic.
- Không N+1 query.
- Dùng eager loading.
- Có index database phù hợp.
- Có unique constraint phù hợp.
- Có foreign key.
- Có soft delete khi cần.
- Có comments cho logic phức tạp, không comment hiển nhiên.
- Không tạo duplicate service.

---

# 22. Migration Strategy

Đây là hệ thống có thể đang có dữ liệu.

Bắt buộc:

- Không chỉnh sửa migration cũ đã chạy.
- Tạo migration mới.
- Migration phải có `down()`.
- Không drop column hoặc table hiện có nếu chưa có phương án migration dữ liệu.
- Không đổi enum database nguy hiểm nếu dự án dùng string enum.
- Dùng nullable hoặc default hợp lý để tránh lỗi production.
- Tạo index sau khi phân tích query.
- Với dữ liệu lớn, tránh migration khóa bảng lâu nếu có thể.
- Trước khi thêm unique constraint, phải kiểm tra dữ liệu trùng.
- Cung cấp lệnh chạy migration.
- Cung cấp lệnh rollback cho batch mới.
- Không rollback các migration không liên quan.

---

# 23. Testing

Phải tạo test thực tế.

## 23.1. Unit Tests

Tối thiểu:

- Tính subtotal.
- Discount fixed.
- Discount percentage.
- VAT.
- Multiple items.
- VND rounding.
- Discount limit.
- Price book validity.
- Price book audience.
- Quotation state transition.
- Create revision.
- Cannot edit sent quotation.
- Cannot confirm expired quotation.
- Cannot confirm superseded version.

## 23.2. Feature Tests

Tối thiểu:

- Staff chỉ thấy Customer được giao.
- Staff chỉ thấy Price Book được phép.
- Admin tạo Service.
- Admin tạo Package.
- Admin tạo Price Book.
- Staff tạo quotation.
- Staff không thể sửa sent quotation.
- Approval flow.
- Send quotation dispatches job.
- Public link token hợp lệ.
- Token sai trả 404 hoặc response an toàn.
- Accept quotation.
- Reject quotation.
- Request revision.
- PDF route.
- Audit log created.
- Interaction created.
- Payment status update authorization.

## 23.3. Queue Tests

- Không gửi trùng khi retry.
- Email log được cập nhật.
- Failed job lưu lỗi.
- PDF được tạo trước khi gửi nếu cần.

---

# 24. Seeders và dữ liệu mẫu

Tạo seeders tối thiểu cho môi trường dev:

- Service: Email doanh nghiệp
- Package: Email Pro
- Package: Email Business #2 - 24 tháng
- Price Book: Bảng giá doanh nghiệp 2026
- Price Book Item: 900.000 VND
- Bank Account mẫu
- Quotation email template
- Access rule cho CSKH

Không chạy seeder phá dữ liệu production.

---

# 25. Báo cáo và dashboard

MVP dashboard cần có:

- Tổng báo giá.
- Draft.
- Pending approval.
- Sent.
- Viewed.
- Accepted.
- Rejected.
- Expired.
- Tổng giá trị báo giá.
- Giá trị accepted.
- Accepted nhưng unpaid.
- Conversion:
  - Sent → Viewed
  - Viewed → Accepted
- Báo giá sắp hết hạn.
- Staff có báo giá cần follow-up.

Query phải có scope theo role.

---

# 26. Trạng thái và state transitions

Tạo một nơi quản lý transition, không cập nhật status tùy tiện.

Các transition hợp lệ:

```text
draft → pending_approval
draft → approved
draft → cancelled

pending_approval → approved
pending_approval → draft
pending_approval → cancelled

approved → sent
approved → cancelled

sent → viewed
sent → accepted
sent → rejected
sent → revision_requested
sent → expired
sent → cancelled

viewed → accepted
viewed → rejected
viewed → revision_requested
viewed → expired
viewed → cancelled

revision_requested → superseded
revision_requested → cancelled

accepted → không đổi nội dung thương mại
rejected → có thể tạo revision mới
expired → có thể tạo revision mới
```

Hãy tạo domain service hoặc state transition validator.

---

# 27. Định dạng mã báo giá

Mã ví dụ:

```text
QT202600001
```

Yêu cầu:

- Có service sinh code.
- Chống trùng khi concurrent request.
- Dùng transaction/locking hoặc sequence table.
- Không dùng `max(id) + 1`.
- Hỗ trợ prefix cấu hình.
- Hỗ trợ reset theo năm nếu cần.

Đề xuất:

```text
QT + YYYY + 5 chữ số tăng dần
```

Ví dụ:

```text
QT202600001
QT202600002
```

Version không nhất thiết đưa vào code gốc, có thể hiển thị:

```text
QT202600001-V2
```

---

# 28. Thông tin thanh toán và QR

QR phải được tạo từ:

- Bank code
- Account number
- Account name nếu provider hỗ trợ
- Amount
- Transfer content

Transfer content mặc định:

```text
[QUOTATION_CODE] [CUSTOMER_NAME]
```

Ví dụ:

```text
QT202600001 CONG TY TNHH ABC DIGITAL
```

Yêu cầu:

- Có helper loại bỏ ký tự không phù hợp nếu ngân hàng giới hạn.
- Không làm thay đổi nội dung đã snapshot.
- Có fallback nếu QR provider không hoạt động.
- PDF vẫn hiển thị thông tin chuyển khoản dạng text.

---

# 29. Customer lifecycle

Không tự động áp đặt chuyển trạng thái nếu codebase có logic riêng.

Đề xuất nghiệp vụ:

- Customer `potential` được phép nhận báo giá.
- Accepted chưa có nghĩa là đã paid.
- Khi payment status = `paid`, hệ thống có thể:
  - Chuyển Customer status sang `active`; hoặc
  - Chuyển lifecycle sang `purchasing` / `onboarding`.
- Hành vi này phải nằm trong service hoặc config.
- Không cập nhật trực tiếp trong Filament action.

---

# 30. Kế hoạch triển khai theo giai đoạn

## Phase 0: Khảo sát

Trước khi code:

1. Đọc toàn bộ cấu trúc module.
2. Xác định module phù hợp:
   - Tạo module `Sales`; hoặc
   - Mở rộng CRM nếu hệ thống chưa có Sales.
3. Kiểm tra model, enum, service và table hiện có.
4. Kiểm tra `Quotation` hiện có hay chưa.
5. Kiểm tra relation Customer, Staff, User, Interaction.
6. Kiểm tra audit log.
7. Kiểm tra email sending.
8. Kiểm tra PDF library.
9. Kiểm tra permission strategy.
10. Báo cáo các xung đột trước khi thay đổi.

## Phase 1: Catalog và Price Book

- Services
- Packages
- Price Books
- Price Book Items
- Access Rules
- Bank Accounts
- Policies
- Filament Resources
- Tests

## Phase 2: Quotation Core

- Quotations
- Quotation Items
- Pricing
- Snapshot
- Code generator
- Status workflow
- Revision
- Filament Resource
- Tests

## Phase 3: Public View và PDF

- Public routes
- Token access
- Public Blade
- QR
- PDF
- Documents
- View tracking
- Tests

## Phase 4: Transactional Email

- Email templates
- Email logs
- Mail service
- Jobs
- PDF attachment
- Interaction
- Audit
- Tests

## Phase 5: Confirmation và Approval

- Accept
- Reject
- Revision request
- Approval
- Discount limits
- Notifications
- Tests

## Phase 6: Customer Care

- Workspace
- Timeline
- Reminder
- Dashboard
- Payment tracking
- Reports

---

# 31. Quy trình làm việc bắt buộc của AI

Bạn không được bắt đầu viết hàng loạt file ngay lập tức.

Hãy làm theo từng bước.

## Bước 1: Khảo sát codebase

Xuất báo cáo:

```text
- Cấu trúc module hiện tại
- Model liên quan
- Migration liên quan
- Enum liên quan
- Resource liên quan
- Service liên quan
- Route liên quan
- Policy liên quan
- Hạ tầng email
- Hạ tầng PDF
- Audit log
- Customer interaction
- Các xung đột tên
- Các chức năng có thể tái sử dụng
```

## Bước 2: Đề xuất kiến trúc

Trước khi sửa code, trình bày:

- Chọn module nào.
- Danh sách bảng mới.
- Danh sách bảng mở rộng.
- Danh sách model.
- Danh sách enum.
- Danh sách service.
- Danh sách Resource/Page.
- Danh sách route.
- Danh sách job.
- Danh sách test.
- Rủi ro migration.

## Bước 3: Lập kế hoạch file

Tạo checklist file cụ thể.

Ví dụ:

```text
[ ] migration create_services_table
[ ] migration create_service_packages_table
[ ] model Service
[ ] model ServicePackage
[ ] enum ServiceStatus
[ ] policy ServicePolicy
[ ] filament ServiceResource
[ ] tests
```

## Bước 4: Triển khai theo checkpoint

Mỗi checkpoint phải:

1. Nêu mục tiêu.
2. Nêu file sẽ tạo/sửa.
3. Thực hiện.
4. Chạy format.
5. Chạy test.
6. Báo lỗi nếu có.
7. Chỉ chuyển checkpoint khi ổn định.

## Bước 5: Không che giấu lỗi

Nếu gặp lỗi:

- Hiển thị lỗi chính xác.
- Phân tích nguyên nhân.
- Nêu file liên quan.
- Sửa có kiểm soát.
- Chạy lại test.
- Không tuyên bố hoàn thành khi test chưa đạt.

---

# 32. Nhật ký triển khai

Trong quá trình code, hãy tạo hoặc cập nhật file:

```text
docs/implementation/sales-quotation-progress.md
```

Nội dung:

```text
# Sales Quotation Implementation Progress

## Current Phase

## Completed

## In Progress

## Pending

## Files Created

## Files Modified

## Migrations

## Commands Run

## Tests Run

## Errors Encountered

## Decisions

## Risks

## Next Step
```

Sau mỗi checkpoint phải cập nhật file này.

Không ghi secret, password hoặc token vào log.

---

# 33. Kết quả đầu ra yêu cầu

Khi hoàn thành mỗi phase, phải cung cấp:

1. Tóm tắt nghiệp vụ đã triển khai.
2. Danh sách file tạo mới.
3. Danh sách file sửa.
4. Migration mới.
5. Lệnh cần chạy.
6. Seeder cần chạy.
7. Queue worker cần chạy.
8. Scheduler cần cấu hình.
9. Route mới.
10. Permission mới.
11. Test đã chạy.
12. Test pass/fail.
13. Rủi ro còn lại.
14. Các mục chưa làm.
15. Hướng kiểm thử thủ công.

---

# 34. Tiêu chí nghiệm thu MVP

MVP được coi là đạt khi:

## Catalog

- Admin tạo được Service.
- Admin tạo được Package.
- Admin tạo được Price Book.
- Admin thêm Package vào Price Book.
- Có giá, VAT và discount limit.
- Có phân loại cá nhân/doanh nghiệp.
- Có access rule.

## Quotation

- Staff được cấp quyền có thể tạo báo giá cho Customer được giao.
- Staff không thể tạo báo giá cho Customer không được giao.
- Chỉ thấy Price Book được phép.
- Tính tiền đúng.
- Snapshot đúng.
- Có mã báo giá.
- Có version.
- Có preview.
- Có PDF.
- Có public URL.

## Email

- Gửi được email transactional.
- Có PDF đính kèm.
- Có public link.
- Có log gửi.
- Có retry nhưng không gửi trùng.
- Có interaction.

## Customer Action

- Khách xem được báo giá.
- Hệ thống ghi viewed.
- Khách accepted được.
- Khách rejected được.
- Khách request revision được.
- Không xác nhận được báo giá expired.
- Không xác nhận được version superseded.

## Security

- Token sai không truy cập được.
- Staff không vượt quyền.
- Giá không bị sửa từ frontend.
- Báo giá đã gửi không sửa trực tiếp.
- Có audit log.

## Customer Care

- Nhân viên thấy báo giá của khách hàng được giao.
- Timeline có sự kiện báo giá.
- Có filter cần follow-up.
- Có trạng thái accepted nhưng unpaid.

---

# 35. Những điều không được làm

Không được:

- Tạo Campaign cho mỗi báo giá.
- Dùng ID tuần tự làm public URL.
- Dùng `max(id) + 1` để sinh mã.
- Dùng float cho tiền.
- Tin total từ frontend.
- Cho sửa trực tiếp báo giá đã gửi.
- Cho xác nhận version cũ.
- Hard-code tài khoản ngân hàng trong view.
- Hard-code permission chỉ ở giao diện.
- Bỏ qua audit.
- Bỏ qua test.
- Sửa migration cũ đã chạy.
- Xóa dữ liệu production.
- Refactor toàn hệ thống ngoài phạm vi.
- Tạo bảng trùng với bảng hiện có.
- Tạo logic tính tiền ở nhiều nơi.
- Tuyên bố hoàn thành khi chưa chạy test.
- Viết pseudo-code thay cho implementation thực tế.
- Tự ý thay đổi nghiệp vụ Contact → Customer hiện tại.

---

# 36. Câu lệnh bắt đầu

Hãy bắt đầu bằng việc:

1. Khảo sát repository.
2. Đọc các file mô tả hệ thống và code hiện tại.
3. Tìm tất cả file liên quan đến:
   - Customer
   - Contact
   - Staff
   - Assignment
   - Customer Interaction
   - Quotation
   - Service
   - Price
   - EmailSendingService
   - EmailTemplate
   - SendingAccount
   - AuditLog
   - PDF
   - Filament Panel
   - Roles và Policies
4. Không tạo code ở bước đầu.
5. Trả về báo cáo khảo sát và kế hoạch triển khai.
6. Chờ xác nhận kiến trúc nếu phát hiện xung đột lớn.
7. Nếu không có xung đột lớn, triển khai theo từng checkpoint nhỏ.

Cấu trúc phản hồi đầu tiên bắt buộc:

```text
# 1. Repository Assessment

# 2. Existing Components That Can Be Reused

# 3. Conflicts and Gaps

# 4. Proposed Module Architecture

# 5. Proposed Database Changes

# 6. Proposed Services and Workflows

# 7. Authorization Design

# 8. Implementation Phases

# 9. File-by-File Plan

# 10. Risks and Questions Requiring Confirmation
```

Không bắt đầu code trước khi hoàn thành báo cáo này.
