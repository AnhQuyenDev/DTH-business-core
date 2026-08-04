# Hướng dẫn sửa toàn bộ luồng Import Landing Page và Form Template

> Repository: `https://github.com/AnhQuyenDev/DTH-business-core.git`  
> Branch được rà soát: `master`  
> Thư mục ứng dụng: `marketing-email-laravel-v12/`  
> Ngày rà soát: `2026-08-04`

---

## 1. Mục tiêu nghiệp vụ đã chốt

Hệ thống phải hoạt động theo nguyên tắc sau:

1. Import Landing Page chỉ import nội dung và giao diện Landing Page.
2. Nếu HTML Landing Page có form cũ thì loại bỏ form và toàn bộ vùng giao diện form cũ.
3. Import Landing Page không tự tạo Form Template.
4. Form Template được import riêng và chỉ thuộc một trong hai loại:
   - `personal`: khách hàng cá nhân.
   - `business`: khách hàng doanh nghiệp.
5. Form Template được tái sử dụng cho nhiều Landing Page.
6. Mỗi Landing Page chỉ được gắn tối đa:
   - Một Form cá nhân.
   - Một Form doanh nghiệp.
7. Hai form được render dưới dạng hai tab tại cuối Landing Page.
8. Màu form lấy từ `LandingPage.theme_tokens`, không lấy cố định từ Form Template.
9. Các trường trong bảng `form_fields` là nguồn dữ liệu chính để render và validate form.
10. Khi sửa label, placeholder, options, required hoặc mapping trong Filament, preview và trang public phải thay đổi theo.

Luồng mong muốn:

```text
Import Landing Page HTML
    -> loại bỏ vùng form cũ
    -> giữ nguyên HTML/CSS phần nội dung
    -> phát hiện theme
    -> lưu Landing Page ở Draft

Import Form HTML
    -> chuẩn hóa name/id cho input
    -> đọc field theo thứ tự DOM
    -> tạo FormTemplate
    -> tạo FormField
    -> chuyển form HTML sang shell chứa {{fields}}
    -> preview bằng renderer của hệ thống

Edit Landing Page
    -> chọn Form personal
    -> chọn Form business
    -> chỉnh theme_tokens
    -> lưu landing_page_forms

Render public
    -> render field từ database
    -> dùng theme_tokens
    -> chèn vùng tab form trước </body>
```

---

## 2. Kết quả rà soát source hiện tại

### 2.1. Những phần đã có trong repo

Các phần sau đã tồn tại:

- `app/Services/Marketing/LandingPageImportService.php`
- `app/Services/Marketing/LandingPageThemeService.php`
- `app/Http/Controllers/Marketing/Admin/FormTemplatePreviewController.php`
- `LandingPageResource.php` đã có:
  - Hai tab chọn Form cá nhân và doanh nghiệp.
  - Section `Tone màu Form` với `ColorPicker`.
- `LandingPage.php` đã có:
  - `theme_tokens` trong `$fillable`.
  - `theme_tokens => array` trong `casts()`.
- `CreateLandingPage.php` và `EditLandingPage.php` đã có logic đồng bộ `landing_page_forms`.
- `ListLandingPages.php` đã gọi `LandingPageImportService`.
- `ListFormTemplates.php` đã gọi `FormTemplateImportService`.
- `LandingPageRenderService.php` đã có:
  - Render hai tab.
  - Chèn form cuối trang.
  - CSS variables `--lp-*`.
  - Preview Form Template.

### 2.2. Những lỗi còn tồn tại

#### Lỗi A — Chưa có migration `theme_tokens`

Model và Filament form đã dùng `theme_tokens`, nhưng trong thư mục `database/migrations` chưa có migration thêm cột này vào `landing_pages`.

Hậu quả:

- Database mới chạy từ repo có thể báo `Unknown column theme_tokens`.
- ColorPicker có thể xuất hiện nhưng không lưu được.
- Preview fallback về màu mặc định.

#### Lỗi B — ColorPicker nằm ở Landing Page, không nằm ở Form Template

ColorPicker hiện nằm tại:

```text
app/Filament/Resources/LandingPageResource.php
```

Không nằm tại:

```text
app/Filament/Resources/FormTemplateResource.php
```

Đây là đúng kiến trúc đã chốt: Landing Page quyết định tone màu. Tuy nhiên UI cần ghi chú rõ để user không tìm ColorPicker ở Edit Form Template.

#### Lỗi C — Repeater FormField thu gọn nhưng không có tiêu đề

Trong `FormTemplateResource.php`, Repeater đang có:

```php
->collapsed()
```

nhưng không có:

```php
->itemLabel(...)
```

Hậu quả là các hàng thu gọn hiển thị thành thanh trống, dù dữ liệu có thể đã được import.

#### Lỗi D — HTML form thiếu `name` và `id`

File form mẫu hiện tại có dạng:

```html
<input type="text" placeholder="Nhập họ và tên..." required>
```

không có:

```html
name="full_name"
id="full_name"
```

Parser hiện fallback sang:

```text
field_0
field_1
field_2
...
```

Nghiêm trọng hơn: nếu HTML hardcode tiếp tục được render, browser không submit giá trị của input không có `name`.

#### Lỗi E — FormField đã sửa nhưng HTML preview có thể vẫn dùng field hardcode

