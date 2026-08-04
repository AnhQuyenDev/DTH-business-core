<?php

namespace App\Http\Controllers\Marketing\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\EmailTemplate;
use App\Services\Marketing\TemplateRenderService;

class EmailTemplatePreviewController extends Controller
{
    public function preview(EmailTemplate $emailTemplate)
    {
        $rendered = app(TemplateRenderService::class)->render($emailTemplate);

        return response($rendered['html_body'])
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
