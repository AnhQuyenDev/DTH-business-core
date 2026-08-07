# Hoàn thiện luồng tiếp nhận Lead - Cài đặt và kiểm thử

## 1. Phạm vi đã triển khai

Bản sửa này hoàn thiện luồng:

`Landing Page -> Submission -> Contact/Company -> Lead -> Qualification -> Hàng chờ phân phối`

Các kiểm soát chính:

- `Submission.data` là dữ liệu gốc và không được sửa sau khi tiếp nhận.
- Trường dịch vụ dùng mapping `lead.service_interest`, không ghi vào Contact.
- Các câu trả lời riêng theo dịch vụ được snapshot vào `Lead.metadata.form_answers`.
- Select/radio/multi-select được kiểm tra giá trị ở backend.
- Number/date và validation rule của Form Template được kiểm tra ở backend.
- Payload ngoài Form Template không được lưu vào Submission.
- Chống nhấn gửi hai lần bằng UUID token, cache lock, fingerprint 60 giây và unique index.
- Lead có `metadata.intake_ready` và `metadata.intake_issues`.
- Lead doanh nghiệp chờ đối chiếu Company không được tự động phân phối.
- Khi Company được xác nhận/gắn lại, readiness của Lead được tính lại tự động.
- Trang chi tiết Lead hiển thị snapshot nhu cầu tại thời điểm khách gửi form.
- Có command backfill Lead cũ và bộ Feature Test mới.

## 2. Cài đặt nhanh bằng file overlay

Tại thư mục repository:

```bash
git switch feature/crm-company-lead-opportunity-flow
git pull --ff-only origin feature/crm-company-lead-opportunity-flow
git switch -c feature/lead-intake-hardening

cd marketing-email-laravel-v12
```

Sao lưu các thay đổi chưa commit nếu có:

```bash
git status
```

Giải nén `DTH-lead-intake-overlay.zip` trực tiếp vào thư mục
`marketing-email-laravel-v12`, cho phép ghi đè file.

Git Bash:

```bash
unzip -o /duong-dan/DTH-lead-intake-overlay.zip -d .
```

Kiểm tra file đã thay đổi:

```bash
git status --short
```

## 3. Chạy migration và cache

```bash
php artisan optimize:clear
php artisan migrate
```

Không chạy `migrate:fresh` vì lệnh đó xóa Campaign, Template, Landing Page,
User và dữ liệu cấu hình đang có.

## 4. Cấu hình Form Template trên UI

Thực hiện cho cả Form cá nhân và Form doanh nghiệp.

### 4.1. Dịch vụ quan tâm

| Thuộc tính | Giá trị |
|---|---|
| Nhãn trường | Dịch vụ quan tâm |
| Khóa trường | `service_interest` |
| Loại | Chọn |
| Bắt buộc | Có |
| Nơi lưu dữ liệu | Lead -> Dịch vụ quan tâm |

Options:

| Giá trị kỹ thuật | Nhãn hiển thị |
|---|---|
| `hosting_basic` | Web Hosting - Bình thường |
| `hosting_pro` | Web Hosting - Trung bình |
| `hosting_vip` | Web Hosting - Cao cấp |

Không dùng nhãn hiển thị làm giá trị kỹ thuật.

### 4.2. Các trường nhu cầu của Hosting

Các trường dưới đây để trống `Nơi lưu dữ liệu`. Khi đó dữ liệu vẫn được lưu ở
Submission và snapshot sang đúng Lead, nhưng không ghi đè hồ sơ Contact.

| Nhãn | Key | Loại | Bắt buộc | Validation | Options |
|---|---|---|---:|---|---|
| Số lượng website | `website_count` | Số | Có | `integer|min:1|max:1000` | - |
| Nhà cung cấp hiện tại | `current_provider` | Văn bản | Không | `max:150` | - |
| Cần chuyển dữ liệu | `migration_required` | Chọn | Có | - | `yes` = Có; `no` = Không; `unsure` = Chưa xác định |
| Ngày dự kiến triển khai | `expected_start_date` | Ngày | Có | `after_or_equal:today` | - |
| Ngân sách dự kiến | `expected_budget` | Số | Không | `numeric|min:0|max:1000000000` | - |
| Mô tả nhu cầu | `requirement_note` | Vùng văn bản | Không | `max:1000` | - |

Sau khi lưu, mở Preview của cả hai Form và mở Landing Page công khai để xác nhận
các trường mới xuất hiện.

## 5. Xử lý Lead cũ

Xem trước kết quả backfill:

```bash
php artisan crm:backfill-lead-intake --dry-run
```

Ghi dữ liệu:

```bash
php artisan crm:backfill-lead-intake
```

Lead cũ không có dịch vụ trong Submission sẽ được đánh dấu `Cần kiểm tra`,
không tự động phân phối. Đối với đợt test sạch đầu tiên, nên xóa dữ liệu nghiệp
vụ test cũ rồi gửi lại Form sau khi cấu hình hoàn tất.

## 6. Chạy test

```bash
php artisan test --filter=LeadIntakeHardeningTest
php artisan test --filter=LeadDistributionTest
php artisan test --filter=CompanyMatchCandidateWorkflowTest
php artisan test --filter=SubmissionCreatesLeadTest
```

Sau đó chạy toàn bộ:

```bash
php artisan test
```

## 7. Dữ liệu chuẩn dùng xuyên suốt các bước CRM/Sales

### Bộ A - Lead doanh nghiệp đầu tiên

