# Hướng dẫn kiểm thử UI toàn hệ thống

## 1. Thông tin tài liệu

- Tên tài liệu: UI Master Test Guide
- Phạm vi: Email, Marketing, CRM, Sales, Configuration
- Kiểu kiểm thử: Manual QA qua giao diện trình duyệt
- Kênh chạy: Admin panel và Public routes
- Mục tiêu: Bao phủ luồng chức năng chính, phân quyền, tích hợp liên module, và các trường hợp âm tính

## 2. Mục tiêu kiểm thử

- Xác nhận toàn bộ chức năng nghiệp vụ hoạt động đúng trên UI.
- Xác nhận phân quyền theo vai trò không bị lộ quyền thao tác.
- Xác nhận các luồng liên thông từ Marketing sang CRM sang Sales.
- Xác nhận các luồng email tracking và unsubscribe hoạt động đúng.
- Xác nhận các cấu hình nền tảng ảnh hưởng đúng tới hành vi hệ thống.

## 3. Phạm vi kiểm thử

### 3.1. Trong phạm vi

- Configuration: Company Settings, User, Position, Staff, Sending Account, Sending Domain.
- CRM: Contact, Contact Qualification, Customer, Assignment, Interaction, Customer Care, Distribution Batch.
- Marketing: Form Template, Landing Page, Landing Page Submission, Segment, Contact List, Tag, Campaign, Campaign Report, UTM report.
- Email: Email Template, Suppression Entry, open/click tracking, unsubscribe.
- Sales: Service, Service Package, Price Book, Bank Account, Quotation, Quotation Approval, Payment Tracking, public quotation.

### 3.2. Ngoài phạm vi

- Pen-test bảo mật sâu.
- Load test hiệu năng diện rộng.
- Tích hợp cổng mail/QR bên thứ ba ở môi trường production thật.

## 4. Môi trường và công cụ

### 4.1. Môi trường chạy

- Admin panel: /admin
- Landing page public: /lp/{slug}
- Email tracking: /m/open/{token}.gif, /m/click/{token}
- Unsubscribe: /m/unsubscribe/{token}
- Public quotation: /q/{quotationCode}/{token}

### 4.2. Công cụ đề xuất

- Trình duyệt: Chrome bản mới nhất.
- Mail inbox local: Mailpit hoặc Mailhog.
- Cơ sở dữ liệu: MySQL local đã migrate và seed.
- Chế độ test: 1 cửa sổ thường + 1 cửa sổ ẩn danh.

### 4.3. Tài khoản kiểm thử đề xuất

- Admin: toàn quyền.
- Marketing Manager.
- Marketing Staff.
- Customer Service Manager.
- Customer Service Staff 1.
- Customer Service Staff 2.
- Viewer hoặc role không có quyền chỉnh sửa.

## 5. Quy ước ghi nhận kết quả

- Pass: kết quả thực tế trùng hoàn toàn kỳ vọng.
- Fail: khác kỳ vọng hoặc lỗi nghiệp vụ/UI.
- Blocked: thiếu dữ liệu, thiếu quyền, hoặc lỗi hạ tầng.

Mẫu ghi nhận nhanh:

- Mã test case:
- Người test:
- Role sử dụng:
- Build/nhánh:
- Bước tái hiện:
- Kết quả kỳ vọng:
- Kết quả thực tế:
- Ảnh/Video:
- Mức độ: Critical/High/Medium/Low

## 6. Bộ test smoke bắt buộc trước khi test sâu

1. Đăng nhập admin vào /admin thành công.
2. Menu nhóm Configuration, CRM, Email Marketing, Sales hiển thị.
3. Dashboard mở không lỗi.
4. CRUD tối thiểu 1 bản ghi ở mỗi module chính mở form bình thường.
5. Public route landing page mở được bằng slug hợp lệ.
6. Public route quotation mở được bằng code/token hợp lệ.

