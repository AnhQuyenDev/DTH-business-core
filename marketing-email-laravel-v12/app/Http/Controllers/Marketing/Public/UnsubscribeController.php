<?php

namespace App\Http\Controllers\Marketing\Public;

use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Marketing\CampaignRecipientStatus;
use App\Enums\Marketing\EmailEventType;
use App\Http\Controllers\Controller;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\EmailEvent;
use App\Services\Marketing\SuppressionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnsubscribeController extends Controller
{
    public function show(string $token): Response
    {
        $recipient = CampaignRecipient::query()->with(['contact', 'campaign'])->where('unsubscribe_token', $token)->first();

        if (! $recipient) {
            return response()->view('marketing.unsubscribe-invalid');
        }

        return response()->view('marketing.unsubscribe', [
            'token' => $token,
            'recipient' => $recipient,
        ]);
    }

    public function store(string $token, Request $request, SuppressionService $suppressionService): Response
    {
        $recipient = CampaignRecipient::query()->with(['contact', 'campaign'])->where('unsubscribe_token', $token)->firstOrFail();

        $recipient->update([
            'status' => CampaignRecipientStatus::Unsubscribed->value,
        ]);

        $customer = $recipient->customer ?? $recipient->contact?->customer;

        if ($customer) {
            $customer->update([
                'consent_status' => CustomerConsentStatus::Unsubscribed,
                'unsubscribed_at' => now(),
            ]);
        }

        $suppressionService->suppress(
            email: $recipient->email,
            reason: 'unsubscribe',
            source: 'unsubscribe_page',
            campaignId: $recipient->campaign_id,
            contactId: $recipient->contact_id,
            note: 'User unsubscribed via public page.',
            createdBy: null,
        );

        EmailEvent::query()->create([
            'campaign_id' => $recipient->campaign_id,
            'campaign_recipient_id' => $recipient->id,
            'contact_id' => $recipient->contact_id,
            'event_type' => EmailEventType::Unsubscribed->value,
            'event_payload' => [
                'email' => $recipient->email,
            ],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'occurred_at' => now(),
        ]);

        return response()->view('marketing.unsubscribe-confirmed', [
            'recipient' => $recipient,
        ]);
    }
}
