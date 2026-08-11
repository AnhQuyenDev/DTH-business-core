# DTH Business Core V1 - Release Hardening Audit

## 1. Phạm vi kiểm tra

- Nguồn kiểm tra: `marketing-email-laravel-v12.zip`
- SHA-256: `23c5bee58a75af5a6d35cf42e6b98ec4df79cd111b6f985e090b91fae5fd8b2c`
- Framework phát hiện trong lock file: Laravel 12.65.0, Filament 3.3.54, Livewire 3.8.2.
- Bộ test có sẵn: 80 file test sau hardening, hơn 400 test declarations/annotations.
- Kiểm tra tĩnh đã chạy: PHP syntax toàn bộ `app/bootstrap/config/database/routes/tests`; route discovery; kiểm tra literal translation keys; kiểm tra trực tiếp các enum/ngôn ngữ liên quan ảnh lỗi.

## 2. Kết luận hiện tại

Bản ZIP gốc **chưa nên đưa thẳng lên production**. Có các blocker về đóng gói release, an toàn dữ liệu test, i18n và một số action UI. Patch hardening đi kèm tài liệu này đã sửa các lỗi có thể xác định chắc chắn từ source và ảnh người dùng cung cấp. Vẫn cần chạy full automated test trên máy có đủ PHP extensions và chạy UAT trình duyệt trên staging trước khi gắn nhãn Release Candidate/Production.

## 3. Các lỗi đã xác định và đã sửa trong hardening patch

### P0 - i18n bị trộn Việt/Anh

**Triệu chứng:** `The mã phòng ban has already been taken.`

**Nguyên nhân:** ứng dụng có `vi.json`/`en.json` nhưng thiếu `lang/vi/validation.php` và `lang/en/validation.php`. Khi rule chuẩn Laravel phát sinh lỗi, framework dùng message tiếng Anh.

**Sửa:** thêm bộ validation message cho cả `vi` và `en`, đồng thời đưa 49 validation message nghiệp vụ hiện có vào group `validation` để tránh key JSON cũ bị che khuất.

### P0 - đổi ngôn ngữ chỉ refresh một Livewire component

**Triệu chứng:** sidebar/nav/modal/table có thể tồn tại đồng thời hai ngôn ngữ sau khi switch.

**Nguyên nhân:** JavaScript custom trong `AdminPanelProvider` tìm phần tử `[wire:id]` đầu tiên và gọi `$refresh()`, trong khi locale ảnh hưởng toàn bộ document.

**Sửa:** sau khi server ghi locale thành công, reload toàn bộ document. Điều này làm navigation, topbar, page, modal và validation cùng nhận một locale.

### P1 - `Quản lý nhân viên` và `Chỉnh sửa` trùng chức năng; modal Edit rỗng

**Nguyên nhân:** `DepartmentStaffTable` có một action URL sang trang edit đầy đủ **và** thêm `EditAction::make()` nhưng standalone EditAction không được khai báo form.

**Sửa:** giữ một action `Chỉnh sửa` duy nhất, điều hướng sang `StaffResource` edit đầy đủ; loại bỏ modal edit rỗng.

### P1 - trạng thái nhân viên hiện raw `active`

**Nguyên nhân:** column có enum cast để lấy màu nhưng không format label.

**Sửa:** `formatStateUsing()` gọi `StaffEmploymentStatus::label()`.

### P1 - modal `Tạo position`

**Nguyên nhân:** standalone Filament action tự suy model label `position` cho modal heading.

**Sửa:** đặt explicit translated modal heading/submit label cho Create/Edit/Delete Position; Create Staff cũng được đặt heading rõ ràng.

### P1 - một số enum còn hard-code tiếng Việt

Đã chuyển `LeadIntakeStatus`, `CompanyContactDecisionRole`, `PaymentNoticeStatus` sang translation keys và bổ sung bản dịch `vi/en`.

### P1 - lựa chọn màu phòng ban quá ít

**Trước:** 6 semantic colors.

**Sau:** giữ 6 màu tương thích cũ và bổ sung Orange, Lime, Emerald, Teal, Cyan, Sky, Blue, Indigo, Violet, Purple, Fuchsia, Pink, Rose. Các màu được đăng ký với Filament nên badge vẫn dùng palette chuẩn, thay vì lưu hex tùy ý gây khó kiểm soát tương phản.

### P0 - lệnh reset V1 có thể chạy trên production

**Nguyên nhân:** `v1:reset-business-data --force` chỉ kiểm tra `--force`, chưa chặn `APP_ENV=production`.

**Sửa:** command từ chối tuyệt đối trên production trước khi chạm DB/files.

### P0 - seeder demo/test có credential cố định

- `DatabaseSeeder` tạo `test@example.com / password`.
- `V1AcceptanceTestSeeder` tạo các tài khoản UAT với mật khẩu chung.

**Sửa:** cả hai bị chặn trên production. V1 seeder ném exception nếu bị gọi trực tiếp ở production.

## 4. Hiệu suất và `php artisan serve`

Dòng `Indexing project for definitions and static references...` trong ảnh không đến từ Laravel request lifecycle. Đây là hoạt động index của PHP language server/extension trong editor để tìm definition/reference. Vì vậy:

- Restart `php artisan serve` không phải nguyên nhân Laravel phải "quét lại toàn bộ DB/source".
- Production không chạy VS Code language-server nên không có bước editor indexing này.
- Không cần restart `artisan serve` sau mỗi thao tác UI. Server có thể để chạy trong suốt phiên test.
- Nếu VS Code liên tục re-index, cần kiểm tra extension PHP đang dùng và tránh mở đồng thời nhiều PHP language servers; loại các thư mục build/log/backup/node_modules khỏi workspace/file-watcher theo cấu hình của extension.

