<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\FormTemplate;
use Illuminate\Support\HtmlString;

/**
 * Form Template preview intentionally delegates to the exact same renderer used
 * by Landing Pages. This mirrors the original Marketing source and prevents the
 * preview/runtime split that caused a form to look correct by itself but break
 * when mounted inside a Landing Page.
 */
final class FormTemplatePreviewService
{
    public function __construct(
        private readonly LandingPageRenderService $renderer,
    ) {}

    public function render(FormTemplate $template, bool $disabled = true): HtmlString
    {
        return new HtmlString($this->renderer->renderTemplateFragment($template, $disabled));
    }

    public function renderDocument(FormTemplate $template, bool $disabled = true): string
    {
        return $this->renderer->previewFormTemplate($template);
    }

    public function presentationAssets(FormTemplate $template): string
    {
        // Source parity: imported Form Template page-level CSS is deliberately
        // not injected into a Landing Page. The Landing Page form theme owns the
        // runtime presentation, exactly as the original FormTemplateImportService.
        return '';
    }
}
