<?php

namespace App\Http\Controllers\Marketing\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\LandingPageRenderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FormTemplatePreviewController extends Controller
{
    public function preview(
        Request $request,
        FormTemplate $formTemplate,
        LandingPageRenderService $renderer
    ): Response {
        $landingPage = null;

        if ($request->filled('landing_page_id')) {
            $landingPage = LandingPage::query()->find(
                $request->integer('landing_page_id')
            );
        }

        $html = $renderer->previewFormTemplate(
            $formTemplate,
            $landingPage
        );

        return response($html)->header(
            'Content-Type',
            'text/html; charset=UTF-8'
        );
    }
}
