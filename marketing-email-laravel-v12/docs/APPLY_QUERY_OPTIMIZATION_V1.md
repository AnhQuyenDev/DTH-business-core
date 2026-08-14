# Hướng dẫn áp dụng Query Optimization V1

## 1. Sao lưu

Sao lưu source và database đang chạy trước khi thay file.

```bat
cd C:\laragon\www\dth-business-core
mysqldump -u root marketing_email > backup_before_query_optimization.sql
```

Nếu MySQL có mật khẩu, thêm `-p`.

## 2. Chép code

Có hai cách:

- dùng bản full source đã tối ưu; hoặc
- chép đè đúng cấu trúc từ ZIP patch vào thư mục `marketing-email-laravel-v12`/project Laravel đang chạy.

Không chép `.env` từ gói. Giữ `.env` hiện tại của máy.

## 3. Cấu hình local Laragon

Thêm vào `.env`:

```dotenv
APP_DEBUG=false
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
DASHBOARD_CACHE_SECONDS=45
PERFORMANCE_TRACE_ENABLED=false
PERFORMANCE_SLOW_REQUEST_MS=750
```

Nếu đang chỉnh sửa code liên tục, có thể để `APP_ENV=local`; `APP_DEBUG=false` vẫn giảm toolbar/error rendering và log chi tiết không cần thiết.

## 4. Chạy migration và cache

```bat
cd C:\laragon\www\dth-business-core

php artisan optimize:clear
composer dump-autoload -o
php artisan migrate
php artisan test --filter=DashboardQueryBudgetTest
php artisan test
php artisan system:production-warmup
```

Nếu lệnh test riêng không chạy do cách đặt test suite, dùng:

```bat
php artisan test tests/Feature/Performance/DashboardQueryBudgetTest.php
```

Sau đó Laragon `Stop All` → `Start All`.

## 5. OPcache trên Laragon

Kiểm tra:

```bat
php -r "var_dump(opcache_get_status(false) !== false);"
```

Nếu kết quả là `bool(false)`, bật OPcache trong menu PHP Extensions của Laragon và khởi động lại Apache. Cấu hình tham khảo:

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=32
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
realpath_cache_size=4096K
realpath_cache_ttl=600
```

## 6. Đo tự động các URL chậm

Tạm bật:

```dotenv
PERFORMANCE_TRACE_ENABLED=true
PERFORMANCE_SLOW_REQUEST_MS=750
```

Chạy `php artisan optimize:clear`, đăng nhập và thao tác bình thường trong vài phút. Đọc kết quả bằng PowerShell:

```powershell
Get-Content .\storage\logs\laravel.log | Select-String "admin.performance.slow_request"
```

Sau khi đo, tắt lại:

```dotenv
PERFORMANCE_TRACE_ENABLED=false
```

Rồi chạy:

```bat
php artisan system:production-warmup
```

## 7. Cấu hình server production

```dotenv
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
DASHBOARD_CACHE_SECONDS=45
PERFORMANCE_TRACE_ENABLED=false
```

Nếu có Redis ổn định, dùng Redis cho cache/session. Nếu chưa có, database cache/session vẫn hoạt động; không cần trì hoãn deploy chỉ để chờ Redis.

Trong quy trình deploy chạy:

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan system:production-warmup
```

## 8. Rollback

Nếu migration mới gây vấn đề:

```bat
php artisan migrate:rollback --step=1
php artisan optimize:clear
```

Sau đó khôi phục các file từ bản sao lưu source và chạy lại:

```bat
composer dump-autoload -o
php artisan optimize:clear
```

Migration chỉ thêm index, không biến đổi hoặc xóa dữ liệu nghiệp vụ.
