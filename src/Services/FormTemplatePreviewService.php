<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Models\FormField;
use Dth\Marketing\Models\FormTemplate;
use Illuminate\Support\HtmlString;

final class FormTemplatePreviewService
{
    public function render(FormTemplate $template, bool $disabled = true): HtmlString
    {
        $template->loadMissing('fields');

        if ($this->usesVisualTokens($template)) {
            return new HtmlString($this->renderVisualFragment($template, $disabled, true));
        }

        return new HtmlString($this->renderLegacyFragment($template, $disabled));
    }

    public function renderDocument(FormTemplate $template, bool $disabled = true): string
    {
        $template->loadMissing('fields');

        if (! $this->usesVisualTokens($template)) {
            $preview = $this->renderLegacyFragment($template, $disabled);

            return '<!doctype html><html lang="'.e(str_replace('_', '-', app()->getLocale())).'">'
                .'<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
                .'<title>'.e($template->name).'</title>'.$this->fallbackDocumentCss().'</head>'
                .'<body><main class="dth-form-preview-shell">'.$preview.'</main></body></html>';
        }

        $body = $this->renderVisualFragment($template, $disabled, false);
        $bodyAttrs = $this->bodyAttributes($template);
        $assets = $this->presentationAssets($template);

        return '<!doctype html><html lang="'.e(str_replace('_', '-', app()->getLocale())).'">'
            .'<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            .'<title>'.e($template->name).'</title>'.$assets.$this->previewSafetyCss().'</head>'
            .'<body'.$this->renderHtmlAttributes($bodyAttrs).'>'.$body.'</body></html>';
    }

    public function presentationAssets(FormTemplate $template): string
    {
        $assets = data_get($template->schema, 'presentation_assets', '');

        return is_string($assets) ? trim($assets) : '';
    }

    private function usesVisualTokens(FormTemplate $template): bool
    {
        return data_get($template->schema, 'render_mode') === 'visual_tokens_v3'
            && str_contains((string) $template->html_body, '{{control:');
    }

