<?php

namespace App\Services\Marketing;

use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;

class LandingPageRenderService
{
    public function __construct(private readonly LandingPageThemeService $themeService) {}

    public function render(
        LandingPage $landingPage,
        string $formType = 'personal'
    ): string {
        $html = $this->normalizeStyleEntities(
            (string) $landingPage->html_body
        );

        if (blank($html)) {
            $html = '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
            content="width=device-width, initial-scale=1.0">
        <title>{{page_title}}</title>
    </head>
    <body>
        <h1>{{headline}}</h1>
        <p>{{subheadline}}</p>
        <div>{{content}}</div>
    </body>
    </html>';
        }

        if (filled($landingPage->css_body)) {
            $html = str_replace(
                '</head>',
                "<style>\n{$landingPage->css_body}\n</style>\n</head>",
                $html
            );
        }

        $html = $this->ensureTailwindConfig($html);

        $personalForm = $this->renderForm(
            $landingPage,
            'personal',
            false
        );

        $businessForm = $this->renderForm(
            $landingPage,
            'business',
            false
        );

        $formsSection = $this->renderTabbedFormsSection(
            $landingPage,
            $personalForm,
            $businessForm
        );

        $ctaText = e($landingPage->cta_text ?? '');

        $html = strtr($html, [
            '{{page_title}}' => e($landingPage->page_title ?? $landingPage->name),
            '{{headline}}' => e($landingPage->headline ?? ''),
            '{{subheadline}}' => e($landingPage->subheadline ?? ''),
            '{{content}}' => $landingPage->content ?? '',
            '{{cta_text}}' => $ctaText,
            '{{company_name}}' => e(config('app.name', 'Company')),
        ]);

        $themeCss = $this->formThemeCss();

        if (preg_match('/<\/head>/i', $html)) {
            $html = preg_replace(
                '/<\/head>/i',
                $themeCss."\n</head>",
                $html,
                1
            ) ?? $html;
        } else {
            $html = $themeCss.$html;
        }

        return $this->appendFormsToEnd(
            $html,
            $formsSection
        );
    }

    protected function renderGenericForm(LandingPage $landingPage): ?string
    {
        $landingPageForm = $landingPage->forms()
            ->where('form_type', 'generic')
            ->where('status', 'active')
            ->first();

        if (! $landingPageForm || ! $landingPageForm->formTemplate) {
            return null;
        }

        return $this->renderForm($landingPage, 'generic', false);
    }

    protected function hasBothForms(LandingPage $landingPage): bool
    {
        $hasPersonal = $landingPage->forms()->where('form_type', 'personal')->where('status', 'active')->exists();
        $hasBusiness = $landingPage->forms()->where('form_type', 'business')->where('status', 'active')->exists();

        return $hasPersonal && $hasBusiness;
    }

    protected function renderResolvedFormTemplate(
        LandingPage $landingPage,
        FormTemplate $formTemplate,
        string $formType,
        bool $showTypeSelector = false
    ): string {
        $actionUrl = route('marketing.landing-pages.public.submit', $landingPage->slug);

        $utmQuery = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $utmParam) {
            $utmValue = request()->query($utmParam);
            if ($utmValue !== null) {
                $utmQuery[$utmParam] = $utmValue;
            }
        }
        if ($utmQuery) {
            $actionUrl .= '?'.http_build_query($utmQuery);
        }
        $csrfToken = csrf_token();
        $submitText = e($formTemplate->submit_button_text ?: 'Gửi thông tin');
        $formName = e($formTemplate->name ?? '');
        $typeSelector = $showTypeSelector ? $this->renderTypeSelector($landingPage, $formType) : '';

        $fieldsHtml = '';
        $groups = [];
        foreach ($formTemplate->fields as $field) {
            $groups[$field->sort_order][] = $field;
        }
        foreach ($groups as $fields) {
            if (count($fields) === 1) {
                $fieldsHtml .= $this->renderField($fields[0]);
            } else {
                $fieldsHtml .= '<div style="display:grid;grid-template-columns:repeat('.count($fields).',1fr);gap:16px;margin-bottom:16px;">';
                foreach ($fields as $field) {
                    $fieldsHtml .= $this->renderField($field, true);
                }
                $fieldsHtml .= '</div>';
            }
        }

        $heading = $formName ? '<h3 style="font-size:20px;font-weight:700;color:#1e293b;text-align:center;margin:0 0 20px;">'.$formName.'</h3>' : '';

