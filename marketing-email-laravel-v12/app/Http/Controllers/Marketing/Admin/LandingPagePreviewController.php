<?php

namespace App\Http\Controllers\Marketing\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\LandingPageRenderService;

class LandingPagePreviewController extends Controller
{
    public function preview(LandingPage $landingPage)
    {
        $html = app(LandingPageRenderService::class)->render($landingPage);
        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
