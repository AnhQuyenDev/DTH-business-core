<?php

namespace App\Services\Marketing;
use App\Models\Marketing\FormTemplate;
use Illuminate\Support\Facades\DB;
use RuntimeException;
class FormTemplateImportService
{
    public function __construct(
    private readonly LandingPageRenderService $renderer) {

    }
    public function parseFieldsFromHtml(string $html,string $audienceType): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $fields = [];
        $sortOrder = 0;
        $seenKeys = [];

        $inputs = $dom->getElementsByTagName('input');
        $textareas = $dom->getElementsByTagName('textarea');
        $selects = $dom->getElementsByTagName('select');

        foreach ($inputs as $input) {
            $type = $input->getAttribute('type') ?: 'text';
            if (in_array($type, ['hidden', 'submit', 'button', 'reset'], true)) {
                continue;
            }
            $name = $input->getAttribute('name') ?: 'field_' . $sortOrder;
            if ($name === '') continue;

            if (in_array($type, ['radio', 'checkbox'], true)) {
                if (isset($seenKeys[$name])) {
                    continue;
                }
                $seenKeys[$name] = true;
            }

            $fieldType = match ($type) {
                'email'            => 'email',
                'tel', 'phone'     => 'phone',
                'radio'            => 'select',
                'checkbox'         => 'checkbox',
                default            => 'text',
            };

            $label = $this->findLabel($input, $dom);
            $fields[] = [
                'label'             => $label ?: $name,
                'field_key'         => $name,
                'field_type'        => $fieldType,
                'placeholder'       => $input->getAttribute('placeholder') ?: null,
                'options'           => null,
                'default_value'     => $input->getAttribute('value') ?: null,
                'is_required'       => $input->hasAttribute('required'),
                'contact_mapping'   => self::suggestContactMapping(
                                            $name,
                                            $fieldType,
                                            $audienceType
                                        ),
                'tag_from_value'    => false,
                'position' => $sortOrder,
                'sort_order'        => $sortOrder++,
                'validation_rules'  => null,
            ];
        }

        foreach ($textareas as $textarea) {
            $name = $textarea->getAttribute('name') ?: 'field_' . $sortOrder;
            if ($name === '' || isset($seenKeys[$name])) continue;

            $seenKeys[$name] = true;
            $label = $this->findLabel($textarea, $dom);
            $fields[] = [
                'label'             => $label ?: $name,
                'field_key'         => $name,
                'field_type'        => 'textarea',
                'placeholder'       => $textarea->getAttribute('placeholder') ?: null,
                'options'           => null,
                'default_value'     => null,
                'is_required'       => $textarea->hasAttribute('required'),
                'contact_mapping'   => self::suggestContactMapping($name, 'textarea', $audienceType),
                'tag_from_value'    => false,
                'position' => $sortOrder,
                'sort_order'        => $sortOrder++,
                'validation_rules'  => null,
            ];
        }

        foreach ($selects as $select) {
            $name = $select->getAttribute('name') ?: 'field_' . $sortOrder;
            if ($name === '' || isset($seenKeys[$name])) continue;

            $seenKeys[$name] = true;

            $options = [];
            foreach ($select->getElementsByTagName('option') as $option) {
                $val = $option->hasAttribute('value') ? $option->getAttribute('value') : $option->textContent;
                if ($val !== '') {
                    $options[] = $val;
                }
            }

            $label = $this->findLabel($select, $dom);
            $fields[] = [
                'label'             => $label ?: $name,
                'field_key'         => $name,
                'field_type'        => 'select',
                'placeholder'       => null,
                'options'           => $options ?: null,
                'default_value'     => null,
                'is_required'       => $select->hasAttribute('required'),
                'contact_mapping'   => self::suggestContactMapping($name, 'select', $audienceType),
                'tag_from_value'    => false,
                'position' => $sortOrder,
                'sort_order'        => $sortOrder++,
                'validation_rules'  => null,
            ];
        }

        return $fields;
    }

    protected function findLabel(\DOMElement $element, \DOMDocument $dom): ?string
    {
        $id = $element->getAttribute('id');
        if ($id !== '') {
            $xpath = new \DOMXPath($dom);
            $labels = $xpath->query("//label[@for='{$id}']");
            if ($labels !== false && $labels->length > 0) {
                return trim($labels->item(0)->textContent);
            }
        }

        $parent = $element->parentNode;
        if ($parent !== null) {
            $labelChildren = [];
            foreach ($parent->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->tagName === 'label' && ! $child->hasAttribute('for')) {
                    return trim($child->textContent);
                }
            }
        }

        return null;
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
            'legal_representative' =>
                'business.legal_representative',
            'contact_position' =>
                'business.contact_position',
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

    public function import(array $data,string $sourceHtml,?int $userId): FormTemplate {
        $audienceType = (string) $data['audience_type'];

        if (! in_array($audienceType, ['personal', 'business'], true)) {
            throw new RuntimeException(
                'Form Template chỉ được thuộc loại Cá nhân hoặc Doanh nghiệp.'
            );
        }

        $formHtml = $this->extractSingleForm($sourceHtml);

        $fields = $this->parseFieldsFromHtml(
            $formHtml,
            $audienceType
        );

        if ($fields === []) {
            throw new RuntimeException(
                'Không tìm thấy input, select hoặc textarea hợp lệ trong form.'
            );
        }

        return DB::transaction(function () use (
            $data,
            $audienceType,
            $formHtml,
            $fields,
            $userId
        ): FormTemplate {
            $formTemplate = FormTemplate::query()->create([
                'name' => (string) $data['name'],
                'slug' => (string) $data['slug'],
                'audience_type' => $audienceType,
                'status' => 'draft',
                'submit_button_text' =>
                    $data['submit_button_text'] ?? 'Gửi thông tin',
                'html_body' => $formHtml,
                'created_by' => $userId,
            ]);

            $formTemplate->fields()->createMany($fields);

            return $formTemplate->fresh('fields');
        });
    }

    private function extractSingleForm(string $html): string
    {
        $html = $this->renderer->sanitizeImportedHtml(
            $html,
            false
        );

        preg_match_all(
            '/<form\b[^>]*>.*?<\/form>/is',
            $html,
            $matches
        );

        $forms = $matches[0] ?? [];

        if (count($forms) === 0) {
            throw new RuntimeException(
                'File HTML không chứa thẻ <form>.'
            );
        }

        if (count($forms) > 1) {
            throw new RuntimeException(
                'Mỗi Form Template chỉ được chứa một thẻ <form>.'
            );
        }

        $formHtml = $this->addSystemFormClass($forms[0]);

        /*
        * Chỉ giữ CSS nằm trong <style>.
        * Không giữ toàn bộ <html>, <head> hoặc <body>.
        */
        preg_match_all(
            '/<style\b[^>]*>.*?<\/style>/is',
            $html,
            $styleMatches
        );

        $styles = implode("\n", $styleMatches[0] ?? []);

        return trim(
            $styles."\n".
            '<div class="lp-form-template">'.
            $formHtml.
            '</div>'
        );
    }

    private function addSystemFormClass(string $formHtml): string
    {
        if (preg_match('/<form\b[^>]*class\s*=/i', $formHtml)) {
            return preg_replace(
                '/(<form\b[^>]*class\s*=\s*["\'])([^"\']*)/i',
                '$1$2 lp-form-template__form',
                $formHtml,
                1
            ) ?? $formHtml;
        }

        return preg_replace(
            '/<form\b/i',
            '<form class="lp-form-template__form"',
            $formHtml,
            1
        ) ?? $formHtml;
    }
}
