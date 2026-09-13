<?php

namespace Dth\Marketing\Http\Controllers;

use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Services\LandingPageRenderService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FormTemplatePreviewController
{
    public function __invoke(
        Request $request,
        FormTemplate $formTemplate,
        LandingPageRenderService $renderer,
        MarketingAuthorizationService $authorization,
    ): Response {
        abort_unless($authorization->view($request->user()), 403);

        $landingPage = null;

        if ($request->filled('landing_page_id')) {
            $landingPage = LandingPage::query()->find($request->integer('landing_page_id'));
        }

        return response($renderer->previewFormTemplate($formTemplate, $landingPage), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
