<?php

namespace App\Services\Crm;

use App\Enums\Crm\CompanyContactDecisionRole;
use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Support\Facades\DB;

final class CompanyContactLinkService
{
    public function link(
        Company $company,
        Contact $contact,
        ?BusinessContactProfile $profile = null,
        ?LandingPageSubmission $submission = null,
        ?string $jobTitle = null,
        CompanyContactDecisionRole|string $decisionRole = CompanyContactDecisionRole::Other,
    ): void {
        DB::transaction(function () use (
            $company,
            $contact,
            $profile,
            $submission,
            $jobTitle,
            $decisionRole,
        ): void {
            Company::query()
                ->whereKey($company->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $roleValue = $decisionRole instanceof CompanyContactDecisionRole
                ? $decisionRole->value
                : $decisionRole;

            $alreadyLinked = $company->contacts()
                ->where('contacts.id', $contact->getKey())
                ->exists();

            if ($alreadyLinked) {
                $company->contacts()->updateExistingPivot(
                    $contact->getKey(),
                    [
                        'job_title' => $jobTitle,
                        'decision_role' => $roleValue,
                        'is_active' => true,
                        'left_at' => null,
                    ]
                );
            } else {
                $isPrimary = ! $company->contacts()
                    ->wherePivot('is_primary', true)
                    ->wherePivot('is_active', true)
                    ->exists();

                $company->contacts()->attach(
                    $contact->getKey(),
                    [
                        'job_title' => $jobTitle,
                        'department' => null,
                        'decision_role' => $roleValue,
                        'is_primary' => $isPrimary,
                        'is_active' => true,
                        'joined_at' => now()->toDateString(),
                    ]
                );
            }

            if ($profile !== null && $profile->company_id !== $company->id) {
                $profile->update([
                    'company_id' => $company->id,
                ]);
            }

            if (
                $submission !== null
                && $submission->company_id !== $company->id
            ) {
                $submission->update([
                    'company_id' => $company->id,
                ]);
            }
        });
    }
}
