# DTH — BA Round 2 Test Checklist

## A. Chuẩn bị hệ thống

1. Chạy migration.
2. Chạy `php artisan sales:sync-vn-banks`.
3. Admin cấu hình Company Settings / VietQR credential nếu có.
4. Finance/Admin tạo ít nhất một Bank Account Active và đánh dấu Default.
5. Nếu có Quotation cũ chưa gửi nhưng thiếu bank snapshot, chạy:
   `php artisan sales:backfill-quotation-payment-snapshots`
6. Phòng Kinh doanh phải có Sending Account SMTP Active.
7. Queue worker phải chạy queue `quotations`.

## B. Catalog / Price Book

- [ ] Service Package `unit` là `gói`/`tháng`/`lần`..., không phải VND.
- [ ] Billing cycle và quantity unit không bị lẫn.
- [ ] Sales Staff không quản lý Price Book.
- [ ] Sales Manager quản lý Price Book.
- [ ] Quotation chỉ load package đúng `Opportunity.service_interest`.
- [ ] Unit Price/VAT/Service/Package readonly trên Quotation.
- [ ] Discount 5% với max 10%: PASS.
- [ ] Discount 10% với max 10%: PASS.
- [ ] Discount 20% với max 10%: validation message, không 500.

## C. Maker–checker

- [ ] Opportunity owner là Sales Staff.
- [ ] Sales Staff tạo Draft.
- [ ] Sales Manager không tạo Draft cho deal thay Staff.
- [ ] Draft không thể Approved trực tiếp.
- [ ] Submit -> Pending Approval.
- [ ] Người lập không tự approve.
- [ ] Sales Manager approve -> Approved.
- [ ] Bank Account + party email bắt buộc trước Submit Approval.

## D. Send Email

- [ ] Popup Gửi hiển thị Contact + Company.
- [ ] Recipient email readonly và đúng snapshot.
- [ ] Authorized Signer chỉ lấy Contact thuộc Opportunity.
- [ ] Dropdown template không có `meta`/template rỗng.
- [ ] Có Subject.
- [ ] Không cho sửa raw HTML; có preview.
- [ ] Thiếu Sales SMTP -> validation ngay, không báo queued giả.
- [ ] Send thành công -> Email Log Sent + Quotation Sent.
- [ ] Send thất bại -> Quotation vẫn Approved, Email Log Failed.
- [ ] Không auto retry email thương mại.
- [ ] PDF chính thức được tạo trước Send.
- [ ] Sau Send không còn action regenerate PDF.

## E. Public Link / OTP

- [ ] Public Link không mở được trước Send.
- [ ] Mở public link -> Sent thành Viewed, view_count tăng.
- [ ] PDF public là file snapshot đã gửi.
- [ ] Email khác Authorized Signer không gửi OTP được.
- [ ] OTP đúng 6 số, 5 phút.
- [ ] Gửi OTP quá nhanh bị throttle.
- [ ] Sai OTP quá số lần bị throttle.
- [ ] Accept bắt buộc OTP.
- [ ] Reject bắt buộc OTP + lý do.
- [ ] Request Revision bắt buộc OTP + lý do.
- [ ] Confirmation lưu OTP verified email/time + IP/user agent.

## F. Revision

- [ ] Request Revision -> RevisionRequested.
- [ ] Sales owner thấy “Tạo phiên bản chỉnh sửa”.
- [ ] Tạo revision -> bản cũ Superseded, bản mới Draft version +1.
- [ ] Bản mới phải duyệt lại và gửi lại.

## G. VietQR / Payment

- [ ] Bank list sync thành công hoặc fallback master có dữ liệu.
- [ ] Lookup account name hoạt động khi có credential.
- [ ] Không có credential lookup -> thông báo cấu hình, vẫn có thể nhập account name thủ công.
- [ ] QR có amount = grand_total và transfer content = quotation code.
- [ ] Accepted mới hiện bước thanh toán.
- [ ] Khách thông báo chuyển khoản chỉ được khai đúng full grand total.
- [ ] Payment Notice -> Pending Verification.
- [ ] Finance thấy record ở Theo dõi Thanh toán.
- [ ] Sales/Admin không có action Mark Paid.
- [ ] Finance reject -> Unpaid + notice Rejected + lý do.
- [ ] Finance verify -> Paid.

## H. Conversion / Customer Care

Sau Finance Paid:

- [ ] Customer được create/reuse đúng một bản ghi.
- [ ] Quotation.customer_id được gắn.
- [ ] Opportunity -> Won.
- [ ] Qualification -> Converted.
- [ ] Company lifecycle -> Customer.
- [ ] Revenue cộng đúng một lần.
- [ ] Chạy verify Paid lần 2 không cộng lại.
- [ ] Customer owner là CSKH eligible, ưu tiên Company Account Owner.
- [ ] Không fallback Customer owner sang Sales.
- [ ] Nếu không có CSKH eligible có audit pending assignment.
- [ ] Customer Care interaction sử dụng thời gian backend `now()`.

## I. UTM / Form / Landing Page

- [ ] UTM generated link có source/medium/campaign/content/term.
- [ ] Submit form vẫn giữ UTM query.
- [ ] Submission lưu UTM + referrer.
- [ ] Lead.metadata.attribution có đủ UTM.
- [ ] Sau Paid, Customer metadata giữ attribution.
- [ ] Form Template không còn auto Tag/List/Segment UI.
- [ ] Landing Page không còn auto Tag/List/Segment UI.
- [ ] Landing Page 1 active form vẫn publish được.
- [ ] Có `lead.service_interest` nhưng thiếu Service -> chặn publish.
- [ ] Form Template dùng lại được cho Service khác.

## J. Backend integrity / chống bypass UI

- [ ] Crafted request đổi Unit Price không làm đổi giá Price Book snapshot.
- [ ] Crafted request đổi VAT không làm đổi VAT Price Book.
- [ ] Crafted request đổi `unit` thành `VND` không làm đổi quantity unit từ Service Package.
- [ ] Crafted request đổi Discount Type không được phép; type lấy từ Price Book.
- [ ] PriceBookItem thuộc Price Book khác -> validation.
- [ ] Quantity vượt min/max -> validation.
- [ ] Opportunity PPH02 cố inject PPH03 -> validation.
- [ ] Popup Send không chọn template vẫn có subject/body fallback hợp lệ.
- [ ] Lỗi gửi OTP qua SMTP hiển thị validation, không trả 500.
