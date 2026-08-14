# DTH Business Core V1 — Báo cáo audit và redesign truy vấn

Ngày audit: 14/08/2026

Source chuẩn: `DTH-business-core-feature-crm-company-lead-opportunity-flow(9).zip`

Đối chiếu: `dth_database_schema(1).sql`, `dth_table_sizes(1).csv`

## 1. Kết luận nguyên nhân

Database hiện có 97 bảng nhưng dữ liệu nghiệp vụ gần như trống. Các số liệu đáng chú ý:

- `staff`: 4 dòng;
- `staff_business_functions`: 8 dòng;
- `audit_logs`: 1 dòng;
- phần lớn bảng Lead, Opportunity, Quotation, Payment, Campaign và Landing Page: 0 dòng;
- `cache`: 276 dòng.

Vì vậy việc đăng nhập và mở trang mất nhiều giây không xuất phát từ dung lượng dữ liệu. Hai nguyên nhân chính trong code là:

1. Filament dựng menu và gọi nhiều kiểm tra quyền. Mỗi lần kiểm tra quyền lại gọi `Schema::hasTable()` cho bốn bảng RBAC và `Permission::exists()`. Chi phí này lặp lại theo số mục menu.
2. Dashboard chạy các truy vấn thống kê theo từng bucket, từng chiến dịch và từng nhân viên. Khi dữ liệu tăng, số truy vấn tăng tuyến tính theo số đối tượng (N+1).

Ngoài ra, lần request đầu trên Windows còn chịu chi phí nạp class Laravel/Filament, biên dịch Blade, discovery component và Windows Defender quét nhiều file PHP. Đây là lý do những lần sau nhanh dần dù dữ liệu không đổi.

## 2. Redesign đã thực hiện

### RBAC và điều hướng

- `RbacSyncService` và `RbacAuthorizationService` được đăng ký singleton.
- Trạng thái tồn tại bảng/quyền được ghi nhớ, không truy vấn lại cho từng mục menu.
- Không thay đổi ma trận quyền, vai trò, Super Admin hoặc policy nghiệp vụ.

### Dashboard điều hành

- Revenue trend: từ một truy vấn cho mỗi bucket sang một truy vấn tổng hợp theo ngày rồi chia bucket trong bộ nhớ.
- Revenue theo nguồn: tổng hợp trực tiếp bằng SQL thay vì tải toàn bộ Payment và Attribution vào PHP.
- Revenue theo nhân viên kinh doanh: `GROUP BY sales_staff_id`.
- Marketing Campaign, Email Campaign và Landing Page: lấy danh sách ID một lần, sau đó tổng hợp submissions, leads, recipients và payments theo ID. Không còn truy vấn trong vòng `map()`.
- Snapshot Dashboard cache ngắn 45 giây theo `user + locale + dataset + period`; có thể cấu hình hoặc tắt.

### Workforce Analytics

- Trước đây mỗi nhân viên phát sinh nhiều truy vấn riêng cho campaign, submission, lead, quotation, payment, ticket và interaction.
- Hiện tại truy vấn được gom theo `created_by`, `staff_id`, `assigned_staff_id`, `sales_staff_id` và `verified_by_user_id`.
- Số truy vấn phụ thuộc vào số nhóm nghiệp vụ xuất hiện, không phụ thuộc vào số lượng nhân viên.
- Có test hồi quy kiểm tra số truy vấn không tăng khi số nhân viên tăng.

### Widget tổng hợp

- Các widget nhân sự, khách hàng, pipeline, campaign và landing page dùng `GROUP BY status` hoặc conditional aggregate.
- Các chuỗi 3–9 câu `COUNT()` độc lập được rút xuống 1–3 câu tổng hợp.
- Điều kiện “hôm nay” dùng khoảng thời gian đầu/cuối ngày thay cho `whereDate()`, giúp index timestamp có thể được sử dụng.

### Index

Migration mới bổ sung index cho các đường truy vấn nóng:

- Quotation theo nhân viên + ngày tạo/ngày chấp nhận;
- Payment theo người xác minh + thời điểm xác minh;
- Interaction và Support Ticket theo nhân viên + thời gian;
- Campaign/Landing Page theo người tạo + ngày tạo;
- Landing Page View/Submission theo trang hoặc campaign + thời gian;
- Lead theo ngày tạo;
- Customer theo lịch follow-up;
- Audit log theo user + thời gian.

Các index đã tồn tại trong schema được giữ nguyên và không tạo trùng.

## 3. Cơ chế tự phát hiện URL chậm

Không cần nhập danh sách URL thủ công. Bật tạm trong `.env`:

```dotenv
PERFORMANCE_TRACE_ENABLED=true
PERFORMANCE_SLOW_REQUEST_MS=750
```

Sau đó chạy:

```bat
php artisan optimize:clear
```

Sử dụng hệ thống bình thường. Mỗi admin request vượt ngưỡng sẽ ghi một dòng `admin.performance.slow_request` vào `storage/logs/laravel.log`, gồm:

- method và path;
- tổng thời gian request;
- số truy vấn;
- tổng thời gian database;
- user ID.

Không ghi SQL binding, mật khẩu hoặc dữ liệu khách hàng. Sau khi đo xong, đặt `PERFORMANCE_TRACE_ENABLED=false`.

## 4. Mục tiêu kiểm chứng

Không thể cam kết millisecond trước khi chạy trên PHP/MySQL của máy đích. Các tiêu chí bắt buộc sau khi áp dụng là:

- số truy vấn Workforce không tăng theo số nhân viên;
- trend không tăng truy vấn theo số bucket;
- campaign/landing page không tăng truy vấn theo số bản ghi hiển thị;
- permission readiness chỉ được kiểm tra một lần;
- lần tải lại Dashboard trong 45 giây dùng snapshot;
- không thay đổi quyền truy cập, workflow V1 và UI/UX.

## 5. Phần không nên làm

- Không bật Redis để che N+1 trước khi gom truy vấn.
- Không thêm index cho mọi cột; index dư làm chậm INSERT/UPDATE và tăng dung lượng.
- Không để performance tracing bật lâu trên production vì query log dùng thêm bộ nhớ.
- Không dùng `APP_DEBUG=true` trên server thật.
