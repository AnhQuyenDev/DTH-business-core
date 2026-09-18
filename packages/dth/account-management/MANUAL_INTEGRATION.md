# Tích hợp thủ công vào DTH Business Core

Module này độc lập và không sửa trực tiếp CRM, Marketing, Commercial, Email hoặc Human Resource.

## 1. Copy package

Copy thư mục này thành:

```text
packages/dth/account-management
```

## 2. Root composer.json

Thêm vào `require`:

```json
"dth/account-management": "@dev"
```

Root đã có path repository `packages/dth/*`, không cần repository mới.

Chạy:

```bash
composer update dth/account-management --with-dependencies
```

## 3. Đăng ký Filament plugin

Trong `app/Providers/Filament/AdminPanelProvider.php` thêm:

```php
use Dth\AccountManagement\Filament\AccountManagementPlugin;
use Dth\AccountManagement\Http\Middleware\EnsureAccountIsActive;
```

Thêm plugin ngang hàng các module khác:

```php
->plugin(
    AccountManagementPlugin::make()
)
```

Để tài khoản inactive/locked không thể tiếp tục dùng Admin Panel, thêm middleware sau `Authenticate::class`:

```php
->authMiddleware([
    Authenticate::class,
    EnsureAccountIsActive::class,
])
```

## 4. Migration

```bash
php artisan migrate
php artisan dth:accounts:sync-permissions
php artisan optimize:clear
```

## 5. Bootstrap quản trị

Mặc định user có ID nhỏ nhất được xem là bootstrap administrator. Có thể tắt bằng:

```env
DTH_ACCOUNT_OLDEST_USER_ADMIN=false
```

hoặc chỉ định email quản trị:

```env
DTH_ACCOUNT_BOOTSTRAP_ADMINS=admin@company.vn,owner@company.vn
```

Sau khi vào module:

1. Tạo/chỉnh Role.
2. Gán Role cho toàn bộ user cần dùng hệ thống.
3. Kiểm tra quyền module.
4. Vào **Cấu hình truy cập**.
5. Đổi `security.permissions_enforced` từ `false` thành `true`.

Việc để enforcement mặc định `false` giúp cài module không làm mất quyền truy cập hiện có trước khi bạn hoàn tất gán role.

## 6. Gate names đang bridge

Các module hiện tại có thể dùng ngay các permission sau:

- Commercial: `commercial.view`, `commercial.manage-catalog`, `commercial.manage-opportunities`
- CRM: `crm.view`, `crm.manage`
- Marketing: `marketing.view`, `marketing.manage`, `marketing.view-reports`, `marketing.export`, `marketing.process-submissions`
- Human Resource: `hr.view`, `hr.manage`
- Email: `email.view`, `email.manage` qua Gate-before bridge cho model abilities; `email.reports` và `email.export` đã có trong registry để dùng khi Email tách permission chi tiết hơn.

## 7. Human Resource integration

Không có Composer dependency sang HR. Nếu class `Dth\HumanResource\Models\Employee` và bảng `hr_employees` có mặt:

- form User hiện selector **Nhân viên liên kết**;
- Account module cập nhật `hr_employees.user_id`;
- khi Employee chuyển khỏi trạng thái `active`, Account có thể tự chuyển `account_status = inactive`.

Tắt đồng bộ tự động:

```env
DTH_ACCOUNT_SYNC_HR_STATUS=false
```

## 8. Lời mời

Invitation lưu **hash của token**, không lưu token plain text. Link kích hoạt có hạn mặc định 72 giờ.

```env
DTH_ACCOUNT_INVITATION_EXPIRES_HOURS=72
DTH_ACCOUNT_INVITATION_SEND_MAIL=true
```

Nếu mail transport chưa cấu hình, invitation vẫn được tạo nhưng mail có thể không gửi. Có thể resend sau khi cấu hình mail.

## 9. Những phần có sẵn nhưng cần module nghiệp vụ tiêu thụ để phát huy đầy đủ

`data_scope = own/team/all` được lưu trên Role và `AccessControlService::dataScope()` đã sẵn sàng. Muốn giới hạn record ở mức "chỉ cơ hội tôi phụ trách" hoặc "chỉ campaign của team", Resource/query của từng module cần gọi data scope khi xây query.

2FA/SSO chưa được bật mặc định trong package này vì chúng thay đổi trực tiếp authentication flow của host. Cấu trúc security/session/audit đã được chuẩn bị để bổ sung riêng mà không phá schema Role/Permission.
