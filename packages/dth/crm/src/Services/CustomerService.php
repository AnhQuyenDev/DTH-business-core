<?php

namespace Dth\Crm\Services;

use Dth\Crm\Models\Customer;
use Dth\Crm\Models\CustomerAssignment;
use Dth\Crm\Models\Lead;
use Dth\Crm\Support\CodeGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CustomerService
{
    public function __construct(private CodeGenerator $codes) {}

    public function convertFromPaidSales(
        Lead $lead,
        string $opportunityReference,
        float $revenue,
        ?string $quotationReference = null,
        ?int $agentProfileId = null,
    ): Customer {
        return DB::transaction(function () use ($lead, $opportunityReference, $revenue, $quotationReference, $agentProfileId): Customer {
            $existing = Customer::query()->where('origin_lead_id', $lead->id)->first();

            if ($existing) {
                $existing->update([
                    'status' => 'active',
                    'latest_purchase_at' => now(),
                    'first_purchase_at' => $existing->first_purchase_at ?? now(),
                    'total_revenue' => (float) $existing->total_revenue + $revenue,
                    'metadata' => array_merge($existing->metadata ?? [], [
                        'latest_sales_opportunity_reference' => $opportunityReference,
                        'latest_quotation_reference' => $quotationReference,
                    ]),
                ]);

                return $existing;
            }

            $contact = $lead->contact;
            $company = $lead->company;
            $ownerProfileId = $agentProfileId ?? $lead->assigned_agent_profile_id;

            $customer = Customer::create([
                'customer_code' => $this->codes->make('CUS'),
                'contact_id' => $lead->contact_id,
                'company_id' => $lead->company_id,
                'origin_lead_id' => $lead->id,
                'customer_type' => $company ? 'business' : 'personal',
                'display_name' => $company?->legal_name ?? $contact?->display_name ?? $lead->title ?? $lead->lead_code,
                'email' => $contact?->email,
                'normalized_email' => $contact?->normalized_email,
                'phone' => $contact?->phone,
                'normalized_phone' => $contact?->normalized_phone,
                'acquisition_source' => $lead->source,
                'status' => 'active',
                'lifecycle_stage' => 'purchasing',
                'conversion_reason' => 'purchased',
                'converted_at' => now(),
                'converted_by_agent_profile_id' => $ownerProfileId,
                'first_purchase_at' => now(),
                'latest_purchase_at' => now(),
                'total_revenue' => $revenue,
                'metadata' => [
                    'lead_code' => $lead->lead_code,
                    'sales_opportunity_reference' => $opportunityReference,
                    'quotation_reference' => $quotationReference,
                    'attribution' => $lead->attribution ?? [],
                ],
            ]);

            if ($ownerProfileId) {
                CustomerAssignment::create([
                    'customer_id' => $customer->id,
                    'agent_profile_id' => $ownerProfileId,
                    'assignment_type' => 'owner',
                    'status' => 'active',
                    'reason' => 'new_customer',
                    'starts_at' => now(),
                ]);
            }

            $lead->qualification?->update([
                'status' => 'converted',
                'converted_at' => now(),
            ]);

            $lead->update([
                'sales_opportunity_reference' => $opportunityReference,
                'converted_to_opportunity_at' => $lead->converted_to_opportunity_at ?? now(),
                'intake_status' => 'converted_to_opportunity',
            ]);

            if ($company) {
                $company->update(['lifecycle_stage' => 'customer']);
            }

            return $customer;
        });
    }

    public function convertLead(
        Lead $lead,
        string $reason = 'confirmed_need',
        ?int $agentProfileId = null,
    ): Customer {
        if (config('dth-crm.customer_on_paid_only', true)) {
            throw ValidationException::withMessages([
                'lead' => 'Khách hàng chỉ được tạo sau khi bộ phận Sales/Finance xác nhận giao dịch đã thanh toán.',
            ]);
        }

        return $this->convertFromPaidSales($lead, 'manual', 0, null, $agentProfileId);
    }
}
