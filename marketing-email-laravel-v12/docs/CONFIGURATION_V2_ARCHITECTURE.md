# DTH Business Core V1 - Configuration Module Refactor V2

## 1. Mục tiêu

Configuration V2 tách rõ bốn khái niệm vốn dễ bị trộn lẫn trong hệ thống quản trị doanh nghiệp:

- **Phòng ban (Department):** đơn vị tổ chức mà nhân viên trực thuộc.
- **Chức danh (Job Title):** danh mục master data dùng chung toàn công ty, không thuộc riêng phòng ban.
- **Chức năng nghiệp vụ (Business Function):** năng lực/nghiệp vụ nhân viên được phép tham gia, có thể kiêm nhiệm nhiều chức năng.
- **Vai trò & quyền (RBAC):** quyền thao tác phần mềm, độc lập với phòng ban/chức danh.
- **Tài khoản truy cập (Access Account):** danh tính đăng nhập, có thể được cấp sau khi hồ sơ nhân viên đã tồn tại.

Nguyên tắc cốt lõi: **Cơ cấu tổ chức != năng lực nghiệp vụ != quyền phần mềm.**

## 2. Mô hình dữ liệu V2

```text
Department ------\
                  >---- Staff ---- StaffBusinessFunction (1..n)
Global Job Title-/          \
                             \---- Access Account ---- Roles / Permissions
```

### Chức danh dùng chung

`positions` được chuyển thành danh mục toàn công ty:

- `title` unique toàn hệ thống.
- `code` unique, ổn định.
- `group_key` phân nhóm chức danh.
- `authority_level` mô tả trách nhiệm tổ chức.
- `function_key` tùy chọn, chỉ dùng để gợi ý/phân loại chức danh chuyên môn đặc thù.
- `department_id` được giữ nullable tạm thời vì tương thích dữ liệu cũ, nhưng UI V2 không ghi trường này.

Migration `2026_08_11_200000_globalize_job_titles_for_configuration_v2.php` hợp nhất chức danh lặp, chuyển liên kết nhân viên về bản ghi giữ lại, backfill mã/nhóm/phạm vi và đặt `department_id = null`.

## 3. Điều hướng mới

```text
CẤU HÌNH
├─ Cơ cấu tổ chức
│  ├─ Phòng ban
│  ├─ Chức danh
│  └─ Nhân viên
├─ Truy cập & phân quyền
│  ├─ Tài khoản truy cập
│  └─ Vai trò & quyền
├─ Giao diện & màu sắc
│  └─ Màu nhãn dùng chung
└─ Cài đặt công ty
```

Business Function được khai báo trực tiếp trong hồ sơ Nhân viên để người quản trị không phải nhảy qua một màn hình mapping riêng.

## 4. Quản trị màu

- `Department.color`: nguồn duy nhất cho badge phòng ban.
- `department_function`: màu dùng chung theo Business Function, độc lập màu phòng ban.
- `role` và `audience`: có thể cấu hình từ Màu nhãn dùng chung.
- Business status: khóa theo semantic (`success`, `warning`, `danger`, `info`, `gray`), không cho người dùng đổi tùy ý.
- 22 màu đăng ký dùng chung qua `SystemColorPalette`.

## 5. I18N

Module Cấu hình dùng namespace riêng:

- `lang/vi/configuration.php`
- `lang/en/configuration.php`

Hai file phải có cùng cấu trúc key. UI mới không hard-code nhãn/heading/helper text trong PHP/Blade. `PositionAuthority` cũng đọc label từ namespace Configuration thay vì thuật ngữ phòng ban cũ.

## 6. Phạm vi refactor

V2 chỉ thay đổi Configuration và các thành phần nền cần thiết trực tiếp cho Configuration (model/migration/test/i18n/UI palette). Không thay đổi workflow nghiệp vụ Marketing, Sales, Finance hay Customer Care.
