# Hệ thống Email Marketing & CRM & Sales — Mô tả Giao diện (UI/UX)

> Tài liệu mô tả **chi tiết giao diện người dùng** của hệ thống hiện tại
> (Laravel 12 + Filament v3 — Admin Panel). Tài liệu này mô tả từng màn hình:
> vị trí menu, bố cục, form, bảng, lọc, hành động, quyền hiển thị, màu sắc trạng thái.
>
> Dự án: `marketing-email-laravel-v12` — Panel `admin`, đường dẫn `/admin`,
> màu chủ đạo **Amber** (hổ phách), giao diện tiếng Việt/tiếng Anh (i18n qua `lang/vi.json`, `lang/en.json`).

---

## Mục lục

1. [Tổng quan giao diện](#1-tổng-quan-giao-diện)
2. [Cấu trúc menu điều hướng](#2-cấu-trúc-menu-điều-hướng)
3. [Khối Email Marketing](#3-khối-email-marketing)
4. [Khối Marketing / Landing Page](#4-khối-marketing--landing-page)
5. [Khối CRM](#5-khối-crm)
6. [Khối Kinh doanh (Sales)](#6-khối-kinh-doanh-sales)
7. [Khối Hệ thống & Cấu hình](#7-khối-hệ-thống--cấu-hình)
8. [Các trang Dashboard](#8-các-trang-dashboard)
9. [Các trang công khai (Public)](#9-các-trang-công-khai-public)
10. [Quy ước giao diện chung](#10-quy-ước-giao-diện-chung)

---

## 1. Tổng quan giao diện

### 1.1. Kiến trúc giao diện
- **Admin Panel** dựng trên **Filament v3** — một panel quản trị chuẩn hóa: thanh sidebar trái, header trên, nội dung trung tâm.
- Toàn bộ giao diện quản trị được định nghĩa bằng code PHP (form/table schema) trong `app/Filament/Resources`, `app/Filament/Pages`, `app/Filament/Widgets`.
- Một số trang sử dụng **Blade tùy biến** thay cho giao diện mặc định: `admin-dashboard`, `campaign-report-page`, `customer-care`, `sales-dashboard`, `staff-dashboard`, `list-departments`, `utm-report-widget`, `utm-links-modal`, `email-preview`, `template-tokens`.
- Bảng màu: primary = **Amber** (`#f59e0b` vùng), badge màu theo ngữ nghĩa trạng thái (success/info/warning/danger/gray/primary).
- Đa ngôn ngữ: nút chuyển ngôn ngữ ở góc phải **menu người dùng** (Tiếng Việt / English). Nếu biểu mẫu còn dữ liệu chưa lưu, hệ thống hiện hộp thoại xác nhận khi chuyển ngôn ngữ.

### 1.2. Các thành phần giao diện mặc định của Filament được dùng
| Thành phần | Mục đích |
|---|---|
| `ListRecords` | Trang danh sách (bảng + lọc + tìm kiếm + phân trang) |
| `CreateRecord` / `EditRecord` / `ViewRecord` | Form tạo / sửa / xem chi tiết |
| `Section` | Khối form chia theo chủ đề |
| `Repeater` | Khối lặp (điều kiện phân khúc, dòng báo giá, trường biểu mẫu, mục bảng giá) |
| `Tabs` | Phân trang con theo trạng thái (Pipeline) |
| `RelationManager` | Tab panh chi tiết trong trang xem/sửa (Items, Approvals, Confirmations...) |
| `Modal` | Hành động mở hộp thoại (gửi email, duyệt, từ chối, sinh URL UTM...) |
| `FullCalendar` | Lịch làm việc cú nhân viên (plugin `saade/filament-fullcalendar`) |
| `StatsOverviewWidget` | Thẻ thống kê (number + label) ở bảng điều khiển |

### 1.3. Quy tắc hiển thị theo vai trò
- **Admin**: thấy toàn bộ menu, mọi widget thống kê.
- **Marketing Manager**: thấy nhóm Email/Marketing/CRM + các action manage; KHÔNG thấy Cài đặt công ty, Audit, User.
- **Marketing Staff**: thấy Email/Marketing/CRM nhưng giới hạn: không gửi chiến dịch, không quản lý sending/suppression.
- **CSKH Staff/Manager**: thấy thêm khối Kinh doanh (Sales), Chăm sóc khách hàng.
- **Viewer**: chỉ xem (không nút tạo/xóa).
- Chi tiết ma trận phân quyền xem tại tài liệu `emailmarketing-v2.md` (mục Tác nhân & Phân quyền).

---

## 2. Cấu trúc menu điều hướng

Sidebar điều hướng được chia 6 nhóm (theo `AdminPanelProvider->navigationGroups`):

| Nhóm | Label VN | Nhóm | Label EN | Nguồn |
|---|---|---|---|---|
| **Email** | — | `email_marketing` | "Email" | Campaign, Email Template, Sending Account, Sending Domain, Suppression, Báo cáo chiến dịch |
| **Marketing** | — | `marketing` | "Marketing" | Ad Campaign, Landing Page, Form Template, Form Submission, Segment, Contact List, Tag, Custom Field |
| **CRM** | — | `crm` | "CRM" | Chăm sóc khách hàng, Khách hàng, Đánh giá liên hệ, Phiên phân phối, Phòng ban|
| **Sales** | Kinh doanh | `sales` | "Sales"| Tổng quan báo giá, Báo giá, Phê duyệt báo giá, Theo dõi thanh toán, Bảng giá, Dịch vụ, Gói dịch vụ, Ngân hàng|
| **Hệ thống** | `system` | "System"| Người dùng, Nhật ký hệ thống |
| **Cấu hình** | — | `configuration` | "Configuration"| Cài đặt công ty |

Ghi chú: một số Resource ẩn khỏi menu chỉ để route (`shouldRegisterNavigation=false`): `ContactResource`, `PersonalContact`, `BusinessContact`, `Staff`, `Position`.

---

## 3. Khối Email Marketing

### 3.1. Chiến dịch Email (Campaign) — `Email Campaign(s)` — icon Megaphone — sort 10

**Trang danh sách (`ListCampaigns`)**
- Cột:
  - `Tên` (searchable/sortable)
  - `Chủ đề` (giới hạn 40 ký tự)
  - `Mẫu email` (template.name)
  - `Trang đích` (toggleable)
  - `Trạng thái` badge màu:
    - `sent` (Đã gửi) → **success**
    - `sending/preparing/scheduled/testing` → **info**
    - `paused` → **warning**
    - `failed/cancelled` → **danger**
    - còn lại (draft...) → **gray**
  - `Lên lịch lúc` (sortable), `Gửi lúc` (toggleable)
- Hành động dòng: ActionGroup (View/Edit/Delete).
- Hành động hàng loạt: Xóa.
- Header: nút `Tạo chiến dịch`.
- *Không có bộ lọc.*

**Trang tạo/sửa (`CreateCampaign` / `EditCampaign`)**
- Section **Chi tiết chiến dịch** (lưới 2 cột):
  - Tên (*bắt buộc*)
  - Trạng thái (dropdown 9 trạng thái, mặc định `draft`)
  - Chủ đề (*bắt buộc*, tối đa 255)
  - Dòng xem trước email (preheader, tối đa 255)
  - Mẫu email (select, bắtbuộc, `email_template_id`)
  - Tài khoản gửi (select, **chỉ hiển thị tài khoản chung** — không thuộc phòng ban)
  - Loại đối tượng (`audience_type`, **live** — đổi thì tải lại form): `all_subscribed` / `list` / `tag` / `segment` / `qualified`
    - Nếu `list/tag/segment` → hiện `audience_id` với nhãn/options động theo loại.
    - Nếu `qualified` → hiện `Trang đích` (landing_page_id) bắt buộc.
  - Ngày gửi (`scheduled_at`, datetime).
- Khi tạo mới, tự ghi `created_by = auth()->id()`.

### 3.2. Mẫu Email (Email Template) — nhóm Email — icon Envelope

**Danh sách (`ListEmailTemplates`)**
- Cột: Tên, Danh mục (badge: `marketing`→**info**, `quotation`→**warning**), Chủ đề (searchable, 50 ký tự), Trạng thái badge (`active`→success, `inactive`→danger, `draft`→gray), `Ngày tạo`.
- Action dòng: **Preview** (mở tab mới trang `/admin/marketing/email-templates/{id}/preview`) + ActionGroup (Sửa/Xóa).
- Header actions: **Tạo mẫu mới** + **Nhập HTML** (modal: Tên, Danh mục select mặc định `marketing`, Upload file `.html`; sau khi nhập sẽ trích `body` và văn bản thuần từ HTML).
- Bulk: Deleted.

**Tạo/sửa (`Create/Edit`)**
- Tên (*), Danh mục (select động từ các category đã có + mặc định `marketing`).
- Nhóm 3 cột: Chủ đề (*), Dòng xem trước (preheader), Trạng thái (`draft` mặc định).
- `HTML body` (RichEditor, full width), `Text body` (textarea 6 dòng).
- Hỗ trợ dán biến token: `{{first_name}} {{last_name}} {{full_name}} {{email}} {{company_name}} {{unsubscribe_url}} {{landing_page_url}}`.

### 3.3. Tài khoản gửi (Sending Account) — icon Cog — **Chỉ Admin**

- **Danh sách:** Tên, Nhà cung cấp badge (Laravel Mail/SMTP), Phòng ban badge, Email gửi, Trạng thái badge (`active`→success, `inactive`→danger, `testing`→warning), Ngay tạo.
- **Header action:** `Gửi thử` (modal: Chọn tài khoản gửi, Email người nhận, Tiêu đề, Nội dung) → gửi email test và thông báo kết quả.
- **Form (Section Tài khoản gửi):**
  - Tên (*)
  - Bộ phận sử dụng tài khoản (`department_id`, helper: “Bộ phận sử dụng tài khoản”; trống = tài khoản chung dùng cho chiến dịch)
  - Nhà cung cấp (`laravel_mail` / `smtp`, mặc định `laravel_mail`, *)
  - Tên người gửi (*), Email gửi (*)
  - Reply-To (email)
  - Cấu hình mã hóa (`config_encrypted`) — KeyValue (key/value)
  - Giới hạn ngày (numeric), Giới hạn giờ (numeric) — mặc định 0
  - Trạng thái (`active`/`inactive`/`testing`)
- Nguồn bảng có cột `department_id` liên kết với Bảng Phòng ban; campaign chỉ dùng tài khoản không có phòng ban.

### 3.4. Tên miền gửi (Sending Domain) — icon AtSymbol — Chỉ Admin

- Form (Section “Kiểm tra tên miền”): Tên miền (*unique), 4 trạng thái DNS: `status`,`spf_status`,`dkim_status`,`dmarc_status` (mỗi cột dropdown `unknown/pending/verified/failed` mặc định `unknown`), Ghi chú, `verified_at`.
- Danh sách: 4 cột status với badge màu: `unknown`→info, `pending`→warning, `verified`→success, `failed`→danger.

### 3.5. Danh sách loại trừ (Suppression Entry) — icon NoSymbol — **Marketing Manager/Admin**

- **Form:** Email (*), Lý do (select suppression reason: `unsubscribe/bounce/complaint/manual/invalid_email/do_not_contact`), Nguồn (max 255), Ghi chú.
- **Danh sách** cột:`email`, `reason` (badge danger cho đa số, `manual`→warning), `source`, `created_at`.

### 3.6. Báo cáo chiến dịch (Campaign Report) — Page `CampaignReportPage` — group Email, icon chart-bar

- **Quyền:** mọi người dùng Marketing.
- **Bố cục** (Blade tùy biến *`campaign-report-page.blade.php`*):
  - Dropdown chọn chiến dịch (`wire:model.live="campaignId"`), mặc định **chiến dịch mới nhất**.
  - Hàng thẻ thống kê thứ 1: Tổng người nhận / Đã gửi / Thất bại / Hủy đăng ký.
  - Hàng thẻ thứ 2: Đã mở / Đã nhấp / Tỷ lệ mở (%) / Tỷ lệ nhấp (%).
  - Bảng danh sách người nhận: Tên liên hệ, Email, Trạng thái badge, Gửi lúc, Mở lúc, Nhấp lúc, Lý do thất bại.
- Không có nút xuất dữ liệu.

---

## 4. Khối Marketing / Landing Page

### 4.1. Chiến dịch quảng cáo (Ad Campaign / MarketingCampaign) — icon SpeakerWave — sort 10

- **Form**: Tên (*), Slug (*unique), Mô tả, Trạng thái (`draft/active/paused/completed` mặc định `draft`), Ngày bắt đầu & Ngày kết thúc (date), Ngân sách (numeric, VND), **Các trang đích** (multi-select `landing_pages` — không lưu trực tiếp, hydrate từ quan hệ `landingPages`), Ghi chú.
- **Danh sách**: Tên, Slug, Trạng thái badge (`active`→success, `paused`→warning, `completed`→info, draft→gray), Số trang đích, Ngày bắt đầu/kết thúc, Ngân sách (tiền VND).
- Xóa được phép ở bảng; trên trang Sửa hành xóa của trang đích đặt `null` (không cho xóa khi đang gắn).

### 4.2. Trang đích (Landing Page) — icon Window — sort 20 — **Resource lớn nhất khối**

**Danh sách**: Tên, Slug, Trạng thái badge (`published`→success, `archived`→danger, draft→gray), Chiến dịch email (defaultCampaign), Chiến dịch quảng cáo (marketingCampaign, toggleable), `published_at`, `created_at`.

**Hành động dòng (thứ tự quan trọng):**
1. `Liên kết` → **modal** hiển thị bảng UTM URLs (copy/delete từng liên kết).
2. ActionGroup:
   - `Xuất bản` (publish) — hiện khi chưa xuất bản; **kiểm tra phải liên kết với chiến dịch** trước khi cho xuất bản (có thông báo lỗi); gán `status=published`.
   - `Hủy xuất bản` (unpublish).
   - `Tạo URL theo dõi (UTM)` → modal nhập utm_source/medium/campaign/content/term + tạo record.
   - `Sao chép liên kết` → thông báo với URL công khai `/lp/{slug}`.
   - `Liên kết công khai` → mở tab mới.
   - `Xem trước` → mở trang preview.
   - `Sửa`, `Xóa`.

**Header (List):** `Tạo trang đích mới`, `Nhập HTML` (Tên, Slug, Chiến dịch quảng cáo, Chiến dịch email, File HTML → tạo **draft**).

**Form / Chi tiết (CreateLandingPage/EditLandingPage):**
- Section `Chi tiết trang đích` (grid 2):
  - Chiến dịch quảng cáo (select, searchable) + Chiến dịch email (select).
  - Tên (*), Slug (*unique).
  - `page_title`, `headline` (tiêu đề chính), `subheadline`, `cta_text` (nội dung CTA).
  - Trạng thái (`draft/published/archived` default `draft`), `published_at` (datetime).
- Repeater `form` (quan hệ `forms` — khớp đối tượng):
  - Mẫu biểu mẫu (form_template_id, select bắt buộc)
  - Loại biểu mẫu (`personal/business/generic`, bắt buộc)
  - Kiểu hiển thị (`embedded` mặc định), mã chức danh (position_key), mặc định (toggle), thứ tự, trạng thái (`active/inactive`).
- Section `Tự động`: auto_tag_names, auto_list_names (TagsInput), auto_create_tags/lists/segment (toggles).
- Section `HTML tùy chỉnh` (collapsible, collapsed): RichEditor.
- RelationManager (resource level) **UTR URLs**: form gồm `utm_source`/`utm_medium` (select tùy chọn chuẩn), `utm_campaign`,`utm_content`,`utm_term`, `url` (textarea, bắt buộc). Bảng gồm source/medium/campaign (placeholder), `url` copyable, created_at.

### 4.3. Mẫu biểu mẫu (Form Template) — icon DocumentDuplicate — sort 30

**Danh sách**: Tên, Slug, `audience_type` badge (`personal`→info, `business`→warning, `generic`→gray), Trạng thái badge (`active`→success, `draft`→gray, `archived`→danger), `version`, `created_at`.

**Header actions**: `Tạo biểu mẫu mới` + `Nhập HTML` (name, slug, audience_type, file html → parse trường).

**Form `Create/Edit`:**
- Section Chi tiết (grid 2): `name` (*), `slug` (*unique), `audience_type` (default `generic`, live), `status` (draft/active/archived), `submit_button_text` (default "Submit"), `success_message`, `redirect_url`.
- Repeater `fields` (quan hệ `fields`):
  - `label` (*), `field_key` (tự động), `field_type` (text/email/phone/textarea/select/checkbox/hidden, live), `placeholder`, `options` (TagsInput hiện với select/checkbox), `default_value`, `is_required` (Toggle), `contact_mapping` (select động theo audience_type), `validation_rules`, `tag_from_value` (Toggle), `sort_order`, `position`.
- Section `Thao tác tự động`: `auto_tag_names`, `auto_list_names`, hộp tạo tags/lists/segment.
- Section `HTML tùy chỉnh` (collapsible): RichEditor `html_body`.

### 4.4. Lượt gửi biểu mẫu (Landing Page Submission) — `Submissions` — icon InboxStack — sort 40

- **Danh sách**: Page title: "Form Submissions". Cột: Trang đích (searchable), Email chuẩn hóa (searchable), Trạng thái badge (`processed`→success, `received`→info, `failed`→danger, `spam`→warning), Nhân viên được phân công (toggleable), `contact_action` badge (`created`→success, `updated`→info, `skipped`→gray), Ngày gửi, Ngày tạo.
- Hành động: View / **`edit-modal`** (modal cập nhật `qualification_status`, `assigned_staff_id`, `note`) / Delete + bulk delete.
- **Header action `Tự động phân phối`** (auto_distribute) → modal chọn `strategy` (`least_loaded`/`round_robin`/`weighted`), `staff_ids` (multi-select nhân viên có thể nhận khách), tùy chọn `landing_page_id`; gọi dịch vụ phân phối lead và thông báo số đã gán/bỏ qua.

### 4.5. Phân khúc (Segment) — `funnel` icon — sort 60

- **Form**:
  - Section Chi tiết: tên, slug, mô tả, trạng thái (`active`/`inactive`).
  - Section Điều kiện → Repeater `rules` (grid 4):
    - `field` (một trong 10 tiêu chí: trạng thái/loopđời/loại khách hàng, nhân viên owner, có thẻ, thuộc danh sách, consent, chuyển đổi trong ☀ N, chuyển đổi giữa ngày, msst đã xác thực)
    - `operator` (`equals`/`not_equals`/`gte`/`lte`)
    - `value` (thay đổi động theo field: số/dropdown thẻ/danh sách/trạng thái/nhân viên)
    - `value_from`/`value_to` (date-start với field khoảng ngày)
- **Header actions (bảng)**: `Xem trước số lượng` + `Xem trước mẫu` (modal chọn segment → thống kê/email mẫu).

### 4.6. Danh sách liên hệ (Contact List) — sort 50 — `rectangle-stack` icon

- **Form:** Tên (*), Slug (tự động), Mô tả, **Loại** (`newsletter`/`service`/`event`), **Trạng thái** (`active`/`inactive`/`archived`).
- **Bảng:** Tên, Slug, Loại badge (info), Trạng thái badge, Số khách hàng, Ngày tạo.

### 4.7. Thẻ (Tag) — `tag` icon — sort 70

- **Form:** Tên (*), Slug (auto), **Màu** (ColorPicker), Mô tả (*).
- **Bảng:** Tên, Slug, **nhóm màu pill HTML** (hiển thị màu của thẻ), Số khách hàng, Ngày tạo.

### 4.8. Trường tùy chỉnh (Custom Field) — `adjustments` icon — sort 999

- **Form:** Tên (*), Khóa (key auto `[a-z0-9_]+`), Kiểu dữ liệu (`text/number/date/boolean/select/multi_select`), Options (KeyValue), Bắt buộc (Toggle), Cho phép lọc (Toggle), Thứ tự.
- **Bảng:** Tên, Khóa, Kiểu badge, Bắt buộc badge, Lọc badge, Thứ tự.

---

## 5. Khối CRM

### 5.1. Chăm sóc khách hàng (Customer Care) — `customer_care` Page — CRM sort 10 — **CS Staff/Manager/Admin**

Đây là trang chuyên sâu kiểu **"workspace bàn làm việc"** (Blade tự thiết kế).
**Bố cục hai cột: danh sách khách hàng (trái) + bảng điều khiển chăm sóc (phải).**

- **Header (thẻ thống kê):** Tổng khách hàng / Cần chăm sóc tiếp (quá hạn) / Báo giá đang chờ (sent/viewed) / Chưa thanh toán (quá hạn) — kèm mô tả ngắn.
- **Bảng khách hàng:** cột Tên (kèm mã khách hàng), loại badge, email, phone, ưu tiên, vòng loại, `next_follow_up_at` (màu **đỏ + nhãn "Quá hạn"** khi quá ngày), mặc định sort theo follow-up tăng dần. Lọc: lifecycle_stage, priority. Tìm kiếm: tên/mã/số điện thoại/email.
- **OpenCareWorkspace** → modal 7xl mở trạng thái khách hàng với 5 tab:
  - **Tổng quan**: thông tin + thẻ thống kê (cuộc gọi, tin nhắn, email đã gửi, báo giá) + quick actions (ghi cuộc gọi, gửi email, tạo báo giá).
  - **Email**: form soạn (To/Cc/Bcc tự parse danh sách, Đính kèm kéo-thả tối đa 20MB, danh sách MIME cho phép), lịch sử email (gửi/mở/nhấp), só báo góp; gửi qua SMTP tài khoản phòng ban với **tracking pixel/link** tự động.
  - **Gọi & Nhắn**: form ghi nhận cuộc gọi/tin nhắn (`callType in call/message`, `callStatus`, `outcome`, `next_follow_up_at`) → tạo `CustomerInteraction`.
  - **Báo giá**: danh sách báo giá của khách + nút **Tạo báo giá** (chuyển sang trang tạo quotation với `customer_id`).
  - **Dòng thời gian**: feed tổng hợp (interaction, email events, quotations, phân công -/kết thúc).
- **Gỡ phân công** (`ReleaseCustomer`): chỉ CS Staff (non-Admin) → modal chọn lý do (`customer_refused/wrong_owner/no_response/overload/other`) + ghi chú → kết thúc active assignment.

### 5.2. Khách hàng (Customers) — `customer.plural` — icon `heroicon-user-group` — sort 20

**Danh sách (`ListCustomers`)**
- Cột: customer_code, Tên hiển thị (display_name), Email, Phone, Loại badge (`business`→warning, `personal`→info), Trạng thái badge (`active`→success, `inactive`→danger, `potential`→info, `churned/blocked/archived`→gray), Vòng đời badge, Người phụ trách (toggleable), Nhân viên hỗ trợ (toggleable), Ngày tạo.
- **Lọc**: Trạng thái / Loại / Trạng thái đồng ý nhận email.
- **Quyền scope dữ liệu (`getEloquentQuery`):** Admin/CS Manager/Marketing Manager thấy tất cả; **CS Staff** chỉ thấy khách hàng có phân công active cho mình; vai trò khác thấy trống.

**Hành động dòng (ActionGroup - “hộp vận hành”):**
1. Xem / Sửa.
2. **Phân công phụ trách** (`assign_owner`, Admin/CSM): modal chọn nhân viên → tạo phân g chủ `active` (lý do Manual).
3. **Phân công hỗ trợ** (`assign_support`, Admin/CSM): tạo phân công `support` active (có thể nhiều phân công hỗ trợ cùng lúc).
4. **Chuyển giao** (`transfer_owner`): modal nhân viên + ghi chú; nếu trùng chủ → báo; ngược lại kết thúc phân công hiện tại + tạo phân mới lý do `Transfer`, giữ `original_owner_staff_id` của người đishại đầu.
5. **Gỡ phân công** (`release_customer`): modal xác nhận → kết thúc **toàn bộ** phân công active của khách hàng.

**Form tạo/sửa — 2 Sections:**
- *case_details* (Chi tiết khách hàng): `customer_code` (disabled khi sửa), `customer_type` (default `personal`), `display_name` (*), email, phone, first_name, last_name, company, tax_code.
- *settings* (Phân loại): `status` (default `potential`), `lifecycle_stage` (default `new_customer`), `consent_status` (default `pending`), `priority`, `converted_by_staff_id`, `date_of_birth`, `converted_at`, `first/latest_purchase_at`, và **TagsInput `metadata.tags`**.

**Relation Managers (trang Xem/Sửa):**
- `Assignments` (Phân công): loại (owner/support badge), trạng thái badge, nhân viên, lý do; có Create/Edit/Delete.
- `Interactions` (Lịch sử chăm sóc): loại badge (call/email/message/meeting→info, support/follow_up→warning), trạng thái, nhân viên, chủ đề, `interaction_at`, `next_follow_up_at`; sort mới nhất.

### 5.3. Đánh giá liên hệ / Lead Pipeline — `ContactQualification` — `loop` icon — sort 50 — trang `lead_pipeline`

- **Trang danh sách sử dụng Tabs** (`ListContactQualifications`): `all` / `not_contacted` (New+Assigned) / `in_progress` (Contacting+FollowUp) / `qualified` / `converted`. URL query `?activeTab=` để nhảy đúng tab.
- **Cột bảng:** Liên hệ (tên/email), Nhân viên phụ trách, Trạng thái badge (màu theo từng trạng thái pipeline), Kết quả badge (`confirmed_need`/`purchased`→success, `no_need`/`unreachable`/`invalid_information`→danger), Ưu tiên badge (VIP/high/normal/low), Ngày follow-up kế tiếp.
- **Action dòng:**
  - `Bắt đầu liên hệ` (new/assigned/follow_up) → set trạng `contacting` + ghi bookmark thời gian.
  - `Xử lý liên hệ` (khi chưa `last_contacted_at`) → modal: `status` (bắt buộc), `qualification_result`, `next_follow_up_at`, ghi chú → tạo note manual.
  - `Đã xử lý` (disabled marker khi đã liên hệ).
  - `Chuyển thành khách hàng` (khi `status=qualified` và kết quả `confirmed_need|purchased`) → modal chọn nhân viên đích → tạo `Customer` + auto distribute.
- **Kanban widget** `LeadPipelineKanbanWidget` (blade kán): chia khối theo trạng thái — New, Assigned, Contacting, Follow up, Qualified, Converted; mỗi khối liệt thẻ cá nhân gần nhất 5 dòng kèm đường dẫn về tab tương ứng.

### 5.4. Phiên phân phối (CustomerDistributionBatch) — sort 30 — icon arrow-path — Admin/CSM

- **Bảng cột:** batch_code, loại/trạng thái/strategy (badges), Tổng khách hàng, Tổng đã phân, `completed_at`.
- **View Infolist:** đầy Bar các thông tin (neutrod nguồn, người khởi tạo, ghi chú, hiệu lực, ngày tạo).
- Chỉ xem; không tạo/sửa thủ công (phân phân động qua các luồng tự động: ban đầu, staff vắng...).

### 5.5. Phòng ban / Nhân viên / Chức danh (Cấu hình Tổ chức)

- **`DepartmentResource` – trang `ListDepartments` (Blade master-detail):**
  - Bảng phòng ban: Tên, Mã (badge), Số vị trí, `/staff_count`, Sending accounts, `is_active` icon. **Mỗi cột đều chọn được (makeSelectableColumn)** → bấm chọn phòng ban để drill down.
  - Khi chọn phòng ban (cũng nhận từ `?department=`) → hiện Section với 2 tabs (Livewire):
    - **`department-positions-table`**: bảng chức danh của phòng ban (title, staff_count, is_active, created_at) + nút Tạo mới.
    - **`department-staff-table`**: bảng nhân viên phòng ban (mã nhân viên, họ tên, chức danh, employment_status badge, có thể nhận khách hàng, created_at) + nút Tạo nhi.
- **`Staff` resource (ẩn menu, admin only):**
  - Form: `user_id`(select bằng email, *), `department_id`(*, chọn phòng ban **đang hoạt động**, live → xóa chức danh không hợp khi đổi phòng), `position_id` (lọc theo phòng ban; **rule lỗi nếu chức danh không thuộc phòng ban** — "Chức danh phải thuộc phòng ban đã chọn"), mã nhân viên (auto `EMP-` + số 00), họ tên, phone, `employment_status` (active/inactive/resigned), `can_receive_customers` (Toggle đúng), `customer_capacity` (numeric, nullable), `distribution_weight` (numeric, default 1), started_at/ended_at (dates).
  - Bảng cột: mã NV, họ tên, phòng ban badge (màu theo mã phòng ban’95), chức danh, trạng thái, có nhận khách hàng, ngày tạo. bộ lọc: phòng ban / trạng thái / có nhận khách.
  - **Khi chuyển `employment_status` active → inactive/resigned**: hệ thống tự kích hoạt **phân phối chữa cháy** (staff vắng) — kết thúc các phân công chính, chia sẻ lại khách hàng. Khi Inactive → Active: khách hàng cũ (đang support) được **trả lại**.
- **`Position` (ẩn)**: tiêu đề, phòng ban (*), mô tả, active; bảng + lọc phòng ban.

---

## 6. Khối Kinh doanh (Sales)

Nhóm menu "Kinh doanh" (`navigation.group.sales`).

### 6.0. Sales Dashboard — `SalesDashboard` — icon `presentation-chart-bar`

- **Access:** Admin & Customer Service Manager.
- **KPI (hàng 1):** Tổng báo giá, Tổng giá trị, `draft`, `pending_approval`, `approved`.
- **KPI (hàng 2):** sent / viewed / accepted / rejected / expired / cancelled.
- **Thẻ:** `accepted_value` (giá trị đã chấp nhận), `accepted_unpaid` (chấp nhận nhưng chưa TT), `expiring_soon` (sắp hết hạn trong 7 ngày — **alert Amber**).
- **Conversion:** "Tỷ lệ gửi → xem" và "Tỷ lệ xem → chấp nhận" (%).
- **Bảng "Cần nhân viên theo dõi":** group bởi `assigned_staff_id` cho các báo giá sent/viewed/revision_requested và accepted+unpaid (kèm là tra cứu user của nhân viên).
- Blade tùy biến: 2 grid KPI + % conversion + cảnh báo + bảng follow-up.

### 6.2. Báo giá (Quotation) — `document-text` icon — **resource phức tạp nhất khối Sales**

**Danh sách (`ListQuotations`)**
- Cột: `quotation_number` (mã), `version`, Khách hàng (display_name), Tiêu đề (30 ký tự), `grand_total` (tiền VND), Trạng thái badge, Trạng thanh toán badge, Nhân viên phụ trách, Ngày báo giá, Ngày hết hạn, Lượt xem, Ngày tạo.
- **Filters:** trạng thái (`QuotationStatus`), trạng thái thanh toán (`PaymentStatus`), nhân viên phụ trách.
- **Hành động dòng (ActionGroup):**
  1. `View` (eye) → trang chi tiết.
  2. `Edit` (pencil) → nếu `isEditable()` (draft/pending_approval/revision_requested).
  3. **`send` (Gửi email)** → modal:
     - `customer_id` (select khách, default = khách của báo giá; khi đổi email gửi tự cập nhật `recipient_email`),
     - `recipient_email` (email, bắt buộc),
     - `template_id` (chỉ mẫu `category = quotation` + `status = active`; khi chọn → **render tự động** subject+body theo mẫu),
     - `subject` (default "`[code] title`"),
     - `body` (textarea 12 dòng) → Gửi qua queue, hiển thị "Email đã được xếp hàng gửi".
  4. **`cancel`** (Hủy, màu danger) → nếu chưa đầu-trạng thái terminal; ghi lịch sử hủy + hồi về trạng thái Hủy.
- **Bulk actions:** Xóa; `Hu suspected hàng loạt` (giới hạn approve); `Đánh dấu đã thanh toán hàng loạt` (Accepted + chưa paid → Paid).

**Trang chi tiết (`ViewQuotation`)** — header actions (hiện theo guard):
- `Edit` (nếu editable) → trang sửa.
- **`submit_approval` (Gửi duyệt)** — khi `draft` → gửi approval (step=1, approver_role=CS manager) → trạng thái `pending_approval`.
- **`approve` (Duyệt)** — khi `pending_approval` + quyền `sales.approve-quotations`.
- **`reject_approval` (Từ chối duyệt)** — bắt buộc nhập `reason` → trả về `draft`.
- **`send`** — giống modal gửi email ở bảng.
- **`generate_pdf` (Xuất PDF)** — khi không terminal.
- **`cancel`** — khi không terminal + có quyền hủy.
- **`public_link`** — mở trang công khai `/q/{code}/{token}` tab mới.

**Relation Managers trên View:**
- `Items` (Chi tiết dịch vụ): service/package_snapshot, unit, quantity, unit_price, discount_amount, vat_amount, line_total (định dạng VND); sort theo `sort_order`; có Create/Edit/Delete.
- `Approvals` (Lịch sử duyệt): step / status badge / reason / requested_at / reviewed_at — **read-only**.
- `Confirmations` (Xác nhận): confirmation_type / signer / confirmed_at / ip — read-only.
- `Documents` (Tài liệu): document_type / file_name / file_size (KB) / generated_at — read-only.
- `EmailLogs` (Lịch sử gửi email): recipient_email / subject / status / queued_at / sent_at / error — sort mới nhất.

**Form tạo/sửa (`Create/Edit`):**
- Section Chi tiết (grid 2): khách hàng (select, *), Bảng giá (select, **live**; giới hạn theo quyền truy cập giá — hiện chỉ `personal`), Tài khoản ngân hàng (select), Tiêu đề (*), Ngày báo giá (default now), Ngày hết hạn (default now + 30).
- Section Chi tiết dịch vụ — Repeater `items` (default 1 dòng):
  - Hidden `price_book_item_id`; snapshot service_name (*), snapshot package; `unit` (default 'tháng'), `quantity` (default 1, *), `unit_price` (VNĐ, *).
  - Nhịp `discount_type`, `discount_value`, `vat_rate` (mặc định 10%).
  - `description_snapshot` (textfield full row).
- Chọn bảng giá → `loadItemsFromPriceBook()` tự điền snapshot cho từng dòng (bảo lỗi nếu bảng giá chưa có sản phẩm).
- Khi sửa: save lại items (delete+recreate) dựa trên snapshot.

### 6.3. Phê duyệt báo giá (Quotation Approval) — `check-badge` icon

- **Bảng:** Mã báo giá, Title (limit 40), Bước, Trạng thái badge, Lý do, Ngày yêu cầu, Ngày xem.
- **Action:** `approve` (khi Pending) và `reject` (khi Pending, danger — ghi lý do mặc định "Từ chối từ danh sách duyệt").
- **Cloud framework có lọc trạng thái.**

### 6.4. Theo dõi thanh toán (Payment Tracking) — `credit-card` icon — Admin/CSM

- Là resource thứ hai trên cùng model **Quotation**.
- **Cột:** mã quotation, khách hàng, grand_total (VNĐ), payment_status badge, status (giới hạn Accepted/Sent/Viewed), `accepted_at`, created_at.
- **Hành động dòng:**
  - `mark_paid` (Đã thanh toán — success, khi unpaid/pending_verification/partial) → `updateStatus(Paid)`.
  - `mark_pending` (Chờ xác nhận — warning, khi unpaid) → `PendingVerification`.
  - `mark_unpaid` (Chưa thanh toán — danger, khi pending_verification) → `Unpaid`.
- **Bulk:** `bulk_mark_paid` (modal xác nhận).

### 6.5. Bảng giá (Price Book) — `currency-dollar` icon

- **Form:**
  - Section Chi tiết: `price_book_code` (*unique), name (*), description, `audience_type` (default both), `currency` (VND/USD), `tax_mode` (ToggleButtons, `exclusive` mặc định), `valid_from` (*), `valid_until`, `status` (default `draft`), `is_default` (toggle).
  - Section (Gói dịch vụ và giá — Repeater `items` quan hệ items): Mỗi mục: `service_package_id` (select, *), `unit_price` (VNĐ,*), `vat_rate` (mặc định 10%), min/max quantity, `default_discount_type` (fixed/percentage), `default_discount_value`, `maximum_discount_value`, `scope_override`, `terms_override`, `sort_order`.
- **Quan hệ `Quyền truy cập` (PriceBookAccessRules):** bảng/quản form: `access_type` (all/role/department/staff/branch), `role`, `department`, `staff`, toggles `can_view`, `can_create_quotation`, `discount_limit_type/value` (giới hạn chiết).

### 6.6. Dịch vụ (Service) — 6.7. Gói dịch vụ (Service Package)

- **Service:** service_code (*unique), name (*), slug, description, category (numeric), `default_scope` (textarea), `default_terms` (textarea), status (`active` default), sort_order. Bảng: mã/tên/trạng thái badge/thứ tự.
- **Service Package:** service (*), package_code (*), name (*), description, `audience_type` (both default), billing_period + unit (day/month/year/one_time), `unit` (default "tháng"), `default_quantity` (1), status (`active`), sort.

### 6.7. Tài khoản ngân hàng (Bank Account) — `building-library` icon

- **Form:** chọn ngân hàng từ UND **VnBank** (`searchable`, live → tự điền `bank_name` + `swift_code` từ bảng) + `account_number` (*), `account_name` (*), status (`active` default), `is_default` (toggle). Hai trường được điền tự động là Hidden.
- **Bảng:** msmã, tên NH, số TK, tên TK, status badge, mặc định bool, ngày tạo.

---

## 7. Khối Hệ thống & Cấu hình

### 7.1. Người dùng (Users) — `user-circle` icon — **Admin only**

- **Form:** tên (*), email (*unique), **Vai trò** (`admin/marketing_manager/marketing_staff/customer_service_manager/customer_service_staff/viewer`), mật khẩu (chỉ bắt buộc khi tạo).
- **Bảng:** tên, email, Vai (badge màu: admin→danger, quản lý→warning, staff→info, viewer→gray), ngày tạo. Hành động: ActionGroup (Xem/Sửa/Xóa) + bulk Xóa.

### 7.2. Nhật ký hệ thống (Audit Log) — `clipboard-document-list` icon — **Admin only**

- **Danh sách thuần** — không xóa/sửa, không tạo, không filter.
- Cột: thời gian (sortable), người thực hiện, action (searchable), loại đối tượng, id đối tượng, IP.

### 7.3. Cài đặt công ty (Company Settings) — `building-office-2` icon — Configuration

- **Form trả về giới hạn một hàng (`id=1`):**
  - Section Thông tin công ty (2 cols): tên công ty (*), mã số thuế, địa chỉ, điện thoại, email, website, **Logo** (FileUpload, image, maxSize 2MB, directory `company`).
  - Section **API VietQR** (2 cols): `vietqr_client_id`, `api_key` (password, hiển thị toggle) để tạo QR thanh toán.
- Nút **Lưu** → lưu + notification "Đã lưu cài đặt công ty".

---

## 8. Các trang Dashboard

### 8.1. Admin Dashboard (Bảng điều khiển) — `admin-dashboard.blade.php`

- Trang mặc định **được thay thế bằng Blade tabbed** (Alpine):
  - Tab **Email** → `DashboardCampaignWidget`.
  - Tab **Marketing** → `DashboardLandingPageWidget` + `MarketingUtmReportWidget`.
  - Tab **Khách hàng** → `DashboardCustomerStatsWidget` + `DashboardSystemAlertsWidget`.
  - Tab **Nhân viên** → `DashboardStaffWorkloadWidget` + `DashboardStaffDetailTableWidget` + `DashboardUpcomingScheduleWidget`.
- Widget move `StatsOverviewWidget` poll 60s, `isAdmin()`:
  - **Campaign widget:** Total campaigns, drafts, (sending+scheduled), sent, total recipients, sent, opened, clicked, bounce/unsub, tỷ lệ mở (%), tỷ lệ nhấp (%).
  - **Landing page widget:** Total, published/draft, views, unique views, submissions, submissions today, personal/business, conversion %.
  - **System alerts widget:** overdue care tasks, unassigned leads, submissions đang xử lý còn `received`, campaigns bị fail, phân loại due today.
- Khi **non- admin** mount: tự redirect về trang đầu tiên khả dụng (StaffDashboard, SalesDashboard, CustomerCarePage, CampaignReportPage).

### 8.2. Staff Dashboard (Lịch làm việc của tôi) — `staff_schedule`

- **Page** hiện 1 widget **FullCalendar** (Saade fullcalendar) cho nhân viên đang đăng nhập:
  - Views: month, week, day; **editable + selectable** (kéo-thả tạo/cập nhật sự kiện).
  - Sự kiện = `CustomerInteraction` của nhân viên: chủ đề, tên khách, loại, ngày follow-up (đỏ khi quá hạn).
  - Create/Edit/Delete qua modal form smart (Master nhân viên giới hạn khách hàng được phân, loại tương tác, chủ đề, nội dung, `next_follow_up_at`).
- Quyền: người có hồ sơ Staff (không phải `isAdmin`).

### 8.3. Chi tiết giao diện Staff Widget trên Admin Dashboard

- **`staff_workload` (dashboard)** profile: active, present, on-leave (leave/sick/absent), inactive/resigned, total mọi, avg load.
- **`staff_detail_table`**: bảng nhân viên kèm `💠 managing_count` / `supporting_count` (sort desc).
- **`upcoming_schedule`**: 10 cuộc hẹn tiếp theo (status `scheduled`) cảnh báo màu khi quá hạn.

---

## 9. Các trang giao diện công khai (Public)

### 9.1. Trang đích (Landing Page) — `/lp/{slug}`
- Khi **published** mới hiển. Slug phân loại mọi lượt: `?type=personal|business` chọn biểu mẫu.
- Texture: tự động render 1 trang HTML hoàn chỉnh: headers (headline/subheadline/CTA), nội dung, `<form>` (chân lại tinput HTML tùy mẫu), thanh nhấn “Gửi”. Hỗ trợ nhiều biểu mẫu (personal + business) trên 1 trang.
- Sau submit: validate → nếu mẫu có `redirect_url` → chuyển hướng; không → trang Cảm ơn `/lp/{slug}/thank-you` + thư thông tin thành công.

### 9.2. Trạng thái báo giá công khai — `/q/{code}/{token}` (`sales/public/show.blade.php`)
- Header: company name + title + `{code}-V{version}`.
- Block info: quotation_date / valid_until, khách hàng + mã số thuế.
- Bảng dịch vụ (STT, service+package, unit, quantity, unit price, discount, VAT, thành tiền).
- Tổng block: subtotal, discount, tax, grand total + currency.
- Block thanh toán: bank name, account no, name, chi nhánh, **nội dung chuyển khoản mặc định = mã code**.
- Terms block: hạn dùng, scope, notes.
- Footer: buttons **Tải PDF** (`/q/{code}/{token}/pdf`) + **In**.
- Nếu `canConfirm()` (`sent`/`viewed`) → 3 nút: **Chấp nhận / Từ chối / Yêu cầu chỉnh sửa** (mở modal):
  - Chấp nhận: Tên người xác nhận (*), chức vụ, phone, email (*).
  - Từ chối: name (*), email (*), lý do (optional).
  - Yêu cầu chỉnh sửa: name/email (*) + **lý do & bắt buộc**.
- Trạng thái `accepted` → block xanh ✓ with signature info + confirmation code. `superseded` → notice vàng.
- OTP (`send-otp`/`verify-otp`) tồn tại trong code nhưng **chưa hiển thị** trên giao diện public hiện tại.

### 9.3. Trang hủy đăng ký Email — `/m/unsubscribe/{token}`
- `GET` hiển thị form xác nhận (email label + nút Xác nhận), hoặc “Liên kết không hợp lệ” nếu thiếu token.
- `POST` → cập nhật `Customer.consent_status=unsubscribed`, thêm suppression (reason `unsubscribe`), hiển thị trang "Đã hủy đăng ký thành công".
- `/m/open/{token}.gif` và `/m/click/{token}` là **pixel/redirect vô hình** (không có UI riêng).

---

## 10. Quy ước giao diện chung

| Quy ước | Ghi chú |
|---|---|
| **Ngôn ngữ** | Tương tự nhau thông tin code qua `lang/*.json`, hoàn toàn không hardcode tiếng Việt trong UI (có lệnh `i18n:audit-hardcoded` để rà) |
| **Badge màu chuẩn** | success=[xanh lá], info=[xanh dương], warning=[vàng/hổ phách], danger=[đỏ], gray=[xám] — dùng nhất quán cho mọi enum/trạng thái |
| **Tiền tệ** | VND format qua helper `money()`/`money_vn()` |
| **Bulk actions** | Mọi bảng đều có bulk delete; riêng quotation có thêm bulk_cancel, bulk_mark_paid |
| **Modal thay vì tab** | Các action kịch bản (xử lý đóng trực tiếp) mở modal có form |
| **Live reload** (`->live()`) | Khi chọn giá trị phụ thuộc: ad-type→audience, price_book→items, template→email preview, bank→bank_name, hospital→department (form) |
| **i18n switch** | Menu người muỗi góc phải; tự cảnh báo nếu chưa lưu |
| **Search** | Mọi bảng đều `searchable()` các cột quan trọng |

---

*Hết tài liệu UI/UX.* Phiên bản tương ứng: hệ thống hiện tại (Marketing v1 + CRM + Sales), ngày soạn: 2026.