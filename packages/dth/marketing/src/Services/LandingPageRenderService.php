<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\SemanticFieldRole;
use Dth\Marketing\Models\FormField;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Source-parity renderer ported from
 * feature/crm-company-lead-opportunity-flow::App\Services\Marketing\LandingPageRenderService.
 *
 * The important invariant from the original source is that Form Template preview
 * and Landing Page runtime use the same form renderer and the same form theme.
 * Imported Landing Page form sections are not reused as runtime containers;
 * the system form section is appended before </body>.
 */
final class LandingPageRenderService
{
    public function __construct(
        private readonly LandingPageThemeService $themeService,
        private readonly LandingPageCatalogService $serviceCatalog,
    ) {}

    public function render(
        LandingPage $page,
        bool $preview = false,
        ?Request $request = null,
    ): string {
        $page->loadMissing(['personalFormTemplate.fields', 'businessFormTemplate.fields']);
        $request ??= request();

        $html = $this->normalizeStyleEntities((string) $page->html_body);

        if (blank($html)) {
            $html = '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{page_title}}</title>
</head>
<body>
    <h1>{{headline}}</h1>
    <p>{{subheadline}}</p>
    <div>{{content}}</div>
</body>
</html>';
        }

        if (filled($page->css_body)) {
            if (preg_match('/<\/head\s*>/i', $html)) {
                $html = preg_replace(
                    '/<\/head\s*>/i',
                    "<style id=\"dth-marketing-page-css\">\n{$page->css_body}\n</style>\n</head>",
                    $html,
                    1,
                ) ?? $html;
            } else {
                $html = "<style id=\"dth-marketing-page-css\">\n{$page->css_body}\n</style>\n".$html;
            }
        }

        $personalForm = $page->personalFormTemplate
            ? $this->renderResolvedFormTemplate(
                $page,
                $page->personalFormTemplate,
                'personal',
                $preview || ! config('dth-marketing.features.public_submission', false),
                $request,
            )
            : '';

        $businessForm = $page->businessFormTemplate
            ? $this->renderResolvedFormTemplate(
                $page,
                $page->businessFormTemplate,
                'business',
                $preview || ! config('dth-marketing.features.public_submission', false),
                $request,
            )
            : '';

        $formsSection = $this->renderTabbedFormsSection($page, $personalForm, $businessForm);

        $html = strtr($html, [
            '{{page_title}}' => e($page->page_title ?: $page->name),
            '{{headline}}' => e((string) ($page->headline ?? '')),
            '{{subheadline}}' => e((string) ($page->subheadline ?? '')),
            '{{content}}' => (string) ($page->content ?? ''),
            '{{cta_text}}' => e((string) ($page->cta_text ?? '')),
            '{{company_name}}' => e((string) config('app.name', 'Company')),
        ]);

        $themeCss = $this->formThemeCss();
        if (preg_match('/<\/head\s*>/i', $html)) {
            $html = preg_replace('/<\/head\s*>/i', $themeCss."\n</head>", $html, 1) ?? $html;
        } else {
            $html = $themeCss."\n".$html;
        }

        $html = $this->appendFormsToEnd($html, $formsSection);

        if ($preview) {
            $badge = '<div class="dth-marketing-preview-badge" style="position:fixed;right:18px;top:18px;z-index:2147483647;background:#111827;color:#fff;padding:8px 12px;border-radius:999px;font:600 12px/1.2 system-ui,sans-serif;box-shadow:0 8px 24px rgba(15,23,42,.22)">Preview</div>';
            $html = preg_match('/<\/body\s*>/i', $html)
                ? (preg_replace('/<\/body\s*>/i', $badge."\n</body>", $html, 1) ?? $html)
                : $html.$badge;
        }

        return $html;
    }