## 7. Kiểm thử chi tiết theo module

## 7A. Configuration Module

### CFG-01. Company Settings

Mục tiêu: xác nhận lưu cấu hình công ty và VietQR.

Bước test:

1. Vào menu Company Settings.
2. Nhập Company Name, Tax Code, Address, Phone, Email, Website.
3. Upload logo ảnh dưới 2MB.
4. Nhập VietQR Client ID và VietQR API Key.
5. Bấm Save.
6. Reload trang.

Kỳ vọng:

- Hiển thị thông báo lưu thành công.
- Dữ liệu được giữ nguyên sau reload.
- Logo hiển thị đúng.
- User không phải admin không truy cập được trang.

### CFG-02. User Management

Mục tiêu: xác nhận tạo và phân role user.

Bước test:

1. Vào User.
2. Tạo user mới với role customer_service_staff.
3. Đăng xuất và đăng nhập bằng user mới.
4. Quan sát menu và quyền thao tác.

Kỳ vọng:

- User tạo thành công.
- Menu hiển thị theo role.
- Không thể truy cập trang ngoài quyền khi nhập URL trực tiếp.

### CFG-03. Position và Staff

Mục tiêu: xác nhận quản lý nhân sự và lịch khả dụng.

Bước test:

1. Vào Position tạo mới chức danh.
2. Vào Staff tạo mới nhân sự gắn user và position.
3. Mở relation Staff Availability, thêm ngày nghỉ.
4. Kiểm tra phân bổ khách có né staff đang nghỉ hay không.

Kỳ vọng:

- Dữ liệu staff/position lưu đúng.
- Availability lưu đúng trạng thái và ngày.
- Nghiệp vụ phân bổ tôn trọng khả dụng.

### CFG-04. Sending Account và Sending Domain

Mục tiêu: xác nhận cấu hình kênh gửi email.

Bước test:

1. Tạo Sending Account với provider hợp lệ.
2. Đặt account mặc định nếu có cờ default.
3. Tạo Sending Domain và trạng thái xác thực.
4. Tạo campaign chọn account/domain vừa tạo.

Kỳ vọng:

- Account/domain lưu thành công.
- Campaign chọn được account hợp lệ.
- Domain không hợp lệ bị chặn nếu có rule.

## 7B. CRM Module

### CRM-01. Contact cá nhân

Mục tiêu: tạo mới contact personal.

Bước test:

1. Vào Contact hoặc Personal Contact.
2. Tạo contact với email và phone hợp lệ.
3. Điền profile cá nhân.
4. Save.

Kỳ vọng:

- Contact tạo thành công.
- Hiển thị đúng type personal.
- Search bằng email/phone tìm ra bản ghi.

### CRM-02. Contact doanh nghiệp

Mục tiêu: tạo mới contact business.

Bước test:

1. Vào Business Contact.
2. Nhập company name, tax code, legal representative, email.
3. Save.

Kỳ vọng:

- Contact business tạo thành công.
- Hiển thị đúng thông tin doanh nghiệp.

### CRM-03. Contact Qualification pipeline

Mục tiêu: xác nhận pipeline lead và thao tác từng stage.

Bước test:

1. Vào Contact Qualification list.
2. Kiểm tra tab stage: all, new, assigned, contacting, follow_up, qualified, converted.
3. Kiểm tra khu vực Kanban hiển thị số lượng theo stage.
4. Bấm tiêu đề stage trên Kanban để điều hướng tab.
5. Dùng action process_lead để đổi trạng thái.
6. Dùng action convert_to_customer khi lead đủ điều kiện.

Kỳ vọng:

- Tab lọc đúng dữ liệu theo status.
- Kanban khớp số lượng với list.
- Điều hướng tab từ Kanban đúng.
- Chỉ role được cấp quyền mới thấy action.
- Lead converted tạo Customer tương ứng.

### CRM-04. Customer management