    private function renderVisualFragment(FormTemplate $template, bool $disabled, bool $wrapBody): string
    {
        $html = (string) ($template->html_body ?? '');
        $renderedKeys = [];
        $unplaced = '';

        foreach ($template->fields as $field) {
            $token = '{{control:'.(string) $field->field_key.'}}';
            $control = $this->renderControl($template, $field, $disabled);

            if (str_contains($html, $token)) {
                $html = str_replace($token, $control, $html);
                $renderedKeys[(string) $field->field_key] = true;
            } else {
                $unplaced .= $this->renderField($template, $field, $disabled);
            }
        }

        // A field removed in the Builder must not survive merely because the
        // imported visual HTML still contains its token.
        $html = preg_replace('/\{\{control:[^}]+\}\}/', '', $html) ?? $html;

        if ($unplaced !== '') {
            if (preg_match('/<button\b[^>]*data-dth-submit\b[^>]*>/i', $html)) {
                $html = preg_replace(
                    '/(<button\b[^>]*data-dth-submit\b[^>]*>)/i',
                    $unplaced.'$1',
                    $html,
                    1,
                ) ?? $html;
            } elseif (preg_match('/<\/form\s*>/i', $html)) {
                $html = preg_replace('/<\/form\s*>/i', $unplaced.'</form>', $html, 1) ?? $html;
            } else {
                $html .= $unplaced;
            }
        }

        $html = str_replace(
            ['{{fields}}', '{{submit_button_text}}'],
            ['', e($template->submit_button_text ?: 'Submit')],
            $html,
        );

        // M-D is still preview/display only. M-E will replace this guard with
        // the real submission action + CSRF/idempotency pipeline.
        if ($disabled || ! config('dth-marketing.features.public_submission', false)) {
            $html = preg_replace_callback('/<form\b([^>]*)>/i', static function (array $match): string {
                $attrs = (string) ($match[1] ?? '');
                $attrs = preg_replace('/\s+onsubmit\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attrs) ?? $attrs;

                return '<form'.$attrs.' onsubmit="return false;">';
            }, $html, 1) ?? $html;

            $html = preg_replace_callback('/<button\b([^>]*)>/i', static function (array $match): string {
                $attrs = (string) ($match[1] ?? '');

                return preg_match('/\bdisabled\b/i', $attrs)
                    ? $match[0]
                    : '<button'.$attrs.' disabled>';
            }, $html) ?? $html;

            $html = preg_replace_callback('/<input\b([^>]*)type\s*=\s*(["\']?)submit\2([^>]*)>/i', static function (array $match): string {
                return preg_match('/\bdisabled\b/i', $match[0])
                    ? $match[0]
                    : (preg_replace('/>$/', ' disabled>', $match[0]) ?? $match[0]);
            }, $html) ?? $html;
        }

        if (! $wrapBody) {
            return $html;
        }

        $attrs = $this->bodyAttributes($template);
        $class = trim('dth-marketing-form-document-root '.(string) ($attrs['class'] ?? ''));
        unset($attrs['class'], $attrs['id']);
        $attrs['class'] = $class;
        $attrs['data-dth-form-template-id'] = (string) $template->getKey();

        return '<div'.$this->renderHtmlAttributes($attrs).'>'.$html.'</div>';
    }

    private function renderLegacyFragment(FormTemplate $template, bool $disabled): string
    {
        $fieldsHtml = '';

        foreach ($template->fields as $field) {
            $fieldsHtml .= $this->renderField($template, $field, $disabled);
        }

        $submitText = e($template->submit_button_text ?: 'Submit');
        $storedHtml = trim((string) ($template->html_body ?? ''));

        if ($storedHtml !== '') {
            $html = str_replace(
                ['{{fields}}', '{{submit_button_text}}'],
                [$fieldsHtml, $submitText],
                $storedHtml,
            );

            if ($disabled || ! config('dth-marketing.features.public_submission', false)) {
                $html = preg_replace('/<form\b([^>]*)>/i', '<form$1 onsubmit="return false;">', $html, 1) ?? $html;
                $html = preg_replace_callback('/<button\b([^>]*)>/i', static function (array $match): string {
                    $attrs = (string) ($match[1] ?? '');

                    return preg_match('/\bdisabled\b/i', $attrs)
                        ? $match[0]
                        : '<button'.$attrs.' disabled>';
                }, $html) ?? $html;
            }

            return $html;
        }

        $disabledAttr = ($disabled || ! config('dth-marketing.features.public_submission', false)) ? ' disabled' : '';

        return '<div class="dth-marketing-form-preview">'
            .$fieldsHtml
            .'<button type="button"'.$disabledAttr.' class="dth-marketing-submit">'.$submitText.'</button>'
            .'</div>';
    }

    private function renderField(FormTemplate $template, FormField $field, bool $disabled): string
    {
        $type = $field->field_type instanceof FormFieldType
            ? $field->field_type
            : (FormFieldType::tryFrom((string) $field->field_type) ?? FormFieldType::Text);
        $control = $this->renderControl($template, $field, $disabled);

        if ($type === FormFieldType::Hidden) {
            return $control;
        }

        $label = e($field->label);
        if ($type === FormFieldType::Checkbox) {
            return '<div class="dth-marketing-field"><label class="dth-marketing-checkbox">'
                .$control.' <span>'.$label.'</span></label></div>';
        }

        return '<label class="dth-marketing-field"><span>'
            .$label.($field->is_required ? ' *' : '').'</span>'.$control.'</label>';
    }

    private function renderControl(FormTemplate $template, FormField $field, bool $disabled): string
    {
        $key = e((string) $field->field_key);
        $placeholder = e((string) ($field->placeholder ?? ''));
        $required = $field->is_required ? ' required' : '';
        $disabledAttr = ($disabled || ! config('dth-marketing.features.public_submission', false)) ? ' disabled' : '';
        $default = e((string) ($field->default_value ?? ''));
        $type = $field->field_type instanceof FormFieldType
            ? $field->field_type
            : (FormFieldType::tryFrom((string) $field->field_type) ?? FormFieldType::Text);
        $extraAttrs = $this->renderAttributes($this->importedAttributes($template, (string) $field->field_key));

        return match ($type) {
            FormFieldType::Textarea => '<textarea name="'.$key.'" placeholder="'.$placeholder.'"'.$extraAttrs.$required.$disabledAttr.'>'.$default.'</textarea>',
            FormFieldType::Select => $this->select($template, $field, $key, $extraAttrs.$required.$disabledAttr),
            FormFieldType::Checkbox => '<input type="checkbox" name="'.$key.'" value="'.($default !== '' ? $default : '1').'"'.$extraAttrs.$required.$disabledAttr.'>',
            FormFieldType::Hidden => '<input type="hidden" name="'.$key.'" value="'.$default.'">',
            FormFieldType::Email => '<input type="email" name="'.$key.'" value="'.$default.'" placeholder="'.$placeholder.'"'.$extraAttrs.$required.$disabledAttr.'>',
            FormFieldType::Phone => '<input type="tel" name="'.$key.'" value="'.$default.'" placeholder="'.$placeholder.'"'.$extraAttrs.$required.$disabledAttr.'>',
            default => '<input type="text" name="'.$key.'" value="'.$default.'" placeholder="'.$placeholder.'"'.$extraAttrs.$required.$disabledAttr.'>',
        };
    }

    private function select(FormTemplate $template, FormField $field, string $key, string $attributes): string
    {
        $placeholder = data_get($template->schema, 'select_placeholders.'.(string) $field->field_key, '--');
        $placeholder = is_string($placeholder) && trim($placeholder) !== '' ? trim($placeholder) : '--';
        $html = '<select name="'.$key.'"'.$attributes.'><option value="">'.e($placeholder).'</option>';
        $default = (string) ($field->default_value ?? '');

        foreach ((array) ($field->options ?? []) as $value => $label) {
            if (is_array($label)) {
                $optionValue = (string) ($label['value'] ?? $value);
                $optionLabel = (string) ($label['label'] ?? $optionValue);
            } else {
                $optionValue = (string) $value;
                $optionLabel = (string) $label;
            }

            $selected = $default !== '' && $default === $optionValue ? ' selected' : '';
            $html .= '<option value="'.e($optionValue).'"'.$selected.'>'.e($optionLabel).'</option>';
        }

        return $html.'</select>';
    }

    /** @return array<string, string> */
    private function importedAttributes(FormTemplate $template, string $key): array
    {
        $attributes = data_get($template->schema, 'field_attributes.'.$key, []);

        return is_array($attributes) ? array_filter(
            $attributes,
            static fn (mixed $value, mixed $name): bool => is_string($name) && is_scalar($value),
            ARRAY_FILTER_USE_BOTH,
        ) : [];
    }

    /** @return array<string, string> */
    private function bodyAttributes(FormTemplate $template): array
    {
        $attributes = data_get($template->schema, 'body_attributes', []);

        return is_array($attributes) ? array_filter(
            $attributes,
            static fn (mixed $value, mixed $name): bool => is_string($name) && is_scalar($value),
            ARRAY_FILTER_USE_BOTH,
        ) : [];
    }

    /** @param array<string, mixed> $attributes */
    private function renderAttributes(array $attributes): string
    {
        $allowed = ['id', 'class', 'style', 'autocomplete', 'inputmode', 'min', 'max', 'step', 'pattern', 'rows', 'cols', 'aria-label', 'aria-describedby'];
        $html = '';

        foreach ($allowed as $name) {
            if (! array_key_exists($name, $attributes)) {
                continue;
            }

            $value = trim((string) $attributes[$name]);
            if ($value === '') {
                continue;
            }

            $html .= ' '.e($name).'="'.e($value).'"';
        }

        return $html;
    }

    /** @param array<string, mixed> $attributes */
    private function renderHtmlAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $name => $value) {
            if (! is_string($name) || (! is_scalar($value) && $value !== null)) {
                continue;
            }

            $name = preg_replace('/[^a-zA-Z0-9_:\-.]/', '', $name) ?? '';
            if ($name === '' || $value === null) {
                continue;
            }

            $html .= ' '.e($name).'="'.e((string) $value).'"';
        }

