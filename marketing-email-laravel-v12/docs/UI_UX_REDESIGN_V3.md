# DTH Business Core — UI/UX Redesign V3

> **Cập nhật:** Các điều chỉnh browser UAT mới nhất về Toggle, modal/dropdown, border field, table row, action bar và sidebar được mô tả tại `docs/UI_UX_REDESIGN_V4_UAT_FIXES.md`. V4 được nạp sau V3 và thay thế các quy tắc trình bày tương ứng.


## 1. Mục tiêu

UI/UX Redesign V3 chuẩn hóa toàn bộ giao diện quản trị Laravel 12 + Filament 3 theo một hệ thống thiết kế thống nhất, có thể áp dụng xuyên suốt các phân hệ Email, Marketing, CRM, Kinh doanh, Tài chính, Chăm sóc khách hàng, Cấu hình và Hệ thống.

Phạm vi của phiên bản này **chỉ là lớp trình bày**. Không thay đổi model, migration, query nghiệp vụ, policy, permission, service, workflow, state machine, validation rule, event, job, route công khai hoặc cách dữ liệu được lưu.

## 2. Kiến trúc triển khai

### 2.1. Lớp thiết kế toàn cục

Lớp giao diện mới nằm tại:

- `resources/views/filament/ui-system-v3.blade.php`
- được nạp bởi `resources/views/filament/ui-system.blade.php`
- `ui-system.blade.php` tiếp tục được đăng ký trong `AdminPanelProvider` qua `PanelsRenderHook::HEAD_END`

Cách triển khai này giữ nguyên theme và component gốc của Filament. V3 chỉ bổ sung design token, CSS nâng cấp và progressive enhancement bằng JavaScript cho các thành phần do Filament/Livewire render động.

### 2.2. Thành phần Toggle có nhãn trạng thái

Thành phần dùng chung:

- `app/Filament/Forms/Components/StateToggle.php`

Mọi Toggle trong khu vực Filament được chuyển sang `StateToggle`, nhưng vẫn dùng chính field boolean và cơ chế hydrate/dehydrate của Filament. Mỗi Toggle khai báo hai nhãn theo ngữ cảnh, ví dụ:

```php
StateToggle::make('is_active')
    ->stateLabels(
        __('uiux.state.active'),
        __('uiux.state.paused'),
    );
```

Nhờ đó trạng thái không còn được truyền đạt chỉ bằng màu sắc. Người dùng luôn thấy rõ “Đang hoạt động”, “Tạm ngừng”, “Đang nhận khách hàng”, “Không nhận khách hàng”, “Bắt buộc”, “Không bắt buộc”… tùy nghiệp vụ.

### 2.3. Helper text dạng tooltip

Các `helperText()` trong toàn bộ Filament được chuyển sang:

```php
->hintIcon('heroicon-m-question-mark-circle', __('...'))
```

Thông tin hướng dẫn được hiển thị khi rê chuột hoặc focus vào biểu tượng `?`, giúp form gọn hơn mà vẫn hỗ trợ người dùng. Lớp V3 còn có fallback tự động chuyển helper text phát sinh trong tương lai thành tooltip nếu developer chưa kịp refactor.

### 2.4. Icon cho hành động

Toàn bộ action chuẩn và custom action trong Resources, Pages, Relation Managers, Widgets và Blade button đều có icon. Quy ước chính:

| Hành động | Icon |
|---|---|
| Tạo / thêm | `plus-circle` |
| Chỉnh sửa | `pencil-square` |
| Xem | `eye` |
| Xóa | `trash` |
| Gửi | `paper-airplane` |
| Duyệt | `check-circle` |
| Từ chối | `x-circle` |
| Quay lại | `arrow-left` |
| Xuất dữ liệu | `arrow-down-tray` |
| Liên kết / gỡ liên kết | `link` / `link-slash` |

JavaScript fallback chỉ bổ sung icon cho button Filament được tạo động bởi package hoặc Livewire mà chưa có icon, không thay đổi hành vi click.

## 3. Hệ thống thiết kế

### 3.1. Design token

V3 sử dụng token cho:

- canvas, surface, border và typography ở light/dark mode;
- bán kính bo góc theo ba cấp;
- shadow nhẹ, có chiều sâu nhưng không làm mất phong cách Filament;
- transition ngắn và hỗ trợ `prefers-reduced-motion`;
- accent theo phân hệ.

### 3.2. Màu nhận diện theo phân hệ

| Phân hệ | Accent |
|---|---|
| Email | Sky/Cyan |
| Marketing | Purple |
| CRM | Blue |
| Kinh doanh | Amber |
| Tài chính | Emerald |
| Chăm sóc khách hàng | Rose |
| Cấu hình | Amber |
| Hệ thống | Slate |

Accent chỉ dùng cho trạng thái active, icon, rail, focus và điểm nhấn. Màu semantic của Filament như success, warning, danger, info vẫn được giữ nguyên.

### 3.3. Khả năng đọc

- Text chính và text phụ có cấp độ tương phản rõ ràng ở cả light/dark mode.
- Input, select và textarea có border rõ, nền khác biệt với canvas và focus ring theo accent.
- Read-only/disabled field dùng border nét đứt và màu riêng, tránh nhầm với field có thể nhập.
- Validation error, required mark và trạng thái nguy hiểm vẫn dùng màu semantic.
- Focus bằng bàn phím luôn có outline dễ nhìn.

## 4. Sidebar

Sidebar dùng chung một cấu trúc cho tất cả phân hệ:

