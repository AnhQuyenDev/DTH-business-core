<?php

return [
    'enabled' => (bool) env('DTH_NOTIFICATION_CENTER_ENABLED', true),

    'features' => [
        'in_app' => true,
        'email' => true,
        'manual_send' => true,
        'preferences' => true,
        'topbar_bell' => true,
    ],

    'ui' => [
        'recent_limit' => 8,
        'page_limit' => 100,
        'poll_seconds' => 30,
    ],

    'email' => [
        'queue' => (bool) env('DTH_NOTIFICATION_EMAIL_QUEUE', true),
        'tries' => (int) env('DTH_NOTIFICATION_EMAIL_TRIES', 3),
        'backoff_seconds' => (int) env('DTH_NOTIFICATION_EMAIL_BACKOFF', 60),
    ],


    'attachments' => [
        'disk' => env('DTH_NOTIFICATION_ATTACHMENT_DISK', 'local'),
        'directory' => 'dth-notifications/attachments',
        'max_files' => 5,
        'max_size_kb' => 10240,
        'accepted_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],

    'fallback_administrator_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('DTH_NOTIFICATION_ADMIN_EMAILS', '')),
    ))),

    'integrations' => [
        'hr_account_request_channels' => ['in_app', 'email'],
        'hr_account_request_resolved_channels' => ['in_app', 'email'],
    ],

    'types' => [
        'action_required' => 'Cần xử lý',
        'approval' => 'Phê duyệt',
        'assignment' => 'Phân công',
        'mention' => 'Nhắc đến',
        'reminder' => 'Nhắc việc',
        'announcement' => 'Thông báo chung',
        'system' => 'Hệ thống',
        'status_update' => 'Cập nhật trạng thái',
    ],

    'priorities' => [
        'low' => 'Thấp',
        'normal' => 'Bình thường',
        'high' => 'Cao',
        'critical' => 'Khẩn cấp',
    ],

    'permissions' => [
        'notifications.send' => [
            'module' => 'notifications',
            'name' => 'Gửi thông báo',
            'description' => 'Gửi thông báo thủ công tới người dùng cụ thể; là quyền nền cho các quyền gửi mở rộng.',
        ],
        'notifications.send.roles' => [
            'module' => 'notifications',
            'name' => 'Gửi thông báo theo vai trò',
            'description' => 'Cho phép chọn một hoặc nhiều vai trò làm tập người nhận.',
        ],
        'notifications.send.groups' => [
            'module' => 'notifications',
            'name' => 'Gửi thông báo theo nhóm & đơn vị',
            'description' => 'Cho phép chọn nhóm hoặc đơn vị làm tập người nhận.',
        ],
        'notifications.send.departments' => [
            'module' => 'notifications',
            'name' => 'Gửi thông báo theo phòng ban',
            'description' => 'Cho phép chọn phòng ban HR làm tập người nhận.',
        ],
        'notifications.send.broadcast' => [
            'module' => 'notifications',
            'name' => 'Gửi thông báo toàn hệ thống',
            'description' => 'Cho phép gửi broadcast tới toàn bộ tài khoản đang hoạt động.',
        ],
        'notifications.audit.view' => [
            'module' => 'notifications',
            'name' => 'Xem nhật ký gửi thông báo',
            'description' => 'Xem trạng thái phân phối In-app và Email của các thông báo.',
        ],
        'notifications.templates.manage' => [
            'module' => 'notifications',
            'name' => 'Quản lý mẫu thông báo',
            'description' => 'Quản lý nội dung mẫu và biến dữ liệu dùng cho thông báo hệ thống.',
        ],
        'notifications.settings.manage' => [
            'module' => 'notifications',
            'name' => 'Cấu hình Notification Center',
            'description' => 'Quản lý các thiết lập hệ thống và thông báo bắt buộc.',
        ],
    ],
];
