# DTH Business Core — Email Marketing & CRM

Hệ thống quản lý **Marketing – CRM – Bán hàng – Tài chính – Chăm sóc khách hàng** cho doanh nghiệp, xây dựng trên **Laravel 12 + Filament 3** (quản trị trực quan, không cần code).

> 📚 Tài liệu hướng dẫn sử dụng chi tiết: [`docs/USER_MANUAL.md`](docs/USER_MANUAL.md)

---

## 1. Tổng quan

| | |
|---|---|
| **Framework** | Laravel 12.x (PHP 8.2+) |
| **Quản trị (Admin Panel)** | Filament 3.3 — truy cập tại `/admin` |
| **Cơ sở dữ liệu** | MySQL (production) / SQLite (test tự động) |
| **Nền tảng** | Linux VPS/Shared hosting (có hỗ trợ PHP + Composer + cron + queue worker) |
| **Nhánh chính** | `feature/crm-company-lead-opportunity-flow` (mặc định trên GitHub) |

**8 nhóm chức năng chính:**

1. **Email Marketing** — chiến dịch email, mẫu email, tài khoản/domain gửi, danh sách chặn.
2. **Marketing** — landing page, chiến dịch quảng cáo, form, phân khúc, danh sách liên hệ, trường tùy chỉnh.
3. **CRM** — liên hệ, công ty, khách hàng tiềm năng (Lead V1/V2), đối soát trùng, phân loại chất lượng.
4. **Kinh doanh (Sales)** — cơ hội (pipeline), báo giá + link công khai, phê duyệt, bảng giá, dịch vụ/gói/sản phẩm.
5. **Tài chính (Finance)** — thanh toán, công nợ, tài khoản ngân hàng, báo cáo doanh thu.
6. **Chăm sóc khách hàng** — khách hàng, phân phối chăm sóc, phiếu hỗ trợ (ticket) + webhook email tự tạo ticket.
7. **Cấu hình** — phòng ban, vị trí, nhân viên, người dùng, vai trò & quyền (RBAC), nhãn hệ thống, giao diện, thông tin công ty.
8. **Hệ thống** — nhật ký kiểm toán (audit log).

Ngoài ra còn **11 trang dashboard & báo cáo** (doanh thu, chiến dịch, nhân sự...) và **trang công khai** cho khách hàng (landing page, link báo giá, hủy đăng ký email).

---

## 2. Yêu cầu hệ thống

| Thành phần | Yêu cầu tối thiểu | Khuyến nghị |
|---|---|---|
| PHP | 8.2 | **8.3+** |
| Composer | 2.x | mới nhất |
| Cơ sở dữ liệu | MySQL 8 / MariaDB 10.4+ | MySQL 8 |
| Web server | Nginx / Apache | Nginx + PHP-FPM |
| Bộ mở rộng PHP | `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd` | đủ bộ trên |
| Trình duyệt quản trị | Chrome / Edge / Firefox | mới nhất |

---

## 3. Clone mã nguồn

```bash
# 1. Tải mã nguồn (nhánh chính, đã cài sẵn mọi chức năng)
git clone https://github.com/AnhQuyenDev/DTH-business-core.git
cd DTH-business-core

# 2. Cài đặt các thư viện PHP (dùng composer)
composer install --no-dev --optimize-autoloader
```

> **Lưu ý:** nếu chạy trên máy phát triển (muốn chạy test) dùng `composer install` (không `--no-dev`).

---

## 4. Cấu hình môi trường

```bash
# 3. Tạo file cấu hình từ mẫu
cp .env.example .env

# 4. Sinh khóa bảo mật cho ứng dụng (bắt buộc)
php artisan key:generate
```

### 4.1. Sửa file `.env`

Mở file `.env` và sửa các giá trị sau:

| Biến | Giá trị cần đặt | Ví dụ |
|---|---|---|
| `APP_NAME` | Tên hệ thống hiển thị | `DTH Business Core` |
| `APP_ENV` | `production` khi triển khai thật | `production` |
| `APP_DEBUG` | `false` khi triển khai thật | `false` |
| `APP_URL` | Địa chỉ web công khai (đã đăng ký domain) | `https://crm.congty.vn` |
| `DB_CONNECTION` | `mysql` | `mysql` |
| `DB_HOST` / `DB_PORT` | Địa chỉ + cổng MySQL | `127.0.0.1` / `3306` |
| `DB_DATABASE` | Tên database đã tạo | `dth_business_core` |
| `DB_USERNAME` / `DB_PASSWORD` | Tài khoản MySQL có quyền | `dth_user` / `••••••` |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` | Cấu hình SMTP của nhà cung cấp email | `smtp` / `smtp.gmail.com` / `587` |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Email + tên người gửi hiển thị | `marketing@congty.vn` / `DTH Marketing` |
| `QUEUE_CONNECTION` | `database` (để gửi email/queue chạy nền) | `database` |
| `FILESYSTEM_DISK` | `local` (hoặc `public` nếu muốn lưu file upload web-accessible) | `local` |

> ⚠️ `APP_DEBUG=true` trên server thật sẽ **lộ mã nguồn và mật khẩu** — nhất định để `false`.
> ⚠️ **Tuyệt đối không đăng `.env` lên GitHub** (file này đã được chặn sẵn trong `.gitignore`).

---

## 5. Tạo cơ sở dữ liệu & dữ liệu mẫu

```bash
# 5. Tạo cấu trúc bảng (migrations) — chạy sau khi đã tạo database trống trong MySQL
php artisan migrate --force

