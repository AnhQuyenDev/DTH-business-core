# Tài liệu Test Case Tổng Hợp Toàn Hệ Thống

## 1. Mục tiêu tài liệu
- Vai trò: BA + Tester.
- Phạm vi: Email, Marketing, CRM, Sales.
- Mục tiêu:
  - Làm rõ vì sao từng chức năng tồn tại trong hệ thống.
  - Chuẩn hóa luồng vận hành nghiệp vụ theo dạng activity diagram.
  - Cung cấp bộ test case chi tiết để kiểm thử thủ công/UAT.

## 2. Thông tin chung
- Nền tảng: Laravel 12 + Filament 3.
- Loại kiểm thử: Functional Test, Integration Flow Test, Role/Permission Test, Negative Test.
- Môi trường đề xuất: Staging đồng bộ dữ liệu mẫu với Production.
- Vai trò người dùng chính:
  - Admin
  - Marketing Manager/Staff
  - Customer Service Manager/Staff
  - Sales Manager/Staff
  - Người dùng public (khách truy cập landing page, khách nhận báo giá)

## 3. Tiêu chí hoàn thành kiểm thử
- 100% test case mức High và Critical phải Pass.
- 0 lỗi Severity 1/Severity 2 trước khi go-live.
- Tất cả luồng chính từ đầu vào lead đến chốt sale phải chạy xuyên module.

---

# Module A. Email

## A.1. Vì sao module Email tồn tại
- Đảm bảo hệ thống có thể gửi thông tin đúng người, đúng thời điểm, đúng nội dung.
- Cho phép theo dõi hành vi email (queued, sent, opened, clicked, failed) để đo hiệu quả.
- Giảm thao tác thủ công nhờ template, account gửi, domain xác thực.

## A.2. Chức năng chính cần có
- Quản lý mẫu email (Email Template).
- Quản lý tài khoản gửi (Sending Account).
- Quản lý domain gửi (Sending Domain: SPF, DKIM, DMARC).
- Theo dõi trạng thái gửi và sự kiện email.

## A.3. Activity diagram luồng gửi email chiến dịch
~~~mermaid
flowchart TD
    A[Người dùng tạo/chọn Email Template] --> B[Chọn Sending Account + Sending Domain]
    B --> C[Hệ thống validate cấu hình gửi]
    C -->|Hợp lệ| D[Đưa email vào hàng đợi]
    C -->|Không hợp lệ| X[Thông báo lỗi cấu hình]
    D --> E[Worker xử lý gửi email]
    E --> F[Cập nhật trạng thái recipient: queued/sent/failed]
    F --> G[Ghi email events: sent/opened/clicked/bounced]
    G --> H[Hiển thị báo cáo trong giao diện admin]
~~~

## A.4. Test case chi tiết

### A-TC-01: Tạo Email Template mới
- Mục tiêu: Đảm bảo người dùng tạo template hợp lệ.
- Tiền điều kiện: User có quyền quản lý template.
- Bước thực hiện:
  1. Vào màn hình Email Template.
  2. Chọn Tạo mới.
  3. Nhập Name, Category, Subject, HTML Body, Status.
  4. Bấm Lưu.
- Kết quả mong đợi:
  - Bản ghi được tạo thành công.
  - Hiển thị đúng status badge và category badge.

### A-TC-02: Validate bắt buộc trường Subject/Body
- Mục tiêu: Chặn dữ liệu thiếu.
- Bước thực hiện:
  1. Tạo template nhưng để trống Subject hoặc Body.
  2. Bấm Lưu.
- Kết quả mong đợi:
  - Hệ thống báo lỗi validate rõ ràng.
  - Không tạo bản ghi.

### A-TC-03: Sending Account inactive không được dùng gửi
- Mục tiêu: Đảm bảo an toàn vận hành.
- Tiền điều kiện: Có 1 sending account trạng thái inactive.
- Bước thực hiện:
  1. Tạo chiến dịch/email gửi với account inactive.
  2. Thực hiện gửi.
- Kết quả mong đợi:
  - Hệ thống chặn hoặc trả trạng thái failed có thông báo nguyên nhân.

### A-TC-04: Domain pending hiển thị cảnh báo
- Mục tiêu: Đảm bảo user biết rủi ro domain chưa xác thực.
- Bước thực hiện:
  1. Vào Sending Domain có SPF/DKIM/DMARC pending.
- Kết quả mong đợi:
  - Badge trạng thái hiển thị warning.
  - Có hướng dẫn thao tác tiếp theo (nếu có trong UI).