Mục tiêu: xác nhận quản lý hồ sơ khách hàng và phân công.

Bước test:

1. Vào Customer.
2. Tạo mới customer personal/business.
3. Dùng action assign owner/support.
4. Dùng action transfer owner.
5. Dùng action release customer.

Kỳ vọng:

- Assignment được tạo đúng loại owner/support.
- Chỉ 1 owner active tại cùng thời điểm.
- Transfer ghi nhận lịch sử lý do.
- Release kết thúc assignment active.

### CRM-05. Customer interactions

Mục tiêu: xác nhận ghi nhận tương tác và lịch follow-up.

Bước test:

1. Mở chi tiết customer.
2. Thêm interaction call/email/meeting.
3. Đặt next follow-up và trạng thái scheduled/completed.
4. Quay về Customer Care page kiểm tra bộ đếm.

Kỳ vọng:

- Interaction hiển thị đúng timeline.
- Dữ liệu follow-up phản ánh đúng ở dashboard/customer care.

### CRM-06. Customer Care page

Mục tiêu: xác nhận workspace chăm sóc khách hàng.

Bước test:

1. Vào Customer Care.
2. Chọn customer bằng action care.
3. Tab overview: kiểm tra thông tin tổng quan.
4. Tab quotation: xem danh sách báo giá khách.
5. Tab interaction: thêm ghi chú/call log.
6. Tab email: gửi email chăm sóc, upload attachment hợp lệ.
7. Dùng release modal cho staff.

Kỳ vọng:

- Modal workspace mở đúng khách.
- Gửi email thành công, có bản ghi log liên quan.
- Validation attachment hoạt động đúng.
- Staff chỉ thao tác trên khách được phân công.

### CRM-07. Customer Distribution Batch

Mục tiêu: xác nhận phân bổ hàng loạt.

Bước test:

1. Vào Customer Distribution Batch.
2. Tạo batch mới, chọn strategy round_robin hoặc least_loaded.
3. Chạy batch.
4. Kiểm tra item kết quả từng customer.
5. Đối chiếu assignment thực tế trong Customer.

Kỳ vọng:

- Batch đổi trạng thái hoàn tất khi chạy thành công.
- Assignment tạo đúng nhân viên theo strategy.
- Không tạo trùng owner active bất hợp lệ.

## 7C. Marketing Module

### MKT-01. Form Template

Mục tiêu: xác nhận tạo form template và field mapping.

Bước test:

1. Vào Form Template.
2. Tạo template cho personal hoặc business.
3. Thêm nhiều field: text, email, phone, textarea, select, checkbox, hidden.
4. Map field vào dữ liệu contact phù hợp.
5. Chuyển trạng thái active.

Kỳ vọng:

- Template lưu đúng cấu trúc field.
- Template active có thể chọn trong Landing Page.

### MKT-02. Landing Page tạo và publish

Mục tiêu: xác nhận tạo trang đích và ràng buộc publish.

Bước test:

1. Vào Landing Page, tạo bản mới.
2. Nhập name, slug, html content.
3. Chọn audience_type.
4. Gắn form template personal/business đúng ngữ cảnh.
5. Save draft.
6. Chuyển status published.
7. Dùng chức năng preview admin.

Kỳ vọng:

- Slug unique.
- Publish bị chặn nếu thiếu form bắt buộc theo audience.
- Preview admin hiển thị không lỗi.

### MKT-03. Public Landing Page submit

Mục tiêu: xác nhận luồng public nhận lead.

Bước test:

1. Mở /lp/{slug} ở cửa sổ ẩn danh.
2. Submit form với email mới.
3. Mở trang thank-you.
4. Quay lại admin vào Landing Page Submission kiểm tra bản ghi.
5. Submit lại cùng email để kiểm tra không tạo trùng contact.

Kỳ vọng:

- Submission được lưu.
- Contact được tạo mới hoặc cập nhật đúng logic.
- UTM params (nếu có) được lưu trong submission.

