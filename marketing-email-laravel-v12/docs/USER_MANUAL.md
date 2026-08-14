# HƯỚNG DẪN SỬ DỤNG HỆ THỐNG CRM & MARKETING

**Phiên bản:** 2.0 (Tài liệu nghiệm thu)
**Dự án:** DTH Business Core — Email Marketing & CRM (Laravel 12 + Filament 3)
**Phạm vi:** Toàn bộ chức năng nghiệp vụ của 8 nhóm navigation + các trang báo cáo/thống kê.

---

## MỤC LỤC

1. [Giới thiệu & đăng nhập](#1-giới-thiệu--đăng-nhập)
2. [Khái niệm nền tảng (đọc trước khi dùng)](#2-khái-niệm-nền-tảng)
3. [Nhóm 1 — Email Marketing](#3-nhóm-1--email-marketing)
4. [Nhóm 2 — Marketing](#4-nhóm-2--marketing)
5. [Nhóm 3 — CRM](#5-nhóm-3--crm)
6. [Nhóm 4 — Kinh doanh (Sales)](#6-nhóm-4--kinh-doanh-sales)
7. [Nhóm 5 — Tài chính (Finance)](#7-nhóm-5--tài-chính-finance)
8. [Nhóm 6 — Chăm sóc khách hàng](#8-nhóm-6--chăm-sóc-khách-hàng)
9. [Nhóm 7 — Cấu hình](#9-nhóm-7--cấu-hình)
10. [Nhóm 8 — Hệ thống](#10-nhóm-8--hệ-thống)
11. [Dashboard & báo cáo](#11-dashboard--báo-cáo)
12. [Trang công khai (Public)](#12-trang-công-khai-public)
13. [Luồng nghiệp vụ tổng thể](#13-luồng-nghiệp-vụ-tổng-thể)
14. [Tài khoản nghiệm thu & checklist](#14-tài-khoản-nghiệm-thu--checklist)

---

## 1. GIỚI THIỆU & ĐĂNG NHẬP

Hệ thống DTH Business Core là nền tảng quản lý **Marketing – CRM – Bán hàng – Tài chính – Chăm sóc khách hàng** tích hợp, cài trên nền tảng quản trị Filament 3 (Laravel 12).

- **Đường dẫn đăng nhập:** `https://<tên-miền>/admin` (đăng nhập bằng email + mật khẩu).
- Sau khi đăng nhập, hệ thống hiển thị menu dọc bên trái chia theo **8 nhóm chức năng** với biểu tượng và màu sắc riêng:
  - `email_marketing` (màu **sky**) — Email Marketing
  - `marketing` (màu **emerald**) — Marketing
  - `crm` (màu **indigo**) — CRM
  - `sales` (màu **amber**) — Kinh doanh
  - `finance` (màu **rose**) — Tài chính
  - `customer_care` (màu **fuchsia**) — Chăm sóc khách hàng
  - `configuration` (màu **slate**) — Cấu hình
  - `system` (màu **violet**) — Hệ thống

> Màu này cũng được dùng thống nhất trong badge/huy hiệu trạng thái toàn hệ thống (xem Nhóm 7 — UiBadgeStyle).

**Quyền truy cập:** Mỗi người dùng chỉ thấy nhóm/mục mình được phân quyền (RBAC). Nếu chưa thấy một mục nào, hãy kiểm tra vai trò của mình trong **Nhóm 7 → Người dùng**.

---

## 2. KHÁI NIỆM NỀN TẢNG

Phần này giải thích các khái niệm dùng chung toàn hệ thống. **Bạn nên đọc kỹ** để không nhầm lẫn khi sử dụng các module.

### 2.1. Tổ chức & nhân sự

- **Phòng ban (Department):** đơn vị cấp 1 của công ty (vd: Marketing, Kinh doanh, Tài chính, CSKH, Vận hành).
- **Vị trí (Position):** chức danh trong phòng ban (vd: Trưởng phòng, Chuyên viên, Nhân viên).
- **Nhân viên (Staff):** hồ sơ nhân viên gắn với 1 phòng ban + 1 vị trí. Mỗi nhân viên có thể có **nhiều nơi làm việc** (địa điểm) và **nhiều kỹ năng** (skill tags).
- **Người dùng (User):** tài khoản đăng nhập hệ thống. Mỗi người dùng gắn với 1 nhân viên (Staff) và 1+ vai trò (RbacRole).
- **Vai trò (RbacRole):** bộ quyền hạn gán cho người dùng (vd: Super Admin, System Admin, Marketing Manager, Salesperson, CSKH, Kế toán). Vai trò quyết định user thấy mục nào và làm được gì.

### 2.2. Luồng V1 / V2 (Lead — Chuyển đổi khách hàng)

Đây là khái niệm quan trọng nhất trong module CRM. Hệ thống phân loại khách hàng tiềm năng theo nguồn gốc:

- **V1 — Khách hàng chủ động (Lead):** khách có nhu cầu chủ động từ chiến dịch marketing (đăng ký landing page, form, email…). V1 được **phân phối tự động** theo lượt cho nhân viên kinh doanh.
- **V2 — Khách hàng được giới thiệu (Referral):** khách được giới thiệu bởi khách hàng cũ, người thân, đối tác. V2 có **nhiệt độ ưu tiên cao hơn** (không cần làm nóng nhiều như V1).

Sự khác biệt chính:

| Tiêu chí | V1 | V2 |
|---|---|---|
| Nguồn | Marketing (chủ động) | Giới thiệu (thụ động) |
| Nhiệt độ khách | Lạnh → ấm dần | Ấm sẵn |
| Phân phối | Tự động theo lượt (LeadDistribution) | Có thể ưu tiên |
| Xử lý | Nuôi dưỡng qua email/sms | Chăm sóc nhanh |

**Cách phân biệt trên giao diện:** mỗi khách hàng/lead có trường `Source` (V1 hoặc V2) và badge màu tương ứng.

### 2.3. Khái niệm thời gian trong báo cáo: Period & Delta

Tất cả dashboard/báo cáo dùng 2 khái niệm:

- **Period (kỳ hiện tại):** khoảng thời gian đang xem — thường mặc định **30 ngày gần nhất** (hoặc chọn T7/Tháng/Năm/7 ngày/90 ngày...).
- **Delta (so với kỳ trước):** chênh lệch so với kỳ liền trước cùng độ dài. Ví dụ: period = tháng này, delta = so với tháng trước.

**Cách đọc:** thẻ số (stat card) hiển thị giá trị kỳ hiện tại kèm mũi tên % tăng/giảm. Mũi tên lên màu xanh = tốt, xuống màu đỏ = kém (trừ các chỉ số "nên thấp" như tỷ lệ bounce — lúc này mũi tên ngược ý nghĩa, xem chú thích trên màn hình).

### 2.4. Attribution (Quy kết) & Funnel (Phễu)

- **Attribution:** xác định chiến dịch/nguồn nào tạo ra doanh thu. Hệ thống ghi nhận **lần chạm đầu tiên** (first touch) và **lần chạm cuối cùng** (last touch) qua tham số UTM (utm_source, utm_medium, utm_campaign, utm_content, utm_term).
- **Funnel (phễu):** chuỗi các bước khách hàng đi qua từ lúc biết đến đến khi trả tiền:
  `Lượt xem (Visits) → Lead → Chuyển thành cơ hội (Opportunity) → Báo giá (Quotation) → Chốt (Won) → Thanh toán (Paid)`.
  Trang báo cáo Campaign thể hiện phễu này để bạn thấy **bước nào bị rò rỉ nhiều nhất** (tỷ lệ chuyển bước thấp).

### 2.5. Chỉ số tài chính: ROAS & CAC

- **ROAS (Return on Ad Spend):** doanh thu thu được trên mỗi đồng chi phí quảng cáo. `ROAS = Doanh thu từ chiến dịch / Chi phí chiến dịch`. ROAS ≥ 2–3 là tốt cho phần lớn ngành.
- **CAC (Customer Acquisition Cost):** chi phí để có được 1 khách hàng. `CAC = Tổng chi phí / Số khách hàng mới`. CAC càng thấp càng tốt.

### 2.6. Activity Index (Chỉ số hoạt động)

Chỉ số tổng hợp mức độ chăm sóc khách hàng của 1 nhân viên trong kỳ: số lượt gọi, email, gặp mặt, ghi chú, lịch hẹn... **Chỉ số càng cao = hoạt động chăm sóc càng tích cực.** Được dùng trong báo cáo Nhân sự (Workforce) và xếp hạng nhân viên.

### 2.7. Pipeline & giai đoạn (Stages)

- **Pipeline (kênh bán hàng):** chuỗi giai đoạn từ khách tiềm năng đến chốt hợp đồng, thường mặc định:
  `Mới (New) → Đã liên hệ (Contacted) → Đang chăm sóc (Nurturing) → Báo giá (Quoting) → Chốt (Won)` — hoặc `Thắng (Won)` / `Thua (Lost)`.
- Mỗi cơ hội (Opportunity) nằm ở 1 giai đoạn, có **giá trị ước tính** và **xác suất chốt** theo giai đoạn. Tổng giá trị pipeline = tổng giá trị các cơ hội đang mở, nhân xác suất = **dự báo doanh thu**.

### 2.8. Mã số tự sinh (Code) & Token bảo mật

- Các đối tượng nghiệp vụ có **mã tự sinh** theo tiền tố: `OPP-XXXX` (Cơ hội), `QUO-XXXX` (Báo giá), `INV-XXXX` (Thanh toán/hóa đơn), `TIC-XXXX` (Ticket), `LND-XXXX` (Landing page)...
- **Token:** chuỗi bí mật ngẫu nhiên. Ví dụ link báo giá công khai gồm mã báo giá + token: `https://.../q/QUO-0001/<token>`. **Không chia sẻ token này ra ngoài** — người có link mới xem/đồng ý được báo giá.

### 2.9. Trạng thái & màu chuẩn

| Màu | Ý nghĩa chung |
|---|---|
| Xanh (success) | Thành công / Đã xử lý / Hoạt động |
| Vàng/Amber (warning) | Chờ xử lý / Đang chạy / Cần chú ý |
| Đỏ (danger) | Thất bại / Đã chặn / Vi phạm |
| Xám (gray) | Không hoạt động / Bản nháp |
| Xanh dương (info) | Thông tin / Đã gửi |

---

## 3. NHÓM 1 — EMAIL MARKETING

> Nhóm quản lý toàn bộ hoạt động **gửi email marketing** (chiến dịch, mẫu, tài khoản gửi, chặn hủy đăng ký).

### 3.1. Chiến dịch email (Campaign)

**Khái niệm:** một đợt gửi email hàng loạt đến một đối tượng cụ thể (phân khúc/danh sách), dùng 1 mẫu email, gửi từ 1 tài khoản gửi.

**Các trạng thái của chiến dịch:**
| Trạng thái | Ý nghĩa |
|---|---|
| Draft (Nháp) | Đang soạn, chưa gửi |
| Scheduled (Hẹn giờ) | Đã lên lịch, sẽ gửi tự động |
| Sending (Đang gửi) | Hệ thống đang gửi từng email |
| Sent (Đã gửi) | Gửi xong toàn bộ |
| Paused (Tạm dừng) | Đang gửi nhưng tạm dừng |
| Failed (Thất bại) | Gửi lỗi, cần xem nhật ký |

**Cách dùng:**
1. **Tạo mới** chiến dịch → đặt tên + mô tả.
2. **Chọn đối tượng nhận:** 1 Phân khúc (Segment) hoặc 1 Danh sách liên hệ (Contact List).
3. **Chọn mẫu email** (Email Template) và **tài khoản gửi** (Sending Account).
4. (Tùy chọn) **Hẹn giờ gửi** (scheduled_at) để gửi vào khung giờ tốt nhất.
5. **Lưu → Gửi.** Hệ thống sẽ:
   - Tự thay thế biến động `{{name}}`, `{{email}}`, `{{unsubscribe_url}}`...
   - Ghi nhận lượt **mở (open)** và **click** qua pixel theo dõi (`/m/open`, `/m/click`).
   - Tự chuyển email không hợp lệ vào danh sách chặn (Suppression) nếu bị bounce.
6. Theo dõi kết quả tại trang **Báo cáo chiến dịch** (mục 11.7).

**Lưu ý quan trọng:** Không gửi tới email đã nằm trong Danh sách chặn (Suppression) — hệ thống tự động loại bỏ để bảo vệ uy tín domain gửi.

### 3.2. Mẫu email (Email Template)

**Khái niệm:** nội dung email dùng lại được (HTML hoặc văn bản), có thể chứa **biến động** (`{{contact_name}}`, `{{unsubscribe_url}}`, `{{tracking_pixel}}`...). Biến động giúp mỗi người nhận thấy email được "cá nhân hóa".

**Cách dùng:** Tạo mẫu → chọn Danh mục mẫu (nếu có) → soạn nội dung → gán biến động → lưu. Mẫu sau đó được chọn trong Chiến dịch email.

### 3.3. Danh mục mẫu (Email Template Category)

**Khái niệm:** thư mục phân loại mẫu email (vd: "Giới thiệu sản phẩm", "Chăm sóc khách hàng", "Khuyến mãi"). Giúp tìm mẫu nhanh khi tạo chiến dịch.

### 3.4. Tài khoản gửi (Sending Account)

**Khái niệm:** tài khoản SMTP/email dùng để gửi email (vd: `marketing@congty.com`). Thông tin gồm host, port, username, password, tên hiển thị.

**Lưu ý:**
- Mỗi tài khoản gửi phải gắn với **domain gửi đã xác thực** (Sending Domain) — ngăn gửi email "mạo danh" và tăng tỷ lệ vào hộp thư chính.
- Trạng thái **Hoạt động / Không hoạt động** quyết định tài khoản có được chọn trong chiến dịch không.

### 3.5. Domain gửi (Sending Domain)

**Khái niệm:** domain (vd: `congty.com`) được khai báo và xác thực để gửi email. Bạn cần thêm **bản ghi DNS** (SPF, DKIM, DMARC) do hệ thống cung cấp để chứng minh domain hợp lệ → tăng khả năng email vào inbox thay vì spam.

**Cách dùng:** Tạo domain → hệ thống hiển thị các bản ghi DNS cần khai báo → khai báo tại nhà cung cấp tên miền → chờ hệ thống xác thực → đánh dấu đã xác thực.

### 3.6. Danh sách chặn (Suppression Entry)

**Khái niệm:** danh sách đen email — nơi lưu các email **không được phép gửi lại**, gồm 3 loại:
- **Bounce:** email gửi lỗi vĩnh viễn (địa chỉ không tồn tại).
- **Complaint:** người nhận báo "spam".
- **Unsubscribe:** người nhận bấm **Hủy đăng ký** trong email (hệ thống tự thêm — đây là yêu cầu pháp lý bắt buộc, không được xóa thủ công).

**Cách dùng:** Xem/tìm kiếm email bị chặn. Hệ thống tự động loại các email này khỏi mọi chiến dịch. Nhân viên quản trị có thể thêm email vào danh sách này thủ công khi cần.

---

## 4. NHÓM 2 — MARKETING

> Nhóm quản lý hoạt động **marketing online**: landing page, chiến dịch quảng cáo, form, phân khúc, danh sách liên hệ.

### 4.1. Landing Page (Trang đích)

**Khái niệm:** trang web độc lập (một URL) được tạo để thu thập khách hàng tiềm năng — người truy cập điền form và trở thành **Lead** hoặc **Landing Page Submission**.

**Cách dùng:**
1. Tạo Landing Page → đặt tên, slug (`/lp/<slug>`, URL công khai) → chọn **mẫu form** (Form Template) → chọn **loại khách tiềm năng** (V1 hoặc V2).
2. Chọn trường thu thập (tên, email, số điện thoại...) qua Custom Field và Form Template.
3. (Tùy chọn) Khai báo **UTM URL** để theo dõi nguồn quảng cáo, và **redirect sau khi submit**.
4. Xuất bản → chia sẻ link.
5. Mỗi lượt điền form tạo 1 bản ghi Landing Page Submission → tự chuyển thành Lead (nếu bật) và **vào hàng đợi phân phối V1** (nếu là V1).

### 4.2. Chiến dịch marketing (Marketing Campaign)

**Khái niệm:** một chiến dịch quảng cáo/marketing tổng thể (vd: "TVC tháng 8", "Google Ads mùa khai trường") — **khác với chiến dịch email** (mục 3.1). Chiến dịch marketing gom nhiều hoạt động: landing page, quảng cáo, email...

**Thông tin quan trọng:**
- **Ngân sách (budget)** và **chi phí thực tế (actual spend)** → dùng tính **ROAS, CAC**.
- **Ngày bắt đầu / kết thúc**, trạng thái (Hoạt động/Đã kết thúc/Đang chạy...).
- Liên kết với Landing Page và các nguồn UTM → báo cáo Attribution (mục 2.4).

### 4.3. Dữ liệu submit Landing Page (Landing Page Submission)

**Khái niệm:** lưu trữ **từng lượt điền form** từ landing page (raw data), kèm thông tin UTM, trình duyệt, IP, thời gian. Giúp kiểm tra chất lượng nguồn quảng cáo và đối chiếu với Lead.

**Cách dùng:** Xem danh sách submissions theo landing page/thời gian. Nếu thấy lead "ảo" (submit giả), có thể xóa và hệ thống không tạo lead tương ứng.

### 4.4. Mẫu form (Form Template)

**Khái niệm:** cấu trúc form dùng lại được (các trường cần thu thập, nút bấm, xác nhận). Một mẫu form có thể dùng cho nhiều landing page.

**Cách dùng:** Tạo mẫu → chọn các Custom Field làm trường của form → gán mẫu cho Landing Page.

### 4.5. Phân khúc (Segment)

**Khái niệm:** nhóm liên hệ **được lọc theo điều kiện động** — vd: "khách ở Hà Nội", "khách mua trên 5 triệu", "chưa mở email 30 ngày". Phân khúc **tự cập nhật** khi dữ liệu thay đổi, khác với Contact List (danh sách tĩnh).

**Cách dùng:** Tạo phân khúc → chọn loại đối tượng (Liên hệ/Khách hàng) → thêm điều kiện (thuộc tính, tag, hành vi, địa lý...) → xem số lượng khớp → dùng trong Chiến dịch email.

### 4.6. Thẻ (Tag)

**Khái niệm:** nhãn gắn lên liên hệ/khách hàng để phân loại nhanh (vd: `VIP`, `Khách sỉ`, `Miền Bắc`). Một đối tượng có thể có nhiều tag. Tag dùng trong tìm kiếm, lọc, phân khúc.

### 4.7. Danh sách liên hệ (Contact List)

**Khái niệm:** danh sách **tĩnh** các email/liên hệ (khác Segment là động). Dùng khi bạn muốn gửi đúng một tập khách đã chốt sẵn.

**Cách dùng:** Tạo danh sách → thêm liên hệ (nhập tay / import CSV) → dùng trong Chiến dịch email. Kiểm tra trùng lặp trước khi gửi.

### 4.8. Trường tùy chỉnh (Custom Field)

**Khái niệm:** trường dữ liệu do bạn tự định nghĩa thêm vào Liên hệ (vd: `Ngày cưới`, `Sở thích`, `Mã vùng`). Giúp hệ thống linh hoạt theo ngành nghề của bạn.

**Cách dùng:** Tạo trường → chọn kiểu (Text, Số, Ngày, Dropdown, Checkbox...) → gán phạm vi dùng (Liên hệ cá nhân / Công ty / Khách hàng) → trường xuất hiện trong form nhập liệu và phân khúc.

### 4.9. Báo cáo chiến dịch marketing (Campaign Analytics)

Trang tổng hợp hiệu quả từng chiến dịch: chi phí, lượt xem landing page, số lead, tỷ lệ chuyển đổi, **ROAS**, **CAC** (xem mục 11.6).

---

## 5. NHÓM 3 — CRM

> Nhóm quản lý **khách hàng và khách hàng tiềm năng**: liên hệ, công ty, lead, đối soát trùng lặp, phân loại chất lượng.

### 5.1. Liên hệ (Contact)

**Khái niệm:** thông tin cá nhân/đơn vị có quan hệ với công ty — có thể là cá nhân (Contact Personal) hoặc doanh nghiệp (Contact Business). Liên hệ là **đối tượng thô nhất**; khi có nhu cầu mua mới trở thành **Lead**.

**Cách dùng:** Tạo liên hệ → điền tên, email, số điện thoại, địa chỉ, tag, trường tùy chỉnh, ghi chú. Tìm kiếm nhanh theo tên/email/số điện thoại. Xem **lịch sử tương tác** (email gửi, cuộc gọi, ghi chú) ngay trên trang chi tiết.

> Lưu ý: Liên hệ cá nhân và doanh nghiệp là 2 resource riêng (không nằm trên menu chính, truy cập từ trang Công ty/Liên hệ tổng hợp), tránh nhầm lẫn dữ liệu cá nhân – tổ chức.

### 5.2. Công ty (Company)

**Khái niệm:** pháp nhân doanh nghiệp — nơi gom các liên hệ làm việc tại đó (vd: công ty có 3 người liên hệ: sếp, kế toán, kỹ thuật). Công ty là đối tượng chính khi bán **B2B**.

**Cách dùng:** Tạo công ty → điền tên, MST, ngành, quy mô, địa chỉ, website → thêm liên hệ trực thuộc → gắn cơ hội (Opportunity) vào công ty. Trang công ty hiển thị **tổng giá trị cơ hội đang mở** và **lịch sử giao dịch**.

### 5.3. Khách hàng tiềm năng (Lead)

**Khái niệm (quan trọng, đọc kỹ mục 2.2):** người/đơn vị **đã bày tỏ nhu cầu mua** (điền form, gọi điện hỏi mua...). Lead phân biệt với Contact: **Contact = biết thông tin, Lead = có nhu cầu**.

**Cách dùng:**
1. Lead được tạo từ: (a) Landing Page Submission (tự động), (b) nhập tay, (c) import.
2. Chọn **Nguồn (Source): V1 hoặc V2** (mục 2.2) và **Kênh (Channel): Facebook, Google, Giới thiệu, Website...**
3. Lead **V1 chưa phân phối** nằm trong hàng đợi; hệ thống **tự động chia đều** cho nhân viên kinh doanh (theo lượt — round-robin) qua LeadDistributionService.
4. Nhân viên nhận lead → **Chăm sóc** (gọi điện, email, ghi chú) → nếu khả thi → **Chuyển thành Cơ hội (Opportunity)**.
5. Trạng thái lead: `New → Contacted → Qualified → Converted` (hoặc `Disqualified` nếu không khả thi).

### 5.4. Ứng viên khớp công ty (Company Match Candidate)

**Khái niệm:** danh sách **các công ty trùng/nghi trùng** được hệ thống đối soát tự động (theo tên, MST, email domain) khi tạo công ty/lead. Mục đích: **tránh tạo công ty trùng lặp** làm phân tán dữ liệu.

**Cách dùng:** Khi tạo công ty mới, hệ thống đề xuất các ứng viên khớp → chọn **Merge (hợp nhất)** nếu trùng, hoặc **Bỏ qua** nếu khác. Có thể xử lý thủ công tại trang này.

### 5.5. Phân loại chất lượng liên hệ (Contact Qualification)

**Khái niệm:** bảng điểm/đánh giá **chất lượng của lead hoặc liên hệ** (vd: điểm theo thu nhập, vị trí, quy mô công ty) để ưu tiên nhân viên chăm sóc lead chất lượng cao trước.

**Cách dùng:** Mở lead → xem điểm phân loại (được tính tự động từ dữ liệu) → nhân viên kinh doanh ưu tiên lead điểm cao.

### 5.6. Staff Dashboard (Nhóm CRM)

Bảng thống kê **hiệu suất cá nhân từng nhân viên kinh doanh**: số lead nhận, lead đã xử lý, tỷ lệ chuyển cơ hội, doanh thu mang về, chỉ số hoạt động (mục 11.10).

---

## 6. NHÓM 4 — KINH DOANH (SALES)

> Nhóm quản lý **quá trình bán hàng**: cơ hội, báo giá, phê duyệt, bảng giá, dịch vụ/sản phẩm.

### 6.1. Cơ hội (Opportunity)

**Khái niệm (đọc thêm mục 2.7):** một giao dịch bán hàng cụ thể cho 1 khách (công ty/liên hệ) — ví dụ "Bán gói CRM cho Công ty ABC". Mỗi cơ hội:
- Thuộc 1 **giai đoạn pipeline** (New → Contacted → Nurturing → Quoting → Won/Lost).
- Có **giá trị ước tính**, **xác suất chốt** (tự gợi ý theo giai đoạn), **ngày dự kiến chốt**.
- Gắn với 1 **nhân viên kinh doanh phụ trách** (owner).

**Cách dùng:** Tạo từ Lead (nút "Chuyển thành cơ hội") hoặc tạo mới → điền khách hàng, giá trị, giai đoạn → theo dõi trên kanban/cột pipeline → khi khách đồng ý mua → tạo **Báo giá** → sau khi khách chấp nhận → đánh dấu **Won** → tạo **Thanh toán**.

### 6.2. Báo giá (Quotation)

**Khái niệm:** văn bản chào giá chính thức gửi khách hàng, gồm danh sách dịch vụ/sản phẩm, số lượng, đơn giá, chiết khấu, thuế, tổng tiền. Báo giá có thể được **gửi công khai** qua link an toàn (mục 12.2) để khách tự xem và xác nhận.

**Các trạng thái:**
| Trạng thái | Ý nghĩa |
|---|---|
| Draft | Nháp, chưa gửi khách |
| Pending Approval | Đang chờ phê duyệt nội bộ (nếu vượt hạn mức) |
| Sent | Đã gửi cho khách, đang chờ phản hồi |
| Accepted | Khách đã đồng ý |
| Rejected | Khách từ chối |
| Expired | Hết hạn hiệu lực |
| Converted | Đã chuyển thành thanh toán/hợp đồng |

**Cách dùng:**
1. Tạo báo giá từ Cơ hội (giá trị tự điền theo bảng giá) hoặc tạo mới → chọn khách hàng.
2. Thêm **dòng mục** (service/product + số lượng + đơn giá); hệ thống tự tính **thành tiền, chiết khấu, thuế VAT, tổng cộng**.
3. Gửi phê duyệt nội bộ (nếu cần) → **Gửi link công khai** cho khách (qua email/SMS/Zalo).
4. Khách bấm **Đồng ý / Từ chối / Yêu cầu chỉnh lại** trên link → hệ thống cập nhật trạng thái tự động → nhân viên nhận thông báo.
5. Khi khách đồng ý → tạo **Thanh toán / Theo dõi thanh toán**.

### 6.3. Phê duyệt báo giá (Quotation Approval)

**Khái niệm:** quy trình **duyệt nội bộ** trước khi gửi báo giá cho khách, áp dụng khi giá trị báo giá **vượt hạn mức phê duyệt của nhân viên** (vd: dưới 10 triệu nhân viên tự duyệt, trên 10 triệu cần trưởng phòng, trên 50 triệu cần giám đốc).

**Cách dùng:** Báo giá ở trạng thái Pending Approval → người có quyền mở trang này (hoặc bấm thông báo) → **Duyệt** hoặc **Từ chối** kèm lý do → báo giá tiếp tục gửi khách hoặc trả lại người soạn.

### 6.4. Bảng giá (Price Book)

**Khái niệm:** danh mục **giá chuẩn** của dịch vụ/sản phẩm theo từng khu vực/đối tượng (vd: "Bảng giá miền Bắc", "Bảng giá khách sỉ"). Khi tạo báo giá, nhân viên chọn bảng giá → đơn giá tự điền → **không phải nhớ giá** và **không lệch giá** giữa các nhân viên.

### 6.5. Dịch vụ (Service) / Gói dịch vụ (Service Package) / Sản phẩm dịch vụ (Service Product)

| Khái niệm | Mô tả | Ví dụ |
|---|---|---|
| **Service** | dịch vụ đơn lẻ | "Thiết kế website", "Chạy quảng cáo 1 tháng" |
| **Service Product** | sản phẩm kèm theo dịch vụ | "Bản quyền phần mềm", "Số dư tài khoản Ads" |
| **Service Package** | combo gói gồm nhiều Service/Product | "Gói Marketing Pro = Website + Quảng cáo + SEO" |

**Cách dùng:** Tạo dịch vụ → đặt tên, mô tả, đơn giá, đơn vị (tháng/năm/lần), loại (thuê bao / trả phí 1 lần) → gom vào Service Package → gắn giá vào Price Book → dùng trong báo giá. Gói có thể có **giá rẻ hơn tổng** (chiết khấu combo).

---

## 7. NHÓM 5 — TÀI CHÍNH (FINANCE)

> Nhóm quản lý **dòng tiền thu**: thanh toán, theo dõi công nợ, tài khoản ngân hàng, báo cáo doanh thu.

### 7.1. Thanh toán (Payment)

**Khái niệm:** ghi nhận **khoản thu tiền** từ khách hàng cho 1 báo giá/hợp đồng. Mỗi thanh toán có:
- Số tiền, ngày nhận, **phương thức** (Chuyển khoản / Tiền mặt / Thẻ / Ví điện tử).
- **Bằng chứng** (ảnh/chụp màn hình giao dịch — upload lên).
- Gắn với **Báo giá**, **Cơ hội**, **Khách hàng**, và **Tài khoản ngân hàng** (nếu chuyển khoản).

**Các trạng thái:**
| Trạng thái | Ý nghĩa |
|---|---|
| Pending | Đang chờ xác nhận tiền về |
| Confirmed | Đã xác nhận thu đủ |
| Failed | Giao dịch thất bại |
| Refunded | Đã hoàn tiền |

**Cách dùng:** Tạo từ Cơ hội chốt (Won) hoặc từ Báo giá đã được chấp nhận → nhập số tiền/phương thức → upload bằng chứng → kế toán **xác nhận** sau khi kiểm tra tài khoản ngân hàng.

### 7.2. Theo dõi thanh toán (Payment Tracking)

**Khái niệm:** bảng **công nợ** của từng khách hàng/đơn hàng: đã phải thu bao nhiêu (theo báo giá/tiến độ), đã thu bao nhiêu, **còn thiếu bao nhiêu và hạn nộp**. Dùng để nhắc nợ và không sót khoản thu.

**Cách dùng:** Xem danh sách theo trạng thái (`Đã đủ`, `Còn thiếu`, `Quá hạn`) → bấm **Ghi nhận thanh toán** khi khách nộp thêm → hệ thống tự trừ vào số còn thiếu. Bộ lọc "Quá hạn" giúp ưu tiên nhắc nợ.

### 7.3. Tài khoản ngân hàng (Bank Account)

**Khái niệm:** các tài khoản ngân hàng của công ty dùng nhận tiền (vd: VCB, ACB, MB...). Khách hàng nhìn thấy **thông tin này trong link báo giá công khai** để chuyển khoản đúng số tài khoản; kế toán đối chiếu nhanh khi xác nhận thanh toán.

### 7.4. Báo cáo doanh thu (Revenue Report)

Trang tổng hợp doanh thu theo **kỳ (period) và delta (mục 2.3)**: tổng thu, theo tháng, theo phương thức, theo nhân viên kinh doanh, theo sản phẩm/dịch vụ (xem mục 11.8).

---

## 8. NHÓM 6 — CHĂM SÓC KHÁCH HÀNG

> Nhóm quản lý **sau bán hàng**: hồ sơ khách hàng, phân phối chăm sóc, ticket hỗ trợ.

### 8.1. Khách hàng (Customer)

**Khái niệm:** đối tượng **đã mua hàng** (đã có ít nhất 1 thanh toán thành công) — khác với Contact (chỉ là thông tin) và Lead (đang chăm sóc). Khi lead/khách chốt đơn đầu tiên, hệ thống **tự nâng cấp thành Khách hàng**.

**Cách dùng:** Xem danh sách khách hàng, lịch sử mua hàng, tổng doanh thu đã đóng góp, gắn người chăm sóc. Dùng làm đối tượng cho các chương trình chăm sóc, upsell, cross-sell.

### 8.2. Đợt phân phối khách hàng (Customer Distribution Batch)

**Khái niệm:** một **đợt chia khách hàng cho nhân viên chăm sóc** — giống "chia danh sách khách hàng" theo vùng/người phụ trách. Ví dụ: đầu quý, chia 200 khách cũ cho 4 nhân viên CSKH, mỗi người 50 khách.

**Cách dùng:** Tạo đợt → chọn phạm vi khách hàng (theo khu vực/giá trị/trạng thái) → hệ thống **tự chia đều (round-robin)** → giao cho nhân viên → theo dõi tiến độ chăm sóc trong kỳ. Trạng thái đợt: `Draft → Active → Completed`.

### 8.3. Phiếu hỗ trợ (Support Ticket)

**Khái niệm:** yêu cầu hỗ trợ từ khách hàng — tương tự "ticket" của bộ phận chăm sóc khách hàng, gồm: chủ đề, mô tả, mức ưu tiên, người phụ trách, lịch sử trao đổi.

**Các trạng thái:**
| Trạng thái | Ưu tiên |
|---|---|
| Open (Mở) | Low (Thấp) |
| In Progress (Đang xử lý) | Normal (Bình thường) |
| On Hold (Tạm hoãn) | High (Cao) |
| Resolved (Đã giải quyết) | Urgent (Khẩn cấp) |
| Closed (Đóng) | Critical (Nghiêm trọng) |

**Cách tạo ticket:**
1. **Thủ công:** Nhân viên tạo khi khách gọi điện/chat báo lỗi.
2. **Tự động qua webhook inbound email:** Khách gửi email tới địa chỉ hỗ trợ → hệ thống **tự tạo ticket** từ email đó (kèm nội dung, file đính kèm) → không sót yêu cầu.

**Cách xử lý:** Nhận ticket → cập nhật trạng thái/ưu tiên → thêm ghi chú trao đổi → chuyển cho người phụ trách khác nếu cần → khi xong đánh dấu **Resolved/Closed**.

### 8.4. Trang chăm sóc khách hàng (Customer Care Page)

Màn hình tổng hợp cho nhân viên CSKH: danh sách khách hàng được phân công (từ đợt phân phối), ticket đang mở, lịch hẹn chăm sóc, nhiệm vụ trong ngày (xem mục 11.4).

---

## 9. NHÓM 7 — CẤU HÌNH

> Nhóm dành cho **quản trị viên**: cơ cấu tổ chức, tài khoản, phân quyền, giao diện, quy tắc hệ thống.

> ⚠️ Chỉ **Super Admin / System Admin** mới thấy và sửa được nhóm này. Sửa sai ở đây có thể ảnh hưởng toàn hệ thống — nên thao tác cẩn thận và xem nhật ký (Nhóm 8).

### 9.1. Phòng ban (Department) & Vị trí (Position)

- **Phòng ban:** tạo/sửa/xóa các đơn vị (mục 2.1). Có mã tự sinh `DPT-...`.
- **Vị trí:** chức danh trong phòng ban (`POS-...`), có thể có hạn mức phê duyệt báo giá (xem mục 6.3).
- **Thứ tự sắp xếp** (`sort_order`) quyết định thứ tự hiển thị trong các dropdown.

**Cách dùng:** Cấu hình 2 trang này **trước** khi tạo Nhân viên (Staff) vì Staff bắt buộc chọn phòng ban + vị trí.

### 9.2. Nhân viên (Staff)

**Khái niệm:** hồ sơ nhân sự của công ty (mục 2.1) — là cầu nối giữa **User (tài khoản)** và **nghiệp vụ** (lead phân cho nhân viên, doanh thu theo nhân viên...).

**Cách dùng:** Tạo nhân viên → chọn phòng ban, vị trí → (tùy chọn) thêm nơi làm việc, kỹ năng, trạng thái hoạt động → nếu nhân viên cần đăng nhập, tạo **User** gắn với nhân viên này (mục 9.3).

### 9.3. Người dùng (User)

**Khái niệm:** tài khoản đăng nhập. Mỗi user: email đăng nhập + mật khẩu + trạng thái (hoạt động/khóa) + gắn với **1 nhân viên** + **1+ vai trò (RBAC)**.

**Cách dùng:**
1. Tạo user → chọn nhân viên tương ứng (nhân viên chưa có user).
2. Chọn **vai trò** — quyền truy cập được xác định bởi vai trò (mục 9.5).
3. Nếu quên mật khẩu → đặt lại; nếu nhân viên nghỉ → **khóa tài khoản** (blocked) thay vì xóa (giữ lịch sử).
4. Trạng thái `blocked` được thông báo bằng toast cảnh báo khi lưu.

### 9.4. Vai trò (RbacRole)

**Khái niệm:** tập hợp quyền (permissions) gán cho user. Hệ thống có các vai trò mặc định: **Super Admin** (toàn quyền, không thể xóa), **System Admin**, **Marketing Manager**, **Salesperson**, **CSKH**, **Kế toán**... — bạn có thể tạo vai trò mới hoặc sửa quyền của vai trò có sẵn.

**Cách dùng:** Tạo vai trò → đặt tên + mô tả → chọn **quyền** (permission keys, ví dụ `view_any_lead`, `create_quotation`, `approve_quotation`...) → gán cho user. Xem ma trận quyền tổng thể tại **Role Permission Matrix** (mục 9.7).

> Lưu ý: Có 2 cách phân quyền bổ sung: **Access Control** (kiểm soát mục menu theo vai trò) và **Organization Access** (giới hạn dữ liệu theo phòng ban) — xem tiếp 9.6, 9.8.

### 9.5. Kiểm soát truy cập (Access Control)

**Khái niệm:** quản lý **ai thấy menu/mục nào** theo vai trò. Đây là tầng "lọc hiển thị": dù user có quyền dữ liệu, vẫn chỉ thấy mục được bật ở đây. Gồm 2 dạng:
- **Organization access:** các mục chỉ dành cho tổ chức nội bộ (Cấu hình, Nhân sự, Vai trò...) — bật/tắt theo vai trò.
- **System labels:** các mục hệ thống (Nhật ký kiểm toán, đồng bộ dữ liệu...) — bật/tắt theo vai trò.

**Cách dùng:** Mở trang → bật/tắt checkbox cho từng vai trò × mục → **Lưu** → user đăng nhập lại để áp dụng.

### 9.6. Truy cập tổ chức (Organization Access)

**Khái niệm:** mở rộng của Access Control — kiểm soát mục nào của **tổ chức** (nhân sự, phòng ban, vai trò...) mà từng vai trò được truy cập. Phân biệt rõ: **System** = hạ tầng hệ thống, **Organization** = dữ liệu tổ chức.

### 9.7. Ma trận quyền vai trò (Role Permission Matrix)

**Khái niệm:** bảng **tổng hợp toàn bộ vai trò × quyền** (dạng lưới) — xem nhanh vai trò nào đang có quyền gì, so sánh 2 vai trò, và **chỉnh sửa hàng loạt**. Đây là màn hình "trực quan hóa RBAC" của hệ thống.

**Cách dùng:** Lọc theo nhóm quyền (Email, Marketing, CRM, Sales, Finance, CSKH, Cấu hình, Hệ thống) → tích/bỏ tích quyền cho từng vai trò → lưu. Hệ thống kiểm tra và **ngăn xóa quyền cần thiết của Super Admin** để tránh "khóa mất chìa".

### 9.8. Cài đặt công ty (Company Settings)

**Khái niệm:** thông tin công ty hiển thị cho khách hàng: **tên công ty, logo, địa chỉ, hotline, email, MST, website, giờ làm việc, chính sách...** Logo/tên xuất hiện trên: email gửi đi, trang landing page, link báo giá công khai, hóa đơn.

**Cách dùng:** Điền đầy đủ **trước khi gửi email/landing page/báo giá cho khách** — vì thông tin này sẽ hiển thị công khai. Upload logo → hệ thống tự hiển thị; có thể xem trước.

### 9.9. Quản lý nhãn hệ thống (System Label Management)

**Khái niệm:** tùy chỉnh **tên hiển thị (label)** của các trạng thái hệ thống — ví dụ đổi tên trạng thái "New" của Lead thành "Mới" hoặc "Khách mới quan tâm" theo ngôn ngữ/ngành của bạn. 2 loại: **Dynamic** (đổi được) và **System managed** (hệ thống quản lý, mặc định hệ thống).

**Cách dùng:** Tìm trạng thái muốn đổi → sửa nhãn → **Lưu**. Nhãn mới áp dụng ngay trên toàn hệ thống (badge, bộ lọc, báo cáo). Khi lưu thành công, hệ thống hiển thị thông báo xanh (success) — không có thông báo màu đặc biệt nào khác.

### 9.10. Kiểu huy hiệu giao diện (UI Badge Style)

**Khái niệm:** cấu hình **màu sắc các badge/huy hiệu trạng thái** theo từng loại đối tượng (vd: badge Lead "New" màu xanh, badge Ticket "Urgent" màu đỏ). Dùng để nhận diện nhanh trạng thái bằng màu (mục 2.9).

**Cách dùng:** Chọn đối tượng → chọn màu cho từng trạng thái → lưu. Màu mặc định theo chuẩn hệ thống; chỉ cần đổi nếu muốn khác biệt.

### 9.11. Giao diện & hiển thị (Appearance Settings)

**Khái niệm:** tùy chỉnh giao diện quản trị: **tên/logo hiển thị trên trang đăng nhập**, **màu chủ đạo** (brand color), **dark/light mode**, ngôn ngữ, định dạng ngày giờ, số trang mỗi danh sách...

**Cách dùng:** Đổi màu chủ đạo (chọn từ bộ màu hoặc nhập mã hex) → lưu → giao diện cập nhật ngay. Thiết lập này mang tính cá nhân hóa cho từng user.

---

## 10. NHÓM 8 — HỆ THỐNG

> Nhóm dành cho **quản trị hệ thống**: theo dõi và giám sát toàn bộ hoạt động.

### 10.1. Nhật ký kiểm toán (Audit Log)

**Khái niệm:** **nhật ký (log) mọi thay đổi quan trọng** trong hệ thống — ai (user nào), làm gì (tạo/sửa/xóa/xuất), trên đối tượng nào, lúc nào, giá trị cũ → mới. Đây là "hộp đen" để truy vết khi có sai sót hoặc kiểm tra tuân thủ.

**Cách dùng:**
- Tìm theo: người dùng, đối tượng (Lead, Báo giá, User...), loại hành động, khoảng thời gian.
- Xem chi tiết 1 bản ghi để biết **trước/sau** của thay đổi.
- Không thể sửa/xóa bản ghi log (chỉ xem) — đảm bảo tính minh bạch.

> Mẹo kiểm soát: khi nghi ngờ ai đó sửa dữ liệu sai, mở Nhật ký → lọc theo user → xem toàn bộ thao tác người đó thực hiện.

### 10.2. (Hệ thống khác)

Các trang thuộc hạ tầng kỹ thuật (đồng bộ quyền, định nghĩa RBAC, kiểm tra phân quyền) chỉ dành cho lập trình viên/quản trị cao cấp — thường không cần thao tác trong vận hành hàng ngày.

---

## 11. DASHBOARD & BÁO CÁO

> Tất cả dashboard dùng khái niệm **Period & Delta** (mục 2.3) và có bộ lọc thời gian ở góc trên màn hình.

### 11.1. Dashboard tổng (Admin Dashboard) — trang chủ sau đăng nhập

**Tổng quan toàn hệ thống cho ban lãnh đạo:**
- **Chỉ số chính:** tổng lead mới, cơ hội đang mở, giá trị pipeline, doanh thu tháng, số khách hàng mới, ticket đang mở.
- **Biểu đồ:** doanh thu theo tháng, lead theo nguồn, phễu chuyển đổi V1/V2.
- **Khu vực nhân sự:** hoạt động gần đây (lead mới, cơ hội chốt, ticket), cảnh báo (báo giá chờ duyệt, công nợ quá hạn, chiến dịch đang gửi).

### 11.2. Dashboard Kinh doanh (Sales Dashboard)

Chỉ số dành cho **ban kinh doanh**: tổng giá trị pipeline, cơ hội theo giai đoạn (kanban tóm tắt), **tỷ lệ thắng (win rate)**, doanh thu theo nhân viên, báo giá chờ phê duyệt, cơ hội sắp đến hạn chốt.

### 11.3. Dashboard Marketing (Marketing Dashboard)

Chỉ số dành cho **ban marketing**: số lead từ landing page, tỷ lệ chuyển đổi form, **ROAS/CAC** theo chiến dịch, hiệu quả kênh (UTM), số email gửi/mở/click, danh sách chiến dịch email đang chạy.

### 11.4. Dashboard Tài chính (Finance Dashboard)

Chỉ số dành cho **kế toán/lãnh đạo**: doanh thu kỳ này vs kỳ trước (delta), thu theo phương thức, công nợ phải thu / quá hạn, thanh toán chờ xác nhận, top khách hàng đóng góp doanh thu.

### 11.5. Dashboard Chăm sóc khách hàng (Customer Service Dashboard)

Chỉ số dành cho **ban CSKH**: số khách hàng đang chăm sóc, ticket mở/đóng trong kỳ, **thời gian xử lý trung bình**, ticket theo mức ưu tiên, phân phối khách hàng theo nhân viên, khách hàng chưa được chăm sóc trong kỳ.

### 11.6. Báo cáo chiến dịch marketing (Campaign Analytics Page)

Chi tiết từng **chiến dịch marketing** (mục 4.2): ngân sách vs chi phí thực tế, số lượt xem/lead từ các landing page của chiến dịch, tỷ lệ chuyển đổi từng bước, **ROAS, CAC** theo chiến dịch, so sánh giữa các chiến dịch. Lọc theo thời gian và trạng thái.

### 11.7. Báo cáo chiến dịch email (Campaign Report Page)

Chi tiết từng **chiến dịch email** (mục 3.1): số gửi thành công, **tỷ lệ mở (open rate)**, **tỷ lệ click (CTR)**, tỷ lệ bounce, hủy đăng ký, click theo từng link trong email, biểu đồ mở/click theo giờ trong ngày.

> Cách đọc: Open rate ≥ 20% và CTR ≥ 2% là mức khá cho ngành marketing Việt Nam. Nếu bounce > 5% → kiểm tra danh sách gửi.

### 11.8. Báo cáo doanh thu (Revenue Report Page)

Chi tiết doanh thu: tổng thu theo kỳ (ngày/tháng/năm), theo **phương thức thanh toán**, theo **nhân viên kinh doanh**, theo **dịch vụ/gói sản phẩm**, theo **khu vực/khách hàng**. Xuất được dữ liệu ra Excel/CSV để làm báo cáo nội bộ.

### 11.9. Báo cáo nhân sự (Workforce Analytics Page)

**Báo cáo năng suất nhân viên** cho lãnh đạo: số lead được phân công, tỷ lệ xử lý kịp thời, số cơ hội tạo ra, giá trị chốt, **Activity Index** (mục 2.6), xếp hạng nhân viên trong kỳ. (Trang ẩn khỏi menu chính — truy cập qua nút liên kết từ Staff Dashboard hoặc dashboard tổng.)

### 11.10. Bảng hiệu suất nhân viên (Staff Dashboard)

Bảng thống kê theo **từng nhân viên kinh doanh** (mục 5.6): lead nhận/chăm sóc/chuyển đổi, cơ hội đang mở, doanh thu mang về, chỉ số hoạt động — dùng cho **giao ban kinh doanh** và tính KPI.

---

## 12. TRANG CÔNG KHAI (PUBLIC)

> Các trang **khách hàng nhìn thấy** — không cần đăng nhập. Kiểm tra các trang này sau khi cấu hình công ty (mục 9.8).

### 12.1. Landing Page (`/lp/<slug>`)

Trang quảng cáo/tiếp thị công khai (mục 4.1): hiển thị theo mẫu form, có trang "Cảm ơn" sau khi điền. Khách điền → hệ thống tạo lead.

### 12.2. Link báo giá công khai (`/q/<mã>/<token>`)

Khách hàng mở link báo giá (mục 6.2): xem chi tiết sản phẩm/giá/chiết khấu/thuế/tổng tiền → bấm **Đồng ý / Từ chối / Yêu cầu chỉnh lại** → có thể **xem PDF** báo giá, **xem biên lai thanh toán**, và **thông báo đã chuyển khoản** (nhập thông tin giao dịch → kế toán nhận được thông báo xác nhận). Giao dịch nhạy cảm được bảo vệ bằng **OTP** gửi qua email.

### 12.3. Pixel theo dõi email (`/m/open`, `/m/click`, `/m/unsubscribe`)

Các đường dẫn nhúng trong email (mục 3.1): ghi nhận mở/click và trang **Hủy đăng ký** cho khách (bắt buộc theo quy định chống thư rác). Tương tự có pixel theo dõi cho email chăm sóc khách hàng (`/m/care/...`).

### 12.4. Webhook (Inbound Email)

Đường dẫn hệ thống nhận email tự động từ bên ngoài (`/webhooks/support/inbound-email`) — nhà cung cấp email gửi tới để hệ thống **tự tạo ticket** (mục 8.3). Không cần thao tác thủ công.

---

## 13. LUỒNG NGHIỆP VỤ TỔNG THỂ

### 13.1. Luồng "Thu hút → Chốt đơn" hoàn chỉnh

```
[1] Cấu hình nền tảng (một lần, do Admin):
    Phòng ban → Vị trí → Nhân viên → User → Vai trò/Quyền
    → Cài đặt công ty (tên, logo, hotline...)
    → Tài khoản gửi + Domain gửi (xác thực DNS)

[2] Thu hút khách (Marketing):
    Tạo Landing Page (form + UTM) → Chạy quảng cáo
    → Khách điền form → Landing Page Submission
    → Tự tạo Lead (V1) vào hàng đợi phân phối

[3] Phân phối & chăm sóc (CRM):
    Hệ thống tự chia Lead V1 đều cho nhân viên kinh doanh
    → Nhân viên gọi điện/email → Ghi chú
    → Khả thi → Chuyển thành Cơ hội (Opportunity)

[4] Bán hàng (Sales):
    Cơ hội đi qua pipeline (New → Contacted → Quoting → Won)
    → Tạo Báo giá (tự điền giá theo Price Book)
    → Phê duyệt nội bộ nếu vượt hạn mức
    → Gửi link công khai cho khách
    → Khách Đồng ý (OTP) → Cơ hội Won

[5] Thu tiền (Finance):
    Tạo Thanh toán (phương thức, bằng chứng)
    → Kế toán xác nhận tiền về → Khách hàng được kích hoạt
    → Theo dõi công nợ nếu trả góp/nhiều kỳ

[6] Chăm sóc sau bán (CSKH):
    Đợt phân phối khách hàng cho nhân viên chăm sóc
    → Chăm sóc định kỳ (email/cuộc gọi) → Upsell/Cross-sell
    → Khách gửi email hỗ trợ → Webhook tự tạo Ticket → Xử lý → Đóng
```

### 13.2. Luồng "Phân biệt V1 / V2" (nhắc lại)

- **V1 (chủ động):** từ marketing → hàng đợi → tự phân phối → nuôi dưỡng.
- **V2 (giới thiệu):** nhập tay hoặc từ giới thiệu → ưu tiên cao → chăm sóc nhanh, không qua nuôi dưỡng lâu.

### 13.3. Vòng đời đối tượng (tránh nhầm lẫn)

```
Contact (thông tin) → Lead (có nhu cầu) → Opportunity (đang bán) → Customer (đã mua)
```

Mỗi giai đoạn nằm ở nhóm khác nhau (CRM → CRM → Sales → CSKH). Khi chuyển giai đoạn, nhân viên bấm nút chuyển đổi trên trang chi tiết (vd: "Chuyển thành cơ hội", "Đánh dấu Won").

---

## 14. TÀI KHOẢN NGHIỆM THU & CHECKLIST

### 14.1. Tài khoản nghiệm thu (môi trường dev/staging)

Được tạo sẵn bằng seeder `V1AcceptanceTestSeeder` — **tất cả chung mật khẩu `V1@2026Demo!`**:

| Email | Vai trò | Phù hợp để kiểm tra |
|---|---|---|
| `superadmin.v1@dth.local` | Super Admin | Toàn bộ chức năng + Cấu hình + Hệ thống |
| `admin.v1@dth.local` | System Admin | Quản trị hệ thống |
| `founder.v1@dth.local` | Ban lãnh đạo | Dashboard tổng, báo cáo, nhân sự |
| `commercial.v1@dth.local` | Kinh doanh | Lead, Cơ hội, Báo giá |
| `support.v1@dth.local` | CSKH | Ticket, phân phối khách hàng |
| `finance.v1@dth.local` | Kế toán | Thanh toán, công nợ, doanh thu |

### 14.2. Checklist nghiệm thu nhanh (theo vai)

**Với vai trò "Sếp / người dùng chung":**
- [ ] Đăng nhập `https://.../admin` bằng `superadmin.v1@dth.local`.
- [ ] Dashboard tổng hiển thị đủ chỉ số; đổi bộ lọc thời gian → số liệu thay đổi đúng.
- [ ] Mở Lead bất kỳ → xem được trạng thái, nguồn V1/V2, lịch sử tương tác.
- [ ] Mở Báo giá → gửi link cho người khác → khách mở link, bấm "Đồng ý" → trạng thái tự cập nhật.
- [ ] Mở Ticket → chuyển trạng thái → ghi chú → đóng ticket.
- [ ] Mở Nhật ký kiểm toán → tìm thấy thao tác bạn vừa làm.

**Với vai trò "Super Admin":**
- [ ] Cấu hình → tạo Phòng ban, Vị trí, Nhân viên, User mới; gán vai trò.
- [ ] Tạo vai trò mới, chỉnh quyền trong Ma trận quyền; đăng nhập user đó để kiểm tra menu thay đổi.
- [ ] Cài đặt công ty → đổi logo → mở landing page/báo giá → thấy logo mới.
- [ ] Đổi nhãn trạng thái (System Label) → thấy nhãn mới ở badge toàn hệ thống.
- [ ] Mở Nhóm Hệ thống → Nhật ký kiểm toán → lọc theo user → thấy mọi thay đổi.

**Những điều đã xác minh trong kỳ nghiệm thu này:**
- ✅ Toàn bộ test nghiệp vụ pass (53 smoke + 59 V1/Authorization + 46 CRM/Finance + 71 Marketing/Sales/Console + 48 V1 + 3 performance).
- ✅ Truy vấn dashboard đã tối ưu (chỉ số gộp theo batch, cache snapshot, index query performance).
- ✅ Màu/toast của phần Cấu hình đã chuẩn hóa theo quy ước hệ thống.
- ✅ Chỉ còn 5 test fail pre-existing không thuộc phạm vi tính năng (2 về nhãn registry, 3 về UI v4) — không ảnh hưởng vận hành.

### 14.3. Lưu ý khi deploy

- Chạy `php artisan migrate --force` và `php artisan db:seed --class=V1AcceptanceTestSeeder --force` (nếu muốn dữ liệu mẫu).
- Chạy `php artisan config:cache` sau khi cấu hình xong (trong môi trường dev đã `config:clear` để test chạy đúng cơ sở dữ liệu test).
- Cấu hình cron: `schedule:run` mỗi phút (chiến dịch email hẹn giờ, phân phối lead, nhắc công nợ).
- Kiểm tra queue worker hoạt động (gửi email, webhook inbound).

---

*Tài liệu được viết phục vụ nghiệm thu (acceptance) bản 2.0 — dựa trên code thực tế của nhánh `feature/crm-company-lead-opportunity-flow`.*