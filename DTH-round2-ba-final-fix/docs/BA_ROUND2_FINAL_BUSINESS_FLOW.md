# DTH Business Core — BA Round 2 Final Business Flow

Ngày rà soát: 08/08/2026  
Nguồn chuẩn: ZIP source local mới nhất do dự án cung cấp, nhánh `feature/crm-company-lead-opportunity-flow`.

## 1. Mục tiêu của Round 2

Round 2 khóa lại nghiệp vụ từ lúc Sales đã có Opportunity cho đến khi khách thực sự trở thành Customer, đồng thời dọn các điểm dễ gây sai dữ liệu ở Landing Page/Form/UTM và cấu hình thanh toán.

Sau Round 2, các thay đổi tiếp theo nên chủ yếu là UI/UX, ngoại trừ các phase mở rộng đã được ghi rõ ở cuối tài liệu.

## 2. Nguyên tắc nghiệp vụ chốt

1. **Maker–checker bắt buộc cho Báo giá**: Sales Staff lập; Sales Manager duyệt. Người lập không tự duyệt.
2. **Admin không thực hiện workflow thương mại**: Admin được xem/audit, quản lý master/config, nhưng không gửi/duyệt báo giá hoặc xác minh thanh toán thay bộ phận nghiệp vụ.
3. **Báo giá đã gửi là snapshot thương mại bất biến**: giá, ngân hàng, PDF và người được phép xác nhận phải được chốt trước khi gửi.
4. **Khách chấp nhận báo giá chưa phải Customer**. Chỉ sau khi Finance xác minh thanh toán thì Opportunity mới Won và Contact/Company mới được chuyển sang Customer lifecycle.
5. **QR thanh toán không đồng nghĩa với đã thanh toán**. QR chỉ hỗ trợ nhập thông tin chuyển khoản; Finance vẫn là nguồn xác nhận Paid trong Round 2.
6. **Nguồn Marketing phải đi xuyên suốt**: UTM/Referrer từ Landing Page → Submission → Lead → Customer metadata.
7. **Form Template là mẫu thu thập dữ liệu**, không phải nơi tự tạo Tag/List/Segment hoặc tự quyết định dịch vụ kinh doanh.

## 3. Phân vai cuối cùng

| Nghiệp vụ | Sales Staff | Sales Manager | Finance | CSKH | Admin/Executive/Viewer |
|---|---|---|---|---|---|
| Tạo báo giá từ Opportunity được giao | Có | Không | Không | Không | Không |
| Sửa Draft | Có (deal của mình) | Có quyền hỗ trợ | Không | Không | Chỉ xem |
| Gửi duyệt | Có | Có thể hỗ trợ deal | Không | Không | Không |
| Duyệt/Từ chối | Không | Có | Không | Không | Không |
| Gửi khách | Người phụ trách Sales | Có thể hỗ trợ | Không | Không | Không |
| Xác minh Paid | Không | Không | Có | Không | Không |
| Chăm sóc Customer sau Paid | Không mặc định | Không mặc định | Không | Có | Chỉ xem/audit |

## 4. State machine Báo giá

```text
Draft
  -> Pending Approval
      -> Approved
          -> Sent
              -> Viewed
                  -> Accepted
                  -> Rejected
                  -> Revision Requested
              -> Accepted / Rejected / Revision Requested

Pending Approval -> Draft       (Manager từ chối duyệt)
Revision Requested -> Superseded + tạo Version mới ở Draft
Draft/Pending/Approved -> Cancelled theo quyền Manager
Sent/Viewed -> Expired khi quá hạn
```

**Không còn đường Draft → Approved trực tiếp.**

## 5. Tạo và phê duyệt Báo giá

### Giữ lại

- Opportunity bắt buộc có Primary Contact và Sales owner.
- Price Book phải hợp loại đối tượng (Personal/Business) và có access rule.
- Quotation chỉ load package khớp `Opportunity.service_interest`.
- Tên dịch vụ, package, đơn giá, VAT và đơn vị là snapshot từ Price Book/Service Package và không cho Sales Staff sửa trực tiếp trên Quotation.
- Quantity và discount là dữ liệu thương lượng; discount phải nằm trong giới hạn Price Book.
- Báo giá phải có Bank Account hợp lệ trước khi gửi phê duyệt.
- Sales Manager không được tự tạo rồi tự duyệt báo giá.

