<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\CustomerInteraction;
use App\Models\Sales\Quotation;
use Illuminate\Support\Collection;

class QuotationReminderService
{
    public function getSentNotViewed(int $days = 2): Collection
    {
        return Quotation::where('status', QuotationStatus::Sent)
            ->whereDate('sent_at', '<=', now()->subDays($days))
            ->get();
    }

    public function getViewedNotResponded(int $days = 3): Collection
    {
        return Quotation::where('status', QuotationStatus::Viewed)
            ->whereDate('last_viewed_at', '<=', now()->subDays($days))
            ->get();
    }

    public function getExpiringSoon(int $days = 2): Collection
    {
        return Quotation::whereIn('status', [
            QuotationStatus::Approved,
            QuotationStatus::Sent,
            QuotationStatus::Viewed,
        ])->whereDate('valid_until', now()->addDays($days))
            ->get();
    }

    public function getAcceptedUnpaid(): Collection
    {
        return Quotation::where('status', QuotationStatus::Accepted)
            ->where('payment_status', 'unpaid')
            ->get();
    }

    public function getRevisionRequested(): Collection
    {
        return Quotation::where('status', QuotationStatus::RevisionRequested)->get();
    }

    public function createFollowUpTask(Quotation $quotation, string $note): CustomerInteraction
    {
        return CustomerInteraction::query()->create([
            'customer_id' => $quotation->customer_id,
            'staff_id' => $quotation->assigned_staff_id,
            'interaction_type' => 'follow_up',
            'subject' => 'Nhắc nhở: ' . $quotation->quotation_code,
            'content' => $note,
            'status' => 'scheduled',
            'interaction_at' => now(),
            'next_follow_up_at' => now()->addDay(),
        ]);
    }
}
