<?php

namespace App\Console\Commands\Sales;

use App\Models\Sales\BankAccount;
use App\Models\Sales\Quotation;
use Illuminate\Console\Command;

class BackfillQuotationPaymentSnapshots extends Command
{
    protected $signature = 'sales:backfill-quotation-payment-snapshots
        {--quotation= : Only backfill a specific quotation code}';

    protected $description = 'Attach the active default bank account to unsent quotations that do not yet have a payment snapshot';

    public function handle(): int
    {
        $bank = BankAccount::query()
            ->where('is_default', true)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();

        if ($bank === null) {
            $this->error(
                'No active default bank account exists. Configure one in Sales > Bank Accounts first.'
            );

            return self::FAILURE;
        }

        $query = Quotation::query()
            ->whereNull('sent_at')
            ->whereIn('status', [
                'draft',
                'pending_approval',
                'approved',
            ])
            ->where(function ($query): void {
                $query
                    ->whereNull('bank_account_id')
                    ->orWhereNull('payment_snapshot');
            });

        if (filled($this->option('quotation'))) {
            $query->where(
                'quotation_code',
                (string) $this->option('quotation')
            );
        }

        $updated = 0;

        $query->orderBy('id')->chunkById(100, function ($quotations) use (
            $bank,
            &$updated,
        ): void {
            foreach ($quotations as $quotation) {
                $transferContent = $quotation->quotation_code;

                $quotation->forceFill([
                    'bank_account_id' => $bank->id,
                    'payment_snapshot' => [
                        'bank_account_id' => $bank->id,
                        'bank_code' => $bank->bank_code,
                        'bank_name' => $bank->bank_name,
                        'account_number' => $bank->account_number,
                        'account_name' => $bank->account_name,
                        'branch_name' => $bank->branch_name,
                        'swift_code' => $bank->swift_code,
                        'qr_provider' => $bank->qr_provider,
                        'qr_template' => $bank->qr_template,
                        'transfer_content' => $transferContent,
                    ],
                ])->save();

                $updated++;
            }
        });

        $this->info("Updated {$updated} quotation payment snapshot(s).");

        return self::SUCCESS;
    }
}
