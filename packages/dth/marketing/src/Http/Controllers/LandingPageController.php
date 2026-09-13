<?php

namespace Dth\Marketing\Http\Controllers;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\LandingPageSubmissionStatus;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Services\LandingPageRenderService;
use Dth\Marketing\Services\LandingPageSubmissionService;
use Dth\Marketing\Services\LandingPageTrackingService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Dth\Marketing\Support\UiText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class LandingPageController
{
    public function show(
        Request $request,
        string $slug,
        LandingPageRenderService $renderer,
        LandingPageTrackingService $tracking,
    ): Response {
        $page = LandingPage::query()
            ->with(['personalFormTemplate.fields', 'businessFormTemplate.fields'])
            ->where('slug', $slug)
            ->where('status', LandingPageStatus::Published->value)
            ->firstOrFail();

        if (config('dth-marketing.features.public_submission', false)) {
            $tracking->trackView($page, $request);
        }

        return $this->html($renderer->render($page, false, $request));
    }

    public function submit(
        Request $request,
        string $slug,
        LandingPageSubmissionService $submissions,
    ): RedirectResponse {
        $page = LandingPage::query()
            ->with(['personalFormTemplate.fields', 'businessFormTemplate.fields'])
            ->where('slug', $slug)
            ->where('status', LandingPageStatus::Published->value)
            ->firstOrFail();

        if (! config('dth-marketing.features.public_submission', false)) {
            abort(404);
        }

        try {
            $submission = $submissions->handle($page, $request->all(), $request);
        } catch (ValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors())->withInput();
        }

        $template = $submission->formTemplate;

        if ($submission->status === LandingPageSubmissionStatus::Failed) {
            return redirect()->back()->withErrors([
                'submission' => UiText::get('submission.processing_failed_public', 'Your information was received but could not be processed. Please contact support if the problem continues.'),
            ])->withInput();
        }

        if (
            $submission->status === LandingPageSubmissionStatus::Processed
            && $template !== null
            && filled($template->redirect_url)
        ) {
            return redirect()->away((string) $template->redirect_url);
        }

        $message = $template?->success_message
            ?: UiText::get('submission.success_default', 'Thank you. Your information has been received.');

        return redirect()
            ->route('marketing.landing-pages.public.thank-you', ['slug' => $page->slug])
            ->with('success_message', $message);
    }

    public function thankYou(string $slug): View
    {
        $page = LandingPage::query()
            ->where('slug', $slug)
            ->where('status', LandingPageStatus::Published->value)
            ->firstOrFail();

        return view('dth-marketing::public.thank-you', [
            'page' => $page,
            'successMessage' => session(
                'success_message',
                UiText::get('submission.success_default', 'Thank you. Your information has been received.'),
            ),
        ]);
    }

    public function preview(
        Request $request,
        LandingPage $landingPage,
        LandingPageRenderService $renderer,
        MarketingAuthorizationService $authorization,
    ): Response {
        abort_unless($authorization->view($request->user()), 403);

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
