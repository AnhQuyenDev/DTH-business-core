<?php

namespace App\Services\Crm;

use App\Models\Crm\BusinessContactProfile;

final class CompanyTaxVerificationSyncService
{
    public function syncFromProfile(
        BusinessContactProfile $profile
    ): void {
        $profile->loadMissing('company');

        if ($profile->company === null) {
            return;
        }

        $metadata = $profile->company->metadata ?? [];

        $metadata['tax_verification'] = [
            'status' => $profile->tax_verification_status instanceof \BackedEnum
                ? $profile->tax_verification_status->value
                : $profile->tax_verification_status,
            'tax_code' => $profile->tax_code,
            'verified_at' => $profile->tax_verified_at?->toISOString(),
            'data' => $profile->tax_verification_data ?? null,
            'synced_from_profile_id' => $profile->id,
            'synced_at' => now()->toISOString(),
        ];

        $profile->company->update([
            'metadata' => $metadata,
        ]);
    }
}
