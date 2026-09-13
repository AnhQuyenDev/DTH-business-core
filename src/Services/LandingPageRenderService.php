<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;

final class LandingPageRenderService
{
    public function __construct(
        private readonly FormTemplatePreviewService $forms,
    ) {}

    public function render(LandingPage $page, bool $preview = false): string
    {
        $page->loadMissing(['personalFormTemplate.fields', 'businessFormTemplate.fields']);

        // M-D is display/preview only. M-E turns these controls into real public
        // submission forms. Keeping them disabled now prevents accidental submits.
        $disabled = $preview || ! config('dth-marketing.features.public_submission', false);

        $personal = $page->personalFormTemplate
            ? (string) $this->forms->render($page->personalFormTemplate, $disabled)
            : '';
        $business = $page->businessFormTemplate
            ? (string) $this->forms->render($page->businessFormTemplate, $disabled)
            : '';
        $formAssets = $this->formAssets(
            $page->personalFormTemplate,
            $page->businessFormTemplate,
        );

        $html = trim((string) ($page->html_body ?? ''));

        if ($this->isFullDocument($html)) {
            return $this->renderImportedDocument(
                $page,
                $html,
                $personal,
                $business,
                $formAssets,
                $preview,
            );
        }

        return view('dth-marketing::public.landing-page', [
            'page' => $page,
            'previewMode' => $preview,
            'personalForm' => $personal !== '' ? $personal : null,
            'businessForm' => $business !== '' ? $business : null,
            'formAssets' => $formAssets,
        ])->render();
    }

    private function renderImportedDocument(
        LandingPage $page,
        string $html,
        string $personal,
        string $business,
        string $formAssets,
        bool $preview,
    ): string {
        $formsBlock = $this->formsBlock($personal, $business);

        $html = strtr($html, [
            '{{page_title}}' => e($page->page_title ?: $page->name),
            '{{headline}}' => e((string) ($page->headline ?? '')),
            '{{subheadline}}' => e((string) ($page->subheadline ?? '')),
            '{{cta_text}}' => e((string) ($page->cta_text ?? '')),
            '{{content}}' => nl2br(e((string) ($page->content ?? ''))),
            // Imported forms are deliberately detached from their old position.
            // The selected Form Templates are always appended at page end.
            '{{form}}' => '',
            '{{form_personal}}' => '',
            '{{form_business}}' => '',
            '{{forms_section}}' => '',
        ]);

        if (filled($page->page_title) && preg_match('/<title\b[^>]*>.*?<\/title>/is', $html)) {
            $html = preg_replace(
                '/<title\b[^>]*>.*?<\/title>/is',
                '<title>'.e((string) $page->page_title).'</title>',
                $html,
                1,
            ) ?? $html;
        }

        $headInjection = $this->formSupportCss();
        if ($formAssets !== '') {
            $headInjection .= "\n".$formAssets;
        }
        if (filled($page->css_body)) {
            $headInjection .= "\n<style id=\"dth-marketing-page-css\">\n".(string) $page->css_body."\n</style>";
        }

        if (preg_match('/<\/head\s*>/i', $html)) {
            $html = preg_replace('/<\/head\s*>/i', $headInjection."\n</head>", $html, 1) ?? $html;
        } else {
            $html = $headInjection."\n".$html;
        }

        $bodyInjection = $formsBlock !== '' ? "\n".$formsBlock : '';
        if ($preview) {
            $bodyInjection .= "\n<div class=\"dth-marketing-preview-badge\">Preview</div>";
        }

        if ($bodyInjection !== '') {
            if (preg_match('/<\/body\s*>/i', $html)) {
                $html = preg_replace('/<\/body\s*>/i', $bodyInjection."\n</body>", $html, 1) ?? $html;
            } else {
                $html .= $bodyInjection;
            }
        }

        return $html;
    }

    private function formsBlock(string $personal, string $business): string
    {
        if ($personal === '' && $business === '') {
            return '';
        }

        $html = '<section id="lead-forms" class="dth-marketing-imported-forms">';

        if ($personal !== '') {
            $html .= '<div class="dth-marketing-form-slot" data-form-type="personal">'.$personal.'</div>';
        }

        if ($business !== '') {
            $html .= '<div class="dth-marketing-form-slot" data-form-type="business">'.$business.'</div>';
        }

        return $html.'</section>';
    }

    private function formAssets(?FormTemplate $personal, ?FormTemplate $business): string
    {
        $assets = [];
        foreach ([$personal, $business] as $template) {
            if (! $template) {
                continue;
            }

            $value = trim($this->forms->presentationAssets($template));
            if ($value !== '') {
                $assets[$value] = true;
            }
        }

        return implode("\n", array_keys($assets));
    }

    private function formSupportCss(): string
    {
        return <<<'HTML'
<style id="dth-marketing-form-support">
.dth-marketing-imported-forms{display:block;width:100%;margin:2rem 0 0;padding:0}.dth-marketing-form-slot{display:block;width:100%;min-width:0}.dth-marketing-form-slot+.dth-marketing-form-slot{margin-top:2rem}.dth-marketing-form-document-root{display:block;width:100%}.dth-marketing-form-preview{display:grid;gap:.875rem}.dth-marketing-field{display:grid;gap:.375rem}.dth-marketing-field input,.dth-marketing-field textarea,.dth-marketing-field select{max-width:100%}.dth-marketing-preview-badge{position:fixed;right:16px;top:16px;z-index:2147483647;background:#111827;color:#fff;padding:8px 12px;border-radius:999px;font:700 12px/1.2 ui-sans-serif,system-ui,sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.22)}
</style>
HTML;
    }

    private function isFullDocument(string $html): bool
    {
        return $html !== '' && (
            preg_match('/<!doctype\s+html/i', $html) === 1
            || preg_match('/<html\b/i', $html) === 1
            || preg_match('/<head\b/i', $html) === 1
            || preg_match('/<body\b/i', $html) === 1
        );
    }
}
