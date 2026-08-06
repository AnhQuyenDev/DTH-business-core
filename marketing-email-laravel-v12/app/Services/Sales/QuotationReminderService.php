<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuotationReminderService
{
    public function __construct(
        private readonly QuotationInteractionService $interactions,
    ) {}

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

    public function createFollowUpTask(Quotation $quotation, string $note): Model
    {
        return $this->interactions->logFollowUpScheduled(
            $quotation,
            $note,
        );
    }
}
