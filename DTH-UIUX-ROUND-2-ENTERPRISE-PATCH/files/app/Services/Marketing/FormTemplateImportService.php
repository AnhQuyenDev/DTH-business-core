<?php

namespace App\Services\Marketing;

use App\Models\Marketing\FormTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FormTemplateImportService
{
    public function __construct(
        private readonly LandingPageRenderService $renderer
    ) {}

    public function import(array $data, string $sourceHtml, ?int $userId): FormTemplate
    {
        $audienceType = (string) ($data['audience_type'] ?? '');

        if (! in_array($audienceType, ['personal', 'business'], true)) {
            throw new RuntimeException(
                'Form Template chỉ được thuộc loại Cá nhân hoặc Doanh nghiệp.'
            );
        }

        $prepared = $this->prepareImportedForm($sourceHtml, $audienceType);

        return DB::transaction(function () use ($data, $audienceType, $prepared, $userId): FormTemplate {
            $template = FormTemplate::query()->create([
                'name' => (string) $data['name'],
                'slug' => (string) ($data['slug'] ?? Str::slug((string) $data['name'])),
                'audience_type' => $audienceType,
                'status' => 'draft',
                'submit_button_text' => $prepared['submit_button_text']
                    ?: ($data['submit_button_text'] ?? 'Gửi thông tin'),
                'html_body' => $prepared['html_body'],
                'created_by' => $userId,
            ]);

            $template->fields()->createMany($prepared['fields']);

            return $template->fresh('fields');
        });
    }

    private function prepareImportedForm(string $sourceHtml, string $audienceType): array
    {
        $sanitized = $this->renderer->sanitizeImportedHtml($sourceHtml, false);

        $dom = new \DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">'.$sanitized,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();

        if (! $loaded) {
            throw new RuntimeException('Không thể phân tích HTML Form.');
        }

        $xpath = new \DOMXPath($dom);
        $forms = $xpath->query('//form');

        if ($forms === false || $forms->length === 0) {
            throw new RuntimeException('File HTML không chứa thẻ <form>.');
        }

        if ($forms->length > 1) {
            throw new RuntimeException(
                'Mỗi Form Template chỉ được chứa một thẻ <form>.'
            );
        }

        /** @var \DOMElement $form */
        $form = $forms->item(0);

        $fields = $this->normalizeAndExtractFields($dom, $form, $audienceType);

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

    private function normalizeAndExtractFields(
        \DOMDocument $dom,
        \DOMElement $form,
        string $audienceType
    ): array {
        $xpath = new \DOMXPath($dom);

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
            if (! $node instanceof \DOMElement) {
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

            $fieldKey = $this->makeUniqueFieldKey($rawKey, $usedKeys);

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
                'sort_order' => $this->resolveVisualRow($node, $position),
                'validation_rules' => null,
            ];

            $position++;
        }

        return $fields;
    }

    private function makeUniqueFieldKey(string $base, array &$usedKeys): string
    {
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

    private function resolveFieldType(\DOMElement $node, string $type): string
    {
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

    private function extractOptions(\DOMElement $node): ?array
    {
        if ($node->tagName !== 'select') {
            return null;
        }

        $options = [];

        foreach ($node->getElementsByTagName('option') as $option) {
            $valueAttr = $option->getAttribute('value');
            $value = trim($valueAttr !== '' ? $valueAttr : $option->textContent);

            $label = trim($option->textContent);

            // Skip options with empty value attribute (placeholder options)
            if ($valueAttr === '') {
                continue;
            }

            $options[] = [
                'value' => $value,
                'label' => $label !== '' ? $label : $value,
            ];
        }

        return $options !== [] ? $options : null;
    }

    private function extractSubmitButtonText(\DOMElement $form): ?string
    {
        foreach ($form->getElementsByTagName('button') as $button) {
            $type = strtolower($button->getAttribute('type') ?: 'submit');

            if ($type === 'submit') {
                $text = trim($button->textContent);

                return $text !== '' ? $text : null;
            }
        }

        return null;
    }

    private function connectLabelToField(\DOMElement $field, \DOMDocument $dom, string $id): void
    {
        $xpath = new \DOMXPath($dom);
        $labels = $xpath->query("//label[@for='$id']");

        if ($labels !== false && $labels->length === 0) {
            $parent = $field->parentNode;
            if ($parent instanceof \DOMElement) {
                $label = $dom->createElement('label', '');
                $label->setAttribute('for', $id);
                $parent->insertBefore($label, $field);
            }
        }
    }

    protected function findLabel(\DOMElement $element, \DOMDocument $dom): ?string
    {
        $id = $element->getAttribute('id');
        if ($id !== '') {
            $xpath = new \DOMXPath($dom);
            $labels = $xpath->query("//label[@for='$id']");
            if ($labels !== false && $labels->length > 0) {
                return trim($labels->item(0)->textContent);
            }
        }

        $parent = $element->parentNode;
        if ($parent !== null) {
            foreach ($parent->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->tagName === 'label' && ! $child->hasAttribute('for')) {
                    return trim($child->textContent);
                }
            }
        }

        return null;
    }

    private function resolveVisualRow(\DOMElement $node, int $position): int
    {
        return $position;
    }

    private function addSystemFormClass(string $formHtml): string
    {
        return preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1];

                /*
                * Xóa các thuộc tính nhận diện/giao diện từ Form HTML gốc.
                *
                * Không được giữ class="form-container", vì class này
                * có thể trùng với CSS của Landing Page.
                */
                $attributes = preg_replace(
                    '/\s+class\s*=\s*(["\']).*?\1/i',
                    '',
                    $attributes
                ) ?? $attributes;

                $attributes = preg_replace(
                    '/\s+id\s*=\s*(["\']).*?\1/i',
                    '',
                    $attributes
                ) ?? $attributes;

                $attributes = preg_replace(
                    '/\s+style\s*=\s*(["\']).*?\1/i',
                    '',
                    $attributes
                ) ?? $attributes;

                $attributes = preg_replace(
                    '/\s+on\w+\s*=\s*(["\']).*?\1/i',
                    '',
                    $attributes
                ) ?? $attributes;

                return '<form'
                    .$attributes
                    .' class="lp-form-template__form">';
            },
            $formHtml,
            1
        ) ?? $formHtml;
    }

    public static function suggestContactMapping(
        string $fieldKey,
        string $fieldType,
        string $audienceType
    ): ?string {
        $key = strtolower(trim($fieldKey));

        $personalMap = [
            'first_name' => 'personal.first_name',
            'last_name' => 'personal.last_name',
            'full_name' => 'personal.first_name',
            'email' => 'personal.email',
            'phone' => 'personal.phone',
            'date_of_birth' => 'personal.date_of_birth',
            'gender' => 'personal.gender',
            'occupation' => 'personal.occupation',
        ];

        $businessMap = [
            'company_name' => 'business.company_name',
            'company' => 'business.company_name',
            'tax_code' => 'business.tax_code',
            'business_email' => 'business.business_email',
            'email' => 'business.business_email',
            'business_phone' => 'business.business_phone',
            'phone' => 'business.business_phone',
            'company_address' => 'business.company_address',
            'legal_representative' => 'business.legal_representative',
            'contact_position' => 'business.contact_position',
            'industry' => 'business.industry',
        ];

        $mapping = $audienceType === 'business'
            ? $businessMap
            : $personalMap;

        if (isset($mapping[$key])) {
            return $mapping[$key];
        }

        if ($fieldType === 'email') {
            return $audienceType === 'business'
                ? 'business.business_email'
                : 'personal.email';
        }

        if ($fieldType === 'phone') {
            return $audienceType === 'business'
                ? 'business.business_phone'
                : 'personal.phone';
        }

        return null;
    }
}
