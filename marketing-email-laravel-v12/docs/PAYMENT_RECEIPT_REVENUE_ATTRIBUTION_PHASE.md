# Payment, Receipt & Revenue Attribution Phase

## Mục tiêu nghiệp vụ

Phase này mở rộng Golden Path hiện tại từ `Quotation Accepted` trở đi mà không thay đổi các bước Sales đã PASS.

Luồng chính:

`Accepted -> khách chuyển khoản -> Payment Notice + chứng từ bắt buộc -> Pending Verification -> Finance đối soát -> Verified Payment -> Receipt PDF -> Paid -> Customer/Won -> Revenue Attribution`.

Ba khái niệm được tách riêng:

1. **Payment Notice / Evidence**: khách khai báo đã chuyển khoản và tải chứng từ. Chưa phải bằng chứng cuối cùng rằng công ty đã nhận tiền.
2. **Payment**: bản ghi tài chính có thẩm quyền, chỉ sinh khi Finance xác minh.
3. **Receipt**: biên lai/xác nhận thanh toán do hệ thống công ty phát hành sau khi Payment đã Verified; không thay thế hóa đơn điện tử/VAT.

## Quyết định BA đã chốt

- Public Payment Notice bắt buộc 1-3 chứng từ JPG/JPEG/PNG/WEBP/PDF, mặc định tối đa 10 MB/file.
- Evidence và Receipt lưu trên private storage; không dùng public URL trực tiếp.
- Finance là role duy nhất xác minh `Paid`.
- Admin/Executive/Viewer được xem/audit Customer Care nhưng không gửi email, log call/message hoặc quản lý assignment.
- V1 chỉ hỗ trợ thanh toán đủ 100%; chưa hỗ trợ partial/installment/refund UI.
- `payments` là source of truth tài chính. `quotations.payment_status` là trạng thái tổng hợp phục vụ workflow/UI.
- Receipt là immutable snapshot. Nếu file PDF bị mất, hệ thống tái tạo từ snapshot đã lưu, không lấy Company/Customer master data mới.
- Revenue Attribution V1 = **Lead Origin Attribution**, weight 100% cho nguồn đã capture khi Lead được tạo. Chưa phải multi-touch.
- Tách rõ:
  - `gross collected`: tiền khách thực chuyển, có VAT.
  - `net revenue`: doanh thu trước VAT.
  - `tax`: VAT.
- Product revenue lấy từ `quotation_items` và được snapshot thành `payment_revenue_lines`; do đó một Payment nhiều dịch vụ/gói không bị dồn toàn bộ doanh thu vào một sản phẩm.

## Dữ liệu mới

Migration: `2026_08_09_140000_create_payment_receipt_revenue_attribution_tables.php`

### quotation_payment_notice_files

Chứng từ khách upload, bao gồm private path, MIME, size, SHA-256.

### payments

Một bản ghi Payment authoritative cho mỗi Quotation trong scope V1 full-payment.

Các trường chính: payment_code, quotation/customer/opportunity/payment_notice/bank/sales, gross/net/tax, currency, transfer_reference, paid_at, verified_at, verified_by.

### payment_revenue_lines

Snapshot từng Quotation Item: Service, Service Package, quantity, net, VAT, gross.

### payment_attributions

Snapshot nguồn tạo Lead: Marketing Campaign, Email Campaign, Landing Page, acquisition source, UTM source/medium/campaign/content/term, referrer.

### payment_receipts

Receipt code, private PDF, SHA-256 và immutable JSON snapshot.

## UI mới / thay đổi

### Public Quotation

- Payment Notice bắt buộc chứng từ.
- Sau Paid có link tải receipt bằng public token của Quotation.

### Finance -> Theo Dõi Thanh Toán

- Hiển thị số lượng chứng từ.
- Action `Xem chứng từ` có preview ảnh và link file gốc.
- Chỉ Finance có `Đã thanh toán` / `Đối soát không khớp`.
- Với Business Flow V2, không thể Paid nếu Pending Notice không có evidence.

### Tài chính -> Lịch sử thanh toán

