# KẾ HOẠCH CẢI TIẾN TOÀN BỘ LUỒNG CRM → CƠ HỘI KINH DOANH → BÁO GIÁ → KHÁCH HÀNG

> Repository: `https://github.com/AnhQuyenDev/DTH-business-core.git`  
> Nhánh khảo sát: `master`  
> Thư mục ứng dụng: `marketing-email-laravel-v12/`  
> Ngày lập kế hoạch: `2026-08-05`  
> Stack hiện tại: Laravel 12, Filament 3, MySQL, queue jobs

---

## 0. Mục tiêu của tài liệu

Tài liệu này dùng làm **đặc tả triển khai cho AI Coding Agent hoặc lập trình viên Laravel + Filament**. Đây là kế hoạch **cải thiện hệ thống hiện tại**, không phải viết lại toàn bộ hệ thống.

Các mục tiêu nghiệp vụ đã được chốt:

1. Một doanh nghiệp là một thực thể riêng, không đồng nhất với một người liên hệ.
2. Nhiều người như Giám đốc, IT, Kế toán có thể cùng thuộc một doanh nghiệp.
3. Một người liên hệ có thể gửi nhiều yêu cầu tư vấn khác nhau.
4. Mỗi yêu cầu tư vấn phải có quy trình đánh giá và phân công riêng.
5. Doanh nghiệp chưa mua vẫn được quản lý trong CRM với tư cách `Prospect`, nhưng chưa phải `Customer`.
6. Khi yêu cầu đủ điều kiện thì tạo **Cơ hội kinh doanh**.
7. Báo giá thuộc **Cơ hội kinh doanh**, không bắt buộc phải có Customer.
8. Xem hoặc chấp nhận báo giá chưa làm phát sinh Customer.
9. Chỉ khi thanh toán thành công, hoặc một điều kiện giao dịch tương đương được kích hoạt, hệ thống mới chuyển thành Customer.
10. Module **Chăm sóc khách hàng** chỉ quản lý người hoặc doanh nghiệp đã thực sự trở thành Customer.

Luồng đích:

```text
Landing Page Submission
        ↓
Lead / Yêu cầu tư vấn
        ↓
Company + Contact
        ↓
Qualification / Đánh giá
        ↓
Opportunity / Cơ hội kinh doanh
        ↓
Quotation / Báo giá
        ↓
Payment / Thanh toán
        ↓
Customer
        ↓
Customer Care
```

---

# 1. Kết quả rà soát source hiện tại

## 1.1. Cấu trúc đang có

### Marketing

```text
app/Models/Marketing/Contact.php
app/Models/Marketing/LandingPageSubmission.php
app/Services/Marketing/LandingPageSubmissionService.php
app/Filament/Resources/LandingPageSubmissionResource.php
```

### CRM

```text
app/Models/Crm/PersonalContactProfile.php
app/Models/Crm/BusinessContactProfile.php
app/Models/Crm/ContactQualification.php
app/Models/Crm/ContactQualificationNote.php
app/Models/Crm/Customer.php
app/Models/Crm/CustomerAssignment.php
app/Models/Crm/CustomerInteraction.php
app/Services/Crm/LeadDistributionService.php
app/Services/Crm/CustomerDistributionService.php
app/Filament/Resources/ContactQualificationResource.php
app/Filament/Resources/CustomerResource.php
app/Filament/Pages/CustomerCarePage.php
```

### Sales

```text
app/Models/Sales/Quotation.php
app/Models/Sales/QuotationItem.php
app/Models/Sales/QuotationApproval.php
app/Models/Sales/QuotationConfirmation.php
app/Models/Sales/QuotationDocument.php
app/Models/Sales/QuotationEmailLog.php
app/Services/Sales/QuotationCreationService.php
app/Services/Sales/QuotationPaymentService.php
app/Services/Sales/QuotationMailService.php
app/Services/Sales/QuotationPdfService.php
app/Services/Sales/QuotationPublicAccessService.php
app/Services/Sales/QuotationStateMachine.php
app/Filament/Resources/Sales/QuotationResource.php
app/Jobs/Sales/*
```

## 1.2. Những vấn đề kiến trúc hiện tại

### A. Chưa có thực thể Company

`BusinessContactProfile` đang chứa cả dữ liệu của người liên hệ và dữ liệu công ty:

```text
company_name
tax_code
company_address
legal_representative
contact_position
business_email
business_phone
industry
```

Hậu quả:

- Giám đốc Công ty A và nhân viên IT Công ty A dễ thành hai “công ty” khác nhau.
- Phân phối theo người có thể giao hai nhân viên chăm sóc khác nhau mà không biết họ cùng doanh nghiệp.
- Không có Account Owner chịu trách nhiệm tổng thể cho doanh nghiệp.

### B. Contact chỉ có một Qualification

`Contact` hiện dùng quan hệ gần với:

```php
public function qualification(): HasOne
```

Điều này không phản ánh thực tế:

```text
Cùng một Contact
├── Yêu cầu VPS tháng 8
├── Yêu cầu Email Marketing tháng 10
└── Yêu cầu bảo trì tháng 12
```

Mỗi nhu cầu phải có một Lead và Qualification riêng.

### C. Submission, Qualification và Assignment bị trùng trạng thái

`LandingPageSubmission` đang giữ cả:

```text
status
qualification_status
assigned_staff_id
```

Trong khi `ContactQualification` cũng giữ:

```text
status
assigned_staff_id
qualification_result
```

Điều này tạo nguy cơ hai bảng không đồng nhất.

### D. Phân phối đang chạy theo Submission

`LeadDistributionService` hiện phân phối các Submission chưa có `assigned_staff_id`. Nếu cùng Contact gửi nhiều Submission, có thể bị giao cho nhiều nhân viên khác nhau.

### E. Báo giá bắt buộc có Customer

`Quotation` và `QuotationCreationService` hiện lấy `customer_id` bắt buộc. Điều này ép hệ thống phải tạo Customer trước khi báo giá, trái với nghiệp vụ mới.

### F. Chuyển Customer quá sớm

`ContactQualification::isConvertible()` và `ConvertContactToCustomerAction` hiện cho phép chuyển khi mới `confirmed_need`. Đây mới chỉ là đủ điều kiện, chưa phải đã mua.

### G. Customer Care chứa dữ liệu báo giá trước bán

`CustomerCarePage` hiện thống kê báo giá chờ xử lý, chưa thanh toán và có URL tạo báo giá từ Customer. Theo luồng mới, đây là trách nhiệm của Sales Opportunity, không phải Customer Care.

### H. User/Role hiện còn đơn giản

`User` hiện dùng trường chuỗi `role`, gồm chủ yếu:

```text
admin
marketing_manager
marketing_staff
customer_service_manager
customer_service_staff
viewer
```

Hệ thống chưa phân tách rõ Sales và Finance. Các phase sau phải mở rộng nhưng không cần thay cả cơ chế đăng nhập.

---

# 2. Các nguyên tắc bất biến sau cải tiến

## 2.1. Nguồn dữ liệu chính

| Khái niệm | Bảng/Model nguồn chính |
|---|---|
| Request form thô | `landing_page_submissions` |
| Một yêu cầu tư vấn | `leads` |
| Một con người | `contacts` + personal/business profile |
| Một doanh nghiệp | `companies` |
| Người thuộc doanh nghiệp | `company_contacts` |
| Người phụ trách doanh nghiệp | `company_assignments` / `companies.account_owner_staff_id` |
| Đánh giá Lead | `contact_qualifications` gắn `lead_id` |
| Cơ hội kinh doanh | `sales_opportunities` |
| Báo giá | `quotations` gắn `opportunity_id` |
| Khách hàng đã mua | `customers` |
| Chăm sóc sau bán | `customer_interactions`, `customer_assignments` |

## 2.2. Quy tắc trạng thái

```text
Submission.status
→ chỉ phản ánh tiếp nhận kỹ thuật: received/processed/failed/spam

Lead.assigned_staff_id
→ nguồn chính của trạng thái phân phối

ContactQualification.status
→ nguồn chính của quy trình đánh giá

SalesOpportunity.stage
→ nguồn chính của quy trình bán hàng

Quotation.payment_status
→ nguồn chính xác định đã thanh toán

Customer
→ chỉ được tạo khi giao dịch được xác nhận thành công
```

## 2.3. Không sửa migration cũ đã chạy

Không chỉnh trực tiếp các migration lịch sử đã deploy.

Luôn tạo migration mới:

```bash
php artisan make:migration ...
```

Chỉ được xóa cột/bảng legacy sau khi:

1. Đã có backfill.
2. Đã chạy dual-read/dual-write tối thiểu một release.
3. Đã kiểm tra không còn reference bằng `rg`.
4. Toàn bộ test pass.

---

# 3. Kiến trúc dữ liệu đích

```text
companies
  1 ──────── * company_contacts * ──────── 1 contacts
  │                                              │
  │                                              ├── * landing_page_submissions
  │                                              └── * leads
  │                                                    │
  └── * leads                                         └── 1 contact_qualification
          │
          └── * sales_opportunities
                    │
                    ├── * opportunity_contacts
                    ├── * opportunity_interactions
                    └── * quotations
                              │
                              └── payment_status = paid
                                      ↓
                                  customers
                                      ↓
                                customer_care
```

---

# 4. Ma trận vai trò đề xuất

Không cần thay hệ thống login. Tiếp tục dùng `users.role`, bổ sung role/helper và Policy.

| Vai trò | Quyền chính |
|---|---|
| `admin` | Toàn quyền, merge Company, điều chuyển owner, sửa cấu hình |
| `marketing_manager` | Quản lý Landing Page/Form/Submission, xem Lead, báo cáo nguồn |
| `marketing_staff` | Quản lý nội dung Marketing, chỉ xem Submission/Lead cơ bản |
| `customer_service_manager` | Phân phối Lead, giám sát Qualification, điều chuyển có lý do |
| `customer_service_staff` | Xử lý Lead được giao, cập nhật liên hệ và Qualification |
| `sales_manager` | Quản lý Opportunity, phân công Sales, duyệt báo giá |
| `sales_staff` | Xử lý Opportunity được giao, tạo/gửi báo giá |
| `finance_staff` | Xác minh thanh toán, không được tự đổi Qualification/Owner |
| `viewer` | Chỉ xem báo cáo được phép |

Sau phase chuyển đổi:

- Customer Service xử lý Lead/Qualification.
- Sales xử lý Opportunity/Quotation.
- Finance xác minh Payment.
- Customer Care chỉ xuất hiện sau khi Customer được tạo.

---

# 5. Chiến lược triển khai

Triển khai theo phase, không làm tất cả trong một commit.

```text
Phase 0  — Baseline, backup, feature flag
Phase 1  — Chuẩn hóa Submission và tab Qualification
Phase 2  — Company/Account và nhận diện doanh nghiệp
Phase 3  — Lead/Yêu cầu tư vấn và tách khỏi Contact
Phase 4  — Phân phối, Account Owner và chống giao trùng
Phase 5  — Workflow đánh giá và hoạt động trước bán
Phase 6  — Sales Opportunity
Phase 7  — Chuyển Quotation từ Customer sang Opportunity
Phase 8  — Thanh toán và chuyển Customer
Phase 9  — Role, Policy, menu và giao diện chuẩn
Phase 10 — Backfill, báo cáo, jobs và xóa legacy
```


---

# PHASE 0 — BASELINE, BACKUP VÀ FEATURE FLAG

## Mục tiêu

- Có điểm phục hồi trước khi đổi schema.
- Chạy được test baseline.
- Cho phép triển khai từng phần mà không làm hỏng production.

## 0.1. Tạo branch

```bash
cd marketing-email-laravel-v12

git checkout master
git pull origin master
git checkout -b feature/crm-company-lead-opportunity-flow
```

## 0.2. Cài dependency và kiểm tra môi trường

```bash
composer install
npm install
php artisan optimize:clear
php artisan about
php artisan migrate:status
php artisan route:list > storage/logs/routes-before-crm-v2.txt
```

## 0.3. Backup database

```bash
mkdir -p storage/backups
mysqldump -h 127.0.0.1 -u root -p marketing_email \
  > storage/backups/marketing_email_before_crm_v2_$(date +%Y%m%d_%H%M%S).sql
```

Windows PowerShell:

```powershell
New-Item -ItemType Directory -Force storage/backups
mysqldump -h 127.0.0.1 -u root -p marketing_email `
  > storage/backups/marketing_email_before_crm_v2.sql
```

## 0.4. Tạo feature flag

### Tạo file

```text
config/business_flow.php
```

```php
<?php