    public function previewFormTemplate(FormTemplate $template, ?LandingPage $page = null): string
    {
        $template->loadMissing('fields');
        $page ??= new LandingPage([
            'id' => 0,
            'name' => 'Form Preview',
            'slug' => 'form-preview',
            'theme_tokens' => $this->themeService->defaults(),
        ]);

        $type = $template->audience_type instanceof \BackedEnum
            ? (string) $template->audience_type->value
            : (string) $template->audience_type;
        $type = $type === 'business' ? 'business' : 'personal';

        $form = $this->renderResolvedFormTemplate($page, $template, $type, true, request());
        $section = $type === 'business'
            ? $this->renderTabbedFormsSection($page, '', $form)
            : $this->renderTabbedFormsSection($page, $form, '');

        return '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.e($template->name).'</title>
    '.$this->formThemeCss().'
</head>
<body style="margin:0;">'.$section.'</body>
</html>';
    }

    public function renderTemplateFragment(FormTemplate $template, bool $disabled = true): string
    {
        $template->loadMissing('fields');
        $page = new LandingPage([
            'id' => 0,
            'name' => 'Form Preview',
            'slug' => 'form-preview',
            'theme_tokens' => $this->themeService->defaults(),
        ]);
        $type = $template->audience_type instanceof \BackedEnum
            ? (string) $template->audience_type->value
            : (string) $template->audience_type;

        return $this->renderResolvedFormTemplate(
            $page,
            $template,
            $type === 'business' ? 'business' : 'personal',
            $disabled,
            request(),
        );
    }

    private function renderResolvedFormTemplate(
        LandingPage $page,
        FormTemplate $template,
        string $formType,
        bool $disabled,
        Request $request,
    ): string {
        $template->loadMissing('fields');

        $actionUrl = '#';
        if (Route::has('marketing.landing-pages.public.submit') && filled($page->slug)) {
            $actionUrl = route('marketing.landing-pages.public.submit', ['slug' => $page->slug]);
            $utm = [];
            foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $key) {
                $value = $request->query($key);
                if (is_scalar($value) && trim((string) $value) !== '') {
                    $utm[$key] = (string) $value;
                }
            }
            if ($utm !== []) {
                $actionUrl .= '?'.http_build_query($utm);
            }
        }

        $submissionToken = (string) Str::uuid();
        $fieldsHtml = $this->renderFields($template, $page, $formType, $disabled);
        $submitText = e($template->submit_button_text ?: 'Gửi thông tin');
        $formName = e((string) $template->name);
        $heading = $formName !== ''
            ? '<h3 style="font-size:20px;font-weight:700;color:#1e293b;text-align:center;margin:0 0 20px;">'.$formName.'</h3>'
            : '';

        $body = $this->canonicalTemplateBody($template);
        $body = $this->isolateRuntimeFormShell($body);

