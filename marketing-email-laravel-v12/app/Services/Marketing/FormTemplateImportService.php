<?php

namespace App\Services\Marketing;

class FormTemplateImportService
{
    public function parseFieldsFromHtml(string $html): array
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
                'contact_mapping'   => self::suggestContactMapping($name, $fieldType),
                'tag_from_value'    => false,
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
                'contact_mapping'   => self::suggestContactMapping($name, 'textarea'),
                'tag_from_value'    => false,
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
                'contact_mapping'   => self::suggestContactMapping($name, 'select'),
                'tag_from_value'    => false,
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

    public static function suggestContactMapping(string $fieldKey, string $fieldType): ?string
    {
        $map = [
            'first_name' => 'personal.first_name',
            'last_name'  => 'personal.last_name',
            'email'      => 'personal.email',
            'phone'      => 'personal.phone',
            'company_name' => 'business.company_name',
            'company'    => 'business.company_name',
            'tax_code'   => 'business.tax_code',
            'business_email' => 'business.business_email',
            'business_phone' => 'business.business_phone',
        ];

        $key = strtolower(trim($fieldKey));
        if (isset($map[$key])) {
            return $map[$key];
        }

        if ($fieldType === 'email') {
            return 'personal.email';
        }

        return null;
    }
}
