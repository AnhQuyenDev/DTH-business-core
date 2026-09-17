<?php

namespace Dth\Commercial\Integrations\Crm;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Services\OpportunityCodeGenerator;
use Dth\Crm\Contracts\SalesHandoffProvider;
use Dth\Crm\DTO\QualifiedLeadData;
use Illuminate\Support\Facades\DB;

final class DthCrmSalesHandoffProvider implements SalesHandoffProvider
{
    public function __construct(private readonly OpportunityCodeGenerator $codes) {}

    public function available(): bool
    {
        return (bool) config('dth-commercial.enabled', true)
            && (bool) config('dth-commercial.features.opportunities', true);
    }

    public function handoff(QualifiedLeadData $lead): ?string
    {
        if (! $this->available()) {
            return null;
        }

        return DB::transaction(function () use ($lead): string {
            $existing = Opportunity::query()->where('lead_reference', $lead->leadReference)->first();
            if ($existing) {
                return (string) $existing->opportunity_code;
            }

            $crmLead = null;
            $leadModel = 'Dth\\Crm\\Models\\Lead';
            if (class_exists($leadModel) && ctype_digit($lead->leadReference)) {
                $crmLead = $leadModel::query()
                    ->with(['contact', 'company', 'assignedAgentProfile.employee'])
                    ->find((int) $lead->leadReference);
            }

            $service = null;
            if (filled($lead->serviceReference)) {
                $service = Service::query()
                    ->where('slug', $lead->serviceReference)
                    ->orWhere('service_code', $lead->serviceReference)
                    ->first();
            }

            $serviceReference = $service?->reference() ?? $lead->serviceReference;
            $serviceName = $service?->name
                ?? $crmLead?->service_interest
                ?? $serviceReference;

            $opportunity = Opportunity::query()->create([
                'opportunity_code' => $this->codes->next(),
                'lead_reference' => $lead->leadReference,
                'lead_code_snapshot' => $lead->leadCode,
                'contact_reference' => $lead->contactReference,
                'contact_name_snapshot' => $crmLead?->contact?->display_name,
                'company_reference' => $lead->companyReference,
                'company_name_snapshot' => $crmLead?->company?->legal_name,
                'assigned_employee_reference' => $crmLead?->assignedAgentProfile?->employee_id
                    ? (string) $crmLead->assignedAgentProfile->employee_id
                    : null,
                'assigned_employee_name_snapshot' => $crmLead?->assignedAgentProfile?->employee?->full_name,
                'service_id' => $service?->getKey(),
                'service_reference' => $serviceReference,
                'service_name_snapshot' => $serviceName,
                'title' => $crmLead?->title
                    ?: trim(implode(' · ', array_filter([$lead->leadCode, $serviceName])))
                    ?: $lead->leadCode,
                'stage' => OpportunityStage::Qualified->value,
                'estimated_value' => $lead->estimatedValue,
                'probability' => OpportunityStage::Qualified->probability(),
                'metadata' => array_merge($lead->metadata, [
                    'handoff_source' => 'dth-crm',
                    'service_interest_snapshot' => $crmLead?->service_interest,
                ]),
            ]);

            return (string) $opportunity->opportunity_code;
        });
    }
}