        if ($formTemplate->html_body) {
            $body = $formTemplate->html_body;
            $body = $this->isolateRuntimeFormShell($body);

            // Defensive: nếu HTML cũ chưa có {{fields}}, normalize runtime để tương thích.
            if (! str_contains($body, '{{fields}}')) {
                $body = self::sanitizeHtmlBody($body);
            }

            // Ensure method="POST" on the form tag
            $body = preg_replace_callback(
                '/<form\b([^>]*)>/i',
                static function (array $matches): string {
                    $attributes = $matches[1];

                    $attributes = preg_replace(
                        '/\s+method\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                        '',
                        $attributes
                    ) ?? $attributes;

                    $attributes = preg_replace(
                        '/\s+action\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                        '',
                        $attributes
                    ) ?? $attributes;

                    return '<form'
                        .$attributes
                        .' method="POST"'
                        .' action="{{action_url}}">';
                },
                $body,
                1
            ) ?? $body;

            if (! str_contains($body, '{{csrf_token}}')) {
                $body = preg_replace(
                    '/(<form\b[^>]*>)/i',
                    '$1'."\n".
                    '<input type="hidden" name="_token" value="{{csrf_token}}">',
                    $body,
                    1
                ) ?? $body;
            }

            $inject = '<input type="hidden" name="form_template_id" value="'.$formTemplate->id.'">'."\n";
            $inject .= '<input type="hidden" name="submission_type" value="'.$formType.'">'."\n";
            $body = preg_replace(
                '/(<form\b[^>]*>)/i',
                '$1'."\n".$inject,
                $body,
                1
            ) ?? $body;

            $formHtml = strtr($body, [
                '{{form_name}}' => $formName,
                '{{submit_button_text}}' => $submitText,
                '{{action_url}}' => $actionUrl,
                '{{csrf_token}}' => $csrfToken,
                '{{fields}}' => $fieldsHtml,
                '{{type_selector}}' => $typeSelector,
            ]);

            return $heading.$formHtml;
        }

