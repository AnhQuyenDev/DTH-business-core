# DTH Account Management

Module **Tài khoản & Phân quyền** cho DTH Business Core.

## Chức năng

- Dashboard tài khoản và sức khỏe bảo mật.
- Quản lý người dùng nội bộ.
- Role / Permission theo module và thao tác.
- Nhóm & đơn vị, cấu trúc nhóm cha/con.
- Liên kết tùy chọn với Employee của Human Resource, không phụ thuộc cứng.
- Lời mời qua email và trang kích hoạt tài khoản.
- Phiên đăng nhập và thu hồi session.
- Audit log cho thao tác model, login/logout/login-failed.
- Khóa tài khoản tạm sau nhiều lần đăng nhập sai.
- Cấu hình enforcement và chính sách bảo mật.
- Laravel Gate bridge cho CRM, Commercial, Marketing, Human Resource; bridge model-level cho Email.
- Data scope ở mức Role: own / team / all, sẵn sàng cho các module dùng khi lọc dữ liệu.

## Màu giao diện

Module dùng **Graphite / Slate** (`#334155`) để không trùng Email, Marketing, Commercial, CRM hoặc Human Resource. Save/Cancel dùng cùng pattern action của Human Resource: class riêng + selector specificity cao.

Xem `MANUAL_INTEGRATION.md` để gắn module vào host.
