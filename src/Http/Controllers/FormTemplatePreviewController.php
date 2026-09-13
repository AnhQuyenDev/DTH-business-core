<?php

namespace Dth\Marketing\Http\Controllers;

use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Services\FormTemplatePreviewService;
use Illuminate\Http\Response;

final class FormTemplatePreviewController
{
    public function __invoke(FormTemplate $formTemplate, FormTemplatePreviewService $preview): Response
    {
        return response($preview->renderDocument($formTemplate, true), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
