<?php

namespace App\Services\Marketing;

use App\Models\Marketing\FormField;
use App\Models\Marketing\LandingPage;

class LandingPageRenderService
{
    public function render(LandingPage $landingPage, string $formType = 'personal'): string
    {
        $genericFormHtml = $this->renderGenericForm($landingPage);

        if ($genericFormHtml !== null) {
            $html = $landingPage->html_body;
            $css = $landingPage->css_body;

            if ($html) {
                if ($css) {
                    $html = str_replace('</head>', "<style>\n{$css}\n</style>\n</head>", $html);
                }
                $html = $this->ensureTailwindConfig($html);
                $html = $this->replaceExistingFormsWithPlaceholder($html);

                return $this->replacePlaceholders($html, $landingPage, $genericFormHtml, '', false);
            }

            $html = '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{page_title}}</title></head><body><h1>{{headline}}</h1><p>{{subheadline}}</p><div>{{content}}</div><div>{{form}}</div></body></html>';

            return $this->replacePlaceholders($html, $landingPage, $genericFormHtml, '', false);
        }

        $hasBoth = $this->hasBothForms($landingPage);
        $formPersonal = $this->renderForm($landingPage, 'personal', ! $hasBoth);
        $formBusiness = $this->renderForm($landingPage, 'business', ! $hasBoth);

        $html = $landingPage->html_body;
        $css = $landingPage->css_body;

        if ($html) {
            if ($css) {
                $html = str_replace('</head>', "<style>\n{$css}\n</style>\n</head>", $html);
            }
            $html = $this->ensureTailwindConfig($html);
            $html = $this->replaceExistingFormsWithPlaceholder($html);

            return $this->replacePlaceholders($html, $landingPage, $formPersonal, $formBusiness, $hasBoth);
        }

        $html = '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{{page_title}}</title></head><body><h1>{{headline}}</h1><p>{{subheadline}}</p><div>{{content}}</div><div>{{form}}</div></body></html>';

        return $this->replacePlaceholders($html, $landingPage, $formPersonal, $formBusiness, $hasBoth);
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

    public function renderForm(LandingPage $landingPage, string $formType = 'personal', bool $showTypeSelector = true): string
    {
        $formTemplate = $this->resolveFormTemplate($landingPage, $formType);

        if (! $formTemplate) {
            return '<p style="color:#e53e3e;">Chưa có form được gắn vào Landing Page này.</p>';
        }

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

            // Ensure method="POST" on the form tag
            $body = preg_replace('/<form\b([^>]*)method\s*=\s*["\']get["\']([^>]*)>/i', '<form$1method="POST"$2>', $body) ?? $body;
            if (! preg_match('/<form\b[^>]*method\s*=/i', $body)) {
                $body = preg_replace('/(<form\b)/i', '<form method="POST"', $body, 1) ?? $body;
            }

            if (! str_contains($body, '{{csrf_token}}')) {
                $body = preg_replace(
                    '/(<form\b[^>]*>)/i',
                    '$1'."\n".'<input type="hidden" name="_token" value="{{csrf_token}}">',
                    $body
                ) ?? $body;
            }

            $body = preg_replace_callback(
                '/(<form\b[^>]*?)\s+action\s*=\s*"([^"]*)"/i',
                fn ($m) => $m[1].' action="{{action_url}}"',
                $body,
                1
            ) ?? $body;
            $body = preg_replace_callback(
                "/(<form\b[^>]*?)\s+action\s*=\s*'([^']*)'/i",
                fn ($m) => $m[1]." action='{{action_url}}'",
                $body,
                1
            ) ?? $body;

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

    public function sanitizeImportedHtml(string $html): string
    {
        $html = preg_replace_callback(
            '/<script\b(?!\s*src\s*=)[^>]*>.*?<\/script>/is',
            function ($match) {
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
        $escaped = implode('|', array_map(fn ($d) => preg_quote($d, '/'), $trustedDomains));
        $html = preg_replace('/<script\s+src\s*=\s*"(?:https?:)?\/\/(?!'.$escaped.')[^"]*"[^>]*>.*?<\/script>/is', '', $html) ?? $html;
        $html = preg_replace("/<script\s+src\s*=\s*'(?:https?:)?\/\/(?!".$escaped.")[^']*'[^>]*>.*?<\/script>/is", '', $html) ?? $html;
        $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $html) ?? $html;
        $html = preg_replace('/href\s*=\s*["\']?\s*javascript:[^"\'>\s]*/i', 'href="#"', $html) ?? $html;
        $html = $this->replaceExistingFormsWithPlaceholder($html);

        return $html;
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
}