return [
    'v2_enabled' => env('BUSINESS_FLOW_V2_ENABLED', false),
    'company_resolution_enabled' => env('COMPANY_RESOLUTION_ENABLED', false),
    'opportunity_quotation_enabled' => env('OPPORTUNITY_QUOTATION_ENABLED', false),
    'customer_on_paid_only' => env('CUSTOMER_ON_PAID_ONLY', false),
];
```

### Sửa `.env.example`

```dotenv
BUSINESS_FLOW_V2_ENABLED=false
COMPANY_RESOLUTION_ENABLED=false
OPPORTUNITY_QUOTATION_ENABLED=false
CUSTOMER_ON_PAID_ONLY=false
```

Không bật cờ cho đến khi phase tương ứng đã migrate và test.

## 0.5. Baseline test

```bash
php artisan test
./vendor/bin/pint --test
```

Nếu baseline đang có test fail, ghi lại trước khi sửa:

```bash
php artisan test > storage/logs/tests-before-crm-v2.txt 2>&1
```

## Tiêu chí nghiệm thu Phase 0

- Có file backup database.
- Có branch riêng.
- Có danh sách route baseline.
- Có feature flag mặc định `false`.
- Không thay đổi hành vi người dùng.

---

# PHASE 1 — CHUẨN HÓA SUBMISSION VÀ QUY TRÌNH ĐÁNH GIÁ HIỆN TẠI

## Mục tiêu

Sửa ngay các bất nhất người dùng đang thấy mà chưa cần đổi kiến trúc lớn:

- Hiển thị loại biểu mẫu Cá nhân/Doanh nghiệp.
- `Processed` đổi nhãn thành “Đã ghi nhận”, không hiểu là đã phân phối.
- Hiển thị riêng trạng thái phân phối và CRM.
- Tab `Assigned` thuộc “Đang xử lý”, không thuộc “Chưa liên hệ”.
- Không cho sửa trực tiếp trạng thái CRM trên Submission.

## 1.1. Sửa translation

### File

```text
lang/vi.json
lang/en.json
```

Thêm hoặc sửa:

```json
{
  "submission.status.received": "Đã tiếp nhận",
  "submission.status.processed": "Đã ghi nhận",
  "submission.status.failed": "Xử lý thất bại",
  "submission.status.spam": "Spam",
  "submission.type.personal": "Cá nhân",
  "submission.type.business": "Doanh nghiệp",
  "distribution.unassigned": "Chưa phân phối",
  "distribution.assigned": "Đã phân phối",
  "lead.status.new": "Chưa liên hệ",
  "lead.status.assigned": "Đã phân phối",
  "lead.status.contacting": "Đang liên hệ",
  "lead.status.follow_up": "Đang theo dõi",
  "lead.status.qualified": "Đủ điều kiện",
  "lead.status.unqualified": "Không đủ điều kiện",
  "lead.status.converted": "Đã chuyển thành khách hàng"
}
```

Bản tiếng Anh phải có key tương ứng.

## 1.2. Sửa bảng Submission

### File sửa

```text
app/Filament/Resources/LandingPageSubmissionResource.php
```

### Thêm cột loại biểu mẫu

```php
TextColumn::make('submission_type')
    ->label('Loại biểu mẫu')
    ->badge()
    ->formatStateUsing(fn (?string $state): string => match ($state) {
        'personal' => __('submission.type.personal'),
        'business' => __('submission.type.business'),
        default => 'Không xác định',
    })
    ->color(fn (?string $state): string => match ($state) {
        'personal' => 'info',
        'business' => 'warning',
        default => 'gray',
    });
```

### Sửa cột trạng thái tiếp nhận

```php
TextColumn::make('status')
    ->label('Tiếp nhận')
    ->badge()
    ->formatStateUsing(fn ($state): string => match ($state?->value ?? $state) {
        'received' => __('submission.status.received'),
        'processed' => __('submission.status.processed'),
        'failed' => __('submission.status.failed'),
        'spam' => __('submission.status.spam'),
        default => (string) ($state?->value ?? $state ?? '—'),
    });
```

### Thêm trạng thái phân phối tạm thời

Trước khi có `Lead`, lấy từ Qualification hiện tại:

```php
TextColumn::make('distribution_state')
    ->label('Phân phối')
    ->badge()
    ->getStateUsing(
        fn (LandingPageSubmission $record): string =>
            $record->contact?->qualification?->assigned_staff_id
                ? 'assigned'
                : 'unassigned'
    )
    ->formatStateUsing(fn (string $state): string => match ($state) {
        'assigned' => __('distribution.assigned'),
        default => __('distribution.unassigned'),
    })
    ->color(fn (string $state): string => $state === 'assigned' ? 'success' : 'warning');
```

### Thêm trạng thái CRM

```php
TextColumn::make('contact.qualification.status')
    ->label('Trạng thái CRM')
    ->badge()
    ->formatStateUsing(function ($state): string {
        $value = $state?->value ?? $state;

        return match ($value) {
            'new' => __('lead.status.new'),
            'assigned' => __('lead.status.assigned'),
            'contacting' => __('lead.status.contacting'),
            'follow_up' => __('lead.status.follow_up'),
            'qualified' => __('lead.status.qualified'),
            'unqualified' => __('lead.status.unqualified'),
            'converted' => __('lead.status.converted'),
            default => $value ?: '—',
        };
    });
```

### Thêm filter

```php
SelectFilter::make('submission_type')
    ->label('Loại biểu mẫu')
    ->options([
        'personal' => __('submission.type.personal'),
        'business' => __('submission.type.business'),
    ]);
```

### Xóa action sai trách nhiệm

Xóa hoặc ẩn action đang cho sửa trực tiếp:

```text
qualification_status
assigned_staff_id
```

trên `LandingPageSubmissionResource`.

Submission chỉ là dữ liệu đầu vào, không phải nơi điều khiển workflow CRM.

## 1.3. Sửa tab Qualification

### File

```text
app/Filament/Resources/ContactQualificationResource/Pages/ListContactQualifications.php
```

### Code

```php
public function getTabs(): array
{
    return [
        'overview' => Tab::make('Tổng quan'),

        'not_contacted' => Tab::make('Chưa liên hệ')
            ->modifyQueryUsing(
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::New->value
                )
            ),

        'in_progress' => Tab::make('Đang xử lý')
            ->modifyQueryUsing(
                fn ($query) => $query->whereIn('status', [
                    ContactQualificationStatus::Assigned->value,
                    ContactQualificationStatus::Contacting->value,
                    ContactQualificationStatus::FollowUp->value,
                ])
            ),

        'qualified' => Tab::make('Đủ điều kiện')
            ->modifyQueryUsing(
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::Qualified->value
                )
            ),

        'closed' => Tab::make('Không đủ điều kiện')
            ->modifyQueryUsing(
                fn ($query) => $query->whereIn('status', [
                    ContactQualificationStatus::Unqualified->value,
                    ContactQualificationStatus::Duplicate->value,
                    ContactQualificationStatus::Spam->value,
                    ContactQualificationStatus::Archived->value,
                ])
            ),

        'converted' => Tab::make('Đã chuyển thành khách hàng')
            ->modifyQueryUsing(
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::Converted->value
                )
            ),
    ];
}
```

## 1.4. Test Phase 1

Tạo:

```text
tests/Feature/Marketing/LandingPageSubmissionResourceTest.php
tests/Feature/Crm/ContactQualificationTabsTest.php
```

Các case bắt buộc:

```text
- Submission personal hiển thị Cá nhân.
- Submission business hiển thị Doanh nghiệp.
- Processed được hiển thị Đã ghi nhận.
- Qualification new nằm Chưa liên hệ.
- Qualification assigned nằm Đang xử lý.
- Submission không có action sửa qualification trực tiếp.
```

Chạy:

```bash
php artisan test --filter=LandingPageSubmissionResourceTest
php artisan test --filter=ContactQualificationTabsTest
./vendor/bin/pint
```

## Tiêu chí nghiệm thu Phase 1

Một Submission mới phải hiển thị:

```text
Tiếp nhận: Đã ghi nhận
Phân phối: Chưa phân phối
CRM: Chưa liên hệ
Loại biểu mẫu: Cá nhân hoặc Doanh nghiệp
```

---

# PHASE 2 — THÊM COMPANY/ACCOUNT VÀ NHẬN DIỆN DOANH NGHIỆP

## Mục tiêu

- Một Công ty A chỉ có một bản ghi Company.
- Giám đốc và nhân viên IT là hai Contact cùng liên kết Company A.
- Có Account Owner quản lý doanh nghiệp.
- Không còn dùng `company_name` đơn thuần để nhóm doanh nghiệp.

## 2.1. Tạo enum

### File mới

```text
app/Enums/Crm/CompanyLifecycleStage.php
```

```php
<?php

namespace App\Enums\Crm;

enum CompanyLifecycleStage: string
{
    case Prospect = 'prospect';
    case Qualified = 'qualified';
    case Customer = 'customer';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Prospect => 'Tiềm năng',
            self::Qualified => 'Đủ điều kiện',
            self::Customer => 'Khách hàng',
            self::Inactive => 'Không hoạt động',
        };
    }
}
```

### File mới

```text
app/Enums/Crm/CompanyContactDecisionRole.php
```

```php
<?php

namespace App\Enums\Crm;

enum CompanyContactDecisionRole: string
{
    case DecisionMaker = 'decision_maker';
    case Influencer = 'influencer';
    case TechnicalContact = 'technical_contact';
    case BillingContact = 'billing_contact';
    case EndUser = 'end_user';
    case Other = 'other';
}
```

## 2.2. Migration Company

Chạy:

```bash
php artisan make:migration create_companies_table
php artisan make:migration create_company_contacts_table
php artisan make:migration create_company_assignments_table
php artisan make:migration create_company_match_candidates_table
php artisan make:migration add_company_id_to_business_contact_profiles_table --table=business_contact_profiles
php artisan make:migration add_company_id_to_landing_page_submissions_table --table=landing_page_submissions
```

### `create_companies_table`

```php
Schema::create('companies', function (Blueprint $table): void {
    $table->id();
    $table->string('company_code')->unique();
    $table->string('legal_name');
    $table->string('normalized_name')->index();
    $table->string('tax_code')->nullable()->unique();
    $table->string('email_domain')->nullable()->index();
    $table->string('website')->nullable();
    $table->string('phone')->nullable();
    $table->string('normalized_phone')->nullable()->index();
    $table->string('industry')->nullable();
    $table->text('address')->nullable();
    $table->string('province')->nullable();
    $table->string('country_code', 2)->nullable();
    $table->string('lifecycle_stage')->default('prospect')->index();
    $table->foreignId('account_owner_staff_id')
        ->nullable()
        ->constrained('staff')
        ->nullOnDelete();
    $table->foreignId('created_from_submission_id')
        ->nullable()
        ->constrained('landing_page_submissions')
        ->nullOnDelete();
    $table->json('metadata')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
});
```

### `create_company_contacts_table`

```php
Schema::create('company_contacts', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
    $table->string('job_title')->nullable();
    $table->string('department')->nullable();
    $table->string('decision_role')->default('other');
    $table->boolean('is_primary')->default(false);
    $table->boolean('is_active')->default(true);
    $table->date('joined_at')->nullable();
    $table->date('left_at')->nullable();
    $table->timestamps();

    $table->unique(['company_id', 'contact_id']);
    $table->index(['company_id', 'is_primary']);
});
```

### `create_company_assignments_table`

```php
Schema::create('company_assignments', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('company_id')->constrained()->cascadeOnDelete();
    $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
    $table->string('assignment_type')->default('owner');
    $table->string('status')->default('active');
    $table->text('reason')->nullable();
    $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('starts_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamps();

    $table->index(['company_id', 'status']);
});
```

### `create_company_match_candidates_table`

```php
Schema::create('company_match_candidates', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('submission_id')->nullable()
        ->constrained('landing_page_submissions')->cascadeOnDelete();
    $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('suggested_company_id')->constrained('companies')->cascadeOnDelete();
    $table->unsignedTinyInteger('confidence_score');
    $table->string('matched_by');
    $table->string('status')->default('pending');
    $table->json('evidence')->nullable();
    $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamps();
});
```

### Thêm `company_id`

```php
Schema::table('business_contact_profiles', function (Blueprint $table): void {
    $table->foreignId('company_id')
        ->nullable()
        ->after('contact_id')
        ->constrained('companies')
        ->nullOnDelete();
});
```

```php
Schema::table('landing_page_submissions', function (Blueprint $table): void {
    $table->foreignId('company_id')
        ->nullable()
        ->after('contact_id')
        ->constrained('companies')
        ->nullOnDelete();
});
```

## 2.3. Model mới

### `app/Models/Crm/Company.php`

```php
<?php

