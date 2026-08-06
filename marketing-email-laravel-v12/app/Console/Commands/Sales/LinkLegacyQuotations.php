<?php

namespace App\Console\Commands\Sales;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Company;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Services\Sales\OpportunityCodeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LinkLegacyQuotations extends Command
{
    protected $signature = 'sales:link-legacy-quotations
        {--dry-run : Chỉ thống kê, không ghi dữ liệu}
        {--chunk=200 : Số bản ghi xử lý mỗi chunk}';

    protected $description =
        'Tạo Opportunity legacy và gắn vào Quotation cũ chưa có Opportunity (giữ customer_id)';

    public function handle(OpportunityCodeGenerator $codeGenerator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $quotations = Quotation::query()
            ->with(['customer.contact.businessProfile', 'customer.company'])
            ->whereNull('opportunity_id')
            ->whereNotNull('customer_id')
            ->orderBy('id')
            ->get();

        $stats = [
            'legacy_quotations_without_opportunity' => $quotations->count(),
            'opportunities_created' => 0,
            'quotations_linked' => 0,
        ];

        if ($dryRun) {
            foreach ($quotations as $quotation) {
                $this->line('Quotation #'.$quotation->id.' ('.$quotation->quotation_code.') → stage '.$this->inferStage($quotation)->value);
            }
        } else {
            foreach ($quotations->chunk($chunkSize) as $chunk) {
                foreach ($chunk as $quotation) {
                    $opportunity = DB::transaction(function () use (
                        $quotation,
                        $codeGenerator
                    ): Opportunity {
                        $customer = $quotation->customer;

                        $companyId = $quotation->company_id
                            ?? $customer?->company_id
                            ?? $customer?->contact?->businessProfile?->company_id;

                        if (
                            $companyId !== null
                            && $quotation->payment_status->value === PaymentStatus::Paid->value
                        ) {
                            Company::query()
                                ->whereKey($companyId)
                                ->update([
                                    'lifecycle_stage' => CompanyLifecycleStage::Customer->value,
                                ]);
                        }

                        return Opportunity::query()->create([
                            'opportunity_code' => $codeGenerator->next(),
                            'company_id' => $companyId,
                            'primary_contact_id' => $customer?->contact_id,
                            'assigned_staff_id' => $quotation->assigned_staff_id,
                            'title' => 'Cơ hội legacy cho báo giá '.$quotation->quotation_code,
                            'service_interest' => $quotation->service_interest,
                            'stage' => $this->inferStage($quotation)->value,
                            'estimated_value' => $quotation->grand_total,
                            'probability' => $this->inferStage($quotation)->defaultProbability(),
                        ]);
                    });

                    $quotation->update(['opportunity_id' => $opportunity->id]);
                    $stats['opportunities_created']++;
                    $stats['quotations_linked']++;
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Chỉ số', 'Số lượng'],
            collect($stats)
                ->map(fn (int $value, string $key): array => [$key, $value])
                ->values()
                ->all()
        );

        if ($dryRun) {
            $this->warn('DRY-RUN: không có dữ liệu nào được ghi.');
        } else {
            $this->info('Link legacy quotations hoàn tất.');
        }

        return self::SUCCESS;
    }

    private function inferStage(Quotation $quotation): OpportunityStage
    {
        if ($quotation->payment_status === PaymentStatus::Paid) {
            return OpportunityStage::Won;
        }

        return match ($quotation->status) {
            QuotationStatus::Accepted => OpportunityStage::Negotiation,
            QuotationStatus::Sent, QuotationStatus::Viewed => OpportunityStage::Proposal,
            QuotationStatus::Draft, QuotationStatus::Approved, QuotationStatus::PendingApproval => OpportunityStage::Qualified,
            QuotationStatus::RevisionRequested => OpportunityStage::Proposal,
            default => OpportunityStage::Qualified,
        };
    }
}
