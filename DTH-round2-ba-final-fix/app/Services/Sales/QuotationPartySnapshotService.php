<?php

namespace App\Services\Sales;

use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;

final class QuotationPartySnapshotService
{
    public function customerSnapshot(
        Opportunity $opportunity
    ): array {
        $opportunity->loadMissing([
            'primaryContact.personalProfile',
            'primaryContact.businessProfile',
            'company',
        ]);

        $contact = $opportunity->primaryContact;
        $isBusiness = $opportunity->company_id !== null;

        return [
            'id' => null,
            'display_name' => $isBusiness
                ? ($opportunity->company?->legal_name
                    ?? $contact?->full_name)
                : $contact?->full_name,
            'contact_name' => $contact?->full_name,
            'customer_type' => $isBusiness
                ? 'business'
                : 'personal',
            'email' => $this->email($contact),
            'phone' => $this->phone($contact),
            'first_name' => $contact?->personalProfile?->first_name,
            'last_name' => $contact?->personalProfile?->last_name,
            'contact_id' => $contact?->id,
            'company_id' => $opportunity->company_id,
            'source' => 'opportunity',
        ];
    }

    public function companySnapshot(
        Opportunity $opportunity
    ): ?array {
        if ($opportunity->company_id === null) {
            return null;
        }

        $opportunity->loadMissing([
            'company',
            'primaryContact.businessProfile',
        ]);

        $company = $opportunity->company;
        $profile = $opportunity->primaryContact?->businessProfile;

        return [
            'company_id' => $company?->id,
            'company_name' => $company?->legal_name,
            'tax_code' => $company?->tax_code,
            'company_address' => $company?->address,
            'legal_representative' => $opportunity->primaryContact?->full_name,
            'contact_position' => $profile?->contact_position,
            'business_email' => $profile?->business_email,
            'business_phone' => $profile?->business_phone,
            'industry' => $company?->industry,
            'website' => $company?->website,
        ];
    }

    private function email(?Contact $contact): ?string
    {
        return $contact?->businessProfile?->business_email
            ?? $contact?->personalProfile?->email;
    }

    private function phone(?Contact $contact): ?string
    {
        return $contact?->businessProfile?->business_phone
            ?? $contact?->personalProfile?->phone;
    }
}
