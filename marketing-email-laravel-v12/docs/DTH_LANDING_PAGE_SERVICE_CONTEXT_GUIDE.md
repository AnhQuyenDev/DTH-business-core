# DTH Business Core - Landing Page Service Context

## Mục tiêu

Giải quyết hai vấn đề trước khi tiếp tục test tự động phân phối Lead:

1. **Form Template phải tái sử dụng được**. Form cá nhân/doanh nghiệp chỉ mô tả cấu trúc dữ liệu cần thu thập, không hard-code Web Hosting/VPS/Email vào template.
2. **Nguồn Lead hiển thị thân thiện trên UI**. Database vẫn lưu mã kỹ thuật `landing_page`, nhưng Filament hiển thị `Landing Page`.

## Kiến trúc sau thay đổi

```text
Service / Service Package
        |
        v
Landing Page  <--- chọn Dịch vụ chính + các gói được phép
        |
        +---- Form Template cá nhân dùng chung
        +---- Form Template doanh nghiệp dùng chung
        |
        v
Public Form
Dịch vụ quan tâm = options động theo Landing Page
        |
        v
Submission (snapshot gốc)
        |
        v
Lead
- service_interest = package_code ổn định
- metadata.service_interest_label = nhãn tại thời điểm gửi
- metadata.service_context = snapshot Service/Package
- source = landing_page (mã kỹ thuật)
- UI Source = Landing Page
```

### Vì sao không lưu `Landing Page` trực tiếp vào DB?

`landing_page` là mã kỹ thuật ổn định để filter/query/API không phụ thuộc ngôn ngữ. UI mới là nơi dịch thành `Landing Page`. Khi đổi giao diện sang tiếng Anh, cùng bản ghi có thể hiển thị `Landing Page` mà không phải sửa database.

### Vì sao Service nằm ở Landing Page thay vì Form Template?

Một Form Template có thể được gắn vào nhiều Landing Page:

```text
Form doanh nghiệp dùng chung
  -> Landing Page Hosting -> HOSTING_BASIC / HOSTING_PRO / HOSTING_VIP
  -> Landing Page VPS     -> VPS_2GB / VPS_4GB / VPS_8GB
  -> Landing Page Email   -> EMAIL_10 / EMAIL_30 / EMAIL_100
```

Do đó `Dịch vụ quan tâm` là một **field semantic dùng chung**, còn danh sách lựa chọn phụ thuộc vào ngữ cảnh Landing Page.

---

# 1. File đã thay đổi

- `app/Models/Marketing/LandingPage.php`
- `app/Models/Marketing/FormTemplate.php`
- `app/Models/Crm/Lead.php`
- `app/Services/Marketing/LandingPageServiceCatalogService.php` (mới)
- `app/Services/Marketing/LandingPageRenderService.php`
- `app/Services/Marketing/LandingPageSubmissionService.php`
- `app/Services/Crm/LeadFormAnswerSnapshotService.php`
- `app/Services/Crm/LeadCreationService.php`
- `app/Filament/Resources/FormTemplateResource.php`
- `app/Filament/Resources/LandingPageResource.php`
- `app/Filament/Resources/LandingPageResource/Pages/CreateLandingPage.php`
- `app/Filament/Resources/LandingPageResource/Pages/EditLandingPage.php`
- `app/Filament/Resources/LeadResource.php`
- `database/migrations/2026_08_07_160000_add_service_context_to_landing_pages.php` (mới)
- `lang/vi.json`
- `lang/en.json`
- `tests/Feature/Marketing/LandingPageServiceContextTest.php` (mới)

---

# 2. Database mới

Migration bổ sung:

```text
landing_pages.service_id
```

và pivot:

```text
landing_page_service_package
- landing_page_id
- service_package_id
```

Ý nghĩa:

- `service_id`: Landing Page đang quảng bá dịch vụ nào.
- Pivot package: Landing Page cho khách chọn những gói nào.
- Nếu không chọn package nào: hệ thống tự dùng **tất cả package active** của Service tương ứng và đúng audience.

---

# 3. Cấu hình Form Template sau khi migrate

Vào **Marketing -> Mẫu biểu mẫu**.

Thực hiện trên cả Form cá nhân và Form doanh nghiệp.

Field `Dịch vụ quan tâm`:

| Thuộc tính | Giá trị |
|---|---|
| Nhãn | Dịch vụ quan tâm |
| Key | `service_interest` |
| Type | Chọn |
| Bắt buộc | Có |
| Nơi lưu dữ liệu | `Lead -> Dịch vụ quan tâm (lấy từ Landing Page)` |
| Các lựa chọn | Không cần cấu hình |

Khi chọn mapping `Lead -> Dịch vụ quan tâm`, UI tự đặt:

- Type = Select
- Required = true
- Ẩn phần Options tĩnh

Các field nhu cầu riêng khác như `website_count`, `migration_required`, `expected_budget` vẫn cấu hình bình thường trong Form Template.

---

# 4. Cấu hình dữ liệu Service/Gói dịch vụ

Vào **Kinh doanh -> Dịch vụ**.

Ví dụ Web Hosting:

```text
Mã dịch vụ: HOSTING
Tên: Web Hosting
Trạng thái: Hoạt động
```

Vào **Kinh doanh -> Gói dịch vụ**.

Ví dụ:

