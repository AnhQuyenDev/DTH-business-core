<?php

namespace App\Jobs\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpireQuotationsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $quotations = Quotation::whereDate('valid_until', '<', now())
            ->whereIn('status', [
                QuotationStatus::Approved->value,
                QuotationStatus::Sent->value,
                QuotationStatus::Viewed->value,
            ])->get();

        foreach ($quotations as $quotation) {
            $quotation->update([
                'status' => QuotationStatus::Expired->value,
                'expired_at' => now(),
            ]);

            SendQuotationExpiredNotificationJob::dispatch($quotation);
        }

        Log::info('ExpireQuotationsJob: expired quotations', ['count' => $quotations->count()]);
    }
}