### Bỏ

- Tạo báo giá từ Opportunity bởi Admin/CSKH Manager.
- Bypass Draft → Approved.
- Sales tự nhập lại giá/VAT/tên package ngoài Price Book.
- So sánh `10%` với `10 VND` ở discount limit.

## 6. Email gửi khách

### Giữ lại

- Email Template loại `quotation`.
- Subject cho phép Sales chỉnh trước khi gửi.
- Nội dung email được render từ template hoặc fallback template hệ thống.
- PDF đính kèm.
- Email Log + CRM interaction + Opportunity activity.
- Gửi bằng Sending Account SMTP của **phòng Kinh doanh đang phụ trách**, không dùng mailer mặc định toàn hệ thống.

### Sửa/chốt

- Recipient email phải trùng email snapshot của bên nhận báo giá; không cho gõ một email tùy ý.
- Chọn riêng **người được phép xác nhận báo giá** từ danh sách Contact của Opportunity.
- Không cho người dùng chỉnh raw HTML trong popup Gửi; chỉ preview HTML đã render.
- Template rỗng/`meta` không xuất hiện trong dropdown.
- Kiểm tra Sales SMTP trước khi queue để lỗi cấu hình hiện ngay ở form.
- Job gửi email không tự retry (`tries = 1`) để tránh một phản hồi SMTP không chắc chắn dẫn tới gửi trùng báo giá. Nếu thất bại, Quotation vẫn Approved và Sales chủ động gửi lại.
- Send thành công mới chuyển `Approved -> Sent`.

## 7. PDF và Public Link

- Trước khi queue email, hệ thống regenerate PDF chính thức.
- Sau khi gửi, **không được regenerate PDF** của version đó nữa.
- Public PDF chỉ tải file PDF snapshot đã gửi, không render lại từ master data hiện tại.
- Public Link chỉ xuất hiện từ trạng thái Sent trở đi.
- GET Public Link tự ghi nhận lượt xem; bỏ nút “Đánh dấu đã xem”.

## 8. Xác nhận điện tử và OTP

Giữ OTP nhưng gọi đúng nghiệp vụ là **Xác nhận điện tử**, không tuyên bố đây là chữ ký số/chữ ký điện tử pháp lý chuyên dụng.

### Quy tắc

- Chỉ email được Sales chọn làm `authorized_signer` mới được nhận OTP và xác nhận.
- OTP: 6 số, lưu hash trong cache, hiệu lực 5 phút.
- Giới hạn gửi: 3 lần/phút, 8 lần/giờ cho quotation + email.
- Giới hạn verify sai: 5 lần/10 phút.
- Sau verify thành công, quyền xác nhận tồn tại 10 phút và bị consume sau hành động.
- `Accept`, `Reject`, `Request Revision` đều bắt buộc OTP.
- Reject và Request Revision bắt buộc lý do.
- Confirmation lưu `otp_verified_at`, `otp_verified_email`, IP, user agent và phương thức `email_otp`.

## 9. Request Revision

Giữ chức năng này vì khách thường không chỉ Accept/Reject.

Flow:

```text
Khách Request Revision + OTP + lý do
-> Quotation = RevisionRequested
-> Sales owner bấm “Tạo phiên bản chỉnh sửa”
-> Version cũ = Superseded
-> Version mới = Draft
-> sửa lại
-> Submit Approval lại
-> gửi lại khách
```

Không sửa trực tiếp bản đã gửi.

## 10. VietQR và Tài khoản ngân hàng

### Giữ

- Đồng bộ danh sách ngân hàng Việt Nam vào master `vn_banks`.
- Chọn ngân hàng từ dropdown thay vì gõ tay bank code.
- Nếu có VietQR Client ID/API Key: cho phép lookup tên chủ tài khoản và generate QR v2.
- Nếu chưa có credential Generate API: fallback sang VietQR QuickLink image để vẫn tạo QR.
- Mỗi quotation lưu `payment_snapshot` riêng để thay đổi Bank Account master sau này không làm đổi báo giá cũ.
- Một Bank Account `is_default` tại một thời điểm.

