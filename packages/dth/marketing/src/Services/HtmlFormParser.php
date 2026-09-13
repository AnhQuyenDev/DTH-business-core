<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Illuminate\Support\Str;
use RuntimeException;

final class HtmlFormParser
{
    public function __construct(
        private readonly SemanticFieldResolver $semanticResolver,
    ) {}

    /** @return array<int, string> */
    public function extractForms(string $html): array
    {
        if (! preg_match_all('/<form\b[^>]*>.*?<\/form>/is', $html, $matches)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $form): string => trim($form),
            $matches[0],
        )));
    }

    public function detectAudienceType(string $formHtml): FormAudienceType
    {
        $text = Str::lower(strip_tags($formHtml).' '.$formHtml);
        $businessTokens = [
            'business', 'company', 'organization', 'organisation', 'enterprise',
            'tax_code', 'tax-code', 'tax code', 'mst', 'vat', 'legal_representative',
            'company_name', 'company-name', 'industry', 'position', 'job_title',
            'doanh nghiep', 'doanh_nghiep', 'cong ty', 'cong_ty', 'ma so thue',
            'ma_so_thue', 'chuc vu', 'chuc_vu',
        ];
        $personalTokens = [
            'personal', 'individual', 'first_name', 'first-name', 'last_name',
            'last-name', 'full_name', 'full-name', 'date_of_birth', 'birthday',
            'gender', 'occupation', 'ca nhan', 'ca_nhan', 'ho ten', 'ho_ten',
        ];

        $business = 0;
        foreach ($businessTokens as $token) {
            $business += substr_count($text, $token);
        }

        $personal = 0;
        foreach ($personalTokens as $token) {
            $personal += substr_count($text, $token);
        }

        return $business > $personal
            ? FormAudienceType::Business
            : FormAudienceType::Personal;
    }

    /**
     * @return array{
     *   fields: array<int, array<string, mixed>>,
     *   html_body: string,
     *   submit_button_text: ?string,
     *   schema: array<string, mixed>
     * }
     */
    public function prepareForm(string $sourceHtml, FormAudienceType $audienceType): array
    {
        $safeSource = $this->sanitizePresentationHtml($sourceHtml);
        $forms = $this->extractForms($safeSource);

        if ($forms === []) {
            throw new RuntimeException('The HTML file does not contain a <form> element.');
        }

        if (count($forms) > 1) {
            throw new RuntimeException('Each Form Template HTML import must contain exactly one <form> element.');
        }

        $formHtml = $this->sanitizeTemplateHtml($forms[0]);
        $fields = $this->extractFields($formHtml, $audienceType);

        if ($fields === []) {
            throw new RuntimeException('No supported input, select, or textarea fields were found in the form.');
        }

        $submitText = $this->extractSubmitButtonText($formHtml);
        $fieldAttributes = [];
        $selectPlaceholders = [];

        foreach ($fields as $field) {
            $key = (string) $field['field_key'];
            $fieldAttributes[$key] = $field['_html_attributes'] ?? [];

            if (filled($field['_select_placeholder'] ?? null)) {
                $selectPlaceholders[$key] = (string) $field['_select_placeholder'];
            }
        }

        // Source parity: imported Form Templates do not carry their full-page
        // stylesheet/runtime into Landing Pages. Only a sanitized form shell is
        // stored; the canonical Landing Page renderer owns runtime presentation.
        $visualBody = '<div class="lp-form-template">'.$this->buildFormShell($formHtml).'</div>';

        foreach ($fields as &$field) {
            unset(
                $field['_html_attributes'],
                $field['_source_offset'],
                $field['_source_length'],
                $field['_source_control_html'],
                $field['_select_placeholder'],
            );
        }
        unset($field);

        return [
            'fields' => $fields,
            'html_body' => $visualBody,
            'submit_button_text' => $submitText,
            'schema' => [
                'source' => 'html_import',
                'render_mode' => 'source_parity_v1',
                'field_attributes' => $fieldAttributes,
                'select_placeholders' => $selectPlaceholders,
                'presentation_assets' => '',
                'body_attributes' => [],
            ],
        ];
    }

    /**
     * Keep presentation resources required by imported HTML while removing
     * executable vectors. This intentionally mirrors the trusted-CDN policy
     * used by Landing Page import so Tailwind/browser CSS imports keep working.
     */
    public function sanitizePresentationHtml(string $html): string
    {
        $html = preg_replace_callback(
            '/<script\\b([^>]*)>(.*?)<\\/script>/is',
            function (array $match): string {
                $attributes = (string) ($match[1] ?? '');
                $body = (string) ($match[2] ?? '');

                if (preg_match('/\\bsrc\\s*=\\s*(["\\\'])(.*?)\\1/is', $attributes, $srcMatch)) {
                    return $this->isTrustedPresentationScript((string) $srcMatch[2]) ? $match[0] : '';
                }

                return stripos($body, 'tailwind.config') !== false ? $match[0] : '';
            },
            $html,
        ) ?? $html;

        $html = preg_replace('/<(iframe|object|embed)\\b[^>]*>.*?<\\/\\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<(iframe|object|embed)\\b[^>]*\\/?>/is', '', $html) ?? $html;
        $html = preg_replace('/<meta\\b[^>]*http-equiv\\s*=\\s*(["\\\']?)refresh\\1[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/\\son[a-z0-9_-]+\\s*=\\s*(?:"[^"]*"|\\\'[^\\\']*\\\'|[^\\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace('/\\b(?:javascript|vbscript)\\s*:/i', '', $html) ?? $html;
        $html = preg_replace('/data\\s*:\\s*text\\/html/i', '', $html) ?? $html;

        return trim($html);
    }

    public function extractPresentationAssets(string $html): string
    {
        // Scan the complete source, not only <head>. Some real HTML exports
        // place <style> or Tailwind configuration blocks inside <body>. Those
        // are presentation assets and must survive import for faithful preview.
        preg_match_all(
            '/<style\b[^>]*>.*?<\/style>|<link\b[^>]*rel\s*=\s*(?:"stylesheet"|\'stylesheet\'|stylesheet)[^>]*>|<script\b[^>]*>.*?<\/script>/is',
            $html,
            $matches,
        );

        $assets = [];
        foreach ($matches[0] ?? [] as $asset) {
            $asset = trim((string) $asset);
            if ($asset === '') {
                continue;
            }

            if (str_starts_with(strtolower($asset), '<script')) {
                if (preg_match('/\\bsrc\\s*=\\s*(["\\\'])(.*?)\\1/is', $asset, $src)) {
                    if (! $this->isTrustedPresentationScript((string) $src[2])) {
                        continue;
                    }
                } elseif (stripos($asset, 'tailwind.config') === false) {
                    continue;
                }
            }

            $assets[] = $asset;
        }

        return implode("\n", array_values(array_unique($assets)));
    }

    /** @return array<string, string> */
    public function extractBodyAttributes(string $html): array
    {
        if (! preg_match('/<body\\b([^>]*)>/is', $html, $match)) {
            return [];
        }

        $attrs = $this->parseAttributes((string) $match[1]);
        $safe = [];
        foreach (['class', 'style', 'id'] as $name) {
            if (isset($attrs[$name]) && is_string($attrs[$name]) && trim($attrs[$name]) !== '') {
                $safe[$name] = trim($attrs[$name]);
            }
        }

        return $safe;
    }

    private function extractBodyInner(string $html): string
    {
        if (preg_match('/<body\\b[^>]*>(.*?)<\\/body>/is', $html, $match)) {
            return trim((string) $match[1]);
        }

        $html = preg_replace('/<!doctype[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/<head\\b[^>]*>.*?<\\/head>/is', '', $html) ?? $html;
        $html = preg_replace('/<\\/?html\\b[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/<\\/?body\\b[^>]*>/is', '', $html) ?? $html;

        return trim($html);
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function buildVisualBody(string $safeSource, string $formHtml, array $fields): string
    {
        $tokenized = $this->tokenizeForm($formHtml, $fields);
        $body = $this->extractBodyInner($safeSource);
        $position = strpos($body, $formHtml);

        if ($position !== false) {
            return trim(substr_replace($body, $tokenized, $position, strlen($formHtml)));
        }

        // Fallback for HTML parsers/browsers that normalized whitespace between
        // extraction and body parsing. The form itself is still preserved.
        return trim($tokenized);
    }

    /** @param array<int, array<string, mixed>> $fields */
    private function tokenizeForm(string $formHtml, array $fields): string
    {
        $replacements = [];
        foreach ($fields as $field) {
            if (! isset($field['_source_offset'], $field['_source_length'])) {
                continue;
            }

            $replacements[] = [
                'offset' => (int) $field['_source_offset'],
                'length' => (int) $field['_source_length'],
                'value' => '{{control:'.(string) $field['field_key'].'}}',
            ];
        }

        usort($replacements, static fn (array $a, array $b): int => $b['offset'] <=> $a['offset']);
        foreach ($replacements as $replacement) {
            $formHtml = substr_replace(
                $formHtml,
                $replacement['value'],
                $replacement['offset'],
                $replacement['length'],
            );
        }

        $formHtml = preg_replace_callback(
            '/<form\\b([^>]*)>/i',
            function (array $match): string {
                $attrs = $this->parseAttributes((string) ($match[1] ?? ''));
                unset($attrs['action'], $attrs['method'], $attrs['target'], $attrs['onsubmit']);
                $class = trim((string) ($attrs['class'] ?? ''));
                $attrs['class'] = trim($class.' dth-marketing-imported-form');
                $attrs['data-dth-form-template'] = '1';

                return '<form'.$this->renderAttributes($attrs).'>';
            },
            $formHtml,
            1,
        ) ?? $formHtml;

        $formHtml = preg_replace_callback(
            '/<button\\b([^>]*)>(.*?)<\\/button>/is',
            function (array $match): string {
                $attrs = $this->parseAttributes((string) ($match[1] ?? ''));
                $type = Str::lower((string) ($attrs['type'] ?? 'submit'));
                if ($type !== 'submit') {
                    return $match[0];
                }

                unset($attrs['onclick']);
                $attrs['type'] = 'submit';
                $attrs['data-dth-submit'] = '1';

                return '<button'.$this->renderAttributes($attrs).'>{{submit_button_text}}</button>';
            },
            $formHtml,
            1,
        ) ?? $formHtml;

        return $formHtml;
    }

    private function isTrustedPresentationScript(string $src): bool
    {
        $src = trim($src);
        if (str_starts_with($src, '//')) {
            $src = 'https:'.$src;
        }

        $host = strtolower((string) parse_url($src, PHP_URL_HOST));

        return in_array($host, [
            'cdn.tailwindcss.com',
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'unpkg.com',
        ], true);
    }

    /** @return array<int, array<string, mixed>> */
    private function extractFields(string $formHtml, FormAudienceType $audienceType): array
    {
        $labels = $this->labelsByFor($formHtml);
        $pattern = '/<input\b[^>]*>|<textarea\b[^>]*>.*?<\/textarea>|<select\b[^>]*>.*?<\/select>/is';

        if (! preg_match_all($pattern, $formHtml, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $fields = [];
        $usedKeys = [];
        $radioGroups = [];
        $position = 0;

        foreach ($matches[0] as [$controlHtml, $offset]) {
            $controlHtml = (string) $controlHtml;
            $tag = $this->controlTagName($controlHtml);
            $attrs = $this->parseAttributes($this->openingTagAttributes($controlHtml));
            $inputType = Str::lower((string) ($attrs['type'] ?? ($tag === 'input' ? 'text' : $tag)));

            if ($tag === 'input' && in_array($inputType, ['hidden', 'submit', 'button', 'reset', 'image', 'file'], true)) {
                continue;
            }

            $label = $this->resolveLabel($formHtml, (int) $offset, $controlHtml, $attrs, $labels);
            $placeholder = trim((string) ($attrs['placeholder'] ?? ''));
            $rawName = trim((string) ($attrs['name'] ?? ''));
            $rawKey = $rawName !== '' ? $rawName : ($label !== '' ? $label : ($placeholder !== '' ? $placeholder : 'field_'.$position));
            $normalizedBase = $this->normalizeKey($rawKey);

            if ($inputType === 'radio' && $rawName !== '') {
                $groupKey = $this->normalizeKey($rawName);
                if (isset($radioGroups[$groupKey])) {
                    $index = $radioGroups[$groupKey];
                    $value = trim((string) ($attrs['value'] ?? $label));
                    if ($value !== '') {
                        $fields[$index]['options'][$value] = $label !== '' ? $label : Str::headline($value);
                    }
                    continue;
                }
            }

            $fieldKey = $this->makeUniqueKey($normalizedBase, $usedKeys);
            $fieldType = $this->resolveFieldType($tag, $inputType);
            $options = $this->extractOptions($tag, $controlHtml, $attrs, $label);
            $default = $this->extractDefaultValue($tag, $controlHtml, $attrs);
            $validation = $this->inferValidationRules($inputType, $attrs);
            $effectiveLabel = $label !== '' ? $label : Str::headline($fieldKey);
            $semantic = $this->semanticResolver->resolve(
                fieldKey: $fieldKey,
                label: $effectiveLabel,
                placeholder: $placeholder !== '' ? $placeholder : null,
                fieldType: $fieldType,
                audienceType: $audienceType,
                htmlAttributes: $attrs,
            );
            $semanticRole = $semantic['role'];

            $fields[] = [
                'label' => $effectiveLabel,
                'field_key' => $fieldKey,
                'field_type' => $fieldType->value,
                'placeholder' => $placeholder !== '' ? $placeholder : null,
                'options' => $options,
                'default_value' => $default,
                'is_required' => array_key_exists('required', $attrs),
                // Keep the previous lead.* value only as an integration-compatibility
                // bridge. Marketing now owns the module-neutral semantic role.
                'contact_mapping' => $semantic['source'] === 'auto' ? $semanticRole?->legacyContactMapping() : null,
                'semantic_role' => $semanticRole?->value,
                'semantic_confidence' => (int) $semantic['confidence'],
                'semantic_source' => $semantic['source'],
                'validation_rules' => $validation,
                'sort_order' => $position,
                '_html_attributes' => $this->safeFieldAttributes($attrs),
                '_source_offset' => (int) $offset,
                '_source_length' => strlen($controlHtml),
                '_source_control_html' => $controlHtml,
                '_select_placeholder' => $tag === 'select' ? $this->extractSelectPlaceholder($controlHtml) : null,
            ];

            if ($inputType === 'radio' && $rawName !== '') {
                $radioGroups[$this->normalizeKey($rawName)] = array_key_last($fields);
            }

            $position++;
        }

        return $fields;
    }

    private function resolveFieldType(string $tag, string $inputType): FormFieldType
    {
        if ($tag === 'textarea') {
            return FormFieldType::Textarea;
        }

        if ($tag === 'select' || $inputType === 'radio') {
            return FormFieldType::Select;
        }

        return match ($inputType) {
            'email' => FormFieldType::Email,
            'tel', 'phone' => FormFieldType::Phone,
            'checkbox' => FormFieldType::Checkbox,
            default => FormFieldType::Text,
        };
    }

    /** @return array<string, string>|null */
    private function extractOptions(string $tag, string $controlHtml, array $attrs, string $label): ?array
    {
        if ($tag === 'select') {
            $options = [];
            if (preg_match_all('/<option\b([^>]*)>(.*?)<\/option>/is', $controlHtml, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $optionAttrs = $this->parseAttributes((string) $match[1]);
                    $optionLabel = trim(html_entity_decode(strip_tags((string) $match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $value = array_key_exists('value', $optionAttrs)
                        ? trim((string) $optionAttrs['value'])
                        : $optionLabel;

                    if ($value === '') {
                        continue;
                    }

                    $options[$value] = $optionLabel !== '' ? $optionLabel : $value;
                }
            }

            return $options !== [] ? $options : null;
        }

        if (($attrs['type'] ?? null) === 'radio') {
            $value = trim((string) ($attrs['value'] ?? ''));

            return $value !== '' ? [$value => ($label !== '' ? $label : Str::headline($value))] : null;
        }

        return null;
    }

    private function extractSelectPlaceholder(string $controlHtml): ?string
    {
        if (! preg_match_all('/<option\\b([^>]*)>(.*?)<\\/option>/is', $controlHtml, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $match) {
            $attrs = $this->parseAttributes((string) $match[1]);
            $value = array_key_exists('value', $attrs) ? trim((string) $attrs['value']) : null;
            if ($value !== '') {
                continue;
            }

            $label = trim(html_entity_decode(strip_tags((string) $match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            return $label !== '' ? $label : null;
        }

        return null;
    }

    private function extractDefaultValue(string $tag, string $controlHtml, array $attrs): ?string
    {
        if ($tag === 'textarea' && preg_match('/<textarea\b[^>]*>(.*?)<\/textarea>/is', $controlHtml, $match)) {
            $value = trim(html_entity_decode(strip_tags((string) $match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return $value !== '' ? $value : null;
        }

        if ($tag === 'select' && preg_match('/<option\b([^>]*)selected(?:\s|=|>)([^>]*)>(.*?)<\/option>/is', $controlHtml, $match)) {
            $attrsSelected = $this->parseAttributes((string) $match[1].' selected '.(string) $match[2]);
            $label = trim(html_entity_decode(strip_tags((string) $match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $value = trim((string) ($attrsSelected['value'] ?? $label));

            return $value !== '' ? $value : null;
        }

        $value = trim((string) ($attrs['value'] ?? ''));

        return $value !== '' ? $value : null;
    }

    private function inferValidationRules(string $inputType, array $attrs): ?string
    {
        $rules = [];

        if ($inputType === 'email') {
            $rules[] = 'email';
        }

        if (isset($attrs['minlength']) && ctype_digit((string) $attrs['minlength'])) {
            $rules[] = 'min:'.(int) $attrs['minlength'];
        }

        if (isset($attrs['maxlength']) && ctype_digit((string) $attrs['maxlength'])) {
            $rules[] = 'max:'.(int) $attrs['maxlength'];
        }

        return $rules !== [] ? implode('|', array_unique($rules)) : null;
    }


    public function sanitizeTemplateHtml(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<(iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<(iframe|object|embed)\b[^>]*\/?>/is', '', $html) ?? $html;
        $html = preg_replace('/<meta\b[^>]*http-equiv\s*=\s*(["\']?)refresh\1[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z0-9_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace('/\b(?:javascript|vbscript)\s*:/i', '', $html) ?? $html;
        $html = preg_replace('/data\s*:\s*text\/html/i', '', $html) ?? $html;

        return trim($html);
    }

    private function buildFormShell(string $formHtml): string
    {
        if (! preg_match('/<form\b([^>]*)>/i', $formHtml, $open)) {
            return '<form class="dth-marketing-imported-form">{{fields}}<button type="submit">{{submit_button_text}}</button></form>';
        }

        $attrs = $this->parseAttributes((string) $open[1]);
        unset($attrs['action'], $attrs['method'], $attrs['target'], $attrs['onsubmit']);

        $class = trim((string) ($attrs['class'] ?? ''));
        $attrs['class'] = trim($class.' dth-marketing-imported-form');
        $openTag = '<form'.$this->renderAttributes($attrs).'>';

        $submit = '';
        if (preg_match_all('/<button\b([^>]*)>(.*?)<\/button>|<input\b([^>]*)type\s*=\s*(?:"submit"|\'submit\'|submit)[^>]*>/is', $formHtml, $matches, PREG_SET_ORDER)) {
            $last = end($matches);
            if (is_array($last)) {
                $submit = (string) $last[0];

                if (preg_match('/^<button\b([^>]*)>/i', $submit, $buttonOpen)) {
                    $buttonAttrs = $this->parseAttributes((string) $buttonOpen[1]);
                    unset($buttonAttrs['onclick']);
                    $buttonAttrs['type'] = 'submit';
                    $submit = '<button'.$this->renderAttributes($buttonAttrs).'>{{submit_button_text}}</button>';
                } elseif (preg_match('/^<input\b([^>]*)>/i', $submit, $inputOpen)) {
                    $buttonAttrs = $this->parseAttributes((string) $inputOpen[1]);
                    unset($buttonAttrs['onclick']);
                    $buttonAttrs['type'] = 'submit';
                    $buttonAttrs['value'] = '{{submit_button_text}}';
                    $submit = '<input'.$this->renderAttributes($buttonAttrs).'>';
                }
            }
        }

        if ($submit === '') {
            $submit = '<button type="submit">{{submit_button_text}}</button>';
        }

        // Original source keeps hidden inputs while replacing visible controls
        // with {{fields}}. Preserve the same behavior for imported forms.
        $hidden = '';
        if (preg_match_all('/<input\b[^>]*type\s*=\s*(["\']?)hidden\1[^>]*>/i', $formHtml, $hiddenMatches)) {
            $hidden = implode("\n", $hiddenMatches[0]);
            if ($hidden !== '') {
                $hidden .= "\n";
            }
        }

        return $openTag."\n".$hidden."{{fields}}\n".$submit."\n</form>";
    }

    private function extractSubmitButtonText(string $formHtml): ?string
    {
        if (preg_match_all('/<button\b([^>]*)>(.*?)<\/button>/is', $formHtml, $matches, PREG_SET_ORDER)) {
            foreach (array_reverse($matches) as $match) {
                $attrs = $this->parseAttributes((string) $match[1]);
                $type = Str::lower((string) ($attrs['type'] ?? 'submit'));
                if ($type !== 'submit') {
                    continue;
                }

                $text = trim(html_entity_decode(strip_tags((string) $match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($text !== '') {
                    return $text;
                }
            }
        }

        if (preg_match('/<input\b([^>]*)type\s*=\s*(?:"submit"|\'submit\'|submit)[^>]*>/i', $formHtml, $match)) {
            $attrs = $this->parseAttributes((string) $match[1]);
            $value = trim((string) ($attrs['value'] ?? ''));

            return $value !== '' ? $value : null;
        }

        return null;
    }

    /** @return array<string, string> */
    private function labelsByFor(string $formHtml): array
    {
        $labels = [];
        if (! preg_match_all('/<label\b([^>]*)>(.*?)<\/label>/is', $formHtml, $matches, PREG_SET_ORDER)) {
            return $labels;
        }

        foreach ($matches as $match) {
            $attrs = $this->parseAttributes((string) $match[1]);
            $for = trim((string) ($attrs['for'] ?? ''));
            if ($for === '') {
                continue;
            }

            $text = trim(html_entity_decode(strip_tags((string) $match[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text !== '') {
                $labels[$for] = $text;
            }
        }

        return $labels;
    }

    private function resolveLabel(string $formHtml, int $offset, string $controlHtml, array $attrs, array $labels): string
    {
        $id = trim((string) ($attrs['id'] ?? ''));
        if ($id !== '' && isset($labels[$id])) {
            return $labels[$id];
        }

        $before = substr($formHtml, 0, $offset);

        $labelStart = false;
        $labelOpeningEnd = 0;
        if (preg_match_all('/<label\b[^>]*>/is', $before, $labelMatches, PREG_OFFSET_CAPTURE)) {
            $lastLabel = end($labelMatches[0]);
            if (is_array($lastLabel)) {
                $labelStart = (int) $lastLabel[1];
                $labelOpeningEnd = $labelStart + strlen((string) $lastLabel[0]);
            }
        }

        $previousControlEnd = 0;
        $controlPattern = '/<input\b[^>]*>|<textarea\b[^>]*>.*?<\/textarea>|<select\b[^>]*>.*?<\/select>/is';
        if (preg_match_all($controlPattern, $before, $controlMatches, PREG_OFFSET_CAPTURE)) {
            $lastControl = end($controlMatches[0]);
            if (is_array($lastControl)) {
                $previousControlEnd = (int) $lastControl[1] + strlen((string) $lastControl[0]);
            }
        }

        if ($labelStart !== false) {
            $textStart = max($labelOpeningEnd, $previousControlEnd);
            $text = trim(html_entity_decode(
                strip_tags(substr($formHtml, $textStart, $offset - $textStart)),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            ));

            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    /** @return array<string, string|true> */
    private function parseAttributes(string $raw): array
    {
        $attributes = [];
        preg_match_all(
            '/([a-zA-Z_:][a-zA-Z0-9_:\-.]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?/s',
            $raw,
            $matches,
            PREG_SET_ORDER,
        );

        foreach ($matches as $match) {
            $name = Str::lower((string) $match[1]);
            $value = $match[2] ?? $match[3] ?? $match[4] ?? true;
            $attributes[$name] = $value === '' ? '' : $value;
        }

        return $attributes;
    }

    private function openingTagAttributes(string $controlHtml): string
    {
        return preg_match('/^<[a-z0-9]+\b([^>]*)>/is', $controlHtml, $match)
            ? (string) $match[1]
            : '';
    }

    private function controlTagName(string $controlHtml): string
    {
        return preg_match('/^<([a-z0-9]+)/i', $controlHtml, $match)
            ? Str::lower((string) $match[1])
            : 'input';
    }

    private function normalizeKey(string $raw): string
    {
        $ascii = Str::lower(Str::ascii($raw));
        $key = preg_replace('/[^a-z0-9]+/', '_', $ascii) ?? '';
        $key = trim($key, '_');

        if ($key === '' || ! ctype_alpha($key[0])) {
            $key = 'field_'.($key !== '' ? $key : Str::lower(Str::random(6)));
        }

        return substr($key, 0, 100);
    }

    private function makeUniqueKey(string $base, array &$used): string
    {
        $candidate = $base;
        $counter = 2;
        while (isset($used[$candidate])) {
            $suffix = '_'.$counter++;
            $candidate = substr($base, 0, 100 - strlen($suffix)).$suffix;
        }
        $used[$candidate] = true;

        return $candidate;
    }

    /** @return array<string, string> */
    private function safeFieldAttributes(array $attrs): array
    {
        $allowed = ['id', 'class', 'style', 'autocomplete', 'inputmode', 'min', 'max', 'step', 'pattern', 'rows', 'cols', 'aria-label', 'aria-describedby'];
        $safe = [];

        foreach ($allowed as $name) {
            if (! isset($attrs[$name]) || ! is_string($attrs[$name])) {
                continue;
            }

            $value = trim($attrs[$name]);
            if ($value !== '') {
                $safe[$name] = $value;
            }
        }

        return $safe;
    }

    private function renderAttributes(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $name => $value) {
            if ($value === true) {
                $html .= ' '.htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $html .= ' '.htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8')
                .'="'.htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8').'"';
        }

        return $html;
    }
}
