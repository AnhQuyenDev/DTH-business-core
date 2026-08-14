<?php

return [
    'heading' => 'Ma trận vai trò hệ thống',
    'description' => 'Vai trò hệ thống được tách khỏi Phòng ban và Chức danh. Người dùng nghiệp vụ nhận quyền theo Phòng ban và cấp quyền của Chức danh.',
    'no_department_requirement' => 'Không ràng buộc phòng ban',
    'fixed_role_note' => 'Phiên bản hiện tại dùng bộ vai trò cố định để bảo đảm Gate/Policy nhất quán. Muốn tạo vai trò tùy biến cần chuyển sang RBAC theo permission trong một phase riêng.',
    'summary' => [
        'admin' => 'Quản trị cấu hình, tài khoản, danh mục hệ thống và giám sát toàn hệ thống.',
        'marketing_manager' => 'Quản lý hoạt động Marketing và các quyền phê duyệt/gửi liên quan.',
        'marketing_staff' => 'Thực thi nghiệp vụ Marketing, Landing Page, Campaign và dữ liệu Marketing.',
        'customer_service_manager' => 'Quản lý phân công Customer hậu bán, Ticket hỗ trợ và chất lượng chăm sóc khách hàng.',
        'customer_service_staff' => 'Chăm sóc Customer sau mua, xử lý Ticket, follow-up và ghi nhận tương tác hậu bán.',
        'sales_manager' => 'Quản lý phân phối/đánh giá Lead, Service/Package/Price Book, hoạt động bán hàng và chính sách phê duyệt báo giá.',
        'sales_staff' => 'Tiếp nhận và đánh giá Lead được giao, tư vấn bán hàng, tạo/gửi báo giá và theo dõi khách đến khi thanh toán.',
        'finance_staff' => 'Quản lý tài khoản ngân hàng và xác minh thanh toán.',
        'viewer' => 'Chỉ xem dữ liệu nghiệp vụ được phép, không thực hiện thao tác thay đổi dữ liệu.',
        'executive' => 'Xem xuyên phòng ban phục vụ điều hành; không mặc định thực hiện thao tác nghiệp vụ hằng ngày của từng phòng.',
        'user' => 'Người dùng nội bộ. Quyền nghiệp vụ được suy ra từ một hoặc nhiều chức năng nghiệp vụ được gán cho nhân viên.',
    ],
    'organization_rule_note' => 'Quyền nghiệp vụ = Vai trò hệ thống + Chức năng phòng ban + Cấp quyền theo chức danh. Ví dụ: Người dùng + Kinh doanh + Trưởng phòng (Quản lý) tương đương quyền quản lý Kinh doanh; Người dùng + Kinh doanh + Nhân viên (Thành viên) tương đương quyền nhân viên Kinh doanh.',
];
