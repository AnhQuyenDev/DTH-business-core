<?php

namespace Dth\Email\Http\Controllers;

use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\EmailTrackedLink;
use Dth\Email\Services\EmailEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailTrackingController
{
    public function open(
        Request $request,
        string $token,
        EmailEventService $events,
    ): Response {
        $message = EmailMessage::query()
            ->with('campaignRecipient')
            ->where('tracking_token', $token)
            ->firstOrFail();

        $events->opened(
            message: $message,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        $gif = base64_decode(
            'R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='
        );

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($gif),
            'Cache-Control' =>
                'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(
        Request $request,
        string $token,
        EmailEventService $events,
    ): RedirectResponse {
        $link = EmailTrackedLink::query()
            ->with('message.campaignRecipient')
            ->where('tracking_token', $token)
            ->firstOrFail();

        $events->clicked(
            link: $link,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return redirect()->away(
            $link->original_url
        );
    }

    public function unsubscribe(
        Request $request,
        string $token,
        EmailEventService $events,
    ): Response {
        $message = EmailMessage::query()
            ->with('campaignRecipient')
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $events->unsubscribed(
            message: $message,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        return response(
            'You have been unsubscribed.',
            200,
            [
                'Content-Type' =>
                    'text/plain; charset=UTF-8',
            ],
        );
    }
}