| Mã gói | Tên | Dịch vụ | Đối tượng | Trạng thái |
|---|---|---|---|---|
| `HOSTING_BASIC` | Bình thường | Web Hosting | Cả hai | Active |
| `HOSTING_PRO` | Trung bình | Web Hosting | Cả hai | Active |
| `HOSTING_VIP` | Cao cấp | Web Hosting | Cả hai | Active |

Khi tạo VPS sau này:

```text
Service: VPS
Packages:
VPS_2GB -> 2GB RAM
VPS_4GB -> 4GB RAM
VPS_8GB -> 8GB RAM
```

Không sửa Form Template.

---

# 5. Cấu hình Landing Page Hosting hiện tại

Vào **Marketing -> Trang đích -> Giới thiệu dịch vụ Hosting -> Sửa**.

Section mới **Dịch vụ của trang đích**:

```text
Dịch vụ chính: Web Hosting
Các gói được phép chọn:
- Bình thường
- Trung bình
- Cao cấp
```

Có thể để trống `Các gói được phép chọn` nếu muốn tự lấy toàn bộ gói Web Hosting đang Active.

Lưu Landing Page.

Public form sẽ render:

```text
Web Hosting - Bình thường
Web Hosting - Trung bình
Web Hosting - Cao cấp
```

Nếu sau này gắn cùng Form Template vào Landing Page VPS và chọn:

```text
Dịch vụ chính: VPS
```

thì cùng field `service_interest` tự đổi thành:

```text
VPS - 2GB RAM
VPS - 4GB RAM
VPS - 8GB RAM
```

Không cần clone hoặc sửa Form Template.

---

# 6. Quy tắc validation mới

Backend không tin dữ liệu gửi từ trình duyệt.

Ví dụ Landing Page VPS chỉ cho phép:

```text
VPS_2GB
VPS_4GB
VPS_8GB
```

Nếu request cố tình gửi:

```text
HOSTING_PRO
```

backend từ chối trước khi tạo Submission/Contact/Lead.

Package cũng được lọc theo:

- đúng Service của Landing Page;
- trạng thái Active;
- audience `personal`, `business` hoặc `both`;
- subset package được Landing Page chọn (nếu có).

---

# 7. Dữ liệu lưu ở Lead

Ví dụ khách chọn `HOSTING_PRO`.

Database:

```text
leads.service_interest = HOSTING_PRO
leads.source = landing_page
```

Metadata snapshot:

```json
{
  "service_interest_label": "Web Hosting - Trung bình",
  "service_context": {
    "service_id": 1,
    "service_code": "HOSTING",
    "service_name": "Web Hosting",
    "service_package_id": 2,
    "service_package_code": "HOSTING_PRO",
    "service_package_name": "Trung bình",
    "display_label": "Web Hosting - Trung bình"
  }
}
```

Filament Lead List hiển thị:

```text
Nguồn: Landing Page
Dịch vụ quan tâm: Web Hosting - Trung bình
```

Không hiển thị:

```text
landing_page
HOSTING_PRO
```

cho người dùng nghiệp vụ.

---

# 8. Test bắt buộc trước khi tiếp tục phân phối Lead

## Test A - Hosting

1. Cùng Form Template dùng chung.
2. Landing Page = Hosting.
3. Service = Web Hosting.
4. Chọn `HOSTING_PRO`.
5. Submit.

Expected Lead:

```text
Nguồn = Landing Page
Dịch vụ quan tâm = Web Hosting - Trung bình
Đủ dữ liệu = Có
```

## Test B - Reuse Form trên VPS

Không chỉnh Form Template.

1. Tạo/đổi một Landing Page test sang Service = VPS.
2. Gắn đúng Form Template cá nhân/doanh nghiệp đang dùng cho Hosting.
3. Public form phải chỉ hiện package VPS.
4. Không được hiện package Hosting.

## Test C - Chống giả request

Trên Landing Page VPS gửi `HOSTING_PRO` bằng request thủ công.

Expected:

```text
Validation error service_interest
0 Submission mới
0 Lead mới
```

## Automated test

```bash
php artisan test --filter=LandingPageServiceContextTest
php artisan test --filter=LeadIntakeHardeningTest
```

Sau đó:

```bash
php artisan test
```

---

# 9. Trình tự triển khai nhanh

```bash
git status

git add .
git commit -m "checkpoint: lead intake hardening before service context"

unzip -o DTH-service-context-overlay.zip -d .

php artisan optimize:clear
php artisan migrate
php artisan test --filter=LandingPageServiceContextTest
php artisan test --filter=LeadIntakeHardeningTest
```

Sau khi test pass:

```bash
git add .
git commit -m "feat: resolve lead service interest from landing page catalog"
```

Không chạy `migrate:fresh` vì sẽ xóa dữ liệu cấu hình hiện có.

---

# 10. Sau khi code xong, reset test data rồi test lại Golden Path

Sau khi cấu hình Web Hosting trên Landing Page:

```bash
php artisan crm:reset-ui-test-data --dry-run
php artisan crm:reset-ui-test-data --force --reset-sequences
```

Sau đó submit lại Bộ A từ đầu.

Checkpoint trước khi bấm **Tự động phân phối**:

```text
Submission: 1
Company: 1
Lead: 1
Lead.source UI: Landing Page
Lead.service_interest UI: Web Hosting - Trung bình
Lead Đủ dữ liệu: Có
```

Khi checkpoint này đạt mới tiếp tục test tự động phân phối.
