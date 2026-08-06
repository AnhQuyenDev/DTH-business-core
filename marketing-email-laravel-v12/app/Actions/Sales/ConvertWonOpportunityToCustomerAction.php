<?php

namespace App\Actions\Sales;

use App\Actions\Contacts\CustomerCodeGenerator;
use App\Enums\Crm\CompanyLifecycleStage;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\CustomerAssignmentReason;
use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerConversionReason;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Crm\InteractionStatus;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerInteraction;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Crm\ContactQualificationWorkflowService;
use App\Services\Marketing\AuditLogService;
use App\Services\Sales\OpportunityWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConvertWonOpportunityToCustomerAction
{
    public function __construct(
        private readonly CustomerCodeGenerator $codeGenerator,
        private readonly OpportunityWorkflowService $opportunityWorkflow,
        private readonly ContactQualificationWorkflowService $qualificationWorkflow,
        private readonly AuditLogService $auditLog,
    ) {}

    public function execute(
        Opportunity $opportunity,
        Quotation $quotation,
        User $verifiedBy,
    ): Customer {
        return DB::transaction(function () use (
            $opportunity,
            $quotation,
            $verifiedBy,
        ): Customer {
            $lockedOpportunity = Opportunity::query()
                ->with([
                    'lead.qualification',
                    'company.accountOwner',
                    'primaryContact.personalProfile',
                    'primaryContact.businessProfile',
                    'assignedStaff',
                ])
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedQuotation = Quotation::query()
                ->whereKey($quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertValidPaidQuotation(
                $lockedOpportunity,
                $lockedQuotation,
            );

            /*
             * Idempotency cấp Quotation:
             * job/request chạy lại trả về Customer đã gắn.
             */
            if ($lockedQuotation->customer_id !== null) {
                return Customer::query()
                    ->withTrashed()
                    ->findOrFail($lockedQuotation->customer_id);
            }

            $customer = $this->findExistingCustomer(
                $lockedOpportunity
            );

            if ($customer === null) {
                $customer = $this->createCustomer(
                    $lockedOpportunity,
                    $lockedQuotation,
                    $verifiedBy,
                );
            } else {
                if ($customer->trashed()) {
                    $customer->restore();
                }

                $this->refreshExistingCustomer(
                    $customer,
                    $lockedOpportunity,
                    $lockedQuotation,
                    $verifiedBy,
                );
            }

            $lockedQuotation->update([
                'customer_id' => $customer->id,
                'updated_by' => $verifiedBy->id,
            ]);

            $this->convertQualification(
                $lockedOpportunity,
                $customer,
                $verifiedBy,
            );

            $this->markOpportunityWon(
                $lockedOpportunity,
                $verifiedBy,
            );

            $this->markCompanyAsCustomer(
                $lockedOpportunity
            );

            $assignment = $this->ensureCustomerAssignment(
                $customer,
                $lockedOpportunity,
                $verifiedBy,
            );

            $this->ensureConversionInteraction(
                $customer,
                $lockedOpportunity,
                $lockedQuotation,
                $assignment,
            );

            $this->auditLog->log(
                'opportunity.converted_to_customer',
                $customer,
                [],
                [
                    'opportunity_id' => $lockedOpportunity->id,
                    'opportunity_code' => $lockedOpportunity->opportunity_code,
                    'quotation_id' => $lockedQuotation->id,
                    'quotation_code' => $lockedQuotation->quotation_code,
                    'verified_by_user_id' => $verifiedBy->id,
                ],
            );

            return $customer->fresh([
                'company',
                'contact',
                'assignments',
            ]);
        });
    }

    private function assertValidPaidQuotation(
        Opportunity $opportunity,
        Quotation $quotation,
    ): void {
        if ($quotation->opportunity_id !== $opportunity->id) {
            throw ValidationException::withMessages([
                'quotation' => __(
                    'validation.quotation_not_in_opportunity'
                ),
            ]);
        }

        $paymentStatus = $quotation->payment_status?->value
            ?? (string) $quotation->payment_status;

        if ($paymentStatus !== PaymentStatus::Paid->value) {
            throw ValidationException::withMessages([
                'payment_status' => __(
                    'validation.quotation_must_be_paid'
                ),
            ]);
        }
    }

    private function findExistingCustomer(
        Opportunity $opportunity,
    ): ?Customer {
        $query = Customer::query()->withTrashed();

        if ($opportunity->company_id !== null) {
            return $query
                ->where('company_id', $opportunity->company_id)
                ->first();
        }

        return $query
            ->where('contact_id', $opportunity->primary_contact_id)
            ->first();
    }

    private function createCustomer(
        Opportunity $opportunity,
        Quotation $quotation,
        User $verifiedBy,
    ): Customer {
        $contact = $opportunity->primaryContact;
        $personal = $contact?->personalProfile;
        $business = $contact?->businessProfile;
        $company = $opportunity->company;
        $isBusiness = $company !== null;

        return Customer::query()->create([
            'customer_code' => $this->codeGenerator->generate(),
            'contact_id' => $opportunity->primary_contact_id,
            'company_id' => $opportunity->company_id,
            'converted_from_opportunity_id' => $opportunity->id,
            'customer_type' => $isBusiness
                ? 'business'
                : 'personal',
            'display_name' => $company?->legal_name
                ?? $contact?->full_name
                ?? $quotation->party_display_name,
            'email' => $quotation->party_email,
            'normalized_email' => filled($quotation->party_email)
                ? mb_strtolower(trim($quotation->party_email))
                : null,
            'phone' => $quotation->party_phone,
            'normalized_phone' => filled($quotation->party_phone)
                ? preg_replace('/\D+/', '', $quotation->party_phone)
                : null,
            'acquisition_source' => 'paid_opportunity',
            'consent_status' => CustomerConsentStatus::Pending->value,
            'status' => CustomerStatus::Active->value,
            'lifecycle_stage' => CustomerLifecycleStage::NewCustomer->value,
            'conversion_reason' => CustomerConversionReason::Purchased->value,
            'converted_at' => now(),
            'converted_by_staff_id' => $opportunity->assigned_staff_id,
            'first_purchase_at' => now(),
            'latest_purchase_at' => now(),
            'total_revenue' => $quotation->grand_total,
            'priority' => 'normal',

            // Personal snapshot
            'first_name' => $personal?->first_name,
            'last_name' => $personal?->last_name,
            'date_of_birth' => $personal?->date_of_birth,
            'gender' => $personal?->gender,
            'occupation' => $personal?->occupation,

            // Business snapshot
            'company_name' => $company?->legal_name,
            'tax_code' => $company?->tax_code,
            'company_address' => $company?->address,
            'company_province' => $company?->province,
            'legal_representative' => $business?->legal_representative,
            'contact_position' => $business?->contact_position,
            'business_email' => $business?->business_email,
            'business_phone' => $business?->business_phone,
            'industry' => $company?->industry,
            'metadata' => [
                'created_from_payment' => true,
                'quotation_id' => $quotation->id,
                'opportunity_id' => $opportunity->id,
                'verified_by_user_id' => $verifiedBy->id,
            ],
        ]);
    }

    private function refreshExistingCustomer(
        Customer $customer,
        Opportunity $opportunity,
        Quotation $quotation,
        User $verifiedBy,
    ): void {
        $customer->update([
            'company_id' => $customer->company_id
                ?? $opportunity->company_id,
            'converted_from_opportunity_id' => $customer->converted_from_opportunity_id
                ?? $opportunity->id,
            'status' => CustomerStatus::Active->value,
            'lifecycle_stage' => CustomerLifecycleStage::Purchasing->value,
            'conversion_reason' => CustomerConversionReason::Purchased->value,
            'converted_at' => $customer->converted_at ?? now(),
            'converted_by_staff_id' => $customer->converted_by_staff_id
                ?? $opportunity->assigned_staff_id,
            'first_purchase_at' => $customer->first_purchase_at ?? now(),
            'latest_purchase_at' => now(),
            'total_revenue' => (float) $customer->total_revenue
                + (float) $quotation->grand_total,
            'metadata' => array_merge(
                $customer->metadata ?? [],
                [
                    'latest_paid_quotation_id' => $quotation->id,
                    'latest_paid_opportunity_id' => $opportunity->id,
                    'latest_verified_by_user_id' => $verifiedBy->id,
                ],
            ),
        ]);
    }

    private function convertQualification(
        Opportunity $opportunity,
        Customer $customer,
        User $verifiedBy,
    ): void {
        $qualification = $opportunity->lead?->qualification;

        if ($qualification === null) {
            return;
        }

        $current = $qualification->status?->value
            ?? (string) $qualification->status;

        if ($current === ContactQualificationStatus::Converted->value) {
            return;
        }

        $this->qualificationWorkflow->transition(
            qualification: $qualification,
            to: ContactQualificationStatus::Converted,
            data: [
                'converted_customer_id' => $customer->id,
            ],
            actorUserId: $verifiedBy->id,
            fromPaymentService: true,
        );
    }

    private function markOpportunityWon(
        Opportunity $opportunity,
        User $verifiedBy,
    ): void {
        $stage = $opportunity->stage?->value
            ?? (string) $opportunity->stage;

        if ($stage === OpportunityStage::Won->value) {
            return;
        }

        $this->opportunityWorkflow->transition(
            opportunity: $opportunity,
            to: OpportunityStage::Won,
            actorUserId: $verifiedBy->id,
            fromPaymentService: true,
        );
    }

    private function markCompanyAsCustomer(
        Opportunity $opportunity,
    ): void {
        if ($opportunity->company === null) {
            return;
        }

        $opportunity->company->update([
            'lifecycle_stage' => CompanyLifecycleStage::Customer->value,
        ]);
    }

    private function ensureCustomerAssignment(
        Customer $customer,
        Opportunity $opportunity,
        User $verifiedBy,
    ): ?CustomerAssignment {
        $existing = $customer->assignments()
            ->where('assignment_type',
                CustomerAssignmentType::Owner->value)
            ->where('status',
                CustomerAssignmentStatus::Active->value)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $staffId = $opportunity->company?->account_owner_staff_id
            ?? $opportunity->assigned_staff_id;

        if ($staffId === null) {
            return null;
        }

        return CustomerAssignment::query()->create([
            'customer_id' => $customer->id,
            'staff_id' => $staffId,
            'assignment_type' => CustomerAssignmentType::Owner->value,
            'status' => CustomerAssignmentStatus::Active->value,
            'starts_at' => now(),
            'assigned_by_user_id' => $verifiedBy->id,
            'reason' => CustomerAssignmentReason::NewCustomer->value,
            'note' => 'Tạo từ Opportunity đã thanh toán',
        ]);
    }

    private function ensureConversionInteraction(
        Customer $customer,
        Opportunity $opportunity,
        Quotation $quotation,
        ?CustomerAssignment $assignment,
    ): void {
        CustomerInteraction::query()->firstOrCreate(
            [
                'customer_id' => $customer->id,
                'interaction_type' => 'system',
                'subject' => 'Chuyển thành khách hàng từ thanh toán',
            ],
            [
                'staff_id' => $assignment?->staff_id
                    ?? $opportunity->assigned_staff_id,
                'customer_assignment_id' => $assignment?->id,
                'content' => sprintf(
                    'Thanh toán báo giá %s của cơ hội %s đã được xác nhận.',
                    $quotation->quotation_code,
                    $opportunity->opportunity_code,
                ),
                'outcome' => 'paid_conversion',
                'status' => InteractionStatus::Completed->value,
                'interaction_at' => now(),
                'is_support_action' => false,
            ],
        );
    }
}