| Trường | Giá trị |
|---|---|
| Chức vụ | Trưởng phòng Công nghệ thông tin |
| Tên doanh nghiệp | Công ty TNHH Giải pháp Số Minh Phát - TEST |
| Mã số thuế | `9999999999` |
| Người đại diện liên hệ | Nguyễn Minh Khoa |
| Số điện thoại công ty | `0900001001` |
| Email doanh nghiệp | `lead.hosting.b2b.001@example.com` |
| Dịch vụ quan tâm | `hosting_pro` - Web Hosting - Trung bình |
| Số lượng website | `5` |
| Nhà cung cấp hiện tại | AZDIGI |
| Cần chuyển dữ liệu | Có |
| Ngày dự kiến triển khai | `01/09/2026` hoặc ngày tương lai |
| Ngân sách dự kiến | `5000000` |
| Mô tả nhu cầu | Chuyển 5 website WordPress, yêu cầu hạn chế gián đoạn và hỗ trợ SSL. |

Kỳ vọng:

- 1 Submission, 1 Contact doanh nghiệp, 1 Company, 1 Lead, 1 Qualification.
- Lead có `service_interest = hosting_pro`.
- UI hiển thị nhãn `Web Hosting - Trung bình`.
- `intake_ready = true`.
- Chưa tạo Customer, Opportunity, Quotation hoặc Payment.

### Bộ B - Lead cá nhân

| Trường | Giá trị |
|---|---|
| Họ | Lê |
| Tên | Thu Hà |
| Giới tính | Nữ |
| Email | `lead.hosting.personal.001@example.com` |
| Số điện thoại | `0900001002` |
| Dịch vụ quan tâm | `hosting_basic` |
| Số lượng website | `1` |
| Nhà cung cấp hiện tại | Chưa có |
| Cần chuyển dữ liệu | Không |
| Ngày dự kiến triển khai | `05/09/2026` hoặc ngày tương lai |
| Ngân sách dự kiến | `1000000` |
| Mô tả nhu cầu | Website giới thiệu cá nhân, cần SSL và hướng dẫn quản trị. |

Kỳ vọng:

- Tạo Contact cá nhân và Lead.
- Không tạo Company.
- Lead vẫn `intake_ready = true`.

### Bộ C - Người liên hệ thứ hai của cùng Company

| Trường | Giá trị |
|---|---|
| Chức vụ | Kế toán trưởng |
| Tên doanh nghiệp | Công ty TNHH Giải pháp Số Minh Phát - TEST |
| Mã số thuế | `9999999999` |
| Người đại diện liên hệ | Trần Ngọc Mai |
| Số điện thoại công ty | `0900001003` |
| Email doanh nghiệp | `lead.hosting.b2b.002@example.com` |
| Dịch vụ quan tâm | `hosting_vip` |
| Số lượng website | `12` |
| Nhà cung cấp hiện tại | Vietnix |
| Cần chuyển dữ liệu | Chưa xác định, cần tư vấn |
| Ngày dự kiến triển khai | `15/09/2026` hoặc ngày tương lai |
| Ngân sách dự kiến | `15000000` |
| Mô tả nhu cầu | Cần đánh giá tài nguyên và lập kế hoạch chuyển 12 website theo từng đợt. |

Kỳ vọng:

- Tổng Company vẫn là 1.
- Company có 2 Contact.
- Tạo Lead mới, không ghi đè nhu cầu của Lead A.
- Khi phân phối, hai Lead cùng Company phải ưu tiên cùng Account Owner.

### Bộ D - Chống gửi trùng

Mở một Form mới, nhập dữ liệu Bộ A và nhấn nút gửi liên tiếp hai lần.

Kỳ vọng: chỉ có 1 Submission và 1 Lead.

### Bộ E - Validation backend

Dùng DevTools hoặc test tự động gửi `service_interest=hosting_free_forever`.

Kỳ vọng: request bị từ chối; không tạo Contact, Submission hoặc Lead dở dang.

### Bộ F - Cùng Contact, nhu cầu mới

Dùng lại email và số điện thoại Bộ B nhưng chọn `hosting_vip`, số website `3`,
ngân sách `8000000`.

Kỳ vọng:

- Vẫn chỉ 1 Contact cá nhân.
- Tạo Submission mới và Lead mới.
- Lead cũ giữ nguyên `hosting_basic`; Lead mới là `hosting_vip`.

## 8. Tiêu chí đạt trước khi test tự động phân phối

- Danh sách Lead hiển thị đúng dịch vụ.
- Chi tiết Lead hiển thị đầy đủ câu trả lời riêng theo dịch vụ.
- Submission gốc không chỉnh sửa được.
- Lead hợp lệ có biểu tượng `Đủ dữ liệu`.
- Lead thiếu dịch vụ hoặc đang chờ đối chiếu Company có trạng thái `Cần kiểm tra`.
- Nút tự động phân phối chỉ xử lý Lead có `intake_ready = true`.
- Sau khi chấp nhận đối chiếu Company, Lead được tính lại và chuyển sang đủ dữ liệu.

## 9. Commit đề xuất

```bash
git add app database/migrations lang tests docs
git commit -m "feat(crm): harden landing page lead intake flow"
```

## 10. Hoàn tác nhanh

Nếu chưa commit:

```bash
git restore app lang tests
git clean -fd app/Console/Commands app/Observers app/Services/Crm database/migrations tests/Feature/Marketing docs
```

An toàn hơn là xóa nhánh triển khai và quay về nhánh gốc:

```bash
git switch feature/crm-company-lead-opportunity-flow
git branch -D feature/lead-intake-hardening
```