Read-only ledger: Payment code, ngày, customer, quotation, gross/net/VAT, nguồn, campaign, Sales, Finance verifier, receipt.

### Tài chính -> Báo cáo doanh thu

Bộ lọc: ngày Paid, Marketing Campaign, Email Campaign, Landing Page, UTM source/medium/campaign/content, Service, Package, Sales.

Báo cáo:

- Thực thu / Net Revenue / VAT.
- Giao dịch Paid / Paid Customers / Average Payment.
- Marketing Campaign: Budget, Leads, Paid Customer, conversion, Net Revenue, CAC, ROAS.
- UTM Source.
- UTM Campaign + Content.
- Landing Page.
- Service / Package.
- Sales.
- Customer.
- Email Campaign.

Lưu ý: Campaign Budget hiện là budget tổng lưu trên Campaign; nếu lọc một phần thời gian của campaign, ROAS là chỉ báo tham khảo cho tới khi có expense ledger theo ngày.

### Customer / Quotation

Có relation `Lịch sử thanh toán` / `Thanh toán`, bao gồm receipt.

### Customer Care

Admin/read-across chỉ xem/audit. CSKH Manager hoặc CSKH Staff hợp lệ mới được thao tác theo policy.

## Email sau Paid

Sau Finance xác minh:

- Receipt PDF được sinh ngay.
- Email xác nhận thanh toán được gửi ngay bằng Sending Account của Quotation, không yêu cầu người dùng bật queue worker cho notification này.
- Receipt PDF được attach vào email.
- Nếu PDF/email lỗi, Payment vẫn giữ Paid vì xác minh tài chính là authoritative; lỗi được report để sửa/retry/backfill.

## Backfill dữ liệu Paid cũ

Sau migrate chạy:

```bash
php artisan finance:backfill-payments
```

Command chỉ tạo ledger/attribution/receipt cho Quotation đã Paid trước phase này. Nó **không** chạy lại conversion và **không** cộng `Customer.total_revenue` lần nữa.

Quotation Paid lịch sử có thể không có evidence vì được tạo trước khi evidence trở thành bắt buộc. Từ phase này trở đi, Business Flow V2 yêu cầu evidence trước Paid.

## Cách deploy

Backup DB và code trước khi áp dụng.

```bash
php artisan migrate --force
php artisan finance:backfill-payments
php artisan optimize:clear
```

Không cần migration seed mới. Không thay `.env` hiện tại. Có thể cấu hình tùy chọn:

```env
PAYMENT_EVIDENCE_DISK=local
PAYMENT_EVIDENCE_MAX_FILES=3
PAYMENT_EVIDENCE_MAX_KB=10240
PAYMENT_RECEIPT_DISK=local
```

`local` của Laravel 12 là private local storage; không cần `storage:link` cho evidence/receipt.

## UAT đề xuất

1. Tạo Quotation mới -> Accepted.
2. Public Payment Notice không chọn file -> phải bị chặn.
3. Upload ảnh/PDF -> Pending Verification.
4. Finance xem được preview evidence; Admin không có action Paid.
5. Finance Reject -> notice Rejected, Quotation Unpaid; khách gửi lại được.
6. Gửi evidence lần hai -> Finance Paid.
7. Kiểm tra đúng 1 `payments`, đúng số `payment_revenue_lines`, đúng 1 attribution.
8. Kiểm tra receipt PDF tải được và email có attachment.
9. Customer/Quotation hiển thị lịch sử thanh toán.
10. Revenue Report phản ánh Gross/Net/VAT và đúng Service/Package/Source/Campaign.
11. Filter Service trên Payment nhiều items chỉ tính tiền line của Service đó, không tính toàn Payment.
12. Refresh/retry không tạo Payment/Receipt/Customer/revenue duplicate.
13. Admin mở Customer Care -> xem được nhưng không create/edit interaction/assignment, không gửi email/log call.

## Ngoài scope

- Partial payment / installments.
- Refund transaction ledger/UI.
- VAT e-Invoice.
- Expense / COGS / Profit & Loss.
- Multi-touch attribution.
- Tự động đối soát ngân hàng/payment gateway.