### A-TC-05: Theo dõi sự kiện email sau gửi
- Mục tiêu: Đảm bảo tracking event hoạt động.
- Bước thực hiện:
  1. Gửi email test.
  2. Mở email và click link tracking.
  3. Kiểm tra log/event.
- Kết quả mong đợi:
  - Có event sent, opened, clicked.
  - Dữ liệu timestamp chính xác.

---

# Module B. Marketing

## B.1. Vì sao module Marketing tồn tại
- Thu hút lead mới qua landing page, form, campaign.
- Phân nhóm đối tượng (segment/list) để gửi nội dung đúng nhu cầu.
- Đo lường hiệu quả chiến dịch để tối ưu ngân sách.

## B.2. Chức năng chính cần có
- Landing Page + UTM URL.
- Form Template + mapping dữ liệu.
- Marketing Campaign.
- Segment và Contact List.
- Landing Page Submission xử lý lead đầu vào.

## B.3. Activity diagram luồng từ landing page đến chiến dịch
~~~mermaid
flowchart TD
    A[Admin tạo Landing Page + Form Template] --> B[Admin tạo UTM Link]
    B --> C[Khách truy cập Landing Page qua UTM]
    C --> D[Hệ thống ghi nhận view + nguồn UTM]
    D --> E[Khách submit form]
    E --> F[Hệ thống tạo/cập nhật Contact]
    F --> G[Ghi Landing Page Submission]
    G --> H[Đưa Contact vào Segment/List phù hợp]
    H --> I[Marketing tạo Campaign]
    I --> J[Campaign gửi email theo tập recipient]
    J --> K[Thu thập open/click và báo cáo hiệu quả]
~~~

## B.4. Test case chi tiết

### B-TC-01: Tạo Landing Page và publish
- Mục tiêu: Đảm bảo publish đúng điều kiện.
- Tiền điều kiện: Có campaign mặc định hoặc marketing campaign liên kết.
- Bước thực hiện:
  1. Tạo Landing Page với slug hợp lệ.
  2. Bấm Publish.
- Kết quả mong đợi:
  - Status chuyển Published.
  - Có published_at.

### B-TC-02: Generate UTM URL và hiển thị danh sách link
- Mục tiêu: Đảm bảo tracking link có thể tạo, xem, copy, xóa.
- Bước thực hiện:
  1. Mở action Generate URL.
  2. Chọn utm_source, utm_medium, utm_campaign.
  3. Tạo link.
  4. Mở action Links (slide over).
  5. Thử copy và delete link.
- Kết quả mong đợi:
  - Link tạo đúng tham số UTM.
  - Copy thành công và hiển thị toast.
  - Delete thành công, danh sách cập nhật ngay.

### B-TC-03: Submit form landing page tạo mới contact
- Mục tiêu: Đảm bảo đầu vào được ghi nhận chuẩn.
- Bước thực hiện:
  1. Truy cập landing page public.
  2. Nhập email chưa tồn tại và submit.
- Kết quả mong đợi:
  - Có bản ghi submission status received/processed.
  - Contact mới được tạo.
  - contact_action là created.

### B-TC-04: Submit form với email đã tồn tại cập nhật contact
- Mục tiêu: Tránh trùng dữ liệu contact.
- Bước thực hiện:
  1. Submit cùng email đã tồn tại.
- Kết quả mong đợi:
  - Không tạo contact trùng.
  - Contact hiện hữu được cập nhật theo rule.
  - contact_action là updated hoặc skipped tùy logic.

### B-TC-05: Campaign status lifecycle
- Mục tiêu: Đảm bảo trạng thái campaign chuyển đúng.
- Bước thực hiện:
  1. Tạo campaign dạng draft.
  2. Kích hoạt gửi/schedule.
  3. Theo dõi tiến trình.
- Kết quả mong đợi:
  - Trạng thái đi qua các bước hợp lệ (draft, active/paused/completed theo nghiệp vụ).
  - Badge màu hiển thị đúng quy ước.

---

# Module C. CRM

## C.1. Vì sao module CRM tồn tại
- Quản lý vòng đời lead/customer tập trung, tránh thất thoát dữ liệu.
- Chuẩn hóa quy trình phân công và chăm sóc khách hàng.
- Tăng khả năng theo dõi hiệu suất đội ngũ và lịch sử tương tác.

## C.2. Chức năng chính cần có
- Quản lý Contact và Customer.
- Lead Pipeline (Contact Qualification).
- Customer Assignment (owner/support).
- Customer Interaction.
- Staff và lịch availability.
- Customer Distribution Batch.

