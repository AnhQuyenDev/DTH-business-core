# DTH - Campaign / Landing Page Service Scope

## 1. Mục tiêu nghiệp vụ

Thiết kế sau thay đổi phải trả lời rõ bốn câu hỏi:

1. **Campaign đang quảng bá dịch vụ gì?**  
   `MarketingCampaign <-> Services` (nhiều-nhiều).
2. **Landing Page cụ thể đang giới thiệu dịch vụ gì?**  
   `LandingPage -> Service` (một dịch vụ chính).
3. **Landing Page cho khách chọn những gói nào?**  
   `LandingPage <-> ServicePackages`.
4. **Form Template cần hỏi khách những gì?**  
   Form không chứa cứng danh mục Hosting/VPS. Trường `lead.service_interest`
   lấy options từ Landing Page lúc render.

Luồng chuẩn:

```text
Service Catalog
      |
      +--> Ads Campaign: phạm vi dịch vụ quảng bá (1..n)
                  |
                  +--> Landing Page: 1 dịch vụ cụ thể thuộc scope Campaign
                              |
                              +--> 0..n gói dịch vụ được phép chọn
                                          |
                                          +--> Form Template dùng chung
                                                      |
                                                      +--> Submission
                                                                  |
                                                                  +--> Lead snapshot
```

## 2. Quy tắc dữ liệu bắt buộc

- Campaign phải có ít nhất **một dịch vụ quảng bá đang hoạt động**.
- Campaign có thể quảng bá nhiều dịch vụ, ví dụ `Web Hosting + VPS`.
- Landing Page thuộc Ads Campaign phải có `service_id`.
- `LandingPage.service_id` bắt buộc thuộc `MarketingCampaign.services`.
- Gói trên Landing Page bắt buộc thuộc `LandingPage.service_id`.
- Landing Page đang thuộc Campaign khác không được giành sang Campaign mới âm thầm.
- Khi thu hẹp service scope của Campaign, Landing Page không tương thích phải được
  người dùng chủ động gỡ/chuyển trước; UI không âm thầm làm mất liên kết.
- Form Template vẫn tái sử dụng được cho nhiều Landing Page/dịch vụ.
- Submission và Lead tiếp tục lấy service/package từ Landing Page, không lấy từ
  danh sách option hard-code trong Form Template.

## 3. Migration

Migration mới:

```text
database/migrations/2026_08_07_170000_add_service_scope_to_marketing_campaigns.php
```

Tạo pivot:

```text
marketing_campaign_service
- marketing_campaign_id
- service_id
```

Migration có backfill an toàn: với Landing Page hiện đã có cả
`marketing_campaign_id` và `service_id`, dịch vụ đó tự được thêm vào scope của
Campaign. Campaign cũ không có dữ liệu đủ để suy luận sẽ để trống và cần cấu hình
thủ công trên UI.

## 4. UI Campaign

Vào:

```text
Marketing -> Chiến dịch quảng cáo
```

Form có trường mới:

```text
Dịch vụ quảng bá
```

Có thể chọn nhiều dịch vụ.

Ví dụ Campaign Hosting:

```text
Chiến dịch Hosting Siêu Tốc Q3/2026
Dịch vụ quảng bá:
[x] Web Hosting
```

Ví dụ Campaign hạ tầng tổng hợp:

```text
Hạ tầng Web 2026
Dịch vụ quảng bá:
[x] Web Hosting
[x] VPS
```

Danh sách Campaign có thêm cột `Dịch vụ quảng bá`.

### Landing Page trong form Campaign

Danh sách Landing Page chỉ đề xuất:

- trang chưa thuộc Campaign khác;
- có Dịch vụ chính nằm trong service scope đang chọn.

Khi sửa Campaign, các Landing Page hiện thuộc Campaign vẫn được giữ trong
options để tránh việc Filament tự làm mất state. Nếu người dùng bỏ một service
nhưng vẫn giữ Landing Page dùng service đó, save bị chặn và hiển thị lỗi.

## 5. UI Landing Page

Vào:

```text
Marketing -> Trang đích
```

Khi chọn Ads Campaign, tên Campaign hiển thị kèm service scope, ví dụ:

```text
Chiến dịch Hosting Siêu Tốc Q3/2026 — Web Hosting
Hạ tầng Web 2026 — Web Hosting, VPS
Campaign cũ — Chưa cấu hình dịch vụ
```

Sau khi chọn Campaign:

- `Dịch vụ chính` chỉ hiển thị service nằm trong Campaign.
- đổi Campaign sẽ reset `Dịch vụ chính` và `Các gói được phép chọn` để tránh
  giữ dữ liệu sai ngữ cảnh.
- chọn `Dịch vụ chính` sẽ reset các package cũ.
- `Các gói được phép chọn` chỉ lấy package active thuộc service đã chọn.

Ví dụ:

```text
Campaign: Chiến dịch Hosting Siêu Tốc Q3/2026 — Web Hosting
Dịch vụ chính: Web Hosting
Gói:
- Bình thường
- Trung bình
- Cao cấp
```

Một cấu hình sau bị chặn:

```text
Campaign: Hosting Siêu Tốc -> Web Hosting
Landing Page: Giới thiệu VPS -> VPS
```

## 6. Form Template

Không tạo Form riêng cho Hosting/VPS chỉ vì options dịch vụ khác nhau.

Cấu hình trường:

```text
Nhãn: Dịch vụ quan tâm
Key: service_interest
Loại: Chọn
Bắt buộc: Có
Nơi lưu dữ liệu: Lead -> Dịch vụ quan tâm
```