        return $html;
    }

    private function previewSafetyCss(): string
    {
        return <<<'HTML'
<style id="dth-form-preview-safety">
html,body{min-height:100%}.dth-marketing-form-document-root{width:100%}.dth-marketing-imported-form button[disabled],.dth-marketing-imported-form input[disabled],.dth-marketing-imported-form select[disabled],.dth-marketing-imported-form textarea[disabled]{cursor:not-allowed}
</style>
HTML;
    }

    private function fallbackDocumentCss(): string
    {
        return <<<'HTML'
<style>
*{box-sizing:border-box}body{margin:0;background:#f8fafc;color:#0f172a;font-family:ui-sans-serif,system-ui,-apple-system,sans-serif}.dth-form-preview-shell{max-width:760px;margin:48px auto;padding:28px;background:#fff;border:1px solid #e2e8f0;border-radius:14px}.dth-marketing-form-preview{display:grid;gap:16px}.dth-marketing-field{display:grid;gap:6px;font-weight:600}.dth-marketing-field input,.dth-marketing-field textarea,.dth-marketing-field select{font:inherit;font-weight:400;padding:10px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff}.dth-marketing-submit{padding:11px 16px;border:0;border-radius:10px;background:#2563eb;color:#fff;font-weight:700}
</style>
HTML;
    }
}