## C.3. Activity diagram luồng xử lý lead
~~~mermaid
flowchart TD
    A[Lead vào từ Landing Page hoặc Import] --> B[Tạo Contact]
    B --> C[Khởi tạo Contact Qualification]
    C --> D[Phân công staff xử lý lead]
    D --> E[Staff cập nhật trạng thái: contacting/follow_up/qualified]
    E --> F{Lead đạt điều kiện?}
    F -->|Có| G[Convert sang Customer]
    F -->|Không| H[Đánh dấu unqualified/spam/duplicate]
    G --> I[Tạo Assignment owner/support]
    I --> J[Ghi tương tác chăm sóc định kỳ]
    J --> K[Nuôi dưỡng và chuyển sang Sales]
~~~

## C.4. Test case chi tiết

### C-TC-01: Lead Pipeline đổi trạng thái hợp lệ
- Mục tiêu: Đảm bảo staff có thể xử lý lead đúng lifecycle.
- Bước thực hiện:
  1. Mở Lead Pipeline.
  2. Chọn 1 lead trạng thái new/assigned.
  3. Thực hiện Start Contacting.
  4. Thực hiện Process Lead với status mới.
- Kết quả mong đợi:
  - last_contacted_at được cập nhật.
  - Nút Process Lead ẩn, thay bằng Processed (disabled) khi đã xử lý.

### C-TC-02: Convert lead sang customer
- Mục tiêu: Đảm bảo chuyển đổi lead thành khách hàng chính xác.
- Bước thực hiện:
  1. Từ lead đủ điều kiện, chọn Convert to Customer.
- Kết quả mong đợi:
  - Tạo customer liên kết contact.
  - Trạng thái qualification cập nhật converted.

### C-TC-03: Assignment owner/support hiển thị đúng màu và dữ liệu
- Mục tiêu: Kiểm tra phân công và hiển thị nhất quán.
- Bước thực hiện:
  1. Tạo assignment owner.
  2. Tạo assignment support.
  3. Kết thúc một assignment.
- Kết quả mong đợi:
  - assignment_type owner/support hiển thị badge đúng màu.
  - status active/ended/cancelled hiển thị đúng màu.

### C-TC-04: Staff availability ảnh hưởng năng lực phân công
- Mục tiêu: Đảm bảo lịch nghỉ ảnh hưởng phân phối khách.
- Bước thực hiện:
  1. Đặt staff status absent/leave.
  2. Chạy phân phối hoặc gán thủ công.
- Kết quả mong đợi:
  - Staff không đủ điều kiện không được chọn/ưu tiên nhận mới.

### C-TC-05: Customer Distribution Batch xử lý hàng loạt
- Mục tiêu: Đảm bảo phân phối hàng loạt chạy đúng và có log.
- Bước thực hiện:
  1. Tạo batch phân phối.
  2. Thực thi batch.
  3. Mở trang chi tiết batch và items.
- Kết quả mong đợi:
  - Batch có status processing/completed/failed hợp lệ.
  - Items có result_status success/skipped rõ ràng.

---

# Module D. Sales

## D.1. Vì sao module Sales tồn tại
- Chuẩn hóa quy trình báo giá từ tạo, duyệt, gửi, phản hồi đến thanh toán.
- Đảm bảo kiểm soát rủi ro qua approval flow.
- Tăng tốc chốt sale nhờ tự động hóa email và theo dõi trạng thái.

## D.2. Chức năng chính cần có
- Quotation và vòng đời trạng thái.
- Approval workflow.
- Payment tracking.
- Service, Service Package, Price Book.
- Bank Account phục vụ thanh toán báo giá.

## D.3. Activity diagram luồng báo giá đầu-cuối
~~~mermaid
flowchart TD
    A[Sales tạo Quotation Draft] --> B[Submit Approval]
    B --> C{Manager duyệt?}
    C -->|Approve| D[Quotation Approved]
    C -->|Reject| E[Trả về chỉnh sửa hoặc hủy]
    D --> F[Gửi email báo giá cho khách]
    F --> G[Khách mở link public báo giá]
    G --> H{Khách phản hồi}
    H -->|Accept| I[Quotation Accepted]
    H -->|Reject| J[Quotation Rejected]
    H -->|Request Revision| K[Revision Requested]
    I --> L[Theo dõi thanh toán]
    L --> M[Paid/Partially Paid/Pending Verification]
~~~

## D.4. Test case chi tiết

### D-TC-01: Tạo báo giá từ bảng giá hợp lệ
- Mục tiêu: Đảm bảo tạo quotation với dữ liệu pricing chuẩn.
- Bước thực hiện:
  1. Vào Quotation Create.
  2. Chọn customer, price book, service package.
  3. Nhập quantity/discount/tax.
  4. Lưu.
- Kết quả mong đợi:
  - Quotation tạo thành công trạng thái draft.
  - Tổng tiền tính đúng.

