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

    public function unsubscribeOneClick(
        Request $request,
        string $token,
        EmailEventService $events,
    ): Response {
        $isOneClick = (string) $request->input('List-Unsubscribe') === 'One-Click';
        $isHumanConfirmation = (string) $request->input('confirm') === '1';

        if (! $isOneClick && ! $isHumanConfirmation) {
            return response('Invalid unsubscribe request.', 422, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $message = EmailMessage::query()
            ->with('campaignRecipient')
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        $events->unsubscribed(
            message: $message,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );

        if ($isOneClick) {
            return response('', 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        return response()->view('dth-email::unsubscribe-success', [
            'email' => $message->recipient_email,
        ]);
    }

    public function unsubscribe(
        Request $request,
        string $token,
    ): Response {
        $message = EmailMessage::query()
            ->where('unsubscribe_token', $token)
            ->firstOrFail();

        return response()->view('dth-email::unsubscribe-confirm', [
            'email' => $message->recipient_email,
            'postUrl' => route('dth.email.unsubscribe.one-click', ['token' => $token]),
        ]);
    }

}