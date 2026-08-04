<?php

namespace App\Console\Commands\Sales;

use App\Enums\Sales\ApprovalStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationApproval;
use Illuminate\Console\Command;

class BackfillApprovalCancelled extends Command
{
    protected $signature = 'sales:backfill-approval-cancelled';

    protected $description = 'Create a Cancelled approval record for cancelled quotations that have none';

    public function handle(): int
    {
        $quotations = Quotation::withTrashed()
            ->where('status', QuotationStatus::Cancelled->value)
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($quotations as $quotation) {
            $hasCancelled = QuotationApproval::where('quotation_id', $quotation->id)
                ->where('status', ApprovalStatus::Cancelled->value)
                ->exists();

            if ($hasCancelled) {
                $skipped++;
                continue;
            }

            QuotationApproval::create([
                'quotation_id' => $quotation->id,
                'step' => ($quotation->approvals()->max('step') ?? 0) + 1,
                'approver_user_id' => null,
                'approver_role' => null,
                'status' => ApprovalStatus::Cancelled,
                'reason' => 'Backfill lịch sử',
                'requested_at' => $quotation->cancelled_at ?? $quotation->created_at,
                'reviewed_at' => $quotation->cancelled_at ?? $quotation->created_at,
            ]);

            $created++;
        }

        $this->info("Backfilled {$created} cancelled quotation(s); skipped {$skipped} already having history.");

        return self::SUCCESS;
    }
}