### MKT-04. Segment, Contact List, Tag

Mục tiêu: xác nhận phân khúc và danh sách hoạt động.

Bước test:

1. Tạo Tag mới.
2. Tạo Contact List mới.
3. Thêm contact vào list.
4. Tạo Segment theo điều kiện role/status/tag/list.
5. Preview số lượng trong segment.

Kỳ vọng:

- Thành viên list đúng.
- Segment trả đúng tập contact.

### MKT-05. Campaign lifecycle

Mục tiêu: xác nhận vòng đời campaign.

Bước test:

1. Vào Campaign hoặc Marketing Campaign.
2. Tạo campaign draft: subject, template, sending account, segment/list.
3. Prepare recipients.
4. Kiểm tra số recipient pending/queued.
5. Trigger send hoặc schedule.
6. Theo dõi trạng thái sending/sent.
7. Pause hoặc cancel (nếu luồng cho phép).

Kỳ vọng:

- Recipient records được tạo đúng.
- Trạng thái campaign chuyển đúng vòng đời.
- Lỗi gửi được ghi nhận vào recipient/event.

### MKT-06. Campaign Report và UTM Report

Mục tiêu: xác nhận thống kê chiến dịch.

Bước test:

1. Vào Campaign Report page.
2. Đổi campaign trong dropdown.
3. Kiểm tra stats total, sent, failed, opened, clicked, rates.
4. Vào UTM report widget, chọn landing page.
5. Export CSV.

Kỳ vọng:

- Số liệu hiển thị khớp recipient/event thực tế.
- Export file CSV thành công.

## 7D. Email Module

### EML-01. Email Template render biến

Mục tiêu: xác nhận template có thể thay biến động.

Bước test:

1. Vào Email Template.
2. Tạo template có biến tên, unsubscribe link, landing page link.
3. Gắn template vào campaign hoặc email chăm sóc.
4. Gửi thử.

Kỳ vọng:

- Nội dung email render biến đúng.
- Không còn placeholder thô trong email nhận được.

### EML-02. Open và Click Tracking

Mục tiêu: xác nhận ghi nhận open/click event.

Bước test:

1. Mở email trong Mailpit.
2. Mở nội dung HTML có pixel tracking.
3. Bấm link tracking trong email.
4. Vào report hoặc event log kiểm tra opened/clicked.

Kỳ vọng:

- Tạo event opened khi pixel được gọi.
- Tạo event clicked và redirect đúng URL đích.

### EML-03. Unsubscribe flow

Mục tiêu: xác nhận người nhận có thể unsubscribe.

Bước test:

1. Mở link unsubscribe trong email.
2. Xác nhận unsubscribe ở trang public.
3. Vào admin kiểm tra suppression entry.
4. Chạy campaign mới chứa contact đó.

Kỳ vọng:

- Contact chuyển trạng thái unsubscribe hoặc do_not_contact theo rule.
- Suppression được tạo.
- Campaign bỏ qua contact đã unsubscribe.

### EML-04. Suppression Entry quản trị

Mục tiêu: xác nhận quản lý danh sách chặn gửi.

Bước test:

1. Vào Suppression Entry.
2. Tạo bản ghi chặn thủ công.
3. Chạy campaign gửi tới email đó.

Kỳ vọng:

- Recipient bị skip/unsubscribed theo quy tắc.

## 7E. Sales Module

### SAL-01. Service và Service Package

Mục tiêu: xác nhận danh mục dịch vụ.

Bước test:

1. Tạo Service mới.
2. Tạo nhiều Service Package thuộc service.
3. Chỉnh active/inactive.

Kỳ vọng:

- Package liên kết đúng service.
- Package inactive không được chọn trong quotation create.

### SAL-02. Price Book và Access Rule

Mục tiêu: xác nhận bảng giá theo đối tượng và quyền staff.

Bước test:

1. Tạo Price Book audience personal.
2. Thêm item giá cho package.
3. Tạo Price Book audience business.
4. Thiết lập rule staff nào được dùng price book nào.
5. Đăng nhập staff test tạo quotation.

Kỳ vọng:

- Staff chỉ thấy price book được cấp.
- Customer personal không dùng price book business trừ khi admin override.

### SAL-03. Bank Account

Mục tiêu: xác nhận tài khoản nhận thanh toán.

Bước test:

1. Tạo bank account.
2. Chọn bank account khi tạo quotation.
3. Kiểm tra khối QR thanh toán hiển thị.

Kỳ vọng:

- QR hoặc thông tin chuyển khoản hiển thị đúng.

### SAL-04. Quotation create và tính tiền

Mục tiêu: xác nhận tạo báo giá và tính toán dòng tiền.

Bước test:

1. Vào Quotation create.
2. Chọn customer và price book hợp lệ.
3. Thêm nhiều item, quantity, unit price, discount, VAT.
4. Save.

Kỳ vọng:

- Tính subtotal, discount, VAT, grand total đúng.
- Mã quotation sinh tự động, trạng thái draft.

### SAL-05. Approval flow

Mục tiêu: xác nhận duyệt báo giá nội bộ.

Bước test:

1. Từ quotation draft bấm submit approval.
2. Đăng nhập manager vào Quotation Approval.
3. Approve hoặc reject.
4. Quay lại quotation kiểm tra trạng thái.

Kỳ vọng:

- Draft sang pending approval.
- Approve sang approved.
- Reject quay về draft hoặc trạng thái theo rule.
- Có bản ghi approval history.

### SAL-06. Send quotation email

Mục tiêu: xác nhận gửi báo giá cho khách.

Bước test:

1. Trên quotation approved, bấm send.
2. Mở Mailpit kiểm tra email nhận.
3. Bấm public link trong email.

Kỳ vọng:

- Quotation chuyển trạng thái sent.
- Có email lịch sử trong tab Email History.
- Public link mở được trang chi tiết quotation.

### SAL-07. Public quotation actions

Mục tiêu: xác nhận khách hàng phản hồi báo giá.

Bước test:

1. Mở /q/{quotationCode}/{token} ở ẩn danh.
2. Thực hiện Accept.
3. Tạo quotation khác để test Reject.
4. Tạo quotation khác để test Request Revision.
5. Test tải PDF public.

Kỳ vọng:

- Accept cập nhật trạng thái accepted.
- Reject cập nhật rejected.
- Request revision cập nhật revision_requested.
- PDF tải thành công.

### SAL-08. OTP xác thực public quotation

Mục tiêu: xác nhận cơ chế xác thực OTP nếu bật trong luồng.

Bước test:

1. Trên trang public quotation, bấm gửi OTP.
2. Nhập OTP đúng và sai.
3. Thực hiện action yêu cầu xác thực.

Kỳ vọng:

- OTP đúng cho phép thao tác.
- OTP sai hiển thị lỗi hợp lệ.

### SAL-09. Revision flow

Mục tiêu: xác nhận tạo bản sửa từ báo giá cũ.

Bước test:

1. Đảm bảo quotation ở trạng thái revision_requested.
2. Admin tạo revision mới.
3. Kiểm tra quotation cũ bị superseded.
4. Kiểm tra quotation mới là draft version tăng.

Kỳ vọng:

- Chuỗi version chính xác.
- Public token mới được tạo cho revision.

### SAL-10. Payment Tracking

Mục tiêu: xác nhận theo dõi thanh toán.

Bước test:

1. Vào Payment Tracking list.
2. Chọn quotation accepted.
3. Chạy action mark pending verification.
4. Chạy action mark paid.
5. Kiểm tra side effects: nâng lifecycle khách hoặc log email nội bộ.

Kỳ vọng:

- Chuyển payment status đúng state machine.
- Mark paid tạo dữ liệu liên quan theo nghiệp vụ.

