<?php

namespace App\Http\Controllers\Marketing\Public;

use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\EmailEventType;
use App\Http\Controllers\Controller;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\TrackedLink;
use Illuminate\Http\Response;

class EmailTrackingController extends Controller
{
    public function open(string $token): Response
    {
        $recipient = CampaignRecipient::query()->where('tracking_token', $token)->first();

        if ($recipient) {
            EmailEvent::query()->firstOrCreate(
                [
                    'campaign_id' => $recipient->campaign_id,
                    'campaign_recipient_id' => $recipient->id,
                    'contact_id' => $recipient->contact_id,
                    'event_type' => EmailEventType::Opened->value,
                ],
                [
                    'event_payload' => [],
                    'ip_address' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                    'occurred_at' => now(),
                ]
            );

            if (! $recipient->opened_at) {
                $recipient->update([
                    'opened_at' => now(),
                    'status' => CampaignRecipientStatus::Opened->value,
                ]);
            }
        }

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($gif),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function click(string $token)
    {
        $trackedLink = TrackedLink::query()->with(['recipient'])->where('tracking_token', $token)->firstOrFail();
        $recipient = $trackedLink->recipient;

        $trackedLink->increment('click_count');
        $trackedLink->update(['last_clicked_at' => now()]);

        if ($recipient) {
            EmailEvent::query()->create([
                'campaign_id' => $trackedLink->campaign_id,
                'campaign_recipient_id' => $recipient->id,
                'contact_id' => $recipient->contact_id,
                'event_type' => EmailEventType::Clicked->value,
                'event_payload' => [
                    'original_url' => $trackedLink->original_url,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'occurred_at' => now(),
            ]);

            if (! $recipient->clicked_at) {
                $recipient->update([
                    'clicked_at' => now(),
                    'status' => CampaignRecipientStatus::Clicked->value,
                ]);
            }
        }

        return redirect()->away($trackedLink->original_url);
    }

    /**
     * Open-tracking pixel for one-to-one care emails (no campaign recipient).
     */
    public function openCare(string $token): Response
    {
        $event = EmailEvent::query()
            ->where('tracking_token', $token)
            ->whereNull('campaign_id')
            ->whereNotNull('customer_id')
            ->first();

        if ($event) {
            EmailEvent::query()->firstOrCreate(
                [
                    'tracking_token' => $token,
                    'event_type' => EmailEventType::Opened->value,
                ],
                [
                    'customer_id' => $event->customer_id,
                    'contact_id' => $event->contact_id,
                    'event_payload' => [],
                    'ip_address' => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                    'occurred_at' => now(),
                ]
            );
        }

        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');

        return response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Content-Length' => strlen($gif),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Click-tracking redirect for one-to-one care emails (no campaign recipient).
     */
    public function clickCare(string $token)
    {
        $event = EmailEvent::query()
            ->where('tracking_token', $token)
            ->whereNull('campaign_id')
            ->whereNotNull('customer_id')
            ->first();

        if ($event) {
            EmailEvent::query()->create([
                'tracking_token' => $token,
                'customer_id' => $event->customer_id,
                'contact_id' => $event->contact_id,
                'event_type' => EmailEventType::Clicked->value,
                'event_payload' => [
                    'original_url' => request()->query('u'),
                ],
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'occurred_at' => now(),
            ]);
        }

        $originalUrl = request()->query('u') ?? '/';

        return redirect()->away($originalUrl);
    }
}