`FormTemplateImportService` hiện lưu form gốc vào `html_body`.

`LandingPageRenderService::renderResolvedFormTemplate()` chỉ thay `{{fields}}` nếu placeholder đó tồn tại. Nếu form import vẫn chứa input hardcode, việc sửa FormField trong Filament không chắc thay đổi HTML hiển thị.

Nguồn dữ liệu bị chia làm hai:

```text
form_templates.html_body
và
form_fields
```

Phải chuyển sang một nguồn duy nhất:

```text
form_fields = source of truth
html_body = shell layout có {{fields}}
```

#### Lỗi F — Parser không duyệt field theo thứ tự DOM

Parser hiện duyệt lần lượt:

1. Tất cả `input`.
2. Tất cả `textarea`.
3. Tất cả `select`.

Do đó thứ tự field có thể sai so với HTML.

#### Lỗi G — Theme detector chưa giải quyết `var(--bg)`

Landing Page VPS khai báo:

```css
:root {
    --primary: #7c3aed;
    --secondary: #4f46e5;
    --bg: #f5f3ff;
}

body {
    background: var(--bg);
}
```

Theme service hiện đọc được `--primary`, nhưng regex background chỉ đọc màu hex trực tiếp. Nó không resolve `var(--bg)`, nên background form có thể vẫn dùng màu mặc định.

#### Lỗi H — Chỉ xóa thẻ `<form>`, chưa xóa vùng form cũ

`prepareImportedLandingPageHtml()` hiện chỉ xóa:

```html
<form>...</form>
```

Nhưng Landing Page VPS còn giữ lại:

```html
<section class="form-section">
    <h2>Triển Khai Máy Chủ Ngay</h2>
    <div class="tabs">...</div>
</section>
```

Hậu quả:

- Tiêu đề form cũ còn lại.
- Tab cũ còn lại nhưng không hoạt động.
- Renderer lại chèn form mới ở cuối trang.
- Có thể xuất hiện hai khu vực form hoặc giao diện thừa.

#### Lỗi I — Form Preview route chưa có trong `routes/web.php`

Controller và action preview đã tồn tại, nhưng branch `master` hiện chưa khai báo route:

```php
marketing.form-templates.preview
```

Máy local có thể đang có thay đổi chưa push, nhưng source sạch từ repo sẽ lỗi `Route not defined`.

#### Lỗi J — Redirect sau submit vẫn dùng quan hệ form cũ

Trong `LandingPageController::submit()` hiện đang dùng:

```php
$formTemplate = $landingPage->formTemplate;
```

Đây là quan hệ legacy `landing_form_template_id` và không xác định đúng tab personal/business vừa submit.

Phải dùng:

```php
$formTemplate = $submission->formTemplate;
```

---

# 3. Danh sách file cần sửa

| Mức | File | Nội dung |
|---|---|---|
| P0 | `database/migrations/*_add_theme_tokens_to_landing_pages_table.php` | Tạo cột theme JSON |
| P0 | `app/Services/Marketing/FormTemplateImportService.php` | Chuẩn hóa field, field key, thứ tự DOM, chuyển HTML sang `{{fields}}` |
| P0 | `app/Filament/Resources/FormTemplateResource.php` | Hiển thị tên Repeater item |
| P0 | `app/Services/Marketing/LandingPageThemeService.php` | Resolve CSS variables |
| P0 | `app/Services/Marketing/LandingPageRenderService.php` | Xóa vùng form cũ, render DB fields, theme hóa đúng |
| P0 | `routes/web.php` | Thêm route preview form |
| P0 | `app/Http/Controllers/Marketing/Public/LandingPageController.php` | Dùng FormTemplate của submission |
| P1 | `app/Filament/Resources/LandingPageResource.php` | Ghi chú rõ ColorPicker thuộc Landing Page |
| P1 | `app/Filament/Resources/FormTemplateResource.php` | Thêm preview theo Landing Page |
| P1 | `tests/Unit/...` và `tests/Feature/...` | Test regression |

---

# 4. Sửa migration `theme_tokens`

## 4.1. Tạo migration

Chạy trong thư mục `marketing-email-laravel-v12`:

```bash
php artisan make:migration add_theme_tokens_to_landing_pages_table --table=landing_pages
```

## 4.2. Nội dung migration

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table): void {
            if (! Schema::hasColumn('landing_pages', 'theme_tokens')) {
                $table->json('theme_tokens')
                    ->nullable()
                    ->after('css_body');
            }
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table): void {
            if (Schema::hasColumn('landing_pages', 'theme_tokens')) {
                $table->dropColumn('theme_tokens');
            }
        });
    }
};
```

Lưu ý: không sửa migration cũ đã chạy trên production. Luôn tạo migration mới.

---

# 5. Sửa Repeater bị trống tiêu đề

File:

```text
app/Filament/Resources/FormTemplateResource.php
```

Tìm:

```php
Repeater::make('fields')
```

Thêm `itemLabel()` sau `collapsed()`:

```php
Repeater::make('fields')
    ->relationship('fields')
    ->label(__('field.fields'))
    ->addActionLabel(__('action.add_form'))
    ->defaultItems(0)
    ->reorderableWithButtons()
    ->collapsed()
    ->itemLabel(function (array $state): string {
        $label = trim((string) ($state['label'] ?? ''));
        $key = trim((string) ($state['field_key'] ?? ''));
        $type = trim((string) ($state['field_type'] ?? ''));

        if ($label !== '' && $key !== '') {
            return $label.' — '.$key.($type !== '' ? ' ['.$type.']' : '');
        }

        if ($label !== '') {
            return $label;
        }

        if ($key !== '') {
            return $key;
        }

        return 'Trường biểu mẫu';
    })
    ->schema([
        // Giữ nguyên schema hiện tại
    ])
