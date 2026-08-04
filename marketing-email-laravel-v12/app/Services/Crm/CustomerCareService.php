<?php

namespace App\Services\Crm;

use App\Enums\Marketing\EmailEventType;
use App\Models\Crm\Customer;
use App\Models\Sales\Quotation;
use Illuminate\Support\Collection;

class CustomerCareService
{
    /**
     * Quick statistics for a customer's care workspace.
     */
    public function stats(Customer $customer): array
    {
        return [
            'calls' => $customer->interactions()
                ->where('interaction_type', 'call')
                ->count(),
            'messages' => $customer->interactions()
                ->where('interaction_type', 'message')
                ->count(),
            'emails' => $customer->interactions()
                ->where('interaction_type', 'email')
                ->count(),
            'quotations' => Quotation::query()
                ->where('customer_id', $customer->id)
                ->count(),
        ];
    }

    /**
     * Merged, time-ordered timeline of everything that happened with this customer:
     * interactions, email events, quotations and assignment changes.
     */
    public function timeline(Customer $customer): Collection
    {
        $items = collect();

        foreach ($customer->interactions()->with('staff')->get() as $interaction) {
            $items->push([
                'at' => $interaction->interaction_at,
                'kind' => 'interaction',
                'data' => $interaction,
            ]);
        }

        foreach ($customer->emailEvents()->get() as $event) {
            $items->push([
                'at' => $event->occurred_at,
                'kind' => 'email_event',
                'data' => $event,
            ]);
        }

        foreach (Quotation::query()->where('customer_id', $customer->id)->with('emailLogs')->get() as $quotation) {
            $items->push([
                'at' => $quotation->sent_at ?? $quotation->created_at,
                'kind' => 'quotation',
                'data' => $quotation,
            ]);
        }

        foreach ($customer->assignments()->with('staff')->get() as $assignment) {
            $items->push([
                'at' => $assignment->created_at,
                'kind' => 'assignment',
                'data' => $assignment,
            ]);

            if ($assignment->ended_at) {
                $items->push([
                    'at' => $assignment->ended_at,
                    'kind' => 'assignment_end',
                    'data' => $assignment,
                ]);
            }
        }

        return $items
            ->filter(fn (array $item) => $item['at'] !== null)
            ->sortByDesc('at')
            ->values();
    }

    /**
     * Email history for this customer, oldest first (thread view).
     */
    public function emailThread(Customer $customer): Collection
    {
        return $customer->emailEvents()
            ->orderBy('occurred_at', 'asc')
            ->get()
            ->map(function ($event) {
                $payload = $event->event_payload ?? [];

                return [
                    'event' => $event,
                    'subject' => $payload['subject'] ?? $event->event_type->value,
                    'is_care' => $event->campaign_id === null,
                    'status' => $event->event_type->value,
                ];
            });
    }

    /**
     * Latest engagement summary per event type (e.g. delivered / opened / clicked timestamps).
     */
    public function emailSummary(Customer $customer): array
    {
        $types = [];

        foreach ($customer->emailEvents()->orderBy('occurred_at', 'asc')->get() as $event) {
            $types[$event->event_type->value] = $event->occurred_at;
        }

        return [
            'last_email_at' => $types[EmailEventType::Sent->value] ?? null,
            'last_opened_at' => $types[EmailEventType::Opened->value] ?? null,
            'last_clicked_at' => $types[EmailEventType::Clicked->value] ?? null,
        ];
    }
}
