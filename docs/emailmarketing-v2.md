# Hệ thống Email Marketing, CRM & Sales — Báo cáo hệ thống phiên bản 2 (v2)

> Tài liệu mô tả **toàn bộ hệ thống hiện tại**: tổng quan, kiến trúc, dữ liệu & CSDL,
> chi tiết mọi chức năng kèm thao tác, và các luồng hoạt động chính.
>
> Dự án: `marketing-email-laravel-v12` (Laravel 12 + Filament v3)
> Thư mục: `E:\email and marketing-module\marketing-email-laravel-v12`
> Tài liệu giao diện chi tiết (UI/UX) xem tại: [`UI-UX.md`](UI-UX.md)

---

## Mục lục

1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
2. [Kiến trúc & Công nghệ](#2-kiến-trúc--công-nghệ)
3. [Cấu trúc dữ liệu & CSDL](#3-cấu-trúc-dữ-liệu--csdl)
4. [Tác nhân, vai trò & Phân quyền](#4-tác-nhân-vai-trò--phân-quyền)
5. [Mô tả chi tiết chức năng & thao tác](#5-mô-tả-chi-tiết-chức-năng--thao-tác)
6. [Các luồng hoạt động chính](#6-các-luồng-hoạt-động-chính)
7. [Phụ lục: Jobs, Services, Routes, Schedules, Observers](#7-phụ-lục)

---

## 1. Tổng quan hệ thống

### 1.1. Giới thiệu
Hệ thống là một **nền tảng CRM + Email Marketing + Bán hàng (Báo giá)** doanh nghiệp, xây dựng trên nền tảng Laravel 12 với panel quản trị **Filament v3**. Hệ thống phục vụ chuỗi vận hành:

```
Quảng cáo / Email Marketing → Landing Page thu lead → Phân phối Lead
→ Chăm sóc khách hàng (CRM) → Báo giá (Quotation) → Phê duyệt → Gửi khách
→ Xác nhận (Accept/Reject) → Theo dõi thanh toán (QR/VietQR)
```

Hệ thống chia làm **4 module lớn**:

| Module | Mô tả |
|---|---|
| **Email Marketing** | Quản lý chiến dịch email, mẫu email, tài khoản/tên miền gửi, danh sách loại trừ, tracking mở/nhấp, báo cáo chiến dịch |
| **Marketing / Landing Page** | Chiến dịch quảng cáo, trang đích (landing page), mẫu biểu mẫu, lượt submit, phân đoạn khách hàng, thẻ/danh sách liên hệ, báo cáo UTM |
| **CRM** | Nhân viên & tổ chức (phòng ban, chức danh), liên hệ (cá nhân/doanh nghiệp), pipeline đánh giá lead, khách hàng, phân phối/ủy quyền, chăm sóc khách hàng (email/cuộc gọi/lịch) |
| **Sales (Kinh doanh)** | Dịch vụ, gói dịch vụ, bảng giá & quyền truy cập giá, tài khoản ngân hàng, báo giá (vòng đời: nháp→duyệt→gửi→chấp nhận→thanh toán), phê duyệt, theo dõi thanh toán, PDF & VietQR |

### 1.2. Mô hình dữ liệu chủ đạo (Customer-Centric)
- Thực thể vận hành chính là **`customers`** (khách hàng) — chứa đầy đủ trường hồ sơ (cá nhân + doanh nghiệp), trạng thái, đồng ý nhận email, vòng đời, phân công (owner/support).
- **`contacts`** là lớp trung gian mảnh (join layer) với 2 bảng hồ sơ: `personal_contact_profiles`, `business_contact_profiles`; mỗi liên hệ có 1 hồ sơ tương ứng theo `contact_type`.
- **`contact_qualifications`** — pipeline lead: `new → assigned → contacting → follow_up → qualified → converted` (hoặc `unqualified/duplicate/spam/archived`).
- **`customer_assignments`** — phân công: `owner` (phụ trách chính) hoặc `support` (hỗ trợ), trạng thái `active/ended/cancelled`, lý do ghi rõ nguồn gốc.
- Email marketing vận hành trên **`customers`** (audience `all_subscribed/list/tag/segment/qualified`), chi tiết từng email tại **`campaign_recipients`**, sự kiện tracking tại **`email_events`** + **`tracked_links`**.
- Bán hàng vận hành trên **`quotations`** với snapshot (khách hàng/công ty/thanh toán/điều khoản) + items snapshot để giữ nguyên báo giá theo thời điểm.

### 1.3. Các con số (thời điểm phân tích)
- ~98 migration, 54 model, 46 enum, 15+ resource Filament, 7 custom pages, 17+ widgets, 16 jobs, 30+ services.

---

## 2. Kiến trúc & Công nghệ

### 2.1. Công nghệ
| Thành phần | Lựa chọn |
|---|---|
| Framework | Laravel **12** (PHP 8.x) |
| Admin Panel | Filament **v3** (panel `admin`, path `/admin`, màu Amber) |
| Database | SQLite (mặc định dev) — hỗ trợ MySQL qua `config/database.php` |
| Queue | Laravel Queue (queues: `marketing`, `quotations`, default) — database driver |
| Scheduler | `routes/console.php` chạy mỗi phút: `ProcessScheduledCampaignsJob` + `sales:process-reminders` |
| PDF | `barryvdh/laravel-dompdf` (DejaVu Sans, remote images) |
| FullCalendar | `saade/filament-fullcalendar` (lịch nhân viên) |
| i18n | `lang/vi.json`, `lang/en.json` (khóa `__()`) + middleware `SetLocale` |
| Cache | default file/database (OTP, tax verification 14 ngày, VietQR 12 giờ) |
| Auth | Filament auth (Eloquent), listener `LogSuccessfulLogin` ghi AuditLog |

### 2.2. Cấu trúc thư mục chính
```
app/
├── Filament/
│   ├── Resources/          # Contact, Campaign, LandingPage, Customer, Staff, ...
│   │   └── Sales/          # Service, ServicePackage, PriceBook, BankAccount,
│   │                       # Quotation (+RelationManagers), QuotationApproval, PaymentTracking
│   ├── Pages/              # AdminDashboard, CampaignReportPage, CompanySettingsPage,
│   │                       # CustomerCarePage, SalesDashboard, StaffDashboard
│   ├── Widgets/            # 17 widget thống kê/bảng
│   └── Actions/            # ReleaseCustomerAction, ConvertContactToCustomerAction
├── Models/                 # User + Crm/ Marketing/ Sales/
├── Services/               # Crm/ Marketing/ Sales/ (logic nghiệp vụ)
├── Jobs/                   # Marketing/ Sales/ Crm/
├── Mail/                   # MarketingCampaignMail + Sales/*Mail
├── Observers/              # StaffObserver, CustomerAssignmentObserver
├── Policies/               # Customer, CustomerAssignment, CustomerInteraction, Staff, Sales/*
└── Http/Controllers/       # Marketing/Admin, Marketing/Public, Sales
database/migrations/        # ~98 migration
database/seeders/           # DatabaseSeeder (roles, staff, positions, banks...)
routes/web.php + console.php
resources/views/            # blade public + filament custom views
lang/                       # vi.json, en.json
docs/                       # tài liệu
```

### 2.3. Đường link (routing) chính
| Route | Chức năng |
|---|---|
| `/admin` | Panel quản trị (Filament) |
| `/lp/{slug}` | Trang đích công khai |
| `/lp/{slug}/submit`, `/lp/{slug}/thank-you` | Submit biểu mẫu / Cảm ơn |
| `/m/open/{token}.gif` | Pixel mở email marketing |
| `/m/click/{token}` | Redirect click email marketing |
| `/m/unsubscribe/{token}` (GET/POST) | Hủy đăng ký |
| `/m/care/open/{token}.gif`, `/m/care/click/{token}` | Tracking email chăm sóc 1-1 |
| `/q/{code}/{token}` (+/pdf, /accept, /reject, /request-revision, /send-otp, /verify-otp) | Báo giá công khai |
| `/language/switch` | Chuyển ngôn ngữ |
| `/admin/marketing/landing-pages/{id}/preview`, `.../utm-urls/{id}`, `/admin/marketing/email-templates/{id}/preview` | Preview/quản lý admin |

---

## 3. Cấu trúc dữ liệu & CSDL

### 3.1. Bảng hệ thống (Laravel built-in)
`users` (có `role` default `viewer`), `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

### 3.2. Bảng Email Marketing

**`email_templates`** — Mẫu email (soft delete)
- `name`, `category` (default `marketing`; values: `marketing`, `quotation`), `subject`, `preheader`, `html_body` (longText), `text_body`, `status` (`draft`/`active`/`inactive`), `created_by`.

**`sending_accounts`** — Tài khoản gửi
- `name`, `provider` (`laravel_mail`/`smtp`), `from_name`, `from_email`, `reply_to`, `config_encrypted` (encrypted array), `daily_limit`, `hourly_limit`, `status` (`active`/`inactive`/`testing`), `department_id` (FK departments, null = tài khoản chung).

**`sending_domains`** — Tên miền gửi
- `domain` (unique), `status`, `spf_status`, `dkim_status`, `dmarc_status` (mỗi cái: `unknown/pending/verified/failed`), `notes`, `verified_at`.

**`campaigns`** — Chiến dịch email (soft delete, audit)
- `name`, `subject`, `preheader`, `email_template_id`, `sending_account_id`, `landing_page_id`, `audience_type` (`all_subscribed`/`list`/`tag`/`segment`/`qualified`), `audience_id`, `status` (**9 trạng thái**: `draft, testing, scheduled, preparing, sending, sent, paused, cancelled, failed`), `scheduled_at`, `sent_at`, `created_by`.

**`campaign_recipients`** — Người nhận của chiến dịch
- `campaign_id`, `contact_id`, `customer_id` (nullable), `email`, `status` (`pending, queued, sent, delivered, opened, clicked, failed, bounced, unsubscribed, skipped`), `personalized_subject`, `personalized_html`, `sent_at`, `opened_at`, `clicked_at`, `failed_at`, `failure_reason`, `unsubscribe_token` (unique), `tracking_token` (unique), unique(`campaign_id`,`contact_id`).

**`email_events`** — Sự kiện email (log tracking)
- `tracking_token`, `campaign_id`, `campaign_recipient_id`, `contact_id`, `customer_id`, `event_type` (`queued, sent, delivered, opened, clicked, failed, bounced, complained, unsubscribed, skipped`), `event_payload` (json), `ip_address`, `user_agent`, `occurred_at`.

**`tracked_links`** — Liên kết bị theo dõi
- `campaign_id`, `campaign_recipient_id`, `original_url`, `tracking_token` (unique), `click_count`, `last_clicked_at`.

**`suppression_entries`** — Danh sách loại trừ
- `email` + `reason` (unique pair; reason: `unsubscribe, bounce, complaint, manual, invalid_email, do_not_contact`), `source`, `campaign_id`, `contact_id`, `customer_id`, `note`, `created_by`.

**`audit_logs`** — Nhật ký hoạt động (morph)
- `user_id`, `action` (ví dụ `campaign.created`), `auditable_type/id` (MorphTo), `old_values`, `new_values` (json), `ip_address`, `user_agent`, `created_at`. Không có `updated_at`.

**`custom_fields`** + **`contact_custom_field_values`** — Trường tùy chỉnh (kiểu text/number/date/boolean/select/multi_select; giá trị phân tách theo kiểu; unique(contact, field)).

**`segments`** — Phân khúc
- `name`, `slug`, `description`, `rules` (json), `status`, `created_by`.

**`import_batches`** — Đợt import (status `processing/completed/failed`, error_file_path) — code đã có nhưng chưa nối UI.

### 3.3. Bảng Marketing / Landing Page

**`marketing_campaigns`** — Chiến dịch quảng cáo
- `name`, `slug`, `description`, `status` (`draft/active/paused/completed`), `start_date`, `end_date`, `budget`, `notes`, `created_by`. Quan hệ: hasMany `LandingPage` (qua `marketing_campaign_id`).

**`form_templates`** — Mẫu biểu mẫu (soft delete)
- `name`, `slug`, `description`, `submit_button_text`, `success_message`, `redirect_url`, `html_body`, `auto_tag_names` (json), `auto_create_tags` (true), `auto_create_lists` (true), `auto_create_segment` (false), `status` (`draft/active/archived`), `audience_type` (`personal/business/generic`), `version`, `is_system_template`, `schema` (json), `created_by`.

**`form_fields`** — Trường của mẫu biểu mẫu
- `landing_form_template_id` (FK), `label`, `field_key` (unique với template), `field_type` (`text/email/phone/textarea/select/checkbox/hidden`), `placeholder`, `options` (json), `default_value`, `sort_order`, `tag_from_value`, `contact_mapping`, `validation_rules`, `position`.

**`landing_pages`** — Trang đích (**không soft delete**)
- `landing_form_template_id` (FK null), `campaign_id` (FK campaigns — chiến dịch email), `marketing_campaign_id`, `name`, `slug` (unique), `page_title`, `headline`, `subheadline`, `content`, `cta_text`, `html_body`, `css_body`, `auto_tag_names`, `auto_list_names`, `auto_create_tags/lists/segment`, `status` (`draft/published/archived`), `published_at`, `created_by`.

**`landing_page_views`** — Lượt xem
- `landing_page_id`, `session_id`, `ip_address`, `user_agent`, `referrer`, `utm_source`, `utm_medium`, `utm_campaign`, `utm_content`, `utm_term`, `viewed_at`.

**`landing_page_submissions`** — Lượt gửi biểu mẫu
- `submission_type`, `landing_page_id`, `campaign_id`, `landing_form_template_id`, `contact_id`, `data` (json), `normalized_email` (index), `business_tax_code`, `status` (`received/processed/failed/spam`), `contact_action` (`created/updated/skipped`), `qualification_status`, `assigned_staff_id`, `verified_at`, `verified_by_staff_id`, `ip_address`, `user_agent`, `referrer`, utm cols, `submitted_at`.

**`landing_page_utm_urls`** — URL theo dõi UTM
- `landing_page_id`, `utm_source/medium/campaign/content/term`, `url`, `name`.

**`landing_page_forms`** — Gắn form vào trang
- `landing_page_id`, `form_template_id`, `form_type` (`personal/business/generic`), `display_mode` (`embedded`), `position_key`, `is_default`, `sort_order`, `status`, unique(landing_page, form_type).

### 3.4. Bảng CRM — Tổ chức & Nhân sự

**`departments`** — Phòng ban
- `code` (unique: `admin, marketing, customer_service, sales, technical, email_service`), `name`, `description`, `sort_order`, `is_active`.

**`positions`** — Chức danh
- `title`, `department_id`, `description`, `sort_order`, `is_active`.

**`staff`** — Nhân viên (soft delete)
- `user_id` (unique), `department_id`, `position_id`, `employee_code` (unique, format `EMP-xxxx`), `full_name`, `phone`, `employment_status` (`active/inactive/resigned`), `can_receive_customers` (true), `customer_capacity` (nullable), `distribution_weight` (1.00), `started_at`, `ended_at`, `metadata`.

**`staff_availabilities`** — Tình trạng làm việc (tạm thời)
- `staff_id`, `status` (`working/absent/leave/sick/remote/half_day`), `starts_at`, `ends_at`, `can_receive_new_customers`, `can_support_customers`, `reason`, `note`, `approved_by_user_id`.

### 3.5. Bảng CRM — Liên hệ & Khách hàng

**`contacts`** — Liên hệ (soft delete, mảnh)
- `contact_type` (`personal/business`), `owner_user_id`. → 1-1 profile: `personal_contact_profiles` (first_name, last_name, email, phone, date_of_birth, gender, ward/district/province/country, occupation) hoặc `business_contact_profiles` (company_name, tax_code unique, company_address, legal_representative, contact_position, business_email, business_phone, industry, **tax_verification_status** (`pending/verified/not_found/mismatch/error/manual_review`), tax_verified_at, provider, data, message).

**`contact_qualifications`** — Đánh giá lead
- `contact_id` (unique), `assigned_staff_id`, `status` (`new/assigned/contacting/follow_up/qualified/unqualified/converted/duplicate/spam/archived`), `priority`, `score`, `qualification_result` (`confirmed_need/purchased/no_need/unreachable/invalid_information`), `service_interest`, `estimated_value`, `first_contacted_at`, `last_contacted_at`, `next_follow_up_at`, `qualified_at`, `qualified_by_staff_id`, `unqualified_reason`, `converted_at`, `converted_customer_id`.

**`contact_qualification_notes`** — Ghi chú xử lý lead
- `contact_qualification_id`, `staff_id`, `note_type`, `content`, `outcome`, `contacted_at`, `next_follow_up_at`.

**`customers`** — Khách hàng (soft delete, trung tâm)
- `customer_code` (unique, format `CUS-YYYYMM-xxxxxx`), `contact_id` (unique), `customer_type` (`personal/business`), `display_name`, toàn bộ hồ sơ: `first_name, last_name, date_of_birth, gender, ward/district/province/country, occupation, company_name, tax_code, company_address, legal_representative, contact_position, business_email, business_phone, industry, email, normalized_email, phone, normalized_phone, email_verified_at`.
- Marketing: `acquisition_source`, `consent_status` (`pending/subscribed/unsubscribed/bounced/complained/do_not_contact`), `subscribed_at`, `unsubscribed_at`, `last_engaged_at`.
- Vòng đời: `status` (`potential/active/inactive/churned/blocked/archived`), `lifecycle_stage` (`new_customer/onboarding/nurturing/purchasing/retained/at_risk/churned`), `conversion_reason` (`confirmed_need/purchased`), `converted_at`, `converted_by_staff_id`, `first_purchase_at`, `latest_purchase_at`, `total_revenue`, `priority`, `next_follow_up_at`.
- `metadata` (json, chứa `tags`), `company_group_id` (uuid — nhóm khách cùng công ty).

**`customer_interactions`** — Tương tác chăm sóc (soft delete)
- `customer_id`, `staff_id`, `customer_assignment_id`, `interaction_type` (`call/email/message/meeting/support/follow_up/complaint/note`), `subject`, `content`, `outcome`, `status` (`scheduled/completed/cancelled/no_show/rescheduled`), `interaction_at`, `next_follow_up_at`, `is_support_action`, `original_owner_staff_id`.

**`customer_assignments`** — Phân công
- `customer_id`, `staff_id`, `assignment_type` (`owner/support`), `status` (`active/ended/cancelled`), `starts_at`, `ends_at`, `assigned_by_user_id`, `reason` (`initial_distribution/new_customer/manual/staff_absence/staff_return/rebalance/transfer`), `original_owner_staff_id`, `distribution_batch_id`, `note`, `ended_by_user_id`, `ended_at`.

**`customer_distribution_batches`** + **`customer_distribution_items`** — Phiên & chi tiết phân phối
- Batch: `batch_code` (unique), `type` (`initial/new_customer/staff_absence/staff_return/rebalance/manual`), `status` (`draft/processing/completed/failed/reverted`), `source_staff_id`, `effective_from/until`, `strategy` (`round_robin/least_loaded/weighted/manual`), `total_customers`, `total_assigned`, `initiated_by_user_id`, `completed_at`, `note`, `payload`.
- Item: `distribution_batch_id`, `customer_id`, `original_owner_staff_id`, `assigned_staff_id`, `assignment_type`, `result_status` (`pending/success/skipped`), `reason`, `customer_assignment_id`.

**Pivot:** `customer_tag` (customer_id, tag_id) — `customer_list_members` (customer_id, customer_list_id, status, subscribed_at, unsubscribed_at) — model `ContactListMember` map sang bảng này.

### 3.6. Bảng Sales

**`services`** (soft delete): `service_code` unique, `name`, `slug`, `description`, `service_category_id` (không FK), `default_scope`, `default_terms`, `status` (`active/inactive/archived`), `sort_order`, `created_by`, `updated_by`.

**`service_packages`** (soft delete): `service_id`, `package_code` unique, `name`, `description`, `audience_type` (`personal/business/both`), `billing_period`, `billing_period_unit` (`day/month/year/one_time`), `unit` (default "tháng"), `default_quantity`, `status`, `sort_order`.

**`price_books`** (soft delete): `price_book_code`, `name`, `description`, `audience_type`, `currency` (`VND/USD`), `tax_mode` (`inclusive/exclusive/none`; **lưu ý**: default migration là `tax_exclusive` — không khớp enum), `valid_from`, `valid_until`, `status` (`draft/active/inactive/expired/archived`), `is_default`, `approved_by`, `approved_at`.

**`price_book_items`** (soft delete): `price_book_id`, `service_package_id`, `unit_price`, `minimum_quantity`, `maximum_quantity`, `default_discount_type` (`fixed/percentage`), `default_discount_value`, `maximum_discount_value`, `vat_rate`, `description`, `scope_override`, `terms_override`, `sort_order`.

**`price_book_access_rules`** (soft delete): `price_book_id`, `access_type` (`all/role/department/staff/branch`), `role`, `department`, `staff_id`, `branch_id` (FK tới staff), `can_view`, `can_create_quotation`, `discount_limit_type`, `discount_limit_value`.

**`bank_accounts`** (soft delete): `bank_code`, `bank_name`, `account_number`, `account_name`, `branch_name`, `swift_code`, `qr_provider`, `qr_template`, `status`, `is_default`.

**`vn_banks`**: `code` (VCB...), `bin` (6 số), `short_name`, `name`, `swift_code`, `logo` — nguồn chọn ngân hàng + sinh QR.

**`quotations`** (soft delete): 
- Nhận diện: `quotation_code` unique (format `QTYYYYNNNNN`), `version` (bắt đầu 1; hiển thị `-V{n}`), `parent_quotation_id`, `replaces_quotation_id`, `public_token` (64 hex unique).
- Liên kết: `customer_id`, `assigned_staff_id`, `price_book_id`, `bank_account_id`.
- Nội dung: `title`, `quotation_date`, `valid_until`, `currency`, `subtotal`, `discount_total`, `tax_total`, `grand_total`.
- Trạng thái: `status` (11: `draft, pending_approval, approved, sent, viewed, accepted, rejected, revision_requested, expired, cancelled, superseded`), `payment_status` (`not_required, unpaid, pending_verification, partially_paid, paid, refunded, cancelled`), `email_status` (`unsent, queued, sending, sent, failed`).
- Snapshot: `customer_snapshot`, `company_snapshot`, `payment_snapshot`, `terms_snapshot` (json), `metadata`.
- Mốc thời gian: `sent_at, first_viewed_at, last_viewed_at, view_count, accepted_at, rejected_at, revision_requested_at, expired_at, cancelled_at, approved_at`; `created_by/updated_by/approved_by`.

**`quotation_items`**: `quotation_id`, `service_id`, `service_package_id`, `price_book_item_id` + snapshot (`service_code_snapshot`, `service_name_snapshot`, `package_code_snapshot`, `package_name_snapshot`, `description_snapshot`, `scope_snapshot`, `terms_snapshot`), `unit`, `quantity`, `unit_price`, `discount_type`, `discount_value`, `discount_amount`, `vat_rate`, `vat_amount`, `line_subtotal`, `line_total`, `sort_order`.

**`quotation_approvals`**: `quotation_id`, `step` (1..), `approver_user_id`, `approver_role` (`customer_service_manager`), `status` (`pending/approved/rejected/cancelled`), `reason`, `requested_at`, `reviewed_at`.

**`quotation_confirmations`**: `quotation_id`, `confirmation_type` (`accepted/rejected/revision_requested`), `signer_name`, `signer_position`, `signer_email`, `signer_phone`, `confirmation_code` (32 hex), `otp_verified_at`, `confirmed_at`, `ip_address`, `user_agent`, `confirmation_data`.

**`quotation_email_logs`**: `quotation_id`, `recipient_email`, `cc`, `bcc` (json), `subject`, `body_snapshot`, `attachment_path`, `provider_message_id`, `status` (`draft/queued/sending/sent/failed/cancelled`), `error_message`, `queued_at`, `sent_at`, `failed_at`, `created_by`.

**`quotation_documents`**: `quotation_id`, `version`, `document_type` (`pdf/signed_snapshot`), `file_path`, `file_name`, `mime_type`, `file_size`, `file_hash` (sha256), `generated_by`, `generated_at`.

**`company_settings`** (1 dòng): `company_name`, `tax_code`, `address`, `phone`, `email`, `website`, `logo_path`, `vietqr_client_id`, `vietqr_api_key`.

### 3.7. Quan hệ chính (sơ lược ER)
- `customers` 1-1 `contacts` (unique) ; `contacts` 1-1 `personal_contact_profiles` / `business_contact_profiles` (theo type); 1-1 `contact_qualifications`; 1-N `customer_assignments`, `customer_interactions`, `campaign_recipients`, `email_events`, `quotations`.
- `staff` N-1 `departments`, `positions`; 1-N `customer_assignments`, `customer_interactions`, `staff_availabilities`.
- `campaigns` N-1 `email_templates`, `sending_accounts`, `landing_pages`; 1-N `campaign_recipients`; `campaign_recipients` 1-N `email_events`, `tracked_links`.
- `landing_pages` N-1 `marketing_campaigns`; 1-N `landing_page_views`, `landing_page_submissions`, `landing_page_utm_urls`, `landing_page_forms`; `landing_page_forms` N-1 `form_templates` (1-N `form_fields`).
- `price_books` 1-N `price_book_items` (N-1 `service_packages` N-1 `services`), 1-N `price_book_access_rules`.
- `quotations` N-1 `customers`/`price_books`/`bank_accounts`; 1-N `quotation_items`, `quotation_approvals`, `quotation_confirmations`, `quotation_email_logs`, `quotation_documents`.
- `audit_logs` — polymorphic (MorphTo `auditable`).

---

## 4. Tác nhân, vai trò & Phân quyền

### 4.1. Vai trò (trường `users.role`)
| Role | Ý nghĩa |
|---|---|
| `admin` | Quản trị viên — toàn quyền |
| `marketing_manager` | Quản lý Marketing |
| `marketing_staff` | Nhân viên Marketing |
| `customer_service_manager` | Quản lý CSKH |
| `customer_service_staff` | Nhân viên CSKH |
| `viewer` | Người dùng chỉ xem |

### 4.2. Hệ thống Gate (tại `AppServiceProvider`)
| Gate | Quyền cần |
|---|---|
| `marketing.view-*` (contacts/tags/lists/segments/custom-fields/templates/campaigns/reports/landing-pages/submissions) | Mọi người dùng marketing (`isAnyMarketingUser`) |
| `marketing.manage-*` | Marketing staff trở lên |
| `marketing.send-campaigns` | Marketing Manager + Admin |
| `marketing.manage-sending`, `marketing.view-audit` | Admin |
| `marketing.manage-suppression`, `marketing.export-data` | Marketing Manager + Admin |
| `crm.manage-contact-qualification` | Marketing staff + CS staff |
| `crm.assign-contact`, `crm.convert-contact` | Manager các loại + Admin |
| `crm.manage-staff`, `crm.distribute-customers`, `crm.transfer-customer-owner`, `crm.rebalance-customers`, `crm.verify-business-tax`, `crm.view-all-customers` | Admin (vài gate) / Manager |
| `crm.view-owned/supported-customers`, `crm.manage-customer-interactions` | Marketing staff + CS staff |
| `sales.view-*` | Mọi người dùng marketing |
| `sales.manage-*` (services/packages/price-books/bank-accounts) | Admin |
| `sales.approve-price-books` | Admin + CS Manager |
| `sales.create-quotations` | Admin + CS staff/manager |
| `sales.send-quotations`, `sales.approve-quotations`, `sales.cancel-quotations` | Admin + CS Manager |
| `sales.revise-quotations` | Admin |
| `sales.export-quotations` | Mọi người dùng marketing |

### 4.3. Scope dữ liệu theo vai trò
- **CustomerResource**: CS Staff chỉ thấy khách có phân công active cho mình.
- **QuotationResource**: nhân viên chỉ thấy báo giá mình tạo hoặc được phân công; Admin/CSM thấy tất cả.
- **StaffResource/UserResource/CompanySettings/AuditLog**: Admin only.
- **QuotationApproval / PaymentTracking**: Admin + CS Manager.

---

## 5. Mô tả chi tiết chức năng & thao tác

> Mỗi chức năng: mục đích → thao tác → kết quả/hệ quả. Chi tiết giao diện từng màn hình xem [UI-UX.md](UI-UX.md).

### 5.1. MODULE EMAIL MARKETING

#### 5.1.1. Quản lý Chiến dịch Email (Campaign)
- **Tạo chiến dịch**: khai báo tên, chủ đề, preheader, chọn **mẫu email**, **tài khoản gửi chung** (không thuộc phòng ban), **đối tượng** (tất cả đã subscribe / danh sách / thẻ / phân khúc / lead đủ điều kiện từ landing page) và (tùy chọn) **thời điểm gửi**.
  - Thao tác: menu Email → Chiến dịch email → Tạo → điền form → Lưu.
  - Hệ quả: chiến dịch ở trạng thái `draft`, ghi audit `campaign.created`.
- **Lên lịch gửi**: chọn `scheduled_at` → lưu. Scheduler mỗi phút: `ProcessScheduledCampaignsJob` chuyển `scheduled → preparing` → `PrepareCampaignRecipientsJob`.
- **Chuẩn bị người nhận (tự động)**: resolve audience → lọc email hợp lệ + không nằm suppression → loại trùng → tạo `CampaignRecipient` (token unsubscribe + tracking) → render subject/body cá nhân hóa → dispatch `SendCampaignEmailJob` từng người → chiến dịch chuyển `sending`; nếu 0 người nhận → `sent`/`failed`.
- **Gửi email (tự động)**: `SendCampaignEmailJob` (tries 3): thay thế toàn bộ `href` bằng link tracking (`/m/click/{token}`), thêm pixel `/m/open/{token}.gif` → gửi `MarketingCampaignMail` → cập nhật recipient `sent` + EmailEvent `sent`; lỗi → `failed` + `failure_reason`. Khi hết recipient → chiến dịch `sent`/`failed`.
- **Xem báo cáo**: vào Báo cáo chiến dịch → chọn chiến dịch → xem các chỉ số (gửi/mở/nhấp/tỷ lệ/người nhận). Không xuất file.

#### 5.1.2. Quản lý Mẫu Email (Email Template)
- **Tạo**: tên, danh mục (`marketing`/`quotation`), subject, preheader, HTML body (RichEditor, hỗ trợ token `{{first_name}}`...), text body.
- **Nhập từ HTML**: nút "Nhập HTML" → upload file → trích body + text.
- **Xem trước**: nút Preview mở tab mới — hiển thị subject/preheader + HTML trong iframe, token giữ nguyên dạng `{{token}}`.
- **Ghi chú**: mẫu danh mục `quotation` được dùng để soạn email báo giá.

#### 5.1.3. Quản lý Tài khoản gửi (Sending Account)
- **Tạo**: tên, phòng ban (trống = chung cho chiến dịch), provider, from name/email, reply-to, cấu hình SMTP (KeyValue mã hóa), giới hạn ngày/giờ, trạng thái.
- **Gửi thử**: nút "Gửi thử" → modal nhập email nhận → gửi email test qua `SendingAccountService::sendTestEmail`.
- **Chỉ Admin**.

#### 5.1.4. Quản lý Tên miền gửi (Sending Domain) — Admin
- Thêm domain + cập nhật trạng thái SPF/DKIM/DMARC (unknown/pending/verified/failed) để theo dõi xác thực DNS.

#### 5.1.5. Danh sách loại trừ (Suppression) — Marketing Manager/Admin
- **Thêm thủ công**: email + lý do (unsubscribe/bounce/complaint/manual/invalid_email/do_not_contact) + nguồn + ghi chú.
- **Tự động**: mọi chiến dịch kiểm tra suppression trước khi tạo recipient; unsubscribe từ trang public cũng thêm vào.
- Unique (email, reason) — cùng email có thể có nhiều lý do khác nhau.

#### 5.1.6. Nhật ký hệ thống (Audit Log) — Admin
- Chỉ xem: thời gian, người, hành động (action), loại đối tượng, ID, IP.

### 5.2. MODULE MARKETING / LANDING PAGE

#### 5.2.1. Chiến dịch quảng cáo (Ad Campaign)
- **Tạo**: tên, slug, mô tả, trạng thái, ngày chạy, ngân sách, gắn các **trang đích**.
- Thao tác: Marketing → Chiến dịch quảng cáo → Tạo → gắn landing page → Lưu (sau khi lưu sẽ ghi `marketing_campaign_id` vào từng landing page).

#### 5.2.2. Trang đích (Landing Page)
- **Tạo**: khai thông tin chiến dịch email/quảng cáo, tên/slug, headline, subheadline, CTA, gắn **mẫu biểu mẫu** (repeater: có thể 2 form personal + business), cấu hình tự động gắn thẻ/danh sách, HTML tùy chỉnh.
- **Xuất bản**: bấm "Xuất bản" → hệ thống **bắt buộc trang phải liên kết 1 chiến dịch** → chuyển `published`, gán `published_at`.
- **Tạo URL UTM**: nút "Tạo URL theo dõi (UTM)" → chọn source/medium/campaign/content/term → sinh `LandingPageUtmUrl` (dùng trong quảng cáo để đo hiệu quả).
- **Xem/Sao chép liên kết**: copy URL công khai `/lp/{slug}`; "Liên kết" hiện modal danh sách UTM URL có nút copy/delete; "Xem trước" xem bản nháp (không tracking).
- **Nhập HTML**: tạo trang ở trạng thái draft từ file HTML.

#### 5.2.3. Mẫu biểu mẫu (Form Template)
- **Tạo**: khai báo trường (label, key, type, options, mapping vào contact, validation, tag_from_value), thông báo thành công, URL redirect, tự động tạo thẻ/danh sách/segment, HTML tùy chỉnh.
- **Nhập HTML**: upload file HTML chứa form → **tự parse** thành các trường (`FormTemplateImportService`).
- Khi submit landing page: payload được validate theo field → tạo/upsert Contact + profile + tạo `ContactQualification` (status New) → gắn tag/list tự động → ghi `LandingPageSubmission` (contact_action: created/updated/skipped).

#### 5.2.4. Lượt gửi biểu mẫu (Form Submissions)
- **Xem danh sách** theo trang đích, trạng thái, người phân công.
- **Xử lý thủ công**: action "Sửa" → modal cập nhật `qualification_status` + gán nhân viên + ghi chú.
- **Tự động phân phối**: nút "Tự động phân phối" → chọn chiến lược (least_loaded/round_robin/weighted) + nhân viên (tùy chọn) + trang đích → `LeadDistributionService::distributeUnassigned` gán submission → cập nhật qualification `assigned`.

#### 5.2.5. Phân khúc (Segment)
- **Tạo phân khúc** với nhiều điều kiện (Repeater): trạng thái khách, vòng đời, loại khách, nhân viên owner, có thẻ, thuộc danh sách, consent, chuyển đổi trong N ngày / khoảng ngày, MST đã xác thực; toán tử equals/not_equals/gte/lte.
- **Xem trước**: "Xem trước số lượng" (đếm khách phù hợp), "Xem trước mẫu" (lấy 5 khách mẫu kèm email).

#### 5.2.6. Danh sách liên hệ (Contact List) & Thẻ (Tag)
- Tạo list (tên, slug, loại newsletter/service/event, trạng thái) hoặc tag (tên, slug, màu sắc, mô tả). Dùng để phân loại đối tượng chiến dịch.

#### 5.2.7. Báo cáo UTM (Marketing Utm Report)
- Widget trên tab Marketing của Admin Dashboard: chọn landing page → bảng thống kê lượt xem/submit theo `utm_source` (mặc định "Trực tiếp" khi không có UTM), cột tỷ lệ chuyển đổi + progress bar; nút **Xuất CSV**.

### 5.3. MODULE CRM

#### 5.3.1. Chăm sóc khách hàng (Customer Care)
- **Bàn làm việc**: xem danh sách khách hàng của mình (CS Staff) hoặc tất cả (Admin/CSM); chỉ số cần xử lý (quá hạn follow-up, báo giá chờ phản hồi, chưa thanh toán).
- **Gửi email 1-1**: mở workspace → tab Email → soạn (To/Cc/Bcc, đính kèm ≤20MB) → gửi qua **tài khoản SMTP của phòng ban** (`CustomerCareEmailService`; báo lỗi nếu chưa cấu hình SMTP hoặc phòng ban chưa có tài khoản). Email tự động có **tracking mở/nhấp** (`/m/care/open...`, `/m/care/click...`). Ghi `EmailEvent` + `CustomerInteraction` (email) + lịch sử email của khách.
- **Ghi cuộc gọi/tin nhắn**: tab Gọi & Nhắn → loại (call/message), trạng thái, nội dung, kết quả, follow-up → tạo `CustomerInteraction`.
- **Tạo báo giá nhanh**: tab Báo giá → nút Tạo báo giá → chuyển trang tạo quotation đã chọn sẵn khách hàng.
- **Dòng thời gian**: feed tổng hợp mọi hoạt động (interaction, email, quotation, phân công bắt đầu/kết thúc).
- **Gỡ phân công**: CS Staff có nút "Gỡ phân công" (lý do: khách từ chối / không thuộc phạm vi / không liên hệ được / quá tải / khác) → kết thúc phân công → khách về nhóm chưa phân công.

#### 5.3.2. Khách hàng (Customer)
- **Tạo**: mã tự sinh, loại, hồ sơ, trạng thái, vòng đời, consent, ưu tiên, thẻ (metadata.tags).
- **Phân công phụ trách** (Admin/CSM): chọn nhân viên → tạo phân công owner (reason `manual`).
- **Phân công hỗ trợ**: tạo phân công support (có thể nhiều).
- **Chuyển giao chủ quyền**: chọn nhân viên mới → kết thúc phân công cũ (ghi chú) → tạo phân công mới (reason `transfer`), giữ `original_owner_staff_id` của người đầu tiên.
- **Gỡ phân công**: kết thúc toàn bộ active assignment.
- **Tự động phân phối** khi: nhân viên nghỉ (staff_absence), nhân viên quay lại (staff_return), khách mới (new_customer), khởi tạo (initial), cân bằng tải (rebalance).
- **Import/Export**: dịch vụ CSV/Excel có sẵn (`CustomerImportService`/`CustomerExportService`) — hiện chưa nối giao diện.

#### 5.3.3. Pipeline đánh giá lead (Lead Qualification)
- **Bắt đầu liên hệ**: lead `new/assigned/follow_up` → `contacting` + ghi mốc liên hệ.
- **Xử lý liên hệ**: mở modal (khi chưa liên hệ lần nào) → chọn trạng thái mới, kết quả, follow-up, ghi chú → tạo note manual.
- **Chuyển thành khách hàng**: khi `qualified` + kết quả `confirmed_need`/`purchased` → modal chọn nhân viên đích → tạo `Customer` (status potential, lifecycle new_customer, consent subscribed) + `CustomerAssignment` (reason `new_customer`) + update qualification `converted`.
- **Theo dõi pipeline** qua tabs (Mới / Chưa liên hệ / Đang xử lý / Đủ điều kiện / Đã chuyển đổi) + Kanban widget.

#### 5.3.4. Phân phối khách hàng (Distribution)
- Tự động chạy trong transaction: chọn staff đủ điều kiện (active, nhận khách, không nghỉ, còn capacity) → tạo batch → phân theo chiến lược (RoundRobin / LeastLoaded / Weighted / Manual) → tạo `CustomerAssignment` + ghi item kết quả + audit.
- Chi tiết batch xem tại CRM → Phiên phân phối (chỉ xem).

#### 5.3.5. Tổ chức: Phòng ban / Chức danh / Nhân viên (Admin)
- **Phòng ban**: danh sách + drill-down 2 tab (Chức danh / Nhân viên) với nút tạo nhanh.
- **Nhân viên**: tạo từ tài khoản User (chọn user bằng email), phòng ban, chức danh (phải thuộc phòng ban), mã NV tự sinh, sức chứa, trọng số phân phối, trạng thái làm việc.
- **Hệ quả khi đổi trạng thái nhân viên**:
  - Active → Inactive/Resigned: tự kết thúc các phân công owner của NV và **phân phối lại khách hàng** cho NV khác (least_loaded, reason `staff_absence`).
  - Inactive → Active: trả lại khách hàng cũ (các phân công support có `original_owner_staff_id` = NV này) về làm owner (reason `staff_return`).
  - *Lưu ý: logic này nằm ở cả `StaffObserver` và `EditStaff::afterSave` — có thể chạy 2 lần (ghi chú tại mã).*
- **Tình trạng làm việc (Availability)**: khai báo absent/leave/sick/remote/half_day theo khoảng thời gian; ảnh hưởng đến tiêu chí nhận khách trong phân phối và thống kê dashboard.
- **Lịch nhân viên**: FullCalendar quản lý lịch chăm sóc (interactions) của từng NV — kéo-thả, tạo/sửa/xóa sự kiện.

#### 5.3.6. Xác thực mã số thuế (Tax Verification)
- Khi tạo cập nhật doanh nghiệp: job `VerifyBusinessTaxCodeJob` gọi provider (hiện tại: `FakeTaxVerificationProvider` — mock, cache 14 ngày) → cập nhật `tax_verification_status` (verified/not_found/mismatch/error). Chưa có nút UI kích hoạt (gate `crm.verify-business-tax` đã định nghĩa).

### 5.4. MODULE SALES (Báo giá)

#### 5.4.1. Danh mục: Dịch vụ / Gói dịch vụ (Admin quản lý)
- **Dịch vụ**: mã, tên, mô tả, phạm vi & điều khoản mặc định, trạng thái.
- **Gói dịch vụ**: thuộc dịch vụ, mã gói, đối tượng (cá nhân/doanh nghiệp/cả hai), chu kỳ thanh toán (ngày/tháng/năm/một lần), đơn vị tính (mặc định "tháng"), số lượng mặc định.

#### 5.4.2. Bảng giá (Price Book) & Quyền truy cập (Admin quản lý, CSM duyệt)
- **Tạo bảng giá**: mã, tên, đối tượng, tiền tệ, chế độ thuế (đã/ chưa gồm thuế), hiệu lực, trạng thái, mặc định; thêm **mục bảng giá** (gói dịch vụ + đơn giá + VAT + chiết khấu mặc định/tối đa + ghi đè phạm vi/điều khoản).
- **Quyền truy cập**: thêm rule (toàn bộ / theo role / theo phòng ban / theo nhân viên / theo chi nhánh) với quyền xem, tạo báo giá, giới hạn chiết khấu.
- **Cách dùng**: khi tạo báo giá, chỉ các bảng giá **đang active + trong hạn + đúng đối tượng + user có quyền xem** mới xuất hiện. `PriceBookAccessService` áp dụng quyền cho Admin (luôn OK) và các rule.

#### 5.4.3. Tạo báo giá (Quotation)
- Thao tác: Sales → Báo giá → Tạo → chọn **khách hàng**, **bảng giá** (hệ thống tự nạp danh sách dịch vụ theo mục bảng giá), **tài khoản ngân hàng** (để sinh QR), tiêu đề, ngày báo giá (mặc định hôm nay), hiệu lực đến (mặc định +30 ngày).
- Hệ thống tự tạo **mã báo giá** `QTYYYYNNNNN` (năm + số 5 chữ số tăng dần, lock), `version=1`, token công khai 64 hex, snapshot khách hàng/công ty/thanh toán/điều khoản.
- **Sửa items**: quantity, đơn giá, chiết khấu (fixed/%), VAT (%), ghi chú. Lưu sẽ **tính lại toàn bộ** (subtotal − discount + tax = grand_total) và chống chiết khấu vượt `maximum_discount_value`.

#### 5.4.4. Vòng đời & thao tác (state machine)
| Từ → Đến | Thao tác (ai làm) |
|---|---|
| `draft → pending_approval` | Nút "Gửi duyệt" (CS staff/manager/admin) — tạo approval step 1 |
| `pending_approval → approved` | Nút "Duyệt" (Admin/CSM) |
| `pending_approval → draft` | Nút "Từ chối duyệt" (bắt buộc lý do) |
| `approved → sent` | Nút "Gửi email" (Admin/CSM) — chọn mẫu quotation + subject/body |
| `sent → viewed` | Khách mở link công khai (tự động) |
| `sent/viewed → accepted` | Khách bấm "Chấp nhận" + điền người xác nhận (tự động: tạo confirmation, payment=unpaid, gửi mail nội bộ, ghi interaction) |
| `sent/viewed → rejected` | Khách bấm "Từ chối" + lý do |
| `sent/viewed → revision_requested` | Khách bấm "Yêu cầu chỉnh sửa" (bắt buộc lý do) |
| `revision_requested → superseded` (+ tạo bản mới) | `QuotationRevisionService::createRevision` (Admin; chưa có nút UI) |
| `* → expired` | Job `ExpireQuotationsJob` (hết `valid_until`) |
| `* (non-terminal) → cancelled` | Nút "Hủy" / "Huỷ hàng loạt" (Admin/CSM) |

- **Hệ quả trạng thái**: `isEditable()` = draft/pending_approval/revision_requested; `canSend()` = approved; `isTerminal()` = accepted/rejected/expired/cancelled/superseded.

#### 5.4.5. Gửi email báo giá
- Modal: chọn khách (mặc định khách của báo giá) → email nhận → chọn **mẫu quotation** (tự render subject/body) → Gửi.
- Hệ quả: tạo `QuotationEmailLog` (queued) → job `SendQuotationEmailJob` (queue `quotations`) gửi email **kèm PDF** → cập nhật email_status `sent`, log Sent, đồng bộ CRM (EmailEvent + Interaction).

#### 5.4.6. PDF báo giá
- Nút "Xuất PDF" → `QuotationPdfService` tạo file `{code}-V{version}.pdf` lưu `storage/app/private/quotations/{code}/` + ghi `QuotationDocument` (sha256). Layout: header BÁO GIÁ + code, thông tin nhà cung cấp/khách hàng, bảng dịch vụ, tổng (subtotal/discount/VAT/grand), **thông tin thanh toán + QR VietQR**, phạm vi/điều khoản, đại diện công ty, footer thời gian.

#### 5.4.7. Trang báo giá công khai (khách hàng)
- URL `/q/{code}/{token}` (token 64 hex, throttle 30/1 phút).
- Khách xem chi tiết, tải PDF, in; nếu trạng thái sent/viewed → 3 nút quyết định (chấp nhận/từ chối/yêu cầu sửa) với form người xác nhận. Mỗi hành động ghi `QuotationConfirmation` (code 32 hex) + cập nhật trạng thái + gửi mail thông báo nội bộ + `CustomerInteraction`.
- OTP: endpoint `send-otp`/`verify-otp` (giới hạn 3 lần/5 phút, OTP 6 số cache 5 phút, chỉ log — chưa gửi email thật) — **chưa có UI**.

#### 5.4.8. Phê duyệt báo giá (Approval List) — Admin/CSM
- Bảng các approval (mặc định status Pending) → nút "Duyệt" / "Từ chối" (từ chối quay về draft).
- Từ chối ở đây ghi lý do mặc định "Từ chối từ danh sách duyệt".

#### 5.4.9. Theo dõi thanh toán (Payment Tracking) — Admin/CSM
- Bảng các báo giá ở trạng thái Accept/Sent/Viewed với payment_status.
- Thao tác: **Đã thanh toán** (unpaid/pending/partial → paid), **Chờ xác nhận** (unpaid → pending_verification), **Chưa thanh toán** (pending_verification → unpaid); bulk "đánh dấu đã thanh toán".
- **Hệ quả khi Paid**: cập nhật khách hàng (status `active`, lifecycle `purchasing`, first/latest_purchase_at, total_revenue), gửi mail xác nhận cho khách + ghi EmailEvent/Interaction.

#### 5.4.10. Sales Dashboard — Admin/CSM
- Xem tổng báo giá/giá trị theo trạng thái, tỷ lệ gửi→xem, xem→chấp nhận, cảnh báo báo giá hết hạn trong 7 ngày, bảng "cần nhân viên theo dõi".

### 5.5. HỆ THỐNG & CẤU HÌNH
- **Người dùng (Admin)**: tạo tài khoản + gán vai trò.
- **Cài đặt công ty (Admin)**: thông tin công ty (tên, MST, địa chỉ, phone, email, website, logo) + **VietQR Client ID/API Key** (dùng tạo QR thanh toán qua `api.vietqr.io`).
- **Đa ngôn ngữ**: chuyển đổi VN/EN bất cứ lúc nào; nếu form đang sửa dở sẽ hỏi xác nhận.

---

## 6. Các luồng hoạt động chính

### 6.1. Luồng: Quảng cáo/Landing Page → Lead → Khách hàng → Chăm sóc
1. Marketing tạo Ad Campaign → gắn Landing Page (published, có UTM URLs) → chạy quảng cáo.
2. Khách bấm link UTM → view ghi `LandingPageView` (session, UTM, referrer) → xem trang + form theo `type` (personal/business).
3. Khách submit → `LandingPageSubmissionService::handle`:
   - Validate field theo mẫu biểu mẫu.
   - Upsert `Contact` (theo email) + profile tương ứng; nếu mới → tạo; consent `subscribed`.
   - Tạo `ContactQualification` (status `new`).
   - Áp dụng auto-tag / auto-list (tạo nếu cần).
   - Ghi submission (status `received`, contact_action, UTM).
   - Redirect (nếu có redirect_url) hoặc trang Cảm ơn.
4. Nhân viên (hoặc auto_distribute) xử lý lead: phân công → liên hệ (`contacting`) → xử lý (result, follow-up) → **qualified**.
5. `Chuyển thành khách hàng` → tạo `Customer` + phân công `owner` (reason `new_customer`, least-loaded) → vào bàn **Chăm sóc khách hàng** (email/gọi/báo giá).

### 6.2. Luồng: Phân phối khách hàng khi nhân viên nghỉ/quay lại
1. Admin đổi `employment_status` NV sang inactive/resigned (hoặc NV bị xóa).
2. `StaffObserver` (hoặc page) kích hoạt `CustomerDistributionService::distribute`:
   - Kiểu `staff_absence`, chiến lược `least_loaded`.
   - Chọn staff đủ điều kiện (active + nhận khách + không nghỉ + còn sức chứa).
   - Tạo batch → kết thúc phân công owner cũ (lý do staff_absence) → tạo phân công mới cho NV khác (support→owner mapping giữ `original_owner_staff_id`).
3. Khi NV quay lại (inactive→active): hệ thống tìm các phân công support có `original_owner_staff_id` = NV này → kết thúc → phân phối lại về NV (kiểu `staff_return`).

### 6.3. Luồng: Tạo & gửi chiến dịch Email Marketing
1. Tạo mẫu email (token cá nhân hóa + `{{unsubscribe_url}}` + `{{landing_page_url}}`).
2. Tạo campaign: mẫu + tài khoản gửi chung + đối tượng (list/tag/segment/landing-qualified) (+ landing page nếu cần link).
3. Lưu → `draft`. Chọn `scheduled_at` → `scheduled`.
4. Scheduler (`ProcessScheduledCampaignsJob`, mỗi phút) → `preparing` → `PrepareCampaignRecipientsJob`:
   - Resolve audience → lọc (email hợp lệ, consent subscribed, không suppression, không trùng) → tạo `CampaignRecipient` + token → render cá nhân hóa → dispatch `SendCampaignEmailJob` từng recipient → `sending`.
5. `SendCampaignEmailJob` (tries 3): tracking link wrap + pixel → gửi `MarketingCampaignMail` (from tài khoản) → recipient `sent`, EmailEvent `sent`; lỗi → `failed` + reason.
6. Khách mở/nhấp → pixel/redirect cập nhật recipient (opened/clicked) + EmailEvent + TrackedLink.
7. Khách bấm hủy đăng ký → suppression + consent `unsubscribed`.
8. Khi hết recipient → campaign `sent`/`failed`. Theo dõi ở Báo cáo chiến dịch.

### 6.4. Luồng: Chăm sóc khách hàng bằng email 1-1 (Customer Care)
1. CS Staff mở bàn làm việc → chọn khách → tab Email.
2. Soạn email (từ mẫu hoặc tay) → hệ thống kiểm tra tài khoản SMTP của phòng ban → gửi.
3. Email gắn tracking (pixel + link cloaked `/m/care/...`) → `EmailEvent` + `CustomerInteraction` (email) → hiển thị trong lịch sử email & dòng thời gian; khách mở/nhấp cập nhật.
4. Ghi cuộc gọi/tin nhắn tạo interaction; follow-up lên lịch → hiện trong lịch NV và "cần chăm sóc tiếp".

### 6.5. Luồng: Báo giá từ đầu đến thanh toán
1. **Tạo**: CS staff chọn khách + bảng giá (theo quyền truy cập) → items snapshot → mã QT tự sinh → `draft`.
2. **Duyệt nội bộ**: Submit duyệt (`pending_approval`) → CSM/Admin duyệt (`approved`) hoặc từ chối (về `draft`).
3. **Gửi khách**: chọn mẫu quotation → email kèm PDF (queue) → `sent` + log email + CRM sync.
4. **Khách xem**: mở link token → `viewed`.
5. **Quyết định**: Accept → confirmation + `accepted` + payment `unpaid` + mail nội bộ + interaction; Reject → `rejected` + mail nội bộ; Request revision → `revision_requested` + mail nội bộ (bản mới qua `QuotationRevisionService`).
6. **Hết hạn**: job `ExpireQuotationsJob` + mail thông báo (cảnh báo trước khi hết hạn qua `SendQuotationExpiringNotificationJob`).
7. **Thanh toán**: khách chuyển khoản (QR VietQR từ `QrPaymentService` / `api.vietqr.io`) → CSM/Admin vào Theo dõi thanh toán → Đánh dấu Paid → khách hàng cập nhật lifecycle `purchasing` + mail xác nhận.

### 6.6. Luồng: Theo dõi & báo cáo (Dashboard)
- Admin dashboard tabbed: Email (campaign stats), Marketing (landing page + UTM report), Khách hàng (customer stats + cảnh báo hệ thống), Nhân viên (tải trọng NV + lịch sắp tới) — tất cả poll 60 giây.
- Non-admin: auto-redirect đến trang phù hợp vai trò (StaffDashboard / SalesDashboard / CustomerCare / CampaignReport).

### 6.7. Luồng: Đa ngôn ngữ
- Bấm nút chuyển ngôn ngữ (menu người dùng) → POST `/language/switch` → lưu locale session → refresh component; cảnh báo nếu form chưa lưu.

### 6.8. Luồng: Các job nền (tổng hợp)
| Job | Kích hoạt | Chức năng |
|---|---|---|
| `ProcessScheduledCampaignsJob` | mỗi phút | Campaign scheduled → preparing → dispatch prepare |
| `PrepareCampaignRecipientsJob` | từ job trên | Build recipients + render + dispatch send |
| `SendCampaignEmailJob` | queue marketing | Gửi từng email + tracking + finalize |
| `VerifyBusinessTaxCodeJob` | queue | Xác thực MST (provider mock, cache 14 ngày) |
| `SendQuotationEmailJob` | queue quotations | Gửi email báo giá kèm PDF |
| `GenerateQuotationPdfJob` | queue | Sinh PDF |
| `ExpireQuotationsJob` | mỗi phút (`sales:process-reminders`) | Báo giá quá hạn → expired |
| `SendQuotationExpiringNotificationJob` / `SendQuotationExpiredNotificationJob` | job | Mail nội bộ (sales@company.com) |
| `SendQuotationAcceptedNotificationJob` / `Rejected` / `RevisionRequested` | public controller | Mail nội bộ khi khách phản hồi |
| `SendQuotationReminderJob` | schedule | Nhắc theo dõi |
| `SendPaymentConfirmedNotificationJob` | khi mark paid | Mail xác nhận thanh toán cho khách |

---

## 7. Phụ lục

### A. Các Service chính
- **Marketing**: `EmailSendingService`, `CampaignAudienceService`, `TemplateRenderService`, `SendingAccountService`, `SuppressionService`, `TrackingLinkService`, `UtmReportService`, `SegmentQueryService` (2 bản: Marketing\SegmentQueryService legacy Contact / Crm\SegmentQueryService dùng cho Customer — cái sau là engine thực tế), `CampaignLandingPageService`, `LandingPageRenderService`, `LandingPageSubmissionService`, `LandingPageTrackingService`, `AuditLogService`, `FormTemplateImportService`.
- **CRM**: `CustomerImportService`, `CustomerExportService`, `CustomerDistributionService`, `LeadDistributionService`, `CustomerCareService`, `CustomerCareEmailService`, `TaxCodeVerificationService`, `FakeTaxVerificationProvider`.
- **Sales**: `QuotationCreationService`, `QuotationPricingService`, `QuotationPdfService`, `QuotationMailService`, `QuotationStateMachine`, `QuotationApprovalService`, `QuotationConfirmationService`, `QuotationPaymentService`, `QuotationReminderService`, `QuotationRevisionService`, `QuotationPublicAccessService`, `QuotationEmailCrmSyncer`, `QuotationInteractionService`, `QuotationCodeGenerator`, `QuotationTemplateRenderer`, `PriceBookResolverService`, `PriceBookAccessService`, `QrPaymentService`, `ServiceCatalogService`.

### B. Observers & Policies
- `StaffObserver` (saving): xử lý chuyển trạng thái NV ↔ tái phân phối khách hàng.
- `CustomerAssignmentObserver` (creating/updating): chỉ giữ **1 phân công active** mỗi khách hàng (đóng các active cũ).
- Policies: `CustomerPolicy`, `CustomerAssignmentPolicy`, `CustomerInteractionPolicy`, `StaffPolicy`, `Sales\BankAccountPolicy`, `Sales\PriceBookPolicy`, `Sales\QuotationPolicy`, `Sales\ServicePolicy`.

### C. Controllers công khai
- `Marketing\Public\EmailTrackingController` (open/click/openCare/clickCare), `UnsubscribeController` (show/store), `LandingPageController` (show/submit/thankYou).
- `Sales\QuotationPublicController` (show/pdf/accept/reject/requestRevision/sendOtp/verifyOtp — throttle 30/1).
- `Marketing\Admin\*` (preview landing/template, delete utm url), `PageController` (welcome, languageSwitch).

### D. Các điểm cần lưu ý (đã phát hiện trong mã)
1. **Chưa nối UI**: Import/Export Customer, `QuotationRevisionService` (nút tạo phiên bản mới), OTP trên trang public, `ActivityLogRelationManager`, `ItemsRelationManager` (Distribution batch), Kanban widget, template-tokens palette, `LeadDistributionService` — dùng được nhưng chưa có nút nào gọi.
2. **Trùng lặp logic**: `StaffObserver` + `EditStaff::afterSave` đều xử lý chuyển trạng thái NV (rủi ro chạy 2 lần).
3. **Lỗi nhỏ**: default `price_books.tax_mode` = `tax_exclusive` không khớp enum `exclusive`; form Quotation hardcode `customerType = 'personal'` khi nạp bảng giá; `QuotationTemplateRenderer` dùng accessor `assignee` không tồn tại (token staff rỗng).
4. **Bảo mật/op: OTP chỉ log ra log server, chưa gửi mail thật; mẫu trạng thái nhiều tài khoản email gửi qua cùng mailer.

---

*Hết tài liệu hệ thống v2.* Ngày soạn: 2026.
