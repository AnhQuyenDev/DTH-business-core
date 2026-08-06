<?php

namespace App\Services\Sales;

use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Sales\OpportunityStage;
use App\Models\Crm\Lead;
use App\Models\Sales\Opportunity;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpportunityCreationService
{
    public function __construct(
        private readonly OpportunityCodeGenerator $codeGenerator,
    ) {}

    public function createFromQualifiedLead(
        Lead $lead,
        array $data,
        int $createdByUserId,
    ): Opportunity {
        $lead->loadMissing('qualification', 'contact', 'company');

        $qualificationStatus = $lead->qualification?->status;

        $statusValue = $qualificationStatus instanceof \BackedEnum
            ? $qualificationStatus->value
            : (string) $qualificationStatus;

        if ($statusValue !== 'qualified') {
            throw ValidationException::withMessages([
                'lead' => __('validation.opportunity_requires_qualified_lead'),
            ]);
        }

        return DB::transaction(function () use (
            $lead,
            $data,
            $createdByUserId,
        ): Opportunity {
            $opportunity = Opportunity::query()->firstOrCreate(
                ['lead_id' => $lead->id],
                [
                    'opportunity_code' => $this->codeGenerator->next(),
                    'company_id' => $lead->company_id,
                    'primary_contact_id' => $lead->contact_id,
                    'assigned_staff_id' => $data['assigned_staff_id']
                        ?? $lead->assigned_staff_id,
                    'title' => $data['title'] ?? $lead->title,
                    'service_interest' => $data['service_interest']
                        ?? $lead->service_interest,
                    'stage' => OpportunityStage::Qualified->value,
                    'estimated_value' => $data['estimated_value']
                        ?? $lead->estimated_value,
                    'probability' => $data['probability'] ?? 50,
                    'expected_close_date' => $data['expected_close_date'] ?? null,
                    'created_by' => $createdByUserId,
                ]
            );

            if ($opportunity->wasRecentlyCreated) {
                $opportunity->contacts()->syncWithoutDetaching([
                    $lead->contact_id => [
                        'role' => 'primary_contact',
                        'is_primary' => true,
                    ],
                ]);

                $lead->update([
                    'intake_status' => LeadIntakeStatus::ConvertedToOpportunity->value,
                    'converted_to_opportunity_at' => now(),
                ]);
            }

            return $opportunity->fresh([
                'lead',
                'company',
                'primaryContact',
                'assignedStaff',
                'contacts',
            ]);
        });
    }
}
