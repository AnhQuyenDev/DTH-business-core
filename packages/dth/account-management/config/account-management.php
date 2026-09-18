<?php

return [
    'enabled' => (bool) env('DTH_ACCOUNT_MANAGEMENT_ENABLED', true),

    'features' => [
        'overview' => true,
        'users' => true,
        'roles' => true,
        'groups' => true,
        'invitations' => true,
        'sessions' => true,
        'audit' => true,
        'settings' => true,
    ],

    'security' => [
        'max_failed_logins' => (int) env('DTH_ACCOUNT_MAX_FAILED_LOGINS', 5),
        'lock_minutes' => (int) env('DTH_ACCOUNT_LOCK_MINUTES', 30),
        'active_session_minutes' => (int) env('DTH_ACCOUNT_ACTIVE_SESSION_MINUTES', 30),
        'default_timezone' => env('DTH_ACCOUNT_DEFAULT_TIMEZONE', 'Asia/Ho_Chi_Minh'),
        'default_locale' => env('DTH_ACCOUNT_DEFAULT_LOCALE', 'vi'),
    ],

    'bootstrap' => [
        'administrator_emails' => array_values(array_filter(array_map('trim', explode(',', (string) env('DTH_ACCOUNT_BOOTSTRAP_ADMINS', ''))))),
        'oldest_user_is_administrator' => (bool) env('DTH_ACCOUNT_OLDEST_USER_ADMIN', true),
    ],

    'invitations' => [
        'expires_hours' => (int) env('DTH_ACCOUNT_INVITATION_EXPIRES_HOURS', 72),
        'send_mail' => (bool) env('DTH_ACCOUNT_INVITATION_SEND_MAIL', true),
    ],

    'human_resource' => [
        'employee_model' => 'Dth\\HumanResource\\Models\\Employee',
        'auto_disable_when_employee_inactive' => (bool) env('DTH_ACCOUNT_SYNC_HR_STATUS', true),
    ],

    /*
     | Permissions are synchronized into account_permissions by the migration
     | and by `php artisan dth:accounts:sync-permissions`.
     | Existing DTH modules already consume many of these Laravel Gate names.
     */
    'permissions' => [
        'accounts.view' => ['module' => 'accounts', 'name' => 'Xem quản lý tài khoản', 'description' => 'Xem tổng quan, người dùng và cấu hình tài khoản.'],
        'accounts.users.manage' => ['module' => 'accounts', 'name' => 'Quản lý người dùng', 'description' => 'Tạo, sửa, khóa, mở khóa và cập nhật tài khoản.'],
        'accounts.roles.manage' => ['module' => 'accounts', 'name' => 'Quản lý vai trò & phân quyền', 'description' => 'Quản lý role và permission.'],
        'accounts.groups.manage' => ['module' => 'accounts', 'name' => 'Quản lý nhóm & đơn vị', 'description' => 'Tạo nhóm và gán người dùng vào nhóm.'],
        'accounts.invitations.manage' => ['module' => 'accounts', 'name' => 'Quản lý lời mời', 'description' => 'Tạo và gửi lời mời tài khoản.'],
        'accounts.sessions.manage' => ['module' => 'accounts', 'name' => 'Quản lý phiên đăng nhập', 'description' => 'Xem và thu hồi phiên đăng nhập.'],
        'accounts.audit.view' => ['module' => 'accounts', 'name' => 'Xem nhật ký hoạt động', 'description' => 'Xem audit log và sự kiện bảo mật.'],
        'accounts.settings.manage' => ['module' => 'accounts', 'name' => 'Cấu hình truy cập', 'description' => 'Thay đổi chính sách truy cập và bảo mật.'],

        'commercial.view' => ['module' => 'commercial', 'name' => 'Xem Dịch vụ & Kinh doanh', 'description' => 'Truy cập module Commercial.'],
        'commercial.manage-catalog' => ['module' => 'commercial', 'name' => 'Quản lý danh mục Commercial', 'description' => 'Quản lý dịch vụ và gói dịch vụ.'],
        'commercial.manage-opportunities' => ['module' => 'commercial', 'name' => 'Quản lý cơ hội kinh doanh', 'description' => 'Tạo, cập nhật và chuyển giai đoạn cơ hội.'],

        'crm.view' => ['module' => 'crm', 'name' => 'Xem CRM', 'description' => 'Truy cập dữ liệu CRM.'],
        'crm.manage' => ['module' => 'crm', 'name' => 'Quản lý CRM', 'description' => 'Tạo và cập nhật dữ liệu CRM.'],

        'marketing.view' => ['module' => 'marketing', 'name' => 'Xem Marketing', 'description' => 'Truy cập module Marketing.'],
        'marketing.manage' => ['module' => 'marketing', 'name' => 'Quản lý Marketing', 'description' => 'Tạo và cập nhật dữ liệu Marketing.'],
        'marketing.view-reports' => ['module' => 'marketing', 'name' => 'Xem báo cáo Marketing', 'description' => 'Xem báo cáo và số liệu Marketing.'],
        'marketing.export' => ['module' => 'marketing', 'name' => 'Xuất dữ liệu Marketing', 'description' => 'Xuất báo cáo Marketing.'],
        'marketing.process-submissions' => ['module' => 'marketing', 'name' => 'Xử lý biểu mẫu Marketing', 'description' => 'Xử lý submission và chuyển đổi dữ liệu.'],

        'hr.view' => ['module' => 'human-resource', 'name' => 'Xem Nhân sự', 'description' => 'Truy cập module Human Resource.'],
        'hr.manage' => ['module' => 'human-resource', 'name' => 'Quản lý Nhân sự', 'description' => 'Quản lý nhân viên, phòng ban và chức danh.'],

        'email.view' => ['module' => 'email', 'name' => 'Xem Email', 'description' => 'Truy cập module Email.'],
        'email.manage' => ['module' => 'email', 'name' => 'Quản lý Email', 'description' => 'Quản lý chiến dịch, template và cấu hình Email.'],
        'email.reports' => ['module' => 'email', 'name' => 'Xem báo cáo Email', 'description' => 'Xem phân tích và báo cáo Email.'],
        'email.export' => ['module' => 'email', 'name' => 'Xuất dữ liệu Email', 'description' => 'Xuất báo cáo Email.'],
    ],
];