- navigation group có icon, nhãn chữ hoa nhỏ và vùng tương tác rõ;
- group đang hoạt động được đánh dấu bằng accent của phân hệ;
- item có icon tương ứng, rail dọc cho cấp con và active bar bên trái;
- hover/focus không gây dịch layout;
- vẫn tương thích chế độ thu gọn sidebar của Filament;
- phân hệ hiện tại được nhận diện lại sau mỗi lần `livewire:navigated`.

Không thay đổi navigation group, URL, permission hoặc điều kiện hiển thị menu hiện tại.

## 5. Form CRUD

### 5.1. Cấu trúc

- Mỗi nhóm dữ liệu dùng `Section` với icon và header rõ ràng.
- Khoảng cách giữa field, section và action được chuẩn hóa.
- Layout responsive: một cột trên mobile, hai hoặc ba cột khi đủ không gian.
- Field dài như mô tả, nội dung, JSON, subject hoặc dữ liệu biểu mẫu dùng toàn chiều rộng.
- Repeater, checkbox list, file upload, rich editor và tabs có cùng visual grammar.

Các form phẳng còn lại đã được tổ chức lại, nổi bật gồm:

- Contact: nhóm “Hồ sơ liên hệ”.
- Landing Page Submission: nhóm “Dữ liệu biểu mẫu đã gửi”.
- Support Ticket: tách “Người yêu cầu & khách hàng”, “Nội dung Ticket” và “Ưu tiên, trạng thái & phân công”.

### 5.2. Action bar

Action bar của form trang được đánh dấu tự động và sticky trên desktop/mobile, giúp người dùng luôn truy cập được nút Lưu/Tạo/Quay lại khi form dài. Action footer trong modal không bị áp dụng sticky.

## 6. Bảng dữ liệu

Bảng Filament được chuẩn hóa:

- table container là surface độc lập;
- toolbar và bộ lọc tách rõ với dữ liệu;
- header có typography rõ và sticky khi phù hợp;
- zebra nhẹ, hover row có accent rail;
- badge có cùng kích thước và độ đậm;
- row action trực quan bằng icon;
- pagination, empty state, search và horizontal scroll được đồng bộ;
- màu semantic của trạng thái nghiệp vụ không bị ghi đè.

## 7. Modal, notification và empty state

- Modal có overlay blur, header/footer tách lớp và khoảng cách nhất quán.
- Dropdown và notification dùng cùng radius, border và shadow với toàn hệ thống.
- Empty state có điểm nhấn nhẹ theo phân hệ và CTA có icon.
- Không thay đổi event đóng/mở modal hoặc action callback.

## 8. Đồng bộ ngôn ngữ

- `lang/en.json` và `lang/vi.json` giữ cùng 2.404 key.
- Các file PHP translation `configuration.php`, `finance.php`, `uiux.php`, `validation.php` được kiểm tra parity theo key.
- Nhãn trạng thái mới và tiêu đề section mới được bổ sung đồng thời cho tiếng Anh và tiếng Việt.
- Toàn bộ literal translation key được sử dụng trong `app/Filament` và `resources/views/filament` được đối chiếu bằng script audit.
- Các nhóm key động như trạng thái, enum, analytics và card cấu hình cũng được kiểm tra tồn tại ở cả hai ngôn ngữ.
- Translation key của package Filament có namespace `::` vẫn được package cung cấp và không bị sao chép vào local language files.

## 9. Kiểm tra tự động

Chạy audit không cần Composer/vendor:

```bash
php scripts/audit-ui-redesign.php
```

Audit kiểm tra:

- parity key giữa tiếng Anh và tiếng Việt;
- literal translation key đang được sử dụng;
- không còn `helperText()` trong Filament;
- không còn raw `Toggle::make()`;
- mọi `StateToggle` có cặp nhãn trạng thái;
- mọi `Section` có icon;
- mọi Filament action có icon;
- mọi Blade button có icon;
- mọi navigation item có icon;
- lớp UI V3 được nạp đúng.

Kiểm tra syntax PHP:

```bash
find app bootstrap config database lang resources routes scripts tests \
    -name '*.php' -print0 | xargs -0 -n1 php -l
```

## 10. Checklist UAT trình duyệt

Sau khi cài dependency và chạy ứng dụng, kiểm tra ở cả tiếng Việt/tiếng Anh và light/dark mode:

1. Mở lần lượt tám navigation group; kiểm tra active group, active item và icon.
2. Mở trang danh sách của mỗi phân hệ; kiểm tra search, filter, sort, pagination, empty state và row action.
3. Mở Create/Edit/View của các Resource chính; kiểm tra responsive layout ở 1440 px, 1024 px và mobile.
4. Hover/focus mọi dấu `?`; kiểm tra tooltip không che field đang nhập.
5. Bật/tắt từng Toggle; kiểm tra text trạng thái đổi đúng ngữ cảnh và dữ liệu vẫn lưu như trước.
6. Mở action modal Create/Edit/Delete/Attach/Detach; kiểm tra icon, action footer và keyboard focus.
7. Kiểm tra các Dashboard, báo cáo, Customer Care workspace, Company Settings và RBAC permission matrix.
8. Chuyển ngôn ngữ; kiểm tra không xuất hiện translation key thô.
9. Kiểm tra quyền của Super Admin, Admin và nhân viên phòng ban; menu/action phải giữ đúng visibility cũ.
10. Chạy Golden Path V1 để xác nhận UI không ảnh hưởng workflow nghiệp vụ.

## 11. Nguyên tắc mở rộng

Khi thêm Resource/Page mới:

- dùng navigation icon và section icon có ý nghĩa;
- dùng `StateToggle` cho boolean có thể thao tác;
- dùng `hintIcon()` thay cho đoạn helper text dài dưới field;
- mọi action phải có icon;
- mọi text giao diện phải vào cả EN/VI;
- chạy `php scripts/audit-ui-redesign.php` trước khi commit.