### Bảo mật

- `vietqr_api_key` lưu encrypted.
- API key không hydrate ngược ra browser khi mở trang Company Settings.
- Để trống API Key khi Save nghĩa là giữ secret cũ.

### Không làm trong Round 2

- QR scan hoặc QR hiển thị **không tự chuyển Paid**.
- Không polling tài khoản ngân hàng bằng QR API để tự phán Paid.

## 11. Thanh toán

Round 2 hỗ trợ **thanh toán đủ 100%** cho một quotation.

```text
Accepted
-> Unpaid
-> Khách chuyển khoản theo QR
-> Khách có thể bấm “Thông báo đã chuyển khoản”
-> Pending Verification
-> Finance đối soát
   -> Paid
   hoặc
   -> Unpaid (không khớp, có lý do)
```

### Lý do chưa làm partial payment

Schema hiện có payment status nhưng chưa có ledger/installment model đủ để giải thích mỗi khoản tiền, công nợ còn lại, hoàn tiền và nhiều lần chuyển khoản. Cho phép `PartiallyPaid` bằng một cờ trạng thái sẽ dễ đóng nhầm một báo giá chưa trả đủ.

Partial payment/installment là phase riêng.

## 12. Sau Paid: chuyển sang Customer

Paid được Finance xác minh là trigger duy nhất của conversion:

1. Quotation `payment_status = Paid`.
2. Nếu Customer chưa tồn tại: tạo Customer từ Opportunity/Contact/Company snapshot.
3. Nếu Customer đã tồn tại: reuse, không tạo trùng.
4. Gắn `quotation.customer_id`.
5. Qualification -> Converted.
6. Opportunity -> Won bằng payment service.
7. Company lifecycle -> Customer.
8. Cộng doanh thu đúng một lần theo Quotation.
9. Tạo Customer Assignment cho CSKH:
   - ưu tiên Company Account Owner nếu vẫn là CSKH hợp lệ;
   - nếu không, chọn CSKH eligible có tải thấp nhất;
   - **không fallback sang Sales owner**;
   - nếu không có CSKH eligible, ghi audit `customer.assignment_pending` để quản lý xử lý.
10. Tạo interaction hệ thống “Chuyển thành khách hàng từ thanh toán”.

Idempotency: nếu Payment callback/request chạy lại, cùng một Quotation không được tạo Customer hoặc cộng doanh thu lần hai.

## 13. Chăm sóc khách hàng

- Sau Paid, CSKH thao tác trên Customer/Customer Care.
- `interaction_at` do backend dùng `now()`, không bắt người dùng nhập thời điểm thao tác hiện tại.
- Sales activity vẫn ở Opportunity; CSKH activity sau conversion ở Customer Care, tránh trộn timeline.

## 14. UTM / Attribution

### Giữ

- `utm_source`
- `utm_medium`
- `utm_campaign`
- `utm_content`
- `utm_term`
- `referrer`

Flow:

```text
Generated UTM URL
-> Landing Page GET
-> action của Form giữ lại UTM query
-> LandingPageSubmission snapshot UTM/referrer
-> Lead.metadata.attribution
-> Customer.metadata.attribution + acquisition_source sau Paid
```

### Bỏ

- Tạo URL UTM bằng hai UI khác nhau. UTM URL được tạo từ action chính của Landing Page; relation manager chỉ là lịch sử/copy/delete.

## 15. Landing Page / Form Template

### Chốt

- Form Template tái sử dụng, không hard-code Hosting/VPS.
- Landing Page định nghĩa context Service; field `lead.service_interest` nhận package theo context đó.
- Landing Page có thể chỉ có Form Personal **hoặc** chỉ có Form Business; không bắt buộc cả hai.
- Publish cần ít nhất một form đang active.
- Nếu form có `lead.service_interest` thì Landing Page phải chọn Service chính.
- Submission là immutable source; Lead snapshot answer/context.

### Bỏ khỏi luồng v2

