<?php

namespace App\Http\Controllers\Marketing\Public;

use App\Enums\Crm\ContactType;
use App\Http\Controllers\Controller;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\LandingPageRenderService;
use App\Services\Marketing\LandingPageSubmissionService;
use App\Services\Marketing\LandingPageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function __construct(
        private readonly LandingPageRenderService $renderService,
        private readonly LandingPageSubmissionService $submissionService,
        private readonly LandingPageTrackingService $trackingService,
    ) {}

    public function show(Request $request, string $slug): Response|\Illuminate\Http\RedirectResponse
    {
        $landingPage = LandingPage::where('slug', $slug)->first();

        if (! $landingPage || ! $landingPage->isPublished()) {
            abort(404);
        }

        $this->trackingService->trackView($landingPage, $request);

        $formType = $request->query('type', 'personal');

        $html = $this->renderService->render($landingPage, $formType);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function submit(Request $request, string $slug): \Illuminate\Http\RedirectResponse
    {
        $landingPage = LandingPage::where('slug', $slug)->first();

        if (! $landingPage || ! $landingPage->isPublished()) {
            abort(404);
        }

        $campaignId = $request->integer('cid') ?: $request->integer('campaign_id') ?: null;

        try {
            $submission = $this->submissionService->handle($landingPage, $request->all(), $request, $campaignId);
        } catch (ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        $formTemplate = $submission->formTemplate;

        if ($formTemplate && $formTemplate->redirect_url) {
            return redirect()->away(
                $formTemplate->redirect_url
            );
        }

        return redirect()
            ->route(
                'marketing.landing-pages.public.thank-you',
                $slug
            )
            ->with(
                'success_message',
                $formTemplate?->success_message
                    ?? 'Cảm ơn! Thông tin của bạn đã được ghi nhận.'
            );
    }

    public function thankYou(Request $request, string $slug): \Illuminate\Contracts\View\View
    {
        $landingPage = LandingPage::where('slug', $slug)->first();

        $successMessage = session('success_message', 'Cảm ơn! Thông tin của bạn đã được ghi nhận.');

        return view('marketing.landing-pages.thank-you', [
            'landingPage'    => $landingPage,
            'successMessage' => $successMessage,
        ]);
    }
}
