# UI/UX System Refactor – Round 2 (2026-08-09)

## Mục tiêu

Đợt nâng cấp này tập trung vào tính nhất quán vận hành trên toàn bộ Laravel 12 + Filament 3 mà không thay đổi Golden Path nghiệp vụ đã kiểm thử. Trọng tâm là ngôn ngữ, form, badge, audit log, slug, form builder, dashboard theo vai trò, báo cáo và nhận diện thương hiệu.

## Kiến trúc điều hướng

Hệ thống tiếp tục dùng 8 phân hệ: Email, Marketing, CRM, Kinh doanh, Tài chính, Chăm sóc khách hàng, Cấu hình và Hệ thống. Báo cáo chuyên môn không chiếm thêm menu chính; chúng được mở từ dashboard phù hợp với vai trò.

## Dashboard theo vai trò

- Admin / Executive / Viewer: Bảng điều khiển doanh nghiệp, funnel, thực thu, cảnh báo, báo cáo quản trị và audit.
- Marketing Manager: chiến dịch, Landing Page, nguồn Lead, doanh thu quy nguồn và báo cáo Email/Revenue.
- Marketing Staff: dữ liệu công việc do chính người dùng phụ trách.
- CSKH Manager: Lead mới/chưa gán, SLA/follow-up, qualification, tải nhân viên.
- CSKH Staff: Lead của tôi, hàng chờ ưu tiên và lịch follow-up.
- Sales Manager: pipeline toàn phòng, báo giá, thực thu và báo cáo.
- Sales Staff: pipeline/cơ hội/báo giá thuộc phạm vi của tôi.
- Finance: đối soát, thực thu, biên lai và báo cáo doanh thu.
- Nhân viên khác: workspace cá nhân và thông tin công việc; lịch CSKH chỉ hiển thị khi đúng chức năng.

Dashboard dùng `DashboardScopeService` để tránh lộ dữ liệu ngoài phạm vi role/department/staff.

## Nhật ký hệ thống

Trang Nhật ký hoạt động được chuyển từ log kỹ thuật sang log nghiệp vụ:

- Bảng chính chỉ hiển thị: thời gian, người thực hiện, phân hệ, hoạt động dễ hiểu và mô tả.
- Không đưa Model class, object ID, IP address lên bảng chính.
- Chi tiết thay đổi được mở bằng modal và che trường nhạy cảm.
- Hoạt động không có bản dịch không còn hiện raw key; dùng nhãn nghiệp vụ an toàn.

## Ngôn ngữ

Các literal translation key trong `app/Filament` và `resources/views/filament` được kiểm tra cho cả `vi` và `en`. Có test `UiTranslationCoverageTest` để ngăn `section.xxx`, `field.xxx`, `action.xxx` lọt ra UI trong các component dùng key tĩnh.

## Form design

- Form lớn chia thành Section theo ý nghĩa nghiệp vụ.
- Layout responsive: mobile 1 cột, desktop 2–3 cột khi phù hợp.
- Field ngắn không bị kéo full-width vô lý; textarea/nội dung dài dùng full-width khi cần.
- Select dùng cho dữ liệu có tập giá trị; number/date/toggle dùng đúng kiểu dữ liệu.
- `sort_order` bị ẩn khỏi các form CRUD thông thường.

## Slug

Slug quan trọng khi nó là một phần của URL công khai/stable identifier.

- Landing Page: slug vẫn hiển thị vì ảnh hưởng URL public/UTM/share link; tự sinh khi nhập tên và vẫn cho phép chỉnh trước khi publish.
- Contact List, Segment, Tag, Service, Form Template, Email Template Category, Marketing Campaign: slug là kỹ thuật nội bộ nên ẩn, model tự sinh khi thiếu.
- Email Template: không dùng slug vì không có public route cần slug.

## Form Template Builder

- Bỏ field `position`/“chức danh” không phù hợp.
- Bỏ `validation_rules` thô khỏi UI builder.
- `field_key` tự sinh từ label và ẩn khỏi người dùng.
- `sort_order` chỉ được giữ tại danh sách field của Form Template vì nó có ý nghĩa bố cục.
- Hỗ trợ cả nhập thứ tự và drag/drop (`orderColumn('sort_order')`).

## Badge

- Status enum dùng `BadgePalette` thống nhất.
- Cá nhân/Doanh nghiệp dùng cùng một mapping màu trên hệ thống.
- Role và Department Function có palette dùng chung.
- Admin có `Cấu hình > Màu badge` để tùy chỉnh mà không sửa từng Resource.
- Department cụ thể vẫn dùng màu của chính Department.

## Báo cáo

- Campaign Report và Revenue Report được ẩn khỏi navigation chính và mở từ dashboard đúng vai trò.
- Dashboard Manager/Admin có biểu đồ/KPI và xuất CSV.
- Campaign Report và Revenue Report có Export CSV.
- Revenue Report tiếp tục hỗ trợ attribution theo Campaign, Email Campaign, Landing Page, UTM, Service, Package, Sales, Customer.

## Brand

Header Filament dùng Company Settings:

- Tên công ty.
- Logo công ty nếu có.
- Fallback initials khi chưa cấu hình logo.

## Không thay đổi nghiệp vụ

Đợt này không thay migration/schema nghiệp vụ và không thay state machine Golden Path. Các thay đổi Model chỉ liên quan tự sinh slug và UI support. Không cần queue worker hay npm build riêng cho patch này.

## Checklist kiểm thử sau áp dụng

1. Đăng nhập Admin: logo/tên công ty, Dashboard, Audit Log, Badge Color.
2. Đăng nhập Marketing Manager/Staff: dashboard và phạm vi dữ liệu.
3. Đăng nhập CSKH Manager/Staff: dashboard, Lead scope, schedule.
4. Đăng nhập Sales Manager/Staff: dashboard và pipeline scope.
5. Đăng nhập Finance: dashboard, Payment Tracking, Revenue Report.
6. Form Template: label -> hidden field key, drag/drop, sort order, không còn position/validation raw.
7. Landing Page: slug tự sinh và vẫn chỉnh được.
8. Email Template: không có slug.
9. Cá nhân/Doanh nghiệp: màu nhất quán.
10. Đổi ngôn ngữ VI/EN và rà các Create/Edit/View/List chính.
