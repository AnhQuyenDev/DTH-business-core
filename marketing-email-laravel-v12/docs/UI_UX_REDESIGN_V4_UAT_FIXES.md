# UI/UX Redesign V4 — Browser UAT Fixes

## Mục tiêu

V4 là lớp hoàn thiện sau khi kiểm tra trực tiếp UI V3 trên trình duyệt. Phạm vi chỉ gồm giao diện và trải nghiệm thao tác của Filament; không thay đổi model, service, policy, migration, route, validation, callback Livewire hoặc quy trình nghiệp vụ.

Lớp sửa chung được đặt tại:

```text
resources/views/filament/ui-system-v4-fixes.blade.php
```

và được nạp sau V3 trong:

```text
resources/views/filament/ui-system.blade.php
```

## Các lỗi đã xử lý

### 1. Toggle bị lặp trạng thái

Cơ chế nâng cấp Toggle nay phân biệt giữa:

- nhãn gốc vốn đã chính là trạng thái, ví dụ “Đang hoạt động”;
- nhãn ngữ cảnh, ví dụ “Có thể xem”, cần giữ lại bên cạnh trạng thái “Được phép/Không được phép”.

Khi nhãn gốc trùng với một trong hai nhãn trạng thái, nhãn gốc chỉ được giữ cho trình đọc màn hình và UI chỉ hiển thị một nhãn trạng thái động. Khi nhãn gốc là ngữ cảnh, UI giữ nhãn ngữ cảnh và hiển thị thêm đúng một trạng thái động.

Field boolean, tên field, giá trị true/false và cơ chế lưu dữ liệu không thay đổi.

### 2. Dropdown tài khoản, panel thông báo và modal quá trong suốt

Các bề mặt sau được chuyển sang nền đặc, có border và shadow rõ ràng:

- user/account dropdown;
- database notification drawer;
- action modal và form modal;
- global search result panel;
- modal header, content và footer.

Overlay phía sau modal được tăng độ che phủ để nội dung bên dưới không xuyên qua và làm giảm khả năng đọc.

### 3. Ô nhập liệu thiếu đường viền

Đường viền được tăng tương phản cho:

- TextInput, Select và Textarea;
- Rich Editor, Markdown Editor và Tags Input;
- Key Value, File Upload, Builder và Repeater;
- các field trong page, section, modal và filter table.

Các trạng thái hover, focus, readonly và disabled có cách thể hiện riêng. Focus vẫn sử dụng accent theo module.

### 4. Bảng dữ liệu khó phân biệt hàng

Bảng Filament sử dụng đồng thời:

- zebra row rõ hơn;
- đường kẻ ngang giữa các hàng;
- nền header đặc;
- hover row có nền và accent rail.

Màu được cấu hình riêng cho light và dark mode để vẫn bảo đảm độ tương phản.

### 5. Action bar chèn lên Color Picker

Action bar của form page không còn nổi đè lên nội dung. Bar được đặt trong luồng tài liệu, toàn chiều rộng, có nền và border riêng. Footer của modal vẫn sử dụng cơ chế gốc của Filament.

### 6. Sidebar phải cuộn lại sau mỗi lần chuyển trang

Vị trí cuộn của sidebar được lưu trong `sessionStorage` theo tab trình duyệt và phục hồi khi:

- tải lại toàn bộ trang;
- chuyển trang qua Livewire navigation;
- chọn một menu ở vị trí sâu trong sidebar.

Dữ liệu lưu chỉ là vị trí cuộn giao diện, tự hết hiệu lực sau một giờ và không liên quan tới dữ liệu nghiệp vụ.

### 7. Đường nối sidebar bị gãy

Rail dọc và grouped-border mặc định được loại bỏ. Menu con được đặt trong một nested surface bo góc, có border và khoảng thụt nhất quán. Active item dùng accent bar riêng nên không bị gãy khi hover hoặc khi cấp menu thay đổi.

### 8. Nút Nhập HTML không rõ ràng

Ba action Nhập HTML tại Email Template, Form Template và Landing Page được gắn class trình bày dùng chung:

```text
dth-import-html-action
```

Nút có icon sẵn, border, nền secondary, shadow và hover state rõ ràng ở cả light/dark mode.

## Các file giao diện chính đã thay đổi

```text
resources/views/filament/ui-system-v3.blade.php
resources/views/filament/ui-system-v4-fixes.blade.php
resources/views/filament/ui-system.blade.php
app/Filament/Resources/EmailTemplateResource/Pages/ListEmailTemplates.php
app/Filament/Resources/FormTemplateResource/Pages/ListFormTemplates.php
app/Filament/Resources/LandingPageResource/Pages/ListLandingPages.php
scripts/audit-ui-redesign.php
tests/Feature/Ui/UiUxV4RegressionTest.php
```

## Kiểm tra sau khi cài dependency

```bash
composer install
npm ci
npm run build
php artisan optimize:clear
php artisan test --filter=UiUxV4RegressionTest
php artisan test
```

## Checklist UAT khuyến nghị

1. Bật/tắt Toggle ở Department, Position, Staff, User, Company Settings và Email Template Category; mỗi Toggle chỉ được có một trạng thái động.
2. Mở user menu và notification drawer; nội dung bên dưới không được nhìn xuyên qua.
3. Mở Create/Edit modal của Department và các modal Nhập HTML; nền modal phải đặc.
4. Kiểm tra input/select/textarea ở Email, Marketing, CRM, Sales, Finance, Customer Care và Configuration.
5. Kiểm tra zebra row, đường kẻ và hover ở các bảng danh sách.
6. Cuộn sidebar xuống cuối, chọn menu, sau khi trang mới mở sidebar phải giữ vị trí gần menu vừa chọn.
7. Hover menu cấp cha/cấp con; nested surface không được xuất hiện đường nối gãy.
8. Mở Color Picker tại Email Template Category; action bar không được đè lên bảng màu.
9. Kiểm tra ba nút Nhập HTML có border và icon rõ ràng.
10. Lặp lại ở tiếng Việt/tiếng Anh và light/dark mode.
