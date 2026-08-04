<?php

namespace App\Http\Controllers\Marketing\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageUtmUrl;
use Illuminate\Http\JsonResponse;

class LandingPageUtmUrlController extends Controller
{
    public function destroy(LandingPage $landingPage, LandingPageUtmUrl $utmUrl): JsonResponse
    {
        abort_unless(auth()->user()?->isMarketingStaff(), 403);

        if ($utmUrl->landing_page_id !== $landingPage->id) {
            abort(404);
        }

        $utmUrl->delete();

        return response()->json([
            'message' => __('notification.deleted'),
        ]);
    }
}
