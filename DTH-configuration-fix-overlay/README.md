# DTH Configuration Fix - 2026-08-08

## Source baseline

Gói sửa nay được tạo trực tiếp tu file ZIP local mà bạn cung cấp: `DTH-business-core-feature-crm-company-lead-opportunity-flow.zip`.
GitHub raw endpoint vẫn trả về snapshot cũ hơn, vì vậy ZIP local được dùng lam source of truth để tránh ghi đè các thay đổi mới push.

## Phạm vi đã sửa

1. Khôi phục quyền Service / Service Package:
   - Admin: xem + quản lý.
   - Sales Manager: xem + quản lý.
   - Sales Staff, Marketing, CSKH: được xem catalog để thực thi nghiệp vụ.
   - Finance khong quản lý catalog.
2. Bổ sung Gate cho Bank Account:
   - Admin + Finance Staff quản lý.
   - Sales/Finance được xem theo nhu cầu.
3. Price Book:
   - Admin + Sales Manager quản lý.
   - Sales Staff chỉ xem/sử dụng.
4. Chuẩn hóa User - Staff theo mô hình Staff-first:
   - Staff có thể tồn tại mà chưa có User.
   - User nghiệp vụ được cấp tu mot Staff chưa có tài khoản.
   - Không còn fallback phòng Marketing khi tạo User.
   - Xóa User không xóa lịch sử Staff; FK chuyen sang nullOnDelete.
   - User không xóa trực tiếp trên UI; dùng Active Account để khóa đăng nhập.
5. Department là master data:
   - Thêm `function_key` để tách chức năng nghiệp vụ khỏi mã phòng ban.
   - Thêm `color` de đồng bộ badge phòng ban trên Department / Staff / User / Position.
   - Hỗ trợ palette Filament an toàn thay vì hard-code theo department code.
6. Ràng buộc Role - Department:
   - Marketing role -> Department Function Marketing.
   - CSKH role -> Customer Service.
   - Sales role -> Sales.
   - Finance Staff -> Finance.
   - Admin / Viewer khong bắt buộc Department Function.
7. User list:
   - Hiển thị Phòng ban, Chức danh, Role va trạng thái account tách biệt.
8. Staff list:
   - Thêm action `Cấp tài khoản` cho Staff chua co User.
   - Mã nhân viên sinh tự động.
9. Thêm trang `Vai trò & Quyen`:
   - Hien ma trận role cố định hien tai.
   - Không cho tạo role dong vi hệ thống dang dùng enum + Gate/Policy cố định; dynamic RBAC cần phase riêng.
10. Thêm test `OrganizationConfigurationTest`.

## Ap dùng nhanh nhat

Từ thư mục gốc repo:

```bash
git status
git add -A && git commit -m "checkpoint before configuration fix"
```

Giải nén overlay vao dùng thu muc goc repo và cho phép overwrite file.
Sau đó:

```bash
cd marketing-email-laravel-v12
php artisan migrate
php artisan optimize:clear
php artisan test --filter=OrganizationConfigurationTest
php artisan test
```

Nếu đang chạy queue worker:

```bash
php artisan queue:restart
```

## Checkpoint UI sau migrate

Admin:
- Thấy `Dịch vụ`, `Gói dịch vụ`, `Bảng giá`, `Tài khoản ngân hàng` theo quyền mới.
- `Cấu hình -> Phòng ban` co `Chuc nang phòng ban` va `Màu sắc`.
- `Cấu hình -> Nhân viên`: có thể tao Staff khong can User.
- Staff chưa có tài khoản có action `Cấp tài khoản`.
- `Cấu hình -> Nguoi dùng`: hien Phòng ban + Chức danh + Role + Active Account.
- `Cấu hình -> Vai trò & Quyen`: xem ma trận role.

## Lưu ý migration

Migration mới: `2026_08_08_150000_harden_configuration_organization_model.php`.
Cac phòng ban chuan (`marketing`, `customer_service`, `sales`, `admin`, `technical`, `email_service`) được backfill function/color tự động.
Phòng Tài chính được nhận diện theo cac code thong dùng (`finance`, `financial`, `accounting`, `tai_chinh`) hoặc tên `Phòng Tài chính` / `Tài chính` / `Finance`.
Sau migrate nên mở danh sách Phòng ban va kiểm tra lại `Chuc nang phòng ban` cua cac phòng custom.

## Rollback

Nếu chưa có dữ liệu Staff mới không gắn User, có thể:

```bash
php artisan migrate:rollback --step=1
```

Nếu đã tạo Staff khong co User sau khi ap dùng, KHÔNG rollback migration cho đến khi đã gắn tai khoan hoặc xử lý cac Staff do.
Dùng Git de rollback source:

```bash
git reset --hard HEAD~1
```

chỉ khi checkpoint commit o trên là commit riêng và bạn chắc chắn không có thay đổi cần giữ.
