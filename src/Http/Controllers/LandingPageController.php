<?php

namespace Dth\Marketing\Http\Controllers;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Services\LandingPageRenderService;
use Illuminate\Http\Response;

final class LandingPageController
{
    public function show(string $slug, LandingPageRenderService $renderer): Response
    {
        $page = LandingPage::query()
            ->with(['personalFormTemplate.fields', 'businessFormTemplate.fields'])
            ->where('slug', $slug)
            ->where('status', LandingPageStatus::Published->value)
            ->firstOrFail();

        return $this->html($renderer->render($page, false));
    }

    public function preview(LandingPage $landingPage, LandingPageRenderService $renderer): Response
    {
        $landingPage->loadMissing(['personalFormTemplate.fields', 'businessFormTemplate.fields']);

        return $this->html($renderer->render($landingPage, true));
    }

    private function html(string $html): Response
    {
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
