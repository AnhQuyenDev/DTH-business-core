<?php

namespace App\Console\Commands\Finance;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Models\Finance\Payment;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Finance\PaymentLedgerService;
use App\Services\Finance\PaymentReceiptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillVerifiedPayments extends Command
{
    protected $signature = 'finance:backfill-payments {--receipt=1 : Generate missing receipt PDFs}';

    protected $description = 'Backfill immutable payment ledger, revenue attribution and receipts for existing Paid quotations.';

    public function handle(
        PaymentLedgerService $ledger,
        PaymentReceiptService $receipts,
    ): int {
        $count = 0;
        $skipped = 0;

        Quotation::query()
            ->where('payment_status', PaymentStatus::Paid->value)
            ->orderBy('id')
            ->chunkById(100, function ($quotations) use ($ledger, $receipts, &$count, &$skipped): void {
                foreach ($quotations as $quotation) {
                    $existingPayment = Payment::query()
                        ->with('receipt')
                        ->where('quotation_id', $quotation->id)
                        ->first();

                    if ($existingPayment !== null) {
                        if ((bool) $this->option('receipt')) {
                            $receipts->ensureGenerated($existingPayment);
                        }

                        $skipped++;
                        $this->line("Existing {$quotation->quotation_code} -> {$existingPayment->payment_code}; receipt checked.");
                        continue;
                    }

                    $verifiedBy = $quotation->payment_verified_by_user_id
                        ? User::query()->find($quotation->payment_verified_by_user_id)
                        : null;

                    if ($verifiedBy === null) {
                        $this->warn("Skip {$quotation->quotation_code}: missing payment verifier.");
                        $skipped++;
                        continue;
                    }

                    $notice = $quotation->paymentNotices()
                        ->where('status', PaymentNoticeStatus::Verified->value)
                        ->latest('id')
                        ->first();

                    $payment = DB::transaction(function () use ($ledger, $quotation, $notice, $verifiedBy) {
                        $fresh = Quotation::query()
                            ->with(['items', 'opportunity.lead.submission.landingPage.marketingCampaign'])
                            ->whereKey($quotation->id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        $payment = $ledger->recordVerifiedPayment($fresh, $notice, $verifiedBy);
                        $ledger->attachCustomer($payment, $fresh->customer_id);

                        return $payment->fresh();
                    });

                    if ((bool) $this->option('receipt')) {
                        $receipts->ensureGenerated($payment);
                    }

                    $count++;
                    $this->line("Backfilled {$quotation->quotation_code} -> {$payment->payment_code}");
                }
            });

        $this->info("Done. Created: {$count}; skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