namespace App\Models\Crm;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Models\Marketing\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_code',
        'legal_name',
        'normalized_name',
        'tax_code',
        'email_domain',
        'website',
        'phone',
        'normalized_phone',
        'industry',
        'address',
        'province',
        'country_code',
        'lifecycle_stage',
        'account_owner_staff_id',
        'created_from_submission_id',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'lifecycle_stage' => CompanyLifecycleStage::class,
            'metadata' => 'array',
        ];
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'company_contacts')
            ->withPivot([
                'job_title',
                'department',
                'decision_role',
                'is_primary',
                'is_active',
                'joined_at',
                'left_at',
            ])
            ->withTimestamps();
    }

    public function accountOwner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'account_owner_staff_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CompanyAssignment::class);
    }
}
```

Tạo thêm:

```text
app/Models/Crm/CompanyContact.php
app/Models/Crm/CompanyAssignment.php
app/Models/Crm/CompanyMatchCandidate.php
```

## 2.4. Sửa quan hệ model hiện tại

### `BusinessContactProfile.php`

Thêm `company_id` vào `$fillable` và:

```php
public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}
```

### `LandingPageSubmission.php`

Thêm `company_id` vào `$fillable` và:

```php
public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}
```

### `Contact.php`

```php
public function companies(): BelongsToMany
{
    return $this->belongsToMany(Company::class, 'company_contacts')
        ->withPivot([
            'job_title',
            'department',
            'decision_role',
            'is_primary',
            'is_active',
        ])
        ->withTimestamps();
}
```

## 2.5. Company Resolution Service

### File mới

```text
app/Services/Crm/CompanyResolutionService.php
```

Quy tắc:

1. MST trùng chính xác: auto-match.
2. Email domain doanh nghiệp trùng: đề xuất hoặc auto-match khi không xung đột MST.
3. Tên chuẩn hóa + phone/address: chỉ tạo candidate, không auto-merge.
4. Gmail/Outlook/Yahoo không dùng để match Company.

```php
<?php

namespace App\Services\Crm;

use App\Models\Crm\Company;
use Illuminate\Support\Str;

final class CompanyResolutionService
{
    private const FREE_EMAIL_DOMAINS = [
        'gmail.com',
        'outlook.com',
        'hotmail.com',
        'yahoo.com',
        'icloud.com',
    ];

    public function resolve(array $data): CompanyResolutionResult
    {
        $taxCode = $this->normalizeTaxCode($data['tax_code'] ?? null);

        if ($taxCode !== null) {
            $company = Company::query()->where('tax_code', $taxCode)->first();

            if ($company) {
                return CompanyResolutionResult::matched($company, 100, 'tax_code');
            }
        }

        $domain = $this->extractBusinessDomain($data['business_email'] ?? null);

        if ($domain !== null) {
            $matches = Company::query()->where('email_domain', $domain)->get();

            if ($matches->count() === 1) {
                $company = $matches->first();

                if ($taxCode === null || blank($company->tax_code) || $company->tax_code === $taxCode) {
                    return CompanyResolutionResult::matched($company, 85, 'email_domain');
                }
            }
        }

        $normalizedName = $this->normalizeName($data['company_name'] ?? null);

        if ($normalizedName !== null) {
            $company = Company::query()
                ->where('normalized_name', $normalizedName)
                ->first();

            if ($company) {
                return CompanyResolutionResult::candidate($company, 65, 'normalized_name');
            }
        }

        return CompanyResolutionResult::notFound();
    }