- Auto Tag Name.
- Auto Contact List.
- Auto Segment tự sinh khi submit.

Các field/method legacy có thể vẫn tồn tại để đọc dữ liệu cũ nhưng không còn được UI v2 gọi. Lý do: module hiện tại không đảm bảo các entity được tạo tự động thật sự được attach đúng vào Contact/Customer, dễ sinh master data rác.

Nếu sau này cần automation, nên làm rule engine có Trigger/Condition/Action + log execution riêng.

## 16. Service / Service Package / Price Book

- `ServicePackage.billing_period` + `billing_period_unit`: chu kỳ thanh toán (ví dụ 1 năm).
- `ServicePackage.unit`: đơn vị quantity (gói, tháng, lần, tài khoản, GB...), **không phải VND/USD**.
- Currency thuộc Price Book/Quotation.
- `service_category_id` raw numeric bị loại khỏi form Service cho đến khi hệ thống có ServiceCategory master thực sự.

## 17. Hạng mục chủ động DEFER sau Round 2

Những mục sau không phải BA blocker của Golden Path hiện tại:

1. Dynamic RBAC hoàn chỉnh (Role/Permission tự tạo).
2. Partial payment/installment/refund ledger hoàn chỉnh.
3. Payment gateway/webhook tự động (ví dụ payOS) — kiến trúc hiện tại đã tách Finance verification để có thể thay bằng webhook verified ở phase sau.
4. Chữ ký số/chứng thư số pháp lý.
5. Marketing Automation rule engine thay auto Tag/List/Segment cũ.
6. Service Category master.
7. UI/UX redesign, dịch nhãn còn thiếu, layout, màu, summary cards.

## 18. Invariants phải giữ khi sửa UI/UX sau này

- Không bypass maker–checker quotation.
- Không cho Sales sửa price/VAT snapshot ngoài Price Book.
- Không cho PDF thay đổi sau Send.
- Không coi Accepted là Customer.
- Không coi QR/payment notice là Paid.
- Paid chỉ do Finance hoặc future verified payment webhook.
- Không để Sales nhận Customer Care owner fallback.
- Không mất attribution UTM từ Lead sang Customer.
- Không cho public action xác nhận mà thiếu OTP của authorized signer.

## 19. Hardening kỹ thuật bắt buộc đi kèm nghiệp vụ

Các kiểm soát trên UI không được xem là ranh giới bảo mật. Ở backend, Quotation Creation bắt buộc lấy `unit_price`, `vat_rate`, `unit`, `discount_type`, service/package snapshot từ `PriceBookItem -> ServicePackage`; payload gửi tay từ browser không được phép override các giá trị này. `PriceBookItem` phải thuộc đúng Price Book, quantity phải nằm trong min/max và mọi item của Opportunity v2 phải có `package_code` đúng `Opportunity.service_interest`.

Loại discount do người quản lý cấu hình tại Price Book. Sales chỉ nhập giá trị discount trong đúng loại đó; không được đổi Percentage thành Fixed để lách giới hạn. Validation vượt discount phải trả lỗi form (`ValidationException`), tuyệt đối không trả trang 500.

Popup Gửi phải luôn có nội dung mặc định: ưu tiên template quotation active hợp lệ; nếu không có template thì render fallback body hệ thống. Không được gửi một email body rỗng chỉ vì Sales không thay đổi dropdown template. Lỗi SMTP/OTP phải được chuyển thành validation có thể xử lý, không làm public page vỡ 500.

## 20. Quy tắc cho báo giá cũ đã Approved trước Round 2

Một số báo giá đã Approved trước migration có thể chưa có `bank_account_id/payment_snapshot`. Không recreate chứng từ và không sửa tay database. Finance/Admin chọn một Bank Account Active làm Default, sau đó chạy:

```bash
php artisan sales:backfill-quotation-payment-snapshots
```

Hoặc chỉ một báo giá:

```bash
php artisan sales:backfill-quotation-payment-snapshots --quotation=QT_CODE
```

Command chỉ backfill báo giá chưa gửi và không tự chọn một tài khoản bất kỳ nếu chưa có tài khoản Default.