## 5. Cache hiện tại của hệ thống

Source hiện tại cấu hình local:

- `CACHE_STORE=database`
- `SESSION_DRIVER=database`
- `QUEUE_CONNECTION=sync`

Ứng dụng có dùng Cache facade cho một số nghiệp vụ (badge palette, tax verification, QR payment, OTP/public quotation, login de-duplication, lock submit Landing Page), nhưng **không có cơ chế tự động cache toàn bộ Eloquent query**. Database cache chỉ là backend để lưu những entry mà code chủ động cache.

Production nên:

1. bật Laravel/Filament production caches qua `php artisan system:production-warmup` (project đã có command này);
2. không dùng `QUEUE_CONNECTION=sync` cho workload email/import dài; tối thiểu dùng database queue + worker, hoặc Redis nếu hạ tầng có;
3. cân nhắc Redis cho cache/session/queue khi số user/job tăng;
4. bật PHP OPcache ở web server/PHP-FPM;
5. dùng Nginx/Apache/PHP-FPM hoặc nền tảng Laravel tương đương, không xem `php artisan serve` là cấu hình production.

## 6. I18n - kiến trúc đề xuất

Không nên kỳ vọng một package tự dịch đúng toàn bộ thuật ngữ nghiệp vụ DTH. Tách thành hai lớp:

- **Framework/common translations:** dùng Laravel Lang (`laravel-lang/lang` + `laravel-lang/publisher`) để cập nhật message chuẩn Laravel khi framework nâng version.
- **Language switcher của Filament:** có thể thay custom switcher bằng package tương thích Filament 3 sau khi cài/test dependency trên branch riêng.
- **Business terminology:** vẫn do dự án sở hữu và review bởi BA. Nên dần tách JSON lớn thành file theo domain (`field.php`, `action.php`, `enum.php`, `marketing.php`, `sales.php`, `finance.php`, `customer-care.php`...) cho từng locale.

Có thêm ngôn ngữ thì thư mục `lang/<locale>` tăng là bình thường; vấn đề cần tránh là một file JSON khổng lồ và hard-code string trong source.

## 7. Release packaging blocker trong ZIP gốc

ZIP gốc chứa các thứ không được đưa vào gói production/source chia sẻ:

- `.env` có nhiều giá trị đã cấu hình (không ghi giá trị trong báo cáo để tránh lộ secret);
- `storage/logs/*.log`;
- `database/database.sqlite` và file DB local `marketing_email`;
- `.phpunit.result.cache`;
- `node_modules` từ Windows/cross-platform;
- file cấu hình IDE/AI dev như `.mcp.json`, `opencode.json` nếu không phải thành phần deploy.

`vendor` chỉ nên đóng gói nếu đó là artifact được build trên môi trường tương thích target. Quy trình sạch hơn là CI/server chạy `composer install --no-dev --optimize-autoloader`; frontend chạy `npm ci && npm run build`; không deploy `node_modules`.

Patch cung cấp thêm `.env.production.example`, `scripts/validate-release.ps1`, `scripts/build-release.ps1` để giảm rủi ro đóng gói nhầm.

## 8. Kết quả test đã chạy trong môi trường review

### PASS

- PHP syntax scan: **785 PHP files, 0 syntax errors** tại thời điểm chạy hardening.
- Laravel route discovery boot được; `route:list --except-vendor` sinh khoảng **150 dòng**.
- Literal translation scan trên `app/Filament`, `app/Livewire`, `app/Enums`, `resources/views/filament`: **1,564 literal translation keys, 0 key thiếu** cho `vi/en` sau patch.
- Smoke trực tiếp translator: validation unique, staff status, lead status, contact decision role, payment notice status đổi đúng giữa `vi` và `en`.

### BLOCKED bởi runner hiện tại, không phải kết luận FAIL của project

Không thể chạy PHPUnit/Pint đầy đủ trong container review vì PHP CLI của runner thiếu các extension bắt buộc: `mbstring`, `dom/xml`, `xmlwriter`; test suite của dự án dùng SQLite in-memory nhưng runner cũng thiếu `pdo_sqlite/sqlite3`. Vì vậy **chưa được phép tuyên bố full suite PASS**.

`npm run build` cũng không phải phép thử hợp lệ từ `node_modules` trong ZIP vì dependency được đóng từ môi trường khác và thiếu Rollup binary cho Linux. Trước final gate phải xóa/reinstall dependency bằng `npm ci` trên đúng OS/CI.

## 9. Final release gate bắt buộc trên máy dev/staging

1. Backup DB staging.
2. Bảo đảm PHP có `mbstring`, `dom`, `xml`, `xmlwriter`, `pdo_sqlite` cho test và `pdo_mysql` cho MySQL runtime.
3. Không dùng `node_modules` được gửi trong ZIP; chạy `npm ci`.
4. Chạy `powershell -ExecutionPolicy Bypass -File scripts/validate-release.ps1`.
5. Chỉ khi toàn bộ automated tests PASS mới chạy UAT trình duyệt bằng tài khoản Super Admin/Admin/Founder/Commercial/Support/Finance.
6. UAT tối thiểu phải đi đủ: Marketing -> Submission -> Lead -> Qualification -> Opportunity -> Quotation -> Approval -> Public confirmation -> Payment notice -> Finance reconciliation -> Paid -> Customer -> CSKH/Ticket.
7. Chạy riêng permission/direct-URL negative tests cho từng vai trò.
8. Trước deploy production: kiểm tra `.env` production, `APP_DEBUG=false`, mail/queue worker, backup, storage permissions, cron/scheduler, production warmup.

## 10. Trạng thái khuyến nghị

**Hiện tại: RC hardening / chưa production-ready cho đến khi full suite và browser UAT chạy PASS trên môi trường staging đầy đủ extension.**
