# DTH Business Core — BA Round 2 Final Fix

Nguồn patch: ZIP local mới nhất `feature/crm-company-lead-opportunity-flow` được cung cấp ngày 08/08/2026.

## Phạm vi

Đợt này khóa nghiệp vụ gần cuối của CRM/Sales: Price Book → Quotation maker-checker → gửi email/PDF → public link → OTP xác nhận điện tử → revision → VietQR/payment notice → Finance đối soát → Paid → Customer/Opportunity Won → Customer Care. Đồng thời khóa lại attribution UTM và Landing Page/Form v2.

Các quyết định BA chi tiết nằm trong:

- `docs/BA_ROUND2_FINAL_BUSINESS_FLOW.md`
- `docs/BA_ROUND2_TEST_CHECKLIST.md`
- `docs/BA_ROUND2_VIETQR_PAYMENT_DECISION.md`

## Áp dụng nhanh

Từ thư mục chứa `marketing-email-laravel-v12`:

```bash
git status
git add -A
git commit -m "checkpoint before BA round2 final fix"

bash DTH-round2-ba-final-fix/apply.sh marketing-email-laravel-v12
cd marketing-email-laravel-v12

php artisan migrate
php artisan sales:sync-vn-banks
php artisan optimize:clear
php artisan queue:restart
```

### Cấu hình bắt buộc trước khi test Send/Payment

1. **Sales SMTP**: tạo Sending Account `smtp`, Active, gắn Department `Kinh doanh`.
2. **Bank Account**: Finance/Admin tạo ít nhất một tài khoản Active, nên chọn một tài khoản `Mặc định`.
3. **VietQR credential** (tùy chọn): Admin → Cài đặt công ty → Client ID/API Key. Không có credential vẫn có QuickLink QR fallback, nhưng Account Lookup/API Generate authenticated không hoạt động.

### Báo giá cũ đã Approved nhưng chưa có Bank Account

Sau khi đã chọn một Bank Account Active làm Default:

```bash
php artisan sales:backfill-quotation-payment-snapshots
```

Hoặc chỉ một mã:

```bash
php artisan sales:backfill-quotation-payment-snapshots --quotation=QT_CODE
```

Không recreate báo giá đã duyệt chỉ để bổ sung bank snapshot.

## Queue

Trong môi trường local/testing cần worker:

```bash
php artisan queue:work --queue=quotations,default
```

Email báo giá được cấu hình `tries=1` để tránh gửi trùng do retry SMTP không chắc chắn. Nếu job fail, sửa SMTP rồi Sales gửi lại thủ công.

## Test ưu tiên

```bash
php artisan test --filter=QuotationPriceBookIntegrityTest
php artisan test --filter=QuotationMailWithoutCustomerTest
php artisan test --filter=QuotationPublicWithoutCustomerTest
php artisan test --filter=QuotationPolicyTest
php artisan test --filter=PaymentVerificationAuthorizationTest
php artisan test --filter=QuotationStateMachineTest
php artisan test --filter=QrPaymentServiceTest
```

Sau đó:

```bash
php artisan test
```

## Golden Path UI sau khi apply

Sales Staff tạo Quotation từ Opportunity → chọn Price Book đúng package → Bank Account → Submit Approval. Sales Manager duyệt. Sales Staff Send: popup có người nhận + authorized signer + subject + preview HTML, không có raw HTML/meta. Job thành công mới thành Sent. Khách mở public link → Viewed → nhận OTP → Accept/Reject/Request Revision. Accepted hiển thị VietQR và form “Thông báo đã chuyển khoản”. Finance đối soát Pending Verification → Paid. Paid mới tạo/reuse Customer, Opportunity Won, Qualification Converted, Company Customer và gán CSKH.

## Không triển khai trong Round 2

- Partial payment/installment/refund ledger.
- Auto Paid chỉ vì QR được hiển thị/quét.
- payOS/payment webhook tự động.
- Chữ ký số/chứng thư số pháp lý.
- Dynamic RBAC đầy đủ.
- Marketing automation rule engine cho Tag/List/Segment.

Các phần trên là phase riêng; không phải BA blocker của Golden Path hiện tại.