        return $heading.<<<HTML
        <div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:32px;box-shadow:0 4px 12px rgba(0,0,0,0.08);box-sizing:border-box;">
            {$typeSelector}
            <form method="POST" action="{$actionUrl}" novalidate style="margin:0;">
                <input type="hidden" name="_token" value="{$csrfToken}">
                <input type="hidden" name="submission_type" value="{$formType}">
                <input type="hidden" name="form_template_id" value="{$formTemplate->id}">
                {$fieldsHtml}
                <div style="margin-top:20px;">
                    <button type="submit" style="width:100%;padding:14px 20px;background:#2563eb;color:#fff;font-size:16px;font-weight:600;border:none;border-radius:8px;cursor:pointer;transition:background .2s;">{$submitText}</button>
                </div>
            </form>
        </div>
        HTML;
    }

    public function renderForm(
        LandingPage $landingPage,
        string $formType = 'personal',
        bool $showTypeSelector = false
    ): string {
        $formTemplate = $this->resolveFormTemplate(
            $landingPage,
            $formType
        );

        if (! $formTemplate) {
            return '';
        }

        return $this->renderResolvedFormTemplate(
            $landingPage,
            $formTemplate,
            $formType,
            $showTypeSelector
        );
    }

    public function previewFormTemplate(
        FormTemplate $formTemplate,
        ?LandingPage $landingPage = null
    ): string {
        $landingPage ??= new LandingPage([
            'id' => 0,
            'name' => 'Form Preview',
            'slug' => 'form-preview',
            'theme_tokens' => app(
                LandingPageThemeService::class
            )->defaults(),
        ]);

        $formType = $formTemplate->audience_type?->value
            ?? (string) $formTemplate->audience_type;

        $formHtml = $this->renderResolvedFormTemplate(
            $landingPage,
            $formTemplate,
            $formType,
            false
        );

        $section = $formType === 'business'
            ? $this->renderTabbedFormsSection(
                $landingPage,
                '',
                $formHtml
            )
            : $this->renderTabbedFormsSection(
                $landingPage,
                $formHtml,
                ''
            );

        return '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
            content="width=device-width, initial-scale=1.0">
        <title>Xem trước Form Template</title>'
        .$this->formThemeCss().
    '</head>
    <body style="margin:0;">'
        .$section.
    '</body>
    </html>';
    }

    protected function renderTypeSelector(LandingPage $landingPage, string $currentType): string
    {
        $hasPersonal = $landingPage->forms()->where('form_type', 'personal')->exists();
        $hasBusiness = $landingPage->forms()->where('form_type', 'business')->exists();

        if (! $hasBusiness) {
            return '';
        }

        $personalUrl = route('marketing.landing-pages.public.show', ['slug' => $landingPage->slug, 'type' => 'personal']);
        $businessUrl = route('marketing.landing-pages.public.show', ['slug' => $landingPage->slug, 'type' => 'business']);

        $personalActive = $currentType === 'personal' ? 'background:#2563eb;color:#fff;' : 'background:#e2e8f0;color:#1e293b;';
        $businessActive = $currentType === 'business' ? 'background:#2563eb;color:#fff;' : 'background:#e2e8f0;color:#1e293b;';

        return <<<HTML
<div style="display:flex;gap:8px;margin-bottom:20px;justify-content:center;">
    <a href="{$personalUrl}" style="padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;transition:all .2s;{$personalActive}">Cá nhân</a>
    <a href="{$businessUrl}" style="padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;transition:all .2s;{$businessActive}">Doanh nghiệp</a>
</div>
HTML;
    }

    protected function resolveFormTemplate(LandingPage $landingPage, string $formType)
    {
        $landingPageForm = $landingPage->forms()
            ->where('form_type', $formType)
            ->where('status', 'active')
            ->first();

        if ($landingPageForm) {
            return $landingPageForm->formTemplate;
        }

        return null;
    }

    protected function renderField(FormField $field, bool $inGrid = false): string
    {
        $label = e($field->label);
        $key = e($field->field_key);
        $placeholder = e($field->placeholder ?? '');
        $required = $field->is_required ? 'required' : '';
        $requiredMark = $field->is_required ? ' <span style="color:#e53e3e">*</span>' : '';
        $type = $field->field_type->value ?? 'text';
        $inputStyle = 'width:100%;padding:12px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;line-height:1.5;box-sizing:border-box;transition:border-color .2s,box-shadow .2s;outline:none;';

        if ($type === 'hidden') {
            $defaultVal = e($field->default_value ?? '');

            return "<input type=\"hidden\" name=\"{$key}\" value=\"{$defaultVal}\">\n";
        }

        $inputHtml = '';
        switch ($type) {
            case 'textarea':
                $inputHtml = "<textarea name=\"{$key}\" placeholder=\"{$placeholder}\" {$required} style=\"{$inputStyle}min-height:100px;resize:vertical;\"></textarea>";
                break;

            case 'select':
                $options = $field->options ?? [];
                $optionsHtml = '<option value="">-- Chọn --</option>';
                foreach ($options as $opt) {
                    $val = e(is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? $opt) : $opt);
                    $lbl = e(is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? $opt) : $opt);
                    $optionsHtml .= "<option value=\"{$val}\">{$lbl}</option>";
                }
                $inputHtml = "<select name=\"{$key}\" {$required} style=\"{$inputStyle}appearance:auto;\">{$optionsHtml}</select>";
                break;

            case 'checkbox':
                $options = $field->options ?? [];
                if (! empty($options)) {
                    $html = '';
                    $cbMargin = $inGrid ? 'margin-bottom:0;' : 'margin-bottom:8px;';
                    foreach ($options as $opt) {
                        $val = e(is_array($opt) ? ($opt['value'] ?? $opt['label'] ?? $opt) : $opt);
                        $lbl = e(is_array($opt) ? ($opt['label'] ?? $opt['value'] ?? $opt) : $opt);
                        $html .= "<div style=\"{$cbMargin}\"><label style=\"display:flex;align-items:center;gap:10px;cursor:pointer;font-size:15px;\"><input type=\"checkbox\" name=\"{$key}[]\" value=\"{$val}\" {$required} style=\"width:18px;height:18px;accent-color:#2563eb;\"> {$lbl}</label></div>";
                    }

                    return $html;
                }
                $defaultVal = e($field->default_value ?? '1');
                $cbMargin = $inGrid ? 'margin-bottom:0;' : 'margin-bottom:12px;';

                return "<div style=\"{$cbMargin}\"><label style=\"display:flex;align-items:center;gap:10px;cursor:pointer;font-size:15px;\"><input type=\"checkbox\" name=\"{$key}\" value=\"{$defaultVal}\" {$required} style=\"width:18px;height:18px;accent-color:#2563eb;\"> {$label}</label></div>";

            default:
                $inputType = match ($type) {
                    'email' => 'email',
                    'phone' => 'tel',
                    default => 'text',
                };
                $inputHtml = "<input type=\"{$inputType}\" name=\"{$key}\" placeholder=\"{$placeholder}\" {$required} style=\"{$inputStyle}\">";
                break;
        }

        $wrapperMargin = $inGrid ? 'margin-bottom:0;' : 'margin-bottom:16px;';

        return <<<HTML