# 6. (Tùy chọn) Nạp dữ liệu mẫu + tài khoản nghiệm thu
php artisan db:seed --class=V1AcceptanceTestSeeder --force
```

Sau bước 6, hệ thống có sẵn các tài khoản dùng thử (mật khẩu chung `V1@2026Demo!`):

| Email | Vai trò |
|---|---|
| `superadmin.v1@dth.local` | Super Admin (toàn quyền) |
| `admin.v1@dth.local` | System Admin |
| `founder.v1@dth.local` | Ban lãnh đạo |
| `commercial.v1@dth.local` | Kinh doanh |
| `support.v1@dth.local` | CSKH |
| `finance.v1@dth.local` | Kế toán |

> 💡 Trong môi trường thật, sau khi tạo xong tài khoản quản trị hãy **đổi mật khẩu** và **khóa các tài khoản mẫu** này.

---

## 6. Tạo liên kết lưu trữ & tối ưu

```bash
# 7. Tạo liên kết thư mục public/storage (bắt buộc để xem ảnh/logo/file tải lên)
php artisan storage:link

# 8. Gộp cấu hình (bắt buộc khi triển khai — giúp hệ thống chạy nhanh hơn)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 7. Chạy hệ thống

### 7.1. Triển khai thật (production)

Đảm bảo 3 thành phần sau luôn chạy:

**a) Cron — lập lịch tự động** (gửi email hẹn giờ, phân phối lead, nhắc công nợ...):

Thêm vào crontab của server (sửa đường dẫn đúng vị trí thư mục dự án):

```
* * * * * cd /đường/dẫn/đến/dự-án && php artisan schedule:run >> /dev/null 2>&1
```

**b) Queue worker — xử lý nền** (gửi email, webhook, tác vụ dài):

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Nên chạy bằng **supervisor** để tự khởi động lại khi lỗi (ví dụ file `/etc/supervisor/conf.d/dth-worker.conf`):

```ini
[program:dth-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /đường/dẫn/đến/dự-án/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
numprocs=2
```

**c) Web server** — trỏ document root vào thư mục `public/` của dự án (cấu hình chuẩn Laravel với Nginx/Apache).

### 7.2. Chạy thử trên máy phát triển (local)

```bash
php artisan serve
# mở trình duyệt: http://127.0.0.1:8000/admin  → đăng nhập bằng tài khoản mẫu ở mục 5
```

---

## 8. Kiểm tra hệ thống (test tự động)

```bash
# Chạy toàn bộ test tự động (dùng SQLite trong bộ nhớ — không cần MySQL)
php artisan test
```

Trước khi chạy test trên môi trường có config cache, gỡ cache cấu hình để test dùng đúng database test:

```bash
php artisan config:clear
php artisan test
```

---

## 9. Cập nhật phiên bản mới

```bash
cd DTH-business-core
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
# khởi động lại queue worker (nếu dùng supervisor: supervisorctl restart dth-worker)
```

---

## 10. Xử lý sự cố thường gặp

| Triệu chứng | Nguyên nhân & cách xử lý |
|---|---|
| Trang báo lỗi 500 ngay sau cài đặt | Chưa chạy `key:generate` hoặc `.env` sai cấu hình database |
| Không thấy ảnh/logo tải lên | Chưa chạy `php artisan storage:link` |
| Email hẹn giờ không gửi | Chưa cài **cron schedule** (mục 7.1.a) hoặc queue worker không chạy |
| `config:cache` xong thì test/ứng dụng lỗi DB | Cache cấu hình cũ — chạy lại `php artisan config:clear` rồi `config:cache` |
| Đăng nhập thất bại với tài khoản mẫu | Chưa chạy seeder ở mục 5, hoặc tài khoản đã bị khóa |
| Domain gửi email bị chặn/spam | Chưa khai báo DNS (SPF/DKIM/DMARC) — xem hướng dẫn trong nhóm **Email Marketing → Domain gửi** |

---

## 11. Liên kết hữu ích

- **Hướng dẫn sử dụng (cho người dùng cuối):** [`docs/USER_MANUAL.md`](docs/USER_MANUAL.md)
- **Tài liệu Laravel:** https://laravel.com/docs
- **Tài liệu Filament:** https://filamentphp.com/docs

---

## 12. Giấy phép

Mã nguồn thuộc sở hữu của **DTH Business Core** — phục vụ mục đích nội bộ/triển khai cho khách hàng theo hợp đồng. Không tự ý tái phân phối khi chưa được phép.