Không nhập cứng:

```text
Hosting Basic
Hosting Pro
Hosting VIP
```

vào Form Template nữa.

Landing Page Hosting tự render gói Hosting; Landing Page VPS dùng cùng Form nhưng
render gói VPS.

## 7. Trình tự áp dụng code

Từ repository:

```bash
git switch feature/crm-company-lead-opportunity-flow
git pull --ff-only origin feature/crm-company-lead-opportunity-flow
git status
```

Nên commit checkpoint nếu local đang có thay đổi hợp lệ:

```bash
git add .
git commit -m "checkpoint: lead intake and landing page service context"
```

Vào application:

```bash
cd marketing-email-laravel-v12
```

Giải nén overlay vào đây:

```bash
unzip -o DTH-campaign-service-scope-overlay.zip -d .
```

Kiểm tra:

```bash
git status --short
php artisan optimize:clear
php artisan migrate
```

Migration status:

```bash
php artisan migrate:status | grep -E "160000|170000"
```

Phải thấy cả service context Landing Page và campaign service scope ở trạng thái
`Ran`.

## 8. Cấu hình dữ liệu hiện có sau migration

Thứ tự nên làm:

### 8.1 Service Catalog

Đảm bảo có:

```text
Web Hosting (active)
```

và các package active.

### 8.2 Campaign Hosting

Mở:

```text
Chiến dịch Hosting Siêu Tốc Q3/2026
```

Kiểm tra `Dịch vụ quảng bá`.

Nếu migration suy luận được từ Landing Page hiện tại, `Web Hosting` đã được chọn.
Nếu chưa có, chọn thủ công `Web Hosting` rồi Save.

### 8.3 Landing Page Hosting

Mở:

```text
Giới thiệu dịch vụ Hosting
```

Chọn/kiểm tra:

```text
Chiến dịch quảng cáo: Chiến dịch Hosting Siêu Tốc Q3/2026 — Web Hosting
Dịch vụ chính: Web Hosting
Các gói: Basic / Pro / VIP
```

Lưu lại.

### 8.4 Form Template

Cả Form cá nhân và doanh nghiệp tiếp tục dùng mapping:

```text
lead.service_interest
```

Không cần clone Form khi tạo Landing Page VPS.

## 9. Automated test

Chạy test mới trước:

```bash
php artisan test --filter=MarketingCampaignServiceScopeTest
```

Sau đó regression:

```bash
php artisan test --filter=LandingPageServiceContextTest
php artisan test --filter=LeadIntakeHardeningTest
php artisan test --filter=SubmissionCreatesLeadTest
```

Nếu tất cả đạt:

```bash
php artisan test
```

Test mới bao phủ:

- Campaign có thể quảng bá nhiều service.
- LP chỉ được dùng service thuộc Campaign.
- LP ngoài scope bị từ chối.
- Campaign bắt buộc có service active.
- danh sách LP không lấy LP của Campaign khác.
- khi sửa Campaign, LP hiện hữu không bị mất state âm thầm.

## 10. Chuẩn bị chạy lại UI Test

Sau khi cấu hình Campaign + Landing Page đúng, reset dữ liệu giao dịch:

```bash
php artisan crm:reset-ui-test-data --dry-run
php artisan crm:reset-ui-test-data --force --reset-sequences
```

Sau đó submit lại Bộ A.

Lead List kỳ vọng:

```text
Nguồn: Landing Page
Dịch vụ quan tâm: Web Hosting - Trung bình
Đủ dữ liệu: Có
```

Trước khi test Tự động phân phối Lead, cần xác nhận ba điểm:

1. Campaign list hiển thị `Dịch vụ quảng bá = Web Hosting`.
2. Landing Page Hosting hiển thị `Dịch vụ chính = Web Hosting`.
3. Lead mới hiển thị `Web Hosting - Trung bình`, không phải chỉ `Trung Bình` và
   không phải code kỹ thuật.

## 11. Bảo vệ runtime và snapshot Ads Campaign

Ngoài validation trên Filament, submission public còn kiểm tra lại quan hệ:

```text
LandingPage.marketing_campaign_id
LandingPage.service_id
MarketingCampaign.services
```

Nếu dữ liệu bị sửa trực tiếp trong database hoặc Landing Page cũ còn cấu hình sai,
backend từ chối tạo Submission trước khi Contact/Lead được sinh ra.

Migration bổ sung:

```text
database/migrations/2026_08_07_180000_add_marketing_campaign_context_to_landing_page_submissions.php
```

Thêm:

```text
landing_page_submissions.marketing_campaign_id
```

Mục đích là snapshot Ads Campaign tại thời điểm khách gửi form. Nhờ đó việc đổi
Campaign của Landing Page sau này không làm sai attribution lịch sử.

Lead metadata cũng snapshot:

```text
landing_page_id
landing_page_name
campaign_id                  # Email Campaign attribution cũ
marketing_campaign_id        # Ads Campaign
marketing_campaign_name
service_context
```

Khi cần backfill Lead cũ, command `crm:backfill-lead-intake` cũng bổ sung lại
Landing Page và Ads Campaign vào metadata nếu còn suy luận được từ Submission.

## 12. Migration status cuối cùng

Sau khi áp dụng overlay, chạy:

```bash
php artisan migrate:status | grep -E "160000|170000|180000"
```

Cần thấy cả ba migration ở trạng thái `Ran`:

```text
160000 - Landing Page -> Service/Packages
170000 - Ads Campaign <-> Services
180000 - Submission snapshot Ads Campaign
```
