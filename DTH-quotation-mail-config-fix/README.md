# DTH Quotation Mail Configuration Fix

Bộ sửa này xử lý luồng Sending Account -> Báo giá -> Email -> OTP.

## Những lỗi được sửa

1. Báo giá không còn phụ thuộc SMTP mặc định trong `.env` khi đã cấu hình Sending Account theo phòng ban.
2. Nút "Gửi test" của Sending Account giờ test đúng SMTP credentials của chính account đó.
3. Báo giá tự resolve Sending Account đang active của phòng ban Sales phụ trách Opportunity/Quotation.
4. Email log snapshot lại Sending Account, From name và From email.
5. Mặc định gửi quotation **đồng bộ** để local/dev không cần queue worker. Có thể bật queue cho production bằng env.
6. Nếu send lỗi, email log vẫn tồn tại với trạng thái Failed và error_message.
7. Sau khi gửi thành công: Quotation Approved -> Sent, email_status -> Sent, sent_at được ghi.
8. OTP public quotation cũng gửi bằng cùng Sending Account Sales, không dùng SMTP mặc định.
9. Sửa retry bug: job cũ chuyển status Failed rồi lần retry sau tự skip.

## Áp dụng

Từ thư mục chứa `marketing-email-laravel-v12`:

```bash
bash DTH-quotation-mail-config-fix/apply.sh marketing-email-laravel-v12
cd marketing-email-laravel-v12
php artisan migrate
php artisan optimize:clear
```

Trong `.env` local/dev nên để:

```env
QUOTATION_EMAIL_QUEUE_ENABLED=false
QUOTATION_EMAIL_QUEUE=quotations
```

Khi deploy production và đã có queue worker ổn định:

```env
QUOTATION_EMAIL_QUEUE_ENABLED=true
QUOTATION_EMAIL_QUEUE=quotations
```

Worker production:

```bash
php artisan queue:work --queue=quotations,default --tries=3 --timeout=120
```

## SMTP config_encrypted

Nếu Sending Account có `provider=smtp`, KeyValue nên có tối thiểu:

```text
host       smtp.gmail.com
port       587
encryption tls
username   sales@example.com
password   <app-password-or-smtp-password>
```

Với SMTPS cổng 465 có thể dùng:

```text
port       465
encryption ssl
```

## Kiểm tra trước khi gửi báo giá

1. Sending Account `status=active`.
2. `department_id` phải là phòng Kinh doanh.
3. Quotation phải có `assigned_staff_id` thuộc phòng Kinh doanh.
4. Quotation phải có Bank Account/payment snapshot.
5. Báo giá phải ở `Approved`.

Sau khi apply, hãy chạy lại **Gửi test** của Sending Account. Nếu test này fail, lỗi SMTP/account sẽ được lộ đúng thay vì âm thầm dùng `.env`.

## ViewQuotation local đã tùy biến

Nếu `ViewQuotation.php` local của bạn có popup preview / người được phép xác nhận, không ghi đè toàn file. Áp đoạn trong `VIEW_QUOTATION_SEND_ACTION.md` để action gọi service và hiện lỗi đúng trên UI.