        $body = preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches) use ($actionUrl, $submissionToken, $disabled): string {
                $attributes = (string) ($matches[1] ?? '');
                $attributes = preg_replace('/\s+(?:method|action|target|onsubmit)\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attributes) ?? $attributes;

                $extra = $disabled
                    ? ' method="post" action="#" onsubmit="return false;"'
                    : ' method="post" action="'.e($actionUrl).'" data-lead-intake-form="'.e($submissionToken).'"';

                return '<form'.$attributes.$extra.'>';
            },
            $body,
            1,
        ) ?? $body;

        $hidden = '<input type="hidden" name="_token" value="'.e(csrf_token()).'">'
            .'<input type="hidden" name="form_template_id" value="'.e((string) $template->getKey()).'">'
            .'<input type="hidden" name="submission_type" value="'.e($formType).'">'
            .'<input type="hidden" name="_submission_token" value="'.e($submissionToken).'">';

        if (! $disabled) {
            $hidden .= '<div class="dth-marketing-honeypot" aria-hidden="true" style="position:absolute!important;left:-10000px!important;width:1px!important;height:1px!important;overflow:hidden!important">'
                .'<label>Website<input type="text" name="_dth_website" value="" tabindex="-1" autocomplete="off"></label></div>';
        }

        $body = preg_replace('/(<form\b[^>]*>)/i', '$1'.$hidden, $body, 1) ?? $body;
        $body = strtr($body, [
            '{{fields}}' => $fieldsHtml,
            '{{submit_button_text}}' => $submitText,
            '{{form_name}}' => $formName,
        ]);
        $body = preg_replace('/\{\{control:[^}]+\}\}/', '', $body) ?? $body;

        if ($disabled) {
            $body = preg_replace_callback('/<button\b([^>]*)>/i', static function (array $match): string {
                return preg_match('/\bdisabled\b/i', $match[0])
                    ? $match[0]
                    : '<button'.$match[1].' disabled>';
            }, $body) ?? $body;
            $body = preg_replace_callback('/<input\b([^>]*)type\s*=\s*(["\']?)submit\2([^>]*)>/i', static function (array $match): string {
                return preg_match('/\bdisabled\b/i', $match[0])
                    ? $match[0]
                    : (preg_replace('/>$/', ' disabled>', $match[0]) ?? $match[0]);
            }, $body) ?? $body;
        }

        $guard = $disabled ? '' : $this->renderSubmissionGuardScript($submissionToken);

        return $heading.$body.$guard;
    }

    private function canonicalTemplateBody(FormTemplate $template): string
    {
        $stored = trim((string) $template->html_body);

        // Exact source behavior for source-style templates: keep only the form
        // shell and replace its fields through {{fields}}. Current V3 visual-token
        // records are intentionally normalized to the same shell at render time,
        // so existing data does not require a migration/re-import.
        if ($stored !== '' && str_contains($stored, '{{fields}}') && preg_match('/<form\b/i', $stored)) {
            return $stored;
        }

        return '<div class="lp-form-template"><form class="lp-form-template__form">'
            .'{{fields}}<div style="margin-top:20px;">'
            .'<button type="submit" style="width:100%;padding:14px 20px;background:#2563eb;color:#fff;font-size:16px;font-weight:600;border:none;border-radius:8px;cursor:pointer;">{{submit_button_text}}</button>'
            .'</div></form></div>';
    }

    private function renderFields(FormTemplate $template, LandingPage $page, string $formType, bool $disabled): string
    {
        $groups = [];
        foreach ($template->fields as $field) {
            $groups[(int) $field->sort_order][] = $field;
        }
        ksort($groups);

        $html = '';
        foreach ($groups as $fields) {
            if (count($fields) === 1) {
                $html .= $this->renderField($fields[0], $page, $formType, false, $disabled);
                continue;
            }

            $html .= '<div style="display:grid;grid-template-columns:repeat('.count($fields).',minmax(0,1fr));gap:16px;margin-bottom:16px;">';
            foreach ($fields as $field) {
                $html .= $this->renderField($field, $page, $formType, true, $disabled);
            }
            $html .= '</div>';
        }

        return $html;
    }

    private function renderField(
        FormField $field,
        LandingPage $page,
        string $formType,
        bool $inGrid = false,
        bool $disabled = false,
    ): string {
        $label = e((string) $field->label);
        $key = e((string) $field->field_key);
        $placeholder = e((string) ($field->placeholder ?? ''));
        $semanticRole = $field->semantic_role instanceof SemanticFieldRole
            ? $field->semantic_role->value
            : (string) ($field->semantic_role ?? '');
        $isServiceInterest = $semanticRole === SemanticFieldRole::ServiceInterest->value
            || (string) ($field->contact_mapping ?? '') === 'lead.service_interest';
        $isRequired = (bool) $field->is_required || $isServiceInterest;
        $required = $isRequired ? ' required' : '';
        $requiredMark = $isRequired ? ' <span style="color:#e53e3e">*</span>' : '';
        $disabledAttr = $disabled ? ' disabled' : '';
        $type = $field->field_type instanceof FormFieldType
            ? $field->field_type->value
            : (string) $field->field_type;
        $inputStyle = 'width:100%;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;line-height:1.5;box-sizing:border-box;transition:border-color .2s,box-shadow .2s;outline:none;';

        if ($type === FormFieldType::Hidden->value) {
            return '<input type="hidden" name="'.$key.'" value="'.e((string) ($field->default_value ?? '')).'">';
        }

        if ($type === FormFieldType::Checkbox->value) {
            $value = e((string) ($field->default_value ?: '1'));
            $margin = $inGrid ? 'margin-bottom:0;' : 'margin-bottom:12px;';

            return '<div style="'.$margin.'"><label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:15px;">'
                .'<input type="checkbox" name="'.$key.'" value="'.$value.'"'.$required.$disabledAttr.' style="width:18px;height:18px;accent-color:#2563eb;"> '
                .$label.$requiredMark.'</label></div>';
        }

        $inputHtml = match ($type) {
            FormFieldType::Textarea->value => '<textarea name="'.$key.'" placeholder="'.$placeholder.'"'.$required.$disabledAttr.' style="'.$inputStyle.'min-height:100px;resize:vertical;">'.e((string) ($field->default_value ?? '')).'</textarea>',
            FormFieldType::Select->value => $this->renderSelect($field, $page, $formType, $key, $required.$disabledAttr, $inputStyle, $isServiceInterest),
            FormFieldType::Email->value => '<input type="email" name="'.$key.'" value="'.e((string) ($field->default_value ?? '')).'" placeholder="'.$placeholder.'"'.$required.$disabledAttr.' style="'.$inputStyle.'">',
            FormFieldType::Phone->value => '<input type="tel" name="'.$key.'" value="'.e((string) ($field->default_value ?? '')).'" placeholder="'.$placeholder.'"'.$required.$disabledAttr.' style="'.$inputStyle.'">',
            default => '<input type="text" name="'.$key.'" value="'.e((string) ($field->default_value ?? '')).'" placeholder="'.$placeholder.'"'.$required.$disabledAttr.' style="'.$inputStyle.'">',
        };

        $margin = $inGrid ? 'margin-bottom:0;' : 'margin-bottom:16px;';

        return '<div style="'.$margin.'"><label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#1e293b;">'
            .$label.$requiredMark.'</label>'.$inputHtml.'</div>';
    }

    private function renderSelect(
        FormField $field,
        LandingPage $page,
        string $formType,
        string $key,
        string $attributes,
        string $inputStyle,
        bool $serviceInterest,
    ): string {
        $options = $this->normalizeOptions((array) ($field->options ?? []));

        if ($serviceInterest && filled($page->service_reference)) {
            $catalogOptions = $this->sourceParityServiceOptions($page, $formType);
            if ($catalogOptions !== []) {
                $options = $catalogOptions;
            }
        }

        $html = '<option value="">-- Chọn --</option>';
        $default = (string) ($field->default_value ?? '');
        foreach ($options as $value => $label) {
            $selected = (string) $value === $default ? ' selected' : '';
            $html .= '<option value="'.e((string) $value).'"'.$selected.'>'.e((string) $label).'</option>';
        }

        return '<select name="'.$key.'"'.$attributes.' style="'.$inputStyle.'appearance:auto;">'.$html.'</select>';
    }

    /** @param array<mixed> $options @return array<string, string> */
    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $value => $label) {
            if (is_array($label)) {
                $resolvedValue = $label['value'] ?? $label['key'] ?? $value;
                $resolvedLabel = $label['label'] ?? $label['name'] ?? $resolvedValue;
            } elseif (is_int($value)) {
                $resolvedValue = $label;
                $resolvedLabel = $label;
            } else {
                $resolvedValue = $value;
                $resolvedLabel = $label;
            }

            if (is_scalar($resolvedValue) && is_scalar($resolvedLabel)) {
                $normalized[(string) $resolvedValue] = (string) $resolvedLabel;
            }
        }

        return $normalized;
    }

    /** @return array<string, string> */
    private function sourceParityServiceOptions(LandingPage $page, string $formType): array
    {
        if (! $this->serviceCatalog->available() || blank($page->service_reference)) {
            return [];
        }

        $available = $this->serviceCatalog->packageOptions((string) $page->service_reference);
        $selected = array_values(array_filter(array_map('strval', (array) ($page->package_references ?? []))));

        if ($selected !== []) {
            $available = array_intersect_key($available, array_flip($selected));
        }

        if ($available !== []) {
            return $available;
        }

        $serviceOptions = $this->serviceCatalog->serviceOptions($page->marketing_campaign_id ? (int) $page->marketing_campaign_id : null);
        $reference = (string) $page->service_reference;

        return isset($serviceOptions[$reference])
            ? [$reference => $serviceOptions[$reference]]
            : [];
    }

    private function renderTabbedFormsSection(LandingPage $page, string $personalForm, string $businessForm): string
    {
        if (blank($personalForm) && blank($businessForm)) {
            return '';
        }

        $sectionId = 'lp-forms-section-'.($page->getKey() ?: 'preview');
        $personalTab = filled($personalForm)
            ? '<button type="button" class="lp-form-tab is-active" data-lp-form-tab="personal">Cá nhân</button>'
            : '';
        $businessTab = filled($businessForm)
            ? '<button type="button" class="lp-form-tab'.(filled($personalForm) ? '' : ' is-active').'" data-lp-form-tab="business">Doanh nghiệp</button>'
            : '';
        $personalPanel = filled($personalForm)
            ? '<div class="lp-form-panel is-active" data-lp-form-panel="personal">'.$personalForm.'</div>'
            : '';
        $businessPanel = filled($businessForm)
            ? '<div class="lp-form-panel'.(filled($personalForm) ? '' : ' is-active').'" data-lp-form-panel="business"'.(filled($personalForm) ? ' hidden' : '').'>'.$businessForm.'</div>'
            : '';
        $themeVariables = $this->themeService->cssVariables((array) ($page->theme_tokens ?? []));

        return <<<HTML
<section id="{$sectionId}" class="lp-forms-section" style="{$themeVariables}">
    <div class="lp-forms-card">
        <div class="lp-forms-heading">
            <h2>Đăng ký nhận tư vấn</h2>
            <p>Chọn loại khách hàng phù hợp để gửi thông tin.</p>
        </div>
        <div class="lp-form-tabs">{$personalTab}{$businessTab}</div>
        <div class="lp-form-panels">{$personalPanel}{$businessPanel}</div>
    </div>
</section>
<script>
(function () {
    const root = document.getElementById('{$sectionId}');
    if (!root) return;
    const tabs = root.querySelectorAll('[data-lp-form-tab]');
    const panels = root.querySelectorAll('[data-lp-form-panel]');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const selected = tab.dataset.lpFormTab;
            tabs.forEach(function (item) { item.classList.toggle('is-active', item === tab); });
            panels.forEach(function (panel) {
                const active = panel.dataset.lpFormPanel === selected;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        });
    });
})();
</script>
HTML;
    }

    private function formThemeCss(): string
    {
        return <<<'CSS'
<style id="dth-marketing-source-form-theme">
.lp-forms-section{width:100%;padding:64px 20px;background:var(--lp-background);box-sizing:border-box}.lp-forms-card{width:min(720px,100%);margin:0 auto;padding:32px;color:var(--lp-text);background:var(--lp-surface);border:1px solid var(--lp-border);border-radius:var(--lp-radius);box-shadow:0 16px 40px rgba(15,23,42,.08);box-sizing:border-box}.lp-forms-heading{margin-bottom:24px;text-align:center}.lp-forms-heading h2{margin:0 0 8px;color:var(--lp-text);font-size:28px;line-height:1.25}.lp-forms-heading p{margin:0;color:var(--lp-muted-text)}.lp-form-tabs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-bottom:24px;padding:5px;background:color-mix(in srgb,var(--lp-primary) 8%,var(--lp-surface));border-radius:var(--lp-radius)}.lp-form-tab{padding:12px 18px;color:var(--lp-text);background:transparent;border:0;border-radius:calc(var(--lp-radius) - 4px);cursor:pointer;font:inherit;font-weight:600}.lp-form-tab.is-active{color:#fff;background:var(--lp-primary)}.lp-form-panel[hidden]{display:none!important}.lp-form-panel:not([hidden]){display:block!important}.lp-forms-section form{color:var(--lp-text)}.lp-forms-section form.lp-form-template__form{display:block!important;visibility:visible!important;opacity:1!important;transform:none!important;animation:none!important;position:static!important;width:100%!important;max-width:none!important;height:auto!important;overflow:visible!important}.lp-form-template{display:block!important;width:100%!important;max-width:none!important;height:auto!important;overflow:visible!important}.lp-forms-section label{color:var(--lp-text)!important}.lp-forms-section input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]),.lp-forms-section select,.lp-forms-section textarea{width:100%;padding:12px 14px;color:var(--lp-text)!important;background:var(--lp-surface)!important;border:1px solid var(--lp-border)!important;border-radius:calc(var(--lp-radius) - 4px)!important;box-sizing:border-box}.lp-forms-section input:focus,.lp-forms-section select:focus,.lp-forms-section textarea:focus{outline:none!important;border-color:var(--lp-primary)!important;box-shadow:0 0 0 3px color-mix(in srgb,var(--lp-primary) 18%,transparent)!important}.lp-forms-section button[type="submit"],.lp-forms-section input[type="submit"]{color:#fff!important;background:var(--lp-primary)!important;border:0!important;border-radius:calc(var(--lp-radius) - 4px)!important}.lp-forms-section button[type="submit"]:hover,.lp-forms-section input[type="submit"]:hover{background:var(--lp-primary-hover)!important}.dth-marketing-honeypot{position:absolute!important;left:-10000px!important;width:1px!important;height:1px!important;overflow:hidden!important}@media(max-width:640px){.lp-forms-section{padding:40px 14px}.lp-forms-card{padding:22px 16px}.lp-form-tabs{grid-template-columns:1fr}}
</style>
CSS;
    }

    private function appendFormsToEnd(string $html, string $formsSection): string
    {
        $html = str_replace(['{{form}}', '{{form_personal}}', '{{form_business}}', '{{forms_section}}'], '', $html);

        if (blank($formsSection)) {
            return $html;
        }

        if (preg_match('/<\/body\s*>/i', $html)) {
            return preg_replace('/<\/body\s*>/i', $formsSection."\n</body>", $html, 1) ?? $html;
        }

        return $html."\n".$formsSection;
    }

    private function isolateRuntimeFormShell(string $html): string
    {
        return preg_replace_callback('/<form\b([^>]*)>/i', static function (array $matches): string {
            $attributes = (string) ($matches[1] ?? '');
            $attributes = preg_replace('/\s+class\s*=\s*(["\']).*?\1/i', '', $attributes) ?? $attributes;
            $attributes = preg_replace('/\s+id\s*=\s*(["\']).*?\1/i', '', $attributes) ?? $attributes;
            $attributes = preg_replace('/\s+style\s*=\s*(["\']).*?\1/i', '', $attributes) ?? $attributes;

            return '<form'.$attributes.' class="lp-form-template__form">';
        }, $html, 1) ?? $html;
    }

    private function renderSubmissionGuardScript(string $submissionToken): string
    {
        $token = json_encode($submissionToken, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return <<<HTML
<script>
(function(){const token={$token};const form=document.querySelector('form[data-lead-intake-form="'+token+'"]');if(!form||form.dataset.leadIntakeBound==='1')return;form.dataset.leadIntakeBound='1';form.addEventListener('submit',function(){form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach(function(button){button.disabled=true;button.setAttribute('aria-disabled','true');});});})();
</script>
HTML;
    }

    private function normalizeStyleEntities(string $html): string
    {
        return preg_replace_callback('/<style\b([^>]*)>(.*?)<\/style>/is', static function (array $matches): string {
            $attributes = $matches[1];
            $css = $matches[2];

            for ($i = 0; $i < 3; $i++) {
                $normalized = preg_replace('/&amp;(#(?:x[0-9a-f]+|\d+);)/i', '&$1', $css) ?? $css;
                if ($normalized === $css) {
                    break;
                }
                $css = $normalized;
            }

            $css = preg_replace_callback('/&#(?:x[0-9a-f]+|\d+);/i', static fn (array $entity): string => html_entity_decode(
                $entity[0],
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            ), $css) ?? $css;

            return '<style'.$attributes.'>'.$css.'</style>';
        }, $html) ?? $html;
    }
}
