<?php

namespace App\Services\Security;

final class RbacDefinition
{
    /** @return array<string, array{label:string,module:string,description?:string}> */
    public static function permissions(): array
    {
        $p = [];
        $add = function (string $name, string $label, string $module, string $description = '') use (&$p): void {
            $p[$name] = compact('label', 'module', 'description');
        };

        foreach ([
            'view-contacts' => 'Xem liên hệ', 'manage-contacts' => 'Quản lý liên hệ',
            'view-tags' => 'Xem thẻ', 'manage-tags' => 'Quản lý thẻ',
            'view-lists' => 'Xem danh sách liên hệ', 'manage-lists' => 'Quản lý danh sách liên hệ',
            'view-segments' => 'Xem phân khúc', 'manage-segments' => 'Quản lý phân khúc',
            'view-custom-fields' => 'Xem trường tùy chỉnh', 'manage-custom-fields' => 'Quản lý trường tùy chỉnh',
            'view-templates' => 'Xem mẫu Email', 'manage-templates' => 'Quản lý mẫu Email',
            'view-campaigns' => 'Xem chiến dịch', 'manage-campaigns' => 'Quản lý chiến dịch',
            'send-campaigns' => 'Gửi chiến dịch Email', 'manage-sending' => 'Quản lý tài khoản gửi',
            'view-suppression' => 'Xem danh sách chặn', 'manage-suppression' => 'Quản lý danh sách chặn',
            'export-data' => 'Xuất dữ liệu Marketing', 'view-reports' => 'Xem báo cáo Marketing',
            'view-landing-pages' => 'Xem Landing Page', 'manage-landing-pages' => 'Quản lý Landing Page',
            'view-landing-page-submissions' => 'Xem lượt gửi biểu mẫu',
            'view-landing-form-templates' => 'Xem mẫu biểu mẫu', 'manage-landing-form-templates' => 'Quản lý mẫu biểu mẫu',
        ] as $suffix => $label) $add('marketing.'.$suffix, $label, 'Marketing');

        foreach ([
            'view-companies' => 'Xem công ty', 'view-leads' => 'Xem Lead',
            'assign-lead' => 'Phân công Lead', 'reassign-lead' => 'Điều chuyển Lead',
            'manage-company-owner' => 'Quản lý người phụ trách công ty', 'process-lead' => 'Xử lý và đánh giá Lead',
            'archive-lead' => 'Lưu trữ Lead', 'manage-staff' => 'Quản lý nhân viên',
            'manage-staff-availability' => 'Quản lý lịch nhận việc nhân viên',
        ] as $suffix => $label) $add('crm.'.$suffix, $label, 'CRM');

        foreach ([
            'view-services' => 'Xem dịch vụ', 'manage-services' => 'Quản lý dịch vụ',
            'view-products' => 'Xem sản phẩm', 'manage-products' => 'Quản lý sản phẩm',
            'view-service-packages' => 'Xem gói dịch vụ', 'manage-service-packages' => 'Quản lý gói dịch vụ',
            'view-price-books' => 'Xem bảng giá', 'manage-price-books' => 'Quản lý bảng giá', 'approve-price-books' => 'Duyệt bảng giá',
            'view-bank-accounts' => 'Xem tài khoản ngân hàng', 'manage-bank-accounts' => 'Quản lý tài khoản ngân hàng',
            'view-opportunities' => 'Xem cơ hội kinh doanh', 'create-opportunities' => 'Tạo cơ hội kinh doanh', 'process-opportunities' => 'Xử lý cơ hội kinh doanh',
            'view-quotations' => 'Xem báo giá', 'create-quotations' => 'Tạo báo giá', 'send-quotations' => 'Gửi báo giá',
            'approve-quotations' => 'Duyệt báo giá', 'revise-quotations' => 'Tạo phiên bản báo giá', 'cancel-quotations' => 'Hủy báo giá',
            'record-customer-response' => 'Ghi nhận phản hồi khách hàng', 'export-quotations' => 'Xuất báo giá',
            'verify-payments' => 'Đối soát thanh toán', 'view-payments' => 'Xem thanh toán', 'view-revenue-reports' => 'Xem báo cáo doanh thu',
        ] as $suffix => $label) $add('sales.'.$suffix, $label, in_array($suffix, ['verify-payments','view-payments','view-revenue-reports'], true) ? 'Tài chính' : 'Kinh doanh');

        foreach ([
            'view' => 'Xem khách hàng hậu bán hàng', 'interact' => 'Chăm sóc và tương tác khách hàng',
            'manage-assignments' => 'Quản lý phân công CSKH', 'distribute' => 'Phân phối khách hàng',
            'rebalance' => 'Cân bằng tải CSKH', 'view-tickets' => 'Xem Ticket hỗ trợ',
            'manage-tickets' => 'Xử lý Ticket hỗ trợ',
        ] as $suffix => $label) $add('customer-care.'.$suffix, $label, 'Chăm sóc khách hàng');

        foreach ([
            'manage-rbac' => 'Quản lý vai trò và quyền', 'manage-users' => 'Quản lý tài khoản người dùng',
            'manage-organization' => 'Quản lý cơ cấu tổ chức', 'manage-company-settings' => 'Quản lý cài đặt công ty',
            'view-audit' => 'Xem nhật ký hệ thống', 'view-workforce' => 'Xem phân tích nhân lực',
        ] as $suffix => $label) $add('system.'.$suffix, $label, 'Hệ thống');

        return $p;
    }

