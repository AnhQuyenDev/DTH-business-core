# DTH Business Core - Reset dữ liệu UI test và sử dụng bộ dữ liệu chuẩn

## 1. Phạm vi

Bộ công cụ này xóa dữ liệu nghiệp vụ phát sinh từ các Lead test nhưng giữ nguyên:

- Email Campaign và Ads Campaign.
- Email Template, Form Template và các trường Form.
- Landing Page và liên kết Form.
- User, Staff, Department, Position.
- Sending Account, Sending Domain.
- Service, Service Package, Price Book, Bank Account và cấu hình hệ thống.

Command xóa các dữ liệu liên quan nếu chúng tồn tại:

- Landing Page Submission.
- Contact cá nhân/doanh nghiệp và Custom Field Value.
- Company test, Company Contact, Company Assignment và Match Candidate.
- Lead, Qualification, Qualification Note và Lead Activity.
- Opportunity và các Contact/Interaction của Opportunity.
- Quotation, Item, Approval, Confirmation, Email Log và Document.
- Customer, Assignment, Distribution Item và Interaction.
- Recipient/Event/Tracked Link/Suppression phát sinh từ Contact test.
- Audit log của các thực thể test.

Command từ chối chạy khi `APP_ENV=production`.

## 2. Cài đặt command

Đứng tại thư mục `marketing-email-laravel-v12`:

```bash
git status
unzip -o DTH-ui-test-reset-overlay.zip -d .
php artisan optimize:clear
php artisan list | grep reset-ui-test-data
```

Lệnh cuối phải hiển thị `crm:reset-ui-test-data`.

## 3. Xóa ba Lead cũ hiện tại

Ba email mặc định:

```text
lead.hosting.b2b.001@example.com
lead.hosting.personal.001@example.com
lead.hosting.b2b.002@example.com
```

MST mặc định:

```text
9999999999
```

Xem trước, chưa xóa:

```bash
php artisan crm:reset-ui-test-data --dry-run
```

Kiểm tra bảng thống kê phải nhận diện khoảng:

- 3 Submission.
- 3 Contact.
- 3 Lead.
- 1 Company.
- Opportunity, Quotation và Customer bằng 0 nếu chưa test tới các bước đó.

Xóa thật:

```bash
php artisan crm:reset-ui-test-data --force --reset-sequences
```

`--reset-sequences` chỉ reset mã Lead/Company/Opportunity khi bảng tương ứng đã trống hoàn toàn. Khi đó vòng test sạch có thể bắt đầu lại từ `LEAD-...000001` và `COM-...000001`.

Kiểm tra sau xóa:

```bash
php artisan crm:reset-ui-test-data --dry-run
```

Các số lượng nghiệp vụ phải về 0.

## 4. Xóa dữ liệu của những vòng test sau

Workbook sinh email alias theo `Mã đợt test`, ví dụ:

```text
UITEST-HOSTING-01
```

Xem trước toàn bộ dữ liệu của batch:

```bash
php artisan crm:reset-ui-test-data \
  --batch=UITEST-HOSTING-01 \
  --dry-run
```

Xóa thật batch đó:

```bash
php artisan crm:reset-ui-test-data \
  --batch=UITEST-HOSTING-01 \
  --force
```

Nếu đây là batch nghiệp vụ duy nhất và muốn mã chạy lại từ đầu:

```bash
php artisan crm:reset-ui-test-data \
  --batch=UITEST-HOSTING-01 \
  --force \
  --reset-sequences
```

Có thể xóa bằng email cụ thể:

```bash
php artisan crm:reset-ui-test-data \
  --email="email1@example.com" \
  --email="email2@example.com" \
  --tax-code="9999999999" \
  --dry-run
```

## 5. Sử dụng file dữ liệu test

Mở `DTH_UI_TEST_DATA_MASTER.xlsx`.

Tại sheet `00_HUONG_DAN`, chỉ sửa các ô màu xanh:

1. **Mã đợt test**: giữ `UITEST-HOSTING-01` cho vòng đầu; đổi thành `UITEST-HOSTING-02` ở vòng sau.
2. **Email nhận thư thực tế**: thay `your.email@gmail.com` bằng hộp thư bạn kiểm soát.
3. **Ngày bắt đầu đợt test**: mặc định là ngày mở file.
4. Giữ MST `9999999999` và tên Company test để kiểm tra chống trùng.

Các sheet:

- `01_CAU_HINH_FORM`: cấu hình field, mapping, validation và options chuẩn.
- `02_DU_LIEU_FORM`: bộ A-F để nhập Landing Page.
- `03_PHAN_PHOI`: kiểm thử tự động phân phối và Account Owner.
- `04_QUALIFICATION`: dữ liệu gọi điện, chấm điểm và đủ/không đủ điều kiện.
- `05_OPPORTUNITY_QUOTE`: Opportunity, dòng báo giá, tổng tiền, thanh toán và chuyển Customer.
- `06_DOI_SOAT`: số lượng và dữ liệu phải khớp giữa các module.
- `07_RESET`: lệnh reset nhanh.

## 6. Golden Path cố định

Dùng **Bộ A** xuyên suốt:

```text
Landing Page
→ Submission
→ Contact doanh nghiệp
→ Company Minh Phát
→ Lead A
→ Tự động phân phối
→ Qualification đủ điều kiện
→ Opportunity
→ Quotation 3.850.000 VND
→ Khách chấp nhận
→ Payment Paid
→ Opportunity Won
→ Customer
```

Không đổi người liên hệ, Company, dịch vụ hoặc số tiền giữa chừng. Điều này giúp đối soát và backfill về sau có cùng một chuẩn.

## 7. Backfill khi cần

Xem trước:

```bash
php artisan crm:backfill-lead-intake --dry-run
```

Chạy thật:

```bash
php artisan crm:backfill-lead-intake
```

Sau backfill, đối chiếu lại bằng sheet `06_DOI_SOAT`. Lead cũ thiếu dịch vụ phải ở trạng thái cần kiểm tra và không được tự động phân phối.

## 8. Lưu ý email

Workbook dùng plus-alias, ví dụ:

```text
your.email+dth.uitest.hosting.01.b2b001@gmail.com
```

Gmail hỗ trợ kiểu alias này. Nếu nhà cung cấp email của bạn không hỗ trợ dấu `+`, hãy nhập email riêng trực tiếp tại sheet `02_DU_LIEU_FORM`.
