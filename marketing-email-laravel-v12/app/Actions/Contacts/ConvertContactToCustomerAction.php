<?php

namespace App\Actions\Contacts;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\CustomerAssignmentReason;
use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerConversionReason;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Crm\QualificationResult;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class ConvertContactToCustomerAction
{
    public function __construct(
        private AuditLogService $auditLog,
        private CustomerCodeGenerator $codeGenerator,
    ) {}

    public function execute(
        Contact $contact,
        Staff $convertedBy,
        ?Staff $assignToStaff = null,
    ): Customer {
        $qualification = $contact->qualification;

        if (! $qualification || ! $qualification->isConvertible()) {
            throw new \RuntimeException('Contact is not eligible for conversion.');
        }

        return DB::transaction(function () use ($contact, $convertedBy, $assignToStaff, $qualification) {
            $contact->lockForUpdate();
            $qualification->lockForUpdate();

            if ($qualification->converted_customer_id) {
                $converted = Customer::withTrashed()->find($qualification->converted_customer_id);
                if ($converted) {
                    return $converted;
                }
            }

            $isPurchased = $qualification->qualification_result === QualificationResult::Purchased;

            $profile = $contact->personalProfile;
            $business = $contact->businessProfile;

            $data = [
                'customer_type' => $contact->contact_type?->value ?? 'personal',
                'display_name' => $contact->full_name ?: $contact->email,
                'first_name' => $profile?->first_name,
                'last_name' => $profile?->last_name,
                'date_of_birth' => $profile?->date_of_birth,
                'gender' => $profile?->gender,
                'customer_ward' => $profile?->ward,
                'customer_district' => $profile?->district,
                'customer_province' => $profile?->province,
                'customer_country' => $profile?->country,
                'occupation' => $profile?->occupation,
                'company_name' => $business?->company_name,
                'tax_code' => $business?->tax_code,
                'company_address' => $business?->company_address,
                'company_ward' => $business?->company_ward,
                'company_province' => $business?->company_province,
                'legal_representative' => $business?->legal_representative,
                'contact_position' => $business?->contact_position,
                'business_email' => $business?->business_email,
                'business_phone' => $business?->business_phone,
                'industry' => $business?->industry,
                'email' => $contact->email,
                'normalized_email' => strtolower(trim($contact->email)),
                'phone' => $contact->phone,
                'normalized_phone' => $contact->phone ? preg_replace('/[^0-9]/', '', $contact->phone) : null,
                'consent_status' => CustomerConsentStatus::Subscribed,
                'subscribed_at' => now(),
                'status' => $isPurchased ? CustomerStatus::Active : CustomerStatus::Potential,
                'lifecycle_stage' => $isPurchased ? 'retained' : 'new_customer',
                'conversion_reason' => $isPurchased
                    ? CustomerConversionReason::Purchased->value
                    : CustomerConversionReason::ConfirmedNeed->value,
                'converted_at' => now(),
                'converted_by_staff_id' => $convertedBy->id,
                'acquisition_source' => $contact->landingPageSubmissions()
                    ->latest('submitted_at')
                    ->value('utm_source'),
                'first_purchase_at' => $isPurchased ? now() : null,
                'latest_purchase_at' => $isPurchased ? now() : null,
            ];

            $existingTrashed = Customer::withTrashed()
                ->where('contact_id', $contact->id)
                ->first();

            if ($existingTrashed?->trashed()) {
                $existingTrashed->restore();
                $customer = $existingTrashed;
                $customer->update($data);
            } else {
                $customer = Customer::create([
                    'customer_code' => $this->codeGenerator->generate(),
                    'contact_id' => $contact->id,
                    ...$data,
                ]);
            }

            $assignStaff = $assignToStaff ?? $this->resolveAutoAssignStaff($convertedBy);

            if ($assignStaff) {
                CustomerAssignment::create([
                    'customer_id' => $customer->id,
                    'staff_id' => $assignStaff->id,
                    'assignment_type' => CustomerAssignmentType::Owner->value,
                    'status' => CustomerAssignmentStatus::Active->value,
                    'starts_at' => now(),
                    'assigned_by_user_id' => $convertedBy->user_id,
                    'reason' => CustomerAssignmentReason::NewCustomer->value,
                ]);
            }

            $qualification->update([
                'status' => ContactQualificationStatus::Converted->value,
                'converted_at' => now(),
                'converted_customer_id' => $customer->id,
            ]);

            $this->auditLog->log('contact.converted_to_customer', $contact, [
                'customer_id' => $contact->id,
            ], [
                'customer_id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'converted_by' => $convertedBy->id,
            ]);

            return $customer;
        });
    }

    private function resolveAutoAssignStaff(Staff $convertedBy): ?Staff
    {
        $eligible = Staff::query()
            ->where('employment_status', 'active')
            ->where('can_receive_customers', true)
            ->whereDoesntHave('availabilities', function ($q) {
                $q->active()->where('can_receive_new_customers', false);
            })
            ->get()
            ->filter(fn (Staff $s) => $s->hasCapacity())
            ->sortBy(fn (Staff $s) => $s->currentLoad())
            ->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        $minLoad = $eligible->first()->currentLoad();
        $candidates = $eligible->filter(fn (Staff $s) => $s->currentLoad() === $minLoad);

        return $candidates->random();
    }
}