```

Kết quả mong đợi:

```text
Họ và Tên — full_name [text]
Số điện thoại — phone [phone]
Email cá nhân — email [email]
Chọn gói dịch vụ — service_package [select]
```

---

# 6. Viết lại `FormTemplateImportService`

File:

```text
app/Services/Marketing/FormTemplateImportService.php
```

## 6.1. Yêu cầu bắt buộc

Service phải:

1. Chỉ chấp nhận đúng một thẻ `<form>`.
2. Sanitize `onclick`, `onsubmit`, script không tin cậy.
3. Duyệt field theo thứ tự xuất hiện trong DOM.
4. Nếu field thiếu `name`, tự sinh từ label hoặc placeholder.
5. Nếu field thiếu `id`, tự gắn `id` bằng field key.
6. Gắn `for` cho label.
7. Bảo đảm field key không trùng.
8. Tạo `FormField` theo dữ liệu đã normalize.
9. Chuyển HTML form hardcode sang shell có `{{fields}}`.
10. Không để HTML hardcode trở thành nguồn field thứ hai.

## 6.2. Thêm imports

```php
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;
```

## 6.3. Thay luồng import

Thay phần xử lý chính bằng cấu trúc sau:

```php
public function import(
    array $data,
    string $sourceHtml,
    ?int $userId
): FormTemplate {
    $audienceType = (string) ($data['audience_type'] ?? '');

    if (! in_array($audienceType, ['personal', 'business'], true)) {
        throw new RuntimeException(
            'Form Template chỉ được thuộc loại Cá nhân hoặc Doanh nghiệp.'
        );
    }

    $prepared = $this->prepareImportedForm(
        $sourceHtml,
        $audienceType
    );

    return DB::transaction(function () use (
        $data,
        $audienceType,
        $prepared,
        $userId
    ): FormTemplate {
        $template = FormTemplate::query()->create([
            'name' => (string) $data['name'],
            'slug' => (string) $data['slug'],
            'audience_type' => $audienceType,
            'status' => 'draft',
            'submit_button_text' =>
                $prepared['submit_button_text']
                ?: ($data['submit_button_text'] ?? 'Gửi thông tin'),
            'html_body' => $prepared['html_body'],
            'created_by' => $userId,
        ]);

        $template->fields()->createMany($prepared['fields']);

        return $template->fresh('fields');
    });
}
```

## 6.4. Thêm method `prepareImportedForm`

```php
private function prepareImportedForm(
    string $sourceHtml,
    string $audienceType
): array {
    $sanitized = $this->renderer->sanitizeImportedHtml(
        $sourceHtml,
        false
    );

    $dom = new DOMDocument('1.0', 'UTF-8');

    libxml_use_internal_errors(true);

    $loaded = $dom->loadHTML(
        '<?xml encoding="UTF-8">'.$sanitized,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    libxml_clear_errors();

    if (! $loaded) {
        throw new RuntimeException('Không thể phân tích HTML Form.');
    }

    $xpath = new DOMXPath($dom);
    $forms = $xpath->query('//form');

    if ($forms === false || $forms->length === 0) {
        throw new RuntimeException('File HTML không chứa thẻ <form>.');
    }

    if ($forms->length > 1) {
        throw new RuntimeException(
            'Mỗi Form Template chỉ được chứa một thẻ <form>.'
        );
    }

    /** @var DOMElement $form */
    $form = $forms->item(0);

    $fields = $this->normalizeAndExtractFields(
        $dom,
        $form,
        $audienceType
    );

    if ($fields === []) {
        throw new RuntimeException(
            'Không tìm thấy input, select hoặc textarea hợp lệ.'
        );
    }

    $submitText = $this->extractSubmitButtonText($form);

    $formHtml = $dom->saveHTML($form) ?: '';
    $formHtml = $this->addSystemFormClass($formHtml);

    // Quan trọng: thay input hardcode bằng {{fields}}.
    $formHtml = LandingPageRenderService::sanitizeHtmlBody($formHtml);

    // Không lưu CSS toàn trang của file form vào public Landing Page.
    // Hệ thống dùng formThemeCss() + theme_tokens để đồng bộ tone.
    $htmlBody = trim(
        '<div class="lp-form-template">'.$formHtml.'</div>'
    );

    return [
        'html_body' => $htmlBody,
        'fields' => $fields,
        'submit_button_text' => $submitText,
    ];
}
```

## 6.5. Duyệt field theo thứ tự DOM

Không tiếp tục dùng ba vòng lặp tách biệt input/textarea/select.

Thêm method:

```php
private function normalizeAndExtractFields(
    DOMDocument $dom,
    DOMElement $form,
    string $audienceType
): array {
    $xpath = new DOMXPath($dom);

    $nodes = $xpath->query(
        './/input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="reset")]'
        .' | .//textarea | .//select',
        $form
    );

    if ($nodes === false) {
        return [];
    }

    $fields = [];
    $usedKeys = [];
    $position = 0;

    foreach ($nodes as $node) {
        if (! $node instanceof DOMElement) {
            continue;
        }

        $type = strtolower($node->getAttribute('type') ?: $node->tagName);
        $fieldType = $this->resolveFieldType($node, $type);
        $label = $this->findLabel($node, $dom);
        $placeholder = trim($node->getAttribute('placeholder'));

        $rawKey = trim($node->getAttribute('name'));

        if ($rawKey === '') {
            $rawKey = Str::slug(
                $label ?: $placeholder ?: 'field_'.$position,
                '_'
            );
        }

        if ($rawKey === '') {
            $rawKey = 'field_'.$position;
        }

        $fieldKey = $this->makeUniqueFieldKey(
            $rawKey,
            $usedKeys
        );

        $node->setAttribute('name', $fieldKey);

        $id = trim($node->getAttribute('id'));

        if ($id === '') {
            $id = $fieldKey;
            $node->setAttribute('id', $id);
        }

        $this->connectLabelToField($node, $dom, $id);

        $fields[] = [
            'label' => $label ?: Str::headline($fieldKey),
            'field_key' => $fieldKey,
            'field_type' => $fieldType,
            'placeholder' => $placeholder !== ''
                ? $placeholder
                : null,
            'options' => $this->extractOptions($node),
            'default_value' => $node->getAttribute('value') ?: null,
            'is_required' => $node->hasAttribute('required'),
            'contact_mapping' => self::suggestContactMapping(
                $fieldKey,
                $fieldType,
                $audienceType
            ),
            'tag_from_value' => false,
            'position' => $position,
            'sort_order' => $this->resolveVisualRow(
                $node,
                $position
            ),
            'validation_rules' => null,
        ];

        $position++;
    }

    return $fields;
}
```

## 6.6. Các helper cần thêm

```php
private function makeUniqueFieldKey(
    string $base,
    array &$usedKeys
): string {
    $key = Str::slug($base, '_');
    $key = $key !== '' ? $key : 'field';

    $candidate = $key;
    $counter = 2;

    while (isset($usedKeys[$candidate])) {
        $candidate = $key.'_'.$counter;
        $counter++;
    }

    $usedKeys[$candidate] = true;

    return $candidate;
}

private function resolveFieldType(
    DOMElement $node,
    string $type
): string {
    if ($node->tagName === 'textarea') {
        return 'textarea';
    }

    if ($node->tagName === 'select') {
        return 'select';
    }

    return match ($type) {
        'email' => 'email',
        'tel', 'phone' => 'phone',
        'checkbox' => 'checkbox',
        'radio' => 'select',
        default => 'text',
    };
}

private function extractOptions(DOMElement $node): ?array
{
    if ($node->tagName !== 'select') {
        return null;
    }

    $options = [];

    foreach ($node->getElementsByTagName('option') as $option) {
        $value = trim(
            $option->getAttribute('value')
            ?: $option->textContent
        );

        $label = trim($option->textContent);

        if ($value === '') {
            continue;
        }

        $options[] = [
            'value' => $value,
            'label' => $label !== '' ? $label : $value,
        ];
    }

    return $options !== [] ? $options : null;
}

private function extractSubmitButtonText(
    DOMElement $form
): ?string {
    foreach ($form->getElementsByTagName('button') as $button) {
        $type = strtolower($button->getAttribute('type') ?: 'submit');

        if ($type === 'submit') {
            $text = trim($button->textContent);

            return $text !== '' ? $text : null;
        }
    }

    return null;
}
```

### `resolveVisualRow()`

Tối thiểu có thể dùng mỗi field một hàng:

```php
private function resolveVisualRow(
    DOMElement $node,
    int $position
): int {
    return $position;
}
```

Khuyến nghị nâng cấp để nhận diện `.grid-2`:

- Hai `.form-group` bình thường liên tiếp có cùng `sort_order`.
- `.form-group.full` có một `sort_order` riêng.

Mục tiêu với form cá nhân:

```text
sort_order 0: full_name, phone
sort_order 1: email
sort_order 2: service_package
```

Renderer hiện gom các field cùng `sort_order` thành grid nhiều cột.

---

# 7. Sửa `LandingPageThemeService` để đọc CSS variables

File:

```text
app/Services/Marketing/LandingPageThemeService.php
```

## 7.1. Thêm method lấy biến CSS

```php
private function extractCssVariables(string $html): array
{
    $variables = [];

    preg_match_all(
        '/--([a-z0-9-_]+)\s*:\s*([^;}]+)/i',
        $html,
        $matches,
        PREG_SET_ORDER
    );

    foreach ($matches as $match) {
        $variables[strtolower($match[1])] = trim($match[2]);
    }

    return $variables;
}

private function normalizeHex(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);

    if (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
        return strtolower($value);
    }

    if (preg_match('/^#[0-9a-f]{3}$/i', $value)) {
        return strtolower(sprintf(
            '#%s%s%s%s%s%s',
            $value[1], $value[1],
            $value[2], $value[2],
            $value[3], $value[3],
        ));
    }

    return null;
}
```

## 7.2. Thay `detectFromHtml()`

```php
public function detectFromHtml(string $html): array
{
    $tokens = $this->defaults();
    $variables = $this->extractCssVariables($html);

    $primary = $this->normalizeHex(
        $variables['lp-primary']
        ?? $variables['primary']
        ?? null
    ) ?? $this->detectPrimaryColor($html);

    if ($primary !== null) {
        $tokens['primary'] = $primary;
        $tokens['primary_hover'] = $this->normalizeHex(
            $variables['primary-hover']
            ?? $variables['secondary']
            ?? null
        ) ?? $this->darken($primary, 12);
    }

    $tokens['background'] = $this->normalizeHex(
        $variables['lp-background']
        ?? $variables['background']
        ?? $variables['bg']
        ?? null
    ) ?? $this->detectBodyColor(
        $html,
        'background(?:-color)?'
    ) ?? $tokens['background'];

    $tokens['surface'] = $this->normalizeHex(
        $variables['lp-surface']
        ?? $variables['surface']
        ?? null
    ) ?? $tokens['surface'];

    $tokens['text'] = $this->normalizeHex(
        $variables['lp-text']
        ?? $variables['text']
        ?? null
    ) ?? $this->detectBodyColor(
        $html,
        'color'
    ) ?? $tokens['text'];

    if (preg_match(
        '/border-radius\s*:\s*(\d+(?:\.\d+)?(?:px|rem))/i',
        $html,
        $match
    )) {
        $tokens['radius'] = $match[1];
    }

    return $tokens;
}