<div style="{$wrapperMargin}">
    <label style="display:block;font-weight:600;margin-bottom:6px;font-size:14px;color:#1e293b;">{$label}{$requiredMark}</label>
    {$inputHtml}
</div>
HTML;
    }

    public function replacePlaceholders(string $html, LandingPage $landingPage, string $formPersonal, string $formBusiness, bool $hasBoth): string
    {
        $allFormsHtml = $this->renderAllFormsInline($landingPage, $formPersonal, $formBusiness, $hasBoth);

        $hasFormPlaceholder = str_contains($html, '{{form}}');
        $hasFormPersonalPlaceholder = str_contains($html, '{{form_personal}}');
        $hasFormBusinessPlaceholder = str_contains($html, '{{form_business}}');

        $ctaText = e($landingPage->cta_text ?? '');
        $ctaStyle = 'display:inline-block;padding:14px 32px;background:#2563eb;color:#fff;font-size:16px;font-weight:600;text-decoration:none;border-radius:8px;border:none;cursor:pointer;transition:background .2s;';
        $ctaHtml = $ctaText ? "<a href=\"#\" style=\"{$ctaStyle}\">{$ctaText}</a>" : '';

        $replacements = [
            '{{page_title}}' => e($landingPage->page_title ?? $landingPage->name),
            '{{headline}}' => e($landingPage->headline ?? ''),
            '{{subheadline}}' => e($landingPage->subheadline ?? ''),
            '{{content}}' => $landingPage->content ?? '',
            '{{cta_text}}' => $ctaHtml,
            '{{company_name}}' => e(config('app.name', 'Company')),
            '{{form}}' => $allFormsHtml,
            '{{form_personal}}' => $formPersonal,
            '{{form_business}}' => $formBusiness,
        ];

        $rendered = strtr($html, $replacements);

        if (! $hasFormPlaceholder && ! $hasFormPersonalPlaceholder && ! $hasFormBusinessPlaceholder) {
            $rendered = str_replace('</body>', $allFormsHtml."\n</body>", $rendered);
        }

        return $rendered;
    }

    protected function renderAllFormsInline(LandingPage $landingPage, string $formPersonal, string $formBusiness, bool $hasBoth): string
    {
        if (! $hasBoth) {
            if (str_contains($formPersonal, '<form')) {
                return $formPersonal;
            }
            if (str_contains($formBusiness, '<form')) {
                return $formBusiness;
            }

            return $formPersonal ?: $formBusiness;
        }

        return $formPersonal."\n".$formBusiness;
    }

    public static function sanitizeHtmlBody(string $htmlBody): string
    {
        $hasFields = str_contains($htmlBody, '{{fields}}');
        $hasHardcodedFields = preg_match('/<(?:input|select|textarea)\b[^>]*>/i', $htmlBody);

        if (! $hasFields && ! $hasHardcodedFields) {
            return $htmlBody;
        }

        // Tìm form tag
        if (! preg_match('/<form\b[^>]*>/i', $htmlBody, $formOpenMatch, PREG_OFFSET_CAPTURE)) {
            return $htmlBody;
        }

        $formOpenEnd = $formOpenMatch[0][1] + strlen($formOpenMatch[0][0]);
        $formClosePos = strrpos($htmlBody, '</form>');
        if ($formClosePos === false) {
            return $htmlBody;
        }

        // Nội dung bên trong <form>...</form>
        $innerContent = substr($htmlBody, $formOpenEnd, $formClosePos - $formOpenEnd);

        // Tìm submit button (button/a cuối cùng)
        $submitButton = '';
        $searchOffset = 0;
        while (preg_match('/<(?:button|a)\b[^>]*>.*?<\/(?:button|a)>/is', $innerContent, $btnMatch, PREG_OFFSET_CAPTURE, $searchOffset)) {
            $submitButton = $btnMatch[0][0];
            $searchOffset = $btnMatch[0][1] + strlen($submitButton);
        }

        if (empty($submitButton)) {
            return $htmlBody;
        }

        // Giữ lại hidden inputs
        $hiddenInputs = '';
        preg_match_all('/<input\s[^>]*type\s*=\s*["\']?hidden["\']?[^>]*>/i', $innerContent, $hiddenMatches);
        if (! empty($hiddenMatches[0])) {
            $hiddenInputs = implode("\n", $hiddenMatches[0])."\n";
        }

        // Nội dung sau submit button (đóng div, v.v.)
        $submitPos = strrpos($innerContent, $submitButton);
        $afterSubmit = substr($innerContent, $submitPos + strlen($submitButton));

        // Ghép lại: form open + hidden inputs + {{fields}} + submit button + after submit
        $newInner = "\n".$hiddenInputs.'{{fields}}'."\n".$submitButton.$afterSubmit;
        $body = substr_replace($htmlBody, $newInner, $formOpenEnd, $formClosePos - $formOpenEnd);

        return $body;
    }

    public function sanitizeImportedHtml(string $html, bool $replaceForms = true): string
    {
        $html = preg_replace_callback(
            '/<script\b(?!\s*src\s*=)[^>]*>.*?<\/script>/is',
            function (array $match): string {
                if (stripos($match[0], 'tailwind.config') !== false) {
                    return $match[0];
                }

                return '';
            },
            $html
        ) ?? $html;

        $trustedDomains = [
            'cdn.tailwindcss.com',
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'unpkg.com',
        ];

        $escaped = implode(
            '|',
            array_map(
                static fn (string $domain): string => preg_quote($domain, '/'),
                $trustedDomains
            )
        );

        $html = preg_replace(
            '/<script\s+src\s*=\s*"(?:https?:)?\/\/(?!'.$escaped.')[^"]*"[^>]*>.*?<\/script>/is',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            "/<script\s+src\s*=\s*'(?:https?:)?\/\/(?!".$escaped.")[^']*'[^>]*>.*?<\/script>/is",
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/\s+on\w+\s*=\s*"[^"]*"/i',
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            "/\s+on\w+\s*=\s*'[^']*'/i",
            '',
            $html
        ) ?? $html;

        $html = preg_replace(
            '/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i',
            'href="#"',
            $html
        ) ?? $html;

        if ($replaceForms) {
            $html = $this->replaceExistingFormsWithPlaceholder($html);
        }

        return $html;
    }

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

    private function removeImportedFormSections(string $html): string
    {
        /*
        * DOMDocument có thể chuyển Unicode trong CSS thành:
        * &#7912;, &#9889;...
        *
        * Trong <style>, các entity này không được browser giải mã
        * như HTML text thông thường.
        *
        * Vì vậy phải tách style ra trước khi đưa HTML qua DOM.
        */
        $styleBlocks = [];

        $htmlForDom = preg_replace_callback(
            '/<style\b[^>]*>.*?<\/style>/is',
            static function (array $matches) use (&$styleBlocks): string {
                $token = '__LP_STYLE_BLOCK_'
                    .count($styleBlocks)
                    .'__';

                $styleBlocks[$token] = $matches[0];

                return $token;
            },
            $html
        ) ?? $html;

        $dom = new \DOMDocument('1.0', 'UTF-8');

        libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">'.$htmlForDom,
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

        $output = trim(
            $dom->saveHTML() ?: $htmlForDom
        );

        /*
        * Xóa processing instruction được dùng để ép UTF-8.
        */
        $output = preg_replace(
            '/^<\?xml[^>]*\?>\s*/i',
            '',
            $output
        ) ?? $output;

        /*
        * Trả nguyên vẹn CSS ban đầu vào HTML.
        */
        return strtr($output, $styleBlocks);
    }

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

    protected function ensureTailwindConfig(string $html): string
    {
        if (! str_contains($html, 'cdn.tailwindcss.com')) {
            return $html;
        }
        if (preg_match('/tailwind\.config\s*=/i', $html)) {
            return $html;
        }

        $custom = $this->detectCustomColorClasses($html);
        if (empty($custom)) {
            return $html;
        }

        $colors = [];
        foreach ($custom as $name => $true) {
            $colors[$name] = $this->suggestColor($name);
        }

        $configJson = json_encode(
            ['theme' => ['extend' => ['colors' => $colors]]],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        $inject = "<script>\ntailwind.config = {$configJson};\n</script>\n";

        return preg_replace(
            '/(<script\s+src\s*=\s*["\'].*cdn\.tailwindcss\.com.*?<\/script>)/is',
            $inject.'$1',
            $html,
            1
        ) ?? $html;
    }

    protected function detectCustomColorClasses(string $html): array
    {
        $standard = [
            'slate', 'gray', 'zinc', 'neutral', 'stone',
            'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald',
            'teal', 'cyan', 'sky', 'blue', 'indigo', 'violet', 'purple',
            'fuchsia', 'pink', 'rose',
            'white', 'black',
            'transparent', 'current', 'inherit',
        ];

        $nonColor = [
            'text' => ['center', 'left', 'right', 'justify', 'start', 'end', 'xs', 'sm', 'base', 'lg', 'xl', 'clip', 'truncate', 'ellipsis', 'wrap', 'nowrap', 'break', 'uppercase', 'lowercase', 'capitalize', 'normal-case', 'underline', 'line-through', 'no-underline', 'overline', 'italic', 'not-italic', 'antialiased'],
            'border' => ['t', 'b', 'l', 'r', 'x', 'y', 's', 'e', 'solid', 'dashed', 'dotted', 'double', 'hidden', 'none', 'collapse', 'separate', 'spacing'],
            'bg' => ['center', 'top', 'bottom', 'left', 'right', 'auto', 'cover', 'contain', 'repeat', 'no-repeat', 'repeat-x', 'repeat-y', 'fixed', 'local', 'scroll', 'gradient', 'clip'],
            'from' => ['t', 'b', 'l', 'r', 'tl', 'tr', 'bl', 'br', 'top', 'bottom', 'left', 'right'],
            'to' => ['t', 'b', 'l', 'r', 'tl', 'tr', 'bl', 'br', 'top', 'bottom', 'left', 'right'],
            'outline' => ['none', 'solid', 'dashed', 'dotted', 'double', 'hidden', 'offset'],
            'fill' => ['none', 'current'],
            'stroke' => ['none', 'current'],
            'ring' => ['inset'],
            'decoration' => ['none', 'underline', 'overline', 'line-through', 'solid', 'double', 'dotted', 'dashed', 'wavy', 'auto', 'from-font', 'slice', 'clone'],
            'accent' => ['auto'],
            'caret' => ['auto'],
            'via' => ['transparent'],
        ];

        $found = [];
        preg_match_all('/class\s*=\s*"([^"]*)"|\'([^\']*)\'/i', $html, $matches);
        $allClasses = array_filter(array_merge($matches[1], $matches[2]));
        foreach ($allClasses as $classStr) {
            $parts = preg_split('/\s+/', $classStr);
            foreach ($parts as $cls) {
                if (preg_match('~^(bg|text|border|from|to|via|ring|outline|decoration|accent|caret|fill|stroke)-([a-zA-Z][a-zA-Z0-9-]*?)(?:/|\z)~', $cls, $m)) {
                    $utility = $m[1];
                    $fullName = $m[2];
                    $baseName = preg_replace('/-\d+$/', '', $fullName);
                    if ($baseName === '') {
                        continue;
                    }
                    if (in_array($baseName, $standard, true)) {
                        continue;
                    }
                    if (isset($nonColor[$utility])) {
                        $skip = false;
                        foreach ($nonColor[$utility] as $nc) {
                            if ($baseName === $nc || str_starts_with($baseName, $nc.'-')) {
                                $skip = true;
                                break;
                            }
                        }
                        if ($skip) {
                            continue;
                        }
                    }
                    $found[$baseName] = true;
                }
            }
        }

        return $found;
    }

    protected function suggestColor(string $name): string
    {
        $nameLower = strtolower($name);

        $known = [
            'primary' => '#6366f1',
            'secondary' => '#8b5cf6',
            'accent' => '#f59e0b',
            'deep' => '#0b1121',
            'dark' => '#020617',
            'darker' => '#000000',
            'light' => '#cbd5e1',
            'lighter' => '#e2e8f0',
            'muted' => '#64748b',
            'surface' => '#1e293b',
            'background' => '#0f172a',
            'foreground' => '#f8fafc',
            'success' => '#22c55e',
            'warning' => '#eab308',
            'danger' => '#ef4444',
            'info' => '#3b82f6',
            'brand' => '#6366f1',
        ];

        foreach ($known as $key => $color) {
            if (str_contains($nameLower, $key)) {
                return $color;
            }
        }

        $hash = crc32($nameLower);
        $h = $hash % 360;

        return sprintf('hsl(%d, 50%%, 50%%)', abs($h));
    }

    protected function replaceExistingFormsWithPlaceholder(string $html): string
    {
        if (str_contains($html, '{{form}}')) {
            return $html;
        }

        return preg_replace('/<form\b[^>]*>.*?<\/form>/is', '{{form}}', $html) ?? $html;
    }

    public static function extractBodyContent(string $html): string
    {
        if (str_contains($html, '<body')) {
            if (preg_match("/<body[^>]*>(.*?)<\/body>/is", $html, $m)) {
                $html = $m[1];
            }
        }

        $html = preg_replace("/<head[^>]*>.*?<\/head>/is", '', $html) ?? $html;
        $html = preg_replace('/<!DOCTYPE[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/<html[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace("/<\/html>/is", '', $html) ?? $html;

        $trustedDomains = [
            'cdn.tailwindcss.com',
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'unpkg.com',
        ];
        $escaped = implode('|', array_map(fn ($d) => preg_quote($d, '/'), $trustedDomains));
        $html = preg_replace("/<script\s+src\s*=\s*\"(?:https?:)?\/\/(?!".$escaped.")[^\"]*\"[^>]*>.*?<\/script>/is", '', $html) ?? $html;
        $html = preg_replace("/<script\s+src\s*=\s*'(?:https?:)?\/\/(?!".$escaped.")[^']*'[^>]*>.*?<\/script>/is", '', $html) ?? $html;

        return trim($html);
    }

    protected function renderTabbedFormsSection(
        LandingPage $landingPage,
        string $personalForm,
        string $businessForm
    ): string {
        if (blank($personalForm) && blank($businessForm)) {
            return '';
        }

        $sectionId = 'lp-forms-section-'.$landingPage->id;

        $personalTab = filled($personalForm)
            ? <<<'HTML'
    <button
        type="button"
        class="lp-form-tab is-active"
        data-lp-form-tab="personal"
    >
        Cá nhân
    </button>
    HTML
            : '';

        $businessTab = filled($businessForm)
            ? <<<'HTML'
    <button
        type="button"
        class="lp-form-tab"
        data-lp-form-tab="business"
    >
        Doanh nghiệp
    </button>
    HTML
            : '';

        $personalPanel = filled($personalForm)
            ? <<<HTML
    <div
        class="lp-form-panel is-active"
        data-lp-form-panel="personal"
    >
        {$personalForm}
    </div>
    HTML
            : '';

        $businessHidden = filled($personalForm) ? ' hidden' : '';
        $businessActive = filled($personalForm) ? '' : ' is-active';

        $businessPanel = filled($businessForm)
            ? <<<HTML
    <div
        class="lp-form-panel{$businessActive}"
        data-lp-form-panel="business"
        {$businessHidden}
    >
        {$businessForm}
    </div>
    HTML
            : '';

        $themeVariables = $this->themeService
            ->cssVariables($landingPage->theme_tokens);

        return <<<HTML
    <section
        id="{$sectionId}"
        class="lp-forms-section"
        style="{$themeVariables}"
    >
        <div class="lp-forms-card">
            <div class="lp-forms-heading">
                <h2>Đăng ký nhận tư vấn</h2>
                <p>Chọn loại khách hàng phù hợp để gửi thông tin.</p>
            </div>

            <div class="lp-form-tabs">
                {$personalTab}
                {$businessTab}
            </div>

            <div class="lp-form-panels">
                {$personalPanel}
                {$businessPanel}
            </div>
        </div>
    </section>

    <script>
    (function () {
        const root = document.getElementById('{$sectionId}');

        if (!root) {
            return;
        }

        const tabs = root.querySelectorAll('[data-lp-form-tab]');
        const panels = root.querySelectorAll('[data-lp-form-panel]');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const selectedType = tab.dataset.lpFormTab;

                tabs.forEach(function (item) {
                    item.classList.toggle(
                        'is-active',
                        item === tab
                    );
                });

                panels.forEach(function (panel) {
                    const isSelected =
                        panel.dataset.lpFormPanel === selectedType;

                    panel.classList.toggle(
                        'is-active',
                        isSelected
                    );

                    panel.hidden = !isSelected;
                });
            });
        });
    })();
    </script>
    HTML;
    }

    protected function formThemeCss(): string
    {
        return <<<'CSS'
    <style>
    .lp-forms-section {
        width: 100%;
        padding: 64px 20px;
        background: var(--lp-background);
        box-sizing: border-box;
    }

    .lp-forms-card {
        width: min(720px, 100%);
        margin: 0 auto;
        padding: 32px;
        color: var(--lp-text);
        background: var(--lp-surface);
        border: 1px solid var(--lp-border);
        border-radius: var(--lp-radius);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        box-sizing: border-box;
    }

    .lp-forms-heading {
        margin-bottom: 24px;
        text-align: center;
    }

    .lp-forms-heading h2 {
        margin: 0 0 8px;
        color: var(--lp-text);
        font-size: 28px;
        line-height: 1.25;
    }

    .lp-forms-heading p {
        margin: 0;
        color: var(--lp-muted-text);
    }

    .lp-form-tabs {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 24px;
        padding: 5px;
        background: color-mix(
            in srgb,
            var(--lp-primary) 8%,
            var(--lp-surface)
        );
        border-radius: var(--lp-radius);
    }

    .lp-form-tab {
        padding: 12px 18px;
        color: var(--lp-text);
        background: transparent;
        border: 0;
        border-radius: calc(var(--lp-radius) - 4px);
        cursor: pointer;
        font: inherit;
        font-weight: 600;
    }

    .lp-form-tab.is-active {
        color: #fff;
        background: var(--lp-primary);
    }

    .lp-form-panel[hidden] {
        display: none !important;
    }

    .lp-forms-section form {
        color: var(--lp-text);
    }

    .lp-forms-section form.lp-form-template__form {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        transform: none !important;
        animation: none !important;
        position: static !important;
    }

    .lp-form-panel:not([hidden]) {
        display: block !important;
    }

    .lp-form-panel[hidden] {
        display: none !important;
    }

    .lp-form-template {
        display: block !important;
        width: 100%;
    }

    .lp-forms-section label {
        color: var(--lp-text) !important;
    }

    .lp-forms-section input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]),
    .lp-forms-section select,
    .lp-forms-section textarea {
        width: 100%;
        padding: 12px 14px;
        color: var(--lp-text) !important;
        background: var(--lp-surface) !important;
        border: 1px solid var(--lp-border) !important;
        border-radius: calc(var(--lp-radius) - 4px) !important;
        box-sizing: border-box;
    }

    .lp-forms-section input:focus,
    .lp-forms-section select:focus,
    .lp-forms-section textarea:focus {
        outline: none !important;
        border-color: var(--lp-primary) !important;
        box-shadow: 0 0 0 3px color-mix(
            in srgb,
            var(--lp-primary) 18%,
            transparent
        ) !important;
    }

    .lp-forms-section button[type="submit"],
    .lp-forms-section input[type="submit"] {
        color: #fff !important;
        background: var(--lp-primary) !important;
        border: 0 !important;
        border-radius: calc(var(--lp-radius) - 4px) !important;
    }

    .lp-forms-section button[type="submit"]:hover,
    .lp-forms-section input[type="submit"]:hover {
        background: var(--lp-primary-hover) !important;
    }

    @media (max-width: 640px) {
        .lp-forms-section {
            padding: 40px 14px;
        }

        .lp-forms-card {
            padding: 22px 16px;
        }
    }
    </style>
    CSS;
    }

    protected function appendFormsToEnd(
        string $html,
        string $formsSection
    ): string {
        $html = str_replace([
            '{{form}}',
            '{{form_personal}}',
            '{{form_business}}',
            '{{forms_section}}',
        ], '', $html);

        if (blank($formsSection)) {
            return $html;
        }

        if (preg_match('/<\/body>/i', $html)) {
            return preg_replace(
                '/<\/body>/i',
                $formsSection."\n</body>",
                $html,
                1
            ) ?? $html;
        }

        return $html."\n".$formsSection;
    }

    private function isolateRuntimeFormShell(string $html): string
    {
        return preg_replace_callback(
            '/<form\b([^>]*)>/i',
            static function (array $matches): string {
                $attributes = $matches[1];

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

                return '<form'
                    .$attributes
                    .' class="lp-form-template__form">';
            },
            $html,
            1
        ) ?? $html;
    }

    private function normalizeStyleEntities(string $html): string
    {
        return preg_replace_callback(
            '/<style\b([^>]*)>(.*?)<\/style>/is',
            static function (array $matches): string {
                $attributes = $matches[1];
                $css = $matches[2];

                /*
                * Sửa trường hợp bị encode nhiều lần:
                * &amp;#9889; → &#9889;
                */
                for ($i = 0; $i < 3; $i++) {
                    $normalized = preg_replace(
                        '/&amp;(#(?:x[0-9a-f]+|\d+);)/i',
                        '&$1',
                        $css
                    ) ?? $css;

                    if ($normalized === $css) {
                        break;
                    }

                    $css = $normalized;
                }

                /*
                * &#9889; → ⚡
                * &#7912; → Ứ
                */
                $css = preg_replace_callback(
                    '/&#(?:x[0-9a-f]+|\d+);/i',
                    static fn (array $entity): string => html_entity_decode(
                        $entity[0],
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8'
                    ),
                    $css
                ) ?? $css;

                return '<style'
                    .$attributes
                    .'>'
                    .$css
                    .'</style>';
            },
            $html
        ) ?? $html;
    }
}
