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
        'protected_role_keys' => [
            'super-admin', 'super_admin', 'administrator', 'system_admin', 'system-administrator', 'system_administrator',
        ],
    ],

    /*
     | Permission prerequisites. When a role receives an action permission,
     | the base view permission is automatically included. AccessControlService
     | also honors these implications for roles created before this rule existed.
     */
    'permission_dependencies' => [
        'accounts.users.manage' => ['accounts.view'],
        'accounts.roles.manage' => ['accounts.view'],
        'accounts.groups.manage' => ['accounts.view'],
        'accounts.invitations.manage' => ['accounts.view'],
        'accounts.sessions.manage' => ['accounts.view'],
        'accounts.settings.manage' => ['accounts.view'],

        'notifications.send.roles' => ['notifications.send'],
        'notifications.send.groups' => ['notifications.send'],
        'notifications.send.departments' => ['notifications.send'],
        'notifications.send.broadcast' => ['notifications.send'],
        'notifications.templates.manage' => ['notifications.send'],
        'notifications.settings.manage' => ['notifications.send'],

        'commercial.manage-catalog' => ['commercial.view'],
        'commercial.manage-opportunities' => ['commercial.view'],
        'commercial.reports' => ['commercial.view'],
        'commercial.export' => ['commercial.view'],

        'crm.manage' => ['crm.view'],
        'crm.export' => ['crm.view'],

        'marketing.manage' => ['marketing.view'],
        'marketing.view-reports' => ['marketing.view'],
        'marketing.export' => ['marketing.view'],
        'marketing.process-submissions' => ['marketing.view'],

        'hr.manage' => ['hr.view'],
        'hr.export' => ['hr.view'],

        'email.manage' => ['email.view'],
        'email.reports' => ['email.view'],
        'email.export' => ['email.view'],
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

        'notifications.send' => ['module' => 'notifications', 'name' => 'Gửi thông báo', 'description' => 'Gửi thông báo thủ công tới người dùng cụ thể; là quyền nền cho các quyền gửi mở rộng.'],
        'notifications.send.roles' => ['module' => 'notifications', 'name' => 'Gửi thông báo theo vai trò', 'description' => 'Chọn một hoặc nhiều vai trò làm tập người nhận.'],
        'notifications.send.groups' => ['module' => 'notifications', 'name' => 'Gửi thông báo theo nhóm & đơn vị', 'description' => 'Chọn nhóm hoặc đơn vị làm tập người nhận.'],
        'notifications.send.departments' => ['module' => 'notifications', 'name' => 'Gửi thông báo theo phòng ban', 'description' => 'Chọn phòng ban HR làm tập người nhận.'],
        'notifications.send.broadcast' => ['module' => 'notifications', 'name' => 'Gửi thông báo toàn hệ thống', 'description' => 'Gửi broadcast tới toàn bộ tài khoản đang hoạt động.'],
        'notifications.audit.view' => ['module' => 'notifications', 'name' => 'Xem nhật ký gửi thông báo', 'description' => 'Xem trạng thái phân phối In-app và Email của thông báo.'],
        'notifications.templates.manage' => ['module' => 'notifications', 'name' => 'Quản lý mẫu thông báo', 'description' => 'Quản lý nội dung mẫu và biến dữ liệu của thông báo hệ thống.'],
        'notifications.settings.manage' => ['module' => 'notifications', 'name' => 'Cấu hình Notification Center', 'description' => 'Quản lý thiết lập hệ thống và thông báo bắt buộc.'],

        'commercial.view' => ['module' => 'commercial', 'name' => 'Xem Dịch vụ & Kinh doanh', 'description' => 'Truy cập module Commercial.'],
        'commercial.manage-catalog' => ['module' => 'commercial', 'name' => 'Quản lý danh mục Commercial', 'description' => 'Quản lý dịch vụ và gói dịch vụ.'],
        'commercial.manage-opportunities' => ['module' => 'commercial', 'name' => 'Quản lý cơ hội kinh doanh', 'description' => 'Tạo, cập nhật và chuyển giai đoạn cơ hội.'],
        'commercial.reports' => ['module' => 'commercial', 'name' => 'Xem phân tích Commercial', 'description' => 'Xem phân tích và báo cáo Dịch vụ & Kinh doanh.'],
        'commercial.export' => ['module' => 'commercial', 'name' => 'Xuất dữ liệu Commercial', 'description' => 'Xuất PDF, Excel và CSV của Dịch vụ & Kinh doanh.'],

        'crm.view' => ['module' => 'crm', 'name' => 'Xem CRM', 'description' => 'Truy cập dữ liệu CRM.'],
        'crm.manage' => ['module' => 'crm', 'name' => 'Quản lý CRM', 'description' => 'Tạo và cập nhật dữ liệu CRM.'],
        'crm.export' => ['module' => 'crm', 'name' => 'Xuất dữ liệu CRM', 'description' => 'Xuất Excel và CSV từ CRM.'],

        'marketing.view' => ['module' => 'marketing', 'name' => 'Xem Marketing', 'description' => 'Truy cập module Marketing.'],
        'marketing.manage' => ['module' => 'marketing', 'name' => 'Quản lý Marketing', 'description' => 'Tạo và cập nhật dữ liệu Marketing.'],
        'marketing.view-reports' => ['module' => 'marketing', 'name' => 'Xem báo cáo Marketing', 'description' => 'Xem báo cáo và số liệu Marketing.'],
        'marketing.export' => ['module' => 'marketing', 'name' => 'Xuất dữ liệu Marketing', 'description' => 'Xuất báo cáo Marketing.'],
        'marketing.process-submissions' => ['module' => 'marketing', 'name' => 'Xử lý biểu mẫu Marketing', 'description' => 'Xử lý submission và chuyển đổi dữ liệu.'],

        'hr.view' => ['module' => 'human-resource', 'name' => 'Xem Nhân sự', 'description' => 'Truy cập module Human Resource.'],
        'hr.manage' => ['module' => 'human-resource', 'name' => 'Quản lý Nhân sự', 'description' => 'Quản lý nhân viên, phòng ban và chức danh.'],
        'hr.export' => ['module' => 'human-resource', 'name' => 'Xuất dữ liệu Nhân sự', 'description' => 'Xuất Excel và CSV từ Human Resource.'],

        'email.view' => ['module' => 'email', 'name' => 'Xem Email', 'description' => 'Truy cập module Email.'],
        'email.manage' => ['module' => 'email', 'name' => 'Quản lý Email', 'description' => 'Quản lý chiến dịch, template và cấu hình Email.'],
        'email.reports' => ['module' => 'email', 'name' => 'Xem báo cáo Email', 'description' => 'Xem phân tích và báo cáo Email.'],
        'email.export' => ['module' => 'email', 'name' => 'Xuất dữ liệu Email', 'description' => 'Xuất báo cáo Email.'],
    ],
];