private function detectBodyColor(
    string $html,
    string $propertyPattern
): ?string {
    if (! preg_match(
        '/body\s*\{[^}]*'.$propertyPattern.'\s*:\s*(#[0-9a-f]{3,6})/i',
        $html,
        $match
    )) {
        return null;
    }

    return $this->normalizeHex($match[1]);
}
```

Kết quả mong đợi với VPS HTML:

```php
[
    'primary' => '#7c3aed',
    'primary_hover' => '#4f46e5',
    'background' => '#f5f3ff',
    'surface' => '#ffffff',
    // ...
]
```

---

# 8. Xóa toàn bộ vùng form cũ khỏi Landing Page

File:

```text
app/Services/Marketing/LandingPageRenderService.php
```

Hiện `prepareImportedLandingPageHtml()` chỉ dùng regex xóa `<form>`.

Hãy thay bằng DOM cleanup.

## 8.1. Thay method

```php
public function prepareImportedLandingPageHtml(string $html): string
{
    $html = $this->sanitizeImportedHtml($html, false);
    $html = $this->removeImportedFormSections($html);

    $html = str_replace([
        '{{form}}',
        '{{form_personal}}',
        '{{form_business}}',
        '{{forms_section}}',
    ], '', $html);

    return trim($html);
}
```

## 8.2. Thêm `removeImportedFormSections()`

```php
private function removeImportedFormSections(string $html): string
{
    $dom = new \DOMDocument('1.0', 'UTF-8');

    libxml_use_internal_errors(true);

    $loaded = $dom->loadHTML(
        '<?xml encoding="UTF-8">'.$html,
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    libxml_clear_errors();

    if (! $loaded) {
        return preg_replace(
            '/<form\b[^>]*>.*?<\/form>/is',
            '',
            $html
        ) ?? $html;
    }

    $xpath = new \DOMXPath($dom);
    $forms = $xpath->query('//form');

    if ($forms === false) {
        return $html;
    }

    // Copy node list trước vì DOM sẽ thay đổi khi remove.
    $formNodes = [];

    foreach ($forms as $form) {
        $formNodes[] = $form;
    }

    foreach ($formNodes as $form) {
        if (! $form instanceof \DOMElement) {
            continue;
        }

        $container = $this->findImportedFormContainer($form);
        $target = $container ?? $form;

        $target->parentNode?->removeChild($target);
    }

    return trim($dom->saveHTML() ?: $html);
}
```

## 8.3. Thêm finder vùng form

```php
private function findImportedFormContainer(
    \DOMElement $form
): ?\DOMElement {
    $node = $form->parentNode;
    $fallback = null;

    while ($node instanceof \DOMElement) {
        $tag = strtolower($node->tagName);
        $identity = strtolower(
            $node->getAttribute('id').' '.
            $node->getAttribute('class')
        );

        if (
            $tag === 'section' &&
            preg_match(
                '/form|contact|register|registration|signup|lead|inquiry|booking/',
                $identity
            )
        ) {
            return $node;
        }

        if (
            $fallback === null &&
            preg_match(
                '/form-section|form-wrapper|contact-form|register-form/',
                $identity
            )
        ) {
            $fallback = $node;
        }

        if ($tag === 'body') {
            break;
        }

        $node = $node->parentNode;
    }

    return $fallback;
}
```

Với HTML VPS, phải xóa toàn bộ:

```html
<section class="form-section">...</section>
```

không chỉ hai thẻ `<form>` bên trong.

### Lưu ý an toàn

Nếu Landing Page có form nằm trong hero và không có class/id nhận diện, chỉ xóa thẻ form, không xóa cả hero.

---

# 9. Đảm bảo form public luôn render từ `form_fields`

File:

```text
app/Services/Marketing/LandingPageRenderService.php
```

Trong `renderResolvedFormTemplate()`, logic hiện tại có nhánh:

```php
if ($formTemplate->html_body) {
    // render HTML body
}
```

Sau khi sửa import, `html_body` bắt buộc chứa:

```text
{{fields}}
```

Thêm kiểm tra phòng vệ:

```php
if (
    filled($formTemplate->html_body) &&
    ! str_contains($formTemplate->html_body, '{{fields}}')
) {
    $formTemplate->html_body = self::sanitizeHtmlBody(
        $formTemplate->html_body
    );
}
```

Không gọi `$formTemplate->save()` trong renderer. Chỉ normalize runtime để tương thích dữ liệu cũ.

Khuyến nghị tạo command migrate dữ liệu cũ thay vì ghi database khi render.

---

# 10. Thêm Artisan command migrate Form Template cũ

Tạo:

```bash
php artisan make:command NormalizeImportedFormTemplates
```

File:

```text
app/Console/Commands/NormalizeImportedFormTemplates.php
```

Mục tiêu:

- Tìm Form Template có `html_body` không chứa `{{fields}}`.
- Normalize về shell động.
- Không tạo lại FormField nếu đã tồn tại.
- Có `--dry-run`.

Pseudo-code:

```php
FormTemplate::query()
    ->whereNotNull('html_body')
    ->chunkById(100, function ($templates): void {
        foreach ($templates as $template) {
            if (str_contains($template->html_body, '{{fields}}')) {
                continue;
            }

            $normalized = LandingPageRenderService::sanitizeHtmlBody(
                $template->html_body
            );

            // Log trước/sau.
            // Chỉ save nếu không chạy --dry-run.
        }
    });
```

Không cố phục hồi field key chính xác cho bản ghi cũ nếu HTML ban đầu không có `name`. Với dữ liệu test nên xóa và import lại.

---

# 11. Bổ sung route preview Form Template

File:

```text
routes/web.php
```

## 11.1. Thêm import

```php
use App\Http\Controllers\Marketing\Admin\FormTemplatePreviewController;
```

## 11.2. Thêm route trong group admin marketing

```php
Route::middleware(['web', 'auth'])
    ->prefix('admin/marketing')
    ->name('marketing.')
    ->group(function (): void {
        Route::get(
            'landing-pages/{landingPage}/preview',
            [LandingPagePreviewController::class, 'preview']
        )->name('landing-pages.preview');

        Route::get(
            'form-templates/{formTemplate}/preview',
            [FormTemplatePreviewController::class, 'preview']
        )->name('form-templates.preview');

        // Các route hiện tại khác...
    });
```

Sau đó chạy:

```bash
php artisan route:clear
php artisan route:list | grep form-templates
```

Windows PowerShell:

```powershell
php artisan route:list | Select-String form-templates
```

---

# 12. Preview độc lập và preview theo Landing Page

## 12.1. Hành vi đúng

URL không có `landing_page_id`:

```text
/admin/marketing/form-templates/5/preview
```

sẽ dùng theme mặc định màu xanh. Đây không phải lỗi.

URL có Landing Page:

```text
/admin/marketing/form-templates/5/preview?landing_page_id=3
```

phải dùng `theme_tokens` của Landing Page ID 3.

## 12.2. Cải thiện action trong `FormTemplateResource.php`

Giữ action `Xem trước độc lập` và thêm action thứ hai:

```php
Action::make('preview_default')
    ->label('Xem trước độc lập')
    ->icon('heroicon-o-eye')
    ->url(
        fn (FormTemplate $record): string => route(
            'marketing.form-templates.preview',
            $record
        )
    )
    ->openUrlInNewTab(),
```

Thêm action preview theo Landing Page. Vì Filament action có modal form nhưng redirect new tab không thuận tiện, phương án sạch nhất là tạo một trang preview riêng có Select Landing Page.

Tối thiểu thêm helper text trên action độc lập:

```php
->tooltip(
    'Preview độc lập dùng màu mặc định. '
    .'Khi gắn vào Landing Page, form sẽ dùng màu của Landing Page.'
)
```

---

# 13. Làm rõ vị trí ColorPicker

File:

```text
app/Filament/Resources/LandingPageResource.php
```

Đổi mô tả Section:

```php
Section::make('Tone màu của khu vực Form')
    ->description(
        'Tone màu được lưu theo từng Landing Page. '
        .'Form Template không lưu màu riêng. '
        .'Khi gắn Form Template vào Landing Page, form sẽ dùng các màu tại đây.'
    )
```

Trong `FormTemplateResource.php`, thêm Placeholder hoặc Section mô tả:

```php
Section::make('Giao diện và tone màu')
    ->description(
        'Form Template chỉ quản lý cấu trúc và trường dữ liệu. '
        .'Tone màu được cấu hình tại màn hình Edit Landing Page.'
    )
    ->schema([])
    ->collapsed(),
```

Không thêm ColorPicker vào Form Template nếu vẫn giữ nguyên quyết định kiến trúc.

---

# 14. Sửa redirect sau submit

File:

```text
app/Http/Controllers/Marketing/Public/LandingPageController.php
```

Tìm:

```php
$formTemplate = $landingPage->formTemplate;
```

Thay bằng:

```php
$formTemplate = $submission->formTemplate;
```

Đoạn hoàn chỉnh:

```php
$submission = $this->submissionService->handle(
    $landingPage,
    $request->all(),
    $request,
    $campaignId
);

$formTemplate = $submission->formTemplate;

if ($formTemplate && $formTemplate->redirect_url) {
    return redirect()->away($formTemplate->redirect_url);
}

return redirect()
    ->route(
        'marketing.landing-pages.public.thank-you',
        $slug
    )
    ->with(
        'success_message',
        $formTemplate?->success_message
            ?? 'Cảm ơn! Thông tin của bạn đã được ghi nhận.'
    );
```

Kiểm tra model `LandingPageSubmission` có quan hệ:

```php
public function formTemplate(): BelongsTo
{
    return $this->belongsTo(
        FormTemplate::class,
        'landing_form_template_id'
    );
}
```

Nếu chưa có thì bổ sung.

---

# 15. Sửa mẫu HTML form đầu vào

AI Agent phải hỗ trợ HTML thiếu `name`, nhưng file mẫu cũng nên đúng chuẩn.

## Trước

```html
<label>Họ và Tên</label>
<input type="text" placeholder="Nhập họ và tên..." required>
```

## Sau

```html
<label for="full_name">Họ và Tên</label>
<input
    id="full_name"
    name="full_name"
    type="text"
    placeholder="Nhập họ và tên..."
    required
>
```

Các field key đề xuất cho Form cá nhân:

```text
full_name
phone
email
service_package
```

Form doanh nghiệp:

```text
company_name
tax_code
legal_representative
business_phone
service_package
```

Tuy nhiên, backend không được phụ thuộc việc user luôn cung cấp `name` đúng. Backend vẫn phải tự normalize.

---

# 16. Kiểm tra `theme_tokens` trên database

Sau migration:

```bash
php artisan migrate
php artisan optimize:clear
```

Kiểm tra bằng Tinker:

```bash
php artisan tinker
```

```php
Schema::hasColumn('landing_pages', 'theme_tokens');
```

Phải trả về:

```php
true
```

Kiểm tra VPS Landing Page:

```php
$page = App\Models\Marketing\LandingPage::query()
    ->where('slug', 'slug-vps')
    ->first();

$page->theme_tokens;
```

Kỳ vọng:

```php
[
    'primary' => '#7c3aed',
    'primary_hover' => '#4f46e5',
    'background' => '#f5f3ff',
    'surface' => '#ffffff',
    'text' => '#0f172a',
    'muted_text' => '#64748b',
    'border' => '#cbd5e1',
    'danger' => '#dc2626',
    'radius' => '24px', // hoặc giá trị nhận diện được
]
```

---

# 17. Test bắt buộc

## 17.1. Unit test Theme Service

Tạo:

```text
tests/Unit/Marketing/LandingPageThemeServiceTest.php
```

Test:

```php
public function test_it_detects_css_variables_from_vps_html(): void
{
    $html = <<<'HTML'
<style>
:root {
    --primary: #7c3aed;
    --secondary: #4f46e5;
    --bg: #f5f3ff;
}
body { background: var(--bg); }
</style>
HTML;

    $tokens = app(
        LandingPageThemeService::class
    )->detectFromHtml($html);

    $this->assertSame('#7c3aed', $tokens['primary']);
    $this->assertSame('#4f46e5', $tokens['primary_hover']);
    $this->assertSame('#f5f3ff', $tokens['background']);
}
```

## 17.2. Unit test Form Import

Tạo:

```text
tests/Unit/Marketing/FormTemplateImportServiceTest.php
```

Test HTML thiếu name:

```php
public function test_it_generates_stable_keys_for_fields_without_name(): void
{
    $html = <<<'HTML'
<form>
    <div>
        <label>Họ và Tên</label>
        <input type="text" placeholder="Nhập họ và tên" required>
    </div>
    <div>
        <label>Email cá nhân</label>
        <input type="email" placeholder="example@gmail.com" required>
    </div>
    <button type="submit">Đăng ký</button>
</form>
HTML;

    $template = app(
        FormTemplateImportService::class
    )->import([
        'name' => 'Test form',
        'slug' => 'test-form',
        'audience_type' => 'personal',
    ], $html, 1);

    $this->assertSame(
        ['ho_va_ten', 'email_ca_nhan'],
        $template->fields->pluck('field_key')->all()
    );

    $this->assertStringContainsString(
        '{{fields}}',
        $template->html_body
    );
}
```

Có thể chuẩn hóa tiếng Việt không dấu thành:

```text
ho_va_ten
email_ca_nhan
```

hoặc map riêng về:

```text
full_name
email
```

Nếu muốn map tiếng Việt thông minh, thêm dictionary trong parser.

## 17.3. Feature test import Landing Page

Tạo:

```text
tests/Feature/Marketing/ImportLandingPageTest.php
```

Assertions:

```php
$this->assertStringNotContainsString(
    '<form',
    strtolower($page->html_body)
);

$this->assertStringNotContainsString(
    'class="form-section"',
    strtolower($page->html_body)
);

$this->assertSame(
    '#7c3aed',
    $page->theme_tokens['primary']
);
```

## 17.4. Feature test preview route

```php
$this->get(
    route('marketing.form-templates.preview', $template)
)->assertOk();
```

## 17.5. Feature test submit đúng Form Template

- Gắn personal và business template khác nhau.
- Submit tab business.
- Assert `landing_page_submissions.landing_form_template_id` bằng template business.
- Assert success message lấy từ template business.

---

# 18. Quy trình chạy sau khi sửa

```bash
composer install
php artisan migrate
php artisan optimize:clear
php artisan route:list
php artisan test
```

Nếu dùng Vite:

```bash
npm install
npm run build
```

---

# 19. Quy trình test thủ công

## Bước 1 — Xóa dữ liệu test cũ

Xóa Form Template và Landing Page đã import bằng logic cũ. Không dùng dữ liệu cũ để kết luận fix thất bại.

## Bước 2 — Import lại VPS Landing Page

Kỳ vọng:

- Hero và pricing giữ nguyên style.
- Không còn section `Triển Khai Máy Chủ Ngay` cũ.
- Không còn tab cũ.
- `theme_tokens.primary = #7c3aed`.
- `theme_tokens.background = #f5f3ff`.

## Bước 3 — Import lại Form cá nhân

Kỳ vọng:

- Có bốn FormField.
- Repeater thu gọn có tiêu đề.
- Field key không còn `field_0`, `field_1` nếu có label phù hợp.
- `html_body` chứa `{{fields}}`.

## Bước 4 — Edit FormField

Đổi:

```text
Email cá nhân -> Email liên hệ
```

Preview phải hiện ngay `Email liên hệ`.

Nếu preview vẫn hiện label cũ, nghĩa là renderer vẫn dùng input hardcode trong HTML.

## Bước 5 — Edit Landing Page

Tại:

```text
Marketing -> Landing Pages -> Edit
```

phải thấy:

- Tab Form cá nhân.
- Tab Form doanh nghiệp.
- Section Tone màu của khu vực Form.

Chọn Form và lưu.

## Bước 6 — Preview Form độc lập

Màu xanh mặc định là hợp lệ.

## Bước 7 — Preview theo VPS Landing Page

```text
/admin/marketing/form-templates/{id}/preview?landing_page_id={vps_id}
```

Kỳ vọng:

- Tab active màu tím.
- Button submit màu tím.
- Focus input màu tím.
- Nền vùng form là tím rất nhạt.

## Bước 8 — Publish

Không cho publish nếu thiếu personal hoặc business form.

## Bước 9 — Submit

- Personal submit dùng Form Template personal.
- Business submit dùng Form Template business.
- Redirect và success message đúng template vừa submit.

---

# 20. Điều kiện hoàn thành

AI Agent chỉ được báo hoàn thành khi toàn bộ điều kiện sau đạt:

- [ ] Có migration `theme_tokens` và migrate thành công.
- [ ] Landing Page import loại bỏ toàn bộ vùng form cũ.
- [ ] Landing Page không tự tạo Form Template.
- [ ] Form import thiếu `name` vẫn tạo key ổn định.
- [ ] Form Template `html_body` chứa `{{fields}}`.
- [ ] Edit FormField làm preview thay đổi.
- [ ] Repeater thu gọn hiển thị tên field.
- [ ] Theme detector đọc được `--primary`, `--secondary`, `--bg`.
- [ ] Preview route tồn tại trong repo.
- [ ] Preview độc lập dùng default theme.
- [ ] Preview theo Landing Page dùng `theme_tokens`.
- [ ] Form public nằm cuối Landing Page.
- [ ] Form public có tab personal/business.
- [ ] Submit đúng form template theo tab.
- [ ] Không dùng `$landingPage->formTemplate` cho redirect sau submit.
- [ ] Toàn bộ test mới và test cũ đều pass.

---

# 21. Những phần chưa xóa trong đợt fix này

Tạm thời giữ để tương thích dữ liệu cũ:

```text
landing_pages.landing_form_template_id
LandingPage::formTemplate()
FormAudienceType::Generic
LandingPageRenderService::extractBodyContent()
```

Nhưng không dùng chúng trong luồng mới.

Sau khi production ổn định mới tạo task cleanup riêng:

1. Migrate dữ liệu legacy.
2. Xóa cột `landing_form_template_id`.
3. Xóa quan hệ legacy.
4. Xóa generic khỏi UI và code không còn dùng.
5. Xóa các method render cũ.

---

# 22. Yêu cầu đối với AI Agent Coding

AI Agent phải thực hiện theo thứ tự:

1. Đọc các file được liệt kê trong mục 3.
2. Kiểm tra diff hiện tại, không ghi đè thay đổi mới của user.
3. Tạo migration mới, không sửa migration production cũ.
4. Viết test trước hoặc cùng lúc với code.
5. Sửa từng nhóm nhỏ và chạy test sau mỗi nhóm.
6. Không chỉnh trực tiếp `vendor/`.
7. Không dùng `dd()` trong code commit.
8. Không log toàn bộ payload form vì có dữ liệu cá nhân.
9. Báo cáo cuối cùng phải gồm:
   - File đã sửa.
   - Migration đã tạo.
   - Test đã chạy.
   - Kết quả test.
   - Các phần legacy còn giữ.

Prompt ngắn có thể đưa kèm cho Agent:

```text
Hãy đọc file DTH_FIX_LANDING_PAGE_FORM_IMPORT.md và thực hiện toàn bộ mục P0 trước. Không được chỉ sửa giao diện. Phải bảo đảm form_fields là source of truth, Landing Page import loại bỏ toàn bộ vùng form cũ, theme_tokens được lưu bằng migration, preview route tồn tại, và submit dùng đúng Form Template theo tab. Viết test regression và chạy toàn bộ test trước khi báo hoàn thành.
```