    public function normalizeName(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/\b(cong ty|cty|tnhh|co phan|jsc|ltd|company)\b/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalizeTaxCode(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    private function extractBusinessDomain(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return null;
        }

        $domain = Str::lower(Str::after($email, '@'));

        return in_array($domain, self::FREE_EMAIL_DOMAINS, true)
            ? null
            : $domain;
    }
}
```

Tạo DTO:

```text
app/Data/Crm/CompanyResolutionResult.php
```

## 2.6. Company Code Generator

Tạo:

```text
app/Services/Crm/CompanyCodeGenerator.php
```

Format gợi ý:

```text
COM-2026-000001
```

Không dùng ID đơn thuần làm mã hiển thị.

## 2.7. Backfill Company từ dữ liệu cũ

Tạo command:

```bash
php artisan make:command BackfillCompaniesFromBusinessProfiles
```

### File

```text
app/Console/Commands/BackfillCompaniesFromBusinessProfiles.php
```

Luồng:

```text
BusinessContactProfile
→ nhóm theo tax_code nếu có
→ nếu không có MST thì normalized company_name
→ tạo Company
→ cập nhật business_contact_profiles.company_id
→ attach company_contacts
→ không xóa field cũ
```

Chạy dry-run trước:

```bash
php artisan crm:backfill-companies --dry-run
php artisan crm:backfill-companies
```

Command phải idempotent.

## 2.8. Giao diện Company

Tạo:

```text
app/Filament/Resources/CompanyResource.php
app/Filament/Resources/CompanyResource/Pages/*
app/Filament/Resources/CompanyResource/RelationManagers/ContactsRelationManager.php
app/Filament/Resources/CompanyResource/RelationManagers/AssignmentsRelationManager.php
app/Filament/Resources/CompanyMatchCandidateResource.php
```

Trang Company chuẩn:

```text
Tổng quan
Người liên hệ
Yêu cầu tư vấn
Cơ hội kinh doanh
Báo giá
Hoạt động
Phân công
Lịch sử
```

Ở phase này các tab Lead/Opportunity có thể để placeholder hoặc chỉ hiện sau phase tương ứng.

## 2.9. Tax verification job

### File cần sửa

```text
app/Jobs/Crm/VerifyBusinessTaxCodeJob.php
app/Services/Crm/TaxCodeVerificationService.php
```

Chuyển dần nguồn xác minh từ `BusinessContactProfile.tax_code` sang `Company.tax_code`.

Trong một release chuyển tiếp:

- Ghi kết quả vào Company.
- Đồng bộ ngược profile cũ để tránh hỏng UI legacy.

## 2.10. Test Phase 2

Tạo:

```text
tests/Unit/Crm/CompanyResolutionServiceTest.php
tests/Feature/Crm/CompanyBackfillTest.php
tests/Feature/Crm/CompanyResourceAuthorizationTest.php
```

Case bắt buộc:

```text
- Hai người cùng MST → một Company, hai Contact.
- Email cùng domain doanh nghiệp → candidate/match đúng.
- Gmail giống nhau không được dùng để ghép Company.
- Tên gần giống nhưng MST khác → không auto-merge.
- Backfill chạy hai lần không tạo Company trùng.
```

Chạy:

```bash
php artisan migrate
php artisan crm:backfill-companies --dry-run
php artisan test --filter=Company
./vendor/bin/pint
```

## Tiêu chí nghiệm thu Phase 2

```text
Công ty A
├── Nguyễn Văn A — Giám đốc
└── Trần Văn B — IT
```

Hai Contact phải liên kết cùng một `company_id`.


---

# PHASE 3 — THÊM LEAD/YÊU CẦU TƯ VẤN VÀ TÁCH QUALIFICATION KHỎI CONTACT

## Mục tiêu

- Mỗi Submission hợp lệ tạo một Lead.
- Một Contact có thể có nhiều Lead.
- Qualification thuộc Lead, không còn bị giới hạn một Qualification cho một Contact.
- Contact và Company là “ai”, Lead là “họ đang cần gì”.

## 3.1. Tạo enum Lead

### File mới

```text
app/Enums/Crm/LeadIntakeStatus.php
```

```php
<?php

namespace App\Enums\Crm;

enum LeadIntakeStatus: string
{
    case New = 'new';
    case Active = 'active';
    case Duplicate = 'duplicate';
    case Spam = 'spam';
    case Closed = 'closed';
    case ConvertedToOpportunity = 'converted_to_opportunity';
}
```

Không trùng lặp workflow Qualification. `LeadIntakeStatus` chỉ phản ánh vòng đời yêu cầu; chi tiết đánh giá vẫn ở `ContactQualification.status`.

## 3.2. Migration Lead

Chạy:

```bash
php artisan make:migration create_leads_table
php artisan make:migration add_lead_id_to_contact_qualifications_table --table=contact_qualifications
```

### `create_leads_table`

```php
Schema::create('leads', function (Blueprint $table): void {
    $table->id();
    $table->string('lead_code')->unique();
    $table->foreignId('submission_id')
        ->nullable()
        ->unique()
        ->constrained('landing_page_submissions')
        ->nullOnDelete();
    $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
    $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->nullOnDelete();
    $table->string('source')->default('landing_page');
    $table->string('source_detail')->nullable();
    $table->string('title');
    $table->string('service_interest')->nullable();
    $table->decimal('estimated_value', 18, 2)->nullable();
    $table->string('intake_status')->default('new')->index();
    $table->timestamp('assigned_at')->nullable();
    $table->foreignId('assigned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('converted_to_opportunity_at')->nullable();
    $table->json('metadata')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['company_id', 'intake_status']);
    $table->index(['assigned_staff_id', 'intake_status']);
});
```

### Thêm `lead_id` vào Qualification

```php
Schema::table('contact_qualifications', function (Blueprint $table): void {
    $table->foreignId('lead_id')
        ->nullable()
        ->after('id')
        ->constrained('leads')
        ->cascadeOnDelete();

    $table->unique('lead_id');
});
```

Trong phase chuyển tiếp **chưa xóa**:

```text
contact_qualifications.contact_id
contact_qualifications.assigned_staff_id
```

## 3.3. Model Lead

### File mới

```text
app/Models/Crm/Lead.php
```

```php
<?php

namespace App\Models\Crm;

use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lead_code',
        'submission_id',
        'contact_id',
        'company_id',
        'assigned_staff_id',
        'source',
        'source_detail',
        'title',
        'service_interest',
        'estimated_value',
        'intake_status',
        'assigned_at',
        'assigned_by_user_id',
        'converted_to_opportunity_at',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'intake_status' => LeadIntakeStatus::class,
            'estimated_value' => 'decimal:2',
            'assigned_at' => 'datetime',
            'converted_to_opportunity_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(LandingPageSubmission::class, 'submission_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function qualification(): HasOne
    {
        return $this->hasOne(ContactQualification::class);
    }
}
```

Tạo:

```text
app/Services/Crm/LeadCodeGenerator.php
```

Format:

```text
LEAD-2026-000001
```

## 3.4. Sửa quan hệ model cũ

### `Contact.php`

Giữ `qualification()` tạm thời và đánh dấu deprecated bằng comment. Thêm:

```php
public function leads(): HasMany
{
    return $this->hasMany(Lead::class);
}

public function qualifications(): HasManyThrough
{
    return $this->hasManyThrough(
        ContactQualification::class,
        Lead::class,
        'contact_id',
        'lead_id'
    );
}
```

### `LandingPageSubmission.php`

```php
public function lead(): HasOne
{
    return $this->hasOne(Lead::class, 'submission_id');
}
```

### `ContactQualification.php`

Thêm `lead_id` vào `$fillable` và:

```php
public function lead(): BelongsTo
{
    return $this->belongsTo(Lead::class);
}
```

## 3.5. Tách việc tìm Contact khỏi việc tìm Company

### File sửa

```text
app/Services/Marketing/LandingPageSubmissionService.php
```

Hiện không được dùng MST để tìm “Contact trùng”, vì hai người cùng công ty có cùng MST nhưng là hai con người khác nhau.

### Quy tắc dedupe Contact

```text
1. Email cá nhân/doanh nghiệp chính xác.
2. Số điện thoại đã chuẩn hóa.
3. Không tìm Contact theo tax_code.
4. Tax code chỉ dùng để resolve Company.
```

Xóa nhánh tương tự:

```php
BusinessContactProfile::where('tax_code', $taxCode)->first()?->contact
```

khỏi hàm tìm Contact trùng.

## 3.6. Wrap submit trong transaction

### File sửa

```text
app/Services/Marketing/LandingPageSubmissionService.php
```

Thêm:

```php
use Illuminate\Support\Facades\DB;
```

```php
public function handle(
    LandingPage $landingPage,
    array $payload,
    Request $request,
    ?int $campaignId = null
): LandingPageSubmission {
    return DB::transaction(
        fn (): LandingPageSubmission => $this->processSubmission(
            $landingPage,
            $payload,
            $request,
            $campaignId
        )
    );
}
```

Di chuyển thân `handle()` cũ vào:

```php
private function processSubmission(...): LandingPageSubmission
```

## 3.7. Luồng xử lý Submission mới

Trong `processSubmission()`:

```text
1. Resolve Form Template.
2. Validate payload.
3. Resolve hoặc tạo Contact theo email/phone.
4. Nếu business: resolve hoặc tạo Company.
5. Lưu Personal/Business Profile.
6. Attach Contact vào Company.
7. Tạo Submission status=processed.
8. Tạo Lead cho Submission.
9. Tạo ContactQualification gắn lead_id, status=new.
10. Dispatch event sau commit.
```

### Service mới

```text
app/Services/Crm/LeadCreationService.php
```

```php
<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Marketing\LandingPageSubmission;

final class LeadCreationService
{
    public function __construct(
        private readonly LeadCodeGenerator $codeGenerator,
    ) {
    }

    public function createFromSubmission(
        LandingPageSubmission $submission,
        ?int $companyId,
        ?string $serviceInterest,
        ?int $userId = null,
    ): Lead {
        $lead = Lead::query()->firstOrCreate(
            ['submission_id' => $submission->id],
            [
                'lead_code' => $this->codeGenerator->next(),
                'contact_id' => $submission->contact_id,
                'company_id' => $companyId,
                'source' => 'landing_page',
                'source_detail' => $submission->landingPage?->name,
                'title' => $serviceInterest
                    ? 'Yêu cầu tư vấn: '.$serviceInterest
                    : 'Yêu cầu tư vấn từ Landing Page',
                'service_interest' => $serviceInterest,
                'intake_status' => LeadIntakeStatus::New->value,
                'created_by' => $userId,
            ]
        );

        ContactQualification::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'contact_id' => $lead->contact_id, // compatibility phase
                'status' => ContactQualificationStatus::New->value,
                'priority' => 'normal',
            ]
        );

        return $lead->fresh('qualification');
    }
}
```

## 3.8. Company Contact link

Trong `LandingPageSubmissionService`, sau khi có `$contact` và `$company`:

```php
$company->contacts()->syncWithoutDetaching([
    $contact->id => [
        'job_title' => $validatedData[$positionKey] ?? null,
        'decision_role' => $this->inferDecisionRole(
            $validatedData[$positionKey] ?? null
        ),
        'is_primary' => ! $company->contacts()->exists(),
        'is_active' => true,
    ],
]);
```

`inferDecisionRole()` chỉ là đề xuất. Admin/Staff phải chỉnh được.

## 3.9. Backfill Lead

Tạo command:

```bash
php artisan make:command BackfillLeadsFromSubmissions
```

### Luồng backfill

- Mỗi Submission hợp lệ chưa có Lead → tạo Lead.
- Qualification cũ của Contact:
  - gắn vào Lead mới nhất hoặc Lead đang mở.
  - các Lead khác tạo Qualification `new`.
- Sinh report các Contact có nhiều Submission để admin review.

```bash
php artisan crm:backfill-leads --dry-run
php artisan crm:backfill-leads
```

## 3.10. Giao diện Lead

Tạo mới:

```text
app/Filament/Resources/LeadResource.php
app/Filament/Resources/LeadResource/Pages/*
app/Filament/Resources/LeadResource/RelationManagers/*
```

Không xóa `ContactQualificationResource` ngay. Trong phase chuyển tiếp:

- Menu mới dùng `LeadResource`.
- Resource cũ ẩn navigation nhưng vẫn giữ URL để rollback.

### Các cột Lead

```text
Mã Lead
Liên hệ
Doanh nghiệp
Loại liên hệ
Nguồn
Nhu cầu
Nhân viên phụ trách
Trạng thái đánh giá
Lần liên hệ tiếp theo
Tạo lúc
```

### Các tab

```text
Tổng quan
Chưa liên hệ
Đang xử lý
Đủ điều kiện
Không đủ điều kiện
Đã chuyển thành khách hàng
```

## 3.11. Test Phase 3

```text
tests/Feature/Marketing/SubmissionCreatesLeadTest.php
tests/Feature/Crm/MultipleLeadsPerContactTest.php
tests/Feature/Crm/SameCompanyDifferentContactsTest.php
```

Case:

```text
- Một submission tạo đúng một Lead.
- Submit retry không tạo hai Lead cho cùng submission_id.
- Một Contact gửi hai form tạo hai Lead.
- Hai nhân viên cùng MST tạo hai Contact, một Company.
- Nếu lưu profile lỗi, Contact/Submission/Lead đều rollback.
```

Chạy:

```bash
php artisan migrate
php artisan crm:backfill-leads --dry-run
php artisan test --filter=Lead
./vendor/bin/pint
```

## Tiêu chí nghiệm thu Phase 3

- Không còn quan niệm “một Contact chỉ có một nhu cầu”.
- Submission mới luôn có Lead.
- Qualification mới luôn có `lead_id`.

---

# PHASE 4 — PHÂN PHỐI LEAD, ACCOUNT OWNER VÀ CHỐNG GIAO TRÙNG

## Mục tiêu

- Phân phối theo Lead, không theo Submission.
- Nếu Company đã có Account Owner thì Lead mới ưu tiên người đó.
- Không đổi Account Owner khi giao một Opportunity chuyên môn khác.
- Điều chuyển phải có lịch sử và lý do.

## 4.1. Tạo LeadAssignmentService

### File mới

```text
app/Services/Crm/LeadAssignmentService.php
```

```php
<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LeadAssignmentService
{
    public function assign(
        Lead $lead,
        Staff $staff,
        ?int $assignedByUserId,
        ?string $reason = null,
        bool $force = false,
    ): Lead {
        return DB::transaction(function () use (
            $lead,
            $staff,
            $assignedByUserId,
            $reason,
            $force
        ): Lead {
            $lead->loadMissing('company', 'qualification');

            $accountOwnerId = $lead->company?->account_owner_staff_id;

            if (
                ! $force &&
                $accountOwnerId !== null &&
                $accountOwnerId !== $staff->id
            ) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' =>
                        'Doanh nghiệp đang do nhân viên khác phụ trách. '
                        .'Cần quyền điều chuyển và lý do.',
                ]);
            }

            $lead->update([
                'assigned_staff_id' => $staff->id,
                'assigned_at' => now(),
                'assigned_by_user_id' => $assignedByUserId,
                'intake_status' => 'active',
            ]);

            $lead->qualification()->update([
                'assigned_staff_id' => $staff->id, // compatibility phase
                'status' => ContactQualificationStatus::Assigned->value,
            ]);

            if ($lead->company && $lead->company->account_owner_staff_id === null) {
                $lead->company->update([
                    'account_owner_staff_id' => $staff->id,
                ]);

                $lead->company->assignments()->create([
                    'staff_id' => $staff->id,
                    'assignment_type' => 'owner',
                    'status' => 'active',
                    'reason' => $reason ?? 'Tự động gán từ Lead đầu tiên',
                    'assigned_by_user_id' => $assignedByUserId,
                    'starts_at' => now(),
                ]);
            }

            return $lead->fresh(['assignedStaff', 'qualification', 'company.accountOwner']);
        });
    }
}
```

## 4.2. Sửa LeadDistributionService

### File sửa

```text
app/Services/Crm/LeadDistributionService.php
```

Thay nguồn query từ:

```text
LandingPageSubmission where assigned_staff_id is null
```

thành:

```php
Lead::query()
    ->whereNull('assigned_staff_id')
    ->whereHas('qualification', fn ($query) => $query->where('status', 'new'))
    ->with(['company.accountOwner', 'qualification', 'contact']);
```

### Quy tắc chọn người

```text
1. Company có Account Owner active → gán người đó.
2. Không có Account Owner → chọn nhân viên đủ điều kiện ít tải nhất.
3. Lead cá nhân không có Company → chọn theo workload.
4. Không phân phối duplicate/spam/closed.
5. Một Lead chỉ được gán một lần trừ khi force reassign.
```

## 4.3. Tạo CompanyOwnershipService

### File mới

```text
app/Services/Crm/CompanyOwnershipService.php
```

Trách nhiệm:

- Gán Account Owner.
- Điều chuyển owner.
- Kết thúc assignment cũ.
- Yêu cầu reason.
- Ghi audit log.
- Không tự thay owner khi chỉ giao Opportunity Owner khác.

## 4.4. UI phân phối

### Sửa

```text
app/Filament/Resources/LeadResource.php
app/Filament/Resources/LeadResource/Pages/ListLeads.php
```

Action:

```text
Phân công
Điều chuyển
Tự động phân phối
```

`Điều chuyển` chỉ hiện với:

```text
admin
customer_service_manager
sales_manager
```

Form điều chuyển bắt buộc:

```text
Nhân viên mới
Lý do điều chuyển
Có chuyển Account Owner hay chỉ Lead Owner?
```

## 4.5. Không cho Submission tự phân phối

### Sửa

```text
app/Filament/Resources/LandingPageSubmissionResource/Pages/ListLandingPageSubmissions.php
```

Nút “Tự động phân phối” phải gọi LeadDistributionService theo Lead, hoặc chuyển nút sang `LeadResource`.

Khuyến nghị:

- Submission chỉ có action “Mở Lead”.
- Tự động phân phối nằm ở Lead list.

## 4.6. Event

Tạo:

```text
app/Events/Crm/LeadAssigned.php
app/Listeners/Crm/RecordLeadAssignmentAudit.php
```

Dispatch bằng `DB::afterCommit()` hoặc event sau commit.

## 4.7. Test Phase 4

```text
tests/Feature/Crm/LeadDistributionTest.php
tests/Feature/Crm/CompanyOwnershipTest.php
```

Case:

```text
- Lead đầu tiên tạo Account Owner.
- Lead thứ hai cùng Company kế thừa Account Owner.
- Không giao người khác nếu không force.
- Force reassign bắt buộc reason.
- Hai Submission cùng Contact không bị giao hai người.
- Lead spam không được phân phối.
```

Chạy:

```bash
php artisan test --filter=LeadDistribution
php artisan test --filter=CompanyOwnership
```

## Tiêu chí nghiệm thu Phase 4

```text
Công ty A — Account Owner: Nhân viên X
├── Lead của Giám đốc → Nhân viên X
└── Lead của IT → Nhân viên X
```

Nếu Opportunity cần chuyên môn khác, phase 6 sẽ cho phép Opportunity Owner khác mà không đổi Account Owner.

---

# PHASE 5 — WORKFLOW ĐÁNH GIÁ VÀ HOẠT ĐỘNG TRƯỚC BÁN

## Mục tiêu

- Không cập nhật status tùy ý trong Filament Resource.
- Mọi transition đi qua service.
- Ghi lịch sử gọi điện, email, meeting trước khi có Customer.
- CustomerInteraction tiếp tục chỉ dùng sau bán.

## 5.1. Tạo bảng Lead Activity

```bash
php artisan make:migration create_lead_activities_table
```

```php
Schema::create('lead_activities', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
    $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
    $table->string('activity_type');
    $table->string('subject');
    $table->text('content')->nullable();
    $table->string('outcome')->nullable();
    $table->string('status')->default('completed');
    $table->timestamp('activity_at')->nullable();
    $table->timestamp('next_follow_up_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

Tạo:

```text
app/Models/Crm/LeadActivity.php
```

## 5.2. Workflow service

### File mới

```text
app/Services/Crm/ContactQualificationWorkflowService.php
```

```php
<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\ContactQualification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ContactQualificationWorkflowService
{
    private const ALLOWED_TRANSITIONS = [
        'new' => ['assigned', 'duplicate', 'spam', 'archived'],
        'assigned' => ['contacting', 'unqualified', 'duplicate', 'spam'],
        'contacting' => ['follow_up', 'qualified', 'unqualified'],
        'follow_up' => ['contacting', 'qualified', 'unqualified'],
        'qualified' => ['converted', 'unqualified'],
        'unqualified' => ['archived'],
        'converted' => [],
        'duplicate' => ['archived'],
        'spam' => ['archived'],
        'archived' => [],
    ];

    public function transition(
        ContactQualification $qualification,
        ContactQualificationStatus $to,
        array $data = [],
    ): ContactQualification {
        $from = $qualification->status?->value ?? $qualification->status;

        if (! in_array($to->value, self::ALLOWED_TRANSITIONS[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Không thể chuyển từ {$from} sang {$to->value}.",
            ]);
        }

        return DB::transaction(function () use ($qualification, $to, $data) {
            $payload = array_merge($data, ['status' => $to->value]);

            if ($to === ContactQualificationStatus::Contacting) {
                $payload['last_contacted_at'] = now();
            }

            if ($to === ContactQualificationStatus::Qualified) {
                $payload['qualified_at'] = now();
            }

            if ($to === ContactQualificationStatus::Converted) {
                $payload['converted_at'] = now();
            }

            $qualification->update($payload);

            return $qualification->fresh();
        });
    }
}
```

Lưu ý: `converted` chỉ được gọi tự động ở Phase 8.

## 5.3. Sửa ContactQualificationResource

### File

```text
app/Filament/Resources/ContactQualificationResource.php
```

Xóa các closure update trực tiếp:

```php
$record->update(['status' => ...]);
```

Thay bằng gọi Workflow Service.

Actions chuẩn:

```text
Bắt đầu liên hệ
Ghi nhận tương tác
Đặt lịch theo dõi
Đánh dấu đủ điều kiện
Đánh dấu không đủ điều kiện
Đánh dấu trùng/Spam
```

Không cho nhân viên bấm `converted` thủ công.

## 5.4. UI Lead detail

Tabs:

```text
Tổng quan
Thông tin Contact
Thông tin Company
Lịch sử tương tác
Đánh giá
Cơ hội kinh doanh
Nguồn Submission
```

## 5.5. Reminder jobs

Tạo:

```text
app/Jobs/Crm/SendLeadFollowUpReminderJob.php
app/Jobs/Crm/MarkStaleLeadsForReviewJob.php
```

Đăng ký schedule trong:

```text
routes/console.php
```

hoặc cấu trúc scheduler hiện tại:

```php
Schedule::job(new MarkStaleLeadsForReviewJob)
    ->dailyAt('08:00')
    ->withoutOverlapping();
```

Không tự động đổi `unqualified`; chỉ nhắc hoặc đưa vào review.

## 5.6. Test Phase 5

```text
tests/Unit/Crm/ContactQualificationWorkflowServiceTest.php
tests/Feature/Crm/LeadActivityTest.php
```

Case:

```text
- new không thể nhảy trực tiếp qualified.
- assigned → contacting hợp lệ.
- contacting → qualified hợp lệ.
- qualified không tự tạo Customer.
- converted không thể gọi thủ công qua UI staff.
- interaction trước bán được lưu ở lead_activities.
```

Chạy:

```bash
php artisan test --filter=ContactQualificationWorkflow
php artisan test --filter=LeadActivity
```

## Tiêu chí nghiệm thu Phase 5

- Trạng thái có transition rõ ràng.
- Mọi cuộc gọi trước bán nằm trong Lead Activity.
- Customer Care chưa nhìn thấy Lead.


---

# PHASE 6 — THÊM SALES OPPORTUNITY/CƠ HỘI KINH DOANH

## Mục tiêu

- Báo giá không đi trực tiếp từ Qualification sang Customer.
- Một Lead đủ điều kiện tạo một Opportunity.
- Company có thể có nhiều Opportunity.
- Opportunity có owner riêng nhưng vẫn giữ Account Owner của Company.

## 6.1. Tạo enum Opportunity

### File mới

```text
app/Enums/Sales/OpportunityStage.php
```

```php
<?php

namespace App\Enums\Sales;

enum OpportunityStage: string
{
    case Discovery = 'discovery';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Discovery => 'Tìm hiểu nhu cầu',
            self::Qualified => 'Đủ điều kiện',
            self::Proposal => 'Đã gửi báo giá',
            self::Negotiation => 'Đang thương lượng',
            self::Won => 'Thành công',
            self::Lost => 'Thất bại',
            self::Cancelled => 'Đã hủy',
        };
    }
}
```

## 6.2. Migration

```bash
php artisan make:migration create_sales_opportunities_table
php artisan make:migration create_opportunity_contacts_table
php artisan make:migration create_opportunity_interactions_table
```

### `sales_opportunities`

```php
Schema::create('sales_opportunities', function (Blueprint $table): void {
    $table->id();
    $table->string('opportunity_code')->unique();
    $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
    $table->foreignId('primary_contact_id')->constrained('contacts')->cascadeOnDelete();
    $table->foreignId('assigned_staff_id')->nullable()->constrained('staff')->nullOnDelete();
    $table->foreignId('price_book_id')->nullable()->constrained('price_books')->nullOnDelete();
    $table->string('title');
    $table->string('service_interest')->nullable();
    $table->string('stage')->default('qualified')->index();
    $table->decimal('estimated_value', 18, 2)->nullable();
    $table->unsignedTinyInteger('probability')->default(50);
    $table->date('expected_close_date')->nullable();
    $table->timestamp('won_at')->nullable();
    $table->timestamp('lost_at')->nullable();
    $table->text('lost_reason')->nullable();
    $table->json('metadata')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['company_id', 'stage']);
    $table->index(['assigned_staff_id', 'stage']);
});
```

### `opportunity_contacts`

```php
Schema::create('opportunity_contacts', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('opportunity_id')->constrained('sales_opportunities')->cascadeOnDelete();
    $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
    $table->string('role')->default('other');
    $table->boolean('is_primary')->default(false);
    $table->timestamps();

    $table->unique(['opportunity_id', 'contact_id']);
});
```

### `opportunity_interactions`

```php
Schema::create('opportunity_interactions', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('opportunity_id')->constrained('sales_opportunities')->cascadeOnDelete();
    $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
    $table->string('interaction_type');
    $table->string('subject');
    $table->text('content')->nullable();
    $table->string('outcome')->nullable();
    $table->timestamp('interaction_at')->nullable();
    $table->timestamp('next_follow_up_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
```

## 6.3. Models

Tạo:

```text
app/Models/Sales/Opportunity.php
app/Models/Sales/OpportunityContact.php
app/Models/Sales/OpportunityInteraction.php
```

### Quan hệ chính Opportunity

```php
public function lead(): BelongsTo
{
    return $this->belongsTo(Lead::class);
}

public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}

public function primaryContact(): BelongsTo
{
    return $this->belongsTo(Contact::class, 'primary_contact_id');
}

public function assignedStaff(): BelongsTo
{
    return $this->belongsTo(Staff::class, 'assigned_staff_id');
}

public function quotations(): HasMany
{
    return $this->hasMany(Quotation::class);
}
```

## 6.4. Opportunity Workflow Service

### File mới

```text
app/Services/Sales/OpportunityWorkflowService.php
```

Allowed transitions:

```php
private const ALLOWED = [
    'discovery' => ['qualified', 'lost', 'cancelled'],
    'qualified' => ['proposal', 'lost', 'cancelled'],
    'proposal' => ['negotiation', 'won', 'lost', 'cancelled'],
    'negotiation' => ['proposal', 'won', 'lost', 'cancelled'],
    'won' => [],
    'lost' => [],
    'cancelled' => [],
];
```

`Won` không được bấm thủ công bởi Sales Staff nếu `CUSTOMER_ON_PAID_ONLY=true`. Nó chỉ được gọi từ Payment Service.

## 6.5. Tạo Opportunity từ Qualified Lead

### File mới

```text
app/Services/Sales/OpportunityCreationService.php
```

```php
public function createFromQualifiedLead(
    Lead $lead,
    array $data,
    int $createdByUserId,
): Opportunity {
    $lead->loadMissing('qualification', 'contact', 'company');

    if (($lead->qualification?->status?->value ?? $lead->qualification?->status) !== 'qualified') {
        throw ValidationException::withMessages([
            'lead' => 'Lead phải ở trạng thái Đủ điều kiện trước khi tạo Cơ hội.',
        ]);
    }

    return DB::transaction(function () use ($lead, $data, $createdByUserId) {
        $opportunity = Opportunity::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'opportunity_code' => app(OpportunityCodeGenerator::class)->next(),
                'company_id' => $lead->company_id,
                'primary_contact_id' => $lead->contact_id,
                'assigned_staff_id' => $data['assigned_staff_id'] ?? $lead->assigned_staff_id,
                'title' => $data['title'] ?? $lead->title,
                'service_interest' => $data['service_interest'] ?? $lead->service_interest,
                'stage' => 'qualified',
                'estimated_value' => $data['estimated_value'] ?? $lead->estimated_value,
                'probability' => $data['probability'] ?? 50,
                'expected_close_date' => $data['expected_close_date'] ?? null,
                'created_by' => $createdByUserId,
            ]
        );

        $opportunity->contacts()->syncWithoutDetaching([
            $lead->contact_id => [
                'role' => 'primary_contact',
                'is_primary' => true,
            ],
        ]);

        $lead->update([
            'intake_status' => 'converted_to_opportunity',
            'converted_to_opportunity_at' => now(),
        ]);

        return $opportunity;
    });
}
```

## 6.6. Opportunity Owner và Account Owner

Quy tắc:

```text
Company Account Owner
→ người chịu trách nhiệm quan hệ tổng thể

Opportunity Owner
→ người xử lý một cơ hội cụ thể
```

Cho phép Opportunity Owner khác Account Owner khi:

- Dịch vụ khác chuyên môn.
- Có phê duyệt của Sales Manager hoặc CS Manager.
- Account Owner vẫn thấy Opportunity và được ghi nhận collaborator.

## 6.7. Filament OpportunityResource

Tạo:

```text
app/Filament/Resources/Sales/OpportunityResource.php
app/Filament/Resources/Sales/OpportunityResource/Pages/*
app/Filament/Resources/Sales/OpportunityResource/RelationManagers/*
```

### Giao diện list

```text
Mã cơ hội
Tiêu đề
Doanh nghiệp
Liên hệ chính
Owner
Giai đoạn
Giá trị dự kiến
Xác suất
Ngày dự kiến chốt
Báo giá mới nhất
```

### Tabs/Kanban

```text
Tìm hiểu nhu cầu
Đủ điều kiện
Đã gửi báo giá
Đang thương lượng
Thành công
Thất bại
```

### Actions

```text
Ghi nhận hoạt động
Thêm người liên hệ
Tạo báo giá
Chuyển giai đoạn
Đánh dấu thất bại
```

Không có action “Tạo Customer”.

## 6.8. Test Phase 6

```text
tests/Feature/Sales/OpportunityCreationTest.php
tests/Unit/Sales/OpportunityWorkflowServiceTest.php
tests/Feature/Sales/OpportunityAuthorizationTest.php
```

Case:

```text
- Lead chưa qualified không tạo được Opportunity.
- Một Lead chỉ tạo một Opportunity mặc định.
- Opportunity owner có thể khác Account Owner nhưng không thay Account Owner.
- Sales Staff chỉ thấy Opportunity được giao.
- Opportunity chưa paid không được Won bằng action thường.
```

Chạy:

```bash
php artisan migrate
php artisan test --filter=Opportunity
./vendor/bin/pint
```

## Tiêu chí nghiệm thu Phase 6

- Qualified Lead có thể tạo Opportunity.
- Customer vẫn chưa được tạo.
- Báo giá sắp được chuyển sang Opportunity trong Phase 7.

---

# PHASE 7 — CHUYỂN QUOTATION TỪ CUSTOMER SANG OPPORTUNITY

## Mục tiêu

- Báo giá được tạo cho Opportunity.
- `customer_id` nullable cho đến khi thanh toán.
- Public view, PDF, email và jobs vẫn hoạt động khi chưa có Customer.

## 7.1. Migration Quotation

Chạy:

```bash
php artisan make:migration attach_quotations_to_opportunities_and_make_customer_nullable --table=quotations
```

### Migration đề xuất

```php
public function up(): void
{
    Schema::table('quotations', function (Blueprint $table): void {
        $table->foreignId('opportunity_id')
            ->nullable()
            ->after('quotation_code')
            ->constrained('sales_opportunities')
            ->nullOnDelete();

        $table->foreignId('company_id')
            ->nullable()
            ->after('opportunity_id')
            ->constrained('companies')
            ->nullOnDelete();

        $table->foreignId('contact_id')
            ->nullable()
            ->after('company_id')
            ->constrained('contacts')
            ->nullOnDelete();
    });

    Schema::table('quotations', function (Blueprint $table): void {
        $table->dropForeign(['customer_id']);
    });

    Schema::table('quotations', function (Blueprint $table): void {
        $table->foreignId('customer_id')->nullable()->change();
        $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
    });
}
```

Nếu MySQL/Laravel báo lỗi khi `change()`, cài DBAL trong môi trường phát triển:

```bash
composer require doctrine/dbal --dev
```

Không xóa dữ liệu customer_id cũ.

## 7.2. Sửa Quotation model

### File

```text
app/Models/Sales/Quotation.php
```

Thêm `$fillable`:

```php
'opportunity_id',
'company_id',
'contact_id',
```

Thêm relation:

```php
public function opportunity(): BelongsTo
{
    return $this->belongsTo(Opportunity::class);
}

public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}

public function contact(): BelongsTo
{
    return $this->belongsTo(Contact::class);
}
```

## 7.3. Tạo accessor party để tránh null Customer

Trong `Quotation.php`:

```php
public function getPartyDisplayNameAttribute(): string
{
    return $this->customer?->display_name
        ?? $this->company?->legal_name
        ?? $this->contact?->full_name
        ?? $this->customer_name_snapshot
        ?? 'Khách hàng tiềm năng';
}

public function getPartyEmailAttribute(): ?string
{
    return $this->customer?->email
        ?? $this->contact?->personalProfile?->email
        ?? $this->contact?->businessProfile?->business_email
        ?? $this->customer_email_snapshot;
}

public function getPartyPhoneAttribute(): ?string
{
    return $this->customer?->phone
        ?? $this->contact?->personalProfile?->phone
        ?? $this->contact?->businessProfile?->business_phone
        ?? $this->customer_phone_snapshot;
}
```

Tên cột snapshot phải điều chỉnh theo schema thực tế của repo. Không tạo cột trùng nếu đã có.

## 7.4. Sửa QuotationCreationService

### File

```text
app/Services/Sales/QuotationCreationService.php
```

Tạo method mới:

```php
public function createForOpportunity(
    Opportunity $opportunity,
    User $user,
    array $data,
): Quotation {
    $opportunity->loadMissing('company', 'primaryContact', 'assignedStaff');

    if (! in_array($opportunity->stage->value ?? $opportunity->stage, [
        'qualified',
        'proposal',
        'negotiation',
    ], true)) {
        throw ValidationException::withMessages([
            'opportunity_id' => 'Cơ hội không ở trạng thái cho phép tạo báo giá.',
        ]);
    }

    return DB::transaction(function () use ($opportunity, $user, $data) {
        $quotation = Quotation::query()->create([
            'quotation_code' => app(QuotationCodeGenerator::class)->next(),
            'opportunity_id' => $opportunity->id,
            'company_id' => $opportunity->company_id,
            'contact_id' => $opportunity->primary_contact_id,
            'customer_id' => null,
            'assigned_staff_id' => $opportunity->assigned_staff_id,
            'price_book_id' => $data['price_book_id'],
            'status' => 'draft',
            'payment_status' => 'unpaid',
            // giữ các snapshot và field hiện có
            'created_by' => $user->id,
        ]);

        // Giữ nguyên logic item/pricing hiện có.

        return $quotation;
    });
}
```

### Legacy wrapper

Giữ method `create(Customer $customer, ...)` trong một release và đánh dấu:

```php
/** @deprecated Use createForOpportunity() */
```

Không xóa ngay vì jobs/tests/callers cũ có thể còn dùng.

## 7.5. Sửa QuotationResource

### File

```text
app/Filament/Resources/Sales/QuotationResource.php
```

Thay Select bắt buộc `customer_id` bằng:

```php
Select::make('opportunity_id')
    ->label('Cơ hội kinh doanh')
    ->relationship(
        name: 'opportunity',
        titleAttribute: 'title',
        modifyQueryUsing: fn ($query) => $query
            ->whereIn('stage', ['qualified', 'proposal', 'negotiation'])
    )
    ->searchable()
    ->preload()
    ->required()
    ->live();
```

Khi chọn Opportunity:

- Hiển thị Company.
- Hiển thị primary Contact.
- Chọn PriceBook được phép.
- Không cho sửa customer_id thủ công.

## 7.6. Audit mọi code đang gọi `$quotation->customer`

Chạy:

```bash
rg -n "quotation->customer|customer_id|whereIn\('customer_id'|where\('customer_id'" app tests resources
```

Các file bắt buộc rà soát:

```text
app/Services/Sales/QuotationMailService.php
app/Services/Sales/QuotationPdfService.php
app/Services/Sales/QuotationTemplateRenderer.php
app/Services/Sales/QuotationPublicAccessService.php
app/Services/Sales/QuotationReminderService.php
app/Services/Sales/QuotationInteractionService.php
app/Services/Sales/QuotationEmailCrmSyncer.php
app/Jobs/Sales/*
resources/views/sales/public/show.blade.php
resources/views/sales/quotation-pdf.blade.php
resources/views/sales/emails/*
```

Thay việc lấy trực tiếp Customer bằng:

```php
$quotation->party_display_name
$quotation->party_email
$quotation->party_phone
```

## 7.7. CRM sync trước bán

`QuotationEmailCrmSyncer` và `QuotationInteractionService` không được ghi `CustomerInteraction` khi chưa có Customer.

Quy tắc:

```php
if ($quotation->customer_id) {
    // CustomerInteraction
} else {
    // OpportunityInteraction
}
```

## 7.8. Sửa Opportunity stage khi báo giá

- Khi gửi báo giá lần đầu: Opportunity → `proposal`.
- Khi báo giá viewed/accepted/revision requested: Opportunity vẫn `proposal` hoặc `negotiation`.
- Không chuyển `won` khi accepted.

Đặt logic trong:

```text
app/Services/Sales/OpportunityWorkflowService.php
```

không đặt rải rác trong Controller/Job.

## 7.9. Xóa báo giá khỏi Customer Care

### File sửa

```text
app/Filament/Pages/CustomerCarePage.php
resources/views/filament/pages/customer-care.blade.php
```

Xóa hoặc thay:

```text
getQuotationCreateUrl()
Nút tạo báo giá
pending_quotation dành cho pre-sale
unpaid pre-sale
```

Customer Care có thể vẫn hiển thị **lịch sử báo giá đã thuộc Customer**, nhưng không phải nơi tạo báo giá mới cho prospect.

## 7.10. Test Phase 7

```text
tests/Feature/Sales/CreateQuotationForOpportunityTest.php
tests/Feature/Sales/QuotationPublicWithoutCustomerTest.php
tests/Feature/Sales/QuotationMailWithoutCustomerTest.php
tests/Feature/Sales/QuotationPdfWithoutCustomerTest.php
```

Case:

```text
- Opportunity qualified tạo được Quotation với customer_id null.
- PDF render không lỗi null Customer.
- Email lấy đúng email Contact.
- Public link xem được.
- Accepted chưa tạo Customer.
- Customer Care không còn nút tạo báo giá pre-sale.
```

Chạy:

```bash
php artisan migrate
php artisan test --filter=Quotation
php artisan queue:work --once
./vendor/bin/pint
```

## Tiêu chí nghiệm thu Phase 7

```text
Opportunity
└── Quotation
      customer_id = null
```

Báo giá hoạt động đầy đủ trước khi có Customer.

---

# PHASE 8 — THANH TOÁN VÀ CHUYỂN CUSTOMER

## Mục tiêu

- Chỉ thanh toán thành công mới tạo Customer.
- Conversion idempotent: job chạy lại không tạo Customer trùng.
- Cập nhật đồng bộ Company, Opportunity, Qualification, Quotation và Customer.

## 8.1. Tạo Action mới

### File mới

```text
app/Actions/Sales/ConvertWonOpportunityToCustomerAction.php
```

Không tiếp tục mở rộng action cũ theo Contact. Conversion phải bắt đầu từ Opportunity/Payment.

```php
<?php

namespace App\Actions\Sales;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\Customer;
use App\Models\Sales\Opportunity;
use Illuminate\Support\Facades\DB;

final class ConvertWonOpportunityToCustomerAction
{
    public function execute(Opportunity $opportunity, ?int $userId = null): Customer
    {
        return DB::transaction(function () use ($opportunity, $userId) {
            $opportunity = Opportunity::query()
                ->with(['lead.qualification', 'company', 'primaryContact'])
                ->lockForUpdate()
                ->findOrFail($opportunity->id);

            $existingCustomer = Customer::query()
                ->where('converted_from_opportunity_id', $opportunity->id)
                ->first();

            if ($existingCustomer) {
                return $existingCustomer;
            }

            $customer = Customer::query()->create([
                // map dữ liệu theo customer_type hiện có
                'customer_type' => $opportunity->company_id ? 'business' : 'personal',
                'display_name' => $opportunity->company?->legal_name
                    ?? $opportunity->primaryContact->full_name,
                'email' => $opportunity->primaryContact->businessProfile?->business_email
                    ?? $opportunity->primaryContact->personalProfile?->email,
                'phone' => $opportunity->primaryContact->businessProfile?->business_phone
                    ?? $opportunity->primaryContact->personalProfile?->phone,
                'status' => 'active',
                'lifecycle_stage' => 'new_customer',
                'converted_from_contact_id' => $opportunity->primary_contact_id,
                'converted_from_opportunity_id' => $opportunity->id,
                'created_by' => $userId,
            ]);

            $opportunity->update([
                'stage' => OpportunityStage::Won->value,
                'won_at' => now(),
            ]);

            $opportunity->lead?->qualification?->update([
                'status' => ContactQualificationStatus::Converted->value,
                'qualification_result' => QualificationResult::Purchased->value,
                'converted_customer_id' => $customer->id,
                'converted_at' => now(),
            ]);

            $opportunity->company?->update([
                'lifecycle_stage' => CompanyLifecycleStage::Customer->value,
            ]);

            return $customer;
        });
    }
}
```

Tên các field `converted_from_*` phải được thêm bằng migration hoặc điều chỉnh theo schema hiện tại.

## 8.2. Migration trace conversion

```bash
php artisan make:migration add_opportunity_conversion_fields_to_customers_table --table=customers
```

```php
Schema::table('customers', function (Blueprint $table): void {
    $table->foreignId('converted_from_opportunity_id')
        ->nullable()
        ->unique()
        ->constrained('sales_opportunities')
        ->nullOnDelete();

    $table->foreignId('company_id')
        ->nullable()
        ->constrained('companies')
        ->nullOnDelete();
});
```

Nếu Customer hiện đã có `converted_from_contact_id`, giữ nguyên.

## 8.3. Sửa QuotationPaymentService

### File

```text
app/Services/Sales/QuotationPaymentService.php
```

Khi payment thành `paid`:

```php
public function handlePaid(Quotation $quotation, ?int $verifiedByUserId = null): Quotation
{
    return DB::transaction(function () use ($quotation, $verifiedByUserId) {
        $quotation = Quotation::query()
            ->with('opportunity')
            ->lockForUpdate()
            ->findOrFail($quotation->id);

        if (($quotation->payment_status->value ?? $quotation->payment_status) === 'paid'
            && $quotation->customer_id !== null) {
            return $quotation;
        }

        $quotation->update([
            'payment_status' => 'paid',
            'paid_at' => $quotation->paid_at ?? now(),
            'payment_verified_by_user_id' => $verifiedByUserId,
        ]);

        if (! $quotation->opportunity) {
            // Legacy quotation: giữ xử lý Customer cũ.
            return $this->handleLegacyPaidQuotation($quotation);
        }

        $customer = app(ConvertWonOpportunityToCustomerAction::class)
            ->execute($quotation->opportunity, $verifiedByUserId);

        $quotation->update([
            'customer_id' => $customer->id,
        ]);

        DB::afterCommit(function () use ($quotation): void {
            SendPaymentConfirmedNotificationJob::dispatch($quotation->id);
        });

        return $quotation->fresh(['customer', 'opportunity']);
    });
}
```

## 8.4. Sửa ContactQualification::isConvertible

### File

```text
app/Models/Crm/ContactQualification.php
```

Trong giai đoạn mới, không dùng để hiện action chuyển thủ công.

Sửa thành:

```php
public function isConvertible(): bool
{
    return ($this->status?->value ?? $this->status) === 'qualified'
        && ($this->qualification_result?->value ?? $this->qualification_result) === 'purchased';
}
```

Hoặc tốt hơn: giữ method chỉ cho internal action, xóa action UI thủ công.

## 8.5. Sửa action cũ

### File hiện tại

```text
app/Actions/Contacts/ConvertContactToCustomerAction.php
```

Trong Phase 8:

- Đánh dấu deprecated.
- Không cho `confirmed_need` tạo Potential Customer.
- Chỉ giữ wrapper gọi action mới cho legacy nếu tìm được Opportunity đã paid.

Không xóa file ngay.

## 8.6. Customer Assignment sau conversion

Sau khi tạo Customer:

- Nếu Company có Account Owner: tạo `CustomerAssignment` cho Account Owner.
- Nếu không: dùng Opportunity Owner.
- Ghi reason `converted_from_paid_opportunity`.

Tái sử dụng logic trong:

```text
app/Services/Crm/CustomerDistributionService.php
```

Không xóa service này. Nó trở thành logic hậu mãi sau conversion.

## 8.7. Customer Interaction đầu tiên

Tạo một CustomerInteraction:

```text
Loại: system
Chủ đề: Chuyển thành khách hàng
Nội dung: Thanh toán báo giá Q-... thành công
```

Opportunity interactions trước đó vẫn giữ ở pre-sale; có thể hiển thị liên kết trong timeline Customer nhưng không cần copy toàn bộ.

## 8.8. Test Phase 8

```text
tests/Feature/Sales/PaidQuotationConvertsCustomerTest.php
tests/Feature/Sales/AcceptedQuotationDoesNotConvertCustomerTest.php
tests/Feature/Sales/PaymentConversionIdempotencyTest.php
tests/Feature/Sales/PaymentConversionRollbackTest.php
```

Case:

```text
- Viewed/Accepted/RevisionRequested không tạo Customer.
- Paid tạo đúng một Customer.
- Chạy paid handler hai lần vẫn một Customer.
- Opportunity chuyển Won.
- Qualification chuyển Converted + Purchased.
- Company chuyển lifecycle Customer.
- Quotation được gán customer_id.
- Nếu lỗi giữa chừng, transaction rollback toàn bộ.
```

Chạy:

```bash
php artisan migrate
php artisan test --filter=Payment
php artisan queue:work --once
```

## Tiêu chí nghiệm thu Phase 8

```text
Trước paid:
customer_id = null
Opportunity = proposal/negotiation
Qualification = qualified

Sau paid:
customer_id != null
Opportunity = won
Qualification = converted + purchased
Company = customer
Customer Care nhìn thấy bản ghi mới
```

---

# PHASE 9 — ROLE, POLICY, MENU VÀ GIAO DIỆN CHUẨN

## Mục tiêu

- Người dùng chỉ nhìn thấy đúng chức năng theo vai trò.
- Không đặt authorization rải rác chỉ trong Resource.
- Menu phản ánh đúng nghiệp vụ Marketing → CRM → Sales → Customer Care.

## 9.1. Sửa User model

### File

```text
app/Models/User.php
```

Thêm helpers:

```php
public function isSalesManager(): bool
{
    return in_array($this->role, ['admin', 'sales_manager'], true);
}

public function isSalesStaff(): bool
{
    return in_array($this->role, ['admin', 'sales_manager', 'sales_staff'], true);
}

public function isFinanceStaff(): bool
{
    return in_array($this->role, ['admin', 'finance_staff'], true);
}
```

Sửa `canAccessPanel()`/`isAnyMarketingUser()` để các role mới vào được panel:

```php
public function canAccessPanel(Panel $panel): bool
{
    return in_array($this->role, [
        'admin',
        'marketing_manager',
        'marketing_staff',
        'customer_service_manager',
        'customer_service_staff',
        'sales_manager',
        'sales_staff',
        'finance_staff',
        'viewer',
    ], true);
}
```

Tên `isAnyMarketingUser()` không còn phù hợp. Tạo method mới:

```php
public function canAccessBusinessPanel(): bool
```

Giữ method cũ làm wrapper một release để tránh hỏng code.

## 9.2. Policies mới

Tạo:

```text
app/Policies/CompanyPolicy.php
app/Policies/LeadPolicy.php
app/Policies/Sales/OpportunityPolicy.php
```

Sửa:

```text
app/Policies/Sales/QuotationPolicy.php
app/Policies/CustomerPolicy.php
```

Nguyên tắc:

- Marketing Staff: xem Submission/Lead nguồn, không đổi Qualification.
- CS Staff: chỉ Lead được giao.
- CS Manager: phân phối Lead trong phạm vi quản lý.
- Sales Staff: chỉ Opportunity được giao và Quotation liên quan.
- Sales Manager: xem/phân phối/duyệt trong phạm vi Sales.
- Finance: chỉ xác minh Payment.
- Customer Care: chỉ Customer được phân công.
- Admin: tất cả.

## 9.3. Navigation

### File

```text
app/Providers/Filament/AdminPanelProvider.php
```

Giữ các group hiện có nhưng chuẩn hóa nội dung:

```text
Email Marketing
Marketing
CRM
Kinh doanh
Chăm sóc khách hàng
System
Configuration
```

Nếu không muốn thêm group mới, Customer Care có thể nằm trong CRM nhưng phải đặt sau Customer.

### Menu đề xuất

#### Marketing

```text
Chiến dịch quảng cáo
Trang đích
Mẫu biểu mẫu
Lượt gửi biểu mẫu
Danh sách liên hệ
Phân khúc
Thẻ
```

#### CRM

```text
Doanh nghiệp
Liên hệ
Yêu cầu tư vấn
Quy trình đánh giá
Kiểm tra trùng doanh nghiệp
```

#### Kinh doanh

```text
Cơ hội kinh doanh
Báo giá
Phê duyệt báo giá
Bảng giá
Theo dõi thanh toán
Tài khoản ngân hàng
Gói dịch vụ
```

#### Chăm sóc khách hàng

```text
Khách hàng
Chăm sóc khách hàng
Phiên phân phối khách hàng
```

## 9.4. UI chuẩn theo vai trò

### Marketing Submission

- Chỉ xem dữ liệu gửi.
- Có link tới Lead.
- Không phân công trực tiếp.
- Không đổi status CRM.

### Lead Workspace

- Header: Contact, Company, nguồn, assigned staff.
- Actions theo workflow.
- Có timeline hoạt động trước bán.
- Có action tạo Opportunity khi qualified.

### Company Workspace

- Tổng quan Account Owner.
- Danh sách contacts và vai trò quyết định.
- Leads đang mở.
- Opportunities đang mở.
- Duplicate candidates.

### Opportunity Workspace

- Pipeline stage.
- Participants.
- Activities.
- Quotations.
- Không có Customer trước paid.

### Customer Care Workspace

- Chỉ Customer.
- Không tạo báo giá prospect.
- Có lịch sử đơn hàng/thanh toán/báo giá đã chuyển.
- Email/call/follow-up sau bán.

## 9.5. Test authorization

```text
tests/Feature/Authorization/CompanyPolicyTest.php
tests/Feature/Authorization/LeadPolicyTest.php
tests/Feature/Authorization/OpportunityPolicyTest.php
tests/Feature/Authorization/QuotationPolicyTest.php
tests/Feature/Authorization/CustomerCareAccessTest.php
```

Chạy:

```bash
php artisan test --filter=Policy
php artisan test --filter=Authorization
```

## Tiêu chí nghiệm thu Phase 9

Mỗi role chỉ thấy đúng nhóm chức năng. Không thể bypass bằng URL trực tiếp.

---

# PHASE 10 — BACKFILL, JOBS, BÁO CÁO VÀ XÓA LEGACY

## Mục tiêu

- Di chuyển dữ liệu cũ an toàn.
- Đảm bảo jobs không dùng model legacy sai.
- Xóa các field/file dư sau một release ổn định.

## 10.1. Commands backfill bắt buộc

```text
crm:backfill-companies
crm:backfill-leads
sales:backfill-opportunities
sales:link-legacy-quotations
crm:validate-business-flow
```

### `sales:backfill-opportunities`

Quy tắc:

- Qualification qualified/confirmed_need cũ → tạo Opportunity.
- Customer cũ có Quotation → tạo Opportunity legacy và gắn Quotation.
- Không đổi Customer cũ thành Prospect.

### `sales:link-legacy-quotations`

- Tạo Opportunity legacy cho Quotation cũ nếu chưa có.
- Giữ customer_id.
- Đặt Opportunity stage theo Quotation/Payment:
  - paid → won
  - accepted unpaid → negotiation
  - sent/viewed → proposal
  - draft/approved → qualified

## 10.2. Validation command

Tạo command kiểm tra:

```text
- Submission processed nhưng không có Lead.
- Lead không có Qualification.
- Business Lead không có Company.
- Company có nhiều active Account Owner.
- Quotation mới không có Opportunity.
- Paid quotation không có Customer.
- Converted qualification không có converted_customer_id.
```

Chạy:

```bash
php artisan crm:validate-business-flow
```

Command phải trả exit code khác 0 nếu có lỗi nghiêm trọng.

## 10.3. Jobs cần giữ và sửa

### Giữ nguyên nghiệp vụ Marketing

```text
app/Jobs/Marketing/PrepareCampaignRecipientsJob.php
app/Jobs/Marketing/ProcessScheduledCampaignsJob.php
app/Jobs/Marketing/SendCampaignEmailJob.php
```

Không liên quan trực tiếp luồng CRM mới.

### Sửa CRM Job

```text
app/Jobs/Crm/VerifyBusinessTaxCodeJob.php
```

Target chính chuyển sang Company.

### Sales Jobs

Rà soát toàn bộ:

```text
app/Jobs/Sales/*
```

Quy tắc:

- Job chỉ nhận ID, không serialize model graph lớn.
- Reload Quotation với Opportunity/Company/Contact/Customer.
- Không giả định customer_id luôn tồn tại.
- Dispatch sau commit.

## 10.4. Jobs mới gợi ý

```text
app/Jobs/Crm/SendLeadFollowUpReminderJob.php
app/Jobs/Crm/MarkStaleLeadsForReviewJob.php
app/Jobs/Sales/SendOpportunityFollowUpReminderJob.php
app/Jobs/Sales/RecalculateOpportunityProbabilityJob.php
```

Không dùng Job để tự đánh dấu `won` hoặc `unqualified`.

## 10.5. Báo cáo mới

### Marketing

```text
Submission theo Landing Page
Submission theo loại Cá nhân/Doanh nghiệp
Tỷ lệ Submission → Lead hợp lệ
```

### CRM

```text
Lead chưa phân phối
Lead theo Account Owner
Lead theo Company
Thời gian phản hồi đầu tiên
Tỷ lệ Qualified
```

### Sales

```text
Opportunity theo stage
Pipeline value
Win rate
Quotation conversion
Thời gian Qualified → Paid
```

### Customer Care

```text
Customer mới từ Opportunity
Onboarding
Retained/At risk
```

## 10.6. Legacy cleanup — chỉ làm sau khi ổn định

### Migration xóa field dư

Sau ít nhất một release ổn định, tạo migration mới để xóa:

```text
landing_page_submissions.qualification_status
landing_page_submissions.assigned_staff_id
contact_qualifications.contact_id
contact_qualifications.assigned_staff_id
```

Chỉ xóa khi UI/service đã đọc từ:

```text
Lead.assigned_staff_id
ContactQualification.lead_id
```

### BusinessContactProfile

Không xóa cả model. Giữ thông tin người đại diện liên hệ như:

```text
contact_position
business_email
business_phone
```

Chuyển dữ liệu doanh nghiệp sang Company và sau đó xóa dần:

```text
company_name
tax_code
company_address
industry
```

Nếu còn cần compatibility, tạo accessor đọc từ Company trước khi xóa cột.

### Customer grouping legacy

Sau khi Company ổn định:

- Ngừng nhóm Customer bằng `company_name`.
- Ngừng dùng `company_group_id` nếu mục đích chỉ là nhóm tên công ty.
- Dùng `customers.company_id`.

### File deprecated có thể xóa

Sau khi không còn reference:

```text
app/Actions/Contacts/ConvertContactToCustomerAction.php
```

thay bằng:

```text
app/Actions/Sales/ConvertWonOpportunityToCustomerAction.php
```

Có thể xóa/ẩn:

```text
app/Filament/Resources/ContactQualificationResource.php
```

nếu `LeadResource` đã thay hoàn toàn.

### Không xóa

```text
CustomerDistributionService
CustomerAssignment
CustomerInteraction
CustomerCareService
```

Đây là luồng hậu mãi và vẫn cần sau conversion.

## 10.7. Tìm reference trước khi xóa

```bash
rg -n "qualification_status" app tests resources database
rg -n "assigned_staff_id" app tests resources database
rg -n "ConvertContactToCustomerAction" app tests
rg -n "company_group_id" app tests resources
rg -n "contact->qualification|qualification\(\)" app tests
```

Không xóa nếu còn reference runtime.

## 10.8. Full test và quality gate

```bash
php artisan optimize:clear
php artisan migrate:status
php artisan route:list
php artisan test
./vendor/bin/pint --test
npm run build
php artisan queue:work --once
php artisan crm:validate-business-flow
```

Nếu có static analysis trong repo:

```bash
./vendor/bin/phpstan analyse
```

## Tiêu chí nghiệm thu Phase 10

- Không còn dữ liệu orphan.
- Không còn code mới phụ thuộc cột legacy.
- Full test pass.
- Queue jobs chạy được khi customer_id null trước payment.


---

# 6. DANH SÁCH FILE TỔNG HỢP

## 6.1. File tạo mới

### Enums

```text
app/Enums/Crm/CompanyLifecycleStage.php
app/Enums/Crm/CompanyContactDecisionRole.php
app/Enums/Crm/LeadIntakeStatus.php
app/Enums/Sales/OpportunityStage.php
```

### Models

```text
app/Models/Crm/Company.php
app/Models/Crm/CompanyContact.php
app/Models/Crm/CompanyAssignment.php
app/Models/Crm/CompanyMatchCandidate.php
app/Models/Crm/Lead.php
app/Models/Crm/LeadActivity.php
app/Models/Sales/Opportunity.php
app/Models/Sales/OpportunityContact.php
app/Models/Sales/OpportunityInteraction.php
```

### Services/Actions

```text
app/Services/Crm/CompanyCodeGenerator.php
app/Services/Crm/CompanyResolutionService.php
app/Services/Crm/CompanyOwnershipService.php
app/Services/Crm/LeadCodeGenerator.php
app/Services/Crm/LeadCreationService.php
app/Services/Crm/LeadAssignmentService.php
app/Services/Crm/ContactQualificationWorkflowService.php
app/Services/Sales/OpportunityCodeGenerator.php
app/Services/Sales/OpportunityCreationService.php
app/Services/Sales/OpportunityWorkflowService.php
app/Actions/Sales/ConvertWonOpportunityToCustomerAction.php
app/Data/Crm/CompanyResolutionResult.php
```

### Filament

```text
app/Filament/Resources/CompanyResource.php
app/Filament/Resources/CompanyResource/Pages/*
app/Filament/Resources/CompanyResource/RelationManagers/*
app/Filament/Resources/CompanyMatchCandidateResource.php
app/Filament/Resources/LeadResource.php
app/Filament/Resources/LeadResource/Pages/*
app/Filament/Resources/LeadResource/RelationManagers/*
app/Filament/Resources/Sales/OpportunityResource.php
app/Filament/Resources/Sales/OpportunityResource/Pages/*
app/Filament/Resources/Sales/OpportunityResource/RelationManagers/*
```

### Policies

```text
app/Policies/CompanyPolicy.php
app/Policies/LeadPolicy.php
app/Policies/Sales/OpportunityPolicy.php
```

### Jobs/Events

```text
app/Jobs/Crm/SendLeadFollowUpReminderJob.php
app/Jobs/Crm/MarkStaleLeadsForReviewJob.php
app/Jobs/Sales/SendOpportunityFollowUpReminderJob.php
app/Events/Crm/LeadAssigned.php
app/Events/Sales/OpportunityStageChanged.php
app/Events/Sales/OpportunityWon.php
```

### Commands

```text
app/Console/Commands/BackfillCompaniesFromBusinessProfiles.php
app/Console/Commands/BackfillLeadsFromSubmissions.php
app/Console/Commands/BackfillOpportunities.php
app/Console/Commands/LinkLegacyQuotations.php
app/Console/Commands/ValidateBusinessFlow.php
```

### Config

```text
config/business_flow.php
```

## 6.2. File phải sửa

```text
app/Models/User.php
app/Models/Marketing/Contact.php
app/Models/Marketing/LandingPageSubmission.php
app/Models/Crm/BusinessContactProfile.php
app/Models/Crm/ContactQualification.php
app/Models/Crm/Customer.php
app/Models/Sales/Quotation.php

app/Services/Marketing/LandingPageSubmissionService.php
app/Services/Crm/LeadDistributionService.php
app/Services/Crm/TaxCodeVerificationService.php
app/Services/Crm/CustomerDistributionService.php
app/Services/Sales/QuotationCreationService.php
app/Services/Sales/QuotationPaymentService.php
app/Services/Sales/QuotationMailService.php
app/Services/Sales/QuotationPdfService.php
app/Services/Sales/QuotationTemplateRenderer.php
app/Services/Sales/QuotationPublicAccessService.php
app/Services/Sales/QuotationReminderService.php
app/Services/Sales/QuotationInteractionService.php
app/Services/Sales/QuotationEmailCrmSyncer.php

app/Actions/Contacts/ConvertContactToCustomerAction.php

app/Filament/Resources/LandingPageSubmissionResource.php
app/Filament/Resources/LandingPageSubmissionResource/Pages/ListLandingPageSubmissions.php
app/Filament/Resources/ContactQualificationResource.php
app/Filament/Resources/ContactQualificationResource/Pages/ListContactQualifications.php
app/Filament/Resources/Sales/QuotationResource.php
app/Filament/Pages/CustomerCarePage.php
app/Providers/Filament/AdminPanelProvider.php

app/Jobs/Crm/VerifyBusinessTaxCodeJob.php
app/Jobs/Sales/*

resources/views/filament/pages/customer-care.blade.php
resources/views/sales/public/show.blade.php
resources/views/sales/quotation-pdf.blade.php
resources/views/sales/emails/*

lang/vi.json
lang/en.json
.env.example
```

## 6.3. File/cột chỉ xóa ở Phase 10

```text
app/Actions/Contacts/ConvertContactToCustomerAction.php
app/Filament/Resources/ContactQualificationResource.php
```

Các cột legacy:

```text
landing_page_submissions.qualification_status
landing_page_submissions.assigned_staff_id
contact_qualifications.contact_id
contact_qualifications.assigned_staff_id
business_contact_profiles.company_name
business_contact_profiles.tax_code
business_contact_profiles.company_address
business_contact_profiles.industry
customers.company_group_id (nếu chỉ dùng nhóm tên)
```

Không xóa trước khi backfill và feature flag v2 ổn định.

---

# 7. GIAO DIỆN CHUẨN SAU KHI HOÀN THÀNH

## 7.1. Lượt gửi biểu mẫu

Bảng chỉ phản ánh dữ liệu đầu vào:

```text
Trang đích
Loại biểu mẫu
Tên người gửi
Doanh nghiệp
Email
Tiếp nhận
Lead
Trạng thái Lead
Gửi lúc
```

Actions:

```text
Xem dữ liệu gốc
Mở Contact
Mở Company
Mở Lead
Đánh dấu Spam (theo quyền)
```

Không có:

```text
Sửa trạng thái Qualification
Phân phối trực tiếp Submission
Tạo Customer
Tạo báo giá
```

## 7.2. Lead/Yêu cầu tư vấn

Header:

```text
LEAD-2026-000123
Công ty A — Nguyễn Văn A
Nguồn: Landing Page VPS
Owner: Nhân viên X
Trạng thái: Đang liên hệ
```

Tabs:

```text
Tổng quan
Thông tin liên hệ
Doanh nghiệp
Đánh giá
Hoạt động
Cơ hội kinh doanh
Submission gốc
```

## 7.3. Company

Header:

```text
CÔNG TY A
MST: 0101234567
Lifecycle: Prospect
Account Owner: Nhân viên X
Cơ hội đang mở: 2
Pipeline value: 80.000.000
```

Danh sách người liên hệ phải hiển thị:

```text
Họ tên
Chức vụ
Phòng ban
Vai trò quyết định
Primary
Active
```

## 7.4. Opportunity

Header:

```text
OPP-2026-000045
Triển khai 10 VPS cho Công ty A
Stage: Proposal
Opportunity Owner: Nhân viên Y
Account Owner: Nhân viên X
Estimated value: 30.000.000
```

Tabs:

```text
Tổng quan
Người liên hệ
Hoạt động
Báo giá
Tài liệu
Lịch sử stage
```

## 7.5. Quotation

Form tạo báo giá bắt đầu từ:

```text
Cơ hội kinh doanh
```

không bắt đầu từ Customer.

Hiển thị:

```text
Opportunity
Company/Contact nhận báo giá
Price Book
Items
Approval
Email logs
Documents
Payment
```

## 7.6. Customer Care

Chỉ hiển thị Customer đã chuyển đổi.

Tabs:

```text
Tổng quan
Email
Cuộc gọi/Tin nhắn
Đơn hàng/Thanh toán
Dịch vụ đang sử dụng
Gia hạn
Lịch sử
```

Không có nút tạo báo giá cho prospect.

---

# 8. KỊCH BẢN NGHIỆP VỤ E2E BẮT BUỘC

## Scenario 1 — Cá nhân gửi form

```text
1. Cá nhân A submit form.
2. Tạo Submission personal.
3. Resolve hoặc tạo Contact A.
4. Không tạo Company.
5. Tạo Lead A.
6. Qualification = new.
7. Admin phân phối.
8. Qualification = assigned/contacting.
9. Nhân viên xác nhận nhu cầu.
10. Qualification = qualified.
11. Tạo Opportunity.
12. Tạo/gửi báo giá.
13. Báo giá accepted nhưng chưa paid → chưa Customer.
14. Paid → Customer được tạo.
```

## Scenario 2 — Giám đốc và IT cùng Công ty A

```text
Ngày 1:
- Giám đốc submit MST 0101234567.
- Tạo Company A.
- Contact Giám đốc link Company A.
- Account Owner = Nhân viên X.

Ngày 2:
- IT submit cùng MST.
- Tạo Contact IT mới.
- Không tạo Company mới.
- Link IT vào Company A.
- Lead mới mặc định giao Nhân viên X.
```

## Scenario 3 — Cùng Contact gửi nhu cầu khác

```text
Contact A gửi VPS → Lead 1 → Opportunity 1.
Contact A gửi Email Marketing → Lead 2 → Opportunity 2.
Không tạo Contact mới.
Không ghi đè Qualification của Lead 1.
```

## Scenario 4 — Opportunity Owner khác Account Owner

```text
Company A Account Owner = X.
Opportunity VPS Owner = X.
Opportunity Email Marketing Owner = Y.
Company Account Owner vẫn là X.
X vẫn có quyền theo dõi tổng thể theo policy.
```

## Scenario 5 — Không có nhu cầu

```text
Lead assigned → contacting → unqualified.
Không tạo Opportunity.
Không tạo Customer.
```

## Scenario 6 — Báo giá không thanh toán

```text
Opportunity qualified → proposal → negotiation.
Quotation accepted.
Payment unpaid.
Customer không được tạo.
```

## Scenario 7 — Thanh toán thành công

```text
Payment paid.
Opportunity won.
Qualification converted + purchased.
Company lifecycle customer.
Customer được tạo đúng một lần.
Quotation customer_id được gán.
Customer Care thấy Customer.
```

---

# 9. TEST MATRIX THEO PHASE

| Phase | Unit | Feature | Integration/E2E |
|---|---|---|---|
| 1 | Status formatter | Submission columns/tabs | New form appears correct |
| 2 | Company normalization/resolution | Company backfill/policy | Two contacts one company |
| 3 | Lead code/service | Submission creates lead | Multiple leads per contact |
| 4 | Assignment rules | Auto/manual assign | Sticky account owner |
| 5 | Transition matrix | Lead activities/actions | Qualification pipeline |
| 6 | Opportunity workflow | Create from qualified lead | Owner/account owner behavior |
| 7 | Party accessors/pricing | Quote without customer | Email/PDF/public link |
| 8 | Conversion action | Paid conversion/idempotency | Payment → Customer Care |
| 9 | Policies | Role route access | Menu/action visibility |
| 10 | Validation commands | Backfill | Full regression |

Full suite bắt buộc trước merge:

```bash
php artisan test
./vendor/bin/pint --test
npm run build
php artisan crm:validate-business-flow
```

---

# 10. KẾ HOẠCH ROLLOUT AN TOÀN

## Release A

```text
Phase 0 + Phase 1
```

Không thay data model lớn.

## Release B

```text
Phase 2
COMPANY_RESOLUTION_ENABLED=true
```

Dual-write Company + BusinessContactProfile.

## Release C

```text
Phase 3 + Phase 4 + Phase 5
BUSINESS_FLOW_V2_ENABLED=true
```

LeadResource trở thành UI chính. Resource cũ vẫn giữ rollback.

## Release D

```text
Phase 6 + Phase 7
OPPORTUNITY_QUOTATION_ENABLED=true
```

Quotation mới dùng Opportunity. Quote cũ vẫn dùng Customer.

## Release E

```text
Phase 8
CUSTOMER_ON_PAID_ONLY=true
```

Conversion mới theo Payment.

## Release F

```text
Phase 9 + Phase 10
```

Dọn legacy sau khi dữ liệu được xác nhận.

---

# 11. CHECKLIST CODE REVIEW CHO AI AGENT

AI Agent phải kiểm tra trước khi báo hoàn thành mỗi phase:

```text
[ ] Không sửa migration lịch sử.
[ ] Migration up/down chạy được.
[ ] Mọi create/update nhiều bảng nằm trong transaction.
[ ] Job dispatch sau commit.
[ ] Không assume customer_id tồn tại trước payment.
[ ] Không dùng tax_code để dedupe Contact person.
[ ] Company match theo MST là ưu tiên cao nhất.
[ ] Không auto-merge chỉ bằng tên gần giống.
[ ] Một Submission chỉ có một Lead.
[ ] Một Lead chỉ có một Qualification.
[ ] Lead assignment là nguồn chính phân phối.
[ ] Qualification là nguồn chính trạng thái đánh giá.
[ ] Opportunity là nguồn chính pipeline bán hàng.
[ ] Customer chỉ tạo sau paid.
[ ] Policy chặn URL trực tiếp, không chỉ ẩn nút.
[ ] Test mới được thêm cho mỗi rule.
[ ] Pint pass.
[ ] Full regression pass.
```

---

# 12. PROMPT GIAO CHO AI CODING AGENT

```text
Bạn đang sửa repository DTH-business-core, ứng dụng chính ở
marketing-email-laravel-v12, Laravel 12 + Filament 3.

Hãy thực hiện đúng từng phase trong file
docs/DTH_BUSINESS_CORE_CRM_SALES_TRANSFORMATION_PLAN.md.

Quy tắc bắt buộc:
1. Không viết lại hệ thống từ đầu.
2. Không sửa migration cũ đã chạy; luôn tạo migration mới.
3. Mỗi phase là một commit độc lập.
4. Trước khi sửa file, đọc toàn bộ model/service/resource liên quan và tìm tất cả reference bằng ripgrep.
5. Giữ backward compatibility cho dữ liệu Customer/Quotation cũ cho đến Phase 10.
6. Mọi luồng tạo Submission → Contact → Company → Lead → Qualification phải nằm trong transaction.
7. Không dùng tax_code để kết luận hai người là cùng Contact; tax_code chỉ dùng nhận diện Company.
8. Quotation mới thuộc Opportunity và customer_id có thể null.
9. Chỉ payment paid mới tạo Customer.
10. Không cho user chuyển converted/won thủ công khi chế độ customer_on_paid_only bật.
11. Thêm Policy và test authorization.
12. Chạy migrate, test, Pint và ghi lại kết quả sau mỗi phase.

Sau mỗi phase, hãy báo cáo:
- File tạo mới.
- File sửa.
- Migration đã chạy.
- Test đã thêm.
- Câu lệnh đã chạy.
- Rủi ro/backward compatibility.
- Những việc chưa làm của phase tiếp theo.
```

---

# 13. ĐỊNH NGHĨA HOÀN THÀNH TOÀN DỰ ÁN

Dự án chỉ được coi là hoàn thành khi đạt đủ:

1. Hai người cùng MST có thể là hai Contact nhưng cùng một Company.
2. Company có Account Owner và lịch sử điều chuyển.
3. Một Contact có nhiều Lead độc lập.
4. Submission không còn điều khiển workflow CRM.
5. Lead phân phối đúng và không giao trùng theo Submission.
6. Qualification transitions có service kiểm soát.
7. Qualified Lead tạo Opportunity.
8. Quotation tạo từ Opportunity khi customer_id null.
9. Email/PDF/public quotation hoạt động khi chưa có Customer.
10. Accepted quotation không tạo Customer.
11. Paid quotation tạo Customer đúng một lần.
12. Customer Care chỉ hiển thị Customer đã chuyển đổi.
13. Role/Policy chặn đúng quyền.
14. Backfill không mất dữ liệu cũ.
15. Validation command không báo orphan nghiêm trọng.
16. Full test, Pint và build đều pass.

---

# 14. LƯU Ý VỀ SNAPSHOT SOURCE

Tài liệu này được lập dựa trên nhánh `master` được rà soát ngày `2026-08-05`.

Trước khi AI Agent bắt đầu mỗi phase phải chạy:

```bash
git checkout master
git pull origin master
git log -1 --oneline
```

Sau đó so sánh file hiện tại với đường dẫn trong tài liệu. Nếu source mới đã đổi signature hoặc schema, Agent phải giữ nguyên nguyên tắc nghiệp vụ của tài liệu nhưng điều chỉnh patch cho phù hợp, không áp dụng code một cách mù quáng.