### D-TC-02: Submit approval và manager approve
- Mục tiêu: Đảm bảo luồng duyệt hoạt động.
- Bước thực hiện:
  1. Sales submit approval.
  2. Manager vào Quotation Approval, chọn approve.
- Kết quả mong đợi:
  - Trạng thái quotation thành approved.
  - Bản ghi approval có reviewed_at.

### D-TC-03: Gửi email báo giá sau khi approved
- Mục tiêu: Đảm bảo chỉ báo giá đủ điều kiện mới gửi.
- Bước thực hiện:
  1. Từ quotation approved, thực hiện Send.
  2. Kiểm tra email log.
- Kết quả mong đợi:
  - Có email log queued/sent.
  - Nếu lỗi gửi, status failed và có error message.

### D-TC-04: Public quotation confirm
- Mục tiêu: Đảm bảo khách phản hồi qua link public.
- Bước thực hiện:
  1. Mở link quotation public bằng token.
  2. Chọn Accept hoặc Reject hoặc Request Revision.
- Kết quả mong đợi:
  - Trạng thái quotation cập nhật đúng.
  - Ghi nhận lịch sử phản hồi.

### D-TC-05: Payment tracking update status
- Mục tiêu: Đảm bảo cập nhật thanh toán chuẩn.
- Bước thực hiện:
  1. Vào Payment Tracking.
  2. Chọn quotation và update mark paid/pending/unpaid.
- Kết quả mong đợi:
  - payment_status đổi đúng.
  - Các hành động không hợp lệ bị ẩn/chặn.

---

# 4. Test liên thông xuyên module (E2E)

## E2E-01: Từ Landing Page đến chốt Sale
- Mục tiêu: Kiểm tra hành trình đầy đủ từ lead đến doanh thu.
- Bước thực hiện:
  1. Khách truy cập UTM link và submit form.
  2. Hệ thống tạo Contact + Qualification.
  3. Staff xử lý lead và convert Customer.
  4. Sales tạo Quotation, duyệt và gửi email.
  5. Khách accept báo giá.
  6. CS/Sales cập nhật thanh toán.
- Kết quả mong đợi:
  - Dữ liệu xuyên suốt không đứt liên kết.
  - Tracking source/campaign còn giữ được khi truy vết.

## E2E-02: Luồng lỗi domain/email
- Mục tiêu: Đảm bảo hệ thống fail-safe khi hạ tầng gửi lỗi.
- Bước thực hiện:
  1. Cấu hình sending domain chưa pass.
  2. Thử gửi campaign/quotation email.
- Kết quả mong đợi:
  - Hệ thống phản hồi lỗi rõ ràng.
  - Không ghi sent giả.

---

# 5. Checklist UAT nhanh theo module

## Email
- Template tạo/sửa/xóa ổn định.
- Account/domain phản ánh đúng readiness.
- Event gửi/open/click theo dõi được.

## Marketing
- Landing page publish/unpublish đúng rule.
- UTM links thao tác đầy đủ create/copy/delete.
- Submission xử lý created/updated/skipped chuẩn.

## CRM
- Lead pipeline thao tác đúng lifecycle.
- Assignment owner/support rõ ràng.
- Staff availability tác động đúng tới phân phối.

## Sales
- Quotation lifecycle đầy đủ.
- Approval workflow không bỏ qua bước.
- Payment tracking cập nhật đúng và có kiểm soát.

---

# 6. Hướng dẫn ghi nhận bug
- Mẫu ghi bug bắt buộc:
  - Module
  - Màn hình
  - Bước tái hiện
  - Kết quả thực tế
  - Kết quả mong đợi
  - Mức độ nghiêm trọng (S1/S2/S3/S4)
  - Ảnh/chứng cứ (screenshot/video/log)
- Quy tắc ưu tiên:
  - S1: chặn luồng kinh doanh chính hoặc mất dữ liệu.
  - S2: sai nghiệp vụ quan trọng, có workaround hạn chế.
  - S3: lỗi giao diện hoặc logic phụ.
  - S4: góp ý cải tiến.

---

# 7. Kết luận BA + Tester
- Bộ tài liệu này đảm bảo:
  - Có đầy đủ lý do nghiệp vụ cho từng module.
  - Có luồng vận hành dạng activity diagram rõ ràng.
  - Có test case chi tiết để QA và UAT triển khai ngay.
- Khuyến nghị vận hành:
  - Chạy regression theo thứ tự: Marketing -> CRM -> Sales -> Email events reconciliation.
  - Mỗi thay đổi lớn cần retest ít nhất 2 luồng E2E ở mục 4.
