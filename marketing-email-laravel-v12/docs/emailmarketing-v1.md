# Hệ thống Email Marketing & CRM — Phiên bản 1.0

## Mục lục

1. [Tổng quan hệ thống](#1-tổng-quan-hệ-thống)
2. [Cấu trúc dữ liệu & CSDL](#2-cấu-trúc-dữ-liệu--csdl)
3. [Các tác nhân & chức năng](#3-các-tác-nhân--chức-năng)
4. [Mô tả chi tiết chức năng](#4-mô-tả-chi-tiết-chức-năng)
5. [Các luồng chính](#5-các-luồng-chính)

---

## 1. Tổng quan hệ thống

Hệ thống **Email Marketing & CRM (Customer Relationship Management)** được xây dựng trên nền tảng Laravel 12 + Filament 3, phục vụ nhu cầu quản lý quan hệ khách hàng, chăm sóc khách hàng tiềm năng qua landing page và gửi email marketing.

### 1.1. Kiến trúc tổng thể

```
┌──────────────────────────────────────────────────────────┐
│                   Filament Admin Panel                    │
│  ┌─────────────┐  ┌────────────┐  ┌──────────────────┐   │
│  │  CRM Module  │  │ Marketing  │  │  Landing Pages    │   │
│  │  (Filament)  │  │ (Filament) │  │  (Filament)      │   │
│  └──────┬───────┘  └─────┬──────┘  └────────┬─────────┘   │
│         │                │                   │             │
└─────────┼────────────────┼───────────────────┼─────────────┘
          │                │                   │
          ▼                ▼                   ▼
┌────────────────────────────────────────────────────────────┐
│                    Service Layer                            │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐  │
│  │Distribution││  Lead    ││  Landing ││  Email        │  │
│  │Service    ││Distribution││  Page    ││  Sending      │  │
│  │           ││ Service   ││Submission││  Service      │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────┘  │
└────────────────────────────────────────────────────────────┘
          │                │                   │
          ▼                ▼                   ▼
┌────────────────────────────────────────────────────────────┐
│                      Job Queue                              │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐    │
│  │ PrepareCampaign││SendCampaign ││ProcessScheduled  │     │
│  │ RecipientsJob ││ EmailJob    ││CampaignsJob       │     │
│  └──────────────┘ └──────────────┘ └──────────────────┘    │
└────────────────────────────────────────────────────────────┘
          │                │                   │
          ▼                ▼                   ▼
┌────────────────────────────────────────────────────────────┐
│                Public Routes (No Auth)                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐  │
│  │  Landing │ │  Email   │ │Unsubscribe││   Open/Click │  │
│  │  Page    │ │  Open    │ │  Page    ││   Tracking   │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────┘  │
└────────────────────────────────────────────────────────────┘
```

### 1.2. Ngôn ngữ & Công nghệ

- **Backend**: PHP 8.3, Laravel 12
- **Admin Panel**: Filament 3 (Livewire + Alpine.js)
- **Database**: MySQL
- **Queue**: Laravel Queue (database driver)
- **Frontend Public**: Blade templates
- **Đa ngôn ngữ**: Laravel JSON translation files (`lang/vi.json`, `lang/en.json`)

---

## 2. Cấu trúc dữ liệu & CSDL

### 2.1. Danh sách bảng

Hệ thống có **37 bảng** (không kể bảng mặc định của Laravel) chia làm 3 nhóm:

#### Nhóm CRM (16 bảng)

| # | Bảng | Mục đích |
|---|------|----------|
| 1 | `customers` | Khách hàng (hợp nhất từ Contact, personal & business) |
| 2 | `customer_assignments` | Phân công khách hàng cho nhân viên (owner/support) |
| 3 | `customer_interactions` | Lịch sử tương tác với khách hàng |
| 4 | `customer_distribution_batches` | Đợt phân bổ khách hàng hàng loạt |
| 5 | `customer_distribution_items` | Chi tiết từng khách hàng trong đợt phân bổ |
| 6 | `customer_lists` | Danh sách khách hàng (segment thủ công) |
| 7 | `customer_list_members` | Thành viên trong danh sách khách hàng |
| 8 | `customer_tag` | Liên kết tag-khách hàng (nhiều-nhiều) |
| 9 | `contacts` | Contact đầu vào (từ landing page, import) |
| 10 | `contact_qualifications` | Quá trình qualification của contact → customer |
| 11 | `contact_qualification_notes` | Ghi chú trong quá trình xử lý lead |
| 12 | `personal_contact_profiles` | Thông tin cá nhân của contact |
| 13 | `business_contact_profiles` | Thông tin doanh nghiệp của contact |
| 14 | `staff` | Nhân viên (mapping với users) |
| 15 | `staff_availabilities` | Lịch nghỉ/vắng của nhân viên |
| 16 | `positions` | Chức vụ trong công ty |
| 17 | `landing_page_forms` | Liên kết giữa Landing Page và Form Template |

#### Nhóm Marketing (18 bảng)

| # | Bảng | Mục đích |
|---|------|----------|
| 18 | `campaigns` | Chiến dịch email |
| 19 | `campaign_recipients` | Người nhận trong chiến dịch |
| 20 | `email_templates` | Mẫu email |
| 21 | `email_events` | Sự kiện email (gửi, mở, click, bounce...) |
| 22 | `sending_accounts` | Tài khoản gửi email (SMTP/API) |
| 23 | `sending_domains` | Domain gửi (SPF, DKIM, DMARC) |
| 24 | `tracked_links` | Link được track trong email |
| 25 | `segments` | Phân khúc khách hàng (rules động) |
| 26 | `contact_lists` | Danh sách contact (marketing) |
| 27 | `contact_list_members` | Thành viên danh sách |
| 28 | `tags` | Thẻ gắn cho khách hàng |
| 29 | `custom_fields` | Trường tùy chỉnh cho contact |
| 30 | `contact_custom_field_values` | Giá trị trường tùy chỉnh |
| 31 | `form_templates` | Mẫu form cho landing page |
| 32 | `form_fields` | Các trường trong form |
| 33 | `landing_pages` | Landing page |
| 34 | `landing_page_views` | Lượt xem landing page |
| 35 | `landing_page_submissions` | Dữ liệu submit từ landing page |
| 36 | `suppression_entries` | Danh sách email không gửi |
| 37 | `import_batches` | Lịch sử import contact |
| 38 | `audit_logs` | Nhật ký hoạt động (polymorphic) |

### 2.2. Quan hệ các bảng — Mô hình ER

#### 2.2.1. Hệ thống khách hàng (Customer-Centric)

```
contacts (1) ──── (0..1) personal_contact_profiles
     │                        (first_name, last_name, email, phone...)
     │
     ├── (0..1) ──── business_contact_profiles
     │                  (company_name, tax_code, legal_rep...)
     │
     ├── (0..1) ──── contact_qualifications
     │                  (status: new→assigned→contacting→qualified→converted)
     │                  ├── (0..n) contact_qualification_notes
     │                  │
     │                  └── mapped_staff (assigned_staff_id → staff.id)
     │
     ├── (0..1) ──── customers (khi được convert)
     │                  (status: potential→active→inactive→churned)
     │
     ├── (0..n) ──── campaign_recipients
     ├── (0..n) ──── email_events
     ├── (0..n) ──── suppression_entries
     ├── (0..n) ──── contact_custom_field_values
     └── (0..n) ──── landing_page_submissions
```

#### 2.2.2. Phân công khách hàng

```
customers (1) ──── (0..n) customer_assignments ──── (0..1) staff
                              │
                              ├── assignment_type: 'owner' | 'support'
                              ├── status: 'active' | 'ended' | 'cancelled'
                              │
                              └── (0..1) customer_distribution_batches
                                            │
                                            ├── type: initial | new_customer | staff_absence | staff_return | rebalance | manual
                                            ├── strategy: round_robin | least_loaded | weighted | manual
                                            └── (0..n) customer_distribution_items
```

#### 2.2.3. Nhân viên

```
users (1) ──── (0..1) staff ──── (0..1) positions
                  │                    (title, department, sort_order)
                  │
                  ├── (0..n) customer_assignments
                  ├── (0..n) customer_interactions
                  ├── (0..n) staff_availabilities
                  │              (status: working|absent|leave|sick|remote|half_day)
                  │
                  └── (0..n) contact_qualifications (as assigned_staff)
```

#### 2.2.4. Marketing Campaign

```
campaigns ──── email_templates
    │              (name, subject, html_body, text_body)
    │
    ├── sending_accounts
    │      (provider: smtp|ses|sendgrid|mailgun|postmark)
    │
    ├── landing_pages (optional)
    │      (name, slug, html_body, status: draft|published|archived)
    │
    ├── (0..n) campaign_recipients ──── contacts / customers
    │              (status: pending→queued→sent→delivered→opened→clicked)
    │
    ├── (0..n) email_events
    │              (event_type: queued|sent|delivered|opened|clicked|bounced|complained)
    │
    ├── (0..n) tracked_links
    │
    └── (0..1) landing_pages (campaign.landing_page_id)
```

#### 2.2.5. Landing Page & Form

```
landing_pages ──── (1) form_templates (landing_form_template_id)
    │                   │
    │                   ├── (0..n) form_fields
    │                   │              (field_type: text|email|phone|textarea|select|checkbox|hidden)
    │                   │              (contact_mapping: personal.first_name|business.tax_code|...)
    │                   │
    │                   └── audience_type: personal|business|generic
    │
    ├── (0..n) landing_page_forms (form gắn trực tiếp, dùng cho multi-form)
    ├── (0..n) landing_page_views
    ├── (0..n) landing_page_submissions ──── contacts
    │              (status: received|processed|failed|spam)
    │              (contact_action: created|updated|skipped)
    │
    └── (0..n) campaigns (referring campaign)
```

### 2.3. Bảng enums quan trọng

#### CRM Enums

| Enum | Cases | Ghi chú |
|------|-------|---------|
| `CustomerStatus` | potential, active, inactive, churned, blocked, archived | `isEmailMarketable()`: potential + active |
| `CustomerLifecycleStage` | new_customer, onboarding, nurturing, purchasing, retained, at_risk, churned |  |
| `CustomerConsentStatus` | pending, subscribed, unsubscribed, bounced, complained, do_not_contact | `isMarketable()`: subscribed |
| `CustomerAssignmentType` | owner, support |  |
| `CustomerAssignmentStatus` | active, ended, cancelled |  |
| `CustomerAssignmentReason` | initial_distribution, new_customer, manual, staff_absence, staff_return, rebalance, transfer |  |
| `ContactQualificationStatus` | **new**, assigned, contacting, follow_up, qualified, unqualified, converted, duplicate, spam, archived | `isConvertible()`: qualified; `isTerminal()`: converted, duplicate, spam, archived |
| `QualificationResult` | confirmed_need, purchased, no_need, unreachable, invalid_information |  |
| `InteractionStatus` | scheduled, completed, cancelled, no_show, rescheduled |  |
| `StaffEmploymentStatus` | active, inactive, resigned |  |
| `StaffAvailabilityStatus` | working, absent, leave, sick, remote, half_day | `canReceiveNewCustomers()`: working, remote |
| `StaffDepartment` | admin, marketing, customer_service, sales |  |
| `ContactType` | personal, business |  |
| `DistributionStrategy` | round_robin, least_loaded, weighted, manual |  |
| `DistributionBatchType` | initial, new_customer, staff_absence, staff_return, rebalance, manual |  |
| `DistributionBatchStatus` | draft, processing, completed, failed, reverted |  |
| `TaxVerificationStatus` | pending, verified, not_found, mismatch, error, manual_review |  |
| `CustomerConversionReason` | confirmed_need, purchased |  |

#### Marketing Enums

| Enum | Cases | Ghi chú |
|------|-------|---------|
| `CampaignStatus` | draft, testing, scheduled, preparing, sending, sent, paused, cancelled, failed | 9 trạng thái |
| `CampaignRecipientStatus` | pending, queued, sent, delivered, opened, clicked, failed, bounced, unsubscribed, skipped | 10 trạng thái |
| `EmailEventType` | queued, sent, delivered, opened, clicked, failed, bounced, complained, unsubscribed, skipped | 10 loại |
| `ContactConsentStatus` | subscribed, unsubscribed, pending, bounced, complained, do_not_contact |  |
| `ContactStatus` | active, inactive, archived |  |
| `LandingPageStatus` | draft, published, archived |  |
| `LandingPageSubmissionStatus` | received, processed, failed, spam |  |
| `LandingPageContactAction` | created, updated, skipped |  |
| `FormTemplateStatus` | draft, active, archived |  |
| `FormFieldType` | text, email, phone, textarea, select, checkbox, hidden | 7 loại |
| `FormAudienceType` | personal, business, generic |  |
| `SuppressionReason` | unsubscribe, bounce, complaint, manual, invalid_email, do_not_contact |  |
| `SendingAccountStatus` | active, inactive, testing |  |
| `DnsStatus` | unknown, pending, verified, failed | SPF/DKIM/DMARC |

---

## 3. Các tác nhân & chức năng

### 3.1. Phân loại người dùng

Hệ thống có **6 role** người dùng, lưu trong cột `role` của bảng `users`:

| Role | Mô tả | Quyền |
|------|-------|-------|
| `admin` | Quản trị viên | Toàn quyền |
| `marketing_manager` | Quản lý Marketing | Tất cả chức năng Marketing + xem CRM |
| `marketing_staff` | Nhân viên Marketing | Chức năng Marketing cơ bản |
| `customer_service_manager` | Quản lý CSKH | Tất cả chức năng CRM + xem Marketing |
| `customer_service_staff` | Nhân viên CSKH | Chức năng CRM cơ bản |
| `viewer` | Người xem | Chỉ đọc |

### 3.2. Ma trận chức năng theo tác nhân

| Chức năng | admin | mkt_mgr | mkt_staff | csvc_mgr | csvc_staff | viewer |
|-----------|-------|---------|-----------|----------|------------|--------|
| **Dashboard tổng quan** | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| **Quản lý Staff** | ✓ | ✗ | ✗ | ✓ | ✗ | ✗ |
| **Quản lý Customer** | ✓ | ✓ (xem) | ✗ | ✓ | ✓ | ✓ (xem) |
| **Phân bổ khách hàng** | ✓ | ✗ | ✗ | ✓ | ✗ | ✗ |
| **Xử lý Lead (Qualification)** | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ |
| **Convert Lead → Customer** | ✓ | ✓ | ✗ | ✓ | ✓ | ✗ |
| **Landing Page (tạo/sửa)** | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| **Campaign Email** | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| **Email Template** | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| **Sending Account/Domain** | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| **Import/Export Contact** | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| **Suppression List** | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## 4. Mô tả chi tiết chức năng

### 4.1. Module CRM

#### 4.1.1. Quản lý Staff (Nhân viên)

**File**: `app/Filament/Resources/StaffResource.php`

**Mục đích**: Quản lý thông tin nhân viên trong công ty (không phải user Filament).

**Trạng thái** (`employment_status`):
- `active` — Đang làm việc
- `inactive` — Tạm nghỉ (có thể quay lại)
- `resigned` — Đã nghỉ việc (vĩnh viễn)

**Các chức năng**:
1. **Xem danh sách nhân viên** — Bảng hiển thị: mã NV, họ tên, phòng ban, chức vụ, trạng thái, SĐT, số KH đang quản lý, số KH đang hỗ trợ
2. **Thêm/sửa nhân viên** — Form gồm các section: Thông tin cơ bản (phòng ban, chức vụ, trạng thái), Phân bổ (cho phép nhận KH, sức chứa, trọng số)
3. **Bộ lọc**: trạng thái (active/inactive/resigned), phòng ban, đang nghỉ phép
4. **Xem lịch nghỉ** — Relation manager hiển thị các đợt nghỉ của nhân viên
5. **Chuyển trạng thái** (active ↔ inactive/resigned):
   - **Active → Inactive/Resigned**: Hệ thống tự động (StaffObserver) kết thúc các assignment owner, phân bổ lại cho nhân viên khác qua `CustomerDistributionService`. Support assignment được giữ nguyên.
   - **Inactive → Active**: Hệ thống tự động kết thúc các support assignment tạm thời, phân bổ lại khách hàng về cho nhân viên.

**Người thực hiện**: admin, customer_service_manager

**Đầu vào**: Thông tin nhân viên, trạng thái mới khi chuyển đổi

**Đầu ra**: Staff record được tạo/cập nhật. Nếu thay đổi trạng thái, `StaffObserver::saving` trigger → khởi tạo `CustomerDistributionService` để phân bổ lại.

---

#### 4.1.2. Quản lý Customer (Khách hàng)

**File**: `app/Filament/Resources/CustomerResource.php`

**Mục đích**: Quản lý toàn bộ khách hàng (hợp nhất từ contact được convert + tạo thủ công).

**Khách hàng có 2 loại** (`customer_type`):
- `personal` — Khách hàng cá nhân
- `business` — Khách hàng doanh nghiệp

**Trạng thái** (`status`):
- `potential` — Tiềm năng
- `active` — Đang hoạt động
- `inactive` — Không hoạt động
- `churned` — Đã mất
- `blocked` — Bị chặn
- `archived` — Lưu trữ

**Các tab trong form**:
1. **Personal Info** — first_name, last_name, email, phone, date_of_birth, gender, địa chỉ, occupation
2. **Business Info** — company_name, tax_code, company_address, legal_representative, ngành nghề, mã số thuế
3. **Settings** — customer_code, acquisition_source, consent_status, priority, lifecycle_stage, tags, lists

**Các chức năng**:
1. **Xem danh sách** — Bảng hiển thị: mã KH, tên, email, SĐT, loại, trạng thái, người phụ trách (owner), nhân viên hỗ trợ, lần tương tác cuối
2. **Lọc**: trạng thái, loại KH, người phụ trách, tags, lists
3. **Tạo khách hàng** — Tạo thủ công với phân loại personal/business. Tự động gán `customer_code` và `display_name`
4. **Phân công** — Gán owner (quản lý chính) hoặc support (hỗ trợ) qua `CustomerAssignment`
5. **Gán tags/lists** — Nhiều-nhiều qua bảng `customer_tag` và `customer_list_members`
6. **Chuyển đổi trạng thái** — Khi chuyển từ potential → active, cập nhật lifecycle_stage tương ứng

**Người thực hiện**: admin, customer_service_manager, customer_service_staff

---

#### 4.1.3. Quản lý Lead (Qualification)

**File**: `app/Filament/Resources/LeadResource.php` (nếu có) hoặc quản lý qua `ContactQualification`

**Mục đích**: Xử lý các contact đầu vào (từ landing page, import) để đánh giá và chuyển đổi thành khách hàng.

**Trạng thái** (`ContactQualificationStatus`):
```
New → Assigned → Contacting → Qualified → Converted (→ Customer)
                              → FollowUp ─→ Qualified
                   → Unqualified (no_need, unreachable, invalid_information)
                   → Duplicate / Spam / Archived (terminal)
```

**Các chức năng**:
1. **Xem danh sách lead** — Pipeline dạng stage hoặc table
2. **Phân công cho nhân viên** — Gán `assigned_staff_id`
3. **Xử lý lead** (process_lead action) — Modal chứa:
   - Kết quả qualification (`QualificationResult`)
   - Ghi chú (lưu vào `ContactQualificationNote`)
   - Hẹn follow-up
   - Nếu `Qualified` → hiện nút **Convert to Customer**
4. **Convert to Customer** — Tạo `Customer` mới từ `Contact` + `ContactQualification`, copy thông tin từ profile tương ứng
5. **Xem ghi chú** — Lịch sử xử lý của lead

**Người thực hiện**: admin, marketing_manager, customer_service_manager, customer_service_staff

---

#### 4.1.4. Phân bổ khách hàng (Distribution)

**File**: `app/Services/Crm/CustomerDistributionService.php`

**Mục đích**: Phân bổ/hỗ trợ khách hàng cho nhân viên theo chiến lược.

**Chiến lược** (`DistributionStrategy`):
- `round_robin` — Lần lượt từng nhân viên
- `least_loaded` — Nhân viên có ít việc nhất
- `weighted` — Theo trọng số (cân bằng theo `distribution_weight` của staff)
- `manual` — Gán tuần tự

**Loại đợt phân bổ** (`DistributionBatchType`):
- `initial` — Phân bổ lần đầu
- `new_customer` — Khách hàng mới
- `staff_absence` — Nhân viên nghỉ
- `staff_return` — Nhân viên quay lại
- `rebalance` — Cân bằng lại
- `manual` — Thủ công

**Luồng xử lý**:
1. Người dùng chọn danh sách khách hàng + nhân viên + chiến lược
2. Hệ thống tạo `CustomerDistributionBatch` (status: processing)
3. Với mỗi khách hàng, chọn nhân viên theo chiến lược (kiểm tra capacity)
4. Tạo `CustomerAssignment` (active) + `CustomerDistributionItem`
5. Cập nhật `CustomerDistributionBatch` (status: completed, total_assigned)

**Người thực hiện**: admin, customer_service_manager

---

#### 4.1.5. Phân bổ Lead (Lead Distribution)

**File**: `app/Services/Crm/LeadDistributionService.php`

**Mục đích**: Tự động phân bổ lead (submission từ landing page) cho nhân viên chưa gán.

**Luồng**:
1. Quét các `LandingPageSubmission` có `assigned_staff_id = null`
2. Lọc nhân viên đủ điều kiện (active, can_receive_customers, đang không nghỉ)
3. Áp dụng chiến lược (mặc định: LeastLoaded)
4. Gán `assigned_staff_id` cho submission + tạo/cập nhật `ContactQualification` với status `Assigned`

**Người thực hiện**: Hệ thống (có thể chạy thủ công hoặc scheduled)

---

#### 4.1.6. Tương tác khách hàng (Interactions)

**File**: `app/Models/Crm/CustomerInteraction.php`

**Mục đích**: Ghi lại lịch sử tương tác với khách hàng.

**Trạng thái** (`InteractionStatus`):
- `scheduled` — Đã lên lịch
- `completed` — Hoàn thành
- `cancelled` — Hủy
- `no_show` — Không đến
- `rescheduled` — Dời lịch

**Chức năng**: 
- Xem lịch sử tương tác theo khách hàng
- Lên lịch chăm sóc (next_follow_up_at)
- Lọc lịch hẹn sắp tới
- Dashboard hiển thị widget lịch hẹn

**Người thực hiện**: Customer service staff, manager

---

### 4.2. Module Marketing

#### 4.2.1. Landing Page

**File**: `app/Filament/Resources/LandingPageResource.php` + `app/Http/Controllers/Marketing/Public/LandingPageController.php`

**Mục đích**: Tạo và quản lý landing page để thu thập contact/lead.

**Trạng thái** (`LandingPageStatus`):
- `draft` — Nháp
- `published` — Đã xuất bản (có thể truy cập public)
- `archived` — Lưu trữ

**Cấu hình**:
- **Nội dung**: headline, subheadline, content, CTA text, HTML body, CSS body
- **Form**: Liên kết với `FormTemplate` (hoặc nhúng trực tiếp)
- **Auto-action**: Tự động gán tags, lists, segment khi có submission
- **UTM tracking**: Lưu utm_source, utm_medium, utm_campaign...

**Luồng public**:
1. Người dùng truy cập `/lp/{slug}`
2. `LandingPageController::show` render landing page (Blade view hoặc raw HTML)
3. Người dùng submit form → `LandingPageController::submit` → `LandingPageSubmissionService::handle`
4. Hệ thống xác thực field, tìm/tạo Contact, lưu profile, gán tags/lists/custom fields
5. Tạo `ContactQualification` (status: New) → chờ xử lý
6. Nếu `auto_create_segment = true`, tạo Segment tự động

**Kết quả đầu ra**:
- `LandingPageSubmission` (status: processed)
- `Contact` (mới hoặc cập nhật)
- `PersonalContactProfile` hoặc `BusinessContactProfile`
- `ContactQualification` (mới)

**Người thực hiện**: Marketing manager, marketing staff (quản lý); Public user (submit)

---

#### 4.2.2. Form Template

**File**: `app/Models/Marketing/FormTemplate.php`

**Mục đích**: Định nghĩa mẫu form có thể tái sử dụng trên nhiều landing page.

**Loại đối tượng** (`FormAudienceType`):
- `personal` — Form cho cá nhân (họ tên, email, SĐT...)
- `business` — Form cho doanh nghiệp (mã số thuế, công ty...)
- `generic` — Form kết hợp (người dùng chọn loại khi submit)

**Trường form** (`form_fields`):
- `field_type`: text, email, phone, textarea, select, checkbox, hidden
- `contact_mapping`: Ánh xạ tới trường trong profile (personal.first_name, business.tax_code, custom_field:my_field...)
- `tag_from_value`: Tự động tạo tag từ giá trị nhập vào

**Chức năng**: 
- CRUD form template với drag-drop fields
- Import form từ template JSON
- Xem trước form

**Người thực hiện**: Marketing manager, marketing staff

---

#### 4.2.3. Campaign Email

**File**: `app/Models/Marketing/Campaign.php` + CampaignSendController + CampaignReportController

**Mục đích**: Tạo và gửi chiến dịch email marketing.

**Trạng thái** (`CampaignStatus` — 9 trạng thái):
```
Draft → Testing → Preparing → Sending → Sent
                         → Paused → Sending
                        → Failed
           → Scheduled → Preparing (tại thời điểm schedule)
   → Cancelled (từ bất kỳ trạng thái nào trước Sent)
```

**Cấu hình chiến dịch**:
- **Template**: Chọn `EmailTemplate` (subject, preheader, HTML body)
- **Sending Account**: Chọn tài khoản gửi
- **Audience**: Chọn loại đối tượng (segment, contact list, hoặc tất cả)
- **Landing Page**: (Tùy chọn) Landing page liên kết

**Luồng gửi**:
1. Tạo campaign (status: draft)
2. Gửi test → 1 email kiểm tra
3. Lên lịch hoặc gửi ngay
4. `PrepareCampaignRecipientsJob`: Tạo `CampaignRecipient` từ audience, chèn link tracking, unsubscribe link
5. `SendCampaignEmailJob`: Gửi từng email qua `EmailSendingService`
6. `EmailTrackingController`: Xử lý sự kiện open, click (tracking token)

**Báo cáo**: 
- Tổng người nhận, đã gửi, đã mở, đã click
- Tỷ lệ mở (open rate), tỷ lệ click (click rate)
- Bounce, hủy đăng ký
- Chi tiết theo từng người nhận

**Người thực hiện**: Marketing manager, marketing staff

---

#### 4.2.4. Email Template

**File**: `app/Models/Marketing/EmailTemplate.php`

**Mục đích**: Quản lý mẫu email cho chiến dịch.

**Thành phần**: name, subject, preheader, html_body, text_body

**Chức năng**: CRUD, xem trước (render HTML)

**Người thực hiện**: Marketing manager, marketing staff

---

#### 4.2.5. Sending Account & Domain

**File**: `SendingAccount.php`, `SendingDomain.php`

**Mục đích**: Quản lý tài khoản gửi email và xác thực domain.

**Sending Account**:
- Provider: SMTP, SES, SendGrid, Mailgun, Postmark
- Cấu hình mã hóa (config_encrypted)
- Giới hạn gửi (daily_limit, hourly_limit)
- Kiểm tra kết nối (test)

**Sending Domain**:
- Xác thực SPF, DKIM, DMARC (`DnsStatus`: unknown → pending → verified → failed)
- Ghi chú

**Người thực hiện**: Admin, marketing_manager

---

#### 4.2.6. Contact (Marketing)

**File**: `app/Http/Controllers/Marketing/Admin/ContactController.php`

**Mục đích**: Quản lý danh sách contact (đầu vào cho marketing).

**Chức năng**:
- CRUD contact (personal/business)
- Import từ file CSV/Excel (qua `ContactImportService`)
- Export danh sách (qua `ContactExportService`)
- Bulk tag / bulk list
- Custom fields (trường tùy chỉnh)

**Mối quan hệ với Customer**:
- Contact → có thể có 1 Customer (khi được convert)
- Contact là đầu vào; Customer là kết quả sau qualification
- Contact có thể tồn tại mà không có Customer

**Người thực hiện**: Marketing manager, marketing staff

---

#### 4.2.7. Segment & List

**File**: `Segment.php`, `ContactList.php`

**Mục đích**: Phân nhóm đối tượng cho chiến dịch.

- **Segment**: Điều kiện động (rules JSON) — lọc real-time
- **List**: Danh sách tĩnh (gán thủ công qua pivot)

**Người thực hiện**: Marketing manager, marketing staff

---

#### 4.2.8. Suppression & Unsubscribe

**File**: `SuppressionEntry.php`, `UnsubscribeController.php`

**Mục đích**: Quản lý danh sách không gửi email.

**Lý do suppression** (`SuppressionReason`):
- `unsubscribe` — Người dùng tự hủy đăng ký
- `bounce` — Email bounce cứng
- `complaint` — Bị báo cáo spam
- `manual` — Thêm thủ công
- `invalid_email` — Email không hợp lệ
- `do_not_contact` — Không được liên hệ

**Luồng unsubscribe**:
1. Email có chứa unsubscribe link với token
2. Người dùng click → `UnsubscribeController::show` (trang xác nhận)
3. Người dùng xác nhận → `UnsubscribeController::store`
4. Tạo `SuppressionEntry` + cập nhật `consent_status` = unsubscribed

**Người thực hiện**: Public (unsubscribe), Admin (quản lý suppression list)

---

#### 4.2.9. Email Tracking

**File**: `EmailTrackingController.php`

**Mục đích**: Theo dõi sự kiện mở email và click link.

**Open tracking**: 
- `GET /m/open/{token}.gif` — 1x1 pixel GIF, ghi `EmailEvent` (event_type: opened)
- Cập nhật `campaign_recipients.opened_at`

**Click tracking**:
- `GET /m/click/{token}` — Redirect qua link gốc
- Ghi `EmailEvent` (event_type: clicked) + `TrackedLink` (click_count++)
- Cập nhật `campaign_recipients.clicked_at`

**Người thực hiện**: Hệ thống (tự động)

---

### 4.3. Module Dashboard

#### 4.3.1. Admin Dashboard

**File**: `app/Filament/Pages/AdminDashboard.php`

**Mục đích**: Trang tổng quan dành riêng cho admin (chỉ admin mới xem được).

**Giao diện tab**: 3 tab (Alpine.js):

| Tab | Widgets |
|-----|---------|
| **Khách hàng** | `DashboardCustomerStatsWidget` (tổng KH, hoạt động, tiềm năng, lead mới, đang liên hệ, đã convert, không đạt, đang xử lý) |
| **Marketing** | `DashboardCampaignWidget` (chiến dịch, người nhận, tỷ lệ mở/click) + `DashboardLandingPageWidget` (lượt xem, submission) |
| **Nhân viên** | `DashboardStaffWorkloadWidget` (nhân viên, phân công) + `DashboardStaffDetailTableWidget` (chi tiết từng NV) + `DashboardUpcomingScheduleWidget` (lịch hẹn) |

---

## 5. Các luồng chính

### 5.1. Luồng: Landing Page → Lead → Customer

```
┌─────────────────────────────────────────────────────────────────────┐
│                  LUỒNG THU THẬP & XỬ LÝ LEAD                        │
└─────────────────────────────────────────────────────────────────────┘

                    ┌──────────────────┐
                    │  Landing Page    │  (marketing tạo, publish)
                    │  /lp/{slug}      │
                    └────────┬─────────┘
                             │
                    Người dùng điền form, submit
                             │
                             ▼
               ┌─────────────────────────┐
               │  LandingPageSubmission   │  status: received
               │  + Contact (tạo/cập nhật)│
               │  + Profile (personal/    │
               │    business)             │
               │  + ContactQualification  │
               │    (status: new)         │
               └──────────┬──────────────┘
                          │
              ┌───────────┴───────────┐
              │                       │
              ▼                       ▼
    ┌─────────────────┐     ┌──────────────────┐
    │Phân bổ tự động   │     │Chờ xử lý thủ công │
    │LeadDistribution  │     │(trong LeadResource)│
    │Service            │     │                  │
    │status: assigned   │     │status: new       │
    └────────┬─────────┘     └────────┬─────────┘
             │                        │
             └───────────┬────────────┘
                         │
                         ▼
              ┌────────────────────┐
              │  Xử lý lead        │  (Process Lead action)
              │  - Chọn kết quả    │
              │  - Ghi chú          │
              │  - Hẹn follow-up   │
              └────────┬───────────┘
                       │
              ┌────────┴──────────┐
              │                   │
              ▼                   ▼
    ┌─────────────────┐   ┌───────────────┐
    │  Qualified       │   │  Unqualified   │
    │  (status: qualified) │  (no_need,      │
    └────────┬─────────┘   │  unreachable... │
             │             └───────┬───────┘
             │                     │
             ▼                     ▼
    ┌─────────────────┐   ┌───────────────┐
    │  Convert to      │   │  Kết thúc      │
    │  Customer        │   │  (terminal)    │
    │  - Tạo Customer  │   └───────────────┘
    │  - Copy profile   │
    │  - status: convert│
    └────────┬─────────┘
             │
             ▼
    ┌─────────────────┐
    │  Customer mới    │
    │  - Chờ phân bổ   │
    │  - Chăm sóc       │
    └─────────────────┘
```

### 5.2. Luồng: Phân bổ khách hàng (Staff nghỉ → Chuyển giao)

```
┌─────────────────────────────────────────────────────────────────────────┐
│               LUỒNG XỬ LÝ KHI NHÂN VIÊN NGHỈ                           │
└─────────────────────────────────────────────────────────────────────────┘

    StaffObserver::saving phát hiện employment_status thay đổi
    (active → inactive/resigned)
                              │
                              ▼
    ┌─────────────────────────────────────────────┐
    │  handleDeactivated()                         │
    │                                              │
    │  1. Kết thúc tất cả OWNER assignments         │
    │     (status: ended, ended_at: now)           │
    │                                              │
    │  2. Support assignments giữ nguyên            │
    │     (để thống kê)                             │
    │                                              │
    │  3. Tìm danh sách nhân viên khả dụng          │
    │     (active + can_receive_customers +         │
    │     không đang nghỉ + còn capacity)           │
    │                                              │
    │  4. Gọi CustomerDistributionService::         │
    │     distribute()                              │
    │     - type: StaffAbsence                     │
    │     - strategy: LeastLoaded                  │
    │     - staffIds: nhân viên khả dụng           │
    └──────────────────────┬──────────────────────┘
                           │
                           ▼
    ┌──────────────────────────────────────────────┐
    │  CustomerDistributionService::distribute()    │
    │                                                │
    │  1. Tạo CustomerDistributionBatch              │
    │     (status: processing)                       │
    │  2. Với mỗi customer:                         │
    │     - Áp dụng chiến lược LeastLoaded           │
    │     - Tạo CustomerAssignment mới               │
    │       (type: support, staff: NV được chọn)    │
    │     - Tạo CustomerDistributionItem             │
    │  3. Cập nhật batch (status: completed)         │
    └──────────────────────┬────────────────────────┘
                           │
                           ▼
    Khi nhân viên quay lại (inactive → active)
                              │
                              ▼
    ┌──────────────────────────────────────────────┐
    │  handleReactivated()                          │
    │  1. Tìm support assignments gốc của NV        │
    │  2. Kết thúc support assignments đó            │
    │  3. Phân bổ lại (StaffReturn) về cho NV chính │
    └──────────────────────────────────────────────┘
```

### 5.3. Luồng: Tạo & gửi chiến dịch Email Marketing

```
┌──────────────────────────────────────────────────────────────────────┐
│              LUỒNG CHIẾN DỊCH EMAIL MARKETING                        │
└──────────────────────────────────────────────────────────────────────┘

    ┌────────────┐
    │  Draft     │  ← Tạo campaign, chọn template, sending account, audience
    └─────┬──────┘
          │
          ▼
    ┌────────────┐
    │  Testing   │  ← Gửi email test (tới email của người tạo)
    └─────┬──────┘
          │
    ┌─────┴──────────┐
    │  Lên lịch /    │
    │  Gửi ngay      │
    └─────┬──────────┘
          │
          ▼
    ┌────────────────┐
    │  Scheduled     │  (nếu lên lịch tương lai)
    └─────┬──────────┘
          │ Tới giờ
          ▼
    ┌───────────────┐
    │  Preparing    │  ← PrepareCampaignRecipientsJob chạy:
    └─────┬─────────┘    1. Query audience (segment/list/tất cả)
                         2. Tạo CampaignRecipient cho mỗi contact/customer
                         3. Render HTML cá nhân hóa (unsubscribe token, tracking token)
                         4. Thay thế link → tracked link
                         5. Loại bỏ email trong suppression list
          │
          ▼
    ┌───────────────┐
    │  Sending      │  ← SendCampaignEmailJob chạy cho từng recipient:
    └─────┬─────────┘    1. Lấy sending account config
                         2. Gửi email qua provider
                         3. Cập nhật status (sent/failed)
                         4. Ghi EmailEvent
          │
          ▼
    ┌───────────────┐
    │  Sent         │  ← Hoàn tất
    └─────┬─────────┘
          │
          ▼
    Người nhận click link / mở email
          │
          ▼
    EmailTrackingController ghi nhận sự kiện
    - Open: 1x1 pixel GIF → EmailEvent (opened) + campaign_recipients.opened_at
    - Click: Redirect → EmailEvent (clicked) + TrackedLink (click_count++)
```

### 5.4. Luồng: Đa ngôn ngữ

```
┌─────────────────────────────────────────────────────────────────────┐
│                    LUỒNG CHUYỂN ĐỔI NGÔN NGỮ                        │
└─────────────────────────────────────────────────────────────────────┘

    User click "Tiếng Việt" / "English" trên user menu
                              │
                              ▼
    Route: GET /language/switch → Language Switch Controller
                              │
                              ▼
    1. Đọc session('locale') → mặc định config('app.locale')
    2. Nếu en → vi, nếu vi → en
    3. Lưu session(['locale' => new_locale])
    4. app()->setLocale(new_locale)
    5. Redirect back
                              │
                              ▼
    SetLocale Middleware (applied trên Filament panel)
    - Đọc session('locale') từ đầu mỗi request
    - app()->setLocale(session('locale'))
                              │
                              ▼
    Laravel __() helper dùng file lang/{locale}.json
    - vi.json → Tiếng Việt
    - en.json → English
```

### 5.5. Luồng: Import Contact

```
┌─────────────────────────────────────────────────────────────────────┐
│                   LUỒNG IMPORT CONTACT                              │
└─────────────────────────────────────────────────────────────────────┘

    Admin upload file CSV/Excel qua form import
                              │
                              ▼
    ContactImportController → ContactImportService
                              │
                              ▼
    1. Parse file, validate header
    2. Với mỗi dòng:
       - Validate dữ liệu (email format, required fields...)
       - Kiểm tra duplicate (email, phone)
       - Tìm/tạo Contact
       - Tạo PersonalContactProfile / BusinessContactProfile
       - Gán tags (nếu có)
       - Thêm vào list (nếu có)
    3. Tạo ImportBatch (success_rows, failed_rows)
    4. Trả về kết quả (thành công/thất bại từng dòng)
```

### 5.6. Luồng: Hủy đăng ký (Unsubscribe)

```
┌─────────────────────────────────────────────────────────────────────┐
│                      LUỒNG UNSUBSCRIBE                              │
└─────────────────────────────────────────────────────────────────────┘

    Người nhận click link "Hủy đăng ký" trong email
                              │
                              ▼
    GET /m/unsubscribe/{token} → UnsubscribeController::show
                              │
                              ▼
    Trang xác nhận hiển thị email của người dùng
                              │
    User xác nhận
                              │
                              ▼
    POST /m/unsubscribe/{token} → UnsubscribeController::store
                              │
                              ▼
    1. Tìm CampaignRecipient theo token
    2. Tạo SuppressionEntry (reason: unsubscribe)
    3. Cập nhật campaign_recipients.status = unsubscribed
    4. Cập nhật consent_status của Contact/Customer = unsubscribed
```

---

## Phụ lục

### A. Danh sách Controllers

#### Marketing Admin Controllers

| Controller | Routes | Mô tả |
|-----------|--------|-------|
| `ContactController` | CRUD | Quản lý contact |
| `ContactImportController` | import, history | Import CSV/Excel |
| `ContactExportController` | export | Export danh sách |
| `ContactBulkActionController` | bulk-tag, bulk-list | Thao tác hàng loạt |
| `TagController` | CRUD (trừ show) | Quản lý tag |
| `ContactListController` | CRUD + add/remove contacts | Danh sách contact |
| `SegmentController` | CRUD + preview | Phân khúc động |
| `CustomFieldController` | CRUD (trừ show) | Trường tùy chỉnh |
| `EmailTemplateController` | CRUD | Mẫu email |
| `EmailTemplatePreviewController` | preview | Xem trước email |
| `CampaignController` | CRUD + audienceOptions | Chiến dịch |
| `CampaignSendController` | send-test, schedule, send-now, pause | Gửi/lên lịch |
| `CampaignReportController` | show | Báo cáo |
| `SendingAccountController` | CRUD + test | Tài khoản gửi |
| `SendingDomainController` | CRUD | Domain gửi |
| `SuppressionEntryController` | index, store, destroy | Suppression list |
| `LandingPagePreviewController` | preview | Xem trước LP |

#### Marketing Public Controllers

| Controller | Routes | Mô tả |
|-----------|--------|-------|
| `LandingPageController` | show, submit, thank-you | Public landing page |
| `EmailTrackingController` | open, click | Open/click tracking |
| `UnsubscribeController` | show, store | Hủy đăng ký |

### B. Danh sách Jobs

| Job | Queue | Mô tả |
|-----|-------|-------|
| `PrepareCampaignRecipientsJob` | default | Tạo recipient list cho campaign |
| `SendCampaignEmailJob` | default | Gửi email cho từng recipient |
| `ProcessScheduledCampaignsJob` | default | Kiểm tra & kích hoạt campaign đã lên lịch |
| `VerifyBusinessTaxCodeJob` | default | Xác thực mã số thuế doanh nghiệp |

### C. Danh sách Services

| Service | Module | Mô tả |
|---------|--------|-------|
| `CustomerDistributionService` | CRM | Phân bổ khách hàng hàng loạt |
| `LeadDistributionService` | CRM | Phân bổ lead từ landing page |
| `TaxCodeVerificationService` | CRM | Xác thực mã số thuế |
| `SegmentQueryService` | CRM/MKT | Truy vấn segment động |
| `CustomerImportService` | CRM | Import khách hàng |
| `CustomerExportService` | CRM | Export khách hàng |
| `LandingPageSubmissionService` | MKT | Xử lý form submission |
| `LandingPageTrackingService` | MKT | Theo dõi UTM |
| `LandingPageRenderService` | MKT | Render landing page |
| `EmailSendingService` | MKT | Gửi email qua provider |
| `CampaignAudienceService` | MKT | Lấy đối tượng cho campaign |
| `AuditLogService` | MKT | Ghi nhật ký |
| `SegmentQueryService` | MKT | Truy vấn segment (marketing) |
| `SuppressionService` | MKT | Kiểm tra suppression |
| `TemplateRenderService` | MKT | Render template email |
| `TrackingLinkService` | MKT | Tạo/parse link tracking |
| `SendingAccountService` | MKT | Test kết nối SMTP |
| `ContactImportService` | MKT | Import contact |
| `ContactExportService` | MKT | Export contact |
| `FormTemplateImportService` | MKT | Import form template từ JSON |

### D. Danh sách Observers

| Observer | Model | Sự kiện | Tác dụng |
|----------|-------|---------|----------|
| `StaffObserver` | Staff | `saving` | Tự động phân bổ lại khách hàng khi staff active/inactive/resigned |
