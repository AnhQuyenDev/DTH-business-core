<?php

namespace App\Console\Commands\Sales;

use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Services\Sales\OpportunityCodeGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillOpportunities extends Command
{
    protected $signature = 'sales:backfill-opportunities
        {--dry-run : Chỉ thống kê, không ghi dữ liệu}
        {--chunk=200 : Số bản ghi xử lý mỗi chunk}';

    protected $description =
        'Backfill Opportunity từ Qualification qualified và Quotation legacy đã thanh toán';

    public function handle(OpportunityCodeGenerator $codeGenerator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $stats = [
            'qualified_qualifications_without_opportunity' => 0,
            'opportunities_from_qualification' => 0,
            'legacy_customers_with_quotation_without_opportunity' => 0,
            'opportunities_linked_to_quotation' => 0,
        ];

        /*
         * 1. Qualification đủ điều kiện (confirmed_need) chưa có Opportunity.
         */
        $qualifiedIds = ContactQualification::query()
            ->where('status', ContactQualificationStatus::Qualified->value)
            ->where('qualification_result', QualificationResult::ConfirmedNeed->value)
            ->whereDoesntHave('lead.opportunity')
            ->orderBy('id')
            ->pluck('id');

        $stats['qualified_qualifications_without_opportunity'] = $qualifiedIds->count();

        if (! $dryRun) {
            foreach ($qualifiedIds->chunk($chunkSize) as $ids) {
                ContactQualification::query()
                    ->whereKey($ids)
                    ->with(['lead.company', 'lead.contact.businessProfile', 'assignedStaff'])
                    ->orderBy('id')
                    ->chunkById($chunkSize, function ($qualifications) use (
                        $codeGenerator,
                        &$stats
                    ): void {
                        foreach ($qualifications as $qualification) {
                            $lead = $qualification->lead;

                            if ($lead === null) {
                                continue;
                            }

                            Opportunity::query()->firstOrCreate(
                                ['lead_id' => $lead->id],
                                DB::transaction(function () use (
                                    $lead,
                                    $qualification,
                                    $codeGenerator
                                ): array {
                                    $company = $lead->company;
                                    $contact = $lead->contact;
                                    $businessProfile = $contact?->businessProfile;

                                    $companyId = $company?->id
                                        ?? $businessProfile?->company_id
                                        ?? $lead->company_id;

                                    if (
                                        $companyId !== null
                                        && $company?->lifecycle_stage?->value
                                            !== CompanyLifecycleStage::Customer->value
                                    ) {
                                        Company::query()
                                            ->whereKey($companyId)
                                            ->update([
                                                'lifecycle_stage' => CompanyLifecycleStage::Qualified->value,
                                            ]);
                                    }

                                    return [
                                        'opportunity_code' => $codeGenerator->next(),
                                        'lead_id' => $lead->id,
                                        'company_id' => $companyId,
                                        'primary_contact_id' => $lead->contact_id,
                                        'assigned_staff_id' => $qualification->assigned_staff_id
                                            ?? $lead->assigned_staff_id,
                                        'title' => 'Cơ hội từ Lead '.$lead->lead_code,
                                        'service_interest' => $qualification->service_interest,
                                        'stage' => OpportunityStage::Qualified->value,
                                        'estimated_value' => $qualification->estimated_value,
                                        'probability' => OpportunityStage::Qualified->defaultProbability(),
                                    ];
                                })
                            );

                            $stats['opportunities_from_qualification']++;
                        }
                    });
            }
        }

        /*
         * 2. Customer cũ có Quotation nhưng chưa có Opportunity.
         * Tạo Opportunity legacy và gắn Quotation (sales:link-legacy-quotations
         * sẽ xử lý chi tiết stage mapping; ở đây tạo skeleton để tránh missing FK).
         */
        $legacyOpportunityPairs = Quotation::query()
            ->whereNull('opportunity_id')
            ->whereNotNull('customer_id')
            ->get([
                'id',
                'customer_id',
                'assigned_staff_id',
                'payment_status',
                'grand_total',
            ]);

        $processedCustomerIds = [];

        foreach ($legacyOpportunityPairs as $quotation) {
            $customerId = $quotation->customer_id;

            if (isset($processedCustomerIds[$customerId])) {
                continue;
            }

            $customer = Customer::query()
                ->with(['company', 'contact.businessProfile', 'contact.personalProfile'])
                ->find($customerId);

            if ($customer === null) {
                continue;
            }

            $processedCustomerIds[$customerId] = true;
            $stats['legacy_customers_with_quotation_without_opportunity']++;

            $companyId = $customer->company_id
                ?? $customer->contact?->businessProfile?->company_id;

            $stage = $quotation->payment_status?->value === PaymentStatus::Paid->value
                ? OpportunityStage::Won
                : OpportunityStage::Qualified;

            if (! $dryRun) {
                DB::transaction(function () use (
                    $customer,
                    $companyId,
                    $quotation,
                    $codeGenerator,
                    $stage
                ): void {
                    $opportunity = Opportunity::query()->create([
                        'opportunity_code' => $codeGenerator->next(),
                        'company_id' => $companyId,
                        'primary_contact_id' => $customer->contact_id,
                        'assigned_staff_id' => $quotation->assigned_staff_id,
                        'title' => 'Cơ hội legacy cho Khách hàng '.$customer->display_name,
                        'service_interest' => null,
                        'stage' => $stage->value,
                        'estimated_value' => $quotation->grand_total,
                        'probability' => $stage->defaultProbability(),
                    ]);

                    Quotation::query()
                        ->whereKey($quotation->id)
                        ->update([
                            'opportunity_id' => $opportunity->id,
                        ]);

                    if (
                        $companyId !== null
                        && $stage === OpportunityStage::Won
                    ) {
                        Company::query()
                            ->whereKey($companyId)
                            ->update([
                                'lifecycle_stage' =>
                                    CompanyLifecycleStage::Customer->value,
                            ]);
                    }
                });

                $stats['opportunities_linked_to_quotation']++;
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
            $this->info('Backfill Opportunity hoàn tất.');
        }

        return self::SUCCESS;
    }
}