## 8. Test phân quyền bắt buộc

Thực hiện cho mỗi role:

1. Đăng nhập role.
2. Chụp lại menu nhìn thấy.
3. Cố truy cập trực tiếp URL nhạy cảm:
   - /admin/sales/quotation-approvals
   - /admin/sales/payment-trackings
   - /admin/company-settings
4. Cố thao tác action nhạy cảm:
   - approve/reject quotation
   - mark paid
   - release customer không thuộc quyền

Kỳ vọng:

- Ẩn menu ngoài quyền.
- Truy cập URL bị chặn.
- Action không hiện hoặc bị chặn ở backend.

## 9. Kịch bản tích hợp end-to-end quan trọng

### E2E-01. Lead đến doanh thu

1. Publish landing page có form.
2. Người dùng public submit form.
3. Contact xuất hiện trong CRM.
4. Qualification chuyển qualified.
5. Convert thành customer.
6. Assign owner cho staff.
7. Staff tạo quotation.
8. Manager approve.
9. Gửi quotation email.
10. Khách mở public link và accept.
11. CS cập nhật payment paid.

Kỳ vọng:

- Dữ liệu đi xuyên module không đứt gãy.
- Các trạng thái chính xác tại từng bước.

### E2E-02. Email marketing và unsubscribe

1. Tạo campaign từ segment chứa contact mới.
2. Prepare và send campaign.
3. Ghi nhận open và click.
4. Người dùng unsubscribe từ email.
5. Send campaign lần 2.

Kỳ vọng:

- Contact unsubscribe bị loại khỏi lần gửi sau.
- Report thể hiện opened/clicked/unsubscribed đúng.

## 10. Test hồi quy sau mỗi thay đổi lớn

Checklist regression nhanh:

1. Tạo Contact personal và business.
2. Chạy luồng qualification đến convert.
3. Tạo customer assignment owner.
4. Tạo quotation và gửi duyệt.
5. Approve và send quotation.
6. Mở public quotation và accept.
7. Mark payment paid.
8. Tạo landing page mới và submit public.
9. Chạy campaign test và kiểm tra report.
10. Kiểm tra company settings vẫn lưu được.

## 11. Test UI/UX và tính ổn định

- Responsive: desktop, tablet, mobile chiều dọc.
- Form validation:
  - Thiếu trường bắt buộc.
  - Sai định dạng email/url/phone.
  - Upload file vượt kích thước hoặc sai loại.
- Search, filter, sort, pagination hoạt động ổn định.
- Notification toast hiển thị đúng ngôn ngữ và nội dung.
- Không phát sinh lỗi trắng trang hoặc lỗi 500 khi thao tác liên tục.

## 12. Test đa ngôn ngữ

1. Chuyển ngôn ngữ qua route switch.
2. Kiểm tra label/menu/action ở các trang chính.
3. Kiểm tra email template hiển thị đúng ngôn ngữ theo ngữ cảnh.

Kỳ vọng:

- Nội dung vi/en hiển thị đầy đủ, không bị key thô.

## 13. Tiêu chí kết thúc kiểm thử

- 100 phần trăm test case mức Critical và High đã chạy.
- Tất cả lỗi Critical đã fix và retest pass.
- Không còn lỗi chặn luồng nghiệp vụ end-to-end.
- Báo cáo test có bằng chứng đầy đủ cho mỗi module.

## 14. Phụ lục mã test case gợi ý

- Configuration: CFG-01 đến CFG-04
- CRM: CRM-01 đến CRM-07
- Marketing: MKT-01 đến MKT-06
- Email: EML-01 đến EML-04
- Sales: SAL-01 đến SAL-10
- End-to-end: E2E-01 đến E2E-02

Tổng đề xuất tối thiểu: 33 test case cốt lõi, chưa bao gồm biến thể âm tính và kiểm thử dữ liệu biên.