    /** @return array<string, array{label:string,description:string,permissions:array<int,string>,system:bool}> */
    public static function roles(): array
    {
        $all = array_keys(self::permissions());

        $marketingManager = array_values(array_unique([
            ...self::matching(['marketing.']),
            'sales.view-services', 'sales.view-products', 'sales.view-service-packages',
            'sales.view-revenue-reports',
        ]));

        $marketingStaff = array_values(array_unique([
            ...self::matching(['marketing.view-']),
            'marketing.manage-contacts', 'marketing.manage-tags', 'marketing.manage-lists',
            'marketing.manage-segments', 'marketing.manage-custom-fields', 'marketing.manage-templates',
            'marketing.manage-campaigns', 'marketing.manage-landing-pages', 'marketing.manage-landing-form-templates',
            'sales.view-services', 'sales.view-products', 'sales.view-service-packages',
        ]));

        $salesManager = [
            'crm.view-companies', 'crm.view-leads', 'crm.assign-lead', 'crm.reassign-lead',
            'crm.manage-company-owner', 'crm.process-lead', 'crm.archive-lead',
            'sales.view-services', 'sales.manage-services',
            'sales.view-products', 'sales.manage-products',
            'sales.view-service-packages', 'sales.manage-service-packages',
            'sales.view-price-books', 'sales.manage-price-books', 'sales.approve-price-books',
            'sales.view-bank-accounts',
            'sales.view-opportunities', 'sales.create-opportunities', 'sales.process-opportunities',
            'sales.view-quotations', 'sales.create-quotations', 'sales.send-quotations',
            'sales.approve-quotations', 'sales.revise-quotations', 'sales.cancel-quotations',
            'sales.record-customer-response', 'sales.export-quotations',
            'sales.view-payments', 'sales.view-revenue-reports',
        ];

        $salesStaff = [
            'crm.view-companies', 'crm.view-leads', 'crm.process-lead',
            'sales.view-services', 'sales.view-products', 'sales.view-service-packages',
            'sales.view-price-books', 'sales.view-bank-accounts',
            'sales.view-opportunities', 'sales.create-opportunities', 'sales.process-opportunities',
            'sales.view-quotations', 'sales.create-quotations', 'sales.send-quotations',
            'sales.revise-quotations', 'sales.record-customer-response', 'sales.export-quotations',
        ];

        return [
            'super_admin' => [
                'label' => 'Super Admin',
                'description' => 'Toàn quyền hệ thống; dành cho demo, vận hành cấp cao và xử lý sự cố.',
                'permissions' => $all,
                'system' => true,
            ],
            'system_admin' => [
                'label' => 'Quản trị hệ thống',
                'description' => 'Quản lý người dùng, tổ chức, cấu hình, quyền và audit; chỉ xem nghiệp vụ, không mặc nhiên thực hiện workflow thương mại.',
                'permissions' => array_values(array_unique([
                    ...self::matching(['system.']),
                    'crm.manage-staff', 'crm.manage-staff-availability',
                    'marketing.manage-sending',
                    'sales.manage-bank-accounts',
                    ...self::matching(['marketing.view-', 'crm.view-', 'sales.view-', 'customer-care.view']),
                ])),
                'system' => true,
            ],
            'executive' => [
                'label' => 'Điều hành / Viewer cấp cao',
                'description' => 'Xem xuyên phòng ban và báo cáo, không thực hiện workflow nghiệp vụ.',
                'permissions' => array_values(array_unique([
                    ...self::matching(['marketing.view-', 'crm.view-', 'sales.view-', 'customer-care.view']),
                    'system.view-workforce',
                ])),
                'system' => true,
            ],
            'viewer' => [
                'label' => 'Chỉ xem',
                'description' => 'Quyền đọc dữ liệu nghiệp vụ và báo cáo theo scope được cấp.',
                'permissions' => self::matching(['marketing.view-', 'crm.view-', 'sales.view-', 'customer-care.view']),
                'system' => true,
            ],
            'marketing_manager' => [
                'label' => 'Trưởng chức năng Marketing',
                'description' => 'Quản lý Marketing, Campaign, Landing Page, Email và báo cáo attribution.',
                'permissions' => $marketingManager,
                'system' => true,
            ],
            'marketing_staff' => [
                'label' => 'Nhân viên Marketing',
                'description' => 'Vận hành Campaign, nội dung, Landing Page và dữ liệu Marketing.',
                'permissions' => $marketingStaff,
                'system' => true,
            ],
            'sales_manager' => [
                'label' => 'Trưởng chức năng Kinh doanh',
                'description' => 'Phân phối Lead, quản lý catalog thương mại, duyệt và điều hành bán hàng.',
                'permissions' => $salesManager,
                'system' => true,
            ],
            'sales_staff' => [
                'label' => 'Nhân viên Kinh doanh',
                'description' => 'Tiếp nhận Lead, tư vấn, tạo/gửi/chỉnh sửa báo giá và ghi nhận phản hồi khách hàng.',
                'permissions' => $salesStaff,
                'system' => true,
            ],
            'customer_care_manager' => [
                'label' => 'Trưởng chức năng CSKH',
                'description' => 'Quản lý CSKH hậu bán hàng, phân công và Ticket.',
                'permissions' => self::matching(['customer-care.']),
                'system' => true,
            ],
            'customer_care_staff' => [
                'label' => 'Nhân viên CSKH',
                'description' => 'Chăm sóc khách hàng, Follow-up, Interaction và Ticket.',
                'permissions' => [
                    'customer-care.view', 'customer-care.interact',
                    'customer-care.view-tickets', 'customer-care.manage-tickets',
                ],
                'system' => true,
            ],
            'finance' => [
                'label' => 'Tài chính',
                'description' => 'Đối soát thanh toán, quản lý tài khoản nhận tiền và báo cáo doanh thu.',
                'permissions' => [
                    'sales.view-payments', 'sales.verify-payments', 'sales.view-revenue-reports',
                    'sales.view-quotations', 'sales.view-bank-accounts', 'sales.manage-bank-accounts',
                    'sales.view-price-books',
                ],
                'system' => true,
            ],
        ];
    }

    /** @param array<int,string> $patterns @return array<int,string> */
    private static function matching(array $patterns): array
    {
        return collect(array_keys(self::permissions()))
            ->filter(function (string $permission) use ($patterns): bool {
                foreach ($patterns as $pattern) {
                    if ($permission === $pattern || str_starts_with($permission, $pattern)) return true;
                }
                return false;
            })->values()->all();
    }
}
