# USE MANUAL — Hướng dẫn sử dụng hệ thống

> Tài liệu hướng dẫn sử dụng từng chức năng theo module và giải thích các khái niệm nghiệp vụ của hệ thống.
> Hệ thống chia làm 8 nhóm chức năng chính: **Email**, **Marketing**, **CRM**, **Kinh doanh**, **Tài chính**, **Chăm sóc khách hàng**, **Cấu hình**, **Hệ thống**.

---

## Mục lục

1. [Giới thiệu chung](#1-giới-thiệu-chung)
2. [Các khái niệm nghiệp vụ nền tảng](#2-các-khái-niệm-nghiệp-vụ-nền-tảng)
3. [Module Cấu hình](#3-module-cấu-hình)
4. [Module Email](#4-module-email)
5. [Module Marketing](#5-module-marketing)
6. [Module CRM](#6-module-crm)
7. [Module Kinh doanh](#7-module-kinh-doanh)
8. [Module Tài chính](#8-module-tài-chính)
9. [Module Chăm sóc khách hàng](#9-module-chăm-sóc-khách-hàng)
10. [Module Hệ thống](#10-module-hệ-thống)
11. [Luồng nghiệp vụ tổng thể](#11-luồng-nghiệp-vụ-tổng-thể)

---

## 1. Giới thiệu chung

Hệ thống là một nền tảng quản trị kinh doanh tích hợp, bao gồm:

| Nhóm | Mô tả |
|---|---|
| **Email** | Gửi email tiếp thị hàng loạt: chiến dịch email, mẫu email, tài khoản gửi, danh sách loại trừ |
| **Marketing** | Thu hút khách hàng: trang đích (landing page), biểu mẫu, chiến dịch quảng cáo, danh sách liên hệ, phân khúc, thẻ |
| **CRM** | Quản lý quan hệ khách hàng: công ty, leads, đánh giá & chuyển đổi |
| **Kinh doanh** | Quản lý bán hàng: cơ hội kinh doanh, báo giá, phê duyệt, sản phẩm/dịch vụ |
| **Tài chính** | Xác minh thanh toán, lịch sử thanh toán, tài khoản ngân hàng |
| **Chăm sóc khách hàng** | Hậu mãi: khách hàng, phân phối khách hàng cho nhân viên, chăm sóc định kỳ |
| **Cấu hình** | Tổ chức (phòng ban, chức danh, nhân viên), tài khoản & quyền, màu nhãn, cài đặt công ty |
| **Hệ thống** | Nhật ký hoạt động, ma trận vai trò |

**Nguyên tắc xuyên suốt:** dữ liệu được chuẩn hóa và dùng chung giữa các module — một khách hàng, một màu nhãn, một nguồn quyền. Các màn hình không "tự đặt" màu hay tự cấp quyền riêng lẻ.

---

## 2. Các khái niệm nghiệp vụ nền tảng

### 2.1. Chuỗi dữ liệu tổ chức — Phòng ban → Chức danh → Nhân viên

- **Phòng ban (Department):** đơn vị tổ chức (Ví dụ: Phòng Marketing, Phòng Kinh doanh). Phòng ban có **màu nhận diện** — nguồn duy nhất cho nhãn màu của phòng ban ở mọi màn hình. Phòng ban **không cấp quyền phần mềm**.
- **Chức danh (Position):** danh mục dùng chung toàn công ty (Giám đốc, Trưởng phòng, Nhân viên…), **không gắn riêng với phòng ban nào**. Mỗi chức danh có **Nhóm chức danh** (Ban lãnh đạo / Quản lý / Chuyên môn / Vận hành / Thời vụ) và **Cấp thẩm quyền** (Lãnh đạo cấp cao / Quản lý / Trưởng nhóm / Thành viên / Giới hạn). Cấp thẩm quyền **không cấp quyền phần mềm**.
- **Nhân viên (Staff):** hồ sơ con người. Mỗi nhân viên thuộc một phòng ban, mang một chức danh và **một hoặc nhiều Chức năng nghiệp vụ** (Marketing, CRM, Kinh doanh, Tài chính, Chăm sóc khách hàng…). **Chức năng nghiệp vụ là nguồn quyền**: quyền phần mềm của nhân viên được suy ra từ các chức năng này.

> **Nguyên tắc:** "Danh mục trước, Nhân viên sau" — tạo Phòng ban và Chức danh trước, rồi mới tạo Nhân viên.

### 2.2. Tài khoản truy cập và vai trò

- **Tài khoản truy cập (User):** một tài khoản đăng nhập. Mỗi nhân viên chỉ có **tối đa một tài khoản**. Tài khoản gắn với nhân viên (1-1); khi liên kết, quyền của tài khoản được **tự động đồng bộ** từ vai trò hệ thống và chức năng nghiệp vụ của nhân viên.
- **Vai trò hệ thống (5 vai trò nền tảng):**

| Vai trò | Ý nghĩa | Cần nhân viên? |
|---|---|---|
| Super Admin | Toàn quyền hệ thống | Không |
| Quản trị hệ thống | Quản trị hệ thống + tổ chức | Không |
| Ban điều hành | Xem xuyên phòng ban (chỉ đọc) | Có |
| Người dùng | Người dùng nghiệp vụ, quyền suy ra từ chức năng nghiệp vụ | Có |
| Chỉ xem / Kiểm soát | Chỉ đọc | Không |

- **Vai trò & quyền (RBAC):** bổ sung các **vai trò tùy chỉnh** với tập quyền chi tiết theo 6 Module (Marketing, CRM, Kinh doanh, Tài chính, Chăm sóc khách hàng, Hệ thống). Chọn một Module là **tự cấp toàn bộ quyền của Module đó**, sau đó có thể bỏ bớt từng quyền. Các vai trò chuẩn theo chức năng nhân viên được hệ thống tự đồng bộ; vai trò tùy chỉnh do quản trị viên gán ở trường "Vai trò quyền bổ sung" của tài khoản.

> **Bảo vệ đặc biệt:** vai trò `super_admin` không bao giờ bị xóa; tài khoản Super Admin chỉ người Super Admin khác mới sửa/xóa được; không xóa tài khoản đang đăng nhập; chỉ xóa Super Admin khi còn ít nhất một tài khoản root hoạt động khác.

### 2.3. Hệ thống màu dùng chung

- **Bảng màu hệ thống:** 22 màu đã đăng ký (Xám, Chủ đạo, thông tin, thành công, cảnh báo, đỏ và các tông màu từ Cam → Đỏ hồng).
- **Màu trạng thái nghiệp vụ = cố định theo ý nghĩa (không sửa được):**

| Màu | Ý nghĩa | Ví dụ trạng thái |
|---|---|---|
| Xám | Trung tính | Nháp, Mới, Không hoạt động, Đã lưu trữ |
| Xanh thông tin (info) | Đang xử lý, đã thực hiện | Đã phân công, Đã gửi, Đã mở, Đang xử lý |
| Xanh thành công (success) | Hoàn thành, tích cực | Hoạt động, Đã duyệt, Đã chuyển đổi, **Đã thanh toán**, Đã xuất bản |
| Vàng cảnh báo (warning) | Chờ đợi, treo | **Chờ xử lý**, Lên lịch, Đang gửi, Tạm dừng |
| Đỏ (danger) | Lỗi, tiêu cực | Từ chối, Thất bại, Hủy, Trả lại, Chặn, Hủy đăng ký |

- **Màu nhãn dùng chung (UiBadgeStyle):** cho phép tùy chỉnh màu của 3 nhóm nhãn: **Vai trò hệ thống**, **Chức năng nghiệp vụ**, **Nhóm đối tượng**. Trạng thái nghiệp vụ không nằm trong danh sách này.

### 2.4. Hai luồng nghiệp vụ (V1 và V2)

Hệ thống hỗ trợ 2 luồng nghiệp vụ (cấu hình bằng biến môi trường):

- **Luồng V1 (cổ điển):** Lead (khách tiềm năng từ trang đích) → Quy trình đánh giá liên hệ (Đánh giá liên hệ / Kanban) → **Chuyển thành khách hàng** khi đánh giá đạt kết quả "Đã mua hàng".
- **Luồng V2 (đầy đủ):** Lead → Đánh giá & đủ điều kiện → **Cơ hội kinh doanh** → **Báo giá → Khách hàng xác nhận → Thanh toán → tự động tạo Khách hàng** khi đã thanh toán (khách hàng chỉ hình thành sau khi có thanh toán thật).

Tùy theo cấu hình mà một số màn hình (Công ty, Leads, Cơ hội kinh doanh, Đối chiếu doanh nghiệp, Quy trình đánh giá liên hệ) xuất hiện hoặc ẩn đi.

### 2.5. Mã tự sinh

Hệ thống tự sinh và **khóa ổn định** các mã: `EMP-XXXX` (nhân viên), `DEPT-XXXX` (phòng ban), `JOB-XXXX` (chức danh), `DIST-…` (phiên phân phối), `PAY…` (thanh toán), `custom_{tên}` (vai trò). Các mã này dùng để đối chiếu suốt vòng đời dữ liệu.

### 2.6. Nhật ký hoạt động

Mọi thao tác nghiệp vụ (tạo/sửa/xóa, chuyển trạng thái, gửi email, xóa tài khoản…) đều được ghi vào **Nhật ký hoạt động** kèm người thực hiện, phân hệ, thời gian và nội dung thay đổi trước/sau.

---

## 3. Module Cấu hình

> Menu: **Phòng ban → Chức danh → Nhân viên → Tài khoản truy cập → Vai trò & quyền → Màu nhãn dùng chung → Cài đặt công ty**.
> Thứ tự bắt buộc: danh mục trước (Phòng ban, Chức danh), nhân viên sau, rồi mới cấp tài khoản & quyền.

### 3.1. Phòng ban

**Để quản lý đơn vị tổ chức và màu nhận diện thống nhất.**

- **Tạo Phòng ban:** nhấn **Tạo phòng ban** (mở modal) → nhập Tên phòng ban (bắt buộc, không trùng) → chọn **Màu nhận diện** từ bảng màu 22 màu → (tùy chọn) Mô tả → nhấn **Kích hoạt** nếu muốn dùng ngay → **Lưu**. Mã phòng ban được tự sinh.
- **Xem nhân viên:** ở hàng phòng ban, chọn **Xem nhân viên** — danh sách nhân viên của phòng ban hiện ngay dưới bảng, có nút **Tạo nhân viên** (được điền sẵn phòng ban).
- **Sửa / Xóa:** Chỉnh sửa trong modal; **Xóa chỉ được khi phòng ban không còn nhân viên** — phải chuyển/xử lý nhân viên trước.
- **Bulk actions:** chọn nhiều phòng ban → **Kích hoạt / Ngừng hoạt động / Xóa mục đã chọn** (hệ thống báo rõ bao nhiêu mục đã xóa, bao nhiêu mục phải giữ lại).

> **Lưu ý:** màu phòng ban là nguồn duy nhất cho nhãn "Phòng ban" ở mọi màn hình. Tắt phòng ban không ảnh hưởng dữ liệu nhân viên.

### 3.2. Chức danh

**Danh mục chức danh dùng chung toàn công ty — tạo một lần, tái sử dụng cho mọi phòng ban.**

- Nhấn **Tạo chức danh** → nhập Tên (gợi ý sẵn: Giám đốc, Trưởng phòng, Chuyên viên…), chọn **Nhóm chức danh**, **Cấp thẩm quyền tổ chức**, (tùy chọn) **Phạm vi nghiệp vụ gợi ý**, trạng thái hoạt động → **Lưu**. Mã chức danh tự sinh.
- **Xóa** chỉ được khi chưa nhân viên nào đang giữ chức danh này ("Hãy chuyển nhân viên sang chức danh khác trước khi xóa").
- Bảng có bộ lọc theo Nhóm, Cấp thẩm quyền, Trạng thái và cột **Đang sử dụng** (số nhân viên đang giữ).

### 3.3. Nhân viên

**Hồ sơ nhân viên kết nối Phòng ban + Chức danh + Chức năng nghiệp vụ + khả năng nhận khách hàng + tài khoản truy cập.** Gồm 5 mục:

1. **Thông tin nhân viên:** Họ và tên, Số điện thoại (tự chuẩn hóa, chống trùng), Mã nhân viên tự sinh.
2. **Cơ cấu tổ chức:** Phòng ban (bắt buộc), Chức danh (danh mục dùng chung, gợi ý theo nhóm chuyên môn).
3. **Chức năng nghiệp vụ:** bảng các chức năng của nhân viên — mỗi dòng: Chức năng + **Cấp thẩm quyền nghiệp vụ** (Thành viên / Trưởng nhóm / Quản lý / Lãnh đạo cấp cao) + **Chức năng chính** (đánh dấu chức năng chính, hệ thống tự hạ chức năng cũ) + trạng thái. **Đây là "năng lực" và nguồn quyền của nhân viên.**
4. **Trạng thái & khả năng tiếp nhận:** Trạng thái làm việc (Đang làm việc / Tạm nghỉ / Đã nghỉ việc) + **Có thể nhận khách hàng** + **Sức chứa khách hàng** (giới hạn số khách tối đa) + **Trọng số phân phối** (mức độ ưu tiên nhận khách khi phân phối tự động).
5. **Tài khoản truy cập:** liên kết tài khoản đăng nhập (chỉ hiện nhân viên chưa có tài khoản); hoặc dùng action **Cấp tài khoản truy cập** trên bảng.

> **Tự động phân phối lại khách hàng:** khi nhân viên chuyển sang Tạm nghỉ/Đã nghỉ, các khách hàng đang do nhân viên đó **phụ trách chính** được tự động phân phối lại cho nhân viên nhẹ việc nhất; khi nhân viên quay lại làm việc, phân công cũ được trả về.

### 3.4. Tài khoản truy cập

**Quản lý tài khoản đăng nhập.**

- **Tạo tài khoản:** chọn **Nhân viên** (tự điền tên hiển thị, xem trước Phòng ban/Chức danh) → nhập Email đăng nhập (bắt buộc, duy nhất) → chọn **Vai trò hệ thống** (Super Admin chỉ người Super Admin mới thấy) → đặt Mật khẩu → **Vai trò quyền bổ sung** (chọn vai trò tùy chỉnh; chỉ hiện khi có quyền quản lý RBAC) → bật **Tài khoản hoạt động** → Lưu.
- **Sửa:** có thể thay Nhân viên liên kết, đổi vai trò, đặt lại mật khẩu (chỉ nhập khi đổi). Quyền của tài khoản **tự động đồng bộ lại** sau mỗi lần lưu.
- **Khóa/ngừng hoạt động:** tắt "Tài khoản hoạt động" hoặc dùng bulk **Ngừng hoạt động mục đã chọn** — tài khoản không đăng nhập được nữa.
- **Xóa:** chỉ Super Admin; **không xóa được chính mình**; khi xóa, tài khoản bị xóa mềm (email ẩn danh hóa, phiên đăng nhập bị hủy) còn **hồ sơ nhân viên và lịch sử nghiệp vụ được giữ nguyên**, liên kết nhân viên–tài khoản được giải phóng.

### 3.5. Vai trò & quyền

**Quản lý vai trò tùy chỉnh và tập quyền.**

- Nhấn **Tạo vai trò** → nhập **Tên** (hiển thị) → **Mã vai trò** tự sinh (chỉ hiện khi sửa) → **Mô tả**.
- Ở mục **Quyền hạn**: đánh dấu các **Module được cấp quyền** → hệ thống tự cấp toàn bộ quyền của module và mở ra danh sách **Quyền chi tiết trong Module** (có thể bỏ bớt từng quyền, tìm kiếm, chọn nhanh). Ví dụ cấp Module "Kinh doanh" = toàn bộ quyền Xem/Tạo/Duyệt báo giá…; bỏ bớt "Duyệt báo giá" nếu vai trò chỉ soạn báo giá.
- **Ma trận vai trò hệ thống** (nút trên trang danh sách): giải thích 5 vai trò nền tảng được gán sẵn, ai cần gắn nhân viên, quyền nào được suy ra.
- **Xóa:** vai trò `super_admin` không bao giờ xóa được; quản trị viên thường chỉ xóa được vai trò tùy chỉnh (không phải vai trò hệ thống). Bulk xóa sẽ bỏ qua các vai trò không được phép xóa.

### 3.6. Màu nhãn dùng chung

**Bảng quy tắc màu thống nhất để cùng một nhãn có cùng màu ở mọi màn hình.**

- **Thêm quy tắc màu:** chọn **Nhóm áp dụng** (Vai trò hệ thống / Chức năng nghiệp vụ / Nhóm đối tượng) → chọn **Giá trị** (ví dụ "Trưởng chức năng Marketing", "Đã hủy đăng ký"…) → chọn **Màu hiển thị** từ bảng màu (dạng hình tròn) → Lưu. Trang **Xem trước** cho thấy nhãn thực tế với màu đã chọn.
- Mỗi Giá trị chỉ có một màu trong một Nhóm (duy nhất theo cặp Nhóm–Giá trị). Màu **Trạng thái nghiệp vụ** không nằm ở đây (đã cố định theo ý nghĩa tại mục 2.3).
- **Bulk actions:** Xóa mục đã chọn.

### 3.7. Cài đặt công ty

- **Thông tin công ty:** tên (bắt buộc), MST, SĐT, địa chỉ, email, website, **logo** (dùng trên thanh điều hướng, báo giá, chứng từ).
- **Chính sách vận hành:**
  - **Chính sách duyệt báo giá:** Không cần duyệt / Duyệt mọi báo giá / Duyệt khi vượt ngưỡng giá trị / Duyệt khi vượt ngưỡng chiết khấu / Duyệt khi vượt giá trị hoặc chiết khấu (ngưỡng mặc định 20.000.000₫ hoặc 10%).
  - **Cách khách xác nhận báo giá:** Xác nhận trực tiếp qua liên kết / Xác minh bằng OTP email.
  - **Bắt buộc chứng từ chuyển khoản:** bật thì khách phải tải chứng từ khi thông báo chuyển khoản.
  - **Bật Ticket hỗ trợ hậu bán hàng.**
- **API VietQR:** Client ID + API Key (mã hóa trong CSDL, không bao giờ hiển thị lại; bỏ trống khi lưu = giữ nguyên khóa cũ) — dùng để tạo QR và tra cứu tên chủ tài khoản ngân hàng.

---

## 4. Module Email

> Menu (nhóm **Email**): **Chiến dịch email → Mẫu email → Danh mục mẫu email → Tài khoản gửi → Mục loại trừ**. Báo cáo chiến dịch ở màn hình ẩn (nút "Báo cáo chiến dịch" từ dashboard/tab Email).

### 4.1. Chiến dịch email

**Gửi email hàng loạt theo kịch bản: chuẩn bị → chọn đối tượng → lên lịch → gửi → đo lường.**

- **Tạo chiến dịch:** nhập **Tên**, **Chủ đề** (bắt buộc), **Dòng xem trước email** (preheader), chọn **Mẫu email**, chọn **Tài khoản gửi** (chỉ hiển thị tài khoản **chung** — không thuộc phòng ban).
- **Đối tượng nhận** (`Loại đối tượng`):
  - **Tất cả khách hàng đã đăng ký** nhận email (mặc định) — chỉ gửi cho khách có trạng thái đồng ý = Đã đăng ký;
  - **Danh sách liên hệ / Thẻ / Phân khúc** — chọn một danh sách (tĩnh), thẻ hoặc phân khúc (điều kiện động);
  - **Liên hệ đủ điều kiện từ trang đích** — kèm chọn **Trang đích** (các liên hệ tạo ra từ trang đích đó đã được đánh giá đủ điều kiện).
- **Lịch gửi:** chọn **Ngày gửi theo lịch** hoặc để trống để gửi ngay.
- **Trạng thái chiến dịch:** Nháp → (tùy chọn Đang gửi thử) → Đã lên lịch → Đang chuẩn bị → Đang gửi → **Đã gửi**; có thể Tạm dừng, Hủy, hoặc chuyển Thất bại. Mỗi lần đổi trạng thái đều được ghi nhật ký.
- **Theo dõi:** xem chiến dịch (Xem nhanh) để biết số người nhận, tỷ lệ mở, tỷ lệ nhấp, bị trả lại, hủy đăng ký.

> **Gửi thử:** trang Tài khoản gửi có nút **Gửi thử** — nhập email nhận, chủ đề, nội dung để kiểm tra tài khoản trước khi chạy chiến dịch thật.

### 4.2. Mẫu email

**Mẫu email tái sử dụng cho chiến dịch, hỗ trợ biến thay thế (placeholder):** `{{first_name}}`, `{{full_name}}`, `{{email}}`, `{{company_name}}`, `{{unsubscribe_url}}` (liên kết hủy đăng ký — tự chèn nếu thiếu), `{{landing_page_url}}`.

- Tạo mẫu: nhập Tên, chọn **Danh mục** (mặc định "Marketing"; có thể tạo danh mục mới ngay trong lúc chọn), Chủ đề, Dòng xem trước, trạng thái (Bản nháp / Hoạt động / Không hoạt động) và **Nội dung HTML** (soạn trực quan) + **Nội dung văn bản** (cho trình đọc email thuần văn bản).
- Nút **Xem trước** mở mẫu trong tab mới.
- **Mẹo:** nếu mẫu không có `{{landing_page_url}}`, hệ thống tự nối liên kết trang đích vào cuối email.

**Danh mục mẫu email:** nhóm mẫu theo loại (Marketing, Báo giá…) với màu riêng. Mẫu loại "Báo giá" được dùng trong luồng gửi báo giá (mục 7.3).

### 4.3. Tài khoản gửi

**Nguồn gửi email; SMTP riêng hoặc bộ gửi hệ thống.**

- **Nhà cung cấp:** `Laravel Mail` (bộ gửi hệ thống) hoặc `SMTP` (nhập cấu hình host, port, mã hóa, username, password — mã hóa trong CSDL).
- **Danh tính người gửi:** Tên người gửi + Email gửi (bắt buộc), Email phản hồi.
- **Phòng ban:** chọn phòng ban sở hữu → tài khoản phòng ban dùng cho email chăm sóc khách hàng; **bỏ trống = tài khoản chung** dùng cho chiến dịch email.
- **Giới hạn gửi:** giới hạn theo ngày/giờ để tránh bị chặn (trường SMTP).
- **Gửi thử** (action ở trang danh sách) để kiểm tra trước khi dùng.

### 4.4. Mục loại trừ (Suppression List)

**Danh sách email "cấm gửi".** Trước mỗi lần gửi, hệ thống kiểm tra; email nằm trong danh sách sẽ bị bỏ qua (trạng thái "Bỏ qua" trong chiến dịch).

- Tự động thêm khi: khách **hủy đăng ký**, email **bị trả lại** (bounce), người nhận **khiếu nại** (complaint).
- Thêm thủ công: nhập Email + **Lý do**: Đã hủy đăng ký / Email bị trả lại / Khiếu nại / Thủ công / Email không hợp lệ / **Không được liên hệ** (do_not_contact) + Nguồn, Ghi chú.

### 4.5. Báo cáo chiến dịch

Chọn một chiến dịch đã gửi → xem: tổng người nhận, chờ gửi, đã gửi, thất bại, đã mở, đã nhấp, hủy đăng ký + các tỷ lệ (mở/nhấp/thất bại/hủy) + **khách đã thanh toán và doanh thu** ghi nhận từ chiến dịch (tỷ lệ chuyển đổi = số khách trả tiền / số đã gửi). Có nút **Xuất CSV** (mở được bằng Excel).

---

## 5. Module Marketing

> Menu (nhóm **Marketing**): **Chiến dịch quảng cáo → Trang đích → Mẫu biểu mẫu → Lượt gửi biểu mẫu → Danh sách liên hệ → Phân khúc → Thẻ → Trường tùy chỉnh**.

### 5.1. Chiến dịch quảng cáo

**Gói quảng bá (nhiều kênh: Facebook, Google, Zalo…) để đo hiệu quả CAC/ROAS** — khác với Chiến dịch email.

- **Thông tin:** Tên, Trạng thái (Nháp / Hoạt động / Tạm dừng / Hoàn thành), Mô tả.
- **Phạm vi quảng bá:** chọn 1+ **Dịch vụ được quảng bá** (ví dụ Web Hosting + VPS) và các **Trang đích** liên quan (chỉ chọn được trang đích có dịch vụ chính nằm trong phạm vi).
- **Thời gian & ngân sách:** ngày bắt đầu/kết thúc, Ngân sách (₫).
- **Ghi chú vận hành:** nội bộ, không hiển thị cho khách.

### 5.2. Trang đích (Landing Page)

**Trang thu hút khách, thu thập thông tin qua biểu mẫu.** Đây là "cỗ máy tạo Lead" của hệ thống.

**Quy trình tạo và xuất bản:**
1. Tạo **Chiến dịch quảng cáo** và khai báo **Dịch vụ** trong phạm vi.
2. **Tạo Trang đích:** Tên (tự sinh đường dẫn), **Chiến dịch quảng cáo** liên kết, chọn **Dịch vụ chính** (phải thuộc phạm vi chiến dịch quảng cáo) và **Gói dịch vụ được phép**; gắn **2 biểu mẫu: một loại Cá nhân và một loại Doanh nghiệp** (hiển thị dạng tab ở cuối trang); chỉnh **Tone màu biểu mẫu** (màu chính, nền, chữ, bo góc…).
3. Cấu hình **Tự động hóa**: Thẻ tự động, Danh sách liên hệ tự động (cho phép tự tạo nếu chưa tồn tại), tự tạo phân khúc. Ví dụ: mọi khách gửi biểu mẫu sẽ được gắn thẻ "Email Marketing".
4. **Xuất bản:** hệ thống kiểm tra — phải gắn chiến dịch (email hoặc quảng cáo), dịch vụ phải nằm trong phạm vi, **thiếu cả hai biểu mẫu (Cá nhân + Doanh nghiệp) thì báo lỗi liệt kê từng phần thiếu**. Sau khi xuất bản, trang có sẵn qua đường dẫn công khai `/lp/{slug}`.
5. **Tạo URL theo dõi (UTM):** nhập **Nguồn** (Facebook, Instagram, Zalo, Google…), **Kênh** (Mạng xã hội, CPC, Email…), Chiến dịch, Nội dung, Từ khóa → hệ thống tạo URL kèm tham số UTM và lưu vào màn hình **Liên kết**. Dán URL này lên các kênh quảng cáo để đo nguồn khách.

> **UTM là gì?** UTM là các tham số thêm vào URL để biết khách đến từ kênh nào. Hệ thống ghi nhận lượt xem và lượt gửi biểu mẫu kèm UTM, giúp so sánh hiệu quả Facebook vs Google vs Zalo…

**Khái niệm cần biết:**
- **Thẻ tự động theo giá trị:** trường biểu mẫu có thể "tạo thẻ từ giá trị" — khách chọn "Lập trình viên" thì tự tạo/gắn thẻ "Lập trình viên".
- Trạng thái trang đích: Bản nháp / **Đã xuất bản** / Đã lưu trữ; **Hủy xuất bản** trả trang về Nháp.

### 5.3. Mẫu biểu mẫu (Form Template)

**Định nghĩa bộ trường cần thu thập, dùng lại cho nhiều trang đích.**

- **Chi tiết:** Tên, **Loại biểu mẫu** (Cá nhân / Doanh nghiệp), Trạng thái, nội dung nút gửi, thông báo thành công, URL chuyển hướng sau khi gửi.
- **Các trường biểu mẫu:** thêm từng trường — Nhãn (tự sinh khóa kỹ thuật), **Loại trường** (Văn bản / Email / SĐT / Số / Ngày / Vùng văn bản / Chọn / Radio / Chọn nhiều / Hộp kiểm / Ẩn) + các tùy chọn, bắt buộc hay không, **Nơi lưu dữ liệu** (ánh xạ vào hồ sơ liên hệ: tên, email, SĐT, công ty, trường tùy chỉnh, hoặc **Dịch vụ quan tâm** của Lead), **Tự tạo thẻ từ giá trị**.
- **Tự động hóa** tương tự trang đích (thẻ/danh sách tự động).
- Dùng chung: cùng một mẫu biểu mẫu có thể tái sử dụng cho Hosting, VPS, Email doanh nghiệp — dịch vụ và gói được cấu hình tại Landing Page, không nhúng vào mẫu.

### 5.4. Lượt gửi biểu mẫu

**Nhật ký mọi lượt khách gửi biểu mẫu từ các trang đích — trung tâm tiếp nhận Lead.**

- Cột chính: Trang đích, **Loại lượt gửi** (Cá nhân / Doanh nghiệp), Email, **Trạng thái xử lý** (Đã tiếp nhận → Đã ghi nhận → Xử lý thất bại / Spam), **Mã Lead** (liên kết thẳng hồ sơ Lead), **Trạng thái phân phối** (Đã phân phối / Chưa phân phối), **Thao tác** (Đã tạo / Đã cập nhật liên hệ / Đã bỏ qua), thời gian gửi.
- Khi khách gửi biểu mẫu, hệ thống tự động: **tạo/cập nhật Liên hệ + Công ty**, gắn thẻ/danh sách theo cấu hình, **tạo Lead** (kèm ảnh chụp nội dung biểu mẫu), sẵn sàng phân phối cho nhân viên kinh doanh. Chống gửi trùng bằng vân tay dữ liệu.
- Thao tác: **Xem** chi tiết dữ liệu đã gửi, **Chỉnh sửa** (đổi trạng thái xử lý, thêm ghi chú), Xóa (admin).

### 5.5. Danh sách liên hệ (Contact List)

**Nhóm khách hàng tĩnh, chứa các bản ghi Khách hàng (Customer).** Dùng làm đối tượng gửi chiến dịch email hoặc điều kiện phân khúc.

- Tạo: Tên + **Loại danh sách** (Bản tin / Dịch vụ / Sự kiện) + Trạng thái (Hoạt động / Không hoạt động / Đã lưu trữ).
- Thành viên được thêm: thủ công, hoặc **tự động** khi khách gửi biểu mẫu (cấu hình tại Trang đích — "Danh sách liên hệ tự động").

> **Phân biệt:** Danh sách liên hệ = tĩnh (liệt kê sẵn); **Phân khúc** = động (tính theo điều kiện tại thời điểm chạy).

### 5.6. Phân khúc (Segment)

**Bộ lọc khách hàng động theo điều kiện, đánh giá trực tiếp khi dùng.**

- Tạo điều kiện: Trường + Toán tử (Bằng / Không bằng / Lớn hơn hoặc bằng / Nhỏ hơn hoặc bằng) + Giá trị.
- **Trường có thể chọn:** Trạng thái khách hàng (Tiềm năng / Hoạt động / Bị chặn…), Giai đoạn vòng đời, Loại khách hàng (Cá nhân / Doanh nghiệp), Nhân viên phụ trách, Có thẻ X, Thuộc danh sách Y, Trạng thái đồng ý nhận email, Chuyển đổi trong vòng N ngày / trong khoảng ngày, Xác thực mã số thuế.
- Nút **Xem trước số lượng** (báo "Số khách hàng phù hợp: N") và **Xem trước mẫu** (danh sách email mẫu) để kiểm tra trước khi dùng.

### 5.7. Thẻ (Tag)

**Nhãn gán cho khách hàng, dùng để lọc/nhóm nhanh** — được dùng trong chiến dịch email, điều kiện phân khúc, và tự gắn từ biểu mẫu trang đích. Mỗi thẻ có Tên + Màu; bảng hiển thị số lượng khách hàng mang thẻ.

### 5.8. Trường tùy chỉnh

**Cho phép công ty thêm trường dữ liệu riêng cho liên hệ** (chưa có trong mẫu chuẩn) — dùng trong biểu mẫu trang đích và phân khúc. Kiểu: Văn bản / Số / Ngày / Đúng-Sai / Chọn / Chọn nhiều (kèm danh sách lựa chọn), đánh dấu Bắt buộc, Cho phép lọc.

---

## 6. Module CRM

> Menu (nhóm **CRM**): **Công ty → Leads → Đối chiếu doanh nghiệp** (màn hình Liên hệ cá nhân/doanh nghiệp ẩn khỏi menu, truy cập qua luồng tự động).

### 6.1. Công ty

**Hồ sơ pháp lý của doanh nghiệp khách hàng (luồng V2).**

- Tạo/sửa: Tên pháp lý, Mã số thuế, Lĩnh vực, **Giai đoạn vòng đời** (Tiềm năng / Đủ điều kiện / Khách hàng / Không hoạt động), Tên miền email, Website, SĐT, Địa chỉ.
- **Tab Liên hệ của công ty:** gắn các liên hệ với vai trò (Người quyết định / Người ảnh hưởng / Liên hệ kỹ thuật / Liên hệ thanh toán / Người sử dụng / Khác), đánh dấu Liên hệ chính.
- **Tab Phân công:** lịch sử **Account Owner** (người phụ trách tài khoản) — nút **Gán Account Owner / Điều chuyển Account Owner** (chọn nhân viên kinh doanh đủ điều kiện), **Kết thúc phân công** kèm lý do. Việc quản lý người phụ trách chỉ thực hiện tại tab này để bảo toàn lịch sử điều chuyển.
- **Phạm vi hiển thị:** nhân viên kinh doanh chỉ thấy công ty mình phụ trách, có lead/cơ hội được giao.

### 6.2. Leads

**Hồ sơ khách tiềm năng — nơi nhân viên kinh doanh xử lý từng đầu mối.**

- **Nguồn Lead:** chủ yếu từ lượt gửi biểu mẫu trang đích (có Mã Lead, Dịch vụ quan tâm, giá trị ước tính, ảnh chụp câu trả lời biểu mẫu); hoặc nhập thủ công, nhập dữ liệu, giới thiệu.
- **Các tab xử lý:** Tất cả / **Chưa liên hệ** (Mới + Đã phân công) / **Đang xử lý** (Đang liên hệ + Cần theo dõi) / **Đủ điều kiện** / **Không đủ điều kiện** / **Đã chuyển thành khách hàng**.
- **Phân phối tự động:** nút **Tự động phân phối** — hệ thống giao các Lead mới, đủ dữ liệu cho nhân viên kinh doanh theo chiến lược: **Ít việc nhất** (mặc định) / **Xoay vòng** / **Theo trọng số**; có thể giới hạn danh sách nhân viên hoặc theo trang đích. Khách hàng của công ty có Account Owner chỉ giao đúng cho chủ đó.
- **Phân công thủ công:** **Phân công Lead** (chọn nhân viên; mặc định Account Owner của công ty), **Điều chuyển Lead** (kèm lý do, tùy chọn chuyển luôn Account Owner).

**Màn hình Xem Lead (trang xử lý chính)** với các nút theo tiến trình:
1. **Bắt đầu liên hệ** (Đã phân công → Đang liên hệ);
2. **Ghi nhận tương tác** (loại: Cuộc gọi/Email/Cuộc họp/Tin nhắn/Ghi chú/Khác + nội dung + kết quả + lần chăm sóc tiếp theo);
3. **Đặt lịch theo dõi**;
4. **Đánh dấu đủ điều kiện** — thu thập: Dịch vụ quan tâm, Giá trị ước tính, **Tình trạng ngân sách** (Đã xác nhận phù hợp / chưa phù hợp / Chưa xác định), **Ngân sách dự kiến**, **Thời gian dự kiến mua** (Trong 7 ngày / 30 ngày / 3 tháng / Trên 3 tháng / Chưa xác định), **Vai trò người liên hệ**, **Ưu tiên** (Thấp / Bình thường / Cao / VIP), **Điểm** 0–100, Ghi chú đánh giá;
5. **Tạo cơ hội kinh doanh** (chuyển sang bộ phận Kinh doanh — chỉ khi Lead đủ điều kiện và chưa có cơ hội; nhân viên kinh doanh thực hiện);
6. Hoặc **Đánh dấu không đủ điều kiện** (kèm kết quả/lý do) / **Trùng lặp** / **Spam** / **Lưu trữ**.

**Hai tab phụ trợ:** **Hoạt động trước bán** (lịch sử cuộc gọi/email/gặp mặt…), **Ghi chú đánh giá** (tự động ghi lại quá trình đánh giá).

### 6.3. Đối chiếu doanh nghiệp

> Màn hình dành cho Quản lý Kinh doanh / Admin (luồng V2).

Khi một liên hệ doanh nghiệp trùng tên/MST/domain/SĐT với công ty đã có, hệ thống không tự gộp mà tạo **đề xuất ghép** kèm **Độ tin cậy (%)** và tiêu chí đối chiếu. Người quản lý phê duyệt: **Chấp nhận ghép** (nối liên hệ vào công ty đã có) hoặc **Từ chối** (giữ công ty mới).

### 6.4. Quy trình đánh giá liên hệ (luồng V1, Kanban)

> Xuất hiện khi hệ thống đang chạy luồng V1 thay cho bộ Leads/Cơ hội.

- **Bảng Kanban 4 cột:** **Chưa liên hệ** (Mới + Đã phân công) → **Đang xử lý** (Đang liên hệ + Cần theo dõi) → **Đủ điều kiện** → **Đã chuyển thành khách hàng**.
- Thao tác theo hàng: **Bắt đầu liên hệ**, **Xử lý liên hệ** (cập nhật trạng thái + kết quả + lần chăm sóc tiếp theo), **Chuyển thành khách hàng** (chỉ khi Đủ điều kiện và kết quả "Đã mua hàng").
- Kết quả đánh giá: Đã xác nhận nhu cầu / **Đã mua hàng** / Không có nhu cầu / Không liên hệ được / Thông tin không hợp lệ.
- Sơ đồ chuyển trạng thái (hệ thống chặn chuyển lệch): Mới → Đã phân công → Đang liên hệ → Cần theo dõi → Đủ điều kiện → Đã chuyển thành khách hàng; các nhánh Không đủ điều kiện / Trùng / Spam / Lưu trữ.

---

## 7. Module Kinh doanh

> Menu (nhóm **Kinh doanh**): **Cơ hội kinh doanh → Báo giá → Sản phẩm → Phê duyệt báo giá → Bảng giá → Dịch vụ → Gói dịch vụ**.

### 7.1. Cơ hội kinh doanh

**Cơ hội bán hàng được tạo từ Lead đủ điều kiện.** (Luồng V2; cơ hội không tạo trực tiếp.)

- Nút **Tạo cơ hội** trên trang danh sách: chọn **Lead đủ điều kiện chưa có cơ hội**, nhân viên phụ trách bán hàng, Tiêu đề, Dịch vụ quan tâm, **Giá trị ước tính (VND)**, **Xác suất (%)** (tự cập nhật theo giai đoạn), **Ngày dự kiến chốt**.
- **Vòng đời cơ hội (các giai đoạn):** Khám phá nhu cầu → Đủ điều kiện → Đề xuất giải pháp → Đàm phán → **Thành công** / **Thất bại** / Hủy bỏ. Xác suất mặc định theo giai đoạn: 20% → 50% → 70% → 85%.
- Thao tác: **Đổi giai đoạn**, **Tạo báo giá** (chỉ ở các giai đoạn Đủ điều kiện/Đề xuất/Đàm phán), **Ghi nhận hoạt động**, **Thêm liên hệ**, **Chuyển người phụ trách** (Quản lý kinh doanh), **Đánh dấu thất bại** (bắt buộc nhập Lý do thất bại).
- **Quy tắc quan trọng:** giai đoạn **Thành công không đánh dấu tay được** — chỉ tự động đạt khi báo giá được **thanh toán và xác minh** (mục 8.2).

### 7.2. Báo giá

**Chứng từ chào giá — từ soạn thảo đến khách xác nhận và thanh toán.**

**Soạn báo giá:**
1. **Chi tiết báo giá:** chọn **Cơ hội kinh doanh** (tự điền Tiêu đề + Người nhận) hoặc (luồng V1) chọn Khách hàng; chọn **Bảng giá** và **Tài khoản ngân hàng** nhận tiền (bắt buộc); ngày báo giá và **Hiệu lực đến** (mặc định +30 ngày).
2. **Chi tiết dịch vụ:** thêm từng dòng — chọn **Sản phẩm/Gói dịch vụ trong Bảng giá** (đơn giá khóa theo bảng giá), Số lượng (giới hạn theo mặt hàng), **Chiết khấu** (loại: Cố định/% + giới hạn theo mặt hàng), VAT (khóa theo mặt hàng). Hệ thống **tự tính lại** thành tiền, chiết khấu, thuế, **Tổng thanh toán**. Nếu dịch vụ quan tâm của cơ hội khớp mặt hàng, dòng đó được chọn sẵn làm gợi ý.

**Vòng đời báo giá:**
1. **Gửi duyệt** (từ Nháp): hệ thống kiểm tra đầy đủ (≥1 dòng, tổng > 0, tài khoản ngân hàng hoạt động, có email người nhận) → đối chiếu **Chính sách duyệt** (mục 3.7): không cần duyệt thì **tự duyệt theo chính sách**; cần duyệt thì chuyển sang **Chờ duyệt** và thông báo Quản lý kinh doanh.
2. **Duyệt / Từ chối duyệt** (tại **Phê duyệt báo giá** hoặc ngay trên báo giá): người lập **không được tự duyệt** báo giá của mình; chỉ Quản lý bộ phận Kinh doanh duyệt. Từ chối phải nhập lý do và báo giá về lại **Nháp**.
3. **Gửi** (chỉ khi Đã duyệt): chọn **Người ký** (từ liên hệ cơ hội), mẫu email loại "Báo giá", chủ đề/nội dung → gửi email kèm **Liên kết công khai** (có token). Từ đây PDF bị "đóng băng" (không sửa được).
4. Khách mở liên kết → **Xác nhận trực tiếp qua liên kết** hoặc **OTP email** (theo cài đặt công ty) → trạng thái hàng loạt: **Đã gửi → Đã xem → Đã chấp nhận** / Từ chối / **Yêu cầu chỉnh sửa** / Hết hạn (quá hạn hiệu lực).
5. Nếu khách **Yêu cầu chỉnh sửa**: tạo **Phiên bản chỉnh sửa** (báo giá mới, bản cũ đánh dấu "Đã được thay thế") → duyệt → gửi lại.
6. **Ghi nhận phản hồi khách hàng** (khi xác nhận qua điện thoại/mạng xã hội…): người trả lời phải là liên hệ đã biết (contact của cơ hội), bắt buộc ghi chú nội dung trao đổi + xác nhận "đã liên hệ trực tiếp".
7. **Hủy** báo giá ở trạng thái chưa kết thúc (Quản lý/Admin); **Xóa hàng loạt** chỉ Admin.

**Màn hình Phê duyệt báo giá:** danh sách các yêu cầu duyệt đang chờ với nút **Duyệt / Từ chối từ danh sách duyệt**; tab **Lịch sử duyệt** trên màn báo giá ghi lại từng bước (ai duyệt, lúc nào, theo chính sách nào).

### 7.3. Danh mục sản phẩm & dịch vụ

- **Dịch vụ** (cấp cao nhất): Mã, Tên, trạng thái (Hoạt động/Không hoạt động/Đã lưu trữ), mô tả, phạm vi/điều khoản mặc định.
- **Sản phẩm:** thuộc dịch vụ, có đơn vị tính và số lượng mặc định.
- **Gói dịch vụ:** nhóm các sản phẩm thành gói (pivot kèm số lượng); có **Nhóm đối tượng** (Cá nhân / Doanh nghiệp / Cả hai) và **Chu kỳ thanh toán** (ngày/tháng/quý/năm/một lần/tùy chỉnh).
- **Bảng giá:** chứa **mặt hàng** (Sản phẩm hoặc Gói dịch vụ) với **Đơn giá, Thuế VAT, Chiết khấu mặc định + giới hạn chiết khấu, Số lượng tối thiểu/tối đa**; bảng giá có Nhóm đối tượng áp dụng và **Quyền truy cập** (theo chi nhánh/phòng ban/vai trò/nhân viên), trạng thái: Nháp / Hoạt động / Hết hạn / Đã lưu trữ. **Mọi dòng báo giá phải lấy từ một Bảng giá.**

---

## 8. Module Tài chính

> Menu (nhóm **Tài chính**): **Theo dõi thanh toán → Lịch sử thanh toán → Tài khoản ngân hàng**.

### 8.1. Theo dõi thanh toán

**Màn hình làm việc của Tài chính — chỉ các báo giá khách đã chấp nhận, chưa thanh toán.**

Quy trình "từ khách đồng ý đến tiền về":

1. Khách mở liên kết công khai của báo giá (chỉ khi Đã chấp nhận, chưa thanh toán) → bấm **Thông báo chuyển khoản**: nhập Tên/Tên email (phải là người ký được ủy quyền), **Số tiền khai báo phải bằng đúng 100% Tổng thanh toán** (±0,5₫), Số tham chiếu giao dịch, Ghi chú và **Chứng từ** (hình/PDF; bắt buộc nếu công ty đã bật chính sách). Chỉ một thông báo đang chờ tại một thời điểm.
2. Trạng thái thanh toán → **Chờ xác minh**; Tài chính được thông báo.
3. Nhân viên Tài chính vào **Theo dõi thanh toán**: mở **Xem chứng từ** → nếu khớp: **Đã thanh toán** (bắt buộc ghi chú; chỉ bộ phận Tài chính được xác minh) → hoặc **Đối soát không khớp** (kèm lý do; thông báo bị từ chối, báo giá về lại Chưa thanh toán).

**Việc xác minh kích hoạt chuỗi tự động (quan trọng):**
- Báo giá → **Đã thanh toán**, giai đoạn cơ hội → **Thành công** (tự động);
- Tạo bản ghi **Thanh toán** trong **Lịch sử thanh toán** (mã PAY…, doanh thu trước VAT, nguồn UTM, chiến dịch marketing, người xác minh);
- **Tự động tạo Khách hàng** (ở Chăm sóc khách hàng) từ công ty/liên hệ chính của cơ hội, ghi nhận doanh thu và **chuyển giao cho nhân viên CSKH** (ưu tiên: Account Owner công ty → nhân viên kinh doanh → nhân viên CSKH nhẹ việc nhất);
- Nếu công ty bật hóa đơn điện tử: phát hành hóa đơn; gửi email xác nhận cho khách và thông báo nhân viên kinh doanh quản lý.

### 8.2. Lịch sử thanh toán

**Sổ cái chỉ đọc:** mọi khoản thanh toán đã xác minh (Mã thanh toán, Ngày, Khách hàng, Báo giá, Thực thu gồm VAT, Doanh thu trước VAT, Thuế VAT, Nguồn UTM, Chiến dịch marketing, Nhân viên kinh doanh, Nhân viên tài chính xác minh, Trạng thái). Thao tác: **Xem biên lai** (PDF), **Mở báo giá** gốc.

### 8.3. Tài khoản ngân hàng

**Danh sách tài khoản nhận chuyển khoản — hiển thị trên báo giá (đóng băng khi gửi).**

- Tạo: chọn **Ngân hàng** (mã tự điền tên + SWIFT) → số tài khoản (6–19 số) kèm nút **tra cứu tên chủ tài khoản** qua VietQR → Tên tài khoản, trạng thái, đánh dấu **Tài khoản mặc định** (chỉ một tài khoản mặc định).
- Báo giá bắt buộc chọn tài khoản **đang hoạt động**; trước khi gửi duyệt, tài khoản phải còn hoạt động.

---

## 9. Module Chăm sóc khách hàng

> Menu (nhóm **Chăm sóc khách hàng**): **Khách hàng → Chăm sóc khách hàng → Phiên phân phối**.

### 9.1. Khách hàng

**Hồ sơ khách hàng sau bán hàng.** Trong luồng V2, khách hàng **được tạo tự động khi thanh toán được xác minh** (nếu khách cũ → cập nhật doanh thu). Vẫn có thể tạo thủ công.

- **Thông tin:** Mã KH (tự sinh), **Loại khách hàng** (Cá nhân/Doanh nghiệp), Tên, Email, SĐT, công ty/MST.
- **Thiết lập:** **Trạng thái** (Tiềm năng / Hoạt động / Không hoạt động / Đã rời bỏ / Bị chặn / Đã lưu trữ), **Giai đoạn vòng đời** (Mới / Đang tiếp nhận / Đang nuôi dưỡng / Đang mua hàng / Đã giữ chân / Có nguy cơ rời bỏ / Đã rời bỏ), **Trạng thái đồng ý nhận email** (Chờ xác nhận / **Đã đăng ký** / Đã hủy đăng ký / Email bị trả lại / Đã khiếu nại / Không được liên hệ — chỉ khách "Đã đăng ký" + trạng thái Tiềm năng/Hoạt động mới nhận được email tiếp thị), **Ưu tiên** (Thấp/Bình thường/Cao/VIP), **Thẻ**.
- **Phân công nhân viên:** **Gán nhân viên** (phụ trách chính), **Nhân viên hỗ trợ**, **Chuyển giao** (giữ lịch sử), **Gỡ phân công** (về nhóm chưa phân công; hồ sơ giữ nguyên).
- **Tab Phân công:** lịch sử đầy đủ (Phụ trách chính / Hỗ trợ, Đang hiệu lực / Đã kết thúc / Đã hủy, lý do, thời gian).
- **Tab Lịch sử chăm sóc:** các lần tương tác (Cuộc gọi/Email/Tin nhắn/…, đã lên lịch/hoàn thành/hủy/khách vắng/dời lịch, lần chăm sóc tiếp theo).
- **Tab Thanh toán:** lịch sử thanh toán của khách (thực thu, doanh thu trước VAT, nguồn UTM) + nút **Biên lai**.
- **Phạm vi hiển thị:** nhân viên CSKH chỉ thấy khách mình có phân công đang hiệu lực; quản lý/admin thấy tất cả.

### 9.2. Chăm sóc khách hàng (Workspace)

**Không gian làm việc cá nhân của nhân viên CSKH** — danh sách khách đang phụ trách với các tab:

- **Tổng quan:** thống kê chăm sóc (cuộc gọi, email đã gửi, tin nhắn, báo giá; email gần nhất + trạng thái mở), hoạt động gần đây.
- **Gọi & Nhắn:** ghi nhận cuộc gọi/tin nhắn (loại, trạng thái, nội dung, kết quả, lần chăm sóc tiếp theo) + lịch sử.
- **Email:** soạn và gửi email cho khách (To/Cc/Bcc, chủ đề, nội dung, đính kèm, chọn mẫu) — gửi qua **tài khoản SMTP của phòng ban**, theo dõi mở/nhấp, lưu vào lịch sử.
- **Báo giá:** danh sách báo giá của khách + nút nhanh **Tạo báo giá**.
- **Dòng thời gian:** hợp nhất tất cả (tương tác, email, phân công, báo giá) theo thứ tự thời gian.
- Cột **Lần chăm sóc tiếp theo** hiện đỏ kèm "Quá hạn" khi quá hạn.

### 9.3. Phiên phân phối

**Bản ghi của mỗi đợt phân phối khách hàng tự động** cho nhân viên CSKH (chỉ đọc; tạo bởi dịch vụ phân phối).

- **Loại phiên:** Khởi tạo / Khách hàng mới / **Nhân viên vắng mặt** / Nhân viên quay lại / Cân bằng lại / Thủ công.
- **Chiến lược:** Xoay vòng / Ít việc nhất / Theo trọng số.
- Mỗi phiên hiển thị: Mã phiên (DIST-…), tổng khách, số khách đã phân công, Người tạo, Nhân viên nguồn (khách từ đâu chuyển qua), kết quả từng khách (Thành công / Bỏ qua + lý do).
- **Bỏ qua khi:** không có nhân viên đủ điều kiện (quá sức chứa, nghỉ, không bật "có thể nhận khách").

---

## 10. Module Hệ thống

### 10.1. Nhật ký hoạt động

- Xem toàn bộ hoạt động: **Thời gian / Người thực hiện (hoặc "Hệ thống") / Phân hệ** (Tài khoản, Email, Marketing, CRM, Kinh doanh, Tài chính, Chăm sóc khách hàng, Hệ thống) / **Hoạt động / Nội dung**.
- Bộ lọc: Người thực hiện, Phân hệ, Khoảng thời gian.
- **Xem chi tiết:** modal hiển thị dữ liệu **Trước/Sau** của bản ghi bị thay đổi — dùng để truy vết ai đã sửa gì.

### 10.2. Ma trận vai trò hệ thống

Bảng giải thích 5 vai trò nền tảng (mã kỹ thuật, màu, quyền được gán sẵn, có cần liên kết nhân viên không, tóm tắt quyền). Truy cập từ nút "Xem ma trận vai trò hệ thống" trên trang Vai trò & quyền.

---

## 11. Luồng nghiệp vụ tổng thể

### 11.1. Quy trình marketing → bán hàng → hậu mãi (V2)

```
[Marketing]                          [Kinh doanh]                  [Tài chính]              [CSKH]
─────────────                        ──────────────                ───────────              ─────
1. Khai báo Dịch vụ / Sản phẩm /
   Gói dịch vụ / Bảng giá
2. Tạo Chiến dịch quảng cáo
   + Trang đích (+2 biểu mẫu)
3. Xuất bản trang, tạo URL UTM
   → quảng cáo trên các kênh
4. Khách gửi biểu mẫu
5. Hệ thống: tạo Liên hệ/Công ty,
   gắn Thẻ/Danh sách, tạo Lead
6. Phân phối Lead cho nhân viên
   Kinh doanh (tự động/thủ công)
7. Xử lý Lead → Đủ điều kiện
8. Tạo Cơ hội kinh doanh
   → Báo giá → Duyệt → Gửi
9. Khách xác nhận qua liên kết/
   OTP → Đã chấp nhận
10. Khách thông báo chuyển khoản
11. Tài chính xác minh
12. Tự động: Cơ hội Thành công,
    tạo Thanh toán, Biên lai,
    TẠO KHÁCH HÀNG, chuyển CSKH
13. CSKH gán/chăm sóc khách,
    phân phối khi vắng mặt
14. Nếu có chiến dịch email tiếp theo:
    đối tượng = khách đã đăng ký /
    danh sách / phân khúc / liên hệ đủ điều kiện
```

### 11.2. Quy trình email tiếp thị

1. Tạo **Mẫu email** (với chủ đề, nội dung, placeholder) → đặt Hoạt động.
2. Tạo **Tài khoản gửi** (SMTP) → **Gửi thử**.
3. Chọn đối tượng: **Danh sách liên hệ / Phân khúc / Thẻ / đủ điều kiện từ trang đích / tất cả khách đã đăng ký**.
4. Tạo **Chiến dịch email** → Lên lịch hoặc gửi ngay.
5. Theo dõi: mở/nhấp/bounce/hủy đăng ký; báo cáo doanh thu theo chiến dịch.
6. Ai **hủy đăng ký / trả lại email / khiếu nại** → tự động vào **Mục loại trừ**, không gửi lại.

### 11.3. Vòng đời "khách hàng có thể nhận email"

Chỉ khách hàng có **Email + Trạng thái đồng ý = Đã đăng ký + Trạng thái = Tiềm năng/Hoạt động** mới được đưa vào đối tượng gửi. Mọi quy tắc khác (bị chặn, đã hủy, trả lại…) đều bị chặn ở mức gửi.

---

> **Ghi chú cuối:** các màn hình có thể khác nhau đôi chút tùy cấu hình hệ thống (luồng V1/V2, bật/tắt hóa đơn điện tử, chính sách duyệt…) và quyền của tài khoản đăng nhập. Khi nghi ngờ, hãy kiểm tra **Nhật ký hoạt động** để truy vết, và hỏi Quản trị hệ thống về quyền được cấp.